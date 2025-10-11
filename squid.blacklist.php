<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}
if(isset($_GET["status"])){page();exit;}
if(isset($_GET["tools"])){tools();exit;}
if(isset($_GET["blkupdates-status"])){blacklist_status();exit;}
if(isset($_GET["category-details"])){categories_details();exit;}
if(isset($_GET["category-details-md"])){categories_details_list();exit;}
if(isset($_GET["category-details-md-title"])){categories_details_title();exit;}
if(isset($_GET["pattern-database-title"])){blacklist_title();exit;}
if(isset($_GET["events"])){blacklist_events();exit;}



if(isset($_POST["reprocess"])){reprocess();exit;}
if(isset($_POST["update-now"])){update_now();exit;}


tabs();


function tools(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$update=Paragraphe("folder-update.png", "{update_now}", "{blklist_update_now_text}","javascript:UpdateBLKNOW()");
	$tr[]=$update;


	
	$tables[]="<table style='width:100%'><tr>";
	$t=0;
	while (list ($key, $line) = each ($tr) ){
			$line=trim($line);
			if($line==null){continue;}
			$t=$t+1;
			$tables[]="<td valign='top'>$line</td>";
			if($t==2){$t=0;$tables[]="</tr><tr>";}
			}
	
	if($t<2){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td valign='top'>&nbsp;</td>";				
		}
	}	
	
	$tables[]="</table>";	
	
	
	$scripts="
	<div id='animateBlcklist'></div>
	". @implode("\n", $tables)."
	<script>
		var x_UpdateBLKNOW= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		 RefreshTab('squid_main_blacklists');
		}	
	
		function UpdateBLKNOW(){
				var XHR = new XHRConnection();
				XHR.appendData('update-now','yes');
				AnimateDiv('animateBlcklist');
				XHR.sendAndLoad('$page', 'POST',x_UpdateBLKNOW);		
			
			}
		
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($scripts);
	
	
	
}




function tabs(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$array["status"]='{status}';
	$array["tools"]='{tools}';
	$array["events"]='{events}';
	
	
	foreach ($array as $num=>$ligne){
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
		//$html=$html . "<li><a href=\"javascript:LoadAjax('squid_main_config','$page?main=$num&hostname={$_GET["hostname"]}')\" $class>$ligne</a></li>\n";
			
		}
	echo build_artica_tabs($html, "squid_main_blacklists");
		
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){ 
	$tmp1 = round((float) $number, $decimals);
  while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
    $tmp1 = $tmp2;
  return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
} 

function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	
	
	
	$html="<div class=explain>{squid_blk_explain}</div>
	<div style='font-size:16px;margin:10px' id='pattern-database-title'></div>
	
	
	<div id='blkupdates'></div>
	
	
	<script>
		function UpdateBlckStatus(){
			LoadAjax('blkupdates','$page?blkupdates-status=yes');
		
		}
		
		function refreshDatabasesTitle(){
			LoadAjax('pattern-database-title','$page?pattern-database-title=yes');
		}
	
	UpdateBlckStatus();
	refreshDatabasesTitle();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}
function GetLastUpdateDate(){
	$q=new mysql();
	$sql="SELECT zDate FROM updates_categories WHERE categories='settings'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	return $ligne["zDate"];
}

function blacklist_title(){
	$q=new mysql_squid_builder();
	$date=GetLastUpdateDate();
	$rows=$q->COUNT_ROWS("dansguardian_community_categories", "artica_backup");
	$rows=FormatNumber($rows);	
	$sql="SELECT avg(progress) as pourcent FROM updates_categories  WHERE filesize>0";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!is_numeric($ligne["pourcent"])){$ligne["pourcent"]=0;}
	$pourcent=round($ligne["pourcent"],2);
	$purc=pourcentage($pourcent);
	$html="<div style='float:right'>".imgtootltip("refresh-24.png","{refresh}","refreshDatabasesTitle()")."</div>
	<table><tbody>
	<tr>
	<td width=100% style='font-size:16px'>
	{pattern_database_version}:&nbsp;$date&nbsp;|&nbsp;$rows&nbsp;{rows}
	</td>
	<td width=1%>$purc</td>
	</tr>
	</tbody>
	</table>
	";
	$tpl=new templates();
	$page=CurrentPageName();	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function blacklist_status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sql="SELECT avg(progress) as pourcent, categories FROM updates_categories  WHERE filesize>0 GROUP BY categories ORDER BY pourcent";
	$confirm_reprosess_category=$tpl->javascript_parse_text("{confirm_reprosess_category}");
	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo "<H2>Fatal error $sql</H2>";
	}
	$num=mysqli_num_rows($results);
	
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("refresh-24.png","{refresh}","UpdateBlckStatus()")."</th>
		<th>{category}</th>
		<th>{status}</th>
		<th>&nbsp;</th>
		
	</tr>
</thead>
<tbody class='tbody'>";	
	

	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$color="black";
		if(trim($ligne["categories"])==null){continue;}
		$js="YahooWin3('680','$page?category-details=".urlencode($ligne["categories"])."','{$ligne["categories"]}')";
		
		$purc=pourcentage(round($ligne["pourcent"]));
		$color="black";
		$rebuild="&nbsp;";
		if(($ligne["pourcent"]>20) && $ligne["pourcent"]<100){
			$rebuild=imgtootltip("refresh-32.png","{reprocess}","ProcessCategory('{$ligne["categories"]}')");
		}
		
		$html=$html."
		
		<tr class=$classtr>
			<td width=1%><img src=img/Database32.png></td>
			<td style='font-size:14px;font-weight:bold;color:$color'>
		
			<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>
			{$ligne["categories"]}</a></td>
			<td style='font-size:14px;font-weight:bold;color:$color'>$purc</td>
			<td style='font-size:14px;font-weight:bold;color:$color' width=1%>$rebuild</td>
		</tr>";	
		
		
		
	}
	
	
	$html=$html."</table>
	
	<script>
	var x_ProcessCategory= function (obj) {
		var results=obj.responseText;
		if(results.length>3){
			alert(results);
			}
		 UpdateBlckStatus();
		}	
	
	
		function ProcessCategory(cat){
			if(confirm('$confirm_reprosess_category')){
				var XHR = new XHRConnection();
				XHR.appendData('reprocess','yes');
				XHR.appendData('category',cat);
				AnimateDiv('blkupdates');
				XHR.sendAndLoad('$page', 'POST',x_ProcessCategory);		
			
			}
		
		}
	</script>
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	

}

function categories_details(){
	$tpl=new templates();
	$page=CurrentPageName();
	$date=GetLastUpdateDate();
	$category=$_GET["category-details"];
	$categorys=urlencode($category);
	$md=md5("$category");
	$html="
	
	
	<div id='categories-details-title-$md' style='font-size:16px;margin:10px'></div>
	<div id='categories-details-$md'></div>
	
	
	<script>
		function RefreshCategoryDetails(){
		LoadAjax('categories-details-$md','$page?category-details-md=$categorys');
		
		}
		
		function RefreshCategoryDetailsTitle(){
		if(!document.getElementById('total-cat')){
			LoadAjax('categories-details-title-$md','$page?category-details-md-title=$categorys');
			}
		
		}		
	
	RefreshCategoryDetails();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}

function categories_details_title(){
	$tpl=new templates();
	$page=CurrentPageName();
	$f=unserialize(@file_get_contents("ressources/squid.categories.count.cache"));	
	$rows=FormatNumber($f[$_GET["category-details-md-title"]]);
	$html="{$_GET["category-details-md-title"]}&nbsp;$rows&nbsp;{rows}";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function categories_details_list(){
$tpl=new templates();
	$page=CurrentPageName();
	$sql="SELECT * FROM updates_categories  WHERE filesize>0 AND categories='{$_GET["category-details-md"]}' ORDER BY progress";

	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo "<H2>Fatal error $sql</H2>";
	}
	$num=mysqli_num_rows($results);
	
	$html="<center style='width:100%;height:450px;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("refresh-24.png","{refresh}","RefreshCategoryDetails()")."</th>
		<th>{database}</th>
		<th>{filesize}</th>
		<th colspan=2>{status}</th>
		
	</tr>
</thead>
<tbody class='tbody'>";	
	

	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}	
		$color="black";
		$filesize=FormatBytes($ligne["filesize"]/1024);
		$purc=pourcentage(round($ligne["progress"]));
		
		$html=$html."
		<tr class=$classtr>
			<td width=1%><img src=img/Database32.png></td>
			<td style='font-size:14px;font-weight:bold;color:$color'>{$ligne["filename"]}</td>
			<td style='font-size:14px;font-weight:bold;color:$color'>$filesize</td>
			<td style='font-size:14px;font-weight:bold;color:$color'>$purc</td>
			<td style='font-size:14px;font-weight:bold;color:$color'>{$ligne["subject"]}</td>	
		</tr>";	
	}
	$html=$html."</table>
	
	<script>
		RefreshCategoryDetailsTitle();
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);		
			
}

function reprocess(){
	$category=urlencode($_POST["category"]);
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reprocess-database=$category");
	
}

function update_now(){
$sock=new sockets();
	$sock->getFrameWork("squid.php?update-database-blacklist=yes");	
}

function blacklist_events(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$html="<center style='width:100%;height:450px;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("refresh-24.png","{refresh}","RefreshTab('squid_main_blacklists')")."</th>
		<th>{date}</th>
		<th>{events}</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	$sql="SELECT ID,zDate,`text` FROM events WHERE `text` LIKE '%BlacklistsDB%' ORDER BY zDate DESC LIMIT 0,100";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		echo "<H2>Fatal error $sql $q->mysql_error</H2>";
	}
	$num=mysqli_num_rows($results);	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}	
		$color="black";
		if(preg_match("#BlacklistsDB\]\s+(.+)#", $ligne["text"],$re)){$ligne["text"]=$re[1];}
		$time=strtotime($ligne["zDate"]);
		
		$distanceOfTimeInWord=distanceOfTimeInWords($time,time());
		$html=$html."
		<tr class=$classtr>
			<td width=1% colspan=2 nowrap style='font-size:14px;font-weight:bold;color:$color'>	<a href=\"javascript:blur();\" OnClick=\"javascript:articaShowEvent({$ligne["ID"]});\"
				style='text-decoration:underline'>{$ligne["zDate"]}</a></td>
			<td style='font-size:13px;font-normal;color:$color'>
				<a href=\"javascript:blur();\" OnClick=\"javascript:articaShowEvent({$ligne["ID"]});\">{$ligne["text"]}</A><div><i style='font-size:11px'>$distanceOfTimeInWord</i></div></td>	
		</tr>";	
	}
	$html=$html."</table>
	<script>
	function articaShowEvent(ID){
		 YahooWin6('750','artica.events.php?ShowID='+ID,'EV::'+ID);
	}

	</script>
	
	
	";

echo $tpl->_ENGINE_parse_body($html);	
	
	
}

