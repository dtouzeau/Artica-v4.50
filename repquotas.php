<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');

	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	
	
$usersmenus=new usersMenus();
if($usersmenus->AsArticaAdministrator==false){die("DIE " .__FILE__." Line: ".__LINE__);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tri"])){echo events_table();exit;}
if(isset($_GET["events-table"])){events_table();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["delete_all_items"])){delete_all_items();exit;}


popup();
function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$member=$tpl->_ENGINE_parse_body("{member}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$files=$tpl->_ENGINE_parse_body("{files}");	
	
	$TB_HEIGHT=450;
	$TB_WIDTH=550;
	$TB2_WIDTH=400;

	$TB_WIDTH=872;
	$TB2_WIDTH=610;
	
	$explain=$tpl->_ENGINE_parse_body("<div class=explain style='font-size:14px'>{quotas_table_explain}</div>");
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$new_category', bclass: 'Catz', onpress : AddCatz},
	
		],	";
	$html="$explain
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?events-table=yes',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width :326, sortable : true, align: 'left'},
		{display: '$size', name : 'blockused', width :190, sortable : true, align: 'left'},
		{display: '$files', name : 'filesusers', width : 190, sortable : true, align: 'left'},
	],

	searchitems : [
		{display: '$member', name : 'uid'},
		],
	sortname: 'blockused',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true
	
	});   
});

	function articaShowEvent(ID){
		 YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
	}
</script>";
	
	echo $html;	
	
}

function events_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="repquota";
	$page=1;
	
	
	
	if($q->COUNT_ROWS("repquota","artica_events")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results=$q->QUERY_SQL($sql,"artica_events");
	writelogs($sql ." ".mysqli_num_rows($result)." rows",__FUNCTION__,__FILE__,__LINE__);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		writelogs("ERROR !!! -> $sql",__FUNCTION__,__FILE__,__LINE__);
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		//writelogs("ROWS -> {$ligne["uid"]} {$ligne["blockused"]}",__FUNCTION__,__FILE__,__LINE__);
		$addons=null;
		if($ligne["blockLimit"]>0){
			$blockLimitText=FormatBytes($ligne["blockLimit"]);
			$pourc=round(($ligne["blockused"]/$ligne["blockLimit"])*100,2);
			$ligne["blockused"]=FormatBytes($ligne["blockused"]);
			$ligne["blockused"]="{$ligne["blockused"]}/{$blockLimitText}&nbsp;&nbsp;($pourc%)";
		}else{
			$ligne["blockused"]=FormatBytes($ligne["blockused"]);
		}
		
		if($ligne["Fileslimit"]>0){
			$pourc=round(($ligne["filesusers"]/$ligne["Fileslimit"])*100,2);
			$ligne["filesusers"]="{$ligne["filesusers"]}/{$ligne["Fileslimit"]}&nbsp;&nbsp;($pourc%)";
		}

		if(substr($ligne['uid'], 0,1)=="@"){$addons="&nbsp;(".$tpl->_ENGINE_parse_body("{group}").")";}
		
		
	$data['rows'][] = array(
		'id' => $ligne['uid'],
		'cell' => array(
		"<a href=\"javascript:blur();\" OnClick=\javascript:Loadjs('domains.edit.user.quota.php?uid={$ligne['uid']}&userid={$ligne['uid']}');\" style='font-size:16px;font-weight:bold;text-decoration:underline'>{$ligne["uid"]}$addons</a>"
		,"<span style='font-size:16px;font-weight:bold'>{$ligne["blockused"]}</span>"
		,"<span style='font-size:16px;font-weight:bold'>{$ligne["filesusers"]}</span>" )
		);
	}
	
	
echo json_encode($data);		

}




//ChangeSuperSuser	
	
?>	

