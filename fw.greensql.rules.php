<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.greensql.inc");

if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete"])){rule_delete_js();exit;}
if(isset($_POST["delete"])){rule_delete();exit;}
if(isset($_GET["enable"])){rule_enable();exit;}

page();


function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-js"]);
	$table=$_GET["tbl"];
	$DBTYPES["mysql_rules"]="MySQL";
	$DBTYPES["pgsql_rules"]="PostgreSQL";
	$zcommand=$_GET["zcommand"];
	
	$title="{$DBTYPES[$table]}: {new_rule} $zcommand";
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM $table WHERE ID='$ID'");
		$title="{$DBTYPES[$table]}:$zcommand {rule} $ID {$ligne["pattern"]}";
	}
	
	$tpl->js_dialog2($title, "$page?rule-popup=$ID&tbl=$table&zcommand=".urlencode($zcommand));
	
}
function rule_enable(){
	$ID=$_GET["enable"];
	$table=$_GET["tbl"];
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM $table WHERE ID='$ID'");
	if(intval($ligne["enabled"])==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE `$table` SET `enabled`='$enabled' WHERE ID='$ID'");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
	
}


function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
	$md=$_GET["md"];
	$table=$_GET["tbl"];
	$ID=intval($_GET["delete"]);
	$ligne=$q->mysqli_fetch_array("SELECT pattern,zcommand FROM $table WHERE ID='$ID'");
	$pattern=$ligne["pattern"];
	$zcommand=$ligne["zcommand"];
	
	$DBTYPES["mysql_rules"]="MySQL";
	$DBTYPES["pgsql_rules"]="PostgreSQL";
	$fBTNS["Sensitive Tables"]="sensitive tables";
	$fBTNS["Alter"]="alter";
	$fBTNS["Create"]="create";
	$fBTNS["Drop"]="drop";
	$fBTNS["Info"]="info";
	$fBTNS["Block"]="block";
	$fBTNS["True Constants"]="true constants";
	$fBTNS["Brute Force"]="bruteforce functions";
	
	foreach ($fBTNS as $text=>$val){$HASH[$val]=$text;}
	$title="{engine}: {$DBTYPES[$table]} cmd:{$HASH[$zcommand]} $pattern ($ID)";
	$tpl->js_confirm_delete($title, "delete", "$table:$ID","$('#$md').remove()");
}

function rule_delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!preg_match("#^(.+?):([0-9]+)#",$_POST["delete"],$re)){
		echo "{$_POST["delete"]} No such right pattern...";
	}
	
	$table=$re[1];
	$ID=intval($re[2]);
	
	$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
	$q->QUERY_SQL("DELETE FROM $table WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function rule_popup(){
	$ID=$_GET["rule-popup"];
	$table=$_GET["tbl"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ligne=array();
	$btn="{add}";
	$js="dialogInstance2.close();";
	
	$DBTYPES["mysql_rules"]="MySQL";
	$DBTYPES["pgsql_rules"]="PostgreSQL";
	$fBTNS["Sensitive Tables"]="sensitive tables";
	$fBTNS["Alter"]="alter";
	$fBTNS["Create"]="create";
	$fBTNS["Drop"]="drop";
	$fBTNS["Info"]="info";
	$fBTNS["Block"]="block";
	$fBTNS["True Constants"]="true constants";
	$fBTNS["Brute Force"]="bruteforce functions";
	$zcommand=$_GET["zcommand"];
	$title="{$DBTYPES["$table"]}: {new_rule}: $zcommand";
	
	$refresh="LoadAjax('table-greensql-tablestart','$page?table=$table&zcommand=".urlencode($zcommand)."');";
	
	foreach ($fBTNS as $text=>$val){
		$HASH[$val]=$text;
	}

	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM `$table` WHERE ID='$ID'");
		$title="{$DBTYPES[$table]}: $zcommand {rule} $ID";
		$btn="{apply}";
		$js=null;
	}
	
	if(!isset($ligne["zcommand"])){$ligne["zcommand"]=$zcommand;}
	
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_hidden("table", $table);
	if($ID==0){$tpl->field_hidden("zcommand", $zcommand);}
	if($ID>0){$form[]=$tpl->field_array_hash($HASH, "zcommand", "{type}", $ligne["zcommand"]);}
	$form[]=$tpl->field_text("pattern", "{pattern}", $ligne["pattern"],true);
	$form[]=$tpl->field_text("explain", "{explain}", $ligne["explain"],true);
	echo $tpl->form_outside($title, $form,null,$btn,"$js;$refresh","AsSquidAdministrator");
}

function rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
	$ID=$_POST["ID"];
	$table=$_POST["table"];
	
	$fields[]="zcommand";
	$fields[]="pattern";
	$fields[]="explain";
	
	foreach ($fields as $key){
		
		$FD[]="`$key`='".sqlite_escape_string2($_POST[$key])."'";
		$Fa[]="`$key`";
		$Fb[]="'".sqlite_escape_string2($_POST[$key])."'";
		
	}
	
	if($ID==0){
		$q->QUERY_SQL("INSERT INTO `$table` (".@implode(", ", $Fa).") VALUES (".@implode(", ", $Fb).")");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}else{
		$q->QUERY_SQL("UPDATE `$table` SET ".@implode(", ", $FD)." WHERE ID='$ID'");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	

	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	

	
	$array["{rules}:MySQL"]="$page?table-start=mysql_rules";
	$array["{rules}:PostgreSQL"]="$page?table-start=pgsql_rules";
	echo $tpl->tabs_default($array);

}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$GREENSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GREENSQL_VERSION");
	$title=$tpl->_ENGINE_parse_body("{APP_GREENSQL} &raquo;&raquo; {rules}");
	$js="LoadAjax('table-greensql-rules','$page?tabs=yes');";
	
	

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>$title</h1></div>
	</div>



	<div class='row'><div id='progress-greensql-routers-restart'></div>
	<div class='ibox-content' style='min-height:600px'>
	<div id='table-greensql-rules'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('greensql-rules');
	$.address.title('Artica: GreenSQL Rules');
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



function table_start(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	echo "<div id='table-greensql-tablestart'></div>
	<script>LoadAjax('table-greensql-tablestart','$page?table={$_GET["table-start"]}');</script>";
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	if(!isset($_GET["zcommand"])){$_GET["zcommand"]="sensitive tables";}
	$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
	$fBTNS["Sensitive Tables"]="sensitive tables";
	$fBTNS["Alter"]="alter";
	$fBTNS["Create"]="create";
	$fBTNS["Drop"]="drop";
	$fBTNS["Info"]="info";
	$fBTNS["Block"]="block";
	$fBTNS["True Constants"]="true constants";
	$fBTNS["Brute Force"]="bruteforce functions";
	
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?rule-js=0&tbl={$_GET["table"]}&zcommand=".urlencode($_GET["zcommand"])."');\"><i class='fa fa-plus'></i> {new_rule} </label>";
	
	$color="btn-info";
	foreach ($fBTNS as $text=>$cmd){
		if($color=="btn-primary"){$color="btn-info";}else{$color="btn-primary";}
		$html[]="<label class=\"btn btn $color\" OnClick=\"javascript:LoadAjax('table-greensql-tablestart','$page?table={$_GET["table"]}&zcommand=".urlencode($cmd)."');\"><i class='fas fa-eye'></i> {$text} </label>";
		
	}
	
	$html[]="</div>";
	
	
	$html[]="<table id='table-routers-zrules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize'>{pattern}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{explain}</th>";
	$html[]="<th data-sortable=false>{enabled}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";

	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$sqladd=null;
	
	
	
	$results=$q->QUERY_SQL("SELECT * FROM `{$_GET["table"]}` WHERE zcommand='{$_GET["zcommand"]}' ORDER BY pattern");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	$TRCLASS=null;
	
	
	foreach ($results as $index=>$ligne){
		$md=md5(serialize($ligne));
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ID=$ligne["ID"];
		$delete=$tpl->icon_delete("Loadjs('$page?delete=$ID&md=$md&tbl={$_GET["table"]}')","AsSquidAdministrator");
		$enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable=$ID&tbl={$_GET["table"]}')");
		
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1% nowrap><strong style='font-family:Courier New'>".$tpl->td_href($ligne["pattern"],null,"Loadjs('$page?rule-js=$ID&tbl={$_GET["table"]}&zcommand=".urlencode($_GET["zcommand"])."');")."</strong></td>";
		$html[]="<td><strong>".$tpl->td_href($ligne["explain"],null,"Loadjs('$page?rule-js=$ID&tbl={$_GET["table"]}&zcommand=".urlencode($_GET["zcommand"])."');")."</td>";
		$html[]="<td  width=1% nowrap>$enabled</td>";
		$html[]="<td  width=1% nowrap>$delete</td>";
		$html[]="</tr>";
		
		
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-routers-zrules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}




function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}