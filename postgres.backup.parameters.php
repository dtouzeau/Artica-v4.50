<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.status.inc');
include_once('ressources/class.artica.graphs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.influx.inc');
$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if(isset($_POST["InFluxBackupDatabaseDir"])){save();exit;}

page();


function page(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	$InFluxBackupDatabaseMaxContainers=intval("InFluxBackupDatabaseMaxContainers");
	if($InFluxBackupDatabaseMaxContainers==0){$InFluxBackupDatabaseMaxContainers=5;}
	$InFluxBackupDatabaseInterval=intval("InFluxBackupDatabaseInterval");
	if($InFluxBackupDatabaseInterval==0){$InFluxBackupDatabaseInterval=10080;}
	if($InFluxBackupDatabaseInterval<1440){$InFluxBackupDatabaseInterval=1440;}
	
	$PostGresBackupMaxContainers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostGresBackupMaxContainers"));
	if($PostGresBackupMaxContainers==0){$PostGresBackupMaxContainers=3;}
	$influxdb_snapshotsize=@file_get_contents("{$GLOBALS["BASEDIR"]}/influxdb_snapshotsize");
	$InfluxDBAllowBrowse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxDBAllowBrowse"));
	
	$Intervals[1440]="1 {day}";
	$Intervals[2880]="1 {days}";
	$Intervals[7200]="5 {days}";
	$Intervals[10080]="1 {week}";
	$Intervals[20160]="2 {weeks}";
	
	for($i=0;$i<100;$i++){
		$PostGresBackupMaxContainersHASH[$i]=$i;
	}
	
	$t=time();
$html="
<div style='font-size:30px;margin-bottom:30px'>{backup}:{statistics_database}</div>		
		
<div id='influx-backup-service-id' style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{backup_each}:</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_array_Hash($Intervals,
					"InFluxBackupDatabaseInterval","$InFluxBackupDatabaseInterval","blur()",null,0,"font-size:22px")."</td>
		
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap>{ExecBackupMaxContainers}:</td>		
		<td style='font-size:22px;font-weight:bold'>".Field_array_Hash($PostGresBackupMaxContainersHASH,
		"PostGresBackupMaxContainers","$PostGresBackupMaxContainers","blur()",null,0,"font-size:22px")."</td>
		<td>". button("{backup_now}","Loadjs('postgres.backup.progress.php')",18)."</td>
	</tr>							
	<tr>
		<td class=legend style='font-size:22px' nowrap>{backup_directory}:</td>		
		<td style='font-size:22px;font-weight:bold'>".Field_text("InFluxBackupDatabaseDir-$t",
				$InFluxBackupDatabaseDir,"font-size:22px;width:550px")."</td>
		<td>". button_browse("InFluxBackupDatabaseDir")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px' nowrap colspan=2 align='right'>
				<a href=\"/backup-influx/\" style='text-decoration:underline'>{backup_directory} ". FormatBytes($influxdb_snapshotsize/1024)."</a>
		</td>
		<td>&nbsp;</td>
	</tr>	

						
						
	<tr>
		<td class=legend style='font-size:22px' nowrap>".texttooltip("{allow_browse_directory}","{allow_browse_directory_web_explain}").":</td>		
		<td style='font-size:22px;font-weight:bold' colspan=2>".Field_checkbox_design("InfluxDBAllowBrowse", 1,$InfluxDBAllowBrowse,"")."</td>
				
	</tr>			
				
				
	<tr>
		<td colspan=3 align='right' style='font-size:22px'>
			&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:GotoSquidNasStorage(true)\" 
				style='text-decoration:underline;font-size:22px'>{also_see_backup_to_nas}</a>&nbsp;&raquo;
		</td>
	</tr>
	<tr style='height:80px;'>
		<td colspan=3 align='right' style='padding-top:30px'><hr>". button("{apply}","Save$t()",40)."</td>
	</tr>			
	</table>
	</div>
<script>
var xSave$t= function (obj) {	
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	RefreshTab('influxdb_main_table');
}	
function Save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('InfluxDBAllowBrowse').checked){XHR.appendData('InfluxDBAllowBrowse', 1);	}else{XHR.appendData('InfluxDBAllowBrowse', 0);}			
	XHR.appendData('InFluxBackupDatabaseInterval', document.getElementById('InFluxBackupDatabaseInterval').value);			
	XHR.appendData('PostGresBackupMaxContainers', document.getElementById('PostGresBackupMaxContainers').value);
	XHR.appendData('InFluxBackupDatabaseDir', document.getElementById('InFluxBackupDatabaseDir-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>  
";	

echo $tpl->_ENGINE_parse_body($html);
	
}

function save(){
	
	$sock=new sockets();
	
	
	while (list ($num, $val) = each ($_POST)){
		$sock->SET_INFO($num, $val);
	
	}
}