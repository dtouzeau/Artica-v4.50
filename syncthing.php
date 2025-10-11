<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_POST["SyncThingPort"])){save();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_GET["params"])){params();exit;}
	if(isset($_GET["status"])){services_status();exit;}
	
	
tabs();


function tabs(){
	
	$sock=new sockets();
	
	$syncthing_installed=trim(base64_decode($sock->getFrameWork("system.php?syncthing-installed=yes")));
	
	if($syncthing_installed<>"TRUE"){
		echo FATAL_ERROR_SHOW_128("{ERROR_SERVICE_NOT_INSTALLED} <hr><center>".button("{manual_update}", "Loadjs('update.upload.php')",32)."</center>");
		return;
	}
	$tpl=new templates();
	$page=CurrentPageName();
	$array["parameters"]='{parameters}';
	$array["events"]="{events}";
	$t=$_GET["t"];
	foreach ($array as $num=>$ligne){
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.rdpproxy-events.php\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&ID=$ID\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_syncthing_tabs")."<script>LeftDesign('sync-256-white.png');</script>";
		
}

function parameters(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	

	$html="
	<div style='font-size:28px;margin-bottom:40px'>{APP_SYNCTHING}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td style='width:350px;vertical-align:top'>
		<div id='APP_SYNCTHING_STATUS'></div>
		
	<div style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('APP_SYNCTHING_STATUS','$page?status=yes',false);")."</div>	
	</td>
	<td style='width:560px'><div id='APP_SYNCTHING_PARAMS'></div></td>
	</tr>
	</table>
	</div>
	<script>
	LoadAjax('APP_SYNCTHING_PARAMS','$page?params=yes',false);
	</script>
		
	";
	echo $tpl->_ENGINE_parse_body($html);

}

function services_status(){

	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?syncthing-ini-status=yes')));
	$APP_SYNCTHING=DAEMON_STATUS_ROUND("APP_SYNCTHING",$ini,null,0);
	$tr[]=$APP_SYNCTHING;
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body(@implode("<p>&nbsp;</p>", $tr));

}

function params(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	

	$EnableSyncThing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyncThing"));
	$SyncThingPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncThingPort"));
	if($SyncThingPort==0){$SyncThingPort=8000;}


	$html=Paragraphe_switch_img("{enable_syncthing}", "{enable_syncthing_text}","EnableSyncThing",$EnableSyncThing,null,740).
	"<br>".
	"<table style='width:100%'>
		<tr>
			<td valign='middle' class=legend style='font-size:22px'>{listen_port}:</td>
			<td>". Field_text("SyncThingPort",$SyncThingPort,"font-size:22px;width:110px")."</td>
			</tr>
			<tr>
			<td valign='middle' class=legend style='font-size:22px'>{webconsole}:</td>
			<td><a href=\"http://{$_SERVER["SERVER_ADDR"]}:{$SyncThingPort}/\"
			style='font-size:22px;text-decoration:underline'
			target=_new>http://{$_SERVER["SERVER_ADDR"]}:{$SyncThingPort}</a></td>
			
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",28)."</td>
		</tr>
		</table>
<script>
	var xSave$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		RefreshTab('main_syncthing_tabs');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableSyncThing',document.getElementById('EnableSyncThing').value);
	XHR.appendData('SyncThingPort',document.getElementById('SyncThingPort').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);

}
	LoadAjax('APP_SYNCTHING_STATUS','$page?status=yes',false);
</script>";

	echo $tpl->_ENGINE_parse_body($html);


}


function save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSyncThing", $_POST["EnableSyncThing"]);
	$sock->SET_INFO("SyncThingPort", $_POST["SyncThingPort"]);
	$sock->getFrameWork("system.php?syncthing-restart=yes");
}