<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
//$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["single-disable"])){single_disable();exit;}
if(isset($_GET["single-enable"])){single_enable();exit;}

if(isset($_GET["browse-js"])){browse_js();exit;}
if(isset($_GET["browse-popup"])){browse_popup();exit;}
if(isset($_GET["browse-search"])){browse_search();exit;}

if(isset($_GET["ids-rules-enabled"])){count_of_enabled();exit;}
if(isset($_GET["filter-categories-js"])){filter_categories();exit;}
if(isset($_GET["filter-categories-popup"])){filter_categories_popup();exit;}

if(isset($_GET["filter-classifications-js"])){filter_classifications();exit;}
if(isset($_GET["filter-classifications-popup"])){filter_classifications_popup();exit;}

if(isset($_GET["disable-all-js"])){disable_all_js();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["enable"])){enable_all_rules();exit;}
if(isset($_GET["disable"])){disable_all_rules();exit;}



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
function browse_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $enabled=intval($_GET["browse-js"]);
    $classtype=$_GET["classtype"];
    $family=$_GET["family"];
    return $tpl->js_dialog1("$classtype/$family","$page?browse-popup=$enabled&classtype=$classtype&family=$family");
}
function browse_popup():bool{
    $enabled=intval($_GET["browse-popup"]);
    $classtype=$_GET["classtype"];
    $family=$_GET["family"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,"","","","&browse-search=$enabled&classtype=$classtype&family=$family&enabled=$enabled");
    return true;
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
    $tpl=new template_admin();
    $classtype=$_GET["classtype"];
    $family=$_GET["family"];
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");
    $q->QUERY_SQL("UPDATE rules SET enabled=1 WHERE classtype='$classtype' AND source_file='$family'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/categories/build");
    header("content-type: application/x-javascript");
    echo jsArray($classtype,$family);
    return admin_tracks("Enable all IDS rules from category $classtype/$family");
}
function count_of_enabled():bool{
    $tpl=new template_admin();
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT SUM(enabled) as tsum FROM suricata_categories");

    $f=$tpl->FormatNumber($ligne["tsum"])." {rules}";
    $f="<small>($f)</small>";
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}

function disable_all_rules():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();
    $classtype=$_GET["classtype"];
    $family=$_GET["family"];
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");
    $q->QUERY_SQL("UPDATE rules SET enabled=0 WHERE classtype='$classtype' AND source_file='$family'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/categories/build");
    header("content-type: application/x-javascript");
    echo jsArray($classtype,$family);
    return admin_tracks("Disable all IDS rules from category $classtype/$family");

}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{IDS} {categories}",
        ico_books,
        "{ids_categories_explain}",
        "$page?search=yes",
        "ids-categories","progress-idscategories-restart",
        false,"main-rules-categories-section");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{IDS}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);


}
function browse_search():bool
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $enabled = intval($_GET["browse-search"]);
    $classtype = $_GET["classtype"];
    $family = $_GET["family"];
    $t=time();
    $html[] = "<table id='table-suricata-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{rule}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize center' data-type='text'></center></th>";
    $html[] = "</thead>";
    $html[] = "<tbody>";

    $q = new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");


    $search=$_GET["search"];
    if(strlen($search)>2){
        $SerchOrg=trim($search);
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $searchq="AND msg LIKE '$search'";
        if(intval($SerchOrg)>0){
            $searchq="AND sid='$SerchOrg'";
        }
    }
    $prios[0]="<span class='label label-default'>{none}</span>";
    $prios[1]="<span class='label label-danger'>{ids_prio_1}</span>";
    $prios[2]="<span class='label label-warning'>{medium}</span>";
    $prios[3]="<span class='label label-info'>{low}</span>";
    $prios[4]="<span class='label label-default'>{info}</span>";
    $td1prc="style='vertical-align:middle;width=1%' class='center' nowrap";
    $sql="SELECT * FROM rules WHERE classtype='$classtype' AND source_file='$family' AND enabled=$enabled $searchq ORDER BY sid LIMIT 512";

    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $TRCLASS=null;
    foreach ($results as $index=>$ligne) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $text_class=null;
        $description=$ligne["msg"];
        $priority=intval($ligne["priority"]);
        $id=$ligne["sid"];
        $md=md5(__FUNCTION__.serialize($ligne));

        $jsAction="Loadjs('$page?single-enable=$id&classtype=$classtype&family=$family&md=$md')";
        $btn="<label class=\"btn btn-primary btn-xs\" OnClick=\"$jsAction\">{enable}</label>";

        if($enabled==1){
            $jsAction="Loadjs('$page?single-disable=$id&classtype=$classtype&family=$family&md=$md')";
            $btn="<label class=\"btn btn-danger btn-xs\" OnClick=\"$jsAction\">{disable}</label>";
        }

        $idF=$tpl->td_href($id,"","Loadjs('$page?rule-js=$id')");
        $label=$prios[$priority];
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"$text_class\">$idF&nbsp;&nbsp;$label</td>";
        $html[]="<td class='$text_class' style='vertical-align:middle;width:100%'>$description</td>";
        $html[]="<td $td1prc>$btn</td>";
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
	$(document).ready(function() { $('#table-suricata-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;

}



function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();

    $html[]="<table id='table-suricata-all-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{categories}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{families}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{enabled}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{rules}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{action}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	$q=new postgres_sql();
	$sql="SELECT * FROM suricata_categories ORDER BY available DESC";

	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error."<br>$sql");
        return false;
    }


    $prios[0]="<span class='label label-default'>{none}</span>";
    $prios[1]="<span class='label label-danger'>{ids_prio_1}</span>";
    $prios[2]="<span class='label label-warning'>{medium}</span>";
    $prios[3]="<span class='label label-info'>{low}</span>";
    $prios[4]="<span class='label label-default'>{info}</span>";

	$TRCLASS=null;
    while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $classtype=$ligne["classtype"];
        $source_file=$ligne["source_file"];


        $Core=td_core($ligne);
        $id=$Core["IDCL"];
        $enabled=$Core["INNER_ENABLED"];
        $availableUri=$Core["INNER_AIVAILABLE"];
        $btns=$Core["INNNER_BTNS"];
        $text_class=$Core["TEXT_CLASS"];
        $td1prc=$Core["td1prc"];
        $td1prcR=$Core["td1prcR"];

        $html[]="<tr class='$TRCLASS'>";
		$html[]="<td id='$id-cl' class=\"$text_class\" style='vertical-align: top' nowrap>$classtype</td>";
		$html[]="<td id='$id-src' class='$text_class' style='vertical-align:middle;width:99%'>{{$source_file}}</td>";
        $html[]="<td id='$id-ena' $td1prcR><span id='$id-url1'>$enabled</span></td>";
		$html[]="<td id='$id-aiv' $td1prcR><span id='$id-url2'>$availableUri</span></td>";
		$html[]="<td $td1prc><span id='$id-url3'>$btns</span></td>";
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

    $jscompile=  $tpl->framework_buildjs(
        "suricata:/build/rules",
        "dumprules.progress",
        "dumprules.progress.txt","progress-idscategories-restart"
    );

    $topbuttons[] = array($jscompile,ico_save,"{apply_changes}");
    $t=time();
    $TINY_ARRAY["TITLE"]="{IDS} {categories} <span id='ids-rules-$t'><span class='fa fa-refresh fa-spin'></span></span>";
    $TINY_ARRAY["ICO"]=ico_books;
    $TINY_ARRAY["EXPL"]="{ids_categories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

   $tt=$tpl->RefreshInterval_js("ids-rules-$t","$page","ids-rules-enabled=$t");

	$html[]="
	<script>
	$tt
	$headsjs
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-suricata-all-rules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

			echo $tpl->_ENGINE_parse_body(implode("\n",$html));
return true;
}

function single_disable():bool{

    $id=$_GET["single-disable"];
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");
    $q->QUERY_SQL("UPDATE rules SET enabled=0 WHERE sid='$id'");
    if(!$q->ok){
        return false;
    }
    $classtype=$_GET["classtype"];
    $family=$_GET["family"];
    $md=$_GET["md"];


    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/categories/build");
    header("content-type: application/x-javascript");
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM suricata_categories WHERE classtype='$classtype' AND source_file='$family'");
    $Array=td_core($ligne);
    echo "// [$id] - - $classtype/$family\n";
    echo "$('#$md').remove();\n";
    echo $Array["JS"];
    return admin_tracks("Disable IDS rule $id from category $classtype/$family");
}
function single_enable():bool{

    $id=$_GET["single-enable"];
    $q=new lib_sqlite("/home/artica/SQLITE/suricata-rules.db");
    $q->QUERY_SQL("UPDATE rules SET enabled=1 WHERE sid='$id'");
    if(!$q->ok){
        return false;
    }
    $classtype=$_GET["classtype"];
    $family=$_GET["family"];
    $md=$_GET["md"];


    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/categories/build");
    header("content-type: application/x-javascript");
    echo "// [$id] - - $classtype/$family\n";
    echo "$('#$md').remove();\n";
    echo jsArray($classtype,$family);
    return admin_tracks("Enable IDS rule $id from category $classtype/$family");
}
function jsArray($classtype,$family):string{
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM suricata_categories 
         WHERE classtype='$classtype' AND source_file='$family'");
    $Array=td_core($ligne);
    return $Array["JS"];
}

function td_core($ligne):array{
    $text_class="font-bold";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $enabled="";
    $classtype=$ligne["classtype"];
    $source_file=$ligne["source_file"];
    $enablediNT=intval($ligne["enabled"]);
    $available=intval($ligne["available"]);
    $availableUri = $tpl->td_href($available, "", "Loadjs('$page?browse-js=0&classtype=$classtype&family=$source_file')");
    $btn=array();
    if($enablediNT==0){
        $text_class="text-muted";
    }else{
        $enabled=$tpl->td_href($enablediNT,"","Loadjs('$page?browse-js=1&classtype=$classtype&family=$source_file')");
    }

    if($enablediNT>0){
        $jsAction="Loadjs('$page?disable=yes&classtype=$classtype&family=$source_file')";
        $btn[]="<label class=\"btn btn-warning btn-xs\" OnClick=\"$jsAction\">{disable_all}</label>";
    }
    if($available>$enablediNT){
        $jsAction="Loadjs('$page?enable=yes&classtype=$classtype&family=$source_file')";
        $btn[]="<label class=\"btn btn-default btn-xs\" OnClick=\"$jsAction\">{enable_all}</label>";
    }

    $btns=$tpl->_ENGINE_parse_body(implode("&nbsp;|&nbsp;",$btn));
    $td1prc="style='vertical-align:middle;width=1%' class='center' nowrap";
    $td1prcR="style='vertical-align:middle;width=1%;text-align:right' class=\"$text_class\" nowrap";

    $id=md5("$classtype$source_file");


    $enabledEnc=base64_encode($tpl->_ENGINE_parse_body($enabled));
    $availableUriEnc=base64_encode($tpl->_ENGINE_parse_body($availableUri));
    $btnsEnc=base64_encode($tpl->_ENGINE_parse_body($btns));
    $js[]="if ( document.getElementById('$id-cl') ){";
    $js[]="document.getElementById('$id-cl').className='$text_class';";
    $js[]="document.getElementById('$id-src').className='$text_class';";
    $js[]="document.getElementById('$id-ena').className='$text_class';";
    $js[]="document.getElementById('$id-aiv').className='$text_class';";
    $js[]="document.getElementById('$id-url1').innerHTML=base64_decode('$enabledEnc');";
    $js[]="document.getElementById('$id-url2').innerHTML=base64_decode('$availableUriEnc');";
    $js[]="document.getElementById('$id-url3').innerHTML=base64_decode('$btnsEnc');";
    $js[]="}else{";
    $js[]="alert('$classtype/$source_file $id-cl not found');";
    $js[]="}";

    return array(
        "INNER_ENABLED"=>$enabled,
        "INNER_AIVAILABLE"=>$availableUri,
        "INNNER_BTNS"=>$btns,
        "TEXT_CLASS"=>$text_class,
        "td1prc"=>$td1prc,
        "td1prcR"=>$td1prcR,
        "IDCL"=>$id,
        "JS"=>implode("\n",$js),
    );
}
