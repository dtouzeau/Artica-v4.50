<?php


class ad_rootdse{
    public $mysql_error;
    public $ok=false;
    private $servername;
    private $port=389;
    private $password;
    private $username;
    private $ldap_connection;
    private $use_ssl=0;
    private $useTLS=0;
    private $ldapbind;
    private $attributes = array('vendorName',
        'vendorVersion',
        'namingContexts',
        'altServer',
        'supportedExtension',
        'supportedControl',
        'supportedSASLMechanisms',
        'supportedLDAPVersion',
        'subschemaSubentry' );

    public function __construct($servername,$port,$username,$password,$use_ssl=0,$useTLS=0){

        $this->servername=$servername;
        $this->port=$port;
        $this->username=$username;
        $this->password=$password;
        if(!is_numeric($use_ssl)){
            if($use_ssl=="true"){$use_ssl=1;}else{$use_ssl=0;}
        }
        $this->use_ssl=$use_ssl;
        $this->useTLS=$useTLS;
        // $this->use_ssl=$use_ssl;
        // Patch 114

    }
    private function SyslogAd($text):bool{
        if(!function_exists("openlog")){return true;}
        $f=basename(__FILE__);
        $text="[$f]: $text";
        openlog("activedirectory", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
        return true;
    }

    private function connect():bool{
        $this->ok=true;
        $HOST=$this->servername;
        if(preg_match("#^ldap(s|):\/\/(.+)#",$this->servername,$re)){$HOST=$re[1];}

        if(!preg_match("#^[0-9\.]+$#",$this->servername)){
            $IPADDR=gethostbyname($this->servername);
            VERBOSE("$this->servername === $IPADDR", __LINE__);
            if($IPADDR==$this->servername){
                $this->ok=false;
                $this->mysql_error="<strong>{$this->servername}:{$this->port}</strong>:<br><strong>{CURLE_COULDNT_RESOLVE_HOST}</strong><br>{check_resolution_dns_engine}";
                return false;
            }

        }

        if($this->use_ssl==1){$this->port=636;}

        VERBOSE("fsockopen($HOST, $this->port, .., .., 2)", __LINE__);
        $fp=@fsockopen($HOST, $this->port, $errno, $errstr, 2);
        if(!$fp){
            $this->ok=false;
            $this->mysql_error="<strong>Socket:{$HOST}:{$this->port}</strong>:<br><strong>$errstr</strong>}";
            VERBOSE("{$HOST}:{$this->port} $errstr", __LINE__);
            return false;
        }else{
            @fclose($fp);
        }

        if($this->use_ssl==1){
            $this->servername="ldaps://$this->servername:636";
            VERBOSE("$this->servername",__LINE__);
        }else{
            $this->servername="ldap://$this->servername:$this->port/";
        }

        VERBOSE("CONNECT: $this->servername",__LINE__);
        $this->ldap_connection=ldap_connect($this->servername);

        if(!$this->ldap_connection){
            VERBOSE("LDAP_CONNECTION_ERROR", __LINE__);
            $ldperr=@ldap_errno($this->ldap_connection);
            $DIAG[]="Unable to connect to LDAP server $this->servername: " .ldap_err2str($ldperr);
            $Error=@implode("; ",$DIAG);
            $this->mysql_error= $Error;
            VERBOSE("$this->mysql_error", __LINE__);
            return false;
        }

        ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
        if($this->use_ssl==1) {
            VERBOSE("LDAP_OPT_X_TLS_REQUIRE_CERT",__LINE__);
            ldap_set_option($this->ldap_connection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);

        }


        if($this->useTLS==1){
            if(!ldap_start_tls($this->ldap_connection)){
                $DIAG[]="{useTLS} {failed}";
                $DIAG[]=ldap_err2str(ldap_errno($this->ldap_connection));
                if (@ldap_get_option($this->ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
                $Error=@implode("; ",$DIAG);
                $this->mysql_error= $Error;
                VERBOSE("$this->mysql_error", __LINE__);
                return false;
            }
        }
        VERBOSE("ldap_bind: $this->username",__LINE__);
        $this->ldapbind=ldap_bind($this->ldap_connection, "$this->username",$this->password);

        if(!$this->ldapbind){
            $this->SyslogAd("$this->servername:$this->port $this->username bind failed");
            $this->ok=false;
            $errornumber= ldap_errno($this->ldap_connection);
            VERBOSE("$this->servername On port($this->port) ldap_bind FAILED ERR:[$errornumber]", __LINE__);
            $this->mysql_error= "ERROR number : $errornumber failed to connect to ldap server $this->servername On port ($this->port)<br>with username $this->username";
            switch ($errornumber) {
                case -1:
                    $this->mysql_error=$this->mysql_error . "\n<br>Unable to bind to server: Can't contact LDAP server";

                case 0x31:
                    $this->mysql_error=$this->mysql_error . "\n<br>Bad username or password. Please try again.";
                    break;
                case 0x32:
                    $this->mysql_error=$this->mysql_error . "\n<br>Insufficient access rights.";
                    break;
                case 81:
                    $this->mysql_error=$this->mysql_error . "<br>Unable to connect to the LDAP server<br>\nplease,\nverify if ldap daemon is running\n or the ldap server address";
                    break;
                case -1:

                    break;
                default:

                    $this->mysql_error=$this->mysql_error .
                        "\nCould not bind to the LDAP server." ."\n".
                        ldap_err2str($errornumber);
            }
            $this->CloseConnection();
            return false;
        }

        return true;
    }

    private function CloseConnection(){
        if ($this->ldap_connection) {
            @ldap_close($this->ldap_connection);
        }

    }

    public function nETBIOSName($suffix):string{

        $Suffix="CN=Partitions,CN=Configuration,$suffix";
        $pattern="(&(objectClass=crossRef)(nETBIOSName=*))";
        $this->connect();
        $this->ok = true;
        if(!$this->ldap_connection){return "";}
        $sr=@ldap_search($this->ldap_connection, $Suffix, "$pattern",array("nETBIOSName"));
        if(!$sr){
            $error=ldap_err2str(ldap_errno($this->ldap_connection));
            $this->mysql_error=$error;
            $this->CloseConnection();
            $this->ok =false;
            return "";
        }
        $entries=ldap_get_entries($this->ldap_connection,$sr);
        if(!is_array($entries)){
            $error=ldap_err2str(ldap_errno($this->ldap_connection));
            $this->mysql_error=$error;
            $this->ok =false;
            return "";
        }

        if(!isset($entries[0]["netbiosname"][0])){
            $this->mysql_error="netbiosname attribute not found.";
            $this->ok =false;
            return "";
        }

        return $entries[0]["netbiosname"][0];


    }


    public function RootDSE(){
        if(!function_exists('ldap_connect')){
            $this->ok=false;
            $this->mysql_error="ldap_connect, no such function.";
            return array();
        }
        if(!$this->connect()){return false;}


        VERBOSE("$this->servername:$this->port ldap_read(...", __LINE__);
        $result=@ldap_read($this->ldap_connection, "", "(objectClass=*)",$this->attributes);


        if (!$result) {

            $errornumber= ldap_errno($this->ldap_connection);
            $error=ldap_err2str($errornumber);
            VERBOSE("$this->servername:$this->port ldap_read($error", __LINE__);
            $this->ok=false;
            $this->mysql_error="Err.$errornumber, $error";
            $this->CloseConnection();
            return false;

        }

        $hash=ldap_get_entries($this->ldap_connection,$result);

        foreach ($hash[0]["namingcontexts"] as $index=>$dn){
            if(!is_numeric($index)){continue;}
            if(preg_match("#^(CN|cn|dc|DC)=(Configuration|Schema|ForestDnsZones|DomainDnsZones)#",$dn)){
                if($GLOBALS["VERBOSE"]){VERBOSE("SKIP $dn",__LINE__);}
                continue;
            }
            if($GLOBALS["VERBOSE"]){VERBOSE("CHOOSE $dn",__LINE__);}
            return $dn;
        }

        $RootDSE=$hash[0]["namingcontexts"][0];
        if(is_array($RootDSE)){return $RootDSE[0];}
        return $RootDSE;

    }
    public function RootDSEArray(){
        $this->ok=true;
        if(!function_exists('ldap_connect')){
            $this->ok=false;
            $this->mysql_error="ldap_connect, no such function.";
            return array();
        }
        if(!$this->connect()){return array();}


        VERBOSE("{$this->servername}:{$this->port} ldap_read(...", __LINE__);
        $result=@ldap_read($this->ldap_connection, "", "(objectClass=*)",$this->attributes);


        if (!$result) {

            $errornumber= ldap_errno($this->ldap_connection);
            $error=ldap_err2str($errornumber);
            VERBOSE("{$this->servername}:{$this->port} ldap_read($error", __LINE__);
            $this->ok=false;
            $this->mysql_error="Err.$errornumber, $error";
            $this->CloseConnection();
            return array();

        }

        $hash=ldap_get_entries($this->ldap_connection,$result);
        return $hash;

    }

}
