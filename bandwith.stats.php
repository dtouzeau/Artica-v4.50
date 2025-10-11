<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.rtmm.tools.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		exit;
		
	}	
	if(isset($_GET["popup"])){popup();exit;}
	

	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{bandwith}");
	
	$htm="YahooWin2(750,'$page?popup=yes','$title')";
	
	echo $htm;
	
	
}

function popup(){
	$tpl=new templates();
	$time=time();
	
	
	echo "<div style='width:98%' class=form>";
	$sql="SELECT DATE_FORMAT(zDate,'%H') as tdate,AVG(download) as tbandwith FROM speedtests 
	WHERE DATE_FORMAT(zDate,'%Y-%m-%d')=DATE_FORMAT(NOW(),'%Y-%m-%d') 
	GROUP BY DATE_FORMAT(zDate,'%H')
	ORDER BY zDate";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo ("<H2>$q->mysql_error</H2><code>$sql</code>");return;}
	$fileName="ressources/logs/web/bandwith-day.png";
	$g=new artica_graphs($fileName,10);
	if(mysqli_num_rows($results)>1){
				while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){	
						$g->ydata[]=round($ligne["tbandwith"],0);
						$g->xdata[]=$ligne["tdate"];
				}	
				
			
			$g->title=$tpl->_ENGINE_parse_body("{today}: {bandwith} KBi/s");
			$g->x_title="hours";
			$g->y_title=null;
			$g->width=650;
			$g->line_green();
			@chmod($fileName,0777);	
			echo "<center style='margin:5px'><img src='ressources/logs/web/bandwith-day.png?$time'</center>";	
	}

$sql="SELECT YEARWEEK(zDate) as tweek,AVG(download) as tbandwith,DAYOFMONTH(zDate) as tdate 
FROM speedtests WHERE YEARWEEK(zDate)=YEARWEEK(NOW()) GROUP BY DAYOFMONTH(zDate) ORDER BY DAYOFMONTH(zDate) ";

	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo ("<H2>$q->mysql_error</H2><code>$sql</code>");return;}
	$fileName="ressources/logs/web/bandwith-week.png";
	$g=new artica_graphs($fileName,10);
	if(mysqli_num_rows($results)>1){
			while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){	
					$g->ydata[]=round($ligne["tbandwith"],0);
					$g->xdata[]=$ligne["tdate"];
			}	
			
		
		$g->title=$tpl->_ENGINE_parse_body("{this_week}: {bandwith} KBi/s");
		$g->x_title="day";
		$g->y_title=null;
		$g->width=650;
		$g->line_green();
		@chmod($fileName,0777);
		echo "<center style='margin:5px'><img src='ressources/logs/web/bandwith-week.png?$time'</center>";
	}



echo "</div>";
	
		
}





?>