<?php
if(isset($_GET["verbose"])){echo __LINE__." verbose OK<br>\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.calendar.inc');
	include_once('ressources/class.tcpip.inc');
	$users=new usersMenus();
	if(!$users->AsDansGuardianAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}	

	if(isset($_GET["www-popup"])){popup();exit;}
	
js();


function js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{unblock} {$_GET["www"]}");
	$www_enc=urlencode($_GET["www"]);
	echo "YahooWinBrowse('650','$page?www-popup=$www_enc','$title')";
	
	
}

function popup(){
	$tpl=new templates();
	
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	
	
	$sql="SELECT category FROM personal_categories ORDER BY category";
	$results=$q->QUERY_SQL($sql);
	$CountDecat=mysqli_num_rows($results);
	$array[null]="{select}";
	while ($ligne = mysqli_fetch_assoc($results)) {
		$array[$ligne["category"]]=$ligne["category"];
	}
	
	$familysite=$q->GetFamilySites($_GET["www-popup"]);
	$html="<div style='font-size:26px;margin-bottom:15px'>{$_GET["www-popup"]}</div>
	
	<div style='width:98%' class=form>
	<table style='width:99%'>
		<tr>
			<td><strong style='font-size:16px'>{unblock}: {$_GET["www-popup"]}</strong></td>
			<td>&nbsp;</td>
			<td width=1% nowrap>". button("Go","Save$t('{$_GET["www-popup"]}')",16)."</td>
		</tR>
	";
	if($familysite<>$_GET["www-popup"]){
	$html=$html."<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
			<td><strong style='font-size:16px'>{or} {unblock}: $familysite</strong></td>
			<td>&nbsp;</td>
			<td width=1% nowrap>". button("Go","Save$t('$familysite')",16)."</td>
		</tR>";
		
	}
if($CountDecat>0){
	$html=$html."
			<tr>
			<td><strong style='font-size:16px'>{or} {move_to_category}:</strong></td>
			<td>".Field_array_Hash($array, "categories-$t",null,"style:font-size:16px")."</td>
			<td width=1% nowrap>". button("Go","Move$t('$familysite')",16)."</td>
			</tR>
";
	}
	
	$html=$html."	
</table>		
</div>
<script>
function xSave$t(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	YahooWinBrowseHide();
	Loadjs('squid.compile.whiteblack.progress.php?ask=yes');
	
}			
			
function Save$t(www){
	var XHR = new XHRConnection();
	XHR.appendData('whitelist-single',www);
	XHR.sendAndLoad('squid.urlrewriteaccessdeny.php', 'POST',xSave$t);	
}
function xMove$t(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	var category=document.getElementById('categories-$t').value;
	YahooWinBrowseHide();
	Loadjs('ufdbguard.compile.category.php?category='+category);
	
}	

function Move$t(www){
	var XHR = new XHRConnection();
	XHR.appendData('textToParseCats',www);
	XHR.appendData('category',document.getElementById('categories-$t').value);
	XHR.appendData('ForceCat',1);
	XHR.appendData('ForceExt',1);
	XHR.sendAndLoad('squid.visited.php', 'POST',xMove$t);	
}



</script>
";				
	
echo $tpl->_ENGINE_parse_body($html);	
}

