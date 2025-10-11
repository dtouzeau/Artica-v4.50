<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	if(isset($_POST["NoCategorizedAnalyze"])){NoCategorizedAnalyze();exit;}
	page();
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$t=time();
	$sql="SELECT zDate,not_categorized FROM tables_day ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$xdata[]=$ligne["tdate"];
		$ydata[]=$ligne["not_categorized"];
	}
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".png";
	$gp=new artica_graphs();
	$gp->width=880;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";

	$gp->line_green();
	if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);$targetedfile="img/kas-graph-no-datas.png";}
	
	$sql="SELECT zDate,not_categorized FROM tables_day WHERE not_categorized>0 ORDER BY zDate DESC";
	$results=$q->QUERY_SQL($sql);

	
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	$c=0;
	$table=null;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$c++;
		$table=$table."<tr>
			<td style='font-size:14px' width=99%><a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.visited.php?day={$ligne["zDate"]}&onlyNot=yes');\" style='font-size:14px;text-decoration:underline'>{$ligne["zDate"]}</a></td>
			<td style='font-size:14px' width=1%><strong>{$ligne["not_categorized"]}</strong></td>
		</tr>
		";
		if($c>10){$c=0;$tr[]="<table style='width:20%' class=form><tbody>$table</tbody></table>";$table=null;}
		
	}	
	if($c>0){$tr[]="<table style='width:20%' class=form><tbody>$table</tbody></table>";}
	$t=time();	
	echo $tpl->_ENGINE_parse_body("
	<div id='$t'>
	<div style='font-size:18px'>{not_categorized}/{days}</div>
	
	<center>
	<div style='margin:8px;float-right;width:100%'>". button("{analyze}", "NoCategorizedAnalyze()")."</div>
	<img src='$targetedfile?t=".time()."'>
	</center>
	". CompileTrGen($tr,6)."
	</div>
	</div>
	<script>
		
	var x_NoCategorizedAnalyze= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			if(document.getElementById('squid_stats_consumption')){
	    		RefreshTab('squid_stats_consumption');
			}
			
			if(document.getElementById('squid_stats_central')){
	    		RefreshTab('squid_stats_central');
			}			
		}	

		function NoCategorizedAnalyze(){
			var XHR = new XHRConnection();
			XHR.appendData('NoCategorizedAnalyze','yes');
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_NoCategorizedAnalyze);
		}
	</script>		
	
	");
	
}

function NoCategorizedAnalyze(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?NoCategorizedAnalyze=yes");
	
}