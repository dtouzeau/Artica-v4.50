<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
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

    $row = acls_api_get("/proxy/headers/acls/$ID");
    $rulename = is_array($row) ? ($row["ruleName"] ?? "") : "";
	if(!rule_delete($ID)){
        return false;}

	echo "$('#$md').remove();";
	return admin_tracks("Remove Proxy ACL header rule $rulename ($ID)");

}
function rule_enable():bool{
	header("content-type: application/x-javascript");
    $ID=intval($_GET["enable-js"]);
	$row = acls_api_get("/proxy/headers/acls/$ID");
    if(!is_array($row)){echo "alert('Failed to fetch rule $ID');";return false;}

    $rulename = $row["ruleName"] ?? "";

	if(intval($row["enabled"])==0){

		$js="$( \"#acl-$ID\" ).removeClass( \"text-muted\" );";
		$enabled=1;
		}
		else{
			$js="$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
			$enabled=0;
		}

	[$ok, $err] = acls_api_send("PUT","/proxy/headers/acls/$ID", ["enabled"=>$enabled]);
	if(!$ok){echo "alert('".addslashes($err)."')";return false;}
	echo $js;
    return admin_tracks("Set Proxy ACL header rule $rulename ($ID) to enable=$enabled");
}

function rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-id-js"]);
	$row = acls_api_get("/proxy/headers/acls/$ID");
    $rulename = is_array($row) ? ($row["ruleName"] ?? "") : "";
	return $tpl->js_dialog("{rule}: $ID $rulename","$page?rule-tabs=$ID");
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
	$ID=intval($_GET["rule-settings"]);
	$row = acls_api_get("/proxy/headers/acls/$ID");
    if(!is_array($row)){echo $tpl->_ENGINE_parse_body($tpl->div_error("Failed to load rule $ID"));return false;}



    $array_access["request_header_add"]="{add_client_header}";
    $array_access["request_header_allow"]="{allow_client_header}";
    $array_access["request_header_replace"]="{replace_client_header}";
    $array_access["request_header_deny"]="{delete_client_header}";
    $array_access["reply_header_add"]="{add_server_header}";
    $array_access["reply_header_allow"]="{allow_server_header}";
    $array_access["reply_header_deny"]="{delete_server_header}";
    $array_access["reply_header_replace"]="{replace_server_header}";
	$explain=$tpl->_ENGINE_parse_body("{new_acls_rule_header_explain}");





	$rulename = $tpl->utf8_encode($row["ruleName"] ?? "");
	$headername = $row["headerName"] ?? "";
	$ztype = $row["zType"] ?? "";
	$headervalue = base64_decode($row["headerValue"] ?? "");
	$aclport = $row["aclPort"] ?? 0;

	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("rulename", "{rule_name}", $rulename,true);
	$form[]=$tpl->field_array_hash(HEADERS_ARRAY(), "headername", "{http_header}", $headername);
	$form[]=$tpl->field_array_hash($array_access, "ztype", "{type}", $ztype);
	$form[]=$tpl->field_text("headervalue", "{new_value}",$headervalue);
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", $aclport);
	$jsafter="LoadAjax('table-acls2-rules','$page?table=yes');";
	$html=$tpl->form_outside($rulename, @implode("\n", $form),$explain,"{apply}",
        $jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function rule_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	$ID=intval($_POST["rule-save"]);
	$rulename=$tpl->utf8_decode($_POST["rulename"]);
	$headervalue=base64_encode($_POST["headervalue"]);

	$body = [
		"ruleName"    => $rulename,
		"headerName"  => $_POST["headername"],
		"zType"       => $_POST["ztype"],
		"headerValue" => $headervalue,
		"aclPort"     => $_POST["aclport"],
	];

	[$ok, $err] = acls_api_send("PUT","/proxy/headers/acls/$ID", $body);
	if(!$ok){echo htmlspecialchars($err);return false;}

	return admin_tracks_post("Save ACLS proxy header rule");
}

function new_rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	return $tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function HEADERS_ARRAY():array{
	$resultsHeaders = acls_api_get("/http-headers");
    $HEADERSZ=array();
    if(is_array($resultsHeaders)){
		foreach ($resultsHeaders as $ligneHeaders){
			$name = $ligneHeaders["headerName"] ?? "";
			if($name !== ""){$HEADERSZ[$name]=$name;}
		}
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
	$ID=intval($_GET["acl-rule-move"]);
	$dir=intval($_GET["acl-rule-dir"]);
	// dir: 1=up (lower order), 0=down (higher order)
	// API: PUT /proxy/headers/acls/{id}/move  body {dir: 0|1}
	[$ok, $err] = acls_api_send("PUT","/proxy/headers/acls/$ID/move", ["dir"=>$dir]);
	if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."');";return;}
	if($GLOBALS["VERBOSE"]){echo "moved $ID dir=$dir\n";}
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


    $users=new usersMenus();
    if(isWritter($users)) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
        $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");
    }

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>$order</th>";
	$html[]="<th>$rulename</th>";
	$html[]="<th>{$description}</th>";
	$html[]="<th class='center'>$enabled</th>";




	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='headers-rows'>";

	$jsAfter="LoadAjax('table-proxyheader-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$results = acls_api_get("/proxy/headers/acls");
	if(!is_array($results)){$results=[];}
	$TRCLASS=null;


	foreach($results as $ligne) {
		$TRCLASS=null;
		$MUTED=null;
		$ID=intval($ligne["id"]);
		$rn=utf8_encode($ligne["ruleName"] ?? "");

		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')","AsDansGuardianAdministrator");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if(intval($ligne["enabled"])==0){$MUTED=" text-muted";}


		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');","AsDansGuardianAdministrator");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');","AsDansGuardianAdministrator");


		$html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
		$html[]="<td class=\"center\" style='width:1%' nowrap>{$ligne["xOrder"]}</td>";
		$html[]="<td style='vertical-align:middle;width:1%' nowrap>". $tpl->td_href($rn,"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
		$html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')",null,"AsDansGuardianAdministrator")."</td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";

	}



	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='headers-pager'></span></div>";
	$html[]="</div></div>";

    $TINY_ARRAY["TITLE"]="{headers_rules_http}";
    $TINY_ARRAY["ICO"]="fa fa-shield";
    $TINY_ARRAY["EXPL"]="{headers_rules_mini_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    $js= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
<script>
$js
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"headers-rows","headers-pager");
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	$rulename=$tpl->utf8_decode($_POST["rulename"]);
	$headervalue=base64_encode($_POST["headervalue"]);

	$body = [
		"ruleName"    => $rulename,
		"headerName"  => $_POST["headername"],
		"zType"       => $_POST["ztype"],
		"headerValue" => $headervalue,
		"aclPort"     => $_POST["aclport"],
	];

	[$ok, $err] = acls_api_send("POST","/proxy/headers/acls", $body);
	if(!$ok){echo htmlspecialchars($err);return;}
}

function rule_delete($ID):bool{
	[$ok, $err] = acls_api_send("DELETE","/proxy/headers/acls/$ID");
	if(!$ok){echo "alert('".addslashes($err)."');";return false;}
	return true;
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
	$row = acls_api_get("/proxy/headers/acls/$ID");
	if(!is_array($row)){return "";}
	$aclport = $row["aclPort"] ?? 0;
	$ztype = $row["zType"] ?? "";
	$headername = $row["headerName"] ?? "";
	$headervalue=trim(base64_decode(trim($row["headerValue"] ?? "")));

	$PORTS_DIRECTIONS=$acls->PORTS_DIRECTIONS();
	$method[]="{when_connecting_to_method} &laquo;{$PORTS_DIRECTIONS[$aclport]}&raquo;, ";
	$objects=$acls->getobjectsNameFromAclrule(intval($row["id"]),"black","squid_http_headers_link");
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

function isReader($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }
    if($users->AsProxyMonitor){
        return true;
    }
    return false;
}
function isWritter($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }

    return false;
}
