<?php
$GLOBALS["ICON_FAMILY"]="COMPUTERS";
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');

if(posix_getuid()<>0){
	$users=new usersMenus();
	if((!$users->AsSambaAdministrator) OR (!$users->AsSystemAdministrator)){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["list"])){listban();exit;}
if(isset($_POST["plusdeny"])){addban();exit;}
if(isset($_POST["delete"])){delban();exit;}
js();


function js(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$title="{ban_addresses_for_interfaces}";
	$title=$tpl->_ENGINE_parse_body($title);
	$html="RTMMail('375','$page?popup=yes','$title');";
	echo $html;
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$t=time();
	$nets=Field_array_Hash($ips,"$t-deny",null,"style:font-size:16px;padding:3px");
	$html="
	<div class=explain style='font-size:14px'>{ban_addresses_for_interfaces_text}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{ipaddr}:</td>
		<tD>$nets</td>
		<td width=1%>". button("{add}","AddIpToBan()",16)."</td>
	</tr>
	</tbody>
	</table>
	<div id='baniplist-$t'></div>
	
	
	<script>
		function RefreshBanIP(){
			LoadAjax('baniplist-$t','$page?list=yes');
		}
		
	var x_AddIpToBan= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		RefreshBanIP();
		
	}

	function DeleteIpBan(ip){
		var XHR = new XHRConnection();	
		XHR.appendData('delete',ip);
		AnimateDiv('baniplist-$t');
		XHR.sendAndLoad('$page', 'POST',x_AddIpToBan);		
	}
		
	function AddIpToBan(){
		var XHR = new XHRConnection();	
		XHR.appendData('plusdeny',document.getElementById('$t-deny').value);
		AnimateDiv('baniplist-$t');
		XHR.sendAndLoad('$page', 'POST',x_AddIpToBan);				
	}		
	
	RefreshBanIP();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function addban(){
	$tpl=new templates();
	$page=CurrentPageName();		
	$sock=new sockets();
	$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaIpListBanned")));
	$datas[$_POST["plusdeny"]]=true;
	$bewdats=base64_encode(serialize($datas));
	$sock->SaveConfigFile($bewdats, "ArticaIpListBanned");	
	
}

function delban(){
	$tpl=new templates();
	$page=CurrentPageName();		
	$sock=new sockets();
	$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaIpListBanned")));
	unset($datas[$_POST["delete"]]);
	$bewdats=base64_encode(serialize($datas));
	$sock->SaveConfigFile($bewdats, "ArticaIpListBanned");		
	
}

function listban(){
	$tpl=new templates();
	$page=CurrentPageName();		
	$sock=new sockets();
	$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaIpListBanned")));
	
$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='margin-top:8px'>
<thead class='thead'>
	<tr>
	<th colspan=3>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	

while (list ($num, $line) = each ($datas)){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
	
	$delete=imgtootltip("delete-32.png","{delete} $num","DeleteIpBan('$num')");
	
	$html=$html . 
	"<tr class=$classtr>
	<td width=1%><img src='img/folder-network-32.png'></td>
	<td nowrap width=100%><strong style='font-size:16px'>$num</strong></td>
	<td width=1%>$delete</td>
	</tr>
	";
	}
	
	$html=$html ."</tbody></table>";
	echo $tpl->_ENGINE_parse_body($html);
	
}


