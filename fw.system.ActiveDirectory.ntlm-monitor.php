<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["NTLMWatchdogEmergency"])){Save();exit;}

js();

function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog1("NTLM {watchdog}", "$page?popup=yes");
}

function popup(){
	$page=CurrentPageName();
    $tpl=new template_admin();
    $NTLMWatchdogFreq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTLMWatchdogFreq"));
    $NTLMWatchdogEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTLMWatchdogEmergency"));
    if($NTLMWatchdogFreq==0){$NTLMWatchdogFreq=10;}

    for($i=2;$i<55;$i++){$hash[$i]="$i {minutes}";}

    $form[]=$tpl->field_checkbox("NTLMWatchdogEmergency","{turn_into_emergency_automatically}",$NTLMWatchdogEmergency);
    $form[]=$tpl->field_array_hash($hash,"NTLMWatchdogFreq","{update_frequency}",$NTLMWatchdogFreq);

    echo $tpl->form_outside("NTLM {watchdog}",$form,"{ntlm_watchdog_explain}","{apply}","LoadAjaxSilent('table-adstate','fw.proxy.ad.status.php?table=yes');");


}
function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->REST_API("/winbindd/reload");

}

