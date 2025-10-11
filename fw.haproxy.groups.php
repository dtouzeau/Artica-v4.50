<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{load_balancing} &nbsp;&raquo;&nbsp; {objects}</h1>
	<p>{APP_HAPROXY_ACLS_OBJECTS}</p>
	</div>

	</div>
	<div class='row'><div id='progress-haproxy-restart'></div>
	<div class='ibox-content'>
		<div id='table-haproxy-objects' class=row></div>
	</div>
	</div>
	


	<script>
	LoadAjaxSilent('table-haproxy-objects','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function delete_js(){
	$groupid=$_GET["delete-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ligne=$q->mysqli_fetch_array("SELECT groupname FROM haproxy_acls_groups WHERE ID='{$groupid}'");
	$md=$_GET["md"];
	$jsafter="$('#$md').remove();";
	$tpl->js_confirm_delete($ligne["groupname"], "delete", $groupid,$jsafter);
}

function delete_perform(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_POST["delete"];
	
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	
	$q->QUERY_SQL("DELETE FROM haproxy_acls_items WHERE groupid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM haproxy_acls_link WHERE groupid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM haproxy_acls_groups WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	
	$html[]="<table id='table-haproxy-allobjects' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >{description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{rules}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
	$html[]="<th data-sortable=false width=1%>Del.</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$sql="SELECT * FROM haproxy_acls_groups ORDER BY groupname";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$haproxy=new haproxy();
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		
		$ligne['groupname']=utf8_encode($ligne['groupname']);
		$GroupTypeText=$tpl->_ENGINE_parse_body($haproxy->acl_GroupType[$ligne["grouptype"]]);
		
		$ligne2=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM haproxy_acls_items WHERE groupid='{$ligne['ID']}'");
		$CountDeMembers=intval($ligne2["tcount"]);
		if(!$q->ok){$CountDeMembers=$q->mysql_error;}
		if($ligne["grouptype"]=="all"){$CountDeMembers="*";}
		$rules_list=rules_list($ligne["ID"]);
		
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1% nowrap>". $tpl->td_href($ligne["groupname"],null,"Loadjs('fw.haproxy.acls.php?groupid-js={$ligne["ID"]}',true)")."</td>";
		$html[]="<td width=1% nowrap>$GroupTypeText</td>";
		$html[]="<td>$rules_list</td>";
		$html[]="<td width=1% nowrap>". FormatNumber($CountDeMembers)."</td>";
		$html[]="<td width=1% nowrap>". $tpl->icon_delete("Loadjs('$page?delete-js={$ligne['ID']}&md=$md')","AsDansGuardianAdministrator")."</td>";
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
	$html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-haproxy-allobjects').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function rules_list($gpid){
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$table="SELECT haproxy_acls_link.groupid,
	haproxy_acls_rules.rulename,
	haproxy_acls_rules.servicename,
	haproxy_acls_rules.ID as aclid FROM haproxy_acls_link,haproxy_acls_rules,haproxy_acls_groups
	WHERE haproxy_acls_link.ruleid=haproxy_acls_rules.ID AND
	haproxy_acls_groups.ID=haproxy_acls_link.groupid AND
	haproxy_acls_link.groupid=$gpid";
	$results = $q->QUERY_SQL($table,"artica_backup");
	if(!$q->ok){return $q->mysql_error;}
	$f=array();
	foreach ($results as $index=>$ligne){
		$servicename=$ligne["servicename"];
		$servicenameenc=urlencode($servicename);
		$js="Loadjs('fw.haproxy.acls.php?ruleid-js={$ligne["aclid"]}&servicename=$servicenameenc')";
		$rulename=$ligne["rulename"];
		$f[]=$tpl->td_href("$rulename",$servicename,$js);

	}

	return @implode("<br>", $f);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}