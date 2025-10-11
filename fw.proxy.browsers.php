<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.useragents.inc");

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

if(isset($_GET["delete-all"])){delete_all_js();exit;}
if(isset($_POST["delete-all"])){delete_all_perform();exit;}


page();
function delete_all_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_delete("{all}","delete-all","yes","LoadAjax('table-acls5-rules','$page?table=yes');");
    return true;
}
function delete_all_perform(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM UsersAgentsDB");
    include_once(dirname(__FILE__)."/ressources/class.squid.acls.useragents.inc");
    $cl=new useragents();
}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{browsers_rules}",ico_ie,
        "{browsers_ntlm_explain}","$page?table=yes","browsers-rules",
        "progress-acls5-restart",false,"table-acls5-rules");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{browsers_rules}",$html);
		echo $tpl->build_firewall();
		return true;
	}

	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function rule_delete_js():bool{
	header("content-type: application/x-javascript");
	$ID=$_GET["rule-delete-js"];
	$md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM UsersAgentsDB WHERE ID='$ID'");
    admin_tracks("Remove Proxy ACL for User-Agent ID=$ID");
	echo "$('#$md').remove();";
	return true;
	
}
function rule_enable():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	header("content-type: application/x-javascript");
	$field=$_GET["field"];
	$ligne=$q->mysqli_fetch_array("SELECT $field FROM UsersAgentsDB WHERE ID='{$_GET["enable-js"]}'");
	if(intval($ligne[$field])==0){
		$enabled=1;
	}
	else{
		$enabled=0;
	}
	
	$q->QUERY_SQL("UPDATE UsersAgentsDB SET $field='$enabled' WHERE ID='{$_GET["enable-js"]}'");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return false;}
    admin_tracks("Set Proxy ACL for User-Agent enabled=$enabled for ID={$_GET["enable-js"]}");

    return true;

}

function rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-id-js"];
	if($ID==0){
        $tpl->js_dialog("{new_rule}","$page?rule-tabs=0");
        return true;
    }
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT pattern FROM UsersAgentsDB WHERE ID='$ID'");
	$tpl->js_dialog("{rule}: $ID {$ligne["pattern"]}","$page?rule-tabs=$ID");
    return true;
}



function rule_tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";
	//$RefreshTable=base64_encode("LoadAjax('table-acls5-rules','$page?table=yes');");
	echo $tpl->tabs_default($array);
    return true;
}


function rule_settings():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-settings"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");


    if(!is_numeric($ID)){$ID=0;}


    $t=time();

    if($ID==0){
        $title="{new_rule}";
        $btname="{add}";
        $ligne["enabled"]=1;

    }else{
        $ligne=$q->mysqli_fetch_array("SELECT * FROM UsersAgentsDB WHERE ID='$ID'");
        $title="{rule}: $ID {$ligne["pattern"]}";
        $btname="{apply}";
    }

    if($_GET["pattern"]<>null){
        $ligne["pattern"]=$_GET["pattern"];
        if($ID==0){
            if(preg_match("#^(.+?)\/#", $ligne["pattern"],$re)){
                $ligne["editor"]=$re[1];
                $ligne["explain"]="Match {$re[1]} UserAgent";
                $ligne["category"]="Personal";
            }
            if(preg_match("#(MacBook|Apple|iPad|iPhone)#", $ligne["pattern"])){$ligne["editor"]="Apple"; }
            if(preg_match("#(Android)#i", $ligne["pattern"])){$ligne["editor"]="Android"; }
            if(preg_match("#(CFNetwork)#i", $ligne["pattern"])){ $ligne["category"]="Smartphones"; }
            if(preg_match("#(Microsoft)#i", $ligne["pattern"])){$ligne["editor"]="Microsoft"; }
        }


        $ligne["pattern"]="regex:".PatternToRegex($ligne["pattern"]);

    }




	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("pattern", "{pattern}", $ligne["pattern"],true,"{browser_pattern_explain}");
	$form[]=$tpl->field_text("editor", "{vendor}", $ligne["editor"],true,"");
	$form[]=$tpl->field_text("explain", "{explain}", $ligne["explain"],true,"{explain}");
	$form[]=$tpl->field_text("category", "{category}", $ligne["category"],true,"{category}");

    $form[]=$tpl->field_checkbox("bypass","{whitelist}",$ligne["bypass"],false,"{browser_allow_explain}");
    $form[]=$tpl->field_checkbox("deny","{deny}",$ligne["deny"],false,"");
    $form[]=$tpl->field_checkbox("bypassWebF","{no_webfilter}",$ligne["bypassWebF"],false,"{no_webfilter_explain}");
    $form[]=$tpl->field_checkbox("bypassWebC","{no_cache}",$ligne["bypassWebC"],false,"{no_cache_explain}");


	$jsafter="LoadAjax('table-acls5-rules','$page?table=yes');";
	$html=$tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}
function PatternToRegex($str):string{
    include_once(dirname(__FILE__)."/ressources/class.squid.acls.useragents.inc");
	$p=new useragents();
	return strval($p->PatternToRegex($str));
	
}

function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["rule-save"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    if($_POST["deny"]==1){
        $_POST["bypassWebF"]=0;
        $_POST["bypassWebC"]=0;
        $_POST["bypass"]=0;

    }

    if($_POST["bypass"]==1){
        $_POST["deny"]=0;
    }

    $_POST["pattern"]=$q->sqlite_escape_string2($_POST["pattern"]);
    $_POST["editor"]=$tpl->CLEAN_BAD_XSS($_POST["editor"]);
    $_POST["explain"]=$tpl->CLEAN_BAD_XSS($_POST["explain"]);

    if($ID>0){
        $sql="UPDATE UsersAgentsDB SET 
			`pattern`='{$_POST["pattern"]}',
			`editor`='{$_POST["editor"]}',
			`explain`='{$_POST["explain"]}',
			`bypass`='{$_POST["bypass"]}',
			`bypassWebF`='{$_POST["bypassWebF"]}',
			`bypassWebC`='{$_POST["bypassWebC"]}',
			`enabled`='{$_POST["enabled"]}',
			`deny`='{$_POST["deny"]}' WHERE ID=$ID";

    }else{
        $sql="INSERT OR IGNORE INTO `UsersAgentsDB` 
		(`pattern`,`explain`,`editor`,`category`,`bypass`,`deny`,`bypassWebF`,`bypassWebC`,`enabled`)
		VALUES ('{$_POST["pattern"]}','{$_POST["explain"]}','{$_POST["editor"]}','{$_POST["category"]}','{$_POST["bypass"]}',
		'{$_POST["deny"]}','{$_POST["bypassWebF"]}','{$_POST["bypassWebC"]}','{$_POST["enabled"]}')";
    }


    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n\n$sql";}
    return true;

}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$eth_sql=null;
	$token=null;
	$class=null;
	$jsdelete="Loadjs('$page?delete-all=yes');";
	$class=null;
    $t=time();
	$add="Loadjs('$page?rule-id-js=0',true);";
    $users=new usersMenus();
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
	$ARRAY["CMD"]="squid2.php?useragents-rules=yes";
	$ARRAY["TITLE"]="{GLOBAL_ACCESS_CENTER}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-acls5-restart')";

    if($users->AsDansGuardianAdministrator) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
        $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");
        $topbuttons[] = array($jsdelete, ico_trash, "{delete_all}");
    }
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%'>{pattern}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{vendor}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center'>{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' nowrap>{whitelist}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' nowrap>{deny}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' nowrap>{bypass_webfilter}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' nowrap>{no_cache}</th>";

    $html[]="<th data-sortable=false></th>";

	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-proxyheader-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$results=$q->QUERY_SQL("SELECT * FROM UsersAgentsDB ORDER BY pattern");
	if(count($results)==0){
        $cl=new useragents();
        $cl->checkTable();
        $results=$q->QUERY_SQL("SELECT * FROM UsersAgentsDB ORDER BY pattern");
    }
	$TRCLASS=null;
	
	
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$ID=$ligne["ID"];
        $MUTED=null;
		$pattern=htmlspecialchars($ligne["pattern"]);
		
		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID&md=$md')","AsDansGuardianAdministrator");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if($ligne["enabled"]==0){$MUTED=" text-muted";}

        $pattern=$tpl->td_href($pattern,null,$js);

		$bypass=$tpl->icon_check($ligne["bypass"],"Loadjs('$page?enable-js=$ID&field=bypass')",null,"AsDansGuardianAdministrator");
        $bypassWebF=$tpl->icon_check($ligne["bypassWebF"],"Loadjs('$page?enable-js=$ID&field=bypassWebF')",null,"AsDansGuardianAdministrator");
        $bypassWebC=$tpl->icon_check($ligne["bypassWebC"],"Loadjs('$page?enable-js=$ID&field=bypassWebC')",null,"AsDansGuardianAdministrator");
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID&field=enabled')",null,"AsDansGuardianAdministrator");
        $deny=$tpl->icon_check($ligne["deny"],"Loadjs('$page?enable-js=$ID&field=deny')",null,"AsDansGuardianAdministrator");

        $ligne["editor"]=htmlspecialchars($ligne["editor"]);
        $ligne["category"]=htmlspecialchars($ligne["category"]);

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=99% nowrap><strong>$pattern</strong></td>";
		$html[]="<td width=1% nowrap>{$ligne["editor"]}</td>";
        $html[]="<td width=1% nowrap>{$ligne["category"]}</td>";
		$html[]="<td class='center' width=1% nowrap>$enabled</td>";
        $html[]="<td class='center' width=1% nowrap>$bypass</td>";
        $html[]="<td class='center' width=1% nowrap>$deny</td>";
        $html[]="<td class='center' width=1% nowrap>$bypassWebF</td>";
        $html[]="<td class='center' width=1% nowrap>$bypassWebC</td>";
		$html[]="<td class='center' width=1% nowrap>$delete</td>";
		$html[]="</tr>";
	
	}

    $TINY_ARRAY["TITLE"]="{browsers_rules}";
    $TINY_ARRAY["ICO"]=ico_ie;
    $TINY_ARRAY["EXPL"]="{browsers_ntlm_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</tbody>";
	$html[]="</table>";
	$html[]="
<script> 
$headsjs\n;NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
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

function rule_delete($ID){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM squid_http_headers_link WHERE aclid='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	$q->QUERY_SQL("DELETE FROM squid_http_headers_acls WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	return true;
	
}
function EXPLAIN_THIS_RULE($ID){
	
	$array_access["request_header_allow"]="{allow_client_header}";
	$array_access["request_header_replace"]="{replace_client_header}";
	$array_access["request_header_deny"]="{delete_client_header}";
	$array_access["reply_header_allow"]="{allow_server_header}";
	$array_access["reply_header_deny"]="{delete_server_header}";
	$array_access["reply_header_replace"]="{replace_server_header}";
	
	$acls=new squid_acls_groups();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_http_headers_acls WHERE ID=$ID");
	$aclport=$ligne["aclport"];
	$ztype=$ligne["ztype"];
	$headername=$ligne["headername"];
	$headervalue=$ligne["headervalue"];
	
	$PORTS_DIRECTIONS=$acls->PORTS_DIRECTIONS();
	$method[]="{when_connecting_to_method} &laquo;{$PORTS_DIRECTIONS[$aclport]}&raquo;, ";
	$objects=$acls->getobjectsNameFromAclrule($ligne['ID'],"black","squid_http_headers_link");
	if(count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}
	$value=" {with} &laquo;&laquo;{$headervalue}&raquo;&raquo;";
	if(!preg_match("#^replace#", $ztype)){$value=null;}
	$method[]="{then} {$array_access[$ztype]} &laquo;&laquo;$headername&raquo;&raquo;{$headervalue}";
	$FINAL=@implode(" ", $method);

    $tpl=new template_admin();
    $page=CurrentPageName();
    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$FINAL</span>");
}


