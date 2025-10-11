<?php

$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["BY"]=null;
$GLOBALS["BYINITD"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}

if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.templates-simple.inc');
if(preg_match("#--smooth#",implode(" ",$argv))){$GLOBALS["SMOOTH"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--withoutloading#",implode(" ",$argv))){$GLOBALS["NO_USE_BIN"]=true;$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--nocaches#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;}
if(preg_match("#--noapply#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;$GLOBALS["NOAPPLY"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--restart#",implode(" ",$argv))){$GLOBALS["RESTART"]=true;}
if(preg_match("#--byschedule#",implode(" ",$argv))){$GLOBALS["BY_SCHEDULE"]=true;}
if(preg_match("#--noverifcaches#",implode(" ",$argv))){$GLOBALS["NO_VERIF_CACHES"]=true;}
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
if(preg_match("#--initd#",implode(" ",$argv))){$GLOBALS["BYINITD"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--FUNC-(.+?)-L-([0-9]+)#", implode(" ",$argv),$re)){$GLOBALS["BY"]=" By {$re[1]} Line {$re[2]}";}
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(isset($argv[1])){
    if($argv[1]=="--dump"){DUMP_TEMPLATES();exit;}
    if($argv[1]=="--single"){TEMPLATE_SINGLE($argv[2]);exit;}
    if($argv[1]=="--export"){TEMPLATE_EXPORT($argv[2]);exit;}
    if($argv[1]=="--nginx"){TEMPLATE_NGINX();exit;}
    if($argv[1]=="--perc"){patching_percents();exit;}

}


echo "Building all templates....Progress={$GLOBALS["PROGRESS"]}\n";
sexec();

function build_progress($text,$pourc){
    if(!$GLOBALS["PROGRESS"]){return;}
    $filename=basename(__FILE__);
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/squid.templates.single.progress";
    echo "[{$pourc}%] $filename: $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);
    if($GLOBALS["OUTPUT"]){usleep(5000);}


}

function TEMPLATE_SINGLE($ERR_TPL){
    $langpth="/usr/share/squid-langpack";
    $langpack="$langpth/templates";

    if($ERR_TPL=="ERR_CACHE_ACCESS_DENIED" or $ERR_TPL=="ERR_ACCESS_DENIED"){
        $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
        if($EnableSquidMicroHotSpot==1){
            return false;
        }
    }

    $SquidHTTPTemplateLanguage=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage");
    if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}

    if(!is_dir("$langpth/$SquidHTTPTemplateLanguage")) {
        @mkdir("$langpth/$SquidHTTPTemplateLanguage", 0755, true);
    }
    @chown("$langpth/$SquidHTTPTemplateLanguage","squid");
    @chgrp("$langpth/$SquidHTTPTemplateLanguage", "squid");


    $templateDestination="$langpack/$ERR_TPL";
    $templateLangDestination="$langpack/$SquidHTTPTemplateLanguage/$ERR_TPL";
    $xtpl=new template_simple($ERR_TPL,$SquidHTTPTemplateLanguage);
    $design=$xtpl->TemplatesDesign();
    echo "Saving $templateDestination " . strlen($design)."Bytes\n";
    echo "Saving $templateLangDestination " . strlen($design)."Bytes\n";

    @file_put_contents($templateDestination, $design);
    @file_put_contents($templateLangDestination, $design);

    @chown($templateLangDestination,"squid");
    @chgrp($templateLangDestination, "squid");
    @chown($templateDestination,"squid");
    @chgrp($templateDestination, "squid");
    patching_percents();
    return true;
}

function DUMP_TEMPLATES(){
    $sock=new sockets();
    print_r(unserialize($sock->GET_INFO("TemplateConfig")));
    $GLOBALS["XTPL_SQUID_DEFAULT"]=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));

}

function TEMPLATE_CacheManager_default(){
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));

    if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
    if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}
    $LicenseInfos["EMAIL"]=str_replace("'", "", $LicenseInfos["EMAIL"]);
    $LicenseInfos["EMAIL"]=str_replace('"', "", $LicenseInfos["EMAIL"]);
    $LicenseInfos["EMAIL"]=str_replace(' ', "", $LicenseInfos["EMAIL"]);
    return $LicenseInfos["EMAIL"];
}

function TEMPLATE_CacheManager(){
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){return TEMPLATE_CacheManager_default();}
    $sock=new sockets();
    $cache_mgr_user=$sock->GET_INFO("cache_mgr_user");
    if($cache_mgr_user==null){return TEMPLATE_CacheManager_default();}
    return $cache_mgr_user;
    patching_percents();


}

function TEMPLATE_NGINX(){
    $unix=new unix();
    $templateDestination="/usr/share/squid-langpack/templates/Err500HyperCache.html";
    $SquidHTTPTemplateLanguage=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage");
    if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}
    $email=TEMPLATE_CacheManager();
    $xtpl=new template_simple("ERR_READ_TIMEOUT",$SquidHTTPTemplateLanguage);
    $design=$xtpl->TemplatesDesign();
    $tpl=new templates();
    $design=str_replace('%U',$email,$design);
    $design=str_replace('%w',$email,$design);
    $design=str_replace('%T',$tpl->time_to_date(time()),$design);
    $design=str_replace('%h',$unix->hostname_g(),$design);
    $design=str_replace('%s',"HyperCache ".@file_get_contents("/usr/share/artica-postfix/VERSION"),$design);
    @file_put_contents($templateDestination, utf8_decode($design));


    $templateDestination="/usr/share/squid-langpack/templates/Err400HyperCache.html";
    $SquidHTTPTemplateLanguage=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage");
    if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}
    $xtpl=new template_simple("ERR_GATEWAY_FAILURE",$SquidHTTPTemplateLanguage);
    $design=$xtpl->TemplatesDesign();
    $tpl=new templates();
    $design=str_replace('%U',$email,$design);
    $design=str_replace('%w',$email,$design);
    $design=str_replace('%T',$tpl->time_to_date(time()),$design);
    $design=str_replace('%h',$unix->hostname_g(),$design);
    $design=str_replace('%s',"HyperCache ".@file_get_contents("/usr/share/artica-postfix/VERSION"),$design);
    @file_put_contents($templateDestination, utf8_decode($design));

    $templateDestination="/usr/share/squid-langpack/templates/Err404BDHyperCache.html";
    $SquidHTTPTemplateLanguage=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage");
    if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}
    $xtpl=new template_simple("ERR_INVALID_REQ",$SquidHTTPTemplateLanguage);
    $design=$xtpl->TemplatesDesign();
    $tpl=new templates();
    $design=str_replace('%U',$email,$design);
    $design=str_replace('%w',$email,$design);
    $design=str_replace('%T',$tpl->time_to_date(time()),$design);
    $design=str_replace('%h',$unix->hostname_g(),$design);
    $design=str_replace('%s',"HyperCache ".@file_get_contents("/usr/share/artica-postfix/VERSION"),$design);
    $design=str_replace('%E',"404 Not found",$design);
    @file_put_contents($templateDestination, utf8_decode($design));
    patching_percents();


}



function sexec(){
    $EXEC_PID_FILE="/etc/artica-postfix/".basename(__FILE__).".sexec.pid";
    $addon=null;
    $unix=new unix();

    $pid=@file_get_contents($EXEC_PID_FILE);
    if($unix->process_exists($pid,basename(__FILE__))){
        echo "Already running PID $pid\n";
        build_progress("Already running",110);
        return false;
    }



    $SquidHTTPTemplateLanguage=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage");
    if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}

    build_progress("{building} MIME.CONF",30);
    echo "Building mime\n";
    system("/usr/sbin/artica-phpfpm-service -proxy-mimeconf");
    TEMPLATE_NGINX();

    $GLOBALS["XTPL_SQUID_DEFAULT"]=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
    $xtpl=new template_simple();
    $MAIN=$GLOBALS["XTPL_SQUID_DEFAULT"][$SquidHTTPTemplateLanguage];

    $SquidHTTPTemplateDir=$SquidHTTPTemplateLanguage;


    if(!is_dir("/usr/share/squid-langpack/$SquidHTTPTemplateDir")) {
        @mkdir("/usr/share/squid-langpack/$SquidHTTPTemplateDir", 0755, true);
    }
    @chown("/usr/share/squid-langpack/$SquidHTTPTemplateDir","squid");
    @chgrp("/usr/share/squid-langpack/$SquidHTTPTemplateDir", "squid");


    foreach ($MAIN as $TEMPLATE_TITLE=>$subarray){

        if($TEMPLATE_TITLE=="ERR_CACHE_ACCESS_DENIED"){
            echo "$TEMPLATE_TITLE SKIP --> HotSpot\n";
            $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
            if($EnableSquidMicroHotSpot==1){continue;}
        }
        build_progress("{building} $TEMPLATE_TITLE",50);
        TEMPLATE_SINGLE($TEMPLATE_TITLE);

    }

    $ln=$unix->find_program("ln");
    foreach ($xtpl->arrayxLangs as $Mainlang=>$xarr){
        echo "Mainlang: $Mainlang\n";
        foreach ($xarr as $index=>$z){
            build_progress("Saving $z",60);
            $destination_path="/usr/share/squid-langpack/templates/$z";
            echo "Mainlang: $Mainlang -> $destination_path\n";
            if(!is_link($destination_path)){
                shell_exec("/bin/rm -f $destination_path");
            }
            @unlink("$destination_path");
            echo "Mainlang: ln -sf \"/usr/share/squid-langpack/templates/$Mainlang $destination_path\n";
            shell_exec("$ln -sf \"/usr/share/squid-langpack/templates/$Mainlang\" \"$destination_path\"");
        }
    }
    if($GLOBALS["BYINITD"]){
        $addon="By init.d";
    }
    if($GLOBALS["BY"]<>null){
        $addon=$GLOBALS["BY"];
    }

    system("/usr/sbin/artica-phpfpm-service -proxy-mimeconf");
    @file_put_contents("/etc/artica-postfix/SQUID_TEMPLATE_DONEv3", time());
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $unix->framework_exec("exec.ufdbguard.build.templates.php --build");

    if($GLOBALS["PROGRESS"]){
        if($SQUIDEnable==1){
            build_progress("{reloading} Proxy service",70);
            squid_admin_mysql(2, "{reloading_proxy_service} in order to refresh templates ($addon)", null,__FILE__,__LINE__);
            $SQUID_BIN=$unix->LOCATE_SQUID_BIN();
            if(is_file($SQUID_BIN)){system("/usr/sbin/artica-phpfpm-service -reload-proxy");}
        }
        build_progress("{done}",100);
        return true;
    }
    patching_percents();
    build_progress("{done}",100);
 return true;
}

function patching_percents():bool{
    $workdir="/usr/share/squid-langpack/templates";
    if(!is_dir($workdir)){
        return false;
    }
    $files=scandir("/usr/share/squid-langpack/templates");
    if(!is_array($files)){return false;}
    foreach ($files as $fname){
        if($fname=="."){continue;}
        if($fname==".."){continue;}
        if(!preg_match("#^ERR_#",$fname)){continue;}
        $tpath="$workdir/$fname";
        echo "Patching Template $fname\n";
        $f=@file_get_contents($tpath);
        $f=str_replace("=99%>","=99%%>",$f);
        $f=str_replace("width:75%;","width:75%%;",$f);
        $f=str_replace("width: 80% ;","width:80%%;",$f);
        $f=str_replace("\t","",$f);
        @file_put_contents($tpath,$f);
    }
    return true;
}



?>