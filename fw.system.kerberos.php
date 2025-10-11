<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){features();exit;}
if(isset($_POST["KerberosUsername"])){Save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$kerberos_authentication=$tpl->_ENGINE_parse_body("{kerberos_authentication}");
	
	$kerberos_authentication_explain2=$tpl->_ENGINE_parse_body("{kerberos_authentication_explain2}");
	$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>Active Directory &raquo;&raquo $kerberos_authentication {cluster_mode}</h1>
	<p>$kerberos_authentication_explain2</p>
	
	</div>
</div>                    
<div class='row'><div id='kerberos-ad-restart' class='white-bg'></div>
	<div class='ibox-content'>
		<div id='table-kerberos'></div>
     </div>
</div>
<script>
	$.address.state('/');
	$.address.value('/cluster-kerberos');
	LoadAjax('table-kerberos','$page?table=yes');
</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Active Directory/$kerberos_authentication",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function features(){
    $td_style=null;
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
    $FORM_FILLED=true;
	$KerberosUsername=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosUsername");
	$kerberosRealm=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosRealm");
	$KerberosSPN=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosSPN");
    $myhostname=php_uname("n");
    $KerberosPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosPassword");
    $kerberosActiveDirectoryHost=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectoryHost");
    $kerberosActiveDirectorySuffix=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectorySuffix"));
    $kerberosActiveDirectory2Host=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectory2Host");
    $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
    $kerberosActiveDirectoryLBEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectoryLBEnable"));


    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    if($LockActiveDirectoryToKerberos==1){
        echo $tpl->FATAL_ERROR_SHOW_128("{please_disconnect_your_server_from_kerberos}");
        return;
    }


    $tt=explode(".",$myhostname);
    unset($tt[0]);
    $DEFAULT_DOMAIN=@implode(".",$tt);
    $DEFAULT_DOMAIN_UPPER=strtoupper($DEFAULT_DOMAIN);
    if($kerberosRealm==null){
        $FORM_FILLED=false;
        $kerberosRealm=$DEFAULT_DOMAIN_UPPER;
    }
    if($kerberosActiveDirectoryHost==null){
        $FORM_FILLED=false;
        $kerberosActiveDirectoryHost="dc1.$DEFAULT_DOMAIN";
    }

    if($FORM_FILLED){
        if($KerberosSPN==null){
            $KerberosSPN="HTTP/$myhostname@$kerberosRealm";
        }
    }

    if($UseNativeKerberosAuth==1) {
        $td_style=" style='width:400px'";
        $ARRAY=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosTicketInfo"));
        $expire=distanceOfTimeInWords(time(),$ARRAY["STOP"]);
        if(!isset($ARRAY["DEFAULT_PRINCPAL"])) {
            $td_content[] = $tpl->widget_rouge("{error}", "Kerberos Ticket");
        }else {
            $td_content[] = $tpl->widget_vert("Kerberos Ticket", "<small style='font-size:11px;color:white'>{$ARRAY["DEFAULT_PRINCPAL"]}</small><br>{expire}: $expire");

            $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

            $jsDisconnect= $tpl->framework_buildjs(
                "/kerberos/keytab/uninstall",
                "ActiveDirectoryFeature.progress",
                "ActiveDirectoryFeature.log",
                "kerberos-ad-restart",
                "LoadAjax('table-kerberos','$page?table=yes');"
            );

            //<i class="fas fa-unlink"></i>
            if($PowerDNSEnableClusterSlave==0) {
                $td_content[] = $tpl->button_autnonome("{disconnect}", $jsDisconnect,
                    "fas fa-unlink", "AsSystemAdministrator", 336, "btn-danger");
            }


        }
    }

    $jsrestart=$tpl->framework_buildjs(
        "/kerberos/keytab/install/NONE",
        "ActiveDirectoryFeature.progress",
        "ActiveDirectoryFeature.log",
        "kerberos-ad-restart",
        "LoadAjax('table-kerberos','$page?table=yes');",
        "LoadAjax('table-kerberos','$page?table=yes');"
    );



    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td valign='top'{$td_style}>".@implode("",$td_content)."</td>";
    $html[]="<td valign='top' style='padding-left: 15px'>";
    if($FORM_FILLED){

        $html[]="<p>{kerberos_authentication_ktpass}</p>";
        $html[]="<p style=\"font-family:'Courier New';color:black;background-color:#EEF2FE;border:1px solid #c0c0c0; font-weight:bold;padding: 9px;border-radius:5px;margin:5px;font-size: initial\">ktpass -princ HTTP/$myhostname@$kerberosRealm -mapuser $KerberosUsername@$DEFAULT_DOMAIN_UPPER -crypto AES256-SHA1 -pass ****** -ptype KRB5_NT_PRINCIPAL -out %HOMEPATH%\Downloads\krb5.keytab</p>";

        $html[]=$tpl->form_add_button_upload("krb5.keytab",$page,"AsSystemAdministrator");
    }



	$form[]=$tpl->field_email("KerberosUsername", "{username}", $KerberosUsername,true);
    $form[]=$tpl->field_password2("KerberosPassword", "{password}", $KerberosPassword,true);
    $form[]=$tpl->field_text("kerberosActiveDirectoryHost", "{ad_full_hostname}", $kerberosActiveDirectoryHost,true,"{ad_quick_1}");
    $form[]=$tpl->field_text("kerberosActiveDirectorySuffix", "{ldap_suffix}", $kerberosActiveDirectorySuffix);


    $form[]=$tpl->field_text("kerberosActiveDirectory2Host", "{FQDNDC2}", $kerberosActiveDirectory2Host,false,"{ad_quick_1}");
    if($FORM_FILLED){$form[]=$tpl->field_info("KerberosSPN", "{KERBSPN}", $KerberosSPN);}
    $form[]=$tpl->field_text("kerberosRealm", "{kerberos_realm}", $kerberosRealm,true);
    $form[]=$tpl->field_checkbox("kerberosActiveDirectoryLBEnable","{load_balancing_compatibility}",$kerberosActiveDirectoryLBEnable);




	$html[]=$tpl->form_outside("{join_activedirectory_domain} / {kerberos_authentication}", @implode("\n", $form),"","{apply}",$jsrestart,"AsSystemAdministrator",true);

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
    $tpl=new template_admin();
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    if($PowerDNSEnableClusterSlave==0) { $tpl->SAVE_POSTs(); }
    $tpl->CLEAN_POST();
$IP=new IP();
    $kerberosActiveDirectoryHost=$_POST["kerberosActiveDirectoryHost"];
    $ipaddr=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($kerberosActiveDirectoryHost);
    $ldap_port=389;
    if(!$IP->isValid($ipaddr)){
        echo "jserror:".$tpl->_ENGINE_parse_body("{CURLE_COULDNT_RESOLVE_HOST} $kerberosActiveDirectoryHost");
        return;
    }

    $ldapserver=$_POST["kerberosActiveDirectoryHost"];
    $KerberosUsername=$_POST["KerberosUsername"];
    $KerberosPassword=$_POST["KerberosPassword"];

    $ldap_connection=@ldap_connect($ldapserver,$ldap_port);
    if(!$ldap_connection){
        $DIAG[]="{Connection_Failed_to_connect_to_DC} ldap://$ldapserver:$ldap_port";
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        echo "jserror:".$tpl->_ENGINE_parse_body(@implode("<br>", $DIAG));
        @ldap_close();
        return false;
    }

    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
    $bind=ldap_bind($ldap_connection, $KerberosUsername,$KerberosPassword);
    if(!$bind){
        $DIAG[]="{login_Failed_to_connect_to_DC} $KerberosUsername";
        $DIAG[]=ldap_err2str(ldap_errno($ldap_connection));
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        echo "jserror".$tpl->_ENGINE_parse_body(@implode("<br>", $DIAG));
        return false;
    }


    $kerberosActiveDirectorySuffix=trim($_POST["kerberosActiveDirectorySuffix"]);
    if($kerberosActiveDirectorySuffix==null){
        include_once(dirname(__FILE__)."/ressources/class.ActiveDirectoryRootDSE.inc");
        $ad_rootdse=new ad_rootdse($kerberosActiveDirectoryHost,$ldap_port,$KerberosUsername,$KerberosPassword);
        $_POST["kerberosActiveDirectorySuffix"]=$ad_rootdse->RootDSE();

    }

    $dnssuffix=$_POST["kerberosActiveDirectorySuffix"];
    $dnssuffix=str_ireplace("DC=","",$dnssuffix);
    $dnssuffix=str_ireplace(",",".",$dnssuffix);

    $KerbAuthInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    $KerbAuthInfos["LDAP_SERVER"]=$kerberosActiveDirectoryHost;
    $KerbAuthInfos["LDAP_DN"]=$KerberosUsername;
    $KerbAuthInfos["WINDOWS_SERVER_ADMIN"]=$KerberosUsername;
    $KerbAuthInfos["WINDOWS_DNS_SUFFIX"]=$dnssuffix;
    $KerbAuthInfos["WINDOWS_SERVER_PASS"]=$KerberosPassword;
    $KerbAuthInfos["LDAP_PASSWORD"]=$KerberosPassword;
    $KerbAuthInfos["LDAP_PORT"]=$ldap_port;
    $KerbAuthInfos["LDAP_SUFFIX"]=$_POST["kerberosActiveDirectorySuffix"];
    $KerbAuthInfos["ADNETIPADDR"]=$ipaddr;
    $tpl->SAVE_POSTs();

}

function file_uploaded(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $file=$_GET["file-uploaded"];

    $jsrestart=$tpl->framework_buildjs(
        "/kerberos/keytab/install/$file",
        "ActiveDirectoryFeature.progress",
        "ActiveDirectoryFeature.log",
        "kerberos-ad-restart",
        "LoadAjax('table-kerberos','$page?table=yes');",
    );


    echo $jsrestart;
}
