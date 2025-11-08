<?php

function CheckSessions(){
    $suffixLogoff=null;
    $noback=array();
    $start = microtime(true);
    if($GLOBALS["EXECUTED_AS_ROOT"]){return true;}
    if(isset($GLOBALS["KAV4PROXY_NOSESSION"])){ if(!$GLOBALS["KAV4PROXY_NOSESSION"]){ return true; } }

    $SessionInactivitytime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionInactivitytime"));
    if(!isset($GLOBALS["DEBUG_TEMPLATE"])){$GLOBALS["DEBUG_TEMPLATE"]=false;}
    $page=CurrentPageName();
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
    if($SessionCookieLifetime==0){$SessionInactivitytime=0;}

    if($page=="fw.ping.php"){
        safe_session_start();
        $curtimeout=0;
        if(isset($_SESSION["SESSARTTIMEOUT"])){$curtimeout=$_SESSION["SESSARTTIMEOUT"];}

        _sessions_syslog("[".__LINE__."]:[$page]: SessionCookieLifetime:$SessionCookieLifetime SessionInactivitytime:$SessionInactivitytime timeout:$curtimeout");

        if($SessionInactivitytime>0){
            if($curtimeout>0) {
                $minutes=session_time_min($curtimeout);
                _sessions_syslog("[".__LINE__."]:[$page]: {$minutes}mn max=$SessionInactivitytime");
                if($minutes>=$SessionInactivitytime){
                    echo "<script>document.location.href='{$suffixLogoff}{$logoffpage}';</script>";
                    die();
                }
            }
        }
        return true;
    }

    VERBOSE("CHECKING SESSION for [$page]...",__LINE__);

    if(!isset($_SESSION["SESSARTTIMEOUT"])){$SessionInactivitytime=0;}
    if($SessionInactivitytime==0) {
        if (@session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
    }


    if(!class_exists("usersMenus")){
        include_once(dirname(__FILE__)."/class.users.menus.inc");
    }

    if($SessionInactivitytime==0) {
        $start = microtime(true);
        safe_session_start();
        ExecTBtime($start, "safe_session_start()", __LINE__);
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

    if($NOBACK){return true;}
    $start = microtime(true);
    if($_SESSION["uid"]==null){
        if(isset($_GET["session"])){
            if(!class_exists("user")) {
                include_once(dirname(__FILE__) . '/class.user.inc');
            }
            $users=new user($_GET["uid"]);
            if(md5($users->password)==$_GET["session"]){
                if(function_exists("admin_tracks")) {
                    admin_tracks("Creating new session for uid={$_GET["uid"]}");
                }
                $_SESSION["uid"]=$_GET["uid"];
                if(!isset($_SESSION["SESSARTSTART"])) {
                    $_SESSION["SESSARTSTART"] = time();
                }
                if(!isset($_SESSION["SESSARTTIMEOUT"])) {
                    $_SESSION["SESSARTTIMEOUT"] = time();
                }
                return true;
            }
        }
    }
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
            admin_tracks("Shutdown current session, no start time defined.");
            if(isset($_SERVER["PHP_AUTH_USER"])){unset($_SERVER["PHP_AUTH_USER"]);}
            unset($_SESSION);
            $_SESSION["uid"]=null;
        }else{
            $start = microtime(true);
            $EndTime=$_SESSION["SESSARTSTART"]+($SessionCookieLifetime*60);
            if(time()>$EndTime){
                admin_tracks("Session timeout, automatic logoff after $SessionCookieLifetime minutes");
                unset($_SESSION);
                if(isset($_SERVER["PHP_AUTH_USER"])){unset($_SERVER["PHP_AUTH_USER"]);}
                $_SESSION["uid"]=null;
            }
            ExecTBtime($start,"SESSARTSTART",__LINE__);
        }
    }

    if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::`{$_SESSION["uid"]}`<br>\n";}
    if($_SESSION["uid"]==null){
        if(isset($_SERVER["PHP_AUTH_USER"])){
            if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::PHP_AUTH_USER -> `{$_SERVER["PHP_AUTH_USER"]}`<br>\n";}
            BuildSession($_SERVER["PHP_AUTH_USER"]);
        }
    }

    if($_SESSION["uid"]<>-100){
        if(!isset($_SESSION["privileges_array"])){
            if($_SESSION["uid"]<>null){
                BuildSession($_SESSION["uid"]);
                if(!isset($_SESSION["privileges_array"])){$_SESSION["privileges_array"]=null;}
            }
        }}
    $start = microtime(true);
    $currentdir=basename(dirname($_SERVER['SCRIPT_FILENAME']));
    if($currentdir<>'smartsieve'){
        //session END --------------------------------------------
        if(!isset($_SESSION["uid"])){
            if(!isset($noback[$page])){$noback[$page]=false;}
            if(!$noback[$page]){
                $user=new usersMenus();
                if($user->LIGHTTPD_LDAP_AUTH){BuildSessionAuth();}
            }
        }

        if(!isset($_SESSION["uid"])){

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
                if(isset($GLOBALS["STOP_SESSION_JS"])){echo "document.location.href='$logoffpage'";die("DIE " .__FILE__." Line: ".__LINE__);}
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
                            die("DIE " . __FILE__ . " Line: " . __LINE__);
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
    ExecTBtime($start,"smartsieve block",__LINE__);
}

function safe_session_start():bool{
    static $hasLoggedRedisError = false;
    static $hasLoggedTcpFallback = false;
    if (session_status() == PHP_SESSION_ACTIVE) {
        return true;
    }
    $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
    $cookie_lifetime = $SessionCookieLifetime == 0 ? 0 : $SessionCookieLifetime * 60;

    $sessionOptions = ['cookie_httponly' => 1, 'use_only_cookies' => 1, 'cookie_lifetime' => $cookie_lifetime];

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