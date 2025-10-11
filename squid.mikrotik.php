<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.system.network.inc');
	
	$user=new usersMenus();

	if($user->SQUID_INSTALLED==false){
		if(!$user->WEBSTATS_APPLIANCE){
			$tpl=new templates();
			echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
			die("DIE " .__FILE__." Line: ".__LINE__);exit();
		}
	}
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	
	
	if(isset($_POST["SquidMikroTikTOS"])){SquidMikroTikTOS();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	
	if(isset($_GET["js"])){js();exit;}
	
	
js();	
function js(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=@mysqli_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM proxy_ports WHERE MIKROTIK_PORT=1 and enabled=1"));
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."')";return;}
	
	$Tcount=intval($ligne["tcount"]);
	
	if($Tcount==0){
		echo "alert('".$tpl->javascript_parse_text("{no_mikrotik_port_saved}")."');";
		return;
		
	}
	
	$title=$tpl->javascript_parse_text("MikrotiK {options}");
	echo "YahooWin3('935','$page?popup=yes','$title')";
}


function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$t=time();
	$SquidMikroTikTOS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMikroTikTOS"));
	$SquidMikrotikMaskerade=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMikrotikMaskerade"));
	if($SquidMikroTikTOS==0){$SquidMikroTikTOS=12;}

	
	
	$ip=new networking();
	
	$interfaces=$ip->Local_interfaces();
	$MAIN_INTERFACES=$ip->NETWORK_ALL_INTERFACES();
	unset($interfaces["lo"]);
	
	$ayDscp = array(0 => '0x00',8 => '0x20',10 => '0x28',12 => '0x30',14 => '0x38',16 => '0x40',18 => '0x48',20 => '0x50',22 => '0x58',24 => '0x60',26 => '0x68',28 => '0x70',30 => '0x78',32 => '0x80',34 => '0x88',36 => '0x90',38 => '0x98',40 => '0xA0',46 => '0xB8',48 => '0xC0',56 => '0xE0');
	
	
	
	foreach ($interfaces as $eth){
		if(preg_match("#^gre#", $eth)){continue;}
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$arrayIP[$eth]="$nic->IPADDR";
	}
	
	$results=$q->QUERY_SQL("SELECT * FROM proxy_ports WHERE MIKROTIK_PORT=1 and enabled=1");
	
	$f[]="/ip firewall filter add action=reject chain=forward comment=\"Artica: Deny QUIC protocol HTTP/UDP\" dst-address=0.0.0.0 dst-port=80 protocol=udp src-address=0.0.0.0";
	$f[]="/ip firewall nat add action=masquerade chain=srcnat comment=\"Artica: Mandatory masquerade for Proxy\"";
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$port=$ligne["port"];
		$IPADDR=$arrayIP[$ligne["nic"]];
		$MAC=$MAIN_INTERFACES[$ligne["nic"]]["MAC"];
		$ALLIP[$IPADDR]=$MAC;
		$UseSSL=$ligne["UseSSL"];
		$f[]="/queue tree add name=ProxyPort{$port} packet-mark=ProxyCached{$port} parent=global queue=default comment=\"Artica: Full bandwidth for Cached content marked as $SquidMikroTikTOS {$ayDscp[$SquidMikroTikTOS]}\"";
		if($UseSSL==0){$f[]="/ip firewall mangle add action=mark-routing chain=prerouting comment=\"Artica: HTTP mark ProxyPort{$port}\" dst-port=80 src-mac-address=!$MAC new-routing-mark=ProxyPort{$port} passthrough=no protocol=tcp";}
		if($UseSSL==1){$f[]="/ip firewall mangle add action=mark-routing chain=prerouting comment=\"Artica: HTTPS 80 mark ProxyPort{$port}\" dst-port=443 src-mac-address=!$MAC new-routing-mark=ProxyPort{$port} passthrough=no protocol=tcp";}
		$f[]="/ip firewall mangle add action=mark-packet chain=forward comment=\"Artica: Marked cached packets from Proxy\" dscp=$SquidMikroTikTOS new-packet-mark=ProxyPort{$port} passthrough=no";
		$f[]="/ip route add check-gateway=ping distance=1 gateway=$IPADDR routing-mark=ProxyPort{$port} comment=\"Artica: mark ProxyPort{$port} to proxy gateway\"";
		
		
		
		
		if($SquidMikrotikMaskerade==0){
			if($UseSSL==0){
				$f[]="/ip firewall mangle add action=mark-connection chain=prerouting comment=\"Artica: Mark HTTP Connections to Connect{$port}\" dst-port=80 new-connection-mark=Connect{$port} passthrough=no protocol=tcp src-mac-address=$MAC";
				$f[]="/ip firewall mangle add action=mark-routing chain=prerouting comment=\"Mangle Proxy HTTP to Internet mark Connect{$port}\" connection-mark=Connect{$port} new-routing-mark=ProxyPort{$port} passthrough=no protocol=tcp src-mac-address=!$MAC src-port=80";
			}
			if($UseSSL==1){
				$f[]="/ip firewall mangle add action=mark-connection chain=prerouting comment=\"Artica: Mark SSL Connections to Connect{$port}\" dst-port=443 new-connection-mark=Connect{$port} passthrough=no protocol=tcp src-mac-address=$MAC";
				$f[]="/ip firewall mangle add action=mark-routing chain=prerouting comment=\"Mangle Proxy SSL to Internet mark Connect{$port}\" connection-mark=Connect{$port} new-routing-mark=ProxyPort{$port} passthrough=no protocol=tcp src-mac-address=!$MAC src-port=443";
			}
			
		}
		
		
	}
	

	
	
	
	while (list ($a, $b) = each ($ayDscp) ){
		if($a==0){continue;}
		$DSCP[$a]=$a;
	}
	
	
	$description="<textarea name='category_text'
	id='category_text-$t' style='height:250px;overflow:auto;font-family:Courier New;width:99%;
	font-size:12px !important'>"
	.@implode("\n", $f)."</textarea>";

$html="
<div style='font-size:36px'>Mikrotik</div>
<div style='font-size:18px' class=explain>{mikrotik_cmd_line_explain}</div>
	$description	
		
<div id='SquidAVParamWCCP' style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td style='font-size:22px' class=legend nowrap>DSCP:</td>
		<td>". Field_array_Hash($DSCP,"SquidMikroTikTOS-$t",$SquidMikroTikTOS,"style:font-size:22px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td style='font-size:22px' class=legend nowrap>MASQUERADE:</td>
		<td>". Field_checkbox_design("SquidMikrotikMaskerade-$t",1,"$SquidMikrotikMaskerade")."</td>
		<td>&nbsp;</td>
	</tr>	
		
	<tr>
		<td colspan=3 align='right'>
			<hr>
				". button("{apply}","Save$t()",32)."
		</td>
	</tr>
	</table>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	YahooWin3Hide();
	Loadjs('$page');
}

function Save$t(){
	var XHR = new XHRConnection();
	var SquidMikrotikMaskerade=0;
	XHR.appendData('SquidMikroTikTOS',
	document.getElementById('SquidMikroTikTOS-$t').value);
	if( document.getElementById('SquidMikrotikMaskerade-$t').checked){SquidMikrotikMaskerade=1;}
	XHR.appendData('SquidMikrotikMaskerade',SquidMikrotikMaskerade);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function SquidMikroTikTOS(){
	$sock=new sockets();
	$sock->SET_INFO("SquidMikroTikTOS", $_POST["SquidMikroTikTOS"]);
	$sock->SET_INFO("SquidMikrotikMaskerade", $_POST["SquidMikrotikMaskerade"]);
	
}
