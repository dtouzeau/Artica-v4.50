<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["SquidWhitelistAuthFrom-js"])){SquidWhitelistAuthFrom_js();exit;}
if(isset($_GET["SquidWhitelistAuthFrom-popup"])){SquidWhitelistAuthFrom_popup();exit;}
if(isset($_POST["SquidWhitelistAuthFrom"])){SquidWhitelistAuthFrom_save();exit;}

if(isset($_GET["SquidWhitelistAuthTo-js"])){SquidWhitelistAuthTo_js();exit;}
if(isset($_GET["SquidWhitelistAuthTo-popup"])){SquidWhitelistAuthTo_popup();exit;}
if(isset($_POST["SquidWhitelistAuthTo"])){SquidWhitelistAuthTo_save();exit;}


if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header(
        "{ActiveDirectory}&nbsp;&raquo;&nbsp;{authentication}&nbsp;&raquo;&nbsp;{whitelists}",
        "fa fa-align-justify",
        "{AUTHWHITELIST_EXPLAIN}",
        "$page?table=yes",
        "ad-white",
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

function rule_delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["rule-delete-js"];
	$md="acl-$ID";
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";
	
	
}
function rule_enable(){
	$q=new mysql_squid_builder();
	header("content-type: application/x-javascript");
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT enabled FROM webfilters_sqacls WHERE ID='{$_GET["enable-js"]}'"));
	if(intval($ligne["enabled"])==0){
		
		$js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
		$enabled=1;}else{
			$js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
			$enabled=0;
		}
	
	$q->QUERY_SQL("UPDATE webfilters_sqacls SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return;}
	echo $js;
}

function SquidWhitelistAuthFrom_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{do_not_authenticate_from_network_elements}","$page?SquidWhitelistAuthFrom-popup=yes");
}
function SquidWhitelistAuthTo_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{do_not_authenticate_to_network_elements}","$page?SquidWhitelistAuthTo-popup=yes");	
}

function SquidWhitelistAuthTo_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $values=array();
	$SquidWhitelistAuthTo=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWhitelistAuthTo"));
    foreach ($SquidWhitelistAuthTo as $index=>$ligne){
        $values[]=$index;
    }
	$form=$tpl->field_textareacode("SquidWhitelistAuthTo", null, @implode("\n",$values));
	$js="LoadAjax('table-ntlmwhite-rules','$page?table=yes');";
	echo $tpl->form_outside("{items}", $form,"{SquidWhitelistAuthTo_explain}","{apply}",$js,"AsProxyMonitor");
	
}

function SquidWhitelistAuthFrom_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $values=array();
	$SquidWhitelistAuthFrom=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWhitelistAuthFrom"));
    foreach ($SquidWhitelistAuthFrom as $index=>$ligne){
	    $values[]=$index;
    }
	$form=$tpl->field_textareacode("SquidWhitelistAuthFrom", null, @implode("\n",$values));
	$js="LoadAjax('table-ntlmwhite-rules','$page?table=yes');";
	echo $tpl->form_outside("{items}", $form,"{SquidWhitelistAuthFrom_explain}","{apply}",$js,"AsProxyMonitor");
}

function SquidWhitelistAuthTo_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$values=explode("\n",$_POST["SquidWhitelistAuthTo"]);	
	$t=array();
	$IP=new IP();
	foreach ($values as $line){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		if($IP->isIPAddressOrRange($line)){$t[$line]=$line;continue;}
        $t[$line]=$line;
    }
	$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($t), "SquidWhitelistAuthTo");
}

function SquidWhitelistAuthFrom_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$values=explode("\n",$_POST["SquidWhitelistAuthFrom"]);
    $t=array();
	$IP=new IP();
	foreach ($values as $line){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		if(!$IP->isIPAddressOrRange($line)){
			if(!$IP->IsvalidMAC($line)){
				continue;
			}
		}
		
		$t[$line]=$line;
		
	}
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($t), "SquidWhitelistAuthFrom");
}






function rule_save(){
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	$_POST["description"]=mysql_escape_string2(url_decode_special_tool($_POST["description"]));
	foreach ($_POST as $key=>$val){
		$add_fields[]="`$key`";
		$add_values[]="'$val'";
		$edit_fields[]="`$key`='$val'";
	}
	$sql="UPDATE ssl_rules SET ".@implode(",", $edit_fields)." WHERE ID='$ID'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}
function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql_squid_builder();
	
	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_hidden("enabled","1");
	
	$form[]=$tpl->field_checkbox("crypt","{uncrypt_ssl}",1,false,"{uncrypt_ssl_explain}");
	$form[]=$tpl->field_checkbox("trust","{trust_ssl}",0,false,"{trust_ssl_explain}");
	$form[]=$tpl->field_text("description", "{rule_name}", null,true);
	$jsafter="LoadAjax('table-acls-ssl-rules','$page?table=yes');BootstrapDialog1.close();";
	
	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),null,"{add}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}






function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();


    $jsrestart=$tpl->framework_buildjs(
        "/proxy/acls/httpaccess/final",
        "BuildHTTPAccessFinal.progress",
        "BuildHTTPAccessFinal.log",
        "progress-ntlmwhite-restart",
        "document.getElementById('progress-ntlmwhite-restart').innerHTML='';"

    );

    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>";
    $bts[]="</div>";

    $html[]="<table id='table-authwhite-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width='99%'>{description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' width='1%'>{items}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$SquidWhitelistAuthFrom_text="{no_item}";
		
		$SquidWhitelistAuthFrom=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWhitelistAuthFrom"));
		$SquidWhitelistAuthFrom_js="Loadjs('$page?SquidWhitelistAuthFrom-js=yes')";
		$SquidWhitelistAuthFrom_class="text-muted";
		
		if($SquidWhitelistAuthFrom){
			if(count($SquidWhitelistAuthFrom)>0){
				$SquidWhitelistAuthFrom_text=count($SquidWhitelistAuthFrom)." {items}";
			}
		}
		
	
		$html[]="<tr style='vertical-align:middle'>";
		$html[]="<td style='vertical-align:middle' class=$SquidWhitelistAuthFrom_class>". $tpl->td_href("{do_not_authenticate_from_network_elements}","{click_to_edit}",$SquidWhitelistAuthFrom_js)."</td>";
		$html[]="<td style='vertical-align:middle' class=$SquidWhitelistAuthFrom_class>". $tpl->td_href($SquidWhitelistAuthFrom_text,"{click_to_edit}",$SquidWhitelistAuthFrom_js)."</td>";
		$html[]="</tr>";
		
// ---------------------------------------------------------------------------------------------------------------------
		
		$SquidWhitelistAuthTo_text="{no_item}";
		
		$SquidWhitelistAuthTo=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWhitelistAuthTo"));
		$SquidWhitelistAuthTo_js="Loadjs('$page?SquidWhitelistAuthTo-js=yes')";
		$SquidWhitelistAuthTo_class="text-muted";
		
		if($SquidWhitelistAuthTo){
			if(count($SquidWhitelistAuthTo)>0){
				$SquidWhitelistAuthTo_text=count($SquidWhitelistAuthTo)." {items}";
			}
		}

		$html[]="<tr style='vertical-align:middle' class='footable-odd'>";
		$html[]="<td style='vertical-align:middle;width:99%' class=$SquidWhitelistAuthTo_class>". $tpl->td_href("{do_not_authenticate_to_network_elements}","{click_to_edit}",$SquidWhitelistAuthTo_js)."</td>";
		$html[]="<td style='vertical-align:middle;width:1%' class=$SquidWhitelistAuthTo_class nowrap>". $tpl->td_href($SquidWhitelistAuthTo_text,"{click_to_edit}",$SquidWhitelistAuthTo_js)."</td>";
		$html[]="</tr>";		

//----------------------------------------------------------------------------------------------------------------------

    $TINY_ARRAY["TITLE"]="{ActiveDirectory}&nbsp;&raquo;&nbsp;{authentication}&nbsp;&raquo;&nbsp;{whitelists}";
    $TINY_ARRAY["ICO"]="fa fa-align-justify";
    $TINY_ARRAY["EXPL"]="{AUTHWHITELIST_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
<script> 
$jstiny
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-authwhite-rules').footable({ \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": false },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); }); 
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function new_rule_save(){
	$q=new mysql_squid_builder();
	unset($_POST["newrule"]);
	$_POST["description"]=mysql_escape_string2(url_decode_special_tool($_POST["description"]));
	
	foreach ($_POST as $key=>$val){
	
		$add_fields[]="`$key`";
		$add_values[]="'$val'";
		$edit_fields[]="`$key`='$val'";
	
	
	}
	$sql="INSERT IGNORE INTO ssl_rules (".@implode(",", $add_fields).") VALUES (".@implode(",", $add_values).")";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}

}

function rule_delete($ID){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE aclid='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	$q->QUERY_SQL("DELETE FROM webfilters_sqacllinks WHERE aclid='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	$q->QUERY_SQL("DELETE FROM webfilters_sqacls WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	$sql="SELECT ID,enabled FROM webfilters_sqacls WHERE aclgpid=$ID";
	$results = $q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne) {acl_rule_delete_perform($ligne["ID"]);}
	return true;
}

