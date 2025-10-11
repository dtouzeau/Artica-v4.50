<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["hourly"])){PAGE_THIS_HOUR();exit;}
if(isset($_GET["today"])){PAGE_TODAY();exit;}
if(isset($_GET["week"])){PAGE_WEEK();exit;}
if(isset($_GET["month"])){PAGE_MONTH();exit;}

if(isset($_GET["familysite"])){GRAPH_FAMILY();exit;}
if(isset($_GET["category"])){GRAPH_CATEGORY();exit;}
if(isset($_GET["ipaddr"])){GRAPH_IPADDR();exit;}
if(isset($_GET["rules"])){GRAPH_RULES();exit;}
if(isset($_GET["courbe"])){COURBE();exit;}



page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$addon=null;
	$APP_DNSFILTERD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSFILTERD_VERSION");
	
	$head="	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_DNSFILTERD} $APP_DNSFILTERD_VERSION {statistics}</h1>
			<p>{APP_DNSFILTERD_STATS_EXPLAIN}</p>
		</div>
	</div>";
	
	if(isset($_GET["with-events"])){$addon="&with-events=yes";$head=null;}

	$html="
	$head
	<div class='row'>
	<div id='progress-dnsfilter-restart'></div>
	<div class='ibox-content' style='min-height:600px'>
	<div id='table-dnsfilterd'></div>
	</div>
	</div>



	<script>
	LoadAjax('table-dnsfilterd','$page?tabs=yes$addon');
	$.address.state('/');
	$.address.value('/dnsfilter-statistics');
	$.address.title('Artica: DNS Filter Statistics');
	</script>";

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: DNS Filter Statistics",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
	$HideCorporateFeatures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCorporateFeatures"));
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	
	if(isset($_GET["with-events"])){
		$array["{events}"]="fw.dns.filterd.events.php";
	}
	
	$array["{this_hour}"]="$page?hourly=yes";
	$array["{today}"]="$page?today=yes";
	$array["{this_week}"]="$page?week=yes";
	$array["{this_month}"]="$page?month=yes";
	
	echo $tpl->tabs_default($array);
}

function PAGE_TODAY(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td valign='top' colspan=2>";
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_COURBE_TODAY"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_COURBE_TODAY' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?courbe=yes&container=DNSFILTERD_STATS_COURBE_TODAY');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";
	$html[]="</tr>";
	
	
	$html[]="<td valign='top' width=50%>";
	
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_FAMILYSITE_TODAY"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_FAMILYSITE_TODAY' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?familysite=yes&container=DNSFILTERD_STATS_FAMILYSITE_TODAY');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	
	$html[]="</td>";
	$html[]="<td valign='top' width=50%>";
	
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_CATEGORY_TODAY"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_CATEGORY_TODAY' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?category=yes&container=DNSFILTERD_STATS_CATEGORY_TODAY');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}	
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="<tr>";
	$html[]="<td valign='top' width=50%>";
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_IPADDR_TODAY"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_IPADDR_TODAY' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?ipaddr=yes&container=DNSFILTERD_STATS_IPADDR_TODAY');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";
	
	$html[]="<td valign='top' width=50%>";
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_RULENAME_TODAY"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_RULENAME_TODAY' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?rules=yes&container=DNSFILTERD_STATS_RULENAME_TODAY');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table><script>".@implode("\n",$js)."</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function PAGE_WEEK(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td valign='top' colspan=2>";
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_COURBE_WEEK"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_COURBE_WEEK' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?courbe=yes&container=DNSFILTERD_STATS_COURBE_WEEK&unit=day');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";
	$html[]="</tr>";


	$html[]="<td valign='top' width=50%>";

	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_FAMILYSITE_WEEK"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_FAMILYSITE_WEEK' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?familysite=yes&container=DNSFILTERD_STATS_FAMILYSITE_WEEK');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}

	$html[]="</td>";
	$html[]="<td valign='top' width=50%>";

	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_CATEGORY_WEEK"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_CATEGORY_WEEK' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?category=yes&container=DNSFILTERD_STATS_CATEGORY_WEEK');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="<tr>";
	$html[]="<td valign='top' width=50%>";
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_IPADDR_WEEK"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_IPADDR_WEEK' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?ipaddr=yes&container=DNSFILTERD_STATS_IPADDR_WEEK');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";

	$html[]="<td valign='top' width=50%>";
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_RULENAME_WEEK"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_RULENAME_WEEK' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?rules=yes&container=DNSFILTERD_STATS_RULENAME_WEEK');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table><script>".@implode("\n",$js)."</script>";
	echo $tpl->_ENGINE_parse_body($html);

}
function PAGE_MONTH(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td valign='top' colspan=2>";
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_COURBE_MONTH"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_COURBE_MONTH' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?courbe=yes&container=DNSFILTERD_STATS_COURBE_MONTH&unit=day');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";
	$html[]="</tr>";


	$html[]="<td valign='top' width=50%>";

	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_FAMILYSITE_MONTH"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_FAMILYSITE_MONTH' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?familysite=yes&container=DNSFILTERD_STATS_FAMILYSITE_MONTH');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}

	$html[]="</td>";
	$html[]="<td valign='top' width=50%>";

	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_CATEGORY_MONTH"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_CATEGORY_MONTH' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?category=yes&container=DNSFILTERD_STATS_CATEGORY_MONTH');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="<tr>";
	$html[]="<td valign='top' width=50%>";
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_IPADDR_MONTH"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_IPADDR_MONTH' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?ipaddr=yes&container=DNSFILTERD_STATS_IPADDR_MONTH');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";

	$html[]="<td valign='top' width=50%>";
	$results=unserialize($sock->GET_INFO("DNSFILTERD_STATS_RULENAME_MONTH"));
	if(count($results)>1){
		$html[]="<div id='DNSFILTERD_STATS_RULENAME_MONTH' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?rules=yes&container=DNSFILTERD_STATS_RULENAME_MONTH');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table><script>".@implode("\n",$js)."</script>";
	echo $tpl->_ENGINE_parse_body($html);

}
function PAGE_THIS_HOUR(){
	$Directory="/home/artica/SQLITE_DNSFILTER";

	$currentfile=date("Y-m-d-H").".db";
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$html[]="<div style='margin-top:10px'></div>";
	$q=new lib_sqlite("$Directory/$currentfile");
	$PieData=array();
	$sql="SELECT count(*) as hits, familysite as string FROM statistics GROUP BY sitename ORDER BY hits LIMIT 20";
	$results=$q->QUERY_SQL($sql);
	
	
	
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td valign='top' width=50%>";
	
	if(count($results)>1){
		foreach ($results as $index=>$ligne){$PieData[$ligne["string"]]=$ligne["hits"];}
		$sock->SaveConfigFile(serialize($PieData), "DNSFILTERD_STATS_FAMILYSITE_HOUR");
		$html[]="<div id='DNSFILTERD_STATS_FAMILYSITE_HOUR' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?familysite=yes&container=DNSFILTERD_STATS_FAMILYSITE_HOUR');";
	}else{
		$html[]="<div class='alert alert-warning alert-dismissable'>{no_data}</div>";
	}
	
	$html[]="</td>";
	$html[]="<td valign='top' width=50%>";
	
	$PieData=array();
	$sql="SELECT count(*) as hits, category FROM statistics GROUP BY category ORDER BY hits LIMIT 20";
	$results=$q->QUERY_SQL($sql);
	if(count($results)>1){
		foreach ($results as $index=>$ligne){
			$category=$tpl->CategoryidToName($ligne["category"]);
			VERBOSE("{$ligne["category"]} -> $category", __LINE__);
			$PieData[$category]=$ligne["hits"];}
		$sock->SaveConfigFile(serialize($PieData), "DNSFILTERD_STATS_CATEGORIES_HOUR");
		$html[]="<div id='DNSFILTERD_STATS_CATEGORY_HOUR' style='with:400px;height:400px'></div>";
		$js[]="Loadjs('$page?category=yes&container=DNSFILTERD_STATS_CATEGORY_HOUR');";
	}
	
	$html[]="</tr>";
	$html[]="<tr>";
	$html[]="<td valign='top' width=50%>";
	
	$PieData=array();
	$sql="SELECT count(*) as hits, ipaddr FROM statistics GROUP BY ipaddr ORDER BY hits LIMIT 20";
	$results=$q->QUERY_SQL($sql);
	if(count($results)>1){
		foreach ($results as $index=>$ligne){
			VERBOSE("{$ligne["ipaddr"]} -> {$ligne["hits"]}", __LINE__);
			$PieData[$ligne["ipaddr"]]=$ligne["hits"];}
			$sock->SaveConfigFile(serialize($PieData), "DNSFILTERD_STATS_IPADDR_HOUR");
			$html[]="<div id='DNSFILTERD_STATS_IPADDR_HOUR' style='with:400px;height:400px'></div>";
			$js[]="Loadjs('$page?ipaddr=yes&container=DNSFILTERD_STATS_IPADDR_HOUR');";
	}
	
	
	$html[]="</td>";
	$html[]="<td valign='top' width=50%>";
	$PieData=array();
	$sql="SELECT count(*) as hits, rulename FROM statistics GROUP BY rulename ORDER BY hits LIMIT 20";
	$results=$q->QUERY_SQL($sql);
	if(count($results)>1){
		foreach ($results as $index=>$ligne){
			VERBOSE("{$ligne["ipaddr"]} -> {$ligne["hits"]}", __LINE__);
			$PieData[$ligne["ipaddr"]]=$ligne["hits"];}
			$sock->SaveConfigFile(serialize($PieData), "DNSFILTERD_STATS_RULES_HOUR");
			$html[]="<div id='DNSFILTERD_STATS_RULES_HOUR' style='with:400px;height:400px'></div>";
			$js[]="Loadjs('$page?rules=yes&container=DNSFILTERD_STATS_RULES_HOUR');";
	}
	
	
	
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table><script>".@implode("\n",$js)."</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function GRAPH_FAMILY(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$PieData=unserialize($sock->GET_INFO($_GET["container"]));
	VERBOSE("{$_GET["container"]} -> ".count($PieData)." items", __LINE__);
	
	

	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_domains}/{block}");
	echo $highcharts->BuildChart();
}
function GRAPH_CATEGORY(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$PieData=unserialize($sock->GET_INFO($_GET["container"]));
	VERBOSE("{$_GET["container"]} -> ".count($PieData)." items", __LINE__);
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{block}");
	echo $highcharts->BuildChart();
}

function GRAPH_IPADDR(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$PieData=unserialize($sock->GET_INFO($_GET["container"]));
	VERBOSE("{$_GET["container"]} -> ".count($PieData)." items", __LINE__);
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members}/{block}");
	echo $highcharts->BuildChart();
	}
function GRAPH_RULES(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$PieData=unserialize($sock->GET_INFO($_GET["container"]));
	VERBOSE("{$_GET["container"]} -> ".count($PieData)." items", __LINE__);
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_rules}");
	echo $highcharts->BuildChart();
}
function COURBE(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$MAIN=unserialize($sock->GET_INFO($_GET["container"]));
	$title="{blocked_requests}";
	$timetext="{time}";
	
	if(isset($_GET["unit"])){$timetext="{{$_GET["unit"]}}";}
	
	if($GLOBALS["VERBOSE"]){
		print_r($MAIN);
	}
	
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle=" users";
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text($timetext.': ');
	$highcharts->LegendSuffix=" {hits}";
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("| {requests}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();

}