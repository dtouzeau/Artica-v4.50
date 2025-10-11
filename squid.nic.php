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
	if(isset($_GET["perfs"])){perfs();exit;}
	if(isset($_POST["SquidBinIpaddr"])){save();exit;}
	js();

	
function js(){

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{listen_address}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$html="
		YahooWin3('420','$page?popup=yes','$title');
	
	";
		echo $html;
	
	
	
	
}

function save(){
	$sock=new sockets();
	if($_POST["SquidBinIpaddr"]==null){$_POST["SquidBinIpaddr"]="0.0.0.0";}
	$sock->SET_INFO("SquidBinIpaddr", $_POST["SquidBinIpaddr"]);
	
	if(isset($_POST["tcp_outgoing_address"])){
		$squid=new squidbee();
		$squid->global_conf_array["tcp_outgoing_address"]=$_POST["tcp_outgoing_address"];
		$squid->SaveToLdap(true);
	}
	
}




function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	$MikrotikTransparent=intval($sock->GET_INFO('MikrotikTransparent'));
	$ip=new networking();
	$t=time();
	$pfws=$ip->ALL_IPS_GET_ARRAY();
	$pfws[null]="{none}";
	
	if($MikrotikTransparent==0){
		$squid=new squidbee();
		$tcp_outgoing_address=$squid->global_conf_array["tcp_outgoing_address"];
		
		$ips=$ip->ALL_IPS_GET_ARRAY();
		$ips["0.0.0.0"]="{all}";
		
		$ff1="<tr>
		<td class=legend style='font-size:16px'>{forward_address}:</td>
		<td style='font-size:16px'>". Field_array_Hash($pfws,"tcp_outgoing_address",$tcp_outgoing_address,"style:font-size:16px")."<td>
		<td style='font-size:16px' width=1%><td>
	</tr>";
		
	}else{
		$MikrotikVirtualIP=$sock->GET_INFO('MikrotikVirtualIP');
		$ff1="
		<tr>
			<td class=legend style='font-size:16px'>{forward_address}:</td>
			<td style='font-size:16px'><strong>MikroTik:$MikrotikVirtualIP</strong><td>
			<td style='font-size:16px' width=1%><td>
		</tr>";
	}

	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px'>{listen_address}:</td>
		<td style='font-size:16px'>". Field_array_Hash($ips,"bindip-$t",$SquidBinIpaddr,"style:font-size:16px")."<td>
		<td style='font-size:16px' width=1%><td>
	</tr>
	$ff1
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveSquidBinIpaddr()",16)."</td>
	</tr>
	</tbody>
	</table>
	</div>
<script>
	var x_SaveSquidBinIpaddr=function (obj) {
		var tempvalue=obj.responseText;
		YahooWin3Hide();
		Loadjs('squid.restart.php?onlySquid=yes&ask=yes');
	}	
	
	function SaveSquidBinIpaddr(){
		var XHR = new XHRConnection();
		XHR.appendData('SquidBinIpaddr',document.getElementById('bindip-$t').value);
		if(document.getElementById('tcp_outgoing_address')){
			XHR.appendData('tcp_outgoing_address',document.getElementById('tcp_outgoing_address').value);
			}
		
		XHR.sendAndLoad('$page', 'POST',x_SaveSquidBinIpaddr);	
	}		
</script>	
";
	echo $tpl->_ENGINE_parse_body($html);
}	