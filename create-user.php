<?php
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die("DIE " .__FILE__." Line: ".__LINE__);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/charts.php');
	include_once('ressources/class.mimedefang.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.ini.inc');	
	
	if(isset($_GET["ou-field"])){OU_FIELD();exit;}
	if(isset($_GET["form"])){formulaire();exit;}
	if(isset($_GET["ch-groupid"])){groups_selected();exit;}
	if(isset($_GET["ch-domain"])){domain_selected();exit;}
	if(isset($_POST["password"])){save();exit;}
	if(isset($_GET["MAILBOX_ZARAFA_LANG_LIST"])){MAILBOX_ZARAFA_LANG_LIST();exit;}
	js();


$users=new usersMenus();
if(!$users->AllowAddUsers){die("alert('not allowed');");}
	
function js(){
	header("content-type: application/x-javascript");
$tpl=new templates();
$page=CurrentPageName();
$users=new usersMenus();
$build_locales_explain=$tpl->javascript_parse_text("{build_locales_explain}");

$ouJS="";
$title=$tpl->_ENGINE_parse_body('{add user explain}');
if(!is_numeric($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
if($_GET["ou"]<>null){$ffou="&ou={$_GET["ou"]}";$ouJS="{$_GET["ou"]}";}

if($_GET["CallBackFunction"]<>null){$CallBackFunction="{$_GET["CallBackFunction"]}();";}

$html="
var x_serid='';

function OpenAddUser(){
	YahooWin5('950','$page?form=yes&t=$t&ByZarafa={$_GET["ByZarafa"]}','$title');
}

var x_ChangeFormValues= function (obj) {
	var tempvalue=obj.responseText;
	var internet_domain='';
	var ouJS='$ouJS';
	var ou=document.getElementById('organization-$t').value;
	if(ouJS.length>0){ou=ouJS;}
	if(!document.getElementById('select_groups-$t')){alert('select_groups-$t no such id');}
	document.getElementById('select_groups-$t').innerHTML=tempvalue;
	if(document.getElementById('internet_domain-$t')){internet_domain=document.getElementById('internet_domain-$t').value;}
	if(document.getElementById('DomainsUsersFindPopupDiv')){DomainsUsersFindPopupDivRefresh();}
	$CallBackFunction
  	 var XHR = new XHRConnection();
  	 XHR.setLockOff();
     XHR.appendData('ou',ou);
	 XHR.appendData('ch-domain',internet_domain);
	 XHR.appendData('t','$t');       	
     XHR.sendAndLoad('$page', 'GET',x_ChangeFormValues2);		
}

var x_ChangeFormValues2= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length==0){return;}
	var domain='';
	var email='';
	var login='';
	var ouJS='$ouJS';
	var ou=document.getElementById('organization-$t').value;
	if(ouJS.length>0){ou=ouJS;}
	$CallBackFunction
	document.getElementById('select_domain-$t').innerHTML=tempvalue;
	if(!document.getElementById('email-$t')){alert('email-$t no such id');}
	if(!document.getElementById('login-$t')){alert('login-$t no such id');}
	
	email=document.getElementById('email-$t').value;
	login=document.getElementById('login-$t').value;
	if(login.length==0){
		if(email.length>0){
			document.getElementById('login-$t').value=email;
		}
	}
		
}


var x_SaveAddUser= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){ alert(tempvalue);return;}
	YahooWin5Hide();
	Loadjs('create-user.progress.php?CallBackFunction={$_GET["CallBackFunction"]}');
	if(document.getElementById('flexRT$t')){ $('#flexRT$t').flexReload(); }
	if(document.getElementById('table-$t')){ $('#table-$t').flexReload(); }
	if(document.getElementById('TABLE_SEARCH_USERS')){  $('#'+document.getElementById('TABLE_SEARCH_USERS').value).flexReload();  }
	if(document.getElementById('main_config_pptpd')){RefreshTab('main_config_pptpd');}
	if(document.getElementById('MAIN_PAGE_ORGANIZATION_LIST')){ $('#table-'+document.getElementById('MAIN_PAGE_ORGANIZATION_LIST').value).flexReload(); }
	if(document.getElementById('admin_perso_tabs')){RefreshTab('admin_perso_tabs');}
	if(document.getElementById('org_main')){RefreshTab('org_main');}
	$CallBackFunction
	ExecuteByClassName('SearchFunction');
}

function SaveAddUserCheck(e){
	if(checkEnter(e)){SaveAddUser();}
}

function SaveAddUser(){
	  var gpid='';
	  var internet_domain='';
	  var ou=document.getElementById('organization-$t').value;
	  var email=document.getElementById('email-$t').value;
	  var firstname=encodeURIComponent(document.getElementById('firstname-$t').value);
	  var lastname=encodeURIComponent(document.getElementById('lastname-$t').value);  
	  var login=document.getElementById('login-$t').value;
	  var password=encodeURIComponent(document.getElementById('password-$t').value);
	  
	  x_serid=login;
	  if(document.getElementById('groupid-$t')){gpid=document.getElementById('groupid-$t').value;}
	  if(document.getElementById('internet_domain-$t')){internet_domain=document.getElementById('internet_domain-$t').value;}
	  var EnableVirtualDomainsInMailBoxes=document.getElementById('EnableVirtualDomainsInMailBoxes-$t').value;
	  if(EnableVirtualDomainsInMailBoxes==1){x_serid=email+'@'+internet_domain;}
	  var XHR = new XHRConnection();
	  
	  if(document.getElementById('ZARAFA_LANG-ZARAFA_LANG')){
	   XHR.appendData('ZARAFA_LANG',document.getElementById('ZARAFA_LANG-ZARAFA_LANG').value);
	  }
	  
	 

  	 
     XHR.appendData('ou',ou);
     XHR.appendData('internet_domain',internet_domain);
	 XHR.appendData('email',email);
     XHR.appendData('firstname',firstname);
     XHR.appendData('lastname',lastname);
     XHR.appendData('login',login);
     XHR.appendData('password',password);
     XHR.appendData('gpid',gpid);
     XHR.appendData('ByZarafa','{$_GET["ByZarafa"]}');    
     XHR.sendAndLoad('$page', 'POST',x_SaveAddUser);		  
}

function BuildLocalesCreateUser(){
	var XHR = new XHRConnection();
	if(!confirm('$build_locales_explain')){return;}
	Loadjs('locales.gen.progress.php');
}


	

function ChangeFormValues(){
  var gpid='';
  var ou=document.getElementById('organization-$t').value;
  if(document.getElementById('groupid-$t')){gpid=document.getElementById('groupid-$t').value;}
  var XHR = new XHRConnection();
  XHR.appendData('ch-groupid',gpid);
  XHR.setLockOff();
  XHR.appendData('ou',ou);
  XHR.sendAndLoad('$page', 'GET',x_ChangeFormValues);	

}



OpenAddUser();";
echo $html;
}

function groups_selected(){
	$t=$_GET["t"];
	$ldap=new clladp();
	if(is_base64_encoded($_GET["ou"])){$_GET["ou"]=base64_decode($_GET["ou"]);}
	$hash_groups=$ldap->hash_groups($_GET["ou"],1);
	$groups=Field_array_Hash($hash_groups,"groupid-$t",$_GET["ch-groupid"],null,null,0,"font-size:28px;padding:3px");
	echo $groups;
	
}

function domain_selected(){
	$t=$_GET["t"];
	$ldap=new clladp();
	if(is_base64_encoded($_GET["ou"])){$_GET["ou"]=base64_decode($_GET["ou"]);}
	$hash_domains=$ldap->hash_get_domains_ou($_GET["ou"]);
	$domains=Field_array_Hash($hash_domains,"internet_domain-$t",$_GET["ch-domain"],null,null,0,"font-size:28px;padding:3px");
	echo $domains;
	
}

function formulaire(){
	$users=new usersMenus();
	$ldap=new clladp();
	$tpl=new templates();
	$page=CurrentPageName();
	$TT=time();	
	$add_new_organisation_text=$tpl->javascript_parse_text("{add_new_organisation_text}");
	$lang=null;
	$t=$_GET["t"];
	if($users->AsAnAdministratorGeneric){
		$hash=$ldap->hash_get_ou(false);
	}else{
		if($_GET["ou"]==null){
			$hash=$ldap->Hash_Get_ou_from_users($_SESSION["uid"],1);
			if(count($hash)==0){if(isset($_SESSION["ou"])){$hash[0]=$_SESSION["ou"];}}
			
		}else{
			$hash[0]=$_GET["ou"];
			if(count($hash)==0){if(isset($_SESSION["ou"])){$hash[0]=$_SESSION["ou"];}}
		}
		
	}
	
	if(count($hash)==0){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{error_no_ou_created}<center style='margin-top:30px'>".
				button("{create_a_new_organization}", "Loadjs('organization.js.php?add-ou=yes');",22)."</center>"));

		return;
		
		
	}
	
	if(count($hash)==1){
		$org=$hash[0];
		$hash_groups=$ldap->hash_groups($org,1);
		$hash_domains=$ldap->hash_get_domains_ou($org);
		$groups=Field_array_Hash($hash_groups,"groupid-$t",null,null,null,0,"font-size:28px;padding:3px");
		$domains=Field_array_Hash($hash_domains,"domain-$t",null,null,null,0,"font-size:28px;padding:3px");
	}
	
	
	$artica=new artica_general();
	$EnableVirtualDomainsInMailBoxes=$artica->EnableVirtualDomainsInMailBoxes;	
	
	if($users->ZARAFA_INSTALLED){
		$sock=new sockets();
		$languages=unserialize(base64_decode($sock->getFrameWork("zarafa.php?locales=yes")));
		while (list ($index, $data) = each ($languages) ){
			if(preg_match("#cannot set#i", $data)){continue;}
			$langbox[$data]=$data;
		}
			$ZARAFA_LANG=$sock->GET_INFO("ZARAFA_LANG");
			$mailbox_language=Field_array_Hash($langbox,"ZARAFA_LANG-ZARAFA_LANG",$ZARAFA_LANG,"style:font-size:28px;padding:3px");
			$lang="
			<tr>
				<td class=legend style='font-size:28px'>{language}:</td>
				<td><span id='MAILBOX_ZARAFA_LANG_LIST'>$mailbox_language</span>
				<div style='float:right;margin-top:5px'>".button("{build_languages}","BuildLocalesCreateUser()")."</div>
				</td>
			</tr>
			";
			
			
	
	}
	
	
	
	foreach ($hash as $num=>$ligne){
		$ous[$ligne]=$ligne;
	}
	$ouenc=urlencode($_GET["ou"]);
	
	$ADMIN=false;
	if($users->AsSquidAdministrator){$ADMIN=true;}
	if($users->AsDebianSystem){$ADMIN=true;}
	if($users->AsSystemAdministrator){$ADMIN=true;}
	
	if($ADMIN) {
		
		$add_ou="<div style='float:right'>". imgtootltip("32-plus.png","{create_a_new_organization}","TreeAddNewOrganisation$TT()")."</div>";
		$add_group="<div style='float:right'>". imgtootltip("32-plus.png","{new_group}","CreateGroup$TT()")."</div>";
		
	}
	
	$ou=Field_array_Hash($ous,"organization-$t",$_GET["ou"],"ChangeFormValues()",null,0,"font-size:28px;padding:3px");
	$form="
	
	<input type='hidden' id='EnableVirtualDomainsInMailBoxes-$t' value='$EnableVirtualDomainsInMailBoxes'>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:28px'>{organization}:</td>
			<td>$add_ou<span id='ou-$TT'></span></td>
		</tr>
		<tr>
			<td class=legend style='font-size:28px'>{group}:</td>
			<td>$add_group<span id='select_groups-$t'>$groups</span>
		</tr>
		<tr>
		<tr>
			<td class=legend style='font-size:28px'>{firstname}:</td>
			<td>" . Field_text("firstname-$t",null,'width:531px;font-size:28px;padding:3px',
					null,'ChangeFormValues()')."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:28px'>{lastname}:</td>
			<td>" . Field_text("lastname-$t",null,'width:531px;font-size:28px;padding:3px',null,"ChangeFormValues()")."</td>
		</tr>		
			$lang
		<tr>
			<td class=legend style='font-size:28px'>{email}:</td>
			<td style='font-size:28px'>" . Field_text("email-$t",null,'width:220px;font-size:28px;padding:3px',
					null,"ChangeFormValues()")."&nbsp;@&nbsp;<span id='select_domain-$t'>$domains</span></td>
		</tr>
		<tr>
			<td class=legend style='font-size:28px' nowrap>{uid}:</td>
			<td>" . Field_text("login-$t",null,'width:320px;font-size:28px;padding:3px')."</td>
		</tr>
		
		<tr>
			<td class=legend style='font-size:28px'>{password}:</td>
			<td>" .Field_password("password-$t",null,"font-size:28px;padding:3px",null,null,null,false,"SaveAddUserCheck(event)")."</td>
		</tr>	
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
			<td colspan=2 align='right' style='padding:10px'><hr>". button("{add}","SaveAddUser()",34)."
				
			</td>
		</tr>
		
		</table>
	</div>
	";
			
	$html="<div id='ffform-$t'>
	<div>$form</div>
	<script>
	var xTreeAddNewOrganisation$TT= function (obj) {
		var response=obj.responseText;
		if(response){alert(response);}
		OpenOU$TT();
		ChangeFormValues();
		$('#table-$t').flexReload();
	}	
	
	function TreeAddNewOrganisation$TT(){
		var org=prompt('$add_new_organisation_text','');
		if(!org){return;}
		var XHR = new XHRConnection();
		XHR.appendData('TreeAddNewOrganisation',org);
		XHR.sendAndLoad('domains.php', 'GET',xTreeAddNewOrganisation$TT);
	}
	
	function OpenOU$TT(){
		LoadAjaxSilent('ou-$TT','$page?ou-field=yes&t=$t&ou=$ouenc');
	}
	
	function CreateGroup$TT(){
		var ou=document.getElementById('organization-$t').value;
		Loadjs('domains.edit.group.php?popup-add-group=yes&ou='+ou+'&t=$t&tt={$_GET["tt"]}&CallBackFunction=ChangeFormValues');
	}
	OpenOU$TT();
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function OU_FIELD(){
	$users=new usersMenus();
	$ldap=new clladp();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	if($users->AsAnAdministratorGeneric){
		$hash=$ldap->hash_get_ou(false);
	}else{
		if($_GET["ou"]==null){
			$hash=$ldap->Hash_Get_ou_from_users($_SESSION["uid"],1);
			if(count($hash)==0){if(isset($_SESSION["ou"])){$hash[0]=$_SESSION["ou"];}}
				
		}else{
			$hash[0]=$_GET["ou"];
			if(count($hash)==0){if(isset($_SESSION["ou"])){$hash[0]=$_SESSION["ou"];}}
		}
	
	}
	
	if(count($hash)==1){
		$org=$hash[0];
		
	}
	foreach ($hash as $num=>$ligne){
		$ous[$ligne]=$ligne;
	}
	
	echo Field_array_Hash($ous,"organization-$t",$_GET["ou"],"ChangeFormValues()",null,0,"font-size:28px;padding:3px")."
	<script>ChangeFormValues();</script>		
	";
	
}


function save(){
	
	$q=new mysql();
	
	$sql="CREATE TABLE IF NOT EXISTS `CreateUserQueue` (
	`zMD5` CHAR(32) NOT NULL,
	`content` TEXT NOT NULL,
	PRIMARY KEY (`zMD5`)
	) ENGINE=MYISAM;";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	$tpl=new templates();   
	$usersmenus=new usersMenus();
	if($usersmenus->ZARAFA_INSTALLED){$_POST["ByZarafa"]="yes";}
	$fulldata=base64_encode(serialize($_POST));
	
	$md5=md5($fulldata);
	$fulldata=mysql_escape_string2($fulldata);
	$q->QUERY_SQL("INSERT IGNORE INTO `CreateUserQueue` (zMD5,`content`) VALUES ('$md5','$fulldata')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	//$sock=new sockets();
	//echo base64_decode($sock->getFrameWork("system.php?create-user=$fulldata"));
	
	
	
}

function MAILBOX_ZARAFA_LANG_LIST(){
	$sock=new sockets();
	$languages=unserialize(base64_decode($sock->getFrameWork("zarafa.php?locales=yes")));
	while (list ($index, $data) = each ($languages) ){
		if(preg_match("#cannot set#i", $data)){continue;}
		$langbox[$data]=$data;
	}
	$ZARAFA_LANG=$sock->GET_INFO("ZARAFA_LANG");
	$mailbox_language=Field_array_Hash($langbox,"ZARAFA_LANG-ZARAFA_LANG",$ZARAFA_LANG,"style:font-size:28px;padding:3px");	
	echo $mailbox_language;
	
}


?>