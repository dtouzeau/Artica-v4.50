<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.graphs.inc');
	


	$user=new usersMenus();
	if($user->AllowViewStatistics==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	

	if(isset($_GET["rows-table"])){rows_table();exit;}
	if(isset($_GET["details-js"])){details_js();exit;}
	if(isset($_GET["details-tabs"])){details_tab();exit;}
	if(isset($_GET["details-today"])){details_today("today");exit;}
	if(isset($_GET["details-week"])){details_today("week");exit;}
	if(isset($_GET["details-month"])){details_today("month");exit;}
	
	if(isset($_GET["details-table"])){details_table();exit;}
	if(isset($_GET["details-tablerows"])){details_tablerows();exit;}
	
	
page();

function details_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$array["IP_TO"]="{ipaddr} {to}";
	$array["IP_FROM"]="{ipaddr} {from}";
	$title=$tpl->_ENGINE_parse_body($array[$_GET["field"]]);
	$html="YahooWin3('750','$page?details-tabs=yes&field={$_GET["field"]}&value={$_GET["value"]}','$title::{$_GET["value"]}::{$_GET["host"]}')";
	echo $html;
	
	
}

function details_tab(){
		$tpl=new templates();
		$page=CurrentPageName();
	
		$array["details-today"]='{today}';
		$array["details-week"]='{week}';
		$array["details-month"]='{month}';
		$t=time();
		
	foreach ($array as $num=>$ligne){
		$tab[]="<li><a href=\"$page?$num=yes&field={$_GET["field"]}&value={$_GET["value"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='ipbandtabs' style='background-color:white;'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#ipbandtabs').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function page(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->_ENGINE_parse_body("{to}");
	$ipaddr1=$tpl->_ENGINE_parse_body("{ipaddr} {from}");
	$ipaddr2=$tpl->_ENGINE_parse_body("{ipaddr} {to}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$TB_HEIGHT=450;
	$TABLE_WIDTH=878;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;
	$bandwith=$tpl->_ENGINE_parse_body("{bandwith}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	 
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},
	
		],	";
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?rows-table=yes',
	dataType: 'json',
	colModel : [
		{display: '$ipaddr1', name : 'IP_FROM', width :371, sortable : false, align: 'left'},
		{display: '$ipaddr2', name : 'IP_TO', width :371, sortable : true, align: 'left'},
		{display: '$bandwith', name : 'tsize', width :80, sortable : true, align: 'left'},
		
	],
	
	searchitems : [
		{display: '$ipaddr1', name : 'IP_FROM'},
		{display: '$ipaddr2', name : 'IP_TO'},
		{display: '$hostname $from', name : 'IP_FROM_HOST'},
		{display: '$hostname $to', name : 'IP_TO_HOST'},		
		],	
	
	sortname: 'tsize',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TABLE_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true
	
	});   
});

	
	
</script>";
	
echo $html;
}	
function rows_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$q->BuildTables();
	$database="artica_events";
	$search='%';
	$table="ipband";
	$page=1;
	$ORDER=null;
	
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){
		json_error_show("no data");
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		if(strpos("  {$_POST["query"]}", "%")>0){
			$searchstring="AND `{$_POST["qtype"]}` LIKE '$search'";
		}else{
			$searchstring="AND `{$_POST["qtype"]}` = '$search'";
		}
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		
		$total = $q->COUNT_ROWS($table, $database);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT AVG(size) as tsize,IP_FROM,IP_TO,`IP_TO_HOST`,`IP_FROM_HOST` FROM `$table` GROUP BY IP_FROM,IP_TO_HOST,IP_FROM_HOST,IP_TO HAVING 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){
		json_error_show($q->mysql_error);
	}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(mysqli_num_rows($results)==0){
		json_error_show("no data");
	}	

	while ($ligne = mysqli_fetch_assoc($results)) {
		$md5=md5(serialize($ligne));
		$ipfrom=$ligne["IP_FROM"];
		$ipto=$ligne["IP_TO"];
		$ipfrom_host=$ligne["IP_FROM_HOST"];
		$ipto_host=$ligne["IP_TO_HOST"];
		if($ipfrom_host==null){
			$ipfrom_host=resolveHost($ipfrom);
			$q->QUERY_SQL("UPDATE ipband SET IP_FROM_HOST='$ipfrom_host' WHERE IP_FROM='$ipfrom'","artica_events");
		}
		if($ipto_host==null){
			$ipto_host=resolveHost($ipto);
			$q->QUERY_SQL("UPDATE ipband SET IP_TO_HOST='$ipto_host' WHERE IP_TO='$ipto'","artica_events");
		}
		
		
		if($ipfrom==$ipfrom_host){$ipfrom_host=null;}
		if($ipto==$ipto_host){$ipto_host=null;}
		$size=FormatBytes($ligne["tsize"]/1024);
		
		$ahrefFrom="<a href=\"javascript:blur();\"
		OnClick=\"Loadjs('$MyPage?details-js=yes&field=IP_FROM&value=$ipfrom')\"
		style='font-size:13px;text-decoration:underline'>";
		
		$ahrefto="<a href=\"javascript:blur();\" 
		OnClick=\"Loadjs('$MyPage?details-js=yes&field=IP_TO&value=$ipto')\"
		style='font-size:13px;text-decoration:underline'>";		
		
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:13px'>$ahrefFrom$ipfrom</a> ($ipfrom_host)</span>",
			"<span style='font-size:13px'>$ahrefto$ipto</a> ($ipto_host)</span>",
			"<span style='font-size:13px'>$size/s</span>",
		 
	
		)
		);
	}
	
	
echo json_encode($data);		

}

function resolveHost($ip){
	if(isset($GLOBALS[$ip])){return $GLOBALS[$ip];}
	$GLOBALS[$ip]=gethostbyaddr($ip);
	return $GLOBALS[$ip];
	
}

function details_today($timerq){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();	
	$now=date('Y-m-d');
	$field=$_GET["field"];
	$value=$_GET["value"];
	$t=time();
	
	
	
	$ssubsql="SELECT size,HOUR(zDate) as hour,DATE_FORMAT(zDate,'%Y-%m-%d') as zDate FROM ipband WHERE $field='$value' AND DATE_FORMAT(zDate,'%Y-%m-%d')=DATE_FORMAT(NOW(),'%Y-%m-%d')";
	
	
	$sql="SELECT AVG(size) as kbs,hour as tdate
		FROM ($ssubsql) as t GROUP BY
		tdate
		ORDER BY hour";
	
	if($timerq=="week"){
		$ssubsql="SELECT size,DAY(zDate) as zDate FROM ipband WHERE $field='$value' AND WEEK(zDate)=WEEK(NOW())";
		$sql="SELECT AVG(size) as kbs,zDate as tdate FROM ($ssubsql) as t GROUP BY tdate ORDER BY tdate";		
		
	}
	
	if($timerq=="month"){
		$ssubsql="SELECT size,DAY(zDate) as zDate FROM ipband WHERE $field='$value' AND MONTH(zDate)=MONTH(NOW())";
		$sql="SELECT AVG(size) as kbs,zDate as tdate FROM ($ssubsql) as t GROUP BY tdate ORDER BY tdate";		
		
	}	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysqli_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2>$sql</center>");return;}
	
	$nb_events=mysqli_num_rows($results);
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$ydata[]=round(($ligne["kbs"]/1024),2);
		
	}	
	$t=time();
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.$field.$value.png";
	$gp=new artica_graphs();
	$gp->width=550;
	$gp->height=220;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{hours}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	if(is_file($targetedfile)){
		$image="<center style='margin-top:10px' class=form><img src='$targetedfile?$t'></center>";
	}	
	$html="
	$image
	<div id='ttable$t'></div>
	<script>
	LoadAjax('ttable$t','$page?details-table=yes&time=$timerq&field={$_GET["field"]}&value={$_GET["value"]}');
	</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function details_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->_ENGINE_parse_body("{to}");
	$ipaddr1=$tpl->_ENGINE_parse_body("{ipaddr} {from}");
	$ipaddr2=$tpl->_ENGINE_parse_body("{ipaddr} {to}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$TB_HEIGHT=250;
	$TABLE_WIDTH=700;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;
	$bandwith=$tpl->_ENGINE_parse_body("{bandwith}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");	
$t=time();	
$html="	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?details-tablerows=yes&time={$_GET["time"]}&field={$_GET["field"]}&value={$_GET["value"]}',
	dataType: 'json',
	colModel : [
		{display: 'time', name : 'zDate', width :80, sortable : false, align: 'left'},
		{display: '$ipaddr1', name : 'IP_FROM', width :208, sortable : false, align: 'left'},
		{display: '$ipaddr2', name : 'IP_TO', width :208, sortable : true, align: 'left'},
		{display: 'proto', name : 'proto', width :44, sortable : true, align: 'left'},
		{display: '$bandwith', name : 'tsize', width :80, sortable : true, align: 'left'},
		
	],
	
	searchitems : [
		{display: '$ipaddr1', name : 'IP_FROM'},
		{display: '$ipaddr2', name : 'IP_TO'},
		{display: '$hostname $from', name : 'IP_FROM_HOST'},
		{display: '$hostname $to', name : 'IP_TO_HOST'},		
		],	
	
	sortname: 'zDate',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TABLE_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true
	
	});   
});";	

echo $html;
	
}

function details_tablerows(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_events";
	$search='%';
	$table="ipband";
	$tablesource=$table;
	$page=1;
	$ORDER=null;
	
	$field=$_GET["field"];
	$value=$_GET["value"];
	
	
	if($_GET["time"]=="today"){
		$timeunit="h";
		$table="(SELECT size,HOUR(zDate) as zDate,IP_FROM,IP_TO,`IP_TO_HOST`,`IP_FROM_HOST`,proto FROM $table WHERE DATE_FORMAT(zDate,'%Y-%m-%d')=DATE_FORMAT(NOW(),'%Y-%m-%d') AND {$_GET["field"]}='{$_GET["value"]}') as t";
	}
	
	if($_GET["time"]=="week"){
		$timeunit=$tpl->_ENGINE_parse_body(" {day}");
		$table="(SELECT size,DAY(zDate) as zDate,IP_FROM,IP_TO,`IP_TO_HOST`,`IP_FROM_HOST`,proto  FROM $table WHERE WEEK(zDate)=WEEK(NOW()) AND {$_GET["field"]}='{$_GET["value"]}') as t";
	}

	if($_GET["time"]=="month"){
		$timeunit=$tpl->_ENGINE_parse_body(" {day}");
		$table="(SELECT size,DAY(zDate) as zDate,IP_FROM,IP_TO,`IP_TO_HOST`,`IP_FROM_HOST`,proto FROM $table WHERE MONTH(zDate)=MONTH(NOW()) AND {$_GET["field"]}='{$_GET["value"]}') as t";
	}		
	
	$total=0;
	if($q->COUNT_ROWS($tablesource,$database)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		if(strpos("  {$_POST["query"]}", "%")>0){
			$searchstring=" AND `{$_POST["qtype"]}` LIKE '$search'";
		}else{
			$searchstring=" AND `{$_POST["qtype"]}` = '$search'";
		}
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT AVG(size) as tsize,zDate,IP_FROM,IP_TO,`IP_TO_HOST`,`IP_FROM_HOST`,proto FROM $table GROUP BY zDate,IP_FROM,IP_TO_HOST,IP_FROM_HOST,IP_TO HAVING 1 $FORCE_FILTER $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){
		
	}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	

	while ($ligne = mysqli_fetch_assoc($results)) {
		$md5=md5(serialize($ligne));
		$ipfrom=$ligne["IP_FROM"];
		$ipto=$ligne["IP_TO"];
		$ipfrom_host=$ligne["IP_FROM_HOST"];
		$ipto_host=$ligne["IP_TO_HOST"];
		$proto=$ligne["proto"];
		if($ipfrom_host==null){
			$ipfrom_host=resolveHost($ipfrom);
			$q->QUERY_SQL("UPDATE ipband SET IP_FROM_HOST='$ipfrom_host' WHERE IP_FROM='$ipfrom'","artica_events");
		}
		if($ipto_host==null){
			$ipto_host=resolveHost($ipto);
			$q->QUERY_SQL("UPDATE ipband SET IP_TO_HOST='$ipto_host' WHERE IP_TO='$ipto'","artica_events");
		}
		
		
		if($ipfrom==$ipfrom_host){$ipfrom_host=null;}
		if($ipto==$ipto_host){$ipto_host=null;}
		$size=FormatBytes($ligne["tsize"]/1024);
		
		$ahrefFrom="<a href=\"javascript:blur();\"
		OnClick=\"Loadjs('$MyPage?details-js=yes&field=IP_FROM&value=$ipfrom&host=$ipfrom_host')\"
		style='font-size:13px;text-decoration:underline'>";
		
		$ahrefto="<a href=\"javascript:blur();\" 
		OnClick=\"Loadjs('$MyPage?details-js=yes&field=IP_TO&value=$ipto&host=$ipto_host')\"
		style='font-size:13px;text-decoration:underline'>";		
		$ligne["zDate"]=str_replace(date('%Y-%m-'), "", $ligne["zDate"]);
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:12.5px'>{$ligne["zDate"]}$timeunit</span>",
			"<span style='font-size:12.5px'>$ahrefFrom$ipfrom</a> ($ipfrom_host)</span>",
			"<span style='font-size:12.5px'>$ahrefto$ipto</a> ($ipto_host)</span>",
			"<span style='font-size:12.5px'>$proto</span>",
			"<span style='font-size:12.5px'>$size/s</span>",
		 
	
		)
		);
	}
	
	
echo json_encode($data);		
}

