<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');


	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["wwwBrowse-search"])){search();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{browse}::{websites}");
	$html="
	$('wwwBrowse-search-list').remove();
	YahooWin4('550','$page?popup=yes&field={$_GET["field"]}&callback={$_GET["callback"]}&day={$_GET["day"]}&user-field={$_GET["user-field"]}&user={$_GET["user"]}','$title');";
	echo $html;
	}
	
	//http://flexigrid.info/
	
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();

	if($_GET["callback"]<>null){$callback="{$_GET["callback"]}()";}
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{search}:</td>
		<td>". Field_text("wwwBrowse-search",null,"font-size:16px;font-weight:bold;width:80%",null,null,null,false,"wwwBrowseCheck(event)")."</td>
	</tr>
	</table>
	
	<div id='wwwBrowse-search-list' style='width:100%;height:450px;overflow:auto'></div>
	
	<script>
		function wwwBrowseCheck(e){
			if(!checkEnter(e)){return;}
			var se=escape(document.getElementById('wwwBrowse-search').value);
			LoadAjax('wwwBrowse-search-list','$page?wwwBrowse-search=yes&field={$_GET["field"]}&callback={$_GET["callback"]}&search='+se);
		}
		
		function BrowseWWWSelect(www){
			document.getElementById('{$_GET["field"]}').value=www;
			YahooWin4Hide();
			$callback;
		}		
		
	var se=escape(document.getElementById('wwwBrowse-search').value);
	LoadAjax('wwwBrowse-search-list','$page?wwwBrowse-search=yes&field={$_GET["field"]}&callback={$_GET["callback"]}&search='+se);
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function search(){
	$search=$_GET["search"];
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$search=str_replace("*", "%", $search);
	if(CACHE_SESSION_GET(__FILE__.__FUNCTION__.$search,__FILE__,2)){return;}
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
		
	$table="visited_sites";
	
	if($q->COUNT_ROWS($table)==0){echo "<H2>".$tpl->_ENGINE_parse_body("TABLE:$table<br>{error_no_datas}")."</H2>";return;}
	
	$sql="SELECT familysite,SUM(HitsNumber) as HitsNumber FROM `$table` 
	GROUP BY familysite HAVING (`familysite` LIKE '$search')
	ORDER BY HitsNumber DESC LIMIT 0,100";
	
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		
		<th width=50% nowrap colspan=2>{websites}</th>
		<th width=50% nowrap>{hits}</th>
		<th style='width:1%' nowrap>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		
		$js="BrowseWWWSelect('{$ligne["familysite"]}');";
		
		$siteTool=imgtootltip("website-add-32.png","{select}:{$ligne["familysite"]}",$js);
		$sitname=texttooltip($ligne["familysite"],"{select}",$js,null,0,"font-size:14px;text-decoration:underline");
		
		
		
		
		$html=$html."
		<tr class=$classtr>
		<td width=1% style='font-size:14px'>$siteTool</td>
		<td style='font-size:14px' width=99%>$sitname</td>
		<td style='font-size:14px' width=1%>{$ligne["HitsNumber"]}</td>
		</tr>	
		
		";
	}	
	
	$html=$html."</tbody></table>
";
	CACHE_SESSION_SET(__FILE__.__FUNCTION__.$search, __FILE__,$tpl->_ENGINE_parse_body($html));
	
}