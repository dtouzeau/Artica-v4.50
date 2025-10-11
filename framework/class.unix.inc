<?php

//SP139
ini_set("memory_limit", "1024M");
if(!defined("PROGRESS_DIR")){ define("PROGRESS_DIR","/usr/share/artica-postfix/ressources/logs/web");}
if(!defined("UPLOAD_DIR")){   define("UPLOAD_DIR","/usr/share/artica-postfix/ressources/conf/upload");}
if(!defined("SUPPORT_API")){define("SUPPORT_API",bin2hex("ek51MUJ3MFdXTE81TmpsT0FvMUR5TFAzRk1ZeGpXNkxVZmUvZ090dktrWXYxdUN1NDVZQlNwNVdISU1HNHpEOQ=="));}

xcgroups();
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
if(!isset($GLOBALS["DEBUG"])){$GLOBALS["DEBUG"]=false;}
if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
if(!isset($GLOBALS["OUTPUT"])){$GLOBALS["OUTPUT"]=false;}
if(!isset($GLOBALS["MAINCD"])){if(isset($argv)){if(is_array($argv)){$GLOBALS["MAINCMD"]=@implode(" ",$argv);}}}
include_once(dirname(__FILE__).'/class.ini-frame.inc');
include_once(dirname(__FILE__).'/class.cgroups.inc');
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir");}
if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; }

include_once("/usr/share/artica-postfix/ressources/class.ini.inc");
include_once("/usr/share/artica-postfix/ressources/class.process.inc");
include_once("/usr/share/artica-postfix/ressources/class.os.system.inc");
include_once("/usr/share/artica-postfix/ressources/class.squid.manager.inc");
include_once("/usr/share/artica-postfix/framework/class.monit.inc");
include_once(dirname(__FILE__)."/class.hd.inc");
$GLOBALS["SSLKEY_PATH"]="/etc/ssl/certs/apache";
$GLOBALS["MAX_RECUSIVE_DIRS"]=400;
$GLOBALS["RECURSVIVE_INC"]=0;
if(!isset($GLOBALS["DEBUG_GREP"])){$GLOBALS["DEBUG_GREP"]=false;}
if(!defined("RE_IP_BYTE")){define( 'RE_IP_BYTE', '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])');}
if(!defined("RE_IP_ADD")){define( 'RE_IP_ADD' , RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE );}
if(!defined("RE_IP_PREFIX")){define( 'RE_IP_PREFIX', '(3[0-2]|[12]?\d)');}
if(!defined("RE_IP_BLOCK")){define( 'RE_IP_BLOCK', RE_IP_ADD . '\/' . RE_IP_PREFIX);}
if(!defined("RE_IPV6_WORD")){define( 'RE_IPV6_WORD', '([0-9A-Fa-f]{1,4})' );}
if(!defined("RE_IPV6_GAP")){define( 'RE_IPV6_GAP', ':(?:0+:)*(?::(?:0+:)*)?' );}
if(!defined("RE_IPV6_V4_PREFIX")){define( 'RE_IPV6_V4_PREFIX', '0*' . RE_IPV6_GAP . '(?:ffff:)?' );}
if(!defined("RE_IPV6_PREFIX")){define( 'RE_IPV6_PREFIX', '(12[0-8]|1[01][0-9]|[1-9]?\d)');}
if(!defined("RE_IPV6_ADD")){define( 'RE_IPV6_ADD', '(:(:' . RE_IPV6_WORD . '){1,7}|' . RE_IPV6_WORD . '(:{1,2}' . RE_IPV6_WORD . '|::$){1,7})' );}
if(!defined("RE_IPV6_BLOCK")){define( 'RE_IPV6_BLOCK', RE_IPV6_ADD . '\/' . RE_IPV6_PREFIX );}
if(!defined("IP_ADDRESS_STRING")){define( 'IP_ADDRESS_STRING','(?:' .RE_IP_ADD . '(\/' . RE_IP_PREFIX . '|)'.'|'.RE_IPV6_ADD . '(\/' . RE_IPV6_PREFIX . '|)' .')' );}


function xcgroups($pid=0){
    if(!isset($GLOBALS["YESCGROUP"])){return false;}
    if(isset($GLOBALS["NOCGROUP"])){if($GLOBALS["NOCGROUP"]){return false;}}
    if(!is_dir("/cgroup/blkio/php")){ return false; }
    if(!is_file("/usr/bin/cgclassify")){return false;}
    $unix=new unix();
    if(!isset($GLOBALS["CLASS_SOCKETS"])){
        if(!class_exists("sockets")){
            include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
        }
            $GLOBALS["CLASS_SOCKETS"]=new sockets();
    }

    $cgroupsPHPNonPtime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsPHPNonPtime"));

    if($cgroupsPHPNonPtime==1){
        if(!$unix->IsProductionTime()){return false;}
    }
    shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php $pid");
    return true;
}

function UNIX_RESTART_CRON():bool{
    $unix=new unix();
    $unix->go_exec("/etc/init.d/cron restart");

   return true;
}



class unix {
    public $mysql_error=null;
    public $proc_status;
    public $MemoryOfvalue=null;
    public $CountOfZombies=0;

    function __construct(){
        $getLocalTimezone=$this->getLocalTimezone();
        @date_default_timezone_set($getLocalTimezone);

    }

    function ReplicatePHPFMPService():bool{
        $srcfile="/usr/share/artica-postfix/bin/articarest";
        $dstfile="/usr/sbin/artica-phpfpm-service";
        $md51=md5_file($srcfile);
        $md52=md5_file($dstfile);
        if($md51==$md52){
            @chmod($dstfile,0755);
            return true;
        }
        if(is_file($dstfile)) {
            @unlink($dstfile);
        }
        @copy($srcfile,$dstfile);
        @chmod($dstfile,0755);
        return true;
    }

    private function go_exec_syslog($text):bool{
        if(!function_exists("openlog")){return false;}
        openlog("go-exec", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, "[PHP] $text");
        closelog();
        return true;
    }
    public function ArticaVersionString():string{
        $VERSION=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
        $SP=$this->ArticaVersionSP();
        if($SP>0){
            return "$VERSION Service Pack $SP";
        }
        return $VERSION;
    }

    public function ArticaVersionSP():int{
      $VERSION=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
      if (!is_dir("/usr/share/artica-postfix/SP")) {
        @mkdir("/usr/share/artica-postfix/SP");
        return 0;
      }
      if (!is_file("/usr/share/artica-postfix/SP/$VERSION")) {
        return 0;
      }
      $SP=intval(@file_get_contents("/usr/share/artica-postfix/SP/$VERSION"));
      return $SP;

    }

    public function go_exec_out($cmd):array{
        $Masterbin = "/bin/go-exec";
        $this->go_exec_syslog($cmd);
        if (!is_file($Masterbin)) {
            $this->go_exec_syslog("go_exec_out() GO Exec bin not found, use fallback");
            exec("$cmd",$resp);
            return $resp;
        }
        $pid = $this->go_exec_pid();
        if (!$this->process_exists($pid)) {
            $this->go_exec_syslog("go_exec_out() GO Exec not running, use fallback");
            exec("$cmd",$resp);
            return $resp;
        }

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
            return array();
        }

        $array=explode("\n",$resp);
        if(!is_array($array)){return array();}
        return $array;

    }

    public function HaProxyVersion($binaryfile):string{
        $version="";
        exec("/usr/local/sbin/haproxy -v 2>&1",$results);
        foreach ($results as $val){
            if(preg_match("#(HA-Proxy|HAProxy)\s+version\s+([0-9\.]+)#", $val,$re)){
                return trim($re[2]);
            }
        }
        return $version;
    }

    private function go_exec_fallback($cmd){
        $sh=$this->sh_command($cmd);
        $nohup=$this->find_program("nohup");
        shell_exec("$nohup $sh >/dev/null 2>&1 &");
    }

    private function IsLocalDNS():bool{
        $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
        if($EnableDNSDist==1){return true;}
        $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
        if($UnboundEnabled==1){return true;}
        return false;
    }

    public function ResolvLocalDNSAddress():array{
        $interfaces=array();
        $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));

        if ($HaClusterClient == 1) {
            if (trim($HaClusterGBConfig["DNS1"]) <> null) {
                $interfaces[$HaClusterGBConfig["DNS1"]]=$HaClusterGBConfig["DNS1"];
            }
            if (trim($HaClusterGBConfig["DNS2"]) <> null) {
                $interfaces[$HaClusterGBConfig["DNS2"]]=$HaClusterGBConfig["DNS2"];
            }
        }


        if(!$this->IsLocalDNS()){
            $f=array();
            if(count($interfaces)==0){return array();}
            foreach ($interfaces as $IpAddr=>$none){$f[]=$IpAddr;}
            return $f;
        }

        $interfaces=array();
        $PowerDNSListenAddr=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr")));
        $ListenOnlyLoopBack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ListenOnlyLoopBack"));
        if($ListenOnlyLoopBack==1){return array("127.0.0.1");}
        foreach ($PowerDNSListenAddr as $interface){
            $interface=trim($interface);
            if($interface==null){continue;}
            $IpAddr = $this->InterfaceToIPv4($interface);
            if($IpAddr==null){continue;}
            if($IpAddr=="0.0.0.0"){continue;}
            if (!$this->IS_IPADDR_EXISTS($IpAddr)) {continue;}
            $interfaces[$IpAddr]=$IpAddr;
        }
        if(count($interfaces)==0){
            $NETS=$this->NETWORK_ALL_INTERFACES(true);
            foreach ($NETS as $IpAddr=>$none){
                if($IpAddr==null){continue;}
                if($IpAddr=="0.0.0.0"){continue;}
                $interfaces[$IpAddr]=$IpAddr;
            }
        }
        $f=array();
        foreach ($interfaces as $IpAddr=>$none){
            $f[]=$IpAddr;

        }
        return $f;
    }


    public function FILE_SIZE_BYTES_TO_MB($filepath):int{
        if(!is_file($filepath)){return 0;}
        $filebytes=@filesize($filepath);
        $fileko=$filebytes/1024;
        $fileMo=round($fileko/1024);
        return intval($fileMo);
    }

    public function folderSec($path,$chmod,$user):bool{
        if(!is_dir($path)){
            @mkdir($path,0755,true);
        }
        @chmod($path,$chmod);
        @chown($path,$user);
        @chgrp($path,$user);
        return true;
    }

    public function go_exec($cmd):bool{
        $Masterbin = "/bin/go-exec";

        if(!is_file("/bin/go-forker")){
            $php=$this->LOCATE_PHP5_BIN();
            $this->go_exec_syslog("Runing go-forker installation");
            shell_exec("$php /usr/share/artica-postfix/exec.go.exec.php --forker");
        }
        if(preg_match("#\/nohup\s+(.+)#",$cmd,$re)){
            $cmd=$re[1];
        }
        $trace=debug_backtrace();
        $file=basename($trace[1]["file"]);
        $function=trim($trace[1]["function"]);
        $line=intval($trace[1]["line"]);
        $this->go_exec_syslog($cmd." ($file/$function/$line)");
        if (!is_file($Masterbin)) {
            $this->go_exec_syslog("GO Exec bin not found, use fallback");
            $this->go_exec_fallback($cmd);
            return false;
        }

        $pid = $this->go_exec_pid();
        if (!$this->process_exists($pid)) {
            $this->go_exec_syslog("GO Exec not running, use fallback");
            $this->go_exec_fallback($cmd);
            return false;
        }

        if(!function_exists("curl_init")){
            $this->go_exec_syslog("GO Exec curl_init not found");
            $this->go_exec_fallback($cmd);
            return false;
        }
        $cmd = base64_encode($cmd);
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, "http://127.0.0.1:3334/$cmd");
        curl_setopt($cURLConnection, CURLOPT_NOPROXY, "*");
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($cURLConnection);
        $curl_error_num=intval(curl_errno($cURLConnection));
        curl_close($cURLConnection);
        if($this->is_curl_error($curl_error_num)){
            $this->go_exec_syslog("[FATAL] ".$this->mysql_error);
            $this->go_exec_fallback($cmd);
            return false;
        }

        if ($resp == "TRUE") {
            return true;
        }
        $this->go_exec_fallback($cmd);
        return false;

    }
    private function is_curl_error($curl_error_num=0){
        if($curl_error_num==7){
            $this->mysql_error="(7) Error Failed to connect() to host";
            return true;
        }
        if($curl_error_num==52){
            $this->mysql_error="(52) Empty reply from server";
            return true;
        }
        $this->go_exec_syslog("Error number $curl_error_num");
        return false;
    }

    private function go_exec_pid(){
        $pid = $this->get_pid_from_file("/var/run/go-exec.pid");
        if ($this->process_exists($pid)) { return $pid; }
        $Masterbin = $this->find_program("go-exec");
        return $this->PIDOF($Masterbin);
    }


    public function log_task($prio,$TaskType,$subject){
        if(!class_exists("lib_sqlite")){include_once("/usr/share/artica-postfix/ressources/class.sqlite.inc");}
        $q=new lib_sqlite("/home/artica/SQLITE/schedule_events.db");
        $zdate=time();

        $sql="CREATE TABLE IF NOT EXISTS `events` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT, `TaskType` INTEGER, `prio` INTEGER NOT NULL DEFAULT 2, `zdate` INTEGER,`subject` TEXT);";

        $subject=str_replace("'",'`',$subject);
        $q->QUERY_SQL($sql);
        if(!$q->ok){$this->ToSyslog("FATAL $q->mysql_error on line ".__LINE__);}
        $q->QUERY_SQL("INSERT INTO events (TaskType,prio,zdate,subject) VALUES ($TaskType,$prio,$zdate,'$subject')");
        if(!$q->ok){$this->ToSyslog("FATAL $q->mysql_error on line ".__LINE__);}

    }

    PUBLIC function LOCATE_SYSLOG_INITD(){
        if(is_file("/etc/init.d/rsyslog")){return "/etc/init.d/rsyslog";}
        if(is_file("/etc/init.d/syslog")){return "/etc/init.d/syslog";}
        if(is_file("/etc/init.d/sysklogd")){return "/etc/init.d/sysklogd";}
        return null;
    }
    public function IsProxy(){
        $ini=new iniFrameWork();
        $sock=new sockets();
        $datas=$sock->GET_INFO("ArticaProxySettings");
        if(trim($datas)==null){return false;}
        $ini->loadString($datas);
        $ArticaProxyServerEnabled=trim(strtolower($ini->_params["PROXY"]["ArticaProxyServerEnabled"]));
        $ArticaProxyServerName=trim($ini->_params["PROXY"]["ArticaProxyServerName"]);

        if($ArticaProxyServerEnabled==1){$ArticaProxyServerEnabled="yes";}if($ArticaProxyServerEnabled=="yes"){
            if($ArticaProxyServerName<>null){return true;}
        }
        return false;
    }
    public function Process1(){
        return true;
    }

    public function isPostGreSQLALive():bool{
        $PostgreSQL_PID=$this->PostgreSQL_PID();
        if($this->process_exists($PostgreSQL_PID)){return true;}
        return false;
   }

    private function PostgreSQL_PID():int{
        $pid=$this->get_pid_from_file("/home/ArticaStatsDB/postmaster.pid");
        if($this->process_exists($pid)){return intval($pid);}
        return intval($this->PIDOF("/usr/local/ArticaStats/bin/postgres"));
    }

    public function reload_monit():bool{
        $monitbin   = $this->find_program("monit");
        $monitconf  = "/etc/monit/monitrc";
        $monitpid   = "/var/run/monit/monit.pid";

        if(!is_file($monitbin)){
            $this->ToSyslog("Unable to find monit binary",true,"generic");
            return false;
        }
        if(!is_file($monitconf)){
            $this->ToSyslog("Unable to find monit configuration file",true,"generic");
            return false;
        }
        $this->ToSyslog("Reloading service monitor (MONIT) service",false,"generic");
        shell_exec("$monitbin -c $monitconf -p $monitpid reload >/dev/null 2>&1");
        return true;
    }



    public function Popuplate_cron_make($cronfile,$schedule,$phpprocess){
        if($schedule==null){return false;}
        $tfile="/etc/cron.d/$cronfile";
        $md51=null;
        if(is_file($tfile)){$md51=crc32_file($tfile);}
        $PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
        $php5=$this->LOCATE_PHP5_BIN();
        $nice=$this->EXEC_NICE();
        $CRON[]="PATH=$PATH";
        $CRON[]="MAILTO=\"\"";
        $DevNull=" >/dev/null 2>&1";
        if(strpos($phpprocess,"dev/null")>0){$DevNull=null;}

        if(strpos("   $phpprocess", "/")>0){
            $CRON[]="$schedule\troot\t$nice $phpprocess$DevNull";
        }else{
            $CRON[]="$schedule\troot\t$nice $php5 /usr/share/artica-postfix/$phpprocess$DevNull";
        }


        $CRON[]="";
        file_put_contents($tfile,@implode("\n", $CRON));
        chmod($tfile,0640);
        chown($tfile,"root");
        $md52=crc32_file($tfile);
        if($md51==$md52){return true;}

        if(function_exists("writelogs_framework")){writelogs_framework("/etc/cron.d/$cronfile -> Done",__FUNCTION__,__FILE__,__LINE__);}
        $this->go_exec("/etc/init.d/cron reload");
        return true;
    }
    public function Popuplate_cron_delete($cronfile):bool{
        $tfile="/etc/cron.d/$cronfile";
        if(!is_file($tfile)){return true;}
        @unlink($tfile);
        $this->cron_reload();
        return true;
    }



    private function cron_pid():int{
        $PIDFILE="/var/run/crond.pid";
        $pid=$this->get_pid_from_file($PIDFILE);
        if($this->process_exists($pid)){return $pid;}
        return $this->PIDOF("/usr/sbin/cron");
    }

    public function cron_reload():bool{
        $pid=$this->cron_pid();
        if(!$this->process_exists($pid)){
            $this->go_exec("/etc/init.d/cron start");
            return true;
        }
        $this->KILL_PROCESS($pid,1);
        return true;
    }


    public function CORP_LICENSE(){
        return $GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    }
    public function wgetrc($key,$val=null){
        $f=explode("\n",@file_get_contents("/etc/wgetrc"));

        foreach ($f as $line){
            $line=trim($line);
            if($line==null){continue;}
            if(substr($line, 0,1)=="#"){continue;}
            if(preg_match("#^$key#", $line)){continue;}
            if(preg_match("#LC_ALL#", $line)){continue;}
            $t[]="$line";

        }

        if($val<>null){$t[]="$key = $val\n";}
        @file_put_contents("/etc/wgetrc", @implode("\n", $t));

        return true;
    }

    public function CLAMAV_DIRECTORIES():bool{
        $dirs[]="/var/clamav";
        $dirs[]="/var/run/clamav";
        $dirs[]="/var/lib/clamav";
        $dirs[]="/var/log/clamav";
        $ClamUser=$this->ClamUser();

        foreach ($dirs as $directory){
            if(!is_dir($directory)) {@mkdir($directory, 0755, true);}

            if(is_file($directory)) {
                @unlink($directory);
                @mkdir($directory, 0755, true);
            }
            $this->chown_func("$ClamUser", "$ClamUser",$directory);

        }
            return true;
    }


    public function MAIN_URI():string{
        $ini=new iniFrameWork();
        $uri=null;
        $ini->loadFile('/etc/artica-postfix/artica-update.conf');
        if(property_exists($ini,"_params")){
            if(isset($ini->_params["AUTOUPDATE"])){
                if(trim($ini->_params["AUTOUPDATE"]["autoinstall"])==null){$ini->_params["AUTOUPDATE"]["autoinstall"]="yes";}
                if(isset($ini->_params["AUTOUPDATE"]["uri"])){$uri=$ini->_params["AUTOUPDATE"]["uri"];}
            }
        }
        if($uri==null){$uri="http://www.articatech.net/auto.update.php";}
        $uri=str_replace("artica.fr", "articatech.net",$uri);
        $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
        if ($ArticaRepoSSL==1){
           $uri="https://www.articatech.com/auto.update.php";
        }
        $arrayURI=parse_url($uri);

        if($this->IsProxy()){
            return "{$arrayURI["scheme"]}://{$arrayURI["host"]}";
        }

        $hostname=$arrayURI["host"];

        if($this->isIPAddress($hostname)){
            return "{$arrayURI["scheme"]}://{$arrayURI["host"]}";
        }

        $this->ToSyslog(__FUNCTION__."[".__LINE__."]: gethostbyname($hostname) ");
        $ipaddress=gethostbyname($hostname);

        if(!$this->isIPAddress($ipaddress)){
            if ($ArticaRepoSSL==1){
                if($hostname<>"www.artica.fr"){return "{$arrayURI["scheme"]}://{$arrayURI["host"]}";}
                $this->ToSyslog(__FUNCTION__."[".__LINE__."]: gethostbyname(https://www.articatech.com) ");
                $ipaddress=gethostbyname("www.articatech.com");
                if(!$this->isIPAddress($ipaddress)){return "";}
                $ini->_params["AUTOUPDATE"]["uri"]="https://www.articatech.com/auto.update.php";
                $ini->saveFile('/etc/artica-postfix/artica-update.conf');
                return "{$arrayURI["scheme"]}://www.articatech.com";
            }
            else {
                if($hostname<>"www.artica.fr"){return "{$arrayURI["scheme"]}://{$arrayURI["host"]}";}
                $this->ToSyslog(__FUNCTION__."[".__LINE__."]: gethostbyname(www.articatech.net) ");
                $ipaddress=gethostbyname("www.articatech.net");
                if(!$this->isIPAddress($ipaddress)){return "";}
                $ini->_params["AUTOUPDATE"]["uri"]="http://www.articatech.net/auto.update.php";
                $ini->saveFile('/etc/artica-postfix/artica-update.conf');
                return "{$arrayURI["scheme"]}://www.articatech.net";
            }

        }

        return "{$arrayURI["scheme"]}://{$arrayURI["host"]}";
    }

    private function getLocalTimezone():string{
        if(isset($GLOBALS["GET_TIME_ZONE"])){return $GLOBALS["GET_TIME_ZONE"];}
        if(is_file("/etc/artica-postfix/FROM_ISO")){
            if(!is_file("/etc/artica-postfix/artica-iso-setup-launched")){
                $GLOBALS["GET_TIME_ZONE"]="Europe/Dublin";
                return $GLOBALS["GET_TIME_ZONE"];
            }
        }


        if(isset($GLOBALS["GET_TIME_ZONE"])){if($GLOBALS["GET_TIME_ZONE"]<>null){return $GLOBALS["GET_TIME_ZONE"];}}
        if(function_exists("debug_mem")){debug_mem();}
        $page=$this->CurrentPageName();
        if($page=="exec.artica-filter.php"){return "";}


        $timezones=trim(@file_get_contents('/etc/artica-postfix/settings/Daemons/timezones'));
        if($timezones==null){
            $timezones=trim(@file_get_contents("/etc/timezone"));
        }


        if($timezones<>null){$GLOBALS["GET_TIME_ZONE"]=$timezones;return $GLOBALS["GET_TIME_ZONE"];}


        $iTime = time();
        $arr = localtime($iTime);
        $arr[5] += 1900;
        $arr[4]++;
        $iTztime = gmmktime($arr[2], $arr[1], $arr[0], $arr[4], $arr[3], $arr[5]);
        $offset = doubleval(($iTztime-$iTime)/(60*60));
        $zonelist =
            array('Kwajalein' => -12.00,'Pacific/Midway' => -11.00,'Pacific/Honolulu' => -10.00,'America/Anchorage' => -9.00,'America/Los_Angeles' => -8.00,'America/Denver' => -7.00,'America/Tegucigalpa' => -6.00,'America/New_York' => -5.00,'America/Caracas' => -4.30,'America/Halifax' => -4.00,'America/St_Johns' => -3.30,'America/Argentina/Buenos_Aires' => -3.00,'America/Sao_Paulo' => -3.00,'Atlantic/South_Georgia' => -2.00,'Atlantic/Azores' => -1.00,'Europe/Dublin' => 0,'Europe/Belgrade' => 1.00,'Europe/Minsk' => 2.00,'Asia/Kuwait' => 3.00,'Asia/Tehran' => 3.30,'Asia/Muscat' => 4.00,'Asia/Yekaterinburg' => 5.00,'Asia/Kolkata' => 5.30,'Asia/Katmandu' => 5.45,'Asia/Dhaka' => 6.00,'Asia/Rangoon' => 6.30,'Asia/Krasnoyarsk' => 7.00,'Asia/Brunei' => 8.00,'Asia/Seoul' => 9.00,'Australia/Darwin' => 9.30,'Australia/Canberra' => 10.00,'Asia/Magadan' => 11.00,'Pacific/Fiji' => 12.00,'Pacific/Tongatapu' => 13.00);
        $index = array_keys($zonelist, $offset);
        if(sizeof($index)!=1){return "";}
        $GLOBALS["GET_TIME_ZONE"]=$index[0];
        writelogs_framework("Define timezones -> {$GLOBALS["GET_TIME_ZONE"]}",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("timezones", $index[0]);
        $GLOBALS["GET_TIME_ZONE"]= $index[0];
        return $GLOBALS["GET_TIME_ZONE"];
    }
    private function CurrentPageName():string{
        if(isset($_SERVER["SCRIPT_FILENAME"])){
            return basename($_SERVER["SCRIPT_FILENAME"]);
        }
        $included_files = get_included_files();
        return basename($included_files[0]);
        }
    public function StringToGrep($search){
        $search=str_replace(".","\.",$search);
        $search=str_replace("*",".*?",$search);
        $search=str_replace("(","\(",$search);
        $search=str_replace(")","\)",$search);
        $search=str_replace("[","\[",$search);
        $search=str_replace("]","\]",$search);
        return $search;
    }
    PUBLIC function LOCATE_SSHD_PID_PATH():string{
        if(is_file("/var/run/sshd.pid")){return "/var/run/sshd.pid";}
        if(is_file("/var/run/sshd.init.pid")){return "/var/run/sshd.init.pid";}
        return "/var/run/sshd.pid";
    }
    PUBLIC function LOCATE_SSHD_CONFIG_PATH():string{
        return "/etc/ssh/sshd_config";

    }
    public function MONIT_RELOAD(){
        $monit=new monit_unix();
        $monit->RELOAD();
    }
    public function maillog_to_backupdir($fullpath){

        $IsMaillog=false;
        $file=basename($fullpath);
        if(preg_match("#^mail\.(log|warn|info)-#", $file)){$IsMaillog=true;}
        if(preg_match("#^mail\.(log|warn|info)\.([0-9]+)#", $file)){$IsMaillog=true;}

        if(!$IsMaillog){return false;}


        if(!isset($GLOBALS["maillogToMysql"])){
            $GLOBALS["maillogToMysql"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("maillogToMysql"));
        }

        if(!is_numeric($GLOBALS["maillogToMysql"])){return false;}
        if($GLOBALS["maillogToMysql"]==0){return false;}

        if(!isset($GLOBALS["maillogStoragePath"])){
            $GLOBALS["maillogStoragePath"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("maillogStoragePath"));
        }

        if(!isset($GLOBALS["maillogMaxDays"])){
            $GLOBALS["maillogMaxDays"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("maillogMaxDays"));
        }
        if($GLOBALS["maillogStoragePath"]==null){$GLOBALS["maillogStoragePath"]="/home/postfix/maillog";}
        if(!is_numeric($GLOBALS["maillogMaxDays"])){$GLOBALS["maillogMaxDays"]=7;}

        $maillogStoragePath=$GLOBALS["maillogStoragePath"];
        $maillogMaxMin=$GLOBALS["maillogMaxDays"]*24;
        $maillogMaxMin=$maillogMaxMin*60;
        if(!is_dir($maillogStoragePath)){ @mkdir($maillogStoragePath,0755,true); }
        $timef=$this->file_time_min($fullpath);
        if($timef>$maillogMaxMin){@unlink($fullpath);return true;}
        if(@copy($fullpath, "$maillogStoragePath/$file")){
            @unlink($fullpath);
            return true;
        }
        return false;

    }
    public function LOCATE_VBoxHeadless(){
        if(is_file("/usr/lib/virtualbox/VBoxHeadless")){return "/usr/lib/virtualbox/VBoxHeadless";}
        if(is_file("/usr/local/lib/virtualbox/VBoxHeadless")){return "/usr/local/lib/virtualbox/VBoxHeadless";}
        return null;
    }
    public function LOCATE_CYRRECONSTRUCT(){
        if(is_file("/usr/sbin/cyrreconstruct")){return "/usr/sbin/cyrreconstruct";}
        if(is_file("/usr/lib/cyrus/bin/reconstruct")){return "/usr/lib/cyrus/bin/reconstruct";}
        if(is_file("/usr/lib/cyrus-imapd/reconstruct")){return "/usr/lib/cyrus-imapd/reconstruct";}
        $this->events("Unable to stat cyrus reconstruct tool");
        return null;
    }

    public function LOCATE_SLPAD_PATH(){
        if(is_file("/usr/sbin/slapd")){return "/usr/sbin/slapd";}
        if(is_file("/usr/lib/openldap/slapd")){return "/usr/lib/openldap/slapd";}
        return $this->find_program("slpad");
    }
    public function LOCATE_SIEVEC(){
        if(is_file("/usr/sbin/sievec")){return "/usr/sbin/sievec";}
        if(is_file("/usr/lib/cyrus/bin/sievec")){return "/usr/lib/cyrus/bin/sievec";}
        if(is_file("/usr/lib/cyrus-imapd/sievec")){return "/usr/lib/cyrus-imapd/sievec";}
        $this->events("Unable to stat cyrus sievec tool");
        return null;
    }
    public function LOCATE_CLAMDCONF():string{
        if(is_file("/etc/clamav/clamd.conf")){return "/etc/clamav/clamd.conf";}
        if(is_file("/etc/clamd.conf")){return "/etc/clamd.conf";}
        if(is_file("/usr/local/etc/clamav/clamd.conf")){return "/usr/local/etc/clamav/clamd.conf";}
        if(is_file("/usr/local/etc/clamd.conf")){return "/usr/local/etc/clamd.conf";}
        return "";
    }
    public function APACHE_LOCATE_PHP_FPM():string{
        $f[]="/usr/sbin/php-fpm8.2";
        $f[]="/usr/sbin/php-fpm8.1";
        $f[]="/usr/sbin/php-fpm8.0";
        $f[]="/usr/sbin/php-fpm7.8";
        $f[]="/usr/sbin/php-fpm7.7";
        $f[]="/usr/sbin/php-fpm7.6";
        $f[]="/usr/sbin/php-fpm7.5";
        $f[]="/usr/sbin/php-fpm7.4";
        $f[]="/usr/sbin/php-fpm7.3";
        $f[]="/usr/sbin/php-fpm7.2";
        $f[]="/usr/sbin/php-fpm7.1";
        $f[]="/usr/sbin/php-fpm7.0";
        $f[]="/usr/sbin/php-fpm";
        foreach ($f as $path){
            if(is_file($path)){return $path;}
        }

        return "";
    }
    public function apache_LOCATE_MIME_TYPES(){
        if(is_file("/etc/mime.types")){return "/etc/mime.types";}
        if(is_file("/etc/apache2/mime.types")){return "/etc/apache2/mime.types";}
        if(is_file("/etc/httpd/mime.types")){return "/etc/httpd/mime.types";}
        return null;
    }
    public function CPU_NUMBER(){
        $time=$this->time_min(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER_TIME")));
        if($time<60){
            $res=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER");
            if($res>0){return $res;}
        }

        $CPU_NUMBER=0;
        $results=array();
        if(is_file("/usr/bin/nproc")){
            exec("/usr/bin/nproc 2>&1",$results);
            $CPU_NUMBER=intval($results[0]);
        }


        if($CPU_NUMBER==0){
            if(is_file("/usr/bin/lscpu")){
                exec("/usr/bin/lscpu 2>&1",$results);
                foreach ($results as $line){
                    $line=trim($line);
                    if(preg_match("^CPU.*?:.*?([0-9]+)$#",$line,$re)){
                        $CPU_NUMBER=intval($re[1]);
                        break;
                    }
                }
            }
        }

        if($CPU_NUMBER==0){
            $results=explode("\n",@file_get_contents("/proc/cpuinfo"));
            foreach ($results as $line){
                $line=trim($line);
                if(preg_match("processor\s+:\s+([0-9])+$#",$line,$re)) {
                    $CPU_NUMBER++;
                    continue;
                }
            }
        }

        if($CPU_NUMBER>0) {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CPU_NUMBER", $CPU_NUMBER);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CPU_NUMBER_TIME", time());
        }
        return $CPU_NUMBER;



    }

    private function LOCATE_AWSTATS_POSSIBLE_DIRS(){
        $f[]="/usr/local/awstats";
        $f[]="/usr/share/awstats";
        $f[]="/root/awstats-6.3";
        $f[]="/srv/www/ispcp/engine/awstats";
        return $f;
    }
    public function LOCATE_AWSTATS_PLUGINS_DIR():string{
        $possibledirs=$this->LOCATE_AWSTATS_POSSIBLE_DIRS();
        foreach ($possibledirs as $path){
            if(is_dir($path."/plugins")){return $path."/plugins";}
        }
        return "";
    }
    public function RECONFIGURE_SQUID(){
        $php5=$this->LOCATE_PHP5_BIN();
        $nohup=$this->find_program("nohup");
        shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --reload-squid >/dev/null 2>&1");

    }
    public function isDirInFsTab($directory){
        $directoryRegex=$directory;
        $directoryRegex=str_replace("/", "\/", $directoryRegex);
        $directoryRegex=str_replace(".", "\.", $directoryRegex);
        $f=explode("\n", @file_get_contents("/etc/fstab"));

        foreach ($f as $val){
            if(preg_match("#^(.+)\s+$directoryRegex#", $val,$re)){
                if($GLOBALS["VERBOSE"]){echo "$directory must be mounted on $re[1]\n";}
                return $re[1];
            }
        }
        return null;
    }
    public function PIDOF_BY_PORT($port):array{
        $fuser=$this->find_program("fuser");
        $RES=array();
        $cmdline="$fuser $port/tcp 2>&1";

        exec("$fuser $port/tcp 2>&1",$results);
        if($GLOBALS["VERBOSE"]){echo "$cmdline ". count($results)." lines\n";}


        foreach ($results as $val){
            if(!preg_match("#tcp:\s+(.+)#", $val,$re)){
                if($GLOBALS["VERBOSE"]){echo "PIDOF_BY_PORT: Not Found '$val'\n";}
                continue;
            }
            if($GLOBALS["VERBOSE"]){echo "PIDOF_BY_PORT: Found {$re[1]}\n";}
            $PIDLINE=intval($re[1]);

            if($PIDLINE<>null){
                $tr=explode(" ",$PIDLINE);
                foreach ($tr as $b){
                    $b=trim($b);
                    if($b==null){continue;}
                    if(!is_numeric($b)){continue;}
                    if($this->PIDOF_DOCKER($b)){continue;}
                    if($b<5){continue;}
                    $RES[$b]=$b;
                }
            }
        }
        return $RES;
    }



    public function NGINX_COMPILE_PARAMS():array{
        $ARRAY=array();
        if(isset($GLOBALS[__CLASS__.".".__FUNCTION__])){return $GLOBALS[__CLASS__.".".__FUNCTION__];}


        $nginx=$this->find_program("nginx");
        if(!is_file($nginx)){return array();}
        exec("$nginx -V 2>&1",$results);

        $NOT["CONFIGURE"]=true; // 	1
        $NOT["ARGUMENTS:"]=true; // 	1
        $NOT["PREFIX"]=true; // 	/etc/nginx
        $NOT["SBIN-PATH"]=true; // 	/usr/sbin/nginx
        $NOT["CONF-PATH"]=true; // 	/etc/nginx/nginx.conf
        $NOT["ERROR-LOG-PATH"]=true; // 	/var/log/nginx/error.log
        $NOT["HTTP-LOG-PATH"]=true; // 	/var/log/nginx/access.log
        $NOT["PID-PATH"]=true; // 	/var/run/nginx.pid
        $NOT["LOCK-PATH"]=true; // 	/var/run/nginx.lock
        $NOT["HTTP-CLIENT-BODY-TEMP-PATH"]=true; // 	/var/cache/nginx/client_temp
        $NOT["HTTP-PROXY-TEMP-PATH"]=true; // 	/var/cache/nginx/proxy_temp
        $NOT["HTTP-FASTCGI-TEMP-PATH"]=true; // 	/var/cache/nginx/fastcgi_temp
        $NOT["HTTP-UWSGI-TEMP-PATH"]=true; // 	/var/cache/nginx/uwsgi_temp
        $NOT["HTTP-SCGI-TEMP-PATH"]=true; // 	/var/cache/nginx/scgi_temp
        $NOT["USER"]=true; // 	nginx
        $NOT["GROUP"]=true; //
        $NOT["WITH-HTTP_GUNZIP_MODULE"]=true; //

        foreach ($results as $value){
            if(preg_match("#nginx version: (.*?)\/([0-9\.]+)#", $value,$re)){
                $ARRAY["DEF"]["VENDOR"]=$re[1];
                $ARRAY["DEF"]["VERSION"]=$re[2];
                continue;
            }
            $ARRAY["srcache_nginx_module"]=false;
            $ARRAY["nginx_pagespeed"]=false;
            if(preg_match("#TLS SNI support enabled#", $value,$re)){$ARRAY["DEF"]["TLS"]=true;continue;}
            if(preg_match("#configure arguments:\s+(.*)#",$value,$re)){
                $f=explode(" ",$value);
                foreach ($f as $b){
                    if(preg_match("#-pagespeed-ngx#",$b)){
                        $ARRAY["nginx_pagespeed"]=true;
                    }
                    if(preg_match("#add-module=(.+)#",$b,$re)){
                        $module=basename($re[1]);
                        if(preg_match("#headers-more-nginx-module#", $module)){
                            $ARRAY["ngx_headers_more"]=true;
                        }
                        if(preg_match("#srcache-nginx-module#",$module)){
                            $ARRAY["srcache_nginx_module"]=true;
                        }

                        $ARRAY["MODULES"][$module]=$module;
                        continue;
                    }
                    if($GLOBALS["VERBOSE"]){echo "NGINX: COMPIL: <$b>\n";}
                    $b=str_replace("--", "", $b);

                    if(preg_match("#with-(.+?)$#", $b,$ri)){
                        $ARRAY["ARGS"][trim(strtoupper($ri[1]))]=true;
                        continue;
                    }


                    if(preg_match("#(.+?)=(.+)#", $b,$ri)){
                        if(isset($NOT[strtoupper($b)])){continue;}
                        $ARRAY["ARGS"][strtoupper($ri[1])]=trim($ri[2]);
                        continue;
                    }
                    if(isset($NOT[strtoupper($b)])){continue;}
                    $ARRAY["ARGS"][strtoupper($b)]=true;
                }

            }
        }
        $GLOBALS[__CLASS__.".".__FUNCTION__]=$ARRAY;
        return $ARRAY;
    }
    public function InterfaceToIPv4($InterfaceName=null):string{
        $InterfaceName=trim($InterfaceName);
        if($InterfaceName==null){return "";}
        if($InterfaceName=="lo"){return "127.0.0.1";}
        if(!$this->is_interface_available($InterfaceName)){return "";}
        return $this->NETWORK_IFNAME_TO_IP($InterfaceName);
    }
    public function InterfaceToIP($eth):array{
        include_once("/usr/share/artica-postfix/ressources/class.system.nics.inc");
        if($eth==null){return array("IP"=>null,"NETMASK"=>null,"NETWORK"=>null,"IPADDR"=>null);}
        $nics=new system_nic($eth);
        if($nics->IPADDR<>null){
            $PR=explode(".",$nics->IPADDR);


            return array("IP"=>$nics->IPADDR,"IPADDR"=>$nics->IPADDR,
                "NETMASK"=>$nics->NETMASK,
                "NETWORK"=>"{$PR[0]}.{$PR[1]}.{$PR[2]}.0"
            );
        }

        $arrayTCP=$this->NETWORK_ALL_INTERFACES();
        $ALLARRAY=$arrayTCP[$eth];

        $PR=explode(".",$ALLARRAY["IPADDR"]);

        return array("IP"=>$ALLARRAY["IPADDR"],"NETMASK"=>$ALLARRAY["NETMASK"]
        ,"NETWORK"=>"{$PR[0]}.{$PR[1]}.{$PR[2]}.0","IPADDR"=>$ALLARRAY["IPADDR"],

        );
    }


    private function CreateUnixUserRemoveLocks(){
        $f[]="/etc/passwd.lock";
        $f[]="/etc/shadow.lock";
        $f[]="/etc/group.lock";
        $f[]="/etc/gshadow.lock";
        foreach ($f as $path){
            if(is_file($path)){
                $this->ToSyslog("SUSCPICIOUS behavior, removed lock file `$path`");
                @unlink($path);}
        }
        return true;
    }

    public function DeleteUnixUser($username,$groupname=null):bool{

        if($this->UnixUserExists($username)){
            shell_exec("/usr/sbin/deluser --quiet \"$username\"");
        }

        if($groupname<>null) {
            if($this->UnixGroupExists($groupname)){
                shell_exec("/usr/sbin/delgroup --quiet $groupname");
            }
        }
        return true;
    }

    public function CreateUnixUser($username,$groupname=null,$displayname=null){
        if($this->UnixUserExists($username)){return true;}
        include_once(dirname(__FILE__)."/class.settings.inc");
        if(!isset($GLOBALS["CLASS_USERSMENUS"])){
            include_once("/usr/share/artica-postfix/ressources/class.users.menus.inc");
            $user=new usersMenus();$GLOBALS["CLASS_USERSMENUS"]=$user;}else{$user=$GLOBALS["CLASS_USERSMENUS"];}

        if($groupname==null){$groupname=$username;}
        if($displayname==null){$displayname=$username;}

        $GLOBALS["CREATE_USERS_LOGS"][]="Checking group \"$groupname\"";
        if(!$this->UnixGroupExists($groupname)){
            $GLOBALS["CREATE_USERS_LOGS"][]="Creating group \"$groupname\"";
            if(!$this->SystemCreateGroup($groupname)){
                $GLOBALS["CREATE_USERS_LOGS"][]="Creating group \"$groupname\" Failed";
                foreach ($GLOBALS["SystemCreateUser"] as $line) {
                    $GLOBALS["CREATE_USERS_LOGS"][] = $line;
                }

                if($GLOBALS["VERBOSE"]){foreach ($GLOBALS["SystemCreateUser"] as $line){if($GLOBALS["VERBOSE"]){echo "SystemCreateGroup: $line\n";}}
                }
                return false;
            }
        }else{
            $GLOBALS["CREATE_USERS_LOGS"][]="Group \"$groupname\" already exists";
        }


        $this->CreateUnixUserRemoveLocks();
        $Shell="/sbin/nologin";
        if($username="ArticaStats"){
            $Shell="/bin/bash";
        }

        $cmd=null;
        $useradd=$this->find_program("useradd");
        if($user->LinuxDistriCode=="CENTOS"){
            $cmd="$useradd -s $Shell -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
        }
        if($user->LinuxDistriCode=="SUSE"){
            $cmd="$useradd -s $Shell -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
        }
        if($user->LinuxDistriCode=="FEDORA"){
            $cmd="$useradd -s $Shell -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
        }
        if($user->LinuxDistriCode=="MANDRAKE"){
            $cmd="$useradd -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
        }
        if($cmd==null){
            $cmd="$useradd -s $Shell -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
        }
        $GLOBALS["CREATE_USERS_LOGS"][]=$cmd;


        if($GLOBALS["VERBOSE"]){echo "CreateUnixUser() $user->LinuxDistriCode \"$cmd\"\n";}
        shell_exec($cmd);
        if(!$this->UnixUserExists($username)){
            if($user->LinuxDistriCode=="CENTOS"){
                $cmd="$useradd -s $Shell -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
            }
            if($user->LinuxDistriCode=="SUSE"){
                $cmd="$useradd -s $Shell -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
            }
            if($user->LinuxDistriCode=="FEDORA"){
                $cmd="$useradd -s $Shell -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
            }
            if($user->LinuxDistriCode=="MANDRAKE"){
                $cmd="$useradd -s $Shell -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
            }
            if($cmd==null){
                $cmd="useradd -m --d /home/$username -s $Shell -c \"$displayname\" $username >/dev/null 2>&1";
            }
            if($GLOBALS["VERBOSE"]){echo "CreateUnixUser() $user->LinuxDistriCode \"$cmd\"\n";}
            shell_exec($cmd);
            $GLOBALS["CREATE_USERS_LOGS"][]=$cmd;
        }

        return $this->UnixUserExists($username);


    }
    public function SystemUserExists($username):bool{
        $userinfo = posix_getpwnam($username);
        if(!isset($userinfo["uid"])){return false;}
        if(!is_numeric($userinfo["uid"])){return false;}
        if($userinfo["uid"]<1){return false;}
        return true;
    }
    public function SystemUserGetuid($username){
        $userinfo = posix_getpwnam($username);
        if(!isset($userinfo["uid"])){return null;}
        if(!is_numeric($userinfo["uid"])){return null;}
        if($userinfo["uid"]<1){return null;}
        return $userinfo["uid"];
    }
    public function SystemGroupUid($groupname){
        if(!function_exists("posix_getgrnam")){
            return "";
        }
        $userinfo = posix_getgrnam($groupname);
        if(!isset($userinfo["gid"])){return null;}
        if(!is_numeric($userinfo["gid"])){return null;}
        if($userinfo["gid"]<1){return null;}
        return $userinfo["gid"];
    }
    public function SystemCreateUser($username,$group=null,$xshell=null,$sHome=null):bool{
        $GLOBALS["SystemCreateUser"]=array();


        if(!$this->SystemUserExists($username)){
            $GLOBALS["SystemCreateUser"][]="$username does not exist, create it";
            $useradd=$this->find_program("useradd");
            if(!is_file($useradd)){
                $GLOBALS["SystemCreateUser"][]="useradd No such binary";
                return false;
            }
            if($xshell==null){$xshell="/sbin/nologin";}
            if($group<>null){
                if(!$this->SystemGroupExists($group)){
                    $GLOBALS["SystemCreateUser"][]="Creating Group `$group`";
                    if($GLOBALS["VERBOSE"]){echo "Creating Group `$group`...\n";}
                    if(!$this->SystemCreateGroup($group)){
                        $GLOBALS["SystemCreateUser"][]="Creating Group `$group` failed";
                        return false;
                    }
                }
            }
            $cmd[]=$useradd;
            if(strlen($xshell)>2){$cmd[]="-s $xshell";}
            if(strlen($sHome)>2){$cmd[]="-d \"$sHome\"";}
            if($group<>null){$cmd[]="-g $group";}


            $cmd[]=$username;
            $cmd_finale=@implode(" ", $cmd);
            if(function_exists("writelogs_framework")){
                writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
            }


            if($GLOBALS["VERBOSE"]){echo $cmd_finale."\n";}
            exec("$cmd_finale 2>&1",$results);
            if($GLOBALS["VERBOSE"]){echo @implode("\n", $results);}
        }

        if($group<>null){
            if(!$this->SystemGroupExists($group)){
                $GLOBALS["SystemCreateUser"][]="Group `$group` does not exists";
                if(!$this->SystemCreateGroup($group)){
                    $GLOBALS["SystemCreateUser"][]="Create user $username finally Failed to create `$group`";
                    return false;
                }
            }
            if(!$this->SystemIsUserIngroup($username,$group)){
                $GLOBALS["SystemCreateUser"][]="Add $username to `$group`";
                $usermod=$this->find_program("usermod");
                shell_exec("$usermod -a -G $group $username >/dev/null 2>&1 &");
                shell_exec("$usermod -A $group $username >/dev/null 2>&1 &");
                shell_exec("$usermod --append $group $username >/dev/null 2>&1 &");
            }
        }
        $GLOBALS["SystemCreateUser"][]="Create $username Success";
        return true;
    }
    public function SystemCreateGroup($group):bool{
        $this->CreateUnixUserRemoveLocks();
        $groupadd=$this->find_program("groupadd");
        if(!is_file($groupadd)){
            $GLOBALS["SystemCreateUser"][]="groupadd no such binary";
            return false;
        }
        $GLOBALS["SystemCreateUser"][]="Creating group $group";
        exec("$groupadd $group 2>&1",$results);
        if(!$this->SystemGroupExists($group)){
            foreach ($results as $line){
                $GLOBALS["SystemCreateUser"][]=$line;
            }
            $GLOBALS["SystemCreateUser"][]="Creating group $group finally failed";
            return false;
        }
        $GLOBALS["SystemCreateUser"][]="Creating group $group finally OK";
        return true;

    }
    public function SystemGroupExists($groupname):bool{
        if(strpos($groupname, ":")>0){
            $exp=explode(":",$groupname);
            $groupname=$exp[1];
        }



        if(function_exists("posix_getgrnam")) {
            $userinfo = posix_getgrnam($groupname);
            if(!is_array($userinfo)){$userinfo=array();}
            if (count($userinfo) > 0) {
                $GLOBALS["SystemCreateUser"][] = "userinfo($groupname) ".count($userinfo). " items";
                if ($GLOBALS['VERBOSE']) {
                    echo "posix_getgrnam($groupname) -> `{$userinfo["gid"]}`\n";
                }
                if (!isset($userinfo["gid"])) {
                    $GLOBALS["SystemCreateUser"][] = "userinfo($groupname) gid no such key";
                    $GLOBALS["SystemCreateUser"][] = serialize($userinfo);
                    foreach ($userinfo as $key => $val) {
                        $GLOBALS["SystemCreateUser"][] = "$groupname: $key = $val";
                    }
                    return false;
                }
                if (!is_numeric($userinfo["gid"])) {
                    $GLOBALS["SystemCreateUser"][] = "userinfo gid not numeric {$userinfo["gid"]}";
                    return false;
                }
                $GLOBALS["SystemCreateUser"][] = "$groupname OK";
                return true;
            }
        }

        $f=explode("\n",@file_get_contents("/etc/group"));
        foreach ($f as $line){
            if(preg_match("#^$groupname:.*?:([0-9]+)#",$line,$re)){
                $GLOBALS["SystemCreateUser"][]="$groupname = $re[1]";
                return true;
            }
        }
        $GLOBALS["SystemCreateUser"][]="$groupname no such group";
        return false;
    }
    public function SystemGetMemberID($username){
        $userinfo = posix_getpwnam($username);
        if(isset($userinfo["uid"])){
            if(is_numeric($userinfo["uid"])){return intval($userinfo["uid"]);}
        }
        return -1;
    }
    public function SystemIsUserIngroup($username,$groupname){
        if(!$this->SystemGroupExists($groupname)){
            $this->ToSyslog("SystemIsUserIngroup Group `$groupname` doesn't exists.. [FALSE]");
            return false;
        }

        if(strpos($username, ":")>0){
            $exp=explode(":",$username);
            $username=$exp[0];
        }

        if(strpos($groupname, ":")>0){
            $exp=explode(":",$groupname);
            $groupname=$exp[1];
        }


        $username=trim(strtolower($username));
        $groupname=trim(strtolower($groupname));

        if($username=="root"){
            if($groupname=="root"){
                return true;
            }
        }
        if(function_exists("posix_getgrnam")) {
            $userinfo = posix_getgrnam($groupname);
        }

        $userid=$this->SystemGetMemberID($username);

        if($GLOBALS["VERBOSE"]){echo "`$groupname` ".count($userinfo["members"])." members for $username/$userid\n";}
        foreach ($userinfo["members"] as $user){
            $user=trim(strtolower($user));
            if($GLOBALS["VERBOSE"]){echo "`$groupname` Member: $user ==? $username\n";}
            if($user==$username){return true;}

        }
        $this->ToSyslog("SystemIsUserIngroup `$username` is not a member of `$groupname`.. [FALSE]");
        return false;

    }
    public function CYRUS_PID_PATH(){
        if(is_file('/var/run/cyrus-master.pid')){return '/var/run/cyrus-master.pid';}
        if(is_file('/var/run/cyrmaster.pid')){return '/var/run/cyrmaster.pid';}
        if(is_file('/var/run/cyrus.pid')){return '/var/run/cyrus.pid';}
        return "/var/run/cyrmaster.pid";
    }
    public function CYRUS_DAEMON_BIN_PATH():string{
        $cyrmaster=$this->find_program("cyrmaster");
        if(is_file($cyrmaster)){return $cyrmaster;}
        if(is_file('/usr/lib/cyrus-imapd/cyrus-master') ){return '/usr/lib/cyrus-imapd/cyrus-master';}
        if (is_file('/usr/lib/cyrus/bin/master') ){return '/usr/lib/cyrus/bin/master';}
        if (is_file('/usr/sbin/cyrmaster') ){return '/usr/sbin/cyrmaster';}
        if (is_file('/usr/sbin/cyrmaster') ){return '/usr/sbin/cyrmaster';}
        if (is_file('/opt/artica/cyrus/bin/master') ){return '/opt/artica/cyrus/bin/master';}
        return "";
    }
    public function CYRADM_PATH():string{
        if (is_file('/usr/bin/cyradm') ){return '/usr/bin/cyradm';}
        if (is_file('/usr/share/bin/cyradm') ){return '/usr/share/bin/cyradm';}
        if (is_file('/opt/artica/bin/cyradm') ){return '/opt/artica/bin/cyradm';}
        return "";
    }
    function CYRUS_SYNC_CLIENT_BIN_PATH():string{
        $sync_client=$this->find_program("sync_client");
        if(is_file($sync_client)){return $sync_client;}
        if(is_file('/usr/lib/cyrus-imapd/sync_client')){return '/usr/lib/cyrus-imapd/sync_client';}
        if(is_file('/usr/lib/cyrus/bin/sync_client')){return '/usr/lib/cyrus/bin/sync_client';}
        if(is_file('/usr/sbin/sync_client')){return '/usr/sbin/sync_client';}
        return "";
    }
    public function LOCATE_CYRUS_QUOTA():string{
        $sync_client=$this->find_program("cyrquota");
        if(is_file($sync_client)){return $sync_client;}
        if(is_file('/usr/sbin/cyrquota') ){ return '/usr/sbin/cyrquota';}
        if(is_file('/usr/lib/cyrus/bin/quota') ){ return '/usr/lib/cyrus/bin/quota';}
        if(is_file('/usr/lib/cyrus-imapd/quota') ){ return '/usr/lib/cyrus-imapd/quota';}
        return "";
    }
    public function JAVA_HOME_GET():string{
        $f=explode("\n", @file_get_contents("/etc/environment"));
        foreach ( $f as $val ){
            if(preg_match("#JAVA_HOME=(.+)#", $val,$re)){
                $re[1]=str_replace('"', "", $re[1]);
                if(is_dir($re[1])){return $re[1];}
            }
        }

        return "";}
    public function MYSQL_REPAIR_TABLE_FILE_NOT_FOUND($file,$table):string{
        $Dir=dirname($file);
        if($table==null){return "";}
        $FF=array();
        $files=$this->DirFiles("$Dir","$table-[0-9]+\.BAK$");
        foreach ($files as $filename=>$f){
            if(!preg_match("#$table-([0-9]+)\.BAK$#", $filename,$re)){continue;}
            $FF[$re[1]]=$filename;
        }

        krsort($FF);
        foreach ($FF as $index=>$filename){
            if($GLOBALS["VERBOSE"]){echo "$index=>$filename\n";}
            $tt[]=$filename;}
        $lastfile=$tt[0];
        $L[]="last backuped file=\"$lastfile\"<br>\n";


        if(!is_file($file)){
            if($lastfile<>null){
                $L[]="replace backuped file=$Dir/$lastfile to $file<br>\n";
                @copy("$Dir/$lastfile", $file);
            }
        }
        if(is_file($file)){
            @chmod($file, 0755);
            @chown($file, "mysql");
            @chgrp($file, "mysql");
            $L[]="$file apply permissions done...<br>\n";
        }else{
            $fileTMD=str_replace(".MYD", ".TMD", $file);
            if(is_file($fileTMD)){@copy($fileTMD,$file);@chown($file, "mysql");@chgrp($file, "mysql");}else{
                $L[]="$file no such file....<br>\n";
            }
        }
        return @implode("", $L);

    }
    public function JAVA_HOME_SET($dir){
        $f=explode("\n", @file_get_contents("/etc/environment"));

        foreach ( $f as $num=>$val ){
            if(preg_match("#JAVA_HOME=(.+)#", $val,$re)){
                $re[1]=str_replace('"', "", $re[1]);
                if($re[1]==$dir){return;}
                $f[$num]="JAVA_HOME=\"$dir\"";
                @file_put_contents("/etc/environment", @implode("\n", $f));
                return;
            }
        }

        $f[]="JAVA_HOME=\"$dir\"";
        @file_put_contents("/etc/environment", @implode("\n", $f));

    }
    public function UnixGroupExists($groupname):bool{

        $f=explode("\n",@file_get_contents("/etc/group"));
        foreach ($f as $line){
            if(preg_match("#^$groupname#i",$line)){
                return true;
            }
        }
        return false;

    }
    public function UnixUserExists($username):bool{

        $results=explode("\n",@file_get_contents("/etc/shadow"));
        foreach ($results as $val){
            $val=trim($val);
            if($val==null){continue;}
            if(preg_match("#^$username:#i", $val)){
                if($GLOBALS["VERBOSE"]){echo "UnixUserExists() $val Match\n";}
                return true;
            }
            if($GLOBALS["VERBOSE"]){echo "UnixUserExists() $val No match\n";}
        }
        $GLOBALS["CREATE_USERS_LOGS"][]="UnixUserExists() user:$username does not exists in database";
        if($GLOBALS["VERBOSE"]){echo "UnixUserExists() user:$username does not exists in database\n";}
        return false;
    }
    function ldap_GET_CONFS(){

        $SLAPD_CONF=$this->SLAPD_CONF_PATH();
        $f=explode("\n",@file_get_contents($SLAPD_CONF));
        foreach ( $f as $line ){
            if(preg_match("#suffix.*?\"(.+?)\"#", $line,$re)){
                $ARRAY["SUFFIX"]=$re[1];
                continue;
            }

            if(preg_match("#rootdn.*?\"(.+?)\"#", $line,$re)){
                $ARRAY["DN"]=$re[1];
                continue;
            }
            if(preg_match("#rootpw.*?\"(.+?)\"#", $line,$re)){
                $ARRAY["PWD"]=$re[1];
                continue;
            }

        }

        return $ARRAY;

    }
    public function file_ext($path){
        $path_info = pathinfo($path);
        return $path_info['extension'];

    }
    public function GetMyHostId(){
        return $this->GetUniqueID();

    }
    public function IsSquidTaskCanBeExecuted(){
        if(!class_exists("settings_inc")){include_once(dirname(__FILE__)."/class.settings.inc");}
        $users=new settings_inc();
        $sock=new sockets();
        $update=true;
        $EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
        if($EnableWebProxyStatsAppliance==1){return true;}
        if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){return true;}
        $CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
        if($CategoriesRepositoryEnable==1){return true;}
        if(!$users->SQUID_INSTALLED){$update=false;}
        return $update;
    }

    public function SQUID_SSL_CRTD_PATH():string{
        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        $sslcrtd_program_in_memory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_program_in_memory"));

        if($SQUIDEnable==0){$sslcrtd_program_in_memory=0;}
        if($sslcrtd_program_in_memory==0){return "/etc/squid3/ssl";}
        return "/media/squidtmpfs/ssl";
    }


    public function tune2fs_values($dev){
        $tune2fs=$this->find_program("tune2fs");
        $cmd="$tune2fs -l $dev 2>&1";
        exec($cmd,$results);
        $array=array();

        foreach ($results as $line){

            if(preg_match("#Bad magic number#", $line)){
                $array["ERROR"]=true;
                $array["ERROR_TEXT"]=$line;
            }

            $av="available";
            if(!preg_match("#^(.+?):\s+(.+)#i",$line,$re)){continue;}
            $key=trim(strtoupper($re[1]));
            $key=str_replace(" ", "_", $key);
            $value=trim($re[2]);
            if($value=="<not $av>"){$value=null;}
            if($value=="<none>"){$value=null;}
            $array[$key]=$value;
        }

        $results=array();
        $df=$this->find_program("df");
        exec("$df -i $dev 2>&1",$results);
        foreach ($results as $ligne){
            if(preg_match("#^.*?\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
                $array["INODES_MAX"]=$re[1];
                $array["INODES_USED"]=$re[2];
                $array["INODES_AVAILABLE"]=$re[3];
                $array["INODES_POURC"]=$re[4];
                break;
            }
        }
        $BLKID=$this->BLKID_INFOS($dev);
        foreach ($BLKID as $num=>$ligne){
            $array[$num]=$ligne;
        }

        return $array;
    }
    public function sysctl($key,$value){
        $keyS=str_replace(".", "\.", $key);
        $t=false;
        $f=explode("\n",@file_get_contents("/etc/sysctl.conf"));
        foreach ( $f as $num=>$val ){
            if(substr($val, 0,1)=="#"){continue;}
            if(preg_match("#^$keyS.*?=#", $val)){
                $f[$num]="$key = $value";
                $t=true;
                break;
            }
        }

        if(!$t){
            $f[]="$key = $value";
        }
        reset($f);
        @file_put_contents("/etc/sysctl.conf", implode("\n", $f));

    }
    public function LOCATE_CLAMDSOCKET():string{
        $f=@explode("\n",@file_get_contents($this->LOCATE_CLAMDCONF()));
        foreach ( $f as $val ){
            if(preg_match("#LocalSocket\s+(.+)#",$val,$re)){
                return trim($re[1]);
            }
        }
        return "";
    }
    public function SQUID_CACHE_FROM_SQUIDCONF(){
        $array=array();
        $fname="/etc/squid3/caches.conf";
        if(!is_file($fname)){$fname="/etc/squid3/squid.conf";}

        $f=explode("\n",@file_get_contents($fname));
        foreach ($f as $line){
            if(preg_match("#cache_dir\s+(.+?)\s+(.+?)\s+#",$line,$re)){
                $array[trim($re[2])]=trim($re[1]);
            }

        }

        return $array;
    }
    public function SQUID_CACHE_FROM_SQUIDCONF_FULL(){
        $array=array();
        $fname="/etc/squid3/caches.conf";
        if(!is_file($fname)){$fname="/etc/squid3/squid.conf";}

        $f=explode("\n",@file_get_contents($fname));
        foreach ($f as $line){
            if(preg_match("#cache_dir\s+(.+?)\s+(.+?)\s+#",$line,$re)){
                $array[trim($re[2])]=$line;
            }

        }

        return $array;
    }
    public function LOCATE_STUNNEL():string{
        $bins[]="stunnel5";
        $bins[]="stunnel4";
        $bins[]="stunnel3";
        foreach ($bins as $prog){
            $a=null;
            $a=$this->find_program($prog);
            if($a<>null){return $a;}
        }
        return "";
    }
    public function LOCATE_STUNNEL_INIT():string{
        $bins[]="stunnel";
        $bins[]="stunnel5";
        $bins[]="stunnel4";
        $bins[]="stunnel3";
        foreach ($bins as $prog){
            if(is_file("/etc/init.d/$prog")){return "/etc/init.d/$prog";}

        }
        return "";
    }

    public function PROCESS_UPTIME($pid){
        $PROC_STAT=$this->LOCATE_PROCSTAT();
        $me=null;
        if(strlen($PROC_STAT)>3){
            exec("$PROC_STAT $pid 2>&1",$results);
            foreach ($results as $ligne){
                if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
                    $tt=$this->time_duration($re[1]);
                    $tt=str_replace(" Months,","M",$tt);
                    $tt=str_replace(" days,","d",$tt);
                    $tt=str_replace(" hours,","h",$tt);
                    $tt=str_replace(" minutes,","mn",$tt);
                    $tt=str_replace(" seconds","s",$tt);
                    $tt=str_replace(" hour,","h",$tt);
                    $tt=str_replace(" minute,","mn",$tt);
                    $tt=str_replace(" second","s",$tt);
                    $me="uptime=$tt";
                    break;
                }

            }
        }

        return $me;

    }
    public function PingHost($cible,$nocache=false){

        $cachefile="/etc/artica-postfix/pinghosts.cache";
        if(!$nocache){
            $cache=unserialize(@file_get_contents($cachefile));
            $current_time=time();
            if(is_array($cache)){
                if(count($cache)>700){@unlink($cachefile);$cache=array();}
                if(isset($cache[$cible])){
                    $old_time=$cache[$cible]["time"];
                    if(!is_numeric($old_time)){$old_time=0;}
                    $difference = ($current_time - $old_time);
                    if($difference<30){return $cache[$cible]["result"];}

                }
            }
        }



        $timeout=1;
        $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
        $socket  = socket_create(AF_INET, SOCK_RAW, 1);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
        socket_connect($socket, $cible, null);


        if(!socket_send($socket, $package, strLen($package), 0)){
            return false;

        }
        if (socket_read($socket, 255)){$result = true;}else{ $result = false;}
        if(is_resource($socket)){socket_close($socket);}
        if(!$result){
            if($GLOBALS["VERBOSE"]){echo "Ping Method 1 -> $cible FALSE\n";}
            $result=$this->PingHostCMD($cible);
        }else{
            if($GLOBALS["VERBOSE"]){echo "Ping Method 1 -> $cible TRUE\n";}
        }
        $cache[$cible]["result"]=$result;
        $cache[$cible]["time"]=time();
        if(!$result){writelogs_framework("$cible -> FALSE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}else{writelogs_framework("$cible -> TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
        if(!$nocache){
            writelogs_framework("Saving : $cachefile",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            @file_put_contents($cachefile, serialize($cache));
        }
        return $result;
    }
    public function NETWORK_DEFAULT_IP_ADDR():string{
        $NETWORK_ALL_INTERFACES=$this->NETWORK_ALL_INTERFACES();
        for($i=0;$i<5;$i++){
            if(!isset($NETWORK_ALL_INTERFACES["eth$i"])){continue;}
            if($NETWORK_ALL_INTERFACES["eth$i"]["IPADDR"]=="0.0.0.0"){continue;}
            if(preg_match("#127.0.0#", $NETWORK_ALL_INTERFACES["eth$i"]["IPADDR"])){continue;}
            return $NETWORK_ALL_INTERFACES["eth$i"]["IPADDR"];

        }

        for($i=0;$i<5;$i++){
            if(!isset($NETWORK_ALL_INTERFACES["br$i"])){continue;}
            if($NETWORK_ALL_INTERFACES["br$i"]["IPADDR"]=="0.0.0.0"){continue;}
            if(preg_match("#127.0.0#", $NETWORK_ALL_INTERFACES["br$i"]["IPADDR"])){continue;}
            return $NETWORK_ALL_INTERFACES["br$i"]["IPADDR"];
        }
        return "";
    }
    public function NETWORK_REPORT(){

        $results[]="Report....:";
        $ifconfig=$this->find_program("ifconfig");
        exec("$ifconfig -a 2>&1",$results);
        $ip=$this->find_program("ip");
        exec("$ip link show 2>&1",$results);
        exec("$ip addr 2>&1",$results);
        $results[]="Routes....:";
        exec("$ip route 2>&1",$results);
        $results[]="DNS.......:\n";
        $results[]=@file_get_contents("/etc/resolv.conf");
        $results[]="TESTS pings......:";
        $ARRAY=$this->NETWORK_ALL_INTERFACES();
        foreach ($ARRAY as $interface=>$ligne){
            if(!isset($ligne["GATEWAY"])){continue;}
            if($ligne["GATEWAY"]==null){continue;}
            $results[]=$this->report_ping($interface,$ligne["GATEWAY"]);
        }

        return @implode("\r\n", $results);
    }
    private function report_ping($from,$to){
        $array[]="Ping from $from -> $to";
        $ping=$this->find_program("ping");
        exec("$ping -I $from -c 2 -i 0 $to 2>&1",$array);
        return @implode("\r\n", $array);

    }

    public function PingHostCMD($cible,$source=null):bool{
        if(isset($GLOBALS["PingHostCMD"][$cible])){return $GLOBALS["PingHostCMD"][$cible];}
        $ping=$this->find_program("ping");
        if($source<>null){
            $source=" -I $source ";
        }
        exec("$ping $source-v -c1 -t3 -W1 -w1 -p ff $cible 2>&1",$results);
        foreach ($results as $val){
            if(preg_match("#, ([0-9]+)\% packet loss#", $val,$re)){
                if($re[1]==0){$GLOBALS["PingHostCMD"][$cible]=true;
                    if($GLOBALS["VERBOSE"]){echo "Ping Method 2 -> $cible TRUE\n";}
                    return true;
                }else{
                    if($GLOBALS["VERBOSE"]){echo "Ping Method 2 -> $cible FALSE\n";}
                    $GLOBALS["PingHostCMD"][$cible]=false;
                    return false;}
            }
        }

        return false;
    }


    public function LOCATE_WINBINDD_PID():string{
        if(is_file("/var/run/winbindd.pid")){return "/var/run/winbindd.pid";}
        if(is_file("/var/run/samba/winbindd.pid")){return "/var/run/samba/winbindd.pid";}
        return "";

    }
    public function LOCATE_CYRUS_IPURGE():string{
        if(is_file("/usr/sbin/ipurge")){return "/usr/sbin/ipurge";}
        if(is_file("/usr/lib/cyrus/bin/ipurge")){return "/usr/lib/cyrus/bin/ipurge";}
        if(is_file("/usr/lib/cyrus-imapd/ipurge")){return "/usr/lib/cyrus-imapd/ipurge";}
        $this->events("Unable to stat cyrus ipurge tool");
        return "";
    }



    public function LOCATE_CYRQUOTA():string{
        if(is_file("/usr/sbin/cyrquota")){return "/usr/sbin/cyrquota";}
        if(is_file("/usr/lib/cyrus/bin/quota")){return "/usr/lib/cyrus/bin/quota";}
        if(is_file("/usr/lib/cyrus-imapd/quota")){return "/usr/lib/cyrus-imapd/quota";}
        $this->events("Unable to stat cyrus cyrquota tool");
        return "";
    }
    public function LOCATE_CYRUS_DAEMON():string{
        if(is_file("/usr/lib/cyrus-imapd/cyrus-master")){return "/usr/lib/cyrus-imapd/cyrus-master";}
        if(is_file("/usr/lib/cyrus/bin/master")){return "/usr/lib/cyrus/bin/master";}
        if(is_file("/usr/sbin/cyrmaster")){return "/usr/sbin/cyrmaster";}
        if(is_file("/opt/artica/cyrus/bin/master")){return "/opt/artica/cyrus/bin/master";}
        return "";
    }
    public function LOCATE_CYRUS_DELIVER_BIN_PATH():string{
        if(is_file("/usr/lib/cyrus/bin/deliver")){return "/usr/lib/cyrus/bin/deliver";}
        if(is_file("/usr/sbin/cyrdelive")){return "/usr/sbin/cyrdeliver";}
        if(is_file("/opt/artica/cyrus/bin/deliver")){return "/opt/artica/cyrus/bin/deliver";}
        if(is_file("/usr/lib/cyrus-imapd/deliver")){return "/usr/lib/cyrus-imapd/deliver";}
        return "";
    }
    public function LOCATE_CYRUS_PIDPATH():string{
        if(is_file("/var/run/cyrus-master.pid")){return "/var/run/cyrus-master.pid";}
        if(is_file("/var/run/cyrmaster.pid")){return "/var/run/cyrmaster.pid";}
        if(is_file("/var/run/cyrus.pid")){return "/var/run/cyrus.pid";}
        $this->events("Unable to stat cyrus pid path");
        return "";
    }

    public function LOCATE_MILTER_GREYLIST_PID():string{
        if(is_file("/var/run/milter-greylist/milter-greylist.pid")){return "/var/run/milter-greylist/milter-greylist.pid";}
        return "";
    }


    private function VERBOSE_COMMAND_LINE($pid){
        return @file_get_contents("/proc/$pid/cmdline");
    }
    private function LOCATE_PROCSTAT():string{
        $procstat=$this->find_program("procstat");
        if(is_file($procstat)){return $procstat;}
        if(is_file("/usr/share/artica-postfix/bin/procstat")){
            @chmod("/usr/share/artica-postfix/bin/procstat",0755);
            return "/usr/share/artica-postfix/bin/procstat";}
        $gcc=$this->find_program("gcc");
        if(!is_file($gcc)){return "";}
        shell_exec("$gcc -o /usr/share/artica-postfix/bin/procstat /usr/share/artica-postfix/bin/install/procstat.c");
        if(is_file("/usr/share/artica-postfix/bin/procstat")){
            @chmod("/usr/share/artica-postfix/bin/procstat",0755);
            return "/usr/share/artica-postfix/bin/procstat";}
        return "";
    }
    public function GetSingleMemoryOf($pid):string{
        if($pid<1){
            if($GLOBALS["DEBUG"]){echo __FUNCTION__." bad pid \"$pid\"\n";}
            return "";
        }
        $rss=$this->GetMemoriesOfUniquePID($pid);
        if($this->process_exists($pid)){$l[]="installed=1";$l[]="running=1";}
        $l[]="master_pid=$pid";
        $l[]="master_memory=$rss";
        $l[]="processes_number=1";

        if(!is_file("/usr/share/artica-postfix/bin/procstat")){
            $gcc=$this->find_program("gcc");
            if(!is_file($gcc)){return "";}
            shell_exec("$gcc -o /usr/share/artica-postfix/bin/procstat /usr/share/artica-postfix/bin/install/procstat.c");
        }

        if(!is_file("/usr/share/artica-postfix/bin/procstat")){
            $this->events("Unable to compile procstat !!! in " .__CLASS__."/".__FUNCTION__." Line".__LINE__);
        }

        @chmod("/usr/share/artica-postfix/bin/procstat",0755);
        exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
        foreach ($results as $ligne){
            if(preg_match("#GLIBC.+?not found#",$ligne)){
                @unlink("/usr/share/artica-postfix/bin/procstat");
                return "";
            }

            if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
                $tt=$this->time_duration($re[1]);
                $tt=str_replace(" Months,","M",$tt);
                $tt=str_replace(" days,","d",$tt);
                $tt=str_replace(" hours,","h",$tt);
                $tt=str_replace(" minutes,","mn",$tt);
                $tt=str_replace(" seconds","s",$tt);
                $tt=str_replace(" hour,","h",$tt);
                $tt=str_replace(" minute,","mn",$tt);
                $tt=str_replace(" second","s",$tt);
                $l[]="uptime=$tt";}

        }
        $l[]="";
        return implode("\n",$l);


    }

    public function THREAD_COMMAND_SET($zcommands):bool{
        $tbl=array();
        $md5command=md5(trim($zcommands));
        if(is_file('/etc/artica-postfix/background')) {
            $FileDataCommand = @file_get_contents('/etc/artica-postfix/background');
            $tbl = explode("\n", $FileDataCommand);
        }
        if(is_array($tbl)){
            foreach ($tbl as $ligne){
                if($ligne==null){continue;}
                $mdtmp=md5(trim($ligne));
                if($md5command==$mdtmp){
                    writelogs_framework("`$zcommands` already scheduled as `$md5command`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                    return false;
                }
            }
        }
        $trace=debug_backtrace();
        foreach ($trace as $index=>$strace){
            $file=$strace["file"];$function=$strace["function"];$line=$strace["line"];
            writelogs_framework("From $function($index) line $line in $file ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        }

        writelogs_framework("Schedule this command `$zcommands`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        $tbl[]=$zcommands;
        @file_put_contents('/etc/artica-postfix/background',implode("\n",$tbl));
        return true;
    }
    public function GetMemoriesOfChild($pid){
        if($pid<1){
            if($GLOBALS["DEBUG"]){echo __FUNCTION__." bad pid \"$pid\"\n";}return null;}


        $rss=$this->GetMemoriesOfUniquePID($pid);

        $count=1;
        if($this->process_exists($pid)){$l[]="installed=1";$l[]="running=1";}
        $l[]="threads_socket=".$this->PROCESS_SOCKETS_NUM($pid);
        $l[]="application_installed=1";
        $l[]="master_pid=$pid";
        $l[]="master_memory=$rss";
        //$l[]="master_cached_memory=$vm";
        $l[]="processes_number=$count";


        exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
        foreach ($results as $ligne){
            if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
                $tt=$this->time_duration($re[1]);
                $tt=str_replace(" Months,","M",$tt);
                $tt=str_replace(" days,","d",$tt);
                $tt=str_replace(" hours,","h",$tt);
                $tt=str_replace(" minutes,","mn",$tt);
                $tt=str_replace(" seconds","s",$tt);
                $tt=str_replace(" hour,","h",$tt);
                $tt=str_replace(" minute,","mn",$tt);
                $tt=str_replace(" second","s",$tt);
                $l[]="uptime=$tt";}

        }
        $l[]="";
        return implode("\n",$l);

    }
    public function GetMemoriesOfUniquePID($pid):int{
        if(!is_numeric($pid)){return 0;}
        if($pid<2){return 0;}
        if(!is_file("/usr/share/artica-postfix/bin/meminfo.pl")){return $this->PROCESS_MEMORY($pid,true);}
        @chmod("/usr/share/artica-postfix/bin/meminfo.pl",0755);
        $line=exec("/usr/share/artica-postfix/bin/meminfo.pl $pid 2>&1");
        if(preg_match("#<(.+?)> <([0-9\.]+)\s+([A-Z]+)>#", $line,$re)){
            $UNIT=trim($re[3]);
            $VAL=round(abs(trim($re[2])));
            if($GLOBALS["VERBOSE"]){echo "GetMemoriesOfUniquePID: $re[1] : $VAL $UNIT\n";}
            if($UNIT=="KB"){return (int) $VAL;}
            if($UNIT=="MB"){return (int) $VAL*1024;}
            if($UNIT=="GB"){$VAL= (int) $VAL*1024;return $VAL*1024;}
        }
        return 0;
    }
    public function ZOMBIE_PROCESS($pid=0):bool{
        if($pid==0){return false;}
        if(!is_file("/proc/$pid/status")){return false;}
        $f=explode("\n",@file_get_contents("/proc/$pid/status"));
        foreach ($f as $line){
            if(preg_match("#^State:\s+([A-Z])#",$line,$re)){
                if($re[1]=="Z"){return true;}
                return false;
            }
        }
        return false;
    }

    function GetMemoriesOfStatus($pid,$APP_NAME=null):string{
        $max=200;
        $temp_dir="/usr/share/artica-postfix/ressources/pmemory";
        if(!is_dir($temp_dir)){mkdir("$temp_dir",0755,true);}
        $results=strval($this->GetMemoriesOf($pid));
        if($APP_NAME<>null){
            $array=array();
            $fileprocess="$temp_dir/$APP_NAME";
            if(is_file($fileprocess)) {
                $dd=@file_get_contents($fileprocess);
                if($this->is_serialized($dd)){
                    $array = unserialize($dd);
                }
            }
            $tt=explode("\n",$results);
            foreach ($tt as $line){
                if(trim($line)==null){continue;}
                $tz=explode("=",$line);
                $key=trim($tz[0]);
                if($key=="master_memory"){
                    if(is_array($array)) {
                        if (count($array) > $max) {
                            $splice = count($array) - $max;
                            array_splice($array, 0, $splice);
                        }
                    }else{
                        $array=array();
                    }
                    $array[]=trim($tz[1]);
                    @file_put_contents($fileprocess,serialize($array));
                }
            }
        }
        return $results;

    }

    function is_serialized($data) {
        // If it is not a string, it cannot be serialized data
        if (!is_string($data)) {
            return false;
        }

        // Check if the data is actually a serialized string
        return (@unserialize($data) !== false || $data === 'b:0;');
    }

    public function GetMemoriesOf($pid,$unique=false){
        $pidsrc=$pid;
        if($pid<1){
            if($GLOBALS["DEBUG"]){echo __FUNCTION__." bad pid \"$pid\"\n";}
            return null;
        }
        $this->CountOfZombies=0;
        $pid=$this->PROCESS_PPID($pid);
        if($GLOBALS["VERBOSE"]){echo "Father of $pidsrc = $pid\n";}
        $rss=$this->GetMemoriesOfUniquePID($pid);

        if(!$unique){
            $cmd=$this->find_program("pgrep")." -P $pid";
            if($GLOBALS["DEBUG"]){echo __FUNCTION__." $cmd\n";}
            $mpid=getmypid();
            exec($cmd,$results);
            $count=1;
            foreach ($results as $ligne){
                $ligne=trim($ligne);
                if($ligne<50){continue;}
                if($ligne==$mpid){continue;}
                $count=$count+1;

                if($this->ZOMBIE_PROCESS($ligne)){
                    $this->CountOfZombies++;
                    continue;
                }
                $rss0=$this->GetMemoriesOfUniquePID($ligne,true);
                $rss=$rss+$rss0;

                if($GLOBALS["DEBUG"]){echo __FUNCTION__."PID $ligne \"$count\" rss:$rss0 Process: \"". $this->VERBOSE_COMMAND_LINE($ligne)."\"\n";}

                $cmd=$this->find_program("pgrep")." -P $ligne 2>&1";
                if($GLOBALS["DEBUG"]){echo __FUNCTION__." $cmd\n";}
                $results2=array();
                exec($cmd,$results2);
                foreach ($results2 as $b){
                    $b=trim($b);
                    if($b<50){continue;}
                    if($b==$mpid){continue;}
                    $count=$count+1;
                    $rss0=$this->GetMemoriesOfUniquePID($b);
                    $rss=$rss+$rss0;

                    if($GLOBALS["DEBUG"]){echo __FUNCTION__."PID $b \"$count\" TOT:$rss; rss:$rss0 Process: \"". $this->VERBOSE_COMMAND_LINE($b)."\"\n";}
                }


            }
        }
        if($count==0){$count=1;}
        if($this->process_exists($pid)){$l[]="installed=1";$l[]="running=1";}
        $l[]="application_installed=1";
        $l[]="master_pid=$pid";
        $l[]="master_time=".$this->PROCESS_TIME_INT($pid);
        $l[]="master_memory=$rss";
        $this->MemoryOfvalue=$rss;
        // $l[]="master_cached_memory=$vm";
        $l[]="processes_number=$count";

        @chmod("/usr/share/artica-postfix/bin/procstat",0755);
        exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
                $tt=$this->time_duration($re[1]);
                $tt=str_replace(" Months,","M",$tt);
                $tt=str_replace(" days,","d",$tt);
                $tt=str_replace(" hours,","h",$tt);
                $tt=str_replace(" minutes,","mn",$tt);
                $tt=str_replace(" seconds","s",$tt);
                $tt=str_replace(" hour,","h",$tt);
                $tt=str_replace(" minute,","mn",$tt);
                $tt=str_replace(" second","s",$tt);
                $l[]="uptime=$tt";}

        }
        $l[]="";
        return implode("\n",$l);

    }
    public function PROCESS_TTL_TEXT($pid):string{
        if(!is_file("/usr/share/artica-postfix/bin/procstat")){return "";}
        @chmod("/usr/share/artica-postfix/bin/procstat",0755);
        exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
        foreach ($results as $ligne){
            if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
                $tt=$this->time_duration($re[1]);
                $tt=str_replace(" Months,","M",$tt);
                $tt=str_replace(" days,","d",$tt);
                $tt=str_replace(" hours,","h",$tt);
                $tt=str_replace(" minutes,","mn",$tt);
                $tt=str_replace(" seconds","s",$tt);
                $tt=str_replace(" hour,","h",$tt);
                $tt=str_replace(" minute,","mn",$tt);
                $tt=str_replace(" second","s",$tt);
                return $tt;
            }

        }
        return "";
    }
    public function MYSQL_DATA_DIR(){
        $sock=new sockets();
        $ChangeMysqlDir=$sock->GET_INFO("ChangeMysqlDir");
        if($ChangeMysqlDir==null){$ChangeMysqlDir="/var/lib/mysql";}
        return $ChangeMysqlDir;
    }
    public function PROCESS_TTL($pid):int{
        if($pid<2){return 0;}
        exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
        foreach ($results as $ligne){
            if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
                $seconds=$re[1];
                return round(($seconds/60));
            }
        }
        return 0;
    }
    public function vhosts_BuildCertificate($hostname){

        if($hostname=="__default__"){$hostnameText=$this->hostname_g();}else{$hostnameText=$hostname;}

        $dir=$GLOBALS["SSLKEY_PATH"];
        if(is_file("$dir/$hostname.crt")){
            if(is_file("$dir/$hostname.key")){return true;}
        }

        if(!is_dir($dir)){@mkdir($dir,0600,true);}

        $sock=new sockets();
        $CertificateMaxDays=$sock->GET_INFO('CertificateMaxDays');
        if($CertificateMaxDays==null){$CertificateMaxDays=730;}
        if(!class_exists("Bs_IniHandler")){
            $ini=new iniFrameWork();
        }else{
            $ini=new Bs_IniHandler();
        }

        $ini->loadFile("/etc/artica-postfix/ssl.certificate.conf");
        $conf="/etc/ssl/certs/apache/$hostname.conf";
        $openssl=$this->find_program("openssl");
        echo "Starting......: ".date("H:i:s")." Certificate \"$conf\" ". count($ini->_params) ." Keys\n";

        unset($ini->_params["HOSTS_ADDONS"]);
        $ini->_params["default_db"]["default_days"]=$CertificateMaxDays;
        $ini->_params["server_policy"]["commonName"]=$hostnameText;
        $ini->_params["user_policy"]["commonName"]=$hostnameText;
        $ini->_params["default_ca"]["commonName"]=$hostnameText;
        $ini->_params["default_ca"]["commonName_value"]=$hostnameText;
        $ini->_params["policy_match"]["commonName"]=$hostnameText;
        $ini->_params["policy_anything"]["commonName"]=$hostnameText;
        reset($ini->_params);
        $ini->saveFile($conf);
        $cmd="$openssl req -new -x509 -batch -config $conf -nodes -out $dir/$hostname.crt -keyout $dir/$hostname.key -days $CertificateMaxDays 2>&1";
        exec($cmd,$results);
        if($GLOBALS["VERBOSE"]){
            echo "$cmd\n".@implode("\n",$results)."\n";
        }

    }
    public function getLOCALEUSED(){
        $locale=$this->find_program("env");
        writelogs_framework("$locale 2>&1",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
        exec("$locale 2>&1",$results);
        foreach ($results as $num=>$ligne){
            writelogs_framework("$ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
            if(preg_match("#LANG=(.+)#",$ligne,$re)){return trim($re[1]);}
        }

        shell_exec("LANG=en_US.utf8");
        return "en_US.utf8";
    }
    public function SSHD_GET_FINGERPRINT($path):string{
        $path=$this->shellEscapeChars($path);
        $sshkeygen=$this->find_program("ssh-keygen");
        $maincmd="$sshkeygen -lf $path";
        writelogs_framework("$maincmd 2>&1" ,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        exec($maincmd,$results);
        foreach ($results as $ligne){
            writelogs_framework("$ligne" ,__FUNCTION__,__FILE__,__LINE__);
            if(preg_match("#^[0-9]+\s+(.+?)\s+#",$ligne,$re)){
                writelogs_framework("FOUND {$re[1]}" ,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                return $re[1];
            }
        }
        return "";
    }
    public function KERNEL_CONFIG():array{
        $uname=$this->find_program("uname");
        if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." uname= $uname\n";}
        exec("$uname -r 2>&1",$results);
        $kernelver=trim(@implode("", $results));
        if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." kernelver = $kernelver\n";}
        $file="/boot/config-$kernelver";
        if(!is_file($file)){
            writelogs_framework("$file no such file" ,__FUNCTION__,__FILE__,__LINE__);
            if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." $file no such file\n";}}
        $t=explode("\n",@file_get_contents($file));

        writelogs_framework("Open $file" ,__FUNCTION__,__FILE__,__LINE__);
        $Array=array();
        foreach ($t as $ligne){
            if(!preg_match("#^([A-Z_\-]+)=(.+)#", $ligne,$re)){continue;}
            if(trim($re[2])=="y"){$re[2]=1;}
            if(trim($re[2])=="m"){$re[2]=1;}
            $Array[trim($re[1])]=trim($re[2]);

        }

        return $Array;

    }
    public function KERNEL_VERSION(){
        if(isset($GLOBALS["KERNEL_VERSION"])){return $GLOBALS["KERNEL_VERSION"];}
        $GLOBALS["KERNEL_VERSION"]=php_uname("r");
        return trim($GLOBALS["KERNEL_VERSION"]);
    }
    public function KERNEL_VERSION_FULL(){
        if(isset($GLOBALS["KERNEL_VERSION_FULL"])){return $GLOBALS["KERNEL_VERSION_FULL"];}
        $GLOBALS["KERNEL_VERSION_FULL"]=php_uname("a");
        return trim($GLOBALS["KERNEL_VERSION_FULL"]);
    }
    public function KERNEL_ARCH(){
        $results=$this->KERNEL_VERSION();
        if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." $results\n";}
        if(preg_match("#i[0-9]86#", $results)){return 32;}
        if(preg_match("#686-bigmem#", $results)){return 32;}
        if(preg_match("#x86_64#", $results)){return 64;}
        if(preg_match("#-amd64#", $results)){return 64;}

        $results=$this->KERNEL_VERSION_FULL();
        if(preg_match("#i[0-9]86#", $results)){return 32;}
        if(preg_match("#686-bigmem#", $results)){return 32;}
        if(preg_match("#x86_64#", $results)){return 64;}
        if(preg_match("#-amd64#", $results)){return 64;}

    }
    public function MYSQL_BIN_PARSE_FILE($filepath){

        $f=explode("\n",@file_get_contents($filepath));
        if($this->MYSQL_BIN_PARSE_ERROR($f[0])){return true;}
        if($this->MYSQL_BIN_PARSE_ERROR($f[1])){return true;}
    }
    public function MYSQL_BIN_PARSE_ERROR($text){
        if(!is_array($text)){
            if(is_file($text)){$text=@file_get_contents($text);}
            $f=explode("\n", $text);
        }else{
            $f=$text;
        }

        foreach ($f as $num=>$ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(preg_match("#already exists#", $ligne)){continue;}
            if($GLOBALS["VERBOSE"]){echo "MYSQL_BIN_PARSE_ERROR:: `$ligne`\n";}

            if(preg_match("#: Got errno [0-9]+ on write#",$ligne)){
                $this->mysql_error=$ligne;
                return true;
            }

            if(preg_match("#Got error:\s+([0-9]+):#", $ligne)){
                $this->mysql_error=$ligne;
                return true;
            }

            if(preg_match("#^ERROR\s+([0-9]+)#", $ligne)){
                $this->mysql_error=$ligne;
                return true;
            }

            if(preg_match("#^ERROR:\s+[A-Z]+#", $ligne)){
                $this->mysql_error=$ligne;
                return true;
            }


            if($GLOBALS["OUTPUT"]){echo "MySQL: `$ligne` (no match)\n";}


        }

        return false;

    }

    public function RELOAD_WEBCONSOLE():bool{
        shell_exec("/usr/local/ArticaWebConsole/sbin/artica-webconsole -c /etc/artica-postfix/webconsole.conf -s reopen");
        shell_exec("/usr/local/ArticaWebConsole/sbin/artica-webconsole -c /etc/artica-postfix/webconsole.conf -s reload");
        return true;
    }

    public function RESTART_SYSLOG($onlysyslog=false):bool{
        $cmd="/usr/sbin/artica-phpfpm-service -reload-syslog -php";
        $ln=$this->sh_command($cmd);
        $this->go_exec($ln);
        if($onlysyslog){return true;}

        if(is_file("/etc/init.d/artica-syslog")) {
            $this->go_exec("/etc/init.d/artica-syslog restart");
        }
        if(is_file("/etc/init.d/postfix-logger")) {
            $this->go_exec("/etc/init.d/postfix-logger restart");
        }

        if(is_file("/etc/init.d/ufdb-tail")) {
            squid_admin_mysql(2,"{restarting} Udbguard-tail after Restarting Syslog service", null,__FILE__,__LINE__);
            $this->go_exec("/etc/init.d/ufdb-tail restart");
        }
        if(is_file("/etc/init.d/ufdb-tail")) {
            $this->go_exec("/etc/init.d/ufdb-tail restart");
        }
        if(is_file("/etc/init.d/auth-tail")) {
            $this->go_exec("/etc/init.d/auth-tail restart");
        }
        return true;

    }
    public function send_email_events($subject,$text,$context,$date=null,$attached_files=array(),$recipient=null){

        if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/events",0755,true);}

        $arr_APP=array(
            "APP_ARTICA_BACKGROUND"=>"Artica Background Executor",
            "APP_OPENEMM_SENDMAIL"=>"SendMail for OpenEMM service",
            "APP_OPENEMM"=>"OpenEMM service",
            "APP_TOMCAT"=>"Tomcat Web server",
            "APP_GREENSQL"=>"GreenSQL Firewall",
            "APP_ZARAFA_WEB"=>"Zarafa web server",
            "APP_ZARAFA_SERVER"=>"Zarafa server",
            "APP_ZARAFA_SPOOLER"=>"Zarafa Spooler",
            "APP_ZARAFA_DAGENT"=>"Zarafa Delivery Agent",
            "APP_ZARAFA_GATEWAY"=>"Zarafa Gateway",
            "APP_ZARAFA_MONITOR"=>"Zarafa Monitor",
            "APP_ZARAFA_ICAL"=>"iCal, CalDav Gateway",
            "APP_ZARAFA_LICENSED"=>"Zarafa license service",
            "APP_ZARAFA"=>"Zarafa Mail server",
            "APP_SNORT"=>"Intrusion Detection System (Snort)",
            "APP_OCSI"=>"OCS Inventory",
            "APP_ARTICA_AUTH_TAIL"=>"Authentication Watchdog",
            "APP_VNSTAT"=>"VNStat",
            "APP_POSTFWD2"=>"Postfix Postfwd2",
            "APP_VMTOOLS"=>"VMWare tools",
            "APP_IETD"=>"iSCSI service",
            "APP_GREYHOLE"=>"Greyhole Samba relatime Backup",
            "APP_AUTOFS"=>"AutoFS, Automount service",
            "APP_MUNIN"=>"Munin Statistics Client",
            "APP_BACKUPPC"=>"BackupPC Daemon",
            "APP_STUNNEL"=>"sTunnel daemon",

            "APP_UFDBGUARD_TAIL"=>"UfdbGuard Watchdog",
            "APP_OPENVPN"=>"OpenVPN Server",
            "OPENVPN_SERVER"=>"OpenVPN server",
            "APP_VBOXADDITIONS"=>"VirtualBox guest additions",


            "APP_GLUSTER"=>"Gluster Cluster daemon",
            "APP_APACHE_SRC"=>"Apache source Daemon",
            "APP_CLUEBRINGER"=>"ClueBringer (Policyd V2)",
            "APP_DDCLIENT"=>"Dynamic DNS Client",
            "APP_SQUID_CLAMAV_TAIL"=>"squidclamav RealTime log",
            "APP_ARTICA_SQUID_TAIL"=>"Squid RealTime log",
            "APP_TFTPD"=>"Trivial File Transfer Protocol service",
            "APP_POLICYD_WEIGHT"=>"Policy Daemon for Postfix",
            "APP_VIRTUALBOX_WEBSERVICE"=>"VirtualBox Web service",
            "APP_MILTER_DKIM"=>"Milter-DKIM service",
            "APP_DROPBOX"=>"DropBox service",
            "APP_ARTICA_SQUIDGUARDTAIL"=>"squidGuard watchdog daemon",
            "APP_UFDBGUARD"=>"UfdbGuard Filtering service",
            "APP_OPENDKIM"=>"OpenDKIM Filter service",
            "APP_SQUIDGUARD_HTTP"=>"SquidGuard HTTP service",
            "APP_DNSMASQ"=>"DNS forwarder caching",
            "APP_SMARTMONTOOLS"=>"SMART hard disk monitoring",
            "APP_AUDITD"=>"Auditd Daemon",
            "APP_OPENSSH"=>"OpenSSH server",
            "APP_KAV4FS"=>"Kaspersky For File server",
            "APP_DHCP"=>"DHCP Server",
            "APP_ARTICA_NOTIFIER"=>"Artica SMTP Notifier",
            "APP_GROUPWARE_APACHE"=>"Apache for groupware applications",
            "APP_ROUNDCUBE"=>"Web service For RoundCube",
            "APP_ZABBIX_SERVER"=>"Zabbix server",
            "APP_MYSQL_ARTICA"=>"Mysql server",
            "APP_ARTICA_MYSQMAIL"=>"Postfix Monitor",
            "APP_ARTICA_DANSGUARDIAN_TAIL"=>"DansGuardian Monitor",
            "APP_ARTICA_WATCHDOG"=>"Watchdog Daemon",
            "APP_ASSP"=>"Anti-Spam SMTP Proxy",
            "APP_BOA"=>"Boa Daemon",
            "APP_CLAMAV"=>"ClamAV Daemon",
            "APP_CUPS"=>"Printer service",
            "APP_CYRUS"=>"Cyrus-imap service",
            "APP_DANSGUARDIAN"=>"DansGuardian",
            "APP_FETCHMAIL_LOGGER"=>"Fetchmail Monitor",
            "APP_FRAMEWORK"=>"Artica FrameWork",
            "APP_FETCHMAIL"=>"Fetchmail Daemon",
            "APP_MAILMAN"=>"Mailman mailinglist Daemon",
            "APP_FRESHCLAM"=>"ClamAV Updater",
            "APP_KAVMILTER"=>"Kaspersky Anti-virus For Mail",
            "APP_LDAP"=>"OpenLDAP service",
            "APP_LIGHTTPD"=>"Artica Web server",
            "APP_MONIT"=>"Watchdog system",
            "APP_RSYNC"=>"Rsync service",
            "APP_PDNS_RECURSOR"=>"PowerDNS external query ",
            "APP_PDNS"=>"PowerDNS service",
            "APP_POSTFIX"=>"Postfix",
            "APP_SAMBA_NMBD"=>"Samba NetBIOS name service",
            "APP_SAMBA_WINBIND"=>"Samba Name Service Switch",
            "APP_SAMBA_SMBD"=>"Samba Filesharing and printing services",
            "APP_SASLAUTHD"=>"System Authentication",
            "APP_SPAMASSASSIN"=>"SpamAssassin",
            "APP_SQUID"=>"Proxy service (squid)",
            "APP_SYSLOGER"=>"Artica System monitor",
            "APP_C_ICAP"=>"C-ICAP For Squid",
            "APP_SCANNED_ONLY"=>"Samba Antivirus Scanner",
            "APP_PUREFTPD"=>"FTP Service",
            "APP_MILTERGREYLIST"=>"Greylist service for Postfix",
            "APP_AMAVISD_MILTER"=>"Amavis listener milter",
            "APP_AMAVISD_NEW"=>"Amavis Daemon",
            "APP_CLAMAV_MILTER"=>"ClamAV For Postfix",
            "APP_MAILSPY"=>"MailSpy",
            "APP_ARTICA"=>"Artica service",


        );

        foreach ($arr_APP as $tok=>$service_name){
            $subject=str_replace($tok,$service_name,$subject);
            $text=str_replace($tok,$service_name,$text);
        }

        $internal_load=null;



        if(function_exists("sys_getloadavg")){
            $array_load=sys_getloadavg();
            $internal_load=" - system load: {$array_load[0]}";
        }

        $filename=basename(__FILE__);
        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();
            $debug_backtrace_array=$this->debug_backtrace_array($trace);
            $function="MAIN";
            $line=0;
            if(isset($debug_backtrace_array["file"])){
                $filename=($debug_backtrace_array["file"]);
            }
            if(isset($debug_backtrace_array["function"])){
                $function=" - function {$debug_backtrace_array["function"]}()";
            }
            if(isset($debug_backtrace_array["line"])){
                $function=$function." line {$debug_backtrace_array["line"]}";
            }
        }


        squid_admin_mysql(1,"[$context]: $subject","$text\n$internal_load\n$function",$filename,0);
        ;
    }
    private function debug_backtrace_array($trace){

        if(isset($trace[1])){
            if(isset($trace[1]["file"])){ $filename=basename($trace[1]["file"]); }
            if(isset($trace[1]["function"])){$function=$trace[1]["function"]; }
            if(isset($trace[1]["line"])){ $line=$trace[1]["line"];}
            return array("function"=>$function,"file"=>$filename,"line"=>$line);

        }

        if(isset($trace[0])){
            if(isset($trace[0]["file"])){$filename=basename($trace[0]["file"]);}
            if(isset($trace[0]["function"])){$function=$trace[0]["function"];}
            if(isset($trace[0]["line"])){$line=$trace[0]["line"];}
            return array("function"=>$function,"file"=>$filename,"line"=>$line);
        }



        return array();

    }
    private function time_duration($seconds, $use = null, $zeros = false){
        // Define time periods
        $periods = array (
            'years'     => 31556926,
            'Months'    => 2629743,
            'weeks'     => 604800,
            'days'      => 86400,
            'hours'     => 3600,
            'minutes'   => 60,
            'seconds'   => 1
        );

        // Break into periods
        $seconds = (float) $seconds;
        $segments = array();
        foreach ($periods as $period => $value) {
            if ($use && strpos($use, $period[0]) === false) {
                continue;
            }
            $count = floor($seconds / $value);
            if ($count == 0 && !$zeros) {
                continue;
            }
            $segments[strtolower($period)] = $count;
            $seconds = $seconds % $value;
        }

        // Build the string
        $string = array();
        foreach ($segments as $key => $value) {
            $segment_name = substr($key, 0, -1);
            $segment = $value . ' ' . $segment_name;
            if ($value != 1) {
                $segment .= 's';
            }
            $string[] = $segment;
        }

        return implode(', ', $string);
    }
    public function GetVersionOf($name){
        echo "GetVersionOf($name) is outdated !!!\n";
    }




    public function shellEscapeChars($path){
        $path=str_replace(" ","\ ",$path);
        $path=str_replace("`","\`",$path);
        $path=str_replace('$','\$',$path);
        $path=str_replace("&","\&",$path);
        $path=str_replace("?","\?",$path);
        $path=str_replace("#","\#",$path);
        $path=str_replace("[","\[",$path);
        $path=str_replace("]","\]",$path);
        $path=str_replace("{","\{",$path);
        $path=str_replace("}","\}",$path);
        //$path=str_replace("*","\*",$path);
        $path=str_replace('"','\\"',$path);
        $path=str_replace("'","\\'",$path);
        $path=str_replace("(","\(",$path);
        $path=str_replace(")","\)",$path);
        $path=str_replace("<","\<",$path);
        $path=str_replace(">","\>",$path);
        $path=str_replace("!","\!",$path);
        $path=str_replace("+","\+",$path);
        $path=str_replace(";","\;",$path);
        $path=str_replace("|","\|",$path);
        $path=str_replace("%","\%",$path);
        return $path;
    }

    function SLAPD_CONF_PATH(){
        if(is_file('/etc/ldap/slapd.conf')){return '/etc/ldap/slapd.conf';}
        if(is_file('/etc/openldap/slapd.conf')){return '/etc/openldap/slapd.conf';}
        if(is_file('/etc/openldap/ldap.conf')){return '/etc/openldap/slapd.conf';}
        if(is_file('/opt/artica/etc/openldap/slapd.conf')){return '/opt/artica/etc/openldap/slapd.conf';}
        if(is_file('/usr/local/etc/openldap/slapd.conf')){return '/usr/local/etc/openldap/slapd.conf';}
        return '/etc/ldap/slapd.conf';
    }
    function SLAPD_PID_PATH():string{
        if(is_file('/var/run/slapd/slapd.pid')){return '/var/run/slapd/slapd.pid';}
        if(is_file('/var/run/openldap/slapd.pid')){ return '/var/run/openldap/slapd.pid';}

        $conffile=$this->SLAPD_CONF_PATH();
        if(!is_file($conffile)){return "/var/run/slapd/slapd.pid";}
        $f=explode("\n",@file_get_contents($conffile));
        foreach ($f as $line){
            if(preg_match('#pidfile\s+(.+)#',$line,$re)){return trim($re[1]);}
        }

        return('/var/run/slapd/slapd.pid');
    }

    public function APACHE_GROUPWARE_ACCOUNT():string{
        $tbl=explode("\n",@file_get_contents("/usr/local/apache-groupware/conf/apache-groupware.conf"));
        foreach ($tbl as $line){
            if(preg_match("#User\s+(.+)#",$line,$re)){
                return "{$re[1]}:{$re[1]}";
            }

        }
        return "";
    }

    public function IS_IPADDR_EXISTS($ipaddr):bool{
        $ipaddr=trim($ipaddr);
        if($ipaddr==null){return false;}
        if(!isset($GLOBALS["IS_IPADDR_EXISTS_ARRAY"])){
            $ifconfig=$this->find_program("ifconfig");
            exec("$ifconfig -a 2>&1",$GLOBALS["IS_IPADDR_EXISTS_ARRAY"]);
        }
        $ipaddrregex=str_replace(".", "\.", $ipaddr);
        reset($GLOBALS["IS_IPADDR_EXISTS_ARRAY"]);
        foreach ($GLOBALS["IS_IPADDR_EXISTS_ARRAY"] as $num=>$line){
            if(preg_match("#$ipaddrregex#",$line)){return true;}
        }
        $keepalived_enable = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE");
        $keepalived_enable_slave = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE");
        if (intval($keepalived_enable) == 1 || intval($keepalived_enable_slave) == 1) {
            $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
            $sql = "SELECT COUNT(*) as tcount FROM `keepalived_virtual_interfaces` WHERE virtual_interface='$ipaddr' AND enable='1'";
            $results_sql = $q->mysqli_fetch_array($sql);
            if (!$q->ok) {
                return false;
            }
            if(intval($results_sql['tcount'])>0){
                return true;
            }
        }
        return false;

    }




    public function APACHE_SRC_ACCOUNT(){
        if(isset($GLOBALS["APACHE_SRC_ACCOUNT"])){return $GLOBALS["APACHE_SRC_ACCOUNT"];}
        $GLOBALS["APACHE_SRC_ACCOUNT"]=$this->_APACHE_SRC_ACCOUNT();
        if($GLOBALS["APACHE_SRC_ACCOUNT"]=='${APACHE_RUN_USER}'){$GLOBALS["APACHE_SRC_ACCOUNT"]="www-data";}
        return $GLOBALS["APACHE_SRC_ACCOUNT"];
    }

    private function _APACHE_SRC_ACCOUNT():string{
        if(is_file("/etc/artica-postfix/settings/Daemons/ApacheSRCAccount")){
            $data=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ApacheSRCAccount"));
            if(strlen($data)>3){return $data;}
        }


        $httpd_conf=$this->LOCATE_APACHE_CONF_PATH();
        if(is_null($httpd_conf)){return "www-data";}
        $envars=dirname($httpd_conf)."/envvars";
        if(is_file($envars)){
            $tbl=explode("\n",@file_get_contents($envars));
            foreach ($tbl as $line){
                if(preg_match("#export APACHE_RUN_USER=(.+)#",$line,$re)){
                    return trim($re[1]);
                }
            }
        }
        if(is_file($httpd_conf)){
            $tbl=explode("\n",@file_get_contents($httpd_conf));
            foreach ($tbl as $line){
                    if(preg_match("#^User\s+(.+)#",$line,$re)){
                        if(strpos($re[1], '"')==0){
                            return trim($re[1]);
                        }
                    }
            }
        }

        if(is_file("/etc/apache2/uid.conf")){
            $tbl=explode("\n",@file_get_contents("/etc/apache2/uid.conf"));
            foreach ($tbl as $line){
                if(preg_match("#^User\s+(.+)#",$line,$re)){
                    return trim($re[1]);
                }
            }
        }

        $tbl=explode("\n",@file_get_contents("/etc/passwd"));
        foreach ($tbl as $line){
            if(preg_match("#^(.+?):x:[0-9]+:[0-9]+:(.+?):\/var\/www:#",$line,$re)){
                return trim($re[1]);
            }
        }

        return "www-data";


    }
    public function APACHE_SRC_GROUP(){
        if(isset($GLOBALS["APACHE_SRC_GROUP"])){
            if(preg_match("#APACHE_RUN_GROUP#", $GLOBALS["APACHE_SRC_GROUP"])){$GLOBALS["APACHE_SRC_GROUP"]="www-data";}
            return $GLOBALS["APACHE_SRC_GROUP"];
        }
        $GLOBALS["APACHE_SRC_GROUP"]=$this->_APACHE_SRC_GROUP();
        if(preg_match("#APACHE_RUN_GROUP#", $GLOBALS["APACHE_SRC_GROUP"])){$GLOBALS["APACHE_SRC_GROUP"]="www-data";}
        return $GLOBALS["APACHE_SRC_GROUP"];
    }
    private function _APACHE_SRC_GROUP():string{
        if(is_file("/etc/artica-postfix/settings/Daemons/ApacheSRCGroup")){
            $data=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ApacheSRCGroup"));
            if(strlen($data)>3){
                if($data=='${APACHE_RUN_GROUP}'){$data="www-data";}
                if($data=='${APACHE_GROUP}'){$data="www-data";}
                $data=str_replace('${APACHE_RUN_GROUP}','www-data',$data);
                $data=str_replace('${APACHE_GROUP}','www-data',$data);
                if(preg_match("#APACHE_RUN_GROUP#", $data)){$data="www-data";}
                return $data;
            }
            return "www-data";
        }

        $httpd_conf=$this->LOCATE_APACHE_CONF_PATH();
        if(!is_null($httpd_conf)) {
            if (is_file($httpd_conf)) {
                $tbl = explode("\n", @file_get_contents($httpd_conf));
                foreach ($tbl as $line) {
                    if (preg_match("#^Group\s+(.+)#", $line, $re)) {
                        if (strpos($re[1], '"') == 0) {
                            return trim($re[1]);
                        }

                    }
                }
            }
        }

        if(is_file("/etc/apache2/uid.conf")){
            $tbl=explode("\n",@file_get_contents("/etc/apache2/uid.conf"));
            foreach ($tbl as $line){
                if(preg_match("#^Group\s+(.+)#",$line,$re)){
                    $data=trim($re[1]);
                    if($data=='${APACHE_RUN_GROUP}'){return "www-data";}
                }
            }
        }

        $tbl=explode("\n",@file_get_contents("/etc/passwd"));
        foreach ($tbl as $line){
            if(preg_match("#^(.+?):x:[0-9]+:[0-9]+:(.+?):\/var\/www:#",$line,$re)){
                $data=trim($re[1]);
                if($data=='${APACHE_RUN_GROUP}'){return "www-data";}
            }
        }


        return $this->_APACHE_SRC_ACCOUNT();

    }

    public function GetAvailableListenIP(){
        if(isset($GLOBALS["GetAvailableListenIP"])){return $GLOBALS["GetAvailableListenIP"];}
        $unix=new unix();
        $ifconfig=$unix->find_program("ifconfig");
        exec("$ifconfig -a 2>&1",$results);
        foreach ($results as $num=>$line){
            if(preg_match("#inet addr.*?([0-9\.]+)#", $line,$re)){

                if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." available network: {$re[1]}\n";}
                $GLOBALS["GetAvailableListenIP"][$re[1]]=$re[1];
                continue;
            }
            if(preg_match("#inet6 addr:\s+(.*?)\/#", $line,$re)){
                if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." available network: {$re[1]}\n";}
                $GLOBALS["GetAvailableListenIP"][$re[1]]=$re[1];
                continue;
            }

            if($GLOBALS["VERBOSE"]){echo "GetAvailableListenIP() no match `$line`\n";}
        }
        if(count($GLOBALS["GetAvailableListenIP"])==0){echo "Starting......: ".date("H:i:s")." No IP ????\n";}
        return $GLOBALS["GetAvailableListenIP"];

    }
    public function SQUID_ACTIVE_REQUESTS(){

        $CacheFile="/usr/share/artica-postfix/ressources/logs/active_requests.inc";
        if(isset($GLOBALS["squidclient"]["active_requests"])){return $GLOBALS["squidclient"]["active_requests"];}
        $CacheTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientCacheTime"));
        if($CacheTime==0){$CacheTime=30;}

        if(is_file($CacheFile)){
            $xtime=$this->file_time_min($CacheFile);
            if($xtime<$CacheTime){
                return unserialize(@file_get_contents($CacheFile));
            }
        }




        $manager=new cache_manager();
        return $manager->active_requests();

    }




    public function isNGnx(){
        if(isset($GLOBALS["isNGnx"])){return $GLOBALS["isNGnx"];}
        $nginx=$this->find_program("nginx");

        if(!is_file($nginx)){$GLOBALS["isNGnx"]=false;return $GLOBALS["isNGnx"];}
        $sock=new sockets();
        $EnableNginx=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
        if(!is_numeric($EnableNginx)){$EnableNginx=1;}
        if($EnableNginx==1){
            $GLOBALS["isNGnx"]=true;
            return $GLOBALS["isNGnx"];
        }

        $GLOBALS["isNGnx"]=false;
        return $GLOBALS["isNGnx"];

    }


    private function debug($text){

        if(!$GLOBALS["VERBOSE"]){return;}
        if(function_exists("debug_backtrace")){
            $trace=@debug_backtrace();
            $debug_backtrace=$this->debug_backtrace_array($trace);
            $function="{$debug_backtrace["function"]}()";
            $line=$debug_backtrace["line"];
        }


        echo __CLASS__."/$function [$line]: $text\n";
    }


    function dskspace_bytes($dir_name,$nocache=false){
        $EXEC_NICE=$this->EXEC_NICE();
        $du=$this->find_program("du");
        if(is_link($dir_name)){$dir_name=readlink($dir_name);}
        if(!is_dir($dir_name)){return 0;}
        $mdkey=md5($dir_name);
        $cache_file="/etc/artica-postfix/DIRSIZE_MB_CACHE/$mdkey.bytes";

        if(!$nocache){
            if(!is_dir("/etc/artica-postfix/DIRSIZE_MB_CACHE")){@mkdir("/etc/artica-postfix/DIRSIZE_MB_CACHE",0755,true);}
            $Time=$this->file_time_min($cache_file);
            if($Time<60){return trim(@file_get_contents("$cache_file"));}
            @unlink($cache_file);
        }

        if(!function_exists("system_is_overloaded")){
            include_once("/usr/share/artica-postfix/ressources/class.os.system.inc");
        }
        if(function_exists("system_is_overloaded")){
            if(system_is_overloaded()){return 0;}
        }


        $dir_name_regex=str_replace("/", "\/", $dir_name);
        $dir_name_regex=str_replace(".", "\.", $dir_name_regex);
        $pid=$this->PIDOF_PATTERN("du -bs $dir_name_regex");
        if($this->process_exists($pid)){return 0;}

        if(is_file($du)){
            exec("$EXEC_NICE$du -bs $dir_name 2>&1",$results);
            foreach ($results as $ligne){
                $ligne=trim($ligne);
                if($ligne==null){continue;}
                if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){
                    @file_put_contents($cache_file, $re[1]);
                    return $re[1];
                }


            }
            return 0;
        }


        $dir_size =0;

        if ($dh = opendir($dir_name)) {
            while (($file = readdir($dh)) !== false) {
                if(is_link($dir_name."/".$file)){continue;}
                if($file !="." && $file != ".."){
                    if(is_file($dir_name."/".$file)){
                        $dir_size += filesize($dir_name."/".$file);
                    }
                    /* check for any new directory inside this directory */
                    if(is_dir($dir_name."/".$file)){
                        $dir_size +=  $this->dskspace_bytes($dir_name."/".$file);
                    }
                }
            }
        }

        closedir($dh);
        return $dir_size;
    }



    public function is_interface_available($INTERFACE){

        if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$INTERFACE)){
            if($this->NETWORK_IS_LISTEN_ADDR_EXISTS($INTERFACE)){return true;}
            return false;
        }


        $INTERFACE=trim(strtolower($INTERFACE));
        $datas=explode("\n",@file_get_contents("/proc/net/dev"));
        foreach ($datas as $line){
            if (!preg_match("#^(.+?):#", $line, $re)) {continue;}
            $int=trim(strtolower($re[1]));
            if($int==$INTERFACE){return true;}
        }

        if(!isset($GLOBALS["INTERFACES"])){$GLOBALS["INTERFACES"]=$this->NETWORK_ALL_INTERFACES();}
        if(!isset($GLOBALS["INTERFACES"][$INTERFACE]["IPADDR"])){return false;}
        $STATE=$GLOBALS["INTERFACES"][$INTERFACE]["STATE"];
        if($STATE=="DOWN"){return false;}
        return true;


    }

    public function NETWORK_IS_LISTEN_ADDR_EXISTS($ipaddr){
        $NETWORK_ALL_INTERFACES=$this->NETWORK_ALL_INTERFACES(true);
        if(isset($NETWORK_ALL_INTERFACES[$ipaddr])){return true;}
        return false;
    }

    public function NETWORK_DEFAULT_LISTEN_ADDR(){
        return $this->NETWORK_DEFAULT_IP_ADDR();
    }


    private function ifconfig_interface($Interface,$array){
        $ip=$this->find_program("ifconfig");
        exec("$ip $Interface 2>&1",$results);
        foreach ($results as $num=>$ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(preg_match("#^inet\s+[A-za-z]+:([0-9\.]+)\s+[A-za-z]+:([0-9\.]+)\s+[A-za-z]+:([0-9\.]+)#",$ligne,$re)){
                $array[$Interface]["NETMASK"]=$re[3];
                $array[$Interface]["BROADCAST"]=$re[2];
            }
        }
        return $array;
    }

    public function NETWORK_IFNAME_TO_IP($eth):string{
        $eth=trim($eth);
        $NETWORK_ALL_INTERFACES=$this->NETWORK_ALL_INTERFACES();
        if(!isset($NETWORK_ALL_INTERFACES[$eth])){return "";}
        if($NETWORK_ALL_INTERFACES[$eth]["IPADDR"]=="0.0.0.0"){return "";}
        return strval($NETWORK_ALL_INTERFACES[$eth]["IPADDR"]);

    }

    public function NETWORK_ALL_INTERFACES_DEB9($OnlyIPS=false){
        $array=array();
        $IPTABLE=array();
        $iplist="/usr/share/artica-postfix/bin/iplist.py";
        chmod($iplist,0755);
        exec("$iplist 2>&1",$ips);
        foreach ($ips as $line){
            $zip=explode("||",$line);
            $Interface=$zip[0];
            $array[$Interface]["INTERFACE"]=$Interface;
            $array[$Interface]["IPADDR"]=$zip[1];
            $IPTABLE[$zip[1]]=true;
            $maindir="/sys/class/net/$Interface";
            if(!is_dir($maindir)){continue;}
            $array[$Interface]["STATE"]="no";
            $array[$Interface]["PROMISC"]=false;
            $array[$Interface]["MAC"]=trim(@file_get_contents("$maindir/address"));
            $operstate=strtoupper(trim(@file_get_contents("$maindir/operstate")));
            if($operstate=="UP"){
                $array[$Interface]["STATE"]="yes";
            }
        }
        if($OnlyIPS){return $IPTABLE;}
        $ip=$this->find_program("ip");
        exec("$ip route list 2>&1",$results);

        foreach ($results as $ligne){
            if(preg_match("#default via\s+([0-9\.]+)\s+dev\s+([0-9A-Za-z\-\_]+)#", $ligne,$re)){
                $Interface=$re[2];
                if(preg_match("#^(.+?)@#", $Interface,$ri)){$Interface=$ri[1];}
                if($Interface==null){continue;}
                $array[$Interface]["GATEWAY"]=$re[1];

            }
        }


        return $array;
    }


    public function CleanInterfacesForBondMembers($eth,$ipaddr,$netmask):string{
        //Reset interfaces members of bound
        include_once(dirname(__FILE__)."/class.tcpip-parser.inc");
        $ip=$this->find_program("ip");
        $tcp = new Unixipv4($ipaddr, $netmask);
        $net = $tcp->netmask();
        $perfix = $tcp->NetMaskToCdir();
        $perfix = explode("/",$perfix);
        return "{$ipaddr}/{$perfix[1]}";
//        writelogs("PERFORMING $ip addr del {$ipaddr}/{$perfix[1]} dev {$eth}",__FUNCTION__,__FILE__,__LINE__);
//        exec("$ip link set $eth down 2>&1",$results);
//        writelogs("RESULT FROM DOWN is ".print_r($results,TRUE),__FUNCTION__,__FILE__,__LINE__);
//        exec("$ip addr del {$ipaddr}/{$perfix[1]} dev {$eth} 2>&1",$results);
//        writelogs("RESULT FROM DEL is ".print_r($results,TRUE),__FUNCTION__,__FILE__,__LINE__);

    }

    public function NETWORK_ALL_INTERFACES($OnlyIPS=false):array{
        if(isset($GLOBALS["MEM_NETWORK_ALL_INTERFACES_$OnlyIPS"])){return $GLOBALS["MEM_NETWORK_ALL_INTERFACES_$OnlyIPS"];}
        include_once(dirname(__FILE__)."/class.tcpip-parser.inc");
        $ip=$this->find_program("ip");
        $IPTABLE=array();


        exec("$ip -json addr show 2>&1",$results);


        $array=array();
        $json=json_decode($results[0]);

        foreach ($json as $zdata){
            $Interface=$zdata->ifname;
            if($GLOBALS["VERBOSE"]){echo "Parsing interface: $Interface\n";}
            $operstate=$zdata->operstate;
            $IPADDR=null;$CDIR=0;
            $array[$Interface]["INTERFACE"]=$Interface;
            $array[$Interface]["STATE"]="no";
            $array[$Interface]["PROMISC"]=false;

            foreach ($zdata->flags as $flag){
                if($GLOBALS["VERBOSE"]){echo "\tFlag -> $flag\n";}
                if($flag=="POINTOPOINT"){
                    $array[$Interface]["MAC"]="00:00:00:00:00:00";
                }
                if($flag=="PROMISC"){
                    $array[$Interface]["PROMISC"]=true;
                }
            }
            if($operstate=="UP"){
                $array[$Interface]["STATE"]="yes";
            }
            if(property_exists($zdata,"address")){
                $array[$Interface]["MAC"]=$zdata->address;
            }
            if(!property_exists($zdata,"addr_info")) {
                $array[$Interface]["IPADDR"] = null;
                continue;
            }

            if(isset($zdata->addr_info[0])) {
                if(property_exists($zdata->addr_info[0],"local")) {
                    $IPADDR = $zdata->addr_info[0]->local;
                }
                if(property_exists($zdata->addr_info[0],"prefixlen")) {
                    $CDIR = $zdata->addr_info[0]->prefixlen;
                }
                $array[$Interface]["CDIR"]=$CDIR;
                $array[$Interface]["IPADDR"]=$IPADDR;
                $IPTABLE[$IPADDR]=true;
                if($CDIR>0) {
                    $tcp = new Unixipv4($IPADDR, $CDIR);
                    $array[$Interface]["NETMASK"] = $tcp->netmask();
                }
                if (property_exists($zdata->addr_info[0], "address")) {
                    if($GLOBALS["VERBOSE"]){echo "\tPeer -> {$zdata->addr_info[0]->address}\n";}
                    $array[$Interface]["PEER"] = $zdata->addr_info[0]->address;
                }
            }

            foreach ($zdata->addr_info as $zips){
                if(property_exists($zips,"secondary")){
                    if($zips->secondary<>1){continue;}
                    if(!property_exists($zips,"label")){continue;}
                    if(property_exists($zips,"local")){
                        $IPTABLE[$zips->local]=true;
                        $array[$zips->label]["IPADDR"]=$zips->local;
                        $array[$zips->label]["CDIR"]=$zips->prefixlen;
                        $tcp=new Unixipv4($zips->local, $zips->prefixlen);
                        $array[$zips->label]["NETMASK"]=$tcp->netmask();
                        $array[$zips->label]["STATE"]="yes";
                        $array[$zips->label]["MAC"]="00:00:00:00:00:00";
                    }
                }
            }
        }

        if($OnlyIPS){return $IPTABLE;}
        $results=array();
        exec("$ip route list 2>&1",$results);

        foreach ($results as $ligne){
            if(preg_match("#default via\s+([0-9\.]+)\s+dev\s+([0-9A-Za-z\-\_]+)#", $ligne,$re)){
                $Interface=$re[2];
                if(preg_match("#^(.+?)@#", $Interface,$ri)){$Interface=$ri[1];}
                if($Interface==null){continue;}
                $array[$Interface]["GATEWAY"]=$re[1];

            }
        }

        $keepalived_enable = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE");
        $keepalived_enable_slave = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE");
        if (intval($keepalived_enable) == 1 || intval($keepalived_enable_slave) == 1) {
            $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
            $sql = "SELECT * FROM `keepalived_primary_nodes` WHERE enable='1'";
            $results = $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error_html();
            }
            foreach ($results as $index => $ligne) {
                $sql_vip = "SELECT * FROM keepalived_virtual_interfaces WHERE primary_node_id='{$ligne["ID"]}' AND enable='1' ORDER BY ID";
                $results_vips = $q->QUERY_SQL($sql_vip);
                if (!$q->ok) {
                    echo $q->mysql_error_html();
                }
                foreach ($results_vips as $index2 => $vips) {
                    $array[$vips["label"]]["IPADDR"] = $vips["virtual_ip"];
                    $array[$vips["label"]]["INTERFACE"] = $vips["label"];
                    $array[$vips["label"]]["LABEL"] = "Failover Floating IP {$vips["virtual_ip"]}";
                    $array[$vips["label"]]["STATE"] = "yes";

                }
            }
        }

        $GLOBALS["MEM_NETWORK_ALL_INTERFACES_$OnlyIPS"] = $array;
        return $array;

    }
    public function TCP_LIST_NICS(){
        if(isset($GLOBALS["TCP_LIST_NICS"])){return $GLOBALS["TCP_LIST_NICS"];}
        $datas=explode("\n",@file_get_contents("/proc/net/dev"));
        foreach ( $datas as $line){
            if(!preg_match("#^(.+?):#",$line,$re)){continue;}
            $GLOBALS["TCP_LIST_NICS"][trim(strtolower($re[1]))]=true;

        }
        return $GLOBALS["TCP_LIST_NICS"];
    }

    public function NETWORK_INTERFACE_OK($eth){
        $TCP_LIST_NICS=$this->TCP_LIST_NICS();
        if(!isset($TCP_LIST_NICS[trim(strtolower($eth))])){return false;}
        $ip=$this->find_program("ip");
        exec("$ip link show $eth 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(preg_match("#does not exist#",$ligne)){return false;}
            if(!preg_match("#^[0-9]+.*?state\s+([A-Z]+)#", $ligne,$re)){continue;}
            $state=$re[1];
            if($state=="DOWN"){return false;}
        }
        return true;
    }

    function NMAP_CMDLINE($net,$tmpfile){
        $nmap=$this->find_program("nmap");
        return "$nmap $net -oG $tmpfile -F -R -O -A --system-dns -p1 --min-hostgroup 30 --min-parallelism 30 --osscan-guess --host-timeout 5000";
    }


    function NETWORK_DEBIAN_FILCONF($eth){
        $return=array();
        $f=explode("\n",@file_get_contents('/etc/network/interfaces'));
        $ttr["address"]="IPADDR";
        $ttr["netmask"]="NETMASK";
        $ttr["gateway"]="GATEWAY";
        $ttr["broadcast"]="BROADCAST";
        $ttr["network"]="NETWORK";
        $ttr["metric"]="METRIC";
        $outputz=array();

        foreach ($f as $ligne){
            $interface=null;
            if(preg_match("#iface\s+(.+?)\s+.*?(static|dhcp)#", $ligne,$re)){
                $interface=$re[1];
                $outputz[$interface]["BOOTPROTO"]=$re[2];
                $outputz[$interface]="METHOD=debian";
                $outputz[$interface]="DEVICE=$interface";
                continue;
            }

            if(preg_match("#(.+?)\s+(.+)#", $ligne,$re)){
                $key=strtolower(trim($re[1]));
                if(isset($ttr[$key])){
                    $outputz[$interface][$ttr[$key]]=trim($re[2]);
                    continue;
                }

                $outputz[$interface][$key]=$re[2];

            }

        }
        foreach ($outputz[$eth] as $num=>$ligne){
            $return[]="$num=$ligne";
        }
        return @implode("\n",$return);
    }


    function APACHE_ListenDefaultAddress(){
        if(isset($GLOBALS["APACHE_ListenDefaultAddress"])){return $GLOBALS["APACHE_ListenDefaultAddress"];}
        if($this->isNGnx()){$GLOBALS["APACHE_ListenDefaultAddress"]="127.0.0.1";return "127.0.0.1";}


        $GLOBALS["APACHE_ListenDefaultAddress"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeWebListen"));
        if($GLOBALS["APACHE_ListenDefaultAddress"]==null){$GLOBALS["APACHE_ListenDefaultAddress"]="*";}
        if($GLOBALS["APACHE_ListenDefaultAddress"]<>"*"){
            if(is_numeric($GLOBALS["APACHE_ListenDefaultAddress"])){
                if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Apache WARNING `{$GLOBALS["APACHE_ListenDefaultAddress"]}` is not a valid network address...\n";}
                $GLOBALS["APACHE_ListenDefaultAddress"]="*";
            }
        }

        if($GLOBALS["APACHE_ListenDefaultAddress"]=="0.0.0.0"){return $GLOBALS["APACHE_ListenDefaultAddress"];}
        if($GLOBALS["APACHE_ListenDefaultAddress"]=="*"){return $GLOBALS["APACHE_ListenDefaultAddress"];}
        $GetAvailableListenIP=$this->GetAvailableListenIP();
        if(!isset($GetAvailableListenIP[$GLOBALS["APACHE_ListenDefaultAddress"]])){
            if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Apache WARNING `{$GLOBALS["APACHE_ListenDefaultAddress"]}` does not exists...\n";}
            $GLOBALS["APACHE_ListenDefaultAddress"]="*";
            return $GLOBALS["APACHE_ListenDefaultAddress"];
        }
        return $GLOBALS["APACHE_ListenDefaultAddress"];
    }





    function GET_PERFS($value){
        if(!class_exists("Bs_IniHandler")){
            $ini=new iniFrameWork("/etc/artica-postfix/performances.conf");
        }else{
            $ini=new Bs_IniHandler("/etc/artica-postfix/performances.conf");
        }

        if(isset($ini->_params["PERFORMANCES"][$value])){
            return $ini->_params["PERFORMANCES"][$value];
        }

    }

    public function EXEC_NICE(){
        include_once(dirname(__FILE__)."/class.nice.inc");
        $nice=new exec_nice();
        return $nice->nice;
    }

    public function MYSQL_VERSION():string{
        if(isset($GLOBALS["MYSQL_VERSION"])){return $GLOBALS["MYSQL_VERSION"];}
        $mysqld=$this->find_program("mysqld");

        exec("$mysqld --version 2>&1",$results);
        foreach ($results as $line){
            if(preg_match("#Ver\s+([0-9\.]+)#", $line,$re)){
                $GLOBALS["SQUID_DB_VERSION"]=$re[1];
                return $re[1];
            }
        }

        return "0.0.0";
    }


    public function LIGHTTPD_PORT($conf=null):int{
        if($conf==null){$conf=$this->LIGHTTPD_CONF_PATH();}
        $tbl=explode("\n",@file_get_contents($conf));
        foreach ($tbl as $line){
            if(preg_match("#^server\.port.*?=.*?([0-9]+)#",$line,$re)){
                return intval($re[1]);
            }
        }
        return 0;
    }

    public function LIGHTTPD_USER($conf=null):string{
        if($conf==null){$conf=$this->LIGHTTPD_CONF_PATH();}
        $tbl=explode("\n",@file_get_contents($conf));
        foreach ($tbl as $line){
            if(preg_match("#^server\.username.*?=(.+?)$#",$line,$re)){
                $re[1]=str_replace("\"","",$re[1]);
                return trim($re[1]);
            }
        }
        return "";
    }

    private function LIGHTTPD_CONF_PATH():string{
        if(is_file('/etc/lighttpd/lighttpd.conf')){return '/etc/lighttpd/lighttpd.conf';}
        if(is_file('/usr/local/etc/lighttpd/lighttpd.conf')){return '/usr/local/etc/lighttpd/lighttpd.conf';}
        return "";
    }

    public function MEMORY_OF($pid):int{
        $datas=explode("\n",@file_get_contents("/proc/$pid/status"));
        foreach ($datas as $line){
            if(preg_match("#^VmSize.+?([0-9]+)#",$line,$re)){
                return intval($re[1]);
            }
        }
        return 0;
    }

    public function PPID_OF($pid):int{
        if(!is_numeric($pid)){return 0;}
        $datas=explode("\n",@file_get_contents("/proc/$pid/status"));
        foreach ($datas as $line){
            if(preg_match("#^PPid.+?([0-9]+)#i",$line,$re)){
                if($re[1]<3){return $pid;}
                return intval($re[1]);
            }
        }

        return intval($pid);

    }

    public function GET_WIRELESS_CARD(){

        $t=explode("\n",@file_get_contents("/proc/net/wireless"));

        foreach ($t as $index=>$line){
            if(preg_match("#(.+?):\s+([0-9]+)#",$line,$re)){return $re[1];}
        }

        $iwconfig=$this->find_program("iwconfig");
        if($iwconfig==null){
            if($GLOBALS["VERBOSE"]){echo "Unable to find iwconfig...\n";}
            return;
        }

        exec("$iwconfig 2>&1",$results);

        foreach ($results as $index=>$line){
            if(preg_match("#^(.+?)\s+.+?ESSID#",$line,$re)){
                return $re[1];
            }else{
                if($GLOBALS["VERBOSE"]){echo "$line NO MATCH...\n";}
            }
        }

    }


    private function GET_INFO($key){
        $value=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("$key"));
        return $value;

    }

    public function EnableClamavDaemon(){

        $bin_path=$this->find_program("clamd");
        if(!is_file((string) $bin_path)){return 0;}
        $EnableClamavDaemon=intval($this->GET_INFO("EnableClamavDaemon"));
        $EnableClamavDaemonForced=intval($this->GET_INFO("EnableClamavDaemonForced"));
        $EnableSecurityAppliance=intval($this->GET_INFO("EnableSecurityAppliance"));
        $CicapEnabled=intval($this->GET_INFO("CicapEnabled"));
        if($EnableSecurityAppliance==1){$CicapEnabled=0;}
        if($EnableClamavDaemonForced==1){$EnableClamavDaemon=1;}

        if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return 1;}

        $squidbin=$this->LOCATE_SQUID_BIN();

        if(is_file($squidbin)){
            $SQUIDEnable=$this->GET_INFO("SQUIDEnable");
            if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
            if($SQUIDEnable==1){
                if($CicapEnabled==1){
                    $EnableClamavInCiCap=intval($this->GET_INFO("EnableClamavInCiCap"));
                    if($EnableClamavInCiCap==1){$EnableClamavDaemon=1;}
                }
            }
        }

        $mimedefang=$this->find_program("mimedefang");
        if(is_file($mimedefang)){
            $MimeDefangEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangEnabled"));
            if($MimeDefangEnabled==1){
                $MimeDefangClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangClamav"));
                if($MimeDefangClamav==1){return 1;}
            }

        }



        return $EnableClamavDaemon;
    }



    public function LOCATE_NET_BIN_PATH(){
        $net=$this->find_program("net");
        if(is_file($net)){return $net;}
        $net=$this->find_program("net.samba3");
        if(is_file($net)){return $net;}
    }

    public function FULL_HOSTNAME(){
        if(isset($GLOBALS["FULL_HOSTNAME"])){return $GLOBALS["FULL_HOSTNAME"];}
        if(is_file("/etc/artica-postfix/FULL_HOSTNAME")){
            if($this->file_time_min("/etc/artica-postfix/FULL_HOSTNAME")<10){
                $GLOBALS["FULL_HOSTNAME"]=@file_get_contents("/etc/artica-postfix/FULL_HOSTNAME");
                return $GLOBALS["FULL_HOSTNAME"];
            }
        }

        if(is_file("/etc/hostname")){
            $GLOBALS["FULL_HOSTNAME"]=trim(@file_get_contents("/etc/hostname"));
            if($GLOBALS["FULL_HOSTNAME"]<>null){return $GLOBALS["FULL_HOSTNAME"];}
        }

        $ypdomainname=$this->find_program("ypdomainname");
        $hostname=$this->find_program("hostname");
        $sysctl=$this->find_program("sysctl");
        if($ypdomainname<>null){
            exec("$ypdomainname 2>&1",$results);
            $domain=trim(@implode(" ",$results));

        }else{
            exec("$sysctl -n kernel.domainname 2>&1",$results);
            $domain=trim(@implode(" ",$results));

        }
        unset($results);
        exec("$hostname -s 2>&1",$results);
        $host=trim(@implode(" ",$results));
        writelogs_framework("$hostname -s -> $host",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        unset($results);

        if(preg_match("#not set#",$domain)){$domain=null;}
        if(preg_match("#\(none#",$domain)){$domain=null;}
        if($domain==null){

            exec("$hostname -d 2>&1",$results);
            $domain=trim(@implode(" ",$results));
            if(strpos($domain, "not known")){$domain=null;}

            writelogs_framework("$hostname -d -> $domain",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            if($domain<>null){
                if($ypdomainname<>null){shell_exec("$ypdomainname $domain");}
            }
        }

        if(strlen($domain)>0){$host="$host.$domain";}
        writelogs_framework("Final = $host ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        $host=str_replace('.(none)',"",$host);
        if(preg_match("#(.+?)\.hostname:#", $host,$re)){$host=$re[1];}
        $GLOBALS["FULL_HOSTNAME"]=$host;
        @unlink("/etc/artica-postfix/FULL_HOSTNAME");
        @file_put_contents("/etc/artica-postfix/FULL_HOSTNAME", $GLOBALS["FULL_HOSTNAME"]);
        return $GLOBALS["FULL_HOSTNAME"];
    }


    public FUNCTION GET_LOCAL_SID(){

        $DISABLED=true;
        if(is_file("/etc/init.d/winbind")){$DISABLED=false;}
        if(is_file("/etc/init.d/samba")){$DISABLED=false;}
        if($DISABLED){return;}

        if(!isset($GLOBALS[__FUNCTION__])){$GLOBALS[__FUNCTION__]=null;}
        if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}
        if(!isset($_SESSION[__CLASS__])){$_SESSION[__CLASS__][__FUNCTION__]=null;}

        if($_SESSION[__CLASS__][__FUNCTION__]<>null){return $_SESSION[__CLASS__][__FUNCTION__];}
        if(is_file("/etc/artica-postfix/LOCAL_SID")){
            $filtime=$this->file_time_min("/etc/artica-postfix/LOCAL_SID");
            if($filtime<520){return @file_get_contents("/etc/artica-postfix/LOCAL_SID");}
        }
        $net=$this->LOCATE_NET_BIN_PATH();
        if(strlen($net)<3){return null;}

        exec("$net getlocalsid 2>&1",$results);
        foreach ($results as $index=>$line){
            if(preg_match("#is:\s+(.+)#",$line,$re)){
                $GLOBALS[__FUNCTION__]=trim($re[1]);
                $_SESSION[__CLASS__][__FUNCTION__]=trim($re[1]);
                @file_put_contents("/etc/artica-postfix/LOCAL_SID",$re[1]);
                writelogs_framework("SID:{$re[1]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                return trim($re[1]);
            }

            if(preg_match("#Can.+?t fetch domain SID for#",$line,$re)){
                $date=time();
                $newsid='S-1-5-21-2596694876-1976058220-'.$date;
                exec("$net setlocalsid $newsid",$rr);
                foreach ($rr as $b){writelogs_framework($b,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
                $GLOBALS[__FUNCTION__]=$newsid;
                $_SESSION[__CLASS__][__FUNCTION__]=trim($newsid);
                writelogs_framework("SID:$newsid",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                @file_put_contents("/etc/artica-postfix/LOCAL_SID",$newsid);
                return trim($newsid);
            }
        }
    }


    public function COUNT_LINES_OF_FILE($path):int{
        $wc=$this->find_program("wc");
        if(strlen($wc)<3){
            $array=explode("\n",@file_get_contents("$path"));
            return count($array);

        }
        $path=$this->shellEscapeChars($path);
        exec("$wc -l $path 2>&1",$results);
        $line=@implode(" ",$results);
        if(preg_match("#^([0-9]+)\s+#",$line,$re)){
            return intval($re[1]);
        }else{
            echo "$line\n";
        }
        return 0;
    }

    public function KILL_PROCESS($pid,$signal=0){
        $pid=intval($pid);
        if($GLOBALS["VERBOSE"]){echo "KILL_PROCESS:: $pid signal:$signal\n";}
        if($pid<5){
            if($GLOBALS["VERBOSE"]){echo "KILL_PROCESS:: ABORTING\n";}
            return;}
        if($signal=="HUP"){
            $kill=$this->find_program("kill");
            shell_exec("$kill -HUP $pid >/dev/null 2>&1");
            return;
        }
        if($signal==null){$signal=15;}
        if($signal==0){$signal=15;}
        $cmdline=null;

        $arg["HUP"]=1;
        $arg["USR1"]=10;
        $arg["USR2"]=12;
        $arg["TERM"]=15;
        $arg["WINCH"]=28;
        $arg["KILL"]=9;

        $argnum[1]="HUP";
        $argnum[10]="USR1";
        $argnum[12]="USR2";
        $argnum[15]="TERM";
        $argnum[28]="WINCH";
        $argnum[9]="KILL";

        if(is_numeric($signal)){
            if(isset($argnum[$signal])){
                if(is_file("/proc/$pid/cmdline")){$cmdline=@file_get_contents("/proc/$pid/cmdline");}
                $this->Tologs("/var/log/processes.killed.logs", "Send signal {$argnum[$signal]} process id: $pid `$cmdline`");
                if(function_exists("posix_kill")) {
                    posix_kill($pid, $signal);
                }else{
                    $kill=$this->find_program("kill");
                    shell_exec("$kill -{$signal} $pid");
                }
            }else{
                if($GLOBALS["VERBOSE"]){echo "KILL_PROCESS:: unkbown signal: $signal ABORTING\n";}
            }
            return;

        }



        if(isset($arg[$signal])){
            if(is_file("/proc/$pid/cmdline")){$cmdline=@file_get_contents("/proc/$pid/cmdline");}
            $this->Tologs("/var/log/processes.killed.logs", "Send signal $signal process id: $pid `$cmdline`");
            posix_kill($pid,$arg[$signal]);
        }

    }
    public function LOCATE_SQUID_CONF(){
        if(is_file('/etc/squid3/squid.conf')){ return '/etc/squid3/squid.conf';}
        if(is_file('/opt/artica/etc/squid.conf')){ return '/opt/artica/etc/squid.conf';}
        if(is_file('/etc/squid/squid.conf')){ return '/etc/squid/squid.conf';}
    }

    public function LOCATE_DHCPD_PID_PATH(){

        if(is_file('/var/run/dhcpd.pid')){ return '/var/run/dhcpd.pid';}
        if(is_file('/var/run/dhcpd/dhcpd.pid')){ return '/var/run/dhcpd/dhcpd.pid';}
        if(is_file('/var/run/dhcp3-server/dhcpd.pid')){ return '/var/run/dhcp3-server/dhcpd.pid';}
    }

    public function LOCATE_PURE_FTPD_PID_PATH():string{
        if(is_file('/var/run/pure-ftpd/pure-ftpd.pid')){return "/var/run/pure-ftpd/pure-ftpd.pid";}
        if(is_file('/var/run/pure-ftpd.pid')){return "/var/run/pure-ftpd.pid";}
        return "";
    }


    public function SQUID_CONFIG_PATH(){
        if(is_file("/etc/squid3/squid.conf")){return '/etc/squid3/squid.conf';}
        if(is_file('/opt/artica/etc/squid.conf')){return '/opt/artica/etc/squid.conf';}
        if(is_file('/etc/squid/squid.conf')){return '/etc/squid/squid.conf';}
        return "/etc/squid3/squid.conf";
    }

    public function SQUID_ALL_PORTS(){
        $array=array();
        $f=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
        foreach ($f as $line){
            $line=trim($line);
            if($line==null){continue;}
            if(preg_match("#^http.*?_port\s+(.+)#",$line,$re)){
                $port=trim($re[1]);
                if($GLOBALS["VERBOSE"]){echo " *** PORT: `$port` -> $line\n";}
                if(preg_match("#^(.+?)\s+#", $port,$re)){$port=$re[1];}

                if(preg_match("#^(.+?):([0-9]+)#", $port,$re)){
                    if($GLOBALS["VERBOSE"]){echo " *** PORT: `{$re[2]}` -> $port\n";}
                    $port=$re[2];}
                $array[$port]=true;
            }

        }

        return $array;

    }
    public function BetterFS(){
        if(!$this->IsExt4()){return "ext3";}
        if(is_file($this->find_program("mkfs.ext4"))){return "ext4";}
        if(is_file($this->find_program("mkfs.ext3"))){return "ext3";}
        return "ext3";
    }

    public function IsExt4(){
        $f=explode("\n",@file_get_contents("/proc/filesystems"));
        foreach ($f as $num=>$ligne){
            if(preg_match("#.*?\s+ext4#",$ligne)){return true;}
        }
        return false;
    }

    public function hostname_g(){

        if(is_file("/etc/artica-postfix/settings/Daemons/myhostname")){
            $results=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
            if(strlen($results)>2){return $results;}
        }


        $uname=posix_uname();
        $domainname=trim($uname["domainname"]);
        if($domainname=="(none)"){$domainname="localhost.localdomain";}

        $hostname=trim($uname["nodename"]);
        if(strpos($hostname, ".")==0) {
            if($domainname<>null){$hostname=trim("$hostname.$domainname");}
        }
        $hostname=trim($hostname);
        $hostname=str_replace(" ", "", $hostname);
        $hostname=str_replace(" ", "", $hostname);
        $hostname=str_replace(";", "", $hostname);
        $hostname=str_replace(",", "", $hostname);
        $hostname=str_replace(":", "", $hostname);
        $hostname=str_replace("\\", "", $hostname);
        $hostname=str_replace("/", "", $hostname);
        $hostname=str_replace("!", "", $hostname);
        $hostname=str_replace("?", "", $hostname);
        $hostname=str_replace("%", "", $hostname);
        $hostname=str_replace(")", "", $hostname);
        $hostname=str_replace("(", "", $hostname);
        $hostname=str_replace("]", "", $hostname);
        $hostname=str_replace("[", "", $hostname);
        $hostname=str_replace("@", "", $hostname);
        $hostname=str_replace("}", "", $hostname);
        $hostname=str_replace("{", "", $hostname);
        $hostname=str_replace("&", "", $hostname);
        $hostname=str_replace("..", ".", $hostname);
        $hostname=str_replace("..", ".", $hostname);
        $hostname=str_replace("=", "-", $hostname);
        $hostname=str_replace("|", "-", $hostname);
        $hostname=str_replace("#", "-", $hostname);
        if(strlen($hostname)<3){$hostname="localhost.localdomain";}
        if($hostname==null){$hostname="localhost.localdomain";}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("myhostname", $hostname);
        return trim($hostname);



    }
    public function hostname_simple(){
        $host=$this->hostname_g();
        if(strpos($host, ".")>0){$tt=explode(".",$host);$host=$tt[0];}
        return $host;
    }

    function BACKUPPC_locate_config_path(){
        if(is_file("/etc/backuppc/config.pl")){return "/etc/backuppc";}
        if(is_file("/etc/BackupPC/config.pl")){return "/etc/BackupPC";}

    }




    function chmod_alldirs($bit,$dir){
        $dirz=explode("/",$dir);
        $dirStart="/{$dirz[0]}";
        if($dirStart=="/"){$dirStart=null;}
        for($i=1;$i<count($dirz);$i++){
            $dirStart=$dirStart."/".$dirz[$i];
            if(!is_dir($dirStart)){@mkdir($dirStart,$bit,true);continue;}
            @chmod($dirStart, $bit);

        }


    }

    public function DiskLabel($sda=null){
        if($sda==null){return "";}
        if(!is_dir("/dev/disk/by-label")){return "";}
        $handle = opendir("/dev/disk/by-label");
        if(!$handle){return "";}

        if($handle){
            while (false !== ($filename = readdir($handle))) {
                if($filename=="."){continue;}
                if($filename==".."){continue;}
                $targetFile="/dev/disk/by-label/$filename";
                if(is_dir($targetFile)){continue;}
                if(!is_link($targetFile)){continue;}
                $spath=readlink($targetFile);
                if(preg_match("#\/$sda#i",$spath)){return $filename;}
            }
        }
        return "";
    }

    public function ToSyslog($text,$error=false,$forcefile=null){

        if(!is_bool($error)){
            $forcefile=$error;
        }

        $file="framework";
        $function=null;
        $line=0;
        if(function_exists("debug_backtrace")){
            $trace=@debug_backtrace();
            $debug_backtrace=$this->debug_backtrace_array($trace);
            $file=basename($debug_backtrace["file"]);
            if($debug_backtrace["function"]<>__FUNCTION__) {
                $function = $debug_backtrace["function"]."()";
            }
            $line=intval($debug_backtrace["line"]);


        }
        if($forcefile<>null){$file=$forcefile;}
        $text="$function{".$line."} $text";
        if($GLOBALS["VERBOSE"]){echo "$text\n";}
        if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
        if(!function_exists("syslog")){return false;}
        openlog($file, LOG_PID , LOG_SYSLOG);
        syslog($LOG_SEV, $text);
        closelog();
        return true;
    }

    private function Tologs($file,$text){
        if($GLOBALS["VERBOSE"]){echo "[$file]: $text\n";}
        if(!is_dir(dirname($file))){@mkdir(dirname($file));}
        $logFile=$file;

        if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
        if (is_file($logFile)) {
            $size=filesize($logFile);
            if($size>100000){unlink($logFile);}
        }
        $logFile=str_replace("//","/",$logFile);
        $f = @fopen($logFile, 'a');

        $time=date("H:i:s");
        @fwrite($f, "$time: $text\n");
        @fclose($f);
    }

    private function SystemUserGroups($username){
        $username=strtolower(trim($username));
        $groupsLine=null;
        $ARRAY=array();
        $groupsbin=$this->find_program("groups");
        exec("$groupsbin $username 2>&1",$results);
        foreach ($results as $index=>$ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(preg_match("$username.*?:(.+)#", $ligne,$re)){
                $groupsLine=trim($re[1]);
                break;
            }
        }

        if($groupsLine==null){return array();}
        $xgroupsLine=explode(" ",$groupsLine);
        foreach ($xgroupsLine as $ligne){
            $ligne=trim(strtolower($ligne));
            if($ligne==null){continue;}
            $ARRAY[$ligne]=$ligne;
        }

    }

    public function SystemIsUserInGroup_cmdline($username,$groupName){
        $groupName=trim(strtolower($groupName));
        if($groupName==null){return true;}
        $ARRAY=$this->SystemUserGroups($username);
        if(!isset($ARRAY[$groupName])){return false;}
        return true;


    }

    public function SystemSecurityLimitsConf():bool{
        $sysctl=$this->find_program("sysctl");
        shell_exec("$sysctl -w fs.nr_open=2147483584");
        $T[]="*       soft    nofile   2147483584";
        $T[]="*       hard    nofile   2147483584";
        $T[]="cyrus       soft    nofile   2147483584";
        $T[]="cyrus       hard    nofile   2147483584";
        $T[]="squid       soft    nofile   2147483584";
        $T[]="squid       hard    nofile   2147483584";
        $T[]="www-data    soft    nofile   2147483584";
        $T[]="www-data    hard    nofile   2147483584";
        $T[]="haproxy    hard    nofile   2147483584";
        $T[]="haproxy    soft    nofile   2147483584";
        $T[]="unbound    hard    nofile   2147483584";
        $T[]="unbound    soft    nofile   2147483584";
        $T[]="mysql    hard    nofile   2147483584";
        $T[]="mysql    soft    nofile   2147483584";
        $T[]="nginx    hard    nofile   2147483584";
        $T[]="nginx    soft    nofile   2147483584";
        $T[]="elasticsearch\tsoft\tmemlock\tunlimited";
        $T[]="elasticsearch\thard\tmemlock\tunlimited";
        $T[]="elasticsearch\tsoft\tnofile 65536";
        $T[]="elasticsearch\thard\tnofile 65536";
        $T[]="elasticsearch\thard\tnproc\t2048";
        $T[]="elasticsearch\t-\tmemlock\tunlimited";
        $T[]="root    hard    nofile   2147483584";
        $T[]="root    soft    nofile   2147483584";
        @file_put_contents("/etc/security/limits.conf", @implode("\n", $T)."\n");
        return true;
    }



    public function SystemAddUserToGroup($username,$groupName):bool{
        if($this->SystemIsUserIngroup($username, $groupName)){return true;}
        if(!$this->SystemUserExists($username)){
            $useradd=$this->find_program("useradd");
        }else{
            $useradd=$this->find_program("usermod")." -a ";
        }
        $this->CreateUnixUserRemoveLocks();
        exec("$useradd -G \"$groupName\" \"$username\" 2>&1",$results);
        if($this->SystemIsUserIngroup($username, $groupName)){return true;}
        $GLOBALS["SystemCreateUser"][]="SystemAddUserToGroup $username to $groupName failed";
        foreach ($results as $ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            $GLOBALS["SystemCreateUser"][]=$ligne;
            $this->ToSyslog("Warning, Unable to add user `$username` in `$groupName` $useradd say `$ligne`");
        }


        return false;

    }

    private function debug_backtrace_implode($array){
        if(isset($array[0])){
            return "called by ". basename($array[0]["file"])." {$array[0]["function"]}() line {$array[0]["line"]}";
        }

        if(isset($array[1])){
            return "called by ". basename($array[1]["file"])." {$array[1]["function"]}() line {$array[1]["line"]}";

        }





    }

    function chown_func($user,$group=null,$dir=null){
        $called=null;
        if(function_exists("debug_backtrace")){
            $trace=@debug_backtrace();
            $called=$this->debug_backtrace_implode($trace);
        }

        if($user=="squid"){
            if(!isset($GLOBALS["SQUID_BIN"])){ $GLOBALS["SQUID_BIN"]=$this->LOCATE_SQUID_BIN(); }
            if(!is_file($GLOBALS["SQUID_BIN"])){
                $this->ToSyslog("Warning, want to chown $user:$group with no squid installed $called");
                return;
            }
        }

        if($user=="postfix"){
            if(!isset($GLOBALS["POSTCONF_BIN"])){$GLOBALS["POSTCONF_BIN"]=$this->find_program("postconf");}
            if(!is_file($GLOBALS["POSTCONF_BIN"])){
                $this->ToSyslog("Warning, want to chown $user:$group with no Postfix installed $called");
                return;
            }

        }

        if($user==null){return;}
        $chown=$this->find_program("chown");


        if(!isset($GLOBALS["DEBUG_CHOWN"])){
            $GLOBALS["DEBUG_CHOWN"]=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugChownFunc")));
        }



        if($GLOBALS["DEBUG_CHOWN"]){
            $this->Tologs("/var/log/artica.chown.debug","$user:$group \"$dir\" $called");
        }



        if(trim($dir)==null){
            $this->ToSyslog("Warning, want to chown $user:$group with no directory $called");
            return;
        }
        if($group=='${APACHE_RUN_GROUP}'){$group="www-data";}

        if(trim($group)<>null){
            if(!$this->SystemIsUserIngroup($user,$group) ){
                $this->ToSyslog("Warning, want to chown user: `$user` group:`$group` invalid $called");
                if(!$this->SystemGroupExists($group) ){
                    $this->ToSyslog("Warning, want to chown non existent `$group` -> create it $called");
                    $this->SystemCreateGroup($group);
                }
                if(!$this->SystemGroupExists($group) ){
                    $this->ToSyslog("Warning, want to chown non existent $group failed, aborting");
                    return ;
                }
                $this->ToSyslog("Adding user `$user` to group `$group`");
                if(!$this->SystemAddUserToGroup($user,$group)){
                    $this->ToSyslog("Warning, Unable to add user `$user` in `$group` failed, aborting");
                    return;
                }

            }
        }



        if(isset($GLOBALS["chown_func"][md5("$user$group$dir")])){return;}
        $dir=trim($dir);
        $recursive=false;



        if(substr($dir, strlen($dir)-1,1)=="*"){
            $dir=substr($dir, 0,strlen($dir)-1);
            $recursive=true;
        }

        if(!is_dir($dir)){
            if(!is_file($dir)){
                return;
            }
        }

        if(strlen($group)>1){
            if(strpos("/", $group)){
                if((is_file($group)) OR is_dir(is_file($group)) ){$dir=$group;$group=$user;}
            }
        }

        if(preg_match("#(.*?):(.+)#", $user,$re)){
            $user=$re[1];
            $group=$re[2];
        }

        if($group<>null){$group=":$group";}

        if($recursive){
            if($GLOBALS["VERBOSE"]){echo "$chown -R $user$group $dir >/dev/null\n";}
            shell_exec("$chown -R $user$group $dir >/dev/null");


        }else{
            if($GLOBALS["VERBOSE"]){echo "$chown $user$group $dir >/dev/null\n";}
            shell_exec("$chown $user$group $dir >/dev/null");
        }

        $GLOBALS["chown_func"][md5("$user$group$dir")]=true;

    }

    function chmod_func($bit,$dir){
        if(@is_link($dir)){$dir=@readlink($dir);}
        if(!is_dir($dir)){if(!is_file($dir)){return;}}
        $findbit=false;
        if($bit=="493"){$bit="0755";$findbit=true;}
        if($bit=="511"){$bit="0777";$findbit=true;}
        if($bit=="416"){$bit="0640";$findbit=true;}
        if($bit=="666"){$bit="0666";$findbit=true;}

        if($bit=="0755"){$bit="0755";$findbit=true;}
        if($bit=="0777"){$bit="0777";$findbit=true;}
        if($bit=="0640"){$bit="0640";$findbit=true;}
        if($bit=="0666"){$bit="0666";$findbit=true;}

        if($bit=="755"){$bit="0755";$findbit=true;}
        if($bit=="777"){$bit="0777";$findbit=true;}
        if($bit=="640"){$bit="0640";$findbit=true;}

        if(!$findbit){return;}

        if(isset($GLOBALS["chmod_func"][md5("$bit$dir")])){return;}
        $dir=trim($dir);
        $recursive=false;
        $chmod=$this->find_program("chmod");

        if(!isset($GLOBALS["DEBUG_CHOWN"])){
            $GLOBALS["DEBUG_CHOWN"]=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugChownFunc")));
        }

        if(function_exists("debug_backtrace")){
            $trace=@debug_backtrace();
            if(isset($trace[1])){
                $called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
            }
        }

        if($GLOBALS["DEBUG_CHOWN"]){
            $this->Tologs("/var/log/artica.chown.debug","bit $bit \"$dir\" $called");
        }

        if(substr($dir, strlen($dir)-1,1)=="*"){
            $dir=substr($dir, 0,strlen($dir)-1);
            $recursive=true;
        }



        if($recursive){
            shell_exec("$chmod -R $bit $dir >/dev/null 2>&1");
        }else{
            shell_exec("$chmod $bit $dir >/dev/null 2>&1");
        }

        $GLOBALS["chmod_func"][md5("$bit$dir")]=true;

    }

    function PEAR_INSTALL_CHECK($packagename){
        $pear=$this->find_program("pear");
        if(strlen($pear)<5){return false;}
        exec("$pear list 2>&1",$array);
        foreach ($array as $num=>$ligne){
            if(preg_match("#^$packagename\s+#",$ligne,$re)){
                return true;
            }
        }
        return false;
    }


    function BACKUPPC_GET_CONFIG_INFOS($key){
        $f=explode("\n",@file_get_contents($this->BACKUPPC_locate_config_path()."/config.pl"));
        foreach ($f as $num=>$ligne){
            if(preg_match("#Conf\{$key\}.*?'(.+?)';#i",$ligne,$re)){
                $re[1]=trim($re[1]);
                $re[2]=str_replace("'","",$re[1]);
                return $re[1];
            }
        }

    }

    function BACKUPPC_GET_CGIBIN_PATH(){
        if(is_file("/usr/share/backuppc/cgi-bin/index.cgi")){return "/usr/share/backuppc/cgi-bin/index.cgi";}
        if(is_file("/usr/share/backuppc/cgi-bin/BackupPC_Admin")){return "/usr/share/backuppc/cgi-bin/BackupPC_Admin";}
        if(is_file("/usr/share/BackupPC/sbin/BackupPC_Admin")){return "/usr/share/BackupPC/sbin/BackupPC_Admin";}
    }

    function BACKUPPC_GET_IMG_DIR():string{
        if(is_dir("/usr/share/backuppc/image")){return "/usr/share/backuppc/image";}
        if(is_dir("/usr/share/BackupPC/html")){return "/usr/share/BackupPC/html";}
        return "";
    }





    public function remove_monit($APP):bool{
        $path="/etc/monit/conf.d/$APP.monitrc";
        if(!is_file($path)) {return true;}
        @unlink($path);
        $monit=$this->find_program("monit");
        system("$monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state reload");
        return true;
    }

    public function isFileReplicated($src,$dst):bool{
        $md51=md5_file($src);
        $md52=md5_file($dst);

        if($md51 == $md52 ){@unlink($src); return false;}

        $tt=time();
        $bkfile="$dst.$tt";

        if(is_file($bkfile)){@unlink($bkfile);}
        @copy($dst,$bkfile);

        @unlink($dst);
        if(!@copy($src,$dst)){
            @copy($bkfile,$dst);
            @unlink($src);
            @unlink($bkfile);
            return false;
        }
        @unlink($src);
        @unlink($bkfile);
        return true;


    }
    public function remove_syslog($programname):bool{
        $tpath  = "/etc/rsyslog.d/$programname.conf";

        if(!is_file($tpath)){return true;}
        @unlink($tpath);
        shell_exec("/etc/init.d/rsyslog reload");
        if(is_file("/var/log/$programname.log")){@unlink("/var/log/$programname.log");}
        return true;
    }

    public function create_syslog_pname($programname,$logfile):bool{
        $tpath  = "/etc/rsyslog.d/$programname.conf";
        $tmp    = "/tmp/syslog.temp.$programname.conf";
        $f[]="if  (\$programname =='$programname') then {";
        $f[]="\t-$logfile";
        $f[]="\t& stop";
        $f[]="}\n";
        if(!is_file($tpath)){
            @file_put_contents($tpath, @implode("\n",$f) );
            $unix=new unix();$unix->RESTART_SYSLOG(true);
            return true;
        }
        @file_put_contents($tmp, @implode("\n",$f) );
        if($this->isFileReplicated($tmp,$tpath)){shell_exec("/etc/init.d/rsyslog reload");}
        return true;

    }

    public function create_monit($APP,$pidfile,$init):bool{
        $path="/etc/monit/conf.d/$APP.monitrc";
        if(is_file($path)) {
            @unlink($path);
        }
        $f=array();

        $definition="check process $APP with pidfile $pidfile";

        if(preg_match("#dir:(.+)#",$pidfile,$re)){
            $definition="check DIRECTORY $APP PATH $re[1]";
        }

        $f[]=$definition;
        $f[]="\tstart program = \"/etc/init.d/$init start --monit\"";
        $f[]="\tstop program = \"/etc/init.d/$init stop --monit\"";
        $f[]="";
        @file_put_contents($path, @implode("\n", $f));
        $monit=$this->find_program("monit");
        system("$monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state reload");
        return true;
    }

    function create_service_php($phpname,$initname):bool{
        $php=$this->LOCATE_PHP5_BIN();
        $f[]="#!/bin/sh";
        $f[]="### BEGIN INIT INFO";
        $f[]="# Provides:          $initname";
        $f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$time";
        $f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog ";
        $f[]="# Should-Start:";
        $f[]="# Should-Stop:";
        $f[]="# Default-Start:     3 4 5";
        $f[]="# Default-Stop:      0 1 6";
        $f[]="# Short-Description: $initname daemon";
        $f[]="# chkconfig: 2345 11 89";
        $f[]="# description: $initname";
        $f[]="### END INIT INFO";
        $f[]="case \"\$1\" in";
        $f[]=" start)";
        $f[]="   $php /usr/share/artica-postfix/$phpname --start \$2 \$3";
        $f[]="	 exit 0";
        $f[]="    ;;";
        $f[]="";
        $f[]="  stop)";
        $f[]="   $php /usr/share/artica-postfix/$phpname --stop \$2 \$3";
        $f[]="    ;;";
        $f[]="";
        $f[]=" restart)";
        $f[]="   $php /usr/share/artica-postfix/$phpname --restart \$2 \$3";
        $f[]="	 exit 0";
        $f[]="    ;;";
        $f[]="";
        $f[]=" reload)";
        $f[]="   $php /usr/share/artica-postfix/$phpname --restart \$2 \$3";
        $f[]="	 exit 0";
        $f[]="    ;;";
        $f[]="";
        $f[]="  *)";
        $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
        $f[]="    exit 1";
        $f[]="    ;;";
        $f[]="esac";
        $f[]="exit 0\n";

        $INITD_PATH="/etc/init.d/$initname";
        echo "$initname: [INFO] Writing $INITD_PATH with new config\n";
        @unlink($INITD_PATH);
        @file_put_contents($INITD_PATH, @implode("\n", $f));
        @chmod($INITD_PATH,0755);

        if(is_file('/usr/sbin/update-rc.d')){
            shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
        }

        if(is_file('/sbin/chkconfig')){
            shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
            shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
        }
        return true;
    }

    public function create_service_compliance($Config=array()):bool{
        $DAEMON_ARGS=null;
        $php5script=null;
        $php5Killer=null;
            if(!isset($Config["PHP_BUILDER"])){$Config["PHP_BUILDER"]=null;}
            if(!isset($Config["BINARY_OPTS"])){$Config["BINARY_OPTS"]=null;}
            if(!isset($Config["COMPLIANCE"])){$Config["COMPLIANCE"]=false;}
            if(!isset($Config["PHP_KILLER"])){$Config["PHP_KILLER"]=null;}
            $php=$this->LOCATE_PHP5_BIN();
            $COMPLIANCE=$Config["COMPLIANCE"];
            $INITD_PATH=$Config["INITD_PATH"];
            if($Config["PHP_BUILDER"]<>null){
                $php5script="$php /usr/share/artica-postfix/".$Config["PHP_BUILDER"]." >/dev/null 2>&1 || true";
            }
            if($Config["PHP_KILLER"]<>null){
                $php5Killer="$php /usr/share/artica-postfix/".$Config["PHP_KILLER"]." >/dev/null 2>&1 || true";
            }

            $daemonbinLog=basename($INITD_PATH);
            $binary=$Config["BINARY"];
            $cmdlines=trim($Config["BINARY_OPTS"]);
            $PIDFILE=$Config["PIDFILE"];
            if($cmdlines<>null){$cmdlines=" $cmdlines";}
            if($cmdlines<>null){
                $DAEMON_ARGS=" -- $cmdlines";
            }

            $f[]="#!/bin/sh";
            $f[]="### BEGIN INIT INFO";
            $f[]="# Provides:          $daemonbinLog";
            $f[]="# Required-Start:    \$local_fs \$syslog";
            $f[]="# Required-Stop:     \$local_fs \$syslog";
            $f[]="# Should-Start:";
            $f[]="# Should-Stop:";
            $f[]="# Default-Start:     2 3 4 5";
            $f[]="# Default-Stop:      0 1 6";
            $f[]="# Short-Description: $daemonbinLog";
            $f[]="# chkconfig: - 80 75";
            $f[]="### END INIT INFO";
            $f[]="";
            $f[]=". /lib/lsb/init-functions";
            $f[]="SRVNAME=\"$daemonbinLog Service\"";
            $f[]="BINARY=\"$binary\"";
            $f[]="PIDFILE=\"$PIDFILE\"";
            $f[]="";
            $f[]="";
            $f[]="getpid(){";
            $f[]="	if [  -f \$PIDFILE ]; then";
            $f[]="		PID=\$(cat \$PIDFILE)";
            $f[]="		STRLEN=\${#PID}";
            $f[]="		";
            $f[]="		if [ \"\$STRLEN\" -gt 0 ]; then ";
            $f[]="			if [ -f \"/proc/\$PID/cmdline\" ]; then";
            $f[]="				echo \$PID";
            $f[]="				return \$PID";
            $f[]="			fi";
            $f[]="		fi";
            $f[]="	fi";
            $f[]="	";
            $f[]="	PID=\"\$(pidof -s \$BINARY)\"";
            $f[]="	STRLEN=\${#PID}";
            $f[]="	if [ \"\$STRLEN\" -gt 0 ]; then ";
            $f[]="		if [ -f \"/proc/\$PID/cmdline\" ]; then";
            $f[]="			echo \$PID";
            $f[]="			return \$PID";
            $f[]="		fi";
            $f[]="	fi";
            $f[]="	";
            $f[]="	echo \"0\"";
            $f[]="	return \"0\"";
            $f[]="";
            $f[]="}";
            $f[]="";
            $f[]="";
            $f[]="do_start() {";
            $f[]="	pidnum=\$(getpid)";
            $f[]="	    if [ \"\$pidnum\" -gt \"0\" ]; then ";
            $f[]="	    	log_action_msg \"Daemon is currently running PID \$pidnum\"";
            $f[]="	    	exit 0";
            $f[]="	   fi";
            $f[]="";
            $f[]="	log_action_begin_msg \"Starting \$SRVNAME (\$BINARY)\"";
            if($php5script<>null) {
                $f[] = "  $php5script";
            }
            if(!$COMPLIANCE) {
                $f[] = "	\$BINARY$cmdlines >/dev/null 2>&1";
                $f[] = "	";
                $f[] = "	for i in 1 2 3 4 5";
                $f[] = "	do";
                $f[] = "		sleep 1";
                $f[] = "		pidnum=\$(getpid)";
                $f[] = "		if [ \"\$pidnum\" -gt \"0\" ]; then ";
                $f[] = "		    	log_action_end_msg 0";
                $f[] = "	    		exit 0";
                $f[] = "	   	fi";
                $f[] = "		";
                $f[] = "	done";
                $f[] = "	log_action_end_msg 1";
                $f[] = "	exit 1";
            }else{
                $f[]="	start-stop-daemon --start --quiet --pidfile \$PIDFILE --exec \$BINARY$DAEMON_ARGS";
                $f[]="	return=\$?";
                $f[]="	if [ \$return -eq 0 ]; then";
                $f[]="		sleep 1";
                $f[]="		exit 0";
                $f[]="	fi";
                $f[]="	log_end_msg 0";
                $f[]="	exit 0";
            }
            $f[]="";
            $f[]="}";
            $f[]="do_stop(){";
            $f[]="";
            if($COMPLIANCE) {
                $f[] = " 	log_action_begin_msg \"Stopping $daemonbinLog PID \$pidnum\"";
                $f[] = "    start-stop-daemon --stop --quiet --oknodo --pidfile \$PIDFILE";
                $f[] = "    log_end_msg \$?";
                $f[] = "    rm -f \$PIDFILE";
                $f[] = "    killproc -p \$PIDFILE \$DAEMON";
                $f[] = $php5Killer;
                $f[] = "    return \$?";
            }else {
                $f[] = "	pidnum=\$(getpid)";
                $f[] = "	    if [ \"\$pidnum\" -eq \"0\" ]; then ";
                $f[] = "	    	log_action_begin_msg \"Daemon is currently stopped\"";
                $f[] = "	    	log_action_end_msg 0";
                $f[] = $php5Killer;
                $f[] = "	    	return 0";
                $f[] = "	   fi";
                $f[] = "";
                $f[] = "	  kill -9 \$pidnum";
                $f[] = $php5Killer;
                $f[] = " 	  for i in 1 2 3 4 5";
                $f[] = "		do";
                $f[] = "		pidnum=\$(getpid)";
                $f[] = "		if [ \"\$pidnum\" -eq \"0\" ]; then ";
                $f[] = " 		 log_action_end_msg 0";
                $f[] = " 		 return 0";
                $f[] = " 		fi";
                $f[] = " 		sleep 1";
                $f[] = "	done";
                $f[] = "	log_action_end_msg 1";
                $f[] = "	return 1";
            }
            $f[]="";
            $f[]="}";
            $f[]="";
            $f[]="";
            $f[]="case \"\$1\" in";
            $f[]=" start)";
            $f[]="    do_start";
            $f[]="  ;;";
            $f[]="";
            $f[]="  stop)";
            $f[]="     do_stop";
            $f[]="  ;;";
            $f[]="";
            $f[]="  restart|force-reload)";
            $f[]="     do_stop";
            $f[]="     pidnum=\$(getpid)";
            $f[]="     if [ \"\$pidnum\" -eq \"0\" ]; then ";
            $f[]="      do_start";
            $f[]="     fi";
            $f[]="     exit 1";
            $f[]="     ";
            $f[]=" ;;";
            $f[]="  status)";
            $f[]="";
            $f[]="	    pidnum=\$(getpid)";
            $f[]="	    if [ \"\$pidnum\" -gt \"0\" ]; then ";
            $f[]="	    	echo \"Daemon is currently running PID \$pidnum\"";
            $f[]="	    else";
            $f[]="	    	echo \"PID sent is \$pidnum, daemon not running\"";
            $f[]="	    fi";
            $f[]="    ;;";
            $f[]="  *)";
            $f[]="    echo \"Usage: \$0 {start|stop|restart|status}\"";
            $f[]="    exit 1";
            $f[]="    ;;";
            $f[]="esac";
            $f[]="exit 0";
            $f[]="";

           $md5_src=null;
            if(is_file($INITD_PATH)){
                $md5_src=md5_file($INITD_PATH);
            }

            @file_put_contents($INITD_PATH, @implode("\n", $f));
            @chmod($INITD_PATH,0755);
            $md5_dst=md5_file($INITD_PATH);
            if($md5_dst==$md5_src){return true;}
            if(is_file('/usr/sbin/update-rc.d')){
                shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
            }

            if(is_file('/sbin/chkconfig')){
                shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
                shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
            }
        return true;




    }

    public function create_service($INITD,$binary,$param=null,$startlines=array(),$stoplines=array()):bool{
        $date       = date("Y-m-d H:is");
        $php        = null;
        $updaterc   = "/usr/sbin/update-rc.d";
        $chconf     = "/sbin/chkconfig";
        $INITD_PATH = "/etc/init.d/$INITD";
        if($GLOBALS["VERBOSE"]){echo "create_service: init.d: $INITD_PATH\n";}

        if($param<>null){$param=" $param";}
        if(preg_match("#\.php$#",$binary)){ $php=$this->LOCATE_PHP5_BIN()." "; }

        $f[]="#! /bin/sh";
        $f[]="#";
        $f[]="# Written by Artica on $date";
        $f[]="#";
        $f[]="### BEGIN INIT INFO";
        $f[]="# Provides:          $INITD";
        $f[]="# Required-Start:     \$remote_fs  \$syslog";
        $f[]="# Required-Stop:      \$remote_fs  \$syslog";
        $f[]="# Default-Start:     2 3 4 5";
        $f[]="# Default-Stop:      0 1 6";
        $f[]="# Short-Description: See Artica main console";
        $f[]="### END INIT INFO";
        $f[]="";
        $f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
        $f[]="case \"\$1\" in";
        $f[]=" start)";
        if(count($startlines)==0) {
            $f[] = "    $php$binary --start$param \$2 \$3";
        }else{
            foreach ($startlines as $lines){
                $f[] = "    $lines";
            }
        }
        $f[]="    ;;";
        $f[]="";
        $f[]="  stop)";
        if(count($stoplines)==0) {
            $f[] = "    $php$binary --stop$param \$2 \$3";
        }else{
            foreach ($stoplines as $lines){
                $f[] = "    $lines";
            }
        }
        $f[]="    ;;";
        $f[]="";
        $f[]=" restart)";
        if(count($stoplines)==0) {
            $f[] = "    $php$binary --restart$param \$2 \$3";
        }else{
            $zmd5=array();
            foreach ($stoplines as $lines){
                $zmd5[md5($lines)]=true;
                $f[] = "    $lines";
            }
            foreach ($startlines as $lines){
                if(isset($zmd5[md5($lines)])){continue;}
                $f[] = "    $lines";
            }
        }
        $f[]="    ;;";
        $f[]="";
        $f[]=" reconfigure)";
        $f[]="    $php$binary --build$param \$2 \$3";
        $f[]="    ;;";
        $f[]="";
        $f[]="  *)";
        $f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
        $f[]="    exit 1";
        $f[]="    ;;";
        $f[]="esac";
        $f[]="exit 0\n";

        @unlink($INITD_PATH);
        @file_put_contents($INITD_PATH, @implode("\n", $f));
        @chmod($INITD_PATH,0755);

        if(is_file($updaterc)){
            shell_exec("$updaterc -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
        }

        if(is_file($chconf)){
            shell_exec("$chconf --add " .basename($INITD_PATH)." >/dev/null 2>&1");
            shell_exec("$chconf --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
        }

        return true;

    }


    public function create_EtcHosts($hostname,$ipaddr){
        $netbiosname=null;
        if(strpos($hostname, ".")>0){
            $hostnameT=explode(".",$hostname);
            $netbiosname=$hostnameT[0];
        }
        $hostnameRegex=str_replace(".", "\.", $hostname);
        $f=explode("\n",@file_get_contents("/etc/hosts"));
        foreach ($f as $num=>$ligne){
            if(preg_match("#^([0-9\.]+).*?$hostnameRegex#", $ligne,$re)){
                $f[$num]=null;
            }
        }

        $f[]="$ipaddr\t$hostname\t$netbiosname";
        reset($f);
        $FINAL=array();
        foreach ($f as $num=>$ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            $FINAL[]=$ligne;
        }
        @file_put_contents("/etc/hosts", @implode("\n", $FINAL)."\n");

    }

    public function add_EtcHosts($hostname,$ip){$this->create_EtcHosts($hostname,$ip);}



    public function DISK_LIST(){
        if(!isset($GLOBALS["DEBUG_DISK"])){$GLOBALS["DEBUG_DISK"]=false;}
        $results=array();
        exec($this->find_program("df") ." -P -h -B G 2>&1",$returns);
        $pattern="#(.+)\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)\%\s+(.+?)$#";
        foreach ($returns as $ligne){
            if(!preg_match($pattern,$ligne,$re)){continue;}
            if($GLOBALS["DEBUG_DISK"]){echo trim($re[1])." = $re[2]$re[3]\n";}

            if(preg_match("#\/by-uuid\/(.+)#", trim($re[1]),$ri)){
                $uuid=trim($ri[1]);
                $UUIDS=$this->BLKID_UUIDs();
                if(isset($UUIDS[$uuid]["DEV"])){$re[1]=$UUIDS[$uuid]["DEV"];}
            }

            if($GLOBALS["DEBUG_DISK"]){echo trim($re[1])." = $re[2]$re[3]\n";}
            $results[trim($re[1])]=array("SIZE"=>$re[2],"UNIT"=>$re[3],"USED"=>$re[4],"DISP"=>$re[6],"POURC"=>trim($re[8]));

        }

        return $results;
    }

    public function DISK_INODES(){

        if(is_file("/etc/artica-postfix/settings/Daemons/InodesStatus")){
            $TimeF=$this->file_time_min("/etc/artica-postfix/settings/Daemons/InodesStatus");
            if($TimeF<60){
                return unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InodesStatus"));
            }
            @unlink("/etc/artica-postfix/settings/Daemons/InodesStatus");
        }



        $results=array();
        $dfbin=$this->find_program("df");

        if($this->process_exists($this->PIDOF($dfbin))){return array();}

        exec("$dfbin -i -h 2>&1",$returns);
        $pattern="#^(.+?)\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)%\s+#";
        foreach ($returns as $ligne){
            if(!preg_match($pattern,$ligne,$re)){
                if(preg_match("#^(.+?)\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)\s+([0-9,\.]+)([A-Z])\s+([0-9,\.]+)%\s+#", $ligne,$re)){

                    if(preg_match("#\/by-uuid\/(.+)#", trim($re[1]),$ri)){
                        $uuid=trim($ri[1]);
                        $UUIDS=$this->BLKID_UUIDs();
                        if(isset($UUIDS[$uuid]["DEV"])){$re[1]=$UUIDS[$uuid]["DEV"];}
                    }

                    $results[trim($re[1])]=array("INODES"=>$re[2].$re[3],"USED"=>$re[4],"FREE"=>$re[5].$re[6],"POURC"=>trim($re[7]));
                    continue;
                }
                continue;}

            if(preg_match("#\/by-uuid\/(.+)#", trim($re[1]),$ri)){
                $uuid=trim($ri[1]);
                $UUIDS=$this->BLKID_UUIDs();
                if(isset($UUIDS[$uuid]["DEV"])){$re[1]=$UUIDS[$uuid]["DEV"];}
            }

            $results[trim($re[1])]=array("INODES"=>$re[2].$re[3],"USED"=>$re[4].$re[5],"FREE"=>$re[6].$re[7],"POURC"=>trim($re[8]));

        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("InodesStatus", serialize($results));
        return $results;
    }



    public FUNCTION SQUID_PARMAZ(){
        if(isset($GLOBALS["SQUID_PARMAZ"])){
            if(count($GLOBALS["SQUID_PARMAZ"])>0){return $GLOBALS["SQUID_PARMAZ"];}
        }
        $squid=$this->LOCATE_SQUID_BIN();
        exec("$squid -v 2>&1",$results);
        if(preg_match("#configure options:(.+)#", @implode(" ", $results),$re)){
            $re[1]=str_replace("'", "", $re[1]);
            $opts=explode(" ",trim($re[1]));
            foreach ($opts as $ligne){
                $ligne=trim($ligne);
                if($ligne==null){continue;}
                if(preg_match("#(.+?)=(.*)#", $ligne,$re)){
                    $GLOBALS["SQUID_PARMAZ"][$re[1]]=trim($re[2]);
                    continue;
                }
                $GLOBALS["SQUID_PARMAZ"][$ligne]=true;
            }

        }

        return $GLOBALS["SQUID_PARMAZ"];

    }

    public function SQUID_ICAP_ENABLED():bool{
        $ARRAY=$this->SQUID_PARMAZ();
        if(isset($ARRAY["--enable-icap-client"])){return true;}
        if(isset($ARRAY["--enable-icap-support"])){return true;}
        return false;
    }



    public function file_size($path):int{
        if(!is_file($path)){return 0;}
        exec($this->find_program("stat")." $path 2>&1",$results);
        foreach ($results as $line){
            if(preg_match("#Size:\s+([0-9]+)\s+Blocks#",$line,$re)){
                return intval($re[1]);
            }
        }
        return 0;
    }

    public function file_size_human($path):int{
        if(!is_file($path)){if(!is_dir($path)){return 0;}}
        exec($this->find_program("du")." -h $path ",$results);
        foreach ($results as $line){
            if(preg_match("#^([0-9A-Z,]+)\s+#",$line,$re)){
                return intval($re[1]);
            }
        }
        return 0;
    }

    public function file_extension($filename){
        return @pathinfo($filename, PATHINFO_EXTENSION);
    }

    private function compress_gzip($source,$dest){

        $gzip=$this->find_program("gzip");
        if(!is_file($gzip)){
            $GLOBALS["COMPRESSOR_ERROR"]="binary gzip not found";
            return false;
        }
        if(is_file($dest)){@unlink($dest);}
        exec("$gzip -9 -c $source >$dest", $output, $return);
        if ($return != 0) {
            $GLOBALS["COMPRESSOR_ERROR"]=@implode(", ",$output);
            return false;
        }
        return true;
    }

    public function compress($source,$dest){
        if(!is_file($source)){
            $GLOBALS["COMPRESSOR_ERROR"]="Source not found";
            return false;
        }

        $size=filesize($source);
        $sizekb=$size/1024;
        $sizemb=$sizekb/1024;
        if($sizemb>250){
            return $this->compress_gzip($source,$dest);
        }


        if(!function_exists("gzopen")){return $this->compress_gzip($source,$dest);}
        $mode='wb9';
        $error=false;
        if(is_file($dest)){@unlink($dest);}
        $fp_out=gzopen($dest,$mode);
        if(!$fp_out){
            $GLOBALS["COMPRESSOR_ERROR"]="fp_out return false";
            return false ;
        }
        $fp_in=fopen($source,'rb');
        if(!$fp_in){
            $GLOBALS["COMPRESSOR_ERROR"]="fp_in return false";
            return false;
        }
        while(!feof($fp_in)){gzwrite($fp_out,fread($fp_in,1024*512));}
        fclose($fp_in);
        gzclose($fp_out);
        return true;
    }
    function getSystemMemInfo(){
        $data = explode("\n", file_get_contents("/proc/meminfo"));
        $meminfo = array();
        foreach ($data as $line) {
            if(strpos($line, ":")==0){continue;}
            list($key, $val) = explode(":", $line);
            $val=trim($val);
            if(preg_match("#^([0-9\.]+)\s+#", $val,$re)){$val=$re[1];}
            $meminfo[$key] = trim($val);
        }
        return $meminfo;
    }

    function GetMemFreePourc():int{
        $array=$this->getSystemMemInfo();
        $MemTotal=$array["MemTotal"];
        $MemFree=$array["MemFree"];
        if(!is_numeric($MemFree)){return 0;}
        if(!is_numeric($MemTotal)){return 0;}
        $percent=$MemFree/$MemTotal;
        return round($percent*100);

    }


    public function uncompress($srcName, $dstName) {

        if(!is_file($srcName)){
            $GLOBALS["UNCOMPRESSLOGS"]="Fatal!! $srcName -> No such file";
            $this->_syslog("Fatal!! $srcName -> No such file");
            return false;
        }

        $srcNameText=basename($srcName);
        $GLOBALS["UNCOMPRESS"]=array();
        if(!function_exists("gzopen")){
            $called=null;if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
            if($GLOBALS["VERBOSE"]){echo "uncompress:: FATAL!! gzopen no such function ! $called in\n";}
            $GLOBALS["UNCOMPRESS"][]="$srcNameText FATAL!! gzopen no such function";
            $this->_syslog("Fatal!! gzopen no such function ! $called in ".__FUNCTION__." line ".__LINE__, basename(__FILE__));
            return false;
        }
        $dir=dirname($dstName);
        if(!is_dir($dir)){
            $GLOBALS["UNCOMPRESSLOGS"]="Fatal!! $dir no such directory";
            if($GLOBALS["VERBOSE"]){echo "uncompress:: $dir no such directory\n";}
            return false;
        }

        $dstNameTMP="$dstName.tests";
        $sfp = gzopen($srcName, "rb");

        if(!$sfp){
            $GLOBALS["UNCOMPRESSLOGS"]="gzopen($srcName) return false";
            $GLOBALS["UNCOMPRESS"][]="gzopen($srcName) return false";
            if($GLOBALS["VERBOSE"]){

                echo "gzopen($srcName) return false";}
            return false;
        }
        $fp = fopen($dstNameTMP, "w");



        if(!$fp){if($GLOBALS["VERBOSE"]){
            $GLOBALS["UNCOMPRESSLOGS"]="fopen($dstNameTMP) Permission denied";
            $GLOBALS["UNCOMPRESS"][]="fopen($dstNameTMP) Permission denied.";
            echo "fopen($dstNameTMP) Permission denied.\n";}
            return false;
        }

        while ($string = gzread($sfp, 4096)) {fwrite($fp, $string, strlen($string));}
        gzclose($sfp);
        fclose($fp);

        if(!is_file($dstNameTMP)){
            $GLOBALS["UNCOMPRESSLOGS"]="Fatal!! $srcName -> $dstNameTMP Permission denied";
            $this->_syslog("Fatal!! $srcName -> $dstNameTMP Permission denied !!");
            $GLOBALS["UNCOMPRESS"][]="$dstNameTMP Permission denied";
            if($GLOBALS["VERBOSE"]){echo "$srcName -> $dstNameTMP Permission denied\n";}
            return false;
        }


        $srcNameSize=@filesize($srcName);
        $srcNameSize=round($srcNameSize/1024,2);
        $GLOBALS["UNCOMPRESS"][]="Source File size: {$srcNameSize}Kb";
        $size=@filesize($dstNameTMP);
        if($size==0){
            $GLOBALS["UNCOMPRESSLOGS"]="Fatal!! $srcName -> $dstNameTMP $size = 0 bytes !!";
            $this->_syslog("Fatal!! $srcName -> $dstNameTMP $size = 0 bytes !!");
            $GLOBALS["UNCOMPRESS"][]="$dstNameTMP size = 0";
            if($GLOBALS["VERBOSE"]){echo "$srcName -> $dstNameTMP $size = 0 bytes !!\n";}
            @unlink($dstNameTMP);
            return false;
        }

        @unlink($dstName);
        $GLOBALS["UNCOMPRESS"][]="$dstNameTMP - > $dstName";
        if($GLOBALS["VERBOSE"]){echo "$dstNameTMP - > $dstName\n";}
        @copy($dstNameTMP, $dstName);
        @unlink($dstNameTMP);
        return true;

    }

    function squid_version(){
        $squidbin=$this->LOCATE_SQUID_BIN();
        if(!is_file($squidbin)){return "0.0.0";}
        exec("$squidbin -v 2>&1",$results);
        foreach ($results as $val){
            if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
                return trim($re[1]);
            }
        }

    }







    function samba_version(){

        return $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
    }



    public function _syslog($text,$file=null,$error=false){
        if(!function_exists("syslog")){return;}
        if($file<>null){$file=basename($file);}
        if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
        openlog($file, LOG_PID , LOG_SYSLOG);
        syslog($LOG_SEV, $text);
        closelog();
    }

    public function is_socket($socketpath){
        $stat=$this->alt_stat($socketpath);
        if(!isset($stat["filetype"])){return false;}
        $type=$stat["filetype"]["type"];
        if($type=="socket"){return true;}
        return false;
    }

    public function IS_FIREHOLE_ACTIVE(){
        $firehol=$this->find_program("firehol");
        if(!is_file($firehol)){return false;}
        if(!is_file("/etc/artica-postfix/settings/Daemons/FireHolConfigured")){return false;}
        $FireHolConfigured=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolConfigured"));
        if($FireHolConfigured==0){return false;}
        if(!is_file("/etc/artica-postfix/settings/Daemons/FireHolEnable")){return false;}
        $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
        if($FireHolEnable==0){return false;}
        return true;

    }

    public function alt_stat($file=null) {
        if($file==null){return false;}
        $ss=@stat($file);
        if(!$ss) return false; //Couldnt stat file

        $ts=array(
            0140000=>'ssocket',
            0120000=>'llink',
            0100000=>'-file',
            0060000=>'bblock',
            0040000=>'ddir',
            0020000=>'cchar',
            0010000=>'pfifo'
        );

        $p=$ss['mode'];
        $t=decoct($ss['mode'] & 0170000); // File Encoding Bit

        $str =(array_key_exists(octdec($t),$ts))?$ts[octdec($t)][0]:'u';
        $str.=(($p&0x0100)?'r':'-').(($p&0x0080)?'w':'-');
        $str.=(($p&0x0040)?(($p&0x0800)?'s':'x'):(($p&0x0800)?'S':'-'));
        $str.=(($p&0x0020)?'r':'-').(($p&0x0010)?'w':'-');
        $str.=(($p&0x0008)?(($p&0x0400)?'s':'x'):(($p&0x0400)?'S':'-'));
        $str.=(($p&0x0004)?'r':'-').(($p&0x0002)?'w':'-');
        $str.=(($p&0x0001)?(($p&0x0200)?'t':'x'):(($p&0x0200)?'T':'-'));
        if(!isset($ss['type'])){$ss['type']=null;}
        $s=array(
            'perms'=>array(
                'umask'=>sprintf("%04o",@umask()),
                'human'=>$str,
                'octal1'=>sprintf("%o", ($ss['mode'] & 000777)),
                'octal2'=>sprintf("0%o", 0777 & $p),
                'decimal'=>sprintf("%04o", $p),
                'fileperms'=>@fileperms($file),
                'mode1'=>$p,
                'mode2'=>$ss['mode']),

            'owner'=>array(
                'fileowner'=>$ss['uid'],
                'filegroup'=>$ss['gid'],
                'owner'=>
                    (function_exists('posix_getpwuid'))?
                        @posix_getpwuid($ss['uid']):'',
                'group'=>
                    (function_exists('posix_getgrgid'))?
                        @posix_getgrgid($ss['gid']):''
            ),

            'file'=>array(
                'filename'=>$file,
                'realpath'=>(@realpath($file) != $file) ? @realpath($file) : '',
                'dirname'=>@dirname($file),
                'basename'=>@basename($file)
            ),

            'filetype'=>array(
                'type'=>substr($ts[octdec($t)],1),
                'type_octal'=>sprintf("%07o", octdec($t)),
                'is_file'=>@is_file($file),
                'is_dir'=>@is_dir($file),
                'is_link'=>@is_link($file),
                'is_readable'=> @is_readable($file),
                'is_writable'=> @is_writable($file)
            ),

            'device'=>array(
                'device'=>$ss['dev'], //Device
                'device_number'=>$ss['rdev'], //Device number, if device.
                'inode'=>$ss['ino'], //File serial number
                'link_count'=>$ss['nlink'], //link count
                'link_to'=>($ss['type']=='link') ? @readlink($file) : ''
            ),

            'size'=>array(
                'size'=>$ss['size'], //Size of file, in bytes.
                'blocks'=>$ss['blocks'], //Number 512-byte blocks allocated
                'block_size'=> $ss['blksize'] //Optimal block size for I/O.
            ),

            'time'=>array(
                'mtime'=>$ss['mtime'], //Time of last modification
                'atime'=>$ss['atime'], //Time of last access.
                'ctime'=>$ss['ctime'], //Time of last status change
                'accessed'=>@date('Y M D H:i:s',$ss['atime']),
                'modified'=>@date('Y M D H:i:s',$ss['mtime']),
                'created'=>@date('Y M D H:i:s',$ss['ctime'])
            ),
        );

        return $s;
    }

    private function MapperDetect($dev):string{
        $mapper=@readlink($dev);
        if($mapper==null){return $dev;}
        return str_replace("../mapper","/dev/mapper",$mapper);

    }

    private function folder_to_regex($path):string{
        $path=str_replace("/","\/",$path);
        $path=str_replace(".","\.",$path);
        $path=str_replace("$","\$",$path);
        $path=str_replace("[","\[",$path);
        $path=str_replace("]","\]",$path);
        $path=str_replace("(","\(",$path);
        $path=str_replace(")","\)",$path);
        $path=str_replace("?","\?",$path);
        $path=str_replace("!","\!",$path);
        $path=str_replace("{","\{",$path);
        return str_replace("}","\}",$path);

    }

    function LESSFS_ARRAY():array{
        $datas=explode("\n",@file_get_contents("/proc/mounts"));
        $array=array();
        foreach ($datas as $ligne){
            if(preg_match("#^lessfs\s+(.+?)\s+fuse\.lessfs\s+#",$ligne,$re)){$array[trim($re[1])]=true;}
        }
        return $array;

    }

    public function DISK_MOUNTED($dev):bool{
        $dev=$this->MapperDetect($dev);
        $datas=explode("\n",@file_get_contents("/proc/mounts"));
        $dev=$this->folder_to_regex($dev);

        foreach ($datas as $ligne){
            if(preg_match("#$dev#",$ligne)){
                writelogs_framework("\"$dev\"=$ligne",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                return true;
            }
        }
        return false;
    }

    public function MOUNTED_PATH($dev):string{
        if(strpos($dev,"by-uuid")==0){
            $dev=$this->MapperDetect($dev);
        }
        $dev=$this->folder_to_regex($dev);
        $datas=explode("\n",@file_get_contents("/proc/mounts"));
        $pattern="#^$dev\s+(.+?)\s+(btrfs|ntfs|ext|rw|fusectl|rpc_pipefs|reiserfs|xfs)";
        if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__.":: $pattern\n";}
        foreach ($datas as $ligne){
            if(preg_match("#^$dev\s+(.+?)\s+(btrfs|ntfs|ext|rw|fusectl|rpc_pipefs|reiserfs|xfs)#",$ligne,$re)){
                return trim($re[1]);
            }
        }
        return "";
    }

    public function MOUNTED_DIRS($rpath):array{
        if($rpath==null){return array();}
        $results=array();

        $rpath=$this->MapperDetect($rpath);
        $rpath=$this->folder_to_regex($rpath);
        if($GLOBALS["VERBOSE"]){
            echo "Line: ".__LINE__.":: -> ^(.*?)\s+$rpath(.*?)\s+#  ... ".__FUNCTION__."\n";
        }

        $datas=explode("\n",@file_get_contents("/proc/mounts"));
        foreach ($datas as $ligne){
            if(!preg_match("#^.*?\s+$rpath(.*?)\s+#",$ligne,$re)){
                continue;
            }
            echo "Line: ".__LINE__.":: $ligne -> ^.*?\s+$rpath.+?\s+# match ".__FUNCTION__."\n";
            if(preg_match("#^.*?\s+(.+?)\s+#",$ligne,$re)) {
                echo "Line: ".__LINE__.":: $ligne -> ^.*?\s+$rpath.+?\s+# match $re[1]".__FUNCTION__."\n";
                $results[] = trim($re[1]);
            }
        }
        return $results;
    }

    public function MOUNTED_DIR($path):string{
        $path=$this->MapperDetect($path);
        $path=$this->folder_to_regex($path);
        $datas=explode("\n",@file_get_contents("/proc/mounts"));
        foreach ($datas as $ligne){
            if(!preg_match("#^(.*?)\s+$path\s+(ntfs|btrfs|ext|rw)#",$ligne,$re)){
                if($GLOBALS["VERBOSE"]){echo "Line: ".__LINE__.":: $ligne -> ^(.*?)\s+$path\s+(btrfs|ext|rw) NOT match ".__FUNCTION__."\n";}
                continue;
            }
            return trim($re[1]);
        }
        return "";}

    public function LSOF_PIDS($directory):array{
        $pids=array();
        $lsof=$this->find_program("lsof");
        exec("$lsof $directory 2>&1",$results);
        foreach ($results as $ligne){
            if(preg_match("#^.+?\s+([0-9]+)#",$ligne,$re)){
                $pids[]=$re[1];
            }
        }
        return $pids;
    }


    public function MOUNTED_TMPFS_MEM($directory):int{
        $datas=explode("\n",@file_get_contents("/proc/mounts"));
        $tmpfs=array();
        foreach ($datas as $ligne){
            if(preg_match("#^tmpfs\s+(.+?)\s+tmpfs\s+.+?size=([0-9]+)k\s+#",$ligne,$re)){
                $tmpfs[trim($re[1])]=round($re[2]/1024);
                if($GLOBALS["VERBOSE"]){echo "MOUNTED_TMPFS_MEM:: FOUND {$re[1]} For ".round($re[2]/1024)."M\n";}
                continue;
            }
            if(preg_match("#^tmpfs\s+(.*?)\s+tmpfs\s+.*?,size=([0-9]+)k,#", $ligne,$re)){
                $tmpfs[trim($re[1])]=round($re[2]/1024);
                if($GLOBALS["VERBOSE"]){echo "MOUNTED_TMPFS_MEM:: FOUND {$re[1]} For ".round($re[2]/1024)."M\n";}
            }
        }
        if(!isset($tmpfs[trim($directory)])){return 0;}
        return intval($tmpfs[trim($directory)]);

    }
    public function PROCESS_TIME_INT($pid):int{
        $sec=0;
        if($pid<5){return 0;}
        if($pid==null){return 0;}
        exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
        foreach ($results as $ligne){
            if(!preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){continue;}
            $sec=$re[1];
            break;

        }

        if($sec==null){return 0;}
        return intval(time()-$sec);
    }

    public function PROCCESS_TIME_MIN($pid,$killOnTTL=0):int{
        $sec=null;
        if($pid<5){return 0;}
        if($pid==null){return 0;}
        exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(!preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){continue;}
            $sec=$re[1];
            break;

        }

        if($sec==null){return 0;}


        if($sec>0){
            $sec=$sec/60;
            $return=intval(trim(round($sec)));
            if($killOnTTL>0){
                if($return>$killOnTTL){
                    $kill=$this->find_program("kill");
                    if($pid>5){ unix_system_kill_force($pid); }
                    return 0;
                }
            }

            return $return;
        }
        return 0;
    }

    public function ServerRunSince(){

        return $this->time_min($this->uptime_int());
    }

    private function uptime_int(){
        $e=file('/proc/uptime');
        preg_match('#(.*) (.*)#', $e[0], $e);
        $t=time();
        $Since=$t-intval($e[1]);
        return $Since;
    }

    public function PROCESS_MEMORY($pid,$noMo=false){
        //$PPID=$this->PROCESS_PPID();
        //if($PPID>1)

        if(!is_file("/proc/$pid/status")){return 0;}
        $f=@explode("\n",@file_get_contents("/proc/$pid/status"));
        $pattern="#^VmRSS:\s+([0-9]+)#";
        foreach ($f as $num=>$ligne){
            if(preg_match($pattern,$ligne,$re)){
                if($noMo){return $re[1];}
                return round($re[1]/1024,2);
            }
        }

    }

    public function isMaxInstances(){

        $MaxInstnaces=11;
        $MaxInstancesToDie=16;
        $p=new processes_php();
        $MemoryInstances=$p->MemoryInstances();
        if(!is_numeric($MemoryInstances)){$MemoryInstances=0;}
        if($MemoryInstances>$MaxInstancesToDie){return true;}
        if($MemoryInstances>$MaxInstnaces){return true;}
        return false;

    }


    public function PROCESS_CACHE_MEMORY($pid,$noMo=false){
        //$PPID=$this->PROCESS_PPID();
        //if($PPID>1)

        if(!is_file("/proc/$pid/status")){return 0;}
        $f=@explode("\n",@file_get_contents("/proc/$pid/status"));
        $pattern="#^VmSize:\s+([0-9]+)#";
        foreach ($f as $num=>$ligne){
            if(preg_match($pattern,$ligne,$re)){
                if($noMo){return $re[1];}
                return round($re[1]/1024,2);
            }
        }

    }

    public function PROCESS_PPID($pid):int{
        $pid=trim($pid);
        $pid=str_replace("\r", "", $pid);
        $pid=str_replace("\n", "", $pid);
        if(!is_file("/proc/$pid/status")){
            if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: unable to stat `/proc/$pid/status`\n";}
            return 0;
        }
        $f=@explode("\n",@file_get_contents("/proc/$pid/status"));
        $pattern="#^PPid:\s+([0-9]+)#";
        foreach ($f as $ligne){
            if(preg_match($pattern,$ligne,$re)){
                if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: Found {$re[1]}\n";}
                if(trim($re[1])<50){return intval($pid);}
                $cmdline=trim(@file_get_contents("/proc/$re[1]/cmdline"));
                if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: CMDLINE=[$cmdline]\n";}
                if($cmdline=="-bash"){
                    return intval($pid);
                }


                return intval(trim($re[1]));
            }
        }
        if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: not Found return $pid\n";}
        return intval($pid);

    }

    public function PROCESS_SOCKETS_NUM($pid){
        if(!is_dir("/proc/$pid/fd")){return 0;}
        $list = @glob("/proc/$pid/fd/*");
        $c=0;
        foreach ($list as $line){
            if(!is_link($line)){continue;}
            $line =@readlink($line);
            if(!preg_match("#^socket:#", $line)){continue;}
            $c++;

        }

    }
    public function network_test_port($ip,$port):bool{
        $fp = @stream_socket_client("tcp://$ip:$port",$errno, $errstr,3, STREAM_CLIENT_CONNECT);
        if($fp){@socket_close($fp);return true;}
        return false;
    }


    public function PROCESS_STATUS($pid):array{
        $STATUS=null;
        $PPID=0;
        $datas=explode("\n",@file_get_contents("/proc/$pid/status"));
        if(!is_array($datas)){return array();}
        foreach ($datas as $ligne){
            if(preg_match("#State:\s+([A-Z])\s+#",$ligne,$re)){
                $STATUS=$re[1];
            }
            if(preg_match("#PPid:\s+([0-9]+)#",$ligne,$re)){
                $PPID=$re[1];
            }
        }
        if($STATUS==null){return array(0,0);}
        return array($STATUS,$PPID);
    }


    public function get_pid_from_file($file):int{
        $GLOBALS["get_pid_from_file"]=array();
        if(!is_file($file)){
            $GLOBALS["get_pid_from_file"][]="get_pid_from_file():$file no such file -> 0";
            return 0;
        }

        $datas=trim(@file_get_contents($file));
        $GLOBALS["get_pid_from_file"][]="get_pid_from_file():data:$datas";
        if(strpos($datas, "\n")>0){
            $tr=explode("\n",$datas);
            foreach ($tr as $ligne){
                $ligne=trim($ligne);
                if($ligne==null){continue;}
                $GLOBALS["get_pid_from_file"][]="get_pid_from_file():loop:$ligne";
                if(preg_match("#([0-9]+)#",trim($ligne),$re)){
                    return intval($re[1]);
                }
            }

        }
        $GLOBALS["get_pid_from_file"][]="get_pid_from_file():end:$datas";
        if(preg_match("#([0-9]+)#",$datas,$re)){return intval($re[1]);}
        return 0;
    }
    public function remove_monit_file($path):bool{
        if(!is_file($path)){return true;}
        @unlink($path);
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
        return true;
    }

    public function remove_service($INITD_PATH):bool{
        if( strpos(" $INITD_PATH","/")==0 ){
            $INITD_PATH="/etc/init.d/$INITD_PATH";
        }
        if(!is_file($INITD_PATH)){return true;}
        system("$INITD_PATH stop");
        $basename=basename($INITD_PATH);
        if(is_file("/usr/bin/systemctl")){shell_exec("/usr/bin/systemctl disable --now $basename");}
        if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
        if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
        if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
        return true;
    }


    public function HostToIp($hostname):string{
        if(trim($hostname)==null){return "";}
        $hostname=str_replace('$','',$hostname);
        $ip=gethostbyname($hostname);

        if($ip<>$hostname){
            if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$ip)){return $ip;}
        }
        $nmblookup=$this->find_program("nmblookup");

        if($nmblookup==null){return "";}
        if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$hostname)){return "";}
        $cmd="$nmblookup $hostname";
        exec($cmd,$results);

        foreach ($results as $ligne){
            writelogs_framework("$ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
            if(preg_match("#^([0-9\.]+)\s+#",$ligne,$re)){return $re[1];}
        }
        return "";
    }
    public function IpToHostname($ipaddr):string{
        $ipaddr=trim($ipaddr);
        if(!$this->isIPAddress($ipaddr)){return $ipaddr;}
        if(!isset($GLOBALS["IPNAMES"])){$GLOBALS["IPNAMES"]=array();}
        if(isset($GLOBALS["IPNAMES"][$ipaddr])){return $GLOBALS["IPNAMES"][$ipaddr];}

        if(count($GLOBALS["IPNAMES"])>1500){$GLOBALS["IPNAMES"]=array();}
        $this->ToSyslog(__FUNCTION__."[".__LINE__."]: gethostbyaddr($ipaddr) ");
        $hostres=gethostbyaddr($ipaddr);
        if(!$this->isIPAddress($hostres)){
            $GLOBALS["IPNAMES"][$ipaddr]=$hostres;
            return $hostres;
        }
        $GLOBALS["IPNAMES"][$ipaddr]=$ipaddr;
        return $ipaddr;
    }


    public function IpToMac($ipaddr){
        $ipaddr=trim($ipaddr);

        if(!is_file("/usr/bin/arping")){return;}
        $ttl=date('YmdH');
        if(!isset($GLOBALS["CACHEARP"])){$GLOBALS["CACHEARP"]=array();}
        if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
        if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}

        $cmd="/usr/bin/arping -f -c 1 $ipaddr 2>&1";
        exec($cmd,$results);
        foreach ($results as $num=>$line){
            if(!preg_match("#reply from.*?\[(.+?)\]#", $line,$re)){ continue; }
            $re[1]=trim(strtolower($re[1]));

            if($this->IsvalidMAC($re[1])){
                $GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
                return;
            }else{
                $GLOBALS["CACHEARP"][$ttl][$ipaddr]="00:00:00:00:00:00";
                return "00:00:00:00:00:00";
            }

        }
    }

    public static function isIPAddress( $ip ) {
        $ip=trim($ip);
        if($ip==null){return false;}
        if ( !$ip ) return false;
        if ( is_array( $ip ) ) {return false;}
        if(preg_match("#(.+?)\/([0-9]+)$#", $ip,$re)){
            $re[2]=trim($re[2]);
            if(strlen($re[2])>2){return false;}
            $ip=$re[1];
        }
        if(preg_match("#^".RE_IP_ADD."$#", $ip,$re)){return true;}
        if(preg_match("#^".RE_IPV6_ADD."$#", $ip,$re)){return true;}
        if(substr_count($ip, '::')>0){return false;}
        return false;
    }
    private static function IsvalidMAC($MAC){
        $MAC=str_replace(":", "-", $MAC);
        If(strlen($MAC) > 17) return false;
        If($MAC == "") return false;
        If (!preg_match("#^[0-9A-Z]+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)$#i",$MAC)) return false;
        $Array=explode("-",$MAC);
        If(strlen($Array[0]) != 2) return false;
        If(strlen($Array[1]) != 2) return false;
        If(strlen($Array[2]) != 2) return false;
        If(strlen($Array[3]) != 2) return false;
        If(strlen($Array[4]) != 2) return false;
        If(strlen($Array[5]) != 2) return false;
        return true;
    }


    public function AddFSTab($dev,$mount){
        $hd=new hd($dev);
        $hd->AddFSTab($mount);
    }

    public function DelFSTab($dev){
        if($dev==null){return null;}
        $datas=explode("\n",@file_get_contents("/etc/fstab"));
        foreach ($datas as $num=>$ligne){
            if($ligne==null){continue;}
            if(preg_match("#(.+?)\s+(.+?)\s+#",$ligne,$re)){
                if(trim(strtolower($dev))==trim(strtolower($re[1]))){
                    shell_exec($this->find_program("umount")." -l $dev");
                    unset($datas[$num]);
                    @rmdir(trim($re[2]));
                }
            }
        }

        if(!file_put_contents("/etc/fstab",implode("\n",$datas))){
            writelogs_framework("Unable to save /etc/fstab",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        }


    }

    public function DelFSTabByMount($mountPoint){

        $umountbin=$this->find_program("umount");
        shell_exec("$umountbin -l $mountPoint >/dev/null 2>&1");
        $mountPoint=str_replace(".", "\.", $mountPoint);
        $mountPoint=str_replace("/", "\/", $mountPoint);


        $t=array();
        $datas=explode("\n",@file_get_contents("/etc/fstab"));
        foreach ($datas as $ligne){
            if(trim($ligne)==null){continue;}
            if(!preg_match("#$mountPoint#", $ligne)){
                $t[]=$ligne;
            }

        }



        if(!file_put_contents("/etc/fstab",implode("\n",$t))){
            writelogs_framework("Unable to save /etc/fstab",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        }

    }


    public function GetFSTabMountPoint($dev){
        if($dev==null){return null;}
        $dev=$this->MapperDetect($dev);
        $array=array();
        $dev=str_replace("/","\/",$dev);
        $datas=explode("\n",@file_get_contents("/etc/fstab"));
        foreach ($datas as $num=>$ligne){
            if($ligne==null){continue;}
            if(preg_match("#$dev\s+(.+?)\s+#",$ligne,$re)){
                $array[]=trim($re[1]);
            }
        }

        return $array;

    }


    function SendEmailConfigured($arrayParams,$subject=null,$content=null){
        include_once('/usr/share/artica-postfix/ressources/class.mail.inc');
        include_once('/usr/share/artica-postfix/ressources/smtp/class.smtp.loader.inc');

        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();
            if(isset($trace[1])){
                $file=basename($trace[1]["file"]);
                $function=$trace[1]["function"];
                $line=$trace[1]["line"];

            }

        }
        if($subject==null){$subject="No subject...";}
        $smtp_dest=$arrayParams["smtp_dest"];
        if($smtp_dest==null){echo "No recipient\n";return false;}
        if(trim($arrayParams["smtp_server_name"])==null){echo "No valid SMTP server\n";return false;}

        $array_load=sys_getloadavg();
        $internal_load=$array_load[0];
        $content=str_replace("\n", "\r\n", $content);
        $content=$content."#---------------------\r\nCurrent load:$internal_load\r\nFrom: $file in line $line function $function()\r\n";


        $hostname=$this->hostname_g();
        $addresses=array();
        if(strpos($smtp_dest, ";")>0){
            $destZ=explode(";",$smtp_dest);
            foreach ($destZ as $num=>$email){
                if(preg_match("#^(.+?)@([a-z0-9\.\-\_]+)$#", $email,$re)){$addresses[]="{$re[1]}@{$re[2]}";}
            }
        }


        if(strpos($smtp_dest, ",")>0){
            $destZ=explode(",",$smtp_dest);
            foreach ($destZ as $num=>$email){
                if(preg_match("#^(.+?)@([a-z0-9\.\-\_]+)$#", $email,$re)){$addresses[]="{$re[1]}@{$re[2]}";}
            }
        }


        if(count($addresses)==0){$addresses[]=$smtp_dest;}
        if(!is_numeric($arrayParams["smtp_server_port"])){$arrayParams["smtp_server_port"]=25;}
        $smtp_sender=$arrayParams["smtp_sender"];
        if($smtp_sender==null){$smtp_sender="root@$hostname";}

        $results=true;
        $subject="[$hostname]: $subject";
        foreach ($addresses as $num=>$DESTINATAIRE){
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->IsSMTP();
            $mail->AddAddress($DESTINATAIRE,$DESTINATAIRE);
            $mail->AddReplyTo($smtp_sender,$smtp_sender);
            $mail->From=$smtp_sender;
            $mail->FromName=$smtp_sender;

            $mail->Subject=$subject;
            $mail->Body=$content;
            $mail->Hostname=$hostname;
            $mail->Host=$arrayParams["smtp_server_name"];
            $mail->Port=$arrayParams["smtp_server_port"];

            if(($arrayParams["smtp_auth_user"]<>null) && ($arrayParams["smtp_auth_passwd"]<>null)){
                $mail->SMTPAuth=true;
                $mail->Username=$arrayParams["smtp_auth_user"];
                $mail->Password=$arrayParams["smtp_auth_passwd"];
                if($arrayParams["tls_enabled"]==1){$mail->SMTPSecure = 'tls';}
                if($arrayParams["ssl_enabled"]==1){$mail->SMTPSecure = 'ssl';}
            }

            if(!$mail->Send()){$results=false;continue;}

        }
        return $results;

    }

    public function TOTAL_MEMORY_MB(){
        if(isset($GLOBALS["TOTAL_MEMORY_MB"])){return $GLOBALS["TOTAL_MEMORY_MB"];}
        $array=$this->TOTAL_MEMORY_KB_WORK();
        $Total=$array[0];
        $GLOBALS["TOTAL_MEMORY_MB"]=round($Total/1024);
        return $GLOBALS["TOTAL_MEMORY_MB"];

    }

    public function TOTAL_MEM_POURCENT_USED():int{
        $array=$this->TOTAL_MEMORY_KB_WORK();
        $Total=intval($array[0]);
        if($Total==0){
            return 0;
        }
        $Used=$array[1];
        $prc=$Used/$Total;
        $prc=round($prc*100);
        return $prc;
    }



    private function TOTAL_MEMORY_KB_WORK():array{
        if(!is_file("/etc/artica-postfix/mem.pl.txt")){
            return array(0,0);
        }
        $results=explode("\n",file_get_contents("/etc/artica-postfix/mem.pl.txt"));

        foreach ($results as $ligne){
            if(!preg_match("#T=([0-9]+)\s+U=([0-9]+)#",$ligne,$re)){continue;}
            $Total=$re[1];
            $Used=$re[2];
            return array(intval($Total),intval($Used));
        }
        return array(0,0);
    }


    public function TOTAL_MEMORY_MB_USED(){
        $array=$this->TOTAL_MEMORY_KB_WORK();
        $Used=$array[1]/1024;
        return round($Used);

    }
    public function TOTAL_MEMORY_MB_FREE(){
        $array=$this->TOTAL_MEMORY_KB_WORK();
        $Total=$array[0];
        $Used=$array[1];
        $free=$Total-$Used;
        return round($free/1024);

    }


    public function DIRECTORY_USEPERCENT($dir){

        if(is_link($dir)){$dir=readlink($dir);}
        if(!is_dir($dir)){
            $directory_tmp=dirname($dir);
            if(is_link($directory_tmp)){$directory_tmp=readlink($directory_tmp);}
            if(is_dir($directory_tmp)){$dir=$directory_tmp;}
        }

        $df=$this->find_program("df");
        exec("$df \"$dir\" 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(!preg_match("#^\/.*?[0-9]+\s+[0-9]+\s+[0-9]+\s+[0-9]+\s+([0-9\.,]+)%#", $ligne,$re)){
                if(!preg_match("#^\/.*?[0-9]+\s+[0-9]+\s+[0-9]+\s+([0-9\.,]+)%\s+#", $ligne,$re)){
                    if($GLOBALS["VERBOSE"]){echo "$dir :No match $ligne\n";}
                    continue;
                }else{
                    return round($re[1]);
                }
            }

            return round($re[1]);

        }

        return 0;}

    function TMPFS_USEPERCENT($dir){
        if(is_link($dir)){$dir=readlink($dir);}
        if(!is_dir($dir)){
            $directory_tmp=dirname($dir);
            if(is_link($directory_tmp)){$directory_tmp=readlink($directory_tmp);}
            if(is_dir($directory_tmp)){$dir=$directory_tmp;}
        }

        $df=$this->find_program("df");
        exec("$df \"$dir\" 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(!preg_match("#^tmpfs\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9,\.])+%\s+#", $ligne,$re)){
                if($GLOBALS["VERBOSE"]){echo "$dir :No match $ligne\n";}
                continue;
            }

            return round($re[4]);

        }

    }

    function TMPFS_CURRENTSIZE($dir){

        $df=$this->find_program("df");
        exec("$df \"$dir\" 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(!preg_match("#^(tmpfs|shm)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9,\.])+%\s+#", $ligne,$re)){
                if($GLOBALS["VERBOSE"]){echo "$dir :No match $ligne\n";}
                continue;
            }

            return round($re[2]);

        }

        return 0;}


    function DIRECTORY_MountedOnDirAndDismount($Directory){

        $c=0;
        $umount=$this->find_program("umount");
        $DirectoryEx=str_replace("/", '\/', $Directory);
        $DirectoryEx=str_replace(".", "\.", $DirectoryEx);

        $f=explode("\n",@file_get_contents("/proc/mounts"));
        foreach ($f as $line){
            if(preg_match("#.+?\s+$DirectoryEx\/(.+?)\s+#", $line,$re)){
                shell_exec("$umount -l $Directory/{$re[1]}");
                $c++;
            }
        }

        return $c;

    }

    public function DIRECTORY_FREEM($dir){

        if(is_link($dir)){$dir=readlink($dir);}
        if(!is_dir($dir)){
            $directory_tmp=dirname($dir);
            if(is_link($directory_tmp)){$directory_tmp=readlink($directory_tmp);}
            if(is_dir($directory_tmp)){$dir=$directory_tmp;}
        }

        $df=$this->find_program("df");
        exec("$df -B MB $dir",$results);
        foreach ($results as $num=>$ligne){
            if(preg_match("#^\/.*?[0-9]+MB\s+[0-9]+MB\s+([0-9]+)MB\s+#", $ligne,$re)){return $re[1];}

        }

    }


    public function DF_SATUS_K($dir){
        $df=$this->find_program("df");
        //1K-blocks      Used Available Use%
        exec("$df -B K $dir 2>&1",$results);
        $pattern="#^\/.*?([0-9]+)K\s+([0-9]+)K\s+([0-9]+)K\s+([0-9\.]+)%#";
        if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."] $df -B K $dir 2>&1 = ".count($results)." rows\n";}
        foreach ($results as $num=>$ligne){

            if(preg_match("#^Filesystem#", $ligne)){continue;}

            if(!preg_match("$pattern", $ligne,$re)){
                if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."] $ligne -> No match $pattern\n";}
                continue;}
            return array(
                "SIZE"=>$re[1],
                "USED"=>$re[2],
                "AIVA"=>$re[3],
                "POURC"=>$re[4]
            );
        }

    }


    public function process_number_me($argv=array()):int{
        $cmdlines=null;$t=array();
        $myScript=null;
        if(count($argv)==0){return 0;}
        foreach ($argv as $num=>$ligne){
            if($num==0){
                $myScript=basename($ligne);
                continue;
            }
            if($ligne=="--verbose"){continue;}
            if($ligne=="--force"){continue;}
            if($ligne=="--simulate"){continue;}
            if($ligne=="--notime"){continue;}
            if(preg_match("#schedule-id#", $ligne)){continue;}
            $t[]=$ligne;
        }
        if($myScript==null){return 0;}
        if(count($t)>0){$cmdlines=".*?".@implode( ".*?",$t);}
        $miPid=getmypid();
        $pgrep=$this->find_program("pgrep");

        $f=array();
        exec("$pgrep -l -f \"$myScript$cmdlines\" 2>&1",$results);
        foreach ($results as $ligne){
            if(preg_match("#pgrep#", $ligne)){continue;}
            if(!preg_match("#^([0-9]+)\s+(.+)#", $ligne,$re)){continue;}
            if(preg_match("#(^|\s+|\/)sh\s+-c#", $ligne)){continue;}
            $pid=$re[1];
            if($pid==$miPid){if($GLOBALS["VERBOSE"]){echo "[$miPid]: $pid==$miPid OK\n";}continue;}
            $f[]=$pid;
        }

        return count($f);

    }

    private function default_locale():string{
        $f=explode("\n",@file_get_contents("/etc/default/locale"));
        foreach ($f as $line){
                if(preg_match("#LANG=\"(.+?)\"#",$line,$re)){
                    return trim($re[1]);
                }
        }
        $f=explode("\n",@file_get_contents("/etc/profile.d/lang.sh"));
        foreach ($f as $line){
            if(preg_match("#\{LANG:=(.+?)\}#",$line,$re)){
                return trim($re[1]);
            }
        }
        return "";

    }

    public function shell_php($command,$out=false):bool{
        $php=$this->LOCATE_PHP5_BIN();
        $single=$command;
        $php_script=ARTICA_ROOT."/$command";

        if(preg_match("#(.*?)\.php\s+#",$single,$re)){
            $single=$re[1].".php";
            $single_path=ARTICA_ROOT."/$single";
            if(!is_file($single_path)){
                $this->ToSyslog("$single_path no such binary, aborting",false,"framework");
                return false;
            }
        }
        return $this->shell_command("$php $php_script",$out);

    }

    public function shell_command($command,$out=false):bool{
        $TMPDIR=$this->TEMP_DIR();
        $name=md5($command);
        $tfile="$TMPDIR/$name.sh";
        $sATrue = null;
        if(!is_dir($TMPDIR)){@mkdir($TMPDIR,0755,true);}
        $LANG=trim($this->default_locale());

        $f=array();
        $f[]="#!/bin/sh";
        $f[]="export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin";
        $f[]=". /lib/lsb/init-functions";
        $f[]="export TERM=xterm-256color";
        $f[]="export LC_ALL=C";
        $f[]="export LOGNAME=root";
        $f[]="export HOME=/root";
        $f[]="export SHLVL=1";
        $f[]="export MAIL=/var/mail/root";
        $f[]="export SSH_TTY=/dev/pts/1";
        $f[]="export DEBIAN_FRONTEND=noninteractive";


        if($LANG<>null){
            $LC=array("LC_ADDRESS","LC_NAME","LC_MONETARY","LC_PAPER","LC_IDENTIFICATION","LC_TELEPHONE","LC_MEASUREMENT","LC_TIME","LC_NUMERIC");
            foreach ($LC as $key){
                $f[]="export $key=\"$LANG\"";
            }

        }

        if($GLOBALS["VERBOSE"]){
            echo "Running $tfile\n";
            $f[]="printenv";
            $f[]="echo \"---------------------------------------------\"";
        }
        $sTrue=true;
        if(strpos(" $command","/nohup")>0){$sTrue=false;}
        if($sTrue){$sATrue=" || true";}
        $f[]="$command$sATrue";
        if(!$GLOBALS["VERBOSE"]){$f[]="rm -f $tfile ||true\n";}
        if($GLOBALS["VERBOSE"]){echo "Please, investigate on $tfile\n";}
        @file_put_contents($tfile,@implode("\n",$f));
        @chmod($tfile,0755);

        if($out){
            $nohup=$this->find_program("nohup");
            system("$nohup $tfile >/dev/null 2>&1 &");
            return true;
        }
        system($tfile);
        return true;
    }


    public function process_exists($pid,$filename=null):bool{
        $GLOBALS["PROCES_EXISTS_DEBUG"]=array();
        $pid=trim((string) $pid);
        $pid=str_replace("\r", "", $pid);
        $pid=str_replace("\n", "", $pid);
        $filename=trim((string) $filename);
        $pid=intval($pid);

        if($pid==0){return false;}


        if($pid<5){
            if($GLOBALS["VERBOSE"]){echo "$pid < 5 (strange ? aborting)...\n";}
            $GLOBALS["PROCES_EXISTS_DEBUG"][]="$pid < 5 (strange ? aborting)...";
            return false;
        }

        if(!is_dir("/proc/$pid")){
            if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) /proc/$pid doesn't exists.\n";}
            return false;
        }


        if(is_file("/proc/$pid/exe")){
            $cmdline=var_export(@file_get_contents("/proc/$pid/cmdline"),true);
            $GLOBALS["PROCES_EXISTS_DEBUG"][]="PROC: $pid exec:OK";
            if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) /proc/$pid/status exists cmdline=`$cmdline` Line:".__LINE__."\n";}
            if(preg_match("#'-bash'#",$cmdline)){
                if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) --> $cmdline -> STOPPED Line:".__LINE__."\n";}
                return false;
            }


            if(is_file("/proc/$pid/status")){$this->proc_status=@file_get_contents("/proc/$pid/status");}

            $status=$this->PROCESS_STATUS($pid);
            if($filename<>null){
                if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) filename: `$filename`, PROCESS_STATUS: '{$status[0]}' Line:".__LINE__."\n";}
            }
            if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) PROCESS_STATUS: '{$status[0]}' Line:".__LINE__."\n";}
            if($status[0]=="Z"){

                if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) -> Z = SOMBIE ::: Line:".__LINE__."\n";}
                return false;}
            if($filename<>null){
                if($cmdline==null){
                    if(is_file("/proc/$pid/cmdline")){
                        $cmdline=var_export(@file_get_contents("/proc/$pid/cmdline"),true);
                    }
                }
                $filename=str_replace(".", "\.", $filename);
                $filename=str_replace("/", "\/", $filename);
                if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) cmdline: $cmdline Line:".__LINE__."\n";}
                if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) preg_match #$filename# in \"$cmdline\" Line:".__LINE__."\n";}
                if(preg_match("#$filename#",$cmdline)){if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) preg_match #$filename# SUCCESS Line:".__LINE__."\n";}return true;}else{if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) preg_match #$filename# FAILED Line:".__LINE__."\n";}return false;}
            }

            if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) -->RUNNING Line:".__LINE__."\n";}
            return true;
        }else{
            $GLOBALS["PROCES_EXISTS_DEBUG"][]="missing /proc/$pid/exe";
            if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) /proc/$pid/exe no such file Line:".__LINE__."\n";}
        }

        if(is_file("/proc/$pid/cmdline")){
            if(is_file("/proc/$pid/status")){$this->proc_status=@file_get_contents("/proc/$pid/status");}
            $status=$this->PROCESS_STATUS($pid);
            if(isset($status[0])) {
                if ($status[0] == "Z") {
                    $GLOBALS["PROCES_EXISTS_DEBUG"][] = "Marked as ZOMBIE";
                    return false;
                }
            }

            if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) filename: $filename Line:".__LINE__."\n";}
            $cmdline=@file_get_contents("/proc/$pid/cmdline");
            if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) cmdline: `$cmdline` Line:".__LINE__."\n";}

            if(preg_match("#-bash$#",$cmdline)){
                if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) --> $cmdline -> STOPPED Line:".__LINE__."\n";}
                $GLOBALS["PROCES_EXISTS_DEBUG"][]="Macthes as -bash";
                return false;
            }
            $filename=str_replace("/", '\/', $filename);
            if(preg_match("#$filename#i",$cmdline)){
                if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) -->matches $filename -> RUNNING Line:".__LINE__."\n";}
                return true;
            }

            $GLOBALS["PROCES_EXISTS_DEBUG"][]="$filename no match";
            if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) --> NO matches $filename -> STOPPED Line:".__LINE__."\n";}
            return false;


        }else{
            $GLOBALS["PROCES_EXISTS_DEBUG"][]="/proc/$pid/cmdline no such file ".__LINE__;
        }

        $GLOBALS["PROCES_EXISTS_DEBUG"][]="Return false: ".__LINE__;
        return false;
    }
    public function file_time_set($filename){
        if(!is_dir("/etc/artica-postfix/pids")){@mkdir("/etc/artica-postfix/pids",0755,true);}
        @unlink("/etc/artica-postfix/pids/$filename");
        @file_put_contents("/etc/artica-postfix/pids/$filename", time());
    }

    public function file_time_get($filename):int{
        return $this->file_time_min("/etc/artica-postfix/pids/$filename");
    }

    public function dir_time_min($path){
        if(!is_dir($path)){return 1;}
        $data1 = filemtime($path."/.");
        $data2 = time();
        $difference = ($data2 - $data1);
        $results=intval(round($difference/60));
        if($results<0){$results=1;}
        return $results;
    }


    public function time_min($firstTime){
        if(!is_numeric($firstTime)){
            $trace=debug_backtrace();
            foreach ($trace as $traces){
                $file=basename($traces["file"]);
                $function=$traces["function"];
                $line=$traces["line"];
                error_log("From $file $function L.$line firstime [$firstTime] is not numeric");

            }
            return 0;
        }
        $data1 = $firstTime;
        $data2 = time();
        $difference = ($data2 - $data1);
        $results=intval(round($difference/60));
        if($results<0){$results=1;}
        return $results;

    }

    public function file_head_n($filename,$maxlines){
        if(!is_file($filename)){return null;}
        $file = fopen( $filename, "r" );
        $index=0;
        $t=array();
        while ((( $line = fgets( $file )) !== false) && ( $index++ < $maxlines+1 )) {
            $t[]=$line;
        }
        fclose( $file );

        return @implode("\n",$t);

    }

    public function file_tail_n($filename, $lines, $revers = false):string{
        if(!is_file($filename)){return "";}
        $offset = -1;
        $c = '';
        $read = array();
        $i = 0;
        $fp = @fopen($filename, "r");
        while( $lines && fseek($fp, $offset, SEEK_END) >= 0 ) {
            $c = fgetc($fp);
            if($c == "\n" || $c == "\r"){
                $lines--;
                if( $revers ){
                    $read[$i] = strrev($read[$i]);
                    $i++;
                }
            }
            if( $revers ) $read[$i] .= $c;
            else $read .= $c;
            $offset--;
        }
        fclose ($fp);
        if( $revers ){
            if($read[$i] == "\n" || $read[$i] == "\r")
                array_pop($read);
            else $read[$i] = strrev($read[$i]);
            return implode('',$read);
        }
        return strrev(rtrim($read,"\n\r"));
    }


    public function file_time_min($path):int{
        if(!is_file($path)){
            if(!is_dir($path)){return 10000;}
        }

        $data1 = filemtime($path);
        $data2 = time();
        $difference = ($data2 - $data1);
        $results=intval(round($difference/60));
        if($results<0){$results=1;}
        return $results;
    }

    public function file_time_sec($path):int{
        if(!is_dir($path)){
            if(!is_file($path)){return 100000;}
        }
        $last_modified = filemtime($path);
        $data1 = $last_modified;
        $data2 = time();
        return ($data2 - $data1);
    }

    public function alt_stat2($file) {

        clearstatcache();
        $ss=@stat($file);
        if(!$ss) return false; //Couldnt stat file

        $ts=array(
            0140000=>'ssocket',
            0120000=>'llink',
            0100000=>'-file',
            0060000=>'bblock',
            0040000=>'ddir',
            0020000=>'cchar',
            0010000=>'pfifo'
        );

        $p=$ss['mode'];
        $t=decoct($ss['mode'] & 0170000); // File Encoding Bit


        $s=array(
            'perms'=>array(
                'umask'=>sprintf("%04o",@umask()),
                'octal1'=>sprintf("%o", ($ss['mode'] & 000777)),
                'octal2'=>sprintf("0%o", 0777 & $p),
                'decimal'=>sprintf("%04o", $p),
                'fileperms'=>@fileperms($file),
                'mode1'=>$p,
                'mode2'=>$ss['mode']),

            'owner'=>array(
                'fileowner'=>$ss['uid'],
                'filegroup'=>$ss['gid'],
                'owner'=>
                    (function_exists('posix_getpwuid'))?
                        @posix_getpwuid($ss['uid']):'',
                'group'=>
                    (function_exists('posix_getgrgid'))?
                        @posix_getgrgid($ss['gid']):''
            ),

            'file'=>array(
                'filename'=>$file,
                'realpath'=>(@realpath($file) != $file) ? @realpath($file) : '',
                'dirname'=>@dirname($file),
                'basename'=>@basename($file)
            ),

            'filetype'=>array(
                'type'=>substr($ts[octdec($t)],1),
                'type_octal'=>sprintf("%07o", octdec($t)),
                'is_file'=>@is_file($file),
                'is_dir'=>@is_dir($file),
                'is_link'=>@is_link($file),
                'is_readable'=> @is_readable($file),
                'is_writable'=> @is_writable($file)
            ),

            'device'=>array(
                'device'=>$ss['dev'], //Device
                'device_number'=>$ss['rdev'], //Device number, if device.
                'inode'=>$ss['ino'], //File serial number
                'link_count'=>$ss['nlink'], //link count
                'link_to'=>($ss['type']=='link') ? @readlink($file) : ''
            ),

            'size'=>array(
                'size'=>$ss['size'], //Size of file, in bytes.
                'blocks'=>$ss['blocks'], //Number 512-byte blocks allocated
                'block_size'=> $ss['blksize'] //Optimal block size for I/O.
            ),

            'time'=>array(
                'mtime'=>$ss['mtime'], //Time of last modification
                'atime'=>$ss['atime'], //Time of last access.
                'ctime'=>$ss['ctime'], //Time of last status change
                'accessed'=>@date('Y M D H:i:s',$ss['atime']),
                'modified'=>@date('Y M D H:i:s',$ss['mtime']),
                'created'=>@date('Y M D H:i:s',$ss['ctime'])
            ),
        );

        clearstatcache();
        return $s;
    }

    PUBLIC function LOCATE_SENDMAIL_PATH(){
        $path=$this->find_program("sendmail");
        if(is_file($path)){return $path;}
        if(is_file("/usr/lib/sendmail")){return "/usr/lib/sendmail";}

        return null;}

    public function Ufdbguard_remote_srvc_bool(){
        $UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
        if($UseRemoteUfdbguardService==1){return true;}
        return false;
    }


    public function LOCATE_APACHE_BIN_PATH(){
        if(isset($GLOBALS[__FUNCTION__])){
            if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}
        }
        $f=$this->find_program("apache2");
        if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}
        $f=$this->find_program("apache");
        if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}

        $f=$this->find_program("httpd2");
        if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}

        $f=$this->find_program("httpd");
        if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}

        if(is_file('/usr/local/apache-groupware/bin/apache-groupware')){return '/usr/local/apache-groupware/bin/apache-groupware';}
    }

    public function  SQUID_INSTALLED():bool{
        if(is_file("/usr/sbin/squid")){return true;}
        return false;
    }

    public function LOCATE_SQUID_BIN():string{
        if(is_file("/usr/sbin/squid")){return '/usr/sbin/squid';}
        return "";
    }

    public function framework_progress($prc,$text,$progress_prc,$noOut=false):bool{

        if(is_numeric($text)){
            $old_text=$prc;
            $prc=$text;
            $text=$old_text;
        }

        $maindir="/usr/share/artica-postfix/ressources/logs/web";
        $date=date("Y-m-d H:i:s");
        $progressfile="$maindir/$progress_prc";
        if(!$noOut){ echo "$date: [$prc%] $text\n";}
        $array["POURC"]=$prc;
        $array["TEXT"]=$text;
        @file_put_contents($progressfile, serialize($array));
        @chmod($progressfile,0755);
        return true;
    }

    public function DIRECTORY_SCAN($directory):array{
        $entries_array[]=$directory;
        return $this->recursive_directories($directory,$entries_array);

    }

    private function recursive_directories($directory, $entries_array = array()):array {
        if(!is_dir($directory)) {return array();}
        if(is_dir($directory)) {
            $handle = opendir($directory);
            while(FALSE !== ($entry = readdir($handle))) {
                if($entry == '.' || $entry == '..') {
                    continue;
                }
                $Entry = $directory . "/" . $entry;
                if(is_dir($Entry)) {
                    $entries_array[] = $Entry;
                    $entries_array = $this->recursive_directories($Entry, $entries_array);
                }
            }
            closedir($handle);
        }
        return $entries_array;
    }

    public function nginx_params(){
        $nginx_params_time=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_params_time");
        $nginx_params=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_params"));
        if($nginx_params_time>0){
            $time_exec=$this->time_min($nginx_params_time);
            if($time_exec<120){
                return $nginx_params;
            }
        }



        $nginx=$this->find_program("nginx");
        exec("$nginx -V 2>&1",$results);
        foreach ($results as $line){
            if(!preg_match("#configure arguments:(.+)#",$line,$re)){continue;}
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nginx_params",base64_encode($re[1]));
            return $re[1];
        }

        return "";
    }

    public function framework_exec($cmdline,$ouptutfile=null):bool{
        $maindir=PROGRESS_DIR;
        $cmdline=trim($cmdline);
        if($ouptutfile==null){$ouptutfile=time().".log";}
        $nohup=null;
        $BINARY=false;
        $php5=$this->LOCATE_PHP5_BIN();
        $tempfile=$this->FILE_TEMP().".sh";
        $h[]="#!/bin/sh";
        $h[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";

        if(strpos($cmdline,"&")>0){
            $nohup=$this->find_program("nohup")." ";
            $cmdline=trim(str_replace("&","",$cmdline));
        }
        
        $cmd="$nohup$php5 /usr/share/artica-postfix/$cmdline >$maindir/$ouptutfile 2>&1 &";
        if(preg_match("#\/usr\/.*?\/bin\/#",$cmdline)){$BINARY=true;}
        if(preg_match("#^\/usr\/(sbin|bin)\/#",$cmdline)){$BINARY=true;}
        if(preg_match("#^\/(sbin|bin)\/#",$cmdline)){$BINARY=true;}
        if(preg_match("#^\/etc\/init\.d\/#",$cmdline)){$BINARY=true;}
        if($BINARY){$cmd=$cmdline;}
        $this->go_exec_syslog($cmd." (framework_exec)");
        $h[]=$cmd;
        $h[]="/usr/bin/rm $tempfile";
        @file_put_contents($tempfile,@implode("\n",$h));
        writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
        @chmod($tempfile,0755);
        return $this->go_exec($tempfile);

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
        $Unser=unserialize($Decoded);
        if(!$Unser){
            return array();
        }
        return $Unser;
    }

    public function framework_search_syslog($encoded,$srcfile,$target,$patternf){
            $grep   = $this->find_program("grep");
            $tail   = $this->find_program("tail");
            $bif    = "--binary-files=text";
            $mainf  = PROGRESS_DIR."/$target";
            $pf     = PROGRESS_DIR."/$patternf";
            $TERM   = null;
            $MAIN=$this->unserializeb64($encoded);

            foreach ($MAIN as $val=>$key){
                echo "$val == > $key\n";
                $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
                $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);

            }

            if(!isset($MAIN["TERM"])){$MAIN["TERM"]=null;}
            if(!isset($MAIN["DST"])){$MAIN["DST"]=null;}
            if(!isset($MAIN["IN"])){$MAIN["IN"]=null;}
            if(!isset($MAIN["PID"])){$MAIN["PID"]=null;}
            if(!isset($MAIN["SRC"])){$MAIN["SRC"]=null;}
            if(!isset($MAIN["MAX"])){$MAIN["MAX"]=250;}
            $max=intval($MAIN["MAX"]);
            $date=$MAIN["DATE"];
            if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}
            if($max==0){$max=250;}

            $search="$date.*?$TERM";
            $search=str_replace(".*?.*?",".*?",$search);
            $cmd="$grep $bif -i -E '$search' $srcfile |$tail -n $max >$mainf 2>&1";
            writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
            @file_put_contents($pf, $search);
            shell_exec($cmd);


    }

    public function framework_execute($cmdline,$progress_prc=null,$progress_log=null):bool{
        $maindir=PROGRESS_DIR;
        if($progress_prc==null){$progress_prc=md5($cmdline)."pr";}
        if($progress_log==null){$progress_log=md5($cmdline)."log";}
        $function=null;
        $file=null;
        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();
            if(isset($trace[0])){ $function=$trace[0]["function"]; $line=$trace[0]["line"];$file=$trace[0]["file"]; }
            if(isset($trace[1])){ $function=$trace[1]["function"]; $line=$trace[1]["line"]; }
        }
        writelogs_framework($cmdline ,$function,__FILE__,$file);

        if(!is_dir($maindir)){@mkdir($maindir,0755,true);}

        $ARRAY["PROGRESS_FILE"]="$maindir/$progress_prc";
        $ARRAY["LOG_FILE"]="$maindir/$progress_log";

        if(strpos(" $progress_log","/")>0){
            $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/$progress_log";
        }
        if(strpos(" $progress_prc","/")>0){
            $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/$progress_prc";
        }
        if(is_file($ARRAY["PROGRESS_FILE"])) {
            @unlink($ARRAY["PROGRESS_FILE"]);
        }
        if(is_file($ARRAY["LOG_FILE"])) {
            @unlink($ARRAY["LOG_FILE"]);
        }

        @touch($ARRAY["PROGRESS_FILE"]);
        @touch($ARRAY["LOG_FILE"]);

        @chmod($ARRAY["PROGRESS_FILE"],0777);
        @chmod($ARRAY["LOG_FILE"],0777);

        $python=$this->find_program("python");
        $php5=$this->LOCATE_PHP5_BIN();
        $tempfile=$this->FILE_TEMP().".sh";
        $h[]="#!/bin/sh";
        $h[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";



        if(strpos(" $cmdline","bin/")>0){
            if(preg_match("#^(.+?)\s+#",$cmdline,$re)){
                @chmod("/usr/share/artica-postfix/{$re[1]}",0755);
            }
            $cmd="/usr/share/artica-postfix/$cmdline >{$ARRAY["LOG_FILE"]} 2>&1";
            writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
            $this->go_exec_syslog($cmd." (framework_execute)");
            $h[]="$cmd";
            $h[]="/usr/bin/rm -f $tempfile";
            $h[]="";
            @file_put_contents($tempfile,@implode("\n",$h));
            @chmod($tempfile,0755);
            $this->go_exec($tempfile);
            return true;
        }

        if(preg_match("#\.py($|\s+)#",$cmdline)){$php5=$python;$cmdline="bin/$cmdline";}
        $cmd="$php5 /usr/share/artica-postfix/$cmdline >{$ARRAY["LOG_FILE"]} 2>&1";
        writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
        $h[]="$cmd";
        $h[]="/usr/bin/rm -f $tempfile";
        $h[]="";
        @file_put_contents($tempfile,@implode("\n",$h));
        @chmod($tempfile,0755);
        $this->go_exec($tempfile);
        return true;
    }


    public function ClamUser():string{
        $ClamUser="clamav";
        $squidbin=$this->LOCATE_SQUID_BIN();

        if(is_file($squidbin)){
            $SQUIDEnable=intval($this->GET_INFO("SQUIDEnable"));
            if($SQUIDEnable==1){return "squid";}
        }

        $CicapEnabled=intval($this->GET_INFO("CicapEnabled"));
        if($CicapEnabled==1){return "squid";}


        $postconf=$this->find_program("postconf");
        if(is_file($postconf)){
            $EnablePostfix=$this->GET_INFO("EnablePostfix");
            if($EnablePostfix==1) {
                if (!is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")) {
                    $ClamUser = "postfix";
                }
            }
        }
        return $ClamUser;
    }


    public function SQUID_PID(){


        $squidbin=$this->LOCATE_SQUID_BIN();
        $pidfile=$this->LOCATE_SQUID_PID();
        $pid=$this->get_pid_from_file($pidfile);
        if($GLOBALS["VERBOSE"]){echo "PID FILE: $pidfile - > $pid\n";}

        if(!$this->process_exists($pid)){
            if($GLOBALS["VERBOSE"]){echo "PID: $pid - > NOT RUNNING !! -> PIDOF $squidbin\n";}
            $pid=$this->PIDOF($squidbin);
            if($GLOBALS["VERBOSE"]){echo "PIDOF: $pid\n";}
        }else{
            return $pid;
        }

        if($GLOBALS["VERBOSE"]){
            if(!$this->process_exists($pid)){
                echo " **** ***** ***** ***** $pid] NOT RUNNING !! \n";
            }
        }
        return $pid;


    }





    public function readlastline($filename,$rows_number=1) {
        $tail=$this->find_program("tail");
        exec("$tail -n $rows_number \"$filename\" 2>&1",$RESULTS);
        return $RESULTS;


    }
    public function readFirstline($filename,$rows_number=1) {
        $head=$this->find_program("head");
        exec("$head -n $rows_number \"$filename\" 2>&1",$RESULTS);
        if($GLOBALS["VERBOSE"]){echo "$head -n $rows_number \"$filename\" 2>&1 == ".count($RESULTS)." lines\n";}
        return $RESULTS;


    }
    private function APACHE_DEFAULT_PIDLOG(){
        if(isset($GLOBALS["APACHE_DEFAULT_PIDLOG"])){return $GLOBALS["APACHE_DEFAULT_PIDLOG"];}
        $bin=$this->LOCATE_APACHE_BIN_PATH();
        if(strlen($bin)<3){
            if($GLOBALS["VERBOSE"]){echo "\n\nWARNING !!! LOCATE_APACHE_BIN_PATH return a non value !!\n";}
            return;
        }

        exec("$bin -V 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(preg_match('#DEFAULT_PIDLOG="(.+?)"#',$ligne,$re)){$GLOBALS["APACHE_DEFAULT_PIDLOG"]=$re[1];return $GLOBALS["APACHE_DEFAULT_PIDLOG"];}
        }

    }

    public function LOCATE_APACHE_CONF_PATH(){
        if(isset($GLOBALS["LOCATE_APACHE_CONF_PATH"])){return $GLOBALS["LOCATE_APACHE_CONF_PATH"];}
        $GLOBALS["LOCATE_APACHE_CONF_PATH"]=$this->_LOCATE_APACHE_CONF_PATH();
        return $GLOBALS["LOCATE_APACHE_CONF_PATH"];
    }

    private function _LOCATE_APACHE_CONF_PATH():string{
        $bin=$this->LOCATE_APACHE_BIN_PATH();
        if(is_null($bin)){return "";}
        $r=array();
        if(strlen($bin)<3){
            if($GLOBALS["VERBOSE"]){echo "\n\nWARNING !!! LOCATE_APACHE_BIN_PATH return a non value !!\n";}
            return "";
        }

        $r["SERVER_CONFIG_FILE"]=null;
        $r["HTTPD_ROOT"]=null;
        exec("$bin -V 2>&1",$results);
        foreach ($results as $ligne){
            if(preg_match('#HTTPD_ROOT="(.+?)"#',$ligne,$re)){$r["HTTPD_ROOT"]=$re[1];}
            if(preg_match('#SERVER_CONFIG_FILE="(.+?)"#',$ligne,$re)){$r["SERVER_CONFIG_FILE"]=$re[1];}
        }

        chdir("/root");
        if(is_file($r["SERVER_CONFIG_FILE"])){
            $GLOBALS[__FUNCTION__]=$r["SERVER_CONFIG_FILE"];
            return $GLOBALS[__FUNCTION__];
        }

        if($GLOBALS["VERBOSE"]){echo "UNIX/LOCATE_APACHE_CONF_PATH -> \"{$r["HTTPD_ROOT"]}/{$r["SERVER_CONFIG_FILE"]}\"\n";}

        if(file_exists("{$r["HTTPD_ROOT"]}/{$r["SERVER_CONFIG_FILE"]}")){
            if(isset($r["HTTPD_ROOT"])){$GLOBALS[__FUNCTION__]="{$r["HTTPD_ROOT"]}/{$r["SERVER_CONFIG_FILE"]}";}
            if(isset($GLOBALS[__FUNCTION__])){$GLOBALS[__FUNCTION__]=str_replace("//","/",$GLOBALS[__FUNCTION__]);}
        }
        if(isset($GLOBALS[__FUNCTION__])){
            if($GLOBALS[__FUNCTION__]=="conf/httpd.conf"){
                $GLOBALS[__FUNCTION__]="/etc/httpd/conf/httpd.conf";
            }
            return $GLOBALS[__FUNCTION__];
        }
        return "";

    }

    public function LOCATE_APACHE_PID_PATH():string{
        if(isset($GLOBALS[__FUNCTION__])){if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}}
        $bin_path=$this->LOCATE_APACHE_BIN_PATH();
        if(is_null($bin_path)){return "";}
        $DEFAULT_PIDLOG=null;
        exec("$bin_path -V 2>&1",$results);
        foreach ($results as $ligne){
            if(preg_match('#HTTPD_ROOT="(.+?)"#',$ligne,$re)){
                $httpd_root=$re[1];
            }

            if(preg_match('#DEFAULT_PIDLOG="(.+?)"#',$ligne,$re)){$DEFAULT_PIDLOG=$re[1];}
        }

        if(!is_file($DEFAULT_PIDLOG)){
            if(is_file("$httpd_root/$DEFAULT_PIDLOG")){$DEFAULT_PIDLOG="$httpd_root/$DEFAULT_PIDLOG";}
        }
        $GLOBALS[__FUNCTION__]=$DEFAULT_PIDLOG;
        return strval($GLOBALS[__FUNCTION__]);

    }

    public function LOCATE_APACHE_VERSION(){
        if(isset($GLOBALS[__FUNCTION__])){
            if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}
        }

        $bin_path=$this->LOCATE_APACHE_BIN_PATH();
        $md5=md5_file($bin_path);

        if(is_file("/etc/artica-postfix/apache2_version")){
            $array=unserialize(@file_get_contents("/etc/artica-postfix/apache2_version"));
            if(!is_array($array)){$array=array();}
            if($array["MD5"]==$md5){
                return $array["VERSION"];
            }
        }


        if($GLOBALS["VERBOSE"]){echo "LOCATE_APACHE_VERSION : \"$bin_path\"\n";}
        exec("$bin_path -v 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(preg_match('#^Server version:.+?([0-9\.]+)#',$ligne,$re)){
                $GLOBALS[__FUNCTION__]=$re[1];
                $array["MD5"]=$md5;
                $array["VERSION"]=$GLOBALS[__FUNCTION__];
                @file_put_contents("/etc/artica-postfix/apache2_version", serialize($array));
                return $GLOBALS[__FUNCTION__];
            }
            if($GLOBALS["VERBOSE"]){echo "NO MATCH:: \"^Server version:.+?([0-9\.]+)\" -> \"$ligne\"\n";}
        }

    }


    public function IMAPD_GET($key){
        if(!isset($GLOBALS["IMAPD_GET"])){
            $GLOBALS["IMAPD_GET"]=@file_get_contents("/etc/imapd.conf");
        }
        $datas=explode("\n",$GLOBALS["IMAPD_GET"]);
        foreach ($datas as $num=>$ligne){
            if(preg_match("#^$key:(.+)#",$ligne,$re)){
                return trim($re[1]);
            }
        }
    }

    public function ServicesCMDArray(){
        $f[]="ldap";
        $f[]="saslauthd";
        $f[]="imap";
        $f[]="kav6";
        $f[]="squid";
        $f[]="dansgardian";
        $f[]="boa";
        $f[]="ftp";
        $f[]="mysql";
        $f[]="apache";
        $f[]="spamd";
        $f[]="clamd";
        $f[]="freshclam";
        $f[]="mgreylist";
        $f[]="daemon";
        $f[]="clammilter";
        $f[]="postfix";
        $f[]="dnsmasq";
        $f[]="stunnel";
        $f[]="postfix";
        $f[]="mailgraph";
        $f[]="mimedefang";
        $f[]="roundcube";
        $f[]="kav4samba";
        $f[]="bind9";
        $f[]="yorel";
        $f[]="p3scan";
        $f[]="syslogng";
        $f[]="mailarchive";
        $f[]="bogom";
        $f[]="collectd";
        $f[]="mysql";
        $f[]="fetchmail";
        $f[]="mailspy";
        $f[]="amavis";
        $f[]="retranslator";
        $f[]="spfmilter";
        $f[]="dotclear";
        $f[]="jcheckmail";
        $f[]="mailman";
        $f[]="kas3";
        $f[]="dhcp";
        $f[]="cicap";
        $f[]="openvpn";

        $f[]="dansguardian-tail";
        $f[]="apache-groupware";
        $f[]="cups";
        $f[]="dstat";
        $f[]="dstat-top-mem";
        $f[]="dstat-top-cpu";
        $f[]="rsync";
        $f[]="policydw";
        $f[]="autofs";
        $f[]="mysql-cluster";
        $f[]="assp";
        $f[]="pdns";
        $f[]="gluster";
        $f[]="gluster-cli";
        $f[]="zabbix";
        $f[]="smartd";
        return $f;
    }


    public function monit_array(){
        $monit=new monit_unix();
        return $monit->all_status();


    }
    function check_rsync_error($results=array()):bool{
        $GLOBALS["check_rsync_error"]=null;
        if(!is_array($results)){return false;}
        foreach ($results as $line){
            $line=trim($line);
            if($line==null){continue;}

            if(preg_match("#rsync error#",$line)){
                if(preg_match("#some files\/attrs were not transferred#",$line)){continue;}
                if(preg_match("#some files vanished before they could be transferred#",$line)){continue;}
                $GLOBALS["check_rsync_error"]="ERROR found: $line";
                if(function_exists("_out")) {_out("Copy ERROR: $line"); }
                return true;

            }

            if(preg_match("#rsync: mkstemp.*?failed:#",$line)){
                $GLOBALS["check_rsync_error"]=" ERROR found: $line";
                if(function_exists("_out")) {_out("Copy ERROR: $line"); }
                return true;
            }

        }

        return false;
    }


    public function IsProtectedDirectory($folder,$nopreg=false){
        if($folder==null){return true;}
        if($folder=="/"){return true;}
        $l["/cdrom"]=true;
        $l["/sys"]=true;
        $l["/tmp"]=true;
        $l["/initrd"]=true;
        $l["/dev"]=true;
        $l["/etc"]=true;
        $l["/boot"]=true;
        $l["/var/lib"]=true;
        $l["/sbin"]=true;
        $l["/lib"]=true;
        $l["/bin"]=true;
        $l["/usr/libexec"]=true;
        $l["/usr/sbin"]=true;
        $l["/usr/bin"]=true;
        $l["/usr/include"]=true;
        $l["/usr/local"]=true;
        $l["/usr/src"]=true;
        $l["/usr/usr"]=true;
        $l["/usr/X11R6"]=true;
        $l["/usr/lib"]=true;
        $l["/usr/lib64"]=true;
        $l["/usr/share/artica-postfix"]=true;
        $l["/usr/src"]=true;
        $l["/srv"]=true;
        $l["/var/log"]=true;
        $l["/var/db"]=true;
        $l["/var/lib"]=true;
        $l["/var/lock"]=true;
        $l["/var/mail"]=true;
        $l["/var/milter-greylist"]=true;
        $l["/var/spool"]=true;
        $l["/var/opt"]=true;
        $l["/usr/share"]=true;
        $l["/opt"]=true;
        $l["/var/cache"]=true;
        $l["/var/local"]=true;
        $l["/var/run"]=true;
        $l["/var/tmp"]=true;
        $l["/var/webmin"]=true;
        $l["/lost+found"]=true;

        if($nopreg){
            $l["/home"]=true;
            if(isset($l[$folder])){return true;}
            return false;
        }

        foreach ($l as $num=>$ligne){
            $p=str_replace("/","\/",$num);
            $p=str_replace(".","\.",$p);
            if(preg_match("#$p#",$folder)){
                writelogs("$folder match $p -> protected directory...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                writelogs_framework("$folder match $p -> protected directory...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
                return true;}
        }
    }


    public function LOCATE_HEAD(){
        return $this->find_program("head");
        $this->events("Unable to stat head !");

    }

    public function LOCATE_DB_RECOVER(){
        $file=$this->find_program("db4.10_recover");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.9_recover");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.8_recover");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.7_recover");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.6_recover");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.5_recover");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db_recover");
        if(strlen($file)>3){return $file;}


    }

    public function SLAPD_INITD_PATH(){
        if(is_file('/etc/init.d/ldap')){return '/etc/init.d/ldap';}
        if(is_file('/etc/init.d/slapd')){return '/etc/init.d/slapd';}
        if(is_file('/usr/local/etc/rc.d/slapd')){return '/usr/local/etc/rc.d/slapd';}
        return "/etc/init.d/slapd";
    }

    public function LOCATE_DB_ARCHIVE():string{
        $file=$this->find_program("db4.10_archive");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.9_archive");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.8_archive");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.7_archive");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.6_archive");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.5_archive");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db_archive");
        if(strlen($file)>3){return $file;}
        return "";

    }

    public function LOCATE_DB_STAT():string{
        $file=$this->find_program("db4.10_stat");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.9_stat");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.8_stat");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.7_stat");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.6_stat");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db4.5_stat");
        if(strlen($file)>3){return $file;}
        $file=$this->find_program("db_stat");
        if(strlen($file)>3){return $file;}
        return "";

    }



    public function LOCATE_SYSLOG_PATH():string{
        $datas="";
        if(is_file("/etc/artica-postfix/settings/Daemons/syslog_path")){
            $datas=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("syslog_path"));
            if(!is_file($datas)){$datas="/var/log/syslog";}

        }
        if($datas==null){return "/var/log/syslog";}
        return $datas;
    }

    public function LOCATE_DU():string{
        return $this->find_program("du");
        $this->events("Unable to stat du !");
        return "";
    }

    public function FormatBytes($kbytes):string{

        $spacer="";

        if($kbytes>1048576){
            $value=round($kbytes/1048576, 2);
            if($value>1000){
                $value=round($value/1000, 2);
                return "$value{$spacer}TB";
            }
            return "$value{$spacer}GB";
        }
        elseif ($kbytes>=1024){
            $value=round($kbytes/1024, 2);
            return "$value{$spacer}MB";
        }
        else{
            $value=round($kbytes, 2);
            return "$value{$spacer}KB";
        }

        return "";
    }

    public function TEMP_DIR():string{
        $SysTmpDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SysTmpDir");
        if($SysTmpDir==null){$SysTmpDir="/home/artica/tmp";}
        if($SysTmpDir=="/tmp"){if(function_exists("sys_get_temp_dir")){$SysTmpDir=sys_get_temp_dir();}}
        if(!is_dir($SysTmpDir)){@mkdir($SysTmpDir,01777,true);}
        @chmod($SysTmpDir,01777);
        return $SysTmpDir;
    }


    public function VerifyTar($path){
        if(!is_file($path)){
            echo $path." no such file\n";
            return false;
        }
        $path=$this->shellEscapeChars($path);
        $tar=$this->find_program("tar");
        echo "$tar -tf $path\n";
        exec("$tar -tf $path 2>&1",$results);
        $c=0;
        foreach ($results as $line){
            $c++;
            if(preg_match("#(not look like a tar archive|Exiting with failure|Not found in archive)#i",$line)){
                echo "Failed: $line\n";return false;}
            if($c>10){break;}

        }
        return true;
    }

    public function FILE_TEMP(){
        if(function_exists("tempnam")){
            return tempnam($this->TEMP_DIR(), 'artica-php');
        }
        return $this->TEMP_DIR()."/artica-php-".md5(date("Y-m-d H:i:s"));
    }
    public function sh_command($cmd,$ulimit=0):string{

        if(preg_match("#^nohup:(.+)#",$cmd,$re)){
            $nohup=$this->find_program("nohup");
            $php=$this->LOCATE_PHP5_BIN();
            $cmd=$re[1];
            if(preg_match("#\.php#",$cmd)){
                $cmd="$nohup $php ". ARTICA_ROOT."/$cmd >/dev/null 2>&1 &";
            }
        }

        $tfile=$this->FILE_TEMP().".sh";
        $h=array();
        $h[]="#!/bin/sh";
        $h[]="LC_ALL=C";
        $h[]="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin\"";
        if($ulimit==-1){
            $h[]="ulimit -u unlimited";
        }
        if($ulimit>0){
            $h[]="ulimit -u $ulimit";
        }
        $this->go_exec_syslog("[SH]: $cmd ($tfile)");
        $h[]="$cmd";
        $h[]="/usr/bin/rm $tfile";
        @file_put_contents($tfile,@implode("\n",$h));
        @chmod($tfile,0755);
        return $tfile;
    }

    public function LINUX_CODE_NAME(){
        $f=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinuxDistributionCodeName");
        if(trim($f)<>null){return trim($f);}
        $distriname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinuxDistributionFullName");
        if(preg_match("#Ubuntu#i", $distriname)){return "UBUNTU";}
        if(preg_match("#debian#i", $distriname)){return "DEBIAN";}
        if(preg_match("#suse#i", $distriname)){return "SUSE";}
        if(preg_match("#Fedora#i", $distriname)){return "FEDORA";}
        if(preg_match("#CentOS#i", $distriname)){return "CENTOS";}
        return "";
    }

    public	function BLKID_ARRAY(){
        $unix=new unix();
        $unix->find_program("blkid");
        $df=$unix->find_program("df");
        exec("blkid 2>&1",$results);



        foreach ($results as $line){
            if(!preg_match("#^(.+?):(.+)#", $line,$re)){continue;}
            $DEV=$re[1];
            $line=$re[2];
            $t=explode(" ",$line);
            foreach ($t as $b){
                if(!preg_match("#([A-Z\_]+)=\"(.+?)\"#", $b,$re)){continue;}
                $ARRAY[$DEV][trim($re[1])]=trim($re[2]);
            }
            $ARRAY[$DEV]["PATH"]=$DEV;

        }

        foreach ($ARRAY as $dev=>$sif){
            if(isset($sif["UUID"])){
                $UUIDZ[$sif["UUID"]]=$dev;
            }
        }



        exec("$df -h 2>&1",$results2);
        foreach ($results2 as $index=>$line){
            if(preg_match("#(.+?)\s+(.+?)\s+#", $line,$re)){
                $DEV=$re[1];
                if(preg_match("#by-uuid\/(.+)#", $DEV,$ri)){
                    $UUID=$ri[1];
                    $DEV=$UUIDZ[$UUID];
                }


                $ARRAY[$DEV]["SIZE"]=$re[2];
            }

        }

        return $ARRAY;

    }

    private function udevinfo_disks_query_all($dev){
        if(is_file('/usr/bin/udevinfo')){
            $cmd="/usr/bin/udevinfo -a -p `/usr/bin/udevinfo -q path -n $dev`";
        }
        $ARRAY=array();

        if(is_file('/sbin/udevadm')){
            $cmd="/sbin/udevadm info --query=all --path=`/sbin/udevadm info --query=path --name=$dev`";
        }

        exec("$cmd 2>&1",$results);
        foreach ($results as $line){
            if(preg_match("#E:\s+([A-Z\_]+)=(.+)#", $line,$re)){
                $ARRAY[$re[1]]=trim($re[2]);
            }
        }

        return $ARRAY;

    }


    private function udevinfo_disks(){


        if(is_file('/usr/bin/udevinfo')){$cmd='/usr/bin/udevinfo -e';}
        if(is_file('/sbin/udevadm')){$cmd='/sbin/udevadm info -e';}

        exec("$cmd 2>&1",$results);
        foreach ($results as $index=>$line){
            if(preg_match("#P:\s+(.+)$#", $line,$re)){$DEV=$re[1];continue;}
            if(preg_match("#E:\s+([A-Z\_]+)=(.+)#", $line,$re)){
                $ARRAY[$DEV][$re[1]]=trim($re[2]);
            }

        }


        foreach ($ARRAY as $dev=>$ar){

            if(!isset($ar["DEVNAME"])){
                //if($GLOBALS["VERBOSE"]){echo "$dev -> NO DEVNAME\n";}
                continue;
            }

            if(isset($ar["DEVTYPE"])){
                $NewArray=$this->udevinfo_disks_query_all($ar["DEVNAME"]);

                foreach ($NewArray as $a=>$b){
                    $ARRAY[$dev][$a]=$b;
                }

                foreach ($ARRAY[$dev] as $key=>$val){
                    $NEWARRAY[$ar["DEVNAME"]][$key]=$val;

                }

            }


        }


        return $NEWARRAY;

    }

    public function LINUX_VERS():array{
        $distriname=$this->LINUX_DISTRIBUTION();
        if(preg_match("#([0-9]+)\.([0-9]+)#", $distriname,$re)){
            return array(intval($re[1]),intval($re[2]));
        }

        return array(0,0);

    }

    public function DEBIAN_VERSION():int{

        $int=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
        if($int>0){
            return $int;
        }

        if(is_file("/usr/bin/lsb_release")){
            exec("/usr/bin/lsb_release -sr 2>&1",$results);
            return intval(trim(@implode("",$results)));
        }
        $ver=trim(@file_get_contents("/etc/debian_version"));
        preg_match("#^([0-9]+)\.#",$ver,$re);
        if(preg_match("#squeeze\/sid#",$ver)){return 6;}
        return intval($re[1]);

    }

    function LINUX_ARCHITECTURE():int{
        $uname=$this->find_program("uname");
        exec("$uname -m 2>&1",$results);
        foreach ($results as $val){
            if(preg_match("#i[0-9]86#", $val)){return 32;}
            if(preg_match("#x86_64#", $val)){return 64;}
        }
        return 0;
    }


    public function LINUX_DISTRIBUTION():string{
        return $GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinuxDistributionFullName");

    }
//##############################################################################


    public function KERNEL_VERSION_BIN(){
        $cmd=$this->find_program("uname")." -r";
        exec($cmd,$datas);
        if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#",$datas[0],$re)){
            if(strlen($re[2])==1){$re[2]='0'.$re[2];}
            if(strlen($re[3])==1){$re[3]='0'.$re[3];}
            $kernel=$re[1].$re[2].$re[3];

        }

        return $kernel;
    }






    private function miltergreylist_check(){
        $ConfigurationFile=$this->MILTER_GREYLIST_CONF_PATH();
        $MILTER_GREYLIST_BIN_PATH=$this->MILTER_GREYLIST_BIN_PATH();
        $tmpf=$this->FILE_TEMP();
        for($i=0;$i<50;$i++){
            exec("$MILTER_GREYLIST_BIN_PATH -f $ConfigurationFile -c >$tmpf 2>&1");
            $dd=@file_get_contents($tmpf);

            @unlink($tmpf);
            if(preg_match("#config error at line\s+([0-9]+)#is",$dd,$ri)){
                if(!$this->miltergreylist_removeline($ri[1])){break;}
                continue;
            }

            else{break;}
        }
    }

    private function miltergreylist_removeline($line_number){
        $ConfigurationFile=$this->MILTER_GREYLIST_CONF_PATH();
        if(!is_file($ConfigurationFile)){return null;}
        $datas=@file_get_contents($ConfigurationFile);
        $tbl=explode("\n",$datas);
        if(count($tbl)==$line_number){
            $tbl[]="";
            file_put_contents($ConfigurationFile,implode("\n",$tbl));
            return false;
        }
        echo "Starting......: ".date("H:i:s")." milter-greylist Config...: error, removing  \"{$tbl[$line_number]}\" line $line_number";
        unset($tbl[$line_number]);
        file_put_contents($ConfigurationFile,implode("\n",$tbl));
        return true;
    }



    public function miltergreylist_buildacl($pattern){
        if(preg_match("#(.+?)@(.+)#",$pattern,$re)){
            $part=trim($re[1]);
            $domain=trim($re[2]);
        }
        $part=str_replace('*','',$part);

        if(strlen($part)==0){
            $suffix='acl whitelist domain';
            $domain=str_replace("","@",$domain);
            if(substr($domain, 0,1)=="."){$domain=substr($domain, 1,strlen($domain));}
            $pattern=trim($domain);
            if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $domain)){
                $suffix='acl whitelist addr';
            }


        }else{
            $suffix="acl whitelist from";
        }

        if(strpos($pattern,'*')>0){
            $pattern=str_replace('.','\.',$pattern);
            $pattern=str_replace('*','.*',$pattern);
            $pattern="/$pattern/";
        }

        return "$suffix $pattern";
    }


    public function MILTER_GREYLIST_CONF_PATH(){
        if(is_file('/etc/milter-greylist/greylist.conf')){return '/etc/milter-greylist/greylist.conf';}
        if(is_file('/etc/mail/greylist.conf')){return '/etc/mail/greylist.conf';}
        if(is_file('/opt/artica/etc/milter-greylist/greylist.conf')){return '/opt/artica/etc/milter-greylist/greylist.conf';}
        return '/etc/mail/greylist.conf';
    }

    public function MILTER_GREYLIST_BIN_PATH(){
        if(is_file('/usr/local/bin/milter-greylist')){return '/usr/local/bin/milter-greylist';}
        if(is_file('/usr/sbin/milter-greylist')){return '/usr/sbin/milter-greylist';}
        if(is_file('/usr/bin/milter-greylist')){return '/usr/bin/milter-greylist';}
    }

    public function DIRPART_INFO($directory){
        if(is_link($directory)){$directory=@readlink($directory);}
        $df=$this->find_program("df");
        exec("$df $directory 2>&1",$results);
        foreach ($results as $index=>$line){
            if(preg_match("#(.*?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+(.*)#",$line,$re)){
                $array["DEV"]=$re[1];
                $array["TOT"]=intval($re[2])*1024;
                $array["USED"]=intval($re[3])*1024;
                $array["AIV"]=intval($re[4])*1024;
                $array["POURC"]=$re[5];
                $array["MOUNT"]=$re[6];
                return $array;
            }
        }
    }

    public function DIRPART_OF($directory){
        if(is_link($directory)){$directory=@readlink($directory);}
        $df=$this->find_program("df");
        exec("$df $directory 2>&1",$results);
        foreach ($results as $index=>$line){
            if(preg_match("#(.*?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+(.*)#",$line,$re)){
                if(strpos($re[1],"by-uuid")>0){return $this->DISK_DEVNAMEOF($re[1]);}
                return $re[1];

            }
        }
        return null;
    }

    public function DISK_DEVNAMEOF($string){
        $udevadm=$this->find_program("udevadm");
        exec("$udevadm  info -q all -n \"$string\" 2>&1",$results);
        foreach ($results as $index=>$line){
            if(preg_match("#DEVNAME=(.+)#", $line,$re)){
                return trim($re[1]);

            }
        }
        return null;}

    public function DISK_GET_PARENT_PART($dev){
        $dev=$this->DISK_DEVNAMEOF($dev);
        $ID_PART_ENTRY_DISK=null;
        $udevadm=$this->find_program("udevadm");

        exec("$udevadm  info -q all -n $dev 2>&1",$results);
        if($GLOBALS["VERBOSE"]){echo "udevadm  info -q all -n $dev 2>&1 ".count($results)." rows\n";}
        foreach ($results as $index=>$line){


            if(preg_match("#DEVNAME=(.+)#", $line,$re)){
                $partname=trim($re[1]);
                if($GLOBALS["VERBOSE"]){echo "$dev = Partition : $partname\n";}
                continue;
            }

            if(preg_match("#ID_PART_ENTRY_DISK=(.+)#", $line,$re)){
                $ID_PART_ENTRY_DISK=trim($re[1]);
                if($GLOBALS["VERBOSE"]){echo "$dev = $ID_PART_ENTRY_DISK\n";}
                if($ID_PART_ENTRY_DISK<>null){break;}
                continue;
            }

        }

        if($ID_PART_ENTRY_DISK==null){return null;}
        return $this->DISK_DEVNAMEOF("/dev/block/$ID_PART_ENTRY_DISK");
    }


    public function DIRDISK_OF($directory){
        $ID_PART_ENTRY_DISK=null;
        $dev=$this->DIRPART_OF($directory);
        if($GLOBALS["VERBOSE"]){echo "$directory = Partition : $dev\n";}

        if($dev==null){return null;}
        $udevadm=$this->find_program("udevadm");



        exec("$udevadm  info -q all -n $dev 2>&1",$results);

        if($GLOBALS["VERBOSE"]){echo "udevadm  info -q all -n $dev 2>&1 ".count($results)." rows\n";}

        foreach ($results as $index=>$line){


            if(preg_match("#DEVNAME=(.+)#", $line,$re)){
                $partname=trim($re[1]);
                if($GLOBALS["VERBOSE"]){echo "$dev = Partition : $partname\n";}
                continue;
            }

            if(preg_match("#ID_PART_ENTRY_DISK=(.+)#", $line,$re)){
                $ID_PART_ENTRY_DISK=trim($re[1]);
                if($GLOBALS["VERBOSE"]){echo "$dev = $ID_PART_ENTRY_DISK\n";}
                if($ID_PART_ENTRY_DISK<>null){break;}
                continue;
            }

        }

        if($ID_PART_ENTRY_DISK==null){return null;}
        return $this->DISK_DEVNAMEOF("/dev/block/$ID_PART_ENTRY_DISK");


    }

    public function DIRSIZE_KO_nocache($directory){
        if(is_link($directory)){$directory=readlink($directory);}
        $res=$this->DIRSIZE_BYTES_NOCACHE($directory);
        if($res==0){return 0;}
        return round($res/1024);
    }

    public function DIRSIZE_KO($directory){
        if(is_link($directory)){$directory=readlink($directory);}
        $res=$this->dskspace_bytes($directory);
        if($res==0){return 0;}
        return round($res/1024);
    }

    public function DIRSIZE_MB($directory){
        if(is_link($directory)){$directory=readlink($directory);}
        $mdkey=md5($directory);
        if(!is_dir("/etc/artica-postfix/DIRSIZE_MB_CACHE")){@mkdir("/etc/artica-postfix/DIRSIZE_MB_CACHE",0755,true);}
        $cache_file="/etc/artica-postfix/DIRSIZE_MB_CACHE/$mdkey";
        $Time=$this->file_time_min($cache_file);
        if($Time<120){return trim(@file_get_contents("$cache_file"));}
        @unlink($cache_file);

        $EXEC_NICE=$this->EXEC_NICE();
        $du=$this->find_program("du");

        $dir_name_regex=str_replace("/", "\/", $directory);
        $dir_name_regex=str_replace(".", "\.", $dir_name_regex);
        $pid=$this->PIDOF_PATTERN("$du.*?$dir_name_regex");
        if($this->process_exists($pid)){return 0;}

        if(!function_exists("system_is_overloaded")){
            include_once("/usr/share/artica-postfix/ressources/class.os.system.inc");
        }
        if(function_exists("system_is_overloaded")){
            if(system_is_overloaded()){return 0;}
        }


        exec("$EXEC_NICE$du -m -s $directory 2>&1",$results);

        foreach ($results as $num=>$ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){
                @file_put_contents($cache_file, $re[1]);
                return $re[1];}
        }
    }
    public function DIRSIZE_BYTES_NOCACHE($directory){
        if(is_link($directory)){$directory=readlink($directory);}

        if(!is_dir($directory)){
            $directory_tmp=dirname($directory);
            if(is_link($directory_tmp)){$directory_tmp=readlink($directory_tmp);}
            if(is_dir($directory_tmp)){$directory=$directory_tmp;}
        }
        return $this->dskspace_bytes($directory,true);
    }

    private function is_overloaded(){
        if(!function_exists("system_is_overloaded")){
            include_once("/usr/share/artica-postfix/ressources/class.os.system.inc");
        }
        if(function_exists("system_is_overloaded")){
            return system_is_overloaded();
        }
    }

    public function DIRSIZE_BYTES($directory,$asCache=false){
        if(is_link($directory)){$directory=readlink($directory);}
        if(!is_dir($directory)){return 0;}
        $mdkey=md5($directory);
        if(!is_dir("/etc/artica-postfix/DIRSIZE_BYTES_CACHE")){@mkdir("/etc/artica-postfix/DIRSIZE_BYTES_CACHE",0755,true);}
        $cache_file="/etc/artica-postfix/DIRSIZE_BYTES_CACHE/$mdkey.bytes";
        if($this->is_overloaded()){return trim(@file_get_contents($cache_file));}

        if($asCache){
            $Time=$this->file_time_min($cache_file);
            if($Time<180){return trim(@file_get_contents($cache_file));}
            @unlink($cache_file);
        }


        if(!is_dir($directory)){
            $directory_tmp=dirname($directory);
            if(is_link($directory_tmp)){$directory_tmp=readlink($directory_tmp);}
            if(is_dir($directory_tmp)){$directory=$directory_tmp;}
        }
        if(!$asCache){$nocache=true;}else{$nocache=false;}
        $result=$this->dskspace_bytes($directory,$nocache);
        @file_put_contents($cache_file, $result);
        return $result;
    }

    public function DIR_COUNT_OF_FILES($directory){
        if(is_link($directory)){$directory=readlink($directory);}
        if(!is_dir($directory)){return 0;}
        $find=$this->find_program("find");
        $nice=$this->EXEC_NICE();
        $wc=$this->find_program("wc");
        exec(trim("$nice $find \"$directory\" -type f  | $wc -l 2>&1"),$results);
        return trim(@implode("",$results));
    }

    public function LOCATE_PHP5_BIN():string{
        if(isset($GLOBALS["LOCATE_PHP5_BIN"])){if(strlen($GLOBALS["LOCATE_PHP5_BIN"])>3){return $GLOBALS["LOCATE_PHP5_BIN"];}}
        $locate[]="/usr/bin/php";
        $locate[]="/usr/bin/php7.4";
        $locate[]="/usr/bin/php7.3";
        $locate[]="/usr/bin/php7.2";
        $locate[]="/usr/bin/php7.1";
        $locate[]="/usr/bin/php7.0";

        foreach ($locate as $path){
            if(is_file($path)){
                $GLOBALS["LOCATE_PHP5_BIN"]=$path;
                return $path;
            }
        }
        $php5=$this->find_program("php");
        if(is_file($php5)){$GLOBALS["LOCATE_PHP5_BIN"]=$php5;return $php5;}
        $php5=$this->find_program("php5");
        if(is_file($php5)){$GLOBALS["LOCATE_PHP5_BIN"]=$php5;return $php5;}
        $this->events("Unable to stat php5 !");
        return "";
    }
        public function LOCATE_CUPS_ACCEPT(){
        if(is_file('/usr/sbin/cupsaccept')){return '/usr/sbin/cupsaccept';}
        if(is_file('/usr/sbin/accept')){return '/usr/sbin/accept';}
    }
    //#############################################################################

    public function getmodpathfromconf():string{
        if(is_dir("/etc/apache2/mods-enabled")){return "/etc/apache2";}
        if(is_dir("/etc/httpd/mods-enabled")){return "/etc/httpd";}
        if(is_dir("/usr/local/etc/httpd/mods-enabled")){return "/usr/local/etc/httpd";}
        if(is_dir("/usr/local/etc/apache2/mods-enabled")){return "/usr/local/etc/apache2";}
        $httpdconf=$this->LOCATE_APACHE_CONF_PATH();
        if($httpdconf=="/etc/httpd/conf/httpd.conf"){return "/etc/httpd";}
        if($httpdconf=="/usr/local/etc/httpd/conf/httpd.conf"){return "/usr/local/etc/httpd";}
        return dirname($httpdconf);
    }


    public function CGROUPS_limit_service_structure($groupname,$cpu_shares,$cpus=0,$blkio=0):bool{
        $echobin=$this->find_program("echo");
        $this->CGROUPS_create_service_structure($groupname);
        echo "Starting......: ".date("H:i:s")." cgroups Limiting $groupname to $cpu_shares share CPU:#$cpus I/O $blkio limit\n";
        system("$echobin $cpu_shares > /cgroups/cpu/$groupname/cpu.shares");
        system("$echobin 0 >/cgroups/cpuset/$groupname/cpuset.mems");
        system("$echobin ".intval($cpus)." > /cgroups/cpuset/$groupname/cpuset.cpus");
        system("$echobin $blkio >/cgroups/blkio/$groupname/blkio.weight");
        return true;
    }

    private function CGROUPS_create_service_structure($groupname){
        $subgroups[]="cpuset";
        $subgroups[]="blkio";
        $subgroups[]="cpu";
        $unix=new unix();
        $cgcreate=$this->find_program("cgcreate");
        $CREATED=true;


        foreach ($subgroups as $ligne){
            if(!is_dir("/cgroups/$ligne/$groupname")){$CREATED=false;break;}

        }

        if($CREATED){return;}
        shell_exec("$cgcreate -a root -g cpu,cpuset,blkio:$groupname");
    }





    public function APACHE_DIR_SITES_ENABLED(){
        if(is_dir("/etc/apache2/sites-enabled")){return '/etc/apache2/sites-enabled';}
        $httpdconf=$this->LOCATE_APACHE_CONF_PATH();
        $DAEMON_PATH=$this->getmodpathfromconf();
        return $DAEMON_PATH."/sites-enabled";
        $mods_enabled=$DAEMON_PATH."/mods-enabled";

    }

    public function LIGHTTPD_PHP5_CGI_BIN_PATH(){
        if(isset($GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"])){return $GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"];}
        $GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"]=$this->find_program("php-fcgi");
        if(!is_file($GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"])){$GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"]=$this->find_program("php-cgi");}
        return $GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"];

    }

    function CREATE_NEW_UUID(){
        if(is_file("/etc/artica-postfix/settings/Daemons/SYSTEMID_CREATED")){
            @chmod("/etc/artica-postfix/settings/Daemons/SYSTEMID", 0755);
            $value=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID"));
            if($value<>null){return $value;}
        }
        if(is_file("/etc/artica-postfix/settings/Daemons/SYSTEMID")) {
            $chattr = $this->find_program("chattr");
            shell_exec("$chattr -i /etc/artica-postfix/settings/Daemons/SYSTEMID");
        }
        $uuid=trim($this->gen_uuid());
        if(strlen($uuid)>5){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEMID", $uuid);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEMID_CREATED", time());
            @chmod("/etc/artica-postfix/settings/Daemons/SYSTEMID", 0755);
            shell_exec("$chattr +i /etc/artica-postfix/settings/Daemons/SYSTEMID");

            if(is_file("/etc/init.d/squid")) {
                $nohup=$this->find_program("nohup");
                $php5=$this->LOCATE_PHP5_BIN();
                $pprefix="/usr/share/artica-postfix";
                $todev=">/dev/null 2>&1";
                shell_exec("$nohup $php5 $pprefix/exec.squid.global.access.php --common $todev &");
            }


            return $uuid;

        }else{
            echo "Gen_uuid return bad value!\n";
        }

    }
    public function GetHotFixVersion(){
        $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/fw.updates.php"));
        foreach ($f as $line){
            if(preg_match("#HOTFIX.*?=\"([0-9]+)\"#",$line,$re)){
                return $re[1];
            }
        }
        return 0;
    }

    public function GetUniqueID(){
        if(isset($GLOBALS["MYUUID"])){return $GLOBALS["MYUUID"];}
        $GLOBALS["MYUUID"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID"));
        return $GLOBALS["MYUUID"];

    }

    public function gen_uuid(){
        return sprintf(
            '%08x-%04x-%04x-%02x%02x-%012x',
            mt_rand(),
            mt_rand(0, 65535),
            bindec(substr_replace(
                    sprintf('%016b', mt_rand(0, 65535)), '0100', 11, 4)
            ),
            bindec(substr_replace(sprintf('%08b', mt_rand(0, 255)), '01', 5, 2)),
            mt_rand(0, 255),
            mt_rand()
        );
    }

    public function ifconfig_all_ips(){

        return $this->NETWORK_ALL_INTERFACES(true);



    }



    public function ifconfig_interfaces_list(){
        $ri=array();
        $cmd=$this->find_program("ip")." -o addr sho 2>&1";
        exec($cmd,$results);
        foreach ($results as $index=>$line){
            if(preg_match("#^[0-9]+:\s+(.*?)\s+inet\s+([0-9\.]+)\/#", $line,$re)){
                if(preg_match("#(.+?)@(.+)#", $re[1],$rz)){$re[1]=$rz[1];}
                $ri[$re[1]]=$re[2];
                continue;
            }

        }



        return $ri;
    }

    public function LOCATE_APACHE_CTL(){
        $apachectl=$this->find_program("apachectl");
        if(is_file($apachectl)){return $apachectl;}
        $apachectl=$this->find_program("apache2ctl");
        if(!is_file($apachectl)){$apachectl=$this->find_program("apachectl");}
        if(is_file($apachectl)){return $apachectl;}
    }

    public function SAMBA_GetNetAdsInfos(){
        $net=$this->LOCATE_NET_BIN_PATH();
        if(!is_file($net)){return array();}
        exec("$net ads info 2>&1",$results);
        foreach ($results as $index=>$line){
            if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
                $array[trim($re[1])]=trim($re[2]);
            }
        }
        if(!isset($array["KDC server"])){$array["KDC server"]=null;}
        return $array;
    }

    public function PATH_LDAP_DIRECTORY_DATA(){
        $file=file_get_contents("/etc/ldap/slapd.conf");
        $datas=explode("\n",$file);
        if(!is_array($datas)){
            $this->events("Unable to stat /etc/ldap/slpad.conf !");
            return null;}
        foreach ($datas as $num=>$ligne){
            if(preg_match("#^directory\s+(.+)#",$ligne,$re)){
                return trim($re[1]);
            }
        }
        $this->events("Unable to stat directory in /etc/ldap/slpad.conf ! in ".count($datas)." rows");
    }

    public function FILE_IS_LINK($path){
        $path=rtrim($path, '/');
        $stat=$this->find_program("stat");
        exec("$stat $path 2>&1",$datas);
        foreach ($datas as $num=>$ligne){$ligne=trim($ligne);if($ligne==null){continue;}if(preg_match("#^File:.+?\->\s+.*?#", trim($ligne))){return true;}}
        return false;
    }

    public function FILE_REALPATH($path){
        $orgpath=$path;
        $path=rtrim($path, '/');
        $stat=$this->find_program("stat");
        exec("$stat $path 2>&1",$datas);
        foreach ($datas as $num=>$ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(preg_match("#^File:.+?\->\s+.*?#", trim($ligne))){
                $f=explode("->", $ligne);
                $realpth=trim($f[1]);
                if(substr($realpth, 0,1)=="`"){$realpth=substr($realpth, 1,strlen($realpth));}
                if(substr($realpth, strlen($realpth)-1,1)=="'"){$realpth=substr($realpth, 0,strlen($realpth)-1);}
                return trim($realpth);
            }
        }
        return trim($orgpath);
    }

    function LDAP_SCHEMA_PATH(){
        if(is_dir('/etc/ldap/schema')){return '/etc/ldap/schema';}
        if(is_dir('/etc/openldap/schema')){return '//etc/openldap/schema';}
        if(is_dir('/opt/artica/etc/openldap/schema')){return '//opt/artica/etc/openldap/schema';}
    }


    public function DHCPD_BIN_PATH(){
        $dhcpd=$this->find_program("dhcpd");
        if(is_file($dhcpd)){return $dhcpd;}
        $dhcpd=$this->find_program("dhcpd3");
        if(is_file($dhcpd)){return $dhcpd;}
    }

    public function SessionPathInMemoryInfos(){
        $session_path=ini_get('session.save_path');
        $session_path=str_replace("/","\/",$session_path);
        $session_path=str_replace(".","\.",$session_path);
        $array=array();
        $df=$this->find_program("df");
        $cmd="$df 2>&1";
        exec($cmd,$results);

        $array["MAX"]=0;
        $array["USE"]=0;
        $array["FREE"]=0;
        $array["POURC"]=0;

        foreach ($results as $num=>$line){
            if(preg_match("#tmpfs\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+$session_path#", $line,$re)){
                $array["MAX"]=$re[1];
                $array["USE"]=$re[2];
                $array["FREE"]=$re[3];
                $array["POURC"]=$re[4];
                break;
            }
        }
        return $array;

    }

    public function DEBIAN_INSTALL_PACKAGE($package){
        $progressExists=false;
        $logfile="/var/log/artica-apget.$package.log";
        if(!isset($GLOBALS["OUTPUT"])){$GLOBALS["OUTPUT"]=false;}
        if(function_exists("build_progress_install")){$progressExists=true;$GLOBALS["OUTPUT"]=true;}
        $this->events("*************************************************************************************",$logfile,__FUNCTION__,__FILE__,__LINE__);



        $this->events("Install <$package>",$logfile,__FUNCTION__,__FILE__,__LINE__);
        $aptget=$this->find_program("apt-get");
        if(!is_file($aptget)){
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." apt-get: $package apt-get -> No such binary\n";}
            return false;
        }
        $apttime="/etc/artica-postfix/APT_GET_UPDATE_TIME";
        $time=$this->file_time_min($apttime);

        if($time>1880){
            $php=$this->LOCATE_PHP5_BIN();
            if(function_exists("build_progress_install")){
                if($progressExists){build_progress_install("{updating_repository}",20);}
            }


            system("/usr/sbin/artica-phpfpm-service -sources-list");
            system("$aptget update -y --allow-releaseinfo-change");
            @unlink("/etc/artica-postfix/APT_GET_UPDATE_TIME");
            @file_put_contents("/etc/artica-postfix/APT_GET_UPDATE_TIME", time());
        }

        echo "Starting......: ".date("H:i:s")." apt-get: $package installing package\n";
        if(function_exists("build_progress_install")){
            if($progressExists){build_progress_install("{installing} {please_wait}",25);}
        }

        $cmd="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\" DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" -fuy install $package 2>&1";
        $this->events("<$package>: $cmd",$logfile,__FUNCTION__,__FILE__,__LINE__);

        if(is_file("/etc/group.lock")){ @unlink("/etc/group.lock"); }
        if(is_file("/etc/gshadow.lock")){ @unlink("/etc/gshadow.lock"); }
        if(is_file("/etc/passwd.lock")){ @unlink("/etc/passwd.lock"); }
        if(is_file("/etc/shadow.lock")){ @unlink("/etc/shadow.lock"); }


        $script[]="#!/bin/sh";
        $script[]="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"";
        $script[]="echo PATH=\$PATH";
        $script[]="/usr/sbin/artica-phpfpm-service -savelocales";
        if(preg_match("#freeradius#",$package)){
            if(!is_file("/etc/init.d/freeradius")) {
                $script[] = "touch /etc/init.d/freeradius";
            }
        }

        $script[]="$cmd";
        $script[]="";
        if(preg_match("#freeradius#",$package)){
            if(!is_file("/etc/init.d/freeradius")) {
                $script[] = "/bin/rm -f /etc/init.d/freeradius";
            }
        }
        $tmpfile=$this->FILE_TEMP().".sh";
        @file_put_contents($tmpfile, @implode("\n", $script));
        @chmod($tmpfile,0755);
        $this->events("<$package>: Executing $tmpfile",$logfile,__FUNCTION__,__FILE__,__LINE__);
        $results=array();
        exec("$tmpfile 2>&1",$results);
        foreach ($results as $ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(preg_match("#maybe run apt-get update or try#",$ligne)){
                $this->events("<$package>: Restarting using apt-get update...",$logfile,__FUNCTION__,__FILE__,__LINE__);
                $this->ToSyslog("dpkg","Restarting using apt-get update");
                $this->shell_command("$aptget --allow-releaseinfo-change -y update");
                exec("$tmpfile 2>&1",$results);
            }
            if(preg_match("#No user sessions are running outdated binaries#i",$ligne)){
                $this->events("<$package>: No user sessions are running outdated binaries [NEED RESTART]",$logfile,__FUNCTION__,__FILE__,__LINE__);
                @unlink($logfile);
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NEEDRESTART",1);
                return true;
            }

            if(preg_match("#(Could not connect to|Unable to connect to)#",$ligne)){
                $this->ToSyslog($ligne,false,"dpkg");
                return false;
            }

            if(preg_match("#Temporary failure resolving#",$ligne)){
                $this->ToSyslog($ligne,false,"dpkg");
                return false;
            }

            if(strlen($ligne)>3) {
                $this->ToSyslog($ligne,false,"dpkg");
                $this->events("<$package>: '$ligne'",$logfile,__FUNCTION__,__FILE__,__LINE__);
                if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." apt-get: $ligne\n";}
            }
        }



        if(function_exists("squid_admin_mysql")){
            squid_admin_mysql(2, "{installing} Debian [$package] {package} ", @file_get_contents($logfile),__FILE__,__LINE__);
        }

        @unlink($tmpfile);
        @unlink($logfile);
        if($progressExists){build_progress_install("{installing} {done}",30);}

        return true;

    }


    public function ps_mem_report():string{
        $ftime="/var/run/ps_mem_report.time";
        $stime=$this->file_time_min($ftime);
        if($stime<5){
            $f[]="An already report as been generated since 5 minutes..";
            $f[]=@file_get_contents($ftime);
            return @implode("\n", $f);
        }
        @unlink($ftime);
        @file_put_contents($ftime,time());

        $nice=$this->EXEC_NICE();
        $ps=$this->find_program("ps");
        $sort=$this->find_program("sort");
        $head=$this->find_program("head");
        $results[]="************* Memory summarize ************************";
        $results[]="";
        exec(trim("$nice /usr/share/artica-postfix/bin/percpu -ps 2>&1"),$results);

        $results[]="";
        $results[]="";


        $results[]="************* TOP 50 processes ************************";
        exec("$ps --no-heading -eo user,pid,pcpu,args|$sort -grbk 3|$head -50 2>&1",$results);
        $results[]="";
        $results[]="";

        $results[]="************* TOP 50 Memory processes ************************";
        exec("$ps --no-heading -eo user,pid,pmem,args|$sort -grbk 3|$head -50 2>&1",$results);
        $results[]="";
        $results[]="";
        $results[]="************* Kernel Infos ************************";
        $results[]=@file_get_contents("/proc/meminfo");
        $results[]="";
        $results[]="";
        $results[]="************* ALL Processes ************************";
        exec("$ps auxww 2>&1",$results);
        @file_put_contents($ftime,@implode("\n", $results));
        return @implode("\n", $results);
    }


    public function REMOVE_INTERFACE_CACHE():bool{
        $rm=$this->find_program("rm");
        include_once(dirname(__FILE__)."/class.status.hardware.inc");
        shell_exec("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*");
        shell_exec("$rm -rf /usr/share/artica-postfix/ressources/logs/cache/*");
        shell_exec("$rm -rf /usr/share/artica-postfix/ressources/interface-cache/*");
        if(is_file("/usr/share/artica-postfix/ressources/logs/global.status.ini")) {
            @unlink("/usr/share/artica-postfix/ressources/logs/global.status.ini");
        }
        $php=$this->LOCATE_PHP5_BIN();
        system("$php /usr/share/artica-postfix/exec.status.php --process1");
        shell_exec("$php /usr/share/artica-postfix/exec.squid.interface-size.php --force");
        return true;
    }


    public function KILL_PROCESSES_BY_PORT($port){
        $FUSERS=array();
        $results=array();
        $fuser=$this->find_program("fuser");
        exec("$fuser $port/tcp 2>&1",$results);

        foreach ($results as $key=>$line){
            if($GLOBALS["VERBOSE"]){echo "fuser: ->\"$line\"\n";}
            if(preg_match("#tcp:\s+(.+)#", $line,$re)){
                $FUSERS=explode(" ",$re[1]);
            }
        }

        if(isset($GLOBALS["SERVICE_NAME"])){$GLOBALS["TITLENAME"]=$GLOBALS["SERVICE_NAME"];}

        if(count($FUSERS)>0){
            foreach ($FUSERS as $key=>$pid){
                $pid=trim($pid);
                if(!is_numeric($pid)){continue;}
                if($pid==0){continue;}
                if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing $pid PID that listens $port\n";}
                unix_system_kill_force($pid);
            }
        }
    }

    public function nginx_version(){
        $nginx=$this->find_program("nginx");
        if(!is_file($nginx)){return "0.0.0";}
        exec("$nginx -V 2>&1",$results);
        foreach ($results as $key=>$value){
            if(preg_match("#nginx version: .*?\/([0-9\.]+)#", $value,$re)){return $re[1];}
        }
        return "0.0.0";
    }

    //##############################################################################
    public function IS_CITRIXXEN_HOST(){
        if($GLOBALS["VERBOSE"]){echo "Exec IS_CITRIXXEN_HOST()\n";}
        if(is_file("/etc/artica-postfix/XENSERVER_NOHOST")){
            @unlink("/etc/artica-postfix/XENSERVER_HOST");
            @unlink("/etc/artica-postfix/XENSERVER_NOHOST");
        }

        if(is_file("/etc/artica-postfix/XENSERVER_HOST")){
            $xtime=$this->file_time_get("/etc/artica-postfix/XENSERVER_HOST");
            if($xtime>120){@unlink("/etc/artica-postfix/XENSERVER_HOST");}
        }
        if(is_file("/etc/artica-postfix/XENSERVER_HOST")){
            $data=trim(@file_get_contents("/etc/artica-postfix/XENSERVER_HOST"));
            if(is_numeric($data)){return intval($data);}
        }

        if(is_file("/etc/artica-postfix/dmidecode-type-1.cache")){
            $xtime=$this->file_time_get("/etc/artica-postfix/dmidecode-type-1.cache");
            if($xtime>480){@unlink("/etc/artica-postfix/dmidecode-type-1.cache");}
        }
        if(is_file("/etc/artica-postfix/dmidecode-type-1.cache")){
            if($GLOBALS["VERBOSE"]){echo "Open /etc/artica-postfix/dmidecode-type-1.cache\n";}
            $results=unserialize(@file_get_contents("/etc/artica-postfix/dmidecode-type-1.cache"));

        }else{
            $dmidecode=$this->find_program('dmidecode');
            if (!is_file("$dmidecode")){return 0;}
            if($GLOBALS["VERBOSE"]){echo "Exec $dmidecode --type 1 2\n";}
            exec("$dmidecode --type 1 2>&1",$results);
            @file_put_contents("/etc/artica-postfix/dmidecode-type-1.cache", serialize($results));
        }

        foreach($results as $line){
            if(preg_match("#Manufacturer: Xen#",$line)){
                @file_put_contents("/etc/artica-postfix/XENSERVER_HOST",1);
                return 1;
            }

            if(preg_match("#Xen#",$line)){
                @file_put_contents("/etc/artica-postfix/XENSERVER_HOST",1);
                return 1;
            }
        }
        if($GLOBALS["VERBOSE"]){echo "IS_CITRIXXEN_HOST()\n";}
        @file_put_contents("/etc/artica-postfix/XENSERVER_HOST",0);
        return 0;
    }
    //##############################################################################

    public function IS_NUTANIX_HOST(){
        if($GLOBALS["VERBOSE"]){echo "Exec IS_NUTANIX_HOST()\n";}


        if(is_file("/etc/artica-postfix/NUTANIX_HOST")){
            $xtime=$this->file_time_get("/etc/artica-postfix/NUTANIX_HOST");
            if($xtime>120){@unlink("/etc/artica-postfix/NUTANIX_HOST");}
        }
        if(is_file("/etc/artica-postfix/NUTANIX_HOST")){
            $data=trim(@file_get_contents("/etc/artica-postfix/NUTANIX_HOST"));
            if(is_numeric($data)){return intval($data);}
        }

        if(is_file("/etc/artica-postfix/dmidecode-type-1.cache")){
            $xtime=$this->file_time_get("/etc/artica-postfix/dmidecode-type-1.cache");
            if($xtime>480){@unlink("/etc/artica-postfix/dmidecode-type-1.cache");}
        }
        if(is_file("/etc/artica-postfix/dmidecode-type-1.cache")){
            if($GLOBALS["VERBOSE"]){echo "Open /etc/artica-postfix/dmidecode-type-1.cache\n";}
            $results=unserialize(@file_get_contents("/etc/artica-postfix/dmidecode-type-1.cache"));

        }else{
            $dmidecode=$this->find_program('dmidecode');
            if (!is_file("$dmidecode")){return 0;}
            if($GLOBALS["VERBOSE"]){echo "Exec $dmidecode --type 1 2\n";}
            exec("$dmidecode --type 1 2>&1",$results);
            @file_put_contents("/etc/artica-postfix/dmidecode-type-1.cache", serialize($results));
        }

        foreach($results as $line){
            if(preg_match("#Manufacturer:\s+Nutanix#",$line)){
                @file_put_contents("/etc/artica-postfix/NUTANIX_HOST",1);
                return 1;
            }
        }
        if($GLOBALS["VERBOSE"]){echo "IS_NUTANIX_HOST()\n";}
        @file_put_contents("/etc/artica-postfix/NUTANIX_HOST",0);
        return 0;
    }
    //##############################################################################





    public function isModulesLoaded($modname){
        $lsmod=$this->find_program("lsmod");
        exec("$lsmod 2>&1",$zresults);
        foreach ($zresults as $yline){
            if(preg_match("#^$modname\s+[0-9]+#", $yline)){return true;}
        }
        return false;
    }

    public function find_library($strlib){

        $directory[]="/lib/x86_64-linux-gnu";
        $directory[]="/usr/lib64";
        $directory[]="/usr/lib";
        $directory[]="/lib64";
        $directory[]="/lib";
        $directory[]="/usr/x86_64-linux-gnu/lib64";
        $directory[]="/usr/x86_64-linux-gnu/lib";
        $directory[]="/usr/local/lib/x86_64-linux-gnu";
        $directory[]="/usr/local/lib64";
        $directory[]="/usr/local/lib";

        foreach ($directory as $path){
            if(is_file("$path/$strlib")){return true;}
        }
        return false;

    }

    public function network_get_all_ips(){

        $f=explode("\n",@file_get_contents("/proc/net/fib_trie"));
        $array=array();
        $IPADDR=null;
        foreach ($f as $line){

            if(preg_match("#\/[0-9]+\s+host LOCAL#",$line,$re)){
                if($IPADDR==null){continue;}
                $array[$IPADDR]=true;
            }


            if(!preg_match("#\|--\s+([0-9\.]+)#",$line,$re)){continue;}
            $IPADDR=$re[1];
            if($IPADDR=="127.0.0.0"){$IPADDR=null;continue;}
            if($IPADDR=="127.0.0.1"){$IPADDR=null;continue;}


        }
        $final=array();
        foreach ($array as $ipaddr=>$none){

            $final[]=$ipaddr;
        }
        return $final;

    }

    public function syslog_pattern($MAIN):array{
        $TERM=null;
        $date=null;
        if(!is_array($MAIN)){
            return array("","","");
        }
        if(!isset($MAIN["MAX"])){$MAIN["MAX"]=500;}

        foreach ($MAIN as $key=>$val){
            $MAIN[$key]=str_replace(".", "\.",$val);
            $MAIN[$key]=str_replace("*", ".*?", $val);

        }

        $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}

        if(isset($MAIN["DATE"])){
            $date=$MAIN["DATE"];
        }
        if(isset($MAIN["TERM"])){
            $TERM=".*?{$MAIN["TERM"]}";
        }
        return array($date,$TERM,$max);

    }

    public function mysqladmin($command):string{
        $password="";
        $mysql_password=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
        $username=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
        $server=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
        $port=intval(@file_get_contents("/etc/artica-postfix/settings/Mysql/port"));
        if($port==0){$port=3306;}
        if($server==null){$server="127.0.0.1";}
        if($username==null){$username="root";}

        if( ($server=="localhost") OR ($server=="127.0.0.1")){
            $serv=" --socket=/var/run/mysqld/mysqld.sock --protocol=socket";
        }else{
            $serv=" --host=$server --port=$port";
        }


        $mysqladmin=$this->find_program("mysqladmin");
        if(!is_null($mysql_password)){
            if($mysql_password=="!nil"){$mysql_password=null;}
            $mysql_password=stripslashes($mysql_password);
            $mysql_password=str_replace("\r", "", $mysql_password);
            $mysql_password=trim($mysql_password);
            if(!is_null($mysql_password)){
                $password=" --password=".$this->shellEscapeChars($mysql_password);
            }
        }

        return "$mysqladmin --connect-timeout=5 $serv --user=$username$password $command";
    }

    public function find_program($strProgram,$nocache=false):string {
        if($strProgram=="mysql"){
            if(is_file("/usr/bin/mariadb")){
                return "/usr/bin/mariadb";
            }
        }
        if($strProgram=="mysqld"){
            if(is_file("/usr/sbin/mariadbd")){
                return "/usr/sbin/mariadbd";
            }
        }

        if($strProgram=="mysqladmin"){
            if(is_file("/usr/bin/mariadb-admin")){
                return "/usr/bin/mariadb-admin";
            }
        }


        $strProgram=trim($strProgram);
        $key=md5($strProgram);

        if(!$nocache){
            if(isset($GLOBALS["find_program"][$key])){return $GLOBALS["find_program"][$key];}
            if(function_exists("apc_store")){$value=trim(apc_fetch("LOCATE_PROGRAM_$key"));if($value<>null){if(basename($value)==$strProgram){$GLOBALS["find_program"][$key]=trim($value);return trim($value);}}}
            if(!isset($GLOBALS["find_program"]["$key"])){$GLOBALS["find_program"]["$key"]=null;}
            if(isset($_SESSION["find_program"]["$key"])){if($_SESSION["find_program"]["$key"]<>null){return $_SESSION["find_program"]["$key"];}}
            if(isset($GLOBALS["find_program"]["$key"])){if($GLOBALS["find_program"]["$key"]<>null){return $GLOBALS["find_program"]["$key"];}	}
        }
        $value=trim((string) $this->internal_find_program($strProgram));
        if(function_exists("apc_store")){apc_store("LOCATE_PROGRAM_$key",$value);}
        $GLOBALS["find_program"]["$key"]=$value;
        return $value;

    }

    public function netstat_port($ip,$port):array{
        $array=array();
        $ip=str_replace(".","\.",$ip);
        exec("netstat -anp 2>&1",$results);
        foreach ($results as $line){
            if(!preg_match("#^(tcp|udp)\s+[0-9]+\s+[0-9]+\s+$ip:$port\s+.*?LISTEN\s+([0-9]+)\/(.+)#",$line,$re)){continue;}
            $pid=$re[2];
            $array[$pid]=true;
        }
        return $array;
    }

    public function CICAP_SERVICE_EVENTS($text,$file=null,$line=0){
        $file=basename($file);
        $line=date("D M  ").Intval(date("d")).date(" H:i:s Y").", ".getmypid()."/".
            time().", $text [$file - $line]\n";

        $f = @fopen("/var/log/c-icap/c-icap-server.log", 'a');
        @fwrite($f, $line);
        @fclose($f);

    }

    public 	function CleanOldLibs(){
        $ln=$this->find_program("ln");
        if(is_file("/usr/lib/x86_64-linux-gnu/libtalloc.so.2")){
            if(is_file("/usr/lib/libtalloc.so.2")){@unlink("/usr/lib/libtalloc.so.2");}
        }

        if(is_file("/usr/lib/libicapapi.so.3.0.4")){
            @unlink("/usr/lib/libicapapi.so.3.0.1");
            @unlink("/usr/lib/libicapapi.so.3.0.2");
            @unlink("/usr/lib/libicapapi.so.3.0.3");
            if(!is_link("/usr/lib/libicapapi.so.3")){
                @unlink("/usr/lib/libicapapi.so.3");
                shell_exec("$ln -sf /usr/lib/libicapapi.so.3.0.4 /usr/lib/libicapapi.so.3 >/dev/null 2>&1");
            }
        }

        if(is_file("/usr/lib/libicapapi.so.3.0.3")){
            @unlink("/usr/lib/libicapapi.so.3.0.1");
            @unlink("/usr/lib/libicapapi.so.3.0.2");
            if(!is_link("/usr/lib/libicapapi.so.3")){
                @unlink("/usr/lib/libicapapi.so.3");
                shell_exec("$ln -sf /usr/lib/libicapapi.so.3.0.3 /usr/lib/libicapapi.so.3 >/dev/null 2>&1");
            }
        }
        if(is_file("/usr/lib/libicapapi.so.3.0.2")){
            @unlink("/usr/lib/libicapapi.so.3.0.1");
            if(!is_link("/usr/lib/libicapapi.so.3")){
                @unlink("/usr/lib/libicapapi.so.3");
                shell_exec("$ln -sf /usr/lib/libicapapi.so.3.0.2 /usr/lib/libicapapi.so.3 >/dev/null 2>&1");
            }
        }

        if(is_file("/usr/lib/libicapapi.so.3.0.1")){
            if(!is_link("/usr/lib/libicapapi.so.3")){
                shell_exec("$ln -s /usr/lib/libicapapi.so.3.0.1 /usr/lib/libicapapi.so.3 >/dev/null 2>&1");
            }
        }

    }

    public function load_rt_table(){
        $f=explode("\n", @file_get_contents("/etc/iproute2/rt_tables"));
        foreach ( $f as $index=>$line ){
            if(!preg_match("#([0-9]+)\s+(.+)#", $line,$re)){continue;}
            $array[$re[2]]=$re[1];

        }


    }

    public function last_rt_table_number(){
        $c=0;
        $f=explode("\n", @file_get_contents("/etc/iproute2/rt_tables"));
        foreach ( $f as $index=>$line ){
            if(!preg_match("#([0-9]+)\s+(.+)#", $line,$re)){continue;}
            if($re[1]>0){continue;}
            if($re[1]>252){continue;}
            if($re[1]>$c){$c=$re[1];}
        }

        return $c;

    }

    public function LOCATE_mysqld_bin(){
        if(is_file("/usr/sbin/mariadbd")){return "/usr/sbin/mariadbd";}
        if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
        $bin=$this->find_program('mysqld');
        if(is_file($bin)){$GLOBALS[__FUNCTION__]=$bin;return $bin;}
        if(is_file("/opt/artica/mysql/libexec/mysqld")){$GLOBALS[__FUNCTION__]="/opt/artica/mysql/libexec/mysqld";return "/opt/artica/mysql/libexec/mysqld";}
        if(is_file("/usr/libexec/mysqld")){$GLOBALS[__FUNCTION__]="/usr/libexec/mysqld";return "/usr/libexec/mysqld";}

        return null;}
//##############################################################################


    public function IsProductionTime(){
        $period_from=79200; //22H
        $period_to=21600; //6H
        $time=time();
        $currentStartDay=strtotime(date("Y-m-d 00:00:01"));
        $ThisInt=$time-$currentStartDay;
        if($GLOBALS["VERBOSE"]){echo "Day start at $currentStartDay\n";}
        if($GLOBALS["VERBOSE"]){echo "This time is $ThisInt\n";}
        if($GLOBALS["VERBOSE"]){echo "$period_from = 22H\n";}
        if($GLOBALS["VERBOSE"]){echo "$period_to = 06h\n";}
        if($ThisInt>$period_from){
            if($GLOBALS["VERBOSE"]){echo "$ThisInt>$period_from NO Production time\n";}
            return false;}
        if($ThisInt<$period_to){
            if($GLOBALS["VERBOSE"]){echo "$ThisInt>$period_from NO Production time\n";}
            return false;}
        return true;


    }


    public function BLKID_INFOS($dev){
        $array=array();
        $blkid=$this->find_program("blkid");
        $df=$this->find_program("df");
        if(!is_file($blkid)){return array("UUID"=>"blkid No such binary");}
        $cmd="$blkid \"$dev\" 2>&1";
        exec($cmd,$results);
        $imploded=trim(@implode("", $results));
        $f=explode(" ",$imploded );
        foreach ($f as $num=>$ligne){if(preg_match('#(.+?)="(.+?)"#', $ligne,$re)){$array[strtoupper($re[1])]=$re[2];}}
        $results=array();
        $devRgx=str_replace("/", "\/", $dev);
        $devRgx=str_replace(".", "\.", $devRgx);
        exec("$df -h \"$dev\" 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(preg_match("#$devRgx\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
                $array["SIZE"]=$re[1];
                $array["USED"]=$re[2];
                $array["AIVA"]=$re[3];
                $array["POURC"]=$re[4];
                $array["MOUNTED"]=$re[5];
            }
        }
        return $array;

    }

    public function DIR_STATUS($path){
        $array=array();
        $df=$this->find_program("df");


        $results=array();

        exec("$df \"$path\" 2>&1",$results);

        $pattern="#.*?\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#";

        foreach ($results as $num=>$ligne){
            if(!preg_match($pattern, $ligne,$re)){
                writelogs_framework("No match \"$ligne\" $pattern",__FUNCTION__,__FILE__,__LINE__);
                continue;
            }
            $array["SIZE"]=$re[1];
            $array["USED"]=$re[2];
            $array["AIVA"]=$re[3];
            $array["POURC"]=$re[4];
            $array["MOUNTED"]=$re[5];
        }
        return $array;

    }

    public function BLKID_UUIDs(){
        $uuid=null;
        if(isset($GLOBALS["BLKID_UUIDs"])){return $GLOBALS["BLKID_UUIDs"];}
        $cachefile=PROGRESS_DIR."/blkid-uuid.db";
        $unix=new unix();
        if(!$GLOBALS["VERBOSE"]){
            if(is_file($cachefile)){
                if($unix->file_time_min($cachefile)<3){$GLOBALS["BLKID_UUIDs"]=unserialize(@file_get_contents($cachefile));return $GLOBALS["BLKID_UUIDs"];}
            }
        }
        $GLOBALS["BLKID_UUIDs"]=array();
        $blkid=$unix->find_program("blkid");
        exec("$blkid 2>&1",$results);
        foreach ($results as $num=>$ligne){
            $ligne=trim($ligne);
            $label=null;

            if(preg_match("#^(.+?):#", $ligne,$re)){$dev=$re[1];}
            if(preg_match("#UUID=\"(.+?)\"#", $ligne,$re)){$uuid=$re[1];}
            if(preg_match("#LABEL=\"(.+?)\"#", $ligne,$re)){$label=$re[1];}
            if($GLOBALS["VERBOSE"]){echo "$ligne uuid=$uuid; label=$label;\n";}
            $GLOBALS["BLKID_UUIDs"][$uuid]["DEV"]=$dev;
            $GLOBALS["BLKID_UUIDs"][$uuid]["LABEL"]=$label;
        }

        @file_put_contents($cachefile, serialize($GLOBALS["BLKID_UUIDs"]));
        @chmod($cachefile,0755);
        return $GLOBALS["BLKID_UUIDs"];

    }

    public function BLKID_ALL():array{
        $cachefile=PROGRESS_DIR."/blkid.db";
        $array=array();
        $unix=new unix();
        if(!$GLOBALS["VERBOSE"]){
            if(is_file($cachefile)){
                if($unix->file_time_min($cachefile)<3){
                    $data=unserialize(@file_get_contents($cachefile));
                    if(!is_array($data)){return array();}
                    return $data;
                }
            }
        }
        $blkid=$unix->find_program("blkid");
        exec("$blkid 2>&1",$results);
        $label=null;
        foreach ($results as $ligne){
            $ligne=trim($ligne);
            $uuid="";$dev="";
            if(preg_match("#^(.+?):#", $ligne,$re)){$dev=$re[1];}
            if(preg_match("#UUID=\"(.+?)\"#", $ligne,$re)){$uuid=$re[1];}
            if(preg_match("#LABEL=\"(.+?)\"#", $ligne,$re)){$label=$re[1];}
            if($GLOBALS["VERBOSE"]){echo "$ligne uuid=$uuid; label=$label;\n";}
            $array[$dev]["UUID"]=$uuid;
            $array[$dev]["LABEL"]=$label;
        }

        @file_put_contents(PROGRESS_DIR."/blkid.db", serialize($array));
        @chmod($cachefile,0755);
        return $array;

    }

    public function is_a_partition($directorypath):bool{

        $h=explode("\n",@file_get_contents("/proc/mounts"));
        foreach ($h as $line){
            if(strpos($line,$directorypath)){return true;}
        }
        return false;

    }

    public function SYSTEMS_ALL_PARTITIONS(){
        $df=$this->find_program("df");
        exec("$df -h 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(preg_match("#(.+?)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
                $array[$re[1]]["SIZE"]=$re[2];
                $array[$re[1]]["USED"]=$re[3];
                $array[$re[1]]["AIVA"]=$re[4];
                $array[$re[1]]["POURC"]=$re[5];
                $array[$re[1]]["MOUNTED"]=$re[6];
                $array[$re[1]]["VALUES"]=$this->tune2fs_values($re[1]);

            }
        }
        return $array;

    }

    public function SYSTEM_GET_MEMORY_MB(){

        $free=$this->find_program("free");
        exec("$free -m 2>&1",$results);
        foreach ($results as $num=>$ligne){if(preg_match("#Mem:\s+([0-9]+)#", $ligne,$re)){return $re[1];}}
        return 0;
    }

    public function SYSTEM_GET_SWAP_MB(){

        $free=$this->find_program("free");
        exec("$free -m 2>&1",$results);
        foreach ($results as $num=>$ligne){if(preg_match("#Swap:\s+([0-9]+)#", $ligne,$re)){return $re[1];}}
        return 0;
    }

    public function SYSTEM_GET_SWAP_PERC(){

        $SYSTEM_GET_SWAP_PERC_TIME=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEM_GET_SWAP_PERC_TIME"));
        if($this->time_min($SYSTEM_GET_SWAP_PERC_TIME)<15){
            return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEM_GET_SWAP_PERC"));
        }

        $free=$this->find_program("free");
        exec("$free -m 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(!preg_match("#Swap:\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){continue;}
            $total=intval($re[1]);
            $used=intval($re[2]);
            if($total==0){
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEM_GET_SWAP_PERC_TIME",time());
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEM_GET_SWAP_PERC",0);
                return 0;
            }
            $prc=($used/$total)*100;
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEM_GET_SWAP_PERC_TIME",time());
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEM_GET_SWAP_PERC",round($prc));
            return round($prc);
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEM_GET_SWAP_PERC_TIME",time());
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEM_GET_SWAP_PERC",0);
        return 0;

    }

    function system_mysql_events($subject,$text,$file,$context,$line=null){
        $file=basename($file);
        if(!is_numeric($line)){$line=0;}
        if($GLOBALS["VERBOSE"]){echo "$subject $text script:$file in line $line\n";}
        if(!class_exists("mysql")){include_once("/usr/share/artica-postfix/ressources/class.mysql.inc");}
        if(!class_exists("mysql")){if($GLOBALS["VERBOSE"]){echo "mysql no such class!\n";}return;}
        if(!isset($GLOBALS["MYHOSTNAME"])){$GLOBALS["MYHOSTNAME"]=$this->hostname_g();}
        $file=basename($file);
        $subject=addslashes($subject);
        $text=addslashes($text);
        $sql="INSERT IGNORE INTO events (zDate,hostname,process,text,context,sended,content)
		VALUES(NOW(),'{$GLOBALS["MYHOSTNAME"]}','$file','$subject','$context','1','$text')";
        $q=new mysql();
        $q->QUERY_SQL($sql,"artica_events");
        if(!$q->ok){if(function_exists("writelogs")){writelogs("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}}
    }


    public function UFDBGUARDD_MINOR(){
        if(isset($GLOBALS["UFDBGUARDD_MINOR"])){return $GLOBALS["UFDBGUARDD_MINOR"];}
        $this->UFDBGUARDD_MAJOR();
        return $GLOBALS["UFDBGUARDD_MINOR"];
    }

    public function UFDBGUARDD_MAJOR(){
        if(!isset($GLOBALS["UFDBGUARDD_MAJOR"])){$GLOBALS["UFDBGUARDD_MAJOR"]=null;}
        if(is_numeric($GLOBALS["UFDBGUARDD_MAJOR"])){if($GLOBALS["UFDBGUARDD_MAJOR"]>0){return $GLOBALS["UFDBGUARDD_MAJOR"];}}
        $ufdbguarddd=$this->find_program("ufdbguardd");
        exec("$ufdbguarddd -v 2>&1",$results);

        foreach ($results as $ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(preg_match("#^ufdbguardd.*?([0-9]+)\.([0-9]+)#",$ligne,$re)){
                $major=$re[1];$minor=$re[2];break;
            }
        }

        $GLOBALS["UFDBGUARDD_MAJOR"]=$major;
        $GLOBALS["UFDBGUARDD_MINOR"]=$minor;
        return $GLOBALS["UFDBGUARDD_MAJOR"];
    }

    private function internal_find_program($strProgram):string{
        if($strProgram=="rm"){
            if(!is_file("/bin/rm")){
                $Arch=$this->LINUX_ARCHITECTURE();
                if($Arch==64){
                    @copy("/usr/share/artica-postfix/bin/rm","/bin/rm");
                    $chattr=$this->find_program("chattr");
                    shell_exec("$chattr -i /bin/rm");
                }
            }
        }

        global $addpaths;
        $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
            '/usr/local/sbin','/usr/kerberos/bin','/usr/libexec');
        if (function_exists("is_executable")) {
            foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}
        } else {
            return strpos($strProgram, '.exe');
        }
        return "";
    }

    private function POSTCONF_BIN():string{
        if(isset($GLOBALS["MEM_POSTCONF"])){
            return $GLOBALS["MEM_POSTCONF"];
        }
        if(isset($GLOBALS["postconf"])){
            $GLOBALS["MEM_POSTCONF"]=$GLOBALS["postconf"];
            return $GLOBALS["postconf"];
        }
        $postconf=$this->find_program("postconf");
        $GLOBALS["MEM_POSTCONF"]=$postconf;
        return $postconf;
    }

    public function POSTFIX_RELOAD($instance_id=0):bool{
        $postfix=$this->find_program("postfix");
        if(!is_file($postfix)){return false;}
        if($instance_id>0){
            return $this->POSTFIX_MULTI_RELOAD($instance_id);
        }
        shell_exec("$postfix reload >/dev/null 2>&1");
        return true;
    }

    private function POSTFIX_MULTI_RELOAD($instance_id):bool{
        $postmulti=$this->POSTCONF_MULTI_BIN();
        shell_exec("$postmulti -i postfix-instance$instance_id -p reload >/dev/null 2>&1");
        return true;
    }

    public function POSTCONF_SET($key=null,$value=null,$instance_id=0){
        if($key==null){return false;}
        $value=trim($value);
        if(intval($instance_id)>0){
            return $this->POSTCONF_MULTI_SET("instance$instance_id",$key,$value);
        }

        $postconf=$this->POSTCONF_BIN();
        if($value==null){
            shell_exec("$postconf -X \"$key\" >/dev/null 2>&1");
            return true;
        }
        $value=str_replace('$','\$',$value);

        if(function_exists("_out")){
            _out("$postconf -e \"$key=$value\"");
        }
        shell_exec("$postconf -e \"$key=$value\" >/dev/null 2>&1");
        return true;
    }


    public function POSTCONF_GET($value,$instance_id=0){

        if($instance_id>0){
            return $this->POSTCONF_MULTI_GET("instance$instance_id",$value);
        }

        if(!isset($GLOBALS["MEM_POSTCONF"])){$GLOBALS["MEM_POSTCONF"]=null;}
        if($GLOBALS["MEM_POSTCONF"]<>null){$postconf=$GLOBALS["MEM_POSTCONF"];}else{$postconf=$this->find_program("postconf");$GLOBALS["MEM_POSTCONF"]=$postconf;}
        if($GLOBALS["DEBUG"]){echo "POSTCONF_GET:: $postconf -h $value\n";}
        exec("$postconf -h $value 2>&1",$results);
        $v=array();
        foreach ($results as $num=>$line){if(preg_match("#warning:#", $line)){continue;}$v[]=$line;}
        return trim(implode("",$v));

    }

    public function POSTCONF_MULTI_GET($instance,$value){
        $postmulti=$this->POSTCONF_MULTI_BIN();
        $cmd="$postmulti -i postfix-$instance -x postconf -h $value 2>&1";
        if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
        exec($cmd,$results);
        $v=array();
        foreach ($results as $num=>$line){
            if(preg_match("#warning:#", $line)){continue;}
            $v[]=$line;
        }
        $vFinal=@implode("", $v);
        if($GLOBALS["DEBUG"]){writelogs_framework("$cmd -> $vFinal",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
        return $vFinal;
    }

    public function POSTCONF_MULTI_BIN():string{
        if(isset($GLOBALS["MEM_postmulti"])){
            return $GLOBALS["MEM_postmulti"];
        }

        if(isset($GLOBALS["postmulti"])){
            $GLOBALS["MEM_postmulti"]=$GLOBALS["postmulti"];
            return $GLOBALS["postmulti"];
        }
        $postmulti=$this->find_program("postmulti");
        $GLOBALS["MEM_postmulti"]=$postmulti;
        return $postmulti;

    }

    public function POSTCONF_MULTI_SET($instance,$key,$value):bool{

        $postmulti=$this->POSTCONF_MULTI_BIN();
        $postconf=$this->POSTCONF_BIN();
        $cmd[]="$postmulti -i postfix-$instance -x";
        $cmd[]=$postconf;
        
        $value=trim($value);
        if($value==null){
            $cmd[]="-X \"$key\" >/dev/null 2>&1";
            $cmdline=@implode(" ",$cmd);
            shell_exec($cmdline);
            return true;
        }

        $value=str_replace('$','\$',$value);
        $cmd[]="-e \"$key=$value\" >/dev/null 2>&1";
        $cmdline=@implode(" ",$cmd);
        if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
        shell_exec($cmdline);
        return true;
    }

    public function POSTFIX_MULTI_PID($instance){
        if($GLOBALS["DEBUG"]){echo "POSTFIX_MULTI_PID:: $instance\n";}
        $path=$this->POSTCONF_MULTI_GET($instance,"queue_directory")."/pid/master.pid";
        $data=trim(@file_get_contents($path));
        return trim($data);
    }

    public function POSTFIX_PID(){
        $path=$this->POSTCONF_GET("queue_directory")."/pid/master.pid";
        $GLOBALS["DEBUG_LOGS"][]="Pid path : $path";
        if($GLOBALS["DEBUG"]){echo "POSTFIX_PID:: $path\n";}
        $data=trim(@file_get_contents($path));
        $data=trim($data);
        $GLOBALS["DEBUG_LOGS"][]="Pid content : $data";
        if(preg_match("#([0-9]+)#",$data,$re)){$data=$re[1];}
        if($GLOBALS["DEBUG"]){echo "POSTFIX_PID:: $data\n";}
        return $data;
    }

    public function POSTFIX_MASTER_BIN_PATH(){
        $path=trim($this->POSTCONF_GET("daemon_directory")."/master");
        $GLOBALS["DEBUG_LOGS"][]="Bin path : $path";
        if($GLOBALS["DEBUG"]){echo "POSTFIX_PID:: $path\n";}
        return $path;
    }


    public function ChangeRootPassword($password){

        if(!is_file("/etc/artica-postfix/shadow.bak")){
            if(is_file("/etc/artica-postfix/shadow.bak")){@unlink("/etc/artica-postfix/shadow.bak");}
            @copy("/etc/shadow", "/etc/artica-postfix/shadow.bak");
        }

        $t=array();
        $f=explode("\n",@file_get_contents("/etc/shadow"));
        foreach ($f as $num=>$val){
            if(trim($val)==null){continue;}
            if(preg_match("#^root:(.*?):.*?:#", $val,$re)){
                $val=str_replace($re[1], "", $val);

            }

            $t[]=$val;

        }

        @file_put_contents("/etc/shadow", @implode("\n", $t));



        $php=$this->LOCATE_PHP5_BIN();
        $cmd=trim("/usr/sbin/artica-phpfpm-service -build-pam");
        shell_exec($cmd);

        $echo=$this->find_program("echo");

        $chpasswd=$this->find_program("chpasswd");
        $passwd=$this->shellEscapeChars($password);

        $cmd="$echo root:$passwd | $chpasswd 2>&1";
        exec("$cmd",$results);
        writelogs_framework("$cmd " .count($results)." rows",__FUNCTION__,__FILE__,__LINE__);

        foreach ($results as $num=>$line){writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);}
        reset($results);
        return base64_encode(@implode("\n",$results));



    }





    public function POSTFIX_VERSION(){return $this->POSTCONF_GET("mail_version");}

    public function LOCATE_POSTFIX_DAEMON_DIRECTORY(){
        if(isset($GLOBALS[__FUNCTION__])){
            if(strlen($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
        }
        $locate1=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_1();
        $locate2=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_2();
        $locate3=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_3();
        if($locate2<>null){if($locate1<>$locate2){$locate1=$locate2;}}
        if($locate3<>null){if($locate1<>$locate3){$locate1=$locate3;}}
        $GLOBALS[__FUNCTION__]=$locate1;
        return $locate1;
    }

    function IncidentSquid($subject):bool{
        $q=new lib_sqlite("/home/artica/SQLITE_TEMP/system.perf.queue.db");
        if(!is_dir("/home/artica/SQLITE_TEMP")){@mkdir("/home/artica/SQLITE_TEMP",0755,true);}
        @chmod("/home/artica/SQLITE_TEMP/system.perf.queue.db",0755);
        @chown("/home/artica/SQLITE_TEMP/system.perf.queue.db","www-data");
        @chown("/home/artica/system","www-data");
        $SystemInfoCache="/etc/squid3/squid_get_system_info.db";
        $StoreDirCache="/etc/squid3/squid_storedir_info.db";

        $SourceTime=time();
        $Workdir=$this->TEMP_DIR()."/$SourceTime";
        @mkdir($Workdir,0755,true);
        $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `perfs_queue` ( `zDate` INTEGER NOT NULL PRIMARY KEY, `subject` TEXT NOT NULL, `file` TEXT NOT NULL )");

        $tail=$this->find_program("tail");
        $tmpfile=$Workdir."/cache.log";
        shell_exec("$tail -n 2000 /var/log/squid/cache.log >$tmpfile");
        $tmpfilegz=$this->FILE_TEMP();
        $tar=$this->find_program("tar");
        $ps=$this->find_program("ps");
        $grep=$this->find_program("grep");
        $rm=$this->find_program("rm");
        system("cd $Workdir");
        @chdir($Workdir);
        @copy($SystemInfoCache,"$Workdir/".basename($SystemInfoCache));
        @copy($StoreDirCache,"$Workdir/".basename($StoreDirCache));
        shell_exec("$ps -aux | $grep squid >$Workdir/ps.txt");
        shell_exec("$tar -czf $tmpfilegz *");
        $data=base64_encode(@file_get_contents($tmpfilegz));
        @unlink($tmpfile);
        shell_exec("$rm -rf $Workdir");
        $q->QUERY_SQL("INSERT OR IGNORE INTO `perfs_queue` (zDate,subject,file) VALUES ('$SourceTime','$subject','$data');");
        return true;
    }


    private function LOCATE_POSTFIX_DAEMON_DIRECTORY_1(){
        if(is_file('/usr/lib/postfix/master')){return ('/usr/lib/postfix');}
        if(is_file('/usr/lib64/postfix/master')){return ('/usr/lib64/postfix');}
        if(is_file('/usr/libexec/postfix/master')){return ('/usr/libexec/postfix');}
        if(is_file('/usr/local/libexec/postfix/master')){return ('/usr/local/libexec/postfix');}

    }

    private function LOCATE_POSTFIX_DAEMON_DIRECTORY_2():string{
        if(is_file('/usr/lib/postfix/postscreen')){return ('/usr/lib/postfix');}
        if(is_file('/usr/libexec/postfix/postscreen')){return ('/usr/libexec/postfix');}
        if(is_file('/usr/local/libexec/postfix/postscreen')){return ('/usr/local/libexec/postfix');}
        return "/usr/libexec/postfix";

    }

    private function LOCATE_POSTFIX_DAEMON_DIRECTORY_3():string{
        if(is_file('/usr/lib/postfix/postmulti-script')){return ('/usr/lib/postfix');}
        if(is_file('/usr/libexec/postfix/postmulti-script')){return ('/usr/libexec/postfix');}
        if(is_file('/usr/local/libexec/postfix/postmulti-script')){return ('/usr/local/libexec/postfix');}
        return "/usr/libexec/postfix/postmulti-script";
    }

    public function LOCATE_POSTFIX_POSTSCREEN():string{
        $f=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_2();
        if(!is_null($f)) {
            if (is_file($f)) {
                return $f;
            }
        }
        if(is_file('/usr/lib/postfix/postscreen')){return ('/usr/lib/postfix/postscreen');}
        if(is_file('/usr/lib64/postfix/postscreen')){return ('/usr/lib64/postfix/postscreen');}
        if(is_file('/usr/libexec/postfix/postscreen')){return ('/usr/libexec/postscreen');}
        if(is_file('/usr/local/libexec/postfix/postscreen')){return ('/usr/local/libexec/postfix/postscreen');}
        return "/usr/libexec/postfix/postscreen";
    }



    public function IPTABLES_DELETE_REGEX_ENTRIES($regexPat){
        if(trim($regexPat)==null){return;}
        $conf=null;
        $iptables_save=$this->find_program("iptables-save");
        $iptables_restore=$this->find_program("iptables-restore");
        $cmd="$iptables_save > /etc/artica-postfix/iptables.conf";
        shell_exec($cmd);
        $data=file_get_contents("/etc/artica-postfix/iptables.conf");
        $datas=explode("\n",$data);
        if($GLOBALS["VERBOSE"]){echo __CLASS__.'/'.__FUNCTION__." iptables.conf: ". count($data)." lines L.". __LINE__."\n";}
        $pattern="#$regexPat#";
        $count=0;
        foreach ($datas as $num=>$ligne){
            if($ligne==null){continue;}
            if(preg_match($pattern,$ligne)){
                if($GLOBALS["VERBOSE"]){echo __CLASS__.'/'.__FUNCTION__." Delete \"$ligne\" L.". __LINE__."\n";}
                $count++;continue;
            }
            $conf=$conf . $ligne."\n";
        }

        file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
        if($count>0){
            $cmd="$iptables_restore < /etc/artica-postfix/iptables.new.conf";
            if($GLOBALS["VERBOSE"]){echo __CLASS__.'/'.__FUNCTION__."$cmd L.". __LINE__."\n";}
            shell_exec("$cmd");
        }


    }


    public function LOCATE_SENDMAIL_PID_PATH(){
        if(is_file("/var/run/sendmail.pid")){return "/var/run/sendmail.pid";}
        return null;
    }



    public function events($text,$logFile=null,$phplog=false,$sourcefunction=null,$sourceline=null,$sourcefile=null)
    {
        if ($GLOBALS["OUTPUT"]) {
            echo $text . "\n";
            return;
        }
        $maxSize = 15000000;
        $mem = null;
        $size = 0;
        $internal_load = 0;
        $pid = getmypid();
        if ($logFile <> null) {
            $logbasename = basename($logFile);
            if ($logbasename == "squid.watchdog.log") {
                $maxSize = 900000;
            }
        }
        if ($sourcefile <> null) {
            $sourcefile = basename($sourcefile);
        }
        $date = date("Y-m-d H:i:s");
        if (function_exists("debug_backtrace")) {
            $trace = debug_backtrace();
            if (isset($trace[1])) {
                if ($sourcefile == null) {
                    $sourcefile = basename($trace[1]["file"]);
                }
                if ($sourcefunction == null) {
                    $sourcefunction = $trace[1]["function"];
                }
                if ($sourceline == null) {
                    $sourceline = $trace[1]["line"];
                }
            }

        }

        if (function_exists("memory_get_usage")) {
            $mem = round(((memory_get_usage() / 1024) / 1000), 2);
        }
        if (function_exists("sys_getloadavg")) {
            $array_load = sys_getloadavg();
            $internal_load = $array_load[0];
        }


        $line = "$date [$pid] $sourcefile: $text  - function:$sourcefunction in line:$sourceline Mem:{$mem}MB Load:$internal_load\n";

        if (function_exists("openlog")) {
            openlog("artica", LOG_PID, LOG_SYSLOG);
            syslog(LOG_INFO, "$sourcefile: $text  - function:$sourcefunction in line:$sourceline Mem:{$mem}MB Load:$internal_load");
            closelog();
        }



        if($logFile==null){$logFile="{$GLOBALS["ARTICALOGDIR"]}/syslog.log";}

        if(is_file($logFile)) {
            $size = @filesize($logFile);
        }
        if($size>$maxSize){
            @unlink($logFile);
            @touch($logFile);
            @chmod($logFile, 0777);
        }

        $h = @fopen($logFile, 'a');
        if($GLOBALS["VERBOSE"]){echo $line;}
        if(!isset($_SESSION["uid"])){
            if($phplog){error_log("[ -- ]::$line");}
        }else{
            if($phplog){error_log("[{$_SESSION["uid"]}]::$line");}
        }


        @fwrite($h,$line);
        @fclose($h);
    }

    private function utf8Decode($string=""):string{
        if(is_null($string)){return $string;}

        if(function_exists("mb_convert_encoding")){
            return strval(mb_convert_encoding($string, 'ISO-8859-1'));

        }
        if(function_exists("utf8_decode")) {
            return utf8_decode($string);
        }
    return $string;
    }

    public function dirdir($path){
        $array=array();
        if(is_link($path)){$path=readlink($path);}
        foreach (glob("$path/*",GLOB_ONLYDIR) as $directory) {
            $directory = $this->utf8Decode($directory);
            $directory=str_replace('//', "/", $directory);
            $array[$directory]=$directory;
        }

        return $array;
    }


    public function COUNT_FILES($directory){
        if(is_link($directory)){$directory=readlink($directory);}
        if(!is_dir($directory)){return 0;}
        if (!$handle = @opendir($directory)) {return 0;}
        $c=0;
        while (false !== ($filename = readdir($handle))) {
            if($filename=="."){continue;}
            if($filename==".."){continue;}
            $path = $directory.'/'.$filename;
            if(is_dir($path)){continue;}
            $c++;
        }
        return $c;
    }

    public	function getDirectories($directory){
        if(is_link($directory)){$directory=readlink($directory);}
        $GLOBALS["RECURSVIVE_INC"]=$GLOBALS["RECURSVIVE_INC"]+1;
        if($GLOBALS["MAX_RECUSIVE_DIRS"]<50){$GLOBALS["MAX_RECUSIVE_DIRS"]=400;}
        if($GLOBALS["RECURSVIVE_INC"]>$GLOBALS["MAX_RECUSIVE_DIRS"]){
            $trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
            error_log("[{$_SESSION["uid"]}]::PHP Fatal: cannot parse directory [instance {$GLOBALS["RECURSVIVE_INC"]}], maximum recursive of {$GLOBALS["MAX_RECUSIVE_DIRS"]} has been reached $called");
            return;
        }
        $array_items = array();
        if ($handle = @opendir($directory)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (is_dir($directory. "/" . $file)) {
                        $newarrayDirectories= $this->getDirectories($directory. "/" . $file);
                        if(is_array($newarrayDirectories)){$array_items = array_merge($array_items,$newarrayDirectories);}
                        $file = $directory . "/" . $file;
                        $array_items[] = preg_replace("/\/\//si", "/", $file);
                    } else {
                        //$file = $directory . "/" . $file;
                        //$array_items[] = preg_replace("/\/\//si", "/", $file);
                    }
                }
            }
            closedir($handle);
        }
        return $array_items;


    }

    function DirRecursiveFiles($dir,$pattern=null){
        $Npattern=str_replace(".", "\.", $pattern);
        $Npattern=str_replace("*", ".*?", $Npattern);
        $files = Array();
        $file_tmp= glob($dir.'*',GLOB_MARK | GLOB_NOSORT);

        foreach($file_tmp as $item){
            if(substr($item,-1)!=DIRECTORY_SEPARATOR){
                if($pattern<>null){
                    if(!is_file($item)){continue;}
                    if(!preg_match("#$Npattern$#", $item)){continue;}
                }
                $files[] = $item;
            }else{
                $files = array_merge($files,$this->DirRecursiveFiles($item,$pattern));
            }
        }

        return $files;
    }

    function DirFiles($path,$regexsearch=null){
        $dir_handle = @opendir($path);
        $array=array();
        if(!$dir_handle){
            return array();
        }
        $count=0;
        while ($file = readdir($dir_handle)) {
            if($file=='.'){continue;}
            if($file=='..'){continue;}
            if(is_dir("$path/$file")){continue;}
            if($regexsearch<>null){if(!preg_match("#$regexsearch#i", $file)){continue;}}
            $array[$file]=$file;
            continue;


        }

        @closedir($dir_handle);
        return $array;
    }

    function GLFS_ismounted($path){
        $pathString=str_replace("/","\/",$path);
        $pathString=str_replace(".","\.",$pathString);
        $f=explode("\n",@file_get_contents("/proc/mounts"));
        foreach ( $f as $index=>$line ){
            if(preg_match("#$pathString\s+fuse\.glusterfs#",$line)){
                return true;
            }

        }

    }



    public function dir_count_files_recursive($path) {
        $files = 0;
        $dir = opendir($path);
        if (!$dir){return 0;}
        while (($file = readdir($dir)) !== false) {
            if ($file[0] == '.'){ continue; }
            if (is_dir($path.$file)){
                $files += $this->dir_count_files_recursive($path.$file.DIRECTORY_SEPARATOR);
            }
            else {
                $files++;
            }
        }
        closedir($dir);
        return $files;
    }

    public function LOCATE_SQUID_PID(){
        $pid_filename=null;
        if(is_file("/var/run/squid/squid.pid")){return "/var/run/squid/squid.pid";}

        $squidconf=$this->SQUID_CONFIG_PATH();
        if(is_file($squidconf)) {
            $f = explode("\n", @file_get_contents($squidconf));
            foreach ($f as $num => $ligne) {
                if (preg_match("#pid_filename\s+(.+)#", $ligne, $re)) {
                    $pid_filename = trim($re[1]);
                }
            }
        }

        if($pid_filename<>null){
            if(!is_file($pid_filename)){
                if(is_file("/var/run/squid/squid.pid")){return "/var/run/squid/squid.pid";}
            }
        }
        return "/var/run/squid/squid.pid";

    }


    public function PIDOF($binpath,$noppid=false):int{
        $cmd=$this->find_program('pidof')." -s $binpath";
        if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $cmd\n";}
        exec("$cmd 2>&1",$re);
        if(is_array($re)){
            foreach ($re as $ligne){
                if(!preg_match("#[0-9]+#",$ligne)){continue;}
                $pid=trim($ligne);
                if($this->PID_IS_CHROOTED($pid)){continue;}
                if($this->PIDOF_DOCKER($pid)){continue;}
                if($noppid){
                    if($GLOBALS["VERBOSE"]){echo "PIDOF -> $pid\n";}
                    return intval($pid);
                }
                $PPID=$this->PROCESS_PPID($pid);
                if($PPID==0){return intval($pid);}
                return $PPID;

            }
        }

        return 0;

    }

    public function PID_IS_CHROOTED($pid):bool{
        return false;
        if(is_file("/proc/user_beancounters")){return false;}
        if($this->find_program("cgconfigparser")){return false;}
        $pid=trim($pid);
        if(!is_numeric($pid)){return false;}
        if(is_file("/etc/artica-postfix/AS_VPS_CLIENT")){return false;}
        if(!is_file("/proc/$pid/cgroup")){return false;}
        if(preg_match("#[0-9]+.+?:/(.+?)$#",@file_get_contents("/proc/$pid/cgroup"),$re)){
            $vps=trim($re[1]);
            if(strlen($vps)>1){return true;}
        }
        return false;
    }

    public function PIDOF_UDP_PORT($ipaddr,$port):int{
        $netstat=$this->find_program("netstat");
        exec("$netstat -lupn 2>&1",$results);
        $ipaddr=str_replace(".","\.",$ipaddr);
        foreach ($results as $ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(!preg_match("#^udp\s+[0-9]+\s+[0-9]+\s+$ipaddr:$port\s+.*?\s+([0-9]+)\/#",$ligne,$re)){continue;}
            return intval($re[1]);
        }
        return 0;
    }

    private function PIDOF_DOCKER($pid):bool{
        if(!is_file("/etc/init.d/docker")){return false;}
        $PPID = $this->PPID_OF($pid);
        $cmdline=@file_get_contents("/proc/$PPID/cmdline");
        if($GLOBALS["VERBOSE"]){echo "PIDOF_DOCKER::DOCKER $pid as $PPID parent process ([$cmdline]).\n";}
        if(strpos($cmdline,"/containerd")>1){return true;}
        return false;
    }

    public function PIDOF_PATTERN($pattern):int{
        $pattern=str_replace("/","\/",$pattern);
        $pattern=str_replace("$","\$",$pattern);
        $pattern=str_replace("[","\[",$pattern);
        $pattern=str_replace("]","\]",$pattern);
        $pattern=$this->find_program('pgrep')." -l -f \"$pattern\" 2>&1";
        if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $pattern\n";}
        exec($pattern,$results);
        foreach ($results as $ligne){
            if(preg_match("#pgrep\s+#",$ligne)){continue;}
            if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $ligne\n";}
            if(preg_match("#^([0-9]+)\s+sh$#",$ligne,$re)){continue;}
            if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){
                if($this->PIDOF_DOCKER($re[1])){continue;}
                return $re[1];
            }
        }
        return 0;
    }


    public function PIDOF_PATTERN_ALL($pattern):array{
        $mypid=getmypid();
        $LIST=array();
        $ASREGX=false;
        if(strpos($pattern,".*?")>0){$ASREGX=true;}
        if(!$ASREGX) {
            $pattern = str_replace("/", "\/", $pattern);
            $pattern = str_replace("$", "\$", $pattern);
            $pattern = str_replace(".", "\.", $pattern);
        }
        $pattern=$this->find_program('pgrep')." -l -f \"$pattern\" 2>&1";
        if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $pattern\n";}
        exec($pattern,$results);
        foreach ($results as $num=>$ligne){
            if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $ligne\n";}
            if(preg_match("#pgrep\s+#",$ligne)){continue;}
            if(preg_match("#^([0-9]+)\s+sh$#",$ligne,$re)){continue;}
            if(!preg_match("#^([0-9]+)\s+#",$ligne,$re)){continue;}
            $pid=$re[1];
            if($this->PIDOF_DOCKER($pid)){continue;}
            if($pid==$mypid){
                if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $pid==$mypid (me)\n";}
                continue;
            }
            if(preg_match("#^([0-9]+)\s+sh -c#",$ligne,$re)){continue;}
            if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN_ALL():: Found $pid \"$ligne\"\n";}
            $LIST[$pid]=$pid;

        }
        return $LIST;

    }

    public function PROCESS_GET_USER($pid){
        $filename="/proc/$pid/status";
        if(!is_file($filename)){return null;}
        $f=explode("\n", @file_get_contents($filename));
        foreach ($f as $num=>$ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(!preg_match("#Uid:\s+([0-9]+)#", $ligne,$re)){continue;}
            if($re[1]==0){return "root";}
            return $this->USER_ID_FROM_USERNAME($re[1]);
        }

    }

    public function USER_ID_FROM_USERNAME($id){
        $filename="/etc/shadow";
        $f=explode("\n", @file_get_contents($filename));
        foreach ($f as $num=>$ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(!preg_match("#^(.*?):.*?:$id#",$ligne,$re)){continue;}
            return $re[1];
        }

        return -1;
    }


    public function PROCESS_TIME_TO_LIVE($pid){
        @chmod("/usr/share/artica-postfix/bin/procstat",0755);
        exec("/usr/share/artica-postfix/bin/procstat",$array);
        foreach ($array as $num=>$ligne){
            if(preg_match("#start_time:.+?\((.+?)\.#",$ligne,$re)){
                return ($re[1]/60);
            }
        }
    }


    public function POSTFIX_MULTI_INSTANCES_LIST():array{
        if(!isset($GLOBALS["MEM_postmulti"])){$GLOBALS["MEM_postmulti"]=null;}
        if($GLOBALS["MEM_postmulti"]<>null){$postmulti=$GLOBALS["MEM_postmulti"];}else{$postmulti=$this->find_program("postmulti");$GLOBALS["MEM_postmulti"]=$postmulti;}
        $instance=array();
        if(strlen($postmulti)<3){return array();}
        exec("$postmulti -l 2>&1",$results);
        foreach ($results as $num=>$ligne){
            if(preg_match("#^(.+?)\s+([a-z\-])\s+(.+)#",$ligne,$re)){
                $re[1]=trim($re[1]);
                if(preg_match("#postfix-(.+)#",$re[1],$ri)){
                    $instance[$ri[1]]=$ri[1];
                }
            }
        }

        return $instance;
    }

    public function ISMemoryHiger1G(){
        if(isset($GLOBALS["ISMemoryHiger1G"])){return $GLOBALS["ISMemoryHiger1G"];}
        $ini=new iniFrameWork();
        $ini->loadFile("/etc/artica-postfix/performances.conf");
        $DisableFollowServiceHigerThan1G=$ini->get("PERFORMANCES","DisableFollowServiceHigerThan1G");
        if(!is_numeric($DisableFollowServiceHigerThan1G)){$DisableFollowServiceHigerThan1G=0;}
        if($DisableFollowServiceHigerThan1G==1){
            $GLOBALS["ISMemoryHiger1G"]=true;
            return true;
        }
        $mem=$this->MEM_TOTAL_INSTALLEE();
        if($mem>1012024){
            $GLOBALS["ISMemoryHiger1G"]=true;
            return true;
        }
        $GLOBALS["ISMemoryHiger1G"]=false;
        return false;
    }


    public function MEM_TOTAL_INSTALLEE(){
        if(isset($GLOBALS["MEM_TOTAL_INSTALLEE"])){return $GLOBALS["MEM_TOTAL_INSTALLEE"];}
        $results=1012029;
        $filename="/proc/meminfo";
        $f=explode("\n", @file_get_contents($filename));
        foreach ($f as $num=>$ligne){
            $ligne=trim($ligne);
            if($ligne==null){continue;}
            if(!preg_match("#MemTotal:\s+([0-9]+)#",$ligne,$re)){continue;}
            $results=$re[1];
            break;
        }
        $GLOBALS["MEM_TOTAL_INSTALLEE"]=$results;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TempMemInstalled",$results);
        return $results;
    }

    public function squid_get_alternate_port(){
        $f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
        foreach ($f as $num=>$ligne){
            if(preg_match("#(transparent|tproxy|intercept)#i", trim($ligne))){continue;}
            if(preg_match("#http_port\s+([0-9]+)$#", trim($ligne),$re)){return $re[1];}
            if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)$#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}

            if(preg_match("#http_port\s+([0-9]+)\s+#", trim($ligne),$re)){return $re[1];}
            if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)\s+#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
        }

    }
    public function squid_locate_generic_bin($program){
        $possibleDirs[]="/lib/squid3";
        $possibleDirs[]="/lib64/squid3";
        $possibleDirs[]="/lib/squid";
        $possibleDirs[]="/lib64/squid";
        $possibleDirs[]="/usr/lib/squid";
        $possibleDirs[]="/usr/lib64/squid";
        $possibleDirs[]="/usr/local/squid/libexec";
        foreach ($possibleDirs as $directory){
            if(is_file("$directory/$program")){return "$directory/$program";}
        }

    }



    function Mysql_TestDump($array,$container){
        foreach ($array as $a=>$b){
            if(preg_match("#Got error:#i", $b)){
                if(function_exists("events_repair")){events_repair("Dump failed $b,...");}
                if($GLOBALS["VERBOSE"]){echo "Dump failed $b,...\n";}
                return false;

            }
        }
        $array_text=@implode(" ", $array);
        if(!is_file($container)){
            $this->ToSyslog("Mysql_TestDump:: Dump failed $container, no such file $array_text");
            if($GLOBALS["VERBOSE"]){echo "Dump failed $container, no such file ...\n";}
            squid_admin_mysql(1, "Fatal Error: day: Dump failed $container, no such file",__FUNCTION__,__FILE__,__LINE__,"backup");
            return false;

        }

        $size=@filesize($container);

        if($size<100){
            if($GLOBALS["VERBOSE"]){echo "Dump failed $container size too low ( $size bytes) ...\n";}
            squid_admin_mysql(1, "Fatal Error: day: Dump failed $container size too low ( $size bytes) ... ",__FUNCTION__,__FILE__,__LINE__,"backup");
            @unlink($container);
            return;
        }


        return true;
    }

    private function squid_is31(){
        if(isset($GLOBALS["squid_is31"])){return $GLOBALS["squid_is31"];}
        $root_squid_version=$this->squid_version();
        if($GLOBALS["VERBOSE"]){echo "Version: $root_squid_version\n";}
        $data=null;
        $GLOBALS["squid_is31"]=false;
        $VER=explode(".",$root_squid_version);
        if($VER[0]<4){
            if($VER[1]<2){
                if($GLOBALS["VERBOSE"]){echo "$root_squid_version -> is 3.1.x\n";}
                $GLOBALS["squid_is31"]=true;return true;}
        }
        return false;

    }


    public function squid_internal_port(){
        $array=$this->squid_get_internal_addrport();
        return $array[1];

    }


    public function GET_HTTP_PROXY_STRING(){
        $squidbin=$this->LOCATE_SQUID_BIN();
        if(is_file($squidbin)){
            $SQUIDEnable=$this->GET_INFO("SQUIDEnable");
            if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
            if($SQUIDEnable==1){
                $NoCheckSquid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoCheckSquid"));
                if($NoCheckSquid==1){return null;}
                $squid_port=$this->squid_internal_port();
                return "http://127.0.0.1:$squid_port";
            }

        }
        $ini=new Bs_IniHandler();
        $datas=$this->GET_INFO("ArticaProxySettings");
        if(trim($datas)==null){return null;}
        $ini->loadString($datas);
        $ArticaProxyServerEnabled=strtolower($ini->_params["PROXY"]["ArticaProxyServerEnabled"]);
        if($ArticaProxyServerEnabled==null){return null;}
        if($ArticaProxyServerEnabled=="no"){return null;}
        if($ArticaProxyServerEnabled==0){return null;}
        $ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
        if($ArticaProxyServerName==null){return null;}
        $ArticaProxyServerPort=intval($ini->_params["PROXY"]["ArticaProxyServerPort"]);
        if($ArticaProxyServerPort==0){return null;}
        return "http://$ArticaProxyServerName:$ArticaProxyServerPort";

    }


    private function squid_get_internal_addrport(){
        $SquidMgrListenPort=trim($this->GET_INFO("SquidMgrListenPort"));
        if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
            $SquidBinIpaddr=$this->GET_INFO("SquidBinIpaddr");
            if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
            $http_port=$this->squid_get_alternate_port();

            if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
                $SquidBinIpaddr=$re[1];
                if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
                $http_port=$re[2];
            }

        }else{
            $SquidBinIpaddr="127.0.0.1";
            $http_port=$SquidMgrListenPort;
        }

        return array($SquidBinIpaddr,$http_port);
    }




    public function squidclient($cmd,$nocache=false){
        if(!isset($GLOBALS["makeQueryForce"])){$GLOBALS["makeQueryForce"]=false;}
        if($GLOBALS["makeQueryForce"]){$nocache=true;}

        if($cmd=="info"){
            if(is_file("/etc/artica-postfix/proxy.squid.client.info")){
                $time=$this->file_time_min("/etc/artica-postfix/proxy.squid.client.info");
                if($time<2){
                    return @file_get_contents("/etc/artica-postfix/proxy.squid.client.info");
                }
            }
        }


        if($cmd=="5min"){
            $cachefile=$this->file_time_min("/etc/artica-postfix/SquidClient5min");
            if($cachefile<5){return @file_get_contents("/etc/artica-postfix/SquidClient5min");}
        }


        $xtime=0;
        if(!is_file("/etc/artica-postfix/settings/Daemons/SquidClientCacheTime")){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidClientCacheTime", 4);
        }

        $CacheFile="/etc/artica-postfix/settings/Daemons/SquidClient{$cmd}";
        $CacheTime=0;

        if(!$nocache){
            if(isset($GLOBALS["squidclient"][$cmd])){return $GLOBALS["squidclient"][$cmd];}
            $CacheTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientCacheTime"));
            if($CacheTime==0){$CacheTime=4;}

            if(is_file($CacheFile)){
                $xtime=$this->file_time_min($CacheFile);
                if($xtime<$CacheTime){
                    return @file_get_contents($CacheFile);
                }
            }

        }

        $file=null;
        $function=null;
        $line=0;
        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();
            if(isset($trace[1])){
                if($file==null){$file=basename($trace[1]["file"]);}
                if($function==null){$function=$trace[1]["function"];}
                if($line==0){$line=$trace[1]["line"];}
            }

        }

        $logProcess=basename($file)." $function line $line (nocache=$nocache {$xtime}Mn/{$CacheTime}mn)";

        $cache_manager=new cache_manager();
        $data=$cache_manager->makeQuery($cmd,false,$logProcess);
        if($cache_manager->ok){
            $GLOBALS["squidclient"][$cmd]=$data;
            if(is_file($CacheFile)){@unlink($CacheFile);}
            @file_put_contents($CacheFile, $data);
            if($cmd=="5min"){
                if(is_file("/etc/artica-postfix/SquidClient5min")){
                    @unlink("/etc/artica-postfix/SquidClient5min");
                }
                @file_put_contents("/etc/artica-postfix/SquidClient5min",$data);
            }
            return $data;
        }

    }


    public function isSquidNTLM(){
        $f=explode("\n",@file_get_contents("/etc/squid3/authenticate.conf"));
        foreach ($f as $num=>$ligne){
            if(preg_match("#^auth_param\s+ntlm\s+program#", $ligne)){return true;}
        }
        return false;

    }




    public function squid_get_system_info(){

        $results=explode("\n",$this->squidclient("info"));

        $path=null;

        foreach ($results as $ligne){
            if(preg_match("#^(.*?):$#", trim($ligne),$re)){
                $title=$re[1];
                continue;
            }

            if(preg_match("#\s+(.*?):\s+(.+)#", $ligne,$re)){
                $sub=$re[1];
                $values=trim($re[2]);
            }
            if(strpos($ligne, ':')==0){
                if(preg_match("#\s+([0-9]+)\s+(.+)#", $ligne,$re)){
                    $sub=trim($re[2]);
                    $values=trim($re[1]);
                }
            }

            if($title==null){continue;}
            if($sub==null){continue;}
            $ARRAY[$title][$sub]=$values;


        }

        return $ARRAY;

    }


    public function squid_get_cache_infos(){
        $path=null;
        $array=array();
        $MAINCONTENT=$this->squidclient("storedir");

        if($GLOBALS["VERBOSE"]){echo "******** storedir: ".strlen($MAINCONTENT)." Bytes ********\n";}
        $results=explode("\n",$MAINCONTENT);
        if($GLOBALS["VERBOSE"]){echo "******** storedir: ".count($results)." Rows ********\n";}
        if($GLOBALS["VERBOSE"]){
            if(count($results)<10){echo "################## \n\n$MAINCONTENT\n\n##################\n";}
        }

        foreach ($results as $index=>$line){
            if(preg_match("#Connection refused#",$line)){
                writelogs_framework($line,__FUNCTION__,__LINE__);;
                return;
            }



            if(preg_match("#Shared Memory Cache#", $line)){
                $path="Memory";
                $array[$path]["index"]=0;
                $array[$path]["PARTITION"]="Memory";
                $array[$path]["DIRPART_INFO"]=null;
                $array[$path]["FULL_SIZE"]=null;
            }

            if(preg_match("#Store Directory\s+\#([0-9]+).+?:\s+(.+)#",$line,$re)){
                if($GLOBALS["VERBOSE"]){echo "Store Directory -> {$re[2]}\n";}
                $path=trim($re[2]);
                $array[$path]["index"]=$re[1];
                $array[$path]["PARTITION"]=$this->DIRPART_OF($path);
                $array[$path]["DIRPART_INFO"]=$this->DIRPART_INFO($path);
                $array[$path]["FULL_SIZE"]=$this->DIRSIZE_BYTES($path);
                continue;
            }

            if($path==null){continue;}

            if(preg_match("#Maximum Size:\s+([0-9]+)#",$line,$re)){
                if($GLOBALS["VERBOSE"]){echo "$path -> Maximum Size:{$re[1]}\n";}
                $array[$path]["MAX"]=$re[1];
                continue;
            }

            if(preg_match("#Current Size:\s+([0-9]+)#",$line,$re)){
                if($GLOBALS["VERBOSE"]){echo "$path ->Current Size:{$re[1]} \"$line\"\n";}
                $array[$path]["CURRENT"]=$re[1];
                continue;
            }

            if(preg_match("#Current Size:\s+([0-9]+).*?KB\s+([0-9\.]+)%#",$line,$re)){
                if($GLOBALS["VERBOSE"]){echo "$path ->Current Size:{$re[1]} \"$line\"\n";}
                $array[$path]["CURRENT"]=$re[1];
                $array[$path]["POURC"]=$re[2];
                continue;
            }

            if(preg_match("#Percent Used:\s+([0-9\.]+)#",$line,$re)){
                if($GLOBALS["VERBOSE"]){echo "$path ->Percent Used:{$re[1]}\n";}
                $array[$path]["POURC"]=$re[1];
                continue;
            }

            if(preg_match("#ERR_ACCESS_DENIED#",$line)){
                writelogs_framework("ACCESS DENIED",__FUNCTION__,__LINE__);;
                break;
            }



            if($GLOBALS["VERBOSE"]){echo "NOT DETECTED $line\n";}

        }
        return $array;

    }


    public function MYSQL_SOCKET_PATH(){
        $cnf=$this->MYSQL_MYCNF_PATH();
        $ini=new iniFrameWork($cnf);
        $return=$ini->get("mysqld","socket");
        if(trim($return==null)){return "/var/run/mysqld/mysqld.sock";}
    }

    public function MYSQL_MYCNF_PATH(){
        if(is_file('/etc/mysql/my.cnf')){return('/etc/mysql/my.cnf');}
        if(is_file('/etc/my.cnf')){return '/etc/my.cnf';}

    }

    public function MYSQL_DATADIR(){
        return $this->MYSQL_DATA_DIR();

    }



    public function FSTAB_GETDEV($targetPointPath){
        $datas=explode("\n",@file_get_contents("/etc/fstab"));
        foreach ($datas as $num=>$ligne){
            if(strpos($ligne,$targetPointPath)>0){
                if(preg_match("#(.+?)\s+#",$ligne,$re)){
                    return $re[1];
                }}
        }

    }


    public function FSTAB_QUOTA($dev,$enabled=0){
        $rows=explode("\n",@file_get_contents("/etc/fstab"));
        $dev_pattern=str_replace("/","\/",$dev);
        if(!is_array($rows)){writelogs_framework("no rows, abort",__FUNCTION__,__FILE__);return ;}
        foreach ($rows as $index=>$line){
            if(preg_match("#$dev_pattern\s+(.+?)\s+(.*?)\s+(.*?)\s+(.+)#",$line,$re)){
                $row_index=$index;
                $mount_path=$re[1];
                $type=$re[2];
                $option=$re[3];
                $other=$re[4];
                break;
            }
        }
        if($mount_path==null){return;}
        $options=explode(",",$option);
        foreach ($options as $i=>$token){$tokens[$token]=true;}
        writelogs_framework("OPTIONS=$option mount on $mount_path",__FUNCTION__,__FILE__);
        unset($tokens["usrquota"]);

        if($enabled==0){
            unset($tokens["usrquota"]);
            unset($tokens["usrjquota=aquota.user"]);
            unset($tokens["grpjquota=aquota.group"]);
            unset($tokens["jqfmt=vfsv"]);
            unset($tokens["jqfmt=vfsv0"]);

        }else{
            $tokens["usrjquota=aquota.user"]=true;
            $tokens["grpjquota=aquota.group"]=true;
            $tokens["jqfmt=vfsv0"]=true;
        }
        foreach ($tokens as $token=>$i){$final[]=$token;}
        $new_options=implode(",",$final);
        writelogs_framework("$dev\t$mount_path\t$type\t$new_options\t$other",__FUNCTION__,__FILE__,__LINE__);
        reset($rows);
        $rows[$row_index]="$dev\t$mount_path\t$type\t$new_options\t$other";
        @file_put_contents("/etc/fstab",@implode("\n",$rows));
        $mount=$this->find_program("mount");
        writelogs_framework("$mount -o remount $dev",__FUNCTION__,__FILE__,__LINE__);
        exec("$mount -o remount $dev");
        $quotacheck=$this->find_program("quotacheck");
        if(is_file($quotacheck)){shell_exec("$quotacheck -afgum &");}
        $quotaon=$this->find_program("quotaon");
        if(is_file($quotaon)){shell_exec("$quotaon -avug &");}


    }


    public function grep_php($filename,$maxnumLines=0,$pattern=null){
        if($pattern==null){return $this->tail_php($filename,$maxnumLines);}
        if(!is_file($filename)){
            if($GLOBALS["DEBUG_GREP"]){echo "$filename no such file\n";}
            return array();
        }
        $lines=array();
        $fp = @fopen($filename, "r");
        if(!$fp){
            if($GLOBALS["DEBUG_GREP"]){echo "$filename BAD FD\n";}
            return array();}

        $t=array();
        while(!feof($fp)){
            $line = trim(fgets($fp, 4096));
            $line=str_replace("\r\n", "", $line);
            $line=str_replace("\n", "", $line);
            $line=str_replace("\r", "", $line);
            if(!preg_match("#$pattern#i", $line)){continue;}
            $t[]=$line;
        }

        if(count($t)==0){return array();}
        $RESULTS=array();
        if($maxnumLines>0){
            rsort($t);

            for($i=0;$i<$maxnumLines;$i++){
                if($GLOBALS["DEBUG_GREP"]){echo "{$t[$i]} OK\n";}
                $RESULTS[]=$t[$i];
            }

        }


        @fclose($fp);
        return $RESULTS;
    }


    function tail_php($file, $numLines = 100,$pattern=null){
        if($pattern<>null){return $this->grep_php($file,$numLines,$pattern);}
        $fp = fopen($file, "r");
        $chunk = 4096;
        $fs = sprintf("%u", filesize($file));
        $max = (intval($fs) == PHP_INT_MAX) ? PHP_INT_MAX : filesize($file);
        $f=array();
        for ($len = 0; $len < $max; $len += $chunk) {
            $seekSize = ($max - $len > $chunk) ? $chunk : $max - $len;

            fseek($fp, ($len + $seekSize) * -1, SEEK_END);
            $data = fread($fp, $seekSize);
            $f[]=$data;
            if (substr_count($data, "\n") >= $numLines + 1) {
                preg_match("!(.*?\n){".($numLines)."}$!", $data, $match);
                fclose($fp);
                return $f;
            }
        }
        fclose($fp);
        return $f;
    }





    public function CHANGE_HOSTNAME($hostname){
        $pprefix="/usr/share/artica-postfix";
        $todev=">/dev/null 2>&1";
        if(trim($hostname)==null){return;}
        @unlink("/etc/artica-postfix/FULL_HOSTNAME");
        $hostname=str_replace(" ", "",$hostname);
        if(strpos($hostname, ".")>0){
            $tbl=explode(".",$hostname);
            unset($tbl[0]);
            $domainName=@implode(".", $tbl);
        }

        $domainName=str_replace("(", "", $domainName);
        $domainName=str_replace(")", "", $domainName);
        if($domainName==null){$domainName="localhost.localdomain";}

        @file_put_contents("/etc/hostname", $hostname);
        @file_put_contents("/etc/HOSTNAME", $hostname);
        @file_put_contents("/etc/mailname", $hostname);
        @file_put_contents("/etc/artica-postfix/FULL_HOSTNAME", $hostname);

        $sysctl=$this->find_program("sysctl");
        $hostnamebin=$this->find_program("hostname");
        $nohup=$this->find_program("nohup");
        $php5=$this->LOCATE_PHP5_BIN();
        $hostnamectl=$this->find_program("hostnamectl");

        shell_exec("$nohup $php5 $pprefix/exec.virtuals-ip.php --hosts $todev &");

        if(strlen($sysctl)>3){
            shell_exec("$sysctl -w kernel.domainname=$domainName $todev");
            shell_exec("$sysctl -w kernel.hostname=$hostname $todev");
            shell_exec("$sysctl -p $todev");
        }

        if(strlen($hostnamectl)>1){
            shell_exec("$hostnamectl set-hostname $hostname");
        }
        if(strlen($hostnamebin)>3){
            shell_exec("$hostnamebin $hostname $todev");
        }

        if(is_file("/etc/init.d/squid")) {
            shell_exec("$nohup $php5 $pprefix/exec.squid.global.access.php --common $todev &");
        }
    }


    public function GET_QUOTA_MOUNTED(){
        $mount=$this->find_program("mount");
        exec("$mount 2>&1",$results);
        foreach ($results as $line){
            if(!preg_match("#(.+?)\s+on\s+(.+?)\s+.+?usrjquota#",$line,$re)){continue;}
            $rr[$re[2]]=true;
        }

        return $rr;

    }

    function uptime(){

        $e=file('/proc/uptime');
        preg_match('#(.*) (.*)#', $e[0], $e);
        $t=time();
        $Since=$t-intval($e[1]);
        if($GLOBALS["VERBOSE"]){echo "First integer {$e[1]} -> ".intval($e[1])." ($Since)\n";}
        return $this->distanceOfTimeInWords($Since,time());

    }

    function TARGZ_TEST_CONTAINER($container,$nogzip=false,$output=false){
        if(!is_file($container)){return false;}
        $size=@filesize($container);
        $resultsZ=array();
        if($size<100){return false;}
        $tar=$this->find_program("tar");
        $z="z";
        if($nogzip){$z=null;}
        $cmdline="$tar {$z}tvf $container";
        if($output){echo "Starting......: ".date("H:i:s")." $cmdline\n";}
        exec("$cmdline 2>&1",$resultsZ);


        foreach($resultsZ as $b){
            if($output){echo "Starting......: ".date("H:i:s")." $b\n";}

            if(preg_match("#Syntax error#",$b)){
                if(function_exists("events")){events("TARGZ_TEST_CONTAINER:: $cmdline gzip: $b");}
                if($GLOBALS["VERBOSE"]){echo "gzip: $b\n";}
                return false;
            }

            if(preg_match("#gzip: stdin: unexpected end of file#", $b)){
                if(function_exists("events")){events("TARGZ_TEST_CONTAINER:: $cmdline gzip: stdin: unexpected end of file");}
                if($GLOBALS["VERBOSE"]){echo "gzip: stdin: unexpected end of file\n";}
                return false;
            }

            if(preg_match("#gzip: stdin: not in gzip format#", $b)){
                if(function_exists("events")){events("TARGZ_TEST_CONTAINER:: $cmdline gzip: stdin: not in gzip format");}
                if($GLOBALS["VERBOSE"]){echo "gzip: stdin: not in gzip format\n";}
                return false;
            }

            if(preg_match("#does not look like a tar#", $b)){
                if(function_exists("events")){events("TARGZ_TEST_CONTAINER:: does not look like a tar");}
                $GLOBALS["TARGZ_TEST_CONTAINER_ERROR"]=@implode("\n", $resultsZ);
                if($GLOBALS["VERBOSE"]){echo "tar $container failed $b\n";}
                return false;
            }

            if(preg_match("#tar: Error#", $b)){
                if(function_exists("events")){events("TARGZ_TEST_CONTAINER:: $b");}
                $GLOBALS["TARGZ_TEST_CONTAINER_ERROR"]=@implode("\n", $resultsZ);
                if($GLOBALS["VERBOSE"]){echo "tar $container failed $b\n";}
                return false;
            }
        }
        if($output){echo "Starting......: ".date("H:i:s")." $container correct\n";}
        return true;

    }
    function distanceOfTimeInWords_text($fromTime, $toTime = 0, $showLessThanAMinute = true) {
        $distanceInSeconds = round(abs($toTime - $fromTime));
        $distanceInMinutes = round($distanceInSeconds / 60);

        if ( $distanceInMinutes <= 1 ) {
            if ( !$showLessThanAMinute ) {
                return ($distanceInMinutes == 0) ? 'less than a minute' : '1 minute';
            } else {
                if ( $distanceInSeconds < 5 ) {
                    return 'Less than 5 seconds ('.$distanceInSeconds.'s)';
                }
                if ( $distanceInSeconds < 10 ) {
                    return 'Less than 10 seconds ('.$distanceInSeconds.'s)';
                }
                if ( $distanceInSeconds < 20 ) {
                    return 'Less than 20 seconds ('.$distanceInSeconds.'s) ';
                }
                if ( $distanceInSeconds < 40 ) {
                    return 'About half a minute ('.$distanceInSeconds.'s)';
                }
                if ( $distanceInSeconds < 60 ) {
                    return 'Less than a minute';
                }

                return '1 minute';
            }
        }
        if ( $distanceInMinutes < 45 ) {
            return $distanceInMinutes . ' minutes';
        }
        if ( $distanceInMinutes < 90 ) {
            return 'About 1 hour';
        }
        if ( $distanceInMinutes < 1440 ) {
            return 'About ' . round(floatval($distanceInMinutes) / 60.0) . ' hours';
        }
        if ( $distanceInMinutes < 2880 ) {
            return '1 day';
        }
        if ( $distanceInMinutes < 43200 ) {
            return 'About ' . round(floatval($distanceInMinutes) / 1440) . ' days';
        }
        if ( $distanceInMinutes < 86400 ) {
            return 'About 1 month';
        }
        if ( $distanceInMinutes < 525600 ) {
            return round(floatval($distanceInMinutes) / 43200) . ' months';
        }
        if ( $distanceInMinutes < 1051199 ) {
            return 'About 1 year';
        }

        return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' years';
    }

    function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
        $distanceInSeconds = round(abs($toTime - $fromTime));
        $distanceInMinutes = round($distanceInSeconds / 60);

        if ( $distanceInMinutes <= 1 ) {
            if ( !$showLessThanAMinute ) {
                return ($distanceInMinutes == 0) ? 'less than a minute' : '1 {minute}';
            } else {
                if ( $distanceInSeconds < 5 ) {
                    return '{lessthan} 5 {seconds} ('.$distanceInSeconds.'s)';
                }
                if ( $distanceInSeconds < 10 ) {
                    return '{lessthan} 10 {seconds} ('.$distanceInSeconds.'s)';
                }
                if ( $distanceInSeconds < 20 ) {
                    return '{lessthan} 20 {seconds} ('.$distanceInSeconds.'s) ';
                }
                if ( $distanceInSeconds < 40 ) {
                    return '{abouttime} {halfaminute} ('.$distanceInSeconds.'s)';
                }
                if ( $distanceInSeconds < 60 ) {
                    return '{lessthanaminute}';
                }

                return '1 minute';
            }
        }
        if ( $distanceInMinutes < 45 ) {
            return $distanceInMinutes . ' {minutes}';
        }
        if ( $distanceInMinutes < 90 ) {
            return '{abouttime} 1 {hour}';
        }
        if ( $distanceInMinutes < 1440 ) {
            return '{abouttime} ' . round(floatval($distanceInMinutes) / 60.0) . ' {hours}';
        }
        if ( $distanceInMinutes < 2880 ) {
            return '1 {day}';
        }
        if ( $distanceInMinutes < 43200 ) {
            return '{abouttime} ' . round(floatval($distanceInMinutes) / 1440) . ' {days}';
        }
        if ( $distanceInMinutes < 86400 ) {
            return '{abouttime} 1 {month}';
        }
        if ( $distanceInMinutes < 525600 ) {
            return round(floatval($distanceInMinutes) / 43200) . ' {months}';
        }
        if ( $distanceInMinutes < 1051199 ) {
            return '{abouttime} 1 {year}';
        }

        return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' {years}';
    }


    public function FSTAB_ACL($dev,$aclenabled=0){

        $rows=explode("\n",@file_get_contents("/etc/fstab"));

        $dev_pattern=str_replace("/","\/",$dev);
        if(!is_array($rows)){writelogs_framework("no rows, abort",__FUNCTION__,__FILE__);return ;}
        foreach ($rows as $index=>$line){
            if(preg_match("#$dev_pattern\s+(.+?)\s+(.*?)\s+(.*?)\s+(.+)#",$line,$re)){
                $row_index=$index;
                $mount_path=$re[1];
                $type=$re[2];
                $option=$re[3];
                $other=$re[4];
                break;
            }
        }


        if($mount_path==null){return;}
        $options=explode(",",$option);

        foreach ($options as $i=>$token){$tokens[$token]=true;}

        writelogs_framework("OPTIONS=$option mount on $mount_path",__FUNCTION__,__FILE__);
        if($aclenabled==0){
            unset($tokens["acl"]);
            unset($tokens["user_xattr"]);
            $tune2fs_cmd="-acl";
        }else{
            $tokens["acl"]=true;
            $tokens["user_xattr"]=true;
            $tune2fs_cmd="+acl";
        }
        foreach ($tokens as $token=>$i){$final[]=$token;}

        $new_options=implode(",",$final);
        writelogs_framework("$dev\t$mount_path\t$type\t$new_options\t$other",__FUNCTION__,__FILE__,__LINE__);
        reset($rows);
        $rows[$row_index]="$dev\t$mount_path\t$type\t$new_options\t$other";
        @file_put_contents("/etc/fstab",@implode("\n",$rows));
        $mount=$this->find_program("mount");
        exec("$mount -o remount $dev");
        $tune2fs=$this->find_program("tune2fs");
        if($tune2fs<>null){
            exec("$tune2fs -o user_xattr,acl $dev 2>&1",$rs);
            writelogs_framework("$tune2fs -o user_xattr,acl $dev",__FUNCTION__,__FILE__,__LINE__);
            writelogs_framework(implode(",",$rs),__FUNCTION__,__FILE__,__LINE__);

        }

    }


    public function DISK_GET_PARTNUMBER($dev_path){
        $file=$this->find_program("file");
        $tmp=$this->FILE_TEMP();
        shell_exec("$file -sL $dev_path >$tmp 2>&1");
        shell_exec("$file -sL $dev_path >$tmp 2>&1");
        if($GLOBALS["VERBOSE"]){echo "unix/DISK_GET_PARTNUMBER $file -sL $dev_path >$tmp 2>&1\n";}
        $datas=explode(";",trim(@file_get_contents($tmp)));
        @unlink($tmp);
        $pt=0;
        foreach ($datas as $ligne) {
            if(preg_match("#partition\s+([0-9]+)\s+:#", $ligne,$re)){
                $ptint=intval($re[1]);
                if($ptint>$pt){$pt=$ptint;}
            }
        }

        return $pt;

    }


    public function DISK_GET_TYPE_BYFILE($dev_path){
        $file=$this->find_program("file");
        $tmp=$this->FILE_TEMP();
        shell_exec("$file -sL  $dev_path >$tmp 2>&1");
        if($GLOBALS["VERBOSE"]){echo "unix/DISK_GET_TYPE_BYFILE $file -sL  $dev_path >$tmp 2>&1\n";}
        $datas=explode("\n",@file_get_contents($tmp));
        @unlink($tmp);
        foreach ($datas as $ligne) {
            if(preg_match("#data 'CDROM'#",$ligne)){return "iso9660";}
            if(preg_match("#Linux rev [0-9\.]+\s+(.+?)\s+filesystem data#", $ligne,$re)){return $re[1];}
            if(preg_match('#OEM-ID\s+"NTFS#',$ligne)){return "ntfs";}
            if($GLOBALS["VERBOSE"]){echo "unix/DISK_GET_TYPE_BYFILE $ligne no match...\n";}
        }
    }


    public function DISK_GET_TYPE($dev_path){
        $fs=$this->DISK_GET_TYPE_BYFILE($dev_path);
        if($fs<>null){return $fs;}
        $dev_path=str_replace("/","\/",$dev_path);
        $blkid=$this->find_program("blkid");
        $tmp=$this->FILE_TEMP();
        shell_exec("$blkid >$tmp 2>&1");
        $datas=explode("\n",@file_get_contents($tmp));
        @unlink($tmp);
        foreach ($datas as $ligne) {
            if(preg_match("#^$dev_path.+?TYPE=\"(.+?)\"#",$ligne,$re)){
                return $re[1];
            }

        }
    }

}

function BuildingExecStatus($text,$pourc){
    $ini=new iniFrameWork("/usr/share/artica-postfix/ressources/logs/exec.status.ini");
    $ini->set("status","text","$text");
    $ini->set("status","pourc","$pourc");
    $ini->saveFile("/usr/share/artica-postfix/ressources/logs/exec.status.ini");
    @chmod("/usr/share/artica-postfix/ressources/logs/exec.status.ini",0755);
    error_log(__FUNCTION__.":: status $pourc% $text");

}
function BuildingExecRightStatus($text,$pourc){
    $file="/usr/share/artica-postfix/ressources/logs/exec-right.status.ini";
    $ini=new iniFrameWork($file);
    $ini->set("status","text","$text");
    $ini->set("status","pourc","$pourc");
    $ini->saveFile($file);
    @chmod($file,0755);
}




function cpulimit($forced=0){
    return;
    $unix=new unix();
    if($GLOBALS["cpulimit_path"]==null){
        $GLOBALS["cpulimit_path"]=$unix->find_program("cpulimit");
    }
    if($GLOBALS["cpulimit_path"]==null){return null;}
    if($forced==0){
        $cpu=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cpulimit"));
        if($cpu==null){$cpu=0;}
    }else{
        $cpu=$forced;
    }
    if($cpu==0){return null;}
    $pid=getmypid();
    $cmd="{$GLOBALS["cpulimit_path"]} -p $pid -l $cpu -z >>/var/log/cpulimit 2>&1 &";
    if($GLOBALS["VERBOSE"]){writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);}
    shell_exec($cmd);

}


function GET_INFO_DAEMON($key){
    if(function_exists("apc_store")){$value=apc_fetch($key);if($value<>null){return trim($value);}}
    if(isset($GLOBALS["GET_INFO_DAEMON"][$key])){
        if($GLOBALS["GET_INFO_DAEMON"][$key]<>null){return $GLOBALS["GET_INFO_DAEMON"][$key];}
    }
    $result=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("$key"));
    $GLOBALS["GET_INFO_DAEMON"][$key]=$result;
    if(function_exists("apc_store")){apc_store($key, $result);}
    return $result;
}

function cpulimitProcessName($processName){
    $unix=new unix();
    $cpulimit=$unix->find_program("cpulimit");
    if($cpulimit==null){return null;}
    $cpu=GET_INFO_DAEMON('cpulimit');
    if($cpu==null){$cpu=0;}
    if($cpu==0){return null;}
    $cmd="$cpulimit -e $processName -l $cpu &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function crc32_file($filename){
    if(!is_file($filename)){return "";}
    $patten=hash_file('CRC32', $filename);
    if(!$patten){return "";}
    return $patten;
}
function writelogs_framework($text=null,$function=null,$file_source=null,$line=null){
    if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir");}
    if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; }
    if(!isset($GLOBALS["VERBOSEWEB"])){$GLOBALS["VERBOSEWEB"]=false;}
    if($file_source==null){$file_source= __FILE__ ;}
    $prefix = "[DAEMON]";

    if(function_exists("posix_getuid")) {
        if (posix_getuid() == 0) {
            $prefix = "[DAEMON]";
        } else {
            $prefix = "[CONSOLE]";
        }
    }
    if(function_exists("getmypid")){$pid=getmypid();}
    $date=date('H:i:s');
    if($line<>null){$line=" line $line";}
    $file_source=str_replace("/usr/share/artica-postfix/","",$file_source);
    $file_source=str_replace("/home/dtouzeau/developpement/artica-postfix/","",$file_source);
    if($GLOBALS["VERBOSE"]){
        if($GLOBALS["VERBOSEWEB"]){VERBOSE("[$pid] $prefix::$function:: $text in $file_source", $line);}
        if(!$GLOBALS["VERBOSEWEB"]){echo "[$pid] $prefix::$function:: $text in $file_source$line\n";}
    }
    $text="$date [$pid] $prefix::$function:: $text in $file_source$line";
    writeFrameOtherlogs("{$GLOBALS["ARTICALOGDIR"]}/framework.debug",$text);
}
function writeFrameOtherlogs($file,$text=null){
    if(!is_dir(dirname($file))){@mkdir(dirname($file));}
    $logFile=$file;
    if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
    if (is_file($logFile)) {
        $size=filesize($logFile);
        if($size>100000){unlink($logFile);}
    }
    $logFile=str_replace("//","/",$logFile);
    $f = @fopen($logFile, 'a');

    @fwrite($f, "$text\n");
    @fclose($f);
}
class WakeOnLanClass{
    private $nic;
    var $error=array();
    public function __construct($mac){
        $this->nic = fsockopen("udp://255.255.255.255", 9,$errno, $errstr);
        if( !$this->nic ){
            fclose($this->nic);
            $this->error[]="fsockopen:\nERROR $errno \nudp 255.255.255.255\n {failed} $errstr";
            return false;
        }
        else{
            fwrite($this->nic, $this->pacquet($mac));
            fclose($this->nic);
            $this->error[]="{success}";
            return true;
        }
    }

    private function pacquet($Mac){
        $packet = "";
        for($i = 0; $i < 6; $i++){$packet .= chr(0xFF);}
        for ($j = 0; $j < 16; $j++){
            for($i = 0; $i < 12; $i=$i + 2){$packet .= chr(hexdec(substr($Mac, $i, 2)));}
        }
        return $packet;
    }
}

class debuglogs{
    function __construct(){}

    public function events($text,$logFile,$phplog=false){
        $unix=new unix();
        $unix->events($text,$logFile,$phplog);
    }

}


function ini_set_verbosed(){
    ini_set('html_errors',0);
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string','');
    ini_set('error_append_string','');
}



function system_admin_events($text=null,$function=null,$file=null,$line=null,$category=null,$taskid=0):bool{
    $severity=1;
    if (is_numeric($text)){
        $severity=$text;
        $text=$function;
        $file=$line;
        $line=$category;
    }
    return squid_admin_mysql($severity,$text,null,$file,$line);
}

function system_failover_events($text=null,$function=null,$file=null,$line=null,$category=null,$taskid=0){
    if($text==null){return;}
    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            if($file==null){$file=basename($trace[1]["file"]);}
            if($function==null){$function=$trace[1]["function"];}
            if($line==0){$line=$trace[1]["line"];}
        }

    }

    $mem=round(((memory_get_usage()/1024)/1000),2);
    $array_load=sys_getloadavg();
    $internal_load=$array_load[0];
    $text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
    if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
    if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
    if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
    $array["zdate"]=date("Y-m-d H:i:s");
    $array["text"]=$text;
    $array["function"]=$function;
    $array["file"]=basename($file);
    $array["line"]=$line;
    $array["pid"]=getmypid();
    $array["category"]=$category;
    $array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
    if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}
    $serialize=serialize($array);
    $md5=md5($serialize);
    if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/system_failover_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/system_failover_events",0755,true);}
    @file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/system_failover_events/$md5.log", $serialize);

}
function rotate_events($text=null,$function=null,$file=null,$line=null,$category=null,$taskid=0){
    if($text==null){return;}
    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            if($file==null){$file=basename($trace[1]["file"]);}
            if($function==null){$function=$trace[1]["function"];}
            if($line==0){$line=$trace[1]["line"];}
        }

    }

    $mem=round(((memory_get_usage()/1024)/1000),2);
    $array_load=sys_getloadavg();
    $internal_load=$array_load[0];
    $text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
    if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
    if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
    if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
    $array["zdate"]=date("Y-m-d H:i:s");
    $array["text"]=$text;
    $array["function"]=$function;
    $array["file"]=basename($file);
    $array["line"]=$line;
    $array["pid"]=getmypid();
    $array["category"]=$category;
    $array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
    if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}

    $serialize=serialize($array);
    $md5=md5($serialize);
    if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/rotate_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/rotate_events",0755,true);}
    @file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/rotate_events/$md5.log", $serialize);

}

function hotspot_admin_mysql($severity,$subject,$text,$file=null,$line=0){
    if(!is_numeric($line)){$line=0;}
    // 0 -> RED, 1 -> WARN, 2 -> INFO
    $function=null;
    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            if($file==null){
                $file=basename($trace[1]["file"]);
            }
            $function=$trace[1]["function"];
            if($line==0){
                $line=$trace[1]["line"];
            }
        }

    }


    if(function_exists("syslog")){
        $file=basename($file);
        $LOG_SEV=LOG_INFO;
        openlog($file, LOG_PID , LOG_SYSLOG);
        syslog($LOG_SEV, $subject);
        closelog();
    }



    if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}


    $array["zdate"]=date("Y-m-d H:i:s");
    $array["subject"]=$subject;
    $array["text"]=$text;
    $array["severity"]=$severity;
    $array["function"]=$function;
    $array["file"]=basename($file);
    $array["line"]=$line;
    $array["pid"]=getmypid();
    $array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
    $serialize=serialize($array);
    $md5=md5($serialize);
    if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/hotspot_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/hotspot_admin_mysql",0755,true);}
    @file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/hotspot_admin_mysql/$md5.log", $serialize);

}
function rdpproxy_admin_mysql($severity,$subject,$text,$file=null,$line=0){
    if(!is_numeric($line)){$line=0;}
    $function=null;
    // 0 -> RED, 1 -> WARN, 2 -> INFO

    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            if($file==null){
                $file=basename($trace[1]["file"]);
            }
            $function=$trace[1]["function"];
            if($line==0){
                $line=$trace[1]["line"];
            }
        }

    }


    if(function_exists("syslog")){
        $file=basename($file);
        $LOG_SEV=LOG_INFO;
        openlog($file, LOG_PID , LOG_SYSLOG);
        syslog($LOG_SEV, $subject ." [$line]");
        closelog();
    }

    $unix=new unix();
    $unix->events($subject,"/var/log/squid/artica.watchdog.log",false,$function,$line,$file);

    if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}


    $array["zdate"]=date("Y-m-d H:i:s");
    $array["subject"]=$subject;
    $array["text"]=$text;
    $array["severity"]=$severity;
    $array["function"]=$function;
    $array["file"]=basename($file);
    $array["line"]=$line;
    $array["pid"]=getmypid();
    $array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
    $serialize=serialize($array);
    $md5=md5($serialize);
    if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/rdpproxy_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/rdpproxy_admin_mysql",0755,true);}
    @file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/rdpproxy_admin_mysql/$md5.log", $serialize);
}



function blackwhite_admin_mysql($severity,$subject,$text,$file=null,$line=0){
    if(!is_numeric($line)){$line=0;}
    // 0 -> RED, 1 -> WARN, 2 -> INFO

    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            if($file==null){
                $file=basename($trace[1]["file"]);
            }
            $function=$trace[1]["function"];
            if($line==0){
                $line=$trace[1]["line"];
            }
        }

    }


    if(function_exists("syslog")){
        $file=basename($file);
        $LOG_SEV=LOG_INFO;
        openlog($file, LOG_PID , LOG_SYSLOG);
        syslog($LOG_SEV, $subject ." [$line]");
        closelog();
    }

    if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}


    $array["zdate"]=date("Y-m-d H:i:s");
    $array["subject"]=$subject;
    $array["text"]=$text;
    $array["severity"]=$severity;
    $array["function"]=$function;
    $array["file"]=basename($file);
    $array["line"]=$line;
    $array["pid"]=getmypid();
    $array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
    $serialize=serialize($array);
    $md5=md5($serialize);
    if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/blackwhite_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/blackwhite_admin_mysql",0755,true);}
    @file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/blackwhite_admin_mysql/$md5.log", $serialize);





}
function vsftpd_admin_mysql($severity,$subject,$text,$file=null,$line=0){
    squid_admin_mysql($severity, "VSFTPD: $subject", $text,$file,$line);
}
function time_admin_mysql_events($text,$function,$line){
    $logFile="/var/log/artica.squid.smtp.log";
    $maxSize=900000;
    if(!is_file($logFile)){@touch($logFile);}

    $size=@filesize($logFile);
    if($size>$maxSize){
        @unlink($logFile);
        @touch($logFile);
        @chmod($logFile, 0777);
    }

    $h = @fopen($logFile, 'a');
    $line=date("Y-m-d H:i:s")." ".getmypid()."] $text ($function/$line)\n";
    @fwrite($h,$line);
    @fclose($h);

}










function nginx_admin_mysql($severity,$subject,$text,$file=null,$line=0){squid_admin_mysql($severity, $subject, $text,$file,$line);}

function squid_admin_mysql_FormatBytes($kbytes){

    $spacer=" ";


    if($kbytes>1048576){
        $value=round($kbytes/1048576, 2);
        if($value>1000){
            $value=round($value/1000, 2);
            return "$value{$spacer}TB";
        }
        return "$value{$spacer}GB";
    }
    elseif ($kbytes>=1024){
        $value=round($kbytes/1024, 2);
        return "$value{$spacer}MB";
    }
    else{
        $value=round($kbytes, 2);
        return "$value{$spacer}KB";
    }
}
function squid_admin_hacluster($text):bool{
    if(!function_exists("syslog")){return false;}
    $LOG_SEV=LOG_INFO;
    openlog("hacluster-client", LOG_PID , LOG_SYSLOG);
    syslog($LOG_SEV, "$text");
    closelog();
    return true;

}
function __get_memory():float{
    $m=array();
    foreach(file('/proc/meminfo') as $ri) {
        $ri=trim($ri);
        if($ri==null){continue;}
        if(!preg_match("#^(.+?):\s+([0-9\.]+)#",$ri,$re)){continue;}
        $m[$re[1]] = $re[2];
    }
    return 100 - round(($m['MemFree'] + $m['Buffers'] + $m['Cached']) / $m['MemTotal'] * 100);
}

function squid_admin_mysql($severity=0,$subject=null,$text=null,$file=null,$line=0):bool{
    $unix           = new unix();
    $traces         = array();
    $basename       = "";
    if(!is_null($file)) {
        $basename = basename($file);
    }
    $function       = null;
    $debg           = array();
    $traces[]       = "Memory: ".__get_memory()."%";
    if(!isset($GLOBALS["DEBUG_NOTIFS"])){$GLOBALS["DEBUG_NOTIFS"]=false;}



    if(preg_match("#OVERLOADED_SYSTEM#",$subject)){
        if($text==null){
            $unix=new unix();
            $text=$unix->ps_mem_report();
        }
    }

    $subject=str_replace("\n"," ",$subject);
    if(!is_numeric($severity)){
        if($subject<>null){$text=$subject;}
        $subject=$severity;
        $severity=2;
    }
    if(strlen($subject)>250){
        $text=$subject."\n".$text;
        $subject=substr($subject,0,247);
    }


    if($line==0 AND $file==null) {
        if (function_exists("debug_backtrace")) {
            $trace = debug_backtrace();
            if (isset($trace[1])) {
                if (isset($trace[1]["file"])) {$file = basename($trace[1]["file"]);}
                if (isset($trace[1]["line"])) {$line = intval($trace[1]["line"]);}
            }

            foreach ($trace as $index=>$ligne){
                $sfile= "-";
                $sline= "-";
                $sfunc= "main()";
                if(isset($ligne["file"])){ $sfile=basename($ligne["file"]);}
                if(isset($ligne["line"])){$sline=$ligne["line"];}
                if(isset($ligne["function"])){$sfunc=$ligne["function"]."()";}
                $debg[]="$index] From  $sfile: $sfunc in line $sline";
            }


        }
    }


    $ServerRunSince=$unix->ServerRunSince();
    if($ServerRunSince<5){
        $severity=2;
        $text="Server running since {$ServerRunSince}mn During server startup first 5 minutes, some issues are normal.\nConsider them to be not important..\n$text";
    }

    if(!$unix->IsProductionTime()){
        if($severity==0){$severity=1;}
        if($severity==1){$severity=2;}
        $text="During non-production time, serverity as been decreased, and maintenance tasks increase computer load and issues.\n$text";
    }


    if(is_array($text)){
        $new_text=null;
        foreach ($text as $index=>$xline){
            $new_text=$new_text.$index.")$xline\n";
        }
        $text=$new_text;
    }

    if(isset($GLOBALS["CLASS_SOCKETS"])) {
        if (!isset($GLOBALS["HaClusterClient"])) {
            $GLOBALS["HaClusterClient"] = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        }
    }



    $PROXY_DAEMONS["exec.squid.watchdog.php"]="squid";
    $PROXY_DAEMONS["exec.squid.php"]="squid";
    if ($GLOBALS["HaClusterClient"] == 1) {
        $daemonname=$basename;
        if(isset($PROXY_DAEMONS[$basename])){$daemonname=$PROXY_DAEMONS[$basename];}
        squid_admin_hacluster("[$daemonname}]: $subject [L.$line]");
    }


    if(!is_numeric($line)){$line=0;}
    // 0 -> RED, 1 -> WARN, 2 -> INFO


    if($severity<2) {
        $error_get_last = error_get_last();
        if (isset($error_get_last["message"])) {
            $traces[] = "Last error {$error_get_last["message"]} line {$error_get_last["line"]} in file " .
                basename($error_get_last["file"]);

            if (function_exists("debug_backtrace")) {
                $trace = debug_backtrace();
                foreach ($trace as $index => $ligne) {
                    $file = basename($ligne["file"]);
                    $line = $ligne["line"];
                    $function = $ligne["function"];
                    $traces[] = "Line $line in $function() in file $file";
                }
            }

        }

        $text = $text . "\n" . @implode("\n", $traces) . "\n";
    }
    $TRANSFERT_FILE["exec.pdns.php"]="pdns";
    $TRANSFERT_FILE["exec.squid.watchdog.php"]="squid";


    $syslogfile=$file;
    if(function_exists("syslog")){
        if(isset($TRANSFERT_FILE[$basename])){$syslogfile=$TRANSFERT_FILE[$basename];}
        $LOG_SEV=LOG_INFO;
        openlog($syslogfile, LOG_PID , LOG_SYSLOG);
        syslog($LOG_SEV, $subject ." [$line]");
        closelog();
    }

    $md5=md5("$severity$subject$file".date("Y-m-d H:i"));
    if(isset($GLOBALS[$md5])){return true;}
    $GLOBALS[$md5]=true;
    $ServerRunSince=$unix->ServerRunSince()*60;
    $fromTime=time()-$ServerRunSince;
    $TargetDir="{$GLOBALS["ARTICALOGDIR"]}/squid_admin_mysql";

    $ServerRunSince_text    = $unix->distanceOfTimeInWords_text($fromTime,time());
    $array_mem              = getSystemMemInfo();
    $load                   = sys_getloadavg();
    $internal_load          = @implode(", ",$load);
    $VERSION                = trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
    $ServicePack            = 0;
    $NUTANIX_HOST           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NUTANIX_HOST"));
    $VMWARE_HOST            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_HOST"));

    if(is_file("/usr/share/artica-postfix/SP/$VERSION")) {
        $ServicePack = intval(@file_get_contents("/usr/share/artica-postfix/SP/$VERSION"));
    }

    if($NUTANIX_HOST==1) {
        $ifmem[] = "Nutanix Edition.";
    }
    if($VMWARE_HOST==1){
        $ifmem[] = "VMWare Edition.";
    }

    $ifmem[]="Artica Version......: $VERSION Service Pack $ServicePack";
    $ifmem[]="Memory Total........: ".squid_admin_mysql_FormatBytes($array_mem["MemTotal"]);
    $ifmem[]="Memory Free.........: ".squid_admin_mysql_FormatBytes($array_mem["MemFree"]);
    $ifmem[]="Server running since: $ServerRunSince_text";
    if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
    reset($GLOBALS["NETWORK_ALL_INTERFACES"]);
    foreach ($GLOBALS["NETWORK_ALL_INTERFACES"] as $Interface=>$ligne){
        if($Interface=="lo"){continue;}
        if($Interface=="tun0"){continue;}
        if(!isset($ligne["IPADDR"])){continue;}
        if($ligne["IPADDR"]=="0.0.0.0"){continue;}
        $ifmem[]="Interface $Interface: {$ligne["IPADDR"]}";
    }
    $text=$text."\r\n".
        "-------------------------------------------------\r\n".
        "Load: $internal_load\r\n".
        "-------------------------------------------------\r\n".
        @implode("\r\n",$ifmem)."\r\n".
        "-------------------------------------------------\r\n".
        @implode("\r\n",$debg)."\r\n";



    if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
    $array["zdate"]=date("Y-m-d H:i:s");
    $array["subject"]=$subject;
    $array["text"]=$text;
    $array["severity"]=$severity;
    $array["function"]=$function;
    $array["file"]=basename($file);
    $array["line"]=$line;
    $array["pid"]=getmypid();
    $array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
    $serialize=serialize($array);
    $md5=md5($serialize);
    if(!is_dir($TargetDir)){@mkdir($TargetDir,01777,true);}
    @chmod($TargetDir, 01777);
    @file_put_contents("$TargetDir/$md5.log", $serialize);
    return true;
}
function ufdbguard_admin_events($text,$function,$file=null,$line=0,$category=null):bool{
    $file=basename($file);$subject=substr($text,0,128);
    return squid_admin_mysql(2, $subject, $text."\nFunction $function category $category in $file $line",$file,$line);

}
function mysql_admin_events($text,$function,$file,$line,$category=null):bool{
    $file=basename($file);$subject=substr($text,0,128);
    return squid_admin_mysql(2, $subject, $text."\nFunction $function category $category in $file $line",$file,$line);
}
function update_events($text,$function,$file,$line,$category=null):bool{
    $file=basename($file);$subject=substr($text,0,128);
    return squid_admin_mysql(2, $subject, $text."\nFunction $function category $category in $file $line",$file,$line);

}

function unix_system_kill_force($pid){
    $file=null;
    $function=null;
    $line=0;
    if(function_exists("debug_backtrace")){ $trace=debug_backtrace(); if(isset($trace[1])){ $file=basename($trace[1]["file"]); $function=$trace[1]["function"]; $line=$trace[1]["line"]; }}
    if(!is_numeric($pid)){
        _unix_system_kill_debug("[9]: PID $pid is not a numeric! from  $file ($function) in line $line");
        return;
    }
    if($pid<5){
        _unix_system_kill_debug("[9]: PID $pid is under 5!! from  $file ($function) in line $line");
        return;
    }
    if(!is_dir("/proc/$pid")){return;}

    $unix=new unix();



    $cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));

    if($cmdline==null){
        $ps=$unix->find_program("ps");
        $grep=$unix->find_program("grep");
        $cmdline=exec("$ps aux|$grep --binary-files=text -Ei \".*?\s+$pid\s+\" 2>&1");
    }

    $unix->KILL_PROCESS($pid,9);
    _unix_system_kill_debug("[9]: PID $pid killed \"$cmdline\" from  $file ($function) in line $line");


}

function _unix_system_kill_debug($line):bool{

    if (is_file("/var/log/killed-proc.log")) {
        $size=filesize("/var/log/killed-proc.log");
        if($size>1000000){@unlink("/var/log/killed-proc.log");}
    }

    $f = @fopen("/var/log/killed-proc.log", 'a');
    $lineToSave=date("Y-m-d H:i:s")." " .$line;
    @fwrite($f, "$lineToSave\n");
    @fclose($f);
    return true;
}

function unix_system_kill($pid){
    $unix=new unix();
    if(function_exists("debug_backtrace")){ $trace=debug_backtrace(); if(isset($trace[1])){ $file=basename($trace[1]["file"]); $function=$trace[1]["function"]; $line=$trace[1]["line"]; }}


    if(!is_numeric($pid)){
        _unix_system_kill_debug("[?]: PID $pid is not a numeric! from  $file ($function) in line $line");
        return;
    }
    if($pid<5){
        _unix_system_kill_debug("[?]: PID $pid is under 5!! from  $file ($function) in line $line");
        return;
    }
    $kill=$unix->find_program("kill");
    $cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));

    if($cmdline==null){
        $ps=$unix->find_program("ps");
        $grep=$unix->find_program("grep");
        $cmdline=exec("$ps aux|$grep --binary-files=text -Ei \".*?\s+$pid\s+\" 2>&1");
    }

    shell_exec("$kill $pid >/dev/null 2>&1");
    _unix_system_kill_debug("[?]: PID $pid smoothly killed \"$cmdline\" from  $file ($function) in line $line");


}
function unix_system_HUP($pid):bool{
    $unix=new unix();
    $line=0;
    $function=null;
    $file=null;
    if(function_exists("debug_backtrace")){ $trace=debug_backtrace(); if(isset($trace[1])){ $file=basename($trace[1]["file"]); $function=$trace[1]["function"]; $line=$trace[1]["line"]; }}


    if(!is_numeric($pid)){
        _unix_system_kill_debug("[?]: PID $pid is not a numeric! from  $file ($function) in line $line");
        return false;
    }
    if($pid<5){
        _unix_system_kill_debug("[?]: PID $pid is under 5!! from  $file ($function) in line $line");
        return false;
    }


    $kill=$unix->find_program("kill");
    $cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));

    if($cmdline==null){
        $ps=$unix->find_program("ps");
        $grep=$unix->find_program("grep");
        $cmdline=exec("$ps aux|$grep --binary-files=text -Ei \".*?\s+$pid\s+\" 2>&1");
    }

    shell_exec("$kill -HUP $pid >/dev/null 2>&1");
    _unix_system_kill_debug("[?]: PID $pid smoothly HUP \"$cmdline\" from  $file ($function) in line $line");

    return true;
}

function recursive_remove_directory($directory, $empty=FALSE){
    if($directory==null){return false;}
    if(substr($directory,-1) == '/'){ $directory = substr($directory,0,-1); }
    if(!file_exists($directory) || !is_dir($directory)) { return FALSE; }
    if(!is_readable($directory)){return FALSE;}

    $handle = opendir($directory);
    while (FALSE !== ($item = readdir($handle))){
        if($item == '.'){continue;}
        if($item == '..'){continue;}

        $path = $directory.'/'.$item;
        if(is_dir($path)) {
            recursive_remove_directory($path);
            continue;
        }
        unlink($path);

    }


    closedir($handle);


    if($empty == FALSE){
        if(!rmdir($directory)){
            return FALSE;
        }
    }
    return TRUE;

}

function is_cluster(){
    $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
    if($PowerDNSEnableClusterMaster==1){return true;}
    return false;

}
function cluster_mode(){
    if(!isset($GLOBALS["CLUSTER"])){$GLOBALS["CLUSTER"]=false;}
    if($GLOBALS["CLUSTER"]){return;}
    if(!is_cluster()){return;}
    cluster_table();
}
function cluster_table(){
    if(!isset($GLOBALS["CLUSTER"])){$GLOBALS["CLUSTER"]=false;}
    if($GLOBALS["CLUSTER"]){return;}
    if(!is_cluster()){return;}
    if (!class_exists("sockets")){return;}
    $sock=new sockets();
    $sock->REST_API("/cluster/server/smooth");
}

function buildlocalsyslogfile($tfile):string{
    if(!is_dir(dirname($tfile))){@mkdir(dirname($tfile));}
    return "\taction(type=\"omfile\" dirCreateMode=\"0700\" FileCreateMode=\"0755\" File=\"$tfile\" ioBufferSize=\"128k\" flushOnTXEnd=\"off\" asyncWriting=\"on\")";
}
function buildlocalomhttp($remoteport,$restpath="inputs"):string{

    if(!is_file("/usr/lib/x86_64-linux-gnu/rsyslog/omhttp.so")){
        if(is_file("/etc/rsyslog.d/00_omhttp.conf")){
            @unlink("/etc/rsyslog.d/00_omhttp.conf");
        }
        return "";
    }

    if (!is_file("/etc/rsyslog.d/00_omhttp.conf")) {
        @file_put_contents("/etc/rsyslog.d/00_omhttp.conf", "module(load=\"omhttp\")\n");
    }


    return  "action(type=\"omhttp\"
                    server=\"127.0.0.1\"
                    serverport=\"$remoteport\"
                    errorfile=\"\"
                    restpath=\"$restpath\"
                    retry=\"on\"
                    useHttps=\"off\"
                    queue.Type=\"LinkedList\"
                    queue.Size=\"300000\"
                    queue.workerthreadminimummessages=\"30000\"
                    queue.workerthreads=\"3\"
                    )";

    }

function BuildRemoteSyslogs($logtype='squid',$uniqKey=null):string{
    if(!class_exists("lib_sqlite")){
        include_once(ARTICA_ROOT."/ressources/class.sqlite.inc");
    }

    if($uniqKey==null){
        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();
            $uniqKey=md5(serialize($trace));
        }
    }
    if(!class_exists("lib_sqlite")){include_once("/usr/share/artica-postfix/ressources/class.sqlite.inc");}
    $q= new lib_sqlite("/home/artica/SQLITE/syslogrules.db");
    if(!$q->TABLE_EXISTS("rules")){
        $sql="CREATE TABLE IF NOT EXISTS rules (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
        server TEXT NOT NULL,
        port INTEGER NOT NULL DEFAULT 514,
        proto TEXT NOT NULL DEFAULT 'udp',
        logtype TEXT NOT NULL DEFAULT 'proxy',
        myhostname TEXT NOT NULL DEFAULT '',
        enabled INTEGER NOT NULL DEFAULT 1,
        queue_size INTEGER NOT NULL DEFAULT 0,
	    queue_size_mb INTEGER NOT NULL DEFAULT 0,
        ssl     INTEGER NOT NULL DEFAULT 0 )";
        $q->QUERY_SQL($sql);
        return "";

    }
    $results                = $q->QUERY_SQL("SELECT * FROM rules WHERE enabled=1 AND logtype='$logtype'");
    $SyslogRules            = array();
    $CountOfSyslogRules     = 0;

    if($logtype=="all"){
        $LogSinkClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClient"));
        if($LogSinkClient==1){
            $LogSinkClientPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientPort"));
            $LogSinkClientServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinClientServer"));
            $LogSinkClientTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientTCP"));
            $LogSinkClientQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientQueue"));
            $LogSinkClientQueueSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientQueueSize"));
            $ligne["ID"]=0;
            $ligne["ssl"]=0;
            $ligne["proto"]="udp";
            $ligne["port"]=$LogSinkClientPort;
            $ligne["server"]=$LogSinkClientServer;
            $ligne["myhostname"]=null;
            $ligne["queue_size"]=$LogSinkClientQueue;
            $ligne["queue_size_mb"]=$LogSinkClientQueueSize;
            if($LogSinkClientTCP==1){
                $ligne["proto"]="tcp";
            }
            $results[]=$ligne;

        }
    }


    foreach ($results as $index=>$ligne){
        $prefix=null;
        $id=intval($ligne["ID"]);
        $ssl=trim($ligne["ssl"]);
        $server=trim($ligne["server"]);
        if($server==null){continue;}
        $proto=$ligne["proto"];
        $port=intval($ligne["port"]);
        $myhostname=trim($ligne["myhostname"]);
        $queue_size=intval($ligne["queue_size"]);
        $queue_size_mb=intval($ligne["queue_size_mb"]);
        $omfwd_tpl=null;
        $queue_path="/home/artica/syslog/spool/$id";
        $queueP="queue.spoolDirectory=\"$queue_path\"";
        if(!is_dir($queue_path)){@mkdir($queue_path,0755,true);}

        $KeyID=md5($server.$proto.$port.$logtype);
        if(isset($ALREADY[$KeyID])){
            continue;
        }
        $ALREADY[$KeyID]=true;
        if($queue_size==0){
            $queue_size=10000;
        }
        if($queue_size_mb==0){
            $queue_size_mb=500;
        }
        if($myhostname<>null){
            $templatename="omfwd-tpl-$id";
            $omfwd_tpl=" template=\"$templatename\"";
            $SyslogRules[]="template(name=\"$templatename\" type=\"string\" string=\"<%PRI%>%TIMESTAMP% $myhostname %syslogtag:1:32%%msg:::sp-if-no-1st-sp%%msg%\")\n";
        }




        $rand=rand(0,time());
        $queue_filename="$id-".md5($id.$server.$proto.$port.$logtype.$uniqKey.$myhostname.$rand);
        $opt_ssl=null;
        if($logtype=="all"){$prefix="*.*";}
        if($ssl==1){
            $proto="tcp";
            $opt_ssl="StreamDriver=\"ossl\" StreamDriverMode=\"1\" StreamDriverAuthMode=\"anon\" StreamDriver.PermitExpiredCerts=\"on\"";
        }
        //$errorfile="/var/log/syslog.rule$id.error.log";
        //$errorops="action.errorfile=\"$errorfile\" action.errorfile.maxsize=\"52428800\"";

        if($queue_size>500000){
            $queue_size=100000;
        }
        $SyslogRules[]="$prefix\taction(name=\"rule-$id\" type=\"omfwd\"$omfwd_tpl queue.type=\"linkedlist\" queue.filename=\"$queue_filename\"  queue.size=\"$queue_size\" queue.maxDiskSpace=\"{$queue_size_mb}M\" $queueP action.resumeRetryCount=\"-1\" action.reportSuspension=\"on\" queue.saveOnShutdown=\"on\" target=\"$server\" port=\"$port\" protocol=\"$proto\" $opt_ssl)";
        $CountOfSyslogRules++;
    }
    if($CountOfSyslogRules==0){return "";}
    return @implode("\n",$SyslogRules);
}