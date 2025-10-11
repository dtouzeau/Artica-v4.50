<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["courbe"])){courbe();exit;}
if(isset($_GET["camembert"])){camembert();exit;}
if(isset($_GET["bandwidth-day"])){day_bandwidth();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber<2){$ProxyGraphsTOPNumber=10;}
	
	$html[]="<table style='width:100%'>";
	$q=new postgres_sql();
	$now=date("Y-m-d 00:00:00",strtotime('monday this week'));
	$ToChar="to_char(zdate, 'DD')";
	
	$sql="SELECT $ToChar as thour,SUM(SIZE) as size FROM proxy_traffic WHERE zdate>'$now' GROUP BY thour ORDER BY thour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array();
	while($ligne=pg_fetch_array($results)){
		
		$size=$ligne["size"]/1024;
		$size=$size/1024;
		$size=$size/1024;
		$MAIN["xdata"][]=$ligne["thour"];
		$MAIN["ydata"][]=$size;
	}
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($MAIN["xdata"])." elements -->";
	if(count($MAIN["xdata"])>1){
		$title=urlencode("{bandwidth}/{this_week}");
		$rowtitle=urlencode("| {bandwidth}");
		$data=serialize($MAIN);
		$timetext=urlencode("{day}");
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/week.bandwidth.data", $data);
		$html[]="<tr><td valign='top' colspan=2><div id='week-bandwidth' style='with:1450;height:350px'></div></td></tr>";
		$js[]="Loadjs('$page?courbe=yes&timetext=$timetext&container=week-bandwidth&title=$title&data-file=week.bandwidth.data&legend=GB&row-title=$rowtitle');";
	}
	
	$sql="SELECT $ToChar as thour,SUM(rqs) as size FROM proxy_traffic WHERE zdate>'$now' GROUP BY thour ORDER BY thour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	
	
	$MAIN=array();
	while($ligne=pg_fetch_array($results)){
		$MAIN["xdata"][]=$ligne["thour"];
		$MAIN["ydata"][]=$ligne["size"];
	}

	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($MAIN["xdata"])." elements -->";
	if(count($MAIN["xdata"])>1){
		$data=serialize($MAIN);
		$timetext=urlencode("{day}");
		$title=urlencode("{requests}/{this_week}");
		$rowtitle=urlencode("| {requests}");
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/week.requests.data", $data);
		$html[]="<tr><td valign='top' colspan=2><div id='week-requests' style='with:1450;height:350px'></div></td></tr>";
		$js[]="Loadjs('$page?courbe=yes&timetext=$timetext&container=week-requests&title=$title&data-file=week.requests.data&legend=rqs&row-title=$rowtitle');";
	}
	
	//-------------------------------------------------------------------------------------------------------------
	
	$sql="SELECT familysite,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY familysite ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array();
	$PieData=array();
	$table=array();
	
	$table[]="<table class='table table-striped'>";
	$table[]="<thead>";
	$table[]="<tr>";
	$table[]="<th>{familysite}</th>";
	$table[]="<th>{requests}</th>";
	$table[]="<th>{size}</th>";
	$table[]="</tr>";
	$table[]="</thead>";
	while($ligne=pg_fetch_array($results)){
		$familysite=$ligne["familysite"];
		$size=$ligne["size"]/1024;
		$rqs=FormatNumber($ligne["rqs"]);
		$PieData[$familysite]=$size;
		
		$table[]="<tr>";
		$table[]="<td nowrap>$familysite</td>";
		$table[]="<td width=1% nowrap>$rqs</td>";
		$table[]="<td width=1% nowrap>". FormatBytes($size)."</td>";
		$table[]="</tr>";
		
		
	}
	$table[]="</tr>";
	$table[]="</table>";
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($PieData)." elements -->";
	
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/week.familysite.data", $data);
		$html[]="<tr>
				<td valign='top'><div id='week-familysite' style='with:600px;height:500px'></div></td>
				<td valign='top' style='width:1%' nowrap>".@implode("\n", $table)."</td>
				</tr>";
		$js[]="Loadjs('$page?camembert=yes&container=week-familysite&data-file=week.familysite.data&title=top_visited_websites');";
	}
//---------------------------------------------------------------------------------------------------------------------------------
	//-------------------------------------------------------------------------------------------------------------
	$table=array();
	$sql="SELECT category,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY category ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array();
	$MAIN=array();
	$PieData=array();
	$table=array();
	
	$table[]="<table class='table table-striped'>";
	$table[]="<thead>";
	$table[]="<tr>";
	$table[]="<th>{category}</th>";
	$table[]="<th>{requests}</th>";
	$table[]="<th>{size}</th>";
	$table[]="</tr>";
	$table[]="</thead>";
	while($ligne=pg_fetch_array($results)){
		$familysite=$tpl->CategoryidToName($ligne["category"]);
		$size=$ligne["size"]/1024;
		$rqs=FormatNumber($ligne["rqs"]);
		$PieData[$familysite]=$size;
	
		$table[]="<tr>";
		$table[]="<td nowrap>$familysite</td>";
		$table[]="<td width=1% nowrap>$rqs</td>";
		$table[]="<td width=1% nowrap>". FormatBytes($size)."</td>";
		$table[]="</tr>";
	
	
	}
	$table[]="</tr>";
	$table[]="</table>";
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($PieData)." elements -->";
	
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/hour.category.data", $data);
		$html[]="<tr>
				<td valign='top'><div id='hour-category' style='with:600px;height:500px'></div></td>
				<td valign='top' style='width:1%' nowrap>".@implode("\n", $table)."</td>
				</tr>";
		$js[]="Loadjs('$page?camembert=yes&container=hour-category&data-file=hour.category.data&title=top_visited_categories');";
	}
	//---------------------------------------------------------------------------------------------------------------------------------
	
	//-------------------------------------------------------------------------------------------------------------
	$table=array();$PieData=array();
	$sql="SELECT member,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY member ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array();
	$MAIN=array();
	$PieData=array();
	$table=array();
	
	$table[]="<table class='table table-striped'>";
	$table[]="<thead>";
	$table[]="<tr>";
	$table[]="<th>{members}</th>";
	$table[]="<th>{requests}</th>";
	$table[]="<th>{size}</th>";
	$table[]="</tr>";
	$table[]="</thead>";
	while($ligne=pg_fetch_array($results)){
		$familysite=$ligne["member"];
		$size=$ligne["size"]/1024;
		$rqs=FormatNumber($ligne["rqs"]);
		$PieData[$familysite]=$size;
	
		$table[]="<tr>";
		$table[]="<td nowrap>$familysite</td>";
		$table[]="<td width=1% nowrap>$rqs</td>";
		$table[]="<td width=1% nowrap>". FormatBytes($size)."</td>";
		$table[]="</tr>";
	
	
	}
	$table[]="</tr>";
	$table[]="</table>";
	
	$html[]="<!-- ".__LINE__.": $sql  -->";
	$html[]="<!-- ".__LINE__.": From $now, ".count($PieData)." elements -->";
	
	if(count($PieData)>1){
		$data=serialize($PieData);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/week.member.data", $data);
		$html[]="<tr>
				<td valign='top'><div id='week-member' style='with:600px;height:500px'></div></td>
				<td valign='top' style='width:1%' nowrap>".@implode("\n", $table)."</td>
				</tr>";
		$js[]="Loadjs('$page?camembert=yes&container=week-member&data-file=week.member.data&title=top_members');";
	}
	//---------------------------------------------------------------------------------------------------------------------------------
		
	
	
	$html[]="</table>";
	$html[]="<script>".@implode("\n", $js)."</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
			
	
	
}


function courbe(){
	$MAIN=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/{$_GET["data-file"]}"));
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$_GET["title"];
	$timetext=$_GET["timetext"];
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$title;
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle=$_GET["legend"];
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text("{$timetext}: ");
	$highcharts->LegendSuffix=$_GET["legend"];
	$highcharts->xAxisTtitle=$timetext;
	$highcharts->datas=array("{$_GET["row-title"]}"=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();
	
}

function camembert(){
	$tpl=new templates();
	$page=CurrentPageName();
	$PieData=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/{$_GET["data-file"]}"));
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{{$_GET["title"]}}");
	echo $highcharts->BuildChart();
	
	
}






function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}