<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.backup.inc');

$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["RemoteArticaServer"])){Save();exit;}
js();


function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sources=$tpl->_ENGINE_parse_body("WebGET");	
	$ID=$_GET["taskid"];
	$task=$tpl->_ENGINE_parse_body("{task}");
	$html="YahooWin4('550','$page?popup=yes&taskid=$ID&index={$_GET["index"]}&CopyFrom={$_GET["CopyFrom"]}','$task $ID&raquo;WebGET');";
	echo $html;
}


function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();	
	$taskid=$_GET["taskid"];
	$index=$_GET["index"];
	$buttonname="{add}";
	if(!is_numeric($index)){$index=-1;}
	if($index>-1){
		$buttonname="{apply}";
		$sql="SELECT datasbackup FROM backup_schedules WHERE ID='$taskid'";
		$q=new mysql();
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$ressources=unserializeb64($ligne["datasbackup"]);
		preg_match("#WEBGET:(.*)#", $ressources[$index],$re);
		$ligne=unserializeb64($re[1]);
		$copy=imgtootltip("img/copy-16.png","{copy}","CopyWebGetSite('{$re[1]}')");
		
	}
	
	if($_GET["CopyFrom"]<>null){
		$ligne=unserializeb64($_GET["CopyFrom"]);
		unset($ligne["RemoteArticaSite"]);
		
	}
	
	
	
	if(!is_numeric($ligne["RemoteArticaPort"])){$ligne["RemoteArticaPort"]=9000;}
	
	$html="
	<div id='$t'>
	<div style='float:right;margin-bottom:10px'>$copy</div>
	</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{remote_artica_server}:</td>
		<td>". Field_text("RemoteArticaServer-$t",$ligne["RemoteArticaServer"],"font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{remote_artica_port}:</td>
		<td>". Field_text("RemoteArticaPort-$t",$ligne["RemoteArticaPort"],"font-size:14px;width:90px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{username}:</td>
		<td>". Field_text("RemoteArticaUser-$t",$ligne["RemoteArticaUser"],"font-size:14px;width:220px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{password}:</td>
		<td>". Field_password("RemoteArticaPassword-$t",$ligne["RemoteArticaPassword"],"font-size:14px;width:220px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{sitename}:</td>
		<td>". Field_text("RemoteArticaSite-$t",$ligne["RemoteArticaSite"],"font-size:14px;width:220px")."</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:14px'>{auto-restore}:</td>
		<td>". Field_checkbox("AutoRestore-$t",1,$ligne["AutoRestore"],"DefaultCheck$t()")."</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:14px'>{mysql_instance}:</td>
		<td><span id='freeweb-mysql-instances-$t'></span></td>
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:14px'>{sitename}:</td>
		<td>". Field_text("AutoRestoreSiteName-$t",$ligne["AutoRestoreSiteName"],"font-size:14px;width:99%")."</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'><hr>". button($buttonname, "SaveConfig$t()",16)		."</td>
	</tr>
	</table>
<script>
	var x_SaveConfig$t= function (obj) {
			var index=$index;
			document.getElementById('$t').innerHTML='';
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);return;};
			if(document.getElementById('backup-sources-table-list')){ $('#backup-sources-table-list').flexReload(); }
			if(document.getElementById('table-backup-tasks')){ $('#table-backup-tasks').flexReload(); }
			if(index<0){ YahooWin4Hide(); }
			
		 }	

	function SaveConfig$t(){
			if(!document.getElementById('AutoRestoreSqlInstance-$t')){alert('AutoRestoreSqlInstance-$t no such id');return;}
			var tt=document.getElementById('RemoteArticaSite-$t').value;
			if(tt.length<3){return;}
			var XHR = new XHRConnection();
			XHR.appendData('taskid',$taskid);
			XHR.appendData('index','$index');
			XHR.appendData('RemoteArticaServer',document.getElementById('RemoteArticaServer-$t').value);
			XHR.appendData('RemoteArticaPort',document.getElementById('RemoteArticaPort-$t').value);
			XHR.appendData('RemoteArticaUser',document.getElementById('RemoteArticaUser-$t').value);
			XHR.appendData('RemoteArticaSite',document.getElementById('RemoteArticaSite-$t').value);
			var pp=encodeURIComponent(document.getElementById('RemoteArticaPassword-$t').value);
			if(document.getElementById('AutoRestore-$t').checked){XHR.appendData('AutoRestore',1);}else{XHR.appendData('AutoRestore',0);}
			XHR.appendData('AutoRestoreSiteName',document.getElementById('AutoRestoreSiteName-$t').value);
			XHR.appendData('AutoRestoreSqlInstance',document.getElementById('AutoRestoreSqlInstance-$t').value);
			XHR.appendData('RemoteArticaPassword',pp);
			XHR.sendAndLoad('$page', 'POST',x_SaveConfig$t);
			AnimateDiv('$t');
	
	}

	function CopyWebGetSite(hash){
		Loadjs('$page?CopyFrom='+hash+'&taskid=$taskid');
	}

	function freeweb_mysql_instances$t(){
		LoadAjaxTiny('freeweb-mysql-instances-$t','freeweb.edit.php?freeweb-mysql-instances-field=yes&servername=&t=$t&default-value={$ligne["AutoRestoreSqlInstance"]}&field-name=AutoRestoreSqlInstance-$t');
	}
	
	function DefaultCheck$t(){
		document.getElementById('AutoRestoreSiteName-$t').disabled=true;
		if(document.getElementById('AutoRestoreSqlInstance-$t')){document.getElementById('AutoRestoreSqlInstance-$t').disabled=true;}
		
		if(document.getElementById('AutoRestore-$t').checked){
			document.getElementById('AutoRestoreSiteName-$t').disabled=false;
			if(document.getElementById('AutoRestoreSqlInstance-$t')){document.getElementById('AutoRestoreSqlInstance-$t').disabled=false;}
		}
		
	}
	function mysql_instance_id_check(){}
	
	freeweb_mysql_instances$t();
	DefaultCheck$t();



</script>	
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sql="SELECT datasbackup FROM backup_schedules WHERE ID='{$_POST["taskid"]}'";
	$q=new mysql();
	foreach ($_POST as $num=>$val){$_POST[$num]=trim($val);}
	reset($_POST);
	
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$ressources=unserializeb64($ligne["datasbackup"]);	
	$index=$_POST["index"];
	$_POST["RemoteArticaPassword"]=url_decode_special_tool($_POST["RemoteArticaPassword"]);
	if($index>-1){
		$ressources[$index]="WEBGET:".base64_encode(serialize($_POST));
	}else{
		$ressources[]="WEBGET:".base64_encode(serialize($_POST));
	}
	$new_ressources=base64_encode(serialize($ressources));
	$sql="UPDATE backup_schedules SET datasbackup='$new_ressources' WHERE ID='{$_POST["taskid"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
	
}
