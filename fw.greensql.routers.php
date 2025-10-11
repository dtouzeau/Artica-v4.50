<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.greensql.inc");


if(isset($_POST["dbpid"])){dbpid_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["proxyid-js"])){proxyid_js();exit;}
if(isset($_GET["proxyid-popup"])){proxyid_popup();exit;}
if(isset($_GET["proxyid-tabs"])){proxyid_tabs();exit;}
if(isset($_POST["proxyid"])){proxyid_save();exit;}
if(isset($_GET["proxyid-js-database"])){proxyid_js_database();exit;}
if(isset($_GET["proxyid-databases"])){proxyid_databases();exit;}
if(isset($_GET["proxyid-databases-list"])){VERBOSE("proxyid-databases-list",__LINE__);proxyid_databases_list();exit;}
if(isset($_GET["dbpid-js"])){dbpid_js();exit;}
if(isset($_GET["dbpid-popup"])){dbpid_popup();exit;}
if(isset($_GET["dbpid-delete"])){dbpid_delete_js();exit;}
if(isset($_POST["dbpid-delete"])){dbpid_delete();exit;}
if(isset($_GET["proxyid-delete"])){proxyid_delete_js();exit;}
if(isset($_POST["proxyid-delete"])){proxyid_delete();exit;}
page();


function proxyid_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$proxyid=intval($_GET["proxyid-js"]);
	$title="{new_router}";
	if($proxyid>0){
		$q=new green_sql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT proxyname FROM proxy WHERE proxyid='$proxyid'"));
		$title=$ligne["proxyname"];
		$tpl->js_dialog2($title, "$page?proxyid-tabs=$proxyid");
		return;
	}
	
	$tpl->js_dialog2($title, "$page?proxyid-popup=$proxyid");
	
}
function proxyid_js_database(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$proxyid=intval($_GET["proxyid-js-database"]);
	$title="{new_router}";
	$q=new green_sql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT proxyname FROM proxy WHERE proxyid='$proxyid'"));
	$title=$ligne["proxyname"];
	$tpl->js_dialog2($title, "$page?proxyid-databases=$proxyid");
}


function dbpid_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$dbpid=intval($_GET["dbpid-js"]);
	$proxyid=intval($_GET["proxid"]);
	$q=new green_sql();
	$title="{new_router}";
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT proxyname FROM proxy WHERE proxyid='$proxyid'"));
	$proxyname=$ligne["proxyname"];
	$title="$proxyname >> {new_database}";
	
	if($dbpid>0){
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT proxyid,db_name FROM db_perm WHERE dbpid='$dbpid'"));
		$proxyid=intval($ligne["proxyid"]);
		$DBNAME=$ligne["db_name"];
		
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT proxyname FROM proxy WHERE proxyid='$proxyid'"));
		$proxyname=$ligne["proxyname"];
		$title="$proxyname:[$proxyid] >> $DBNAME";
	}

	$tpl->js_dialog4($title, "$page?dbpid-popup=$dbpid&proxyid=$proxyid");

}

function proxyid_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new green_sql();
	$md=$_GET["md"];
	$proxyid=intval($_GET["proxyid-delete"]);
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT proxyname FROM proxy WHERE proxyid='$proxyid'"));
	$db_name=$ligne["proxyname"];
	$tpl->js_confirm_delete($db_name, "proxyid-delete", $proxyid,"$('#$md').remove()");
}

function proxyid_delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$proxyid=intval($_POST["proxyid-delete"]);
	$q=new green_sql();
	$q->QUERY_SQL("DELETE FROM db_perm WHERE proxyid='$proxyid'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM proxy WHERE proxyid='$proxyid'");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function dbpid_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new green_sql();
	$md=$_GET["md"];
	$dbpid=intval($_GET["dbpid-delete"]);
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT db_name FROM db_perm WHERE dbpid='$dbpid'"));
	$db_name=$ligne["db_name"];
	$tpl->js_confirm_delete($db_name, "dbpid-delete", $dbpid,"$('#$md').remove()");
	
}

function dbpid_delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new green_sql();
	$dbpid=intval($_POST["dbpid-delete"]);
	$q->QUERY_SQL("DELETE FROM db_perm WHERE dbpid=$dbpid");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function proxyid_popup(){
	$proxyid=$_GET["proxyid-popup"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ligne=array();
	$btn="{add}";
	$js="dialogInstance2.close();";
	$title="{new_router}";
	$DBTYPES["mysql"]="MySQL";
	$DBTYPES["pgsql"]="PostgreSQL";

	if($proxyid>0){
		$q=new green_sql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM proxy WHERE proxyid='$proxyid'"));
		$title=$ligne["proxyname"];
		$btn="{apply}";
		$js=null;
	}
	
	$form[]=$tpl->field_hidden("proxyid", $proxyid);
	$form[]=$tpl->field_text("proxyname", "{router_name}", $ligne["proxyname"],true);
	$form[]=$tpl->field_localips("frontend_ip", "{listen_address}",  $ligne["frontend_ip"]);
	$form[]=$tpl->field_numeric("frontend_port","{listen_port}",$ligne["frontend_port"]);
	$form[]=$tpl->field_section("{should_be_forwarded_to}");
	$form[]=$tpl->field_array_hash($DBTYPES, "dbtype", "{database}", $ligne["dbtype"]);
	$form[]=$tpl->field_text("backend_server", "{remote_server}", $ligne["backend_server"],true);
	$form[]=$tpl->field_ipv4("backend_ip", "{remote_address}",  $ligne["backend_ip"]);
	$form[]=$tpl->field_numeric("backend_port","{remote_port}",$ligne["backend_port"]);
	echo $tpl->form_outside($title, $form,"{APP_GREENSQL_ROUTER_ABOUT}",$btn,"$js;LoadAjax('table-greensql-router','$page?table=yes');","AsSquidAdministrator");
}

function proxyid_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new green_sql();
	
	if(!preg_match("/^[a-zA-Z0-9_\.\ ]+$/", $_POST['proxyname'])){
		echo "Router name is invalid. It contains illegal characters.<br>Valid characters are a-z, A-Z, 0-9, '_', ' ' and '.'.<br/>\n";
		return;
	}
	if(!preg_match("/^[a-zA-Z0-9_\.\ ]+$/", $_POST['backend_server'])){
		echo "Remote server Name is invalid. It contains illegal characters.<br>Valid characters are a-z, A-Z, 0-9, '_' and '.'.<br/>\n";
		return;
	}
	
	if(ip2long($_POST['backend_ip']) == -1){
		echo "Remote IP has wrong IP address format.<br/>\n";
	}
	if(ip2long($_POST['frontend_ip']) == -1){
		echo "Local IP has wrong IP address format.<br/>\n";
	}
	
	if($_POST["proxyid"]==0){
		$sql = "SELECT proxyid from proxy WHERE frontend_ip = '{$_POST['frontend_ip']}' AND frontend_port = ".$_POST['frontend_port'];
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		if($ligne["proxyid"]>0){echo "Failed to add new router, same frontend ip and port already used.";return;}
		
		$sql = "INSERT into proxy (proxyname, frontend_ip, frontend_port, backend_server, backend_ip, backend_port, dbtype, status) 
		VALUES ('{$_POST['proxyname']}', '{$_POST['frontend_ip']}',{$_POST['frontend_port']}, '{$_POST['backend_server']}','{$_POST['backend_ip']}', {$_POST['backend_port']}, '{$_POST['dbtype']}',0)";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}else{
		
		$sql = "SELECT * from proxy WHERE frontend_ip='{$_POST['frontend_ip']}' AND frontend_port ={$_POST['frontend_port']} AND proxyid != {$_POST['proxyid']}";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		if($ligne["proxyid"]>0){echo "Failed, same frontend ip and port already used.";return;}
		
		
		$sql="UPDATE proxy SET `proxyname`='{$_POST['proxyname']}',
		frontend_ip='{$_POST['frontend_ip']}',
		frontend_port='{$_POST['frontend_port']}',
		backend_server='{$_POST['backend_server']}',
		backend_ip='{$_POST['backend_ip']}',
		backend_port='{$_POST['backend_port']}', 
		dbtype='{$_POST['dbtype']}' WHERE proxyid='{$_POST["proxyid"]}'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$GREENSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GREENSQL_VERSION");
	$title=$tpl->_ENGINE_parse_body("{APP_GREENSQL} &raquo;&raquo; {routers}");
	$js="LoadAjax('table-greensql-router','$page?table=yes');";
	
	

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>$title</h1>
	<p>{APP_GREENSQL_ROUTER_ABOUT}</p>

	</div>

	</div>



	<div class='row'><div id='progress-greensql-routers-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-greensql-router'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('greensql-routers');
	$.address.title('Artica: GreenSQL Routers');
	$js

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_GREENSQL} ",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}


function proxyid_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$proxyid=$_GET["proxyid-tabs"];
	
	$array["{router}"]="$page?proxyid-popup=$proxyid";
	$array["{databases}"]="$page?proxyid-databases=$proxyid";
	echo $tpl->tabs_default($array);
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$q=new green_sql();
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?proxyid-js=0');\"><i class='fa fa-plus'></i> {new_router} </label>";
	$html[]="</div>";
	
	
	$html[]="<table id='table-routers-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{router}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{databases}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1% nowrap>{packets_from}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1% nowrap>{should_be_forwarded_to}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";

	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$results=$q->QUERY_SQL("SELECT * FROM `proxy` ORDER BY proxyname");
	
	
	$TRCLASS=null;
	$DBTYPES["mysql"]="MySQL";
	$DBTYPES["pgsql"]="PostgreSQL";
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$proxyid=$ligne["proxyid"];
		$md=md5(serialize($ligne));
		$proxyname=$ligne["proxyname"];
		$frontend_ip=$ligne["frontend_ip"];
		$frontend_port=$ligne["frontend_port"];
		$backend_server=$ligne["backend_server"];
		$backend_ip =$ligne["backend_ip"];
		$backend_port=$ligne["backend_port"];
		$dbtype=$ligne["dbtype"];
		$status=$ligne["status"];
		$delete=$tpl->icon_delete("Loadjs('$page?proxyid-delete=$proxyid&md=$md')","AsSquidAdministrator");
		$dbs=table_db_list($proxyid);
		if($dbs==null){$dbs_text=$tpl->icon_nothing("Loadjs('$page?proxyid-js-database=$proxyid')");}else{
			$dbs_text=$tpl->td_href($dbs,null,"Loadjs('$page?proxyid-js-database=$proxyid')");
		}
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1% class='center' nowrap>{$status}</center></td>";
		$html[]="<td width=1% nowrap><strong>".$tpl->td_href($proxyname,null,"Loadjs('$page?proxyid-js=$proxyid');")."</strong></td>";
		$html[]="<td width=1% nowrap>{$DBTYPES[$dbtype]}</td>";
		$html[]="<td>{$dbs_text}</td>";
		$html[]="<td>$frontend_ip:$frontend_port</td>";
		$html[]="<td>$backend_ip:$backend_port</td>";
		$html[]="<td  width=1% nowrap>$delete</td>";
		$html[]="</tr>";
		
		
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-routers-rules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function proxyid_databases(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$q=new green_sql();
	$proxyid=$_GET["proxyid-databases"];
	
	echo "<div id='proxyid-databases-list'></div><script>LoadAjax('proxyid-databases-list','$page?proxyid-databases-list=$proxyid');</script>";
	
}

function table_db_list($proxyid){
	$q=new green_sql();
	$results=$q->QUERY_SQL("SELECT db_name FROM `db_perm` WHERE proxyid='$proxyid' ORDER BY db_name");
	if(!$q->ok){return;}
		$t=array();
		$TRCLASS=null;
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$db_name=$ligne["db_name"];
			$t[]=$db_name;
		}
		if(count($t)==0){return;}
		return @implode(", ", $t);
}

function proxyid_databases_list(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$q=new green_sql();
	$proxyid=$_GET["proxyid-databases-list"];
	
	$html[]="<div class='row' style='text-align:left;padding-left:10px'><div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px;text-align:left'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?dbpid-js=0&proxid=$proxyid');\">
		<i class='fa fa-plus'></i> {new_database} </label>";
	$html[]="</div></div>";
	
	
	$html[]="<table id='table-routers-db-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{database}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	VERBOSE("SELECT * FROM `db_perm` WHERE proxyid='$proxyid' ORDER BY db_name ",__LINE__);
	$results=$q->QUERY_SQL("SELECT * FROM `db_perm` WHERE proxyid='$proxyid' ORDER BY db_name");
	if(!$q->ok){
		VERBOSE("$q->mysql_error",__LINE__);echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	$TRCLASS=null;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$dbpid=$ligne["dbpid"];
		$proxyid=$ligne["proxyid"];
		$db_name=$ligne["db_name"];
		$perms=$ligne["perms"];
		$perms2=$ligne["perms2"];
		$status=$ligne["status"];
		$sysdbtype=$ligne["sysdbtype"];
		$status_changed=$ligne["status_changed"];
		
		$delete=$tpl->icon_delete("Loadjs('$page?dbpid-delete=$dbpid&md=$md')","AsSquidAdministrator");
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1% class='center' nowrap>{$status}</center></td>";
		$html[]="<td><strong>".$tpl->td_href($db_name,null,"Loadjs('$page?dbpid-js=$dbpid');")."</strong></td>";
		$html[]="<td  width=1% nowrap>$delete</td>";
		$html[]="</tr>";
		
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-routers-db-rules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


function dbpid_popup(){
	$q=new green_sql();
	$proxyid=$_GET["proxyid"];
	$dbpid=$_GET["dbpid-popup"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ligne=array();
	$btn="{add}";
	$js="dialogInstance4.close();";
	$title="{new_database}";
	$DBTYPES["mysql"]="MySQL";
	$DBTYPES["pgsql"]="PostgreSQL";
	$js=$js."LoadAjax('proxyid-databases-list','$page?proxyid-databases-list=$proxyid');LoadAjax('table-greensql-router','$page?table=yes');";
	
	if($dbpid>0){
		
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM db_perm WHERE dbpid='$dbpid'"));
		$proxyid=$ligne["proxyid"];
		$title=$ligne["db_name"];
		$btn="{apply}";
		$js=null;
		$ligne['alter_perm']  = ($ligne['perms'] & 4) ? 1 : 0;
		$ligne['create_perm'] = ($ligne['perms'] & 1) ? 1 : 0;
		$ligne['drop_perm']   = ($ligne['perms'] & 2) ? 1 : 0;
		$ligne['info_perm']   = ($ligne['perms'] & 8) ? 1 : 0;
		$ligne['block_q_perm']= ($ligne['perms'] & 16)? 1 : 0;
		
		
	}
	if($proxyid>0){
		$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT proxyname FROM proxy WHERE proxyid='$proxyid'"));
		$proxyname=$ligne2["proxyname"];
		$form[]=$tpl->field_info("rname", "{router}",$proxyname);
		
		
	}
	$form[]=$tpl->field_hidden("proxyid", $proxyid);
	$form[]=$tpl->field_hidden("dbpid", $dbpid);
	$form[]=$tpl->field_text("db_name", "{database}", $ligne["db_name"],true);
	
	$arr=get_db_modes();
	foreach ($arr as $index=>$array){$get_db_modes[$index]=$array["mode"];}
	$form[]=$tpl->field_array_hash($get_db_modes, "block_mode","{method}", $ligne["status"],true);
	
	$accepttypes[0]["LABEL"]="{deny}";
	$accepttypes[0]["VALUE"]="0";
	
	$accepttypes[1]["LABEL"]="{allow}";
	$accepttypes[1]["VALUE"]="1";
	
		
	$form[]=$tpl->field_checkbox_toogle("alter_perm", "{CHDBSTRUCT}", $ligne['alter_perm'], $accepttypes);
	$form[]=$tpl->field_checkbox_toogle("create_perm", "{CRTBIDIC}", $ligne['create_perm'], $accepttypes);
	$form[]=$tpl->field_checkbox_toogle("drop_perm", "{DELTBIDIC}", $ligne['drop_perm'], $accepttypes);
	$form[]=$tpl->field_checkbox_toogle("info_perm","{INFOTBIDIC}", $ligne['info_perm'], $accepttypes);
	$form[]=$tpl->field_checkbox_toogle("block_q_perm","{EXECTBIDIC}", $ligne['block_q_perm'], $accepttypes);

	echo $tpl->form_outside($title, $form,"{GREENSQL_DB_ABOUT}",$btn,"$js;","AsSquidAdministrator");
	
	
}

function get_db_modes(){
	$modes = array();
	$modes[0] = array('mode' => 'IPS',
			'help' => 'Block high risk queries based on the heuristics and privileged commands. '.
			'Whitelist is checked for exceptions.');
	$modes[1] = array('mode' => 'IPS (block admin commands only)',
			'help' => 'Block high privileged commands only for example CREATE TABLE. Whitelist is checked for exceptions.');
	$modes[2] = array('mode' => 'IDS (no blocking)',
			'help' => 'Nothing is blocked. Only warning is generated for suspicious queries. Whitelist is checked for exceptions.');
	$modes[4] = array('mode' => 'Firewall',
			'help' => 'Block all commands unlisted in whitelist. It is recommended to enable this mode after whitelist is build.');
	$modes[10]= array('mode' => 'Learning Mode',
			'help' => 'During learning mode no queries are blocked. Query patterns are automatically added to the whitelist.');
	$modes[11]= array('mode' => 'Learning Mode for 3 days',
			'help' => 'Same as <stromg>Learning Mode</strong>. Query patterns are automatically added to the whitelist.<br/>'.
			'After 3 days database is automatically switched to the <strong>Database Firewall</strong> mode.');
	$modes[12]= array('mode' => 'Learning Mode for 7 days',
			'help' => 'Same as <strong>Learning Mode</strong>. Query patterns are automatically added to the whitelist.<br/>'.
			'After 7 days database is automatically switched to the <strong>Database Firewall</strong> mode.');
	return $modes;
}


function dbpid_save(){
	$dbpid=$_POST["dbpid"];
	$db['create_perm'] = intval(trim($_POST['create_perm']));
	$db['drop_perm']   = intval(trim($_POST['drop_perm']));
	$db['alter_perm']  = intval(trim($_POST['alter_perm']));
	$db['info_perm']   = intval(trim($_POST['info_perm']));
	$db['block_q_perm']= intval(trim($_POST['block_q_perm']));
	$block_mode        = intval(trim($_POST['block_mode']));
	$db['db_name']=trim($_POST["db_name"]);
	
	if ($db['create_perm'] != 0 && $db['create_perm'] != 1){
		echo "Create table permission is invalid.<br/>\n";
		return;
	} else if ($db['create_perm'] == 1) {
		$db['perms'] = $db['perms'] | 1;
	}
	
	if ($db['drop_perm'] != 0 && $db['drop_perm'] != 1){
		echo "Drop permission is invalid.<br/>\n";
		return;
	} else if ($db['drop_perm'] == 1) {
		$db['perms'] = $db['perms'] | 2;
	}
	
	if ($db['alter_perm'] != 0 && $db['alter_perm']  != 1){
		echo "Change table structure permission is invalid.<br/>\n";
		return;
	} else if ($db['alter_perm'] == 1) {
		$db['perms'] = $db['perms'] | 4;
	}
	
	if ($db['info_perm'] != 0 && $db['info_perm'] != 1){
		echo "Disclose table structure permission is invalid.<br/>\n";
		return;
	} else if ($db['info_perm'] == 1) {
		$db['perms'] = $db['perms'] | 8;
	}
	

	if ($db['block_q_perm'] != 0 && $db['block_q_perm'] != 1){
		echo "Block sensitive queries permission is invalid.<br/>\n";
	} else if ($db['block_q_perm'] == 1) {
		$db['perms'] = $db['perms'] | 16;
	}
		
	if ($block_mode > 13 || $block_mode < 0){
			echo "Block Status value is invalid.<br/>\n";
			return;
	}
		
	if (strlen($db['db_name']) > 100){
			echo "Database name is too long.<br/>\n";
			return;
	}
		
	if (strlen($db['db_name']) == 0){
		echo  "Database name can not be empty.<br/>\n";
		return;
	} else if (!preg_match("/^[a-zA-Z0-9_\ -]+$/",$db['db_name'])){
		echo "Database Name is invalid. It contains illegal characters. Valid characters are a-z, A-Z, 0-9 and '_'.<br/>\n";
		return;
	}
	
	if(intval($_POST['proxyid'])==0){
		echo "Wrong Router == 0!";
		return;
	}
	
	$fields["proxyid"]=$_POST['proxyid'];
	$fields["db_name"]=$db['db_name'];
	$fields["perms"]=$db['perms'];
	$fields["status"]=$block_mode;
	$fields["status_changed"]=date("Y-m-d H:i:s");
	
	foreach ($fields as $fieldname=>$value){
		$ff[]="`$fieldname`";
		$ft[]="'$value'";
		$fe[]="`$fieldname`='$value'";
	}
	
	$sqladd="INSERT INTO `db_perm` (".@implode(", ", $ff).") VALUES (".@implode(", ", $ft).")";
	$sqled="UPDATE db_perm SET ".@implode(", ", $fe)." WHERE dbpid='$dbpid'";
	$sql=$sqled;
	if($dbpid==0){$sql=$sqladd;}
	$q=new green_sql();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."<br><code>$sql</code>";}
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}