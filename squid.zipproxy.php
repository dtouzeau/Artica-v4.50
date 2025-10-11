<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.highcharts.inc');
	$user=new usersMenus();
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	if(isset($_GET["popup"])){parameters();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["ZipProxyListenIpAdress"])){Save();exit;}
	if(isset($_POST["EnableProxyCompressor"])){SaveEnableProxyCompressor();exit;}
	if(isset($_GET["service-status"])){service_status();exit;}
	if(isset($_GET["graph"])){graph();exit;}
tabs();
	
function tabs(){
$sock=new sockets();

$results=base64_decode($sock->getFrameWork("squid.php?ziproxy-isinstalled=yes"));
if($results<>"TRUE"){
	echo FATAL_ERROR_SHOW_128("{the_specified_module_is_not_installed}");
	return;
}
$tpl=new templates();
$page=CurrentPageName();
$array["status"]='{status}';
$array["popup"]='{parameters}';
$array["events-ziproxy"]='{compressor_requests}';
$t=time();

foreach ($array as $num=>$ligne){
	if($num=="events-ziproxy"){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.zipproxy.access.php?popup=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
		continue;
	
	}
	
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
}
echo build_artica_tabs($html, "main_zipproxy_tabs");
}	


function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$error=null;
	$GraphsDiv=null;
	$setTimeout=null;
	$EnableProxyCompressor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
	$ZipProxyUnrestricted=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZipProxyUnrestricted"));
	$SquidAsMasterPeer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAsMasterPeer"));
	$p1=Paragraphe_switch_img("{enable_http_compression_service}", 
			"{enable_http_compression_service_text}","EnableProxyCompressor",$EnableProxyCompressor,
			null,600);
	
	$p2=Paragraphe_switch_img("{unrestricted_networks}",
			"{unrestricted_networks_text}","ZipProxyUnrestricted",$ZipProxyUnrestricted,null,600);	
	
	$apply="<div style='margin-top:15px;text-align:right'>". button("{apply}","Save$t()",24)."</div>";
	
	if($SquidAsMasterPeer==0){
		$error="<p class=text-error style='font-size:16px'>{this_server_is_not_enabled_proxy_parent}</p>";
		$p1=Paragraphe_switch_disable("{enable_http_compression_service}", 
			"{enable_http_compression_service_text}","EnableProxyCompressor",$EnableProxyCompressor,
			null,600);
		$p2=Paragraphe_switch_disable("{unrestricted_networks}",
				"{unrestricted_networks_text}","ZipProxyUnrestricted",$ZipProxyUnrestricted,null,600);
		$apply=null;
	}
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/zipproxy_stats.db")){
		$GraphsDiv="<hr><div id='Graphdiv' style='width:650px;height:450px;margin-top:10px'></div>";
		$setTimeout="setTimeout(\"Fsix$t()\",800);";
	}
	
	
	$html="<div style='width:98%' class=form>
<table style='width:100%'>
<tr>
	<td valign='top' style='width:450px'>
			<div id='zipproxy_status'></div>
	</div>	
	<td valign='top' width=100%>
	$error
		$p1
		<br>
		$p2
		$apply	
		$GraphsDiv
	</td>
</tr>
</table>	
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	RefreshTab('main_zipproxy_tabs');
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableProxyCompressor',document.getElementById('EnableProxyCompressor').value);
	XHR.appendData('ZipProxyUnrestricted',document.getElementById('ZipProxyUnrestricted').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function Fsix$t(){
	AnimateDiv('Graphdiv');
	Loadjs('$page?graph=yes&container=Graphdiv',true);
}


LoadAjax('zipproxy_status','$page?service-status=yes');
$setTimeout
</script>	
	
";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}
function SaveEnableProxyCompressor(){
	
	$sock=new sockets();
	$sock->SET_INFO("EnableProxyCompressor", $_POST["EnableProxyCompressor"]);
	$sock->SET_INFO("ZipProxyUnrestricted", $_POST["ZipProxyUnrestricted"]);
	$sock->getFrameWork("squid.php?ziproxy-restart=yes");
	sleep(3);
	
}


function parameters(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$ZipProxyListenIpAdress=$sock->GET_INFO("ZipProxyListenIpAdress");

	$zipproxy_port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_port"));
	if($zipproxy_port==0){$zipproxy_port=5561;}
	$zipproxy_MaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_MaxSize"));
	if($zipproxy_MaxSize==0){$zipproxy_MaxSize=1048576;}
	$ConvertToGrayscale=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ConvertToGrayscale"));
	
	$zipproxy_ProcessHTML=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_ProcessHTML"));
	$zipproxy_ProcessCSS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_ProcessCSS"));
	$zipproxy_ProcessJS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_ProcessJS"));
	
	$net=new networking();
	$ips=$net->ALL_IPS_GET_ARRAY();
	$ips[null]="{all}";
	
	$zipproxy_MaxSize=round($zipproxy_MaxSize/1024);
	
	
	
	
	$html="
<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{listen_address}:<td>
		<td style='font-size:18px'>". Field_array_Hash($ips,"ZipProxyListenIpAdress-$t",$ZipProxyListenIpAdress,"style:font-size:18px")."<td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{listen_port}:<td>
		<td style='font-size:18px'>". Field_text("zipproxy_port-$t",$zipproxy_port,"font-size:18px;width:90px")."<td>
		<td width=1%></td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{maxsize}:<td>
		<td style='font-size:18px'>". Field_text("zipproxy_MaxSize-$t",$zipproxy_MaxSize,"font-size:18px;width:90px")."&nbsp;KB<td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{ConvertToGrayscale}:<td>
		<td style='font-size:18px'>". Field_checkbox("ConvertToGrayscale-$t",1,$ConvertToGrayscale)."<td>
		<td width=1%>".help_icon("{ConvertToGrayscale_explain}")."</td>
	</tr>						
	<tr>
		<td class=legend style='font-size:18px'>{ProcessHTML}:<td>
		<td style='font-size:18px'>". Field_checkbox("zipproxy_ProcessHTML-$t",1,$zipproxy_ProcessHTML)."<td>
		<td width=1%></td>
	</tr>		
	<tr>
		<td class=legend style='font-size:18px'>{ProcessCSS}:<td>
		<td style='font-size:18px'>". Field_checkbox("zipproxy_ProcessCSS-$t",1,$zipproxy_ProcessCSS)."<td>
		<td width=1%></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{ProcessJS}:<td>
		<td style='font-size:18px'>". Field_checkbox("zipproxy_ProcessJS-$t",1,$zipproxy_ProcessJS)."<td>
		<td width=1%></td>
	</tr>
		
	</table>
	<div style='margin-top:15px;text-align:right'><hr>". button("{apply}","Save$t()",24)."</div>			
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ZipProxyListenIpAdress',document.getElementById('ZipProxyListenIpAdress-$t').value);
	XHR.appendData('zipproxy_port',document.getElementById('zipproxy_port-$t').value);
	XHR.appendData('zipproxy_MaxSize',document.getElementById('zipproxy_MaxSize-$t').value);
	if(document.getElementById('zipproxy_ProcessHTML-$t').checked){
		XHR.appendData('zipproxy_ProcessHTML',1);
	}else{
		XHR.appendData('zipproxy_ProcessHTML',0);
	}
	if(document.getElementById('zipproxy_ProcessCSS-$t').checked){
		XHR.appendData('zipproxy_ProcessCSS',1);
	}else{
		XHR.appendData('zipproxy_ProcessCSS',0);
	}	
	if(document.getElementById('zipproxy_ProcessJS-$t').checked){
		XHR.appendData('zipproxy_ProcessJS',1);
	}else{
		XHR.appendData('zipproxy_ProcessJS',0);
	}		
	if(document.getElementById('ConvertToGrayscale-$t').checked){
		XHR.appendData('ConvertToGrayscale',1);
	}else{
		XHR.appendData('ConvertToGrayscale',0);
	}		
	
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$_POST["zipproxy_MaxSize"]=$_POST["zipproxy_MaxSize"]*1024;
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST)){
		$sock->SET_INFO($key, $value);
	}
	
	$sock->getFrameWork("squid.php?ziproxy-restart=yes");
}
function service_status(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$data=base64_decode($sock->getFrameWork("squid.php?zipproxy-status=yes"));
	$ini->loadString($data);
	$f[]=DAEMON_STATUS_ROUND("APP_ZIPROXY", $ini);
	$f[]="<div style='text-align:right'>".imgtootltip("refresh-32.png","{refresh}",
			"LoadAjax('zipproxy_status','$page?service-status=yes');")."</div>";
	echo $tpl->_ENGINE_parse_body(@implode("<p>&nbsp;</p>", $f));

}
function graph(){
	$tpl=new templates();
	
	
	$content=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/zipproxy_stats.db"));
	
	$total_incoming=$content["incoming"];
	$total_outgoing=$content["outgoing"];
	$total_noncached=$total_incoming-$total_outgoing;
	if($total_incoming==0){
		echo "document.getElementById('{$_GET["container"]}').innerHTML=''";
		return;
	}
	
	$perc=round(($total_outgoing/$total_incoming)*100,2);
	
	$received=FormatBytes($total_incoming/1024);
	$Sended=FormatBytes($total_outgoing/1024);
	
	$PieData["{compressed_data} " .FormatBytes($total_outgoing/1024) ]=round($total_outgoing/1024);
	$PieData["{non_compressed_data} " .FormatBytes($total_noncached/1024)]=round($total_noncached/1024);
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle=null;
	$highcharts->Title=$tpl->_ENGINE_parse_body("{compressed_data} {rate}:{$perc}%");
	$highcharts->subtitle="{received}: $received - {sended} $Sended";
	$highcharts->LegendSuffix=" KB";
	echo $highcharts->BuildChart();
}