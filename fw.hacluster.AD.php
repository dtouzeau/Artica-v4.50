<?php
// stamp 01/11/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/ressources/PowerShellKTPass.inc.php");
include_once(dirname(__FILE__)."/ressources/class.kerberos.spn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["disable-ad"])){disable_ad_ask();exit;}
if(isset($_GET["enable-ad"])){enable_ad();exit;}
if(isset($_GET["autorenew-js"])){autorenew_js();exit;}
if(isset($_GET["autorenew-popup"])){autorenew_popup();exit;}
if(isset($_POST["EnablehaclusterAutoRenew"])){autorenew_save();exit;}
if(isset($_GET["LockActiveDirectoryToKerberosBasic-js"])){LockActiveDirectoryToKerberosBasic_js();exit;}
if(isset($_GET["LockActiveDirectoryToKerberosBasic-popup"])){LockActiveDirectoryToKerberosBasic_popup();exit;}
if(isset($_POST["LockActiveDirectoryToKerberosBasic"])){LockActiveDirectoryToKerberosBasic_save();exit;}
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

function kerberos_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{kerberos_ticket}","$page?kerberos-popup=yes");
}
function LockActiveDirectoryToKerberosBasic_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{LockActiveDirectoryToKerberosBasic}",
        "$page?LockActiveDirectoryToKerberosBasic-popup=yes",650);
}
function keytab_error_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{kerberos_ticket}","$page?keytab-error-popup=yes");
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
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
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
    // GET /hacluster/server/keytab/info : sortie de `klist -kte` de la keytab du
    // cluster ; remplace framework/hacluster.php?kerberos-ticket (fichier relu).
    $kt=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/keytab/info"));
    $data=(is_object($kt) && isset($kt->Info->Klist))?(string)$kt->Info->Klist:"";
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
        $html[]="<td style='width:1%' nowrap>{$re[1]}</td>";
        $time=$re[2]." ".$re[3];
        $stime=strtotime($time);
        $duration=distanceOfTimeInWords($stime,time());
        $time_text=$tpl->time_to_date($stime);
        $html[]="<td style='width:1%' nowrap>$time_text</td>";
        if(preg_match("#^HTTP\/#",$re[4])){
            $label="<span class='label label-primary'>{used}</span>";
        }
        $html[]="<td style='width:99%' nowrap>$re[4] $label</td>";
        $html[]="<td style='width:1%' nowrap>$duration</td>";
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
    // GET /hacluster/server/keytab/info : keytab ou krb5.crypt deposee (sans kinit) ;
    // remplace framework/hacluster.php?keytabexists (fichier relu).
    $kt=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/keytab/info"));
    $exists=is_object($kt) && isset($kt->Info->Exists) && $kt->Info->Exists;
    VERBOSE("keytab exists == [".($exists?1:0)."]",__LINE__);
    return $exists;
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

    $hcadv=array("user"=>$KerberosUsername,"haspass"=>strlen((string)$KerberosPassword)>0,"host"=>$kerberosActiveDirectoryHost,
        "host2"=>$kerberosActiveDirectory2Host,"suffix"=>(strpos((string)$kerberosActiveDirectorySuffix,"=")===false?"":$kerberosActiveDirectorySuffix),
        "realm"=>$kerberosRealm,"ldaps"=>$KerberosLDAPS,"synctime"=>$KerberosSynCAD,"myhostname"=>$myhostname);
    $html[]=hcad_setup_form($tpl,$hcadv);


    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="function RefreshFwHaClusterPHP(){";
    $html[]="LoadAjaxSilent('table-kerberos','$page?table=yes');";
    $html[]="}";
    $html[]="Loadjs('$page?buttons=yes');";
    $html[]="</script>";
    echo hcad_setup_style().$tpl->_ENGINE_parse_body($html).hcad_setup_js($tpl,$hcadv);
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
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
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
    // Champ laisse tel quel (masque ou absent) : on garde le mot de passe enregistre.
    if(!isset($_POST["KerberosPassword"]) || $_POST["KerberosPassword"]==="" || $_POST["KerberosPassword"]==="%%MASKED%%"){
        $prev=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
        $_POST["KerberosPassword"]=is_array($prev)?(string)($prev["KerberosPassword"] ?? ""):"";
        if($_POST["KerberosPassword"]===""){echo $tpl->post_error("{password}: {cannot_be_a_null_value}");return false;}
    }
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

    // Un suffixe qui n'est pas un DN ("false" laisse par une detection ratee :
    // RootDSE() rend false, enregistre tel quel puis relu "false" cote Go) est
    // traite comme vide, sinon il part sur les noeuds (squid -b false).
    $kerberosActiveDirectorySuffix=trim((string)$_POST["kerberosActiveDirectorySuffix"]);
    if(strpos($kerberosActiveDirectorySuffix,"=")===false){$kerberosActiveDirectorySuffix="";}

    // Les identifiants sont verifies AVANT tout enregistrement : un echec
    // n'enregistre rien (ticket Lemnia 2026-11-09). La verification est faite
    // par articarest (POST /hacluster/kerberos/validate) : sur un refus 52e d'une
    // forme user@domain, il essaie les autres formes (UPN du domaine reel,
    // NetBIOS) et rend l'identifiant, le royaume et le suffixe a retenir.
    $vreq=array("username"=>$KerberosUsername,"password"=>$KerberosPassword,"host"=>$ldapserver,
        "ldaps"=>intval($KerberosLDAPS),"suffix"=>$kerberosActiveDirectorySuffix);
    $v=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/hacluster/kerberos/validate",$vreq,60),true);
    if(!is_array($v) || !isset($v["ok"])){
        echo $tpl->post_error("{login_Failed_to_connect_to_DC} $KerberosUsername<br>Artica API: no answer from /hacluster/kerberos/validate");
        return false;
    }
    if(!$v["ok"]){
        $DIAG=array();
        $DIAG[]="{login_Failed_to_connect_to_DC} $KerberosUsername";
        if(trim((string)($v["hint"] ?? ""))<>""){$DIAG[]="<strong>".hcad_h($v["hint"])."</strong>";}
        $DIAG[]=hcad_h((string)($v["error"] ?? ""));
        if(isset($v["attempts"]) && is_array($v["attempts"]) && count($v["attempts"])>1){
            $tried=array();
            foreach($v["attempts"] as $at){$tried[]=hcad_h((string)($at["login"] ?? ""));}
            $DIAG[]="{hcad_f_tried}: ".implode(", ",$tried);
        }
        admin_tracks("HaCluster: AD settings not saved, LDAP bind refused by $kerberosActiveDirectoryHost for $KerberosUsername" );
        echo $tpl->post_error(@implode("<br>", $DIAG));
        return false;
    }
    // Compte accepte : appliquer ce que l'annuaire a confirme.
    $fixes=array();
    if(!empty($v["changed"]) && trim((string)$v["login"])<>""){
        $fixes[]=array("{username}",$KerberosUsername,(string)$v["login"]);
        $KerberosUsername=(string)$v["login"];
        $_POST["KerberosUsername"]=$KerberosUsername;
    }
    $vrealm=strtoupper(trim((string)($v["realm"] ?? "")));
    if($vrealm<>"" && $vrealm<>$_POST["kerberosRealm"]){
        $fixes[]=array("{activedirectory_domain}",$_POST["kerberosRealm"],$vrealm);
        $_POST["kerberosRealm"]=$vrealm;
        $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        $_POST["KerberosSPN"]="HTTP/$MyHostname@$vrealm";
    }
    $vsuffix=trim((string)($v["suffix"] ?? ""));
    if(strpos($vsuffix,"=")!==false && strcasecmp($vsuffix,$kerberosActiveDirectorySuffix)<>0){
        if($kerberosActiveDirectorySuffix<>""){$fixes[]=array("{ldap_suffix}",$kerberosActiveDirectorySuffix,$vsuffix);}
        $kerberosActiveDirectorySuffix=$vsuffix;
    }
    if(count($fixes)>0){
        $_SESSION["HCAD_AUTOFIX"]=$fixes;
        $ft=array();
        foreach($fixes as $f){$ft[]=strip_tags($f[1])." -> ".$f[2];}
        admin_tracks("HaCluster: AD settings corrected automatically: ".implode(", ",$ft));
    }

    $_POST["kerberosActiveDirectorySuffix"]=$kerberosActiveDirectorySuffix;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerberosSynCAD",$_POST["KerberosSynCAD"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD",base64_encode(serialize($_POST)));

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
    $tpl=new template_admin();
    $m=hcad_model(isset($_GET["spn-refresh"]));

    // Colonne gauche inchangee (demande utilisateur) : tuile d'etat du keytab
    // rafraichie toutes les 3 s et bouton d'import du keytab.
    $zpage[]="<table style='width:100%'>";
    $zpage[]="<tr>";
    $zpage[]="<td style='width:350px;vertical-align:top;padding:5px;'>";
    $zpage[]="<div id='ticket-status'></div>";
    $zpage[]="<div style='margin-top:10px'>";
    // display:flex (pas block) : garde le centrage vertical de .abt (inline-flex + align-items)
    $classname="style=width:336px;display:flex;justify-content:center;class=btn-success";
    $zpage[]=$tpl->button_upload("{upload_keytab}",$page,$classname);
    $zpage[]="</div>";
    $zpage[]="</td>";
    $zpage[]="<td style='width:99%;vertical-align:top;padding:5px 5px 5px 15px;'>";
    $zpage[]="<div class='ak hcad'>";
    $zpage[]=hcad_autofix_banner();
    $zpage[]=hcad_banner($m);
    $zpage[]=hcdx_diagnosis($m);
    $zpage[]=hcad_methods($m);
    $zpage[]=hcad_spnfix($m);
    $zpage[]="<div class='hcad-cols'>";
    $zpage[]=hcad_connection($m);
    $zpage[]=hcad_options($m);
    $zpage[]="</div>";
    $zpage[]="</div>";
    $zpage[]="</td>";
    $zpage[]="</tr>";
    $zpage[]="</table>";

    $zrefresh=$tpl->RefreshInterval_js("ticket-status",$page,"flat-ticket=yes");
    $script="<script>$zrefresh\nLoadjs('$page?buttons=yes');</script>";

    echo hcad_style().hcdx_style().$tpl->_ENGINE_parse_body($zpage).hcad_js().$script;
}

// Formulaire de mise en service (Stitch « load balancer form »). Les champs portent
// les identifiants md5(nom.suffixid) de template_admin : fw.browse-suffix.php les
// retrouve ainsi (utilisateur, mot de passe, hote, LDAPS) comme avec field_*.
function hcad_setup_form($tpl,$v):string{
    $page=CurrentPageName();
    $sid=$tpl->suffixid;
    $id=function($n) use ($sid){return md5($n.$sid);};
    $h="hcad_h";
    $stored=$v["haspass"];
    $pwval=$stored?"%%MASKED%%":"";
    $suffixjs="Loadjs('fw.browse-suffix.php?js=yes&field-id=".$id("kerberosActiveDirectorySuffix")."&title=LDAP&suffixid=$sid')";
    $o=array();
    $o[]="<form class='ak hcadf' id='hcadf-$sid' autocomplete='off' onsubmit='return false'>";

    // 1. Compte de service
    $o[]="<section class='ak-card ak-card-pad hcadf-sec'>";
    $o[]=hcadf_head(1,"{hcad_f_account_t}","{hcad_f_account_s}","<span class='ak-pill ak-ok'>{hcad_f_required}</span>");
    $o[]=hcadf_field($id("KerberosUsername"),"{username}",true,"<input class='ak-in' type='text' id='".$id("KerberosUsername")."' value='".$h($v["user"])."' spellcheck='false'>","fa-user","{hcad_f_user_help}");
    $o[]="<div class='hcadf-row'>";
    $o[]=hcadf_field($id("KerberosPassword"),"{password}",true,"<input class='ak-in' type='password' id='".$id("KerberosPassword")."' value='$pwval' autocomplete='new-password' onfocus=\"if(this.value==='%%MASKED%%'){this.value='';}\" oninput='HcadfMatch()'>"
        ."<button type='button' class='hcadf-eye' aria-label='{hcad_f_show_pw}' OnClick=\"HcadfEye('".$id("KerberosPassword")."',this)\"><i class='fas fa-eye'></i></button>","fa-key",$stored?"{hcad_f_pw_keep}":"");
    $o[]=hcadf_field($id("KerberosPassword2"),"{hcad_f_confirm}",true,"<input class='ak-in' type='password' id='".$id("KerberosPassword2")."' value='$pwval' autocomplete='new-password' onfocus=\"if(this.value==='%%MASKED%%'){this.value='';}\" oninput='HcadfMatch()'>"
        ."<span class='hcadf-match' id='hcadf-match-$sid' aria-hidden='true'></span>","fa-lock","");
    $o[]="</div>";
    $o[]="</section>";

    // 2. Controleurs de domaine et royaume
    $o[]="<section class='ak-card ak-card-pad hcadf-sec'>";
    $o[]=hcadf_head(2,"{hcad_f_topo_t}","{hcad_f_topo_s}","<span class='ak-chip'>LDAP 389 &middot; LDAPS 636</span>");
    $o[]=hcadf_field($id("kerberosActiveDirectoryHost"),"{ad_full_hostname}",true,"<input class='ak-in' type='text' id='".$id("kerberosActiveDirectoryHost")."' value='".$h($v["host"])."' spellcheck='false'>","fa-server","{hcad_f_host_help}");
    $o[]=hcadf_field($id("kerberosActiveDirectorySuffix"),"{ldap_suffix}",false,"<input class='ak-in' type='text' id='".$id("kerberosActiveDirectorySuffix")."' value='".$h($v["suffix"])."' spellcheck='false'>"
        ."<button type='button' class='ak-btn hcadf-browse' OnClick=\"$suffixjs\"><i class='fas fa-sitemap'></i> {browse}...</button>","fa-sitemap","{hcad_f_suffix_help}");
    $o[]=hcadf_field($id("kerberosActiveDirectory2Host"),"{FQDNDC2} <span class='ak-muted'>({hcad_f_optional})</span>",false,"<input class='ak-in' type='text' id='".$id("kerberosActiveDirectory2Host")."' value='".$h($v["host2"])."' spellcheck='false'>","fa-database","{hcad_f_dc2_help}");
    $o[]=hcadf_field($id("kerberosRealm"),"{activedirectory_domain}",true,"<input class='ak-in hcadf-upper' type='text' id='".$id("kerberosRealm")."' value='".$h($v["realm"])."' spellcheck='false' oninput='this.value=this.value.toUpperCase()'>","fa-globe","{hcad_f_realm_help}");
    $o[]="</section>";

    // 3. Securite du transport et horloge
    $o[]="<section class='ak-card ak-card-pad hcadf-sec'>";
    $o[]=hcadf_head(3,"{hcad_f_sec_t}","{hcad_f_sec_s}","<span class='ak-chip'><i class='fas fa-clock-rotate-left'></i> {hcad_f_skew}</span>");
    $o[]=hcadf_toggle($id("KerberosLDAPS"),"{useSSL} (LDAPs)","<span class='ak-pill ak-ok'>{hcad_f_recommended}</span>","{hcad_f_ldaps_help}",$v["ldaps"]);
    $o[]=hcadf_toggle($id("KerberosSynCAD"),"{synchronize_time_with_ad}","<span class='ak-pill ak-warn'>{hcad_f_kerb_req}</span>","{hcad_f_sync_help}",$v["synctime"]);
    $o[]="</section>";

    // Barre d'actions
    $o[]="<div class='ak-card ak-card-pad hcadf-bar'>";
    $o[]="<div class='ak-banner ak-err hcadf-err' id='hcadf-err-$sid' role='alert' hidden></div>";
    $o[]="<div class='hcadf-acts'>";
    $o[]="<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?kerberos-enc-js=yes')\"><i class='fas fa-shield-halved'></i> {supported_encryption_type}</button>";
    $o[]="<button type='button' class='ak-btn ak-btn-danger' OnClick=\"Loadjs('$page?disable-ad=yes')\"><i class='fas fa-power-off'></i> {hcad_f_disable_ad}</button>";
    $o[]="<span class='ak-grow'></span>";
    $o[]="<button type='button' class='ak-btn' OnClick=\"HcadfReset()\"><i class='fas fa-rotate'></i> {hcad_f_reset}</button>";
    $o[]="<button type='button' class='ak-btn ak-btn-primary ak-btn-lg' id='hcadf-go-$sid' OnClick=\"HcadfSubmit()\"><i class='fas fa-plug-circle-check'></i> {hcad_f_submit}</button>";
    $o[]="</div>";
    $o[]="</div>";
    $o[]="</form>";
    return implode("",$o);
}

function hcadf_head($n,$title,$sub,$side):string{
    return "<div class='hcadf-head'><span class='hcadf-num' aria-hidden='true'>$n</span>"
        ."<div class='ak-grow'><div class='hcadf-t'>$title</div><div class='hcadf-s'>$sub</div></div>$side</div>";
}

function hcadf_field($id,$label,$req,$control,$icon,$help):string{
    $star=$req?" <span class='hcadf-req' aria-hidden='true'>*</span>":"";
    $help=$help<>""?"<div class='ak-help'>$help</div>":"";
    return "<div class='ak-f hcadf-f' data-req='".($req?"1":"0")."'><label for='$id'>$label$star</label>"
        ."<div class='hcadf-ctl'><i class='fas $icon hcadf-ico' aria-hidden='true'></i>$control</div>"
        ."<div class='ak-ferr' hidden>{hcad_f_missing}</div>$help</div>";
}

// Interrupteur + case cachee portant l'identifiant attendu (fw.browse-suffix lit .checked).
function hcadf_toggle($id,$label,$badge,$help,$on):string{
    $chk=intval($on)==1;
    return "<div class='hcadf-tog'><div class='ak-grow'><div class='hcadf-togl' id='$id-l'>$label $badge</div><div class='ak-help'>$help</div></div>"
        ."<input type='checkbox' id='$id' hidden".($chk?" checked":"").">"
        ."<button type='button' role='switch' class='ak-sw' aria-labelledby='$id-l' aria-checked='".($chk?"true":"false")."' OnClick=\"HcadfSw(this,'$id')\"></button></div>";
}

function hcad_setup_js($tpl,$v):string{
    $page=CurrentPageName();
    $sid=$tpl->suffixid;
    $ids=array();
    foreach(array("KerberosUsername","KerberosPassword","KerberosPassword2","kerberosActiveDirectoryHost","kerberosActiveDirectorySuffix","kerberosActiveDirectory2Host","kerberosRealm","KerberosLDAPS","KerberosSynCAD") as $n){$ids[$n]=md5($n.$sid);}
    $J=json_encode($ids);
    $host=json_encode((string)$v["myhostname"]);
    $mismatch=json_encode($tpl->javascript_parse_text("{hcad_f_pw_mismatch}"));
    $short=json_encode($tpl->javascript_parse_text("{hcad_f_pw_short}"));
    $checking=json_encode($tpl->javascript_parse_text("{hcad_f_checking}"));
    return "<script>
(function(){
var I=$J, H=$host, MIS=$mismatch, SHORT=$short, CHK=$checking, SID='$sid';
function el(n){return document.getElementById(I[n]);}
function err(msg){var e=document.getElementById('hcadf-err-'+SID); if(!e){return;} if(!msg){e.hidden=true;e.innerHTML='';return;} e.innerHTML=msg; e.hidden=false;}
window.HcadfEye=function(id,b){var x=document.getElementById(id); if(!x){return;} if(x.value==='%%MASKED%%'){x.value='';} x.type=x.type==='password'?'text':'password';}
window.HcadfSw=function(b,id){var on=b.getAttribute('aria-checked')!=='true'; b.setAttribute('aria-checked',on?'true':'false'); document.getElementById(id).checked=on;}
window.HcadfMatch=function(){var a=el('KerberosPassword').value,c=el('KerberosPassword2').value,m=document.getElementById('hcadf-match-'+SID); if(!m){return;}
  m.innerHTML=(a.length>0 && a===c && a!=='%%MASKED%%')?'<i class=\"fas fa-check\"></i>':''; }
var init={}; for(var k in I){var x=document.getElementById(I[k]); if(x){init[k]=(x.type==='checkbox')?x.checked:x.value;}}
window.HcadfReset=function(){for(var k in init){var x=el(k); if(!x){continue;} if(x.type==='checkbox'){x.checked=init[k]; var s=x.nextElementSibling; if(s){s.setAttribute('aria-checked',init[k]?'true':'false');}} else {x.value=init[k];}}
  err(''); var f=document.querySelectorAll('#hcadf-'+SID+' .ak-ferr'); for(var i=0;i<f.length;i++){f[i].hidden=true;} var b=document.querySelectorAll('#hcadf-'+SID+' .ak-bad'); for(var j=0;j<b.length;j++){b[j].classList.remove('ak-bad');} HcadfMatch();}
window.HcadfSubmit=function(){
  err(''); var ok=true;
  var req=document.querySelectorAll('#hcadf-'+SID+' .hcadf-f');
  for(var i=0;i<req.length;i++){var f=req[i], inp=f.querySelector('input'), fe=f.querySelector('.ak-ferr');
    var bad=f.getAttribute('data-req')==='1' && inp && inp.value.trim()==='';
    if(inp){inp.classList.toggle('ak-bad',bad);} if(fe){fe.hidden=!bad;} if(bad){ok=false;}}
  var p=el('KerberosPassword').value, c=el('KerberosPassword2').value;
  if(p!=='%%MASKED%%' || c!=='%%MASKED%%'){
    if(p!==c){err(MIS);return;}
    if(p.length>0 && p.length<5){err(SHORT);return;}
  }
  if(!ok){return;}
  var realm=el('kerberosRealm').value.trim().toUpperCase();
  var X=new XHRConnection();
  X.appendData('KerberosUsername',encodeURIComponent(el('KerberosUsername').value.trim()));
  if(p!=='%%MASKED%%'){X.appendData('KerberosPassword',encodeURIComponent(p));}
  X.appendData('kerberosActiveDirectoryHost',encodeURIComponent(el('kerberosActiveDirectoryHost').value.trim()));
  X.appendData('kerberosActiveDirectorySuffix',encodeURIComponent(el('kerberosActiveDirectorySuffix').value.trim()));
  X.appendData('kerberosActiveDirectory2Host',encodeURIComponent(el('kerberosActiveDirectory2Host').value.trim()));
  X.appendData('kerberosRealm',encodeURIComponent(realm));
  X.appendData('KerberosSPN',encodeURIComponent('HTTP/'+H+'@'+realm));
  X.appendData('KerberosLDAPS',encodeURIComponent(el('KerberosLDAPS').checked?1:0));
  X.appendData('KerberosSynCAD',encodeURIComponent(el('KerberosSynCAD').checked?1:0));
  var go=document.getElementById('hcadf-go-'+SID); if(go){go.disabled=true;go.setAttribute('aria-busy','true');}
  var note=document.getElementById('hcadf-err-'+SID); if(note){note.className='ak-banner ak-info hcadf-err'; note.innerHTML=CHK; note.hidden=false;}
  X.sendAndLoad('$page','POST',function(obj){
    var r=String(obj.responseText||''); if(go){go.disabled=false;go.removeAttribute('aria-busy');}
    if(note){note.className='ak-banner ak-err hcadf-err';}
    if(r.trim().length<=3){err('');LoadAjax('table-kerberos','$page?table=yes');return;}
    err(r.indexOf('jserror:')===0?r.substring(8):r);
  });
};
})();
</script>";
}

function hcad_setup_style():string{
    if(isset($GLOBALS["HCADF_STYLE"])){return "";}
    $GLOBALS["HCADF_STYLE"]=true;
    return "<style>
.hcadf{display:flex;flex-direction:column;gap:20px;max-width:1100px}
.hcadf-head{display:flex;align-items:flex-start;gap:14px;padding-bottom:16px;margin-bottom:18px;border-bottom:1px solid #edf1f4}
.hcadf-num{flex:0 0 auto;width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;background:var(--ak-soft,rgba(16,185,129,.12));color:#00504a;border:1px solid var(--ak-line,rgba(16,185,129,.35))}
.hcadf-t{font-size:17px;font-weight:600;color:#111d23}
.hcadf-s{font-size:14px;color:#3d4947;margin-top:2px}
.hcadf-f{margin-bottom:18px}
.hcadf-f label{font-size:14px;font-weight:600;color:#111d23;margin-bottom:6px;display:block}
.hcadf-req{color:#ba1a1a}
.hcadf-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.hcadf-ctl{position:relative;display:flex;gap:10px;align-items:center}
.hcadf-ico{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6d7a77;font-size:14px;pointer-events:none}
.hcadf-ctl .ak-in{padding-left:36px;font-size:14px}
.hcadf-upper{text-transform:uppercase}
.hcadf-eye{position:absolute;right:6px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#6d7a77;width:30px;height:30px;border-radius:6px;cursor:pointer}
.hcadf-eye:hover,.hcadf-eye:focus-visible{color:#111d23;background:#f1f5f9}
.hcadf-match{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#0a8059}
.hcadf-browse{flex:0 0 auto;height:40px}
.hcadf-tog{display:flex;align-items:center;gap:16px;padding:14px 16px;border:1px solid #e2e8f0;border-radius:10px;background:#fbfdfd;margin-bottom:12px}
.hcadf-togl{font-size:15px;font-weight:600;color:#111d23;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.hcadf-acts{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.hcadf-err{margin-bottom:14px}
@media(max-width:1200px){.hcadf-row{grid-template-columns:1fr}}
</style>";
}

// Avis a usage unique : valeurs corrigees par Save() apres un refus 52e.
function hcad_autofix_banner():string{
    if(!isset($_SESSION["HCAD_AUTOFIX"]) || !is_array($_SESSION["HCAD_AUTOFIX"])){return "";}
    $fixes=$_SESSION["HCAD_AUTOFIX"];
    unset($_SESSION["HCAD_AUTOFIX"]);
    $rows=array();
    foreach($fixes as $f){
        $rows[]="<li>{$f[0]} : <span class='ak-mono'>".hcad_h($f[1])."</span> &rarr; <strong class='ak-mono'>".hcad_h($f[2])."</strong></li>";
    }
    return "<div class='ak-banner ak-info hcad-banner' role='status'><i class='fas fa-wand-magic-sparkles hcad-bico' aria-hidden='true'></i>"
        ."<div class='ak-banner-m'><div class='hcad-btitle'>{hcad_autofix_title}</div><div class='hcad-bsub'>{hcad_autofix_explain}</div>"
        ."<ul class='hcad-fixlist'>".implode("",$rows)."</ul></div></div>";
}

function hcad_h($s):string{return htmlspecialchars((string)$s,ENT_QUOTES);}

// Un seul modele pour tous les widgets : chaque source est lue UNE fois.
function hcad_model($spnRefresh=false):array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $ad=$sock->unserializeb64($sock->GET_INFO("HaClusterAD"));
    if(!is_array($ad)){$ad=array();}
    $m=array();
    $m["host"]=trim((string)($ad["kerberosActiveDirectoryHost"] ?? ""));
    $m["host2"]=trim((string)($ad["kerberosActiveDirectory2Host"] ?? ""));
    $m["realm"]=strtoupper(trim((string)($ad["kerberosRealm"] ?? "")));
    $m["suffix"]=trim((string)($ad["kerberosActiveDirectorySuffix"] ?? ""));
    if(strpos($m["suffix"],"=")===false){$m["suffix"]="";}
    $m["user"]=trim((string)($ad["KerberosUsername"] ?? ""));
    $m["haspass"]=strlen((string)($ad["KerberosPassword"] ?? ""))>0;
    $m["ldaps"]=intval($ad["KerberosLDAPS"] ?? 0);
    $m["synctime"]=intval($sock->GET_INFO("KerberosSynCAD"));
    $m["basic"]=intval($sock->GET_INFO("HaClusterDisableBasicAuth"))==1?0:1;
    $m["autorenew"]=intval($sock->GET_INFO("EnablehaclusterAutoRenew"));
    $m["myhostname"]=trim((string)$sock->GET_INFO("myhostname"));
    $m["keytab_file"]=is_file("/home/artica/PowerDNS/Cluster/storage/krb5.keytab");

    // Test du keytab (kinit vers le KDC) : null = API illisible.
    $m["kt_ok"]=null; $m["kt_error"]=""; $m["kt_principal"]=""; $m["kt_findings"]=array();
    $kt=json_decode((string)$sock->REST_API("/hacluster/server/keytab/status"));
    if(is_object($kt) && !empty($kt->Status) && isset($kt->Info) && is_object($kt->Info)){
        $m["kt_ok"]=!empty($kt->Info->Status);
        $m["kt_error"]=trim((string)($kt->Info->Error ?? ""));
        // Constats du moteur de diagnostic d'articarest (HaClusterTool.DiagnoseKerberos) ;
        // absents avec un daemon anterieur au 2026-11-15 : la trace brute reste affichee.
        $m["kt_principal"]=(string)($kt->Info->Principal ?? "");
        $m["kt_findings"]=(isset($kt->Info->Findings) && is_array($kt->Info->Findings))?$kt->Info->Findings:array();
    }

    // Controle du SPN fait par le LB (cache 10 min cote demon, ?refresh=1 force).
    $m["spn"]=null;
    $sj=json_decode((string)$sock->REST_API("/hacluster/kerberos/spn".($spnRefresh?"?refresh=1":"")));
    if(is_object($sj) && isset($sj->kerberos) && is_object($sj->kerberos)){$m["spn"]=$sj->kerberos;}

    // Compte tel que l'AD le voit (lecture LDAP en direct, une fois par rendu).
    $m["dn"]=""; $m["spns"]=array(); $m["upns"]=array(); $m["ldap_read"]=false;
    // Lecture faite par le demon (GET /hacluster/kerberos/account, go-ldap) :
    // DN, SPN, UPN du compte. L'ancienne classe ActiveDirectory n'ouvrait jamais
    // de connexion, le bloc ne s'affichait donc jamais.
    $m["ldap_error"]="";
    $acc=json_decode((string)$sock->REST_API("/hacluster/kerberos/account"));
    if(is_object($acc) && !empty($acc->read)){
        $m["ldap_read"]=true;
        $m["dn"]=(string)($acc->dn ?? "");
        if(isset($acc->spns) && is_array($acc->spns)){foreach($acc->spns as $v){$m["spns"][]=(string)$v;}}
        if(isset($acc->upns) && is_array($acc->upns)){foreach($acc->upns as $v){$m["upns"][]=(string)$v;}}
    }elseif(is_object($acc)){
        $m["ldap_error"]=(string)($acc->error ?? "");
    }

    // Etat du bandeau : on ne colore en rouge qu'un probleme prouve.
    $spnstate=is_object($m["spn"])?(string)($m["spn"]->state ?? ""):"";
    if(!$m["keytab_file"]){
        $m["state"]="nokeytab";
    }elseif($m["kt_ok"]===false){
        $m["state"]="rejected";
    }elseif(kerberos_spn_is_problem($m["spn"])){
        $m["state"]="spn";
    }elseif($spnstate=="error" || (is_object($m["spn"]) && !empty($m["spn"]->awaiting_replication))){
        $m["state"]="unverified";
    }elseif($m["kt_ok"]===true){
        $m["state"]="ok";
    }else{
        $m["state"]="unverified";
    }
    $m["checked"]=is_object($m["spn"])?intval($m["spn"]->time ?? 0):0;
    return $m;
}

function hcad_banner($m):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tones=array("ok"=>"ak-ok","nokeytab"=>"ak-warn","rejected"=>"ak-err","spn"=>"ak-err","unverified"=>"ak-off");
    $icons=array("ok"=>"fa-circle-check","nokeytab"=>"fa-file-circle-exclamation","rejected"=>"fa-circle-xmark","spn"=>"fa-circle-xmark","unverified"=>"fa-circle-question");
    $state=$m["state"];
    $sub=""; $more="";
    if($state=="ok"){
        $title="{hcad_kerberos_working}";
        $sub="{hcad_kerberos_working_sub}";
    }elseif($state=="nokeytab"){
        $title="{hcad_no_keytab}";
        $sub="{KRB5_FILE_MISSING}";
    }elseif($state=="rejected"){
        $title="{hcad_keytab_rejected}";
        $lines=array_values(array_filter(array_map("trim",explode("\n",str_replace("exit status 1","exit status 1\n",$m["kt_error"]))),"strlen"));
        $first=count($lines)>0?$lines[0]:"";
        if($first<>""){$sub="<code class='hcad-errline'>".hcad_h($first)."</code>";}
        if(count($lines)>1){
            $rows=array();
            foreach($lines as $l){
                $cls="";
                if(strpos($l,"Preauthentication failed")!==false || strpos($l,"exit status 1")!==false){$cls=" class='hcad-bad'";}
                $rows[]="<div$cls>".hcad_h($l)."</div>";
            }
            $more="<details class='hcad-more'><summary>{hcad_show_full_error} <i class='fas fa-chevron-down'></i></summary><div class='hcad-errfull'>".implode("",$rows)."</div></details>";
        }
    }elseif($state=="spn"){
        $t=kerberos_spn_titles($m["spn"]->state);
        $title=$t[0];
        list($spn,$kdckvno,$detail)=kerberos_spn_failing($m["spn"]);
        $spnshort=preg_replace('#@.*$#','',$spn);
        if($spnshort<>""){$sub="<code class='hcad-errline'>".hcad_h($spnshort)."</code>";}
    }else{
        $title="{hcad_spn_unverified}";
        $sub="{hcad_spn_unverified_sub}";
    }
    $checked="";
    if($m["checked"]>0){$checked="<div class='ak-note'>{kerberos_spn_checked} ".$tpl->time_to_date($m["checked"],true)."</div>";}
    $recheck="LoadAjax('formflat','$page?formflat=yes&spn-refresh=yes');";

    $o=array();
    $o[]="<div class='ak-banner hcad-banner {$tones[$state]}' role='status'>";
    $o[]="<i class='fas {$icons[$state]} hcad-bico' aria-hidden='true'></i>";
    $o[]="<div class='ak-banner-m'><div class='hcad-btitle'>$title</div>";
    if($sub<>""){$o[]="<div class='hcad-bsub'>$sub</div>";}
    $o[]=$more;
    $o[]="</div>";
    $o[]="<div class='hcad-bside'><button type='button' class='ak-btn ak-btn-sm' OnClick=\"$recheck\"><i class='fas fa-rotate'></i> {check_again}</button>$checked</div>";
    $o[]="</div>";
    return implode("",$o);
}

// Les deux facons d'obtenir un keytab, seulement quand il manque ou qu'il est refuse.
function hcad_methods($m):string{
    if($m["state"]<>"nokeytab" && $m["state"]<>"rejected"){return "";}
    $page=CurrentPageName();
    $ps1=$m["myhostname"]."-keytab.ps1";   // nom reel du fichier telecharge (DownloadKeyTab)
    $cmd="Unblock-File \"$ps1\"";
    $o=array();
    $o[]="<div class='ak-card ak-card-pad hcad-sec'>";
    $o[]="<div class='ak-sec-t'>{hcad_choose_method}</div>";
    $o[]="<div class='hcad-meth'>";
    $o[]="<div class='hcad-mcard'>";
    $o[]="<div class='hcad-mt'><i class='fas fa-wand-magic-sparkles'></i> {use_the_wizard}</div>";
    $o[]="<p class='ak-muted'>{hcad_wizard_explain}</p>";
    $o[]="<div class='ak-grow'></div>";
    $o[]="<div><button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?wizard-js=yes')\"><i class='fas fa-wand-magic-sparkles'></i> {use_the_wizard}</button></div>";
    $o[]="</div>";
    $o[]="<div class='hcad-mcard'>";
    $o[]="<div class='hcad-mt'><i class='fas fa-terminal'></i> {hcad_ps_title}</div>";
    $o[]="<div class='hcad-step'><span>1. {download_powershell_script}</span>";
    $o[]="<button type='button' class='ak-btn ak-btn-sm' OnClick=\"document.location.href='/$page?download-ps1=yes'\"><i class='fas fa-download'></i> .ps1</button></div>";
    $o[]="<div class='hcad-step hcad-step-col'><span>2. {hcad_ps_step2}</span>".hcad_code($cmd)."</div>";
    $o[]="<div class='hcad-step'><span>3. {hcad_ps_step3}</span></div>";
    $o[]="</div>";
    $o[]="</div>";
    $o[]="</div>";
    return implode("",$o);
}

// Bloc de commande sombre + bouton copier (texte copie = contenu de data-copy).
function hcad_code($cmd):string{
    return "<div class='hcad-code'><code>".hcad_h($cmd)."</code>"
        ."<button type='button' class='hcad-copy' aria-label='{hcad_copy}' data-copy='".hcad_h($cmd)."' OnClick=\"HcadCopy(this)\"><i class='fas fa-copy'></i></button></div>";
}

// Correction d'un probleme de SPN prouve : memes faits et memes etapes que la
// tuile partagee kerberos_spn_render(), mis en forme pour cette page.
function hcad_spnfix($m):string{
    $d=$m["spn"];
    if(!kerberos_spn_is_problem($d)){return "";}
    $tpl=new template_admin();
    $state=(string)$d->state;
    list($spn,$kdckvno,$detail)=kerberos_spn_failing($d);
    $spnshort=preg_replace('#@.*$#','',$spn);
    $realm=strpos($spn,"@")!==false?substr($spn,strrpos($spn,"@")+1):"";
    $client=(string)($d->client ?? "");
    $account=preg_replace('#@.*$#','',$client);
    if(substr($account,-1)!='$'){$account="<{proxy_service_account}>";}
    $kvnos=array();
    if(isset($d->keytab_kvnos) && is_array($d->keytab_kvnos)){foreach($d->keytab_kvnos as $k){$kvnos[]=intval($k);}}
    $owner="<{kerberos_spn_owner}>";

    $facts=array();
    $facts[]=array("Keytab","<span class='ak-mono'>".hcad_h($d->keytab ?? "")."</span>","");
    if(count($kvnos)>0){$facts[]=array("{kerberos_keytab_kvno}",implode(", ",$kvnos),"");}
    if($kdckvno>0){
        $hl=(count($kvnos)>0 && !in_array($kdckvno,$kvnos))?" class='hcad-warnrow'":"";
        $facts[]=array("{kerberos_spn_ticket_kvno}",strval($kdckvno),$hl);
    }
    if($client<>""){$facts[]=array("{kerberos_test_account}","<span class='ak-mono'>".hcad_h($client)."</span>","");}
    if($detail<>""){$facts[]=array("{hcad_error}","<span class='ak-mono hcad-errtxt'>".nl2br(hcad_h($detail))."</span>","");}
    if(($d->helper_error ?? "")<>""){
        $when=intval($d->helper_error_time ?? 0)>0?$tpl->time_to_date(intval($d->helper_error_time),true)." &middot; ":"";
        $facts[]=array("{kerberos_helper_last_error}",$when."<span class='ak-mono'>".hcad_h($d->helper_error)."</span>","");
    }
    if(intval($d->time ?? 0)>0){$facts[]=array("{kerberos_spn_checked}",$tpl->time_to_date(intval($d->time),true),"");}

    $ktpass="ktpass -princ $spn -mapuser <DOMAIN>\\$owner -crypto AES256-SHA1 -ptype KRB5_NT_PRINCIPAL -pass * -out http.keytab";
    if($spn==""){$ktpass="ktpass -princ HTTP/<fqdn>@$realm -mapuser <DOMAIN>\\$owner -crypto AES256-SHA1 -ptype KRB5_NT_PRINCIPAL -pass * -out http.keytab";}

    $steps=array();
    if($state=="spn_other_account"){
        $steps[]=array("{kerberos_spn_fix_find}",hcad_code("setspn -Q $spnshort").hcad_code("setspn -X"));
        $steps[]=array("{kerberos_spn_fix_upn}",hcad_code("dsquery * domainroot -filter \"(userPrincipalName=$spnshort*)\" -attr distinguishedName userPrincipalName msDS-KeyVersionNumber"));
        $steps[]=array("{kerberos_spn_fix_ktpass}",hcad_code($ktpass));
        $steps[]=array("{kerberos_spn_fix_import_lb}","");
        $steps[]=array("{kerberos_spn_fix_purge}",hcad_code("klist purge"));
    }
    if($state=="spn_not_registered"){
        $steps[]=array("{kerberos_spn_fix_register}",hcad_code("setspn -S $spnshort $account"));
        $steps[]=array("{kerberos_spn_fix_purge}",hcad_code("klist purge"));
    }
    if($state=="kinit_failed"){
        $steps[]=array("{kerberos_keytab_fix_account}","");
        $steps[]=array("{kerberos_spn_fix_ktpass}",hcad_code($ktpass));
        $steps[]=array("{kerberos_spn_fix_import_lb}","");
    }

    $o=array();
    $o[]="<div class='ak-card ak-card-pad hcad-sec hcad-fix'>";
    $o[]="<div class='hcad-mt hcad-fixt'><i class='fas fa-screwdriver-wrench'></i> {kerberos_spn_fix_title}</div>";
    $o[]="<table class='hcad-facts'>";
    foreach($facts as $f){$o[]="<tr{$f[2]}><th>{$f[0]}</th><td>{$f[1]}</td></tr>";}
    $o[]="</table>";
    $o[]="<ol class='hcad-steps'>";
    foreach($steps as $s){$o[]="<li><div>{$s[0]}</div>{$s[1]}</li>";}
    $o[]="</ol>";
    $o[]="</div>";
    return implode("",$o);
}

function hcad_onoff($v):string{
    if(intval($v)==1){return "<span class='ak-pill ak-ok'><i class='fas fa-circle-check'></i> {hcad_on}</span>";}
    return "<span class='ak-pill ak-off'><i class='fas fa-circle-xmark'></i> {hcad_off}</span>";
}

function hcad_kv($label,$value,$mono=true):string{
    $v=trim((string)$value);
    $v=$v===""?"<span class='ak-muted'>&mdash;</span>":($mono?"<span class='ak-mono'>".hcad_h($v)."</span>":$v);
    return "<div class='hcad-kv'><span class='hcad-k'>$label</span><span class='hcad-v'>$v</span></div>";
}

function hcad_connection($m):string{
    $page=CurrentPageName();
    $o=array();
    $o[]="<div class='ak-card ak-card-pad hcad-conn'>";
    $o[]="<div class='hcad-ch'><div class='hcad-mt'><i class='fab fa-windows'></i> {hcad_connection}</div>";
    $o[]="<button type='button' class='ak-btn-icon ak-btn ak-btn-ghost' aria-label='{hcad_edit_connection}' OnClick=\"Loadjs('$page?ad-form-js=yes')\"><i class='fas fa-pen'></i></button></div>";
    $o[]=hcad_kv("{ad_full_hostname}",$m["host"]);
    $o[]=hcad_kv("{FQDNDC2}",$m["host2"]);
    $o[]=hcad_kv("{activedirectory_domain}",$m["realm"]);
    $o[]=hcad_kv("{ldap_suffix}",$m["suffix"]);
    $o[]=hcad_kv("{username}",$m["user"]);
    $o[]=hcad_kv("{useSSL} (LDAPs)",hcad_onoff($m["ldaps"]),false);
    $o[]=hcad_kv("{synchronize_time_with_ad}",hcad_onoff($m["synctime"]),false);
    $o[]="<div class='hcad-seen'>";
    $o[]="<div class='hcad-seen-h'><span>{hcad_account_seen}</span><span class='ak-note'>{hcad_read_live}</span></div>";
    if(!$m["ldap_read"]){
        $o[]="<div class='ak-note'>{hcad_account_unreadable}</div>";
        if($m["ldap_error"]<>""){$o[]="<div class='ak-note ak-mono'>".hcad_h($m["ldap_error"])."</div>";}
    }else{
        $o[]="<div class='hcad-k'>{ldap_user_dn}</div><div class='ak-mono hcad-dn'>".hcad_h($m["dn"])."</div>";
        $o[]="<div class='hcad-k'>{KERBSPN}</div>".hcad_chips($m["spns"]);
        $o[]="<div class='hcad-k'>{userprincipalname}</div>".hcad_chips($m["upns"]);
    }
    $o[]="</div>";
    $o[]="</div>";
    return implode("",$o);
}

function hcad_chips($list):string{
    if(count($list)==0){return "<div class='ak-muted'>&mdash;</div>";}
    $c=array();
    foreach($list as $v){$c[]="<span class='ak-chip'>".hcad_h($v)."</span>";}
    return "<div class='ak-chips hcad-chips'>".implode("",$c)."</div>";
}

function hcad_options($m):string{
    $users=new usersMenus();
    $dis=$users->AsSquidAdministrator?"":" disabled";
    $renew=$m["autorenew"]==1?"{each_day} 1h45":"";
    $o=array();
    $o[]="<div class='ak-card ak-card-pad hcad-opts'>";
    $o[]="<div class='hcad-mt hcad-opth'><i class='fas fa-sliders'></i> {hcad_options}</div>";
    $o[]=hcad_switch("LockActiveDirectoryToKerberosBasic","{LockActiveDirectoryToKerberosBasic}","",$m["basic"],$dis);
    $o[]=hcad_switch("EnablehaclusterAutoRenew","{autorenew_ticket}",$renew,$m["autorenew"],$dis);
    $o[]="</div>";
    return implode("",$o);
}

function hcad_switch($key,$label,$help,$on,$dis):string{
    $id="hcad-sw-$key";
    $checked=intval($on)==1?"true":"false";
    $h=$help<>""?"<div class='ak-note'>$help</div>":"";
    return "<div class='hcad-optrow'><div><div class='hcad-optl' id='$id-l'>$label</div>$h</div>"
        ."<button type='button' role='switch' class='ak-sw' aria-checked='$checked' aria-labelledby='$id-l' data-key='$key' OnClick=\"HcadToggle(this)\"$dis></button></div>";
}

// Interrupteurs : on reutilise les gestionnaires POST existants (droit verifie
// cote serveur), puis on recharge le bloc.
function hcad_js():string{
    $page=CurrentPageName();
    return "<script>
function HcadCopy(b){
  var t=b.getAttribute('data-copy');
  function done(){var i=b.innerHTML;b.innerHTML='<i class=\"fas fa-check\"></i>';setTimeout(function(){b.innerHTML=i;},1200);}
  if(navigator.clipboard && window.isSecureContext){navigator.clipboard.writeText(t).then(done);return;}
  var a=document.createElement('textarea');a.value=t;a.style.position='fixed';a.style.opacity='0';document.body.appendChild(a);a.select();
  try{document.execCommand('copy');done();}catch(e){}
  document.body.removeChild(a);
}
function HcadToggle(b){
  if(b.disabled){return;}
  var v=b.getAttribute('aria-checked')==='true'?0:1;
  var d={};d[b.getAttribute('data-key')]=v;
  b.disabled=true;
  $.post('$page',d).done(function(resp){
    if(String(resp).indexOf('jserror:')===0){
      swal({title:'Oops...',text:'<strong>'+String(resp).substring(8)+'</strong>',html:true,type:'error'});
      b.disabled=false;return;
    }
    LoadAjaxSilent('formflat','$page?formflat=yes');
  }).fail(function(){b.disabled=false;});
}
</script>";
}

function hcad_style():string{
    if(isset($GLOBALS["HCAD_STYLE"])){return "";}
    $GLOBALS["HCAD_STYLE"]=true;
    return "<style>
.hcad{display:flex;flex-direction:column;gap:20px}
.hcad-banner{display:flex;align-items:flex-start;gap:14px;padding:16px 18px;border-left-width:4px}
.hcad-bico{font-size:22px;margin-top:2px}
.hcad-btitle{font-size:16px;font-weight:600}
.hcad-bsub{margin-top:4px;font-size:14px}
.hcad-errline{display:inline-block;margin-top:2px;padding:4px 8px;border-radius:4px;background:rgba(255,255,255,.7);color:#111d23;font-size:13px;white-space:pre-wrap;word-break:break-all}
.hcad-more{margin-top:8px}
.hcad-more summary{cursor:pointer;font-size:13px;text-decoration:underline}
.hcad-errfull{margin-top:6px;padding:8px 10px;background:#fff;border-radius:6px;font-family:monospace;font-size:13px;color:#111d23;white-space:pre-wrap;word-break:break-all}
.hcad-bad{color:#ba1a1a;font-weight:700}
.hcad-bside{margin-left:auto;text-align:right;flex:0 0 auto;display:flex;flex-direction:column;align-items:flex-end;gap:6px}
.hcad-fixlist{margin:6px 0 0;padding-left:18px;font-size:14px}
.hcad-mt{display:flex;align-items:center;gap:8px;font-size:15px;font-weight:600;color:#111d23}
.hcad-meth{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:12px}
.hcad-mcard{display:flex;flex-direction:column;gap:12px;border:1px solid #e2e8f0;border-radius:8px;padding:16px;background:#fbfdfd}
.hcad-mcard p{margin:0}
.hcad-step{display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:14px;color:#3d4947}
.hcad-step-col{flex-direction:column;align-items:stretch}
.hcad-code{display:flex;align-items:center;gap:10px;background:#1f2430;border-radius:6px;padding:10px 12px;margin-top:6px}
.hcad-code code{flex:1;background:transparent;color:#e6edf3;font-size:13px;white-space:pre-wrap;overflow-wrap:anywhere;word-break:normal;padding:0}
.hcad-copy{flex:0 0 auto;background:transparent;border:0;color:#aeb8c4;cursor:pointer;width:28px;height:28px;border-radius:6px}
.hcad-copy:hover,.hcad-copy:focus-visible{color:#fff;background:rgba(255,255,255,.12)}
.hcad-fix{border-color:#ba1a1a}
.hcad-fixt{color:#ba1a1a;margin-bottom:12px}
.hcad-facts{width:100%;border-collapse:collapse;font-size:14px;margin-bottom:16px}
.hcad-facts th{width:260px;font-weight:400;color:#3d4947;padding:8px 12px;border-bottom:1px solid #edf1f4;vertical-align:top;text-align:left}
.hcad-facts td{padding:8px 12px;border-bottom:1px solid #edf1f4;word-break:break-all}
.hcad-warnrow th,.hcad-warnrow td{background:#fff8ec;color:#865300;font-weight:600}
.hcad-errtxt{color:#ba1a1a}
.hcad-steps{margin:0;padding-left:20px;font-size:14px}
.hcad-steps li{margin:0 0 14px}
.hcad-cols{display:grid;grid-template-columns:3fr 2fr;gap:20px;align-items:start}
.hcad-ch{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.hcad-kv{display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #edf1f4;font-size:14px}
.hcad-k{color:#3d4947;font-size:13px}
.hcad-v{text-align:right;word-break:break-all}
.hcad-seen{margin-top:16px;padding-top:12px;border-top:1px solid #e2e8f0;display:flex;flex-direction:column;gap:6px}
.hcad-seen-h{display:flex;justify-content:space-between;align-items:baseline;font-weight:600;font-size:14px;margin-bottom:4px}
.hcad-dn{font-size:13px;word-break:break-all;margin-bottom:6px}
.hcad-chips{margin-bottom:6px}
.hcad-opth{margin-bottom:4px}
.hcad-optrow{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid #edf1f4}
.hcad-optrow:last-child{border-bottom:0}
.hcad-optl{font-size:14px;font-weight:600;color:#111d23}
@media(max-width:1200px){.hcad-meth,.hcad-cols{grid-template-columns:1fr}}
</style>";
}

function LockActiveDirectoryToKerberosBasic_popup():bool{
    $HaClusterDisableBasicAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterDisableBasicAuth");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $LockActiveDirectoryToKerberosBasic=1;
    if($HaClusterDisableBasicAuth==1){
        $LockActiveDirectoryToKerberosBasic=0;
    }
    echo $tpl->BigCircleCheckbox("LockActiveDirectoryToKerberosBasic",
        "{LockActiveDirectoryToKerberosBasic}",
        "{LockActiveDirectoryToKerberosBasic_explain}",
        $LockActiveDirectoryToKerberosBasic,"LoadAjax('table-kerberos','$page?table=yes');dialogInstance2.close();");

    return true;
}
function LockActiveDirectoryToKerberosBasic_save():bool{
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo (new template_admin())->post_error("{ERROR_NO_PRIVS2}");return false;}

    if($_POST["LockActiveDirectoryToKerberosBasic"]==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterDisableBasicAuth",0);
        $act="Activate";
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterDisableBasicAuth",1);
        $act="Disable";
    }


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    return admin_tracks("HaCluster: $act basic authentication trough Active Directory");

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

// ---------------------------------------------------------------------------------------
// Diagnostic de l'echec du keytab (prefixe hcdx_). Le moteur est dans articarest
// (HaClusterTool.DiagnoseKerberos) : la page n'interprete RIEN, elle affiche ses constats.
// Titre, explication et action sont traduits par l'identifiant stable du constat, avec
// ses arguments ordonnes (Args) ; le texte anglais du daemon sert de repli.
// ---------------------------------------------------------------------------------------
function hcdx_t(string $k,array $args,string $fallback):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$k."}");
    if($s==="{".$k."}" || trim($s)===""){return hcad_h($fallback);}
    $s=trim(preg_replace('/\s+/',' ',str_replace(array("[br]","<br>")," ",$s)));
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p)."<span class='hcdx-arg'>".str_replace(array("{","}"),array("&#123;","&#125;"),hcad_h($a))."</span>".substr($s,$p+2);
    }
    return $s;
}

function hcdx_finding($f):string{
    $id=preg_replace('/[^a-z_]/','',(string)($f->id ?? "unknown"));
    $sev=(string)($f->severity ?? "error");
    if(!in_array($sev,array("error","warning","info"),true)){$sev="error";}
    $args=array();
    if(isset($f->args) && is_array($f->args)){foreach($f->args as $a){$args[]=(string)$a;}}
    $icons=array("error"=>"fa-circle-xmark","warning"=>"fa-triangle-exclamation","info"=>"fa-circle-info");
    $o=array();
    $o[]="<div class='hcdx-f hcdx-$sev'>";
    $o[]="<div class='hcdx-fh'><i class='fas {$icons[$sev]}' aria-hidden='true'></i><span class='hcdx-sev'>{hcdx_sev_$sev}</span>"
        ."<span class='hcdx-ft'>".hcdx_t("hcdx_".$id."_t",$args,(string)($f->summary ?? $id))."</span></div>";
    $o[]="<div class='hcdx-fs'>".hcdx_t("hcdx_".$id."_s",$args,(string)($f->summary ?? ""))."</div>";
    $act=(string)($f->action ?? "");
    if($act!==""){
        $o[]="<div class='hcdx-fa'><b>{hcdx_todo}</b> ".hcdx_t("hcdx_".$id."_a",$args,$act)."</div>";
    }
    if(isset($f->commands) && is_array($f->commands) && count($f->commands)>0){
        $o[]="<div class='hcdx-cmds'>";
        foreach($f->commands as $c){$o[]=hcad_code((string)$c);}
        $o[]="</div>";
    }
    $ev=trim((string)($f->evidence ?? ""));
    if($ev!==""){
        $o[]="<div class='hcdx-ev'><span>{hcdx_trace}</span> <code>".str_replace(array("{","}"),array("&#123;","&#125;"),hcad_h($ev))."</code></div>";
    }
    $o[]="</div>";
    return implode("",$o);
}

function hcdx_diagnosis($m):string{
    if($m["kt_ok"]!==false || !is_array($m["kt_findings"]) || count($m["kt_findings"])==0){return "";}
    $o=array();
    $o[]="<section class='ak-card ak-card-pad hcdx' aria-labelledby='hcdx-title'>";
    $o[]="<div class='hcdx-head'><i class='fas fa-stethoscope' aria-hidden='true'></i><span id='hcdx-title'>{hcdx_title}</span></div>";
    $tested=$m["kt_principal"]!==""?" <span class='hcdx-arg'>".hcad_h($m["kt_principal"])."</span>":"";
    $o[]="<p class='hcdx-intro'>{hcdx_intro}$tested</p>";
    foreach($m["kt_findings"] as $f){
        if(is_object($f)){$o[]=hcdx_finding($f);}
    }
    $o[]="</section>";
    return implode("",$o);
}

function hcdx_style():string{
    return "<style>
.hcdx{margin:12px 0}
.hcdx-head{display:flex;align-items:center;gap:10px;font-size:15px;font-weight:600;color:#111d23}
.hcdx-head>svg,.hcdx-head>i{color:#0b6654}
.hcdx-intro{margin:6px 0 12px;color:#3d4947;font-size:13px}
.hcdx-arg{font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;background:#f1f5f9;border-radius:4px;padding:0 5px;color:#111d23}
.hcdx-f{border:1px solid #e2e8f0;border-left-width:4px;border-radius:0 8px 8px 0;padding:12px 14px;margin-bottom:10px;background:#fff}
.hcdx-error{border-left-color:#DC2626}.hcdx-warning{border-left-color:#D97706}.hcdx-info{border-left-color:#94a3b8;background:#f8fafc}
.hcdx-fh{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.hcdx-error .hcdx-fh>svg{color:#DC2626}.hcdx-warning .hcdx-fh>svg{color:#D97706}.hcdx-info .hcdx-fh>svg{color:#64748b}
.hcdx-sev{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#475569}
.hcdx-ft{font-size:14px;font-weight:600;color:#111d23}
.hcdx-fs{margin-top:6px;color:#334155;line-height:1.5;font-size:13px}
.hcdx-fa{margin-top:6px;color:#334155;line-height:1.5;font-size:13px}
.hcdx-cmds{margin-top:8px;display:flex;flex-direction:column;gap:6px}
.hcdx-ev{margin-top:8px;font-size:12px;color:#64748b}
.hcdx-ev code{font-size:12px;color:#475569;background:transparent;white-space:pre-wrap;word-break:break-word}
</style>";
}
