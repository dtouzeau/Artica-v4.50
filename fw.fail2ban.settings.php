<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["main"])){main();exit;}
if(isset($_POST["Fail2bantime"])){save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_VERSION");
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_FAIL2BAN} v$version</h1></div>

	</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-fail2ban-settings'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-fail2ban-settings','$page?main=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}


function main(){
	$sock=new sockets();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$users=new usersMenus();
	
	$Fail2bantime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2bantime"));
	$Fail2findtime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2findtime"));
	$Fail2maxretry=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2maxretry"));
	$Fail2Purge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2Purge"));
	if($Fail2bantime==0){$Fail2bantime=600;}
	if($Fail2findtime==0){$Fail2findtime=600;}
	if($Fail2maxretry==0){$Fail2maxretry=5;}
	if($Fail2Purge==0){$Fail2Purge=7;}
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$Fail2Purge=7;}
	
	$form[]=$tpl->field_numeric("Fail2bantime","{bantime} ({seconds})",$Fail2bantime,"{Fail2bantime}");
	$form[]=$tpl->field_numeric("Fail2findtime","{findtime}",$Fail2findtime,"{Fail2findtime}");
	$form[]=$tpl->field_numeric("Fail2maxretry","{maxretry}",$Fail2maxretry,"{Fail2maxretry}");
	$form[]=$tpl->field_section("{statistics}");
	$form[]=$tpl->field_numeric("Fail2Purge","{retention_days}",$Fail2maxretry,"{retention_days}");
	
	echo $tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}","Loadjs('fw.fail2ban.dashboard.php?reconfigure-js=yes')","AsFirewallManager",true);
}

function save(){
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
		
	}
}
