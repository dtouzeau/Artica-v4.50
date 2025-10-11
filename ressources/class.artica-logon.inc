<?php
include_once(dirname(__FILE__).'/class.templates.inc');
include_once(dirname(__FILE__).'/class.external.ad.inc');
include_once(dirname(__FILE__).'/class.ldap.inc');
if(!class_exists("class_manager")){include_once(dirname(__FILE__) . '/class.manager.inc');}
class artica_logon{
    public $error;
    public $Simulate=false;
    public $VERBOSE=array();
    private $ldap_admin;
    private $ldap_password;
    public  $username;
    private $password;
    public $ACTIVE_DIRECTORY_INDEX="Nan";
    public $ACTIVE_DIRECTORY_DN=null;
    public $ACTIVE_DIRECTORY_INFO=null;
    public $AsHotSpot=false;
    private $RADIUS_ID=0;
    private $SQLITE_ID=0;
    private $DebugWebConsoleAuth=0;
    public $x509=array();
    function __construct($username=null,$password=null){
        VERBOSE("__construct",__LINE__);
        if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
        if($username<>null){$this->username=trim($username);}
        if($password<>null){$this->password=trim($password);}
        $this->DebugWebConsoleAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugWebConsoleAuth"));
        writelogs("DebugWebConsoleAuth=$this->DebugWebConsoleAuth",__FUNCTION__,__FILE__,__LINE__);
        VERBOSE("__construct",__LINE__);
    }

    public function VERBOSE($text,$line=0){
        writelogs("$text","..","class.artica-logon.inc",$line);
        if($this->DebugWebConsoleAuth==0){return false;}
        if($line>0) {
            $this->VERBOSE[] = "$text [$line]";
            return false;
        }

        $this->VERBOSE[] = $text;
        return true;
    }

    function CheckCreds($username=null,$password=null){
        $LLDAP=false;
        if(!isset($this->x509["subject"])) {
            $this->error = "{wrong_password_or_username}";
            if (trim($username) == null) {
                return false;
            }
            if (trim($password) == null) {
                return false;
            }
            $this->username = $username;
            $this->password = trim($password);
        }
        if(isset($this->x509["subject"])) {
            if(isset($this->x509["subject"]["UID"])) {
                $this->VERBOSE("Username: {$this->x509["subject"]["UID"]} --> SSL CERT", __LINE__);
                if(preg_match("#^(LLDAP|AD|LOCAL):(.+)#",$this->x509["subject"]["UID"],$re)) {
                    $this->username = $re[2];
                    $this->VERBOSE("Username: $this->username --> {$re[1]}", __LINE__);
                    if ($re[1] == "LLDAP") {
                        $this->VERBOSE("Username: $this->username --> OpenLdap() --> SSL CERT", __LINE__);
                        if ($this->OpenLdap()) {
                            $this->VERBOSE("Username: $this->username --> OpenLdap() --> TRUE", __LINE__);
                            $this->SetUser();
                            return true;
                        }
                    }
                    if ($re[1] == "LOCAL") {
                        if ($this->sqlite_admins()) {
                            $this->VERBOSE("Username: $this->username --> sqlite_admins() --> SSL CERT", __LINE__);
                            $this->SetUser();
                            return true;
                        }
                    }
                    if ($re[1] == "AD") {
                        if($this->ActiveDirectoryLinked()){
                            $this->VERBOSE("Username: $this->username <strong>TRUE</strong>",__LINE__);
                            $this->SetUser();
                            return true;
                        }
                        if($this->ActiveDirectoryMain()){
                            $this->SetUser();
                            return true;
                        }
                    }
                return false;}
            }
        }

        $this->VERBOSE("isManager()?",__LINE__);
        if($this->isManager()){
            $this->VERBOSE("isManager() TRUE",__LINE__);
            $this->SetManager();
            return true;
        }
        $this->VERBOSE("<strong>isAdministrator() </strong>?",__LINE__);
        if($this->isAdministrator()){
            $this->SetManager();
            return true;
        }
        $this->VERBOSE("-HR-");
        $this->VERBOSE("Username: $this->username <strong>ActiveDirectoryLinked() ?</strong>",__LINE__);
        if($this->ActiveDirectoryLinked()){
            $this->VERBOSE("Username: $this->username <strong>TRUE</strong>",__LINE__);
            $this->SetUser();
            return true;
        }
        $this->VERBOSE("-HR-");
        $this->VERBOSE("Username: $this->username <strong>ActiveDirectoryMain()</strong> ?",__LINE__);
        if($this->ActiveDirectoryMain()){
            $this->SetUser();
            return true;
        }

        if($this->Radius_admins()){
            $this->SetUser();
            return true;
        }

        if($this->OpenLdap()){
            $this->VERBOSE("Username: $this->username --> OpenLdap() --> TRUE",__LINE__);
            $this->SetUser();
            return true;
        }

        if($this->sqlite_admins()){
            $this->VERBOSE("Username: $this->username --> sqlite_admins() --> TRUE",__LINE__);
            $this->SetUser();
            return true;
        }


        $this->VERBOSE("Username: $this->username FATAL, no Credentials Engines have validated this user ",__LINE__);
        return false;
    }

    public function GetNotice(){
        foreach ($_SERVER as $index=>$value){
            if(is_array($value)){
                foreach ($value as $a=>$b){
                    $notice[]="$index:$a:$b";
                }
                continue;
            }
            $notice[]="$index:$value";

        }
        return @implode("\n",$notice);
    }

    public function GetRemoteIP():string{
        $IPADDR="";
        if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
        if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
        if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
        return $IPADDR;
    }

    public function logoff(){
        unset($_SESSION["privileges_array"]);




        unset($_SESSION["FORCED_TEMPLATE"]);
        unset($_SESSION["MINIADM"]);
        unset($_SESSION["uid"]);
        unset($_SESSION["privileges"]);
        unset($_SESSION["qaliases"]);
        unset($_SERVER['PHP_AUTH_USER']);
        unset($_SESSION["ARTICA_HEAD_TEMPLATE"]);
        unset($_SESSION['smartsieve']['authz']);
        unset($_SESSION["passwd"]);
        unset($_SESSION["LANG_FILES"]);
        unset($_SESSION["TRANSLATE"]);
        unset($_SESSION["__CLASS-USER-MENUS"]);
        unset($_SESSION["FONT_CSS"]);
        unset($_SESSION["translation"]);
        unset($_SESSION["UID_KEY"]);
        $_COOKIE["username"]="";
        $_COOKIE["password"]="";
        $_COOKIE["MINIADM"]="";

        foreach ($_SESSION as $num=>$ligne){
            unset($_SESSION[$num]);
        }
        setcookie("shellinaboxCooKie", "", time()-3600,"","",true,true);
        setcookie("AsWebStatisticsCooKie", "", time()-3600,"","",true,true);
        setcookie("password", "", time()-3600,"","",true,true);
        setcookie("username", "", time()-3600,"","",true,true);
        session_destroy();
    }

    private function SetManager(){
        if($this->Simulate){return;}
        $users=new usersMenus();
        //admin_tracks("Success to logon on the Artica Web console from ".$this->GetRemoteIP()." as SuperAdmin");

        //session_start();
        $_SESSION["uid"]='-100';
        $_SESSION["groupid"]='-100';
        $_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";
        if(isset( $_POST["lang"])) {
            setcookie("artica-language", $_POST["lang"], time() + 172800,"","",true,true);
            $_SESSION["detected_lang"] = $_POST["lang"];
        }
        $_SESSION["CORP"]=$users->CORP_LICENSE;
        $_SESSION["privileges"]["ArticaGroupPrivileges"]='
			[AllowAddGroup]="yes"
			[AllowAddUsers]="yes"
			[AllowChangeKav]="yes"
			[AllowChangeKas]="yes"
			[AllowChangeUserPassword]="yes"
			[AllowEditAliases]="yes"
			[AllowEditAsWbl]="yes"
			[AsSystemAdministrator]="yes"
			[AsPostfixAdministrator]="yes"
			[AsArticaAdministrator]="yes"
			[AsArticaMetaAdmin]="yes"
			[AsFirewallManager]="yes"
			[AsVPNManager]="yes"
			';
        if(!isset($_SESSION["SESSARTSTART"])) {
            $_SESSION["SESSARTSTART"] = time();
        }
    }

    public function IsAdminPrivileges():bool{
        $privs=new privileges($this->username);
        $privileges_array=$privs->privs;
        $users=new usersMenus();
        $users->_TranslateRights($privileges_array,true);
        if($users->AsArticaAdministrator){return true;}
        if($users->AsSystemAdministrator){return true;}
        if($users->AsDebianSystem){return true;}
        return false;
    }

    private function SetUser(){
        $_SESSION["SESSARTTIMEOUT"] = time();
        if($this->Simulate){return false;}
        if($this->ACTIVE_DIRECTORY_DN<>null){
            $this->VERBOSE("Username: $this->username --> ACTIVE_DIRECTORY_DN --> $this->ACTIVE_DIRECTORY_DN",__LINE__);
            $_SESSION["ACTIVE_DIRECTORY_INDEX"]=$this->ACTIVE_DIRECTORY_INDEX;
            $_SESSION["ACTIVE_DIRECTORY_DN"]=$this->ACTIVE_DIRECTORY_DN;
            $_SESSION["ACTIVE_DIRECTORY_INFO"]=$this->ACTIVE_DIRECTORY_INFO;

        }

        if($this->RADIUS_ID>0){$_SESSION["RADIUS_ID"]=$this->RADIUS_ID;}
        if($this->SQLITE_ID>0){$_SESSION["SQLITE_ID"]=$this->SQLITE_ID;}

        $username=$this->username;
        if(is_numeric($this->ACTIVE_DIRECTORY_INDEX) || $this->ACTIVE_DIRECTORY_INDEX=="adagent"){
            $username="AD:$this->ACTIVE_DIRECTORY_INDEX:$this->ACTIVE_DIRECTORY_DN:user=$this->username";
            writelogs($username,__LINE__);

        }
        $UID_KEY="PrivsOf".md5($this->username);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($UID_KEY,"-");

        setcookie("MINIADM", "No", time()+1000,"","",true,true);
        $_SESSION["InterfaceType"]="{APP_ARTICA_ADM}";

        if(isset($_COOKIE["artica-language"])){
            $_POST["lang"]=$_COOKIE["artica-language"];
        }

        if(isset($_POST["lang"])) {
            setcookie("artica-language", $_POST["lang"], time() + 172800,"","",true,true);
            $_SESSION["detected_lang"] = $_POST["lang"];
        }
        $_SESSION["UID_KEY"]=$UID_KEY;
        $_SESSION["CORP"]=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();


        $this->VERBOSE("<H2>Username: $username -- check privileges()</H2>",__LINE__);
        $privs=new privileges($username);
        foreach ($privs->VERBOSE as $line){
            $this->VERBOSE($line,0);
        }
        if(!isset($GLOBALS["ACTIVE_DIRECTORY_GROUPSDN"])){$GLOBALS["ACTIVE_DIRECTORY_GROUPSDN"]=array();}
        if(!is_array($GLOBALS["ACTIVE_DIRECTORY_GROUPSDN"])){$GLOBALS["ACTIVE_DIRECTORY_GROUPSDN"]=array();}

        $this->VERBOSE("ACTIVE_DIRECTORY_GROUPSDN=".count($GLOBALS["ACTIVE_DIRECTORY_GROUPSDN"]),__LINE__);
        $this->isCategories($privs->ACTIVE_DIRECTORY_GROUPSDN);
        $privileges_array=$privs->privs;
        $this->VERBOSE("SetUser: privileges_array = ".count($privileges_array),__LINE__);
        $this->DumpPrivileges($this->username,$privileges_array);

        $users=new usersMenus();
        $users->_TranslateRights($privileges_array,true);

        setcookie("mem-logon-user", $this->username, time()+172800,"","",true,true);
        $_SESSION["privileges_array"]=$privs->privs;
        $_SESSION["uid"]=$this->username;
        BuildSession($this->username);
        admin_tracks("Success to logon on the Artica Web console from ".$this->GetRemoteIP()." as $this->username");
        setcookie("MINIADM", "No", time()+1000,"","",true,true);
        return true;
    }

    private function isCategories($ACTIVE_DIRECTORY_GROUPSDN){
        if(!$_SESSION["CORP"]){return null;}
        $q=new postgres_sql();

        foreach ($ACTIVE_DIRECTORY_GROUPSDN as $dn) {
            $results = $q->QUERY_SQL("SELECT category_id FROM personal_categories_privs 
            WHERE dngroup='$dn'");
            if(!$q->ok){
                writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
                continue;
            }
            if(!$results){continue;}

            while ($ligne = pg_fetch_assoc($results)) {
                $category_id=$ligne["category_id"];
                $this->VERBOSE("$dn: Category:$category_id TRUE",__LINE__);
                $_SESSION["MANAGE_CATEGORIES"][$category_id]=true;

            }

        }

    }

    private function isAdministrator(){
        $manager=new class_manager();

        $trimed_artica_username=trim(strtolower($this->username));
        $trimed_artica_admin=trim(strtolower($manager->admin));
        if($trimed_artica_username==$trimed_artica_admin){
            $this->VERBOSE("$trimed_artica_username === $trimed_artica_username ?", __LINE__);
            return false;}

        $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
        if(!isset($savedsettings["administrator"])){
            $this->VERBOSE("administrator no set in wizard, Aborting",__LINE__);
            return false;}
        if(!isset($savedsettings["administratorpass"])){return false;}
        if($savedsettings["administrator"]==null){
            $this->VERBOSE("administrator no set in wizard, Aborting",__LINE__);
            return false;}
        if($savedsettings["administratorpass"]==null){return false;}

        $admin=trim(strtolower($savedsettings["administrator"]));
        if($trimed_artica_username<>$admin){return false;}
        if(trim($savedsettings["administratorpass"])<>$this->password){return false;}
        return true;
    }

    public function webconsole_syslog($text=null,$file=null,$error=false){
        if($text==null){return false;}
        $LOG_SEV=LOG_INFO;
        if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
        if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
        if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
        $text="[$IPADDR]: $text";
        if(function_exists("openlog")){openlog("webconsole", LOG_PID , LOG_SYSLOG);}
        if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
        if(function_exists("closelog")){closelog();}
        return true;
    }
    public function logon_events($succes){
        $uid=$this->username;
        if($uid==-100){$uid="Manager";}
        if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
        if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
        if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
        $logFile="/var/log/artica-webauth.log";
        $date=date('M  j H:i:s');
        $f = fopen($logFile, 'a');
        $hostname=php_uname("n");
        fwrite($f, "$date $hostname: $uid $IPADDR $succes\n");
        fclose($f);
    }


    public function isManager(){
        if(isset($this->x509["subject"])){
           $this->VERBOSE("Subject attribute exists",__LINE__);
           if(!isset($this->x509["subject"]["UID"])){
               return false;
           }
           if($this->x509["subject"]["UID"]=="-100"){return true;}
        }
        $this->VERBOSE("Subject attribute doesn't exists",__LINE__);
        $manager=new class_manager();
        $this->ldap_admin=$manager->admin;
        $this->ldap_password=$manager->password;

        if(preg_match("#^cn=(.+)#", $this->ldap_admin,$re)){$this->ldap_admin=$re[1];}
        $trimed_artica_username=trim(strtolower($this->username));
        $trimed_ldap_admin=trim(strtolower($this->ldap_admin));
        $this->VERBOSE("Checking $trimed_ldap_admin Against $trimed_artica_username",__LINE__);
        if($trimed_ldap_admin<>$trimed_artica_username){
            $this->VERBOSE("Wrong username, aborting",__LINE__);
            return false;
        }
        if($this->password<>$this->ldap_password){
            $this->VERBOSE("Wrong password for Manager account...",__LINE__);
            return false;}
        $this->VERBOSE("isManager() --- TRUE!",__LINE__);
        return true;
    }

    private function X509_to_dn(){
        $DN=array();
        foreach ($this->x509["subject"] as $key=>$val){
            if(is_array($val)){
                foreach ($val as $a=>$b){
                    //$this->VERBOSE("ActiveDirectoryLinked() x509 $key:$a=$b",__LINE__);
                    $DN[]="$key=$b";
                }
                continue;
            }
            if($key=="UID"){continue;}
            $DN[]="$key=$val";
            //$this->VERBOSE("ActiveDirectoryLinked() x509 $key=$val",__LINE__);
        }
        krsort($DN);
        return @implode(",",$DN);

    }
    private function ActiveDirectoryFromAgent():bool{
        $external_ad_search=new external_ad_search(null,"adagent");
        $AD_Agent_LBL_USER_CAN_CONNECT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AD_Agent_LBL_USER_CAN_CONNECT"));
        if($AD_Agent_LBL_USER_CAN_CONNECT==0){
            $this->VERBOSE("ActiveDirectoryFromAgent() AD_Agent_LBL_USER_CAN_CONNECT is 0, return false",__LINE__);
            return false;
        }
        $x509dn=null;
        if(isset($this->x509["subject"])) {
            $x509dn=$this->X509_to_dn();
            $this->VERBOSE("ActiveDirectoryFromAgent() x509 certificate sent [$x509dn]",__LINE__);
        }else{
            $this->VERBOSE("ActiveDirectoryFromAgent() x509 certificate not sent",__LINE__);
        }
        if($x509dn<>null){
            $this->VERBOSE("ActiveDirectoryFromAgent() Check if $this->username is inside $x509dn",__LINE__);
            if($external_ad_search->if_user_in_dn_ad_agent($x509dn,$this->username)){
                $this->VERBOSE("ActiveDirectoryFromAgent() Check if ,$this->username inside $x509dn OK",__LINE__);
                return true;
            }
            $this->VERBOSE("ActiveDirectoryFromAgent() Check if $this->username inside $x509dn FAILED",__LINE__);
        }
        $password=trim($this->password);
        if($password==null){return false;}
        if($this->username==null){return false;}
        $data=$this->username."|".$password;
        $data64=base64_encode($data);
        $external_ad_search->set_verbose();
        if($external_ad_search->checkAuthADAgent($data64,$this->username)){
            foreach ($external_ad_search->VERBOSED_ARRAY as $line){
                $this->VERBOSE($line,__LINE__);
            }

            if(strpos($this->username, "@")>0){$ZZYYY=explode("@",$this->username);$this->username=$ZZYYY[0];}
            $this->ACTIVE_DIRECTORY_INDEX="adagent";
            $this->ACTIVE_DIRECTORY_DN=$external_ad_search->userDN;
            $this->ACTIVE_DIRECTORY_INFO=$external_ad_search->ACTIVE_DIRECTORY_INFO;

            $this->VERBOSE("ActiveDirectoryFromAgent:: $this->username Success {$external_ad_search->userDN}",__LINE__);
            return true;
        }



        return false;
    }
    public function ActiveDirectoryLinked(){
        $this->ACTIVE_DIRECTORY_INDEX="Nan";
        $this->ACTIVE_DIRECTORY_DN=null;
        $this->ACTIVE_DIRECTORY_INFO=null;
        $sock=new sockets();
        $x509dn=null;
        $ActiveDirectoryConnections=unserialize($sock->GET_INFO("ActiveDirectoryConnections"));
        $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
        if($EnableExternalACLADAgent==1){

            return $this->ActiveDirectoryFromAgent();

        }

        if(!is_array($ActiveDirectoryConnections)){return false;}
        $CountOfActiveDirectoryConnections=count($ActiveDirectoryConnections);
        $this->VERBOSE("*** ActiveDirectoryConnections Number:$CountOfActiveDirectoryConnections",__LINE__);
        if($CountOfActiveDirectoryConnections==0){
            $this->VERBOSE("ActiveDirectoryLinked:: No Active Directory connection set....",__LINE__);
            return false;
        }
        if(isset($this->x509["subject"])) {
            $x509dn=$this->X509_to_dn();
            $this->VERBOSE("ActiveDirectoryLinked() x509 certificate sent [$x509dn]",__LINE__);
        }else{
            $this->VERBOSE("ActiveDirectoryLinked() x509 certificate not sent",__LINE__);
        }

        foreach ($ActiveDirectoryConnections as $index=>$HASH){
            $name   = null;
            if(isset($HASH["NAME"])){$name=$HASH["NAME"];}
            if(!isset($HASH["LDAP_SERVER"])){continue;}
            $host="{$HASH["LDAP_SERVER"]}:{$HASH["LDAP_PORT"]}";
            if(!isset($HASH["ADUserCanConnect"])){$HASH["ADUserCanConnect"]=0;}
            if(!$this->AsHotSpot) {
                if (intval($HASH["ADUserCanConnect"]) == 0) {
                    $this->VERBOSE("TEST Active Directory [$index] $name $host ADUserCanConnect=FALSE (aborting)", __LINE__);
                    continue;
                }
            }


            $this->VERBOSE("ActiveDirectoryLinked() TEST Active Directory index:[$index] $name $host",__LINE__);
            $external_ad_search=new external_ad_search(null,$index);
            $external_ad_search->set_verbose();
            if($x509dn<>null){
                $this->VERBOSE("ActiveDirectoryLinked() Check if $this->username is inside $x509dn",__LINE__);
                if($external_ad_search->if_user_in_dn($x509dn,$this->username)){
                    $this->VERBOSE("ActiveDirectoryLinked() Check if ,$this->username inside $x509dn OK",__LINE__);
                    return true;
                }
                $this->VERBOSE("ActiveDirectoryLinked() Check if $this->username inside $x509dn FAILED",__LINE__);
            }

            if(!$external_ad_search->CheckUserAuth($this->username,$this->password)){
                foreach ($external_ad_search->VERBOSED_ARRAY as $line){
                    $this->VERBOSE($line,__LINE__);
                }

                $this->VERBOSE("*** TEST Active Directory [$index] $name $host FAILED",__LINE__);
                continue;
            }

            foreach ($external_ad_search->VERBOSED_ARRAY as $line){
                $this->VERBOSE($line,__LINE__);
            }

            if(strpos($this->username, "@")>0){$ZZYYY=explode("@",$this->username);$this->username=$ZZYYY[0];}
            $this->ACTIVE_DIRECTORY_INDEX=$index;
            $this->ACTIVE_DIRECTORY_DN=$external_ad_search->userDN;
            $this->ACTIVE_DIRECTORY_INFO=$external_ad_search->ACTIVE_DIRECTORY_INFO;
            $this->VERBOSE("ActiveDirectoryLinked:: $host $this->username Success {$external_ad_search->userDN}",__LINE__);
            return true;

        }

        return false;

    }

    private function Radius_admins(){
        $EnableMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));
        if($EnableMySQL==0){return false;}
        $q=new mysql();
        $sql="SELECT `username`,`value`,id FROM radcheck WHERE `username`='$this->username' AND `attribute`='Cleartext-Password' LIMIT 0,1";
        $ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
        if(!is_numeric($ligne["id"])){$ligne["id"]=0;}
        if(!$q->ok){return false;}
        if($ligne["id"]==0){return false;}

        if(md5($ligne["value"])==$this->password){
            $this->RADIUS_ID=$ligne["id"];
            return true;
        }
        if($ligne["value"]==$this->password){
            $this->RADIUS_ID=$ligne["id"];
            return true;
        }
    }

    private function sqlite_admins():bool{
        $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
        if(isset($this->x509["subject"]["UID"])) {
            $sql="SELECT ID FROM `users` WHERE username='$this->username' COLLATE NOCASE AND enabled=1";
            $ligne=$q->mysqli_fetch_array($sql);
            $ID=intval($ligne["ID"]);
            if($ID>0){
                $this->SQLITE_ID=$ligne["ID"];
                return true;
            }
            return false;
        }

        $password=md5($this->password);
        $sql="SELECT ID FROM `users` WHERE username='$this->username' COLLATE NOCASE AND `passmd5`='$password' AND enabled=1";
        $ligne=$q->mysqli_fetch_array($sql);
        if(!$q->ok){
            writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        if(!isset($ligne["ID"])){return false;}

        $ID=intval($ligne["ID"]);
        if($ID>0){
            $this->SQLITE_ID=$ligne["ID"];
            return true;
        }

        return false;

    }

    public function OpenLdap($username=null,$password=null):bool{
        if(is_null($password)){return false;}
        if($username<>null){$this->username=$username;}
        if($password<>null){$this->password=$password;}
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"))==0){return false;}

        $u=new user($this->username);
        $userPassword=trim($u->password);
        if(is_null($u->uidNumber)){
            return false;
        }
        if(trim($u->uidNumber)==null){
            $this->VERBOSE("OPENLDAP: $this->username == [$u->uidNumber]",__LINE__);
            return false;}
        if(isset($this->x509["subject"]["UID"])) {return true;}
        if( trim($this->password)<>$userPassword){return false;}
        return true;
    }


    public function ActiveDirectoryMain(){
        $external_ad_search=new external_ad_search();
        if($this->AsHotSpot){$external_ad_search->ADUserCanConnect=1;}
        if($external_ad_search->ADUserCanConnect==0){
            $this->VERBOSE("ActiveDirectoryMain: ADUserCanConnect == 0, aborting",__LINE__);
            return false;
        }

        if(isset($this->x509["subject"])) {
            $x509dn=$this->X509_to_dn();
            $this->VERBOSE("ActiveDirectoryMain() Check if $this->username is inside $x509dn",__LINE__);
                if($external_ad_search->if_user_in_dn($x509dn,$this->username)){
                    $this->VERBOSE("ActiveDirectoryMain() Check if ,$this->username inside $x509dn OK",__LINE__);
                    return true;
                }
            $this->VERBOSE("ActiveDirectoryMain() Check if $this->username inside $x509dn FAILED",__LINE__);
        }

        if(!$external_ad_search->CheckUserAuth($this->username,$this->password)){
            foreach ($external_ad_search->VERBOSED_ARRAY as $line){
                $this->VERBOSE($line,__LINE__);
            }
            return false;}
        if(strpos($this->username, "@")>0){$ZZYYY=explode("@",$this->username);$this->username=$ZZYYY[0];}
        $this->ACTIVE_DIRECTORY_INDEX=0;
        $this->ACTIVE_DIRECTORY_DN=$external_ad_search->userDN;
        $this->ACTIVE_DIRECTORY_INFO=$external_ad_search->ACTIVE_DIRECTORY_INFO;
        return true;
    }


    private function DumpPrivileges($username,$array){
        $f=array();
        foreach ($array as $key=>$val){
            if($val=="yes"){
                $this->VERBOSE("<strong>$key</strong> === $val",__LINE__);
                $f[]="[$key]=\"$val\"";
            }
        }

        $_SESSION["privileges"]["ArticaGroupPrivileges"]=@implode("\n", $f);
    }


}