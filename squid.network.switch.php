<?php
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");

$users=new usersMenus();
if(!$users->AsProxyMonitor){
	header("content-type: application/x-javascript");
	echo "alert('No privs!');";
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["SquidNetworkSwitch"])){SquidNetworkSwitch();exit;}

js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{network_switch}");
	$html="YahooWinBrowse('890','$page?popup=yes&t=$t','$title')";
	echo $html;
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$SquidNetworkSwitch=$sock->GET_INFO("SquidNetworkSwitch");
	
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	unset($interfaces["lo"]);
	$t=$_GET["t"];
	$array[null]="{default}";
	foreach ($interfaces as $eth){
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		
		
	}
	
	
	
	$html="<div style='width:98%' class=form>
		<div style='font-size:22px;margin-bottom:20px' class=explain>
		{squid_network_switch_explain}
		</div>	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:26px' nowrap>{outgoing_interface}:</td>
		<td>". Field_array_Hash($array, "SquidNetworkSwitch-$t",$SquidNetworkSwitch,"style:font-size:26px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right' style='padding-top:50px'><hr>". button("{apply}","Save$t();","36")."</td>
	</tr>
	</table>
	</div>
<script>
var xSave$t= function (obj) {	
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	Loadjs('squid.network.switch.progress.php');
}	
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SquidNetworkSwitch', document.getElementById('SquidNetworkSwitch-$t').value);	
	XHR.sendAndLoad('$page', 'POST',xSave$t);  			
}
</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}	

function SquidNetworkSwitch(){
	$sock=new sockets();
	$sock->SET_INFO("SquidNetworkSwitch", $_POST["SquidNetworkSwitch"]);
	
}
	
	