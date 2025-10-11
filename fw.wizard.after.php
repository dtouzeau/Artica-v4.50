<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.invalid.mail.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__).'/ressources/class.identity.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["step1"])){step1();exit;}
if(isset($_GET["use-com"])){usecom();exit;}
if(isset($_GET["use-entr"])){use_entr();exit;}
if(isset($_POST["company_name"])){use_entr_save();exit;}
if(isset($_GET["autoeval-error"])){autoeval_error();exit;}
page();

function page(){
$page=CurrentPageName();
$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\"><h1 class=ng-binding>{WELCOME_ON_ARTICA_PROJECT} </H1>
		<div id='wizard-stepz'></div>
	</div>
</div>
<script>LoadAjax('wizard-stepz','$page?step1=yes');</script>";


$tpl=new template_admin(null,$html);
echo $tpl->build_firewall();



}
function autoeval_error(){
    $GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsWizardExecuted",1);
    header("content-type: application/x-javascript");
    echo "document.location.href='/index'";
}

function wizard_executed(){


}

function step1(){
	$tpl=new template_admin();
    $html[]="<div id='first-wizard-after'></div>";
	$html[]="<p style='font-size:16px'>{WELCOME_4_1}</p>";
    $html[]=$tpl->div_warning("{WELCOME_EVAL}");
	$page=CurrentPageName();
    $HideCorporateFeatures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCorporateFeatures"));
    if($HideCorporateFeatures==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HideCorporateFeatures",0);
    }


    $button1=$tpl->widget_style1("yellow-bg","fab fa-linux",null,"{use_community_edition}");
    $button2=$tpl->widget_style1("navy-bg","fas fa-building",null,"{use_enterprise_edition}");

    $askeval=$tpl->framework_buildjs("/register/key?isDemo=true","artica.license.progress","artica.license.progress.log","wizard-stepz","document.location.href='/license'","Loadjs('$page?autoeval-error=yes');");

		
	$html[]="<table style='width:100%'>
    <tr>
    
	<td style='width:50%;padding:10px'><a href='javascript:blur()' onclick=\"LoadAjax('wizard-stepz','$page?use-com=yes');\">$button1</a></td>
	<td style='width:50%;padding:10px'><a href=javascript:blur()' OnClick=\"$askeval\">$button2</a></td>
	</tr>
	</table>";	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function usecom(){
	$tpl=new template_admin();
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HideCorporateFeatures", 1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsWizardExecuted", 1);
	$html[]="<p style='font-size:16px'>{WELCOME_4_2}</p>";
	$page=CurrentPageName();
	$button1=$tpl->button_autnonome("{features}", "window.location.href='/features';", "fas fa-arrow-alt-to-bottom",null,501);
	
	
	// http://www.asual.com/jquery/address/docs/
	
	$html[]="<table style='width:100%'><tr><td style='width:99%'>&nbsp;</td>
	<td style='width:1%'>$button1</td>
	</tr>
	</table>
	<script>
	    LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');
	
	
	
</script>
	
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function use_entr(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$WizardSavedSettings=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
	$WizardSavedSettingsSend=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettingsSend"));
	if(!isset($WizardSavedSettings["company_name"])){$WizardSavedSettings["company_name"]=null;}
	$company_name=$WizardSavedSettings["company_name"];
	$organization=$WizardSavedSettings["organization"];
	$employees=$WizardSavedSettings["employees"];
	$company_name=$WizardSavedSettings["company_name"];
	$country=$WizardSavedSettings["country"];
	$city=$WizardSavedSettings["city"];
	$mail=$WizardSavedSettings["mail"];
	$telephone=$WizardSavedSettings["telephone"];
	$UseServerV=$WizardSavedSettings["UseServer"];
	$smtp_domainname=$WizardSavedSettings["smtp_domainname"];
	$company_www=$WizardSavedSettings["company_www"];

    $sidentity=new sidentity();

    if($company_name==null){$company_name=$sidentity->GET("company_name");}
    if($company_name==null){$company_name=$sidentity->GET("organization");}
    if($mail==null){$mail=$sidentity->GET("mail");}
    if($company_www==null){$company_www=$sidentity->GET("company_www");}
    if($country==null){$country=$sidentity->GET("country");}
    if($city==null){$city=$sidentity->GET("city");}
    if($telephone==null){$telephone=$sidentity->GET("telephone");}
    if($employees==null){$employees=$sidentity->GET("employees");}

	
	$form[]=$tpl->field_section("{YourRealCompany}",null);
	$form[]=$tpl->field_text("company_name", "{company_name}", $company_name,true);
	$form[]=$tpl->field_text("company_www", "{company_website}", $company_www,true);
	$form[]=$tpl->field_array_hash(CountryList(), "country", "{country}", $country);
	$form[]=$tpl->field_text("city", "{city}", $city,true);
	$form[]=$tpl->field_text("mail", "{your_email_address}", $mail,true);
	$form[]=$tpl->field_text("telephone", "{phone_title}", $telephone,true);
	$form[]=$tpl->field_numeric("employees", "{nb_employees}", $employees,true);


	echo $tpl->form_outside("Entreprise Edition", $form,"<p>{WELCOME_4_3}</p>","{next}","window.location.href='/license-wizard-request';");
}

function use_entr_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
	$WizardSavedSettings=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
	if($_POST["company_www"]<>null){
		$_POST["company_www"]=url_decode_special_tool($_POST["company_www"]);
		if(preg_match("#^http#", $_POST["company_www"])){
			$parse_url=parse_url( $_POST["company_www"]);
			if(isset($parse_url["host"])){$_POST["company_www"]=$parse_url["host"];}
		}
	}
    $sidentity=new sidentity();
	foreach ($_POST as $num=>$ligne){
		$ligne=url_decode_special_tool($ligne);
        $sidentity->SET($num,$ligne);
		$WizardSavedSettings[$num]=$ligne;
		$LicenseInfos[$num]=$ligne;
	}
	
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($WizardSavedSettings)), "WizardSavedSettings");
	$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
	$sock->SET_INFO("HideCorporateFeatures", 0);
	
	if(!invalid_mail($_POST["mail"])){echo "<br>{$_POST["mail"]} INVALID !!!!! ";return;}
	$sock->SET_INFO("IsWizardExecuted", 1);
	$sock->SET_INFO("LicenseWasRequested", 1);
	
	
	
	
}
