<?php
include_once('ressources/class.users.menus.inc');
include_once ("ressources/class.templates.inc");
include_once ("ressources/class.user.inc");
include_once ("ressources/class.fetchmail.inc");
session_start();

if(isset($_GET["script"])){start_js();exit;}
if(isset($_GET["page-index"])){page_index();exit;}
if(isset($_GET["page-display"])){page_list();exit;}
if(isset($_GET["page-right-button"])){page_list_buttons();exit;}
if(isset($_GET["page-modify"])){page_modify_rule();exit;}
if(isset($_POST["fetchmail_rule_id"])){page_save();exit;}
if(isset($_GET["DeleteFetchAccount"])){page_del();exit;}
if(isset($_GET["page-fetchmail-aliases"])){page_fetchmail_aliases_index();exit;}
if(isset($_GET["page-fetchmail-aliases-list"])){echo page_fetchmail_aliases_list($_GET["page-fetchmail-aliases-list"]);exit;}
if(isset($_GET["FetchmailAddAliase"])){page_fetchmail_aliases_add();exit;}
if(isset($_GET["FetchmailDeleteAliase"])){page_fetchmail_aliases_del();exit;}
if(isset($_POST["ExecuteFetchAccount"])){ExecuteFetchAccount();exit;}

if(isset($_GET["enable-js-rule"])){page_list_js_enable();exit;}
if(isset($_GET["enable-fetch-rule"])){page_list_js_save();exit;}

if(isset($_GET["find-isp-popup"])){find_isp_popup();exit;}
if(isset($_GET["isp-choose-proto"])){find_isp_proto();exit;}
if(isset($_GET["isp-end"])){find_isp_end();exit;}

if(isset($_GET["debug-popup"])){debug_popup();exit;}
if(isset($_GET["debug-popup-tables"])){debug_popup_tables();exit;}
if(isset($_GET["debug-popup-zoom"])){debug_popup_zom();exit;}
if(isset($_POST["DebugFetchDelete"])){debug_popup_delete();exit;}


function start_js(){
	$page=CurrentPageName();
	if($_GET["uid"]){$uid=$_GET["uid"];}else{$uid=$_SESSION["uid"];}
	
	
	$users=new usersMenus();
	if(!$users->AsAnAdministratorGeneric){
		if($uid<>$_SESSION["uid"]){
			echo "alert('No privileges!\n');";
			return false;
		}
	}
	
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{messaging_accounts}');
	$title1=$tpl->_ENGINE_parse_body('{fetchmail_aliases}');
	$server=$tpl->_ENGINE_parse_body('{server_name}');
	$username=$tpl->_ENGINE_parse_body('{username}');
	$delconfirm=$tpl->javascript_parse_text('{fetch_delete_rule_confirm}');
	$fetchaliase=$tpl->javascript_parse_text('{fetchmail_aliases_ask}');
	$GET_RIGHT_ISP_SETTINGS=$tpl->_ENGINE_parse_body('{GET_RIGHT_ISP_SETTINGS}');
	
	$html="
	var uid='$uid';
	
	var x_FetchmailAddAliase= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		LoadAjax('FetchmailAddAliaseDIV','$page?page-fetchmail-aliases-list=$uid');
	}		
	
	
	function StartFetchmailPage(){
		YahooWin5('650','$page?page-display=$uid','$title');
	}
	
	function AliasesFetchmail(){
		YahooWin4(550,'$page?page-fetchmail-aliases=yes&uid=$uid','$title1');
		
	}
	
	function DisplayAccount(){
		Loadjs('wizard.fetchmail.newbee.php?script=yes&uid=$uid')
	}
	
	function SelectRule(num){
		LoadAjax('rightbutton','$page?page-right-button='+num+'&uid=$uid');
	
	}
	
	function ModifyFetchAccount(num){
		YahooWin4('510','$page?page-modify='+num+'&uid='+uid);
		
	
	}
	
	function AddFetchAccount(){
		YahooWin4('510','$page?page-modify=-1&uid='+uid);
	}
	
	function FetchmailAddAliase(uid){
		var email=prompt('$fetchaliase');
		if(email){
			var XHR = new XHRConnection();
			XHR.appendData('FetchmailAddAliase',email);
			XHR.appendData('uid','$uid');
			document.getElementById('FetchmailAddAliaseDIV').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_FetchmailAddAliase);
		
		}
	
	}
	
	function FetchmailDeleteAliase(email){
			var XHR = new XHRConnection();
			XHR.appendData('FetchmailDeleteAliase',email);
			XHR.appendData('uid','$uid');
			document.getElementById('FetchmailAddAliaseDIV').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_FetchmailAddAliase);
	
	}
	

	
var x_DeleteFetchAccount= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	DisplayAccount();
}	
	
	function DeleteFetchAccount(num,poll){
		if(confirm(poll+': \\n$delconfirm')){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteFetchAccount',num);
			XHR.appendData('uid','$uid');
			XHR.sendAndLoad('$page', 'GET',x_DeleteFetchAccount);		
			}
	}
	
	function LoadFetchmailISPList(){
		YahooWinBrowse(450,'$page?find-isp-popup=yes','$GET_RIGHT_ISP_SETTINGS');
	}
	
	function FetchmailISPSelect(){
		var isp_choose=document.getElementById('isp_choose').value;
		LoadAjax('isp_proto','$page?isp-choose-proto='+isp_choose);
	}
	
	function FetchmailISPProtoSelect(){
		var isp_choose=document.getElementById('isp_choose').value;
		var isp_proto_list=document.getElementById('isp_proto_list').value;
		LoadAjax('isp_end','$page?isp-end=yes&isp='+isp_choose+'&proto='+isp_proto_list);
	}
	
	function ApplyISPFind(){
		document.getElementById('poll').value=document.getElementById('isp_server').value;
		document.getElementById('proto').value=document.getElementById('isp_protos').value;
		document.getElementById('choosen_isp').value=document.getElementById('isp_server_name').value;
		YahooWinBrowseHide();
	}
	
	StartFetchmailPage();
	
	";
	
	
	echo $html;
}


function page_index(){
	$uid=$_GET["uid"];
	$user=new user($uid);
	
	if(count($user->fetchmail_rules)>0){
		if(count($user->FetchMailMatchAddresses)==0){
			$error=Paragraphe("64-red.png","{no_fetchmail_aliases}","{no_fetchmail_aliases_intro}","javascript:AliasesFetchmail()",null,210,null,1);
		}
		else{$error=Paragraphe("recup-remote-mail.png","{fetchmail_aliases}","{no_fetchmail_aliases_intro}","javascript:AliasesFetchmail()",null,210,null,1);}
	}
	
	
	
	$html="
	<div style='width:100%;background-image:url(img/bg-wizard-panel-email.png);background-repeat:repeat-y;margin:-10px;padding:0px' id='ftechid'>
	<table style='width:100%'>
	<tr>
	<td style='width:120px' valign='top'>&nbsp;</td>
	<td valign='top' style='padding-top:10px'>
		<p class=caption>{wizard_intro}</p>
		<div style='text-align:right;border-bottom:1px dotted #CCCCCC;margin-bottom:5px'><H3>$user->DisplayName</H3></div>
			<table style='width:100%;margin-left:10px'>
			<tr>
			<td valign='top'>
				<table style='width:100%'>
				<tr>
				<td>" . Paragraphe("folder-64-fetchmail.png",'{fetchmail_modify_rules}','{fetchmail_modify_rules_text}',"javascript:DisplayAccount();")."</td>
				</tr>
				<tR>
				<td>" . Paragraphe("folder-64-fetchmail-add.png",'{fetchmail_add_rule}','{fetchmail_add_rule_text}',"javascript:AddFetchAccount();")."<br></td>
				</tr>
				</table>
			</td>
			<td valign='top'>
				<table style='width:100%;margin-left:10px'>
					<tr>
						<td valign='top'>$error</td>
					</tr>
				</table>
			</td>
			</tr>
			</table>
	</div>
	<p>&nbsp;</p>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function page_list_js_enable(){
	$page=CurrentPageName();
	$html="
		var fetchmailRule='{$_GET["enable-js-rule"]}';
		var uid='{$_GET["uid"]}';
		
		var x_page_list_js_enable= function (obj) {
			var results=trim(obj.responseText);
			if(results.length>0){alert(results);}
			DisplayAccount();
		}		
		
		function page_list_js_enable(){
			var XHR = new XHRConnection();
			if(document.getElementById('{$_GET["enable-js-rule"]}_enabled').checked){
				XHR.appendData('enable-fetch-rule',1);
			}else{
			 XHR.appendData('enable-fetch-rule',0);
			}
			XHR.appendData('fetchmail-rule-id','{$_GET["enable-js-rule"]}');
			XHR.sendAndLoad('$page', 'GET');		
		}
	
	page_list_js_enable();
	";
	echo $html;
}

function page_list_js_save(){
	
	$sql="UPDATE fetchmail_rules SET enabled='{$_GET["enable-fetch-rule"]}' WHERE ID='{$_GET["fetchmail-rule-id"]}'"; 
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
	$sock=new sockets();
	if($sock->GET_INFO("ArticaMetaEnabled")==1){$sock->getFrameWork("cmd.php?artica-meta-fetchmail-rules=yes");}
	
	$sock->getFrameWork('cmd.php?restart-fetchmail=yes');
	
}

function page_list(){
	
	$t=time();
	$html="
	<div id='$t'></div>
	<script>LoadAjax('$t','fetchmail.user.php?uid={$_GET["page-display"]}');</script>
	";
	echo $html;
	
	
}

function page_list_old(){
	$fetch=new Fetchmail_settings();
	$page=CurrentPageName();
	$tpl=new templates();
	$fetchmail_execute_debug_warn=$tpl->javascript_parse_text("{fetchmail_execute_debug_warn}");
	$rules=$fetch->LoadUsersRules($_GET["page-display"]);
	$user=new user($_GET["page-display"]);
	$sock=new sockets();
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	$imgerr="recup-remote-mail-48.png";
	$imgerr_text="{add_alias}";
	if(count($rules)>0){
		if(count($user->FetchMailMatchAddresses)==0){$imgerr="user-error-48.png";$imgerr_text="{no_fetchmail_aliases}";}
		
			$error="<table class=form><tbody><tr>
			<td width=1%>".imgtootltip($imgerr,"{no_fetchmail_aliases_intro}","AliasesFetchmail()")."</td>
			<td>
			<div style='font-size:14px;font-weight:bold'>$imgerr_text</div>
			<a href=\"javascript:blur();\" OnClick=\"javascript:AliasesFetchmail()\" style='text-decoration:underline;font-size:12px'>{no_fetchmail_aliases_intro}</a>
			</td>
			</tr>
			</table>
			";
	}	
	
		$tbl="
		
<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>".imgtootltip("plus-24.png","{add}","AddFetchAccount()")."</th>
		<th><strong>{user}</strong></th>
		<th><strong>{imap_server_name}</th>
		<th colspan=5><strong>{enabled}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	if(is_array($rules)){
	
		$q=new mysql();
		while (list ($num, $ligne) = each ($rules) ){
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$enabled=Field_checkbox("{$num}_enabled",1,$ligne["enabled"],"Loadjs('$page?enable-js-rule=$num&uid={$_GET["page-display"]}')");
			if($ligne["enabled"]==0){$color="#E60B03";}else{$color="black";}
			
			$edit=imgtootltip("32-administrative-tools.png","{apply}","ModifyFetchAccount($num)");
			$delete=imgtootltip("delete-32.png","{delete}","DeleteFetchAccount($num,'{$ligne["poll"]}');");
			$execute=imgtootltip("mailbox-32.png","{execute_in_debug}","ExecuteFetchAccount($num);");
			$warn="<img src='img/fetchmail-rule-32.png'>";
			$showdebuglogs="&nbsp;";
			
			$sql="SELECT COUNT(ID) as tcount FROM fetchmail_debug_execute WHERE account_id='$num'";
			$ligneCOUNT=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
			if($ligneCOUNT["tcount"]>0){
				$showdebuglogs=imgtootltip("script-32.png","{events}","FetchAccountDebugs($num)");
			}
			
			if($EnablePostfixMultiInstance==1){
				if(trim($ligne["smtp_host"])==null){
					$warn=imgtootltip("status_warning.png","{smtp_host_not_set}");
					$execute=imgtootltip("mailbox-32-grey.png","{execute_in_debug}");
				}
			}
			
			$tbl=$tbl."
				<tr class=$classtr>
				<td>$warn</td>
				<td style='font-size:14px;color:$color' align='left' nowrap>{$ligne["user"]}</td>
				<td style='font-size:14px;color:$color' align='left' nowrap>{$ligne["poll"]}:{$ligne["proto"]}</td>
				<td style='font-size:14px;color:$color' align='right' nowrap>$enabled</td>
				<td style='font-size:14px;color:$color' align='right' nowrap>$edit</td>
				<td style='font-size:14px;color:$color' align='right' nowrap>$execute</td>
				<td style='font-size:14px;color:$color' align='right' nowrap>$showdebuglogs</td>
				<td style='font-size:14px;color:$color' align='right' nowrap>$delete</td>
				</tr>";
		}
	}else{
		$tbl="<tr><td colspan=3>{click_on_add}</td></tr>";
	}
	
	$tbl=$tbl."</tbody></table>";
	
	
	$html="
	<div id='fetchmail-rules-js' style='height:250px;overflow:auto'>$tbl</div>
	<div id='rightbutton'></div>
	$error	
		
	
	<script>
	var x_ExecuteFetchAccount=function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert('results');}
		}	
	
	
		function ExecuteFetchAccount(ID){
			var XHR = new XHRConnection();
			if(confirm('$fetchmail_execute_debug_warn')){
    			XHR.appendData('ExecuteFetchAccount',ID);
    			XHR.sendAndLoad('$page', 'POST',x_ExecuteFetchAccount);
			}
		}
		
		function FetchAccountDebugs(ID){
			RTMMail('550','$page?debug-popup=yes&ID='+ID,ID);
		
		}
		
	";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
	
}
function page_list_buttons(){
	$num=$_GET["page-right-button"];
	
	$fetchmail=new Fetchmail_settings();
	$array=$fetchmail->LoadRule($num);
	
	$html="
	<table style='width:100%'>
	<tr>
		<td>". button("{apply}","ModifyFetchAccount($num)")."</td>
	</tr>
	<tr>
		<td>". button("{delete}","DeleteFetchAccount($num,'{$array["poll"]}');")."</td>
	</tr>	
	
	";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function fetchmail_PostFixMultipleInstanceList($ou){
	$sock=new sockets();
	$uiid=$sock->uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));	
	$q=new mysql();
	$sql="SELECT `value`,`ip_address` FROM postfix_multi WHERE `key`='myhostname' AND `ou`='$ou' AND `uuid`='$uiid'";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){writelogs("$q->mysql_error\n$sql",__FUNCTION__,__FILE__,__LINE__);}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){	
		$array[$ligne["value"]]=$ligne["value"];
	}
	$array[null]="{select}";
	return $array;
}


function page_modify_rule(){
	$tt=$_GET["t"];
	if(!is_numeric($tt)){$tt=0;}
	$tpl=new templates();
	$t=time();
	$server=$tpl->_ENGINE_parse_body('{server_name}');
	$username=$tpl->_ENGINE_parse_body('{username}');	
	$user=new user($_GET["uid"]);
	$fetch=new Fetchmail_settings();
	$page=CurrentPageName();
	$array=$fetch->LoadRule($_GET["page-modify"]);
	$sock=new sockets();
	if($array["smtp_host"]==null){$array["smtp_host"]="127.0.0.1";}
	if(!is_numeric($array["smtp_port"])){$array["smtp_port"]=25;}
	
	$warn="&nbsp;";
	if($sock->GET_INFO("EnablePostfixMultiInstance")==1){
		if($array["smtp_host"]==null){$warn="<img src='img/status_warning.png'>";}
		$smtp_sender=
		"<tr>
			<td width=1%>$warn</td>
			<td valign='top' class=legend nowrap style='font-size:14px'>{local_smtp_host}:</td>
			<td valign='top'>".	Field_array_Hash(fetchmail_PostFixMultipleInstanceList($user->ou),"smtp_host-$t",
		$array["smtp_host"],"blur()",null,0,"font-size:14px")."</td>
		</tr>";
	}else{
		$smtp_sender=
		"<tr>
			<td width=1%>$warn</td>
			<td valign='top' class=legend nowrap style='font-size:14px'>{hostname} SMTP:</td>
			<td valign='top'>".	Field_text("smtp_host-$t",$array["smtp_host"],"blur()",null,0,"font-size:14px")."</td>
		</tr>";		
		
	}
	
	$buttonadd="{apply}";
	$advanced=button("{advanced_options}...","UserFetchMailRule({$_GET["page-modify"]},'{$_GET["uid"]}')","14px");
	
	

	
if($_GET["page-modify"]<0){
	$advanced=null;
	$array["keep"]=true;
	$array["proto"]="imap";
	$buttonadd="{add}";
	$find_isp=Paragraphe("64-infos.png","{GET_RIGHT_ISP_SETTINGS}","{GET_RIGHT_ISP_SETTINGS_TEXT}","javascript:Loadjs('$page?find-isp-js=yes')");
}	
	
	$arraypr=array("imap"=>"imap","pop3"=>"pop3");
	$keep=Field_checkbox("keep-$t",1,$array["keep"]);
	$title=$array["poll"];
	if($title==null){$title=$tpl->_ENGINE_parse_body("{new_rule}");}		
	
$html="
	<div style='margin-bottom:15px'>
		<div style='font-size:18px;border-bottom:3px solid #CCCCCC'>$title</div>
	</div>
	
	<div style='width:100%;overflow:auto' id='fetchmail-rule'>
	<form name='ffmfetch'>
	
	<input type='hidden' name='is' id='is' value='$user->mail'>
	
	<table style='width:98%' class=form>
	<tr>
	<td valign='top'>
			<table style='width:450px;'>
			<tr>
				<td valign='top' colspan=3 style='font-size:16px;font-weight:bold;padding-bottom:10px;padding-top:15px'>{session_information}:<br></td>
			</tr>
			<tr>
				<td width=1%>&nbsp;</td>
				<td class=legend nowrap style='font-size:14px'>{username}:</td>
				<td>" . Field_text("user-$t",$array["user"],"font-size:14px;width:200px")."</td>
			</tr>
			<tr>
				<td width=1%>&nbsp;</td>
				<td class=legend nowrap  style='font-size:14px'>{password}:</td>
				<td>" . Field_password("pass-$t",$array["pass"],"font-size:14px;width:200px")."</td>
			</tr>	
			<tr>
				<td width=1%>&nbsp;</td>
				<td class=legend nowrap  style='font-size:14px'>{original_emailaddr}:</td>
				<td>" . Field_text("orgmail-$t",$array["orgmail"],"font-size:14px;width:200px")."</td>
			</tr>			
			<tr>
				<td valign='top' colspan=3 style='font-size:16px;font-weight:bold;padding-bottom:10px;padding-top:15px'>{server_information}:<br></td>
			</tr>
			<tr>
				<td width=1%>&nbsp;</td>
				<td class=legend nowrap valign='top' style='font-size:14px'>{imap_server_name}:</td>
				<td>
					<table style='width:100%'>
					<tr>
						<td valign='top'>
							" . Field_text("poll-$t",$array["poll"],"font-size:14px;width:200px")."
						</td>
						<td valign='top'>" .
							imgtootltip('22-infos.png','<strong>{GET_RIGHT_ISP_SETTINGS}</strong><br>{GET_RIGHT_ISP_SETTINGS_TEXT}',
							"LoadFetchmailISPList()")."&nbsp;<span id='choosen_isp' style='font-weight:bolder'></span>
						</td>
					</tr>
					</table>
				</td>
			</tr>
			
			<tr>
				<td width=1%>&nbsp;</td>
				<td class=legend nowrap style='font-size:14px'>{protocol}:</td>
				<td>" . Field_array_Hash($arraypr,"proto-$t",$array["proto"],"blur()",null,0,"font-size:14px")."</td>
			</tr>	
			$smtp_sender
			<tr>
				<td width=1%>&nbsp;</td>
				<td class=legend nowrap  style='font-size:14px'>{smtp_port}:</td>
				<td>" . Field_text("smtp_port-$t",$array["smtp_port"],"font-size:14px;width:90px")."</td>
			</tr>				
			<tr>
				<td width=1%>&nbsp;</td>
				<td class=legend nowrap style='font-size:14px'>{not_delete_messages}:</td>
				<td>$keep</td>
			</tr>		
			<tr>
				
				<td valign='top' colspan=3 align='right'>$advanced</td>
			</tr>
			</table>
	
	</td>
		
	</tr>
	</table>
	</form>
	<hr>
	<div style='text-align:right;width:100%'>". button("{cancel}","YahooWin4Hide()","14px")."
	&nbsp;&nbsp;&nbsp;". button("$buttonadd","SaveAccount$t()","14px")."
	
	</div>
	</div>
	<script>
	var x_FetchmailSaveAccount$t= function (obj) {
		var tempvalue=obj.responseText;
		var tt=$tt;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin4Hide();
		if(tt>0){
			 $('#flexRT$tt').flexReload();
			 return;
		}
		DisplayAccount();
	}		
	
	function SaveAccount$t(){
		if(document.getElementById('poll-$t').value==''){alert('$server=NULL!');return true;}
		if(document.getElementById('user-$t').value==''){alert('$username=NULL!');return true;}
		var XHR = new XHRConnection();
		if(document.getElementById('smtp_host-$t')){XHR.appendData('smtp_host',document.getElementById('smtp_host-$t').value);}
		XHR.appendData('fetchmail_rule_id','{$_GET["page-modify"]}');
		XHR.appendData('is',document.getElementById('is').value);
		var pp=encodeURIComponent(document.getElementById('pass-$t').value);
		XHR.appendData('uid','{$_GET["uid"]}');
		XHR.appendData('user',document.getElementById('user-$t').value);
		XHR.appendData('pass',pp);
		XHR.appendData('poll',document.getElementById('poll-$t').value);
		XHR.appendData('proto',document.getElementById('proto-$t').value);
		XHR.appendData('orgmail',document.getElementById('orgmail-$t').value);
		if(document.getElementById('keep-$t').checked){XHR.appendData('keep',1);}else{XHR.appendData('keep',0);}
		AnimateDiv('fetchmail-rule');
		XHR.sendAndLoad('$page', 'POST',x_FetchmailSaveAccount$t);
		}
	</script>	
	
	
	";
	

echo $tpl->_ENGINE_parse_body($html);		
	
}

function page_save(){
	$_POST["pass"]=url_decode_special_tool($_POST["pass"]);
	$ldap=new clladp();
	$user=new user($_POST["uid"]);
	$fetchmail=new Fetchmail_settings();
	if($_POST["fetchmail_rule_id"]>-1){
		$fetchmail->EditRule($_POST,$_POST["fetchmail_rule_id"]);
		
	}else{
		
		if(!$fetchmail->AddRule($_POST)){
			echo "->AddRule Class, Mysql error !\n";
			return;
			
		}
	}
	
	$fetchmail=new fetchmail();
	$fetchmail->Save();
	
}

function page_fetchmail_aliases_index(){
	$uid=$_GET["uid"];
	$tpl=new templates();
	$list=page_fetchmail_aliases_list($uid);
	
	
	
	$form="
			<div id='FetchmailAddAliaseDIV'>
			$list
			</div>
	
	";
	
	
	$html="
	<div class=explain>{fetchmail_aliases_text}</div>
	$form
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function page_fetchmail_aliases_list($uid){
	$users=new user($uid);
	if(!is_array($users->FetchMailMatchAddresses)){return null;}
	
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("plus-24.png","<b>{add_alias}</b><hr>{add_alias_text}","FetchmailAddAliase('$uid')")."</th>
		<th >$uid</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";
	
	while (list ($num, $ligne) = each ($users->FetchMailMatchAddresses) ){
		if($ligne==null){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$html=$html . "
		<tr class=$classtr>
			<td colspan=2><strong style='font-size:16px'>$ligne</td>
			<td width=1%>" . imgtootltip('delete-32.png',"{delete}","FetchmailDeleteAliase('$ligne')")."</td>
		</tr>";	
		
		
	}
	
	$html=$html . "
	</tbody>
	</table>";
	
	
	$tpl=new templates();
	
	return $tpl->_ENGINE_parse_body($html); 
	}
	
function page_fetchmail_aliases_add(){
	$email=trim($_GET["FetchmailAddAliase"]);
	
	$ldap=new clladp();
	$hash=$ldap->find_users_by_mail($email);
	if($hash>0){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body('{error_alias_exists}');
		exit;
	}
	
	$uid=$_GET["uid"];
	$users=new user($uid);
	$users->add_alias_fetchmail($email);
	
}
function page_fetchmail_aliases_del(){
	$email=trim($_GET["FetchmailDeleteAliase"]);
	$uid=$_GET["uid"];
	$users=new user($uid);
	$users->del_alias_fetchmail($email);	
}





function page_del(){
	$num=$_GET["DeleteFetchAccount"];
	$uid=$_GET["uid"];
	$fetchmail=new Fetchmail_settings();
	$fetchmail->DeleteRule($num,$uid);
	}
	
function find_isp_popup(){
	$isp=new fetchmail();
	$array=$isp->ISPDB;
	$newarray=$array["ARRAY_POP_ISP"]+$array["ARRAY_IMAP_ISP"];
	
	while (list ($num, $ligne) = each ($newarray) ){
		$isp_list[$num]=$num;
	}
	ksort($isp_list);
	$isp_list[null]="{select}";
	
	$html="
	<div class=explain style='font-size:14px'>{GET_RIGHT_ISP_SETTINGS_TEXT}</div>
	<table class=form style='width:99%'>
	<tr>
		<td valign='top' class=legend style='font-size:14px'>{ISP}:</td>
		<td valign='top'>".Field_array_Hash($isp_list,'isp_choose',null,"FetchmailISPSelect()",null,0,"font-size:14px")."</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:14px'>{proto}:</td>
		<td valign='top'><span id='isp_proto'></span></td>
	</tr>
	<tr>
		<td colspan=2 align='right'><span id='isp_end'></span></td>
	</tr>	
	</table>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function find_isp_proto(){
	$ispname=$_GET["isp-choose-proto"];
	$isp=new fetchmail();
	$array=$isp->ISPDB;	
	if($array["ARRAY_POP_ISP"][$ispname]<>null){
		$arrayz["POP3"]="POP3";
	}
	
	if($array["ARRAY_IMAP_ISP"][$ispname]<>null){
		$arrayz["IMAP"]="IMAP";
	}

	$arrayz[null]="{select}";
	$html=Field_array_Hash($arrayz,'isp_proto_list',null,"FetchmailISPProtoSelect()",null,0,"font-size:14px");
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function find_isp_end(){
	$isp=$_GET["isp"];
	$proto=$_GET["proto"];
	$isps=new fetchmail();
	$array=$isps->ISPDB;
	if($proto=="POP3"){$ar=$array["ARRAY_POP_ISP"];}
	if($proto=="IMAP"){$ar=$array["ARRAY_IMAP_ISP"];}
	$server=$ar[$isp];

	$html="
	<input type='hidden' name='isp_server_name' id='isp_server_name' value='{$_GET["isp"]}'>
	<input type='hidden' name='isp_server' id='isp_server' value='$server'>
	<input type='hidden' name='isp_protos' id='isp_protos' value='". strtolower($proto)."'>
	<hr>
	<div><code style='font-size:16px;font-weight:bold'>$isp ($proto) [$server]</code></div><hr>".button("{apply}","ApplyISPFind()","18px")."
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
	
}

function ExecuteFetchAccount(){
	$ID=$_POST["ExecuteFetchAccount"];
	if(!is_numeric($ID)){echo "false !";return;}
	$sock=new sockets();
	$sock->getFrameWork("fetchmail.php?debug-rule=$ID");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{install_app}");
}

function debug_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$html="
	<div id='debug-tables' style='width:100%;height:250px;overflowl:auto'></div>
	
	<script>
		function DebugTablesEvents(){
			LoadAjax('debug-tables','$page?debug-popup-tables=yes&ID={$_GET["ID"]}');
		}
		DebugTablesEvents();
	</script>
		";
	echo $html;
}

function debug_popup_tables(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sql="SELECT subject,zDate,PID,ID FROM fetchmail_debug_execute WHERE account_id={$_GET["ID"]} ORDER BY PID,zDate DESC";
	
		$q=new mysql();
		
		$results=$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>".imgtootltip("plus-24.png","{add}","AddFetchAccount()")."</th>
		<th>{date}</th>
		<th>{subject}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";			
		
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$urt="<a href=\"javascript:blur();\" OnClick=\"javascript:DebugFetchZoom({$ligne["ID"]});\" style='font-size:14px;text-decoration:underline'>";
		$delete=imgtootltip("delete-32.png","{delete}","DebugFetchDelete('{$ligne["ID"]}')");
		
		$html= $html."<tr class=$classtr>
		<td nowrap style='width:1%;font-size:14px'>{$ligne["PID"]}</td>
		<td nowrap style='width:1%;font-size:14px'>{$ligne["zDate"]}</td>
		<td style='font-size:14px'>$urt{$ligne["subject"]}</a></td>
		<td style='font-size:14px'>$delete</td>
		</tR>
		
		";
		
			
			
		}	
		
$html=$html."</tbody></table>
<script>

	function DebugFetchZoom(id){
		YahooWin6('750','$page?debug-popup-zoom=yes&ID='+id,'Zoom:'+id);
	}
	
	var x_DebugFetchDelete=function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert('results');}
			DebugTablesEvents();
		}	
	
	
		function DebugFetchDelete(ID){
			var XHR = new XHRConnection();
   			XHR.appendData('DebugFetchDelete',ID);
  			XHR.sendAndLoad('$page', 'POST',x_DebugFetchDelete);
		
		}

</script>
";
echo $tpl->_ENGINE_parse_body($html);
	
}

function debug_popup_zom(){
	$q=new mysql();
	$ligneCOUNT=mysqli_fetch_array($q->QUERY_SQL("SELECT events FROM fetchmail_debug_execute WHERE ID={$_GET["ID"]}","artica_events"));
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	$ligneCOUNT["events"]=utf8_encode($ligneCOUNT["events"]);
	$html="<textarea style='width:100%;height:350px;font-size:13px;overflow:auto;border:1px solid #CCCCCC;padding:5px'>{$ligneCOUNT["events"]}</textarea>";
	echo $html;
	
	
}

function debug_popup_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM fetchmail_debug_execute WHERE ID={$_POST["DebugFetchDelete"]}","artica_events");
	if(!$q->ok){echo $q->mysql_error;}
}


?>