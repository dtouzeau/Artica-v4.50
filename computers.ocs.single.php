<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.artica.inc');
include_once ('ressources/class.pure-ftpd.inc');
include_once ('ressources/class.user.inc');
include_once ('ressources/charts.php');
include_once ('ressources/class.mimedefang.inc');
include_once ('ressources/class.computers.inc');
include_once ('ressources/class.ini.inc');
include_once ('ressources/class.ocs.inc');
include_once (dirname ( __FILE__ ) . "/ressources/class.cyrus.inc");

if ((!isset ($_GET["uid"] )) && (isset($_POST["uid"]))){$_GET["uid"]=$_POST["uid"];}
if ((isset ($_GET["uid"] )) && (! isset ($_GET["userid"] ))) {$_GET["userid"] = $_GET["uid"];}

//permissions	
$usersprivs = new usersMenus ( );

if(!$usersprivs->AsInventoryAdmin){die("No privileges");}
if(isset($_GET["hardware"])){hardware();exit;}
if(isset($_GET["softwares"])){softwares();exit;}
if(isset($_GET["ocs-soft-search"])){softwares_search();exit;}
tabs();
	
function tabs(){
	
	$array["hardware"]="{hardware}";
	$array["softwares"]="{softwares}";
	$page=CurrentPageName();
	$tpl=new templates();
$newinterface="style='font-size:14px'";
	foreach ($array as $num=>$ligne){
		if($num=="howto"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"ocs.ng.howto.php\"><span $newinterface>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&uid={$_GET["uid"]}\"><span $newinterface>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_ocsclient style='width:99.5%;height:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_ocsclient').tabs();
			
			
			});
		</script>";
}

function hardware(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmp = new computers ($_GET["uid"]);
	$ocs = new ocs ($cmp->ComputerMacAddress );
	$ocs->LoadParams();
	$infos=$ocs->BuildFirstInfos();
	$html="<div style='font-size:16px'>N.$ocs->HARDWARE_ID)&nbsp;$ocs->ComputerName&nbsp;|&nbsp;$ocs->ComputerOS</div>
	$infos";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function softwares(){

	$page=CurrentPageName();
	$tpl=new templates();	
	$_GET["uid"]=urlencode($_GET["uid"]);
	$html="
	<center>
	<table style='width:65%' class=form>
	<tbody>
		<tr><td class=legend>{softwares}</td>
		<td>". Field_text("ocs-soft-search",null,"font-size:16px;width:220px",null,null,null,false,"OCSSoftSearchCheck(event)")."</td>
		<td width=1%>". button("{search}","GroupsDansSearch()")."</td>
		</tr>
	</tbody>
	</table>
	</center>
	
	<div id='ocssoft-groups-list' style='width:100%;height:350px;overlow:auto'></div>
	
	<script>
		function OCSSoftSearchCheck(e){
			if(checkEnter(e)){OCSSoftSearch();}
		}
		
		function OCSSoftSearch(){
			var se=escape(document.getElementById('ocs-soft-search').value);
			LoadAjax('ocssoft-groups-list','$page?ocs-soft-search=yes&search='+se+'&uid={$_GET["uid"]}');
		
		}
		
		OCSSoftSearch();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function softwares_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmp = new computers ($_GET["uid"]);
	$ocs = new ocs ($cmp->ComputerMacAddress );
	$ocs->LoadParams();	
	$q=new mysql();	
	$search=$_GET["search"];
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$search=str_replace("*", "%", $search);
	$group_text=$tpl->_ENGINE_parse_body("{group}");
	$sql="SELECT * FROM softwares WHERE HARDWARE_ID=$ocs->HARDWARE_ID AND ((PUBLISHER LIKE '$search') OR (NAME LIKE '$search') OR (COMMENTS LIKE '$search')) ORDER BY NAME LIMIT 0,50";
	
	
	$results=$q->QUERY_SQL($sql,"ocsweb");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><code style='font-size:11px'>$sql</code>";}
	
	
	
	
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<thstyle='width:1%;' nowrap colspan=2>{PUBLISHER}</th>
		<th width=99%>{software}</th>
		<th width=1% align='center'>{version}</th>
	</tr>
</thead>
<tbody class='tbody'>";
	
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(trim($ligne["PUBLISHER"])==null){$ligne["PUBLISHER"]="&nbsp;";}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$color="black";
		$html=$html."
		<tr class=$classtr>
			<td width=1%><img src='img/software-task-32.png'></td>
			<td style='font-size:14px;font-weight:normal;color:$color' nowrap>{$ligne["PUBLISHER"]}</td>
			<td style='font-size:14px;font-weight:normal;color:$color'>{$ligne["NAME"]}<div style='font-size:10px'><i>{$ligne["COMMENTS"]}</i></td>
			<td style='font-size:14px;font-weight:normal;color:$color' align='center'>{$ligne["VERSION"]}</td>
		</tr>
		";
	}
	
	$html=$html."</table>
	</center>
	";	
	echo $tpl->_ENGINE_parse_body($html);
}


