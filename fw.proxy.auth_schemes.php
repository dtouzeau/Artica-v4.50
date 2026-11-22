<?php
$GLOBALS["SCHEME"]["digest"]="{digest_authentication}";
$GLOBALS["SCHEME"]["negotiate"]="{kerberos_authentication}";
$GLOBALS["SCHEME"]["ntlm"]="{ntlm_authentication}";
$GLOBALS["SCHEME"]["basic"]="{basic_authentication}";
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Pentest 2026-11-04 : explain-this-rule/rule-delete-js etaient recopies sans encodage dans la page (XSS).
foreach(array("explain-this-rule","rule-delete-js") as $xssk){if(isset($_GET[$xssk])){$_GET[$xssk]=intval($_GET[$xssk]);}}
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
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["enable-js"]);
	$data=acls_api_get("/proxy/auth-schemes/acls/$ID");
	if(!is_array($data)){
		echo "alert('auth-schemes get failed for ID=$ID');\n";
		return;
	}
	$enabled_src=intval($data["enabled"] ?? 0);
	$enabled=($enabled_src==0) ? 1 : 0;
	$js=($enabled_src==0)
		? "$( \"#acl-$ID\" ).removeClass( \"text-muted\" );"
		: "$( \"#acl-$ID\" ).addClass( \"text-muted\" );";

	list($ok,$err)=acls_api_send("PUT","/proxy/auth-schemes/acls/$ID",["enabled"=>$enabled]);
	if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."');\n";return;}
	echo $js;
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-id-js"]);
	$data=acls_api_get("/proxy/auth-schemes/acls/$ID");
	$ruleName=is_array($data) ? ($data["ruleName"] ?? "") : "";
	$tpl->js_dialog("{rule}: $ID $ruleName","$page?rule-tabs=$ID");
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
	$ID=intval($_GET["rule-settings"]);
	$data=acls_api_get("/proxy/auth-schemes/acls/$ID");
	if(!is_array($data)){
		echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
		return;
	}

    $explain=$tpl->_ENGINE_parse_body("{new_acls_rule_auth_schemes_explain}");
    $ruleName=$tpl->utf8_encode($data["ruleName"] ?? "");

	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("rulename", "{rule_name}", $ruleName,true);
	$form[]=$tpl->field_array_hash($GLOBALS["SCHEME"], "ztype", "{authentication_method}", $data["zType"] ?? "");
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", $data["aclPort"] ?? 0);
	$jsafter="LoadAjax('table-authscheme-rules','$page?table=yes');";
	$html=$tpl->form_outside($ruleName, @implode("\n", $form),$explain,"{apply}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["rule-save"]);
	$body=[
		"ruleName" => url_decode_special_tool($_POST["rulename"] ?? ""),
		"zType"    => $_POST["ztype"]  ?? "",
		"aclPort"  => intval($_POST["aclport"] ?? 0),
	];
	list($ok,$err)=acls_api_send("PUT","/proxy/auth-schemes/acls/$ID",$body);
	if(!$ok){echo htmlspecialchars($err);return;}
    admin_tracks("Update authentication {$_POST["ztype"]} scheme rule {$_POST["rulename"]}");
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
	$ID=intval($_GET["acl-rule-move"]);
	$dir=intval($_GET["acl-rule-dir"]);
	list($ok,$err)=acls_api_send("PUT","/proxy/auth-schemes/acls/$ID/move",["dir"=>$dir]);
	if(!$ok){
		header("content-type: application/x-javascript");
		echo "alert('".$tpl->javascript_parse_text($err)."');";
		return;
	}
	if($GLOBALS["VERBOSE"]){echo "// rule_move ID=$ID dir=$dir OK\n";}
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


	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>$order</th>";
	$html[]="<th class='awc-nowrap'>$rulename</th>";
	$html[]="<th>{$description}</th>";
	$html[]="<th class='center'>$enabled</th>";
	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='authschemes-rows'>";
	
	$rows=acls_api_get("/proxy/auth-schemes/acls");
	if(!is_array($rows)){$rows=[];}
	$TRCLASS=null;

	foreach($rows as $ligne) {
		$TRCLASS=null;
		$MUTED=null;
		$ID=intval($ligne["id"]);
		$ruleName=htmlspecialchars($ligne["ruleName"] ?? "");

		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if(intval($ligne["enabled"] ?? 1)==0){$MUTED=" text-muted";}

		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");

		$html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
		$html[]="<td class=\"center\" style='width:1%' nowrap>".intval($ligne["xOrder"] ?? 0)."</td>";
		$html[]="<td style='vertical-align:middle' style='width:1%' nowrap>". $tpl->td_href($ruleName,"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
		$html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_check(intval($ligne["enabled"] ?? 1),"Loadjs('$page?enable-js=$ID')")."</td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";

	}
    $html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
    $html[]="<td class=\"center\" style='width:1%' nowrap>999999</td>";
    $html[]="<td style='vertical-align:middle' style='width:1%' nowrap>{default}</td>";
    $html[]="<td style='vertical-align:middle'>{for} {all} {then} {provide_auth_sheme} &laquo;&laquo;kerberos,ntlm,basic,digest&raquo;&raquo;</td>";
    $html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>".$tpl->icon_nothing()."</center></td>";
    $html[]="</tr>";
	
	
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='authschemes-pager'></span></div>";
	$html[]="</div></div>";


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
	
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"authschemes-rows","authschemes-pager");
    return true;
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$body=[
		"ruleName" => url_decode_special_tool($_POST["rulename"] ?? ""),
		"zType"    => $_POST["ztype"]  ?? "",
		"aclPort"  => intval($_POST["aclport"] ?? 0),
	];
	list($ok,$err)=acls_api_send("POST","/proxy/auth-schemes/acls",$body);
	if(!$ok){echo htmlspecialchars($err);return;}
    admin_tracks("Creating a new authentication {$_POST["ztype"]} scheme rule {$_POST["rulename"]}");
}

function rule_delete($ID){
	$tpl=new template_admin();
	$data=acls_api_get("/proxy/auth-schemes/acls/$ID");
	$ruleName=is_array($data) ? ($data["ruleName"] ?? $ID) : $ID;
	list($ok,$err)=acls_api_send("DELETE","/proxy/auth-schemes/acls/$ID");
	if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."');";return false;}
    admin_tracks("Deleted authentication rule $ruleName");
	return true;
}
function EXPLAIN_THIS_RULE($ID){
	$acls=new squid_acls_groups();
	$data=acls_api_get("/proxy/auth-schemes/acls/$ID");
	if(!is_array($data)){return "";}
	$aclport=$data["aclPort"] ?? 0;
	$ztype=$data["zType"]    ?? "";

	$PORTS_DIRECTIONS=$acls->PORTS_DIRECTIONS();
	$method[]="{when_connecting_to_method} &laquo;".($PORTS_DIRECTIONS[$aclport] ?? $aclport)."&raquo;, ";
	$objects=$acls->getobjectsNameFromAclrule(intval($data["id"] ?? $ID),"black","squid_auth_schemes_link");
	if(count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}

    $sheme=$GLOBALS["SCHEME"][$ztype] ?? $ztype;
	$method[]="{then} {provide_auth_sheme} &laquo;&laquo;$sheme&raquo;&raquo;";
	$FINAL=@implode(" ", $method);

    $tpl=new template_admin();
    $page=CurrentPageName();
    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }
    return $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$FINAL</span>");
}

