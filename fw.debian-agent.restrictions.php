<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["newitem-js"])){newitem_js();exit;}
if(isset($_GET["new-item-popup"])){newitem_popup();exit;}
if(isset($_POST["newitem"])){newitem_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_confirm();exit;}
page();



function newitem_js(){

	$page=CurrentPageName();
	$tpl=new template_admin();
    $function=$_GET["function"];
	$title=$tpl->javascript_parse_text("{new_item}");
	$tpl->js_dialog6($title, "$page?new-item-popup=yes&function=$function");
	
}

function delete_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
	$md=$_GET["md"];
	$id=$_GET["delete-js"];
    return $tpl->js_confirm_delete($id, "delete",$id,"$('#$md').remove();");
}



function newitem_popup():bool{
		$tpl=new template_admin();
        $function=$_GET["function"];
        $html[]=$tpl->BigTextField("newitem","{network_item}","{pdns_network_item_add}","","dialogInstance6.close();$function();");
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return true;
}

function CleanedList():array{

    $Conf=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/params"),true);
    $results=array();
    if(isset($Conf["Params"]["security"]["ip_whitelist"]) && count($Conf["Params"]["security"]["ip_whitelist"])>0) {
        $results=$Conf["Params"]["security"]["ip_whitelist"];
    }
    foreach ($results as $IPs){
        $MAIN[$IPs]=true;
    }

    return $MAIN;
}

function newitem_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $item=$_POST["newitem"];
    $item=$tpl->CLEAN_BAD_CHARSNET($item);

    if($item==null){
        echo "No posted data\n";
        return false;
    }

    $MAIN=CleanedList();
    $MAIN[$item]=true;
    $New=array();
    foreach ($MAIN as $IP=>$None){
        $New[]=$IP;
    }
    $ip_whitelist=implode(",",$New);
    $ip_whitelist=urlencode($ip_whitelist);
    $uri="/debianagent/whitelists/$ip_whitelist";
    $GLOBALS["CLASS_SOCKETS"]->REST_API($uri);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/restart");
    return admin_tracks("Meta Agent: Add new allowed node $item");

}
function delete_confirm():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $MAIN=CleanedList();
    $item=$_POST["delete"];
    unset($MAIN[$item]);
    $New=array();
    foreach ($MAIN as $IP=>$None){
        $New[]=$IP;
    }
    $ip_whitelist=implode(",",$New);
    $ip_whitelist=urlencode($ip_whitelist);
    $uri="/debianagent/whitelists/$ip_whitelist";
    $GLOBALS["CLASS_SOCKETS"]->REST_API($uri);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/restart");
    return admin_tracks("Meta Agent: remove allowed node $item");
}

function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();
	echo "<div id='debagnt-top-buttons' style='margin-top:5px;margin-bottom:5px;'></div>";
    echo $tpl->search_block($page);
}
function top_buttons(){
    $function=$_GET["function"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $new_entry=$tpl->_ENGINE_parse_body("{new_item}");
    $topbuttons[] = array("Loadjs('$page?newitem-js=yes&function=$function')",ico_plus,$new_entry);
    echo $tpl->th_buttons($topbuttons);
}
function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $function=$_GET["function"];
	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");
    $TRCLASS=null;

    $html[]="<table id='table-dns-forward-zones' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$items</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>$delete</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $Conf=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/params"),true);
    $results=array();
    if(isset($Conf["Params"]["security"]["ip_whitelist"]) && count($Conf["Params"]["security"]["ip_whitelist"])>0) {
        $results=$Conf["Params"]["security"]["ip_whitelist"];
    }

	$c=0;
    VERBOSE("COunt(results)=".count($results),__LINE__);
	foreach ($results as $index=>$item){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($item));
		$item_encode=urlencode($item);
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>$item</strong></td>";
		$html[]="<td style='vertical-align:middle;width:1%'  class='center'>".$tpl->icon_delete("Loadjs('$page?delete-js=$item_encode&md=$md')","AsDnsAdministrator")."</td>";
		$html[]="</tr>";
		$c++;
	}
	
	if($c==0){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(time());
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>{PowerDNS-allow-from-default}</td>";
		$html[]="<td style='vertical-align:middle'></center></td>";
		$html[]="</tr>";
		
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="LoadAjaxSilent('debagnt-top-buttons','$page?top-buttons=yes&function=$function');";
    $html[]="</script>";

echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}