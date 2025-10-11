<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["main"])){main();exit;}
if(isset($_GET["newitem-js"])){newitem_js();exit;}
if(isset($_GET["new-item-popup"])){newitem_popup();exit;}
if(isset($_POST["newitem"])){newitem_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){newitem_delete();exit;}
page();



function newitem_js(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_item}");
	$tpl->js_dialog($title, "$page?new-item-popup=yes");
	
}

function delete_js(){
	
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
	
}



function newitem_popup(){
		$page=CurrentPageName();
		$tpl=new template_admin();
        $form[]=$tpl->field_text("newitem", "{network_item}", null);
		$html[]=$tpl->form_outside("{new_item}", @implode("\n", $form),"{pdns_network_item_add}","{add}",
				"LoadAjax('table-loader-recursor','$page?main=yes');BootstrapDialog1.close();","AsDnsAdministrator");
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function newitem_save():bool{
    $tpl=new template_admin();
    $ActiveDirectoryRestRestrict=explode("\n",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestRestrict"));
    $item=url_decode_special_tool($_POST["newitem"]);
    $item=$tpl->CLEAN_BAD_CHARSNET($item);

    if($item==null){
        echo "No posted data\n";
        return false;
    }
    if(!is_array($ActiveDirectoryRestRestrict)){$ActiveDirectoryRestRestrict=array();}
    $ActiveDirectoryRestRestrict[]=$item;

    foreach ($ActiveDirectoryRestRestrict as $items){
        $newitels[$items]=$items;
    }
    $fins=array();
    foreach ($newitels as $item){
        $fins[]=$item;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryRestRestrict",@implode("\n",$fins));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reload");

    return admin_tracks("Web API service: Add new allowed node $item");

}
function newitem_delete():bool{
	$item=url_decode_special_tool($_POST["delete"]);
    $ActiveDirectoryRestRestrict=explode("\n",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestRestrict"));
    $newitels=array();
    foreach ($ActiveDirectoryRestRestrict as $items){
        $newitels[$items]=$items;
    }
    unset($newitels[$item]);
    $fins=array();
    foreach ($newitels as $item){
        $fins[]=$item;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryRestRestrict",@implode("\n",$fins));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reload");
    return admin_tracks("Web API service: Addin removing allowed node $item");
}

function page(){
	$page=CurrentPageName();
	$html="<div id='progress-recursor-restart' style='margin-top:10px'></div>
	<div id='table-loader-recursor'></div>
	<script>
	LoadAjax('table-loader-recursor','$page?main=yes');
	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");


    $topbuttons[] = array("Loadjs('$page?newitem-js=yes')",ico_plus,$new_entry);


	$html[]="<table id='table-dns-forward-zones' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";


	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$items}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{$delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


    $resultsTemp=explode("\n",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestRestrict"));
    if(!is_array($resultsTemp)){$resultsTemp=array();}
    $results=array();
    foreach ($resultsTemp as $line){
        if(trim($line)==null){continue;}
        $results[]=$line;

    }

	$c=0;

    VERBOSE("COunt(results)=".count($results),__LINE__);

	if(count($results)==0){
        $ACLREST[] = "192.168.0.0/16";
        $ACLREST[] = "10.0.0.0/8";
        $ACLREST[] = "172.16.0.0/12";
        $ACLREST[] = "127.0.0.0/8";

        foreach ($ACLREST as $address){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $md=md5(serialize($address));
            $item=$address;
            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td><strong>$item</strong></td>";
            $html[]="<td style='vertical-align:middle' width=1%></td>";
            $html[]="</tr>";
        }

    }

	foreach ($results as $index=>$item){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($item));
		$item_encode=urlencode($item);
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>$item</strong></td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center'>".$tpl->icon_delete("Loadjs('$page?delete-js=$item_encode&id=$md')","AsDnsAdministrator")."</td>";
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

    $TINY_ARRAY["TITLE"]="{SQUID_AD_RESTFULL}";
    $TINY_ARRAY["ICO"]="fad fa-monitor-heart-rate";
    $TINY_ARRAY["EXPL"]="{PowerDNS-allow-from}";
    $TINY_ARRAY["URL"]="ad-webapi";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."$jstiny
	
</script>";

echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}