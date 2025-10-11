<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');

$users=new usersMenus();
if(!$users->AsSquidAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["popup-add-js"])){popup_js();exit;}
if(isset($_GET["popup-param-js"])){popup_params_js();exit;}
if(isset($_GET["popup-parameters"])){popup_parameters();exit;}
if(isset($_POST["BDW"])){popup_parameters_save();exit;}
if(isset($_POST["www"])){Save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["remove"])){remove();exit;}
table();





function popup_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{new_server}");
	$html="YahooWin3(681,'$page?popup=yes','$title')";
	echo $html;
	
}

function popup_params_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{parameters}");
	$html="YahooWin3(681,'$page?popup-parameters=yes','$title')";
	echo $html;	
	
}

function popup_parameters(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sock=new sockets();
	$BDWWizard=unserialize($sock->GET_INFO("BDWWizard"));
	$BDW_CURRENT=intval($BDWWizard["BDW_FINAL"]);
	$BDWT=$BDW_CURRENT*8;
	$BDWT=$BDWT/1000;
	$BDWT_UNIT="kbits";
	if($BDWT>1000){
		$BDWT_UNIT="mbits";
		$BDWT=$BDWT/1000;
	}
	
	$BDWAR[$BDW_CURRENT]="{$BDWT}{$BDWT_UNIT}";
	$BDWAR["16000"]="128kbits";
	$BDWAR["64000"]="512kbits";
	for($i=1;$i<101;$i++){
		$val=$i*1000;
		$val=$val*1000;
		$val=$val/8;
		$BDWAR[$val]="{$i}mbits";
		
		
	}
	
	$EnableSquidBandWidthGlobal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidBandWidthGlobal"));
	
	$html="<div style='font-size:26px;margin-bottom:40px'>{limit_rate}</div>
<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:22px'>{enable}:</td>
			<td>".Field_checkbox_design("EnableSquidBandWidthGlobal", 1,"$EnableSquidBandWidthGlobal")."</td>
		</tr>			
		<tr>
			<td class=legend style='font-size:22px'>{bandwidth}:</td>
			<td>".Field_array_Hash($BDWAR, "BDW-$t",$BDWWizard["BDW"],'style:font-size:22px;padding:3px;font-weight:bold')."&nbsp;</td>
		</tr>
	</table>
<div style='text-align:right;width:100%'><HR>". button("{apply}","Start4$t()",30)."</div>
</div>
<script>
var xStart4$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	YahooWin3Hide();
	LoadAjaxRound('squid-bandwidth-general','squid.bandwww.php');
	Loadjs('squid.bandwww.progress.php');
}
	
function Start4$t(){
	var XHR = new XHRConnection();
	EnableSquidBandWidthGlobal=0;
	XHR.appendData('BDW',document.getElementById('BDW-$t').value);
	if(document.getElementById('EnableSquidBandWidthGlobal').checked){EnableSquidBandWidthGlobal=1;}
	XHR.appendData('EnableSquidBandWidthGlobal',EnableSquidBandWidthGlobal);
	XHR.sendAndLoad('$page', 'POST',xStart4$t);
}
</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function popup_parameters_save(){
	$sock=new sockets();
	$BDWWizard=unserialize($sock->GET_INFO("BDWWizard"));
	$BDWWizard["BDW_FINAL"]=$_POST["BDW"];
	$sock->SaveConfigFile(serialize($BDWWizard), "BDWWizard");
	$sock->SET_INFO("EnableSquidBandWidthGlobal", $_POST["EnableSquidBandWidthGlobal"]);
}


function popup(){
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{new_website}:</td>
		<td>		<textarea style='margin-top:5px;margin-bottom:20px;
		font-family:Courier New;font-weight:bold;width:98%;height:250px;
		border:5px solid #8E8E8E;overflow:auto;font-size:22px !important'
		id='www-$t'></textarea></td>
	</tr>		
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","Save$t()",30)."</td>
	</tr>
	</table>
	</div>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	YahooWin3Hide();
	$('#limit_bdwww_TABLE').flexReload();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('www', document.getElementById('www-$t').value);
   	XHR.sendAndLoad('$page', 'POST',xSave$t);  		
}
</script>						
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$q=new mysql_squid_builder();
	
	$www=explode("\n",$_POST["www"]);
	while (list ($i, $site) = each ($www) ){
		$site=trim(strtolower($site));
		if($site==null){continue;}
		$q->QUERY_SQL("INSERT IGNORE INTO limit_bdwww (website) VALUE ('{$site}')");
		if(!$q->ok){echo $q->mysql_error;return;}
	}

	
	
	
}


function remove(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM limit_bdwww WHERE website='{$_POST["remove"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function table(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$compile=$tpl->_ENGINE_parse_body("{compile}");
	$port=25;
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$server=$tpl->_ENGINE_parse_body("{RHSBL}");
	$add=$tpl->_ENGINE_parse_body("{new_website}");
	$add_websites=$tpl->_ENGINE_parse_body("{add}");
	$verify=$tpl->_ENGINE_parse_body("{analyze}");
	$log=$tpl->_ENGINE_parse_body("{log}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$delete_all=$tpl->_ENGINE_parse_body("{delete_all_items}");
	$import_catz_art_expl=$tpl->javascript_parse_text("{import_catz_art_expl}");
	$apply=$tpl->javascript_parse_text('{apply}');
	
	$parameters=$tpl->javascript_parse_text("{parameters}");
	
	
	$sql="CREATE TABLE IF NOT EXISTS `limit_bdwww` ( `website` VARCHAR( 128 ) NOT NULL PRIMARY KEY ) ENGINE=MYISAM;";
	
	$WIZARD=false;
	$EnableSquidBandWidthGlobal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidBandWidthGlobal"));
	if($EnableSquidBandWidthGlobal==0){$WIZARD=true;}
	if($q->COUNT_ROWS("limit_bdwww")==0){$WIZARD=true;}
	if($WIZARD){
			echo $tpl->_ENGINE_parse_body("<center style='margin:50px'>".button("{bandwidth_wizard}", 
					"Loadjs('squid.bandwww.wizard.php')",50)."</center>");
			return;
		
	}
	
	$BDWWizard=unserialize($sock->GET_INFO("BDWWizard"));
	$BDW_CURRENT=intval($BDWWizard["BDW_FINAL"]);
	$BDWT=$BDW_CURRENT*8;
	$BDWT=$BDWT/1000;
	$BDWT_UNIT="kbits";
	if($BDWT>1000){
		$BDWT_UNIT="mbits";
		$BDWT=$BDWT/1000;
	}
	$title=$tpl->javascript_parse_text("{limit_rate} {$BDWT}{$BDWT_UNIT}");
	
	$q->QUERY_SQL($sql);
	
$buttons="
	buttons : [
		
		{name: '<strong style=font-size:18px>$add</strong>', bclass: 'Add', onpress : NewServer$t},
		{name: '<strong style=font-size:18px>$parameters</strong>', bclass: 'Settings', onpress : Parameters$t},
		{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : Apply$t},
		
	
		],";
	
		$html="
		<table class='limit_bdwww_TABLE' style='display: none' id='limit_bdwww_TABLE' style='width:100%'></table>
		<script>
		var xsite='';
		$(document).ready(function(){
		$('#limit_bdwww_TABLE').flexigrid({
		url: '$page?search=yes',
		dataType: 'json',
		colModel : [
		{display: '<span style=font-size:22px>$pattern</span>', name : 'website', width : 651, sortable : false, align: 'left'},
		{display: 'DEL', name : 'Del', width : 60, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$pattern', name : 'website'},
		],
		sortname: 'website',
		sortorder: 'asc',
		usepager: true,
		title: '<strong style=font-size:30px>$title</strong>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 600,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
var xRemoveWebSiteBandwidth= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#limit_bdwww_TABLE').flexReload();
}

function NewServer$t(){
	Loadjs('$page?popup-add-js=yes');

}

function Apply$t(){
	Loadjs('squid.bandwww.progress.php');
}

function Parameters$t(){
	Loadjs('$page?popup-param-js=yes');
}
	
	
function RemoveWebSiteBandwidth(ID){
	var XHR = new XHRConnection();
	XHR.appendData('remove',ID);
	XHR.sendAndLoad('$page', 'POST',xRemoveWebSiteBandwidth);
	}
	
</script>
	
	";
	echo $html;
}

function search(){
	$search='%';
	$page=1;
	$port=$_GET["port"];
	$q=new mysql_squid_builder();
	$tpl=new templates();
	
	
	$sql_search=string_to_flexquery();
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
		
	if($sql_search<>null){
	
	
		$sql="SELECT COUNT(*) AS TCOUNT FROM limit_bdwww WHERE  $sql_search";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) AS tcount FROM limit_bdwww";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM limit_bdwww WHERE 1 $sql_search $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = mysqli_num_rows($results);
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){json_error_show("No data",1);}
	
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$dnsbl=$ligne["website"];
		$delete=imgsimple("delete-42.png",null,"RemoveWebSiteBandwidth('$dnsbl')");
	
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<strong style='font-size:26px'>$dnsbl</strong>",
						"<center>$delete</center>")
		);
	
	
	}
	echo json_encode($data);
	
}


