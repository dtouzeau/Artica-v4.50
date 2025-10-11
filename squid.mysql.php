<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}

	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["maintenance"])){maintenance_settings();exit;}
	
	if(isset($_GET["remote-users"])){remote_users();exit;}
	if(isset($_GET["local-users"])){local_users();exit;}
	if(isset($_GET["member-add"])){members_add();exit;}
	if(isset($_GET["member-delete"])){members_delete();exit;}		
	
	if(isset($_GET["tools"])){tools();exit;}
	if(isset($_GET["run-compile"])){task_run_sarg();exit;}
	
	if(isset($_GET["events"])){events();exit;}
	if(isset($_POST["squidMaxTableDays"])){SAVE();exit;}
	if(isset($_GET["squid-mysql-status"])){squid_mysql_status();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$title=$tpl->_ENGINE_parse_body("{ARTICA_DATABASE_MAINTENANCE}");
	$html="YahooWin2('920','$page?popup=yes','$title');";
	
	echo $html;
	
	
	
}

function popup(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$array["maintenance"]="{maintenance}";
	
	if($q->UseStandardMysql){
		$array["MySQLStandEvents"]="{mysql_events}";
	}
	
	foreach ($array as $num=>$ligne){
		
		if($num=="MySQLStandEvents"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"system.mysql.events.php\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		
		$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");

	}
	
	$id=time();
	echo build_artica_tabs($html, "artica_squid_db_tabs");
	
	
	
}	

function maintenance_settings(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	
	
	$requests=$q->COUNT_ROWS("dansguardian_events","artica_events");
	$requests=numberFormat($requests,0,""," ");
	$dansguardian_events="dansguardian_events_".date('Ymd');	
	$sql="SELECT max( ID ) as tid FROM $dansguardian_events";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_events'));
	$sql="SELECT zDate, DATE_FORMAT(zDate,'%M %W %Y %H:%i') as tdate FROM $dansguardian_events WHERE ID ={$ligne["tid"]}";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'squidlogs'));
	
	$lastevents=$ligne["zDate"];
	$lastevents_text=$ligne["tdate"];
	$t2=strtotime($lastevents);
	
	$sql="SELECT min( ID ) as tid FROM $dansguardian_events";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_events'));
	$sql="SELECT zDate,DATE_FORMAT(zDate,'%M %W %Y') as tdate FROM $dansguardian_events WHERE ID ={$ligne["tid"]}";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'squidlogs'));	
	
	$first_events=$ligne["zDate"];
	$first_events_text=$ligne["tdate"];
	$t1=strtotime($first_events);
	
	$distanceOfTimeInWords=distanceOfTimeInWords($t1,$t2);
	
	
	$sock=new sockets();
	$squidMaxTableDays=$sock->GET_INFO("squidMaxTableDays");
	$squidMaxTableDaysBackup=$sock->GET_INFO("squidMaxTableDaysBackup");
	$squidMaxTableDaysBackupPath=$sock->GET_INFO("squidMaxTableDaysBackupPath");
	$squidEnableRemoteStatistics=$sock->GET_INFO("squidEnableRemoteStatistics");
	$squidRemostatisticsServer=$sock->GET_INFO("squidRemostatisticsServer");
	$squidRemostatisticsPort=$sock->GET_INFO("squidRemostatisticsPort");
	$squidRemostatisticsUser=$sock->GET_INFO("squidRemostatisticsUser");
	$squidRemostatisticsPassword=$sock->GET_INFO("squidRemostatisticsPassword");
	
	
	if(!is_numeric($squidMaxTableDays)){$squidMaxTableDays=730;}
	if(!is_numeric($squidMaxTableDaysBackup)){$squidMaxTableDaysBackup=1;}
	if(!is_numeric($squidEnableRemoteStatistics)){$squidEnableRemoteStatistics=0;}
	if(!is_numeric($squidRemostatisticsPort)){$squidRemostatisticsPort=3306;}
	if($squidMaxTableDaysBackupPath==null){$squidMaxTableDaysBackupPath="/home/squid-mysql-bck";}
	
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%><div id='squid-mysql-status'></div>
		<center>". button("{wizard}","Loadjs('squid.stats-appliance.php')",14)."</center>
		
		</td>
		<td valign='top'>
			<div class=explain style='font-size:14px'>{ARTICA_DATABASE_SQUID_MAINTENANCE_WHY}</div>
			
		</td>
	</tr>
	</table>
	<div id='maxdayeventsdiv'>
	<div style='width:98%' class=form>
	<table style='width:99%'>		
	<tr>
		<td class=legend style='font-size:16px'>{max_day_events}:</td>
		<td>". Field_text("squidMaxTableDays","$squidMaxTableDays","font-size:16px;padding:3px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{backup_datas_before_delete}:</td>
		<td>". Field_checkbox("squidMaxTableDaysBackup",1,"$squidMaxTableDaysBackup")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{backup_path}:</td>
		<td>". Field_text("squidMaxTableDaysBackupPath","$squidMaxTableDaysBackupPath","font-size:16px;padding:3px;width:290px")."</td>
	</tr>
	<tr>
		<td colspan=2>
		<div class=explain style='font-size:16px'><strong>{external_artica_mysql_statistics_generator}</strong><br>{external_artica_mysql_statistics_generator_explain}</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{use_external_mysql_server}:</td>
		<td>". Field_checkbox("squidEnableRemoteStatistics",1,"$squidEnableRemoteStatistics","CheckSquidMysqlForm()")."</td>
	</tr>	
		<tr>
			<td align='right' style='font-size:16px' nowrap class=legend>{mysqlserver}:</strong></td>
			<td align='left'>" . Field_text('squidRemostatisticsServer',$squidRemostatisticsServer,'width:210px;padding:3px;font-size:16px',null,null,'')."</td>
		</tr>
		<tr>
			<td align='right' style='font-size:16px' nowrap class=legend>{listen_port}:</strong></td>
			<td align='left'>" . Field_text('squidRemostatisticsPort',$squidRemostatisticsPort,'width:110px;padding:3px;font-size:16px',null,null,'')."</td>
		</tr>				
		<tr>
			<td align='right' style='font-size:16px' nowrap class=legend>{mysqlroot}:</strong></td>
			<td align='left'>" . Field_text('squidRemostatisticsUser',$squidRemostatisticsUser,'width:210px;padding:3px;font-size:16px',null,null)."</td>
		</tr>
		<tr>
			<td align='right' style='font-size:16px' nowrap class=legend>{mysqlpass}:</strong></td>
			<td align='left'>" . Field_password("squidRemostatisticsPassword",$squidRemostatisticsPassword,"width:110px;padding:3px;font-size:16px")."</td>
		</tr>	
	
	<tr>
		<td colspan=2 align='right'>". button("{apply}","SavesquidMaxTableDays()",18)."</td>
	</tr>
	</table>
	</div>
	</div>
<script>
	var x_SavesquidMaxTableDays= function (obj) {
			RefreshTab('artica_squid_db_tabs');
		}
	
	
	function SavesquidMaxTableDays(){
		var XHR = new XHRConnection();
		XHR.appendData('squidMaxTableDays',document.getElementById('squidMaxTableDays').value);
		XHR.appendData('squidMaxTableDaysBackup',document.getElementById('squidMaxTableDaysBackup').value);
		XHR.appendData('squidMaxTableDaysBackupPath',document.getElementById('squidMaxTableDaysBackupPath').value);
		if(document.getElementById('squidEnableRemoteStatistics').checked){XHR.appendData('squidEnableRemoteStatistics',1);}else{XHR.appendData('squidEnableRemoteStatistics',0);}
		XHR.appendData('squidRemostatisticsServer',document.getElementById('squidRemostatisticsServer').value);
		XHR.appendData('squidRemostatisticsPort',document.getElementById('squidRemostatisticsPort').value);
		XHR.appendData('squidRemostatisticsUser',document.getElementById('squidRemostatisticsUser').value);
		XHR.appendData('squidRemostatisticsPassword',document.getElementById('squidRemostatisticsPassword').value);
		AnimateDiv('maxdayeventsdiv');
		XHR.sendAndLoad('$page', 'POST',x_SavesquidMaxTableDays);
	}
	
	function CheckSquidMysqlForm(){
		document.getElementById('squidRemostatisticsServer').disabled=true;
		document.getElementById('squidRemostatisticsPort').disabled=true;
		document.getElementById('squidRemostatisticsUser').disabled=true;
		document.getElementById('squidRemostatisticsPassword').disabled=true;
		if(document.getElementById('squidEnableRemoteStatistics').checked){
			document.getElementById('squidRemostatisticsServer').disabled=false;
			document.getElementById('squidRemostatisticsPort').disabled=false;
			document.getElementById('squidRemostatisticsUser').disabled=false;
			document.getElementById('squidRemostatisticsPassword').disabled=false;	
		}
	}
	CheckSquidMysqlForm();
	
	function RefreshMysqlConnection(){
		LoadAjax('squid-mysql-status','$page?squid-mysql-status=yes');
	}
	RefreshMysqlConnection();
</script>	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SAVE(){
	$sock=new sockets();
	$sock->SET_INFO("squidMaxTableDays",$_POST["squidMaxTableDays"]);
	$sock->SET_INFO("squidMaxTableDaysBackup",$_POST["squidMaxTableDaysBackup"]);
	$sock->SET_INFO("squidEnableRemoteStatistics",$_POST["squidEnableRemoteStatistics"]);
	$sock->SET_INFO("squidRemostatisticsServer",$_POST["squidRemostatisticsServer"]);
	$sock->SET_INFO("squidRemostatisticsPort",$_POST["squidRemostatisticsPort"]);
	$sock->SET_INFO("squidRemostatisticsUser",$_POST["squidRemostatisticsUser"]);
	$sock->SET_INFO("squidRemostatisticsPassword",$_POST["squidRemostatisticsPassword"]);
	
	
	}

function squid_mysql_status(){
	$q=new mysql_squid_builder();
	$img="ok64.png";
	$title="{mysqli_connectION}";
	$text=date('H:i:s')."<br>".$q->mysql_server.":".$q->mysql_port."<br>$q->SocketPath";
	
	if(!$q->BD_CONNECT()){
		$img="danger64.png";
		$title="{MYSQL_ERROR}";
		$text=$text."<br>".$q->mysql_error."<br>$q->SocketPath";
	}
	
	$tpl=new templates();
	echo Paragraphe($img, $title, $text,null,$text)."<div style='text-align:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshMysqlConnection()");
	
}