<?php

	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.mysql.inc');
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["browse-domains-list"])){browse_domains_list();exit;}
	if(isset($_GET["add-js"])){add_js();exit;}
	if(isset($_GET["add-popup"])){add_domain();exit;}
	
js();
	
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{browse} {domains}");
	$html="YahooSetupControl('650','$page?popup=yes&field={$_GET["field"]}','$title');";
	echo $html;
}
function add_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_internet_domain}");
	$html="LoadWinORG(650,'$page?add-popup=yes&t={$_GET["t"]}','$title')";
	echo $html;

}

function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{internet_domains}");
	$new=$tpl->javascript_parse_text("{new_internet_domain}");
	$item=$tpl->javascript_parse_text("{domains}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	
	$select=$tpl->javascript_parse_text("{select}");
	$apply=$tpl->javascript_parse_text("{apply}");
	
	
	$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
function LoadTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?browse-domains-list=yes&t=$t&field={$_GET["field"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'dom', width : 50, sortable : false, align: 'center'},
	{display: '$item', name : 'pattern', width : 389, sortable : true, align: 'left'},
	{display: '$select', name : 'del', width : 50, sortable : false, align: 'center'},
	
	],
	buttons : [
	{name: '$new', bclass: 'add', onpress : NewRule$t},
	],
	searchitems : [
	{display: '$item', name : 'pattern'},
	],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '<div style=\"font-size:16px\">$title</div>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});
}
var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	ExecuteByClassName('SearchFunction');
}
	
function SelectBrowseDomains$t(domain){
	document.getElementById('{$_GET["field"]}').value=domain
	YahooSetupControlHide();
}
	

function Apply$t(){
	Loadjs('firehol.progress.php');
}

function NewRule$t() {
	Loadjs('$page?add-js=yes&t=$t',true);
}
LoadTable$t();
</script>
";
	
	echo $html;

}

function add_domain(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	if($users->AsSystemAdministrator){
		$ldap=new clladp();
		$ous=$ldap->hash_get_ou(true);
		$orgs=Field_array_Hash($ous, "ou-$t",null,"style:font-size:18px");
	}else{
		$orgs="<span style='font-size:18px'>{$_SESSION["ou"]}</span>
		<input type='hidden' id='ou-$t' value='{$_SESSION["ou"]}'>";
	}
	
	
	$html="<div style='font-size:26px;margin-bottom:20px'>{new_internet_domain}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{domain}:</div>
		<td>". Field_text("domain-$t",null,"font-size:18px",null,null,null,false,"SaveCheck$t(event)")."</div>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{organization}:</div>
		<td>$orgs</div>
	</tr>						
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","Save$t()",26)."</td>
	</tr>
	</table>	
<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		WinORGHide();
		$('#flexRT{$_GET["t"]}').flexReload();
		UnlockPage();
		if(document.getElementById('main_config_dhcpd')){RefreshTab('main_config_dhcpd');}
	}
	
function SaveCheck$t(e){
	if(!checkEnter(e)){return;}
	Save$t();

}
function Save$t(){
		LockPage();
		var XHR = new XHRConnection();
		XHR.appendData('AddNewInternetDomain',document.getElementById('ou-$t').value);
		XHR.appendData('AddNewInternetDomainDomainName',document.getElementById('domain-$t').value);
		XHR.sendAndLoad('domains.edit.domains.php', 'GET',xSave$t);
		}

</script>			
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function browse_domains_list(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ldap=new clladp();
	$users=new usersMenus();
	$search=$_GET["search"];
	$t=$_GET["t"];
	$search="*$search*";
	$search=str_replace("***","*",$search);
	$search=str_replace("**","*",$search);
	$search_sql=str_replace("*","%",$search);
	$search_sql=str_replace("%%","%",$search_sql);
	$search_regex=str_replace(".","\.",$search);	
	$search_regex=str_replace("*",".*?",$search);
	
	if($users->AsSystemAdministrator){
		$q=new mysql();
		$sql="SELECT domain FROM officials_domains WHERE domain LIKE '$search_sql' ORDER BY domain";
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$results=$q->QUERY_SQL($sql,"artica_backup");
	
		if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$domains[$ligne["domain"]]=$ligne["domain"];}
			$hash=$ldap->hash_get_all_domains();
			foreach ($hash as $num=>$ligne){if(preg_match("#$search_regex#", $ligne)){$domains[$ligne]=$ligne;}}
	
	}else{
		$hash=$ldap->hash_get_domains_ou($_SESSION["ou"]);
		foreach ($hash as $num=>$ligne){if(preg_match("#$search_regex#", $ligne)){$domains[$ligne]=$ligne;}}
	}
	
	ksort($domains);
	
	if(count($domains)<$_POST["rp"]){$_POST["rp"]=$domains;}
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $_POST["rp"];
	$data['rows'] = array();
	
	if(count($domains)==0){json_error_show("no data");}
	
	
	
	while (list ($num, $ligne) = each ($domains) ){
		$val=0;
		$color="black";
		$delete=imgsimple("delete-48.png",null,"Delete{$_GET["t"]}({$ligne["ID"]})");
		$select=imgtootltip("arrow-blue-left-32.png","{select}","SelectBrowseDomains$t('$ligne')");
		$data['rows'][] = array(
				'id' => "{$ligne["ID"]}",
				'cell' => array(
				"<div style='font-size:22px;font-weight:bold;color:$color;margin-top:10px'><img src='img/domain-32.png'></span>",
				"<div style='font-size:22px;font-weight:bold;color:$color;margin-top:10px'>$ligne</a>",
				"<div>$select</div>")
		);
	}
	
	echo json_encode($data);
	
	
}