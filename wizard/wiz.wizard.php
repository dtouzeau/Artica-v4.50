<?php
include_once(dirname(__FILE__)."/ressources/class.wizard.inc");

if(is_file("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED")){
    echo header('location:/fw.login.php');
    exit;
}

if(isset($_GET["LicenseFile"])){step_license_uploaded();exit;}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
if(isset($_GET["snaphost"])){step_upload_snapshot_performed();exit;}
if(isset($_POST["artica_method"])){save();exit;}
if(isset($_POST["raison_sociale"])){save();exit;}
if(isset($_POST["timezones"])){save();exit;}
if(isset($_POST["administrator"])){save();exit;}
if(isset($_POST["netbiosname"])){save_network();exit;}
if(isset($_GET["step"])){step();exit;}
if(isset($_GET["body-step"])){body_step();exit;}
if(isset($_GET["step-network"])){step_network();exit;}
if(isset($_GET["step-products"])){step_products();exit;}
if(isset($_GET["step-manager"])){step_manager();exit;}
if(isset($_GET["step-finish"])){step_finish();exit;}
if(isset($_GET["remove-snapshot-js"])){remove_snapshot();exit;}
page();


function page():bool{



    $wiz=new artica_wizard("{WELCOME_ON_ARTICA_PROJECT}");
    echo $wiz->Build();
    return true;
}

function save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(isset($_POST["lang2"])){
        setcookie("artica-language", $_POST["lang2"], time()+31536000);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FixedLanguage",$_POST["lang2"]);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LockLang",1);
    }

    if(isset($_POST["organization"])){
        $_POST["company_name"]=$_POST["organization"];
    }
    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
        $_POST["artica_method"]=4;
    }
    if(is_file("/etc/artica-postfix/ARTICA_SMTP_APPLIANCE")){
        $_POST["artica_method"]=6;
    }

    $wiz=new artica_wizard();
    $wiz->SavedSettings($_POST);
    return true;
}
function remove_snapshot():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $wiz=new artica_wizard();
    $wiz->SnapshotDelete();
    echo $wiz->ReturnMainAjax("$page?step-products=yes");
    return true;
}

function step():bool{
    $wiz=new artica_wizard();
    echo $wiz->Step($_GET["step"]);
    return true;
}
function body_step():bool{
    $wiz=new artica_wizard();
    $page=CurrentPageName();
    $Addon=null;
    $AddonExplain=null;
    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
        $Addon=" - Reverse-Proxy";
        $AddonExplain="<p style='font-weight: bold'>{APP_REVERSE_PROXY_EXPLAIN}</p>";
    }

    if(is_file("/etc/artica-postfix/ARTICA_SMTP_APPLIANCE")){
        $Addon=" - Artica SMTP gateway";
        $AddonExplain="<p style='font-weight: bold'>{APP_POSTFIX_TEXT}</p>";
    }


    $htmltools_inc  = new htmltools_inc();
    $lang           = $htmltools_inc->LanguageArray();

    $timezone=$wiz->timezone();
    $wiz->field_array_hash($timezone,"{timezone}","timezones",$wiz->timezone_def);
    $wiz->field_array_hash($lang,"{mylanguage}",  "lang2",$wiz->DetectedLanguage);
    $wiz->form_after("$page?step-network=yes");

    $wiz->StepNumber(1);
    $html[]=$wiz->build_form();
    echo $wiz->BuildSection("{WELCOME_ON_ARTICA_PROJECT}$Addon","{WELCOME_WIZARD_ARC1}$AddonExplain",$html);
    return true;

}
function step_network():bool{
    $wiz=new artica_wizard();
    if(strlen($wiz->DNS2)==0){$wiz->DNS2="8.8.4.4";}
    $page=CurrentPageName();
    //serveretdom
    $wiz->field_text("{netbiosname}","netbiosname",$wiz->netbiosname,"text");
    $wiz->field_text("{DomainOfThisserver}","domain",$wiz->domainname,"text");
    $wiz->field_text( "{primary_dns}","DNS1" ,$wiz->DNS1,"ipaddr");
    $wiz->field_text("{secondary_dns}", "DNS2",$wiz->DNS2,"ipaddr");

    $NICS=new networking();
    $Local_interfaces=$NICS->Local_interfaces(true);

    foreach ($Local_interfaces as $nic=>$none) {
        $MAIN=array();
        if(!isset($wiz->savedsettings["NET_INTERFACES"][$nic])) {
            $MAIN = $wiz->savedsettings["NET_INTERFACES"][$nic];
        }
        if(preg_match("#^wlp#",$nic)){
            continue;
        }

        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/nicstatus/$nic"));
        $nicinfos=$data->Info;

        $tbl = explode(";", $nicinfos);
        $MAC=$tbl[1];
        $IPADDR = $MAIN["IPADDR"];
        $NETMASK = $MAIN["NETMASK"];
        $GATEWAY = $MAIN["GATEWAY"];
        $metric = $MAIN["metric"];
        $BROADCAST = $MAIN["BROADCAST"];
        $KEEPNET = $MAIN["KEEPNET"];
        $VPS_COMPATIBLE = $MAIN["VPS_COMPATIBLE"];
        $system_nic=new system_nic($nic);
        if($IPADDR==null){$IPADDR=$system_nic->IPADDR;}
        if($NETMASK==null){$NETMASK=$system_nic->NETMASK;}
        if($GATEWAY==null){$GATEWAY=$system_nic->GATEWAY;}
        if($GATEWAY=="no"){$GATEWAY="0.0.0.0";}
        if(strlen($GATEWAY)<3){$GATEWAY="0.0.0.0";}
        if(trim($NETMASK)==""){
            $NETMASK="255.255.255.0";
        }

        $MAINFORMS=array();
        $MAINFORMS["nic-$nic-IPADDR"]["LABEL"]="{ipaddr} $nic ($MAC)";
        $MAINFORMS["nic-$nic-IPADDR"]["VALUE"]=$IPADDR;
        $MAINFORMS["nic-$nic-IPADDR"]["VALID"]="ipaddr";

        $MAINFORMS["nic-$nic-GATEWAY"]["LABEL"]="{gateway} $nic ($MAC)";
        $MAINFORMS["nic-$nic-GATEWAY"]["VALUE"]=$GATEWAY;
        $MAINFORMS["nic-$nic-GATEWAY"]["VALID"]="ipaddr";

        $MAINFORMS["nic-$nic-NETMASK"]["LABEL"]="{netmask} $nic ($MAC)";
        $MAINFORMS["nic-$nic-NETMASK"]["VALUE"]=$NETMASK;
        $MAINFORMS["nic-$nic-NETMASK"]["VALID"]="ipaddr";
        $wiz->multi_fieldtext($MAINFORMS);

    }
    $wiz->StepNumber(2);
    $wiz->form_previous("$page?body-step=yes");
    $FormAfter="$page?step-products=yes";

    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
        $FormAfter="$page?step-manager=yes";
    }
    if(is_file("/etc/artica-postfix/ARTICA_SMTP_APPLIANCE")){
        $FormAfter="$page?step-manager=yes";
    }

    $wiz->form_after($FormAfter);
    $html[]=$wiz->build_form();
    echo $wiz->BuildSection("{your_server_net}","{your_server_net_explain}",$html);
    return true;
}
function cleanhostname($str):string{

    $str=str_replace('%','',$str);
    $str=str_replace(';','.',$str);
    $str=str_replace(',','.',$str);
    $str=str_replace('?','.',$str);
    $str=str_replace('[','',$str);
    $str=str_replace(']','',$str);
    $str=str_replace('(','',$str);
    $str=str_replace(')','',$str);
    $str=str_replace('$','',$str);
    $str=str_replace('#','',$str);
    $str=str_replace('{','',$str);
    $str=str_replace('}','',$str);
    $str=str_replace('@','',$str);
    $str=str_replace('^','',$str);
    $str=str_replace('&','',$str);
    $str=str_replace('..','.',$str);
    $str=str_replace('..','.',$str);
    $str=str_replace('..','.',$str);
    return $str;

}

function save_network():bool{

    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    if(isset($_POST["netbiosname"])){
        if(isset($_POST["domain"])){
            if(strlen($_POST["domain"])<3){
                $_POST["domain"]="localhost.localdomain";
            }
        }
        $hostname=cleanhostname($_POST["netbiosname"].".".$_POST["domain"]);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/nohup/hostname/$hostname");
    }

    foreach ($_POST as $key=>$val){
        if(preg_match("#^nic-(.+?)-(.+)#",$key,$re)){
            $nic=$re[1];
            $field=$re[2];
            $_POST["NET_INTERFACES"][$nic][$field]=$val;
            unset($_POST[$key]);
        }

    }

    $wiz=new artica_wizard();
    $wiz->SavedSettings($_POST);
    $content=json_encode($_POST["NET_INTERFACES"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WizardInterfaces",$content);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    return true;
}

function step_products():bool{
    $wiz=new artica_wizard();
    $page=CurrentPageName();

    $wiz->StepNumber(3);
    $wiz->form_previous("$page?step-network=yes=yes");
    $wiz->form_after("$page?step-manager=yes");

    $LIST[0]=array(
        "LABEL"=>"{APP_SQUID}","EXPLAIN"=>"{simple_proxy_and_webfiltering}","PIC"=>"PROXY");

    $LIST[8]=array(
        "LABEL"=>"HaCluster","EXPLAIN"=>"{hacluster_explain}","PIC"=>"HACLUSTER");

    $LIST[1]=array(
        "LABEL"=>"{APP_DNS_FIREWALL}","EXPLAIN"=>"{APP_DNS_FIREWALL_ABOUT}","PIC"=>"DNS");

    $LIST[10]=array(
        "LABEL"=>"{APP_UNBOUND}","EXPLAIN"=>"{didyouknow_unbound}","PIC"=>"DNSC");

    $LIST[7]=array(
        "LABEL"=>"{APP_RBLDNSD}","EXPLAIN"=>"{APP_RBLDNSD_EXPLAIN}","PIC"=>"RBL");

    $LIST[4]=array(
        "LABEL"=>"Reverse-Proxy","EXPLAIN"=>"{APP_REVERSE_PROXY_EXPLAIN}","PIC"=>"RPROXY");

    $LIST[3]=array(
        "LABEL"=>"{logs_sink}","EXPLAIN"=>"{log_sink_explain}","PIC"=>"LOGS");

    $sock=new sockets();
    $KEA_INSTALLED=intval($sock->GET_INFO("KEA_INSTALLED"));
    if($KEA_INSTALLED==1) {
        $LIST[9] = array(
            "LABEL" => "{APP_DHCP} - IPAM", "EXPLAIN" => "{dhcp_ipam_explain}", "PIC" => "DHCP");
    }

    $LIST[6] = array(
        "LABEL" => "{APP_POSTFIX}", "EXPLAIN" => "{wizard_smtp_explain}", "PIC" => "SMTP");

    $LIST[2]=array(
        "LABEL"=>"{minimalist_gateway}","EXPLAIN"=>"{minimalist_gateway_short}","PIC"=>"FW");

    $LIST[5]=array(
        "LABEL"=>"{restore_a_snapshot}","EXPLAIN"=>"{wizard_restore_snapshot}","PIC"=>"RESTORE");

    $wiz->field_sections("artica_method",$LIST);

    $html[]=$wiz->build_form();
    echo $wiz->BuildSection("{aks_articasrv_type}","{aks_articasrv_type_explain}",$html);

    //

    return true;

}

function step_manager():bool{

    $wiz=new artica_wizard();
    $page=CurrentPageName();

    if($wiz->artica_method==5){
        if($wiz->snapshot_uploaded==0){
            step_upload_snapshot();
            return true;
        }
    }

    if($wiz->artica_method==5){
        $wiz->field_info_button("{restore_a_snapshot}",$wiz->savedsettings["snapshot_fname"],
            "{delete}","Loadjs('$page?remove-snapshot-js=yes')");
    }
    $WizardLicenseUploaded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardLicenseUploaded"));
    $WizardLicenseUploadedFile=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardLicenseUploadedFile");
    if($WizardLicenseUploadedFile==null){$WizardLicenseUploaded=0;}
    $wiz->field_text("{administrator_account}","administrator",$wiz->administrator,"text");
    $wiz->field_password("{password}","administratorpass",$wiz->administratorpass,"text");
    $wiz->field_text("{your_email_address}","mail",$wiz->mail,"mail");
    $wiz->field_text("{organizationName}","organization",$wiz->organization,"text");
    $wiz->field_text("{organizationalUnitName}","organizationUnit",$wiz->organizationUnit,"text");
    $wiz->field_text("{countryName}","countryName",$wiz->countryName,"text");
    $wiz->field_text("{stateOrProvinceName}","stateOrProvinceName",$wiz->stateOrProvinceName,"text");
    $wiz->field_text("{localityName}","localityName",$wiz->localityName,"text");

    if($WizardLicenseUploaded==0) {
        $wiz->button_upload("{license_key_file}", "LicenseFile", $page);
    }else{
        $wiz->field_info_text("{license_key_file}",$WizardLicenseUploadedFile);
    }

    $wiz->StepNumber(4);
    $FormPrev="$page?step-products=yes";

    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
        $FormPrev="$page?step-network=yes=yes";
    }
    if(is_file("/etc/artica-postfix/ARTICA_SMTP_APPLIANCE")){
        $FormPrev="$page?step-network=yes=yes";
    }

    $wiz->form_previous($FormPrev);




    $wiz->form_after("$page?step-finish=yes");
    $html[]="<div style='margin-top:120px' id='license-uploaded'></div>";
    $html[]=$wiz->build_form();
    echo $wiz->BuildSection("{administrator_account}/{virtual_company}","{administrator_account_wizard_explain}<br>{virtual_company_email_ask}",$html);

    return true;

}
function step_upload_snapshot():bool{
    $wiz=new artica_wizard();
    $page=CurrentPageName();
    $wiz->StepNumber(4);
    $wiz->form_previous("$page?step-products=yes");
    $wiz->form_after("$page?step-manager=yes&snapshot-uploaded=yes");
    $wiz->button_upload("{your_snapshot}","snaphost",$page);
    $html[]="<div style='margin-top:120px' id='snapshot-uploaded'></div>";
    $html[]=$wiz->build_form();
    echo $wiz->BuildSection("{restore_a_snapshot}","{upload_snapshot_explain}",$html);
    return true;

}

function step_upload_snapshot_performed():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $file=$_GET["snaphost"];
    header("content-type: application/x-javascript");
    $error=array();
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$file";
    $error[]="<div class=\"alert alert-info\" role=\"alert\" style='font-size:18px'>";
    $error[]=$tpl->_ENGINE_parse_body("{please_wait} ($file)");
    $error[]="</div>";


    if(!preg_match("#\.gz$#",$file)){
        $error=array();
        @unlink($fullpath);
        $error[]="<div class=\"alert alert-danger\" role=\"alert\" style='font-size:18px'>";
        $error[]=$tpl->_ENGINE_parse_body("{incompatible_file_retry}");
        $error[]="</div>";
        $errorEnc=base64_encode(@implode("",$error));
        echo "document.getElementById('snapshot-uploaded').innerHTML=base64_decode('$errorEnc');";
        return false;
    }

    $FILEOK=false;
    $files=$tpl->TarListFiles($fullpath);
    foreach ($files as $fname){
        if(!preg_match("#^(ARRAY_CONTENT|TRUNCATE_TABLES|ldap_database.gz)$#",$fname)){continue;}
        $FILEOK=true;
        break;
    }

    if(!$FILEOK){
        $error=array();
        @unlink($fullpath);
        $error[]="<div class=\"alert alert-danger\" role=\"alert\" style='font-size:18px'>";
        $error[]=$tpl->_ENGINE_parse_body("{incompatible_file_retry}");
        $error[]="</div>";
        $errorEnc=base64_encode(@implode("",$error));
        echo "document.getElementById('snapshot-uploaded').innerHTML=base64_decode('$errorEnc');";
        return false;
    }


    $fileen=base64_encode($file);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?move-wizard-snapshot=$fileen");
    $wiz=new artica_wizard();
    $wiz->snapshotuploaded($file);
    echo $wiz->ReturnMainAjax("$page?step-manager=yes");
    return true;

}

function step_finish():bool{
    $wiz=new artica_wizard();

    if($wiz->artica_method==5){
        if($wiz->snapshot_uploaded==1){
            $_GET["noform"]="yes";
        }
    }

    $WizardLicenseUploaded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardLicenseUploaded"));
    if($WizardLicenseUploaded==1){
        $_GET["noform"]="yes";

    }


    if(!isset($_GET["noform"])) {
        if ($wiz->timezone_def == "Europe/Paris") {
            step_choosed_form_fr();
            return true;
        }
    }
    $wiz->StepNumber(5);
    $wiz->SaveIdentity();


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/wizard.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/wizard.log";
    $ARRAY["CMD"]="/wizard/install";
    $ARRAY["TITLE"]="{build_parameters}";
    $ARRAY["AFTER"]="document.location.href='/fw.login.php'";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('wiz.wizard.progress.php?content=$prgress&mainid=progress-firehol-restart')";


    $html[]="<div id='progress-firehol-restart' style='height:50px;margin-top:90px'></div>";

    $html[]="<script>
	    LoadAjaxSilent('vertical-steps','wiz.wizard.php?step=5');
		$jsrestart
	</script>";

    $explain="{wizard_explain_refresh}";

    echo $wiz->BuildSection("{preparing_your_server}",
        "<div style='margin-bottom:30px;height:50px'><h2 id='prepare-server-title' >{please_wait}</h2></div>",$html);



    return true;
}

function step_choosed_form_fr():bool{
    $wiz=new artica_wizard();
    $page=CurrentPageName();
    $wiz->StepNumber(4);
    $title="Enregistrement de votre serveur.";
    $content="LEMNIA est le distributeur exclusif pour votre pays.<br>Il est le contact pour les clients et les revendeurs, l’hébergement cloud, ainsi que le support en français.<br>
Pendant votre période d’évaluation de 30 jours, le support LEMNIA vous <strong>offre l’accompagnement par l’un de ses experts ARTICA</strong>.<br>
    <strong>Téléphone: <str
    ong>0 252 352 452</strong> ( <a href='https://www.lemnia.fr' _target='new'>www.lemnia.fr</a>) <br><br><i>* Ces informations sont nécessaires à l’activation de votre licence d’évaluation 30j.</i>";

    $wiz->form_previous("$page?step-manager=yes");
    $wiz->form_after("$page?step-finish=yes&noform=yes");

    $wiz->field_text("Raison sociale *","raison_sociale",$wiz->Prenom,"text");
    $wiz->field_text("Nom *","Nom",$wiz->Prenom,"text");
    $wiz->field_text("Prénom *","Prenom",$wiz->Nom,"text");
    $wiz->field_text("Téléphone *","Telephone",$wiz->Telephone,"text");
    $wiz->field_text("eMail *","eMail",$wiz->eMail,"mail");
    $wiz->field_check("J’autorise ARTICA Tech à transmettre mes informations à LEMNIA afin de bénéficier de ma période d’évaluation de 30 jours avec l’accompagnement et les conseils gratuits d’un expert ARTICA en français.","allow_contact",$wiz->allow_contact);

    $html[]=$wiz->build_form();
    echo $wiz->BuildSection($title,$content,$html);
    return true;

}
function step_license_uploaded():bool{
    $file=urlencode($_GET["LicenseFile"]);
    $tpl=new template_admin();
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/".$_GET["LicenseFile"];
    if(!preg_match("#\.key$#",$_GET["LicenseFile"])){
        $error=array();
        @unlink($fullpath);
        $error[]="<div class=\"alert alert-danger\" role=\"alert\" style='font-size:18px'>";
        $error[]=$tpl->_ENGINE_parse_body("{incompatible_file_retry}");
        $error[]="</div>";
        $errorEnc=base64_encode(@implode("",$error));
        echo "document.getElementById('license-uploaded').innerHTML=base64_decode('$errorEnc');";
        return false;
    }



    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WizardLicenseUploadedFile",$_GET["LicenseFile"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WizardLicenseUploaded",1);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/install/key?file=".urlencode($file));

    $wiz=new artica_wizard();
    $page=CurrentPageName();
    echo $wiz->ReturnMainAjax("$page?step-manager=yes");
    return true;

}

