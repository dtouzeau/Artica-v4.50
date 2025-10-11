<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["renew-perform"])){renew_certificate_perform();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["kerberos-status"])){kerberos_status();exit;}
if(isset($_GET["renew"])){renew_js();exit;}
if(isset($_GET["renew-popup"])){renew_popup();exit;}
if(isset($_POST["DAY"])){renew_save();exit;}
if(isset($_GET["ad-wizard"])){page_wizard();exit;}
page();

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

    $html[] = "<table style='width:100%'>";
    $html[] = "<tr>";
    $html[] = "<td style='width:260px;vertical-align: top'>";
    $html[] = "    <table style='width:100%'>";
    $html[] = "        <tr>";
    $html[] = "            <td style='vertical-align: top'>";
    $html[] = "                    $winbindstatus";
    $html[] = "             </td>";
    $html[] =           "</tr>";
    $html[] =           "</table>";
    $html[] = "</td>";
    $html[] = "<td style='width:99%;vertical-align: top;padding-left: 15px'>";
    $html[] = "    <table style='width:100%'>";
    $html[] = "        <tr>";
    $html[] = "            <td style='vertical-align: top;width:50%'>";
    $html[] = "             $authconv";
    $html[] = "         </td>";
    $html[] = "        </tr>";
    $html[] = "    </table>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</table>";
    $html[] = "<div class='center' style='margin-top:20px'><img src='img/squid/basicauthenticator-hourly.flat.png'></div>";
    $html[] = "<div class='center' style='margin-top:20px'><img src='img/squid/basicauthenticator-day.flat.png'></div>";
    $html[] = "<div class='center' style='margin-top:20px'><img src='img/squid/basicauthenticator-week.flat.png'></div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function page_choose_methods():bool{
    $tpl            = new template_admin();
    $winbindstatus  = $tpl->widget_grey("{no_auth_defined_proxy}","{no_auth_defined}");
    $page=CurrentPageName();

    $html[] = "<table style='width:100%'>";
    $html[] = "<tr>";
    $html[] = "    <td style='width:260px;vertical-align: top'>";
    $html[] = "            <table style='width:100%'>";
    $html[] = "                <tr>";
    $html[] = "                    <td style='vertical-align: top'>";
    $html[] = "                        <div class=\"ibox\" style='border-top:0'>";
    $html[] = "                            <div class=\"ibox-content\" style='border-top:0'>$winbindstatus</div>";
    $html[] = "                        </div>";
    $html[] = "                    </td>";
    $html[] = "                    <td style='width:99%;vertical-align: top'>";
    $html[] = "                        <div id='ad-wizard'></div>";
    $html[] = "                    </td>";
    $html[] = "                </tr>";
    $html[] = "                </table>";
    $html[] = "       </td>";
    $html[] = "</tr>";
    $html[] = "</table>";
    $html[] = "<script>LoadAjax('ad-wizard','$page?ad-wizard=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}

function page_wizard(){
    $tpl=new template_admin();
    $page=CurrentPageName();




    $html[]="<table style='width:550px'>";

    $registrationB=$tpl->button_autnonome("{ntlm_authentication}","document.location.href='/ad-connect'",ico_wizard);
    $registrationC=$tpl->button_autnonome("{kerberaus_authentication}","document.location.href='/single-kerberos'",ico_wizard);
    $registrationD=$tpl->button_autnonome("{cluster_mode}","document.location.href='/cluster-kerberos'",ico_wizard);
    $registrationE=$tpl->button_autnonome("{squid_ldap_auth}","document.location.href='/adauth-ldap'",ico_wizard);

    $html[]="<tr>";
    $html[]="<td style='width:125px;vertical-align: top'><i class='fa-brands fa-windows fa-8x' style='color:rgb(26, 179, 148)'></i></td>";
    $html[]="<td style='vertical-align: top'><H1>{ntlm_authentication}</H1>";
    $html[]="<p>{ntml_explain_quick}</p>";
    $html[]="<div style='margin-top:20px;text-align:right;border-top:1px solid #CCCCCC;padding-top:10px'>$registrationB</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr style='height:80px'><td colspan='2'>&nbsp;</td></tr>";

    $html[]="<tr>";
    $html[]="<td style='width:125px;vertical-align: top'><i class='fa-brands fa-windows fa-8x' style='color:rgb(26, 179, 148)'></i></td>";
    $html[]="<td style='vertical-align: top'><H1>{kerberaus_authentication}</H1>";
    $html[]="<p>{kerberos_explain_quick}</p>";
    $html[]="<div style='margin-top:20px;text-align:right;border-top:1px solid #CCCCCC;padding-top:10px'>$registrationC</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr style='height:80px'><td colspan='2'>&nbsp;</td></tr>";

    $html[]="<tr>";
    $html[]="<td style='width:125px;vertical-align: top'><i class='fa-brands fa-windows fa-8x' style='color:rgb(26, 179, 148)'></i></td>";
    $html[]="<td style='vertical-align: top'><H1>{squid_ldap_auth}</H1>";
    $html[]="<p>{squid_ldap_auth_activedirectory}</p>";
    $html[]="<div style='margin-top:20px;text-align:right;border-top:1px solid #CCCCCC;padding-top:10px'>$registrationE</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr style='height:80px'><td colspan='2'>&nbsp;</td></tr>";
    $html[]="<tr>";
    $html[]="<td style='width:125px;vertical-align: top'><i class='fa-brands fa-windows fa-8x' style='color:rgb(26, 179, 148)'></i></td>";
    $html[]="<td style='vertical-align: top'><H1>{cluster_mode} (Kerberos)</H1>";
    $html[]="<p>{kerberos_cluster_explain_quick}</p>";
    $html[]="<div style='margin-top:20px;text-align:right;border-top:1px solid #CCCCCC;padding-top:10px'>$registrationD</div>";
    $html[]="</td>";




    $html[]="</tr>";

    $html[]="</table>";


    $html[]="<script>Loadjs('$page?tiny-js=yes');</script></div>";
    echo $tpl->_ENGINE_parse_body($html);

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
    }
    if($WindowsActiveDirectoryKerberos==0){
        if( $EnableKerbNTLM ==0){
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
            $ARRAY["CMD"] = "winbindd.php?restart=yes";
            $ARRAY["TITLE"] = "{restarting_service}";
            $ARRAY["AFTER"] = "LoadAjax('table-adstate','$page?table=yes');";
            $prgress = base64_encode(serialize($ARRAY));
            $winbindd_restart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-adstate-restart');";
            $sock->getFrameWork("winbindd.php?status=yes");
            $ini = new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/winbindd.status");
            $winbindstatus = $tpl->SERVICE_STATUS($ini, "SAMBA_WINBIND", $winbindd_restart);
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

    $html[] = "<table style='width:100%'>";
    $html[] = "<tr>";
    $html[] = "<td style='width:260px;vertical-align: top;padding-top:27px'>";

    if ($winbindstatus <> null) {
        $html[] = "<table style='width:100%'>";
        $html[] = "<tr>";
        $html[] = "<td style='vertical-align: top'>";
        $html[] = "$winbindstatus";
        $html[] = "</td></tr>";
        $html[] = "</table>";
    }

    $html[] = "</td>";
	$html[]="<td style='width:100%;vertical-align:top'>";
	$html[]="<table style='width:100%;margin:0;'>";
	$html[]="<tr>";
	$html[]="<td style='padding-left:15px;padding-right:10px;padding-top:20px;vertical-align:top'>";

	
	$MAIN_STATUS="<!-- -------------------------------------------------------------------------------------------------- -->
		<div class=\"widget style1\">
		<div class=\"row\">
		<div class=\"col-xs-4\">
		<i class=\"far fa-times-circle fa-5x\"></i>
		</div>
		<div class=\"col-xs-8 text-right\">
		<span> {ActiveDirectory} {disabled}</span>
		<h2 class=\"font-bold\">-</h2>
		</div>
		</div>
		</div>";
	
	
	if($EnableKerbAuth==1){
        VERBOSE("PASS HERE:",__LINE__);
        $btn[]=array("name"=>"{analyze}","js"=>"Loadjs('fw.system.ActiveDirectory.analyze.php')","icon"=>"fas fa-check-circle","color"=>null);

        $MAIN_STATUS=$tpl->widget_vert("{ActiveDirectory} {enabled}","OK",$btn,null,"100%");

		$MAIN_ERROR=null;
		$DayToLeft=evaluation_period_days();
		if($DayToLeft<365){
			if($DayToLeft>0){
				$MAIN_ERROR=$tpl->_ENGINE_parse_body("{warn_no_license_activedirectory_30days}");
				$MAIN_ERROR=str_replace("%s", $DayToLeft, $MAIN_ERROR);
			}else{
				if($DayToLeft<30){
					$MAIN_ERROR=$tpl->_ENGINE_parse_body("{warn_evaluation_period_end}");
				}
			}
		}
		if($MAIN_ERROR<>null){
			$MAIN_STATUS="<!-- -------------------------------------------------------------------------------------------------- -->
			<div class=\"widget style1 yellow-bg\">
			<div class=\"row\">
			<div class=\"col-xs-4\">
			<i class=\"fas fa-key fa-5x\"></i>
			</div>
			<div class=\"col-xs-8 text-right\">
			<span> {ActiveDirectory} {license}</span>
			<h2 class=\"font-bold\">{license}</h2>
			<p><small>$MAIN_ERROR</small></p>
			</div>
			</div>
			</div>";
		}
		
	}
	
	if($ActiveDirectoryEmergency==1){
		
		$analyze=$tpl->button_autnonome("{analyze}",
                "Loadjs('fw.system.ActiveDirectory.analyze.php')","fas fa-check-circle","","AsProxyMonitor","btn-info");
		$analyze="<div style='text-align:right;margin-top:10px'>$analyze</div>";
        if($HaClusterClient==1){$analyze="";}

        if($LockActiveDirectoryToKerberos==1){$analyze=null;}

		$MAIN_STATUS="<!-- ------------------------------------------------------------------ -->
		<div class=\"widget style1 red-bg\">
		<div class=\"row\">
		<div class=\"col-xs-4\">
		<i class=\"fas fa-exclamation-circle fa-5x\"></i>
		</div>
		<div class=\"col-xs-8 text-right\">
		<span> {activedirectory_emergency_mode}</span>
		<h2 class=\"font-bold\">{error}</h2>
		<p><small>{activedirectory_emergency_mode_explain}</p>
		$analyze
		</div>
		</div>
		</div>";
		}


    $KERBEROS_AUTH_ERR=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KERBEROS_AUTH_ERR");
    if(strlen($KERBEROS_AUTH_ERR)>5){
        $html[]="	<div class=\"widget style1 red-bg\">
		<div class=\"row\">
		<div class=\"col-xs-4\">
		<i class=\"fas fa-exclamation-circle fa-5x\"></i>
		</div>
		<div class=\"col-xs-8 text-right\">
		<span> {active_directory_connection_issue}</span>
		<h2 class=\"font-bold\">{error}</h2>
		<p><small>$KERBEROS_AUTH_ERR</p>
		</div>
		</div>
		</div>";
    }

	$html[]=$MAIN_STATUS;


	
	
	if($WindowsActiveDirectoryKerberos==0){
		$ntmlauthenticators=_ntmlauthenticators();
		if(is_array($ntmlauthenticators)){
            foreach ($ntmlauthenticators as $cpu=>$mainNTLM){
				$purc=$mainNTLM["PRC"];
				$ACTIVE=$mainNTLM["ACTIVE"];
				$ntmlauthenticators_view=$tpl->button_autnonome("{view2}", "Loadjs('fw.system.ActiveDirectory.NTLM.php?cpu=$cpu')","fas fa-signal","AsProxyMonitor",0,"btn-info");
	
				$color="navy-bg";
				if($purc>95){$color="yellow-bg";}
				
				$html[]="<!-- ---------------------------------------------------------- -->
				<div class=\"widget style1 $color\">
				<div class=\"row\">
				<div class=\"col-xs-4\">
				<i class=\"fas fa-microchip fa-5x\"></i>
				</div>
				<div class=\"col-xs-8 text-right\">
				<span> CPU.$cpu {ntlm_processes}:</span>
				<h2 class=\"font-bold\">$ACTIVE ({$purc}%)</h2>
				<div style='text-align:right;margin-top:10px'>$ntmlauthenticators_view</div>
				</div>
				</div>
				</div>";
				
				

			}
		}
	}

    if ($LockActiveDirectoryToKerberos == 1) {
        $EnableKerbNTLM=0;
        VERBOSE("PASS HERE:",__LINE__);
        $html[]=k5start_status();
        $html[]=renew_status();
    }

    VERBOSE("EnableKerbNTLM=$EnableKerbNTLM",__LINE__);
    if($EnableKerbNTLM==1){
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
        $bouton=$tpl->button_autnonome("{rebuild}",
            $krb5_renew,
            "far fa-sync-alt","AsProxyMonitor",0,"btn-primary");
        if(is_file($pname)) {
            $PrincipalName = @file_get_contents($pname);
            $html[] = "<!-- ---------------------------------------------------------------------- -->
		<div class=\"widget style1 navy-bg\">
		<div class=\"row\">
		<div class=\"col-xs-4\">
		<i class=\"fa-solid fa-file-certificate fa-5x\"></i>
		</div>
		<div class=\"col-xs-8 text-right\">
		<span> {kerberos_ticket}</span>
		<h2 class=\"font-bold\">$PrincipalName</h2>
		<div style='text-align:right;margin-top:10px'>$bouton</div>
		</div>
		</div>
		</div>";
        }else{
            $html[] = "<!-- ---------------------------------------------------------------------- -->
		<div class=\"widget style1 yellow-bg\">
		<div class=\"row\">
		<div class=\"col-xs-4\">
		<i class=\"fa-solid fa-file-certificate fa-5x\"></i>
		</div>
		<div class=\"col-xs-8 text-right\">
		<span> {kerberos_ticket}</span>
		<h2 class=\"font-bold\">{disconnected}</h2>
		<div style='text-align:right;margin-top:10px'>$bouton</div>
		</div>
		</div>
		</div>";
        }

        $NTLMWatchdogFreq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTLMWatchdogFreq"));
        if($NTLMWatchdogFreq==0){$NTLMWatchdogFreq=10;}

        $watch_configure=$tpl->button_autnonome("{configure}",
            "Loadjs('fw.system.ActiveDirectory.ntlm-monitor.php')",
             "fa-solid fa-sliders","AsProxyMonitor",0,"btn-primary");



        $html[]="<div class=\"widget style1 navy-bg\">
		<div class=\"row\">
		<div class=\"col-xs-4\">
		<i class=\"fa-solid fa-monitor-waveform fa-5x\"></i>
		</div>
		<div class=\"col-xs-8 text-right\">
		<span> {watchdog}</span>
		<h2 class=\"font-bold\">{each} $NTLMWatchdogFreq {minutes}</h2>
		<div style='text-align:right;margin-top:10px'>$watch_configure</div>
		</div>
		</div>
		</div>";

    }

	

	$html[]="</td>";
    $html[]="<td style='vertical-align: top'>";
    $html[]="<div id='ad-right-status' style='margin-top:23px'></div>";
    $html[]="</td>";

	$html[]="</tr>";
	$html[]="</table>";
	$html[]="</td>";
	$html[]="</tr>";
	
	$html[]="</table>";
    $html[]="<script>";
    $subtitle=null;
    if($HaClusterClient==0) {
        if ($LockActiveDirectoryToKerberos == 1) {
            $subtitle = " Kerberos &raquo;&nbsp; ";
            $topbuttons[] = array("Loadjs('$page?renew-perform=yes')", ico_refresh, "{renew} Kerberos {certificate}");
        }
    }


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
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){return "";}
    $tpl=new template_admin();
    $sock=new sockets();
/*
    "Info": { "time_stamp": 1715059802, "ServiceName": "proxylocal.articatech.nux@ARTICATECH.NUX", "KerberosService": "HTTP/proxylocal.articatech.nux@ARTICATECH.NUX", "DiffMins": 996, "ValidStart": 1715120265, "Expires": 1715156265 } }"
*/

    $json=json_decode($sock->REST_API("/proxy/kerberos/status"));

    if(property_exists($json,"Info")) {
        $jjson=$json->Info;
        $tpl->table_form_field_text("{service}","<small style='text-transform:unset'>$jjson->KerberosService</small>",ico_server);
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
            $Services[]="$principal ($sdate)";
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
function restart_rest_array():string{
    $page   = CurrentPageName();
    $tpl=new template_admin();
    return $tpl->framework_buildjs("watch:/articarest/rest",
        "active-directory-rest.restart","active-directory-rest.restart.log",
        "progress-adstate-restart","LoadAjaxSilent('adrest-status','$page?status=yes');");
}
function k5start_status():string{
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if(!is_file("/bin/k5start")) {return "";}
    if($HaClusterClient==1){return "";}
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/k5start/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($data->Info);

    if(intval($ini->_params["APP_K5START"]["running"])==1) {
        return "<!-- -------------------------------------------------------------- -->
                <div class=\"widget style1 navy-bg\">
                <div class=\"row\">
                    <div class=\"col-xs-4\">
                        <i class=\"fas fa-thumbs-up fa-5x\"></i>
                    </div>
                <div class=\"col-xs-8 text-right\">
                <span> K5Start {service}</span>
                <h2 class=\"font-bold\">{running}</h2>
                <div style='text-align:right;margin-top:10px'>{since} {$ini->_params["APP_K5START"]["uptime"]}</div>
                </div>
                </div>
                </div>";
    }

    return "<!-- ------------------------------------------------------------------ -->
		<div class=\"widget style1 red-bg\">
		<div class=\"row\">
		<div class=\"col-xs-4\">
		<i class=\"fas fa-exclamation-circle fa-5x\"></i>
		</div>
		<div class=\"col-xs-8 text-right\">
		<span> K5Start {service}</span>
		<h2 class=\"font-bold\">{stopped}</h2>
		</div>
		</div>
		</div>";

}
function renew_status(){
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){
        return "";
    }
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $RenewArray     = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberosRenew"));
    if(!is_array($RenewArray)){$RenewArray=array();}
    if(!isset($RenewArray["DAY"])){$RenewArray["DAY"]=0;}

    if($RenewArray["DAY"]==0){
        $RenewArrayButton=$tpl->button_autnonome("{settings}",
            "Loadjs('$page?renew=yes')","fa-solid fa-screwdriver-wrench","AsProxyMonitor",0,"btn-default");

        return "<!-- -------------------------------------------------------------- -->
                <div class=\"widget style1 gray-bg\">
                <div class=\"row\">
                    <div class=\"col-xs-4\">
                        <i class=\"fa-blink far fa-times-circle fa-5x\"></i>
                    </div>
                <div class=\"col-xs-8 text-right\">
                <span>{kerbtgt_renew}</span>
                <h2 class=\"font-bold\">{disabled}</h2>
                <div style='text-align:right;margin-top:10px'>$RenewArrayButton</div>
                </div>
                </div>
                </div>";

    }

    $rnews="Loadjs('$page?renew-perform=yes')";


    $RenewArrayButton="<div class=\"btn-group\" data-toggle=\"buttons\">
        <label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?renew=yes')\"><i class='fa-solid fa-screwdriver-wrench'></i> {settings} </label>
                <label class=\"btn btn btn-info\" OnClick=\"$rnews\">
                <i class='fas fa-play'></i> {run} </label>
     </div>";



    return "<!-- -------------------------------------------------------------- -->
                <div class=\"widget style1 navy-bg\">
                <div class=\"row\">
                    <div class=\"col-xs-4\">
                        <i class=\"far fa-alarm-clock fa-5x\"></i>
                    </div>
                <div class=\"col-xs-8 text-right\">
                <span>{kerbtgt_renew}</span>
                <h2 class=\"font-bold\">{each} {$RenewArray["DAY"]} {days} {$RenewArray["HOUR"]}:{$RenewArray["MIN"]}</h2>
                <div style='text-align:right;margin-top:10px'>$RenewArrayButton</div>
                </div>
                </div>
                </div>";
}