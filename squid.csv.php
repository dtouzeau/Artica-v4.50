<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["EnableSquidCSV"])){EnableSquidCSV();exit;}
	js();
	
	
	
function js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{squid_csv_logs}");
	$page=CurrentPageName();
	$html="YahooWin3('755','$page?popup=yes','$title');";
	echo $html;	
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$squid=new squidbee();
	$sock=new sockets();	
	$t=time();
	$EnableSquidCSV=$sock->GET_INFO("EnableSquidCSV");
	if(!is_numeric($EnableSquidCSV)){$EnableSquidCSV=0;}
	$p=Paragraphe_switch_img("{enable_csv_generator}", "{enable_csv_generator_text}","EnableSquidCSV-$t",$EnableSquidCSV,null,"450");
	$SquidCsvParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCsvParams")));
	
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	
	
	$array["a1"]="{client_source_ip_address}";
	$array["a2"]="{client_fqdn}";
	$array["a3"]="{ComputerMacAddress}";
	$array["a4"]="{server_ip_address}";
	$array["a5"]="{server_fqdn}";
	$array["a6"]="{username}";
	$array["a7"]="{squid_request_method_log}";
	$array["a8"]="{squid_request_url_log}";
	$array["a9"]="{squid_request_protocol_log}";
	$array["a10"]="{squid_request_statuscode_log}";
	$array["a11"]="{squid_request_replysize_log}";
	$array["a12"]="{squid_request_requestsstats_log}";
	$array["a13"]="{useragent}";
	$array["a14"]="{xforwardfor}";	
	
	$disable31=0;
	if(($squid->IS_31) && (!$squid->IS_32)){
		$disable31=1;
		
	}
	
	
	
	if( (count($SquidCsvParams)==0) OR !is_array($SquidCsvParams)){while (list ($code, $explain) = each ($array) ){$SquidCsvParams[$code]=1;}reset($array);}
	$help=Paragraphe("help-64.png","{help}","{online_help}","javascript:s_PopUpFull('http://proxy-appliance.org/index.php?cID=245','1024','900');");
	//
	
	while (list ($code, $explain) = each ($array) ){
		$table[]="
		<tr>
			<td class=legend style='font-size:14px' style='width:5%' nowrap>$explain</td>
			<td style='width:99%' align='left'>". Field_checkbox("$code-$t", 1,$SquidCsvParams[$code])."</td>
		</tr>
		";
		
		$js[]="if(document.getElementById('$code-$t')){if(document.getElementById('$code-$t').checked){XHR.appendData('$code',1);}else{XHR.appendData('$code',0);}}";
		
	}
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2>
	<table>
		<tr>
			<td>$p</td>
			<td>$help</td>
		</tr>
	</table>
	</td>
	</tr>
	".@implode("\n", $table)."
	<tr>
		<td align='right' colspan=2><hr>". button("{apply}", "SaveCSVGen()","16")."</td>
	</tr>
	</table>
	
	<script>
	var x_SaveCSVGen=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}
		document.getElementById('$t').innerHTML='';
		
	}	
	
	function SaveCSVGen(){
		var lock=$EnableRemoteStatisticsAppliance;
		if(lock==1){Loadjs('squid.newbee.php?error-remote-appliance=yes');return;}		
		var XHR = new XHRConnection();
		XHR.appendData('EnableSquidCSV',document.getElementById('EnableSquidCSV-$t').value);
		".@implode("\n", $js)."
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveCSVGen);	
	}	

	function disable31(){
		var dd=$disable31;
		if(dd==0){return;}
		document.getElementById('a4-$t').checked=false;
		document.getElementById('a3-$t').checked=false;
		document.getElementById('a4-$t').disabled=true
		document.getElementById('a3-$t').disabled=true;	
	}
	disable31();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function EnableSquidCSV(){
	$sock=new sockets();
	$sock->SET_INFO("EnableSquidCSV",$_POST["EnableSquidCSV"]);
	unset($_POST["EnableSquidCSV"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SquidCsvParams");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{you_need_to_squidrotatecsv}",1);
	$sock->REST_API("/proxy/acls/php/compile");
	
}