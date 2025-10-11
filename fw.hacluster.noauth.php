<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){main();exit;}
if(isset($_GET["newitem-js"])){newitem_js();exit;}
if(isset($_GET["new-item-popup"])){newitem_popup();exit;}
if(isset($_POST["newitem"])){newitem_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){newitem_delete();exit;}




page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header(
        "{ActiveDirectory}&nbsp;&raquo;&nbsp;{authentication}&nbsp;&raquo;&nbsp;{whitelists}",
        "fa fa-align-justify",
        "{AUTHWHITELIST_EXPLAIN}",
        "$page?start=yes",
        "hacluster-white",
        "progress-ntlmwhite-restart",false,
        "table-ntlmwhite-rules"

    );




    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{ActiveDirectory} {authentication} {whitelists}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);

}

function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);
    return true;
}

function newitem_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $function=$_GET["function"];
	$title=$tpl->javascript_parse_text("{new_item}");
	return $tpl->js_dialog3($title, "$page?new-item-popup=yes&function=$function");
	
}


function delete_js():bool{
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$id=$_GET["delete-js"];
	$ask=$tpl->javascript_parse_text("{delete} $id");

	$html="			
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}	
	$('#{$_GET["id"]}').remove();
	}
function Save$t(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$id');
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
	
Save$t()";
	echo $html;
	return true;
}



function newitem_popup():bool{
    $function=$_GET["function"];
    $tpl=new template_admin();
    $tt[]="dialogInstance3.close();";
    $tt[]="$function();";

    $js=@implode("",$tt);
    $form[]=$tpl->field_text("newitem", "{network_item}", null);
	$html[]=$tpl->form_outside(null,  $form,"{pdns_network_item_add}","{add}", $js,"AsProxyMonitor");
	echo $tpl->_ENGINE_parse_body( $html);
    return true;
}
function enable_js():bool{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $item=$_GET["enable-js"];
    $ligne=$q->mysqli_fetch_array("SELECT enabled from hacluster_noauth WHERE address='$item'");
    $enabled=intval($ligne["enabled"]);
    if($enabled==0){
        $q->QUERY_SQL("UPDATE hacluster_noauth SET enabled=1 WHERE address='$item'");
        if(!$q->ok){
            return $tpl->js_error($q->mysql_error);
        }
        return admin_tracks("Set $item to active from hacluster whitelist authentication");
    }
    $q->QUERY_SQL("UPDATE hacluster_noauth SET enabled=0 WHERE address='$item'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/build");
    return admin_tracks("Set $item to inactive from hacluster whitelist authentication");

}
function newitem_save():bool{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$item=url_decode_special_tool($_POST["newitem"]);
    $item=$tpl->CLEAN_BAD_CHARSNET($item);

	if($item==null){
		echo $tpl->post_error("No posted data");
		return false;
	}
	$sql="INSERT OR IGNORE INTO hacluster_noauth (`address`) VALUES('$item')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/build");
    return admin_tracks_post("Adding Hacluster whitelist address from authentication");
}
function newitem_delete():bool{
    $tpl=new template_admin();
	$item=url_decode_special_tool($_POST["delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
	$q->QUERY_SQL("DELETE FROM hacluster_noauth WHERE address='$item'");
	if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/build");
    return admin_tracks("Remove $item from Hacluster whitelist address from authentication");

}
function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $function=$_GET["function"];


    /*
    $jsrestart=$tpl->framework_buildjs("pdns.php?restart-recusor=yes",
    "recusor.restart.progress",
    "recusor.restart.log",
    "progress-recursor-restart",
    "document.getElementById('progress-recursor-restart').innerHTML='';");
    */

    $delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
    $DelAdd="";

    $topbuttons[] = array("Loadjs('$page?newitem-js=yes&function=$function')", ico_plus, $new_entry);

    $buttons=$tpl->table_buttons($topbuttons);
	$html[]="<table id='table-dns-forward-zones' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";


	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$items</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$delete</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	$sql="SELECT * FROM hacluster_noauth";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }
    if(!is_array($results)){
        $results=array();
    }

	$icocomp=ico_computer;

	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$item=$ligne["address"];
        $enabled=$ligne["enabled"];
		$item_encode=urlencode($item);

        $enable=$tpl->icon_check($enabled,"Loadjs('$page?enable-js=$item_encode&function=$function')");

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong id='$index'><i class='$icocomp'></i>&nbsp;$item</strong></td>";
        $html[]="<td style='width:1%'>$enable</td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_delete("Loadjs('$page?delete-js=$item_encode&id=$md$DelAdd')","AsDnsAdministrator")."</td>";
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

    $TINY_ARRAY["TITLE"]="{ActiveDirectory}&nbsp;&raquo;&nbsp;{authentication}&nbsp;&raquo;&nbsp;{whitelists}";
    $TINY_ARRAY["ICO"]="fa fa-align-justify";
    $TINY_ARRAY["EXPL"]="{AUTHWHITELIST_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$buttons;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";





	$html[]="
<script>
    $jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-dns-forward-zones').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
</script>";

echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}