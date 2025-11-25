<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
//$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["ids-rules-stats"])){top_rules_stats();exit;}
if(isset($_GET["filter-categories-js"])){filter_categories();exit;}
if(isset($_GET["filter-categories-popup"])){filter_categories_popup();exit;}

if(isset($_GET["filter-classifications-js"])){filter_classifications();exit;}
if(isset($_GET["filter-classifications-popup"])){filter_classifications_popup();exit;}

if(isset($_GET["disable-all-js"])){disable_all_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["enable-all-rules"])){enable_all_rules();exit;}
if(isset($_GET["disable-all-rules"])){disable_all_rules();exit;}
if(isset($_GET["enable-all-family"])){enable_all_family();exit;}
if(isset($_GET["disable-all-family"])){disable_all_family();exit;}

page();

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sid=intval($_GET["rule-js"]);
    return $tpl->js_dialog2("$sid","$page?rule-popup=$sid");
}
function rule_popup():bool{
    $id=$_GET["rule-popup"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM rules WHERE sid='$id'");

    $tpl->table_form_field_text("{signature}",$ligne["sid"],ico_script);
    $tpl->table_form_field_text("{description}",$ligne["msg"],ico_infoi);
    $tpl->table_form_field_bool("{enabled}",$ligne["enabled"],ico_check);
        $tpl->table_form_field_text("{category}",$ligne["classtype"]."/".$ligne["source_file"],ico_books);
    $tpl->table_form_field_text("{src}",$ligne["src_addr"],ico_networks);
    $tpl->table_form_field_text("{dst}",$ligne["dst_addr"],ico_networks);
    $html[]=$tpl->table_form_compile();
    $html[]="<div style='margin-top:20px'><textarea spellcheck='false' autocomplete='off'
  style=\"width:100%;min-height:220px;padding:12px 14px;border:1px solid #d1d5db;border-radius:8px;outline:none;
         font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono','Courier New', monospace;
         font-size:16px;line-height:1.5;color:#111827;background:#ffffff;
         white-space:pre-wrap;word-wrap:break-word;overflow:auto;resize:vertical;tab-size:4;caret-color:#2563eb;\">{$ligne["raw"]}</textarea></div>";

echo $tpl->_ENGINE_parse_body(implode("\n",$html));
return true;
}
function enable_signature():bool{
	$t=time();
	$id=$_GET["enable-signature"];
    $tpl=new template_admin();
	$page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");
    $ligne=$q->mysqli_fetch_array("SELECT sid,enabled FROM rules WHERE sid='$id'");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    $enabled=intval($ligne["enabled"]);
    $sid=intval($ligne["sid"]);
    if($enabled==0){$enabled=1;}else{$enabled=0;}
    if($sid==0){
        echo $tpl->js_error("{error} ID $id not found");
        return false;
    }
    $q->QUERY_SQL("UPDATE rules SET enabled='$enabled' WHERE sid='$id'");



    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT sid FROM suricata_rules_conf WHERE sid='$id'"));
	if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
	$ssid=intval($ligne["sid"]);
    if($ssid==0){
        $q->QUERY_SQL("INSERT INTO suricata_rules_conf(sid,enabled) VALUES('$id','$enabled')");
    }else{
        $q->QUERY_SQL("UPDATE suricata_rules_conf SET enabled='$enabled' WHERE sid='$id'");
    }
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}

    return admin_tracks("Enable/Disable IDS rule $id enabled=$enabled");

}
function enable_firewall(){
	$t=time();
	$id=$_GET["enable-firewall"];
	$rulefile=$_GET["cat"];
	$page=CurrentPageName();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM suricata_sig WHERE signature='$id'"));
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}
	$tpl=new template_admin();
	
	if($ligne["firewall"]==0){
		$q->QUERY_SQL("UPDATE suricata_sig SET firewall=1 WHERE signature='$id'");
		if(!$q->ok){echo "alert('$q->mysql_error')";return;}
	
		echo "
		document.getElementById('firewall-$id').className= 'fas fa-check-square-o';
	
		";
		return;
	}
	
	$q->QUERY_SQL("UPDATE suricata_sig SET firewall=0 WHERE signature='$id'");
			if(!$q->ok){echo "alert('$q->mysql_error')";return;}
			echo "
			document.getElementById('firewall-$id').className= 'fa fa-square-o';
	
			";	
	
	
}
function filter_categories():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tpl=new template_admin();
    return $tpl->js_dialog1("{categories}", "$page?filter-categories-popup=yes&function=$function");
}
function filter_classifications():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tpl=new template_admin();
    return $tpl->js_dialog1("{categories}", "$page?filter-classifications-popup=yes&function=$function");
}
function enable_all_rules():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tpl=new template_admin();
    $cat=$_GET["enable-all-rules"];
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");

    $q->QUERY_SQL("UPDATE rules SET enabled=1 WHERE classtype='$cat'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }

    header("content-type: application/x-javascript");
    echo $function."()\n";

    return admin_tracks("Enable all IDS rule from category $cat");
}
function disable_all_js():bool{
    $function=$_GET["function"];
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");
    $q->QUERY_SQL("UPDATE rules SET enabled=0");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo $function."()\n";
    return admin_tracks("Disable all IDS rules from all categories.");
}
function disable_all_rules():bool{

    $function=$_GET["function"];
    $tpl=new template_admin();
    $cat=$_GET["disable-all-rules"];
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");

    $q->QUERY_SQL("UPDATE rules SET enabled=0 WHERE classtype='$cat'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }

    header("content-type: application/x-javascript");
    echo $function."()\n";

    return admin_tracks("Disable all IDS rules from category $cat");
}
function enable_all_family():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tpl=new template_admin();
    $cat=$_GET["enable-all-family"];
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");

    $q->QUERY_SQL("UPDATE rules SET enabled=1 WHERE source_file='$cat'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }

    header("content-type: application/x-javascript");
    echo $function."()\n";

    return admin_tracks("Enable all IDS rule from category $cat");
}
function disable_all_family():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tpl=new template_admin();
    $cat=$_GET["enable-all-family"];
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");

    $q->QUERY_SQL("UPDATE rules SET enabled=0 WHERE source_file='$cat'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }

    header("content-type: application/x-javascript");
    echo $function."()\n";

    return admin_tracks("Disable all IDS rule from category $cat");
}

function filter_categories_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $jsonStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$jsonStatus->Status){
        $html[]=$tpl->div_error("{error} API||$jsonStatus->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return false;
    }
    $GlobalConfig=$jsonStatus->Info;
    $catz=$GlobalConfig->Categories;
    $td1prc="style='vertical-align:middle;width=1%;text-align:right' nowrap";

    $TRCLASS=null;
    $html[]="<table id='table-categories-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='text-align:right'>{records}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'></center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap></center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap></center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    foreach ($catz as $cat=>$records){
        $select="LoadAjaxSilent('main-rules-section','$page?table=yes&category=$cat');dialogInstance1.close();";
        $enable_all="Loadjs('$page?enable-all-rules=$cat&function=$function');";
        $disable_all="Loadjs('$page?disable-all-rules=$cat&function=$function');";


        $button_select="<button OnClick=\"$select\" class='btn btn-primary btn-xs' type='button'>{search}</button>";
        $button_enable="<button OnClick=\"$enable_all\" class='btn btn-primary btn-xs' type='button'>{enable_all}</button>";
        $button_disable="<button OnClick=\"$disable_all\" class='btn btn-danger btn-xs' type='button'>{disable_all}</button>";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $text_class=null;

        $records=$tpl->FormatNumber($records);
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\">$cat</td>";
        $html[]="<td $td1prc >$records</td>";
        $html[]="<td style='width: 1%' nowrap>$button_select</td>";
        $html[]="<td style='width: 1%' nowrap>$button_enable</td>";
        $html[]="<td style='width: 1%' nowrap>$button_disable</td>";
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
	$(document).ready(function() { $('#table-categories-rules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));

    return true;
}
function filter_classifications_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $jsonStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$jsonStatus->Status){
        $html[]=$tpl->div_error("{error} API||$jsonStatus->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return false;
    }
    $GlobalConfig=$jsonStatus->Info;

    $catz=$GlobalConfig->Families;
    $td1prc="style='vertical-align:middle;width=1%;text-align:right' nowrap";

    $TRCLASS=null;
    $html[]="<table id='table-categories-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{group}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='text-align:right'>{records}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'></center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap></center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap></center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    foreach ($catz as $cat=>$records){
        if($cat=="whitelist.rules" OR $cat=="emerging-deleted.rules"){
            continue;
        }
        $select="LoadAjaxSilent('main-rules-section','$page?table=yes&family=$cat');dialogInstance1.close();";
        $enable_all="Loadjs('$page?enable-all-family=$cat&function=$function');";
        $disable_all="Loadjs('$page?disable-all-family=$cat&function=$function');";
        $button_select="<button OnClick=\"$select\" class='btn btn-primary btn-xs' type='button'>{search}</button>";
        $button_enable="<button OnClick=\"$enable_all\" class='btn btn-primary btn-xs' type='button'>{enable_all}</button>";
        $button_disable="<button OnClick=\"$disable_all\" class='btn btn-danger btn-xs' type='button'>{disable_all}</button>";



        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $text_class=null;

        $records=$tpl->FormatNumber($records);
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\"><strong>$cat</strong><br>{{$cat}}</td>";
        $html[]="<td $td1prc >$records</td>";
        $html[]="<td style='width: 1%' nowrap>$button_select</td>";
        $html[]="<td style='width: 1%' nowrap>$button_enable</td>";
        $html[]="<td style='width: 1%' nowrap>$button_disable</td>";
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
	$(document).ready(function() { $('#table-categories-rules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));

    return true;
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{IDS} {rules}",
        "fa fa-list",
        "{ids_rules_explain}",
        "$page?table=yes",
        "ids-rules","progress-ids-restart",false,"main-rules-section");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{IDS}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);


}
function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category="";$family="";
    if(isset($_GET["category"])) {
        $category = $_GET["category"];
    }
    if(isset($_GET["family"])) {
        $family = $_GET["family"];
    }
    echo $tpl->search_block($page,null,null,null,"&category-filter=$category&family-filter=$family",null);
    return true;
}
function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$firewall=$tpl->_ENGINE_parse_body("{firewall}");
    $function=$_GET["function"];
    $category=$_GET["category-filter"];
    $family=$_GET["family-filter"];

    $html[]="<table id='table-suricata-all-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{signature}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>$enabled</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $searchq="WHERE 1";
    $search=$_GET["search"];
    if(strlen($search)>2){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $searchq="WHERE msg LIKE '$search'";
    }
	
	$q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");
	$sql="SELECT * FROM rules $searchq ORDER BY sid LIMIT 500";
    if(strlen($category)>1){
        $sql="SELECT * FROM rules $searchq AND classtype='$category' ORDER BY sid LIMIT 500";
    }
    if(strlen($family)>1){
        $sql="SELECT * FROM rules $searchq AND source_file='$family' ORDER BY sid LIMIT 500";
    }
	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error."<br>$sql");
        return false;
    }
    $td1prc="style='vertical-align:middle;width=1%' class='center' nowrap";

    $prios[0]="<span class='label label-default'>{none}</span>";
    $prios[1]="<span class='label label-danger'>{ids_prio_1}</span>";
    $prios[2]="<span class='label label-warning'>{medium}</span>";
    $prios[3]="<span class='label label-info'>{low}</span>";
    $prios[4]="<span class='label label-default'>{info}</span>";

	$TRCLASS=null;
	foreach ($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$color="black";
        $classtype=$ligne["classtype"];
        $source_file=$ligne["source_file"];
        $description=$ligne["msg"];
        $priority=intval($ligne["priority"]);
		$id=$ligne["sid"];
		if($ligne["enabled"]==0){
			$color="#8a8a8a";
		}
        $bold="style='color:$color;font-weight:bold'";
        $idF=$tpl->td_href($id,"","Loadjs('$page?rule-js=$id')");
        $label=$prios[$priority];
        $html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\">$idF&nbsp;&nbsp;$label</td>";
		$html[]="<td class='$text_class' style='vertical-align:middle'>$description</td>";
        $html[]="<td class='$text_class' style='vertical-align:middle' nowrap>$classtype/$source_file</td>";
		$html[]="<td $td1prc>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-signature=$id')")."</td>";
		$html[]="<td $td1prc></td>";
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

    $jscompile=  $tpl->framework_buildjs(
        "suricata:/build/rules",
        "dumprules.progress",
        "dumprules.progress.txt","progress-ids-restart"
    );
    $topbuttons[] = array("Loadjs('$page?filter-categories-js=yes&function=$function')",ico_books,"{categories}");
    $topbuttons[] = array("Loadjs('$page?filter-classifications-js=yes&function=$function')",ico_folder,"{categories_groups}");


    $t=time();
    $topbuttons[] = array("Loadjs('$page?disable-all-js=yes&function=$function')",ico_trash,"{disable_all}");
    $topbuttons[] = array($jscompile,ico_save,"{apply_changes}");

    $TINY_ARRAY["TITLE"]="{IDS} {rules} <span id='ids-rules-$t'><span class='fa fa-refresh fa-spin'></span></span>";
    $TINY_ARRAY["ICO"]="fa fa-list";
    $TINY_ARRAY["EXPL"]="{ids_rules_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $tt=$tpl->RefreshInterval_Loadjs("ids-rules-$t","$page","ids-rules-stats=$t");

	$html[]="
	<script>
	$tt
	$headsjs
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-suricata-all-rules').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

			echo $tpl->_ENGINE_parse_body(implode("\n",$html));
return true;
}
function top_rules_stats():bool{
    $id=$_GET["ids-rules-stats"];
    $vid="ids-rules-$id";
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/rules/stats"));
    header("content-type: application/x-javascript");
    if(!$json->Status){
        $html=base64_encode("<br><span class='text-danger'>$json->Error</span>");
        echo "document.getElementById('$vid').innerHTML=base64_decode('$html');";
        return false;
    }
   $rules=$tpl->FormatNumber($json->rules_loaded);
   $html=base64_encode($tpl->_ENGINE_parse_body("<small class='font-bold'>$rules {rules_in_production}</small>"));
   echo "document.getElementById('$vid').innerHTML=base64_decode('$html');";
   return true;
}
function enable(){
	
	$filename=$_POST["filename"];
	$q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM suricata_rules_packages WHERE rulefile='$filename'");
	$enabled=intval($ligne["enabled"]);
	if($enabled==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL("UPDATE suricata_rules_packages SET `enabled`='$enabled' WHERE rulefile='$filename'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/build/acls");
}