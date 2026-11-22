<?php
include_once(dirname(__FILE__)."/class.progress.sign.inc");

/**
 * Service Pack 9 - Réponse de refus pour une page atteinte avec 2FA_PENDING encore posé.
 *
 * Fonction séparée pour être appelée aux DEUX points où CheckSessions() doit
 * fermer une page: avant le retour anticipé sur $NOBACK (sinon la grande
 * liste noback() -- des pages volontairement exemptées de tout contrôle de
 * session, ~90 entrées -- laisse passer une session qui n'a prouvé qu'un mot
 * de passe; fw.icon.top.php en fait partie et construit pourtant un
 * usersMenus() authentifié: barre du haut, /status, /system/status), et dans
 * le corps normal du garde pour les pages qui ne sont pas dans cette liste.
 * Répond dans les quatre formes attendues par les appelants (jQueryLjs,
 * PAGEJS, URL en -js=, HTML en META-refresh) pour ne jamais renvoyer du HTML
 * à un appel qui attend du JavaScript.
 */
function _twofactor_pending_refuse($page,$PAGEJS,$suffixLogoff,$logoffpage,$HTTP_X_ARTICA_SUBFOLDER){
    if(isset($_GET["jQueryLjs"])){
        header("content-type: application/x-javascript");
        echo "document.location.href='$logoffpage'";
        exit;
    }
    if(!isset($PAGEJS[$page])){$PAGEJS[$page]=false;}
    if($PAGEJS[$page]){
        header("content-type: application/x-javascript");
        echo "document.location.href='$logoffpage'";
        exit;
    }
    if(preg_match("#^android\.#", $page)){$suffixLogoff="android.";}
    $QUERYS=array();$zQueries="";
    foreach ($_REQUEST as $a=>$b){ $QUERYS[]="$a=$b"; }
    if(count($QUERYS)>0){$zQueries=@implode("&", $QUERYS);}
    if($zQueries<>"" && preg_match("#-js=#", $zQueries)) {
        header("content-type: application/x-javascript");
        if (preg_match("#^fw\.#", $page)) {
            echo "document.location.href='/{$HTTP_X_ARTICA_SUBFOLDER}fw.login.php?disconnect=yes'";
            exit;
        }
        echo "alert('Session end...');\n";
        echo "document.location.href='$suffixLogoff$logoffpage';";
        exit;
    }
    echo "<html><!-- $page  -->\n<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$logoffpage\"> </head>
    <body>
    <script>document.location.href='$suffixLogoff$logoffpage';</script>
    </body>
    </html>";
    header('location:'.$suffixLogoff.$logoffpage);
    exit;
}

/**
 * Le delai d'inactivite est-il depasse ? UNE seule regle, deux appelants.
 *
 * Tant qu'elle vivait en double (une copie dans la branche fw.ping.php, une
 * autre jamais atteinte dans le chemin principal), l'expiration dependait du
 * SONDAGE du navigateur : un onglet ferme, un client qui n'execute pas le
 * JavaScript, et la session ne vieillissait plus. Une regle partagee ne peut
 * pas diverger d'elle-meme.
 */
function session_idle_expired($SessionInactivitytime):bool{
    if(intval($SessionInactivitytime)<=0){return false;}
    if(!isset($_SESSION["SESSARTTIMEOUT"])){return false;}
    $curtimeout=intval($_SESSION["SESSARTTIMEOUT"]);
    if($curtimeout<=0){return false;}
    return session_time_min($curtimeout)>=intval($SessionInactivitytime);
}

/**
 * Ferme une session inactive : la session est DETRUITE cote serveur avant la
 * redirection.
 *
 * Rediriger sans detruire laisserait la session vivante pour tout appelant qui
 * ne suit pas la redirection -- le navigateur la suit, un script non.
 */
/**
 * admin_tracks() vit dans class.template-admin.inc, que les pages legeres
 * (fw.ping.php, le ping de maintien de session) ne chargent pas. L'appeler nu
 * levait « Call to undefined function admin_tracks() » AVANT
 * safe_session_destroy() : la session expiree survivait (support RWE,
 * 2026-10-23). La trace syslog de _sessions_syslog() reste toujours ecrite.
 */
function _sessions_admin_tracks($text):void{
    if(function_exists("admin_tracks")){admin_tracks($text);}
}

function session_idle_logoff($page,$suffixLogoff,$logoffpage,$minutes){
    _sessions_admin_tracks("Session closed after $minutes minutes of inactivity ($page)");
    _sessions_syslog("[".__LINE__."]:[$page]: idle logoff after $minutes mn");
    @session_unset();
    safe_session_destroy();
    echo "<script>document.location.href='{$suffixLogoff}{$logoffpage}';</script>";
    die();
}

function CheckSessions(){
    $suffixLogoff=null;
    $noback=array();
    $start = microtime(true);
    if($GLOBALS["EXECUTED_AS_ROOT"]){return true;}
    if(isset($GLOBALS["KAV4PROXY_NOSESSION"])){ if(!$GLOBALS["KAV4PROXY_NOSESSION"]){ return true; } }

    $SessionInactivitytime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionInactivitytime"));
    if(!isset($GLOBALS["DEBUG_TEMPLATE"])){$GLOBALS["DEBUG_TEMPLATE"]=false;}
    // SECURITE : la cle de page de TOUTES les decisions de cette fonction (retour wiz.*, liste
    // noback, garde anti-CSRF, garde de privileges, porte Artica Meta) est le nom du script
    // servi, fixe par le serveur. CurrentPageName() le prefixait des en-tetes CLIENT
    // X-Artica-Subfolder / X-Meta-Prefix : "X-Artica-Subfolder: wiz.x" faisait sortir ici avant
    // tout controle de session, "exec.a" / "rest.a" / "prxy.a" rendaient la page noback.
    // CurrentPageName() reste l'outil de construction d'URL, jamais de controle d'acces.
    $page=basename(strval(isset($_SERVER["SCRIPT_FILENAME"])?$_SERVER["SCRIPT_FILENAME"]:""));
    ExecTBtime($start,"CheckSessions",__LINE__);
    $start = microtime(true);
    $getmypid="";
    $PAGEJS["nodes.popup.php"]=true;
    $HTTP_X_ARTICA_SUBFOLDER="";
    $logoffpage="/{$HTTP_X_ARTICA_SUBFOLDER}logoff.php";
    if(preg_match("#^fw\.#", $page)){$logoffpage="/{$HTTP_X_ARTICA_SUBFOLDER}fw.login.php?disconnect=yes";}
    if(preg_match("#^wiz\.#", $page)){
        safe_session_start();
        return true;
    }
    $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
    $NOBACK=_noback($page);
    // Anti-CSRF (Fetch Metadata) for every console page; public pages (noback) have their own.
    if(!$NOBACK){
        include_once(dirname(__FILE__)."/class.csrf.guard.inc");
        csrf_guard_enforce($page);
    }
    // Le delai d'inactivite ne depend PLUS de SessionCookieLifetime.
    //
    // Les deux reglages repondent a deux questions differentes -- « combien de
    // temps une session a le droit de vivre » (SessionCookieLifetime, applique
    // plus bas sur SESSARTSTART) et « combien de temps elle a le droit de
    // rester inutilisee » (SessionInactivitytime). Les couper l'un sur l'autre
    // faisait annuler le second par la valeur PAR DEFAUT du premier : un
    // administrateur qui reglait consciencieusement une deconnexion pour
    // inactivite n'en obtenait aucune, et rien ne le lui disait. Pire, laisser
    // le cookie en cookie de session (0) -- le choix le plus sur, puisqu'il
    // meurt avec le navigateur -- etait precisement ce qui desactivait la
    // protection. Les deux reglages sont offerts cote a cote sur
    // fw.articaweb.php, ce qui rendait le piege invisible.

    if($page=="fw.ping.php"){
        safe_session_start();
        _sessions_syslog("[".__LINE__."]:[$page]: SessionCookieLifetime:$SessionCookieLifetime SessionInactivitytime:$SessionInactivitytime");
        if(session_idle_expired($SessionInactivitytime)){
            session_idle_logoff($page,$suffixLogoff,$logoffpage,$SessionInactivitytime);
        }
        return true;
    }

    VERBOSE("CHECKING SESSION for [$page]...",__LINE__);

    // Releve AVANT d'ouvrir la session : la sortie rapide plus bas ne doit
    // valoir que pour un second appel de CheckSessions() dans la MEME requete,
    // pas pour la session que l'on s'apprete a ouvrir soi-meme.
    $sessionWasActive = (@session_status() === PHP_SESSION_ACTIVE);

    if(!class_exists("usersMenus")){
        include_once(dirname(__FILE__)."/class.users.menus.inc");
    }

    // La session est ouverte ICI, INCONDITIONNELLEMENT, et AVANT toute lecture
    // de $_SESSION.
    //
    // Deux defauts se masquaient l'un l'autre. (1) Le marqueur d'inactivite
    // etait teste sur un $_SESSION pas encore charge : isset() rendait donc
    // toujours faux, et le delai retombait a 0 a CHAQUE requete -- le controle
    // plus bas, garde par « if($SessionInactivitytime>0) », ne s'executait
    // jamais. (2) safe_session_start() n'etait appelee que dans la branche
    // « delai == 0 », si bien qu'un delai reellement configure n'aurait de
    // toute facon jamais ouvert de session du tout.
    //
    // Consequence : l'inactivite n'etait appliquee que sur fw.ping.php, donc
    // seulement tant qu'un navigateur sondait. Ouvrir la session d'abord fait
    // disparaitre les deux.
    $start = microtime(true);
    safe_session_start();
    ExecTBtime($start, "safe_session_start()", __LINE__);
    // Key signing the task links of fw.progress.php (class.progress.sign.inc): derived
    // from the session id, nothing is written; also creates the appliance secret on the
    // very first request.
    fw_progress_session_key(true);

    if($SessionInactivitytime==0) {
        if ($sessionWasActive) {
            return true;
        }
    }

    // La comparaison a lieu AVANT le rafraichissement du marqueur (plus bas,
    // a chaque page) : apres, elle mesurerait invariablement zero minute.
    if(session_idle_expired($SessionInactivitytime)){
        session_idle_logoff($page,$suffixLogoff,$logoffpage,$SessionInactivitytime);
    }

    $sesslen=strlen($GLOBALS["SESSION_ID"]);
    if($sesslen<2){
        $GLOBALS["SESSION_ID"]=session_id();
    }
    if(!isset($_SESSION["uid"])){$_SESSION["uid"]=null;}

    if($GLOBALS["DEBUG_TEMPLATE"]){
        $called="";
        if(function_exists("getmypid")){$getmypid="PID:".getmypid()." ";}
        $session_detected_lang=null;
        if(isset($_SESSION["detected_lang"])){$session_detected_lang=$_SESSION["detected_lang"];}
        $trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
        VERBOSE("[{$_SESSION["uid"]}]::$page:{$getmypid}CheckSessions() language=\"{$session_detected_lang}\" $called function:".__FUNCTION__." in " . basename(__FILE__),__LINE__);
    }

    $NOTIMEOUT["fw.system.status.php"]=true;

    if($_SESSION["uid"]<>null) {
        if(!isset($NOTIMEOUT[$page])) {
            $_SESSION["SESSARTTIMEOUT"] = time();
            _sessions_syslog("[" . __LINE__ . "]:[$page]: SESSARTTIMEOUT:{$_SESSION["SESSARTTIMEOUT"]}");
        }
    }

    // 2FA_PENDING doit fermer aussi les pages de la grande liste noback():
    // sans cette ligne, $NOBACK==true ci-dessous fait sortir de la fonction
    // avant que le garde plus bas ne soit jamais consulté. Seules 4 pages
    // restent exemptées -- celles que le flux d'authentification lui-même
    // doit pouvoir atteindre AVANT tout second facteur: fw.login.php (le
    // formulaire ET l'écran de défi, qui sont la même page), fw.login.saml.php
    // (point de réception d'une assertion SAML, sans uid tant qu'elle n'est
    // pas traitée -- déjà exemptée pour cette raison, voir noback() dans
    // class.templates.inc), fw.captcha.php (peut précéder la saisie du mot
    // de passe) et angular.css.php (feuille de style de ces pages: sans elle,
    // l'écran de défi s'affiche sans mise en forme).
    if(!empty($_SESSION["2FA_PENDING"])){
        $TWOFACTOR_EXEMPT=array("fw.login.php"=>true,"fw.login.saml.php"=>true,"fw.captcha.php"=>true,"angular.css.php"=>true);
        if(!isset($TWOFACTOR_EXEMPT[$page])){
            _twofactor_pending_refuse($page,$PAGEJS,$suffixLogoff,$logoffpage,$HTTP_X_ARTICA_SUBFOLDER);
        }
    }

    if($NOBACK){return true;}
    $start = microtime(true);
    // SÉCURITÉ (audit du 2026-11-03, C-01) : il existait ici une ouverture de
    // session par ?uid=<compte>&session=<md5 du mot de passe stocké>. Pour le
    // super-administrateur -100, l'objet user ne porte aucun mot de passe : la
    // comparaison se faisait contre md5("") — une constante publique — et une
    // seule requête GET, sans identifiant, ouvrait une session administrateur.
    // Son seul producteur, les rapports de quarantaine, visait une page
    // supprimée (user.quarantine.query.php). Le chemin est retiré : la session
    // ne naît que de fw.login.php après CheckCreds() (voir plus bas, même
    // principe que PHP_AUTH_USER). Ne pas le rétablir, même « signé ».
    if($SessionInactivitytime>0){
        if(!isset($_SESSION["SESSARTTIMEOUT"])) {
            if($page<>"fw.ping.php") {
                $_SESSION["SESSARTTIMEOUT"] = time();
            }
        }
    }
    ExecTBtime($start,__FUNCTION__,__LINE__);

    if($SessionCookieLifetime>0){
        if(!isset($_SESSION["SESSARTSTART"])){
            error_log("Session time doesn't exists, remove any session information L.".__LINE__);
            _sessions_admin_tracks("Shutdown current session, no start time defined.");
            if(isset($_SERVER["PHP_AUTH_USER"])){unset($_SERVER["PHP_AUTH_USER"]);}
            unset($_SESSION);
            $_SESSION["uid"]=null;
        }else{
            $start = microtime(true);
            $EndTime=$_SESSION["SESSARTSTART"]+($SessionCookieLifetime*60);
            if(time()>$EndTime){
                _sessions_admin_tracks("Session timeout, automatic logoff after $SessionCookieLifetime minutes");
                unset($_SESSION);
                if(isset($_SERVER["PHP_AUTH_USER"])){unset($_SERVER["PHP_AUTH_USER"]);}
                $_SESSION["uid"]=null;
            }
            ExecTBtime($start,"SESSARTSTART",__LINE__);
        }
    }

    if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::`{$_SESSION["uid"]}`<br>\n";}
    // SÉCURITÉ : ne JAMAIS construire une session à partir du seul PHP_AUTH_USER.
    // BuildSession() ne reçoit pas de mot de passe et ne le vérifie pas ; faire
    // confiance à l'en-tête HTTP Basic revenait donc à authentifier n'importe qui
    // sous n'importe quel nom (`curl -u Manager:nimporte` → session administrateur),
    // le port 9000 étant servi directement, sans auth_basic en amont pour valider.
    // La session ne naît désormais QUE de fw.login.php, après CheckCreds() :
    // SetManager()/SetUser() y posent $_SESSION["uid"] (SetUser appelle BuildSession
    // sur le compte VALIDÉ). Le rebuild ci-dessous reste sûr : il est clé sur l'uid
    // DÉJÀ présent en session, donc déjà authentifié.
    // Le déport d'authentification par proxy (mod_auth en amont) a son propre chemin,
    // BuildSessionAuth() sous LIGHTTPD_LDAP_AUTH, plus bas — il n'est pas concerné.

    // Artica Meta (tenants) : proprietaires modifies depuis la construction des privileges de
    // cette session (meta_tenants_flush_privs_cache()) => les privileges sont reconstruits
    // ci-dessous, et BuildSession() recalcule META_SCOPE / META_TENANT avant la page.
    if(function_exists("meta_tenants_session_stale") && meta_tenants_session_stale()){
        unset($_SESSION["privileges_array"]);
    }
    if($_SESSION["uid"]<>-100){
        if(!isset($_SESSION["privileges_array"])){
            if($_SESSION["uid"]<>null){
                BuildSession($_SESSION["uid"]);
                if(!isset($_SESSION["privileges_array"])){$_SESSION["privileges_array"]=null;}
            }
        }}
    // Artica Meta (ruling R-F4) : session ouverte avant la mise a jour (ou Manager) sans
    // META_SCOPE, ou session de tenant sans META_GEN => perimetre pose ici, sinon le daemon
    // refuse (403) les appels navigateur directs (/netagents/*, terminal, console).
    if(function_exists("meta_scope_session_needs_sync") && meta_scope_session_needs_sync()){
        if(!function_exists("meta_scope_session_sync")){include_once(dirname(__FILE__)."/class.privileges.inc");}
        if(function_exists("meta_scope_session_sync")){meta_scope_session_sync();}
        $_SESSION["META_SCOPE_SYNCED"]=1;
    }
    $start = microtime(true);
    $currentdir=basename(dirname($_SERVER['SCRIPT_FILENAME']));
    if($currentdir<>'smartsieve'){
        //session END --------------------------------------------
        // Initialisé ICI, avant les deux usages qui suivent: une session
        // 2FA_PENDING a un uid, donc le bloc if(!isset($_SESSION["uid"]))
        // ci-dessous ne s'exécute pas pour elle, et $noback[$page] serait lu
        // à la ligne du garde sans jamais avoir été écrit -- un notice PHP
        // par requête refusée, émis AVANT les header("content-type: ...")
        // de la réponse JS ci-dessous ("headers already sent").
        if(!isset($noback[$page])){$noback[$page]=false;}
        if(!isset($_SESSION["uid"])){
            if(!$noback[$page]){
                $user=new usersMenus();
                if($user->LIGHTTPD_LDAP_AUTH){BuildSessionAuth();}
            }
        }

        // Une session peut avoir un uid VALIDE et rester refusée: 2FA_PENDING
        // est posé sans condition par SetUser()/SetManager() (appelés depuis
        // CheckCreds(), donc AVANT que fw.login.php ne reprenne la main) dès
        // qu'un mot de passe est vérifié, et n'est levé que par
        // twofactor_release() une fois un second facteur prouvé (x509, SAML,
        // hors périmètre de la politique, ou code TOTP validé). Un contrôle
        // ajouté plus tard dans fw.login.php arriverait donc après coup, une
        // fois la session déjà utilisable: la seule place sûre est ici, dans
        // le garde que traverse toute page.
        //
        // Couverture réelle (deux points de contrôle, pas un seul): cette
        // ligne ferme toute page qui atteint ce point de la fonction, c'est
        // à dire toute page qui N'EST PAS dans la grande liste noback() de
        // class.templates.inc (les pages volontairement publiques/utilitaires
        // -- ~90 entrées). Les pages DE cette liste sont fermées plus haut
        // dans cette même fonction, juste avant `if($NOBACK){return true;}`,
        // par un appel dédié à _twofactor_pending_refuse() -- sauf les 4
        // exemptées là-bas (fw.login.php, fw.login.saml.php, fw.captcha.php,
        // angular.css.php). Ensemble, les deux points ferment toute page sauf
        // ces 4 noms. Ce garde répond déjà dans les quatre formes attendues
        // (jQueryLjs, PAGEJS, URL en -js=, HTML en META-refresh), ce qui
        // évite de renvoyer du HTML à un appel qui attend du JavaScript.
        if(!isset($_SESSION["uid"]) || !empty($_SESSION["2FA_PENDING"])){

            if(!$noback[$page]){
                if(isset($_GET["jQueryLjs"])){
                    header("content-type: application/x-javascript");
                    echo "document.location.href='$logoffpage'";
                    exit;
                }

                if(!isset($PAGEJS[$page])){$PAGEJS[$page]=false;}

                if($PAGEJS[$page]){
                    header("content-type: application/x-javascript");
                    echo "document.location.href='$logoffpage'";
                    exit;
                }
                if(isset($GLOBALS["STOP_SESSION_JS"])){echo "document.location.href='$logoffpage'";die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");}
                if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::".dirname(__FILE__)."/logs.inc\n";}
                include_once(dirname(__FILE__).'/logs.inc');
                error_log("[{$_SESSION["uid"]}]::$page is not allowed, no session, `{$_SESSION["uid"]}` die.. in ".__FILE__." line ". __LINE__);
                if(isset($_GET["in-front-ajax"])){
                    if(preg_match("#^fw\.#", $page)){echo "document.location.href='/{$HTTP_X_ARTICA_SUBFOLDER}fw.login.php?disconnect=yes'";exit;}
                    echo "document.location.href='$logoffpage'";exit;}

                if(preg_match("#^android\.#", $page)){$suffixLogoff="android.";}
                $QUERYS=array();$zQueries="";
                foreach ($_REQUEST as $a=>$b){ $QUERYS[]="$a=$b"; }
                if(is_array($QUERYS)){
                    if(count($QUERYS)>0){
                        $zQueries=@implode("&", $QUERYS);
                    }
                }
                if(!is_null($zQueries)) {
                    if (preg_match("#-js=#", $zQueries)) {
                        header("content-type: application/x-javascript");
                        if (preg_match("#^fw\.#", $page)) {
                            echo "document.location.href='/{$HTTP_X_ARTICA_SUBFOLDER}fw.login.php?disconnect=yes'";
                            die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");
                        }
                        echo "alert('Session end...');\n";
                        echo "document.location.href='$suffixLogoff$logoffpage';";
                        exit;
                    }
                }



                echo "<html><!-- $page  -->\n<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$logoffpage\"> </head>
						<body>
						<script>document.location.href='$suffixLogoff$logoffpage';</script>
						</body>
						</html>";
                echo header('location:'.$suffixLogoff.$logoffpage);exit;

            }


        }}
    // Garde de privilège côté serveur (requêtes POST) : la session est validée à ce
    // point -- sans uid ou en 2FA_PENDING, le bloc ci-dessus a déjà renvoyé vers la
    // connexion. Mode observation par défaut (WebConsolePrivsGuard), voir l'en-tête de
    // class.privs.guard.inc.
    if(!$NOBACK && isset($_SESSION["uid"]) && empty($_SESSION["2FA_PENDING"])){
        include_once(dirname(__FILE__)."/class.privs.guard.inc");
        privs_guard_enforce($page);
    }
    // Artica Meta multi-tenant (spec 2026-11-13 5.10.6) : un compte qui n'a que AsMetaAdmin est
    // le compte d'un CLIENT de l'infogerant. Il n'atteint que les pages Meta et ses pages de
    // compte (usersMenus::MetaOnlyPageAllowed()), en GET comme en POST, quel que soit le mode du
    // garde de privileges ci-dessus : la plupart des pages n'ont aucun controle propre.
    if(!$NOBACK && isset($_SESSION["uid"]) && empty($_SESSION["2FA_PENDING"])){
        if(!function_exists("meta_only_page_refused")){include_once(dirname(__FILE__)."/class.users.menus.inc");}
        if(function_exists("meta_only_page_refused") && meta_only_page_refused($page)){
            $who=preg_replace('/[^A-Za-z0-9_.@\-]/','',strval($_SESSION["uid"]));
            error_log("Artica Meta: page ".basename(strval($page))." refused to tenant account $who");
            http_response_code(403);
            header("content-type: text/plain");
            echo "Forbidden";
            exit;
        }
        // Sens inverse : page Artica Meta demandee par un compte sans aucun droit Meta.
        if(function_exists("meta_page_refused") && meta_page_refused($page)){
            $who=preg_replace('/[^A-Za-z0-9_.@\-]/','',strval($_SESSION["uid"]));
            error_log("Artica Meta: page ".basename(strval($page))." refused to account $who (no Artica Meta privilege)");
            http_response_code(403);
            header("content-type: text/plain");
            echo "Forbidden";
            exit;
        }
    }
    ExecTBtime($start,"smartsieve block",__LINE__);
}
// -------------------------------------------------------------------------------------------
// Session store degradation
//
// The unix -> TCP -> files ladder below only ever tests that the store ACCEPTS A CONNECTION.
// Valkey has a state where it connects and answers reads perfectly but refuses every write:
// after a failed BGSAVE it sets rdb_last_bgsave_status=err and, with the default
// `stop-writes-on-bgsave-error yes`, answers MISCONF to any command that modifies the data set
// (disk full, unwritable `dir`, no memory to fork). session_start() then SUCCEEDS -- it only
// reads -- and the ladder never fires; the first WRITE, session_regenerate_id(true) at login,
// throws RedisException and the login page dies with a blank HTTP 500. Restarting the server
// clears the flag, which is why the outage looks intermittent.
//
// So the verdict cannot be taken at connect time: it is taken the first time a write is
// actually refused, then remembered for 5 minutes so the FOLLOWING requests do not pick the
// store again and lose the session they just created. The marker expires on its own, so the
// console returns to the shared store as soon as the operator has freed the disk.
// -------------------------------------------------------------------------------------------

function session_files_path():string{ return "/var/lib/php5"; }
function session_fallback_marker():string{ return session_files_path()."/.artica-session-degraded"; }

function session_store_degraded():bool{
    $marker=session_fallback_marker();
    if(!@file_exists($marker)){return false;}
    // Self-healing: an expired marker is removed so the next request retries the real store.
    if((time()-intval(@filemtime($marker)))>300){@unlink($marker);return false;}
    return true;
}

function session_store_degrade($why){
    $marker=session_fallback_marker();
    @file_put_contents($marker,date("Y-m-d H:i:s")." ".$why."\n");
    @chmod($marker,0666);
    error_log("Artica sessions: store refused a write, falling back to file sessions for 5mn ($why)");
}

// cookie_samesite=Lax est POSE EXPLICITEMENT, il n'est pas redondant avec le defaut.
// Sans attribut, ce sont les navigateurs qui tranchent, et ils ne tranchent pas pareil:
// Chrome et Edge appliquent Lax-by-default -- avec une grace de 2 minutes apres la creation
// du cookie pendant laquelle un POST cross-site l'emporte quand meme -- tandis que Firefox
// n'active toujours pas laxByDefault, donc y envoie le cookie sur TOUT POST cross-site. La
// protection contre le CSRF dependait ainsi du navigateur de l'exploitant, et c'est la
// seule qui existe: la console n'a pas de jeton anti-CSRF et session.referer_check est vide.
// Lax (pas Strict) parce qu'un lien externe vers la console doit continuer d'arriver
// authentifie; Lax (pas None) parce que plus aucun flux n'a besoin du cookie en cross-site
// -- l'ACS SAML, qui etait le seul, correle desormais par le RelayState (voir
// saml_sp::request_track_put), et le SLO arrive en GET.
// L'option demande PHP 7.3, exactement le plancher du parc.
function session_options():array{
    $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
    $cookie_lifetime = $SessionCookieLifetime == 0 ? 0 : $SessionCookieLifetime * 60;
    // Les attributs sont affirmes ICI, pas laisses au php.ini de chaque
    // appliance : une protection qui depend de la configuration locale n'est
    // pas une protection, c'est une esperance.
    //
    // cookie_secure : la console n'est servie QU'EN HTTPS (la conf nginx
    // generee ne contient qu'un « listen 0.0.0.0:9000 ssl »), le drapeau ne
    // peut donc pas priver un utilisateur legitime de son cookie. Il etait
    // deja effectif sur les parcs dont le php.ini le pose -- ce n'est un
    // changement de comportement que la ou il manquait, c'est-a-dire
    // exactement la ou il fallait.
    //
    // use_strict_mode : sans lui, PHP ADOPTE un identifiant de session qu'il
    // n'a jamais emis. La regeneration faite a l'authentification
    // (SetManager/SetUser) referme deja la fenetre pour la session qui compte ;
    // ceci ferme la periode qui precede, ou une session peut etre imposee au
    // navigateur avant meme la saisie du mot de passe.
    return array(
        'cookie_httponly' => 1,
        'use_only_cookies'=> 1,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => 1,
        'use_strict_mode' => 1,
        'cookie_lifetime' => $cookie_lifetime);
}

function session_start_files($sessionOptions):bool{
    ini_set("session.save_handler","files");
    ini_set("session.save_path",session_files_path());
    return (bool)@session_start($sessionOptions);
}

// Called when a write was refused MID-SESSION: the data read from the shared store is still in
// $_SESSION, so it is carried over to a brand-new file-backed session. The new ID also delivers
// the anti session-fixation rotation that the failed session_regenerate_id() owed us.
function session_migrate_to_files():bool{
    $data=isset($_SESSION)?$_SESSION:array();
    @session_abort();                       // drop the doomed write, keep the captured data
    ini_set("session.save_handler","files");
    ini_set("session.save_path",session_files_path());
    @session_id(session_create_id());
    if(!@session_start(session_options())){return false;}
    foreach($data as $k=>$v){$_SESSION[$k]=$v;}
    return true;
}
// session_regenerate_id() that cannot take the console down. On a store that refuses writes it
// degrades to file sessions instead of throwing: the operator can still log in and go fix the
// disk, which is the whole point.
function safe_session_regenerate_id():bool{
    if(session_status()!==PHP_SESSION_ACTIVE){return false;}
    try{
        session_regenerate_id(true);
        return true;
    }catch(\Throwable $e){
        session_store_degrade("session_regenerate_id: ".$e->getMessage());
        return session_migrate_to_files();
    }
}

function safe_session_destroy():bool{
    if(session_status()!==PHP_SESSION_ACTIVE){return false;}
    try{
        return (bool)session_destroy();
    }catch(\Throwable $e){
        // Logging out must never 500: the cookie is already cleared by the caller.
        session_store_degrade("session_destroy: ".$e->getMessage());
        @session_abort();
        return false;
    }
}

function safe_session_start():bool{
    static $hasLoggedRedisError = false;
    static $hasLoggedTcpFallback = false;
    static $hasLoggedMemcacheTcpFallback = false;

    if (session_status() == PHP_SESSION_ACTIVE) {
        return true;
    }
    $sessionOptions = session_options();

    // A store that already refused a write is skipped outright: it would connect, answer the
    // read, and then drop the session on the next write.
    if (session_store_degraded()) {
        return session_start_files($sessionOptions);
    }

    // Check if using Memcached protocol
    if (isset($GLOBALS["UseMemCacheClient"]) && $GLOBALS["UseMemCacheClient"] == 1) {
        // Try Memcached Unix socket first
        try {
            // Disable session locking BEFORE setting handler (order matters!)
            ini_set("memcached.sess_locking", "0");
            ini_set("memcached.sess_lock_wait_min", "1000");
            ini_set("memcached.sess_lock_wait_max", "2000");
            ini_set("memcached.sess_lock_retries", "5");

            ini_set("session.save_handler", "memcached");
            ini_set("session.gc_probability", 1);
            ini_set("session.gc_divisor", 1000); // avoid frequent GC
            ini_set("session.gc_maxlifetime", 1440);
            ini_set("session.save_path", "/run/artmem.sock");

            if (!session_start($sessionOptions)) {
                throw new Exception("Memcached Unix socket session start failed");
            }
            return true;
        } catch (Throwable $e) {
            // Unix socket failed, try TCP fallback
            if (!$hasLoggedMemcacheTcpFallback) {
                $line=__LINE__;
                error_log("($line): Memcached Unix socket failed, attempting TCP fallback: " . $e->getMessage());
                $hasLoggedMemcacheTcpFallback = true;
            }

            try {
                // Disable session locking BEFORE setting handler (order matters!)
                ini_set("memcached.sess_locking", "0");
                ini_set("memcached.sess_lock_wait_min", "1000");
                ini_set("memcached.sess_lock_wait_max", "2000");
                ini_set("memcached.sess_lock_retries", "5");

                ini_set("session.save_handler", "memcached");
                ini_set("session.gc_probability", 1);
                ini_set("session.gc_divisor", 1000);
                ini_set("session.gc_maxlifetime", 1440);
                ini_set("session.save_path", "127.0.0.1:11155");

                if (!session_start($sessionOptions)) {
                    throw new Exception("Memcached TCP session start failed");
                }
                error_log("Successfully connected to Memcached via TCP: 127.0.0.1:11155");
                return true;
            } catch (Throwable $e2) {
                // Both Memcached methods failed, fallback to file sessions
                ini_set("session.save_handler", "files");
                ini_set("session.save_path", "/var/lib/php5");
                session_start($sessionOptions);
                if (!$hasLoggedRedisError) {
                    error_log("Both Memcached connections failed, fallback to file sessions. Unix: " . $e->getMessage() . ", TCP: " . $e2->getMessage());
                    $hasLoggedRedisError = true;
                }
            }
        }
        return true;
    }

    // Use Redis protocol (default)
    // Try Unix socket first
    try {
        ini_set("session.save_handler", "redis");
        ini_set("session.gc_probability", 1);
        ini_set("session.gc_divisor", 1000); // avoid frequent GC
        ini_set("session.gc_maxlifetime", 1440);
        ini_set("session.save_path", "unix:///run/redis/redis.sock?persistent=1&database=0&timeout=2.5");
        if (!session_start($sessionOptions)) {
            throw new Exception("Redis Unix socket session start failed");
        }
        return true;
    } catch (Throwable $e) {
        // Unix socket failed, try TCP fallback
        if (!$hasLoggedTcpFallback) {
            error_log("Redis Unix socket failed, attempting TCP fallback: " . $e->getMessage());
            $hasLoggedTcpFallback = true;
        }

        try {
            ini_set("session.save_handler", "redis");
            ini_set("session.gc_probability", 1);
            ini_set("session.gc_divisor", 1000);
            ini_set("session.gc_maxlifetime", 1440);
            ini_set("session.save_path", "tcp://127.0.0.1:6379?persistent=1&database=0&timeout=2.5");
            if (!session_start($sessionOptions)) {
                throw new Exception("Redis TCP session start failed");
            }
            error_log("Successfully connected to Redis via TCP: 127.0.0.1:6379");
            return true;
        } catch (Throwable $e2) {
            // Both Redis methods failed, fallback to file sessions
            ini_set("session.save_handler", "files");
            ini_set("session.save_path", "/var/lib/php5");
            session_start($sessionOptions);
            if (!$hasLoggedRedisError) {
                error_log("Both Redis connections failed, fallback to file sessions. Unix: " . $e->getMessage() . ", TCP: " . $e2->getMessage());
                $hasLoggedRedisError = true;
            }
        }
    }
    return true;

}