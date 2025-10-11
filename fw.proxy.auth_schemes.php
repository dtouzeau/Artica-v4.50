<?php
$GLOBALS["SCHEME"]["digest"]="{digest_authentication}";
$GLOBALS["SCHEME"]["negotiate"]="{kerberos_authentication}";
$GLOBALS["SCHEME"]["ntlm"]="{ntlm_authentication}";
$GLOBALS["SCHEME"]["basic"]="{basic_authentication}";
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"]);exit;}
if(isset($_GET["table-start"])){table_start();exit;}
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
if(isset($_GET["via-tabs"])){via_tabs();exit;}

page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{auth_mec_pref}",
        ico_groups_settings,"{auth_mec_pref_explain}","$page?table-start=yes",
        "proxy-auth-mec","progress-squidauth-restart",false,"table-authscheme-rules");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}


function via_tabs():bool{
	$page=CurrentPageName();

	
	$html="<div id='table-authscheme-rules'></div>
    <script>
        LoadAjax('table-authscheme-rules','$page?table-start=yes');
    </script>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
	
}
function table_start():bool{
    $isSquid5=false;
    $SquidVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
    if(preg_match("#^(5|6|7|8|9)\.#",$SquidVersion)){$isSquid5=true;}

    if(!$isSquid5){
        $tpl=new template_admin();
        $TINY_ARRAY["TITLE"]="{auth_mec_pref}";
        $TINY_ARRAY["ICO"]=ico_groups_settings." text-danger";
        $TINY_ARRAY["URL"]="proxy-auth-mec";
        $TINY_ARRAY["EXPL"]="{auth_mec_pref_explain}<br><span class=\"text-danger\">{feature_only_squid5}</span>";
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
        echo $tpl->div_error("<strong>{APP_SQUID} v$SquidVersion.<br>{feature_only_squid5}");
        echo "<script>$jstiny</script>";
        return false;

    }

    $page=CurrentPageName();
    echo "<div id='table-authscheme-div' style='margin-top:10px'></div><script>LoadAjax('table-authscheme-div','$page?table=yes');</script>";
    return true;
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
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM squid_auth_schemes_acls WHERE ID='{$_GET["enable-js"]}'");
	if(intval($ligne["enabled"])==0){
		
		$js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
		$enabled=1;
		}
		else{
			$js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
			$enabled=0;
		}
	
	$q->QUERY_SQL("UPDATE squid_auth_schemes_acls SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return;}
	echo $js;
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-id-js"]);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT aclname FROM squid_auth_schemes_acls WHERE ID='$ID'");
	$tpl->js_dialog("{rule}: $ID {$ligne["aclname"]}","$page?rule-tabs=$ID");
}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";
	
	$RefreshTable=base64_encode("LoadAjax('table-authscheme-rules','$page?table=yes');");
	
	$array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=squid_auth_schemes_link&RefreshTable=$RefreshTable&fastacls=1";
	echo $tpl->tabs_default($array);
	
}


function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-settings"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_auth_schemes_acls WHERE ID='{$_GET["rule-settings"]}'");



    $explain=$tpl->_ENGINE_parse_body("{new_acls_rule_auth_schemes_explain}");
    $ligne["rulename"]=utf8_encode($ligne["rulename"]);

	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("rulename", "{rule_name}", $ligne["rulename"],true);
	$form[]=$tpl->field_array_hash($GLOBALS["SCHEME"], "ztype", "{authentication_method}", $ligne["ztype"]);
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", $ligne["aclport"]);
	$jsafter="LoadAjax('table-authscheme-rules','$page?table=yes');";
	$html=$tpl->form_outside($ligne["rulename"], @implode("\n", $form),$explain,"{apply}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$ID=$_POST["rule-save"];
	$rulename=sqlite_escape_string2(utf8_decode($_POST["rulename"]));
	$TempName=md5(time());
	
	$FF[]="`rulename`";
	$FF[]="`ztype`";
	$FF[]="`aclport`";
	$FF[]="`zorder`";
	$FF[]="`enabled`";
	
	$FA[]="'$rulename'";
	$FA[]="'{$_POST["ztype"]}'";
	$FA[]="'{$_POST["aclport"]}'";
	$FA[]="'{$_POST["zorder"]}'";
	$FA[]="'{$_POST["enabled"]}'";
	
	foreach ($FF as $index=>$key){$FB[]="$key={$FA[$index]}";}
	
	$sql="UPDATE squid_auth_schemes_acls SET ".@implode(",", $FB)." WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}

    admin_tracks("Update a new authentication {$_POST["ztype"]} sheme rule $rulename");
	
	$c=0;
	$sql="SELECT ID FROM squid_auth_schemes_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_auth_schemes_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
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



function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql_squid_builder();
    $explain=$tpl->_ENGINE_parse_body("{new_acls_rule_auth_schemes_explain}");
    $form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
	$form[]=$tpl->field_array_hash($GLOBALS["SCHEME"], "ztype", "{type}", "request_header_replace");
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", 0);
	$jsafter="LoadAjax('table-authscheme-div','$page?table=yes');BootstrapDialog1.close();BootstrapDialog1.close();";

	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),$explain,"{add}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}



function rule_move(){
	$tpl=new template_admin();
	$ID=$_GET["acl-rule-move"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT zorder FROM squid_auth_schemes_acls WHERE `ID`='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
	$xORDER_ORG=intval($ligne["zorder"]);
	$xORDER=$xORDER_ORG;
	
	
	if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE squid_auth_schemes_acls SET zorder=$xORDER WHERE `ID`='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if($_GET["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE squid_auth_schemes_acls SET zorder=$xORDER2 WHERE `ID`<>'$ID' AND zorder=$xORDER";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	}
	if($_GET["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE squid_auth_schemes_acls SET zorder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	}

	$c=0;
	$sql="SELECT ID FROM squid_auth_schemes_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);

	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_auth_schemes_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if($GLOBALS["VERBOSE"]){echo "UPDATE squid_auth_schemes_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}\n";}
		$c++;
	}


}


function table(){
	$tpl=new template_admin();
    $users=new usersMenus();
	$page=CurrentPageName();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$js="OnClick=\"javascript:LoadAjax('table-firewall-rules','$page?table=yes&eth=');\"";
	$class=null;
	$t=time();
	$add="Loadjs('$page?newrule-js=yes',true);";


    $jsrestart=$tpl->framework_buildjs("/proxy/nohup/reconfigure","squid.articarest.nohup","squid.articarest.log","squid-auth-schemes-progress", "");

    $jshelp="s_PopUpFull('https://wiki.articatech.com/proxy-service/authentication/auth_schemes',1024,768,'Auth Schemes')";

    if($users->AsSquidAdministrator) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
        $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");
    }
    $topbuttons[]=array($jshelp,ico_support,"Wiki");
    $btns=$tpl->table_buttons($topbuttons);


	$html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%'>$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$rulename</th>";
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
	
	$results=$q->QUERY_SQL("SELECT * FROM squid_auth_schemes_acls ORDER BY zorder");
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
		$html[]="<td class=\"center\" width=1% nowrap>{$ligne["zorder"]}</td>";
		$html[]="<td style='vertical-align:middle' width=1% nowrap>". $tpl->td_href($ligne["rulename"],"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
		$html[]="<td class='center' width=1% nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')")."</td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";
	
	}
    $html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
    $html[]="<td class=\"center\" width=1% nowrap>999999</td>";
    $html[]="<td style='vertical-align:middle' width=1% nowrap>{default}</td>";
    $html[]="<td style='vertical-align:middle'>{for} {all} {then} {provide_auth_sheme} &laquo;&laquo;kerberos,ntlm,basic,digest&raquo;&raquo;</td>";
    $html[]="<td class='center' width=1% nowrap>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>".$tpl->icon_nothing()."</center></td>";
    $html[]="</tr>";
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";


    $TINY_ARRAY["BUTTONS"]=$btns;
    $TINY_ARRAY["TITLE"]="{auth_mec_pref}";
    $TINY_ARRAY["ICO"]=ico_groups_settings;
    $TINY_ARRAY["URL"]="proxy-auth-mec";
    $TINY_ARRAY["EXPL"]="{auth_mec_pref_explain}";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    
    
	$html[]="
<script> 
    $jstiny
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
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
	$FF[]="`ztype`";
	$FF[]="`aclport`";
	$FF[]="`zorder`";
	$FF[]="`enabled`";
	
	$FA[]="'$TempName'";
	$FA[]="'{$_POST["ztype"]}'";
	$FA[]="'{$_POST["aclport"]}'";
	$FA[]=99999;
	$FA[]=1;
	

	
	$sql="INSERT INTO squid_auth_schemes_acls (".@implode(",", $FF).") VALUES (".@implode(",", $FA).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
    admin_tracks("Creating a new authentication {$_POST["ztype"]} sheme rule $rulename");
	
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM squid_auth_schemes_acls WHERE rulename='$TempName'");
	$LASTID=$ligne["ID"];
	
	$q->QUERY_SQL("UPDATE squid_auth_schemes_acls SET rulename='$rulename' WHERE ID='$LASTID'");
	
	$c=0;
	$sql="SELECT ID FROM squid_auth_schemes_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_auth_schemes_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}
}

function rule_delete($ID){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_auth_schemes_acls WHERE ID=$ID");
    $rulename=$ligne["rulename"];
	$q->QUERY_SQL("DELETE FROM squid_auth_schemes_link WHERE aclid='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	$q->QUERY_SQL("DELETE FROM squid_auth_schemes_acls WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
    admin_tracks("Deleted authentication rule $rulename");
	return true;
	
}
function EXPLAIN_THIS_RULE($ID){

	$acls=new squid_acls_groups();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_auth_schemes_acls WHERE ID=$ID");
	$aclport=$ligne["aclport"];
	$ztype=$ligne["ztype"];

	$PORTS_DIRECTIONS=$acls->PORTS_DIRECTIONS();
	$method[]="{when_connecting_to_method} &laquo;{$PORTS_DIRECTIONS[$aclport]}&raquo;, ";
	$objects=$acls->getobjectsNameFromAclrule($ligne['ID'],"black","squid_auth_schemes_link");
	if(count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}


    $sheme=$GLOBALS["SCHEME"][$ztype];

	$method[]="{then} {provide_auth_sheme} &laquo;&laquo;$sheme&raquo;&raquo;";
	$FINAL=@implode(" ", $method);

    $tpl=new template_admin();
    $page=CurrentPageName();
    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$FINAL</span>");
}

