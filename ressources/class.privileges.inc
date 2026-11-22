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
    // Garde d'idempotence de SearchPrivileges() dans la requete courante.
    private $SEARCH_DONE=false;
    /**
     * Tenants SIEM cumules des groupes de l'utilisateur (feature 021).
     *
     * Un ENSEMBLE, pas une liste: les cles portent les identifiants, si bien que deux groupes
     * portant le meme tenant n'y entrent qu'une fois. Le doublon serait inoffensif pour la requete
     * SQL finale, mais il fausserait tout decompte « combien de tenants cette personne voit-elle ».
     */
    private $SIEM_TENANTS=array();
    private $EnableArticaSiem=null;
    /**
     * Artica Meta multi-tenant (spec 2026-11-13 5.2) : references des groupes de l'utilisateur
     * (meme forme que siem_tenants_privs.dngroup : DN, gidNumber, sqlite:<id>), methode
     * d'authentification qui a resolu les privileges, et nom de connexion tel que range dans
     * $_SESSION["uid"]. Servent a GET /netagents/tenants/owners/resolve.
     */
    private $META_GROUPS=array();
    private $META_SOURCE="";
    private $LOGIN_NAME="";
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
        $this->LOGIN_NAME=trim(strval($uid));
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
            $this->LOGIN_NAME=trim($re[3]);
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
        $this->privs["AsMetaAdmin"] = "yes";
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
    /**
     * Ecrit le cache des privileges -- et REFUSE d'y ecrire un jeu VIDE.
     *
     * Un tableau vide se serialise en "a:0:{}", soit "YTowOnt9" une fois encode:
     * 8 caracteres. Or la relecture ne teste que strlen($privsCache)>1, donc un
     * « cet utilisateur n'a aucun droit » etait memorise et rejoue comme un cache
     * parfaitement valide, indiscernable d'un vrai jeu de droits. La branche de
     * relecture injecte ensuite AsSystemAdministrator="no" quand la cle manque,
     * ce qui porte le compteur a 1 -- exactement la valeur qu'affiche un compte
     * ayant UN droit reel.
     *
     * Incident du 18/08/2026: des privileges poses sur un groupe Active Directory
     * dont l'utilisateur n'etait pas membre (ARTICA_SMTP_ADMIN au lieu de
     * ARTICA_SMTP_MANAGER) ont produit un jeu vide; « SetUser: privileges_array = 1 »
     * est reste identique pendant un mois en signifiant successivement « un droit »
     * puis « aucun droit ». Le fichier de cache contenait YTowOnt9.
     *
     * Le verdict est journalise DANS TOUS LES CAS, sans dependre de
     * DebugWebConsoleAuth: c'est la seule trace qui separe « aucun droit trouve »
     * de « droits trouves », et son absence est ce qui a rendu l'incident opaque.
     */
    private function CachePrivileges($origin=""){
        // Le perimetre SIEM entre dans le jeu de privileges JUSTE AVANT la mise en cache, donc par
        // un seul point quel que soit le chemin de resolution (AD, adagent, openldap, base locale).
        $this->FinalizeSiemTenants();
        // Tenants Artica Meta : meme point unique, juste avant la mise en cache.
        $this->FinalizeMetaTenants();
        $count=is_array($this->privs)?count($this->privs):0;
        if($count==0){
            writelogs("[$this->uid] NO privilege resolved ($origin) - cache NOT written",
                __FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        writelogs("[$this->uid] $count privilege(s) resolved ($origin): "
            .@implode(",",array_keys($this->privs)),__FUNCTION__,__FILE__,__LINE__);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($this->UID_KEY,base64_encode(serialize($this->privs)));
        return true;
    }

    public function SearchPrivileges(){
        // Le constructeur rappelle cette methode quand le premier passage n'a rien
        // donne. Tant qu'un resultat vide etait mis en cache, ce second appel etait
        // gratuit; maintenant qu'il ne l'est plus, il refarait toute la resolution
        // LDAP a chaque page pour un compte sans droit. Le premier passage a deja
        // tout tente: on ne recommence pas.
        if($this->SEARCH_DONE){
            $this->VERBOSED("SearchPrivileges() already performed in this request",__LINE__);
            return;
        }
        $this->SEARCH_DONE=true;
        $privsCache=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($this->UID_KEY);

        if($GLOBALS["DEBUG_PRIVS"]){
            $privsCache="";
        }
        $this->VERBOSED("privsCache:[$privsCache]",__LINE__);
        if(strlen($privsCache)>1){
            $this->VERBOSED("$this->UID_KEY -- HIT !!!",__LINE__);
            $array=@unserialize(base64_decode($privsCache));
            // Tenants Artica Meta resolus avant un changement de proprietaires : cache ignore,
            // resolution complete (meta_tenants_flush_privs_cache(), class.sockets.inc).
            if(is_array($array) && function_exists("meta_tenants_privs_stale") && meta_tenants_privs_stale($array)){
                writelogs("[$this->uid] cached privileges predate an Artica Meta owners change - resolving again",
                    __FUNCTION__,__FILE__,__LINE__);
                $array=array();
            }
            // count($array)>0 et pas seulement !==false: "a:0:{}" se desserialise
            // en un tableau vide parfaitement valide. L'accepter revenait a servir
            // « aucun droit » depuis le cache a chaque requete, y compris apres
            // correction de l'attribution cote annuaire. Un cache vide est ignore,
            // la resolution complete reprend la main, et CachePrivileges() ne le
            // reecrira pas -- les appliances deja piegees se reparent seules.
            if(is_array($array) && count($array)>0){
                $this->privs=$array;
                if(!isset($this->privs["AsSystemAdministrator"])){$this->privs["AsSystemAdministrator"]="no";}
                $this->VERBOSED("AsSystemAdministrator:[{$this->privs["AsSystemAdministrator"]}]",__LINE__);
                if($this->privs["AsSystemAdministrator"]=="yes"){
                    $this->privs["AsDatabaseAdministrator"]="yes";
                }
                return;
            }
            writelogs("[$this->uid] cache present but EMPTY or unreadable - ignored, resolving again",
                __FUNCTION__,__FILE__,__LINE__);
            $this->VERBOSED("HIT BAD unserialize == FALSE or empty !!!",__LINE__);
        }else{
            $this->VERBOSED("$this->UID_KEY ".strlen($privsCache)." bytes in Memcached",__LINE__);
        }
        $this->VERBOSED("->SearchPrivileges() ACTIVE DIRECTORY INDEX=$this->ACTIVE_DIRECTORY_INDEX ACTIVE_DIRECTORY_DN=$this->ACTIVE_DIRECTORY_DN",__LINE__);

        if(isset($this->ACTIVE_DIRECTORY_INDEX) AND $this->ACTIVE_DIRECTORY_INDEX=="adagent"){
            $this->META_SOURCE="ad";
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
            $this->CachePrivileges("adagent, ".count($groups)." group(s) examined");
            return;
        }


        if($this->DebugWebConsoleAuth==1) {
            writelogs("[$this->uid]: ACTIVE_DIRECTORY_INDEX = $this->ACTIVE_DIRECTORY_INDEX dn=[$this->ACTIVE_DIRECTORY_DN]", __FUNCTION__, __FILE__, __LINE__);
        }

        if(is_numeric($this->ACTIVE_DIRECTORY_INDEX) AND ($this->ACTIVE_DIRECTORY_DN<>null)){
            $this->META_SOURCE="ad";
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
                    $this->CachePrivileges("AD/Domain Admins");
                    return;
                }
                if(preg_match("#CN=Enterprise Admins,CN=Users,#i",$dnGroup)){
                    $this->VERBOSED($dnGroup." MATCHES CN=Enterprise Admins,CN=Users (SUCCESS)",__LINE__);
                    $this->ParsePrivileges($this->default_all_rights());
                    return;
                }
                if(preg_match("#CN=Administrators,CN=Builtin#i",$dnGroup)){
                    $this->ParsePrivileges($this->default_all_rights());
                    $this->CachePrivileges("AD/Builtin Administrators");
                    return;
                }
            }
            $this->CachePrivileges("AD index $this->ACTIVE_DIRECTORY_INDEX, ".count($groups)." group(s) examined");
            return;
        }


        if($this->sqliteid>0){
            $this->META_SOURCE="sqlite";
            $this->VERBOSED("->SearchPrivileges() IN SQLITE sqliteid:$this->sqliteid via /localadmins/users/{id}/privileges",__LINE__);
            $sqliteid=intval($this->sqliteid);
            $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/localadmins/users/$sqliteid/privileges")),true);
            $results=array();
            if(!is_array($resp) || empty($resp["success"])){
                $this->VERBOSED("localadmins API failed: ".strval(is_array($resp)?($resp["error"] ?? "unknown"):"no response"),__LINE__);
            }else{
                $results=is_array($resp["data"] ?? null)?$resp["data"]:array();
            }
            foreach ($results as $index=>$ligne){
                $groupid=intval($ligne["id"]);
                $this->VERBOSED("FIND GROUP $groupid Name={$ligne["groupname"]}",__LINE__);
                $this->IsHarmpPrivs("sqlite:$groupid");
                $this->SiemTenantsPrivs("sqlite:$groupid");

                if(strtolower($ligne["groupname"])=='administrators') {
                    $hash["ArticaGroupPrivileges"]=$this->AllPrivs();
                }else{
                    $hash["ArticaGroupPrivileges"]=base64_decode($ligne["privileges"]);
                }
                $nginx=new NginxPrivs("sqlite:$groupid");
                $NginxPrivsText=$nginx->NginxPrivs();
                if(strlen($NginxPrivsText)>0){
                    $zNginxPrivs="\n[NginxPrivs]=\"$NginxPrivsText\"";
                    $hash["ArticaGroupPrivileges"]=$hash["ArticaGroupPrivileges"].$zNginxPrivs;
                }

                $this->ParsePrivileges($hash["ArticaGroupPrivileges"]);

            }
            $this->CachePrivileges("sqlite localadmins");
            return;
        }
        if(trim((string) $this->uid)==null){
            $this->VERBOSED("->SearchPrivileges() -> no uid, return",__LINE__);
            return;
        }
        if($this->radiusid>0) {
            $this->META_SOURCE="radius";
            $q = new mysql();
            $results = $q->QUERY_SQL("SELECT groupname FROM radusergroup WHERE username='$this->uid'", "artica_backup");
            while ($ligne = mysqli_fetch_assoc($results)) {
                $groupname = $ligne["groupname"];
                $this->MetaTenantGroup($groupname);
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
            $this->CachePrivileges("radius");
            return;
        }
        if($this->uid==-100){
            if($this->webpage=="fw.login.php"){$this->VERBOSED("$this->webpage: -->privileges_Manager -> uid=`$this->uid`",__CLASS__."/".__LINE__);}
            $this->privileges_Manager();
            $this->CachePrivileges("manager");
            return;
        }


        if($this->webpage=="fw.login.php"){$this->VERBOSED("$this->webpage: -->SearchPrivileges -> uid=`$this->uid/$this->eMailAddr`",__CLASS__."/".__LINE__);}
        $this->ldap=new clladp();
        // Annuaire OpenLDAP par defaut ; privileges_groups_AD()/adagent() le remplacent par
        // "ad" quand ce sont eux qui ont resolu les groupes.
        $this->META_SOURCE="ldap";
        $this->privileges_groups();
        $this->GetContent();
        $this->SinglePrivileges();
        $this->CachePrivileges("openldap");
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
            $this->SiemTenantsPrivs($dnGroup);
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
        $this->META_SOURCE="ad";
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
            $this->SiemTenantsPrivs($dnGroup);
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
        $this->META_SOURCE="ad";
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
        $f[]="[AsMetaAdmin]=\"yes\"";
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

    /**
     * Cumule les tenants SIEM portes par un groupe (feature 021).
     *
     * Appelee pour CHAQUE groupe de l'utilisateur, aux memes endroits que IsHarmpPrivs(): un
     * utilisateur membre de plusieurs groupes voit l'UNION de leurs tenants, jamais l'intersection
     * ni le dernier resolu.
     *
     * Aucune erreur n'est remontee a l'appelant, volontairement: si la table est injoignable,
     * l'ensemble reste vide et l'utilisateur se voit REFUSER l'acces au SIEM. Une lecture qui
     * echoue ne doit jamais elargir un perimetre -- c'est la seule direction d'echec acceptable
     * pour un mecanisme d'isolation.
     */
    private function SiemTenantsPrivs($GroupDN):bool{
        // Chaque groupe vu ici l'est aussi pour la resolution des tenants Artica Meta, AVANT le
        // test du module SIEM : les deux perimetres sont independants.
        $this->MetaTenantGroup($GroupDN);
        if(strlen($GroupDN)<2){return true;}
        if($this->EnableArticaSiem===null){
            $this->EnableArticaSiem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaSiem"));
        }
        if($this->EnableArticaSiem==0){return false;}
        if(!class_exists("postgres_sql")){include_once(dirname(__FILE__)."/class.postgres.inc");}
        $q=new postgres_sql();
        $dnsql=function_exists("pg_escape_string2")?pg_escape_string2((string)$GroupDN):str_replace("'","''",(string)$GroupDN);
        $results=$q->QUERY_SQL("SELECT tenant_id FROM siem_tenants_privs WHERE dngroup='$dnsql'");
        // La table n'existe pas tant qu'aucune attribution n'a ete faite: ce n'est pas une anomalie,
        // c'est l'etat d'une appliance ou personne n'a encore ouvert l'onglet. On sort en silence.
        if(!$q->ok){return false;}
        while($ligne=pg_fetch_assoc($results)){
            $tid=trim((string)$ligne["tenant_id"]);
            if($tid===""){continue;}
            $this->SIEM_TENANTS[$tid]=true;
            $this->VERBOSED("SIEM tenant [$tid] from $GroupDN",__LINE__);
        }
        return true;
    }

    /**
     * Depose le perimetre resolu sous la cle SIEM_TENANTS, juste avant la mise en cache.
     *
     * Forme: une chaine d'identifiants separes par des virgules, TRIEE. C'est exactement la forme
     * de l'en-tete X-Tenant-Id, donc rien ne se reinterprete entre ici et l'API; et un ordre stable
     * rend deux journaux comparables, ce qu'un ordre variable interdirait.
     *
     * UN PERIMETRE VIDE N'ECRIT RIEN. « Aucun tenant » et « pas encore resolu » sont indiscernables
     * une fois serialises: c'est exactement la confusion qui a rendu l'incident du 18/08/2026
     * opaque pendant un mois sur les privileges eux-memes. Absent vaut donc « aucun tenant », et la
     * resolution complete reprendra la main au prochain passage.
     */
    private function FinalizeSiemTenants(){
        if(count($this->SIEM_TENANTS)==0){return;}
        $list=array_keys($this->SIEM_TENANTS);
        sort($list,SORT_STRING);
        $this->privs["SIEM_TENANTS"]=@implode(",",$list);
    }

    /** Retient une reference de groupe pour la resolution des tenants Artica Meta. */
    private function MetaTenantGroup($GroupDN){
        $ref=trim(strval($GroupDN));
        if(strlen($ref)<1){return;}
        $this->META_GROUPS[$ref]=true;
    }

    /**
     * Depose les tenants Artica Meta resolus sous la cle META_TENANTS, juste avant la mise en
     * cache (meme point unique que FinalizeSiemTenants()).
     *
     * Ne s'applique qu'a un compte AsMetaAdmin qui n'est PAS infogerant : AsArticaMetaAdmin (ou
     * AsArticaAdministrator, qui l'implique dans usersMenus) donne le perimetre global et prime.
     * Forme : ids tries, separes par des virgules. AUCUN tenant ou resolution en echec => la cle
     * est ABSENTE : le compte ne recoit aucun tenant (echec ferme) et la console lui montre un
     * parc vide (META_SCOPE_HEADERS() pose alors tenant:0). L'echec est journalise.
     */
    private function FinalizeMetaTenants(){
        unset($this->privs["META_TENANTS"]);
        unset($this->privs["META_TENANTS_GEN"]);
        if(!is_array($this->privs)){return;}
        if(!privileges_priv_yes($this->privs,"AsMetaAdmin")){return;}
        if(privileges_meta_global($this->privs)){return;}
        // Generation des proprietaires sous laquelle ce resultat vaut (meme en echec ou sans
        // tenant) : meta_tenants_flush_privs_cache() le rend perime, cache et sessions compris.
        $this->privs["META_TENANTS_GEN"]=meta_tenants_generation();
        $source=$this->META_SOURCE;
        $uid=$this->LOGIN_NAME;
        if($source===""){
            writelogs("[$uid] Artica Meta tenants: unknown authentication method, no tenant granted",
                __FUNCTION__,__FILE__,__LINE__);
            return;
        }
        $groups=array_keys($this->META_GROUPS);
        if(is_array($this->ACTIVE_DIRECTORY_GROUPSDN)){
            foreach($this->ACTIVE_DIRECTORY_GROUPSDN as $dn){$groups[]=strval($dn);}
        }
        if(!method_exists($GLOBALS["CLASS_SOCKETS"],"META_TENANTS_RESOLVE")){
            writelogs("[$uid] Artica Meta tenants: resolver unavailable, no tenant granted",__FUNCTION__,__FILE__,__LINE__);
            return;
        }
        $ids=$GLOBALS["CLASS_SOCKETS"]->META_TENANTS_RESOLVE($source,$uid,$groups);
        if(!is_array($ids)){
            writelogs("[$uid] Artica Meta tenants: resolution failed ($source), no tenant granted",__FUNCTION__,__FILE__,__LINE__);
            return;
        }
        if(count($ids)==0){
            writelogs("[$uid] Artica Meta tenants: no tenant owned ($source, ".count($groups)." group(s))",
                __FUNCTION__,__FILE__,__LINE__);
            return;
        }
        $this->privs["META_TENANTS"]=@implode(",",$ids);
        writelogs("[$uid] Artica Meta tenants: ".$this->privs["META_TENANTS"]." ($source)",__FUNCTION__,__FILE__,__LINE__);
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
                $this->SiemTenantsPrivs($gidnumber);
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
                if(strlen($NginxPrivsText)>0){
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

/**
 * Artica Meta multi-tenant (spec 2026-11-13 5.2) : cles de session lues par le daemon
 * (netagents/metascope ParseSession), deposees ici et nulle part ailleurs.
 *
 *   $_SESSION["META_SCOPE"]  = "global"                     infogerant (AsArticaMetaAdmin)
 *                            = "tenants:<id>[,<id>...]"     compte AsMetaAdmin, ids tries
 *                            (absente)                      aucun privilege Meta, ou aucun tenant
 *   $_SESSION["META_TENANT"] = id actif (entier), toujours un element de la liste ; absente hors
 *                              "tenants:". Un id actif deja choisi (selecteur des pages Meta) est
 *                              garde tant qu'il reste dans la liste, sinon le premier.
 *
 * Appelee apres chaque depot de $_SESSION["privileges_array"] (SetUser, BuildSession, lien
 * d'API) et par SetManager (super-administrateur : global).
 */
// privileges_priv_yes(), privileges_meta_global(), meta_tenants_*() : class.sockets.inc
// (predicats partages avec sockets::META_SCOPE_HEADERS()).
if(!function_exists("meta_scope_compute")){
    /**
     * Fonction pure : array("scope"=>"global"|"tenants:..."|"", "tenants"=>int[] tries).
     * $superadmin : session ouverte par SetManager() (uid -100 ET ARTICA_SUPERADMIN).
     */
    function meta_scope_compute($privs,$superadmin=false):array{
        if($superadmin || privileges_meta_global($privs)){
            return array("scope"=>"global","tenants"=>array());
        }
        if(!privileges_priv_yes($privs,"AsMetaAdmin")){return array("scope"=>"","tenants"=>array());}
        $ids=array();
        $raw=isset($privs["META_TENANTS"])?strval($privs["META_TENANTS"]):"";
        foreach(explode(",",$raw) as $p){
            $p=trim($p);
            if(!preg_match('/^[0-9]{1,18}$/',$p)){continue;}
            $id=intval($p);
            if($id>0){$ids[$id]=$id;}
        }
        if(count($ids)==0){return array("scope"=>"","tenants"=>array());}
        $ids=array_values($ids);
        sort($ids,SORT_NUMERIC);
        return array("scope"=>"tenants:".implode(",",$ids),"tenants"=>$ids);
    }
}
if(!function_exists("meta_scope_session_sync")){
    function meta_scope_session_sync($privs=null){
        if(!isset($_SESSION) || !is_array($_SESSION)){return;}
        if($privs===null){
            $privs=isset($_SESSION["privileges_array"])?$_SESSION["privileges_array"]:array();
        }
        $uid=isset($_SESSION["uid"])?strval($_SESSION["uid"]):"";
        $superadmin=!empty($_SESSION["ARTICA_SUPERADMIN"]) && $uid==="-100";
        $r=meta_scope_compute($privs,$superadmin);
        if($r["scope"]===""){
            unset($_SESSION["META_SCOPE"]);
            unset($_SESSION["META_TENANT"]);
            unset($_SESSION["META_GEN"]);
            return;
        }
        $_SESSION["META_SCOPE"]=$r["scope"];
        if($r["scope"]==="global"){
            unset($_SESSION["META_TENANT"]);
            unset($_SESSION["META_GEN"]);
            return;
        }
        // META_GEN : generation des proprietaires sous laquelle CES tenants ont ete resolus
        // (META_TENANTS_GEN du jeu de privileges). Le daemon refuse une session de tenant dont
        // META_GEN differe de MetaTenantsGeneration (proprietaires changes depuis) ou manque
        // (ruling R-F3) : une page PHP reconstruit alors le perimetre. Absente => "" (perime).
        $_SESSION["META_GEN"]=isset($privs["META_TENANTS_GEN"])?trim(strval($privs["META_TENANTS_GEN"])):"";
        $active=0;
        if(isset($_SESSION["META_TENANT"]) && preg_match('/^[0-9]{1,18}$/',strval($_SESSION["META_TENANT"]))){
            $active=intval($_SESSION["META_TENANT"]);
        }
        if(!in_array($active,$r["tenants"],true)){$active=$r["tenants"][0];}
        $_SESSION["META_TENANT"]=intval($active);
    }
}
