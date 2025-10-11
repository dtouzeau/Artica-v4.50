<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.spamassassin.inc');

	
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["dnsbl-save"])){save();exit;}
	
	
	
	js();
	
	



function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$title=$tpl->_ENGINE_parse_body("{SPAMASSASSIN_DNS_BLACKLIST}");
	
	
	$html="
	
	function spamass_dnsbl_load(){
			YahooWin2('600','$page?popup=yes','$title');
		}
	
	spamass_dnsbl_load();";
	
	echo $html;
}


function popup(){
	
$page=CurrentPageName();	
$sock=new sockets();
$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamassassinDNSBL")));
	
$conf["njabl"]="http://www.dnsbl.njabl.org/";
$conf["SORBS"]="http://www.dnsbl.sorbs.net/";
$conf["Spamhaus"]="http://www.spamhaus.org/lookup.lasso";
$conf["sa-hil.habeas.com"]="http://www.robtex.com/dns/habeas.com.html";
$conf["sa-hul.habeas.com"]="http://www.robtex.com/dns/habeas.com.html";
$conf["senderbase.org"]="http://www.senderbase.org/";
$conf["spamcop"]="http://www.spamcop.net/";
$conf["relays.visi.com"]="http://www.visi.com/default.aspx";	


$html="
<div style='font-size:14px'>{SPAMASSASSIN_DNS_BLACKLIST_TEXT}</div>
<div style='font-size:12px'>{DNSBL_EXPLAIN}</div>
<hr>

<table style='width:100%'>";

while (list ($key, $vlue) = each ($conf)){
	
	$html=$html."<tr ". CellRollOver().">
		<td style='font-size:13px'><strong>$key</strong></td>
		<td width=1%>". Field_checkbox("$key",1,$datas[$key],"SpamassassinDNSBL('$key')")."</td>
		<td nowrap style='font-size:11px'><a href='$vlue' target=new>{VISIT_WEB_SITE_INFOS}</a></td>
		</tr>
		";
	
	
}
	
	$html=$html."</table>
	
	<script>
		function SpamassassinDNSBL(dnsbl){
			var XHR = new XHRConnection();
			XHR.appendData('dnsbl-save',dnsbl);
			if(document.getElementById(dnsbl).checked){
				XHR.appendData('enable',1);
			}else{
				XHR.appendData('enable',0);
			}
			
			XHR.sendAndLoad('$page', 'GET');		
			
		}
		
	</script>
	";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function save(){
$sock=new sockets();
$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamassassinDNSBL")));
$datas[$_GET["dnsbl-save"]]=$_GET["enable"];
$sock->SET_INFO("SpamassassinDNSBL",base64_encode(serialize($datas)));
	
}




	

?>