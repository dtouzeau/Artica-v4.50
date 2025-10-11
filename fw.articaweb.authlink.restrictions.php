<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["page"])){page();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["newitem-js"])){newitem_js();exit;}
if(isset($_GET["new-item-popup"])){newitem_popup();exit;}
if(isset($_POST["newitem"])){newitem_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){newitem_delete();exit;}


js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{clients_restrictions} (AUTH LINK)");
    $tpl->js_dialog($title, "$page?page=yes");

}

function newitem_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{clients_restrictions} (AUTH LINK): {new_item}");
	$tpl->js_dialog2($title, "$page?new-item-popup=yes");
	
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
		$page           = CurrentPageName();
		$tpl            = new template_admin();
        $security       = "AsSystemAdministrator";
        $form[]=$tpl->field_text("newitem", "{network_item}", null);
		$html[]=$tpl->form_outside("{new_item}", @implode("\n", $form),"{pdns_network_item_add}","{add}",
				"LoadAjax('table-webconsole-authlink-restrictions','$page?main=yes');dialogInstance2.close();",$security);
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function newitem_save(){
    $tpl        = new template_admin();
	$item       = url_decode_special_tool($_POST["newitem"]);
    $item       = $tpl->CLEAN_BAD_CHARSNET($item);
    $TEMPARRAY  = array();
    $newArray   = array();
	if($item==null){
		echo "jserror:No posted data\n";
		return false;
	}
    $AuthLinkRestrictions=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AuthLinkRestrictions")));
	foreach ($AuthLinkRestrictions as $ligne){
	    $TEMPARRAY[$ligne]=$ligne;
    }
    $TEMPARRAY[$item]=$item;
    foreach ($TEMPARRAY as $ligne=>$none){
        $newArray[]=$ligne;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AuthLinkRestrictions",base64_encode(serialize($newArray)));


}
function newitem_delete(){
	$item                   = url_decode_special_tool($_POST["delete"]);
    $TEMPARRAY              = array();
    $newArray               = array();
    $AuthLinkRestrictions   = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AuthLinkRestrictions")));

    foreach ($AuthLinkRestrictions as $ligne){
        $TEMPARRAY[$ligne]=$ligne;
    }
    unset($TEMPARRAY[$item]);
    foreach ($TEMPARRAY as $ligne=>$none){
        $newArray[]=$ligne;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AuthLinkRestrictions",base64_encode(serialize($newArray)));

}

function page(){
	$page=CurrentPageName();
	
	$html="
	<div id='table-webconsole-authlink-restrictions'></div>
	<script>
	LoadAjax('table-webconsole-authlink-restrictions','$page?main=yes');
	</script>";

	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);

}
function main(){
	$tpl            = new template_admin();
	$page           = CurrentPageName();
    $t              = time();
	$delete         = $tpl->javascript_parse_text("{delete}");
	$items          = $tpl->_ENGINE_parse_body("{items}/{acl_src}");
	$new_entry      = $tpl->_ENGINE_parse_body("{new_item}");
    $security       = "AsSystemAdministrator";

	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?newitem-js=yes');\"><i class='fa fa-plus'></i> $new_entry </label>
			</div>");


	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";


	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$items}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{$delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


    $AuthLinkRestrictions=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AuthLinkRestrictions")));
    if(!is_array($AuthLinkRestrictions)){$AuthLinkRestrictions=array();}

    if(count($AuthLinkRestrictions)==0){
        $ACLREST[] = "192.168.0.0/16";
        $ACLREST[] = "10.0.0.0/8";
        $ACLREST[] = "172.16.0.0/12";


        foreach ($ACLREST as $address){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $md=md5(serialize($address));
            $item=$address;
            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td><strong>$item ({default})</strong></td>";
            $html[]="<td style='vertical-align:middle' width=1%></td>";
            $html[]="</tr>";
        }

    }

	foreach ($AuthLinkRestrictions as $ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$item=$ligne;
		$item_encode=urlencode($item);
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>$item</strong></td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center'>".$tpl->icon_delete("Loadjs('$page?delete-js=$item_encode&id=$md')",$security)."</td>";
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
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() {";
    $html[]="$('#table-$t').footable( {";
    $html[]="\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\":";
    $html[]="{$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";

echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}