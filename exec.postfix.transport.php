<?php
$GLOBALS["EnablePostfixMultiInstance"]=0;
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.maincf.multi.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.main.hashtables.inc');
include_once(dirname(__FILE__) . '/ressources/class.postfix.externaldbs.inc');
include_once(dirname(__FILE__) . '/ressources/class.postfix.certificate.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
$GLOBALS["PROGRESS_FILE"]=null;
$GLOBALS["POURC_START"]=0;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--pourc=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["POURC_START"]=$re[1];}
if(preg_match("#--progress-file=(.+?)\s+#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS_FILE"]=$re[1];}

if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$sock=new sockets();
$GLOBALS["CLASS_UNIX"]=new unix();
$GLOBALS["MAINCF_ROOT"]="/etc/postfix";
$GLOBALS["POSTFIX_INSTANCE_ID"]=0;
if(preg_match("#--instance-id=([0-9]+)#",implode(" ",$argv),$re)){
    $GLOBALS["POSTFIX_INSTANCE_ID"]=intval($re[1]);
}
if($GLOBALS["POSTFIX_INSTANCE_ID"]>0){
    $GLOBALS["MAINCF_ROOT"]="/etc/postfix-instance{$GLOBALS["POSTFIX_INSTANCE_ID"]}";
}
$GLOBALS["EnablePostfixMultiInstance"]=$sock->GET_INFO("EnablePostfixMultiInstance");
$GLOBALS["EnableBlockUsersTroughInternet"]=$sock->GET_INFO("EnableBlockUsersTroughInternet");
$GLOBALS["postconf"]=$GLOBALS["CLASS_UNIX"]->find_program("postconf");
$GLOBALS["postmap"]=$GLOBALS["CLASS_UNIX"]->find_program("postmap");
$GLOBALS["newaliases"]=$GLOBALS["CLASS_UNIX"]->find_program("newaliases");
$GLOBALS["postalias"]=$GLOBALS["CLASS_UNIX"]->find_program("postalias");
$GLOBALS["postfix"]=$GLOBALS["CLASS_UNIX"]->find_program("postfix");
$GLOBALS["newaliases"]=$GLOBALS["CLASS_UNIX"]->find_program("newaliases");
$GLOBALS["virtual_alias_maps"]=array();
$GLOBALS["alias_maps"]=array();
$GLOBALS["bcc_maps"]=array();
$GLOBALS["smtp_generic_maps"]=array();
$GLOBALS["PHP5_BIN"]=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
if(!is_file($GLOBALS["postfix"])){exit();}

if($argv[1]=="--relayhost"){
    internal_pid($argv);
    mailbox_transport_maps();
    perso_settings();
    $GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
    exit();
}


if($argv[1]=="--mailbox-transport-maps"){
    mailbox_transport_maps();
      echo "TRANSPORT: instanceid-{$GLOBALS["POSTFIX_INSTANCE_ID"]}Starting......: ".date("H:i:s")." Postfix reloading\n";
    $GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
    exit();
}

start();

function build_progress($text,$pourc):bool{
    $unix=new unix();
    if($GLOBALS["POURC_START"]>0){if($pourc<$GLOBALS["POURC_START"]){$pourc=$GLOBALS["POURC_START"];}}
    if($GLOBALS["POURC_START"]>0){if($pourc>95){$pourc=95;}}
    $cachefile=PROGRESS_DIR."/postfix.transport.progress";
    if($GLOBALS["PROGRESS_FILE"]<>null){$cachefile=$GLOBALS["PROGRESS_FILE"];}
      echo "TRANSPORT: instanceid-{$GLOBALS["POSTFIX_INSTANCE_ID"]}{$pourc}% $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

    if($GLOBALS["POSTFIX_INSTANCE_ID"]>0) {
        $unix->framework_progress(80, "$pourc: transport:$text", "postfix-multi.{$GLOBALS["POSTFIX_INSTANCE_ID"]}.reinstall.progress");

        if($pourc>95){
            $pourc=95;
            $unix->framework_progress($pourc, "$pourc: transport:$text", "postfix-multi.{$GLOBALS["POSTFIX_INSTANCE_ID"]}.reconfigure.progress");
        }

    }
    return true;
}

function postfix_verify_files():bool{
    $postmap="/usr/sbin/postmap";
    if(!is_file("{$GLOBALS["MAINCF_ROOT"]}/bad_recipients.db")){
        if(is_file("{$GLOBALS["MAINCF_ROOT"]}/bad_recipients")){
            shell_exec("$postmap hash:{$GLOBALS["MAINCF_ROOT"]}/bad_recipients");
        }else{
            @touch("{$GLOBALS["MAINCF_ROOT"]}/bad_recipients");
            shell_exec("$postmap hash:{$GLOBALS["MAINCF_ROOT"]}/bad_recipients");
        }

    }
    return true;


}


function start():bool{

    $php5=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
    postfix_verify_files();
    build_progress("Loading LDAP config",15);
    LoadLDAPDBs();

    build_progress("Building build_cyrus_lmtp_auth",50);
    build_cyrus_lmtp_auth();
    build_progress("Building relay_recipient_maps_build",55);
    relay_recipient_maps_build();
//	$hashT=new main_hash_table();
//	$hashT->mydestination();
    shell_exec("/usr/sbin/artica-phpfpm-service -postfix-transport");

    build_progress("Building perso_settings",80);
    build_progress("{tls_title}",85);
    $sock=new sockets();
    $sock->REST_API("/postfix/tls/{$GLOBALS["POSTFIX_INSTANCE_ID"]}");



    if($GLOBALS["POURC_START"]==0){
        build_progress("{reloading_smtp_service}",90);
        $GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
        build_progress("{done}",100);
    }
    return true;
}


function relay_recipient_maps_build(){
    $relay_recipient_maps=array();
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if(count($ActiveDirectoryConnections)>0){
        foreach ($ActiveDirectoryConnections as $index=>$HASH){
            $x=array();
            $name=$HASH["NAME"];
            $host=$HASH["LDAP_SERVER"];
            $port=$HASH["LDAP_PORT"];
            $suffix=$HASH["LDAP_SUFFIX"];
            $user="{$HASH["LDAP_DN"]}@{$HASH["LDAP_DOMAIN"]}";
            $password=$HASH["LDAP_PASSWORD"];
            $x[]="server_host     = $host";
            $x[]="server_port     = $port";
            $x[]="version         = 3";
            $x[]="bind            = yes";
            $x[]="start_tls       = no";
            $x[]="bind_dn         = $user";
            $x[]="bind_pw         = $password";
            $x[]="search_base     = $suffix";
            $x[]="scope           = sub";
            $x[]="query_filter    = (proxyAddresses=%s)";
            $x[]="result_attribute= mail";
            $x[]="debuglevel      = 0";
            $x[]="";
            $relay_recipient_maps[]="proxy:ldap:{$GLOBALS["MAINCF_ROOT"]}/ActiveDirectory{$index}.cf";
            @file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/relay_recipient_maps_{$index}.cf", @implode("\n", $x));
        }


    }

    if(count($relay_recipient_maps)>0){
        $relay_recipient_maps_text=@implode(",", $relay_recipient_maps);
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("relay_recipient_maps","$relay_recipient_maps_text",$GLOBALS["POSTFIX_INSTANCE_ID"]);

    }


    $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("relay_recipient_maps","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
}

function build_cyrus_lmtp_auth(){
    $users=new usersMenus();
    $disable=false;
    if($users->ZABBIX_INSTALLED){$disable=true;}else{
        if(!$users->cyrus_imapd_installed){$disable=true;}
    }

    if($disable){
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("lmtp_sasl_auth_enable","no",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("lmtp_sasl_password_maps","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("lmtp_sasl_security_options","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        return;
    }


    $sock=new sockets();
    $page=CurrentPageName();
    $CyrusEnableLMTPUnix=$sock->GET_INFO("CyrusEnableLMTPUnix");
    if($CyrusEnableLMTPUnix==1){
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("lmtp_sasl_auth_enable","no",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("lmtp_sasl_password_maps","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("lmtp_sasl_security_options","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
    }else{
        $ldap=new clladp();
        $CyrusLMTPListen=trim($sock->GET_INFO("CyrusLMTPListen"));
        $cyruspass=$ldap->CyrusPassword();
        if($CyrusLMTPListen==null){return;}
        @file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/lmtpauth","$CyrusLMTPListen\tcyrus:$cyruspass");
        shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/lmtpauth >/dev/null 2>&1");
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("lmtp_sasl_auth_enable","yes",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("lmtp_sasl_password_maps","hash:{$GLOBALS["MAINCF_ROOT"]}/lmtpauth",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("lmtp_sasl_mechanism_filter","plain,login",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("lmtp_sasl_security_options","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
    }

}








function LoadLDAPDBs(){
        if(isset($GLOBALS["LoadLDAPDBs_performed"])){return ;}
        $main=new maincf_multi("master","master");
        $databases_list=unserialize(base64_decode($main->GET_BIGDATA("ActiveDirectoryDBS")));
        if(is_array($databases_list)){
            foreach ($databases_list as $dbindex=>$array){
                if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: {$array["database_type"]}; enabled={$array["enabled"]}\n";}
                if($array["enabled"]<>1){
                    if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: {$array["database_type"]} is not enabled, skipping\n";}
                    continue;
                }
                $targeted_file=$main->buidLdapDB("master",$dbindex,$array);
                if(!is_file($targeted_file)){
                    if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: {$array["database_type"]} \"$targeted_file\" no such file, skipping\n";}
                    continue;
                }


                //$GLOBALS["REMOTE_SMTP_LDAPDB_ROUTING"]

                if($array["resolv_domains"]==1){$domains=$main->buidLdapDBDomains($array);}

                $GLOBALS["LDAPDBS"][$array["database_type"]][]="ldap:$targeted_file";
                if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: GLOBALS[LDAPDBS][{$array["database_type"]}]=ldap:$targeted_file\n";}
            }
        }
        $GLOBALS["LoadLDAPDBs_performed"]=true;
}