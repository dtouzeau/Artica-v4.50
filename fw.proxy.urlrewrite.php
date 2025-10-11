<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
    exit();
}

include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["bypasswebf"])){bypasswebf_js();exit;}
if(isset($_GET["bypasswebf-popup"])){bypasswebf_popup();exit;}
if(isset($_GET["bypasswebf-delete"])){bypasswebf_delete();exit;}

if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"]);exit;}
if(isset($_GET["search"])){table();exit;}
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

    $html=$tpl->page_header("{web_filter_policies}",
        "fa fa-shield","{web_filter_policies_explain}","$page?table=yes","webfiltering-policies",
        "progress-acls1-restart",true,"table-acls1-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{web_filter_policies}",$html);
        echo $tpl->build_firewall();
        return false;
    }
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function bypasswebf_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog("{rules}","$page?bypasswebf-popup=yes&function=$function");
}
function bypasswebf_delete():bool{
    $tpl=new template_admin();
    $site=$_GET["bypasswebf-delete"];
    $function=$_GET["function"];
    $md=$_GET["md"];
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/proxy/webfilter/bypass/delete/$site"));
    if(!$json->Status){
        $tpl->popup_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "$function();";
    return admin_tracks("Remove $site From Web Filter whitelist");
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
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM squid_url_rewrite_acls WHERE ID='{$_GET["enable-js"]}'");
	if(intval($ligne["enabled"])==0){
		
		$js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
		$enabled=1;
		}
		else{
			$js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
			$enabled=0;
		}
	
	$q->QUERY_SQL("UPDATE squid_url_rewrite_acls SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return;}
	echo $js;
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-id-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $function=$_GET["function"];
	$ligne=$q->mysqli_fetch_array("SELECT aclname FROM squid_url_rewrite_acls WHERE ID='$ID'");
	$tpl->js_dialog("{rule}: $ID {$ligne["aclname"]}","$page?rule-tabs=$ID&function=$function");
}
function rule_tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
    $function=$_GET["function"];
	$array["{rule}"]="$page?rule-settings=$ID&function=$function";
    $RefreshFunction=base64_encode("$function();");
    $array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=squid_url_rewrite_link&RefreshFunction=$RefreshFunction";
	echo $tpl->tabs_default($array);
	return true;
}
function rule_settings(){
	$tpl=new template_admin();
	$tpl->CLUSTER_CLI=true;
	$ID=$_GET["rule-settings"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_url_rewrite_acls WHERE ID='{$_GET["rule-settings"]}'");
    $function=$_GET["function"];
	$array_access["url_rewrite_access_deny"]="{pass_trough_thewebfilter_engine}";
    $array_access["url_rewrite_access_allow"]="{force_using_thewebfilter_engine}";
	$explain=null;

	$ligne["rulename"]=$tpl->utf8_encode($ligne["rulename"]);
	$ligne["headervalue"]=base64_decode($ligne["headervalue"]);
	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("rulename", "{rule_name}", $ligne["rulename"],true);
	$form[]=$tpl->field_array_hash($array_access, "ztype", "{type}", $ligne["ztype"]);
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", $ligne["aclport"]);
	$jsafter="$function()";
	$html=$tpl->form_outside($ligne["rulename"], @implode("\n", $form),$explain,"{apply}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
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
	$FF[]="`enabled`";
	
	$FA[]="'$rulename'";
	$FA[]="'{$_POST["headername"]}'";
	$FA[]="'{$_POST["ztype"]}'";
	$FA[]="'{$headervalue}'";
	$FA[]="'{$_POST["aclport"]}'";
	$FA[]="'{$_POST["zorder"]}'";
	$FA[]="'{$_POST["enabled"]}'";
	
	foreach ($FF as $index=>$key){$FB[]="$key={$FA[$index]}";}
	
	$sql="UPDATE squid_url_rewrite_acls SET ".@implode(",", $FB)." WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return false;}
	
	$c=0;
	$sql="SELECT ID FROM squid_url_rewrite_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_url_rewrite_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return false;}
		$c++;
	}
	return admin_tracks("Add/update a new webfiltering policy $rulename");
	
}

function new_rule_js(){
    $function=$_GET["function"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes&function=$function");
}

function HEADERS_ARRAY(){
    $HEADERSZ=array();
	$ql=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT * FROM http_headers ORDER BY HeaderName";
	$resultsHeaders=$ql->QUERY_SQL($sql);
	foreach ($resultsHeaders as $index=>$ligneHeaders){
		$HEADERSZ[$ligneHeaders["HeaderName"]]=$ligneHeaders["HeaderName"];
	}
	return $HEADERSZ;
}

function new_rule_popup(){
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $function=$_GET["function"];
    $array_access["url_rewrite_access_deny"]="{pass_trough_thewebfilter_engine}";
    $array_access["url_rewrite_access_allow"]="{force_using_thewebfilter_engine}";
	$explain=null;

    $form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
	$form[]=$tpl->field_array_hash($array_access, "ztype", "{type}", "url_rewrite_access_deny");
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", 0);
	$jsafter="$function();BootstrapDialog1.close();BootstrapDialog1.close();";
	
	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),$explain,"{add}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}
function rule_move(){
	$tpl=new template_admin();
	$ID=$_GET["acl-rule-move"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT zorder FROM squid_url_rewrite_acls WHERE `ID`='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
	$xORDER_ORG=intval($ligne["zorder"]);
	$xORDER=$xORDER_ORG;
	
	
	if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE squid_url_rewrite_acls SET zorder=$xORDER WHERE `ID`='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if($_GET["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE squid_url_rewrite_acls SET zorder=$xORDER2 WHERE `ID`<>'$ID' AND zorder=$xORDER";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	}
	if($_GET["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE squid_url_rewrite_acls SET zorder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	}

	$c=0;
	$sql="SELECT ID FROM squid_url_rewrite_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);

	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_url_rewrite_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if($GLOBALS["VERBOSE"]){echo "UPDATE squid_url_rewrite_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}\n";}
		$c++;
	}


}
function bypasswebf_popup(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $html[]="<table id='table-webfilter-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize center'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{sitename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $sock=new sockets();
    $json=json_decode($sock->REST_API("/proxy/webfilter/bypass/list"));
    $TRCLASS=null;
    foreach($json->Rows as $index=>$ligne) {
        $md=md5($ligne);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $MUTED=null;
        $delete=$tpl->icon_delete("Loadjs('$page?bypasswebf-delete=$ligne&function=$function&md=$md')");
        $status="<i class=\"".ico_earth. "\"></i>";
        $html[]="<tr class='$TRCLASS{$MUTED}' id='$md'>";
        $html[]="<td class=\"center\" style='width:1%' nowrap>$status</td>";
        $html[]="<td class=\"left\" style='width:99%'>$ligne</td>";
        $html[]="<td class=\"center\" style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";
    }
    $html[]="</tr>";
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
    $t=time();
	$function=$_GET["function"];
    $add="Loadjs('$page?newrule-js=yes&function=$function',true);";

    //
    $jsrestart=$tpl->framework_buildjs("/proxy/webfilter/policies",
        "squid.access.center.progress",
        "squid.access.center.progress.log",
        "progress-acls1-restart",
        "$function()",null,null,"AsDansGuardianAdministrator");

    $search=$_GET["search"];
    //
    $topbuttons[]=array($add,ico_plus,"{new_rule}");
    $topbuttons[]=array($jsrestart,ico_save,"{apply_rules}");
    $topbuttons[]=array("$function()",ico_refresh,"{reload}");
    $help_url="https://wiki.articatech.com/en/proxy-service/web-filtering/policies";
    $js_help="s_PopUpFull('$help_url','1024','900');";
    $topbuttons[] = array($js_help, ico_support, "WIKI");

    $html[]="<table id='table-webfilter-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%'>$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$rulename</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{$description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center'>$enabled</th>";


    $AIVABLE=array();
    $f=explode("\n",@file_get_contents("/etc/squid3/url_rewrite_access.conf"));
    foreach ($f as $line){
        if(preg_match("#urlRewriteAccessDenyTemp#",$line,$re)){
            $AIVABLE["urlRewriteAccessDenyTemp"]=true;
            continue;
        }
        
        if(preg_match("#ENABLED_RULE=([0-9]+)#",$line,$re)){
            $AIVABLE[$re[1]]=true;
        }
    }
	
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="$function()";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $max=time();
    $line=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM url_rewrite_temp WHERE enabled=1 AND finaltime > $max");
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $url_rewrite_temp=intval($line["tcount"]);

    $sql="SELECT * FROM squid_url_rewrite_acls ORDER BY zorder";
    if($search<>null){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT * FROM squid_url_rewrite_acls WHERE rulename LIKE '$search' ORDER BY zorder";
    }
	
	$results=$q->QUERY_SQL($sql);
	$TRCLASS=null;
    $tmpwebfiltering=$tpl->_ENGINE_parse_body("{temporary_permissions_webfiltering_explains}");
    $tmpwebfiltering=str_replace("%v","<strong>$url_rewrite_temp</strong>",$tmpwebfiltering);


    $status="<span class='label label'>{inactive}</span>";
    if(isset($AIVABLE["urlRewriteAccessDenyTemp"])){
        $status="<span class='label label-primary'>{active2}</span>";
    }

    $js="Loadjs('fw.proxy.urlrewrite.temp.php?Mainfunc=$function')";
    $html[]="<tr class='$TRCLASS' id='acl-78945621'>";
    $html[]="<td class=\"center\" style='width:1%' nowrap>$status</td>";
    $html[]="<td class=\"center\" style='width:1%' nowrap>0</td>";
    $html[]="<td style='vertical-align:middle;width:1%' nowrap>". $tpl->td_href("{temporary_permissions}","{click_to_edit}",$js)."</td>";
    $html[]="<td style='vertical-align:middle'>$tmpwebfiltering</td>";
    $html[]="<td class='center' style='width:1%' nowrap>&nbsp;</td>";
    $html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>&nbsp;</center></td>";
    $html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>&nbsp;</center></td>";
    $html[]="</tr>";
	
	
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$MUTED=null;
		$ID=$ligne["ID"];
		$ligne['rulename']=$tpl->utf8_encode($ligne['rulename']);
        $status="<span class='label label'>{inactive}</span>";
		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
		$js="Loadjs('$page?rule-id-js=$ID&function=$function')";
		if($ligne["enabled"]==0){$MUTED=" text-muted";}
		
	
		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");

        if(isset($AIVABLE[$ID])){
            $status="<span class='label label-primary'>{active2}</span>";
        }
		
		
		$html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
        $html[]="<td class=\"center\" style='width:1%' nowrap>$status</td>";
		$html[]="<td class=\"center\" style='width:1%' nowrap>{$ligne["zorder"]}</td>";
		$html[]="<td style='vertical-align:middle;width:1%' nowrap>". $tpl->td_href($ligne["rulename"],"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
		$html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')")."</td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";
	
	}

    $status="<span class='label label-primary'>{active2}</span>";
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS$MUTED' id='acl-5465465465465'>";
    $html[]="<td class=\"center\" style='width:1%' nowrap>$status</td>";
    $html[]="<td class=\"center\" style='width:1%' nowrap>{$ligne["zorder"]}</td>";
    $html[]="<td style='vertical-align:middle;width:1%' nowrap>{default}</td>";
    $html[]="<td style='vertical-align:middle'>{when_connecting_to_method} {all_methods} {and} {for_everyone} (".$tpl->td_href("{expect_whitelist}",null,"s_PopUpFull('/proxy-whitelists','1024','900');").") {then} <strong>{force_using_thewebfilter_engine}</strong></td>";
    $html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle;width:1%' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle;width:1%' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";


    $bypass_webfilter_row=$tpl->_ENGINE_parse_body("{bypass_webfilter_row}");
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/proxy/webfilter/bypass/list"));
    $rows=0;
    if(!is_null($json->Rows)) {
        $rows = count($json->Rows);
    }
    $bypass_webfilter_row=str_replace("%s",$rows,$bypass_webfilter_row);

    $status="<span class='label label-primary'>{active2}</span>";
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS{$MUTED}' id='acl-glb'>";
    $html[]="<td class=\"center\" style='width:1%' nowrap>$status</td>";
    $html[]="<td class=\"center\" style='width:1%' nowrap>&nbsp;</td>";
    $html[]="<td style='vertical-align:middle;width:1%' nowrap>{default}</td>";
    $html[]="<td style='vertical-align:middle'>".$tpl->td_href($bypass_webfilter_row,null,"Loadjs('$page?bypasswebf=yes&function=$function')")."</td>";
    $html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle;width:1%' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle;width:1%' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";
    $html[]="</tbody>";
	$html[]="</table>";



    $TINY_ARRAY["TITLE"]="{web_filter_policies}";
    $TINY_ARRAY["ICO"]="fa fa-shield";
    $TINY_ARRAY["EXPL"]="{web_filter_policies_explain}";
    $TINY_ARRAY["URL"]="webfiltering-policies";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$jstiny
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$rulename=$q->sqlite_escape_string2($tpl->utf8_decode($_POST["rulename"]));
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
	

	
	$sql="INSERT INTO squid_url_rewrite_acls (".@implode(",", $FF).") VALUES (".@implode(",", $FA).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM squid_url_rewrite_acls WHERE rulename='$TempName'");
	$LASTID=$ligne["ID"];
	
	$q->QUERY_SQL("UPDATE squid_url_rewrite_acls SET rulename='$rulename' WHERE ID='$LASTID'");
	
	$c=0;
	$sql="SELECT ID FROM squid_url_rewrite_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_url_rewrite_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}
}

function rule_delete($ID):bool{
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM squid_url_rewrite_link WHERE aclid='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	$q->QUERY_SQL("DELETE FROM squid_url_rewrite_acls WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	return true;
	
}
function EXPLAIN_THIS_RULE($ID){
	
	$array_access["url_rewrite_access_deny"]="{pass_trough_thewebfilter_engine}";
	$array_access["url_rewrite_access_allow"]="{force_using_thewebfilter_engine}";

	$acls=new squid_acls_groups();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_url_rewrite_acls WHERE ID=$ID");
	$aclport=$ligne["aclport"];
	$ztype=$ligne["ztype"];

	$PORTS_DIRECTIONS=$acls->PORTS_DIRECTIONS();
	$method[]="{when_connecting_to_method} &laquo;{$PORTS_DIRECTIONS[$aclport]}&raquo;, ";
	$objects=$acls->getobjectsNameFromAclrule($ligne['ID'],"black","squid_url_rewrite_link");
	if(count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}
	$method[]="{then} <strong>$array_access[$ztype]</strong>";

    $FINAL=@implode(" ", $method);

    $tpl=new template_admin();
    $page=CurrentPageName();
    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$FINAL</span>");
}

