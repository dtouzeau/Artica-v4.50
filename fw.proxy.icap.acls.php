<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"]);exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["IcapForwardSSL"])){IcapForwardSSL();exit;}
if(isset($_GET["icap-ssl-rule"])){icap_ssl_rule_span();exit;}
if(isset($_GET["icap-default-rule"])){icap_default_rule();exit;}
if(isset($_GET["icap-default-rule-span"])){icap_default_rule_span();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("ICAP: {policies}",
        "fa fa-shield","{web_filter_policies_explain}","$page?table-start=yes","icap-policies",
        "progress-acls1-restart",false,"table-acls1-start");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{web_filter_policies}",$html);
        echo $tpl->build_firewall();
        return;
    }
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}
function table_start(){
    $page=CurrentPageName();
    echo "<div id='table-acls1-rules'></div>";
    echo "<script>LoadAjax('table-acls1-rules','$page?table=yes');</script>";

}

function rule_delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["rule-delete-js"];
	$md="acl-$ID";
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";
	
	
}
function rule_enable(){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	header("content-type: application/x-javascript");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM squid_icap_acls WHERE ID='{$_GET["enable-js"]}'");
	if(intval($ligne["enabled"])==0){
		
		$js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
		$enabled=1;
		}
		else{
			$js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
			$enabled=0;
		}
	
	$q->QUERY_SQL("UPDATE squid_icap_acls SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return;}
	echo $js;
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-id-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT aclname FROM squid_icap_acls WHERE ID='$ID'");
	$tpl->js_dialog("{rule}: $ID {$ligne["aclname"]}","$page?rule-tabs=$ID");
}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";
	
	$RefreshTable=base64_encode("LoadAjax('table-acls1-rules','$page?table=yes');");
	
	$array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=squid_icap_acls_link&RefreshTable=$RefreshTable";
	echo $tpl->tabs_default($array);
	
}


function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLUSTER_CLI=true;
	$ID=$_GET["rule-settings"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_icap_acls WHERE ID='{$_GET["rule-settings"]}'");
	
	$array_access["url_rewrite_access_deny"]="{not_use_icap_engines}";
    $array_access["url_rewrite_access_allow"]="{force_using_icap_engines}";
	$explain=null;
	

	
	
	
	$ligne["rulename"]=utf8_encode($ligne["rulename"]);
	$ligne["headervalue"]=base64_decode($ligne["headervalue"]);
	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("rulename", "{rule_name}", $ligne["rulename"],true);
	$form[]=$tpl->field_array_hash($array_access, "ztype", "{type}", $ligne["ztype"]);
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", $ligne["aclport"]);
	$jsafter="LoadAjax('table-acls1-rules','$page?table=yes');";
	$html=$tpl->form_outside($ligne["rulename"], @implode("\n", $form),$explain,"{apply}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $sql="CREATE TABLE IF NOT EXISTS `squid_icap_acls` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`rulename` TEXT NOT NULL ,
        `aclname` TEXT,
		`enabled` INTEGER NOT NULL ,
		`ztype` varchar( 20 ) NOT NULL ,
		`headername` TEXT,
		`headervalue` TEXT,
		`aclport` INTEGER ,
		`zorder`  INTEGER NOT NULL DEFAULT 1
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    $sql="CREATE TABLE IF NOT EXISTS `squid_icap_acls_link` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zorder` INTEGER
			)";

    $q->QUERY_SQL($sql);
	
	$ID=$_POST["rule-save"];
	$rulename=sqlite_escape_string2(utf8_decode($_POST["rulename"]));
	$headervalue=base64_encode($_POST["headervalue"]);
	$TempName=md5(time());
	
	$FF[]="`rulename`";
    $FF[]="`aclname`";
	$FF[]="`headername`";
	$FF[]="`ztype`";
	$FF[]="`headervalue`";
	$FF[]="`aclport`";
	$FF[]="`zorder`";
	$FF[]="`enabled`";
	
	$FA[]="'$rulename'";
    $FA[]="'$rulename'";
	$FA[]="'{$_POST["headername"]}'";
	$FA[]="'{$_POST["ztype"]}'";
	$FA[]="'{$headervalue}'";
	$FA[]="'{$_POST["aclport"]}'";
	$FA[]="'{$_POST["zorder"]}'";
	$FA[]="'{$_POST["enabled"]}'";
	
	foreach ($FF as $index=>$key){$FB[]="$key={$FA[$index]}";}
	
	$sql="UPDATE squid_icap_acls SET ".@implode(",", $FB)." WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	
	$c=0;
	$sql="SELECT ID FROM squid_icap_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_icap_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}
	
	
}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function HEADERS_ARRAY(){
	$ql=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT * FROM http_headers ORDER BY HeaderName";
	$resultsHeaders=$ql->QUERY_SQL($sql);
	foreach ($resultsHeaders as $index=>$ligneHeaders){
		$HEADERSZ[$ligneHeaders["HeaderName"]]=$ligneHeaders["HeaderName"];
	}
	return $HEADERSZ;
}

function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
	$q=new mysql_squid_builder();


    $array_access["url_rewrite_access_deny"]="{not_use_icap_engines}";
    $array_access["url_rewrite_access_allow"]="{force_using_icap_engines}";
	$explain=null;
	

	
	$HEADERSZ=HEADERS_ARRAY();
	

	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
	$form[]=$tpl->field_array_hash($array_access, "ztype", "{type}", "url_rewrite_access_deny");
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", 0);
	$jsafter="LoadAjax('table-acls1-rules','$page?table=yes');BootstrapDialog1.close();BootstrapDialog1.close();";
	
	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),$explain,"{add}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}



function rule_move(){
	$tpl=new template_admin();
	$ID=$_GET["acl-rule-move"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT zorder FROM squid_icap_acls WHERE `ID`='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
	$xORDER_ORG=intval($ligne["zorder"]);
	$xORDER=$xORDER_ORG;
	
	
	if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE squid_icap_acls SET zorder=$xORDER WHERE `ID`='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if($_GET["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE squid_icap_acls SET zorder=$xORDER2 WHERE `ID`<>'$ID' AND zorder=$xORDER";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	}
	if($_GET["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE squid_icap_acls SET zorder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	}

	$c=0;
	$sql="SELECT ID FROM squid_icap_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);

	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_icap_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if($GLOBALS["VERBOSE"]){echo "UPDATE squid_icap_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}\n";}
		$c++;
	}


}

function get_current_rules():array{
    $array=array();
    $f=explode("\n",@file_get_contents("/etc/squid3/icap.conf"));
    foreach ($f as $line){
        if(!preg_match("#ICAP_ACCESSR:.*?([0-9]+)#",$line,$re)){continue;}
        $array[$re[1]]=true;
    }

    return $array;
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
    $t=time();
	$add="Loadjs('$page?newrule-js=yes',true);";


    $jsrestart=$tpl->framework_buildjs("squid2.php?icap-silent=yes",
    "squid.access.center.progress",
        "squid.access.center.progress.log","progress-acls2-restart",
        "LoadAjax('table-acls1-rules','$page?table=yes');"
    );

	
	$btns=$tpl->_ENGINE_parse_body("
	
	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
        <label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>
        <label class=\"btn btn btn-primary\" OnClick=\"LoadAjax('table-acls1-rules','$page?table=yes');\"><i class='fas fa-sync-alt'></i> {reload} </label>
     </div>");

    $html[]="<div id='progress-acls2-restart'></div>";
    $html[]="<table id='table-webfilter-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%'>$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$rulename</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{$description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center'>$enabled</th>";
	
	

	
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-acls1-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    if(!$q->FIELD_EXISTS("aclname","squid_icap_acls")){
        $q->QUERY_SQL("ALTER TABLE squid_icap_acls ADD aclname TEXT");
    }
	
	$results=$q->QUERY_SQL("SELECT * FROM squid_icap_acls ORDER BY zorder");
	$TRCLASS=null;
	$rules_active=get_current_rules();
	
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$MUTED=null;
		$ID=$ligne["ID"];
		$ligne['rulename']=utf8_encode($ligne['rulename']);
        $status="<span class='label'>{inactive2}</span>";

        if(isset($rules_active[$ID])){
            $status="<span class='label label-primary'>{active2}</span>";
        }
		
		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if($ligne["enabled"]==0){$MUTED=" text-muted";}
		
	
		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");
		
		
		$html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
        $html[]="<td class=\"center\" width=1% nowrap>$status</td>";
        $html[]="<td class=\"center\" width=1% nowrap>{$ligne["zorder"]}</td>";
		$html[]="<td style='vertical-align:middle' width=1% nowrap>". $tpl->td_href($ligne["rulename"],"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
		$html[]="<td class='center' width=1% nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')")."</td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";
	
	}
    $icap_default_rule=1;
    $ICAPNoTrustWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ICAPNoTrustWhiteLists"));
    if($ICAPNoTrustWhiteLists==1){$icap_default_rule=0;}
    $icap_default_rule_span=icap_default_rule_span(true);
    $icap_default_rule_enable=$tpl->icon_check($icap_default_rule,"Loadjs('$page?icap-default-rule=yes')");
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}


    $IcapForwardSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IcapForwardSSL"));
    $IcapForwardSSL_enable=$tpl->icon_check($IcapForwardSSL,"Loadjs('$page?IcapForwardSSL=yes')");
    $icap_ssl_rule_span=icap_ssl_rule_span(true);
    //LoadAjaxSilent('icap-ssl-rule','$page?icap-ssl-rule=yes');

    $html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
    $html[]="<td class=\"center\" width=1% nowrap><span class='label label-primary'>{active2}</span></td>";
    $html[]="<td class=\"center\" width=1% nowrap>0</td>";
    $html[]="<td style='vertical-align:middle' width=1% nowrap>{check_SSL_protocol}</td>";
    $html[]="<td style='vertical-align:middle'><span id='icap-ssl-rule'>$icap_ssl_rule_span</span></td>";
    $html[]="<td class='center' width=1% nowrap>$IcapForwardSSL_enable</td>";
    $html[]="<td style='vertical-align:middle'style='width:1%;' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle'style='width:1%;' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";

    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
    $html[]="<td class=\"center\" width=1% nowrap><span class='label label-primary'>{active2}</span></td>";
    $html[]="<td class=\"center\" width=1% nowrap>0</td>";
    $html[]="<td style='vertical-align:middle' width=1% nowrap>{finally}</td>";
    $html[]="<td style='vertical-align:middle'><span id='icap-default-rule'>$icap_default_rule_span</span></td>";
    $html[]="<td class='center' width=1% nowrap>$icap_default_rule_enable</td>";
    $html[]="<td style='vertical-align:middle'style='width:1%;' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle'style='width:1%;' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";
	
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='8'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";



    $TINY_ARRAY["TITLE"]="ICAP: {policies}";
    $TINY_ARRAY["ICO"]="fa fa-shield";
    $TINY_ARRAY["EXPL"]="{web_filter_policies_explain}";
    $TINY_ARRAY["URL"]="icap-policies";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-webfilter-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); }); 

function RefreshIcapDefaultRule(){
    $jstiny   
}
setTimeout(\"RefreshIcapDefaultRule()\",1000);
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function IcapForwardSSL(){
    $page=CurrentPageName();
    $IcapForwardSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IcapForwardSSL"));
    if($IcapForwardSSL==1){
        admin_tracks("Disable forward SSL protocol to ICAP services");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IcapForwardSSL",0);
        echo "LoadAjaxSilent('icap-ssl-rule','$page?icap-ssl-rule=yes');";
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?icap-silent=yes");
        return true;
    }
    admin_tracks("Enable forward SSL protocol to ICAP services");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IcapForwardSSL",1);
    echo "LoadAjaxSilent('icap-ssl-rule','$page?icap-ssl-rule=yes');";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?icap-silent=yes");
    return true;

}

function icap_default_rule(){
    $page=CurrentPageName();
    $ICAPNoTrustWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ICAPNoTrustWhiteLists"));
    if($ICAPNoTrustWhiteLists==1){
        admin_tracks("Use global whitelists bypass ICAP services");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ICAPNoTrustWhiteLists",0);
        echo "LoadAjaxSilent('icap-default-rule','$page?icap-default-rule-span=yes');";
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?icap-silent=yes");
        return true;
    }
    admin_tracks("Disable the use of global whitelists for ICAP services");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ICAPNoTrustWhiteLists",1);
    echo "LoadAjaxSilent('icap-default-rule','$page?icap-default-rule-span=yes');";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?icap-silent=yes");
    return true;

}

function icap_ssl_rule_span($return=false):string{
    $tpl=new template_admin();
    $IcapForwardSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IcapForwardSSL"));
    if($IcapForwardSSL==1) {
        $text = "{when_connecting_to_method} {all_methods} {and} {for_everyone} {and} <strong>{ssl_protocol}</strong> {then} <strong>{force_using_icap_engines}</strong>";
    }else{
        $text = "{when_connecting_to_method} {all_methods} {and} {for_everyone} {and} <strong>{ssl_protocol}</strong> {then} <strong>{not_use_icap_engines}</strong>";

    }
    if($return){return $tpl->_ENGINE_parse_body($text);}
    echo $tpl->_ENGINE_parse_body($text);
    return "";
}



function icap_default_rule_span($return=false):string{
    $tpl=new template_admin();
    $expect_whitelist="(".$tpl->td_href("{expect_whitelist}",null,"s_PopUpFull('/proxy-whitelists','1024','900');").")";


    $ICAPNoTrustWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ICAPNoTrustWhiteLists"));
    if($ICAPNoTrustWhiteLists==1){
        $expect_whitelist=null;
    }


    $text="{when_connecting_to_method} {all_methods} {and} {for_everyone} $expect_whitelist {then} <strong>{force_using_icap_engines}</strong>";
    if($return){return $tpl->_ENGINE_parse_body($text);}
    echo $tpl->_ENGINE_parse_body($text);
    return "";
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	
	$rulename=sqlite_escape_string2(utf8_decode($_POST["rulename"]));
	$headervalue=base64_encode($_POST["headervalue"]);
	$TempName=md5(time());
	
	$FF[]="`rulename`";
	$FF[]="`headername`";
	$FF[]="`ztype`";
	$FF[]="`headervalue`";
	$FF[]="`aclport`";
	$FF[]="`zorder`";
	$FF[]="`enabled`";
	
	$FA[]="'$TempName'";
	$FA[]="'{$_POST["headername"]}'";
	$FA[]="'{$_POST["ztype"]}'";
	$FA[]="'{$headervalue}'";
	$FA[]="'{$_POST["aclport"]}'";
	$FA[]=99999;
	$FA[]=1;
	

	
	$sql="INSERT INTO squid_icap_acls (".@implode(",", $FF).") VALUES (".@implode(",", $FA).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM squid_icap_acls WHERE rulename='$TempName'");
	$LASTID=$ligne["ID"];
	
	$q->QUERY_SQL("UPDATE squid_icap_acls SET rulename='$rulename',aclname='$rulename' WHERE ID='$LASTID'");
	
	$c=0;
	$sql="SELECT ID FROM squid_icap_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_icap_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}
}

function rule_delete($ID){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM squid_icap_acls_link WHERE aclid='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	$q->QUERY_SQL("DELETE FROM squid_icap_acls WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	return true;
	
}
function EXPLAIN_THIS_RULE($ID){
	
	$array_access["url_rewrite_access_deny"]="{not_use_icap_engines}";
	$array_access["url_rewrite_access_allow"]="{force_using_icap_engines}";

	$acls=new squid_acls_groups();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_icap_acls WHERE ID=$ID");
	$aclport=$ligne["aclport"];
	$ztype=$ligne["ztype"];

	$PORTS_DIRECTIONS=$acls->PORTS_DIRECTIONS();
	$method[]="{when_connecting_to_method} &laquo;{$PORTS_DIRECTIONS[$aclport]}&raquo;, ";
	$objects=$acls->getobjectsNameFromAclrule($ligne['ID'],"black","squid_icap_acls_link");
	if(count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}
    if(count($objects)==0){$method[]="{for_everyone}";}
	$method[]="{then} <strong>{$array_access[$ztype]}</strong>";


    $FINAL=@implode(" ", $method);

    $tpl=new template_admin();
    $page=CurrentPageName();
    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$FINAL</span>");
}

