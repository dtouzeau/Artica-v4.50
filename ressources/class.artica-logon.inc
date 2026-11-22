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
    // Clé canonique de l'identité authentifiée, support du second facteur.
    // Reste null quand l'annuaire ne fournit aucun identifiant stable: la
    // tâche 9 traite ce cas en refus, jamais en passe-droit.
    public $IDENTITY_KEY=null;
    function __construct($username=null,$password=null){
        VERBOSE("__construct",__LINE__);
        if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
        if($username<>null){$this->username=trim($username);}
        if($password<>null){$this->password=trim($password);}
        $this->DebugWebConsoleAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugWebConsoleAuth"));
        writelogs("DebugWebConsoleAuth=$this->DebugWebConsoleAuth",__FUNCTION__,__FILE__,__LINE__);
        VERBOSE("__construct",__LINE__);
    }

    /**
     * Emet un evenement d'audit de securite vers le daemon.
     *
     * Distinct d'admin_tracks() : celui-ci trace les ACTIONS d'administration
     * et n'ecrit qu'en base + syslog applicatif ; ici on produit la preuve de
     * CONNEXION exigee par le BSI IT-Grundschutz SYS.1.1, y compris les
     * ECHECS, que rien ne journalisait jusqu'ici (seul writelogs() de debug).
     *
     * Le daemon applique la meme condition que admin_tracks (TrackAdmins=1 et
     * licence Corporate) : inutile de la retester ici, un seul endroit decide.
     *
     * L'appel est prefixe de @ et son resultat ignore : une panne du canal
     * d'audit ne doit jamais empecher une connexion legitime d'aboutir.
     */
    private function audit_logon($username,$success,$detail=""){
        if(!isset($GLOBALS["CLASS_SOCKETS"])){return;}
        @$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/securityaudit/event",
            array(
                "kind"    => "auth.console",
                "actor"   => strval($username),
                "src"     => strval($this->GetRemoteIP()),
                "outcome" => $success ? "success" : "failure",
                "detail"  => strval($detail),
            ));
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

    /**
     * Convertit un objectGUID Active Directory en sa forme lisible.
     *
     * On prend le GUID et NON le DN ni le sAMAccountName: le DN change à tout
     * renommage ou déplacement d'unité d'organisation, et un sAMAccountName
     * peut être réattribué après suppression -- le nouveau titulaire hériterait
     * alors du secret de l'ancien. Le GUID survit aux deux.
     *
     * L'attribut n'arrive pas toujours sous la même forme: les deux moteurs
     * LDAP directs (ActiveDirectoryLinked/ActiveDirectoryMain) le posent à la
     * racine (`objectguid`), tandis que l'agent AD distant (ActiveDirectoryFromAgent,
     * cf. getMemberInfoADAgent() dans class.external.ad.inc) l'imbrique sous
     * `data`. L'agent parle JSON sur HTTP (ADAgent::getMemberInfo(), voir
     * class.ad-agent.inc) -- le binaire brut ne traverse pas JSON -- donc la
     * valeur peut y arriver encodée plutôt que sous les 16 octets bruts que
     * fournit directement l'extension LDAP. On tolère les formes reçues, on
     * refuse (null) tout ce qu'on ne sait pas interpréter -- jamais de repli
     * sur DN/sAMAccountName.
     */
    private function ad_guid_from_infos($infos){
        if(!is_array($infos)){return null;}
        $raw=null;
        if(isset($infos["objectguid"])){
            $raw=$infos["objectguid"];
        } elseif(isset($infos["data"]["objectguid"])){
            $raw=$infos["data"]["objectguid"];
        }
        if($raw===null){return null;}
        if(is_array($raw)){
            if(!isset($raw[0])){return null;}
            $raw=$raw[0];
        }
        return $this->ad_guid_normalize($raw);
    }

    /**
     * Ramène un objectGUID -- sous l'une des formes qu'il peut légitimement
     * prendre -- vers sa forme canonique tiretée en minuscules, ou null si la
     * forme n'est reconnue dans aucune des variantes acceptées:
     *   - 16 octets bruts (extension LDAP: `["objectguid"][0]`)
     *   - chaîne hex de 32 caractères (mêmes 16 octets, encodés en hexadécimal)
     *   - base64 de ces mêmes 16 octets (transport JSON de l'agent AD)
     *   - chaîne déjà au format tireté standard (`xxxxxxxx-xxxx-...`)
     */
    private function ad_guid_normalize($raw){
        if(!is_string($raw)){return null;}
        if(preg_match('#^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$#',$raw)){
            // Déjà au format d'affichage final: aucun réarrangement d'octets à faire.
            return strtolower($raw);
        }
        $bytes=null;
        if(strlen($raw)==16){
            $bytes=$raw;
        } elseif(preg_match('#^[0-9A-Fa-f]{32}$#',$raw)){
            $bytes=hex2bin($raw);
        } else {
            $decoded=base64_decode($raw,true);
            if($decoded!==false && strlen($decoded)==16){
                $bytes=$decoded;
            }
        }
        if($bytes===null){return null;}
        if(strlen($bytes)<>16){return null;}
        $h=bin2hex($bytes);
        // Les trois premiers champs d'un GUID Microsoft sont stockés en
        // petit-boutiste; les deux derniers en gros-boutiste.
        return substr($h,6,2).substr($h,4,2).substr($h,2,2).substr($h,0,2)."-"
              .substr($h,10,2).substr($h,8,2)."-"
              .substr($h,14,2).substr($h,12,2)."-"
              .substr($h,16,4)."-".substr($h,20,12);
    }

    private function ad_identity_key($index){
        $guid=$this->ad_guid_from_infos($this->ACTIVE_DIRECTORY_INFO);
        if($guid==null){return null;}
        return "ad:$index:$guid";
    }

    /**
     * Clé canonique de l'administrateur de l'assistant d'installation
     * (WizardSavedSettings["administrator"]), au format « wizadmin:<nom> ».
     *
     * Ce compte n'a pas d'identifiant numérique stable: il n'existe ni dans
     * admins.db, ni dans un annuaire -- il vit dans un réglage, et son nom EST
     * son identité (isAdministrator() n'authentifie que sur ce nom et son mot
     * de passe). Le nom est donc la seule source possible, et le renommer
     * revient à créer un autre compte: le réenrôlement qui s'ensuit est
     * correct, pas un défaut.
     *
     * Le nom est mis en minuscules -- comme la comparaison d'authentification
     * elle-même (isAdministrator() compare des trim(strtolower())) -- puis
     * réduit au jeu de caractères que la clé traverse sans ambiguïté: segment
     * d'URL, colonne SQLite, et ligne syslog d'audit sur une seule ligne. Un
     * nom qui ne laisse rien après ce filtre rend null: l'identité est alors
     * déclarée non enrôlable (§4.3) et la connexion est REFUSÉE avec un
     * diagnostic, jamais laissée passer sans second facteur.
     */
    private function wizadmin_identity_key(){
        $name=trim(strtolower(strval($this->username)));
        $name=preg_replace('#[^a-z0-9._@\-]#','',$name);
        if($name==""){return null;}
        return "wizadmin:".substr($name,0,64);
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
            $this->IDENTITY_KEY="manager:-100";
            $this->SetManager();
            return true;
        }
        $this->VERBOSE("<strong>isAdministrator() </strong>?",__LINE__);
        if($this->isAdministrator()){
            // PAS "manager:-100". isAdministrator() authentifie un compte
            // DIFFERENT du Manager -- l'administrateur de l'assistant
            // (WizardSavedSettings["administrator"]/["administratorpass"]) --
            // et refuse explicitement le nom du Manager (ligne ~526). Partager
            // la clé d'identité, c'était partager LE SECRET TOTP: le premier
            // des deux à s'enrôler détenait le second facteur de l'autre, et
            // aucun des deux ne pouvait être révoqué seul. C'est précisément
            // le défaut que cette fonctionnalité existe pour supprimer
            // (conception §1: « un secret partagé ne prouve l'identité de
            // personne »).
            $this->IDENTITY_KEY=$this->wizadmin_identity_key();
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
        // Perimetre Artica Meta (tenants) : jamais herite par la session suivante.
        unset($_SESSION["META_SCOPE"]);
        unset($_SESSION["META_TENANT"]);
        unset($_SESSION["META_GEN"]);
        unset($_SESSION["META_SCOPE_SYNCED"]);
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
        safe_session_destroy();
    }

    private function SetManager(){
        if($this->Simulate){return;}
        // Anti session-fixation: regenerate the session ID on successful auth,
        // discarding the pre-login SID so a fixed session cannot be reused.
        // safe_session_regenerate_id() and NOT session_regenerate_id(): a Valkey that refuses
        // writes (MISCONF after a failed BGSAVE) throws here, and an uncaught RedisException on
        // this line is a blank HTTP 500 on the login page. See class.sessions.inc.
        safe_session_regenerate_id();
        // New identity, new key for the task links (class.progress.sign.inc): a link
        // displayed before this logon stops working.
        if(function_exists("fw_progress_session_renew")){fw_progress_session_renew();}
        $users=new usersMenus();
        $this->audit_logon("SuperAdmin",true,"SuperAdmin console logon");
        admin_tracks("Success to logon on the Artica Web console from ".$this->GetRemoteIP()." as SuperAdmin");

        //session_start();
        $_SESSION["uid"]='-100';
        // Marqueur du CHEMIN qui a ouvert la session, jamais une chaine
        // choisie par quelqu'un. audit_headers() (class.sockets.inc) s'en sert
        // -- avec uid==='-100' -- pour poser X-Artica-Console-Role: superadmin,
        // le seul droit reconnu par le daemon pour reinitialiser le second
        // facteur d'autrui.
        //
        // Pourquoi pas $_SESSION["uid"] seul: SetUser() y range le NOM
        // d'utilisateur, donc un compte AD/LDAP/local litteralement nomme
        // "manager" -- ou "-100" -- se serait fait passer pour le
        // super-administrateur. Ce drapeau n'est pose que dans cette fonction.
        $_SESSION["ARTICA_SUPERADMIN"]=true;
        // Super-administrateur : perimetre Artica Meta global (infogerant).
        if(function_exists("meta_scope_session_sync")){meta_scope_session_sync(array());}
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
			[AsMetaAdmin]="yes"
			[AsFirewallManager]="yes"
			[AsVPNManager]="yes"
			';
        if(!isset($_SESSION["SESSARTSTART"])) {
            $_SESSION["SESSARTSTART"] = time();
        }

        // La session naît DIMINUÉE. SetUser()/SetManager() sont appelés treize
        // fois depuis CheckCreds(), donc la session est déjà ouverte quand
        // CheckCreds() rend la main: un contrôle ajouté dans fw.login.php
        // arriverait après coup. On retire donc un droit à la session plutôt
        // que d'ajouter un contrôle à un chemin.
        //
        // La pose est INCONDITIONNELLE: c'est la levée qui est conditionnelle.
        // Tout moteur ajouté plus tard hérite ainsi de la protection sans que
        // personne n'y pense -- un oubli fait échouer la connexion, il ne
        // l'ouvre pas.
        $_SESSION["2FA_PENDING"]=true;
        $_SESSION["2FA_IDENTITY"]=$this->IDENTITY_KEY;
        $_SESSION["2FA_DEADLINE"]=time()+300;
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
        // Anti session-fixation: regenerate the session ID on successful auth,
        // discarding the pre-login SID so a fixed session cannot be reused.
        // safe_session_regenerate_id() and NOT session_regenerate_id(): a Valkey that refuses
        // writes (MISCONF after a failed BGSAVE) throws here, and an uncaught RedisException on
        // this line is a blank HTTP 500 on the login page. See class.sessions.inc.
        safe_session_regenerate_id();
        // New identity, new key for the task links (class.progress.sign.inc): a link
        // displayed before this logon stops working.
        if(function_exists("fw_progress_session_renew")){fw_progress_session_renew();}
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
        // META_SCOPE / META_TENANT (Artica Meta, tenants) depuis les privileges qu'on vient de poser.
        if(function_exists("meta_scope_session_sync")){meta_scope_session_sync($privs->privs);}
        BuildSession($this->username);
        $this->audit_logon($this->username,true,"console account logon");
        admin_tracks("Success to logon on the Artica Web console from ".$this->GetRemoteIP()." as $this->username");
        setcookie("MINIADM", "No", time()+1000,"","",true,true);

        // La session naît DIMINUÉE. SetUser()/SetManager() sont appelés treize
        // fois depuis CheckCreds(), donc la session est déjà ouverte quand
        // CheckCreds() rend la main: un contrôle ajouté dans fw.login.php
        // arriverait après coup. On retire donc un droit à la session plutôt
        // que d'ajouter un contrôle à un chemin.
        //
        // La pose est INCONDITIONNELLE: c'est la levée qui est conditionnelle.
        // Tout moteur ajouté plus tard hérite ainsi de la protection sans que
        // personne n'y pense -- un oubli fait échouer la connexion, il ne
        // l'ouvre pas.
        $_SESSION["2FA_PENDING"]=true;
        $_SESSION["2FA_IDENTITY"]=$this->IDENTITY_KEY;
        $_SESSION["2FA_DEADLINE"]=time()+300;
        return true;
    }

    /**
     * Lève le drapeau de second facteur.
     *
     * UNIQUE point de levée: chaque exemption devient un appel repérable par
     * grep, portant son motif dans la piste d'audit -- au lieu d'être une
     * absence silencieuse que personne ne peut auditer.
     *
     * $reason: 'x509' (certificat client, déjà un facteur de possession),
     *          'saml' (MFA déléguée au fournisseur d'identité),
     *          'out-of-scope' (identité hors du périmètre de la politique),
     *          'verified' (code TOTP validé par le daemon).
     */
    public function twofactor_release($reason){
        $allowed=array("x509"=>true,"saml"=>true,"out-of-scope"=>true,"verified"=>true);
        if(!isset($allowed[$reason])){
            // Un motif inconnu ne lève RIEN: refuser est le comportement sûr.
            if(function_exists("writelogs")){
                writelogs("twofactor_release: motif refusé [$reason]",__FUNCTION__,__FILE__,__LINE__);
            }
            return false;
        }
        // L'identité est lue AVANT d'être effacée: elle part dans object=,
        // faute de quoi la piste console dit « un second facteur a été levé »
        // sans dire pour qui, alors que la piste daemon, elle, le sait --
        // deux journaux qui ne se recoupent pas.
        $identity=isset($_SESSION["2FA_IDENTITY"])?strval($_SESSION["2FA_IDENTITY"]):"";
        unset($_SESSION["2FA_PENDING"]);
        unset($_SESSION["2FA_DEADLINE"]);
        if(function_exists("fw_progress_session_renew")){fw_progress_session_renew();}   // second factor passed: new key for the task links
        // 2FA_IDENTITY et 2FA_SCREEN_MODE sont des variables de TRAVAIL de
        // l'écran de défi: les laisser derrière soi ferait survivre l'état
        // d'un défi terminé pendant toute la session, y compris sur le chemin
        // d'échec de twofactor_submit() où 2FA_SCREEN_MODE n'était jamais
        // effacé. Rien d'autre ne les lit une fois le drapeau levé.
        unset($_SESSION["2FA_IDENTITY"]);
        unset($_SESSION["2FA_SCREEN_MODE"]);
        $uid=isset($_SESSION["uid"])?$_SESSION["uid"]:"";
        if($uid=="-100"){$uid="Manager";}
        // Meme forme que securityaudit.Event.Line() cote daemon (ordre des
        // champs, guillemets sur actor/object/detail): les deux pistes
        // doivent se recouper avec UNE seule regle SIEM. $uid vient de
        // $_SESSION, pas d'une liste fermee comme $reason: neutraliser ce
        // qui casserait un enregistrement syslog mono-ligne (CR/LF/guillemet)
        // plutot que de lui faire confiance.
        $actor=str_replace(array("\r","\n","\""),"",strval($uid));
        $AUDIT_PARTS=array();
        $AUDIT_PARTS[]="outcome=success";
        $AUDIT_PARTS[]="kind=2fa.release";
        // Un champ vide est omis, jamais emis en actor="" : le daemon fait de meme.
        if($actor<>""){$AUDIT_PARTS[]="actor=\"$actor\"";}
        // object= porte l'identité, exactement comme securityaudit.Event
        // côté daemon. Même neutralisation que $actor: la valeur vient de
        // $_SESSION, pas d'une liste fermée comme $reason.
        $object=str_replace(array("\r","\n","\""),"",$identity);
        if($object<>""){$AUDIT_PARTS[]="object=\"$object\"";}
        $AUDIT_PARTS[]="detail=\"reason=$reason\"";
        @openlog("artica-audit",LOG_PID,LOG_AUTHPRIV);
        @syslog(LOG_NOTICE,@implode(" ",$AUDIT_PARTS));
        @closelog();
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
            $this->audit_logon($this->username,false,"unknown username");
            return false;
        }
        if($this->password<>$this->ldap_password){
            $this->VERBOSE("Wrong password for Manager account...",__LINE__);
            $this->audit_logon($this->username,false,"invalid password");
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
                $this->IDENTITY_KEY=$this->ad_identity_key("adagent");
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
            $this->IDENTITY_KEY=$this->ad_identity_key("adagent");
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
                    $this->IDENTITY_KEY=$this->ad_identity_key($index);
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
            $this->IDENTITY_KEY=$this->ad_identity_key($index);
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
            $this->IDENTITY_KEY="radius:".intval($ligne["id"]);
            return true;
        }
        if($ligne["value"]==$this->password){
            $this->RADIUS_ID=$ligne["id"];
            $this->IDENTITY_KEY="radius:".intval($ligne["id"]);
            return true;
        }
    }

    /**
     * Ouverture de session a partir d'une assertion SAML deja validee.
     *
     * L'appelant (fw.login.saml.php) a verifie la signature, la destination, la
     * fenetre de validite, l'audience, l'identifiant de requete attendu et le
     * non-rejeu. Il ne reste ici qu'a rapprocher l'identite du referentiel local
     * -- c'est ce rapprochement qui garde la revocation entre les mains de
     * l'exploitant: un compte absent ou desactive est refuse meme si le
     * fournisseur d'identite l'a authentifie.
     *
     * Ne fait AUCUNE verification cryptographique: ne jamais l'appeler ailleurs
     * qu'apres une validation complete de l'assertion.
     */
    public function SamlAssertion($username):bool{
        $username=trim((string)$username);
        if($username==""){
            $this->error="{wrong_password_or_username}";
            return false;
        }
        $this->username=$username;

        // Quand l'annuaire Active Directory pilote les comptes de la console,
        // c'est LUI qui fait autorite sur l'existence du compte et sur les
        // privileges (deduits des groupes), pas la base locale admins.db.
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"))==1){
            $this->VERBOSE("SamlAssertion:: EnableActiveDirectoryFeature=1 --> Active Directory",__LINE__);
            if(!$this->SamlActiveDirectory()){return false;}
            $this->SetUser();
            $this->twofactor_release("saml");
            return true;
        }

        // Mode "saml": le daemon ne consulte pas passmd5, mais exige toujours
        // que le compte existe et soit actif.
        $payload=array("username"=>$username,"saml"=>true);
        $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/localadmins/auth",$payload)),true);
        if(!is_array($resp) || empty($resp["success"])){
            $err=is_array($resp)?strval($resp["error"] ?? "malformed response"):"no response";
            if(function_exists("writelogs")){
                writelogs("POST /localadmins/auth (saml) failed: $err",__FUNCTION__,__FILE__,__LINE__);
            }
            $this->error="{saml_err_backend}";
            return false;
        }
        $ID=intval(is_array($resp["data"] ?? null)?($resp["data"]["id"] ?? 0):0);
        if($ID<1){
            $this->error="{saml_err_no_local_account}";
            return false;
        }
        $this->SQLITE_ID=$ID;
        $this->SetUser();
        $this->twofactor_release("saml");
        return true;
    }

    /**
     * Valide une identite issue d'une assertion SAML contre les connexions
     * Active Directory declarees, sans mot de passe: le fournisseur d'identite
     * a deja prouve l'identite, l'annuaire ne fait que confirmer que le compte
     * existe, qu'il est actif, et fournir le DN d'ou decoulent les privileges.
     *
     * Fail-closed: toute connexion injoignable, tout compte absent ou desactive
     * refuse la session. Le drapeau ADUserCanConnect de chaque connexion est
     * respecte a l'identique du chemin par mot de passe: une connexion dont
     * l'administrateur a interdit l'ouverture de session ne doit pas devenir
     * une porte d'entree simplement parce qu'on arrive par SAML.
     */
    private function SamlActiveDirectory():bool{
        $this->ACTIVE_DIRECTORY_INDEX="Nan";
        $this->ACTIVE_DIRECTORY_DN=null;
        $this->ACTIVE_DIRECTORY_INFO=null;
        $sock=new sockets();

        if(intval($sock->GET_INFO("EnableExternalACLADAgent"))==1){
            if(intval($sock->GET_INFO("AD_Agent_LBL_USER_CAN_CONNECT"))==0){
                $this->VERBOSE("SamlActiveDirectory:: AD_Agent_LBL_USER_CAN_CONNECT=0, refus",__LINE__);
                $this->error="{saml_err_ad_not_allowed}";
                return false;
            }
            $ad=new external_ad_search(null,"adagent");
            $ad->set_verbose();
            $Hash=$ad->SamlMemberFromAgent($this->username);
            foreach($ad->VERBOSED_ARRAY as $line){$this->VERBOSE($line,__LINE__);}
            if(!is_array($Hash)){
                $this->error=$this->SamlADError($ad->SamlLastError);
                return false;
            }
            return $this->SamlADAccept("adagent",$ad,$Hash);
        }

        $connections=@unserialize($sock->GET_INFO("ActiveDirectoryConnections"));
        if(!is_array($connections) || count($connections)==0){
            $this->VERBOSE("SamlActiveDirectory:: aucune connexion Active Directory declaree",__LINE__);
            $this->error="{saml_err_ad_no_connection}";
            return false;
        }

        $Usable=0;
        $LastError="notfound";
        foreach($connections as $index=>$HASH){
            if(!is_array($HASH)){continue;}
            if(!isset($HASH["LDAP_SERVER"])){continue;}
            if(intval(isset($HASH["ADUserCanConnect"])?$HASH["ADUserCanConnect"]:0)==0){
                $this->VERBOSE("SamlActiveDirectory:: [$index] ADUserCanConnect=0 (ignoree)",__LINE__);
                continue;
            }
            $Usable++;
            $this->VERBOSE("SamlActiveDirectory:: [$index] {$HASH["LDAP_SERVER"]} recherche de $this->username",__LINE__);
            $ad=new external_ad_search(null,$index);
            $ad->set_verbose();
            $Hash=$ad->SamlMemberLookup($this->username);
            foreach($ad->VERBOSED_ARRAY as $line){$this->VERBOSE($line,__LINE__);}
            if(!is_array($Hash)){
                if($ad->SamlLastError<>""){$LastError=$ad->SamlLastError;}
                continue;
            }
            return $this->SamlADAccept($index,$ad,$Hash);
        }

        if($Usable==0){
            $this->VERBOSE("SamlActiveDirectory:: aucune connexion n'autorise l'ouverture de session (ADUserCanConnect)",__LINE__);
            $this->error="{saml_err_ad_not_allowed}";
            return false;
        }
        $this->error=$this->SamlADError($LastError);
        return false;
    }

    /**
     * Retient l'annuaire qui a reconnu l'utilisateur. Le DN et l'index sont ce
     * dont SetUser() a besoin pour resoudre les privileges par les groupes,
     * exactement comme apres une authentification par mot de passe.
     */
    private function SamlADAccept($index,$ad,$Hash):bool{
        $this->ACTIVE_DIRECTORY_INDEX=$index;
        $this->ACTIVE_DIRECTORY_DN=isset($Hash["ARTICA_DN"])?$Hash["ARTICA_DN"]:null;
        $this->ACTIVE_DIRECTORY_INFO=$Hash;
        if($this->ACTIVE_DIRECTORY_DN==null){
            $this->VERBOSE("SamlADAccept:: [$index] entree sans DN, refus",__LINE__);
            $this->error="{saml_err_no_ad_account}";
            return false;
        }
        // Le reste de la console identifie l'utilisateur par son sAMAccountName,
        // pas par l'UPN porte par l'assertion.
        if(isset($Hash["sAMAccountName"][0])){
            $this->username=trim(strval($Hash["sAMAccountName"][0]));
        }elseif(strpos($this->username,"@")>0){
            $EXPLODED=explode("@",$this->username);
            $this->username=$EXPLODED[0];
        }
        $this->VERBOSE("SamlADAccept:: [$index] $this->username --> {$this->ACTIVE_DIRECTORY_DN}",__LINE__);
        return true;
    }

    private function SamlADError($reason):string{
        if($reason=="disabled"){return "{saml_err_ad_disabled}";}
        if($reason=="ambiguous"){return "{saml_err_ad_ambiguous}";}
        if($reason=="connect"){return "{saml_err_ad_unreachable}";}
        return "{saml_err_no_ad_account}";
    }

    private function sqlite_admins():bool{
        // Auth check delegated to articarest POST /localadmins/auth
        // (username COLLATE NOCASE + enabled=1, exact passmd5 unless the
        // identity was already proven by a client certificate). Fail-closed:
        // any API/transport error denies the login, same as the legacy
        // behaviour on a SQLite error.
        $x509=isset($this->x509["subject"]["UID"]);
        $payload=array("username"=>strval($this->username),"x509"=>$x509);
        if(!$x509){$payload["passmd5"]=md5($this->password);}
        $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/localadmins/auth",$payload)),true);
        if(!is_array($resp) || empty($resp["success"])){
            $err=is_array($resp)?strval($resp["error"] ?? "malformed response"):"no response";
            if(function_exists("writelogs")){
                writelogs("POST /localadmins/auth failed: $err",__FUNCTION__,__FILE__,__LINE__);
            }
            return false;
        }
        $ID=intval(is_array($resp["data"] ?? null)?($resp["data"]["id"] ?? 0):0);
        if($ID>0){
            $this->SQLITE_ID=$ID;
            $this->IDENTITY_KEY="local:".intval($ID);
            return true;
        }

        return false;

    }

    public function OpenLdap($username=null,$password=null):bool{
        // Les deux arguments sont OPTIONNELS: les deux appelants de CheckCreds() invoquent
        // $this->OpenLdap() sans rien passer, l'identifiant et le mot de passe ayant deja ete
        // deposes sur l'objet. Le garde d'origine testait le PARAMETRE ($password === null) et
        // renvoyait donc false a la premiere ligne a chaque appel reel: aucun compte de
        // l'annuaire interne ne pouvait ouvrir de session, et comme ce moteur ne journalise rien
        // quand il echoue, le fichier de log passait directement d'ActiveDirectoryMain au
        // « FATAL, no Credentials Engines have validated this user ».
        //
        // Le garde porte desormais sur le mot de passe EFFECTIF. L'intention de depart reste
        // entiere -- un mot de passe vide ne doit jamais authentifier -- mais elle est appliquee
        // a la valeur reellement comparee plus bas.
        if($username<>null){$this->username=$username;}
        if($password<>null){$this->password=$password;}
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"))==0){return false;}
        // Le certificat client remplace le mot de passe: ce chemin doit rester ouvert meme sans.
        if(trim((string)$this->password)==="" && !isset($this->x509["subject"]["UID"])){
            $this->VERBOSE("OPENLDAP: $this->username, empty password, aborting",__LINE__);
            return false;
        }

        $u=new user($this->username);
        if(is_null($u->password)){
            $this->VERBOSE("OPENLDAP: $this->username no password! (null)",__LINE__);
            return false;
        }
        $userPassword=trim($u->password);
        if(is_null($u->uidNumber)){
            $this->VERBOSE("OPENLDAP: $this->username not found in the directory",__LINE__);
            return false;
        }
        if(trim($u->uidNumber)==null){
            $this->VERBOSE("OPENLDAP: $this->username == [$u->uidNumber]",__LINE__);
            return false;}
        if(isset($this->x509["subject"]["UID"])) {
            $this->IDENTITY_KEY="ldap:".trim($u->uidNumber);
            return true;
        }
        // Un compte sans mot de passe enregistre ne doit pas etre validable par un mot de passe
        // vide -- comparaison stricte, et refus explicite du cote annuaire vide.
        if($userPassword===""){
            $this->VERBOSE("OPENLDAP: $this->username has no stored password, aborting",__LINE__);
            return false;
        }
        if( trim($this->password)<>$userPassword){
            $this->VERBOSE("OPENLDAP: $this->username, password mismatch",__LINE__);
            return false;}
        $this->VERBOSE("OPENLDAP: $this->username authenticated",__LINE__);
        $this->IDENTITY_KEY="ldap:".trim($u->uidNumber);
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
                    $this->IDENTITY_KEY=$this->ad_identity_key(0);
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
        $this->IDENTITY_KEY=$this->ad_identity_key(0);
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