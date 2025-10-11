<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}



if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["EnablePagePeeker"])){Save();exit;}

js();


function js(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$html="YahooWin4('550','$page?popup=yes','PagePeeker')";
	echo $html;
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnablePagePeeker=$sock->GET_INFO("EnablePagePeeker");
	$PagePeekerID=$sock->GET_INFO("PagePeekerID");
	$PagePeekerMinRequests=$sock->GET_INFO("PagePeekerMinRequests");
	if(!is_numeric($EnablePagePeeker)){$EnablePagePeeker=1;}
	if(!is_numeric($PagePeekerMinRequests)){$PagePeekerMinRequests=20;}
	$t=time();
	
	
	$html="
	<div id='$t'><img src='img/pagepeeker-204.png'></div>
	<div class=explain style='font-size:14px'>{pagepeeker_icon_text}
	</div>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{EnablePagePeeker}:</td>
		<td>". Field_checkbox("EnablePagePeeker", 1,$EnablePagePeeker,"EnablePagePeekerCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>PagePeeker ID:</td>
		<td>". Field_text("PagePeekerID",$PagePeekerID,"font-size:16px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{minimum_requests}:</td>
		<td>". Field_text("PagePeekerMinRequests",$PagePeekerMinRequests,"font-size:16px;width:90px")."</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'>
			<hr>". button("{apply}","EnablePagePeekerSave()",16)."
		</td>
	</tr>
	</table>
	<script>
	
		function EnablePagePeekerCheck(){
			document.getElementById('PagePeekerID').disabled=true;
			document.getElementById('PagePeekerMinRequests').disabled=true;
			if(document.getElementById('EnablePagePeeker').checked){
				document.getElementById('PagePeekerID').disabled=false;
				document.getElementById('PagePeekerMinRequests').disabled=false;
			}
		}
		
	var X_EnablePagePeekerSave= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			document.getElementById('$t').innerHTML='<img src=img/pagepeeker-204.png>';
		}		
		
	function EnablePagePeekerSave(){
		var XHR = new XHRConnection();
		XHR.appendData('PagePeekerID',document.getElementById('PagePeekerID').value);
		if(document.getElementById('EnablePagePeeker').checked){XHR.appendData('EnablePagePeeker',1);}else{XHR.appendData('EnablePagePeeker',0);}
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_EnablePagePeekerSave);     		
	}	

	EnablePagePeekerCheck();
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function Save(){
	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, trim($value));
	}
	
	
}
