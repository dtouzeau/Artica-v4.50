<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/ressources/PowerShellKTPass.inc.php");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["disable-ad"])){disable_ad_ask();exit;}
if(isset($_GET["enable-ad"])){enable_ad();exit;}
if(isset($_GET["autorenew-js"])){autorenew_js();exit;}
if(isset($_GET["autorenew-popup"])){autorenew_popup();exit;}
if(isset($_POST["EnablehaclusterAutoRenew"])){autorenew_save();exit;}

if(isset($_GET["keytab-error-popup"])){keytab_error_popup();exit;}
if(isset($_GET["keytab-error"])){keytab_error_js();exit;}
if(isset($_POST["disable-ad"])){disable_ad_save();exit;}
if(isset($_GET["reset"])){reset_infos();exit;}
if(isset($_GET["table"])){features();exit;}
if(isset($_POST["KerberosUsername"])){Save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["wizard-js"])){wizard_js();exit;}
if(isset($_GET["wizard-popup"])){wizard_popup();exit;}
if(isset($_POST["wizard"])){wizard_save();exit;}
if(isset($_GET["kerberos-js"])){kerberos_js();exit;}
if(isset($_GET["buttons"])){buttons();exit;}
if(isset($_GET["kerberos-popup"])){kerberos_popup();exit();}
if(isset($_GET["kerberos-enc-js"])){kerberos_enc_js();exit;}
if(isset($_GET["kerberos-enc-popup"])){kerberos_enc_popup();exit;}
if(isset($_POST["WINDOWS_SERVER_TYPE"])){kerberos_enc_save();exit;}
if(isset($_GET["flat-ticket"])){flat_ticket();exit;}
if(isset($_GET["formflat"])){form_flat();exit;}
if(isset($_GET["ad-form-js"])){ad_form_js();exit;}
if(isset($_GET["ad-form-popup"])){ad_form_popup();exit;}
if(isset($_GET["download-ps1"])){DownloadKeyTab();exit;}
page();

function wizard_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{wizard}","$page?wizard-popup=yes");
}
function disable_ad_ask():bool{
    $tpl=new template_admin();
    return $tpl->js_confirm_execute("{HaClusterDoNotUseAD}","disable-ad","yes","window.location.reload();");
}
function enable_ad():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterDoNotUseAD",0);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    echo "window.location.reload();\n";
    return admin_tracks("Enable Active Directory Feature in HaCluster section");
}
function disable_ad_save():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterDoNotUseAD",1);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    return admin_tracks("Disable Active Directory Feature in HaCluster section");

}
function  ad_form_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{parameters}","$page?ad-form-popup=yes");
}

function kerberos_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{kerberos_ticket}","$page?kerberos-popup=yes");
}
function keytab_error_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{kerberos_ticket}","$page?keytab-error-popup=yes");
}
function kerberos_enc_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{supported_encryption_type}","$page?kerberos-enc-popup=yes");
}
function autorenew_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{autorenew_ticket}","$page?autorenew-popup=yes");
}

function autorenew_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnablehaclusterAutoRenew=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablehaclusterAutoRenew"));
    $form[]=$tpl->field_checkbox("EnablehaclusterAutoRenew","{autorenew_ticket}",$EnablehaclusterAutoRenew);
    $html[]=$tpl->form_outside("",@implode("\n",$form),"{kerb_autorenew_explain}","{apply}","LoadAjax('formflat','$page?formflat=yes');dialogInstance1.close();","AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function autorenew_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return admin_tracks("Enable/Disable Auto Renew Kerberos Ticket");
}

function kerberos_enc_popup(){
    $tpl=new template_admin();
    $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $severtype["WIN_2003"]="Windows 2000/2003";
    $severtype["WIN_2008AES"]="Windows 2008/2012";
    $severtype["WIN_2016"]="Windows 2016/2019";


    if($haClusterAD["WINDOWS_SERVER_TYPE_PERSO"]==null) {
        $haClusterAD["WINDOWS_SERVER_TYPE_PERSO"] = "rc4-hmac aes256-cts-hmac-sha1-96 aes128-cts-hmac-sha1-96";
    }

    $form[] = $tpl->field_array_hash($severtype, "WINDOWS_SERVER_TYPE", "{WINDOWS_SERVER_TYPE}",$haClusterAD["WINDOWS_SERVER_TYPE"]);
    $form[] = $tpl->field_checkbox("WINDOWS_SERVER_TYPE_OWN","{use_your_own_values}",$haClusterAD["WINDOWS_SERVER_TYPE_OWN"],"WINDOWS_SERVER_TYPE_PERSO");
    $form[] = $tpl->field_text("WINDOWS_SERVER_TYPE_PERSO","{supported_encryption_type}",$haClusterAD["WINDOWS_SERVER_TYPE_PERSO"]);

    $html[] = $tpl->form_outside(null, @implode("\n", $form), "", "{apply}", null, "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);
}
function kerberos_enc_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();


    $KREB_SUPPORT["des-cbc-crc"]=true;
    $KREB_SUPPORT["des-cbc-md4"]=true;
    $KREB_SUPPORT["des-cbc-md5"]=true;
    $KREB_SUPPORT["des3-cbc-sha1"]=true;
    $KREB_SUPPORT["des3-hmac-sha1"]=true;
    $KREB_SUPPORT["des3-cbc-sha1-kd"]=true;
    $KREB_SUPPORT["des-hmac-sha1"]=true;
    $KREB_SUPPORT["aes256-cts-hmac-sha1-96"]=true;
    $KREB_SUPPORT["aes256-cts"]=true;
    $KREB_SUPPORT["aes128-cts-hmac-sha1-96"]=true;
    $KREB_SUPPORT["aes128-cts"]=true;
    $KREB_SUPPORT["arcfour-hmac"]=true;
    $KREB_SUPPORT["rc4-hmac"]=true;
    $KREB_SUPPORT["arcfour-hmac-md5"]=true;
    $KREB_SUPPORT["arcfour-hmac-exp"]=true;
    $KREB_SUPPORT["rc4-hmac-exp"]=true;
    $KREB_SUPPORT["arcfour-hmac-md5-exp"]=true;
    if(intval($_POST["WINDOWS_SERVER_TYPE_OWN"])==1) {
        $NOTAVAIL=array();
        $AVAIL=array();
        $_POST["WINDOWS_SERVER_TYPE_PERSO"]=str_replace(","," ",$_POST["WINDOWS_SERVER_TYPE_PERSO"]);
        $tb=explode(" ",$_POST["WINDOWS_SERVER_TYPE_PERSO"]);
        foreach ($tb as $key){
            $key=trim(strtolower($key));
            if($key==null){continue;}
            if(!isset($KREB_SUPPORT[$key])){
                $NOTAVAIL[]=$key;
                continue;
            }
            $AVAIL[$key]=true;
        }
        if(count($NOTAVAIL)>0){
            echo $tpl->post_error("{invalid}:".@implode(", ",$NOTAVAIL));
            return false;
        }
        $ttr=array();
        foreach ($AVAIL as $key=>$none){
            $ttr[]=$key;
        }
        $_POST["WINDOWS_SERVER_TYPE_PERSO"]=@implode(" ",$ttr);
    }

    $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $haClusterAD["WINDOWS_SERVER_TYPE"]=$_POST["WINDOWS_SERVER_TYPE"];
    $haClusterAD["WINDOWS_SERVER_TYPE_PERSO"]=$_POST["WINDOWS_SERVER_TYPE_PERSO"];
    $haClusterAD["WINDOWS_SERVER_TYPE_OWN"]=intval($_POST["WINDOWS_SERVER_TYPE_OWN"]);
    $haClusterADSer=serialize($haClusterAD);
    $haClusterADSEnc=base64_encode($haClusterADSer);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD",$haClusterADSEnc);

}
function kerberos_popup(){
    $tpl=new template_admin();
    $filepath=PROGRESS_DIR."/hacluster.kerberos";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("hacluster.php?kerberos-ticket=yes");
    $data=@file_get_contents($filepath);
    $tb=explode("\n",$data);
    $t=time();

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>KVNO</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>SPN</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{duration}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($tb as $line){
        $line=trim($line);
        if(!preg_match("#^([0-9]+)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+)#",$line,$re)){continue;}
        $md=md5(serialize($re));
        $label="<span class='label label-default'>{sarg_ignore}</span>";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%' nowrap>{$re[1]}</td>";
        $time=$re[2]." ".$re[3];
        $stime=strtotime($time);
        $duration=distanceOfTimeInWords($stime,time());
        $time_text=$tpl->time_to_date($stime);
        $html[]="<td width='1%' nowrap>$time_text</td>";
        if(preg_match("#^HTTP\/#",$re[4])){
            $label="<span class='label label-primary'>{used}</span>";
        }
        $html[]="<td width='99%' nowrap>{$re[4]} $label</td>";
        $html[]="<td width='1%' nowrap>$duration</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";

     $html[]="</script>";


    echo $tpl->_ENGINE_parse_body($html);

}
function keytabexists(){
    $tfile=PROGRESS_DIR."/keytabexists.key";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("hacluster.php?keytabexists=yes");
    $data=@file_get_contents($tfile);
    $exists=intval($data);
    VERBOSE("$tfile == $data == [$exists]",__LINE__);
    if($exists==1){return true;}
    return false;
}
function wizard_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $KerberosUsername=$haClusterAD["KerberosUsername"];
    $kerberosRealm=strtoupper($haClusterAD["kerberosRealm"]);
    $myhostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $KerberosPassword=$haClusterAD["KerberosPassword"];
    $kerberosActiveDirectoryHost=$haClusterAD["kerberosActiveDirectoryHost"];


    $tt=explode(".",$myhostname);
    unset($tt[0]);
    $DEFAULT_DOMAIN=@implode(".",$tt);
    $DEFAULT_DOMAIN_UPPER=strtoupper($DEFAULT_DOMAIN);
    if($kerberosRealm==null){
        $kerberosRealm=$DEFAULT_DOMAIN_UPPER;
    }
    if($kerberosActiveDirectoryHost==null){
        $kerberosActiveDirectoryHost="dc1.$DEFAULT_DOMAIN";
    }
    $close="dialogInstance1.close();";
    $tpl->field_hidden("wizard","yes");
    $form[]=$tpl->field_email("KerberosUsername", "{username}", $KerberosUsername,true);
    $form[]=$tpl->field_password2("KerberosPassword", "{password}", $KerberosPassword,true);
    $form[]=$tpl->field_text("kerberosActiveDirectoryHost", "{ad_full_hostname}", $kerberosActiveDirectoryHost,true,"{ad_quick_1}");
    $form[]=$tpl->field_text("kerberosRealm", "{activedirectory_domain}", $kerberosRealm,true);


    $js=$tpl->framework_buildjs("/hacluster/server/keytab/wizard","hacluster.wizard.progress","hacluster.wizard.log","kerberos-auth-wizard","LoadAjax('table-kerberos','$page?table=yes');$close",null,null,"AsSystemAdministrator");


    $html[]="<div id='kerberos-auth-wizard'></div>";
    $html[]=$tpl->form_outside("{wizard}: {kerberos_authentication} ".php_uname("n"), @implode("\n", $form),"","{connect}",$js,"AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);

}
function wizard_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $admintracks=array();
    foreach ($_POST as $key=>$val){
        if($key<>"KerberosPassword") {
            $admintracks[] = "$key = $val";
        }
        $haClusterAD[$key]=$val;
    }

    admin_tracks_post("HaCluster: Link to AD using the wizard with ".@implode(" ",$admintracks));
    $haClusterADS=serialize($haClusterAD);
    $haClusterADE=base64_encode($haClusterADS);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD",$haClusterADE);

}
function reset_infos(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    admin_tracks("HaCluster: Reset Active Directory parameters");
    echo  $tpl->framework_buildjs("/hacluster/client/activedirectory/reset","ActiveDirectoryFeature.progress","ActiveDirectoryFeature.log","kerberos-ad-restart","LoadAjax('table-kerberos','$page?table=yes');");
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$kerberos_authentication=$tpl->_ENGINE_parse_body("{kerberos_authentication}");
    $kerberos_authentication_explain2=$tpl->_ENGINE_parse_body("{kerberos_authentication_explain2}");
    
    $html=$tpl->page_header("Active Directory &raquo;&raquo $kerberos_authentication",
    "fab fab fa-windows","$kerberos_authentication_explain2<div id='table-kerberos-buttons'></div>",
        "$page?table=yes","hacluster-kerberos","kerberos-ad-restart",false,"table-kerberos");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Active Directory/$kerberos_authentication",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function feature_disabled(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:1%'>";
    $wbutton[0]["name"]="{online_help}:wiki";
    $wbutton[0]["icon"]="fa-solid fa-square-question";
    $wbutton[0]["js"]="s_PopUpFull('https://wiki.articatech.com/proxy-service/hacluster/kerberos-manual',1024,768,'Wiki');";
    $html[]=$tpl->widget_grey("{disabled}","Active Directory",$wbutton,ico_microsoft);
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;padding-left: 15px'>";
    $html[]="<div class='alert alert-warning' style='margin-top:10px'>{feature_disabled}</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="Loadjs('$page?buttons=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function features(){
    $td_style=null;
    $page=CurrentPageName();
    $tpl=new template_admin();
    $FORM_FILLED=true;
    $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $KerberosUsername=$haClusterAD["KerberosUsername"];
    $kerberosRealm=strtoupper($haClusterAD["kerberosRealm"]);
    $KerberosSPN=$haClusterAD["KerberosSPN"];
    $myhostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $KerberosPassword=$haClusterAD["KerberosPassword"];
    $kerberosActiveDirectoryHost=$haClusterAD["kerberosActiveDirectoryHost"];
    $kerberosActiveDirectory2Host=$haClusterAD["kerberosActiveDirectory2Host"];
    $kerberosActiveDirectorySuffix=trim($haClusterAD["kerberosActiveDirectorySuffix"]);
    $KerberosSynCAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosSynCAD"));
    $KerberosLDAPS=intval($haClusterAD["KerberosLDAPS"]);
    $HaClusterDoNotUseAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDoNotUseAD"));
    $RESOLV_AD_HOSTNAME=true;

    if($HaClusterDoNotUseAD==1){
        feature_disabled();
        return false;
    }

    $tt=explode(".",$myhostname);
    unset($tt[0]);
    $DEFAULT_DOMAIN=@implode(".",$tt);
    $DEFAULT_DOMAIN_UPPER=strtoupper($DEFAULT_DOMAIN);
    $DEFAULT_DOMAIN_LOWER=strtolower($DEFAULT_DOMAIN);
    if($kerberosRealm==null){
        $FORM_FILLED=false;
        $kerberosRealm=$DEFAULT_DOMAIN_UPPER;
    }

    if($kerberosActiveDirectoryHost==null) {
        $sock = new sockets();
        $data = json_decode($sock->REST_API("/dns/resolvns/$DEFAULT_DOMAIN_LOWER"));
        if (!$data->Status) {
            $addomainns_error_resolv=str_replace("%s","<strong>$DEFAULT_DOMAIN_LOWER ($data->Error)</strong>",$tpl->_ENGINE_parse_body("{addomainns_error_resolv}"));
            $html[] = "<div class='alert alert-danger'>";
            $html[] = $addomainns_error_resolv;
            $UnboundEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled");
            if($UnboundEnabled==1){
                $addomain_ns_fwdrule=str_replace("%s","<strong>$DEFAULT_DOMAIN_LOWER</strong>",$tpl->_ENGINE_parse_body("{addomain_ns_fwdrule}"));

                $btn=$tpl->button_autnonome("{forward_zones}",
                    "Loadjs('fw.dns.forward.zone.php?zone-id-js=0&function=RefreshFwHaClusterPHP&domain=$DEFAULT_DOMAIN_LOWER')",ico_plus,"AsDockerAdmin",335,"btn-danger");
                $html[] ="<br>$addomain_ns_fwdrule";
                $html[] ="<div style='text-align:right;margin-top:30px'>";
                $html[] =$btn;
                $html[] ="</div>";
                $RESOLV_AD_HOSTNAME=false;
            }
            $html[] ="</div>";
        }else{
            $kerberosActiveDirectoryHost=$data->Info;
            if(strlen($kerberosActiveDirectoryHost)>3) {
                $haClusterAD["kerberosActiveDirectoryHost"] = $kerberosActiveDirectoryHost;
                $haClusterADEnc=base64_encode(serialize($haClusterAD));
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD",$haClusterADEnc);
            }
        }
    }


    if($kerberosActiveDirectoryHost==null){
        $FORM_FILLED=false;
        $kerberosActiveDirectoryHost="dc1.$DEFAULT_DOMAIN";
    }



    if($FORM_FILLED){
        echo "<div id='formflat'></div>
            <script>LoadAjax('formflat','$page?formflat=yes')</script>
        ";
        return true;
    }

    if($FORM_FILLED){
        if($KerberosSPN==null){
            $KerberosSPN="HTTP/$myhostname@$kerberosRealm";
        }
    }

    $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $hacluster_user_account=$tpl->_ENGINE_parse_body("{hacluster_user_account}");
    $hacluster_user_account=str_replace("%HOSTNAME%",$MyHostname,$hacluster_user_account);
    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:1%'>";


    $wbutton[0]["name"]="{online_help}:wiki";
    $wbutton[0]["icon"]="fa-solid fa-square-question";
    $wbutton[0]["js"]="s_PopUpFull('https://wiki.articatech.com/proxy-service/hacluster/kerberos-manual',1024,768,'Wiki');";
    $html[]=$tpl->widget_jaune("{error_miss_datas}","{fill_form}",$wbutton,"fa-solid fa-circle-1");
    $html[]="<div class='alert alert-warning' style='margin-top:10px'>$hacluster_user_account</div>";


    $html[]="</td>";
    $html[]="<td style='vertical-align:top;padding-left: 15px'>";

    if($RESOLV_AD_HOSTNAME) {
        if ($kerberosActiveDirectoryHost <> null) {
            $kerberosActiveDirectoryHostIP = $GLOBALS["CLASS_SOCKETS"]->gethostbyname($kerberosActiveDirectoryHost);
            if ($kerberosActiveDirectoryHostIP == $kerberosActiveDirectoryHost) {
                $adfullhostname_error_resolv = $tpl->_ENGINE_parse_body("{adfullhostname_error_resolv}");
                $adfullhostname_error_resolv = str_replace("%s", $kerberosActiveDirectoryHost, $adfullhostname_error_resolv);
                $html[] = "<div class='alert alert-danger'>$adfullhostname_error_resolv</div>";
            }
        }
    }
    if($KerberosUsername==null){
        $KerberosUsername="administrator@$DEFAULT_DOMAIN_LOWER";
    }

    $form[]=$tpl->field_email("KerberosUsername", "{username}", $KerberosUsername,true);
    $form[]=$tpl->field_password2("KerberosPassword", "{password}", $KerberosPassword,true);
    $form[]=$tpl->field_text("kerberosActiveDirectoryHost", "{ad_full_hostname}", $kerberosActiveDirectoryHost,true,"{ad_quick_1}");
    $form[]=$tpl->field_ad_suffix("kerberosActiveDirectorySuffix", "{ldap_suffix}", $kerberosActiveDirectorySuffix);
    $form[]=$tpl->field_text("kerberosActiveDirectory2Host", "{FQDNDC2}", $kerberosActiveDirectory2Host,false,"{ad_quick_1}");
    $form[]=$tpl->field_checkbox("KerberosLDAPS","{useSSL} (LDAPs)",$KerberosLDAPS);


    $form[]=$tpl->field_hidden("KerberosSPN","HTTP/$myhostname@$kerberosRealm");
    $form[]=$tpl->field_text("kerberosRealm", "{activedirectory_domain}", $kerberosRealm,true);
    $form[]=$tpl->field_checkbox("KerberosSynCAD", "{synchronize_time_with_ad}", $KerberosSynCAD,false);

    $html[]=$tpl->form_outside("{kerberos_authentication} ".php_uname("n"), @implode("\n", $form),"{hacluster_kerberos_explain}","{apply}","LoadAjax('table-kerberos','$page?table=yes');","AsSystemAdministrator",true);


    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="function RefreshFwHaClusterPHP(){";
    $html[]="LoadAjaxSilent('table-kerberos','$page?table=yes');";
    $html[]="}";
    $html[]="Loadjs('$page?buttons=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function ad_form_popup(){
    $td_style=null;
	$page=CurrentPageName();
	$tpl=new template_admin();
    $FORM_FILLED=true;
    $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
	$KerberosUsername=$haClusterAD["KerberosUsername"];
	$kerberosRealm=strtoupper($haClusterAD["kerberosRealm"]);
	$KerberosSPN=$haClusterAD["KerberosSPN"];
    $myhostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $KerberosPassword=$haClusterAD["KerberosPassword"];
    $kerberosActiveDirectoryHost=$haClusterAD["kerberosActiveDirectoryHost"];
    $kerberosActiveDirectory2Host=$haClusterAD["kerberosActiveDirectory2Host"];
    $kerberosActiveDirectorySuffix=trim($haClusterAD["kerberosActiveDirectorySuffix"]);
    $KerberosSynCAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosSynCAD"));
    $KerberosLDAPS=intval($haClusterAD["KerberosLDAPS"]);
    $UseNativeKerberosAuth=1;

    $tt=explode(".",$myhostname);
    unset($tt[0]);
    $DEFAULT_DOMAIN=@implode(".",$tt);
    $DEFAULT_DOMAIN_UPPER=strtoupper($DEFAULT_DOMAIN);
    if($kerberosRealm==null){
       $kerberosRealm=$DEFAULT_DOMAIN_UPPER;
    }
    if($kerberosActiveDirectoryHost==null){
       $kerberosActiveDirectoryHost="dc1.$DEFAULT_DOMAIN";
    }


    $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $hacluster_user_account=$tpl->_ENGINE_parse_body("{hacluster_user_account}");
    $hacluster_user_account=str_replace("%HOSTNAME%",$MyHostname,$hacluster_user_account);

    $html[]="<div class='alert alert-warning' style='margin-top:10px'>$hacluster_user_account</div>";

    if($kerberosActiveDirectoryHost<>null) {
        $kerberosActiveDirectoryHostIP = $GLOBALS["CLASS_SOCKETS"]->gethostbyname($kerberosActiveDirectoryHost);
        if ($kerberosActiveDirectoryHostIP == $kerberosActiveDirectoryHost) {
            $adfullhostname_error_resolv = $tpl->_ENGINE_parse_body("{adfullhostname_error_resolv}");
            $adfullhostname_error_resolv = str_replace("%s", $kerberosActiveDirectoryHost,$adfullhostname_error_resolv);
            $html[] = "<div class='alert alert-danger'>$adfullhostname_error_resolv</div>";
        }
    }


	$form[]=$tpl->field_email("KerberosUsername", "{username}", $KerberosUsername,true);
    $form[]=$tpl->field_password2("KerberosPassword", "{password}", $KerberosPassword,true);
    $form[]=$tpl->field_text("kerberosActiveDirectoryHost", "{ad_full_hostname}", $kerberosActiveDirectoryHost,true,"{ad_quick_1}");
    $form[]=$tpl->field_ad_suffix("kerberosActiveDirectorySuffix", "{ldap_suffix}", $kerberosActiveDirectorySuffix);
    $form[]=$tpl->field_text("kerberosActiveDirectory2Host", "{FQDNDC2}", $kerberosActiveDirectory2Host,false,"{ad_quick_1}");
    $form[]=$tpl->field_checkbox("KerberosLDAPS","{useSSL} (LDAPs)",$KerberosLDAPS);
    $form[]=$tpl->field_hidden("KerberosSPN","HTTP/$myhostname@$kerberosRealm");
    $form[]=$tpl->field_text("kerberosRealm", "{activedirectory_domain}", $kerberosRealm,true);
    $form[]=$tpl->field_checkbox("KerberosSynCAD", "{synchronize_time_with_ad}", $KerberosSynCAD,false);




    $html[]=$tpl->form_outside("{kerberos_authentication} ".php_uname("n"), $form,"{hacluster_kerberos_explain}","{apply}",
        "LoadAjax('table-kerberos','$page?table=yes');dialogInstance1.close();",
        "AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
}
function buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $HaClusterDoNotUseAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDoNotUseAD"));
    $buttons[]="<table style='width:1%'>";
    if($HaClusterDoNotUseAD==0) {
        $buttons[] = "<td style='padding-left: 10px'>";
        $buttons[] = $tpl->button_autnonome("{use_the_wizard}", "Loadjs('$page?wizard-js=yes')", "fad fa-hat-wizard", "AsSystemAdministrator", 0, "btn-info");
        $buttons[] = "</td>";

        $buttons[] = "<td style='padding-left: 10px'>";
        $buttons[] = $tpl->button_autnonome("{kerberos_ticket}", "Loadjs('$page?kerberos-js=yes')",
                "fad fa-ticket", "AsProxyMonitor");
        $buttons[] = "</td>";
        $Next = "btn-info";

        $buttons[] = "<td style='padding-left: 10px'>";
        $buttons[] = $tpl->button_autnonome("{supported_encryption_type}", "Loadjs('$page?kerberos-enc-js=yes')",
            "fa-solid fa-binary-circle-check", "AsProxyMonitor", 0, $Next);
        $buttons[] = "</td>";

        $Next="btn-info";


        $buttons[]="<td style='padding-left: 10px'>";
        $buttons[]=$tpl->button_autnonome("{reset}","Loadjs('$page?reset=yes')","fad fa-unlink","AsSystemAdministrator",0,"btn-danger");
        $buttons[]="</td>";

        $buttons[]="<td style='padding-left: 10px'>";
        $buttons[]=$tpl->button_autnonome("{disable} ActiveDirectory","Loadjs('$page?disable-ad=yes')",ico_unlink,"AsSystemAdministrator",0,"btn-warning");
        $buttons[]="</td>";

    }else{
        $buttons[] = "<td style='padding-left: 10px'>";
        $buttons[] = $tpl->button_autnonome("{enable} Active Directory", "Loadjs('$page?enable-ad=yes')", ico_microsoft, "AsSystemAdministrator");
        $buttons[] = "</td>";
    }

    $buttons[]="</tr>";
    $buttons[]="</table>";

    $tpl->form_add_button("{wizard}","Loadjs('$page?wizard-js=yes')");
    $tpl->form_add_button("{reset}","Loadjs('$page?reset=yes')");
    if($HaClusterDoNotUseAD==0) {
        $tpl->form_add_button("{disable} ActiveDirectory","Loadjs('$page?reset=yes')");
    }
    $btns= $tpl->_ENGINE_parse_body($buttons);

    $kerberos_authentication=$tpl->_ENGINE_parse_body("{kerberos_authentication}");
    $kerberos_authentication_explain2=$tpl->_ENGINE_parse_body("{kerberos_authentication_explain2}");

    $TINY_ARRAY["TITLE"]="Active Directory &raquo;&raquo $kerberos_authentication";
    $TINY_ARRAY["ICO"]="fab fab fa-windows";
    $TINY_ARRAY["EXPL"]="$kerberos_authentication_explain2";
    $TINY_ARRAY["BUTTONS"]=$btns;

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo $jstiny;
    return true;

}
function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $IP=new IP();
    foreach ($_POST as $key=>$val){
        if($key<>"KerberosPassword") {
            $admintracks[] = "$key = $val";
        }
    }


    $kerberosActiveDirectoryHost=$_POST["kerberosActiveDirectoryHost"];
    $ipaddr=gethostbyname($kerberosActiveDirectoryHost);
    if(!$IP->isValid($ipaddr)){
        echo $tpl->post_error("{CURLE_COULDNT_RESOLVE_HOST} $kerberosActiveDirectoryHost");
        admin_tracks("HaCluster: Failed to resolve Active Directory Host $kerberosActiveDirectoryHost" );
        return false;
    }
    $_POST["kerberosRealm"]=strtoupper(trim($_POST["kerberosRealm"]));
    $ldapserver=$_POST["kerberosActiveDirectoryHost"];

    if($_POST["kerberosRealm"]==null){
        $tblex=explode(".",$ldapserver);
        unset($tblex[0]);
        $_POST["kerberosRealm"]=strtoupper(@implode(".",$tblex));
    }


    if(!preg_match("#HTTP\/(.+?)@(.+?)#",$_POST["KerberosSPN"],$re)){
        $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        $_POST["KerberosSPN"]="HTTP/$MyHostname@{$_POST["kerberosRealm"]}";
    }else{
        $domain=trim($re[2]);
        if($domain==null){
            $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
            $_POST["KerberosSPN"]="HTTP/$MyHostname@{$_POST["kerberosRealm"]}";
        }
    }

    $KerberosUsername=$_POST["KerberosUsername"];
    $KerberosPassword=$_POST["KerberosPassword"];
    $KerberosLDAPS=$_POST["KerberosLDAPS"];
    $LdapPort=389;
    $UseSSL=0;
    $Uri="ldap://$ldapserver:$LdapPort";
    if($KerberosLDAPS==1){
        $LdapPort=636;
        $UseSSL=1;
        $Uri="ldaps://$ldapserver";
    }

    $kerberosActiveDirectorySuffix=trim($_POST["kerberosActiveDirectorySuffix"]);
    if($kerberosActiveDirectorySuffix==null){
        include_once(dirname(__FILE__)."/ressources/class.ActiveDirectoryRootDSE.inc");
        $ad_rootdse=new ad_rootdse($ldapserver,$LdapPort,$KerberosUsername,$KerberosPassword,$UseSSL);
        $_POST["kerberosActiveDirectorySuffix"]=$ad_rootdse->RootDSE();

    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerberosSynCAD",$_POST["KerberosSynCAD"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD",base64_encode(serialize($_POST)));


    $ldap_connection=@ldap_connect($Uri);
    if(!$ldap_connection){
        $DIAG[]="{Connection_Failed_to_connect_to_DC} $Uri";
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        echo $tpl->post_error(@implode("<br>", $DIAG));
        admin_tracks("HaCluster: Failed to LDAP connect to  Active Directory Host $kerberosActiveDirectoryHost" );
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
        admin_tracks("HaCluster: Failed to LDAP connect to  Active Directory Host $kerberosActiveDirectoryHost" );
        echo $tpl->post_error(@implode("<br>", $DIAG));
        return false;
    }

    admin_tracks("HaCluster: Save AD settings using the Form with ".@implode(" ",$admintracks));
    return true;

}

function flat_ticket(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $HaClusterDoNotUseAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDoNotUseAD"));
    if($HaClusterDoNotUseAD==1){
        echo $tpl->_ENGINE_parse_body($tpl->widget_grey("{commandline}", "{disabled}", "", ico_microsoft));
        return  false;
    }
    echo  flat_keytab();
    return true;

}
function keytab_error_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/keytab/status"));
    if (json_last_error()> JSON_ERROR_NONE) {
       echo $tpl->div_error(json_last_error_msg());
       return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    if(!property_exists($json,"Info")){
        echo $tpl->div_error("Info no such attribute");
        return false;
    }
    $json->Info->Error=str_replace("exit status 1","exit status 1\n", $json->Info->Error);
    $tb=explode("\n",$json->Info->Error);
    echo "<div style='padding:10px'>";
    foreach ($tb as $line){
        $class="";
        if(strpos($line,"error from ")>0){
            $class="text-danger";
        }
        if(strpos($line,"exit status 1")>0){
            $class="text-danger font-bold";
        }


        if(strpos($line,"unable to load plugin")>0){
            $class="text-warning";
        }
        if(strpos($line,"Preauthentication failed")>0){
            $class="text-danger font-bold";
        }
        echo "<div style='font-size:14px' class='$class'>$line</div>";
    }
echo "</div>";


    return true;
}

function flat_keytab():string{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/keytab/status"));
    if (json_last_error()> JSON_ERROR_NONE) {
        $wbutton[0]["name"] = "{online_help}:wiki";
        $wbutton[0]["icon"] = "fa-solid fa-square-question";
        $wbutton[0]["js"] = "s_PopUpFull('https://wiki.articatech.com/proxy-service/hacluster/kerberos-manual',1024,768,'Wiki');";
        return $tpl->_ENGINE_parse_body($tpl->widget_rouge("{error}", "JSON", $wbutton, "fa-solid fa-circle-1"));
    }
    if(!$json->Status){
        $wbutton[0]["name"] = "{online_help}:wiki";
        $wbutton[0]["icon"] = "fa-solid fa-square-question";
        $wbutton[0]["js"] = "s_PopUpFull('https://wiki.articatech.com/proxy-service/hacluster/kerberos-manual',1024,768,'Wiki');";
        return $tpl->_ENGINE_parse_body($tpl->widget_rouge("{error}", "WEB API", $wbutton, "fa-solid fa-circle-2"));
    }
    if(!property_exists($json,"Info")){
          $wbutton[0]["name"] = "{online_help}:wiki";
          $wbutton[0]["icon"] = "fa-solid fa-square-question";
          $wbutton[0]["js"] = "s_PopUpFull('https://wiki.articatech.com/proxy-service/hacluster/kerberos-manual',1024,768,'Wiki');";
          return $tpl->_ENGINE_parse_body($tpl->widget_rouge("{error}", "WEB API", $wbutton, "fa-solid fa-circle-3"));
    }
    if($json->Info->Status){
        $wbutton[0]["name"] = "{online_help}:wiki";
        $wbutton[0]["icon"] = "fa-solid fa-square-question";
        $wbutton[0]["js"] = "s_PopUpFull('https://wiki.articatech.com/proxy-service/hacluster/kerberos-manual',1024,768,'Wiki');";
        return $tpl->_ENGINE_parse_body($tpl->widget_vert("krb5.keytab", "{stored}", $wbutton));
    }
    $wbutton[0]["name"] = "{online_help}:wiki";
    $wbutton[0]["icon"] = "fa-solid fa-square-question";
    $wbutton[0]["js"] = "s_PopUpFull('https://wiki.articatech.com/proxy-service/hacluster/kerberos-manual',1024,768,'Wiki');";

    $page=CurrentPageName();
    $wbutton[1]["name"] = "{information}";
    $wbutton[1]["icon"] = ico_bug;
    $wbutton[1]["js"] = "Loadjs('$page?keytab-error=yes');";;

    return $tpl->_ENGINE_parse_body($tpl->widget_rouge("{error}", "Keytab error", $wbutton, "fa-solid fa-circle-4"));

}

function DownloadKeyTab(){
    $page=CurrentPageName();
    $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $KerberosUsername=$haClusterAD["KerberosUsername"];
    $kerberosRealm=strtoupper($haClusterAD["kerberosRealm"]);
    $myhostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $data=BuildPowerShellKTPass($kerberosRealm,$myhostname,$KerberosUsername);
    $timestamp =time();
    $tsstring = gmdate('D, d M Y H:i:s ') . 'GMT';
    header("Content-Length: ".strlen($data));
    header('Content-type: application/x-powershell');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$myhostname-keytab.ps1\"");
    header("Cache-Control: no-cache, must-revalidate");
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
    header("Last-Modified: $tsstring");
    ob_clean();
    flush();
    echo $data;
}


function form_flat(){
    $page=CurrentPageName();
    $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $KerberosUsername=$haClusterAD["KerberosUsername"];
    $kerberosRealm=strtoupper($haClusterAD["kerberosRealm"]);
    $myhostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $KerberosPassword=$haClusterAD["KerberosPassword"];
    $kerberosActiveDirectoryHost=$haClusterAD["kerberosActiveDirectoryHost"];

    $kerberosActiveDirectorySuffix=trim($haClusterAD["kerberosActiveDirectorySuffix"]);
    $KerberosLDAPS=intval($haClusterAD["KerberosLDAPS"]);
    $tpl=new template_admin();
    $js="document.location.href='/$page?download-ps1=yes'";
    $button = $tpl->button_autnonome("{download_powershell_script}",$js,
        ico_download, "AsProxyMonitor");

    $html[]=$tpl->div_explain("{download_powershell_script}||{download_powershell_script_explain}<br>{download_powershell_script_exp}<br>
<p style=\"font-family:'Courier New';color:black;background-color:#EEF2FE;border:1px solid #c0c0c0; font-weight:bold;padding: 9px;border-radius:5px;margin:5px;font-size: initial\">Unblock-File \"$myhostname.int-keytab.ps1\"</p>
    <div style='text-align:right;margin-top:20px'>$button</div>");


    if (!is_file("/home/artica/PowerDNS/Cluster/storage/krb5.keytab")) {
           $html[] = "<div class='alert alert-danger'>{KRB5_FILE_MISSING}</div>";
    }




    $tpl->table_form_field_js("Loadjs('$page?ad-form-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{ad_full_hostname}",$kerberosActiveDirectoryHost,ico_microsoft);
    $tpl->table_form_field_text("{ldap_suffix}",$kerberosActiveDirectorySuffix,ico_earth);
    $tpl->table_form_field_text("{username}",$KerberosUsername,ico_admin);
    if($KerberosUsername<>null){
        if($KerberosPassword<>null) {
            $HASH["LDAP_DN"] = $KerberosUsername;
            $HASH["LDAP_PASSWORD"] = $KerberosPassword;
            $HASH["LDAP_SUFFIX"] = $kerberosActiveDirectorySuffix;
            $HASH["LDAP_SERVER"] = $kerberosActiveDirectoryHost;
            $HASH["LDAP_PORT"] = 389;
            $ActiveDirectory = new ActiveDirectory(0, $HASH);
            $DN = $ActiveDirectory->_Get_dn_userid($KerberosUsername);
            $KERBSPN = 0;
            if ($DN <> null) {
                $tpl->table_form_field_text("{ldap_user_dn}",$DN,ico_user);
                $Hash = $ActiveDirectory->DumpDN($DN);

                for ($i = 0; $i < $Hash["serviceprincipalname"]["count"]; $i++) {
                    $KERBSPN++;
                    $tpl->table_form_field_text("{KERBSPN} AD ($i)", $Hash["serviceprincipalname"][$i], ico_user);
                }

                for ($i = 0; $i < $Hash["userprincipalname"]["count"]; $i++) {
                    $KERBSPN++;
                    $tpl->table_form_field_text("{userprincipalname} AD ($i)", $Hash["serviceprincipalname"][$i], ico_user);

                }

            }
        }

    }
    $tpl->table_form_field_bool("{useSSL} (LDAPs)",$KerberosLDAPS,ico_ssl);

    $EnablehaclusterAutoRenew=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablehaclusterAutoRenew"));
    $tpl->table_form_field_js("Loadjs('$page?autorenew-js=yes')","AsSystemAdministrator");
    if($EnablehaclusterAutoRenew==0){
        $tpl->table_form_field_bool("{autorenew_ticket}",0,ico_certificate);
    }else{
        $tpl->table_form_field_text("{autorenew_ticket}","{each_day} 1h45",ico_certificate);
    }




    $html[]=$tpl->table_form_compile();
    $zpage[]="<table style='width:100%'>";
    $zpage[]="<tr>";
    $zpage[]="<td style='width:350px;vertical-align:top;padding:5px;'>";
    $zpage[]="<div id='ticket-status'></div>";
    $zpage[]="<div style='margin-top:10px'>";
    $classname="style=width:336px;display:block;class=btn-success";
    $zpage[]=$tpl->button_upload("{upload_keytab}",$page,$classname);
    $zpage[]="</div>";
    $zpage[]="</td>";
    $zpage[]="<td style='width:99%;vertical-align:top;padding:5px;'>";
    $zpage[]=@implode("\n",$html);
    $zpage[]="</td>";
    $zpage[]="</tr>";
    $zpage[]="</table>";

    $zrefresh=$tpl->RefreshInterval_js("ticket-status",$page,"flat-ticket=yes");
    $zpage[]="<script>$zrefresh
    Loadjs('$page?buttons=yes');
</script>";

    echo $tpl->_ENGINE_parse_body($zpage);

}

function file_uploaded(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $file=$_GET["file-uploaded"];

    $sock=new sockets();
    $json=json_decode($sock->REST_API("/hacluster/server/keytab/install/$file"));

    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;

    }

    echo "LoadAjax('table-kerberos','$page?table=yes');";
    return admin_tracks("KeyTab was successfully uploaded to Haclutser load-balancer");
}
