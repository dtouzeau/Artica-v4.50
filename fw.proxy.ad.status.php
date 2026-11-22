<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
include_once(dirname(__FILE__)."/ressources/class.kerberos.spn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["renew-perform"])){renew_certificate_perform();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["kerberos-status"])){kerberos_status();exit;}
if(isset($_GET["renew"])){renew_js();exit;}
if(isset($_GET["renew-popup"])){renew_popup();exit;}
if(isset($_POST["DAY"])){renew_save();exit;}
if(isset($_GET["ad-wizard"])){page_wizard();exit;}
if(isset($_GET["ldap-disconnect-js"])){ldap_disconnect_js();exit;}
if(isset($_POST["ldap-disconnect"])){ldap_disconnect_perform();exit;}
page();

// Le mode LDAP n'avait aucun bouton sur cette page, et le menu masque la page
// fw.system.squidadldap.php (seule a porter "Desactiver") des qu'une connexion
// Kerberos/NTLM est declaree : la liaison ne pouvait plus etre coupee.
function ldap_disconnect_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){
        $tpl->js_no_privileges();
        return false;
    }
    return $tpl->js_confirm_execute("{disconnect_ldap_auth}","ldap-disconnect","yes",
        "document.location.href='/ad-state';");
}

// articarest PUT /proxy/auth/ldap : enregistre EnableAdLDAPAuth puis recharge
// Squid en tache de fond. Rend "" si accepte, sinon le message d'erreur.
function ldap_auth_set($enabled):string{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/proxy/auth/ldap",
        array("enabled"=>(bool)$enabled)));
    if(!is_object($json)){
        return "articarest: /proxy/auth/ldap unavailable";
    }
    if(!property_exists($json,"Status") || !$json->Status){
        return property_exists($json,"Error")?(string)$json->Error:"error";
    }
    return "";
}

// Repond vide en cas de succes (js_confirm_execute lit toute reponse comme une erreur).
function ldap_disconnect_perform():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){
        echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");
        return false;
    }
    $err=ldap_auth_set(false);
    if($err<>""){
        echo $err;
        return false;
    }
    return admin_tracks("Disconnected the proxy LDAP authentication (EnableAdLDAPAuth=0)");
}

// Bouton d'en-tete du mode LDAP.
function ldap_auth_tiny_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?ldap-disconnect-js=yes')",ico_unlink,"{disconnect_ldap_auth}");
    $TINY_ARRAY["TITLE"]="{ActiveDirectory}&nbsp;&raquo;&nbsp;{squid_ldap_auth}&nbsp;&raquo;&nbsp;{status}";
    $TINY_ARRAY["ICO"]=ico_microsoft;
    $TINY_ARRAY["EXPL"]="{activedirectory_explain_section}";
    $TINY_ARRAY["URL"]="ad-state";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

function renew_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{kerbtgt_renew}","$page?renew-popup=yes");
    return true;
}
function renew_certificate_perform():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/proxy/kerberos/ticket/renew"));
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    echo "LoadAjax('table-adstate','$page?table=yes');";
    return admin_tracks("Successful Active Directory kerberos certificate renewal");
}

function renew_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LockActiveDirectoryToKerberosRenew",serialize($_POST));
    admin_tracks("Saving Kerberos ticket renew settings");
    $sock=new sockets();
    $sock->REST_API("/mktutils/schedule");
    return true;

}
function renew_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $RenewArray     = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberosRenew"));
    if(!isset($RenewArray["DAY"])){$RenewArray["DAY"]=0;}
    $DAYS[0]="{disabled}";
    for($i=1;$i<30;$i++){
        $term="{days}";
        if($i<2){$term="{day}";}
        $DAYS[$i]="$i $term";
    }
    for($i=1;$i<24;$i++){
        $t="$i";
        if($i<10){$t="0$i";}
        $zH[$i]=$t;
    }
    for($i=1;$i<60;$i++){
        $t="$i";
        if($i<10){$t="0$i";}
        $zM[$i]=$t;
    }
    if(!isset($RenewArray["HOUR"])){$RenewArray["HOUR"]="1";}
    if(!isset($RenewArray["MIN"])){$RenewArray["MIN"]="30";}
    $form[]=$tpl->field_array_hash($DAYS,"DAY","{renew}:{each}",$RenewArray["DAY"]);
    $form[]=$tpl->field_array_hash($zH,"HOUR","{hour}",$RenewArray["HOUR"]);
    $form[]=$tpl->field_array_hash($zM,"MIN","{minutes}",$RenewArray["MIN"]);
    echo $tpl->form_outside(null,$form,null,"{apply}","dialogInstance2.close();LoadAjax('table-adstate','$page?table=yes');");
    return true;
}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $subtitle=null;
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}

    if($LockActiveDirectoryToKerberos==1){
        $subtitle=" Kerberos &raquo;&nbsp; ";
    }

    $html=$tpl->page_header("{ActiveDirectory}&nbsp;&raquo;&nbsp;{$subtitle}{status}","fa fab fa-windows","{activedirectory_explain_section}","$page?table=yes","ad-state","progress-adstate-restart",false,"table-adstate");




	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{ActiveDirectory} {status}",$html);
		echo $tpl->build_firewall();
		return true;
	}
	echo $tpl->_ENGINE_parse_body($html);
    return true;
	
}

function page_choose_ldapauth():bool{
    $tpl            = new template_admin();
    $winbindstatus  = $tpl->widget_vert("{squid_ldap_auth}","{enabled}");
    $AUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("basicauthenticator_current"));

    if($AUTH>0){
        $AUTH=$tpl->FormatNumber($AUTH);
        $authconv=$tpl->widget_vert("{requests}",$AUTH);
    }else{
        $authconv=$tpl->widget_grey("{requests}",0);
    }

    // Mise en page en flex: deux <table> imbriquees pour poser deux blocs cote a
    // cote, avec une colonne de gauche figee a 260px alors que la tuile de service
    // qu'elle recoit en mesure 335.
    $html[] = "<div style='display:flex;flex-wrap:wrap;gap:15px;align-items:flex-start'>";
    $html[] = "<div style='flex:0 0 auto'>$winbindstatus</div>";
    $html[] = "<div style='flex:1 1 420px;min-width:0'>$authconv</div>";
    $html[] = "</div>";
    $html[] = "<div class='center' style='margin-top:20px'><img src='img/squid/basicauthenticator-hourly.flat.png'></div>";
    $html[] = "<div class='center' style='margin-top:20px'><img src='img/squid/basicauthenticator-day.flat.png'></div>";
    $html[] = "<div class='center' style='margin-top:20px'><img src='img/squid/basicauthenticator-week.flat.png'></div>";
    $html[] = "<script>".ldap_auth_tiny_js()."</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function page_choose_methods():bool{
    $tpl=new template_admin();
    $html=array();
    $html[]="<div class='ak adasst'>";
    $html[]="<div class='adasst-grid'>";
    $html[]=ad_state_panel();
    $html[]="<div class='adasst-methods'>".ad_methods_list()."</div>";
    $html[]="</div>";
    $html[]=ad_help_section();
    $html[]="</div>";
    // La feuille de style est concatenee APRES le parse (voir ad_assistant_css()).
    echo ad_assistant_css().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>NoSpinner();</script>";
    return true;
}

// Les cartes seules, pour le point d'entree ?ad-wizard=yes conserve.
function page_wizard(){
    $tpl=new template_admin();
    $html="<div class='ak adasst'><div class='adasst-methods'>".ad_methods_list()."</div></div>";
    echo ad_assistant_css().$tpl->_ENGINE_parse_body($html)."<script>NoSpinner();</script>";
}

function table()
{
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $sock           = new sockets();
    $winbindstatus  = null;

    $winbindd_join=$tpl->framework_buildjs(
        "/ntlm/connect",
        "ntlm.join.progress",
        "ntlm.join.log",
        "progress-adstate-restart",
        "document.location.href='/ad-state'"
    );



    $topbuttons     = array();
    $array_bf["none"] = null;
    $array_bf["green"] = "navy-bg";
    $array_bf["yellow"] = "yellow-bg";
    $array_bf["lazur"] = "lazur-bg";
    $array_bf["red"] = "red-bg";
    $array_bf["blue"] = "blue-bg";
    $array_bf["gray"] = "gray-bg";
    $array_bf["black"] = "black-bg";

    $EnableKerbAuth                 = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $EnableKerbNTLM                 = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbNTLM"));
    $UseNativeKerberosAuth          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
    $EnableAdLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAdLDAPAuth"));
    $WindowsActiveDirectoryKerberos = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
    $LockActiveDirectoryToKerberos  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $EnableSilentNTLMAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSilentNTLMAuth"));
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){
        $UseNativeKerberosAuth=1;
    }

    if($EnableAdLDAPAuth==1){
        return page_choose_ldapauth();
    }


    $ActiveDirectoryEmergency       = intval($sock->GET_INFO("ActiveDirectoryEmergency"));
    if($UseNativeKerberosAuth == 1){$LockActiveDirectoryToKerberos=1;}

    VERBOSE("WindowsActiveDirectoryKerberos:$WindowsActiveDirectoryKerberos; EnableKerbNTLM:$EnableKerbNTLM; EnableKerbAuth:$EnableKerbAuth");

    if($WindowsActiveDirectoryKerberos==0){
        if( $EnableKerbNTLM ==0){
            if($EnableKerbAuth==1){$EnableKerbNTLM=1;}
        }
    }

    if ($LockActiveDirectoryToKerberos == 1) {
        $EnableKerbAuth = 1;
        $WindowsActiveDirectoryKerberos = 1;
        $EnableSilentNTLMAuth=0;
    }
    if($WindowsActiveDirectoryKerberos==0){
        if( $EnableKerbNTLM ==0){
            // Authentification silencieuse installee: sa page remplace le choix des methodes.
            if($EnableSilentNTLMAuth==1){
                echo "<script>document.location.href='/silent-authentication';</script>";
                return true;
            }
            return page_choose_methods();
        }
    }


    $enable_emergency_kerb=$tpl->framework_buildjs("/proxy/emergency/activedirectory/on",
        "ad.emergency.progress",
        "ad.emergency.log",
        "progress-adstate-restart",
        "LoadAjax('table-adstate','$page?table=yes');");

    $disable_emergency_kerb=$tpl->framework_buildjs("/proxy/emergency/activedirectory/off",
        "ad.emergency.progress",
        "ad.emergency.log",
        "progress-adstate-restart",
        "LoadAjax('table-adstate','$page?table=yes');");



    if ($EnableKerbAuth == 1) {
        if ($WindowsActiveDirectoryKerberos == 0) {
            $ARRAY["PROGRESS_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/winbindd.restart.progress";
            $ARRAY["LOG_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/winbindd.restart.progress.log";
            $ARRAY["CMD"] = "/winbindd/restart";
            $ARRAY["TITLE"] = "{restarting_service}";
            $ARRAY["AFTER"] = "LoadAjax('table-adstate','$page?table=yes');";
            $prgress = base64_encode(serialize($ARRAY));
            $winbindd_restart = "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-adstate-restart');";
            // articarest /winbindd/status rend le bloc ini (section APP_WINBINDD).
            $ini = new Bs_IniHandler();
            $wjson = json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/winbindd/status"));
            if (is_object($wjson) && isset($wjson->Info)) { $ini->loadString((string)$wjson->Info); }
            $winbindstatus = $tpl->SERVICE_STATUS($ini, "APP_WINBINDD", $winbindd_restart);
        }
    }




    if ($LockActiveDirectoryToKerberos == 0) {
        $topbuttons[] = array("$winbindd_join",ico_link,"{restart_connection}");
    }

    if ($ActiveDirectoryEmergency == 0) {
        if ($LockActiveDirectoryToKerberos == 0) {
            $topbuttons[] = array("$enable_emergency_kerb","fas fa-exclamation-circle","{enable_emergency_mode}");
        }


        if ($LockActiveDirectoryToKerberos == 1) {
            $topbuttons[] = array("$enable_emergency_kerb","fas fa-exclamation-circle","{enable_emergency_mode}");
        }

    } else {

        if ($LockActiveDirectoryToKerberos == 0) {
            $topbuttons[] = array("$disable_emergency_kerb","fas fa-exclamation-circle","{disable_emergency_mode}");

        }

        if ($LockActiveDirectoryToKerberos == 1) {
            $topbuttons[] = array("$disable_emergency_kerb","fas fa-exclamation-circle","{disable_emergency_mode}");
        }
    }

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $html[] = $tpl->div_error("{license_error}||{license_expired_explain}");
    }

    // Trois <table> imbriquees pour deux colonnes. En flex, la tuile de service
    // garde sa largeur naturelle (335px) au lieu d'etre bridee a 260.
    $html[] = "<div style='display:flex;flex-wrap:wrap;gap:15px;align-items:flex-start'>";
    $html[] = "<div style='flex:0 0 auto;padding-top:27px'>";
    if ($winbindstatus <> null) { $html[] = $winbindstatus; }
    $html[] = "</div>";
    $html[] = "<div style='flex:1 1 420px;min-width:0;padding-top:20px'>";

	
    // Etat sous forme de liste table_form (tuiles en lignes, groupees par theme) au lieu
    // d'une pile de grandes cartes. Chaque bouton porte son privilege en 4e element :
    // th_buttons() le transmet a th_buttons_label(), qui le neutralise sans le droit.
    $analyzeJs="Loadjs('fw.system.ActiveDirectory.analyze.php')";

    $tpl->table_form_section("{status}");
    if($ActiveDirectoryEmergency==1){
        $bt=array();
        if($HaClusterClient==0 && $LockActiveDirectoryToKerberos==0){
            $bt[]=array($analyzeJs,"fas fa-check-circle","{analyze}","AsProxyMonitor");
        }
        adst_row($tpl,"{activedirectory_emergency_mode}","{error}","fas fa-exclamation-circle",true,$bt);
        $tpl->table_form_field_warn("{activedirectory_emergency_mode_explain}");
    }elseif($EnableKerbAuth==1){
        adst_row($tpl,"{ActiveDirectory}","{enabled}",ico_microsoft,false,
            array(array($analyzeJs,"fas fa-check-circle","{analyze}","AsProxyMonitor")));
        $MAIN_ERROR=null;
        $DayToLeft=evaluation_period_days();
        if($DayToLeft<365){
            if($DayToLeft>0){
                $MAIN_ERROR=str_replace("%s",$DayToLeft,$tpl->_ENGINE_parse_body("{warn_no_license_activedirectory_30days}"));
            }else{
                $MAIN_ERROR=$tpl->_ENGINE_parse_body("{warn_evaluation_period_end}");
            }
        }
        if($MAIN_ERROR<>null){
            adst_row($tpl,"{license}","{license}","fas fa-key",true);
            $tpl->table_form_field_warn($MAIN_ERROR);
        }
    }else{
        adst_row($tpl,"{ActiveDirectory}","{disabled}","far fa-times-circle");
    }

    $KERBEROS_AUTH_ERR=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KERBEROS_AUTH_ERR");
    if(strlen($KERBEROS_AUTH_ERR)>5){
        adst_row($tpl,"{active_directory_connection_issue}","{error}","fas fa-exclamation-circle",true);
        // Message du daemon, donc texte libre : echappe, accolades comprises (pas de {jeton}).
        $tpl->table_form_field_warn(str_replace(array("{","}"),array("&#123;","&#125;"),req_html($KERBEROS_AUTH_ERR)));
    }

    if($WindowsActiveDirectoryKerberos==0){
        $ntmlauthenticators=_ntmlauthenticators();
        if(is_array($ntmlauthenticators) && count($ntmlauthenticators)>0){
            $tpl->table_form_section("{ntlm_processes}");
            foreach ($ntmlauthenticators as $cpu=>$mainNTLM){
                $purc=$mainNTLM["PRC"];
                $cpu=intval($cpu);
                adst_row($tpl,"CPU.$cpu",intval($mainNTLM["ACTIVE"])." ({$purc}%)","fas fa-microchip",$purc>95,
                    array(array("Loadjs('fw.system.ActiveDirectory.NTLM.php?cpu=$cpu')","fas fa-signal","{view2}","AsProxyMonitor")));
            }
        }
    }

    if ($LockActiveDirectoryToKerberos == 1) {
        $EnableKerbNTLM=0;
        if($HaClusterClient==0){
            $tpl->table_form_section("Kerberos");
            k5start_status($tpl);
            renew_status($tpl);
        }
    }

    if($EnableKerbNTLM==1){
        $tpl->table_form_section("Kerberos");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("activedirectory.php?klist-principalname=yes");
        $pname=PROGRESS_DIR."/klist_principalname.txt";
        $krb5_renew=$tpl->framework_buildjs(
            "activedirectory.php?ntlm-kerberos=yes",
            "krb5.renew.progress",
            "krb5.renew.log",
            "progress-adstate-restart",
            "LoadAjaxSilent('table-adstate','$page?table=yes');",
            "LoadAjaxSilent('table-adstate','$page?table=yes');"
        );
        $rebuild=array(array($krb5_renew,"far fa-sync-alt","{rebuild}","AsProxyMonitor"));
        if(is_file($pname)) {
            adst_row($tpl,"{kerberos_ticket}",htmlspecialchars(trim((string)@file_get_contents($pname))),"fa-solid fa-file-certificate",false,$rebuild);
        }else{
            adst_row($tpl,"{kerberos_ticket}","{disconnected}","fa-solid fa-file-certificate",true,$rebuild);
        }
        $NTLMWatchdogFreq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTLMWatchdogFreq"));
        if($NTLMWatchdogFreq==0){$NTLMWatchdogFreq=10;}
        adst_row($tpl,"{watchdog}","{each} $NTLMWatchdogFreq {minutes}","fa-solid fa-monitor-waveform",false,
            array(array("Loadjs('fw.system.ActiveDirectory.ntlm-monitor.php')","fa-solid fa-sliders","{configure}","AsProxyMonitor")));
    }
    $html[]=$tpl->table_form_compile();

	

	$html[]="</div>";
    $html[]="<div style='flex:1 1 300px;min-width:0;padding-top:23px'>";
    $html[]="<div id='ad-right-status'></div>";
    $html[]="</div>";
	$html[]="</div>";
    $html[]="<script>";
    $subtitle=null;
    if($HaClusterClient==0) {
        if ($LockActiveDirectoryToKerberos == 1) {
            $subtitle = " Kerberos &raquo;&nbsp; ";
            $topbuttons[] = array("Loadjs('$page?renew-perform=yes')", ico_refresh, "{renew} Kerberos {certificate}");
        }
    }


    // Diagnostic AD/Kerberos en un clic (verdict en une phrase, page /ad-diagnose).
    $topbuttons[] = array("document.location.href='/ad-diagnose'", "fa-solid fa-stethoscope", "{ad_diagnose}");

    $TINY_ARRAY["TITLE"]="{ActiveDirectory}&nbsp;&raquo;&nbsp;{$subtitle}{status}";
    $TINY_ARRAY["ICO"]=ico_microsoft;
    $TINY_ARRAY["EXPL"]="{activedirectory_explain_section}";
    $TINY_ARRAY["URL"]="ad-state";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    if($WindowsActiveDirectoryKerberos==1){
        VERBOSE("-->$page?kerberos-status=yes:",__LINE__);
        $html[]="LoadAjax('ad-right-status','$page?kerberos-status=yes');";
    }
    $html[]="$jstiny</script>";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
	
}

function evaluation_period_days(){
	$Days=86400*30;
	if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){return 365;}
	if(!is_file("/usr/share/artica-postfix/ressources/class.pinglic.inc")){return 365;}
	include_once("/usr/share/artica-postfix/ressources/class.pinglic.inc");
	$EndTime=$GLOBALS['ADLINK_TIME']+$Days;
	$seconds_diff = $EndTime - time();
	return(floor($seconds_diff/3600/24));
}
function _ntmlauthenticators():array{
    $results= array();
    $datas  = array();
    $MAIN   = array();
	include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");

	$cachefile="/etc/artica-postfix/settings/Daemons/makeQuery_ntlmauthenticator";
	if(is_file($cachefile)){$datas=explode("\n",@file_get_contents($cachefile));}
	if(count($datas)==0){
		$cache_manager=new cache_manager();
		$datas=explode("\n",$cache_manager->makeQuery("ntlmauthenticator"));
		if(!$cache_manager->ok){return array();}
	}

	$CPU_NUMBER=0;
	foreach ($datas as $num=>$ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#by kid([0-9]+)#", $ligne,$re)){
			$CPU_NUMBER=$re[1];
			$MAIN[$CPU_NUMBER]["PROCESSES"]=0;
			continue;
		}

		if(preg_match("#number active: ([0-9]+) of ([0-9]+)#",$ligne,$re)){
			$Active=intval($re[1]);
			$Max=intval($re[2]);
			$MAIN[$CPU_NUMBER]["MAX"]=$Max;
			$MAIN[$CPU_NUMBER]["ACTIVE"]=$Active;
			if(!isset($MAIN[$CPU_NUMBER]["PROCESSES"])){$MAIN[$CPU_NUMBER]["PROCESSES"]=0;}
		}

		if(preg_match("#^([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(B|C|R|S|P|\s)\s+([0-9\.]+)\s+([0-9\.]+)\s+(.*)#", $ligne,$re)){
			$Flags=trim($re[6]);
			if($Flags<>null){
				$MAIN[$CPU_NUMBER]["PROCESSES"]=$MAIN[$CPU_NUMBER]["PROCESSES"]+1;
			}
				
				
		}
	}
    foreach ($MAIN as $CPUNUMBER=>$ARRAY){
		$PROCESSES=$ARRAY["PROCESSES"];
		$Active=$ARRAY["ACTIVE"];
		$Max=$ARRAY["MAX"];

		if($PROCESSES==0){
			$results[$CPUNUMBER]["PRC"]=0;
			$results[$CPUNUMBER]["ACTIVE"]=$Active;
			$results[$CPUNUMBER]["MAX"]=$Max;
			continue;
		}

		$prc=round(($PROCESSES/$Max)*100);
		$results[$CPUNUMBER]["PRC"]=$prc;
		$results[$CPUNUMBER]["ACTIVE"]=$Active;
		$results[$CPUNUMBER]["MAX"]=$Max;

	}

	return $results;

}

function kerberos_status():bool{
    $tpl=new template_admin();
    // La tuile SPN passe AVANT la sortie des noeuds de cluster : c'est sur eux que
    // le SPN en double a ete constate (192.168.90.151/.160, 16/10/2026).
    $spntile=kerberos_spn_tile($tpl);
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){
        echo $tpl->_ENGINE_parse_body($spntile);
        return true;
    }
    echo $tpl->_ENGINE_parse_body($spntile);
    $sock=new sockets();
/*
    "Info": { "time_stamp": 1715059802, "ServiceName": "proxylocal.articatech.nux@ARTICATECH.NUX", "KerberosService": "HTTP/proxylocal.articatech.nux@ARTICATECH.NUX", "DiffMins": 996, "ValidStart": 1715120265, "Expires": 1715156265 } }"
*/

    $json=json_decode($sock->REST_API("/proxy/kerberos/status"));

    if(property_exists($json,"Info")) {
        $jjson=$json->Info;
        // Pentest 2026-11-04 : KerberosService / principal (donnee externe) affiche sans encodage (XSS stockee).
        $KerberosServiceH=req_html($jjson->KerberosService ?? "");
        $tpl->table_form_field_text("{service}","<small style='text-transform:unset'>$KerberosServiceH</small>",ico_server);
        $tpl->table_form_field_text("{start_time}",$tpl->time_to_date($jjson->ValidStart,true),ico_clock);
        $tpl->table_form_field_text("{expire}",$tpl->time_to_date($jjson->Expires,true),ico_clock);
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/mktutils/klist");
    $KVNO=array();
    $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/klist.out"));
    $Services=array();
    foreach ($f as $line){
            $line=trim($line);
            if($line==null){continue;}
            VERBOSE($line,__LINE__);
            if(!preg_match("#^([0-9]+)\s+([0-9\/]+)\s+([0-9:]+)\s+(.+)#",$line,$re)){continue;}
            $KVNO[$re[1]]=true;
            $time=strtotime($re[2]." ".$re[3]);
            $principal=trim($re[4]);
            if(strpos("  $principal","HTTP/")==0){continue;}
            $sdate=$tpl->time_to_date($time,true);
            $Services[]=req_html($principal)." ($sdate)";
    }
    $kvnos=array();
    foreach ($KVNO as $key=>$none){
        $kvnos[]=$key;
    }
    if(count($kvnos)>0) {
        $tpl->table_form_field_text("KVNO", @implode(", ",$kvnos), ico_key,"https://wiki.articatech.com/kerberos/kvno");
    }

    if(count($Services)>0){
        $tpl->table_form_section("{history}");
        foreach ($Services as $l){
            $tpl->table_form_field_text("{service}","<small>$l</small>",ico_link);
        }

    }

    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    return true;

}
/**
 * Tuile rouge du controle SPN (rendu partage avec la page des noeuds du LB :
 * ressources/class.kerberos.spn.inc).
 */
function kerberos_spn_tile($tpl):string{
    $page=CurrentPageName();
    $refresh=isset($_GET["spn-refresh"])?"?refresh=1":"";
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/kerberos/spn$refresh"));
    if(!is_object($json) || !isset($json->data) || !is_object($json->data)){return "";}
    $scope=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"))==1?"node":"proxy";
    return kerberos_spn_render($tpl,$json->data,$scope,
        "LoadAjax('ad-right-status','$page?kerberos-status=yes&spn-refresh=yes');");
}

function restart_rest_array():string{
    $page   = CurrentPageName();
    $tpl=new template_admin();
    return $tpl->framework_buildjs("watch:/articarest/rest",
        "active-directory-rest.restart","active-directory-rest.restart.log",
        "progress-adstate-restart","LoadAjaxSilent('adrest-status','$page?status=yes');");
}
/**
 * Une ligne d'etat : libelle, valeur (pastille), icone, rouge si $error, boutons
 * (array($js,$ico,"{label}","Privilege")). table_form_field_js("") desarme le crayon
 * d'edition qu'un appel precedent aurait laisse (switchjs n'est jamais remis a zero).
 */
function adst_row($tpl,$label,$value,$ico,$error=false,$buttons=array()){
    $tpl->table_form_field_js("",null,$buttons);
    $tpl->table_form_field_text($label,$value,$ico,$error);
}

function k5start_status($tpl):bool{
    if(!is_file("/bin/k5start")) {return false;}
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/k5start/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString(is_object($data) && isset($data->Info)?$data->Info:"");
    $running=intval($ini->_params["APP_K5START"]["running"] ?? 0)==1;
    if($running){
        $uptime=htmlspecialchars((string)($ini->_params["APP_K5START"]["uptime"] ?? ""));
        adst_row($tpl,"K5Start {service}||{since} $uptime","{running}","fas fa-thumbs-up");
        return true;
    }
    adst_row($tpl,"K5Start {service}","{stopped}","fas fa-exclamation-circle",true);
    return true;
}

function renew_status($tpl):bool{
    $page           = CurrentPageName();
    $RenewArray     = unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberosRenew"));
    if(!is_array($RenewArray)){$RenewArray=array();}
    $day=intval($RenewArray["DAY"] ?? 0);
    $settings=array("Loadjs('$page?renew=yes')","fa-solid fa-screwdriver-wrench","{settings}","AsProxyMonitor");
    if($day==0){
        adst_row($tpl,"{kerbtgt_renew}","{disabled}","far fa-times-circle",false,array($settings));
        return true;
    }
    $hour=sprintf("%02d",intval($RenewArray["HOUR"] ?? 0));
    $min=sprintf("%02d",intval($RenewArray["MIN"] ?? 0));
    adst_row($tpl,"{kerbtgt_renew}","{each} $day {days} $hour:$min","far fa-alarm-clock",false,
        array($settings,array("Loadjs('$page?renew-perform=yes')","fas fa-play","{run}","AsProxyMonitor")));
    return true;
}

/**
 * Feuille de style de l'assistant de connexion Active Directory.
 * Design: /home/claude/images/stitch_ad_login_assistant.zip ("Admin Core").
 *
 * JAMAIS passee dans _ENGINE_parse_body(): sa regex #{(.+?)}# prend chaque corps de
 * regle CSS pour un {token} et declenche un acces memcached par regle. Elle est donc
 * concatenee APRES l'appel, jamais dedans.
 *
 * Aucune font-family: heriter de la police du skin est ce qui fait qu'un bloc
 * appartient a la console (meme regle que les tableaux awc-*). Les tailles et les
 * graisses du DESIGN.md sont conservees, pas sa police.
 */
function ad_assistant_css():string{
    if(isset($GLOBALS["AD_ASSISTANT_CSS"])){return "";}
    $GLOBALS["AD_ASSISTANT_CSS"]=true;
    return "<style>
.adasst{margin:10px 0}
.adasst-grid{display:grid;grid-template-columns:minmax(240px,1fr) 2fr;gap:24px;align-items:stretch}
@media(max-width:980px){.adasst-grid{grid-template-columns:1fr}}
.adasst-state{position:relative;overflow:hidden;background:#263334;border-radius:12px;padding:32px 24px;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center}
.adasst-state:before{content:'';position:absolute;top:0;right:0;bottom:0;left:0;opacity:.07;background-image:repeating-linear-gradient(60deg,#fff 0 1px,transparent 1px 26px),repeating-linear-gradient(-60deg,#fff 0 1px,transparent 1px 26px)}
.adasst-state>*{position:relative;z-index:1}
.adasst-state-ico{width:64px;height:64px;border-radius:9999px;background:#ffdad6;color:#ba1a1a;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:16px}
.adasst-state-title{font-size:20px;font-weight:700;line-height:26px;letter-spacing:.04em;text-transform:uppercase;color:#fff;margin:0 0 8px}
.adasst-state-text{font-size:13px;line-height:18px;color:#c3c7c8;margin:0 0 16px}
.adasst-state-badge{display:inline-block;padding:8px 16px;border:1px solid rgba(255,255,255,.3);border-radius:4px;background:rgba(255,255,255,.08);font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#fff}
.adasst-methods{display:flex;flex-direction:column;gap:16px}
.adasst-card{position:relative;display:flex;flex-wrap:wrap;gap:24px;align-items:center;padding:20px;transition:box-shadow .15s}
.adasst-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.05)}
.adasst-card-ico{width:72px;height:72px;flex:0 0 auto;border-radius:8px;background:#e3f0f1;color:#006b58;display:inline-flex;align-items:center;justify-content:center;font-size:32px}
.adasst-card-body{flex:1 1 320px;min-width:0}
.adasst-card-title{font-size:20px;font-weight:500;line-height:28px;color:#121e1f;margin:0 0 4px}
.adasst-card-text{font-size:13px;line-height:18px;color:#3c4a45;margin:0}
.adasst-card-act{flex:0 0 auto}
.adasst-reco{position:absolute;top:0;right:0;display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:0 12px 0 8px;background:#cce2fc;color:#50657b;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.adasst-help{padding:20px;margin-top:24px}
.adasst-help-head{display:flex;align-items:center;gap:12px;border-bottom:1px solid #bbcac3;padding-bottom:16px;margin-bottom:16px;color:#4b6076;font-size:22px}
.adasst-help-title{font-size:20px;font-weight:500;line-height:28px;color:#121e1f;margin:0}
.adasst-help-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px}
.adasst-help-lab{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#4b6076;margin-bottom:8px}
.adasst-help-txt{font-size:13px;line-height:18px;color:#3c4a45;margin:0}
</style>";
}

/**
 * Panneau d'etat: la console n'a AUCUNE methode d'authentification definie.
 * C'est la seule chose que l'administrateur doit retenir de cet ecran, d'ou le
 * contraste inverse (surface sombre) qui le detache des cartes blanches.
 */
function ad_state_panel():string{
    $h ="<div class='adasst-state'>";
    $h.="<div class='adasst-state-ico'><i class='fas fa-circle-exclamation'></i></div>";
    $h.="<h3 class='adasst-state-title'>{action_required}</h3>";
    $h.="<p class='adasst-state-text'>{no_auth_defined_proxy}</p>";
    $h.="<div><span class='adasst-state-badge'>{no_auth_defined}</span></div>";
    $h.="</div>";
    return $h;
}

/**
 * Une methode d'authentification proposee sur la page d'accueil Active Directory:
 * pastille d'icone, titre, explication et bouton de lancement de l'assistant.
 * $recommended pose le bandeau d'angle: une seule carte doit le porter, sinon il
 * ne recommande plus rien.
 */
function ad_method_card($ico,$title,$explain,$js,$btnlabel,$recommended=false):string{
    $h ="<div class='ak-card adasst-card'>";
    if($recommended){
        $h.="<span class='adasst-reco'><i class='fas fa-star'></i> {recommended}</span>";
    }
    $h.="<div class='adasst-card-ico'><i class='$ico'></i></div>";
    $h.="<div class='adasst-card-body'>";
    $h.="<h3 class='adasst-card-title'>$title</h3>";
    $h.="<p class='adasst-card-text'>$explain</p>";
    $h.="</div>";
    $h.="<div class='adasst-card-act'>";
    $h.="<a class='ak-btn ak-btn-primary ak-btn-lg' href=\"javascript:blur();\" onclick=\"$js\">";
    $h.="<i class='fas fa-circle-check'></i> $btnlabel</a>";
    $h.="</div>";
    $h.="</div>";
    return $h;
}

/**
 * Les methodes offertes, dans l'ordre de la recommandation: Kerberos d'abord parce
 * qu'il porte le bandeau, NTLM ensuite (le plus repandu), puis LDAP et le mode
 * cluster, qui repond a un cas d'infrastructure precis.
 */
function ad_methods_list():string{
    $h=array();
    $h[]=ad_method_card("fas fa-shield-halved","{kerberos_authentication}","{kerberos_explain_quick}",
        "document.location.href='/single-kerberos'","{select} Kerberos",true);
    $h[]=ad_method_card("fas fa-network-wired","{ntlm_authentication}","{ntml_explain_quick}",
        "document.location.href='/ad-connect'","{select} NTLM");
    $h[]=ad_method_card("fas fa-folder-tree","{squid_ldap_auth}","{squid_ldap_auth_activedirectory}",
        "document.location.href='/adauth-ldap'","{select} LDAP");
    $h[]=ad_method_card("fas fa-server","{cluster_mode} (Kerberos)","{kerberos_cluster_explain_quick}",
        "document.location.href='/cluster-kerberos'","{select} {cluster_mode}");
    $h[]=ad_silent_card();
    return @implode("\n",$h);
}

/**
 * Fiche « authentification silencieuse »: le bouton est desactive, avec la raison, quand
 * /proxy/auth/silent/state refuse (licence, aucune connexion LDAP, autre methode active).
 */
function ad_silent_card():string{
    $r=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/auth/silent/state"),true);
    $refusal=(is_array($r) && isset($r["data"]["refusal"]))?(string)$r["data"]["refusal"]:"";
    $unreadable=!is_array($r) || empty($r["success"]);
    $explain="{silent_authentication_explain}";
    $js="Loadjs('fw.proxy.fakeauth.php?install-js=yes')";
    if($refusal=="" && !$unreadable){
        return ad_method_card("fas fa-user-secret","{silent_authentication}",$explain,$js,"{install}");
    }
    if($unreadable){$reason="{silent_auth_state_unreadable}";}
    elseif($refusal=="license"){$reason="{silent_auth_refusal_license}";}
    elseif($refusal=="no_ldap_connection"){$reason="{silent_auth_refusal_no_ldap}";}
    elseif(strpos($refusal,"other_auth:")===0){
        $reason="{silent_auth_refusal_other} (".preg_replace('/[^A-Za-z0-9_]/','',substr($refusal,11)).")";
    }else{$reason="{silent_auth_refusal_unknown} ($refusal)";}
    $reason.="<br>{silent_auth_no_password_check}";
    if($refusal=="no_ldap_connection"){
        $reason.=" <a href=\"javascript:blur();\" onclick=\"document.location.href='/ad-ldap'\">{silent_auth_add_ldap_connection}</a>";
    }
    $h =ad_method_card("fas fa-user-secret","{silent_authentication}",$explain."<br><b style='color:#ba1a1a'>".$reason."</b>","","{install}");
    // Bouton desactive: pas de gestionnaire de clic, aspect grise.
    $h=str_replace("<a class='ak-btn ak-btn-primary ak-btn-lg' href=\"javascript:blur();\" onclick=\"\">",
        "<a class='ak-btn ak-btn-primary ak-btn-lg' style='opacity:.4;cursor:not-allowed;pointer-events:none' href=\"javascript:blur();\">",$h);
    return $h;
}

// Un motif de panne frequente: pictogramme, intitule et ce qu'il faut verifier.
function ad_help_topic($ico,$label,$text):string{
    $h ="<div>";
    $h.="<div class='adasst-help-lab'><i class='$ico'></i> $label</div>";
    $h.="<p class='adasst-help-txt'>$text</p>";
    $h.="</div>";
    return $h;
}

/**
 * Les trois causes qui expliquent la quasi-totalite des echecs de raccordement AD.
 * Elles figurent sur l'ecran de CHOIX, avant la premiere tentative: les lire apres
 * coup, dans un journal d'erreur, coute une heure a chaque fois.
 */
function ad_help_section():string{
    $h ="<div class='ak-card adasst-help'>";
    $h.="<div class='adasst-help-head'><i class='fas fa-circle-question'></i>";
    $h.="<h3 class='adasst-help-title'>{common_connection_issues}</h3></div>";
    $h.="<div class='adasst-help-grid'>";
    $h.=ad_help_topic("fas fa-server","{dns_resolution}","{ad_help_dns_explain}");
    $h.=ad_help_topic("fas fa-clock","{time_synchronization}","{ad_help_time_explain}");
    $h.=ad_help_topic("fas fa-key","{account_permissions}","{ad_help_account_explain}");
    $h.="</div></div>";
    return $h;
}

