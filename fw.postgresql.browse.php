<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){table();exit;}
js();



function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$OPEN=true;
	$users=new usersMenus();
	if(!$users->IsDBAdmin()){$OPEN=false;}
	if(!$OPEN){$tpl->js_no_privileges();exit();}
	$table=$_GET["table"];
	$tpl->js_dialog6($table, "$page?popup=".urlencode($table),2000);
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$table=$_GET["popup"];
	$md=md5(time().$table);
	$t=time();
	$html[]="<div class=\"row\"> 
		<div class='ibox-content'>
			<div class=\"input-group\">
	      		<input type=\"text\" class=\"form-control\" value=\"SELECT * FROM $table LIMIT 200\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	      		<span class=\"input-group-btn\"><button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button></span>
	     	</div>
    	</div>
	</div>
	<div id='$md'></div>
		<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(base64_encode(document.getElementById('search-this-$t').value));
			LoadAjax('$md','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sql=base64_decode($_GET["search"]);
	$q=new postgres_sql();
    admin_tracks("Launching PostreSQL query [$sql] using the SQL Browser");
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	$html[]="<table id='table-postgresql-query' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	
	$html[]="</thead>";
	$html[]="<tbody>";
	$fieldTotal=0;
	$html[]="<tr>";
	while ($fieldTotal < pg_num_fields($results)){
		$fieldName = pg_field_name($results, $fieldTotal);
		$html[]="<th data-sortable=true class='text-capitalize'>{$fieldName}</th>";
		$fieldTotal++;
	}
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    $TRCLASS=null;
	while ($row = pg_fetch_row($results)){
		$md=md5(serialize($row));
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$count = count($row);
		$y = 0;
		while ($y < $count){
			$c_row = current($row);
			$html[]="<td>$c_row</td>";
			next($row);
			$y = $y + 1;
		}
		
		$html[]="</tr>";
	}

	pg_free_result($results);	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='$fieldTotal'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-postgresql-query').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}


