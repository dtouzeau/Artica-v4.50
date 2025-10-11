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

page();


function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{headers_rules_http}","fa fa-shield","{headers_rules_mini_explain}",
        "$page?table=yes","proxy-acls-headers","progress-acls2-restart",false,"table-acls2-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function rule_delete_js():bool{
	header("content-type: application/x-javascript");
	$ID=intval($_GET["rule-delete-js"]);
	$md="acl-$ID";

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    header("content-type: application/x-javascript");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_http_headers_acls WHERE ID=$ID");
$rulename=$ligne["rulename"];
	if(!rule_delete($ID)){
        return false;}

	echo "$('#$md').remove();";
	return admin_tracks("Remove Proxy ACL header rule $rulename ($ID)");
	
}
function rule_enable():bool{
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	header("content-type: application/x-javascript");
    $ID=intval($_GET["enable-js"]);
	$ligne=$q->mysqli_fetch_array("SELECT rulename,enabled FROM squid_http_headers_acls WHERE ID=$ID");

    $rulename=$ligne["rulename"];

	if(intval($ligne["enabled"])==0){
		
		$js="$( \"#acl-$ID\" ).removeClass( \"text-muted\" );";
		$enabled=1;
		}
		else{
			$js="$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
			$enabled=0;
		}
	
	$q->QUERY_SQL("UPDATE squid_http_headers_acls SET enabled='$enabled' WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return false;}
	echo $js;
    return admin_tracks("Set Proxy ACL header rule $rulename ($ID) to enable=$enabled");
}

function rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-id-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT aclname FROM squid_http_headers_acls WHERE ID='$ID'");
	return $tpl->js_dialog("{rule}: $ID {$ligne["aclname"]}","$page?rule-tabs=$ID");
}



function rule_tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";
	
	$RefreshTable=base64_encode("LoadAjax('table-acls2-rules','$page?table=yes');");
	
	$array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=squid_http_headers_link&RefreshTable=$RefreshTable";
	echo $tpl->tabs_default($array);
    return true;
}


function rule_settings():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-settings"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_http_headers_acls WHERE ID='{$_GET["rule-settings"]}'");



    $array_access["request_header_add"]="{add_client_header}";
    $array_access["request_header_allow"]="{allow_client_header}";
    $array_access["request_header_replace"]="{replace_client_header}";
    $array_access["request_header_deny"]="{delete_client_header}";
    $array_access["reply_header_add"]="{add_server_header}";
    $array_access["reply_header_allow"]="{allow_server_header}";
    $array_access["reply_header_deny"]="{delete_server_header}";
    $array_access["reply_header_replace"]="{replace_server_header}";
	$explain=$tpl->_ENGINE_parse_body("{new_acls_rule_header_explain}");
	

	
	
	
	$ligne["rulename"]=$tpl->utf8_encode($ligne["rulename"]);
	$ligne["headervalue"]=base64_decode($ligne["headervalue"]);
	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("rulename", "{rule_name}", $ligne["rulename"],true);
	$form[]=$tpl->field_array_hash(HEADERS_ARRAY(), "headername", "{http_header}", $ligne["headername"]);
	$form[]=$tpl->field_array_hash($array_access, "ztype", "{type}", $ligne["ztype"]);
	$form[]=$tpl->field_text("headervalue", "{new_value}",$ligne["headervalue"]);
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", $ligne["aclport"]);
	$jsafter="LoadAjax('table-acls2-rules','$page?table=yes');";
	$html=$tpl->form_outside($ligne["rulename"], @implode("\n", $form),$explain,"{apply}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function rule_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$ID=$_POST["rule-save"];
	$rulename=sqlite_escape_string2($tpl->utf8_decode($_POST["rulename"]));
	$headervalue=base64_encode($_POST["headervalue"]);

	
	$FF[]="`rulename`";
	$FF[]="`headername`";
	$FF[]="`ztype`";
	$FF[]="`headervalue`";
	$FF[]="`aclport`";
	$FF[]="`zorder`";
	//$FF[]="`enabled`";
	
	$FA[]="'$rulename'";
	$FA[]="'{$_POST["headername"]}'";
	$FA[]="'{$_POST["ztype"]}'";
	$FA[]="'$headervalue'";
	$FA[]="'{$_POST["aclport"]}'";
	$FA[]="'{$_POST["zorder"]}'";
	//$FA[]="'{$_POST["enabled"]}'";
	
	foreach ($FF as $index=>$key){$FB[]="$key={$FA[$index]}";}
	
	$sql="UPDATE squid_http_headers_acls SET ".@implode(",", $FB)." WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return false;}
	
	$c=0;
	$sql="SELECT ID FROM squid_http_headers_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_http_headers_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return false;}
		$c++;
	}
	
	return admin_tracks_post("Save ACLS proxy header rule");
}

function new_rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	return $tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function HEADERS_ARRAY():array{
	$ql=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT * FROM http_headers ORDER BY HeaderName";
	$resultsHeaders=$ql->QUERY_SQL($sql);
    $HEADERSZ=array();
	foreach ($resultsHeaders as $index=>$ligneHeaders){
		$HEADERSZ[$ligneHeaders["HeaderName"]]=$ligneHeaders["HeaderName"];
	}
    $HEADERSZ["X-User-Auth"]="X-User-Auth";
	return $HEADERSZ;
}

function new_rule_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();


    $array_access["request_header_add"]="{add_client_header}";
	$array_access["request_header_allow"]="{allow_client_header}";
	$array_access["request_header_replace"]="{replace_client_header}";
	$array_access["request_header_deny"]="{delete_client_header}";
    $array_access["reply_header_add"]="{add_server_header}";
	$array_access["reply_header_allow"]="{allow_server_header}";
	$array_access["reply_header_deny"]="{delete_server_header}";
	$array_access["reply_header_replace"]="{replace_server_header}";
	$explain=$tpl->_ENGINE_parse_body("{new_acls_rule_header_explain}");
	

	
	$HEADERSZ=HEADERS_ARRAY();
	

	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
	$form[]=$tpl->field_array_hash($HEADERSZ, "headername", "{http_header}", "User-Agent");
	$form[]=$tpl->field_array_hash($array_access, "ztype", "{type}", "request_header_replace");
	$form[]=$tpl->field_text("headervalue", "{new_value} <small>({if_replace})</small>", null);
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", 0);
	$jsafter="LoadAjax('table-acls2-rules','$page?table=yes');BootstrapDialog1.close();BootstrapDialog1.close();";
	
	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),$explain,"{add}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}



function rule_move(){
	$tpl=new template_admin();
	$ID=$_GET["acl-rule-move"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT zorder FROM squid_http_headers_acls WHERE `ID`='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
	$xORDER_ORG=intval($ligne["zorder"]);
	$xORDER=$xORDER_ORG;
	
	
	if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE squid_http_headers_acls SET zorder=$xORDER WHERE `ID`='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if($_GET["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE squid_http_headers_acls SET zorder=$xORDER2 WHERE `ID`<>'$ID' AND zorder=$xORDER";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	}
	if($_GET["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE squid_http_headers_acls SET zorder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	}

	$c=0;
	$sql="SELECT ID FROM squid_http_headers_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);

	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_http_headers_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if($GLOBALS["VERBOSE"]){echo "UPDATE squid_http_headers_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}\n";}
		$c++;
	}


}


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$add="Loadjs('$page?newrule-js=yes',true);";
	
    $jsrestart=$tpl->framework_buildjs("/proxy/acls/headers",
    "squid.access.center.progress",
        "squid.access.center.progress.log",
        "progress-acls2-restart");


    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");

    $html[]="<table id='table-webfilter-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%'>$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rulename</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center'>$enabled</th>";
	
	

	
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-proxyheader-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$results=$q->QUERY_SQL("SELECT * FROM squid_http_headers_acls ORDER BY zorder");
	$TRCLASS=null;
	
	
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$MUTED=null;
		$ID=$ligne["ID"];
		$ligne['rulename']=utf8_encode($ligne['rulename']);
		
		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if($ligne["enabled"]==0){$MUTED=" text-muted";}
		
	
		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");
		
		
		$html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
		$html[]="<td class=\"center\" style='width:1%' nowrap>{$ligne["zorder"]}</td>";
		$html[]="<td style='vertical-align:middle;width:1%' nowrap>". $tpl->td_href($ligne["rulename"],"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
		$html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')")."</td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";
	
	}
	
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $TINY_ARRAY["TITLE"]="{headers_rules_http}";
    $TINY_ARRAY["ICO"]="fa fa-shield";
    $TINY_ARRAY["EXPL"]="{headers_rules_mini_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    $js= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
<script> 
$js
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-webfilter-rules').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) }); 
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	
	$rulename=sqlite_escape_string2($tpl->utf8_decode($_POST["rulename"]));
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
	$FA[]="'$headervalue'";
	$FA[]="'{$_POST["aclport"]}'";
	$FA[]=99999;
	$FA[]=1;
	

	
	$sql="INSERT INTO squid_http_headers_acls (".@implode(",", $FF).") VALUES (".@implode(",", $FA).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM squid_http_headers_acls WHERE rulename='$TempName'");
	$LASTID=$ligne["ID"];
	
	$q->QUERY_SQL("UPDATE squid_http_headers_acls SET rulename='$rulename' WHERE ID='$LASTID'");
	
	$c=0;
	$sql="SELECT ID FROM squid_http_headers_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_http_headers_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}
}

function rule_delete($ID):bool{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_http_headers_acls WHERE ID=$ID");
    $rulename=$ligne["rulename"];

	$q->QUERY_SQL("DELETE FROM squid_http_headers_link WHERE aclid='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	$q->QUERY_SQL("DELETE FROM squid_http_headers_acls WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	return admin_tracks("Remove Proxy ACL header rule $rulename");
	
}
function EXPLAIN_THIS_RULE($ID):string{
	$array_access["request_header_add"]="{add_client_header}";
    $array_access["request_header_allow"]="{allow_client_header}";
    $array_access["request_header_replace"]="{replace_client_header}";
    $array_access["request_header_deny"]="{delete_client_header}";
    $array_access["reply_header_add"]="{add_server_header}";
    $array_access["reply_header_allow"]="{allow_server_header}";
    $array_access["reply_header_deny"]="{delete_server_header}";
    $array_access["reply_header_replace"]="{replace_server_header}";
	
	$acls=new squid_acls_groups();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_http_headers_acls WHERE ID=$ID");
	$aclport=$ligne["aclport"];
	$ztype=$ligne["ztype"];
	$headername=$ligne["headername"];
	$headervalue=trim(base64_decode(trim($ligne["headervalue"])));
	
	$PORTS_DIRECTIONS=$acls->PORTS_DIRECTIONS();
	$method[]="{when_connecting_to_method} &laquo;{$PORTS_DIRECTIONS[$aclport]}&raquo;, ";
	$objects=$acls->getobjectsNameFromAclrule($ligne['ID'],"black","squid_http_headers_link");
	if(count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}



    if(!is_null($headervalue)){
        $len=strlen($headervalue);
        if($len>1) {
            if($len>80) {
                $headervalue = chunk_split($headervalue, 80, "<br>\n");
            }
            $headervalue = " {with} &laquo;&laquo;$headervalue&raquo;&raquo;";
        }
    }



	$method[]="{then} $array_access[$ztype] &laquo;&laquo;$headername&raquo;&raquo;$headervalue";
	$FINAL=@implode(" ", $method);

    $tpl=new template_admin();
    $page=CurrentPageName();
    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID'  data='$page?explain-this-rule=$ID'>$FINAL</span>");
}

