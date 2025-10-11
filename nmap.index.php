<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.nmap.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');

	
	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){header('location:users.index.php');exit();}
	
	if(isset($_POST["NmapScanEnabled"])){main_settings_edit();exit;}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_GET["events"])){events();exit;}
	if(isset($_GET["NMAP_EVENTS"])){NMAP_EVENTS();exit;}
	if(isset($_POST["DeleteAllNMAPEV"])){events_delete_all();exit;}
	
	
	if(isset($_GET["ScanNow"])){main_scan();exit;}

	js();
		
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_NMAP}");
	$html="YahooWin3('1050','$page?tabs=yes','$title')";
	echo $html;
}

function tabs(){
	$page=CurrentPageName();
	$array["parameters"]='{parameters}';
	$array["events"]='{events}';
	
	
	
	$tpl=new templates();
	$fontsize="style='font-size:22px'";
	foreach ($array as $num=>$ligne){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID={$_GET["ID"]}\"><span $fontsize>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_nmap");
	
}

function NMAP_EVENTS(){
	
	// $findcomputer="{name: '$scan_your_network', bclass: 'ScanNet', onpress : ScanNet},";
	
	
	$ID=$_GET["NMAP_EVENTS"];
	if(!is_numeric($ID)){echo "NOT A NUMERIC";die("DIE " .__FILE__." Line: ".__LINE__);exit;}
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();

	$sql="SELECT * FROM nmap_events WHERE ID='$ID'";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_events'));
	$ligne["text"]=htmlspecialchars($ligne["text"]);
	$ligne["text"]=str_replace("\n", "<br>", $ligne["text"]);
	$html="<div style='font-size:16px'>{$ligne["subject"]}</div><div style='font-size:13px;height:550px;overflow:auto' class=form>{$ligne["text"]}</div>";
	echo $html;
	
	
}

function events(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th width=1%>". imgtootltip("refresh-24.png","{refresh}","RefreshTab('main_config_nmap')")."</th>
	<th width=1%>{date}</th>
	<th width=1%>&nbsp;</th>
	<th width=99%>events</th>
	<th width=1%>". imgtootltip("delete-32.png","{delete_all}","DeleteAllNMAPEV()")."</th>
	</tr>
</thead>
<tbody class='tbody'>";	

	$sql="SELECT subject,zDate,ID,uid FROM nmap_events ORDER BY zDate DESC LIMIT 0,300";
	$results=$q->QUERY_SQL($sql,"artica_events");
		
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$view="&nbsp;";
		if($ligne["uid"]<>null){$view=imgtootltip("computer-32.png","{view}",MEMBER_JS($ligne["uid"],1,1));}
		$color="black";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:NMAP_EVENTS({$ligne["ID"]})\" style='font-size:12px;text-decoration:underline'>";
		$html=$html."
		<tr class=$classtr>
			<td style='font-size:12px;font-weight:bold;color:$color'style='width:1%;' nowrap colspan=2>{$ligne["zDate"]}</a></td>
			<td style='font-size:12px;font-weight:bold;color:$color' width=1% nowrap>$view</a></td>
			<td style='font-size:12px;font-weight:bold;color:$color' width=99% nowrap colspan=2>$link{$ligne["subject"]}</a></td>
		</tr>
		";
	}

	$html=$html."
	</tbody>
	</table>
	
	<script>
		function NMAP_EVENTS(ID){
			YahooWin4('650','$page?NMAP_EVENTS='+ID,ID);
		}
		
	var x_DeleteAllNMAPEV= function (obj) {
		var results=obj.responseText;
		RefreshTab('main_config_nmap');
	}
	
	function DeleteAllNMAPEV(){
		var XHR = new XHRConnection();
		XHR.appendData('DeleteAllNMAPEV','yes');
		XHR.sendAndLoad('$page', 'POST',x_DeleteAllNMAPEV);
	}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
	

function parameters(){
	
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	
	$NmapRotateMinutes=$sock->GET_INFO("NmapRotateMinutes");
	$NmapScanEnabled=$sock->GET_INFO("NmapScanEnabled");
	
	$NmapTimeOutPing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NmapTimeOutPing"));
	if($NmapTimeOutPing==0){$NmapTimeOutPing=30;}
	$NmapFastScan=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NmapFastScan"));
	
	if(!is_numeric($NmapScanEnabled)){$NmapScanEnabled=1;}
	if(!is_numeric($NmapRotateMinutes)){$NmapRotateMinutes=60;}
	if($NmapRotateMinutes<5){$NmapRotateMinutes=5;}
	
	
	
	$findcomputer=Paragraphe("64-samba-find.png","{scan_your_network}",'{scan_your_network_text}',"javascript:Loadjs('computer-browse.php?scan-nets-js=yes')","scan_your_network",210);
	
	$html="
	
	<div id='nmapidset' style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td colspan=2>
			". Paragraphe_switch_img("{NmapScanEnabled}", "{about_nmap}",
					"NmapScanEnabled",$NmapScanEnabled,null,1390)."		
		</td>

	<tr>
		<td class=legend style='font-size:26px'>{NmapRotateMinutes}:</td>
		<td valign='top' nowrap style='font-size:26px'>" . Field_text('NmapRotateMinutes',
				$NmapRotateMinutes,'font-size:26px;width:90px')."&nbsp;{minutes}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{timeout_ping_networks}:</td>
		<td valign='top' nowrap style='font-size:26px'>" . Field_text('NmapTimeOutPing',$NmapTimeOutPing,
				'font-size:26px;width:90px')."&nbsp;{seconds}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{fast_scan}:</td>
		<td valign='middle' nowrap style='font-size:1px'>" . Field_checkbox_design('NmapFastScan',1,$NmapFastScan)."&nbsp;</td>
	</tr>									
	<tr>
	<td colspan=2 align='right'><hr>". button("{apply}","SaveNMAPSettings()",46)."</td>
	

	</tr>
	</tbody>
	</table>
	</div>
	
<script>
	var x_AddComputerToDansGuardian= function (obj) {
		var mid='ip_group_rule_list_'+rule_mem;
		LoadAjax(mid,'dansguardian.index.php?ip-group_list-rule='+rule_mem);
	}

	var x_SaveNMAPSettings= function (obj) {
		var results=obj.responseText;
		RefreshTab('main_config_nmap');
	}
	
	function SaveNMAPSettings(){
		var XHR = new XHRConnection();
		XHR.appendData('NmapRotateMinutes',document.getElementById('NmapRotateMinutes').value);
		XHR.appendData('NmapScanEnabled',document.getElementById('NmapScanEnabled').value);
		XHR.appendData('NmapTimeOutPing',document.getElementById('NmapTimeOutPing').value);
		if(document.getElementById('NmapFastScan').checked){
			XHR.appendData('NmapFastScan',1);
		}else{
			XHR.appendData('NmapFastScan',0);
			}
		
		XHR.sendAndLoad('$page', 'POST',x_SaveNMAPSettings);
	}
</script>	
	";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
}

function main_settings_edit(){
	$sock=new sockets();
	$sock->SET_INFO("NmapRotateMinutes", $_POST["NmapRotateMinutes"]);
	$sock->SET_INFO("NmapScanEnabled", $_POST["NmapScanEnabled"]);
	$sock->SET_INFO("NmapTimeOutPing", $_POST["NmapTimeOutPing"]);
	$sock->SET_INFO("NmapFastScan", $_POST["NmapFastScan"]);
	
	
}




function main_events(){
	
	
	$html="<H3>{nmap_logs}</H3><div id='nmap_events'>".main_events_fill()."</div>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}
function main_events_fill(){
	$datas=@explode("\n",file_get_contents(@dirname(__FILE__).'/ressources/logs/nmap.log'));
	if(is_array($datas)){
		
		$datas=array_reverse($datas);	
		foreach ($datas as $num=>$ligne){
			if(trim($ligne)<>null){
				$ev[]=$ligne;
			}
		
		}
	}
	
	return "<textarea style='border:0px;font-size:10px;width:100%' rows=50>".@implode("\n",$ev)."</textarea>";
	
}

function events_delete_all(){
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE nmap_events","artica_events");
	
}

function main_scan(){
	$sock=new sockets();
	$sock->getfile('NmapScanNow');
	}

?>