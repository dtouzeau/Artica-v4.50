<?php
//$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["ipaddr-js"])){ipaddr_js();exit;}
if(isset($_GET["ipaddr-import-js"])){ipaddr_import_js();exit;}
if(isset($_GET["ipaddr-popup"])){ipaddr_popup();exit;}
if(isset($_POST["ipaddr"])){ipaddr_save();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["import"])){import_save();exit;}
if(isset($_GET["ipaddr-import-popup"])){ipaddr_import_popup();exit;}
if(isset($_GET["table"])){table();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$tpl->js_dialog4("{CybercrimeIPFeeds} {whitelists}","$page?table=yes",900);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$html[]="

	</div>
	<div class='ibox-content'>
	<div id='postfix-transactions'></div>

	</div>
	</div>
	";

	$q=new postgres_sql();
	$q->CREATE_TABLES();
	$html[]=$tpl->search_block($page,"postgres","ipset_whitelists","ipset_whitelists","");
	echo $tpl->_ENGINE_parse_body($html);

}

function delete(){
	$ipaddr=$_GET["delete"];
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM ipset_whitelists WHERE ipaddr='$ipaddr'");
	echo "$('#{$_GET["md"]}').remove()\n";
}


function ipaddr_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ipaddr=$_GET["ipaddr-js"];
	if($ipaddr==null){$title="{new_entry}";}else{$title=$ipaddr;}
	$tpl->js_dialog($title, "$page?ipaddr-popup=$ipaddr&function={$_GET["function"]}");
}
function ipaddr_import_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $title="{import}";
    $tpl->js_dialog($title, "$page?ipaddr-import-popup=yes&function={$_GET["function"]}");
}
function ipaddr_import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();

    $uid=$_SESSION["uid"];
    if($uid==-100){$uid="Manager";}
    $jsafter="BootstrapDialog1.close();{$_GET["function"]}()";

    $bt="{add}";
    $title="{new_address}";
    $form[]=$tpl->field_textareacode("import","{address}", null);
    echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator",true);
}

function ipaddr_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new postgres_sql();
	$ipaddr=$_GET["ipaddr-popup"];
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="Manager";}
	$jsafter="BootstrapDialog1.close();{$_GET["function"]}()";
	if($ipaddr==null){
		$bt="{add}";
		$title="{new_address}";
		$form[]=$tpl->field_text("ipaddr","{address}", $ipaddr);
		$description="Added $uid - ".date("Y-m-d H:i:s");
	}else{
		$bt="{apply}";
		$form[]=$tpl->field_ipaddr("ipaddr", "{address}", null,true);
		$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM ipset_whitelists WHERE ipaddr='$ipaddr'"));
		$description=$ligne["description"];
		$title=$ipaddr." - {$ligne["zdate"]}";
	}
	$form[]=$tpl->field_text("description", "{description}", $description);
	echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator",true);
}

function ipaddr_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ipclass=new IP();
	$ipaddr=$_POST["ipaddr"];
	$description=$_POST["description"];
	$date=date("Y-m-d H:i:s");
	$q=new postgres_sql();


    $description="(".gethostbyaddr($ipaddr).") - $description";
	$q->QUERY_SQL("INSERT INTO ipset_whitelists (ipaddr,description,zDate) VALUES ('$ipaddr','$description','$date')");
	if(!$q->ok){echo "$ipaddr: $q->mysql_error";}


}

function import_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode("\n",$_POST["import"]);
    unset($_POST["import"]);
    foreach($tb as $item) {
        $_POST["ipaddr"]=$item;
        ipaddr_save();
    }
}


function search(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new postgres_sql();
	$t=time();

	$jsrestart=$tpl->framework_buildjs(
		"/firewall/reconfigure","firehol.reconfigure.progress",
		"firehol.reconfigure.log",
		"progress-CybercrimeIPFeeds-restart",
		"");
	
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?ipaddr-js=&function={$_GET["function"]}');\">";
	$html[]="<i class='fa fa-plus'></i> {new_address} </label>";


    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?ipaddr-import-js=&function={$_GET["function"]}');\">";
    $html[]="<i class='fas fa-file-import'></i> {import} </label>";
	
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart;\">";
	$html[]="<i class='fas fa-save'></i> {compile_rules} </label>";
	
	$html[]="</div>";
	
	$search=trim($_GET["search"]);
	$aliases["ipaddr"]="ipaddr";
	$querys=$tpl->query_pattern($search,$aliases);
	$MAX=$querys["MAX"];
	if($MAX==0){$MAX=150;}
	$sql="SELECT * FROM ipset_whitelists {$querys["Q"]} ORDER BY zdate DESC LIMIT $MAX";
	
	if(preg_match("#^([0-9\.]+)#", $search)){
		$sql="SELECT * FROM ipset_whitelists WHERE ipaddr='$search' ORDER BY zdate DESC LIMIT $MAX";
	}
	
	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}
	

	
	$TRCLASS=null;
	$html[]="</div>";
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$ipaddr=$ligne["ipaddr"];
		$zDate=strtotime($ligne["zdate"]);
		$time=$tpl->time_to_date($zDate,true);
		$class_text=null;
		$description=$ligne["description"];
		$ipaddr=$tpl->td_href($ipaddr,null,"Loadjs('$page?ipaddr-js=$ipaddr&function={$_GET["function"]}');");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>{$time}</td>";
		$html[]="<td style='width:1%' nowrap><span class='$class_text'>$ipaddr</span></td>";
		$html[]="<td style='width:99%' nowrap><span class='$class_text'>$description</span></td>";
		$html[]="<td style='width:1%' class='center'nowrap>".$tpl->icon_delete("Loadjs('$page?delete={$ligne["ipaddr"]}&md=$md')")."</td>";
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
	$html[]="<small>$sql</small>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
?>
