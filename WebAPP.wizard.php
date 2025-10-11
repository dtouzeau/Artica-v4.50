<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["hostname"])){save();exit;}
	
js();

function js(){

	$tpl=new templates();
	$page=CurrentPageName();
	$CREATE_YOUR_FIRST_WEBMAIL=$tpl->javascript_parse_text("{CREATE_YOUR_FIRST_WEBMAIL}");
	echo "YahooWinBrowse('700','$page?popup=yes','$CREATE_YOUR_FIRST_WEBMAIL',true)";
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$sock->SET_INFO("ZarafaWebAPPWizard",1);
	$SERVER_ADDR=$_SERVER["SERVER_ADDR"];
	$t=time();
	$html="<div class=explain style='font-size:16px'>{CREATE_YOUR_FIRST_WEBMAIL_W}</div>
	<div style='width:98%' class=form>
		<table style='width:100%'>
			<tr>
				<td style='font-size:16px' class=legend>{hostname}:</td>
				<td>". Field_text("hostname-$t","shadow:$SERVER_ADDR","font-size:16px;width:90%",null,null,null,false,"SaveK$t(event)")."</td>
			</tr>
			<tr>
				<td align='right' colspan=2><hr>". button("{apply}","Save$t()",18)."</td>
			</tr>
		</table>
	</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	CacheOff();
	YahooWinBrowseHide();
}	
						
function SaveK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('hostname',document.getElementById('hostname-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>
";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function save(){
	$sock=new sockets();
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	$hostname=$_POST["hostname"];
	if($hostname==null){echo "Please define a Web server hostname\n";return;}
	$q=new mysql();
	$domainname=null;
	if($EnableFreeWeb==0){
		$sock->SET_INFO("EnableFreeWeb",1);
		$sock->SET_INFO("EnableApacheSystem",1);
		$sock->getFrameWork("freeweb.php?changeinit-off=yes");
		$sock->getFrameWork("cmd.php?restart-artica-status=yes");
		$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	}
	
	if(strpos($hostname, ".")){
		$tr=explode(".",$hostname);
		unset($tr[0]);
		$domainname=@implode(".", $tr);
	}
	
	$q->QUERY_SQL("DELETE FROM freeweb WHERE `servername`='$hostname'","artica_backup");
	
	$sql="INSERT INTO freeweb (useSSL,servername,domainname,groupware) VALUES('0','$hostname','$domainname','WEBAPP')";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='DELETE_FREEWEB' AND `servername`='$hostname'";
	$ligneDrup=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
	if(!is_numeric($ligneDrup["ID"])){$ligneDrup["ID"]=0;}
	if($ligneDrup["ID"]==0){
		$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('INSTALL_GROUPWARE','$hostname')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	$sock=new sockets();
	$hostname=urlencode($hostname);
	$sock->getFrameWork("drupal.php?perform-orders=yes");
	$sock->getFrameWork("nginx.php?restart=yes");	
	$sock->getFrameWork("freeweb.php?rebuild-vhost=yes&servername=$hostname");
}

