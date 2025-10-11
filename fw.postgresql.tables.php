<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["delete-js"])){delete_table_js();exit;}
if(isset($_POST["delete"])){delete_table();exit;}
if(isset($_GET["refresh-js"])){refresh_js();exit;}
if(isset($_GET["table"])){table();exit;}
page();


function delete_table_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$table=$_GET["delete-js"];
	$id=$_GET["id"];
	$tpl->js_confirm_empty("{table}: $table", "delete", $table,"$('#$id').remove()");
	
	
}

function delete_table(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    admin_tracks("Remove PostgreSQL table {$_POST["delete"]}");
	$q=new postgres_sql();
	$q->QUERY_SQL("TRUNCATE TABLE {$_POST["delete"]}");
    if(!$q->ok){echo $q->mysql_error;}
}

function refresh_js(){
    $sock=new sockets();
    $page=CurrentPageName();
    $sock->REST_API("/postgresql/pgsize");
    sleep(2);
    header("content-type: application/x-javascript");
    echo "LoadAjax('tables-pg-size','$page?table=yes');";


}
function page(){
    $page=CurrentPageName();
    echo "<div id='tables-pg-size'></div>
    <script>LoadAjax('tables-pg-size','$page?table=yes');</script>";
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
	
	$html[]="<table id='table-postgrsql-tables' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{updated_on}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{table}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{rows}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='number'>bytes</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{empty}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	
	
	$FORCE_FILTER=null;
	$total=0;
	
	
	
	$sql="SELECT * FROM ztables ORDER BY zbytes DESC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body($tpl->FATAL_ERROR_SHOW_128($q->mysql_error));
		exit();
	}
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		$tablename=$ligne["tablename"];
		
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$total_bytes=FormatBytes($ligne["zbytes"]/1024);
		$rows=FormatNumber($ligne["zrows"]);
		$md=md5($tablename);
        $time=$ligne["zdate"];
        $zdate=$tpl->time_to_date($time,true);
		$tablenameenc=urlencode($tablename);
		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$zdate</td>";
		$html[]="<td>" . $tpl->td_href("$tablename","{browse}","Loadjs('fw.postgresql.browse.php?table=$tablenameenc')")."</td>";
		$html[]="<td style='width:1%' nowrap>$rows</td>";
		$html[]="<td style='width:1%' nowrap>$total_bytes</td>";
        $html[]="<td style='width:1%' nowrap>{$ligne["zbytes"]}</td>";
		$html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-js=$tablenameenc&id=$md')","AsDatabaseAdministrator")."</td>";
		$html[]="</tr>";
	
	
	}

    $topbuttons[] = array("Loadjs('$page?refresh-js=yes')", ico_refresh, "{refresh}");
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_POSTGRESQL_VERSION");
    $TINY_ARRAY["TITLE"]="PostgreSQL $version &raquo;&raquo; {tables}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{PostgreSQL_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

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
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-postgrsql-tables').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}