<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();

include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.sqlite.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.maincf.multi.inc');
include_once(dirname(__FILE__).'/ressources/class.postfix.certificate.inc');

echo "Loading class.postfix.certificate.inc END\n";
$GLOBALS["NORELOAD"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
$unix=new unix();
$GLOBALS["postconf"]=$unix->find_program("postconf");
$GLOBALS["postmap"]=$unix->find_program("postmap");
$GLOBALS["MAINCF_ROOT"]="/etc/postfix";
$GLOBALS["POSTFIX_INSTANCE_ID"]=0;

if(preg_match("#--instance-id=([0-9]+)#",implode(" ",$argv),$re)){
    $GLOBALS["POSTFIX_INSTANCE_ID"]=intval($re[1]);
}
if($GLOBALS["POSTFIX_INSTANCE_ID"]>0){
    $GLOBALS["MAINCF_ROOT"]="/etc/postfix-instance{$GLOBALS["POSTFIX_INSTANCE_ID"]}";
}

xstart();



function xstart():bool{
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$unix=new unix();
	build_progress(20,"{building}");
	
	$SmtpTlsSecurityLevel=trim($main->GET("SmtpTlsSecurityLevel"));
	
	$SmtpTlsWrapperMode=intval($main->GET("SmtpTlsWrapperMode"));
	if($SmtpTlsWrapperMode==1){$SmtpTlsSecurityLevel="encrypt";}
	$PostFixMasterCertificate=trim($main->GET("PostFixMasterCertificate"));
	$smtpd_tls_session_cache_timeout=intval($main->GET("smtpd_tls_session_cache_timeout"));
	if($smtpd_tls_session_cache_timeout==0){$smtpd_tls_session_cache_timeout=3600;}
	$smtpd_tls_auth_only=intval($main->GET("smtpd_tls_auth_only"));
	
	$SmtpTlsProtocols=intval($main->GET("SmtpTlsProtocols"));
	$SmtpTlsSessionCacheTimeout=intval($main->GET("SmtpTlsSessionCacheTimeout"));
	if($SmtpTlsSessionCacheTimeout==0){$SmtpTlsSessionCacheTimeout=3600;}
	if($SmtpTlsProtocols==null){$SmtpTlsProtocols="!SSLv2, !SSLv3";}
	
	
	$unix->POSTCONF_SET("smtp_tls_session_cache_timeout","{$SmtpTlsSessionCacheTimeout}s",$GLOBALS["POSTFIX_INSTANCE_ID"]);
    $unix->POSTCONF_SET("smtp_tls_security_level",$SmtpTlsSecurityLevel,$GLOBALS["POSTFIX_INSTANCE_ID"]);
    $unix->POSTCONF_SET("smtp_tls_protocols",$SmtpTlsProtocols,$GLOBALS["POSTFIX_INSTANCE_ID"]);
    $unix->POSTCONF_SET("smtp_tls_note_starttls_offer","yes",$GLOBALS["POSTFIX_INSTANCE_ID"]);
    $unix->POSTCONF_SET("smtp_tls_wrappermode",$main->YesNo($SmtpTlsWrapperMode),$GLOBALS["POSTFIX_INSTANCE_ID"]);



    $smtpd_tls_protocols=$main->GET("smtpd_tls_protocols");
    if($smtpd_tls_protocols==null){
        $smtpd_tls_protocols="!SSLv2, !SSLv3";
    }

    $PostfixEnableSubmission=intval($main->GET("PostfixEnableSubmission"));
	$PostfixEnableMasterCfSSL=intval($main->GET("PostfixEnableMasterCfSSL"));
	$smtpd_tls_security_level=$main->GET("smtpd_tls_security_level");
	if($smtpd_tls_security_level==null){$smtpd_tls_security_level="may";}
    if($PostfixEnableSubmission){$PostfixEnableMasterCfSSL=1;}
	
	
	echo "Starting......: ".date("H:i:s")." Certificate $PostFixMasterCertificate\n";
	$cert=new postfix_certificate($PostFixMasterCertificate);
	$cert->build();
	$unix->chown_func("postfix","postfix","/etc/ssl/certs/postfix/*");
	
	if($PostfixEnableMasterCfSSL==1){
        $unix->POSTCONF_SET("smtpd_tls_auth_only" ,$main->YesNo($smtpd_tls_auth_only),$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_received_header","no",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_security_level" ,$smtpd_tls_security_level,$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_session_cache_timeout" ,"{$smtpd_tls_session_cache_timeout}s",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_protocols" ,$smtpd_tls_protocols,$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_session_cache_database" ,"btree:/var/lib/postfix/smtpd_tls_cache");

	
	}else{
        $unix->POSTCONF_SET("smtpd_tls_received_header",null,$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_security_level" ,"none",$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_key_file",null,$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_cert_file",null,$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_CAfile",null,$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_auth_only" ,null,$GLOBALS["POSTFIX_INSTANCE_ID"]);
        $unix->POSTCONF_SET("smtpd_tls_protocols" ,null,$GLOBALS["POSTFIX_INSTANCE_ID"]);

	}
	
	build_progress(50,"{building}");

	
	//Vérification du smtp_sender_restriction.
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("/usr/sbin/artica-phpfpm-service -smtpd-restrictions -instanceid {$GLOBALS["POSTFIX_INSTANCE_ID"]}");

	// A LA FIN
    smtp_tls_policy_maps($GLOBALS["POSTFIX_INSTANCE_ID"]);

	return reload();

}

function smtp_tls_policy_maps($instanceid):bool{
    $unix=new unix();

    $GLOBALS["MAINCF_ROOT"]="/etc/postfix";

    if($instanceid>0){
        $GLOBALS["MAINCF_ROOT"]="/etc/postfix-instance$instanceid";
    }


    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    if(!$q->FIELD_EXISTS("transport_maps","instanceid")){
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD instanceid INTEGER NOT NULL DEFAULT 0");
    }

    $results=$q->QUERY_SQL("SELECT * FROM transport_maps WHERE enabled=1 and instanceid=$instanceid ORDER BY addr ");
    if(!$q->ok){echo $q->mysql_error."\n";}

    $array_field_relay_tls=array("none"=>"{none}",
        "may"=>"{Opportunistic_TLS}",
        "dane"=>"{DANE_SMTP}",
        "dane-only"=>"{DANE_ONLY}",
        "encrypt"=>"{Mandatory_TLS_encryption}",
        "verify"=>"{Mandatory_TLS_verification}",
        "secure"=>"{Secure_channel_TLS}");

    if(count($results)==0){
        $unix->POSTCONF_SET("smtp_tls_policy_maps",null,$GLOBALS["POSTFIX_INSTANCE_ID"]);
        echo "smtp_tls_policy_maps No data\n";
        return false;
    }


    $array=array();
    $srelays=array();

        foreach ($results as $num=>$ligne){
            $OtherDomains=unserialize(base64_decode($ligne["OtherDomains"]));
            $OtherDomains[$ligne["addr"]]=true;
            $tls_enabled = intval($ligne["tls_enabled"]);
            $tls_mode = $ligne["tls_mode"];
            $nexthope=$ligne["nexthope"];
            $nextport=$ligne["nextport"];
            if($tls_enabled==1){
                if (isset($array_field_relay_tls[$tls_mode])) {
                    $srelays["$nexthope:$nextport"]=$tls_mode;
                }
            }

            foreach ($OtherDomains as $item=>$none) {
                if (strpos("  $item", "#") > 0) { continue; }
                if (strpos($item, "@") > 0) { continue; }
                if ($tls_enabled == 0) { $array[] = "$item\tnone"; continue; }
                if (!isset($array_field_relay_tls[$tls_mode])) {
                    $array[] = "$item\tnone";
                    continue;
                }

                $array[] = "$item\t$tls_mode";
            }

        }

    if(count($srelays)==0){
        $unix->POSTCONF_SET("smtp_tls_policy_maps",null,$instanceid);
        echo "smtp_tls_policy_maps No record\n";
        return false;

    }
   foreach ($srelays as $hope=>$security){
        $array[] = "$hope\t$security";
   }

    echo "Saving {$GLOBALS["MAINCF_ROOT"]}/smtp_tls_policy_maps\n";
    @file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/smtp_tls_policy_maps", @implode("\n", $array));
    system("postmap hash:{$GLOBALS["MAINCF_ROOT"]}/smtp_tls_policy_maps");
    $unix->POSTCONF_SET("smtp_tls_policy_maps","hash:{$GLOBALS["MAINCF_ROOT"]}/smtp_tls_policy_maps",$instanceid);
    return true;


}



function reload():bool{
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["NORELOAD"]){ build_progress(100,"{building} {success}"); return false; }
	build_progress(95,"{building} master.cf...");

    if(is_file("/etc/init.d/artica-milter")){
        system("$php /usr/share/artica-postfix/exec.artica-milter.php --monit");
    }

	system("$php /usr/share/artica-postfix/exec.postfix.maincf.php --ssl-none --instance-id={$GLOBALS["POSTFIX_INSTANCE_ID"]}");
	build_progress(95,"{reloading}...");
	$unix->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
	build_progress(100,"{building} {success}");
    return true;
}


function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/smtp_tls_policy_maps.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
