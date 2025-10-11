<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');

	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	
	
	if(isset($_GET["ShowFile-popup"])){ShowFile_popup();exit;}
	if(isset($_GET["ShowFile"])){ShowFile_js();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["ports"])){ports();exit;}
	if(isset($_GET["ssl"])){ssl();exit;}
	if(isset($_GET["meta"])){meta();exit;}
	if(isset($_GET["acls"])){acls();exit;}
	if(isset($_GET["acls-extern"])){acls_extern();exit;}
	if(isset($_GET["caches"])){caches();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["show-content-group-js"])){show_content_group_js();exit;}
	if(isset($_GET["show-content-group"])){show_content_group();exit;}
	if(isset($_POST["DenySquidWriteConf"])){DenySquidWriteConf();exit;}
	if(isset($_POST["SQUID_CONTENT"])){SQUID_CONTENT();exit;}
	if(isset($_POST["SQUID_PORT_CONTENT"])){SQUID_PORT_CONTENT();exit;}
	if(isset($_POST["SQUID_SSL_CONTENT"])){SQUID_SSL_CONTENT();exit;}
	if(isset($_POST["SQUID_EXTERNAL_CONTENT"])){SQUID_EXTERNAL_CONTENT();exit;}
	
	
	js();

function ShowFile_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$level=intval($_GET["level"]);
	$level++;
	$YahooWin=3+$level;
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{configuration_file}::{$_GET["ShowFile"]}");
	header("content-type: application/x-javascript");
	$html="YahooWin{$YahooWin}('990','$page?ShowFile-popup=".urlencode($_GET["ShowFile"])."&level=$level','$title');";
	echo $html;	
	
}
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{configuration_file}");
	header("content-type: application/x-javascript");
	$html="YahooWin3('1200','$page?popup=yes','$title');";
	echo $html;
	
	
}
function show_content_group_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{configuration_file}");
	header("content-type: application/x-javascript");
	$groupid=$_GET["show-content-group-js"];
	$q=new mysql_squid_builder();
	$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$groupid'"));
	
	$title=$ligne2["GroupName"];
	$html="YahooWin5('700','$page?show-content-group=$groupid','$title');";
	echo $html;	
}
function show_content_group(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	$t=time();
	$groupid=$_GET["show-content-group"];
	$data=base64_decode($sock->getFrameWork("squid.php?show-content-group=$groupid"));
	echo "	<textarea 
		style='width:99%;height:350px;overflow:auto;border:5px solid #CCCCCC;font-size:14px !important;
		font-weight:bold;padding:3px;font-family:Courier New;'
		id='SQUID_CONTENT-$t'>$data</textarea>";
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]='{configuration_file}';
	
	foreach ($array as $num=>$ligne){
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:18px'><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
	
	}
	
	
	echo build_artica_tabs($html, "squid_conf_tabs");
}

function acls_extern(){
	$sock=new sockets();
	$sock->getFrameWork("squid2.php?squid-conf-externals=yes");
	$datas=@file_get_contents(PROGRESS_DIR."/squid-extern.conf");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div id='$t'></div>
	<div style='width:98%' class=form>
	<textarea
	style='width:99%;height:650px;overflow:auto;border:5px solid #CCCCCC;font-size:14px !important;
	font-weight:normal;font-family:Courier New;color:black !important;padding:3px'
	id='SQUID_CONTENT-$t'>$datas</textarea>
	<center><hr>". $tpl->_ENGINE_parse_body(button("{apply}", "SaveUserConfFile$t()",22))."</center>
	</div>
	<script>
	var x_DenySquidWriteConfSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
	}

	
	var x_SaveUserConfFile= function (obj) {
			var results=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(results.length>3){alert(results);return;}
	}
	
	function SaveUserConfFile$t(){
		var XHR = new XHRConnection();
		XHR.appendData('SQUID_EXTERNAL_CONTENT', encodeURIComponent(document.getElementById('SQUID_CONTENT-$t').value));
		XHR.sendAndLoad('$page', 'POST',x_SaveUserConfFile);
	}
	</script>
	";
	echo $html;
}



function caches(){
	$page=CurrentPageName();
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("cmd.php?squid-conf-view=yes"));
	$sock=new sockets();
	$tpl=new templates();
	echo "<center style='margin:10px;margin-bottom:30px'>".$tpl->_ENGINE_parse_body(button("{reconfigure}","Loadjs('squid.compile.progress.php');",32))."</center>";
	$table=explode("\n",$datas);
	foreach ($table as $num=>$ligne){
		$ligne=trim($ligne);
		if(preg_match("#^cache_dir#", $ligne,$re)){
			echo "<div style='font-size:16px;font-weight:bold;margin:10px;font-family:Courier New;'>$ligne</div>";
		}
	}
	
	
		
	
}

function meta(){
	$sock=new sockets();
	$sock->getFrameWork("squid2.php?squid-conf-meta=yes");
	$datas=@file_get_contents(PROGRESS_DIR."/squid-meta.conf");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	//<center><hr>". $tpl->_ENGINE_parse_body(button("{apply}", "SaveUserConfFile$t()",22))."</center>
	$html="
	<div id='$t'></div>
	<div style='width:98%' class=form>
	<textarea
	style='width:99%;height:650px;overflow:auto;border:5px solid #CCCCCC;font-size:14px !important;
	font-weight:normal;font-family:Courier New;color:black !important;padding:3px'
	id='SQUID_CONTENT-$t'>$datas</textarea>
	
			</div>
			<script>
			var x_DenySquidWriteConfSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
	}
	
	
			var x_SaveUserConfFile= function (obj) {
			var results=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(results.length>3){alert(results);return;}
	}
	
			function SaveUserConfFile$t(){
			var XHR = new XHRConnection();
			XHR.appendData('SQUID_SSL_CONTENT', encodeURIComponent(document.getElementById('SQUID_CONTENT-$t').value));
			XHR.sendAndLoad('$page', 'POST',x_SaveUserConfFile);
	}
			</script>
			";
			echo $html;
	
	
	
	
	}

function ssl(){
	$sock=new sockets();
	$sock->getFrameWork("squid2.php?squid-conf-ssl=yes");
	$datas=@file_get_contents(PROGRESS_DIR."/squid-ssl.conf");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div id='$t'></div>
	<div style='width:98%' class=form>
	<textarea
	style='width:99%;height:650px;overflow:auto;border:5px solid #CCCCCC;font-size:14px !important;
	font-weight:normal;font-family:Courier New;color:black !important;padding:3px'
	id='SQUID_CONTENT-$t'>$datas</textarea>
	<center><hr>". $tpl->_ENGINE_parse_body(button("{apply}", "SaveUserConfFile$t()",22))."</center>
			</div>
			<script>
			var x_DenySquidWriteConfSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
	}
	
	
			var x_SaveUserConfFile= function (obj) {
			var results=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(results.length>3){alert(results);return;}
	}
	
			function SaveUserConfFile$t(){
			var XHR = new XHRConnection();
			XHR.appendData('SQUID_SSL_CONTENT', encodeURIComponent(document.getElementById('SQUID_CONTENT-$t').value));
			XHR.sendAndLoad('$page', 'POST',x_SaveUserConfFile);
	}
			</script>
			";
	echo $html;
	
		
	
	
}

function ports(){
	$sock=new sockets();
	$sock->getFrameWork("squid2.php?squid-conf-ports=yes");
	$datas=@file_get_contents(PROGRESS_DIR."/squid-ports.conf");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div id='$t'></div>
	<div style='width:98%' class=form>
	<textarea
	style='width:99%;height:650px;overflow:auto;border:5px solid #CCCCCC;font-size:14px !important;
	font-weight:normal;font-family:Courier New;color:black !important;padding:3px'
	id='SQUID_CONTENT-$t'>$datas</textarea>
	<center><hr>". $tpl->_ENGINE_parse_body(button("{apply}", "SaveUserConfFile$t()",22))."</center>
	</div>
	<script>
	var x_DenySquidWriteConfSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
	}

	
	var x_SaveUserConfFile= function (obj) {
			var results=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(results.length>3){alert(results);return;}
	}
	
	function SaveUserConfFile$t(){
		var XHR = new XHRConnection();
		XHR.appendData('SQUID_PORT_CONTENT', encodeURIComponent(document.getElementById('SQUID_CONTENT-$t').value));
		XHR.sendAndLoad('$page', 'POST',x_SaveUserConfFile);
	}
	</script>
	";
	echo $html;
	
		
}
function ShowFile_popup(){
	
	echo displayfile($_GET["ShowFile-popup"]);
}

function displayfile($file){
	$page=CurrentPageName();
	$html=null;
	$zSize=@filesize("/etc/squid3/$file");
	if($zSize==0){
		$tpl=new templates();
		$html="<div style='font-size:18px'>$file {is_empty}</div>";
		return $tpl->_ENGINE_parse_body($html);
	}
	$level=intval($_GET["level"]);
	$size=FormatBytes($zSize/1024);
	$datas=file_get_contents("/etc/squid3/$file");
	$fontsize=14;
	$tbr=explode("\n",$datas);
	$html="<div style='font-size:28px;margin-bottom:20px'>$file $size (".count($tbr)." lines)</div><div style='width:98%' class=form>";
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$q=new mysql_squid_builder();
	while (list ($num, $ligne) = each ($tbr) ){
		$color="#660000";
		$addon=null;
		$ligne=trim($ligne);
		if(substr($ligne, 0,1)=="#"){$color="#8a8a8a";}
		
			if(preg_match("#acls\/container_([0-9]+)\.txt#", $ligne,$re)){
				$groupid=$re[1];
				$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$groupid'"));
				$groupname=$ligne2["GroupName"];
				$hrefGP="<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$groupid',true);\" style='color:{$color};font-size:{$fontsize}px;font-family:Courier New;text-decoration:underline'>";
				$addon="&nbsp;-&nbsp;<i>$hrefGP$groupname</i></a>";
			}
			
			if(substr($ligne, 0,1)<>"#"){
			if(preg_match("#Group([0-9]+)#", $ligne,$re)){
				$groupid=$re[1];
				$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$groupid'"));
				$groupname=$ligne2["GroupName"];
				$hrefGP="<a href=\"javascript:blur();\"  OnClick=\"Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$groupid',true);\" style='color:{$color};font-size:{$fontsize}px;font-family:Courier New;text-decoration:underline'>";
				$ligne=str_replace("Group{$groupid}","Group{$groupid}&nbsp;<i>($hrefGP$groupname</a>)</i>",$ligne);
				$addon=null;
			}
			}
		
		if(preg_match("#include\s+\/etc\/squid3\/(.+)#i", $ligne,$re)){
			$html=$html."<div style='color:$color;font-size:{$fontsize}px;font-family:Courier New;font-weight:bold'>include\t<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.conf.php?ShowFile=".urlencode($re[1])."&level=$level');\" style='color:$color;font-size:{$fontsize}px;font-family:Courier New;text-decoration:underline;font-weight:bold'>/etc/squid3/{$re[1]}</a>$addon</div>\n";
			continue;
				
		}
		if(preg_match("#(.*?)\"\/etc\/squid3\/(.+?)\"#i", $ligne,$re)){
		$html=$html."<div style='color:$color;font-size:{$fontsize}px;font-family:Courier New;'>{$re[1]}<strong>/etc/squid3/</strong><a href=\"javascript:blur();\"
		OnClick=\"Loadjs('squid.conf.php?ShowFile=".urlencode($re[2])."&level=$level');\"
		style='color:$color;font-size:{$fontsize}px;font-family:Courier New;text-decoration:underline;font-weight:bold'>{$re[2]}</a>$addon</div>\n";
		continue;
	
		}
		
		if(trim($ligne)==null){$ligne="&nbsp;";}
		
		$html=$html."<div style='color:$color;font-size:{$fontsize}px;font-family:Courier New;'>$ligne$addon</div>\n";
	}
	
	return $html."</div>";
}

function popup(){
	$html=null;
	$sock=new sockets();
	$sock->getFrameWork("squid.php?squid-conf-copy=yes");
	$datas=@file_get_contents(PROGRESS_DIR."/squid.conf");
	$tpl=new templates();
	$DenySquidWriteConf=$sock->GET_INFO("DenySquidWriteConf");
	if(!is_numeric($DenySquidWriteConf)){$DenySquidWriteConf=0;}
	$t=time();
	$page=CurrentPageName();
	$html="
	<div id='$t'></div>
	<div style='width:98%;margin-bottom:20px'>
	<table>
	<tr>
		<td class=legend style='font-size:16px;font-weight:bold'>". $tpl->_ENGINE_parse_body("{deny_artica_to_write_config}")."</td>	
		<td>". Field_checkbox_design("DenySquidWriteConf", 1,$DenySquidWriteConf,"DenySquidWriteConfSave()")."</td>
	</tr>
	</table>
	</div>".

	$html=$html.displayfile("squid.conf");
	
	$html=$html."
	<script>
		var x_DenySquidWriteConfSave= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
		}
	
	function DenySquidWriteConfSave(){
		var XHR = new XHRConnection();
		var DenySquidWriteConf=0;
		if(document.getElementById('DenySquidWriteConf').checked){
			DenySquidWriteConf=1;
		}
		XHR.appendData('DenySquidWriteConf', DenySquidWriteConf);
		XHR.sendAndLoad('$page', 'POST',x_DenySquidWriteConfSave);
	}
	
	var x_SaveUserConfFile= function (obj) {
			var results=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(results.length>3){alert(results);return;}
		}
	
	function SaveUserConfFile(){
		var XHR = new XHRConnection();
		XHR.appendData('SQUID_CONTENT', encodeURIComponent(document.getElementById('SQUID_CONTENT-$t').value));
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveUserConfFile);
	}	
</script>	
	";
echo $html;
	
}
function DenySquidWriteConf(){
	$sock=new sockets();
	$sock->SET_INFO("DenySquidWriteConf", $_POST["DenySquidWriteConf"]);
	
}
function SQUID_CONTENT(){
	$_POST["SQUID_CONTENT"]=url_decode_special_tool($_POST["SQUID_CONTENT"]);

	$sock=new sockets();
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
	@file_put_contents("ressources/logs/web/squid.conf", $_POST["SQUID_CONTENT"]);
	$datas=base64_decode($sock->getFrameWork("squid.php?saveSquidContent=yes"));
	echo $datas;
}

function SQUID_SSL_CONTENT(){
	$_POST["SQUID_SSL_CONTENT"]=url_decode_special_tool($_POST["SQUID_SSL_CONTENT"]);
	
	$sock=new sockets();
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
	@file_put_contents("ressources/logs/web/ssl.conf", $_POST["SQUID_SSL_CONTENT"]);
	$datas=base64_decode($sock->getFrameWork("squid2.php?saveSquidSSLContent=yes"));
	echo $datas;
	
}
function SQUID_EXTERNAL_CONTENT(){
	$_POST["SQUID_EXTERNAL_CONTENT"]=url_decode_special_tool($_POST["SQUID_EXTERNAL_CONTENT"]);
	
	$sock=new sockets();
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
	@file_put_contents("ressources/logs/web/externals.conf", $_POST["SQUID_EXTERNAL_CONTENT"]);
	$datas=base64_decode($sock->getFrameWork("squid2.php?saveSquidExternalContent=yes"));
	echo $datas;
	
}

function SQUID_PORT_CONTENT(){
	$_POST["SQUID_PORT_CONTENT"]=url_decode_special_tool($_POST["SQUID_PORT_CONTENT"]);
	
	$sock=new sockets();
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
	@file_put_contents("ressources/logs/web/squid_ports.conf", $_POST["SQUID_PORT_CONTENT"]);
	$datas=base64_decode($sock->getFrameWork("squid2.php?saveSquidPortContent=yes"));
	echo $datas;	
	
}

?>