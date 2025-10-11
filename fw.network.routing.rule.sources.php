<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["route-move-js"])){route_move_js();exit;}
if(isset($_GET["route-delete-js"])){route_delete_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["export-js"])){export_rules_js();exit;}
if(isset($_GET["import-js"])){import_rules_js();exit;}
if(isset($_GET["export-popup"])){export_rules_popup();exit;}
if(isset($_GET["import-popup"])){import_rules_popup();exit;}
if(isset($_POST["ruleidexport"])){echo "ok";exit;}
if(isset($_POST["ruleidimport"])){import_rules_save();exit;}
start();

function start():bool{


    $page = CurrentPageName();
    $function = "";
    if (isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    $tpl = new template_admin();
    if (!isset($_GET["ID"])) {
        $_GET["ID"] = 0;
    }
    $ID = intval($_GET["ID"]);
    if ($ID == 0) {
        echo $tpl->div_error("ID === 0.?..?");
        return false;
    }

    echo "<div id='buttons-source-{$_GET["ID"]}' style='margin-top:10px;margin-bottom:10px'></div>";
    echo $tpl->search_block($page, "", "", "", "&table={$_GET["ID"]}&function2=$function");
    return true;
}


function rule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_rule}");
	$ID=intval($_GET["rule-js"]);
	$ruleid=intval($_GET["ruleid"]);
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$ligne=$q->mysqli_fetch_array("SELECT RouteName,nic FROM routing_rules WHERE ID='$ruleid'");
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $function2="";if(isset($_GET["function2"])){$function2=$_GET["function2"];}
    $nic=$ligne["nic"];
    $RouteName=$ligne["RouteName"];
    if($ruleid==999999){
        $ligne["RouteName"]="{global_rules}";
    }


	
	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT `pattern` FROM routing_rules_src WHERE ID='$ID'");
		$title=$ligne["pattern"];
		if(!$q->ok){echo $q->mysql_error_html();}
	}


	$tpl->js_dialog2("$nic >> $RouteName: $title", "$page?rule-popup=$ID&ruleid=$ruleid&function2=$function2&function=$function",550);

}
function route_move_js(){
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	$ID=$_GET["route-move-js"];
	$dir=$_GET["dir"];
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM routing_rules_src WHERE ID='$ID'");


	$zOrder=$ligne["zOrder"];
	if($dir=="up"){
		$NewzOrder=$zOrder-1;
	}else{
		$NewzOrder=$zOrder+1;
	}

	if($NewzOrder<0){$NewzOrder=0;}


	$q->QUERY_SQL("UPDATE routing_rules_src SET zOrder='$zOrder' WHERE zOrder='$NewzOrder' AND ID<>'$ID'");
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";}
	$q->QUERY_SQL("UPDATE routing_rules_src SET zOrder='$NewzOrder' WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";}

	$results=$q->QUERY_SQL("SELECT * FROM routing_rules_src WHERE ruleid={$_GET["ruleid"]} ORDER BY zOrder");
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";}
	$c=0;
	foreach ($results as $index=>$ligne){
		$c++;
		$q->QUERY_SQL("UPDATE routing_rules_src SET zOrder='$c' WHERE ID='{$ligne["ID"]}'");
		if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";}
	}
    echo "LoadAjax('table-loader-iprule','fw.network.routing.php?table=yes');";

}

function route_delete_js(){
	header("content-type: application/x-javascript");
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$tpl=new template_admin();
	$q->QUERY_SQL("DELETE FROM routing_rules_src WHERE ID='{$_GET["route-delete-js"]}'");
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	echo "$('#{$_GET["md"]}').remove();\n";
	echo "LoadAjax('table-loader-iprule','fw.network.routing.php?table=yes');";
	
}

function rule_popup(){
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$tpl=new template_admin();
	$btname="{add}";
	$ID=intval($_GET["rule-popup"]);
	$ruleid=intval($_GET["ruleid"]);
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $function2="";if(isset($_GET["function2"])){$function2=$_GET["function2"];}


	$ligne2=$q->mysqli_fetch_array("SELECT `RouteName`,nic  FROM routing_rules WHERE ID=$ruleid");

    $RouteName=$ligne2["RouteName"];

    if($ruleid==999999){
        $RouteName="{global_rules}";
    }
    if($ID>0){
		$btname="{apply}";
		$ligne=$q->mysqli_fetch_array("SELECT * FROM routing_rules_src WHERE ID='$ID'");
		if(!$q->ok){echo $q->mysql_error_html();}
	}

    if(isset($ligne["type"])){
        $ligne["type"]=1;
    }
    $Type=intval($ligne["type"]);
    if($Type==0){
        $Type=1;
    }
	
	$types[1]="{network_nic}";
	$types[2]="{host}";
	$types[4]="{blackhole}";
	$types[5]=$tpl->_ENGINE_parse_body("{iprouteprohibit}");
	
	
	if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=0;}
	if(!is_numeric($ligne["metric"])){$ligne["metric"]=0;}
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_hidden("ruleid", $_GET["ruleid"]);
	$form[]=$tpl->field_numeric("zOrder","{order}",$ligne["zOrder"]);
	$form[]=$tpl->field_numeric("metric","{metric}",$ligne["metric"]);

    if($ruleid==999999) {
        $form[] = $tpl->field_interfaces("nic", "nooloopNoDef:{interface}", $ligne["nic"]);
    }


	$form[]=$tpl->field_array_hash($types, "stype", "{type}", $Type);
	$form[]=$tpl->field_text("pattern", "{item} ({address}/{network2})", $ligne["pattern"]);
	$form[]=$tpl->field_text("gateway", "{gateway}", $ligne["gateway"]);

	$js[]="dialogInstance2.close();";
    if(strlen($function)>2){$js[]="$function()";}
    if(strlen($function2)>2){$js[]="$function2()";}
	$js[]="LoadAjax('table-loader-iprule','fw.network.routing.php?table=yes');";

	
	$html=$tpl->form_outside("", @implode("\n", $form),null,$btname,@implode(";", $js),"AsSystemAdministrator");
	echo $html;
	
}

function rule_save(){
	
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	$ID=$_POST["ID"];
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    if($_POST["pattern"]=="*"){$_POST["pattern"]="0.0.0.0/0";}
    $_POST["pattern"]=$tpl->CLEAN_BAD_CHARSNET($_POST["pattern"]);
    $_POST["gateway"]=$tpl->CLEAN_BAD_CHARSNET($_POST["gateway"]);


	if($ID==0){
		$md5=md5("{$_POST["nic"]}{$_POST["pattern"]}");
		$sql="INSERT INTO routing_rules_src (`type`,`gateway`,`pattern`,`ruleid`,`nic`,`metric`,`zOrder`,`status`)
		VALUES('{$_POST["stype"]}','{$_POST["gateway"]}','{$_POST["pattern"]}','{$_POST["ruleid"]}','{$_POST["nic"]}','{$_POST["metric"]}','{$_POST["zOrder"]}','1');";
	}else{
		$sql="UPDATE routing_rules_src SET `metric`='{$_POST["metric"]}', 
		`zOrder`='{$_POST["zOrder"]}', `type`='{$_POST["stype"]}',
		`pattern`='{$_POST["pattern"]}',
		`gateway`='{$_POST["gateway"]}' WHERE `ID`='$ID'";
	
	}

	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    $function2="";if(isset($_GET["function2"])){$function2=$_GET["function2"];}
	$MAIN_ID=$_GET["table"];
	$type=$tpl->_ENGINE_parse_body("{type}");
	$gateway=$tpl->_ENGINE_parse_body("{next_hope}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$order=$tpl->javascript_parse_text("{order}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
    $export=$tpl->_ENGINE_parse_body("{export}");
    $import=$tpl->_ENGINE_parse_body("{import}");


    $topbuttons[] = array("Loadjs('$page?rule-js=0&ruleid=$MAIN_ID&function=$function&function2=$function2');", ico_plus, $new_rule);
    $topbuttons[] = array("Loadjs('$page?export-js=true&ruleid=$MAIN_ID&function=$function&function2=$function2');", ico_export, $export);
    $topbuttons[] = array("Loadjs('$page?import-js=true&ruleid=$MAIN_ID&function=$function&function2=$function2');", ico_import, $import);
    $buttons=base64_encode($tpl->th_buttons($topbuttons));


	$html[]="</div>";
	$html[]="<table id='table-destsrc-{$MAIN_ID}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$items</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$type</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$gateway</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>mv</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	$sql="SELECT *  FROM `routing_rules_src` WHERE ruleid='$MAIN_ID' ORDER BY zOrder";

    if(isset($_GET["search"])){
        $search=$_GET["search"];
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT *  FROM `routing_rules_src` WHERE ruleid='$MAIN_ID' 
        AND ( pattern LIKE '$search' OR gateway LIKE '$search' ) ORDER BY zOrder";

    }

	$results = $q->QUERY_SQL($sql);

	$TRCLASS=null;
	$types[1]=$tpl->_ENGINE_parse_body("{network_nic}");
	$types[2]=$tpl->_ENGINE_parse_body("{host}");
	$types[3]=$tpl->_ENGINE_parse_body("NAT");
	$types[4]=$tpl->_ENGINE_parse_body("{blackhole}");
	$types[5]=$tpl->_ENGINE_parse_body("{iprouteprohibit}");

	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		
		$md=md5(serialize($ligne));
		$js="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?rule-js={$ligne["ID"]}&ruleid=$MAIN_ID');\" style='text-decoration:underline'>";
		
		$down=$tpl->icon_down("Loadjs('$page?route-move-js={$ligne["ID"]}&ruleid=$MAIN_ID&dir=down');","AsSystemAdministrator");
		$up=$tpl->icon_up("Loadjs('$page?route-move-js={$ligne["ID"]}&ruleid=$MAIN_ID&dir=up');","AsSystemAdministrator");
		$delete=$tpl->icon_delete("Loadjs('$page?route-delete-js={$ligne["ID"]}&ruleid=$MAIN_ID&md=$md');","AsSystemAdministrator");
		
		$metric=intval($ligne["metric"]);
		if($metric>0){$prio=" prio {$ligne["metric"]}";}
		
		if($ligne["gateway"]==null){$ligne["gateway"]=$tpl->icon_nothing();}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' class='center' nowrap>{$ligne["zOrder"]}</center></td>";
		$html[]="<td>$js{$ligne["pattern"]}</a></td>";
		$html[]="<td style='width:1%' nowrap>$js{$types[$ligne["type"]]}</a></td>";
		$html[]="<td style='width:1%' nowrap>{$ligne["gateway"]}</td>";
		$html[]="<td style='width:1%' nowrap>$up&nbsp;$down</td>";
		$html[]="<td style='width:1%' nowrap>$delete</td>";
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
	document.getElementById('buttons-source-$MAIN_ID').innerHTML=base64_decode('$buttons');
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-destsrc-{$MAIN_ID}').footable( { 	\"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function export_rules_js(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{export}");
    $ruleid=intval($_GET["ruleid"]);
    $t=$_GET["t"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->mysqli_fetch_array("SELECT `RouteName` FROM routing_rules WHERE ID='$ruleid'");
    $RouteName=$ligne["RouteName"];
    $tpl->js_dialog2("$RouteName: $title", "$page?export-popup=true&ruleid=$ruleid");

}

function export_rules_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=intval($_GET["ruleid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->QUERY_SQL("SELECT `type`,`gateway`,`pattern`,`status`,`metric`,`zOrder` FROM routing_rules_src WHERE ruleid='$ruleid'");
    $title=$ligne["pattern"];
    if(!$q->ok){echo $q->mysql_error_html();}
    $btname="{copy}";
    $sqldata="";
    foreach ($ligne as $index=>$res){
        $sqldata .= "{$res["type"]}|{$res["gateway"]}|{$res["pattern"]}|{$res["status"]}|{$res["metric"]}|{$res["zOrder"]}\n";
    }

    $form[]=$tpl->field_hidden("ruleidexport", "{$_GET["ruleid"]}");
    $form[]=$tpl->field_textarea("routing_rules_src_export","{routing_rules}",$sqldata);
    $form[]="<script>$( document ).ready(function() { var id=getTAID();$('#'+id).attr('readonly','readonly');});function getTAID(){return $('textarea').attr('id');}</script>";
    $js[]="id=getTAID();console.log(id);copyToClipboard(id);";
    $js[]="dialogInstance2.close();";
    $html=$tpl->form_outside("{export} {routing_rules} ", @implode("\n", $form),null,$btname,@implode("", $js),"AsSystemAdministrator");
    echo $html;

}

function import_rules_js(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{import}");
    $ruleid=intval($_GET["ruleid"]);
    $t=$_GET["t"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->mysqli_fetch_array("SELECT `RouteName` FROM routing_rules WHERE ID='$ruleid'");
    $RouteName=$ligne["RouteName"];
    $tpl->js_dialog2("$RouteName: $title", "$page?import-popup=true&ruleid=$ruleid");

}

function import_rules_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=intval($_GET["ruleid"]);
    $btname="{import}";
    $form[]=$tpl->field_hidden("ruleidimport", "{$_GET["ruleid"]}");
    $form[]=$tpl->field_textarea("routing_rules_src_import","{routing_rules}");
    $js[]="dialogInstance2.close();";
    $js[]="LoadAjax('table-loader-iprule','fw.network.routing.php?table=yes');";
    $js[]="LoadAjaxSilent('sourcedest{$_GET["ruleid"]}','$page?table={$_GET["ruleid"]}');";
    $html=$tpl->form_outside("{import} {routing_rules} ", @implode("\n", $form),null,$btname,@implode("", $js),"AsSystemAdministrator");
    echo $html;

}

function import_rules_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    include_once(dirname(__FILE__)."/class.html.tools.inc");
    $RULEID=$_POST["ruleidimport"];

    if($_POST["pattern"]=="*"){$_POST["pattern"]="0.0.0.0/0";}

    $_POST["pattern"]=$tpl->CLEAN_BAD_CHARSNET($_POST["pattern"]);
    $_POST["gateway"]=$tpl->CLEAN_BAD_CHARSNET($_POST["gateway"]);
    if (empty($_POST["routing_rules_src_import"])) {
        echo $tpl->post_error("{empty} {routing_rules}");return false;
    }
    $data = urldecode($_POST["routing_rules_src_import"]);
    $data = preg_split("/\r\n|\r|\n/", $data);
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    foreach ($data as $rule)
    {
        $values = explode("|",$rule);
        $count = $q->mysqli_fetch_array("SELECT COUNT(*) as count FROM routing_rules_src WHERE pattern='{$values[2]}'");
        $nic= $q->mysqli_fetch_array("SELECT nic FROM routing_rules_src WHERE pattern='{$values[2]}'");
        $currentNic = $q->mysqli_fetch_array("SELECT nic FROM routing_rules_src WHERE ruleid='{$RULEID}'");
        if(intval($count["count"]) > 0 ) {
            echo $tpl->post_error("{duplicate} {routing_rules} {$values[2]} in {$nic["nic"]}");return false;
        }
        $sql="INSERT INTO routing_rules_src (`ruleid`,`type`,`gateway`,`pattern`,`status`,`nic`,`metric`,`zOrder`)
		VALUES('{$RULEID}','{$values[0]}','{$values[1]}','{$values[2]}','{$values[3]}','{$currentNic["nic"]}','{$values[4]}','{$values[5]}');";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);return;

        }
    }

}