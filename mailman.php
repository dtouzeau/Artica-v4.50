<?php
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');

include_once(dirname(__FILE__).'/ressources/class.mailman.ctl.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["service-status"])){service_status();exit;}

if(isset($_GET["script"])){js();exit;}
if(isset($_POST["listname_add"])){list_add();exit;}
if(isset($_GET["mailman-lists"])){echo popup_list();exit;}
if(isset($_GET["table-list"])){echo popup_list_items();exit;}
if(isset($_GET["add"])){popup_add();exit;}
if(isset($_POST["DeleteList"])){delete_distriblist($_POST["DeleteList"]);exit;}

if(isset($_GET["mailmaninfos"])){echo popup_list_info();exit;}
if(isset($_GET["DEFAULT_URL_PATTERN"])){popup_save();exit;}
if(isset($_GET["MailManDeleteList"])){delete_mailman_list();exit;}
if(isset($_GET["BuildMailManRobots"])){buildrobots();exit;}
if(isset($_GET["EnableMailman"])){EnableMailman();exit;}
if(isset($_GET["MailManDeleteList-new"])){delete_distriblist($_GET["MailManDeleteList-new"]);exit;}
if(isset($_GET["adv-options"])){echo popup_options();exit;}
if(isset($_GET["MAILMAN_DEFAULT_URL_PATTERN"])){popup_options_save();exit;}
if(isset($_GET["index"])){index();exit;}
if(isset($_GET["mailman-cmds"])){service_cmds_js();exit;}
if(isset($_GET["mailman-cmds-peform"])){service_cmds_perform();exit;}
if(isset($_POST["reconfigure-postfix"])){reconfigure_postfix();exit;}

function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["mailman-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{mailman}");
	$html="YahooWin4('650','$page?mailman-cmds-peform=$cmd','$mailman::$cmd');";
	echo $html;	
}
function service_cmds_perform(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("mailman.php?service-cmds={$_GET["mailman-cmds-peform"]}")));
	
		$html="
<div style='width:100%;height:350px;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{events}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	while (list ($key, $val) = each ($datas) ){
		if(trim($val)==null){continue;}
		if(trim($val=="->")){continue;}
		if(isset($alread[trim($val)])){continue;}
		$alread[trim($val)]=true;
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$val=htmlentities($val);
			$html=$html."
			<tr class=$classtr>
			<td width=99%><code style='font-size:12px'>$val</code></td>
			</tr>
			";
	
	
}

$html=$html."
</tbody>
</table>
</div>
<script>
	RefreshTab('main_config_mailman');
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}


function service_status(){
$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$datas=base64_decode($sock->getFrameWork("mailman.php?service-status=yes"));
	$MailManEnabled=$sock->GET_INFO('MailManEnabled');
	if(!is_numeric($MailManEnabled)){$MailManEnabled=0;}
	
$commands="<center style='margin-top:-10px'>
<table style='width:50%' class=form>
<tbody>
<tr>
	<td width=1%>". imgtootltip("32-stop.png","{stop}","Loadjs('$page?mailman-cmds=stop')")."</td>
	<td width=1%>". imgtootltip("restart-32.png","{stop} & {start}","Loadjs('$page?mailman-cmds=restart')")."</td>
	<td width=1%>". imgtootltip("32-run.png","{start}","Loadjs('$page?mailman-cmds=start')")."</td>
</tr>
</tbody>
</table>
</center>";	
if($MailManEnabled==0){$commands=null;}
	
	$ini->loadString($datas);	
	$APP_MAILMAN=DAEMON_STATUS_ROUND("MAILMAN",$ini,null,0);
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td style='vertical-align:middle'><center><img src='img/mailman-128.png'></center></td>
	</tr>
	<tr>
	<td>$APP_MAILMAN</td>
	</tr>
	</table>
	$commands
	<script>
		// LoadAjax('pattern-status','$page?pattern-status=yes');
	</script>
	";		
echo $tpl->_ENGINE_parse_body($html);	
}


function EnableMailman(){
	$sock=new sockets();
	$sock->SET_INFO('MailManEnabled',$_GET["EnableMailman"]);
	if($_GET["EnableMailman"]==1){$sock->getFrameWork("mailman.php?service-start=yes");}
	if($_GET["EnableMailman"]==0){$sock->getFrameWork("mailman.php?service-stop=yes");}
}

function delete_mailman_list(){
	$list=$_GET["MailManDeleteList"];
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?mailman-delete=$list");
	$sock->getFrameWork('cmd.php?restart-mailman=yes');
	$mailman=new mailmanctl();
	$mailman->DeleteRobot($list,$_GET["domain"]);
}

function delete_distriblist(){
	$list=$_POST["DeleteList"];
	$sql="UPDATE mailmaninfos SET `delete`=1 WHERE `list`='$list'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("mailman.php?lists-to-delete=yes");
	
}

function buildrobots(){
	$list=$_GET["BuildMailManRobots"];
	$mailman=new mailmanctl();
	$mailman->BuildRobots($list,$_GET["domain"]);
	
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["index"]='{index}';
	$array["mailman-lists"]='{lists}';

	foreach ($array as $num=>$ligne){
		$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_mailman style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_mailman').tabs();
			});
		</script>";		
	
}


function js(){
$page=CurrentPageName();
$prefix=str_replace('.','_',$page);
$tpl=new templates();
$confirm_delete_mailman=$tpl->_ENGINE_parse_body("{confirm_delete_mailman}");
$advanced_options=$tpl->_ENGINE_parse_body("{advanced_options}");
$tite=$tpl->_ENGINE_parse_body("{APP_MAILMAN}");
$sock=new sockets();
$sock->getFrameWork("mailman.php?checks-created=yes");


$html="
	var tmpnum='';
	function {$prefix}load(){
		YahooWin(770,'$page?tabs=yes','$tite');	
	}
	
var x_MailManDeleteList= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	{$prefix}load();
}	

var x_MailManRobots= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	{$prefix}load();
}	
	

	
	function MailManAdvancedOptions(){
		YahooWin2(550,'$page?adv-options=yes','$advanced_options');	
	}
	
	function DeleteMailManList(listname){
		var text='$confirm_delete_mailman';
		if(confirm(text)){
		var XHR = new XHRConnection();
		XHR.appendData('MailManDeleteList-new',listname);
		XHR.sendAndLoad('$page', 'GET',x_MailManDeleteList);		
		}
	}
	
	function MailMainListInfo(listname){
		LoadAjax('mailman_info','$page?mailmaninfos='+listname);
	}
	
	function MailManDeleteList(listname,domain){
		var text='$confirm_delete_mailman';
		if(confirm(text)){
		var XHR = new XHRConnection();
		XHR.appendData('MailManDeleteList',listname);
		XHR.appendData('domain',domain);
		XHR.sendAndLoad('$page', 'GET',x_MailManDeleteList);		
		}
	}
	
	function BuildMailManRobots(listname,domain){
		var XHR = new XHRConnection();
		XHR.appendData('BuildMailManRobots',listname);
		XHR.appendData('domain',domain);
		XHR.sendAndLoad('$page', 'GET',x_MailManRobots);
	
	}
	

	
var x_SaveAdvancedSettings= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	YahooWin2Hide();
	{$prefix}load();
}	
	
function SaveAdvancedSettings(){
		var XHR = new XHRConnection();
		XHR.appendData('MAILMAN_DEFAULT_URL_PATTERN',document.getElementById('MAILMAN_DEFAULT_URL_PATTERN').value);
		XHR.appendData('MAILMAN_PUBLIC_ARCHIVE_URL',document.getElementById('MAILMAN_PUBLIC_ARCHIVE_URL').value);
		XHR.appendData('MAILMAN_DEFAULT_EMAIL_HOST',document.getElementById('MAILMAN_DEFAULT_EMAIL_HOST').value);
		XHR.appendData('MAILMAN_DEFAULT_URL_HOST',document.getElementById('MAILMAN_DEFAULT_URL_HOST').value);
		XHR.appendData('MAILMAN_DEFAULT_SERVER_LANGUAGE',document.getElementById('MAILMAN_DEFAULT_SERVER_LANGUAGE').value);
		XHR.sendAndLoad('$page', 'GET',x_SaveAdvancedSettings);
}
	
{$prefix}load();";
	echo $html;

}

function popup_options_save(){
	$sock=new sockets();
	$sock->SET_INFO("MAILMAN_DEFAULT_URL_PATTERN",$_GET["MAILMAN_DEFAULT_URL_PATTERN"]);
	$sock->SET_INFO("MAILMAN_PUBLIC_ARCHIVE_URL",$_GET["MAILMAN_PUBLIC_ARCHIVE_URL"]);
	$sock->SET_INFO("MAILMAN_DEFAULT_EMAIL_HOST",$_GET["MAILMAN_DEFAULT_EMAIL_HOST"]);
	$sock->SET_INFO("MAILMAN_DEFAULT_URL_HOST",$_GET["MAILMAN_DEFAULT_URL_HOST"]);
	$sock->SET_INFO("MAILMAN_DEFAULT_SERVER_LANGUAGE",$_GET["MAILMAN_DEFAULT_SERVER_LANGUAGE"]);
	
	
	
	
	$sock->getFrameWork("cmd.php?restart-mailman=yes");
}


function popup_start(){
	
	if($_GET["main"]=="global-options"){popup_options();exit;}
	if($_GET["main"]=="yes"){popup_conf_list();exit;}
	
	echo "<div id='main_section_mailman'>";
	popup_conf_list();
	echo "</div>";
	
}

function index(){
	
	$page=CurrentPageName();
	$mailman=new mailmanctl();
	$add=Paragraphe("mailman-add.png",'{add_mailman}','{add_mailman_text}','javascript:MailManAddlist()');
	$sock=new sockets();
	$EnableMailman=$sock->GET_INFO("MailManEnabled");
	if(!is_numeric($EnableMailman)){$EnableMailman=0;}
	$enable_mailman=Paragraphe_switch_img('{ENABLE_MAILMAN}','{ENABLE_MAILMAN_TEXT}','EnableMailman',$EnableMailman,null,320);
	$t=time();
	$html="
	<div id='$t'></div>
	<table style='width:100%'>
		<tr>
			<td width=60% valign='top'>
				
				<div id='mailman_info'></div>
				<div style='width:100%;text-align:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshServicesStatus()")."</div>
		</td>
		<td valign='top' width=99% colspan=2>
			<table style='width:99%' class=form>
				<tr>
					<td>
						$enable_mailman	
						<hr>
						<div style='margin-top:5px;text-align:right'>".button("{apply}","EnableMailManList()","18px")."</div>
					</td>
				</tr>
				<tr>
					<td align='center'>
						
					</td>
				</tr>
			</table>
			
			<center style='width:100%;margin-top:20px'>".Paragraphe("help-64.png","{help}","{online_help}","javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=298','1024','900');")."</center>
		</td>
	</tr>
	</table>
<script>
var x_EnableMailManList= function (obj) {
	var tempvalue=obj.responseText;
	document.getElementById('$t').innerHTML='';
	if(tempvalue.length>3){alert(tempvalue);}
	RefreshTab('main_config_mailman');
}	
	
function EnableMailManList(){
		var XHR = new XHRConnection();
		AnimateDiv('$t');
		XHR.appendData('EnableMailman',document.getElementById('EnableMailman').value);
		XHR.sendAndLoad('$page', 'GET',x_EnableMailManList);
	
	}	
	
function RefreshServicesStatus(){
	LoadAjax('mailman_info','$page?service-status=yes');

}
RefreshServicesStatus();

</script>
	
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'mailman.lists.php');			
	
}


function popup_options(){
	$page=CurrentPageName();
	$tabs=popup_tabs();
	$sock=new sockets();
	$user=new usersMenus();
	$ApacheGroupWarePort=$sock->GET_INFO("ApacheGroupWarePort");
	$user->fqdn;

	$MAILMAN_PUBLIC_ARCHIVE_URL=$sock->GET_INFO("MAILMAN_PUBLIC_ARCHIVE_URL");
	$MAILMAN_DEFAULT_URL_PATTERN=$sock->GET_INFO("MAILMAN_DEFAULT_URL_PATTERN");
	$MAILMAN_DEFAULT_URL_HOST=$sock->GET_INFO("MAILMAN_DEFAULT_URL_HOST");
	$MAILMAN_DEFAULT_SERVER_LANGUAGE=$sock->GET_INFO("MAILMAN_DEFAULT_SERVER_LANGUAGE");
	
	
	if($MAILMAN_DEFAULT_URL_HOST==null){$MAILMAN_DEFAULT_URL_HOST="http://$user->fqdn:$ApacheGroupWarePort";}
	if($MAILMAN_DEFAULT_URL_PATTERN==null){$MAILMAN_DEFAULT_URL_PATTERN="%s/cgi-bin/mailman/";}
	if($MAILMAN_PUBLIC_ARCHIVE_URL==null){
	$MAILMAN_PUBLIC_ARCHIVE_URL="http://%(hostname)s:$ApacheGroupWarePort/pipermail/%(listname)s/index.html";}
	if($MAILMAN_DEFAULT_SERVER_LANGUAGE==null){$MAILMAN_DEFAULT_SERVER_LANGUAGE="en";}
	
	$langs["zh_TW"]="zh_TW";
	$langs["de"]="de";
	$langs["pt_BR"]="pt_BR";
	$langs["no"]="no";
	$langs["sl"]="sl";
	$langs["ja"]="ja";
	$langs["sk"]="sk";
	$langs["sv"]="sv";
	$langs["da"]="da";
	$langs["it"]="it";
	$langs["he"]="he";
	$langs["hu"]="hu";
	$langs["vi"]="vi";
	$langs["gl"]="gl";
	$langs["fr"]="fr";
	$langs["es"]="es";
	$langs["tr"]="tr";
	$langs["zh_CN"]="zh_CN";
	$langs["hr"]="hr";
	$langs["ia"]="ia";
	$langs["uk"]="uk";
	$langs["nl"]="nl";
	$langs["ru"]="ru";
	$langs["sr"]="sr";
	$langs["en"]="en";
	$langs["ro"]="ro";
	$langs["cs"]="cs";
	$langs["et"]="et";
	$langs["ar"]="ar";
	$langs["fi"]="fi";
	$langs["pt"]="pt";
	$langs["ko"]="ko";
	$langs["lt"]="lt";
	$langs["eu"]="eu";
	$langs["ca"]="ca";
	$langs["pl"]="pl";
	
	$MAILMAN_DEFAULT_SERVER_LANGUAGE=Field_array_Hash($langs,"MAILMAN_DEFAULT_SERVER_LANGUAGE",$MAILMAN_DEFAULT_SERVER_LANGUAGE);
	
	$html="
	
	
	
	
	<form name='FFMGS'>
	<H1>{mailman_global_options}</h1>
	<p class=caption>{manage_distribution_lists}</p>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend nowrap>{DEFAULT_URL_PATTERN}:</td>
		<td>" . Field_text('MAILMAN_DEFAULT_URL_PATTERN',$MAILMAN_DEFAULT_URL_PATTERN)."</td>
	</tr>
	<tr>
		<td class=legend nowrap>{PUBLIC_ARCHIVE_URL}:</td>
		<td>" . Field_text('MAILMAN_PUBLIC_ARCHIVE_URL',$MAILMAN_PUBLIC_ARCHIVE_URL)."</td>
	</tr>
	<tr>
		<td class=legend nowrap>{DEFAULT_EMAIL_HOST}:</td>
		<td>" . Field_text('MAILMAN_DEFAULT_EMAIL_HOST',$sock->GET_INFO("MAILMAN_DEFAULT_EMAIL_HOST"))."</td>
	</tr>
	<tr>
		<td class=legend nowrap>{MAILMAN_DEFAULT_URL_HOST}:</td>
		<td>" . Field_text('MAILMAN_DEFAULT_URL_HOST',$MAILMAN_DEFAULT_URL_HOST)."</td>
	</tr>	
	<tr>
		<td class=legend nowrap>{language}:</td>
		<td>$MAILMAN_DEFAULT_SERVER_LANGUAGE</td>
	</tr>	
	
<tr>
	<td colspan=2 align='right'>". button("{apply}","SaveAdvancedSettings()")."</td>
	</tr>		
	</table>
</div>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'mailman.lists.php');		
	
}	

function popup_delete(){
	$users=new AutoUsers();
	unset($users->AutoCreateAccountIPArray[$_GET["AutoCreateAccountDelete"]]);
	$users->Save(1);
}

function popup_add(){
	$ldap=new clladp();
	$page=CurrentPageName();
	$users=new usersMenus();
	$t=$_GET["t"];
	$listname=$_GET["listname"];
	$listnameExists=0;
	$btname="{add}";
	$explain="<div class=explain style='font-size:14px'>{add_mailman_text}</div>";
	if($_GET["ou"]<>null){
		
		$domains=$ldap->hash_get_domains_ou($_GET["ou"]);
	}else{
		$domains=$ldap->hash_get_all_domains();
	}
	
	if($listname<>null){
		$mailman=new mailmancontrol($listname);
		$tr=explode(".", $mailman->emailhost);
		$emailhost=$tr[0];unset($tr[0]);
		$domain=@implode(".", $tr);
		$explain="<div style='font-size:14px;width:95%' class=form>http://$mailman->urlhost&nbsp;|&nbsp;smtp://$mailman->emailhost</div>";
		$btname="{apply}";
		$urlhost=str_replace(".$domain", "", $mailman->urlhost);
		$adminmail=$mailman->adminmail;
		$mangle=$mailman->mangle;
		$listnameExists=1;
	}
	
	$domain=Field_array_Hash($domains,"domain-$t",null,"FillSuffix$t()", null,0,"font-size:16px");
	
	$html="

	$explain
	<div id='animate-$t'></div>
	<table class=form style='width:99%'>
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{listname}:</td>
		<td valign='top'  width=1%>" . Field_text('listname_add',$listname,'width:200px;font-size:16px',null,null,null,false,"FillSuffix$t()",false,null)."</td>
		<td></td>
		<td></td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{domain}:</td>
		<td valign='top'  width=1%>$domain</td>
		<td></td>
		<td></td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{exclusive}:</td>
		<td valign='top'  width=1%>" . Field_checkbox("mangle-$t",1,$mangle,"MangleCheck$t()")."</td>
		<td></td>
		<td></td>
	</tr>			
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{emailhost}:</td>
		<td valign='top'  width=1%>" . Field_text("emailhost-$t",$emailhost,'width:200px;font-size:16px',null,null,null,false,"FillSuffix$t()")."</td>
		<td><span id='suffixDom2-$t' style='font-size:16px'></span></td>
		<td></td>
	</tr>
		
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{urlhost}:</td>
		<td valign='top' width=1%>" . Field_text("urlhost-$t",$urlhost,'width:200px;font-size:16px',null,null,null,false,"FillSuffix$t()")."</td>
		<td><span id='suffixDom-$t' style='font-size:16px'></span></td>
		<td></td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{admin_email}:</td>
		<td valign='top'  width=1%>" . Field_text("adminmail-$t",$adminmail,'width:200px;font-size:16px',null,null,null,false,"FillSuffix$t()")."</td>
		<td>&nbsp;</td>
		<td width=1%>". help_icon("{MailManListAdministrator_text}")."</td>
	</tr>		
	<tr>
		<td colspan=4 align='right'><hr>". button("$btname","SaveList$t();","18px")."</td>
	</tr>	
	</table>
	<div id='showlists$t' style='font-size:14px;font-weight:bold'></div>
	<script>
	var x_SaveList$t= function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById('animate-$t').innerHTML='';
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#flexRT$t').flexReload();
		YahooWin2Hide();
	} 
	
	function MangleCheck$t(){
		if(document.getElementById('mangle-$t').checked){
			document.getElementById('emailhost-$t').disabled=true;
		}else{
			document.getElementById('emailhost-$t').disabled=false;
		}
		
		FillSuffix$t()
	}

   function FillSuffix$t(){
   		var listnameExists=$listnameExists;
   		var smtpdomain='';
   		var smtpdomain_point='.';
   		var textemails='';
   		smtpdomain=document.getElementById('domain-$t').value;
   		var listname=document.getElementById('listname_add').value;
   		if(document.getElementById('mangle-$t').checked){smtpdomain_point='';}else{
   			smtpdomain=document.getElementById('emailhost-$t').value+'.'+document.getElementById('domain-$t').value;
   		}
   		
   		if(listnameExists==1){document.getElementById('listname_add').disabled=true;}
		document.getElementById('suffixDom-$t').innerHTML='.'+document.getElementById('domain-$t').value;
		document.getElementById('suffixDom2-$t').innerHTML=smtpdomain_point+document.getElementById('domain-$t').value;
		if(listname.length>0){
			textemails=textemails+listname+'@'+smtpdomain+'<br>';
			textemails=textemails+listname+'-admin@'+smtpdomain+'<br>';
			textemails=textemails+listname+'-bounces@'+smtpdomain+'<br>';
			textemails=textemails+listname+'-confirm@'+smtpdomain+'<br>';
			textemails=textemails+listname+'-join@'+smtpdomain+'<br>';
			textemails=textemails+listname+'-leave@'+smtpdomain+'<br>';
			textemails=textemails+listname+'-owner@'+smtpdomain+'<br>';
			textemails=textemails+listname+'-request@'+smtpdomain+'<br>';
			textemails=textemails+listname+'-subscribe@'+smtpdomain+'<br>';
			textemails=textemails+listname+'-unsubscribe@'+smtpdomain+'<br>';
			document.getElementById('showlists$t').innerHTML=textemails;
		}

	}
  
  function SaveList$t(){
  	 var XHR = new XHRConnection();
  	 XHR.appendData('listname_add',document.getElementById('listname_add').value);
  	 XHR.appendData('domain',document.getElementById('domain-$t').value);
  	 XHR.appendData('urlhost',document.getElementById('urlhost-$t').value);
  	 XHR.appendData('emailhost',document.getElementById('emailhost-$t').value);
  	 XHR.appendData('adminmail',document.getElementById('adminmail-$t').value);
  	 if(document.getElementById('mangle-$t').checked){XHR.appendData('mangle',1);}else{XHR.appendData('mangle',0);}
  	 AnimateDiv('animate-$t');
  	 XHR.sendAndLoad('$page', 'POST',x_SaveList$t);  
  }
  
  
  MangleCheck$t();
  FillSuffix$t();
 </script>	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'mailman.lists.php');			
}

function popup_list(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$list=$tpl->_ENGINE_parse_body("{lists}");
	$type=$tpl->_ENGINE_parse_body("{type_of_rule}");
	$mailhost=$tpl->_ENGINE_parse_body("{mailhost}");
	$urlhost=$tpl->_ENGINE_parse_body("{urlhost}");
	$hostname=$_GET["hostname"];
	$add=$tpl->_ENGINE_parse_body("{add_mailman}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$reconfigure_mta=$tpl->javascript_parse_text("{reconfigure_mta}");
	
	$btreconf=",{name: '$reconfigure_mta', bclass: 'Reconf', onpress : PostfixReconfigure$t}";
	
	
	if(trim($hostname)==null){$hostname="master";$_GET["hostname"]="master";}
$html="
<div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
var idtmp$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?table-list=yes&hostname=$hostname&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'delete', width : 40, sortable : false, align: 'center'},
		{display: '$list', name : 'list', width :190, sortable : true, align: 'left'},
		{display: '$urlhost', name : 'urlhost', width :190, sortable : true, align: 'left'},
		{display: '$mailhost', name : 'mailhost', width :190, sortable : true, align: 'left'},
		{display: '$delete', name : 'delete', width : 40, sortable : false, align: 'center'},
	],
buttons : [
		{name: '$add', bclass: 'add', onpress : MailManAddlist$t},
		{separator: true}
		$btreconf
		
		],	
	searchitems : [
		{display: '$list', name : 'list'},
		{display: '$urlhost', name : 'urlhost'},
		{display: '$mailhost', name : 'mailhost'},
		],
	sortname: 'list',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: true,
	width: 726,
	height: 345,
	singleSelect: true
	
	});   
});
	
function MailManAddlist$t(){
	YahooWin2(655,'$page?add=yes&ou={$_GET["ou"]}&t=$t','$add');	
}
function MailManEditlist$t(list){
	YahooWin2(655,'$page?add=yes&ou={$_GET["ou"]}&t=$t&listname='+list,list);	
}
var x_MailManDeleteList$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
   	$('#row'+idtmp$t).remove();
	}    

function MailManDeleteList$t(list,id){
	if(confirm('$delete: '+list+'?')){
		idtmp$t=id;
	    var XHR = new XHRConnection();
        XHR.appendData('DeleteList',list);
		XHR.sendAndLoad('$page', 'POST',x_MailManDeleteList$t);  		
	
	}
}
var x_PostfixReconfigure$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
}
   
function PostfixReconfigure$t(){
		var XHR = new XHRConnection();
        XHR.appendData('reconfigure-postfix','yes');
		XHR.sendAndLoad('$page', 'POST',x_PostfixReconfigure$t);  
}

</script>

";	
echo $html;	
	

	
}


function popup_list_items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
		
	
	$search='%';
	$table="mailmaninfos";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$webadministration_page=$tpl->_ENGINE_parse_body("{webadministration_page}");
	
	while ($ligne = mysqli_fetch_assoc($results)) {
	$list=$ligne["list"];
	$urlhost=$ligne["urlhost"];
	$emailhost=$ligne["emailhost"];
	$id=md5($list);
	$delete=imgsimple("delete-32.png",null,"MailManDeleteList$t('$list','$id')");
	
	$color="black";
	if($ligne["delete"]==1){$color="#8a8a8a";}
	
	
	$urljs="<a href=\"javascript:blur();\" OnClick=\"MailManEditlist$t('$list')\"
	style='font-size:16px;text-decoration:underline;color:$color'>";
	
	
	
	$rulfreeweb="<a href=\"javascript:blur();\" OnClick=\"Loadjs('freeweb.edit.php?hostname=$urlhost&t=$t')\"
	style='font-size:16px;text-decoration:underline;color:$color'>";
	
	$urlWebadmin="<div><a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUp('http://$urlhost/mailman/admin/$list')\"
	style='font-size:11px;text-decoration:underline;color:$color'>$webadministration_page</a></div>";
	
	if($urlhost==null){
		$urlWebadmin=null;
		$rulfreeweb=null;
	}
	
	
	

	if($list=="mailman"){$delete="&nbsp;";}
	
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<img src='img/mass-mailing-postfix-32.png'>",
			"<span style='font-size:16px;color:$color'>$urljs$list$urlWebadmin</a></span>",
			"<span style='font-size:16px;color:$color'>$rulfreeweb$urlhost</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs$emailhost</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);		

}

function popup_list_info(){
	$sock=new sockets();
	$list=$_GET["mailmaninfos"];
	
	$mailman=new mailmanctl();
	$default_uri=$mailman->DEFAULT_URL_PATTERN;
	if(substr($default_uri,strlen($default_uri)-1,1)=='/') {
		$default_uri=substr($default_uri,0,strlen($default_uri)-1);
	}
	$default_uri=str_replace('%s',$_SERVER['SERVER_NAME'],$default_uri).'/admin/'.$list;

	
	//https://localhost:9000/cgi-bin/mailman/admin/touzeau/
	
	$ini=new Bs_IniHandler();
	$ini->loadString($sock->getfile("MailManListInfo:$list"));
	
	$html="
	<input type='hidden' id='confirm_delete_mailman' value='{confirm_delete_mailman}'>
	
	<table style='width:100%'>
	<tr>
	<td valign='top' width=1% style='border:1px solid white;padding:5px'>
		" . imgtootltip('cpanel.png','{access_mailman_config}',"s_PopUpFull('$default_uri',800,800)")."
	<p>&nbsp;</p>
	" . imgtootltip('import-users-48.gif','{building_robots}',"BuildMailManRobots('$list','{$ini->_params["INFO"]["host_name"]}')")."
		
		</td>
	<td valign='top'>
	<table class=form style='width:99%'>
	<tr>
	<td colspan=2><H3>$list</H3></td>	
	</tr>
	<tr>
	<td colspan=2 align='right'>" . imgtootltip('ed_delete.gif','{delete}',"MailManDeleteList('$list','{$ini->_params["INFO"]["host_name"]}')")."</td>
	</tr>
	<tr>
		<td class=legend>{domain}:</td>
		<td><strong>{$ini->_params["INFO"]["host_name"]}</strong></td>
	</tr>
	<tr>
		<td class=legend nowrap>{subject_prefix}:</td>
		<td><strong>{$ini->_params["INFO"]["subject_prefix"]}</strong></td>
	</tr>
	<tr>
		<td class=legend>Admin:</td>
		<td nowrap><strong>{$ini->_params["INFO"]["owner"]}</strong></td>
	</tr>
	</table>
	</td>
	</tr>
	</table>		
	
	";
	
$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html,'mailman.lists.php');	
	
}


function popup_save(){
	$mailman=new mailmanctl();
	$mailman->DEFAULT_URL_PATTERN=$_GET["DEFAULT_URL_PATTERN"];
	$mailman->PUBLIC_ARCHIVE_URL=$_GET["PUBLIC_ARCHIVE_URL"];
	$mailman->Save();
	
}




function list_add(){
	$tpl=new templates();
	$listname=strtolower($_POST["listname_add"]);
	$domain=$_POST["domain"];
	$adminmail=$_POST["adminmail"];
	$urlhost=$_POST["urlhost"];
	$emailhost=$_POST["emailhost"];

	$ldap=new clladp();
	$uid=$ldap->uid_from_email($adminmail);
	
	if($uid==null){
		echo $tpl->javascript_parse_text("{mailman_admin_not_exists}",'mailman.lists.php');	
		return;
	}
	$urlhost="$urlhost.$domain";
	
	if($emailhost==null){
		if($_POST["mangle"]==0){
			echo $tpl->javascript_parse_text("{please_fill_subdomain_correctly}");
			return;
		}
	}
	
	$emailhost="$emailhost.$domain";
	
	
	if($_POST["mangle"]==1){$emailhost=$domain;}else{
		if($emailhost==null){
			echo $tpl->javascript_parse_text("{unable_to_add_this_domain_conflict}: $domain");
			return;
		}
		
	}
	
	
	$mailman=new mailmancontrol($listname);
	$mailman->emailhost=$emailhost;
	$mailman->urlhost=$urlhost;
	$mailman->adminmail=$adminmail;
	$mailman->mangle=$_POST["mangle"];
	$mailman->EditMysqlList();
	
	
	
}
function reconfigure_postfix(){
	$sock=new sockets();
	$tpl=new templates();
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
	if($EnablePostfixMultiInstance==1){
		echo $tpl->javascript_parse_text("{multiple_postfix_not_act_performed}\n`$EnablePostfixMultiInstance`",1);
		return;
		
	}
	echo $tpl->javascript_parse_text("{reconfigure_postfix_in_background}");
	$sock->getFrameWork("postfix.php?reconfigure-mailman=yes");
	
}



?>