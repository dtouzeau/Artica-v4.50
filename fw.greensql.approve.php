<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.greensql.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])) {table();exit;}
if(isset($_GET["approve"])){approve_alert();exit;}
if(isset($_GET["disapprove"])){disapprove_alert();exit;}


page();




function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html[]=$tpl->search_block($page,"mysql_green","alert_group","greensql-approvediv","");
	$html[]="<script>";
	$html[]="$.address.state('/');";
	$html[]="$.address.value('greensql-approve');";
	$html[]="</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_GREENSQL} {approve}",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new green_sql();
	$js="OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
	$t=time();

// |   |          |  |    
	$html[]="<table id='table-greensql-approve' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'>{router}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' nowrap>{database}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'>{query}</th>";
	$html[]="<th data-sortable=true class='text-capitalize'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])),array());
	if($search["MAX"]==0){$search["MAX"]=150;}
	$sql="SELECT * FROM alert_group {$search["Q"]}ORDER BY update_time DESC LIMIT 0,{$search["MAX"]}";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error."<br>$sql");return;}
	
	$zstatus[0]="<span class='label label-danger'>{detected}</span>";
	$zstatus[1]="<span class='label label-info'>{approved}</span>";
	$zstatus[2]="<span class='label label-danger'>{high_risk}</span>";
	$zstatus[3]="<span class='label label-info'>{info}</span>";
	$zstatus[4]="<span class='label label-warning'>{error}</span>";
	
	
	
	
	$TRCLASS=null;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$date=strtotime($ligne["update_time"]);
		$date_text=$tpl->time_to_date($date,true);
		$db_name=$ligne["db_name"];
		$status=$ligne["status"];
		$proxyid=$ligne["proxyid"];
		$pattern=$ligne["pattern"];
		$proxyname=$q->GET_PROXY_NAME($proxyid);
		$status_text=$zstatus[$status];
		$encoded=base64_encode(serialize($ligne));
		
		if($status==0){
			$btn=$tpl->button_autnonome("{approve}", "Loadjs('$page?approve=$encoded&md=$md')", 
			"fas fa-thumbs-up","AsSquidAdministrator",0,"btn-primary","small");
		}else{
			$btn=$tpl->button_autnonome("{disapprove}", "Loadjs('$page?disapprove=$encoded&md=$md')", 
			"fas fa-thumbs-down","AsSquidAdministrator",0,"btn-danger","small");
		}
		
		$js_query=$tpl->td_href($pattern,"{alerts}","Loadjs('fw.greensql.alerts.groups.php?agroupid={$ligne["agroupid"]}')");
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1% nowrap>{$date_text}</td>";
		$html[]="<td width=1% nowrap><span id='status-$md'>{$status_text}</span></td>";
		$html[]="<td width=1% nowrap>{$proxyname}</td>";
		$html[]="<td width=1% nowrap>$db_name</td>";
		$html[]="<td>$js_query</td>";
		$html[]="<td  width=1% nowrap><span id='btn-$md'>$btn</span></td>";
		$html[]="</tr>";
                      
	}
	

	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<div><small>$sql</small></div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-greensql-approve').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
}


function approve_alert(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new green_sql();
	$md=$_GET["md"];
	$ligne=unserialize(base64_decode($_GET["disapprove"]));
	$db_name=$ligne["db_name"];
	$db_name_q=mysql_escape_string2($db_name);
	$status=$ligne["status"];
	$proxyid=intval($ligne["proxyid"]);
	$pattern=$ligne["pattern"];
	$agroupid=intval($ligne["agroupid"]);
	
	# first we will check we we have this database created
	$ligne_db_perm=mysqli_fetch_array($q->QUERY_SQL("SELECT dbpid from db_perm WHERE db_name='$db_name_q' AND proxyid=$proxyid"));
	$dbpid=intval($ligne_db_perm["dbpid"]);
	
	if($dbpid==0){
		$sql = "INSERT INTO db_perm (proxyid, db_name) values ($proxyid,'$db_name_q')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
		
	}
	$pattern_sql=mysql_escape_string2($pattern);
	$sql = "INSERT INTO query (proxyid,perm,db_name,query) VALUES($proxyid,1,'$db_name_q','$pattern_sql')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	
	$sql = "UPDATE alert_group set status=1 WHERE agroupid=$agroupid";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	
	$btnmd="btn-$md";
	$btn=$tpl->_ENGINE_parse_body($tpl->button_autnonome("{disapprove}", "Loadjs('$page?disapprove={$_GET["approve"]}&md=$md')","fas fa-thumbs-down","AsSquidAdministrator",0,"btn-danger","small"));
	$btn=mysql_escape_string2($btn);
	$status=mysql_escape_string2($tpl->_ENGINE_parse_body("<span class='label label-info'>{approved}</span>"));
	echo "document.getElementById('$btnmd').innerHTML='$btn'\n";
	echo "document.getElementById('status-$md').innerHTML='$status'\n";
	
}


function disapprove_alert(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new green_sql();
	$md=$_GET["md"];
	$ligne=unserialize(base64_decode($_GET["approve"]));
	$db_name=$ligne["db_name"];
	$db_name_q=mysql_escape_string2($db_name);
	$status=$ligne["status"];
	$proxyid=intval($ligne["proxyid"]);
	$pattern=$ligne["pattern"];
	$agroupid=intval($ligne["agroupid"]);
	$pattern_sql=mysql_escape_string2($pattern);
	
	$q->QUERY_SQL("DELETE FROM query WHERE 
			db_name='$db_name_q' 
			AND proxyid='$proxyid'
			AND query='$pattern_sql'");
	
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	
	
	
	$sql = "UPDATE alert_group set status=0 WHERE agroupid=$agroupid";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	
	$btn=$tpl->button_autnonome("{approve}", "Loadjs('$page?approve={$_GET["disapprove"]}&md=$md')",
			"fas fa-thumbs-up","AsSquidAdministrator",0,"btn-primary","small");
	$btn=mysql_escape_string2($btn);
	$status=mysql_escape_string2($tpl->_ENGINE_parse_body("<span class='label label-danger'>{detected}</span>"));
	
	$btnmd="btn-$md";
	echo "document.getElementById('$btnmd').innerHTML='$btn'\n";
	echo "document.getElementById('status-$md').innerHTML='$status'\n";
	
}


