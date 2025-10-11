<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_POST["SquidEnableParentNTLM"])){Save();exit;}
	if(isset($_GET["parent-ntlm-status"])){cntlm_status();exit;}
	
popup();


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	$t=time();
	
	$SquidEnableParentNTLM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableParentNTLM"));
	$SquidParentNTLMPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidParentNTLMPort"));
	$SquidParentNTLMProxy=$sock->GET_INFO("SquidParentNTLMProxy");
	$SquidParentNTLMProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidParentNTLMProxyPort"));
	$SquidParentNTLMUsername=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidParentNTLMUsername"));
	$SquidParentNTLMPassword=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidParentNTLMPassword"));
	$SquidParentSayHostname=$sock->GET_INFO("SquidParentSayHostname");
	
	if($SquidParentNTLMProxyPort==0){$SquidParentNTLMProxyPort=8080;}
	if($SquidParentNTLMPort==0){
			$SquidParentNTLMPort=rand(8080,9090);}
			
			
	if($SquidParentSayHostname==null){
		$SquidParentSayHostname=php_uname("n");
	}
	
	$p=Paragraphe_switch_img("{SquidEnableParentNTLM}", "{SquidEnableParentNTLM_text}","SquidEnableParentNTLM-$t",$SquidEnableParentNTLM,null,750);

	
$html="<table style='width:100%' class=form>
<tr>
	<td valign='top' style=width:240px'><div id='parent-ntlm-status'></div>
	".imgtootltip("refresh-32.png",null,"RefreshServ$t()",null)."
	
	</td>
	<td valign='top' style='width:90%'>
	$p
	<table style='width:100%'>
	". Field_text_table("SquidParentNTLMPort", "{local_listen_port}",$SquidParentNTLMPort,22,null,150).
	   Field_text_table("SquidParentSayHostname", "{hostname}",$SquidParentSayHostname,22,null,450).
	   Field_text_table("SquidParentNTLMProxy", "{remote_proxy}",$SquidParentNTLMProxy,22,null,450).
	   Field_text_table("SquidParentNTLMProxyPort", "{remote_port}",$SquidParentNTLMProxyPort,22,null,150).
	   Field_button_table_autonome("{apply}", "Save$t()",36).
	"</table>
	</td>
</tr>
</table>
<script>

function RefreshServ$t(){
	LoadAjax('parent-ntlm-status','$page?parent-ntlm-status=yes');
}

var xSave$t= function (obj) {
	Loadjs('squid.restart.php?onlySquid=yes&onlyreload=yes&ApplyConfToo=yes&ask=yes',true);
	RefreshTab('main_squid_prents_tabs');
}			
			
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SquidEnableParentNTLM',document.getElementById('SquidEnableParentNTLM-$t').value);
	XHR.appendData('SquidParentNTLMPort',document.getElementById('SquidParentNTLMPort').value);
	XHR.appendData('SquidParentNTLMProxy',document.getElementById('SquidParentNTLMProxy').value);
	XHR.appendData('SquidParentNTLMProxyPort',document.getElementById('SquidParentNTLMProxyPort').value);
	XHR.appendData('SquidParentSayHostname',document.getElementById('SquidParentSayHostname').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
RefreshServ$t();
</script>	   		
";
echo $tpl->_ENGINE_parse_body($html);
}
function Save(){
	$sock=new sockets();
	$sock->SET_INFO("SquidParentNTLMPort", $_POST["SquidParentNTLMPort"]);
	$sock->SET_INFO("SquidParentSayHostname", $_POST["SquidParentSayHostname"]);
	$sock->SET_INFO("SquidParentNTLMProxy", $_POST["SquidParentNTLMProxy"]);
	$sock->SET_INFO("SquidParentNTLMProxyPort", $_POST["SquidParentNTLMProxyPort"]);
	$sock->SET_INFO("SquidEnableParentNTLM", $_POST["SquidEnableParentNTLM"]);
	$sock->getFrameWork("squid.php?cntlm-parent-restart=yes");
	
	
}
function cntlm_status(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?cntlm-ini-status=yes')));
	$APP_CNTLM=DAEMON_STATUS_ROUND("APP_CNTLM_PARENT",$ini,null,0);
	echo $tpl->_ENGINE_parse_body($APP_CNTLM);
}
?>