<?php
if(isset($_SESSION["TIMEZONES"])){if(function_exists("getLocalTimezone")){@date_default_timezone_set($_SESSION["TIMEZONES"]);}}
if(isset($GLOBALS["TIMEZONES"])){if(function_exists("getLocalTimezone")){@date_default_timezone_set($GLOBALS["TIMEZONES"]);}}
if(function_exists("posix_getuid")){
    if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
}
if(!isset($GLOBALS["DEBUG"])){$GLOBALS["DEBUG"]=false;}
if(!isset($GLOBALS["DEBUG_TEMPLATE"])){$GLOBALS["DEBUG_TEMPLATE"]=false;}
if(!isset($GLOBALS["DEBUG_MEM"])){$GLOBALS["DEBUG_MEM"]=false;}
if(!isset($GLOBALS["DEBUGLOGS"])){$GLOBALS["DEBUGLOGS"]=false;}
if(function_exists("debug_mem")){debug_mem();}

if(dirname($_SERVER['PHP_SELF'])<>"/smartsieve"){
    if(!class_exists("sockets")){include_once(dirname(__FILE__) . '/class.sockets.inc');}
    if(function_exists("debug_mem")){debug_mem();}
}

if(function_exists("debug_mem")){debug_mem();}
if(!function_exists("writelogs")){include_once(dirname(__FILE__) . '/logs.inc');if(function_exists("debug_mem")){debug_mem();}}
if(function_exists("debug_mem")){debug_mem();}
if(!class_exists("templates")){
    include_once(dirname(__FILE__) . '/class.templates.inc');
    if(function_exists("debug_mem")){debug_mem();
    }
}
if(!class_exists("class_manager")){
    include_once(dirname(__FILE__) . '/class.manager.inc');
    if(function_exists("debug_mem")){debug_mem();
    }
}
//if(file_exists(dirname(__FILE__) . '/class.sqlite.inc')){include_once(dirname(__FILE__) . '/class.sqlite.inc');}
if(function_exists("debug_mem")){debug_mem();}
define('grep_ip','([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)');




class clladp{
    var $ldap_admin=null;
    var $ldap_password=null;
    var $ldap_host;
    var $ldap_port=389;
    var $ldap_connection;
    var $ldapbind;
    var $suffix=null;
    var $ldap_last_error;
    var $ldapFailed=false;
    var $use_suse_schema=false;
    var $ldap_last_error_num;
    var $ArticaBranchCorrupted=false;
    var $UseLdap=true;
    var $EnableManageUsersTroughActiveDirectory=false;
    private $SquidPerformance;
    private $EnableIntelCeleron;
    private $EnableOpenLDAP=1;
    public $generated_id=0;
    public $RealOU=null;


    function __construct(){
        $this->ldapFailed=false;
        $this->EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));


        if(!isset($GLOBALS["VERBOSE++"])){$GLOBALS["VERBOSE++"]=false;}
        if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
        if(isset($_GET["debug-page"])){$GLOBALS["VERBOSE"]=true;}

        $this->verbose("EnableOpenLDAP=$this->EnableOpenLDAP",__LINE__);
        include_once(dirname(__FILE__) . '/class.active.directory.inc');


        if($GLOBALS["VERBOSE++"]){
            $trace=debug_backtrace();
            $called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
            writelogs("Instanciate ".__CLASS__ ." $called",__CLASS__."/" .__FUNCTION__,__FILE__);
        }


        if(!isset($_SESSION[__CLASS__.__FUNCTION__]["ldap_connect"])){
            if(!function_exists('ldap_connect')){
                if(!isset($GLOBALS["ldap_connect_send_error"])){
                    $GLOBALS["ldap_connect_send_error"]=true;
                    if($GLOBALS["VERBOSE"]){writelogs("ldap_connect no such function",__CLASS__.'/'.__FUNCTION__,__FILE__);}

                    if($GLOBALS["VERBOSE"]){writelogs("Ldap is enabled but no ldap_connect in ".__FILE__);}
                    if($GLOBALS["VERBOSE"]){writelogs("Ldap is enabled but no php5 ldap !!!",__CLASS__."/" .__FUNCTION__,__FILE__);}
                    return false;
                }

                return false;
            }}
        $_SESSION[__CLASS__.__FUNCTION__]["ldap_connect"]=true;
        $ldapport = $this->ldap_port;
        $this->ldap_host="127.0.0.1";
        $this->verbose("Configuration: $this->ldap_host:$ldapport",__LINE__);


        $sock=new sockets();
        $EnableManageUsersTroughActiveDirectory=$sock->GET_INFO("EnableManageUsersTroughActiveDirectory");
        if(!is_numeric($EnableManageUsersTroughActiveDirectory)){$EnableManageUsersTroughActiveDirectory=0;}
        if($EnableManageUsersTroughActiveDirectory==1){$this->EnableManageUsersTroughActiveDirectory=true;}else{$this->EnableManageUsersTroughActiveDirectory=false;}
        $EnableOpenLdapProxy=$sock->GET_INFO("EnableOpenLdapProxy");
        $OpenLdapProxySuffix=$sock->GET_INFO("OpenLdapProxySuffix");
        if($OpenLdapProxySuffix==null){$OpenLdapProxySuffix="dc=meta";}
        if(!is_numeric($EnableOpenLdapProxy)){$EnableOpenLdapProxy=0;}

        $manager=new class_manager();
        $this->ldap_admin=$manager->admin;
        $this->ldap_password=$manager->password;
        $this->ldap_host=$manager->server;
        $this->ldap_port=$manager->port;
        $this->suffix=$manager->suffix;


        if(!isset($_GLOBAL["USE_SUSE_SCHEMA"])){$_GLOBAL["USE_SUSE_SCHEMA"]=false;}
        $this->use_suse_schema=$_GLOBAL["USE_SUSE_SCHEMA"];

        if($this->ChangeToLocalhost_get()){$this->ChangeToLocalhost();}
        if($EnableOpenLdapProxy==1){$this->suffix=$OpenLdapProxySuffix;}

        $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] INIT: Suffix: $this->suffix";
        $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] INIT: Host..: $this->ldap_host:{$this->ldap_port}";




        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED"))==1){
            $this->SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
            $this->EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));
            if($this->SquidPerformance>2){return false;}
            if($this->EnableIntelCeleron==1){return false;}

        }

        if($this->EnableOpenLDAP==0){
            $this->verbose("EnableOpenLDAP is disabled, aborting",__LINE__);
            return false;
        }
        if(!isset($GLOBALS["VERIFLDAPTRUE"])){$GLOBALS["VERIFLDAPTRUE"]=false;}

        if(!$this->ldap_fsock()){return false;}
        $GLOBALS["LDAP_COUNT_FAILED"]=0;

        if(!$this->ldap_perform_connection($this->ldap_port)){
            if(!function_exists("ldap_err2str")){return false;}
            writelogs(PHP_VERSION." php version) Unable to connect to ldap server $this->ldap_host:$this->ldap_port Error=`".ldap_err2str(ldap_errno($this->ldap_connection))."`",__CLASS__.'/'.__FUNCTION__,__FILE__);
            $this->ldapFailed=true;
            return false;
        }
        $GLOBALS["LDAP_COUNT_FAILED"]=0;
        $GLOBALS["VERIFLDAPTRUE"]=true;
        if($this->ldap_admin==null){return false;}
        if(!$this->ldapbind){
            ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
            $this->ldapbind=@ldap_bind($this->ldap_connection, "cn=$this->ldap_admin,$this->suffix", $this->ldap_password);

            $zCalled=array();
            if(!$this->ldapbind){
                $trace=debug_backtrace();
                foreach ($trace as $index=>$are) {
                    $zCalled[] = " called by " .
                        basename($are["file"]) .
                        " {$are["function"]}() line {$are["line"]}";
                }
                $called=@implode(", ",$zCalled);
                if($GLOBALS["VERBOSE"]){writelogs("Fatal: ldapbind -> cn=$this->ldap_admin,$this->suffix",__CLASS__.'/'.__FUNCTION__,__FILE__);}

                $errornumber=@ldap_errno($this->ldap_connection);
                $error_text=@ldap_err2str($errornumber);
                $error= "[$errornumber] `$error_text`";
                $this->ldapFailed=true;
                writelogs("Fatal: line ". __LINE__." unable to bind to ldap server {$GLOBALS["LDAP_CONNECTION_STRING"]} (cn=$this->ldap_admin,$this->suffix) $error $called"
                    ,__CLASS__."/" .__FUNCTION__,__FILE__);

                switch (ldap_errno($this->ldap_connection)) {
                    case 0x31:
                        $error=$error . "\nBad username or password. Please try again.";
                        break;
                    case 0x32:
                        $error=$error . "\nInsufficient access rights.";
                        break;
                    case 81:
                        $error=$error . "\nUnable to connect to the LDAP server\n
							$this->ldap_host:$ldapport\nplease,\nverify if ldap daemon is running\n or the ldap server address";
                        break;

                    case 34:
                        $error=$error . "\nInvalid DN syntax\n";
                        break;

                    case -1:

                        break;
                    default:
                        $error=$error . "\nCould not bind to the LDAP server." ."\n". @ldap_err2str(ldap_errno($this->ldap_connection));
                }

                //echo $error;
                $this->ldap_last_error=$error;$this->ldapFailed=true;
                if($GLOBALS["VERBOSE"]){writelogs("return False... !!!!",__CLASS__.'/'.__FUNCTION__,__FILE__);}
                $_SESSION["ASLDATESTED"][date('YmdHi')]=false;
                return false;
            }else{
                $GLOBALS["LDAP_BIN_ID"]=$this->ldapbind;
            }

        }

        if(!isset($GLOBALS["LDAPCHECKMASTERTREE"])){$GLOBALS["LDAPCHECKMASTERTREE"]=false;}
        if(!$GLOBALS["LDAPCHECKMASTERTREE"]){
            $this->BuildMasterTree();
            $this->ArticaCreate();
            $GLOBALS["LDAPCHECKMASTERTREE"]=true;
        }
        $_SESSION["ASLDATESTED"][date('YmdHi')]=true;
        return true;

    }



    private function verbose($text,$line=null){
        if(!$GLOBALS["VERBOSE"]){return false;}
        if(!function_exists("VERBOSE")){return false;}
        VERBOSE($text,$line);
        return true;
    }

    private function ldap_fsock(){
        if($this->SquidPerformance>2){return;}
        if($this->EnableIntelCeleron==1){return;}
        if($this->EnableOpenLDAP==0){return;}
        if(isset($_SESSION["ASLDATESTED"][date('YmdHi')])){return $_SESSION["ASLDATESTED"][date('YmdHi')];}
        if($GLOBALS["VERIFLDAPTRUE"]){return true;}

        if(!$this->ldap_fsock_perform($this->ldap_host,$this->ldap_port)){
            $trace=debug_backtrace();
            $called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
            $this->verbose("ldap_fsock:: Fatal: ERROR unable to connect to ldap server  $this->ldap_host: $this->ldap_port",__LINE__);
            if($GLOBALS["DEBUG"]){writelogs("Fatal: ERROR unable to connect to ldap server in line ". __LINE__." $this->ldap_host, $this->ldap_port $called",__CLASS__."/" .__FUNCTION__,__FILE__);}
            if($this->ldap_host=="127.0.0.1"){$this->ldapFailed=true;return false;}

            include_once(dirname(__FILE__)."/local_ldap.php");
            $ARRAY=unserialize(base64_decode($GLOBALS["MAIN_LOCAL_LDAP_SETTINGS"]));
            if(is_array($ARRAY)){
                
                if($this->ldap_fsock_perform("127.0.0.1",389)){
                    $this->ChangeToLocalhost();
                    $this->ChangeToLocalhost_set();
                    writelogs("Fatal: relink local ldap server 127.0.0.1:389",__CLASS__."/" .__FUNCTION__,__FILE__);
                    $_SESSION["ASLDATESTED"][date('YmdHi')]=true;
                    $GLOBALS["VERIFLDAPTRUE"]=true;
                    $this->ldapFailed=false;
                    return true;
                }else{
                    writelogs("Fatal: Unable to connect to local ldap server 127.0.0.1:389",__CLASS__."/" .__FUNCTION__,__FILE__);
                    $this->ldapFailed=true;
                    $_SESSION["ASLDATESTED"][date('YmdHi')]=false;
                    return false;
                }

            }else{
                writelogs("Fatal: line ". __LINE__." ".dirname(__FILE__)."/local_ldap.php no such array",__CLASS__."/" .__FUNCTION__,__FILE__);
            }

        }
        $this->ldapFailed=false;
        $_SESSION["ASLDATESTED"][date('YmdHi')]=true;
        $GLOBALS["VERIFLDAPTRUE"]=true;
        $this->verbose("ldap_fsock:: Return True",__LINE__);
        return true;
    }

    private function ChangeToLocalhost_get(){
        if(isset($_SESSION["CHANGE_LDAP_TO_LOCALHOST"][date('YmdHi')])){return true;}
        if(isset($GLOBALS["CHANGE_LDAP_TO_LOCALHOST"][date('YmdHi')])){return true;}
    }
    private function ChangeToLocalhost_set(){
        $GLOBALS["CHANGE_LDAP_TO_LOCALHOST"][date('YmdHi')]=true;
        $_SESSION["CHANGE_LDAP_TO_LOCALHOST"][date('YmdHi')]=true;

    }
    public function ldapSort(array &$entries, $key)
    {
        $SORT_KEY = 'SortValue';

        $key = strtolower($key);

        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = &$entries[$i];
            $attributes = array_change_key_case($entry, CASE_LOWER);

            $entry[$SORT_KEY] = isset($attributes[$key][0]) ?
                $attributes[$key][0] : null;
        }
        unset($entry);

        $this->quickSort(
            $entries,
            function ($a, $b) use ($SORT_KEY) {
                $a = $a[$SORT_KEY];
                $b = $b[$SORT_KEY];
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
            },
            0, // start
            $entries['count'] - 1 // end
        );
    }

    private function quickSort(&$array, $compare, $start, $end)
    {
        $partition = function (&$array, $start, $end) use (&$partition, $compare) {
            if ($start >= $end) {
                return;
            }
            $pivot = $array[$start];
            $left = $start;
            $right = $end;
            while ($left <= $right) {
                while ($compare($array[$left], $pivot) < 0) {
                    $left += 1;
                }
                while ($compare($array[$right], $pivot) > 0) {
                    $right -= 1;
                }
                if ($left > $right) {
                    break;
                }
                list($array[$left], $array[$right]) = [$array[$right], $array[$left]];
                $left += 1;
                $right -= 1;
            }
            $partition($array, $start, $right);
            $partition($array, $left, $end);
        };

        $partition($array, $start, $end);
    }

    private function ChangeToLocalhost(){
        include_once(dirname(__FILE__)."/local_ldap.php");

        $ARRAY=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["MAIN_LOCAL_LDAP_SETTINGS"]);
        if(is_array($ARRAY)){
            if(!isset($ARRAY["PWD"])){
                $ARRAY["PWD"]="";
            }
            if(!isset($ARRAY["DN"])){
                $ARRAY["DN"]="";
            }
            if(!isset($ARRAY["SUFFIX"])){
                $ARRAY["SUFFIX"]="";
            }
            $binddn=$ARRAY["DN"];
            $password=$ARRAY["PWD"];
            $suffix=$ARRAY["SUFFIX"];
            $this->ldap_host="127.0.0.1";
            $binddnZ=explode(",",$binddn);
            $this->suffix=$suffix;
            $this->ldap_admin=$binddnZ[0];
            $this->ldap_port=389;
            $this->ldap_password=$password;
            $this->ldapFailed=false;
        }
    }

    private function ldap_fsock_perform($server,$port):bool{
        if($this->EnableOpenLDAP==0){return false;}
        $fp=@fsockopen($server, $port, $errno, $errstr, 2);
        if(!$fp){
            $this->ldap_last_error=$errstr;
            if($GLOBALS["DEBUG"]){writelogs("Fatal: fsockopen -> $server:$port",__CLASS__.'/'.__FUNCTION__,__FILE__);}
            return false;
        }
        @fclose($fp);
        return true;
    }

    function ldap_escape($str){
        $metaChars = array('\\', '(', ')', '#', '*');
        $quotedMetaChars = array();
        foreach ($metaChars as $key => $value) $quotedMetaChars[$key] = '\\'.dechex(ord($value));
        $str=str_replace($metaChars,$quotedMetaChars,$str);
        return ($str);
    }

    private function ldap_connect_action(){
        if($this->EnableOpenLDAP==0){return false;}
        $GLOBALS["NOLDAPWINSOCK"]=true;
        $_SESSION["NOLDAPWINSOCK"]=true;
        if(isset($_SESSION["NOLDAPWINSOCK"])){$GLOBALS["NOLDAPWINSOCK"]=true;}

        $SSL=0;
        if($this->ldap_port==636){
            $SSL=1;
        }

        $ServerString="ldap://$this->ldap_host:$this->ldap_port";
        if($SSL==1){
            $ServerString="ldaps://$this->ldap_host:$this->ldap_port";
        }



        $GLOBALS["LDAP_CONNECTION_STRING"]="$this->ldap_host:$this->ldap_port";
        $this->ldap_connection=@ldap_connect($ServerString ) ;
        if($this->ldap_connection){
            ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
            if($SSL==1) {
                ldap_set_option($this->ldap_connection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            }
            $this->verbose("ldap_connect_action: connection to $ServerString SUCCESS",__LINE__);
            $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] $ServerString SUCCESS";
            return true;
        }
        $error=ldap_err2str( ldap_errno($this->ldap_connection) );
        $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] $ServerString FAILED ($error)";
        if($GLOBALS["VERBOSE"]){writelogs("Fatal: ldap_connect -> $ServerString FAILED",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
        $this->verbose("ldap_connect_action: connection to $ServerString failed with error $error",__LINE__);
        return false;
    }




    public function ldap_perform_connection($ldapport=null){
        if($this->EnableOpenLDAP==0){
            $this->verbose("ldap_perform_connection: EnableOpenLDAP = 0",__LINE__);
            return false;
        }

        if(!is_numeric($this->ldap_port)){$this->ldap_port=389;}



        if(!function_exists("ldap_connect")){
            $this->verbose("ldap_perform_connection: ldap_connect no such function",__LINE__);
            if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";writeLogs("-> Call to undefined function ldap_connect() $called".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
            return false;
        }


        if($this->ldap_connection){return true;}


        if(!$this->ldap_connect_action()){
            $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] ldap_perform_connection: ldap_connect_action() return false;";
            return false;
        }

        if(!$this->ldap_connection){
            $this->verbose("ldap_perform_connection: ldap_connection = FALSE ??!!",__LINE__);
            $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] ldap_perform_connection: ldap_connection == false;";
            return false;
        }


        $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] User..: $this->ldap_admin";
        $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] Suffix: $this->suffix";

        ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);

        if(!$this->ldap_connection){
            $this->verbose("ldap_perform_connection: Alert, loosing the connection!!",__LINE__);
        }

        $this->ldapbind=@ldap_bind($this->ldap_connection, "cn=$this->ldap_admin,$this->suffix", $this->ldap_password);
        if($this->ldapbind){
            $this->verbose("ldap_perform_connection: Using creds cn=$this->ldap_admin,$this->suffix SUCCESS",__LINE__);
            $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] ldap_perform_connection: SUCCESS";
            return true;
        }


        $errnum=ldap_errno($this->ldap_connection);
        $error=ldap_err2str($errnum);
        $this->verbose("ldap_perform_connection: cn=$this->ldap_admin,$this->suffix Error[$errnum]:$error <strong>FAILED</strong>",__LINE__);
        $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] ldap_perform_connection(1): ldap_bind:: $error";


        if(!$this->ldap_connect_action()){
            $error=ldap_err2str(ldap_errno($this->ldap_connection));
            $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] ldap_perform_connection(2): $error";
            return false;
        }


        $GLOBALS["LDAP_CONNECT_ID"]=$this->ldap_connection;


        if(!$this->ldap_connection){
            $this->verbose("FAILED -> ldap_connect($this->ldap_host, $ldapport" ,__LINE__);
            $GLOBALS["LDAP_COUNT_FAILED"]=$GLOBALS["LDAP_COUNT_FAILED"]+1;
            if($GLOBALS["LDAP_COUNT_FAILED"]>4){return false;}
            return $this->ldap_perform_connection($this->ldap_port);
        }

        if(!ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3)){
            if(!$this->ldap_connect_action()){ return false; }
            ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        }
        ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);

        $this->ldapbind=@ldap_bind($this->ldap_connection, "cn=$this->ldap_admin,$this->suffix", $this->ldap_password);
        if($this->ldapbind){$GLOBALS["LDAP_BIN_ID"]=$this->ldapbind;}
        else{
            $error=ldap_err2str(ldap_errno($this->ldap_connection));
            $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] BIND (2): $error";
            if($GLOBALS["VERBOSE"]){writelogs("Fatal: ldapbind -> cn=$this->ldap_admin,$this->suffix",__CLASS__.'/'.__FUNCTION__,__FILE__);}
        }
        return true;

    }



    private function builderror($error){
        $ldapClass=$this;
        $port=$ldapClass->ldap_port;
        $suffix=$ldapClass->suffix;
        $ldap_host=$ldapClass->ldap_host;
        return array("TITLE"=>"{LDAP_ERROR_TEXT}","TEXT"=>"<ul><li>$ldap_host:$port</li><li>$suffix</li></ul>$error");
    }

    function dn_escape($dn) {
        $olddn = $dn;
        # Check if the RDN has a comma and escape it.
        while (preg_match('/([^\\\\]),(\s*[^=]*\s*),/',$dn))
            $dn = preg_replace('/([^\\\\]),(\s*[^=]*\s*),/','$1\\\\2C$2,',$dn);
        $dn = preg_replace('/([^\\\\]),(\s*[^=]*\s*)([^,])$/','$1\\\\2C$2$3',$dn);
        return $dn;
    }

    function BuildMasterTree(){
        $suffix=$this->suffix;
        if($this->ExistsDN($suffix)){return null;}
        writelogs("Build master tree seems that $suffix does not exists",__CLASS__.'/'.__FUNCTION__,__FILE__);
        $tbl=explode(",",$this->suffix);
        $tbl[0]=str_replace("dc=","",$tbl[0]);
        $tbl[1]=str_replace("dc=","",$tbl[1]);
        $upd["objectClass"][]="dcObject";
        $upd["objectClass"][]="organization";
        $upd["o"]="Root";
        $upd["dc"]="{$tbl[0]}";

        writelogs("dn: $suffix");
        writelogs("objectClass: dcObject");
        writelogs("objectClass: organization");
        writelogs("dc: {$tbl[0]}");
        writelogs("o: Example Corporation");
        writelogs("description: The Example Corporation");

        $this->ldap_add($suffix,$upd);

    }

    public function BuildOrganizationBranch(){
        if(!$this->UseLdap){return true;}
        $dn="dc=organizations,$this->suffix";
        $upd=array();
        if($this->ExistsDN($dn)){return true;}
        $upd["dc"][]="organizations";
        $upd["o"][]="organizations";
        $upd["objectclass"][]="top";
        $upd["objectclass"][]="organization";
        $upd["objectclass"][]="dcObject";
        if(!$this->ldap_add($dn,$upd)){
            return false;
        }
        return true;
    }

    function uid_from_email($email){
        $this->ldap_perform_connection();
        $real=$this->real_email_from_mail($email);
        if(trim($real)<>null){$email=$real;}
        $attr=array("uid");
        $pattern="(&(objectclass=userAccount)(|(mailAlias=$email)(mail=$email)(mozillaSecondEmail=$email)(FetchMailMatchAddresses=$email)))";
        $sr=@ldap_search($this->ldap_connection,$this->suffix,$pattern,$attr);
        if(!$sr){return null;}
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        if($hash["count"]>0){return $hash[0]["uid"][0];}
    }



    function CountDeUSerOu($ou){
        if($this->IsOUUnderActiveDirectory($ou)){
            include_once(dirname(__FILE__)."/class.external.ad.inc");
            $p=new external_ad_search();
            writelogs("-> class.external.ad.inc -> CountDeUSerOu($ou)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return $p->CountDeUSerOu($ou);
        }
        $this->ldap_perform_connection();
        $pattern="(objectclass=userAccount)";
        $dn="ou=users,ou=$ou,dc=organizations,$this->suffix";
        $sr =@ldap_search($this->ldap_connection,$dn,$pattern,array("dn"));
        if($sr){
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            return $hash["count"];
        }

    }

    function real_email_from_mail($email){
        $hash=array();
        $this->ldap_perform_connection();
        $pattern="(&(objectclass=userAccount)(|(mailAlias=$email)(mozillaSecondEmail=$email)(FetchMailMatchAddresses=$email)))";
        $attr=array("mail");
        $sr =@ldap_search($this->ldap_connection,$this->suffix,$pattern,$attr);
        if($sr){
            $hash=ldap_get_entries($this->ldap_connection,$sr);
        }
        if($hash["count"]>0){
            return $hash[0]["mail"][0];}else{return $email;}
    }

    function find_users_by_mail($email){
        $res=array();
        $this->ldap_perform_connection();
        $pattern="(&(objectclass=userAccount)(|(mailAlias=$email)(mail=$email)(mozillaSecondEmail=$email)(FetchMailMatchAddresses=$email)))";
        $attr=array("uid","mail");
        writelogs("Pattern:$pattern",__CLASS__.'/'.__FUNCTION__,__FILE__);
        $sr =ldap_search($this->ldap_connection,$this->suffix,$pattern,$attr);
        if($sr){
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            writelogs("Found: {$hash["count"]} entries",__CLASS__.'/'.__FUNCTION__,__FILE__);
            if($hash["count"]>0){
                for($i=0;$i<$hash["count"];$i++){
                    $res[$hash[$i]["uid"][0]]=$hash[$i]["mail"][0];
                }
            }else{
                writelogs("Failed search $pattern",__CLASS__.'/'.__FUNCTION__,__FILE__);
            }
            return $res;}

    }

    function AddGroup($group,$ou=null,$generated_id=null){
        $this->ldap_perform_connection();
        writelogs("Adding new group $group ($generated_id) in $ou ",__CLASS__.'/'.__FUNCTION__,__LINE__);

        $group=replace_accents($group);
        $update_array=array();

        if(!is_numeric($generated_id)){$generated_id=null;}

        if($generated_id==null){
            $generated_id=$this->_GenerateGUID();
            writelogs("generated_id=$generated_id",__CLASS__.'/'.__FUNCTION__,__LINE__);
            if($generated_id==1){$generated_id=5001;}
        }

        if($ou==null){if($_GET["ou"]<>null){$ou=$_GET["ou"];}}
        if($ou==null){if($_SESSION["ou"]<>null){$ou=$_SESSION["ou"];}}

        if($ou==null){$dn="cn=$group,dc=organizations,$this->suffix";}
        else{$dn="cn=$group,ou=groups,ou=$ou,dc=organizations,$this->suffix";}

        $dn_master_branch="ou=groups,ou=$ou,dc=organizations,$this->suffix";
        if(!$this->ExistsDN($dn_master_branch)){
            $update_array["objectClass"][]="top";
            $update_array["objectClass"][]="organizationalUnit";
            $update_array["ou"][]="groups";
            if(!$this->ldap_add($dn_master_branch,$update_array)){
                echo "Unable to add master branch\n$dn_master_branch\n$this->ldap_last_error";
                return false;
            }
            unset($update_array);

        }



        $update_array["cn"][0]="$group";
        $update_array["gidNumber"][0]=$generated_id;
        $update_array["description"][0]="New posix group";
        $update_array["objectClass"][]='posixGroup';
        $update_array["objectClass"][]='ArticaSettings';
        $update_array["objectClass"][]='top';

        $dn=$this->dn_escape($dn);
        if(!$this->ldap_add($dn,$update_array)){
            echo  nl2br(
                "Error: Adding {$update_array["gidNumber"][0]} gid 
				cn=$group\ndn:$dn\n".
                $this->ldap_last_error);
            return false;
        }

        $_SESSION["ADD_USER_GROUP"]=$group;
        $this->generated_id=$generated_id;
        return true;


    }

    function delete_associated_domain($ou,$domain){
        $this->ldap_perform_connection();
        $update_array=array();
        $HashDomains=$this->Hash_associated_domains($ou);
        writelogs("Delete domain $domain in $ou",__FUNCTION__,__FILE__);
        if(is_array($HashDomains)){
            unset($HashDomains[$domain]);
            writelogs("Table count " . count($HashDomains) . " entries",__FUNCTION__,__FILE__);
            if(count($HashDomains)==0){
                $update_array["associatedDomain"][0]="localhost.localdomain";
                $this->Ldap_modify("ou=$ou,dc=organizations,$this->suffix",$update_array);
                return true;
            }
            foreach ($HashDomains as $num=>$val){
                $update_array["associatedDomain"][]=$num;
            }

            $this->Ldap_modify("ou=$ou,dc=organizations,$this->suffix",$update_array);
        }
    }

    function StripSpecialsChars($pattern):string{
        include_once(dirname(__FILE__)."/class.html.tools.inc");
        $html=new htmltools_inc();
        return $html->StripSpecialsChars($pattern);
    }

    function AddOrganization($ou){
        $this->ldap_perform_connection();
        $ou=$this->StripSpecialsChars($ou);
        $this->RealOU=$ou;
        if($ou=="users"){echo "Error: Adding\n$ou words not permitted\n";return false;}
        if($ou=="groups"){echo "Error: Adding\n$ou words not permitted\n";return false;;}
        if($ou=="computers"){echo "Error: Adding\n$ou words not permitted\n";return false;;}
        if($ou=="pureftpd"){echo "Error: Adding\n$ou words not permitted\n";return false; ;}
        if($ou=="root"){echo "Error: Adding\n$ou words not permitted\n";return false;;}

        $this->BuildMasterTree();
        $this->BuildOrganizationBranch();
        $update_array=array();
        $update_array["ou"][0]="$ou";
        $update_array['objectClass'][]='organizationalUnit';
        $update_array['objectClass'][]='top';
        $update_array["objectClass"][]='ArticaSettings';
        $dn="ou=$ou,dc=organizations,$this->suffix";
        $GLOBALS["LDAP_EVENTS"][]="[".__LINE__."] AddOrganization: DN: $dn";
        $dn=$this->dn_escape($dn);
        if(!$this->ExistsDN($dn)){
            if(!$this->ldap_add($dn,$update_array)){
                if($this->ldap_last_error_num<>68){
                    if(isset($GLOBALS["LDAP_EVENTS"])){
                        $this->ldap_last_error=$this->ldap_last_error."\n".@implode("\n", $GLOBALS["LDAP_EVENTS"]);
                    }
                    echo "Error: Adding\n$ou\ndn:$dn\n".$this->ldap_last_error;
                    return false;
                }
            }
        }


        unset($update_array);
        $dn="ou=groups,ou=$ou,dc=organizations,$this->suffix";
        if(!$this->ExistsDN($dn)){
            $update_array["objectClass"][]="organizationalUnit";
            $update_array["objectClass"][]="top";
            $update_array["ou"][0]="groups";
            if(!$this->ldap_add($dn,$update_array)){
                writelogs("Error: Adding $dn $this->ldap_last_error",__CLASS__."/".__FUNCTION__,__FILE__);
                if($this->ldap_last_error_num<>68){
                    echo "Error: Adding\ndn:$dn\n".$this->ldap_last_error;
                    return false;
                }
            }
        }

        unset($update_array);
        $dn="ou=users,ou=$ou,dc=organizations,$this->suffix";
        if(!$this->ExistsDN($dn)){
            $update_array["objectClass"][]="organizationalUnit";
            $update_array["objectClass"][]="top";
            $update_array["ou"][0]="users";
            if(!$this->ldap_add($dn,$update_array)){
                writelogs("Error: Adding $dn $this->ldap_last_error",__CLASS__."/".__FUNCTION__,__FILE__);
                if($this->ldap_last_error_num<>68){
                    echo "Error: Adding\ndn:$dn\n".$this->ldap_last_error;
                    return false;
                }
            }}

        unset($update_array);
        $dn="ou=computers,ou=$ou,dc=organizations,$this->suffix";
        if(!$this->ExistsDN($dn)){
            $update_array["objectClass"][]="organizationalUnit";
            $update_array["objectClass"][]="top";
            $update_array["ou"][0]="computers";
            if(!$this->ldap_add($dn,$update_array)){
                writelogs("Error: Adding $dn $this->ldap_last_error",__CLASS__."/".__FUNCTION__,__FILE__);
                if($this->ldap_last_error_num<>68){
                    echo "Error: Adding\ndn:$dn\n".$this->ldap_last_error;
                    return false;
                }
            }
        }

        $_SESSION["ADD_USER_OU"]=$ou;
        writelogs("Success: Adding $ou",__CLASS__."/".__FUNCTION__,__FILE__);
        return true;
    }




    function AddUserGroup($ou,$groupname){
        $array["cn"]=$groupname;
        $guid=$this->_GenerateGUID();
        $array["objectClass"][]='posixGroup';
        $array["objectClass"][]='top';
        $array["objectClass"][]='ArticaSettings';
        $array["gidNumber"][]=$guid;
        $cn="cn=$groupname,ou=groups,ou=$ou,dc=organizations,$this->suffix";
        $cn=$this->dn_escape($cn);
        if(@ldap_add($this->ldap_connection,$cn,$array)==false){
            $this->ldap_last_error='Error number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP AddUserGroup LDAP ADD \ndn:$cn\n$ou; $groupname\n" . ldap_err2str(ldap_errno($this->ldap_connection));
            return 0;}

        return $guid;

    }

    function AddUserToGroup($Groupid,$userid){
        $group_datas=$this->GroupDatas($Groupid);
        if(is_array($group_datas["members"])){
            writelogs("$Groupid=>$userid =\"{$group_datas["members"][$userid]}\"",__CLASS__.'/'.__FUNCTION__,__FILE__);
            if($group_datas["members"][$userid]<>null){return true;}

            foreach ($group_datas["members"] as $num=>$ligne){
                $update_array["memberUid"][]=$num;
            }
        }

        $update_array["memberUid"][]=$userid;
        writelogs("Adding $userid in $Groupid",__CLASS__.'/'.__FUNCTION__,__FILE__);
        if(!$this->Ldap_modify($group_datas["dn"],$update_array)){
            echo $this->ldap_last_error;return false;}
        return true;
    }

    function AllDomains(){
        return $this->hash_get_all_domains();
    }

    function Ldap_search($dn,$filter,$attrs,$maxRow=0){
        if($this->EnableOpenLDAP==0){return;}
        $this->ldapFailed=false;
        if($this->EnableManageUsersTroughActiveDirectory){
            $ldap=new ldapAD();
            return $ldap->Ldap_search($dn,$filter,$attrs,$maxRow);
        }
        $filter=str_replace("**", "*", $filter);

        $this->ldap_perform_connection();
        if(!$this->ldap_connection){return ;}
        if(!is_array($attrs)){
            $sr =@ldap_search($this->ldap_connection,$dn,$filter,array(),0,$maxRow);
        }else{$sr =@ldap_search($this->ldap_connection,$dn,$filter,$attrs,0,$maxRow);}
        if ($sr) {
            return ldap_get_entries($this->ldap_connection,$sr);
        }else{
            $error_number=ldap_errno($this->ldap_connection);
            $error_text=ldap_err2str(ldap_errno($this->ldap_connection));
            $this->ldap_last_error="Error LDAP search number $error_number\nAction:LDAP search\ndn:$dn\n$filter\n$error_text";
            writelogs("ERROR $error_number Ldap_search : $filter in $dn $error_text",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);
            if(strpos("    $error_text", "Can't contact LDAP server")>0){
                writelogs("!!!! FATAL Can't contact LDAP server !!!!",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);
                $this->ldapFailed=true;
            }


            return false;
        }
        return array();
    }




    function Hash_all_kav_settings(){
        $arr=array("gidNumber","KasperkyAVScanningDatas");
        $sr =@ldap_search($this->ldap_connection,$this->suffix,'(&(objectclass=ArticaSettings)(KasperkyAVScanningDatas=*))',$arr);
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);

            for($i=0;$i<$hash["count"];$i++){
                $gidnumber=$hash[$i]["gidnumber"][0];
                $kas=$hash[$i]["kasperkyavscanningdatas"][0];
                if($kas==null){
                    writelogs("there is no datas here, try to get the default value",__FUNCTION__,__FILE__);
                    $def=file_get_contents(dirname(__FILE__) . "/databases/kav4mailserver.default.conf");
                    $def="[smtpscan.group:$gidnumber]\n$def";
                    $kas=$def;
                }

                $res[$gidnumber]=$kas;
            }

            return $res;
        }

    }







    function ArticaDatas(){
        return array();
    }

    function ldap_close(){
        $this->verbose("CLOSING CONNECTION",__LINE__);
        unset($GLOBALS["LDAP_BIN_ID"]);
        unset($GLOBALS["LDAP_CONNECT_ID"]);
    }

    function ArticaDatasFromField($fieldName){
        $sr =@ldap_read($this->ldap_connection,"cn=artica,$this->suffix",'objectClass=ArticaSettings');
        $fieldName=strtolower($fieldName);
        writelogs("read $fieldName in cn=artica,$this->suffix",__FUNCTION__,__FILE__);
        if ($sr) {$hash=ldap_get_entries($this->ldap_connection,$sr);
            if(!isset($hash[0][$fieldName])){writelogs("Failed to load in hash $fieldName in cn=artica,$this->suffix",__FUNCTION__,__FILE__);return null;}
            writelogs("return " . strlen($hash[0][$fieldName][0]) . " caracters",__FUNCTION__,__FILE__);
            return $hash[0][$fieldName][0];
        }else{writelogs("Failed to load $fieldName in cn=artica,$this->suffix",__FUNCTION__,__FILE__);}

    }

    function ArticaDatasAddField($fieldName,$datas){
        $fieldNameLower=strtolower($fieldName);
        $sr =@ldap_read($this->ldap_connection,"cn=artica,$this->suffix",'objectClass=ArticaSettings');
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            if(!isset($hash[0][$fieldNameLower])){
                $updateArray["$fieldName"]=$datas;
                writelogs("adding $fieldName into artica datas with " . strlen($datas) . " caracters",__FUNCTION__,__FILE__);
                $this->Ldap_add_mod("cn=artica,$this->suffix",$updateArray);
            }else{
                $updateArray["$fieldName"][0]=$datas;
                $this->Ldap_modify("cn=artica,$this->suffix",$updateArray);
            }
        }
    }


    function CyrusAdminCreate(){
        $dn="cn=cyrus,dc=organizations,$this->suffix";
        if(!$this->ExistsDN($dn)){
            writelogs('Creating new user Cyrus in order to perform imap operations....',__CLASS__ . '/' . __FUNCTION__);
            $upd=array();
            $upd["objectClass"][]="top";
            $upd["objectClass"][]="inetOrgPerson";
            $upd["cn"][]="cyrus";
            $upd["sn"][]="cyrus";
            $upd["uid"][]="cyrus";
            $upd["userPassword"][]=$this->ldap_password;
            return $this->ldap_add($dn,$upd);

        }



    }

    function CyrusAdminOtherCreate($cyrus_admin,$cyrus_password=null,$OnlyCreate=false){
        if(trim($cyrus_password)==null){
            writelogs("no password set...define has cyrus default",__CLASS__ . '/' . __FUNCTION__,__FILE__,__LINE__);
            $cyrus_password=$this->CyrusPassword();
        }
        if(trim($cyrus_password)==null){
            writelogs("no password set...define has admin LDAP default",__CLASS__ . '/' . __FUNCTION__,__FILE__,__LINE__);
            $cyrus_password=$this->ldap_password;
        }


        $dn="cn=$cyrus_admin,dc=organizations,$this->suffix";
        if(!$this->ExistsDN($dn)){
            writelogs("Creating new user $cyrus_admin Cyrus in order to perform imap operations....",__CLASS__ . '/' . __FUNCTION__,__FILE__,__LINE__);
            $upd=array();
            $upd["objectClass"][]="top";
            $upd["objectClass"][]="inetOrgPerson";
            $upd["cn"][]="$cyrus_admin";
            $upd["sn"][]="$cyrus_admin";
            $upd["uid"][]="$cyrus_admin";
            $upd["userPassword"][]=$cyrus_password;
            $this->ldap_add($dn,$upd);
        }else{
            if($GLOBALS["AS_ROOT"]){
                if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $dn exists\n";}
            }
            if(!$OnlyCreate){
                $upd["userPassword"][]=$cyrus_password;
                $this->Ldap_modify($dn,$upd);
            }
        }



    }


    function ArticaCreate(){
        $DebugThis=false;
        if($GLOBALS["DEBUG_TEMPLATE"]){$DebugThis=true;}
        $update_array=array();
        $this->ldap_perform_connection();

        $uid=null;
        $CURRENT_PAGE=null;
        $detected_lang=null;
        if(isset($_SESSION["uid"])){$uid=$_SESSION["uid"];}
        if(isset($_SESSION["detected_lang"])){$detected_lang=$_SESSION["detected_lang"];}
        if(isset($GLOBALS["CURRENT_PAGE"])){$CURRENT_PAGE=$GLOBALS["CURRENT_PAGE"];}
        $verboseprefix="[$uid]::$CURRENT_PAGE::LANG:[$detected_lang]::";
        if($DebugThis){
            VERBOSE("$verboseprefix this->ExistsDN(cn=artica,$this->suffix)".__FUNCTION__.' in '.__FILE__, __LINE__);
        }
        if(!$this->ExistsDN("cn=artica,$this->suffix")){
            //writelogs("Creating cn=artica,$this->suffix branch",__CLASS__.'/'.__FUNCTION__,__FILE__);
            $dn=$this->dn_escape("cn=artica,$this->suffix");
            $update_array["cn"][]='artica';
            $update_array["objectClass"][]='organizationalRole';
            $update_array["objectClass"][]='ArticaSettings';
            $update_array["objectClass"][]='top';
            if($DebugThis){VERBOSE("$verboseprefix this->ldap_add($dn,$update_array) ".__FUNCTION__.' in '.__FILE__, __LINE__);}
            $this->ldap_add($dn,$update_array);
        }else{
            if($DebugThis){VERBOSE("$verboseprefix this->ExistsDN(cn=artica,$this->suffix) -> OK ".__FUNCTION__.' in '.__FILE__, __LINE__);}
        }

        if(!$this->ExistsDN("cn=artica,$this->suffix")){$this->ArticaBranchCorrupted=true;}

    }

    function IFcn_notexists_CreateIt($name){
        return;
        $DebugThis=false;
        if($GLOBALS["DEBUG_TEMPLATE"]){$DebugThis=true;}

        $update_array=array();

        if($this->ldapFailed){return null;}
        if($DebugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}:: @ldap_search($this->ldap_connection,$this->suffix,(cn=$name))".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(cn=$name)");
        if($DebugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}:: @ldap_search -> $sr".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
        if ($sr) {
            if($DebugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}: @ldap_get_entries($this->ldap_connection,$sr) ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
            $hash=@ldap_get_entries($this->ldap_connection,$sr);
            if($hash["count"]==0){
                $dn=$this->dn_escape("cn=artica,$this->suffix");
                writelogs("Creating $dn",__CLASS__.'/'.__FUNCTION__,__FILE__);
                $update_array["cn"][]='artica';
                $update_array["objectClass"][]='organizationalRole';
                $update_array["objectClass"][]='ArticaSettings';
                $update_array["objectClass"][]='top';
                if($DebugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}: this->ldap_add($dn,$update_array) ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
                $this->ldap_add($dn,$update_array);
            }
        }else{
            if($DebugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]}:: FAILED ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
        }
    }


    function _HashAllBlackWhites(){
        $sr =@ldap_search($this->ldap_connection,$this->suffix,'(KasperkyASDatasAllow=*)',array('KasperkyASDatasAllow'));
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            foreach ($hash as $num=>$ligne){
                for($i=0;$i<$ligne["kasperkyasdatasallow"]["count"];$i++){
                    $allow[$ligne["kasperkyasdatasallow"][$i]]=$ligne["kasperkyasdatasallow"][$i];
                }
            }
        }
        //--------------------------------------------------------------------------------------------------------------------------------------------------------------
        $sr =@ldap_search($this->ldap_connection,$this->suffix,'(KasperkyASDatasDeny=*)',array('KasperkyASDatasDeny'));
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            foreach ($hash as $num=>$ligne){
                for($i=0;$i<$ligne["kasperkyasdatasdeny"]["count"];$i++){
                    $deny[$ligne["kasperkyasdatasdeny"][$i]]=$ligne["kasperkyasdatasdeny"][$i];
                }
            }
        }

        return array("allows"=>$allow,"deny"=>$deny);
    }




    function Hash_GetALLUsers(){
        if($this->EnableManageUsersTroughActiveDirectory){
            $ldap=new ldapAD();
            return $ldap->Hash_GetALLUsers();
        }

        $arr=array("uid","mail");
        $sr =@ldap_search($this->ldap_connection,"dc=organizations,$this->suffix",'(objectclass=userAccount)',$arr);
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            for($i=0;$i<$hash["count"];$i++){
                $mail=null;
                $userid=$hash[$i]["uid"][0];
                if(isset($hash[$i]["mail"][0])){$mail=$hash[$i]["mail"][0];}
                $res[$userid]=$mail;
            }
        }else{
            return array();
        }

        return $res;
    }


    function UserVacation($uid){

        $filter="(&(objectclass=Vacation)(uid=$uid)(vacationActive=TRUE))";
        $attrs=array("vacationEnabled","vacationActive","vacationEnd","vacationStart","vacationInfo","DisplayName");
        if($GLOBALS["VERBOSE"]){writelogs($filter,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"$filter",$attrs);
        if (!$sr) {return array();}
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        return $hash[0];
    }


    function hash_load_transport(){
        $this->ldap_perform_connection();
        $sr =@ldap_search($this->ldap_connection,$this->suffix,'(&(objectclass=transportTable)(transport=*))');
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            if(!is_array($hash)){return array();}
            foreach ($hash as $num=>$ligne){
                if($ligne["cn"][0]<>null){
                    $res[$ligne["cn"][0]]=$ligne["transport"][0];

                }

            }

        }


        return $res;
    }

    function sasl_relayhost($server){

    }

    function WhereisDomainTransport($domain){
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(cn=$domain)");
        $hash=ldap_get_entries($this->ldap_connection,$sr);
        return  $hash[0]["dn"];
    }


    function GetTransportTable($domain){
        $hash=$this->hash_load_transport();
        return $hash[$domain];

    }



    /**
     * return all organizationalUnit in an array
     * array[0]=ou
     *
     * @param none
     * @return array
     */

    function hash_get_user_data_fast($uid){

        $pattern_uid    = $this->ldap_escape($uid);
        $filter         = array("dn","uidNumber","DisplayName","mail","telephonenumber","mobile","givenname","sn");
        $searchdn       = "dc=organizations,$this->suffix";
        $sr             =  @ldap_search($this->ldap_connection,$searchdn,"(uid=".$pattern_uid.")",$filter);


        if(!$sr){
            $errornumber= ldap_errno($this->ldap_connection);
            $error_text=ldap_err2str($errornumber);
            $this->error="Ldap error: $errornumber, $error_text";
            return array();
        }

        $entry_id = ldap_first_entry($this->ldap_connection,$sr);
        if(!$entry_id) {
            $errornumber = ldap_errno($this->ldap_connection);
            $error_text = ldap_err2str($errornumber);
            $this->error = "Ldap error: $errornumber, $error_text";
            return array();
        }

        $attrs = ldap_get_attributes($this->ldap_connection, $entry_id);

        if(isset($attrs["uidNumber"])){$main["uidNumber"]=$attrs["uidNumber"][0];}
        if(isset($attrs["displayName"])){$main["displayName"]=$attrs["displayName"][0];}
        if(isset($attrs["mail"])){$main["mail"]=$attrs["mail"][0];}
        if(isset($attrs["telephonenumber"])){$main["telephonenumber"]=$attrs["telephonenumber"][0];}
        if(isset($attrs["mobile"])){$main["mobile"]=$attrs["mobile"][0];}
        if(isset($attrs["givenname"])){$main["givenname"]=$attrs["givenname"][0];}
        if(isset($attrs["sn"])){$main["sn"]=$attrs["sn"][0];}
        return $main;


    }
    function GetUserGroups($userid){
        if($userid==null){return array();}
        $GroupsOf       = array();
        $attrs          = array('gidNumber','cn');

        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(&(objectClass=posixGroup)(memberUid=$userid))",$attrs);
        if($sr){
            $result = ldap_get_entries($this->ldap_connection, $sr);
            if(!is_array($result)){return array();}
            for($i=0;$i<$result["count"];$i++){
                $GroupsOf[$result[$i]["gidnumber"][0]]=$result[$i]["cn"][0];
            }
            return  $GroupsOf;
        }
    }



    function hash_get_ou($has_key=false):array{
        $debugThis=false;
        $keyMem=null;
        if($has_key){$keyMem="HASKEY";}

        if($GLOBALS["DEBUG_TEMPLATE"]){$debugThis=true;}
        if($debugThis){echo "<li>hash_get_ou():: Starting: haskey=$keyMem ".__LINE__."</li>\n";}
        if($this->IsKerbAuth()){
            $ldap=new external_ad_search();
            return $ldap->hash_get_ou($has_key);
        }



        if($this->EnableManageUsersTroughActiveDirectory){
            if($debugThis){echo "<li>hash_get_ou():: EnableManageUsersTroughActiveDirectory enabled:".__LINE__."</li>\n";}
            $ldap=new ldapAD();
            $res=$ldap->hash_get_ou($has_key);
            $GLOBALS["hash_get_ou_$keyMem"]=$res;
            return $res;
        }

        if(isset($GLOBALS["hash_get_ou_$keyMem"])){
            if($debugThis){echo "<li>hash_get_ou():: Return cache line:".__LINE__."</li>\n";}
            return $GLOBALS["hash_get_ou_$keyMem"];
        }else{
            if($debugThis){echo "<li>hash_get_ou()::hash_get_ou_$keyMem no cache line:".__LINE__."</li>\n";}
        }

        if(!$this->UseLdap){
            return array();
        }

        if($this->ldapFailed){
            $res=array();
            if($debugThis){echo "<li>hash_get_ou():: ldapFailed line:".__LINE__."</li>\n";}
            return $this->ActiveDirectoryOrganizations($has_key,$res);
        }
        $this->ldap_perform_connection();
        $ld =$this->ldap_connection;
        $suffix="dc=organizations,$this->suffix";
        $res=array();
        $arr=array("ou");
        if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} @ldap_list($ld,$suffix,'(&(objectclass=organizationalUnit)(ou=*)) ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
        $sr = @ldap_list($ld,$suffix,'(&(objectclass=organizationalUnit)(ou=*))',$arr);
        if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} @ldap_list DONE ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
        if($debugThis){echo "<li>hash_get_ou()::  @ldap_list DONE line:".__LINE__."</li>\n";}

        if ($sr) {
            if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} ldap_get_entries($ld,$sr) ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
            $hash=ldap_get_entries($ld,$sr);
            if(!is_array($hash)){
                if($debugThis){echo "<li>hash_get_ou():: Not an array:".__LINE__."</li>\n";}
                return $this->ActiveDirectoryOrganizations($has_key);

            }

            foreach ($hash as $ligne){
                if(!isset($ligne["ou"])){
                    continue;
                }
                if(!isset($ligne["ou"][0])){
                    continue;
                }

                if($ligne["ou"][0]<>null){
                    if(!preg_match('#dc=samba,#',$ligne["dn"])){
                        $ou=$ligne["ou"][0];
                        $pos=strpos($ou,',');
                        if($pos>1){$ou=substr($ou,0,$pos-1);}
                        if($ou=="sysusers"){continue;}
                        if($ou=="users"){continue;}
                        if($ou=="groups"){continue;}
                        if($ou=="computers"){continue;}
                        if($ou=="mounts"){continue;}
                        $GLOBALS["hash_get_ou_HASKEY"][$ou]=$ou;
                        if($has_key){$res[$ou]=$ou;}else{$res[]=$ou;}
                    }
                }
            }
        }else{
            if($debugThis){echo "<li>hash_get_ou()::  LDAP ERROR:".__LINE__."</li>\n";}
            $error=ldap_err2str(ldap_errno($this->ldap_connection));
            if($debugThis){error_log("[{$_SESSION["uid"]}]::{$GLOBALS["CURRENT_PAGE"]} LDAP ERROR $error".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
        }

        if($debugThis){echo "<li>hash_get_ou():: Array of ". count($res)." line:".__LINE__."</li>\n";}
        $res=$this->ActiveDirectoryOrganizations($has_key,$res);
        $GLOBALS["hash_get_ou_$keyMem"]=$res;
        return $res;

    }

    public function IsOUUnderActiveDirectory($ou){
        if($this->IsKerbAuth()==0){
            if($GLOBALS["VERBOSE"]){echo "IsKerbAuth return 0<br>\n";}
            return false;
        }

        $dn="ou=$ou,dc=organizations,$this->suffix";
        if($this->ExistsDN($dn)){
            if($GLOBALS["VERBOSE"]){echo "$dn exists under ldap...<br>\n";}
            return false;
        }
        include_once(dirname(__FILE__)."/class.external.ad.inc");
        $ad=new external_ad_search();
        $res=$ad->SearchOu($ou);
        if($GLOBALS["VERBOSE"]){echo "ad->SearchOu($ou) return $res<br>\n";}
        return $res;
    }

    public function IsKerbAuth() {
        $sock=new sockets();
        return $sock->IsKerbAuth();

    }


    private function ActiveDirectoryOrganizations($has_key=false,$array=array()):array{

        if($this->IsKerbAuth()==1){
            include_once(dirname(__FILE__)."/class.external.ad.inc");
            $ad=new external_ad_search();
            return $ad->hash_get_ou($has_key,$array);
        }
        if(!is_array($array)){
            $array=array();
        }
        return $array;
    }


    function ReadDNInfos($dn){
        //if(!$this->ldap_connection){$this->
        $search = @ldap_read($this->ldap_connection,$dn,'(objectClass=*)');
        $entry = @ldap_get_entries($this->ldap_connection, $search);
        return $this->_ReturnArray($entry);

    }


    function DNGetDatas($dn){
        if(preg_match('#ou=(.+?),dc#',$dn,$reg)){
            $return["ou"]=$reg[1];
            $pos=strpos($return["ou"],',');
            if($pos>0){$return["ou"]=substr($return["ou"],0,$pos);}
        }
        $attrs=array();
        $search = ldap_read($this->ldap_connection,$dn,'(objectClass=*)',$attrs);
        if (! $search){return array();}
        if ($entry_id = ldap_first_entry($this->ldap_connection,$search))
            while ($entry_id) {
                $dn = ldap_get_dn($this->ldap_connection,$entry_id);
                $return['dn'] = $dn;
                $attrs = ldap_get_attributes($this->ldap_connection,$entry_id);
                $return=$return + $this->_ReturnArray($attrs);
                $entry_id = ldap_next_entry($this->ldap_connection,$entry_id);

            }

        if (is_array($return)){ksort($return);}
        return $return;
    }


    function OUDatas($ou){
        $dn="ou=$ou,dc=organizations,$this->suffix";
        $upd=array();
        $search =@ldap_read($this->ldap_connection,"ou=$ou,dc=organizations,$this->suffix",'(objectClass=*)');
        $res["dn"]=$dn;
        if (! $search){return $res;}
        $entry_id = ldap_first_entry($this->ldap_connection,$search);
        $attrs = ldap_get_attributes($this->ldap_connection,$entry_id);
        if(!isset($attrs["ArticaGroupPrivileges"])){$attrs["ArticaGroupPrivileges"]=null;}
        if(!isset($attrs["OuTrustMyUSers"])){$attrs["OuTrustMyUSers"]=null;}
        // privileges

        if(!is_array($attrs["ArticaGroupPrivileges"])){
            $upd["ArticaGroupPrivileges"][0]="DEFAULT";
            $this->Ldap_add_mod($dn,$upd);
            $res["ArticaGroupPrivileges"]="DEFAULT";
        }else{
            $res["ArticaGroupPrivileges"]=$attrs["ArticaGroupPrivileges"][0];
        }

        if(!isset($attrs["cn"])){

            $res["cn"]=$ou;
            $res["dn"]=$dn;
            $updER["cn"]=$ou;
            if(!$this->Ldap_add_mod($dn, $updER)){
                writelogs("Failed cn=$ou -> $this->ldap_last_error",__CLASS__,__FUNCTION__,__FILE__,__LINE__);
            }
        }

        //Fake sender  -----------------------------------------------------------------------------------------

        if(!isset($attrs["ArticaFakedMailFrom"])){$attrs["ArticaFakedMailFrom"][0]="pass";}

        if(!is_array($attrs["ArticaFakedMailFrom"])){
            $upd["ArticaFakedMailFrom"][0]="pass";
            $this->Ldap_add_mod($dn,$upd);
            $res["ArticaFakedMailFrom"]="pass";
        }else{
            $res["ArticaFakedMailFrom"]=$attrs["ArticaFakedMailFrom"][0];
        }
        //----------------------------------------------------------------------------------------------------

        //Fake sender  -----------------------------------------------------------------------------------------
        if(!isset($attrs["ArticaDenyNoMXRecords"])){$attrs["ArticaDenyNoMXRecords"][0]="pass";}

        if(!is_array($attrs["ArticaDenyNoMXRecords"])){
            $upd["ArticaDenyNoMXRecords"][0]="pass";
            $this->Ldap_add_mod($dn,$upd);
            $res["ArticaDenyNoMXRecords"]="pass";
        }else{
            $res["ArticaDenyNoMXRecords"]=$attrs["ArticaDenyNoMXRecords"][0];
        }
        //----------------------------------------------------------------------------------------------------

        //quarantine  -----------------------------------------------------------------------------------------
        if(!isset($attrs["ArticaMaxDayQuarantine"])){$attrs["ArticaMaxDayQuarantine"][0]="3";}
        if(!is_array($attrs["ArticaMaxDayQuarantine"])){
            $upd["ArticaMaxDayQuarantine"][0]="3";
            $this->Ldap_add_mod($dn,$upd);
            $res["ArticaMaxDayQuarantine"]="3";
        }else{
            $res["ArticaMaxDayQuarantine"]=$attrs["ArticaMaxDayQuarantine"][0];
        }
        //----------------------------------------------------------------------------------------------------
        if(isset($attrs["VirtualDomainsMapsMTA"])){
            if(is_array($attrs["VirtualDomainsMapsMTA"])){
                for($i=0;$i<$attrs["VirtualDomainsMapsMTA"]["count"];$i++){
                    $res["VirtualDomainsMapsMTA"][$attrs["VirtualDomainsMapsMTA"][$i]]=$attrs["VirtualDomainsMapsMTA"][$i];
                }
            }
        }
        //Country deny list  -----------------------------------------------------------------------------------------
        if(isset($attrs["CountryDeny"])){
            if(is_array($attrs["CountryDeny"])){
                for($i=0;$i<$attrs["CountryDeny"]["count"];$i++){
                    $res["CountryDeny"][$i]=$attrs["CountryDeny"][$i];
                }
            }
        }
        //RBL  -----------------------------------------------------------------------------------------


        //trust users  -----------------------------------------------------------------------------------------
        if(!is_array($attrs["OuTrustMyUSers"])){
            unset($upd);
            $upd["OuTrustMyUSers"]="yes";
            $this->Ldap_add_mod($dn,$upd);
            $res["OuTrustMyUSers"]="yes";
        }else{
            $res["OuTrustMyUSers"]=$attrs["OuTrustMyUSers"][0];
        }


        //ObjectClass
        for($i=0;$i<$attrs["objectClass"]["count"];$i++){
            $res["objectClass"][$attrs["objectClass"][$i]]=true;

        }
        if(!$res["objectClass"]["ArticaSettings"]){
            unset($upd);
            writelogs("objectClass ArticaSettings does not exists, add it...",__CLASS__,__FUNCTION__,__FILE__,__LINE__);
            $upd["objectClass"]="ArticaSettings";
            if(!$this->Ldap_add_mod($dn, $upd)){
                writelogs("Failed $this->ldap_last_error",__CLASS__,__FUNCTION__,__FILE__,__LINE__);
            }
        }


        return $res;
    }


    function Hash_get_ou_blacklisted_domains($ou,$find='*'){
        $this->ldap_perform_connection();
        $update_array=array();
        $dn="cn=blackListedDomains,ou=$ou,dc=organizations,$this->suffix";
        if(!$this->ExistsDN($dn)){
            $update_array["cn"][]='blackListedDomains';
            $update_array["objectClass"][]='PostFixStructuralClass';
            $update_array["objectClass"][]='top';
            $this->ldap_add($dn,$update_array);
        }
        $arr=array("cn");
        $search = @ldap_search($this->ldap_connection,$dn,"(&(objectClass=DomainsBlackListOu)(cn=$find))");
        if (! $search){return array();}
        $hash=ldap_get_entries($this->ldap_connection,$search);
        for($i=0;$i<$hash["count"];$i++){
            $res[]=$hash[$i]["cn"][0];
        }

        return $res;
    }


    function hash_get_ou_full_datas($listefiled=0){
        $this->ldap_perform_connection();
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $suffix="dc=organizations,$this->suffix";
        writelogs($suffix . '= >(&(objectclass=organizationalUnit)(ou=*))',__FUNCTION__,__FILE__);
        $arr=array("ou","dn");
        $sr = @ldap_search($ld,$suffix,'(&(objectclass=organizationalUnit)(ou=*))',$arr);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            foreach ($hash as $num=>$ligne){
                if($ligne["ou"][0]=="sysusers"){continue;}
                if($ligne["ou"][0]==null){continue;}
                if(preg_match('#dc=samba,#',$ligne["dn"])){continue;}
                $ou=$ligne["ou"][0];
                $pos=strpos($ou,',');
                if($pos>1){$ou=substr($ou,0,$pos-1);}
                if($ou=="sysusers"){continue;}
                if($ou=="users"){continue;}
                if($ou=="groups"){continue;}
                if($ou=="computers"){continue;}
                switch ($listefiled) {
                    case 1:$res[$ligne["ou"][0]]=$ligne["ou"][0];break;
                    case 2:$res[]=$ligne["ou"][0];break;
                    default:$res[$ligne["ou"][0]]=$ligne["dn"];	break;
                }


            }
        }

        return $res;

    }

    function Hash_Get_ou_from_users($userid,$listeField=0){
        $this->ldap_perform_connection();
        $arr=array("cn");
        $res=array();
        if($GLOBALS["VERBOSE"]){echo "$this->suffix -> (&(objectclass=posixGroup)(memberUid=$userid))<br>\n";}
        $sr = @ldap_search($this->ldap_connection,$this->suffix,"(&(objectclass=posixGroup)(memberUid=$userid))",$arr);
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            for($i=0;$i<$hash["count"];$i++){
                $dn=$hash[$i]["dn"];
                if($GLOBALS["VERBOSE"]){echo "DN: $dn<br>\n";}
                if(preg_match('#cn=.*?,ou=groups,ou=(.*?),dc=#',$dn,$reg)){
                    if($listeField==0){$res[$reg[1]]="ou={$reg[1]},$this->suffix";}else{$res[]=$reg[1];}
                    continue;
                }

                if(preg_match('#cn=(.+?),ou=#',$dn,$rs)){$cn=$rs[1];}
                if(preg_match('#ou=users,ou=(.+?),dc#',$dn,$reg)){
                    if($listeField==0){$res[$reg[1]]="ou={$reg[1]},$this->suffix";}else{$res[]=$reg[1];}
                }
            }
            return $res;}
    }

    function GroupDeleteUser($gid,$userid){
        $hash=$this->GroupDatas($gid);
        $users=$hash["members"];
        $update_array["memberUid"]=$userid;
        writelogs("Delete $userid user from group $gid",__CLASS__ . "/" . __FUNCTION__,__FILE__ );
        if($this->Ldap_del_mod($hash["dn"],$update_array)){return true;}

    }







    function Hash_vacation_domains(){
        $suffix="cn=PostfixVacationTransport,cn=artica,$this->suffix";
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $arr=array();
        $sr =@ldap_search($ld,$suffix,'(transport=*)',$arr);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            print_r($hash);
        }
    }


    function Hash_associated_domains($ou=null){
        $this->ldap_perform_connection();
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        if($ou<>null){
            $suffix="ou=$ou,dc=organizations,$this->suffix";
        }else{$suffix=$this->suffix;}
        writelogs("pattern:(associatedDomain=*) in $suffix og=$ou",__CLASS__ . "/" . __FUNCTION__,__FILE__ ,__LINE__);
        $arr=array("associatedDomain");
        $sr =@ldap_search($ld,$suffix,'(associatedDomain=*)',$arr);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            writelogs("pattern: " . count($hash) . " entries",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__ );
            for($i=0;$i<$hash["count"];$i++){
                for($j=0;$j<$hash[$i]["associateddomain"]["count"];$j++){
                    writelogs("pattern: {$hash[$i]["associateddomain"][$j]} entries",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__ );
                    $res[$hash[$i]["associateddomain"][$j]]="associateddomain";
                }
            }
        }else{
            $ldap_errno=ldap_errno($this->ldap_connection);
            $ldap_err2str=ldap_err2str($ldap_errno);
            writelogs("Ldap error $ldap_errno $ldap_err2str on $suffix",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__ );
        }
        return $res;
    }


    function ou_by_smtp_domain($domain){
        $this->ldap_perform_connection();
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(associatedDomain=$domain)",array());
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            for($i=0;$i<$hash['count'];$i++){
                if(preg_match("#ou=(.+?),dc=organizations,$this->suffix#",$hash[$i]["dn"],$re)){
                    return $re[1];
                }
            }

        }

        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(&(objectclass=transportTable)(cn=$domain))",array());
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            for($i=0;$i<$hash['count'];$i++){
                if(preg_match("#ou=(.+?),dc=organizations,$this->suffix#",$hash[$i]["dn"],$re)){
                    return $re[1];
                }
            }

        }


    }

    function CountDeDomainsOU($ou){
        return count($this->Hash_domains_table($ou));
    }

    function CountDeGroups($ou){
        return count($this->hash_groups($ou));

    }


    function Hash_domains_table($ou=null){


        // !!!!!! For postfix a dev
        if($this->IsKerbAuth()){return array();}
        $res=array();
        $this->ldap_perform_connection();
        $ld =$this->ldap_connection;
        $log=null;
        if($ou<>null){$suffix="ou=$ou,dc=organizations,$this->suffix";}else{$suffix=$this->suffix;}

        //--------------------- associatedDomain


        $arr=array("associatedDomain");


        $sr =@ldap_search($ld,$suffix,'(associatedDomain=*)',$arr);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            for($i=0;$i<$hash["count"];$i++){
                for($j=0;$j<$hash[$i]["associateddomain"]["count"];$j++){
                    $res[$hash[$i]["associateddomain"][$j]]="associateddomain";
                    $log=$log . $hash[$i]["associateddomain"][$j].";";
                }
            }
        }


        //--------------------- TransportTable
        $log="";
        $arr=array("cn","dn");
        $sr =@ldap_search($ld,$suffix,'(&(objectclass=transportTable)(cn=*))',$arr);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            for($i=0;$i<$hash["count"];$i++){
                $dom=$hash[$i]["cn"][0];
                $log=$log . $dom.";";
                if(preg_match("#(.+?)@(.+)#",$dom)){continue;}
                if(preg_match("#vacation\.#",$dom)){continue;}
                $res[$dom]="dn";
            }
        }
        return $res;

    }
    function add_domains_to_VirtualDomainsMapsMTA($ou,$domain):bool{
        $upd=array();
        $dn="ou=$ou,dc=organizations,$this->suffix";
        if($ou==null){writelogs("unable to open null ou...'",__FUNCTION__,__FILE__);return false;}
        $hash=$this->OUDatas($ou);
        $upd["VirtualDomainsMapsMTA"][]="@$domain";

        if($hash["VirtualDomainsMapsMTA"]["@$domain"]==null){
            return $this->Ldap_add_mod($dn,$upd);
        }
        return true;

    }

    function hash_get_domains_ou($ou,$nokey=false):array{
        $res=array();
        $hash=$this->Hash_domains_table($ou);
        if(is_array($hash)){
            foreach ($hash as $num=>$ligne){
                if($nokey){$res[]=$num;continue;}
                $res[$num]=$num;
            }
        }
        return $res;
    }

    function LOCAL_SID(){
        $sid=null;
        if(isset($GLOBALS[__CLASS__][__FUNCTION__])){return $GLOBALS[__CLASS__][__FUNCTION__];}

        if(isset($GLOBALS["SETTINGS_INC"])){
            if($GLOBALS["SETTINGS_INC"]["LOCAL_SID"]<>null){
                $GLOBALS[__CLASS__][__FUNCTION__]=$GLOBALS["SETTINGS_INC"]["LOCAL_SID"];
                return $GLOBALS["SETTINGS_INC"]["LOCAL_SID"];
            }
        }
        if(isset($GLOBALS["AS_ROOT"])){
            if(class_exists("unix")){
                $unix=new unix();
                $GLOBALS[__CLASS__][__FUNCTION__] = $unix->GET_LOCAL_SID();
                $GLOBALS["SETTINGS_INC"]["LOCAL_SID"]=$GLOBALS[__CLASS__][__FUNCTION__];
                return $GLOBALS["SETTINGS_INC"]["LOCAL_SID"];
            }
        }


        include_once(dirname(__FILE__)."/class.ini.inc");
        $sock=new sockets();
        if(!isset($GLOBALS[__CLASS__][__FUNCTION__]["SambaSMBConf"])){
            $ini=new Bs_IniHandler();
            $ini->loadString($sock->GET_INFO("SambaSMBConf"));
            $GLOBALS[__CLASS__][__FUNCTION__]["SambaSMBConf"]=$ini;
        }else{
            $ini=$GLOBALS[__CLASS__][__FUNCTION__]["SambaSMBConf"];
        }
        if(!isset($ini->_params["global"]["workgroup"])){$ini->_params["global"]["workgroup"]="*";}
        $filter="(&(objectClass=sambaDomain)(sambaDomainName=" . strtoupper($ini->_params["global"]["workgroup"])."))";
        $arr=array("sambaSID");
        if(is_null($this->ldap_connection)){
            return trim($sock->GET_INFO("LOCAL_SID"));
        }
        $sr =@ldap_search($this->ldap_connection,$this->suffix,$filter,$arr);
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            if(isset($hash[0]["sambasid"][0])){$sid=trim($hash[0]["sambasid"][0]);}

        }

        if($sid==null){
            $sid=trim($sock->GET_INFO("LOCAL_SID"));

        }
        $GLOBALS["SETTINGS_INC"]["LOCAL_SID"]=$sid;
        $GLOBALS[__CLASS__][__FUNCTION__]=$sid;
        return $sid;
    }


    function domains_get_locals_domains($ou){
        $hash=$this->Hash_domains_table($ou);
        if(!is_array($hash)){return array();}
        foreach ($hash as $num=>$ligne){
            if($ligne=="associateddomain"){
                $res[$num]=true;
            }
        }
        return $res;
    }


    function domainsDelete($ou,$ArrayNum){
        $table=$this->hash_get_domains_ou($ou);
        //associatedDomain;
        unset($table[$ArrayNum]);
        foreach ($table as $num=>$ligne){
            $update_array["associatedDomain"][]=$ligne;
        }

        $this->Ldap_modify("ou=$ou,dc=organizations,$this->suffix",$update_array);

    }


    function Hash_get_users_groups($ou){
        $pos=strpos($ou,',');
        if($pos>0){$ou=substr($ou,0,$pos);}
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $suffix="ou=$ou,dc=organizations,$this->suffix";
        $arr=array("memberUid");
        $sr =@ldap_search($ld,$suffix,'(objectclass=posixGroup)',$arr);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            for($i=0;$i<$hash["count"];$i++){
                for($t=0;$t<$hash[$i]["memberuid"]["count"];$t++){
                    $array_users[$hash[$i]["memberuid"][$t]]=$hash[$i]["memberuid"][$t];
                }

            }

        }
        return $array_users;

    }

    function COUNT_DE_USERS(){
        if($this->ldapFailed){return 0;}
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $suffix=$this->suffix;
        $arr=array("cn","displayname");
        $sr = @ldap_search($ld,"dc=organizations,$suffix",'(objectclass=userAccount)',$arr);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            return $hash["count"];
        }

        return 0;

    }


    function hash_get_users_Only_ou($ou){
        $pos=strpos($ou,',');
        if($pos>0){$ou=substr($ou,0,$pos);}
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $suffix="ou=$ou,dc=organizations,$this->suffix";

        $arr=array("uid");
        $hashGroups=$this->Hash_get_users_groups($ou);

        writelogs(count($hashGroups) ." groups",__FUNCTION__,__FILE__);
        $sr =@ldap_search($ld,$suffix,'(objectclass=userAccount)',$arr);
        if ($sr) {

            $hash=ldap_get_entries($ld,$sr);

            for($i=0;$i<$hash["count"];$i++){
                $userid=$hash[$i]["uid"][0];
                if(isset($hashGroups[$userid])){continue;}
                $res[]=$userid;
            }
        }
        return $res;
    }

    function hash_users_ou($ou=null){
        $pos=strpos($ou,',');
        if($pos>0){$ou=substr($ou,0,$pos);}
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $suffix=$this->suffix;
        $dn="ou=$ou,dc=organizations,$suffix";
        if(posix_getuid()==0){
            if($ou==null){
                $dn="dc=organizations,$suffix";
            }
        }

        $arr=array("cn","dn","uid","displayname");
        $sr = @ldap_search($ld,$dn,'(objectclass=userAccount)',$arr);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            foreach ($hash as $num=>$ligne){
                $displayname=trim($ligne["displayname"][0]);

                if($displayname==null){$displayname=$ligne["uid"][0];}
                $res[$ligne["uid"][0]]=$displayname;
            }
        }
        return $res;
    }

    function hash_get_local_domains(){
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $suffix=$this->suffix;
        writelogs($suffix . '= >objectclass=domainRelatedObject)(associatedDomain=*))',__CLASS__.'/'.__FUNCTION__,__FILE__);
        $filter=array("associateddomain");

        $sr = @ldap_search($ld,"dc=organizations,$suffix",'(&(objectclass=domainRelatedObject)(associatedDomain=*))',$filter);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);

            for($i=0;$i<$hash["count"];$i++){
                for($t=0;$t<$hash[$i]["associateddomain"]["count"];$t++){
                    $xdom=$hash[$i]["associateddomain"][$t];
                    if($xdom==null){continue;}
                    $arr[$xdom]=$xdom;
                }

            }
        }
        return $arr;
    }

//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    function hash_get_relay_recipients(){
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $dn="cn=relay_recipient_maps ,cn=artica,$this->suffix";
        $sr = @ldap_search($this->ldap_connection,$dn,'(&(objectclass=PostfixRelayRecipientMaps)(cn=*))');
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            if(!is_array($hash)){return null;}
            for($i=0;$i<$hash["count"];$i++){
                $arr[$hash[$i]["cn"][0]]=$hash[$i]["cn"][0];
            }
        }
        return $arr;
    }
//------------------------------------------------------------------------------------------------------------


    function hash_get_all_local_domains(){
        if($this->ldapFailed){return array();}
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $suffix="dc=organizations,$this->suffix";
        $sr = @ldap_search($ld,$suffix,'(&(objectclass=domainRelatedObject)(associatedDomain=*))');
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            for($i=0;$i<$hash["count"];$i++){
                for($z=0;$z<$hash[$i]["associateddomain"]["count"];$z++){
                    $xdom=$hash[$i]["associateddomain"][$z];
                    if(preg_match('#^vacation\.#',$xdom)){continue;}
                    $arr[$xdom]=$xdom;
                }
            }

        }else{
            writelogs("Could not find any domain in $suffix",__FUNCTION__.'/'.__CLASS__,__FILE__);
        }

        return $arr;
    }


    function hash_get_all_domains(){
        if(!isset($GLOBALS["VERBOSEDOMS"])){$GLOBALS["VERBOSEDOMS"]=false;}
        if($this->ldapFailed){
            if($GLOBALS["VERBOSEDOMS"]){echo "LDAP FAILED, RETURN...\n";}
            return array();
        }
        if(isset($GLOBALS["hash_get_all_domains"])){if(is_array($GLOBALS["hash_get_all_domains"])){return $GLOBALS["hash_get_all_domains"];}}
        $ld =$this->ldap_connection;
        if(!$ld){
            return array();
        }
        $arr=array();
        $filters=array("associateddomain");
        $suffix="dc=organizations,$this->suffix";
        $sr = @ldap_search($ld,$suffix,'(&(objectclass=domainRelatedObject)(associatedDomain=*))',$filters);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            for($i=0;$i<$hash["count"];$i++){
                for($z=0;$z<$hash[$i]["associateddomain"]["count"];$z++){
                    $xdom=$hash[$i]["associateddomain"][$z];
                    if(preg_match('#^vacation\.#',$xdom)){continue;}
                    $arr[$xdom]=$xdom;
                }
            }

        }else{
            writelogs("Could not find any domain in $suffix",__FUNCTION__.'/'.__CLASS__,__FILE__);
        }


        $sr = @ldap_search($ld,$suffix,'(&(objectclass=transportTable)(cn=*))',array('cn'));
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            if(is_array($hash)){
                foreach ($hash as $num=>$ligne){
                    $xdom=trim($ligne["cn"][0]);
                    if($xdom==null){continue;}
                    if(preg_match('#^vacation\.#',$xdom)){continue;}
                    $arr[$xdom]=$xdom;
                }
            }
        }

        if($GLOBALS["VERBOSEDOMS"]){echo "PowerDNS -> records\n";}
        $q=new mysql();
        if($q->TABLE_EXISTS("records", "powerdns")){
            $results = $q->QUERY_SQL("SELECT name FROM domains WHERE `type`='MASTER'","powerdns");
            if(!$q->ok){if($GLOBALS["VERBOSEDOMS"]){echo "$q->mysql_error -> records\n";}}
            while ($ligne = mysqli_fetch_assoc($results)) {
                if($ligne["name"]=="in-addr.arpa"){continue;}
                if($GLOBALS["VERBOSEDOMS"]){echo "{$ligne["name"]} -> records\n";}
                $arr[$ligne["name"]]=$ligne["name"];
            }
        }
        $GLOBALS["hash_get_all_domains"]=$arr;
        if($GLOBALS["VERBOSEDOMS"]){print_r($arr);}
        return $arr;


    }


    function GroupsSearch($pattern,$max=20){
        $cols=array("cn","description",'gidnumber');
        $dn="dc=organizations,$this->suffix";


        $ld =$this->ldap_connection;

        if($GLOBALS["VERBOSE"]){

            $sr = ldap_search($ld,$dn,'objectclass=posixGroup',$cols,null,$max);
        }else{
            $sr = ldap_search($ld,$dn,'objectclass=posixGroup',$cols,null,$max);
        }

        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);
                $errstr=@ldap_err2str($error);
                if($GLOBALS["VERBOSE"]){echo "Error: $error $errstr<br>\n";}
                return array();
            }


        }
        $ARRAY=array();
        $hash=ldap_get_entries($ld,$sr);
        if(!is_array($hash)){return array();}
        foreach ($hash as $num=>$ligne){
            $GroupName=$ligne["cn"][0];
            $GroupDN=$ligne["dn"];
            if($GLOBALS["VERBOSE"]){echo "$GroupDN = $GroupName<br>\n";}
            $ARRAY[$GroupDN]=$GroupName;
        }


        return $ARRAY;
    }


    function hash_groups($ou,$fieldlist=0){
        $res=array();
        if($this->IsKerbAuth()){
            $ldap=new external_ad_search();
            return $ldap->hash_groups($ou,$fieldlist);
        }

        if($this->EnableManageUsersTroughActiveDirectory){$ldap=new ldapAD();return $ldap->hash_groups($ou,$fieldlist);}

        if($this->ldapFailed){
            writelogs("ldapFailed !",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();
        }
        if($ou==null){
            writelogs("OU NULL !",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();
        }

        $pos=strpos($ou,',');
        if($pos>0){$ou=substr($ou,0,$pos);}
        $ld =$this->ldap_connection;
        $cols=array("cn","description",'gidnumber');
        $dn="ou=groups,ou=$ou,dc=organizations,$this->suffix";
        $sr = @ldap_search($ld,"$dn",'objectclass=posixGroup',$cols);

        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            if(!is_array($hash)){return array();}
            foreach ($hash as $num=>$ligne){

                if (!isset($ligne["cn"])){
                    continue;
                }

                if(!isset($ligne["cn"][0])){
                    $ligne["cn"][0]=null;
                }

                if($ligne["cn"][0]<>null){
                    switch ($fieldlist) {
                        case 1:$res[$ligne["gidnumber"][0]]=$ligne["cn"][0];break;
                        case 2;$res=$res . "{$ligne["gidnumber"][0]};{$ligne["cn"][0]}\n";break;
                        default:$res[$ligne["cn"][0]]=array("gid"=>$ligne["gidnumber"][0],"description"=>$ligne["description"][0]);break;
                    }
                }

            }
        }else{
            writelogs("Unable to find groups (objectclass=posixGroup) in $dn",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        }
        writelogs(count($res). " groups found for $ou ($dn)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        return $res;
    }

    function GroupAllDatas($gidNumber){
        if($this->ldapFailed){return array();}
        if($gidNumber==null){return null;}
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(gidNumber=$gidNumber)");
        if($sr){
            $result = ldap_get_entries($this->ldap_connection, $sr);
            $res=$this->_ReturnArray($result[0]);
            return $res;
        }
    }

    function GroupGetMembersCount($gidNumber){
        if($this->ldapFailed){return array();}
        $attrs=array('memberUid','cn');
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(&(objectClass=posixGroup)(gidNumber=$gidNumber))",$attrs);
        if($sr){
            $result = ldap_get_entries($this->ldap_connection, $sr);
            $res=$result[0]["memberuid"]["count"];
            if(!is_numeric($res)){$res=0;}
            return $res;
        }else{return 0;}

    }


    function CountDeUsersByGroupDN($DN){

        $attrs=array('memberUid');
        $sr =@ldap_read($this->ldap_connection,$DN,"(objectclass=*)",$attrs);

        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);
                $errstr=@ldap_err2str($error);
                if($GLOBALS["VERBOSE"]){echo "Error: $error $errstr<br>\n";}
                return 0;
            }
        }


        $result = ldap_get_entries($this->ldap_connection, $sr);
        if(!isset($result[0]["memberuid"]["count"])){return 0;}
        return $result[0]["memberuid"]["count"];
    }
    function GidNumberGroupDN($DN){

        $attrs=array('gidNumber');
        $sr =@ldap_read($this->ldap_connection,$DN,"(objectclass=*)",$attrs);

        if (!$sr) {
            if(is_numeric(ldap_errno($this->ldap_connection))){
                $error=ldap_errno($this->ldap_connection);
                $errstr=@ldap_err2str($error);
                if($GLOBALS["VERBOSE"]){echo "Error: $error $errstr<br>\n";}
                return 0;
            }
        }


        $result = ldap_get_entries($this->ldap_connection, $sr);
        if(!isset($result[0]["gidnumber"][0])){return 0;}
        return $result[0]["gidnumber"][0];
    }


    function UserGetGroups($userid,$reverse=0):array{
        if($this->IsKerbAuth()){
            include_once(dirname(__FILE__))."/class.external.ad.inc";
            $p=new external_ad_search();
            $u=new user($userid);
            if($u->AsActiveDirectoryMember){
                return $p->GroupsOfMember($u->dn);

            }
        }
        if($this->ldapFailed){return array();}
        if($userid==null){return array();}
        if(!$this->ldap_connection){
            return array();
        }
        if(!is_object($this->ldap_connection)){
            return array();
        }
        $res=array();
        $attrs=array('gidNumber','cn');
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(&(objectClass=posixGroup)(memberUid=$userid))",$attrs);
        if($sr){
            $result = ldap_get_entries($this->ldap_connection, $sr);
            if(!is_array($result)){return array();}
            for($i=0;$i<$result["count"];$i++){
                if($reverse==1){$res[$result[$i]["gidnumber"][0]]=$result[$i]["cn"][0];}
                else{$res[$result[$i]["cn"][0]]=$result[$i]["gidnumber"][0];}

            }

            if(function_exists("VERBOSE")){VERBOSE("$userid is in " . count($res)." groups",__LINE__);}
            return  $res;
        }
        return array();
    }
    function HashMembersFromOU($ou):array{
        if($this->ldapFailed){return array();}
        $user=array();
        $dn="ou=$ou,dc=organizations,$this->suffix";
        $attrs=array("uid");
        $filter="(&(objectClass=userAccount)(cn=*))";
        $h=$this->Ldap_search($dn,$filter,$attrs);
        for($i=0;$i<$h["count"];$i++){
            $user[$h[$i]["uid"][0]]=$h[$i]["uid"][0];

        }

        return $user;


    }


    function UserAndGroupSearch($ou=null,$tofind=null,$max=0,$OnlyGroups=false,$OnlyUsers=false){


        $filter="(&(|(objectClass=userAccount)(objectClass=posixGroup))(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(uid=$tofind) (givenname=$tofind) ))";

        if($OnlyGroups){
            $filter="(&(objectClass=posixGroup)(|(cn=$tofind)(gidnumber=$tofind)))";
        }

        if($OnlyUsers){
            $filter="(&(objectClass=userAccount)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(uid=$tofind) (givenname=$tofind) ))";
        }

        $attrs=array("displayName","description","uid","memberUid","cn","mail","objectClass","gidNumber","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");
        if($ou==null){$dn=$this->suffix;}else{$dn="ou=$ou,dc=organizations,$this->suffix";}
        $hash=$this->Ldap_search($dn,$filter,$attrs,$max);
        return $hash;
    }


    function UserSearch($ou=null,$tofind=null,$max=0){
        if(!isset($GLOBALS["OUTPUT_DEBUG"])){$GLOBALS["OUTPUT_DEBUG"]=false;}

        if($this->EnableManageUsersTroughActiveDirectory){
            $ldap=new ldapAD();
            $hash=$ldap->UserSearch($ou,$tofind);
            return array($hash,array());

        }


        $uid=$_SESSION["uid"];
        if($tofind==null){$tofind='*';}else{$tofind="*$tofind*";}
        $tofind=str_replace("*****","*",$tofind);
        $tofind=str_replace("****","*",$tofind);
        $tofind=str_replace("***","*",$tofind);
        $tofind=str_replace("**","*",$tofind);

        $filter="(&(objectClass=userAccount)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(uid=$tofind) (givenname=$tofind) ))";
        $attrs=array("displayName","cn","uid","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber");



        if($ou==null){$dn=$this->suffix;}else{$dn="ou=$ou,dc=organizations,$this->suffix";}
        if($GLOBALS["OUTPUT_DEBUG"]){echo "Search $dn<br>";}
        if($GLOBALS["OUTPUT_DEBUG"]){echo "Filter $filter<br>";}

        $hash=$this->Ldap_search($dn,$filter,$attrs,$max);

        if($GLOBALS["OUTPUT_DEBUG"]){echo "HASH:Address book contains ".count($hash). " rows <br>";}


        if($ou==null){$dn=$this->suffix;}else{$dn="ou=$uid,ou=People,dc=$ou,dc=NAB,$this->suffix";}
        $filter="(&(objectClass=evolutionPerson)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(sn=$tofind)(givenname=$tofind)(mozillaSecondEmail=$tofind)(fileAs=$tofind) ))";

        if($GLOBALS["OUTPUT_DEBUG"]){echo "Search $dn<br>";}
        if($GLOBALS["OUTPUT_DEBUG"]){echo "Filter $filter<br>";}
        $hash2=$this->Ldap_search($dn,$filter,$attrs,$max);

        if($GLOBALS["OUTPUT_DEBUG"]){echo "HASH2:Address book contains ".count($hash2). " rows <br>";}
        return array($hash,$hash2);

    }

    function UserFromAuth($name){
        if($this->IsKerbAuth()){return;}
        $filter="(&(objectClass=UserArticaClass)(ArticaInterfaceLogon=$name))";
        $attrs=array("displayName","uid","mail","cn","Phone");
        $dn="dc=organizations,$this->suffix";
        $hash=$this->Ldap_search($dn,$filter,$attrs);
        return $hash;

    }

    function AllGroupsKavSettings(){
        $fl=array('KasperkyAVScanningDatas');
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(&(objectClass=posixGroup)(KasperkyAVScanningDatas=*))",$fl);
        if($sr){
            $result = ldap_get_entries($this->ldap_connection, $sr);
            for($i=0;$i<$result["count"];$i++){
                $conf[]=$result[$i]["kasperkyavscanningdatas"][0];
            }
        }else{writelogs("ldap_search return null for filter (&(objectClass=posixGroup)(KasperkyAVScanningDatas=*))");}

        return $conf;

    }

    function CyrusPassword($uid="cyrus"){
        $sr =@ldap_search($this->ldap_connection,"dc=organizations,$this->suffix","(cn=$uid)");
        if(!$sr){
            $this->writelogs("Unable to find : Cyrus admin",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return null;
        }

        $entry_id = ldap_first_entry($this->ldap_connection,$sr);
        if(!$entry_id){
            $this->writelogs( 'INFOS: bad value $entry_id: (' . $entry_id . ')  find: (cn=cyrus) -> aborting function search engine doesn`t found the pattern',__CLASS__.'/'.__FUNCTION__,__FILE__);
            if($this->CyrusAdminCreate()){return $this->ldap_password;}
        }
        $attrs = ldap_get_attributes($this->ldap_connection, $entry_id);
        if($GLOBALS["VERBOSE"]){$this->writelogs("find a password ".strlen($attrs["userPassword"][0]),__CLASS__.'/'.__FUNCTION__,__FILE__);}
        return $attrs["userPassword"][0];

    }


    private function writelogs($text=null,$function=null,$file_source=null,$line=null){
        if(!function_exists("VERBOSE")){return false;}
        $text=trim($text);
        $text=str_replace("\r\n"," ",$text);
        if($file_source<>null){$file_source=basename($file_source);}
        VERBOSE("File: $file_source $text",$line);
        return true;
        
    }


    function UserDatas($userid):array{
        if($this->ldapFailed){return array();}
        if($userid==null){return array();}
        if($userid==-100){return array();}
        $this->writelogs("Search query:(uid=$userid)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(uid=$userid)");
        if(!$sr){
            $this->writelogs("Unable to find : $userid",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();
        }
        $entry_id = ldap_first_entry($this->ldap_connection,$sr);
        if(!$entry_id){
            $this->writelogs( 'INFOS: bad value $entry_id: (' . $entry_id . ')  find: (uid=' . $userid . ') -> doesn`t found the pattern',__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return array();
        }
        $attrs = ldap_get_attributes($this->ldap_connection, $entry_id);
        $dn=$this->_Get_dn_userid($userid);
        $res=$this->_ReturnArray($attrs);

        $res["ARRAY_KEYS"]=$this->_ReturnArrayKeys($attrs);
        $classes=$this->getobjectClass(null,$dn);
        if($classes["ArticaSettings"]==null){$upd["objectClass"][]="ArticaSettings";}


        if($res["ARRAY_KEYS"]["KasperkyASDatasRules"]==null){
            $upd["KasperkyASDatasRules"][0]="DEFAULT";
            $this->Ldap_add_mod($dn,$upd);
        }
        if($res["ARRAY_KEYS"]["ArticaUserFilterRule"]==null){
            unset($upd);
            $upd["ArticaUserFilterRule"][]="DEFAULT";
            $this->Ldap_add_mod($dn,$upd);
        }

        if($res["ARRAY_KEYS"]["FTPStatus"]==null){
            unset($upd);
            $upd["objectClass"]="PureFTPdUser";
            $upd["FTPStatus"]="FALSE";
            $upd["FTPQuotaMBytes"]="1000";
            $upd["FTPQuotaFiles"]="50";
            $upd["FTPDownloadBandwidth"]="550";
            $upd["FTPUploadBandwidth"]="550";
            $upd["FTPDownloadRatio"]="5";
            $upd["FTPUploadRatio"]="1";
            $this->Ldap_add_mod($dn,$upd);
            writelogs("Adding ftp settings: dn=$dn",__CLASS__.'/'.__FUNCTION__,__FILE__);
            $res["FTPStatus"]="FALSE";
            $res["FTPQuotaMBytes"]="1000";
            $res["FTPQuotaFiles"]="50";
            $res["FTPDownloadBandwidth"]="550";
            $res["FTPUploadBandwidth"]="550";
            $res["FTPDownloadRatio"]="5";
            $res["FTPUploadRatio"]="1";
        }else{
            $res["FTPStatus"]=$attrs["FTPStatus"][0];
            $res["FTPQuotaMBytes"]=$attrs["FTPQuotaMBytes"][0];
            $res["FTPQuotaFiles"]=$attrs["FTPQuotaFiles"][0];
            $res["FTPDownloadBandwidth"]=$attrs["FTPDownloadBandwidth"][0];
            $res["FTPUploadBandwidth"]=$attrs["FTPUploadBandwidth"][0];
            $res["FTPDownloadRatio"]=$attrs["FTPDownloadRatio"][0];
            $res["FTPUploadRatio"]=$attrs["FTPUploadRatio"][0];
        }
        if($res["displayName"]==null){$res["displayName"]=$res["givenName"] . " " . $res["sn"];}
        if($res["displayName"]==null){	$res["displayName"]=$res["uid"];}
        $res["privileges"]=$this->_Get_privileges_userid($userid);
        $res["dn"]=$dn;
        $res["groups"]=$this->UserGetGroups($userid);

        if(preg_match('#ou=users,ou=(.+?),#',$res["dn"],$reg)){$res["ou"]=$reg[1];}

        $res["SenderCanonical"]=$attrs["SenderCanonical"][0];

        for($i=0;$i<$attrs["mailAlias"]["count"];$i++){
            $aliases[]=$attrs["mailAlias"][$i];
        }

        for($i=0;$i<$attrs["ArticaUserFilterRule"]["count"];$i++){
            $res["RegexRules"][]=$attrs["ArticaUserFilterRule"][$i];
        }



        $res["mailAlias"]=$aliases;
        $res["telephoneNumber"]=$attrs["telephoneNumber"][0];
        $res["mobile"]=$attrs["mobile"][0];;
        $res["title"]=$attrs["title"][0];
        $res["CP"]=$attrs["postalCode"][0];
        $res["postalAddress"]=$attrs["postalAddress"][0];
        $res["street"]=$attrs["street"][0];
        $res["town"]=$attrs["l"][0];
        $res["BP"]=$attrs["postOfficeBox"];
        $res["SieveScriptsUser"]=$attrs["SieveScriptsUser"][0];
        $res["KasperkyASDatasRules"]=$attrs["KasperkyASDatasRules"];
        $res["homeDirectory"]=$attrs["homeDirectory"][0];


        for($i=0;$i<$attrs["FetchMailsRules"]["count"];$i++){
            $res["FetchMailsRulesSources"][]=$attrs["FetchMailsRules"][$i];
            $fetch[]=$this->ParseMyLdapInisData($attrs["FetchMailsRules"][$i]);
        }

        $res["fetchmail"]=$fetch;

        for($i=0;$i<$attrs["KasperkyASDatasAllow"]["count"];$i++){
            $whitel[]=$attrs["KasperkyASDatasAllow"][$i];
        }
        $res["WhiteList"]=$whitel;
        for($i=0;$i<$attrs["KasperkyASDatasDeny"]["count"];$i++){
            $Blackl[]=$attrs["KasperkyASDatasDeny"][$i];
        }

        $res["blacklist"]=$Blackl;
        return $res;
    }

    function ParseMyLdapInisData($datas):array{
        $tbl=explode("\n",$datas);
        $array=array();
        foreach ($tbl as $ligne){
            $value=null;$key=null;
            if(preg_match('#\[(.+)\]#',$ligne,$reg1)){$key=$reg1[1];}
            if(preg_match('#"(.+)"#',$ligne,$reg1)){$value=$reg1[1];}
            $array[$key]=$value;
        }
        return $array;

    }

    function _Get_dn_userid($userid):string{
        if($userid==null){return "";}
        if(preg_match("#AD:([0-9]+):(.+?):#",$userid)){return "";}
        $userid=$this->ldap_escape($userid);
        if(!$this->ldap_connection){return "";}
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(uid=$userid)");
        if(!$sr){return "";}
        $result = ldap_get_entries($this->ldap_connection, $sr);
        if(isset($result[0]["dn"])){
            return trim($result[0]["dn"]);
        }
        $this->writelogs("Unable to find dn (uid=$userid)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        return "";

    }

    function _Get_privileges_ou($userid=null,$ou=null,$array=array()){
        if($ou==null){
            $userdn=$this->_Get_dn_userid($userid);
            if(!preg_match("#ou=users,ou=(.+?),dc#",$userdn,$re)){return array();}
            $ou=$re[1];
        }


        $hash=$this->OUDatas($ou);
        $privs=$this->_ParsePrivieleges($hash["ArticaGroupPrivileges"]);

        foreach ($privs as $num=>$ligne){if($ligne=='yes'){$array[$num]="yes";}}
        return $array;
    }


    function _Get_privileges_userid($userid){

        if(!$this->UseLdap){
            $sqlite=new lib_sqlite();
            return $sqlite->_Get_privileges_userid($userid);
        }

        $org_priv   = array();
        $privs      = array();
        $filters    = array("gidNumber","ArticaGroupPrivileges");
        $dr         = ldap_search($this->ldap_connection,
            $this->suffix,"(&(objectClass=posixGroup)(memberUid=$userid))",$filters);
        $result     = ldap_get_entries($this->ldap_connection, $dr);


        if($result){
            for($i=0;$i<$result["count"];$i++){
                $privs=$this->_ParsePrivieleges($result[$i]["articagroupprivileges"][0],$privs);
                $privs=$this->_Get_privileges_ou($userid,null,$privs);
                if(is_array($privs)){
                    foreach ($privs as $num=>$ligne){
                        if($ligne=="yes"){$org_priv[$num]="yes";}
                        if($ligne=="1"){$org_priv[$num]="1";}
                        if($num=="ForceLanguageUsers"){$org_priv[$num]=$ligne;}
                        if($num=="VirtualSambaServer"){$org_priv["VIRTUALS_SERVERS"][$ligne]=true;$org_priv["VirtualSambaServer"]=$ligne;}
                        if($num=="PostfixInstance"){$org_priv["POSTFIX_SERVERS"][$ligne]=true;$org_priv["PostfixInstance"]=$ligne;}


                    }
                    unset($privs);
                }
            }

            if(is_array($org_priv)){
                $conf=null;
                foreach ($org_priv as $num=>$ligne){
                    //writelogs("PRIV:$num=$ligne",__CLASS__.'/'.__FUNCTION__,__FILE__);
                    $conf=$conf."[$num]=\"$ligne\"\n";
                }

            }
        }
        return $conf;}

    function _ParsePrivieleges($content=null,$array=array(),$debug=false):array{
        if(!$debug){if(isset($GLOBALS["DEBUG_PRIVS"])){$debug=true;}}
        if($debug){
            $trace=debug_backtrace();
            $called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
            writelogs("$called",__CLASS__.'/'.__FUNCTION__,__FILE__);
            $strlen=0;
            if(!is_null($content)){
                $strlen=strlen($content);
            }
            writelogs("content = $strlen bytes $content",__CLASS__.'/'.__FUNCTION__,__FILE__);
        }

        if(is_null($content)){
            return array();
        }
        $priv=array();
        if(preg_match_all('#\[([a-zA-Z]+)\]="(.+?)"#is',$content,$reg)){
            if($debug){writelogs("reg[1]=".count($reg[1])." attrs",__CLASS__.'/'.__FUNCTION__,__FILE__);}

            foreach ($reg[1] as $num=>$ligne){
                if(trim($reg[2][$num])==null){
                    writelogs("$ligne=NULL, Abort",__CLASS__.'/'.__FUNCTION__,__FILE__);
                    continue;}
                if($debug){writelogs("$ligne={$reg[2][$num]}",__CLASS__.'/'.__FUNCTION__,__FILE__);}
                if($ligne=="VirtualSambaServer"){$priv["VIRTUALS_SERVERS"][$reg[2][$num]]=true;}
                if($ligne=="PostfixInstance"   ){$priv["POSTFIX_SERVERS"][$reg[2][$num]]=true;}
                $priv[$ligne]=$reg[2][$num];
                writelogs("$ligne={$reg[2][$num]}",__CLASS__.'/'.__FUNCTION__,__FILE__);
            }
        }else{
            if($debug){writelogs("content no match #\[([a-zA-Z]+)\]=\"(.+?)\"#is",__CLASS__.'/'.__FUNCTION__,__FILE__);}
        }

        if(!is_array($priv)){
            if($debug){writelogs("priv is not an array",__CLASS__.'/'.__FUNCTION__,__FILE__);}
            return $array;
        }
        if(!is_array($array)){
            $array=array();
        }

        foreach ($priv as $num=>$ligne){
            $array[$num]=trim($ligne);
        }
        return $array;
    }


    function Hash_all_mailboxesActives($ou=null){
        $suffix=$this->suffix;
        if($ou<>null){$suffix="ou=$ou,dc=organizations,$this->suffix";}
        $filters=array("uid","userPassword");
        $dr =ldap_search($this->ldap_connection,$suffix,"(&(objectClass=userAccount)(MailboxActive=TRUE))",$filters);

        if($dr){
            $results = ldap_get_entries($this->ldap_connection,$dr);
            for($i=0;$i<$results["count"];$i++){

                $res[$results[$i]["uid"][0]]=$results[$i]["userpassword"][0];

            }
            return $res;
        }else{
            writelogs("$suffix : (&(objectClass=userAccount)(MailboxActive=TRUE)) error...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
        }

    }





    function _ReturnArrayKeys($array_search){
        if(!is_array($array_search)){return null;}
        $array_keys=array_keys($array_search);
        foreach ($array_keys as $num=>$key){
            if(!is_numeric($key)){
                $return_array[$key]=true;}

        }


        return $return_array;
    }


    function _ReturnArray($array_search){
        if(!is_array($array_search)){return null;}


        if(isset($array_search["count"])){if(is_array($array_search[0])){$array_search=$array_search[0];}}

        for($i=0;$i<$array_search["count"];$i++){
            $keys[]=$array_search[$i];
        }

        foreach ($keys as $num=>$ligne){
            if($array_search[$ligne]["count"]==1){
                $return_array[$ligne]=$array_search[$ligne][0];
            }else{
                for($z=0;$z<$array_search[$ligne]["count"];$z++){
                    $res[]=$array_search[$ligne][$z];
                }
                $return_array[$ligne]=$res;
            }


        }

        if(isset($array_search["dn"])){$return_array["dn"]=$array_search["dn"];}

        return $return_array;

    }


    function GroupForbiddenAttachment($attach_group,$gid,$add=false){
        $arr=array('FiltersExtensionsGroupName');
        $sr =@ldap_search($this->ldap_connection,$this->suffix,"(&(gidnumber=$gid)(objectClass=*))",$arr);

        if(!$sr){return null;}
        $entry_id =@ldap_first_entry($this->ldap_connection,$sr);
        $dn = ldap_get_dn($this->ldap_connection,$entry_id);
        $attrs =@ldap_get_attributes($this->ldap_connection, $entry_id);

        for($i=0;$i<$attrs["FiltersExtensionsGroupName"]["count"];$i++){
            $arr[$attrs["FiltersExtensionsGroupName"][$i]]="yes";
        }

        if($add==true){
            if($arr[$attach_group]==null){
                $upd["FiltersExtensionsGroupName"]=$attach_group;
                return $this->Ldap_add_mod($dn,$upd);
            }else{return false;}
        }

        if($add==false){
            $upd["FiltersExtensionsGroupName"]=$attach_group;
            return $this->Ldap_del_mod($dn,$upd);
        }

    }

    function GroupIDFromName($ou,$groupname):string{
        $dn="ou=$ou,dc=organizations,$this->suffix";
        $sr =@ldap_search($this->ldap_connection,$dn,"(&(cn=$groupname)(objectClass=posixGroup))",array('gidNumber'));
        if(!$sr){return "";}
        $entry_id = ldap_first_entry($this->ldap_connection,$sr);
        if($entry_id){
            $attrs = ldap_get_attributes($this->ldap_connection, $entry_id);
            return $attrs["gidNumber"][0];
        }
        return "";
    }

    function GroupDatas($gidnumber){
        include_once(dirname(__FILE__) .'/class.groups.inc');
        $group=new groups($gidnumber);
        return $group->main_array;
    }

//-------------------------------------------------------------------------------------------------------------------------------------
    private function ReloadServicesAfterDomain($domain,$ou){
        $usr=new usersMenus();
        $sock=new sockets();
        if($usr->cyrus_imapd_installed){
            $sock->getFrameWork("cmd.php?cyrus-check-cyr-accounts=yes");
        }

        $POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
        if($POSTFIX_INSTALLED==1){
            $sock->getFrameWork("cmd.php?postfix-transport-maps=yes");
            if($usr->AMAVIS_INSTALLED){$sock->getFrameWork("cmd.php?amavis-restart=yes");}
            $EnableDKFilter=$sock->GET_INFO("EnableDkimMilter");
            if($EnableDKFilter==null){$EnableDKFilter=0;}
            if($EnableDKFilter==1){$sock->getFrameWork("services.php?reload-dkim=yes");}
            $PostmasterAutoCreate=$sock->GET_INFO("PostmasterAutoCreate");
            if(!is_numeric($PostmasterAutoCreate)){$PostmasterAutoCreate=1;}
            if($PostmasterAutoCreate==1){
                include_once(dirname(__FILE__)."/class.user.inc");
                $user=new user("postmaster");
                $user->domainname=$domain;
                $user->ou=$ou;
                $user->mail="postmaster@$domain";
                $user->password=time();
                if(!$user->add_user()){writelogs("Failed to add Postmaster $user->ldap_error",__CLASS__.'/'.__FUNCTION__,__FILE__);return;}
                $user=new user("postmaster");
                $user->add_alias("hostmaster@$domain");
                $user->add_alias("webmaster@$domain");
                $user->add_alias("abuse@$domain");
            }
        }

    }



//-------------------------------------------------------------------------------------------------------------------------------------
    function AddDomainEntity($ou=null,$domain=null){
        $sock=new sockets();
        $upd=array();
        $update_array=array();
        if($ou==null){
            echo "Cannot create a domain without an organization...\n";
            return false;
        }

        if(class_exists("usersMenus")){
            $LOCAL_MDA=false;
            $users=new usersMenus();
            if($users->cyrus_imapd_installed){$LOCAL_MDA=true;}
            if($users->ZARAFA_INSTALLED){$LOCAL_MDA=true;}

        }


        $hashdoms=$this->hash_get_all_domains();
        if(!isset($hashdoms[$domain])){$hashdoms[$domain]=null;}
        writelogs("hashdoms[$domain]=$hashdoms[$domain]",__CLASS__."/".__FUNCTION__,__FILE__);

        if($hashdoms[$domain]<>null){
            include_once(dirname(__FILE__).'/class.templates.inc');
            $tpl=new templates();
            $trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
            $this->ldap_last_error=$tpl->javascript_parse_text('{error_domain_exists}\nCalled:'.$called);
            return false;
        }


        $domain=trim($domain);

        $suffix=$this->suffix;
        $GLOBALS["DEBUGLOGS"][]="DN: ou=$ou,dc=organizations,$this->suffix";
        $cn="ou=$ou,dc=organizations,$this->suffix";
        if(!$this->ExistsDN($cn)){
            writelogs("$cn does not exists, create the domain",__CLASS__."/".__FUNCTION__,__FILE__);
            if(!$this->AddOrganization($ou)){echo "Unable to create Organization: $ou";return;}
        }


        $object=$this->getobjectClass($ou,$cn);

        if(!isset($object["domainRelatedObject"])){
            $GLOBALS["DEBUGLOGS"][]="[domainRelatedObject] does not exists, add it";
            writelogs( "[domainRelatedObject] does not exists, add it",__FUNCTION__,__FILE__);
            $upd=$this->getobjectClass($ou,$cn,true);

            $GLOBALS["DEBUGLOGS"][]="[\"objectClass\"][]=\"domainRelatedObject\"";
            $GLOBALS["DEBUGLOGS"][]="[\"associatedDomain\"]=\"$domain\"";

            $upd["objectClass"][]="domainRelatedObject";
            $upd["associatedDomain"]=$domain;
            if(!$this->Ldap_modify($cn,$upd)){
                writelogs("Failed add classes:" .implode("\n",$upd),__CLASS__.'/'.__FUNCTION__,__FILE__);
                return false;
            }else{
                $this->ReloadServicesAfterDomain($domain,$ou);
                return true;
            }
        }

        $hash=$this->Hash_associated_domains($ou);
        writelogs( "associatedDomain=". count($hash) . " entries",__FUNCTION__,__FILE__);
        $update_array=array();
        foreach ($hash as $a=>$b){
            writelogs( "associatedDomain=$a entries",__FUNCTION__,__FILE__);
            $update_array["associatedDomain"][]=$a;
            $GLOBALS["DEBUGLOGS"][]="[\"associatedDomain\"][]=\"$a\"";

        }

        writelogs( "associatedDomain=$domain entries",__FUNCTION__,__FILE__);
        $GLOBALS["DEBUGLOGS"][]="[\"associatedDomain\"][]=\"$domain\"";
        $update_array["associatedDomain"][]=$domain;
        writelogs( "[associatedDomain]=$domain",__FUNCTION__,__FILE__);


        writelogs("Add domain \"$domain\" in $cn",__FUNCTION__,__FILE__);
        if(!$this->Ldap_modify($cn,$update_array)){return false;}
        include_once(dirname(__FILE__)."/class.pdns.inc");
        $p=new pdns();
        $p->AddDomain($domain);
        $this->ReloadServicesAfterDomain($domain,$ou);
        return true;
    }

    function ExistsDN($dn,$debug=false):bool{
        $this->ldap_perform_connection();
        if(is_null($this->ldap_connection)){
            return false;
        }
        $search =@ldap_read($this->ldap_connection,$dn,'(objectClass=*)',array());
        if (!$search){
            if($debug){
                $error=ldap_errno($this->ldap_connection);
                $msg=ldap_err2str(ldap_errno($this->ldap_connection));
                writelogs("(objectClass=*) in $dn is failed err.$error `$msg`...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
            return false;
        }
        return true;

    }
    function load_mynetworks(){
        if(isset($GLOBALS["LDAP_".__FUNCTION__])){return $GLOBALS["LDAP_".__FUNCTION__];}
        $this->ldap_perform_connection();
        $Networks_disabled=array();
        $array_mynetworks=array();
        $nets=array();
        $dn="cn=mynetworks_maps,cn=artica,$this->suffix";
        $sr =@ldap_read($this->ldap_connection,$dn,'objectClass=PostfixMynetworks');
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            if(isset($hash[0]["mynetworks"])){
                for($i=0;$i<$hash[0]["mynetworks"]["count"];$i++){
                    if($GLOBALS["VERBOSE"]){echo "load_mynetworks() ->{$hash[0]["mynetworks"][$i]}<br>\n";}
                    if(trim($hash[0]["mynetworks"][$i])==null){continue;}
                    $nets[trim($hash[0]["mynetworks"][$i])]=$hash[0]["mynetworks"][$i];
                }
            }
        }else{
            if($GLOBALS["VERBOSE"]){echo "load_mynetworks() ->ERROR<br>\n";}


        }

        $sock=new sockets();
        $tbl=@explode("\n",$sock->GET_INFO("NetworkScannerMasks"));
        $disabled=$sock->GET_INFO('NetworkScannerMasksDisabled');

        $tbl2=explode("\n",$disabled);
        if(is_array($tbl2)){
            foreach ($tbl2 as $num=>$maks){
                if(trim($maks)==null){continue;}$Networks_disabled[$maks]=true;
            }
        }


        if(is_array($tbl)){
            $mask=null;

            foreach ($tbl as $i=>$mask){
                if(trim($mask)==null){continue;}
                if(isset($Networks_disabled[$mask])){if($Networks_disabled[$mask]){continue;}}
                $nets[trim($mask)]=trim($mask);
            }
        }
        if($GLOBALS["VERBOSE"]){echo "load_mynetworks() ->PostfixMynetworks\n";}
        $tbl=@explode("\n",base64_decode($sock->GET_INFO("PostfixMynetworks")));
        if(is_array($tbl)){
            foreach ($tbl as $i=>$mask){
                if(trim($mask)==null){continue;}
                $nets[trim($mask)]=trim($mask);
            }
        }


        if(count($nets)>0){
            foreach ($nets as $i=>$myNetwork){
                $array_mynetworks[]=$myNetwork;
            }
        }
        $GLOBALS["LDAP_".__FUNCTION__]=$array_mynetworks;
        return $array_mynetworks;
    }


    function WhitelistsFromDomain($domain=null){
        if($domain==null){$domain="*";}
        $mainF="*@$domain";
        if($mainF=="*@*"){$mainF="*";}
        $filter="(&(objectClass=ArticaSettings)(mail=$mainF)(amavisWhitelistSender=*))";
        $sr=@ldap_search($this->ldap_connection,$this->suffix,$filter,array("mail","amavisWhitelistSender"));
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            if($GLOBALS["VERBOSE"]){echo "$filter -> {$hash["count"]}\n";}
            for($i=0;$i<$hash["count"];$i++){
                $mail=$hash[$i]["mail"][0];
                for($j=0;$j<$hash[$i][strtolower("amavisWhitelistSender")]["count"];$j++){
                    $res[$mail][]=$hash[$i][strtolower("amavisWhitelistSender")][$j];
                }
            }
        }


        $filter="(&(objectClass=ArticaSettings)(objectClass=PostFixStructuralClass)(cn=$domain)(kasperkyasdatasallow=*))";
        $sr=@ldap_search($this->ldap_connection,$this->suffix,$filter,array("cn","KasperkyASDatasAllow"));
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            if($GLOBALS["VERBOSE"]){echo "$filter -> {$hash["count"]}\n";}
            for($i=0;$i<$hash["count"];$i++){
                $mail=$hash[$i]["cn"][0];
                for($j=0;$j<$hash[$i]["kasperkyasdatasallow"]["count"];$j++){
                    $res[$mail][]=$hash[$i]["kasperkyasdatasallow"][$j];
                }
            }
        }

        return $res;
    }
//-------------------------------------------------------------------------------------------------------------------------------------
    function BlackListFromDomain($domain=null){
        if($domain==null){$domain="*";}

        $filter="(&(objectClass=ArticaSettings)(mail=*@$domain)(amavisBlacklistSender=*))";
        $sr=@ldap_search($this->ldap_connection,$this->suffix,$filter,array("mail","amavisBlacklistSender"));
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            for($i=0;$i<$hash["count"];$i++){
                $mail=$hash[$i]["mail"][0];
                for($j=0;$j<$hash[$i][strtolower("amavisBlacklistSender")]["count"];$j++){
                    $res[$mail][]=$hash[$i][strtolower("amavisBlacklistSender")][$j];
                }
            }
        }


        $filter="(&(objectClass=ArticaSettings)(objectClass=PostFixStructuralClass)(cn=$domain)(KasperkyASDatasDeny=*))";
        $sr=@ldap_search($this->ldap_connection,$this->suffix,$filter,array("cn","KasperkyASDatasDeny"));
        if ($sr) {
            $hash=ldap_get_entries($this->ldap_connection,$sr);
            for($i=0;$i<$hash["count"];$i++){
                $mail=$hash[$i]["cn"][0];
                for($j=0;$j<$hash[$i][strtolower("KasperkyASDatasDeny")]["count"];$j++){
                    $res[$mail][]=$hash[$i][strtolower("KasperkyASDatasDeny")][$j];
                }
            }
        }

        return $res;
    }

    function WhiteListsAddDomain($domain,$whitelist,$wbl=0){
        if(!$this->ExistsDN("cn=wlbl,cn=artica,$this->suffix")){
            $upd["objectClass"][]='top';
            $upd["objectClass"][]="PostFixStructuralClass";
            $upd["cn"]="wlbl";
            $this->ldap_add("cn=wlbl,cn=artica,$this->suffix",$upd);
            unset($upd);
        }

        if(!$this->ExistsDN("cn=$domain,cn=wlbl,cn=artica,$this->suffix")){
            $upd["objectClass"][]='top';
            $upd["objectClass"][]='ArticaSettings';
            $upd["objectClass"][]="PostFixStructuralClass";
            $upd["cn"]="$domain";
            $this->ldap_add("cn=$domain,cn=wlbl,cn=artica,$this->suffix",$upd);
            unset($upd);
        }

        if($wbl==0){
            $up["KasperkyASDatasAllow"]=$whitelist;
        }else{
            $up["KasperkyASDatasDeny"]=$whitelist;
        }
        if($this->Ldap_add_mod("cn=$domain,cn=wlbl,cn=artica,$this->suffix",$up)){
            if($wbl==0){$this->WhiteListsDelete($domain,$whitelist,1);}
            if($wbl==1){$this->WhiteListsDelete($domain,$whitelist,0);}
        }

    }


    function WhiteListsAddUser($uid,$whitelist,$wbl=0){
        include_once(dirname(__FILE__) . '/class.user.inc');
        $user=new user($uid);

        if($wbl==0){
            $user->add_whitelist($whitelist);

        }else{
            $user->add_blacklist($whitelist);
        }

    }


    function WhiteListsDelete($to,$from,$wbl=0){
        include_once(dirname(__FILE__) . '/class.user.inc');


        if($wbl==0){
            $upd["KasperkyASDatasAllow"]=$from;
        }else{
            $upd["KasperkyASDatasDeny"]=$from;
        }

        writelogs("cn=$to,cn=wlbl,cn=artica,$this->suffix ?",__CLASS__ . '/' . __FUNCTION__,__FILE__);

        if($this->ExistsDN("cn=$to,cn=wlbl,cn=artica,$this->suffix")){
            writelogs("Yes, delete $from",__CLASS__ . '/' . __FUNCTION__,__FILE__);
            $this->Ldap_del_mod("cn=$to,cn=wlbl,cn=artica,$this->suffix",$upd);
            return ;
        }
        $uid=$this->uid_from_email($to);
        if($uid==null){return null;}
        $user=new user($uid);
        if($wbl==0){$user->del_whitelist($from);}else{$user->del_blacklist($from);}
    }

    function getobjectDNClass($dn,$fulldn=0){
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        if($fulldn==0){$suffix=$dn. ',' . $this->suffix;}else{$suffix=$dn;}

        $arr=array("objectClass");
        $sr = @ldap_search($ld,$suffix,'(&(objectclass=*))',$arr);
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);
            if(is_array($hash[0]["objectclass"])){
                foreach ($hash[0]["objectclass"] as $num=>$ligne){
                    $res[$ligne]=$ligne;
                }
            }
        }

        return $res;
    }
    function getAllobjectsDN($dn,$fulldn=0){
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        if($fulldn==0){$suffix=$dn. ',' . $this->suffix;}else{$suffix=$dn;}


        $sr = @ldap_search($ld,$suffix,'(&(objectclass=*))');
        if ($sr) {
            $hash=@ldap_get_entries($ld,$sr);
            for($i=0;$i<$hash[0]["count"];$i++){
                $res_arr[$hash[0][$i]]=$hash[0][$i];
            }


            if(is_array($hash[0]["objectclass"])){
                foreach ($hash[0]["objectclass"] as $num=>$ligne){
                    $res[$ligne]=$ligne;
                }
            }
        }
        $res_arr["objectclass"]=$res;
        return $res_arr;
    }



    function getobjectClass($ou=null,$dn=null,$hasindex=false):array{
        $ld =$this->ldap_connection;
        $res=array();
        if($dn<>null){$suffix=$dn;}else{$suffix="ou=$ou,dc=organizations,$this->suffix";}
        $GLOBALS["DEBUGLOGS"][]="Get ObjectClass in $suffix";
        writelogs("Get ObjectClass in $suffix",__FUNCTION__.'/'.__CLASS__,__FILE__);
        $arr=array("objectClass");
        $sr = @ldap_search($ld,$suffix,'(&(objectclass=*))',$arr);
        if ($sr) {
            $hash=@ldap_get_entries($ld,$sr);
            if(is_array($hash[0]["objectclass"])){
                foreach ($hash[0]["objectclass"] as $num=>$ligne){
                    if($hasindex){
                        if(!is_numeric($ligne)){$res["objectClass"][]=$ligne;}
                    }else{$res[$ligne]=$ligne;}
                }
            }
        }
        if(count($res)>0){
            writelogs("Get ObjectClass in $suffix return " . count($res). " classes " . implode("\n",$res),__FUNCTION__.'/'.__CLASS__,__FILE__);
        }else{
            writelogs("Get ObjectClass in $suffix return no classes ",__FUNCTION__.'/'.__CLASS__,__FILE__);
            return array();
        }
        return $res;
    }



    function _GenerateGUID(){
        $this->ldap_perform_connection();
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $biguid=0;
        $r =@ldap_search($ld, $this->suffix, '(&(objectClass=posixGroup)(gidNumber=*))',array('gidnumber'));
        if ($r){
            //@ldap_sort($ld, $r, "gidnumber");
            $result = ldap_get_entries($ld, $r);
            $count = $result['count'];
            for($i=0;$i<$count;$i++){
                $gidnumber=$result[$i]["gidnumber"][0];
                if($gidnumber>$biguid){$biguid=$gidnumber;}

            }

            writelogs("found $biguid $count groups",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);
            $biguid=$biguid+1;
            if($biguid<5000){$newbiguid=5000;}else{$newbiguid=$biguid;}
            writelogs("For this group, found $biguid=$newbiguid+1 of $count groups",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);

            return $newbiguid;
        }else{
            writelogs("error !!! finding (&(objectClass=posixGroup)(gidNumber=*))",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);
        }
        return 5001;
    }



    public function GetDNValues($dn){
        $sr =@ldap_search($this->ldap_connection,$dn,'(&(objectclass=*))');
        if ($sr) {
            return ldap_get_entries($this->ldap_connection,$sr);
        }
    }



    function ExplodeError(){
        $ldap_errno=ldap_errno($this->ldap_connection);
        $ldap_err2str=ldap_err2str($ldap_errno);
        $nolog=false;

        switch ($ldap_errno) {
            case 12:$add_error="\nPlease index your LDAP server\n";break;
            case 20:$nolog=true;break;
            case 0:$nolog=true;break;
            case 0:$nolog=true;break;
            default:$nolog=false;break;
        }

        if($nolog==false){
            $this->ldap_last_error_num=$ldap_errno;


            $error_string="ERROR Number: $ldap_errno $ldap_err2str\nLdap_modify:  " . ldap_err2str(ldap_errno($this->ldap_connection));
            writelogs($ldap_err2str . "($ldap_errno)",__CLASS__ . "/" . __FUNCTION__,__FILE__);
            error_log($ldap_err2str . "($ldap_errno)");
            $trace=debug_backtrace();
            if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
            if(count($GLOBALS["DEBUGLOGS"])>0){$add_error=$add_error."\n".@implode("\n", $GLOBALS["DEBUGLOGS"]);}
            return "Error number $ldap_errno $ldap_err2str\nAction:LDAP Modify\n" .
                ldap_err2str(ldap_errno($this->ldap_connection)).
                $add_error.
                "\nTrace:$called\nInformations\n";

        }

    }



    function Ldap_modify($dn,$update_array){
        $this->ldap_perform_connection();
        if(!is_array($update_array)){
            $GLOBALS["INJECT_COMPUTER_TOLDAP"][]="LDAP:: Ldap_modify= No datas in this command !";
            writelogs("No datas in this command !",__CLASS__ . "/" . __FUNCTION__,__FILE__);
            return false;
        }
        $this->ldap_perform_connection();
        $r=@ldap_modify($this->ldap_connection,$this->dn_escape($dn),$update_array);
        if(!$r){

            $ldap_errno=ldap_errno($this->ldap_connection);
            $ldap_err2str=ldap_err2str($ldap_errno);
            $nolog=false;

            switch ($ldap_errno) {
                case 12:$add_error="\nPlease index your LDAP server\n";break;
                case 20:$nolog=true;break;
                case 0:$nolog=true;break;
                case 0:$nolog=true;break;
                default:$nolog=false;break;
            }

            if($nolog==false){
                $this->ldap_last_error_num=$ldap_errno;
                if(function_exists("debug_backtrace")){
                    $trace=debug_backtrace();
                    if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["class"]}/{$trace[1]["function"]}() line {$trace[1]["line"]}";}
                }

                $error_string="ERROR Number: $ldap_errno $ldap_err2str\nLdap_modify  \"dn=$dn\" :  " . ldap_err2str(ldap_errno($this->ldap_connection));
                writelogs($ldap_err2str . "($ldap_errno) Dn:`$dn` $called",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);
                error_log($ldap_err2str . "($ldap_errno)");
                foreach ($update_array as $num=>$ligne){
                    if(is_array($ligne)){
                        foreach ($ligne as $a=>$b){
                            $infos[]="$a = $b";
                            writelogs("ERROR $num($a)=$b",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);
                        }
                    }else{
                        $infos[]="$a = $b";
                        writelogs("ERROR $num=$ligne",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);
                    }


                }
                $add_error=null;
                if(count($GLOBALS["DEBUGLOGS"])>0){$add_error=$add_error."\n".@implode("\n", $GLOBALS["DEBUGLOGS"]);}
                $this->ldap_last_error="Error number $ldap_errno $ldap_err2str\nAction:LDAP Modify\ndn:$dn\n" .
                    ldap_err2str(ldap_errno($this->ldap_connection)).$add_error."\nTrace:$called\nInformations\n".@implode("\n", $infos);



                return false;}

            return true;}

        return true;}

    function Ldap_del_mod($dn,$update_array) {
        $this->ldap_perform_connection();
        $r= @ldap_mod_del($this->ldap_connection,$this->dn_escape($dn),$update_array);
        if(!$r){
            $error_text=ldap_err2str(ldap_errno($this->ldap_connection));
            $error_num=ldap_errno($this->ldap_connection);
            writelogs("ERROR Delete :  $dn :" . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . __FUNCTION__,__FILE__);
            if($error_text="No such attribute"){return true;}
            $this->ldap_last_error="Error number $error_num\nAction:LDAP ldap_mod_del\ndn:$dn\nError: \"$error_text\"";
            return false;
        }
        return true;
    }


    function Ldap_rename_dn($newdn,$olddn,$parent_dn=null) {
        $r= @ldap_rename($this->ldap_connection,$olddn,$newdn,$parent_dn,true);
        if(!$r){
            $this->ldap_last_error='Error number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP Ldap_rename_dn\nSource dn:$olddn\nRenamed dn:$newdn\nParent DN:$parent_dn\n" . ldap_err2str(ldap_errno($this->ldap_connection));
            writelogs("ERROR rename DN :  $olddn to $newdn :" . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . __FUNCTION__,__FILE__);return false;}
        return true;
    }

    function Ldap_add_mod($cn,$update_array){
        $this->ldap_perform_connection();
        $res=@ldap_mod_add($this->ldap_connection,$this->dn_escape($cn),$update_array);

        if(!$res){

            if(function_exists("debug_backtrace")){
                $trace=debug_backtrace();
                if(isset($trace[1])){
                    if(!isset($trace[1]["class"])){$trace[1]["class"]=null;}
                    $called=" called by ".
                        basename($trace[1]["file"]).
                        " {$trace[1]["class"]}/{$trace[1]["function"]}() line {$trace[1]["line"]}";}
            }

            $nolog=false;
            if(ldap_errno($this->ldap_connection)=="0"){return true;}
            if(ldap_errno($this->ldap_connection)<>0){
                switch (ldap_errno($this->ldap_connection)) {
                    case 20:$nolog=true;break;
                    case 21:$nolog=true;$this->__WriteLdapLogs($update_array,__FUNCTION__,$cn,$called);return true;break;
                    case 0:$nolog=true;return true;break;
                    default:$nolog=false;break;
                }
            }
            if($nolog==false){$this->__WriteLdapLogs($update_array,__FUNCTION__,$cn,$called);return false;}

            return true;

        }
        return true;
    }


    function __WriteLdapLogs($update_array,$function,$dn=null,$called=null){

        if(is_array($update_array)){
            foreach ($update_array as $num=>$ligne){
                writelogs("LDAP ERROR $num=$ligne",__CLASS__ . "/" . __FUNCTION__,__FILE__);
                $TT[]="Key:$num for value `$ligne`";
            }
        }


        $this->ldap_last_error='events ' . ldap_errno($this->ldap_connection) .
            "\nAction:LDAP add mod\ndn:$dn\n" . ldap_err2str(ldap_errno($this->ldap_connection))
            ."\nCalled: $called\n--------------\n".@implode("\n", $TT);



        writelogs("LDAP ERROR Number " . ldap_errno($this->ldap_connection) . " Ldap_add_mod  \"dn=$dn\" :  "
            . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . $function,__FILE__);


    }


    function Ldap_read($dn,$filter,$attrs=null){
        $this->ldap_perform_connection();
        if(!$this->ExistsDN($dn)){return null;}


        $res=@ldap_read($this->ldap_connection,$this->dn_escape($dn),$filter,$attrs,0,0,0,0);
        writelogs("read dn " .$this->dn_escape($dn),__CLASS__ . "/" . __FUNCTION__,__FILE__);
        if(!is_array($attrs)){$attrs=array();}
        if(!$res){
            if(ldap_errno($this->ldap_connection)>0){
                $this->ldap_last_error="\n" . '****' . "\n" . 'Error number ' . ldap_errno($this->ldap_connection) . "\nAction:LDAP read\ndn:$dn\nFilter:$filter\nattrs:" . implode(',',$attrs)."\n" . ldap_err2str(ldap_errno($this->ldap_connection) . '****' . "\n");
                writelogs("LDAP ERROR Reading : number " . ldap_errno($this->ldap_connection) .   " " . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . __FUNCTION__,__FILE__);
                return false;
            }
        }

        $result =@ldap_get_entries($this->ldap_connection, $res);
        return $result;

    }


    function ldap_add($dn,$update_array){
        if(!isset($GLOBALS["LDAP_EVENTS"])){
            $GLOBALS["LDAP_EVENTS"]=array();
        }
        if(!isset($GLOBALS[__FUNCTION__][$dn])){$GLOBALS[__FUNCTION__][$dn]=false;}
        if(!$this->UseLdap){
            writelogs("Cannot add this feature for dn $dn".__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        $this->ldap_perform_connection();
        if(!$this->ldap_connection){
            writelogs("ldap_connection failed".__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        $r=@ldap_add($this->ldap_connection,$this->dn_escape($dn),$update_array);
        if(!$r){
            $error=ldap_errno($this->ldap_connection);
            $msg=ldap_err2str(ldap_errno($this->ldap_connection));
            $trace=debug_backtrace();
            $called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
            $msg=$msg." $called";
            $this->ldap_event($error,$msg,__FUNCTION__,$update_array,$dn);
            if($error==20){return true;}
            if($error==68){return true;}
            if($error==98){
                if(!$GLOBALS[__FUNCTION__][$dn]){
                    $this->ldap_perform_connection();
                    $GLOBALS[__FUNCTION__][$dn]=true;
                    return $this->ldap_add($dn,$update_array);
                }
            }
            $this->ldap_last_error_num=ldap_errno($this->ldap_connection) ;
            $this->ldap_last_error="\n****\nError number $error\nAction:LDAP add\nuser:$this->ldap_admin\nserver:$this->ldap_host:$this->ldap_port\ndn:$dn\n$msg****\n";
            return false;
        }

        $GLOBALS["LDAP_EVENTS"]=array();
        return true;}


    function ldap_event($ldap_number,$ldap_mseg,$ldap_action,$update_array,$dn){
        $d=null;
        if($ldap_number==68){writelogs("$dn $ldap_mseg...",__CLASS__ . "/$ldap_action",__FILE__);return true;}
        writelogs("$ldap_action ERROR $ldap_number ($ldap_mseg) on DN \"$dn\" entries: ",__CLASS__ . "/" . __FUNCTION__,__FILE__);
        if(is_array($update_array)){
            foreach ($update_array as $num=>$ligne){
                if(is_array($ligne)){foreach ($ligne as $a=>$b){
                    $d=$d."$num:  $b\n";
                    writelogs("ERROR $ldap_action : $num($a)='{$b}'",__CLASS__ . "/" . __FUNCTION__,__FILE__);
                }}
            }
        }

        writelogs("LDIF\ndn:$dn\n$d");}


    function ldap_group_rename($olddn,$newdn){
        $tbl=explode(",",$olddn);
        unset($tbl[0]);
        $path=implode(",",$tbl);
        if (! @ldap_rename($this->ldap_connection,$olddn,$newdn,$path,1)) {
            $this->ldap_last_error=ldap_err2str(ldap_errno($this->ldap_connection));
            writelogs("ERROR:  ldap_rename(ldap_connection|$olddn|$newdn|$path|1)" . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . __FUNCTION__,__FILE__);return false;
        }


    }

    function HashGetUsersAccounts($ou,$domain){
        $ld =$this->ldap_connection;
        $bind =$this->ldapbind;
        $suffix='ou=' . $ou . ',' . $this->suffix;
        writelogs($suffix . '= >(&(objectclass=*)(domainName=' .$domain .'))',__FUNCTION__,__FILE__);
        //$arr=array("associatedDomain");
        $sr =@ldap_search($ld,$suffix,'(&(objectclass=userAccount)(domainName=' .$domain .'))');
        if ($sr) {
            $hash=ldap_get_entries($ld,$sr);

            if(is_array($hash)){

                foreach ($hash as $num=>$ligne){
                    if($ligne["cn"][0]<>null){
                        $res[$num]["cn"]=$ligne["cn"][0];
                        $res[$num]["dn"]=$ligne["dn"];
                        $res[$num]["mail"]=$ligne["mail"][0];
                        $res[$num]["first_name"]=$ligne["givenname"][0];
                        $res[$num]["last_name"]=$ligne["sn"][0];
                        $res[$num]["uid"]=$ligne["uid"][0];
                        $res[$num]["mailboxactive"]=$ligne["mailboxactive"][0];
                    }

                }
            }

            return $res;

        }
    }


    function noaccents($chaine){
        $chaine =  strtr( $chaine, '���������������', 'aaaiioouuueeeec' );
        return $chaine;
    }


    function ldap_delete($dn,$recursive=false){
        $this->ldap_perform_connection();
        if(trim($dn)==null){
            writelogs("ERROR:  ldap_delete dn is null",__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);
            return true;
        }
        writelogs("Delete $dn recursive=$recursive",__FUNCTION__,__FILE__);
        if($recursive == false){
            if(!@ldap_delete($this->ldap_connection,$dn)){
                writelogs("ERROR:  ldap_delete \"$dn\"" . ldap_err2str(ldap_errno($this->ldap_connection)),__CLASS__ . "/" . __FUNCTION__,__FILE__,__LINE__);return false;
            }
        }
        $sr=@ldap_list($this->ldap_connection,$dn,"ObjectClass=*");
        if($sr){
            $info =@ldap_get_entries($this->ldap_connection, $sr);
            for($i=0;$i<$info['count'];$i++){
                $result=$this->ldap_delete($info[$i]['dn'],$recursive);
                if(!$result){return($result);}
            }
            if(!@ldap_delete($this->ldap_connection,$dn)){
                $this->ldap_last_error=ldap_err2str(ldap_errno($this->ldap_connection));
            }
        }
        return true;
    }




}

class DomainsTools{
    var $transport_port;
    var $transport_ip;
    var $transport_type;
    var $dn=null;
    var $amavisSpamLover="FALSE";
    var $amavisBadHeaderLover="FALSE";
    var $amavisBypassVirusChecks="FALSE";
    var $amavisBypassSpamChecks="FALSE";
    var $amavisBypassHeaderChecks="FALSE";
    var $amavisSpamTagLevel="-999";
    var $amavisSpamTag2Level="5";
    var $amavisSpamKillLevel="5";
    var $amavisSpamModifiesSubj="TRUE";
    var $amavisSpamDsnCutoffLevel=15;
    var $amavisSpamQuarantineCutoffLevel=10;
    var $amavisSpamSubjectTag;
    var $amavisSpamSubjectTag2;
    var $amavis_domain=null;
    var $objectClass_array=array();

    function __construct($domainto=null){
        if($domainto<>null){
            $tr_table=$this->transport_maps_explode($domainto);
            if($tr_table[3]==null){$tr_table[3]=25;}
            $this->transport_type=$tr_table[0];
            $this->transport_port=$tr_table[3];
            $this->transport_ip=$tr_table[1];
        }

    }

    function LoadAmavisDomain($domain){
        $this->amavis_domain=$domain;
        $filter="(&(|(objectclass=transportTable)(objectclass=organizationalUnit))(|(associatedDomain=$domain)(cn=$domain)))";
        $attr=array();
        $ldap=new clladp();

        $sr=@ldap_search($ldap->ldap_connection,$ldap->suffix,$filter,$attr);
        if(!$sr){
            $this->LoadDefaults();
            $this->dn=$this->_Get_dn_domain($domain);
            return null;

        }
        $entry_id = ldap_first_entry($ldap->ldap_connection,$sr);
        if(!$entry_id){
            writelogs( 'INFOS: bad value $entry_id: (' . $entry_id . ')  find: (domain=' . $domain . ') -> aborting function search engine doesn`t found the pattern',__CLASS__.'/'.__FUNCTION__,__FILE__);
            $this->LoadDefaults();
            $this->dn=$this->_Get_dn_domain($domain);
            return null;
        }
        $attrs = ldap_get_attributes($ldap->ldap_connection, $entry_id);

        for($i=0;$i<$attrs["objectClass"]["count"];$i++){
            $class=$attrs["objectClass"][$i];
            $this->objectClass_array[$class]=$class;
            writelogs("Class $class found",__CLASS__.'/'.__FUNCTION__,__FILE__);
        }

        $this->amavisSpamLover=$attrs["amavisSpamLover"][0];
        $this->amavisBadHeaderLover=$attrs["amavisBadHeaderLover"][0];
        $this->amavisBypassVirusChecks=$attrs["amavisBypassVirusChecks"][0];
        $this->amavisBypassSpamChecks=$attrs["amavisBypassSpamChecks"][0];
        $this->amavisBypassHeaderChecks=$attrs["amavisBypassHeaderChecks"][0];
        $this->amavisSpamTagLevel=$attrs["amavisSpamTagLevel"][0];
        $this->amavisSpamTag2Level=$attrs["amavisSpamTag2Level"][0];
        $this->amavisSpamKillLevel=$attrs["amavisSpamKillLevel"][0];
        $this->amavisSpamModifiesSubj=$attrs["amavisSpamModifiesSubj"][0];
        $this->amavisSpamDsnCutoffLevel=$attrs["amavisSpamDsnCutoffLevel"][0];
        $this->amavisSpamQuarantineCutoffLevel=$attrs["amavisSpamQuarantineCutoffLevel"][0];
        $this->amavisSpamSubjectTag=$attrs["amavisSpamSubjectTag"][0];
        $this->amavisSpamSubjectTag2=$attrs["amavisSpamSubjectTag2"][0];





        $this->dn=$this->_Get_dn_domain($domain);
        $this->LoadDefaults();


    }

    function _Get_dn_domain($domain){
        $ldap=new clladp();
        $filter="(&(|(objectclass=transportTable)(objectclass=organizationalUnit))(|(associatedDomain=$domain)(cn=$domain)))";
        $sr =@ldap_search($ldap->ldap_connection,$ldap->suffix,$filter);
        if($sr){
            $result = ldap_get_entries($ldap->ldap_connection, $sr);
            return trim($result[0]["dn"]);
        }else{
            writelogs( 'INFOS: bad value $entry_id: find: (domain=' . $domain . ') -> aborting function search engine doesn`t found the pattern '.$filter,__CLASS__.'/'.__FUNCTION__,__FILE__);
        }

    }

    function DeleteAmavisAttribute($attr){
        $dd=new DomainsTools();
        $dd->LoadAmavisDomain($this->amavis_domain);
        $value=$dd->$attr;
        $upd[$attr]=$value;
        $ldap=new clladp();
        $ldap->Ldap_del_mod($this->dn,$upd);

    }


    function SaveAmavisConfig(){
        $ldap=new clladp();

        $updClass=array();
        writelogs("Saving amavis configuration for dn $this->dn",__CLASS__.'/'.__FUNCTION__,__FILE__);

        if($this->objectClass_array["amavisAccount"]==null){
            $cl=$this->objectClass_array;
            foreach ($cl as $num=>$ligne){
                $updClass["objectClass"][]=$ligne;
            }
            $updClass["objectClass"][]="amavisAccount";
            writelogs( 'Adding new class amavisAccount..',__CLASS__.'/'.__FUNCTION__,__FILE__);
            $ldap->Ldap_modify($this->dn,$updClass);

        }

        $upd=array();
        $upd["amavisSpamLover"][0]=$this->amavisSpamLover;
        $upd["amavisBadHeaderLover"][0]=$this->amavisBadHeaderLover;
        $upd["amavisBypassVirusChecks"][0]=$this->amavisBypassVirusChecks;
        $upd["amavisBypassSpamChecks"][0]=$this->amavisBypassSpamChecks;
        $upd["amavisBypassHeaderChecks"][0]=$this->amavisBypassHeaderChecks;
        $upd["amavisSpamTagLevel"][0]=$this->amavisSpamTagLevel;
        $upd["amavisSpamTag2Level"][0]=$this->amavisSpamTag2Level;
        $upd["amavisSpamKillLevel"][0]=$this->amavisSpamKillLevel;
        $upd["amavisSpamModifiesSubj"][0]=$this->amavisSpamModifiesSubj;
        $upd["amavisSpamDsnCutoffLevel"][0]=$this->amavisSpamDsnCutoffLevel;
        $upd["amavisSpamQuarantineCutoffLevel"][0]=$this->amavisSpamQuarantineCutoffLevel;
        if($this->amavisSpamSubjectTag==null){
            $this->DeleteAmavisAttribute("amavisSpamSubjectTag");
        }else{
            $upd["amavisSpamSubjectTag"][0]=$this->amavisSpamSubjectTag;
        }

        $upd["amavisSpamSubjectTag2"][0]=$this->amavisSpamSubjectTag2;




        if(!$ldap->Ldap_modify($this->dn,$upd)){echo $ldap->ldap_last_error;return false;}
        return true;

    }
    function LoadDefaults(){



    }



    function transport_maps_explode($domainto){
        writepostfixlogs($domainto,__CLASS__.'/'.__FUNCTION__,__FILE__);
        $domainto=strtolower($domainto);
        if(!preg_match('#([a-z\-\.]+):.+#',$domainto)){
            $domainto="smtp:$domainto";



        }
        if(strpos($domainto,'[')>0){
            $resolve="no";
            $domainto=str_replace('[','',$domainto);
            $domainto=str_replace(']','',$domainto);
        }else{$resolve="yes";}



        writepostfixlogs($domainto .' resolve=' . $resolve,__CLASS__.'/'.__FUNCTION__,__FILE__);




        if(preg_match('#([a-z]+):' . grep_ip . ':([0-9]+)#',$domainto,$regs)){
            writepostfixlogs("result=$domainto" .' pattern 1=' . "{$regs[1]};{$regs[2]}.{$regs[3]}.{$regs[4]}.{$regs[5]};{$regs[6]};$resolve",__CLASS__.'/'.__FUNCTION__,__FILE__);
            $resolve="no";
            return array($regs[1],"{$regs[2]}.{$regs[3]}.{$regs[4]}.{$regs[5]}",$regs[6],$resolve);


        }

        if(preg_match('#([a-z]+):' . grep_ip . '#',$domainto,$regs)){
            $resolve="no";
            writepostfixlogs($domainto .' pattern 2=' . "{$regs[1]};{$regs[2]}.{$regs[3]}.{$regs[4]}.{$regs[5]};25;$resolve",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return array($regs[1],"{$regs[2]}.{$regs[3]}.{$regs[4]}.{$regs[5]}",'25',$resolve);
        }

        if(preg_match('#([a-z]+):([a-z\.0-9\-]+):([0-9]+)#',$domainto,$regs)){
            writepostfixlogs("result=".$domainto .' pattern 3=' . "{$regs[1]};{$regs[2]};{$regs[3]};$resolve",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return array($regs[1],$regs[2],$regs[3],$resolve);
        }
        if(preg_match('#([a-z]+):([a-z\.0-9\-]+)#',$domainto,$regs)){
            writepostfixlogs("result=".$domainto .' pattern 4=' . "{$regs[1]};{$regs[2]};{$regs[3]};$resolve",__CLASS__.'/'.__FUNCTION__,__FILE__);
            return array($regs[1],$regs[2],'25',$resolve);
        }

    }



    function transport_maps_implode($domainTo,$port=null,$service=null,$resolvemx="yes"){
        writepostfixlogs("implode values $domainTo,$port,$service,$resolvemx",__CLASS__.'/'.__FUNCTION__,__FILE__);
        if($service<>null){$result="$service";}else{$result='smtp';}
        include_once(dirname(__FILE__).'/class.tcpip.inc');
        $hasaddress=false;
        $ip=new IP();

        if($ip->isIPAddress($domainTo)){
            writepostfixlogs("$domainTo is an ip address, do not resolv mx",__CLASS__.'/'.__FUNCTION__,__FILE__);
            $hasaddress=true;
            $resolvemx="no";}

        if($port==25){$port=null;}

        if($service==null){
            writepostfixlogs("no service specified, consider nothing",__CLASS__.'/'.__FUNCTION__,__FILE__);
            if($hasaddress){
                $result="[$domainTo]";
                if($port<>null){$result=$result.":$port";}
                return $result;
            }
        }

        writepostfixlogs("$domainTo:$port service($result) resolve=$resolvemx",__CLASS__.'/'.__FUNCTION__,__FILE__);

        if(preg_match('#' . grep_ip . '#',$domainTo)){
            $result=$result .":$domainTo";
            $resolvemx="no";
            if($port<>null){$result=$result . ":$port";}
            return $result;
        }

        if($resolvemx=="no"){
            $domainTo="[$domainTo]";
        }
        if($port<>null){
            if($port<>'25'){$result=$result . ":$domainTo:$port";}
            if($port=='25'){$result=$result . ":$domainTo";}
            return $result;
        }


        if($domainTo<>null){
            $result=$result .":$domainTo";
        }
        if($service<>null){
            if($service<>"smtp"){
                if($service<>'relay'){
                    writepostfixlogs("delete [ ]",__CLASS__.'/'.__FUNCTION__,__FILE__);
                    $result=str_replace('[','',$result);
                    $result=str_replace(']','',$result);
                }
            }
        }

        writepostfixlogs("$result",__CLASS__.'/'.__FUNCTION__,__FILE__);
        return $result;
    }

}

function GET_PERFS($value){
    $ini=new Bs_IniHandler("/etc/artica-postfix/performances.conf");
    if(isset($ini->_params["PERFORMANCES"][$value])){
        return $ini->_params["PERFORMANCES"][$value];
    }
    return null;

}

function EXEC_NICE(){

    if(class_exists("unix")){$unix=new unix();return $unix->EXEC_NICE();}
    include_once(dirname(__FILE__).'/class.os.system.inc');
    $useIonice=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useIonice"));
    $useNice=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useNice"));
    $ArticaInCgroups=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaInCgroups"));
    $ArticaCgroup=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaCgroup"));
    $cgroupsEnabled=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsEnabled"));

    if(!is_numeric($ArticaInCgroups)){$ArticaInCgroups=0;}
    if(!is_numeric($useIonice)){$useIonice=1;}
    if(!is_numeric($useNice)){$useNice=1;}
    if(!is_numeric($cgroupsEnabled)){$cgroupsEnabled=0;}

    if($ArticaCgroup==null){$ArticaInCgroups=0;}
    if($cgroupsEnabled==0){$ArticaInCgroups=0;}
    if($ArticaInCgroups==1){
        if(is_dir("/cgroups/cpuacct/$ArticaCgroup")){
            $cgexec=LOCATE_PROGRAM("cgexec");
            if(!is_file($cgexec)){return "$cgexec -g *:$ArticaCgroup ";}
        }
    }

    $tmp=GET_PERFS('ProcessNice');
    if($tmp==null){$tmp=19;}
    if(!is_numeric($tmp)){$tmp=19;}
    if($useIonice==1){
        $ionice=LOCATE_PROGRAM("ionice");
        if(trim($ionice)<>null){$ionice="$ionice -c2 -n7 ";}
    }

    if($useNice==1){
        $nice=LOCATE_PROGRAM("nice");
        if(trim($nice)<>null){$nice="$nice --adjustment=$tmp ";}
    }
    return "$ionice$nice";
}

function strip_path_accents($path){
    $path=str_replace("%E9","é",$path);
    $path=str_replace("%E0","à",$path);
    $path=str_replace("%F9","ù",$path);
    $path=str_replace("%20"," ",$path);
    $path=str_replace("%E8","è",$path);
    $path=str_replace("%E7","ç",$path);
    $path=str_replace("%3F","€",$path);
    $path=str_replace("%26","&",$path);
    $path=str_replace("%FC","ü",$path);
    $path=str_replace("%F6","ö",$path);
    $path=str_replace("%EB","ë",$path);
    $path=str_replace("%EF","ï",$path);
    $path=str_replace("%EE","î",$path);
    $path=str_replace("%EA","ê",$path);
    $path=str_replace("%E2","â",$path);
    $path=str_replace("%FB","û",$path);
    $path=str_replace("%E8","è",$path);
    $path=str_replace("%E9","é",$path);
    $path=str_replace("%u20AC","€",$path);
    //$path=str_replace("%uFFFD","è",$path);
    return $path;
}
function events_debug_ldap($text=null,$function=null,$line=0){echo "$function::$line $text<br>\n";}