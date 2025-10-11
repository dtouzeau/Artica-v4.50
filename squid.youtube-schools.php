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
if(isset($_POST["YoutubeForSchoolsID"])){Save();exit;}

js();


function js(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$html="YahooWin4('550','$page?popup=yes','Youtube For Schools')";
	echo $html;
	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableYoutubeForSchools=$sock->GET_INFO("EnableYoutubeForSchools");
	$YoutubeForSchoolsID=$sock->GET_INFO("YoutubeForSchoolsID");
	if(!is_numeric($EnableYoutubeForSchools)){$EnableYoutubeForSchools=0;}
	$t=time();
	
	
	$html="
	<div id='$t'></div>
	<div class=explain style='font-size:14px'>{EnableYoutubeForSchoolsExplain}
	<hr>
		<div style='width:100%;text-align:right'>
		<a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUp('http://proxy-appliance.org/index.php/artica-for-proxy-appliance/proxy-rule-and-web-filtering/using-the-web-filter-engine/using-youtube-for-schools-inside-your-proxy-appliance/',1024,900);\"
		style='font-size:14px;text-decoration:underline'>{howto}</a>
		</div>
	</div>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{EnableYoutubeForSchools}:</td>
		<td>". Field_checkbox("EnableYoutubeForSchools", 1,$EnableYoutubeForSchools,"EnableYoutubeForSchoolsCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>Youtube ID:</td>
		<td>". Field_text("YoutubeForSchoolsID",$YoutubeForSchoolsID,"font-size:14px;width:220px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>
			<hr>". button("{apply}","EnableYoutubeForSchoolsSave()",16)."
		</td>
	</tr>
	</table>
	<script>
	
		function EnableYoutubeForSchoolsCheck(){
			document.getElementById('YoutubeForSchoolsID').disabled=true;
			if(document.getElementById('EnableYoutubeForSchools').checked){
				document.getElementById('YoutubeForSchoolsID').disabled=false;
			}
		}
		
	var X_EnableYoutubeForSchoolsSave= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			document.getElementById('$t').innerHTML='';
		}		
		
	function EnableYoutubeForSchoolsSave(){
		var XHR = new XHRConnection();
		XHR.appendData('YoutubeForSchoolsID',document.getElementById('YoutubeForSchoolsID').value);
		if(document.getElementById('EnableYoutubeForSchools').checked){XHR.appendData('EnableYoutubeForSchools',1);}else{XHR.appendData('EnableYoutubeForSchools',0);}
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_EnableYoutubeForSchoolsSave);     		
	}	

	EnableYoutubeForSchoolsCheck();
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function Save(){
	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, trim($value));
	}
	
	$sock->getFrameWork("squid.php?ufdbguard-compile-smooth=yes");
}
