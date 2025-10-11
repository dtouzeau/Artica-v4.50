<?php
if(!isset($GLOBALS["DEBUG_PRIVS"])){$GLOBALS["DEBUG_PRIVS"]=false;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){include_once(dirname(__FILE__).'/class.sockets.inc');}
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
include_once(dirname(__FILE__).'/class.ad-agent.inc');
include_once(dirname(__FILE__).'/class.reverse-proxy.privs.inc');
class privileges{
    var $privs=array();
    public $uid=null;
    private $eMailAddr=null;
    private $EnableManagedReverseProxy=0;
    public $UID_KEY=null;
    var $content=null;
    private $page=null;
    var $ldap;
    private $webpage=null;
    var $privilegesuid=null;
    public $VERBOSE=array();
    public $AsArticaAdministrator=false;
    private $radiusid=0;
    private $DebugWebConsoleAuth=0;
    private $sqliteid=0;
    private $ACTIVE_DIRECTORY_INDEX="Nan";
    private $ACTIVE_DIRECTORY_DN=null;
    private $ACTIVE_DIRECTORY_USER=null;
    public $ACTIVE_DIRECTORY_GROUPSDN=array();

    function __construct($uid=null,$privilegesuid=null,$radiusid=0,$nosession=false){
        if(function_exists("CurrentPageName")){$this->webpage=CurrentPageName();}
        $this->VERBOSED("Privileges of $uid",__LINE__);
        $this->EnableManagedReverseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableManagedReverseProxy"));
        $this->UID_KEY="PrivsOf".md5((string) $uid);
        $_SESSION["UID_KEY"]=$this->UID_KEY;
        $this->privilegesuid=$privilegesuid;
        $this->uid=$uid;
        $this->radiusid=$radiusid;
        $this->VERBOSE=array();
        $CountOFPrivileges=0;
        $called     = null;
        $this->DebugWebConsoleAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugWebConsoleAuth"));

        if($GLOBALS["VERBOSE"]){
            if(function_exists("debug_backtrace")){
                $trace=@debug_backtrace();
                foreach ($trace as $ligne){
                    $file=$ligne["file"];
                    $function=$ligne["function"];
                    $line=$ligne["line"];
                    VERBOSE("By $file {$function}() in line $line");
                }
            }
        }

        if($this->uid==null){
            if(isset($GLOBALS["AS_ROOT"])){
                if($GLOBALS["AS_ROOT"]){$this->uid=-100;}
            }else{
                if(function_exists("posix_getuid")){
                    if(posix_getuid()==0){
                        $GLOBALS["AS_ROOT"]=true;
                        $this->uid=-100;
                    }
                }
            }
        }
        if($this->uid==-100){
            $this->DebugWebConsoleAuth=0;
        }



        if(preg_match("#AD:([0-9]|adagent+):(.+?):user=(.+)#",(string) $this->uid,$re)){
            $this->VERBOSED("DN={$re[2]}",__LINE__);
            $this->ACTIVE_DIRECTORY_DN=$re[2];
            $this->ACTIVE_DIRECTORY_INDEX=$re[1];
            $this->ACTIVE_DIRECTORY_USER=$re[3];
            $this->UID_KEY="PrivsOf".md5(trim($re[3]));
            $_SESSION["UID_KEY"]=$this->UID_KEY;
        }else{
            $this->VERBOSED("\"$this->uid\" did not match #AD:([0-9]+):(.+?):user=(.+)#",__LINE__);
        }

        if(isset($_SESSION["RADIUS_ID"])){
            if($GLOBALS["VERBOSE"]){echo "Radius identifier={$_SESSION["RADIUS_ID"]}<br>\n";}
            if($_SESSION["RADIUS_ID"]>0){
                $this->radiusid=$_SESSION["RADIUS_ID"];

            }
        }

        if(isset($_SESSION["SQLITE_ID"])){
            if($_SESSION["SQLITE_ID"]>0){
                $this->sqliteid=$_SESSION["SQLITE_ID"];
                $this->uid="sqlite:$this->sqliteid";
            }
        }
        if(!$nosession) {
            if (!isset($_SESSION["privileges_array"])) {

                if($this->DebugWebConsoleAuth==1){
                   writelogs("Parsing privileges for [$this->uid]", __FUNCTION__, __FILE__, __LINE__);
                }

                $this->VERBOSED("_SESSION[privileges_array] Not set,<strong>Parsing privileges</strong>");
                $this->SearchPrivileges();
                $CountOFPrivileges = count($this->privs);
                if($this->DebugWebConsoleAuth==1) {
                    writelogs("Privileges = $CountOFPrivileges after SearchPrivileges();", __FUNCTION__, __FILE__, __LINE__);
                }
                if ($CountOFPrivileges > 0) {
                    return;
                }
            }

            if(!isset($_SESSION["privileges_array"])){$_SESSION["privileges_array"]=array();}
            $CountOFPrivileges=count($_SESSION["privileges_array"]);
            $this->VERBOSED("!nosession count(_SESSION[privileges_array]) = $CountOFPrivileges",__LINE__);

        }

        if($CountOFPrivileges==0){
            if(!class_exists("clladp")){include_once(dirname(__FILE__)."/class.ldap.inc");}
            $this->ldap=new clladp();
            $this->VERBOSED("$this->webpage: privileges_array COUNT = 0 this->SearchPrivileges() $called",__CLASS__.'/'.__LINE__);
            $this->SearchPrivileges();
            if($GLOBALS["DEBUG_PRIVS"]){$this->DebugPrivs();}
        }

        if(isset($_SESSION[__CLASS__][$uid]["CONTENT"])){
            $this->VERBOSED("$this->webpage: HIT SESSION OF $uid",__CLASS__.'/'.__LINE__);
            $this->content=$_SESSION[__CLASS__][$uid]["CONTENT"];
        }

        if($GLOBALS["DEBUG_PRIVS"]){
            unset($_SESSION["privileges_array"]);
        }

        if(isset($_SESSION["privileges_array"])){
            if(count($_SESSION["privileges_array"])>0) {
                $this->privs = $_SESSION["privileges_array"];
                $this->VERBOSED("$this->webpage: HIT privileges_array OF $uid",__CLASS__.'/'.__LINE__);
                return;
            }
        }



    }
    private function DebugPrivs(){
        if(!isset($_SESSION[__CLASS__][$this->uid]["PRIVS"])){return;}
        $array=$_SESSION[__CLASS__][$this->uid]["PRIVS"];

        foreach ($array as $num=>$ligne){
            if($ligne=="yes"){
                echo "<li style='color:red;font-size:14px'>[PRIVS]: $num = <strong>[$ligne]</strong></span></li>\n";

            }else{
                echo "<li style='color:red;font-size:14px'>[PRIVS]: $num = <code>[$ligne]</code></span></li>\n";
            }
        }

        $array=$_SESSION["privileges_array"];
        foreach ($array as $num=>$ligne){
            if($ligne=="yes"){
                echo "<li style='color:red;font-size:14px'>[PRIVS_SESSION]: $num = <strong>[$ligne]</strong></span></li>\n";

            }else{
                echo "<li style='color:red;font-size:14px'>[PRIVS_SESSION]: $num = <code>[$ligne]</code></span></li>\n";
            }
        }




    }


    public function PolicyPassword($password,$replaceArray=null){
        $policiespwd=@unserialize(base64_decode($this->privs["PasswdPolicy"]));
        $tpl=new templates();
        if(!is_array($replaceArray)){
            $this->VERBOSED("replaceArray is not an array....",__CLASS__.'/'.__LINE__);
        }

        if(is_array($replaceArray)){$policiespwd=$replaceArray;}


        if(!is_array($policiespwd)){
            $this->VERBOSED("policiespwd is not an array....",__CLASS__.'/'.__LINE__);
            return true;
        }
        if($policiespwd["passwd_length"]>0){
            if(strlen($password)<$policiespwd["passwd_length"]-1){
                echo $tpl->javascript_parse_text("ERROR: {password_no_match_policy}
{minimum_length}: {$policiespwd["passwd_length"]}",1);
                return false;
            }
        }


        if($policiespwd["UpperCase"]==1){
            if(!preg_match("#[A-Z]+#",$password)){
                echo $tpl->javascript_parse_text("ERROR: {password_no_match_policy}
{at_least_one_upper_case_letter}",1);
                return false;
            }
        }

        if($policiespwd["LowerCase"]==1){
            if(!preg_match("#[a-z]+#",$password)){
                echo $tpl->javascript_parse_text("ERROR: {password_no_match_policy}
{at_least_one_lower_case_letter}",1);
                return false;
            }
        }

        if($policiespwd["DigitCase"]==1){
            if(!preg_match("#[0-9]+#",$password)){
                echo $tpl->javascript_parse_text("ERROR: {password_no_match_policy}
{at_least_one_digit_letter}",1);
                return false;
            }
        }

        if($policiespwd["SpecialCase"]==1){
            $spec=utf8_decode("$ % ! * # ( ) [ ] § ; :  , / ~ & < > . - _ ²");
            if(!preg_match("#[\$\%\!\*\#\(\)\[\]\,\?\§\;\:\/\~\&\<\>\.\_\-\²]+#",$password)){
                echo $tpl->javascript_parse_text("ERROR: {password_no_match_policy}
{at_least_one_special_letter}
$spec",1);
                return false;
            }
        }

        return true;


    }

    private function VERBOSED($text,$line=0){

        if(!$GLOBALS["VERBOSE"]){
            return;
        }
        $trace=@debug_backtrace();
        $function=$trace[1]["function"];
        $class=$trace[1]["class"];
        $line1=$trace[1]["line"];
        $file=basename($trace[1]["file"]);
        $this->VERBOSE[]="$text class.privileges.inc $text [$line]";
        if(!function_exists("VERBOSE")){return false;}
        VERBOSE($text,$line,"$file/$class/$function($line1)");
        return true;
    }

    private function AllPrivs(){
        $this->privs["AsSystemAdministrator"] = "yes";
        $this->privs["AsPostfixAdministrator"] = "yes";
        $this->privs["AsArticaAdministrator"] = "yes";
        $this->privs["AsSquidAdministrator"] = "yes";
        $this->privs["AsSambaAdministrator"] = "yes";
        $this->privs["AsVirtualBoxManager"] = "yes";
        $this->privs["AsDansGuardianAdministrator"] = "yes";
        $this->privs["AsWebStatisticsAdministrator"] = "yes";
        $this->privs["AsSquidPersonalCategories"] = "yes";
        $this->privs["AsArticaMetaAdmin"] = "yes";
        $this->privs["AsFirewallManager"] = "yes";
        $this->privs["AsVPNManager"] = "yes";
        $this->privs["AsProxyMonitor"] = "yes";
        $this->privs["AsMailBoxAdministrator"] = "yes";
        $this->privs["AsCertifsManager"] = "yes";
        $this->privs["AsDatabaseAdministrator"] = "yes";
        $this->privs["AsDockerAdmin"] = "yes";
        $this->privs["AsDockerReverse"] = "yes";
        foreach ($this->privs as $priv=>$var){
            $f[]="[$priv]=\"$var\"";
        }
        return @implode("\n",$f);
    }
    public function SearchPrivileges(){
        $privsCache=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($this->UID_KEY);

        if($GLOBALS["DEBUG_PRIVS"]){
            $privsCache="";
        }
        $this->VERBOSED("privsCache:[$privsCache]",__LINE__);
        if(strlen($privsCache)>1){
            $this->VERBOSED("$this->UID_KEY -- HIT !!!",__LINE__);
            $array=@unserialize(base64_decode($privsCache));
            if($array !== false){
                $this->privs=$array;
                if(!isset($this->privs["AsSystemAdministrator"])){$this->privs["AsSystemAdministrator"]="no";}
                $this->VERBOSED("AsSystemAdministrator:[{$this->privs["AsSystemAdministrator"]}]",__LINE__);
                if($this->privs["AsSystemAdministrator"]=="yes"){
                    $this->privs["AsDatabaseAdministrator"]="yes";
                }
                return;
            }
            $this->VERBOSED("HIT BAD unserialize == FALSE !!!",__LINE__);
        }else{
            $this->VERBOSED("$this->UID_KEY ".strlen($privsCache)." bytes in Memcached",__LINE__);
        }
        $this->VERBOSED("->SearchPrivileges() ACTIVE DIRECTORY INDEX=$this->ACTIVE_DIRECTORY_INDEX ACTIVE_DIRECTORY_DN=$this->ACTIVE_DIRECTORY_DN",__LINE__);

        if(isset($this->ACTIVE_DIRECTORY_INDEX) AND $this->ACTIVE_DIRECTORY_INDEX=="adagent"){
            include_once(dirname(__FILE__)."/class.external.ad.inc");
            $this->VERBOSED("->SearchPrivileges() - Active Directory Index $this->ACTIVE_DIRECTORY_INDEX ($this->ACTIVE_DIRECTORY_DN)",__LINE__);
            $ad=new external_ad_search(null,"adagent");
            $attrs=$ad->getMemberInfoADAgent($this->ACTIVE_DIRECTORY_USER);
            $groups=$attrs["memberOf"];
            $this->VERBOSED("->SearchPrivileges() - Groups: ".count($groups),__LINE__);

            foreach ($groups as $dnGroup=>$GroupName){
                $this->ACTIVE_DIRECTORY_GROUPSDN[]=$GroupName;
                $GLOBALS["ACTIVE_DIRECTORY_GROUPSDN"][]=$GroupName;
                $hash=$ad->LoadGroupDataByDN($GroupName);
                $this->VERBOSED("Group: $GroupName",__LINE__);

                if(strlen($hash["ArticaGroupPrivileges"])>10){
                    $this->VERBOSED("Group: $GroupName <H3>As privileges</H3>",__LINE__);
                    $this->ParsePrivileges($hash["ArticaGroupPrivileges"]);
                }else{
                    if(preg_match("#CN=Domain Admins,CN=Users,#i",$GroupName)){$this->ParsePrivileges($this->default_all_rights());}
                    if(preg_match("#CN=Enterprise Admins,CN=Users,#i",$GroupName)){
                        $this->VERBOSED($GroupName." MATCHES CN=Enterprise Admins,CN=Users (SUCCESS)",__LINE__);
                        $this->ParsePrivileges($this->default_all_rights());
                        return;
                    }
                    if(preg_match("#CN=Administrators,CN=Builtin#i",$GroupName)){$this->ParsePrivileges($this->default_all_rights());}

                }
            }
            $CachePrivs=base64_encode(serialize($this->privs));
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO($this->UID_KEY,$CachePrivs);
            return;
        }


        if($this->DebugWebConsoleAuth==1) {
            writelogs("[$this->uid]: ACTIVE_DIRECTORY_INDEX = $this->ACTIVE_DIRECTORY_INDEX dn=[$this->ACTIVE_DIRECTORY_DN]", __FUNCTION__, __FILE__, __LINE__);
        }

        if(is_numeric($this->ACTIVE_DIRECTORY_INDEX) AND ($this->ACTIVE_DIRECTORY_DN<>null)){
            include_once(dirname(__FILE__)."/class.external.ad.inc");
            $this->VERBOSED("->SearchPrivileges() - Active Directory Index $this->ACTIVE_DIRECTORY_INDEX ($this->ACTIVE_DIRECTORY_DN)",__LINE__);
            $ad=new external_ad_search(null,$this->ACTIVE_DIRECTORY_INDEX);

            $groups=$ad->GroupsOfMember($this->ACTIVE_DIRECTORY_DN);
            $this->VERBOSED("->SearchPrivileges() - Groups: ".count($groups),__LINE__);

            if($this->DebugWebConsoleAuth==1) {
                writelogs("[$this->uid] Groups:[".count($groups)."]", __FUNCTION__, __FILE__, __LINE__);
            }

            foreach ($groups as $dnGroup=>$GroupName){
                $this->ACTIVE_DIRECTORY_GROUPSDN[]=$dnGroup;
                $GLOBALS["ACTIVE_DIRECTORY_GROUPSDN"][]=$dnGroup;

                if($this->DebugWebConsoleAuth==1) {
                    writelogs("[$this->uid] Check [$dnGroup]", __FUNCTION__, __FILE__, __LINE__);
                }

                $hash=$ad->LoadGroupDataByDN($dnGroup);
                $this->VERBOSED("Group: $dnGroup",__LINE__);
                if(!is_null($hash["ArticaGroupPrivileges"])){
                    if(strlen($hash["ArticaGroupPrivileges"])>10) {
                        $this->VERBOSED("Group: $dnGroup <H3>As privileges</H3>", __LINE__);
                        $this->ParsePrivileges($hash["ArticaGroupPrivileges"]);
                        continue;
                    }
                }

                if(preg_match("#CN=Domain Admins,CN=Users,#i",$dnGroup)){
                    $this->ParsePrivileges($this->default_all_rights());
                    $CachePrivs=base64_encode(serialize($this->privs));
                    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($this->UID_KEY,$CachePrivs);
                    return;
                }
                if(preg_match("#CN=Enterprise Admins,CN=Users,#i",$dnGroup)){
                    $this->VERBOSED($dnGroup." MATCHES CN=Enterprise Admins,CN=Users (SUCCESS)",__LINE__);
                    $this->ParsePrivileges($this->default_all_rights());
                    return;
                }
                if(preg_match("#CN=Administrators,CN=Builtin#i",$dnGroup)){
                    $this->ParsePrivileges($this->default_all_rights());
                    $CachePrivs=base64_encode(serialize($this->privs));
                    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($this->UID_KEY,$CachePrivs);
                    return;
                }
            }
            $CachePrivs=base64_encode(serialize($this->privs));
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO($this->UID_KEY,$CachePrivs);
            return;
        }


        if($this->sqliteid>0){
            $this->VERBOSED("->SearchPrivileges() IN SQLITE sqliteid:$this->sqliteid /home/artica/SQLITE/admins.db",__LINE__);
            $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
            $sql="SELECT lnk.ID as tid,groups.ID,groups.groupname,groups.privileges,groups.enabled FROM `groups`,`lnk`
				WHERE groups.ID=lnk.groupid AND groups.enabled=1 AND lnk.userid=$this->sqliteid ORDER BY groupname";
            $results=$q->QUERY_SQL($sql);
            if(!$q->ok){$this->VERBOSED("$q->mysql_error",__LINE__);}
            foreach ($results as $index=>$ligne){
                $groupid=$ligne["ID"];
                $this->VERBOSED("FIND GROUP $groupid Name={$ligne["groupname"]}",__LINE__);
                $this->IsHarmpPrivs("sqlite:$groupid");

                if(strtolower($ligne["groupname"])=='administrators') {
                    $hash["ArticaGroupPrivileges"]=$this->AllPrivs();
                }else{
                    $hash["ArticaGroupPrivileges"]=base64_decode($ligne["privileges"]);
                }
                $nginx=new NginxPrivs("sqlite:$groupid");
                $NginxPrivsText=$nginx->NginxPrivs();
                if(strlen($NginxPrivsText)>2){
                    $zNginxPrivs="\n[NginxPrivs]=\"$NginxPrivsText\"";
                    $hash["ArticaGroupPrivileges"]=$hash["ArticaGroupPrivileges"].$zNginxPrivs;
                }

                $this->ParsePrivileges($hash["ArticaGroupPrivileges"]);

            }
            $CachePrivs=base64_encode(serialize($this->privs));
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO($this->UID_KEY,$CachePrivs);
            return;
        }
        if(trim((string) $this->uid)==null){
            $this->VERBOSED("->SearchPrivileges() -> no uid, return",__LINE__);
            return;
        }
        if($this->radiusid>0) {
            $q = new mysql();
            $results = $q->QUERY_SQL("SELECT groupname FROM radusergroup WHERE username='$this->uid'", "artica_backup");
            while ($ligne = mysqli_fetch_assoc($results)) {
                $groupname = $ligne["groupname"];
                $this->VERBOSED("$this->radiusid:: Groupname = '$groupname'", __LINE__);
                if ($groupname == "administrators") {
                    $this->AllPrivs();
                }
                if (strtolower($groupname) == strtolower("ProxyAdms")) {
                    $this->privs["AsSquidAdministrator"] = "yes";
                    $this->privs["AsSquidPersonalCategories"] = "yes";
                }
                if (strtolower($groupname) == strtolower("ProxySecurity")) {
                    $this->privs["AsDansGuardianAdministrator"] = "yes";
                    $this->privs["AsSquidPersonalCategories"] = "yes";
                }
                if (strtolower($groupname) == strtolower("WebStatsAdm")) {
                    $this->privs["AsWebStatisticsAdministrator"] = "yes";
                    $this->privs["AsSquidPersonalCategories"] = "yes";
                }
                if (strtolower($groupname) == strtolower("VPNAdmins")) {
                    $this->VERBOSED("$this->radiusid:: AsVPNManager = 'yes'", __LINE__);
                    $this->privs["AsVPNManager"] = "yes";
                }

                if (strtolower($groupname) == strtolower("ProxyMonitor")) {
                    $this->VERBOSED("$this->radiusid:: AsProxyMonitor = 'yes'", __LINE__);
                    $this->privs["AsProxyMonitor"] = "yes";
                }

                if (strtolower($groupname) == strtolower("DBAdmin")) {
                    $this->VERBOSED("$this->radiusid:: DBAdmin = 'yes'", __LINE__);
                    $this->privs["AsDatabaseAdministrator"] = "yes";
                }

                if (strtolower($groupname) == strtolower("DockerAdmins")) {
                    $this->VERBOSED("$this->radiusid:: DockerAdmins = 'yes'", __LINE__);
                    $this->privs["AsDockerAdmin"] = "yes";
                }

                if (strtolower($groupname) == strtolower("ReverseAdmins")) {
                    $this->VERBOSED("$this->radiusid:: DockerAdmins = 'yes'", __LINE__);
                    $this->privs["AsDockerReverse"] = "yes";
                }


            }
            $CachePrivs = base64_encode(serialize($this->privs));
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO($this->UID_KEY, $CachePrivs);
            return;
        }
        if($this->uid==-100){
            if($this->webpage=="fw.login.php"){$this->VERBOSED("$this->webpage: -->privileges_Manager -> uid=`$this->uid`",__CLASS__."/".__LINE__);}
            $this->privileges_Manager();
            $CachePrivs=base64_encode(serialize($this->privs));
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO($this->UID_KEY,$CachePrivs);
            return;
        }


        if($this->webpage=="fw.login.php"){$this->VERBOSED("$this->webpage: -->SearchPrivileges -> uid=`$this->uid/$this->eMailAddr`",__CLASS__."/".__LINE__);}
        $this->ldap=new clladp();
        $this->privileges_groups();
        $this->ParsePrivilegesOU();
        $this->GetContent();
        $this->SinglePrivileges();
        $CachePrivs=base64_encode(serialize($this->privs));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($this->UID_KEY,$CachePrivs);
        $_SESSION[__CLASS__][$this->uid]["CONTENT"]=$this->content;



    }


    public function EmptyCache(){
        unset($_SESSION[__CLASS__][$this->uid]);
    }

    private function privileges_groups_adagent($ExternalADIndex=0):bool{
        $this->VERBOSED("GETTING DATA FRO {$this->uid}",__LINE__);
        if(isset($GLOBALS["privileges_groups_AD$this->uid"])){
            $this->VERBOSED("privileges_groups_adagent$this->uid is alreay set",__LINE__);
            return true;
        }
        include_once(dirname(__FILE__))."/class.external.ad.inc";
        $this->VERBOSED("START privileges_groups_adagent() step1",__LINE__);
        $gp=new external_ad_search(null,"adagent");
        $this->VERBOSED("->getMemberInfoADAgent($this->uid)",__LINE__);
        $attrs=$gp->getMemberInfoADAgent($this->ACTIVE_DIRECTORY_USER);
        $this->VERBOSED("START privileges_groups_adagent() step2 {$attrs["distinguishedName"]}",__LINE__);
        if(!isset($attrs["distinguishedName"])){
            $this->VERBOSED("getMemberInfoADAgen = NULL",__LINE__);
            return false;
        }
        $dn=$attrs["distinguishedName"];
        $this->VERBOSED("->getMemberInfoADAgen($dn)",__LINE__);

        $Groups=$attrs["memberOf"];
        $this->VERBOSED("->GroupsOfMember(".count($Groups).") items",__LINE__);

        foreach ($Groups as $dnGroup=>$GroupName){
            $this->VERBOSED("START privileges_groups_adagent() step3 {$dnGroup}",__LINE__);
            $this->IsHarmpPrivs($dnGroup);
            $hash=$gp->LoadGroupDataByDN($GroupName);
            if(strlen($hash["ArticaGroupPrivileges"])>10){
                $this->VERBOSED("START privileges_groups_adagent() step4",__LINE__);
                if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] ArticaGroupPrivileges:". strlen($hash["ArticaGroupPrivileges"])." bytes for $GroupName -> `$dnGroup`<br>\n";}
                $this->ParsePrivileges($hash["ArticaGroupPrivileges"]);
            }else{
                $this->VERBOSED("START privileges_groups_adagent() step5 $GroupName",__LINE__);
                if(preg_match("#CN=Domain Admins,CN=Users,#i",$GroupName)){$this->ParsePrivileges($this->default_all_rights());$this->VERBOSED("START privileges_groups_adagent() step6",__LINE__);}
                if(preg_match("#CN=Enterprise Admins,CN=Users,#i",$GroupName)){$this->ParsePrivileges($this->default_all_rights());$this->VERBOSED("START privileges_groups_adagent() step7",__LINE__);}
                if(preg_match("#CN=Administrators,CN=Builtin#i",$GroupName)){$this->ParsePrivileges($this->default_all_rights());$this->VERBOSED("START privileges_groups_adagent() step8",__LINE__);}

            }
        }
        $GLOBALS["privileges_groups_AD$this->uid"]=true;
        return true;
    }

    private function privileges_groups_AD($ExternalADIndex=0):bool{
        if(isset($GLOBALS["privileges_groups_AD$this->uid"])){
            if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."]  privileges_groups_AD$this->uid is alreay set<br>\n";}
            return true;}
        include_once(dirname(__FILE__))."/class.external.ad.inc";

        $gp=new external_ad_search(null,$ExternalADIndex);
        if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] ->LoadMember($this->uid)<br>\n";}
        $attrs=$gp->LoadMember($this->uid);
        if(!isset($attrs["distinguishedName"][0])){
            if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] distinguishedName = NULL<br>\n";}
            return false;
        }
        $dn=$attrs["distinguishedName"][0];
        if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] ->GroupsOfMember($dn)<br>\n";}
        $Groups=$gp->GroupsOfMember($dn);
        if(!is_array($Groups)){
            $this->VERBOSED("GroupsOfMember = Not an array",__LINE__);
            return false;
        }

        $this->VERBOSED("->GroupsOfMember(".count($Groups).") items",__LINE__);

        foreach ($Groups as $dnGroup=>$GroupName){
            $this->IsHarmpPrivs($dnGroup);
            $hash=$gp->LoadGroupDataByDN($dnGroup);
            if(strlen($hash["ArticaGroupPrivileges"])>10){
                if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."::".$this->mem_getusage()."] ArticaGroupPrivileges:". strlen($hash["ArticaGroupPrivileges"])." bytes for $GroupName -> `$dnGroup`<br>\n";}
                $this->ParsePrivileges($hash["ArticaGroupPrivileges"]);
            }else{
                if(preg_match("#CN=Domain Admins,CN=Users,#i",$dnGroup)){$this->ParsePrivileges($this->default_all_rights());}
                if(preg_match("#CN=Enterprise Admins,CN=Users,#i",$dnGroup)){$this->ParsePrivileges($this->default_all_rights());}
                if(preg_match("#CN=Administrators,CN=Builtin#i",$dnGroup)){$this->ParsePrivileges($this->default_all_rights());}

            }
        }
        $GLOBALS["privileges_groups_AD$this->uid"]=true;
        return true;
    }

    private function default_all_rights(){

        $f[]="[AsDockerAdmin]=\"yes\"";
        $f[]="[AsCertifsManager]=\"yes\"";
        $f[]="[AsArticaAdministrator]=\"yes\"";
        $f[]="[AsSystemAdministrator]=\"yes\"";
        $f[]="[AsPostfixAdministrator]=\"yes\"";
        $f[]="[AsSquidAdministrator]=\"yes\"";
        $f[]="[AsDansGuardianAdministrator]=\"yes\"";
        $f[]="[AsSystemWebMaster]=\"yes\"";
        $f[]="[AsArticaMetaAdmin]=\"yes\"";
        $f[]="[AsSquidPersonalCategories]=\"yes\"";
        $f[]="[AsFirewallManager]=\"yes\"";
        $f[]="[AsProxyMonitor]=\"yes\"";
        $f[]="[AsVPNManager]=\"yes\"";
        $f[]="[AsMailBoxAdministrator]=\"yes\"";
        $f[]="[AsDatabaseAdministrator]=\"yes\"";
        return @implode("\n", $f)."\n";
    }

    private function privileges_Manager():bool{
        $this->ParsePrivileges($this->default_all_rights());
        return true;
    }

    private function IsHarmpPrivs($GroupDN):bool{
        if(strlen($GroupDN)<2){return true;}
        if($this->EnableManagedReverseProxy==0){return false;}
        $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
        $this->VERBOSED("SELECT groupid FROM privs WHERE dngroup='$GroupDN'",__LINE__);
        $results=$q->QUERY_SQL("SELECT groupid FROM privs WHERE dngroup='$GroupDN'");
        foreach ($results as $index=>$ligne){
            if($ligne["groupid"]==0){continue;}
            $this->VERBOSED("$GroupDN == {$ligne["groupid"]} OK",__LINE__);
            $_SESSION["HAMRP_PRIVS"][$ligne["groupid"]]=true;
        }

        return true;

    }


    private function privileges_groups():bool{
        $this->VERBOSED("Launch privileges_groups",__LINE__);
        if(!$GLOBALS["VERBOSE"]) {
            if (isset($GLOBALS["privileges_groups_executed$this->uid"])) {
                return true;
            }
            $GLOBALS["privileges_groups_executed"] = true;
        }

        if(isset($_SESSION["ACTIVE_DIRECTORY_INDEX"])){
            // Check here the adagent
            if($_SESSION["ACTIVE_DIRECTORY_INDEX"]=="adagent"){
                $this->VERBOSED("START privileges_groups() step4",__LINE__);
                // This one will grab all groups from the user.
               if($this->privileges_groups_adagent($_SESSION["ACTIVE_DIRECTORY_DN"])){return true;}
            }

            if($_SESSION["ACTIVE_DIRECTORY_INDEX"]>0){
                if($this->privileges_groups_AD($_SESSION["ACTIVE_DIRECTORY_INDEX"])){return true;}
            }
        }
        $this->VERBOSED("START privileges_groups() step5",__LINE__);

        $ldap=new clladp();
        if($ldap->IsKerbAuth()){
            $this->VERBOSED("IsKerbAuth() -> `TRUE` -> privileges_groups_AD()",__LINE__);
            if($this->privileges_groups_AD()){return true;}
        }else{
            $this->VERBOSED("IsKerbAuth() -> `FALSE`",__LINE__);
        }

        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP")==0)){
            return true;
        }
        if(!$ldap->ldap_connection){
            return true;
        }
        $filters=array("gidNumber","ArticaGroupPrivileges","cn");
        $dr =@ldap_search($ldap->ldap_connection,$ldap->suffix,"(&(objectClass=posixGroup)(memberUid=$this->uid))",$filters);
        if(!$dr){
            $error=@ldap_err2str(ldap_errno($ldap->ldap_connection));
            $this->VERBOSED("Fatal Error: $error",__CLASS__.'/'.__LINE__);
        }

        $result = @ldap_get_entries($ldap->ldap_connection, $dr);
        if(!$result){
           return false;
        }
        if($result){
            for($i=0;$i<$result["count"];$i++){
                $gidnumber=$result[$i]["gidnumber"][0];
                $this->VERBOSED("LDAP Group: $gidnumber");
                $this->IsHarmpPrivs($gidnumber);
                if($gidnumber==544){
                    include_once(dirname(__FILE__)."/class.translate.rights.inc");
                    $pp=new TranslateRights();
                    $pt=$pp->GetPrivsArray();
                    foreach ($pt as $num=>$ligne){
                        $this->privs[$num]="yes";
                    }
                    continue;
                }
                $zNginxPrivs="";
                $nginx=new NginxPrivs($gidnumber);
                $NginxPrivsText=$nginx->NginxPrivs();
                if(strlen($NginxPrivsText)>2){
                    $zNginxPrivs="\n[NginxPrivs]=\"$NginxPrivsText\"";
                }


                $this->VERBOSED("[$this->uid] is a part of {$result[$i]["cn"][0]} ($gidnumber)",__CLASS__.'/'.__LINE__);
                if(isset($result[$i]["articagroupprivileges"][0])){
                    $ArticagGroupPrivileges=$result[$i]["articagroupprivileges"][0];
                }

                $this->ParsePrivileges($ArticagGroupPrivileges.$zNginxPrivs);

            }
        }else{
            $error=@ldap_err2str(@ldap_errno($ldap->ldap_connection));
            if($error<>"Success"){$this->VERBOSED("Fatal Error: $error",__CLASS__.'/'.__LINE__);}
        }

        return true;
    }


    public function ParsePrivileges($content=null):array{
        VERBOSE("ParsePrivileges: $content",__LINE__);
        if(!is_array($this->privs)){$this->privs=array();}
        if(preg_match_all('#\[([a-zA-Z]+)\]="(.+?)"#is',$content,$reg)){
            foreach ($reg[1] as $num=>$ligne){
                if($ligne=="ForceLanguageUsers"){$this->privs[$ligne]=$ligne;}
                if($ligne=="VirtualSambaServer"){$this->privs["VIRTUALS_SERVERS"][$reg[2][$num]]=true;}
                if($ligne=="PostfixInstance"){$this->privs["POSTFIX_SERVERS"][$reg[2][$num]]=true;
                }
                if($ligne=="NginxPrivs"){
                    $tb=explode(",",$reg[2][$num]);
                    foreach ($tb as $ServiceID){
                        $this->privs["NGINX_SERVICES"][$ServiceID]=true;
                    }
                }
                if($ligne=="SimpleAclsPrivs"){
                    $tb=explode(",",$reg[2][$num]);
                    foreach ($tb as $ServiceID){
                        $this->privs["SIMPLE_ACLS"][$ServiceID]=true;
                    }
                }
                $this->privs[$ligne]=$reg[2][$num];
            }
        }
        return  $this->privs;
    }

    private function mem_getusage():string{
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

    private function ParsePrivilegesOU(){
        $ldap=new clladp();
        $userdn=$ldap->_Get_dn_userid($this->uid);
        if(!preg_match("#ou=users,ou=(.+?),dc#",$userdn,$re)){return ;}
        $ou=$re[1];
        $hash=$ldap->OUDatas($ou);
        if($this->webpage=="logon.php"){$this->VERBOSED("[$this->uid] parsing $ou organization privileges ",__CLASS__.'/'.__LINE__);}
        if(isset($hash["ArticaGroupPrivileges"])){
            $privs=$this->ParsePrivileges($hash["ArticaGroupPrivileges"]);
        }
    }

    private function GetContent(): void{
        $f=array();
        foreach ($this->privs as $num=>$ligne){
            if($num=="NGINX_SERVICES"){
                $f[]=sprintf("[%s]=\"%s\"",$num,@implode(",",$ligne));
                continue;
            }
            $f[]="[$num]=\"$ligne\"";
        }

        $this->content=@implode("\n",$f);
    }

    private function SinglePrivileges():void{
        if($GLOBALS["AS_ROOT"]){return;}
        if($this->uid==-100){return;}
        if($this->uid==null){return;}
        if($this->privilegesuid==null){
            if($GLOBALS["VERBOSE"]){echo "[PRIVS]:[$this->uid] parsing  user privileges\n";}
            $ArticaGroupPrivileges=$this->UserPrivs();
        }else{
            $ArticaGroupPrivileges=$this->privilegesuid;
        }

        $this->ParsePrivileges($ArticaGroupPrivileges);
    }


    private function UserPrivs():string{
        if($GLOBALS["AS_ROOT"]){return "";}
        $ldap=new clladp();
        if($ldap->IsKerbAuth()){return "";}
        $searchdn="dc=organizations,$ldap->suffix";
        if(!$ldap->ldap_connection){
            $this->VERBOSED("LDAP Connection failed",__CLASS__.'/'.__LINE__);
            return "";
        }


        $sr =@ldap_search($ldap->ldap_connection,$searchdn,"(uid=$this->uid)");
        if(!$sr){
            $this->VERBOSED("Unable to find : $this->uid stamp DoesNotExists has TRUE",__CLASS__.'/'.__LINE__);
            return "";
        }

        $entry_id = ldap_first_entry($ldap->ldap_connection,$sr);
        if(!$entry_id){
            writelogs( "INFOS: bad value $entry_id: ($entry_id)  find: (uid=$this->uid) -> aborting function search engine doesn`t found the pattern",__CLASS__.'/'.__LINE__);
            return "";
        }
        $attrs = ldap_get_attributes($ldap->ldap_connection, $entry_id);
        if(!isset($attrs["ArticaGroupPrivileges"][0])){$attrs["ArticaGroupPrivileges"][0]=null;}
        if($GLOBALS["VERBOSE"]){echo "[PRIVS]:[$this->uid] {$attrs["ArticaGroupPrivileges"][0]}\n";}
        if(isset($attrs["ArticaGroupPrivileges"][0])){
            return strval($attrs["ArticaGroupPrivileges"][0]);
        }
        return "";
    }



}