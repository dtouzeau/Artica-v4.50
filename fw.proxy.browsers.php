<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.useragents.inc");

// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
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
    list($ok,)=acls_api_send("DELETE","/proxy/browsers/rules");
    if(!$ok){writelogs("delete_all_perform: API error",__FUNCTION__,__FILE__,__LINE__);}
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
	$ID=intval($_GET["rule-delete-js"]);
	$md=$_GET["md"];
	list($ok,$data)=acls_api_send("DELETE","/proxy/browsers/rules/$ID");
	// Pentest 2026-11-04 : message d'erreur REST (donnee externe) affiche sans encodage (XSS stockee).
	if(!$ok){echo "alert('Delete failed: ".req_js($data ?? 'error')."');";return false;}
    admin_tracks("Remove Proxy ACL for User-Agent ID=$ID");
	echo "$('#$md').remove();";
	return true;

}
function rule_enable():bool{
	header("content-type: application/x-javascript");
	$ID=intval($_GET["enable-js"]);
	$field=$_GET["field"];
	$allowed=["bypass","bypassWebF","bypassWebC","enabled","deny"];
	if(!in_array($field,$allowed)){echo "alert('invalid field');";return false;}
	$row=acls_api_get("/proxy/browsers/rules/$ID");
	if(!is_array($row)){echo "alert('Rule not found');";return false;}
	$current=intval($row[$field] ?? 0);
	$enabled=($current==0)?1:0;
	list($ok,$data)=acls_api_send("PUT","/proxy/browsers/rules/$ID",[$field=>$enabled]);
	// Pentest 2026-11-04 : message d'erreur REST (donnee externe) affiche sans encodage (XSS stockee).
	if(!$ok){echo "alert('".req_js($data)."')";return false;}
    admin_tracks("Set Proxy ACL for User-Agent enabled=$enabled for ID=$ID");

    return true;

}

function rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-id-js"]);
	if($ID==0){
        $tpl->js_dialog("{new_rule}","$page?rule-tabs=0");
        return true;
    }
	$row=acls_api_get("/proxy/browsers/rules/$ID");
	$pattern=is_array($row) ? ($row["pattern"] ?? "") : "";
	$tpl->js_dialog("{rule}: $ID $pattern","$page?rule-tabs=$ID");
    return true;
}



function rule_tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";
	echo $tpl->tabs_default($array);
    return true;
}


function rule_settings():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-settings"];

    if(!is_numeric($ID)){$ID=0;}

    $t=time();

    if($ID==0){
        $title="{new_rule}";
        $btname="{add}";
        $ligne=["enabled"=>1,"pattern"=>"","editor"=>"","explain"=>"","category"=>"","bypass"=>0,"deny"=>0,"bypassWebF"=>0,"bypassWebC"=>0];

    }else{
        $row=acls_api_get("/proxy/browsers/rules/$ID");
        if(!is_array($row)){echo "{error}";return false;}
        $ligne=$row;
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

    if($_POST["deny"]==1){
        $_POST["bypassWebF"]=0;
        $_POST["bypassWebC"]=0;
        $_POST["bypass"]=0;

    }

    if($_POST["bypass"]==1){
        $_POST["deny"]=0;
    }

    $pattern=$tpl->CLEAN_BAD_XSS_SQL($_POST["pattern"]);
    $editor=$tpl->CLEAN_BAD_XSS($_POST["editor"]);
    $explain=$tpl->CLEAN_BAD_XSS($_POST["explain"]);
    $category=$tpl->CLEAN_BAD_XSS($_POST["category"] ?? "");

    $body=[
        "pattern"    => $pattern,
        "editor"     => $editor,
        "explain"    => $explain,
        "bypass"     => intval($_POST["bypass"]),
        "bypassWebF" => intval($_POST["bypassWebF"]),
        "bypassWebC" => intval($_POST["bypassWebC"]),
        "enabled"    => intval($_POST["enabled"] ?? 1),
        "deny"       => intval($_POST["deny"]),
    ];

    if($ID>0){
        list($ok,$data)=acls_api_send("PUT","/proxy/browsers/rules/$ID",$body);
    }else{
        $body["category"]=$category;
        list($ok,$data)=acls_api_send("POST","/proxy/browsers/rules",$body);
    }

    if(!$ok){echo ($data ?? "error")."\n";}
    return true;

}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function new_rule_popup(){
    // This handler is referenced in the dispatcher but has no implementation.
    // The new_rule_js/rule_settings path handles creation via rule-tabs=0.
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{new_rule}","$page?rule-settings=0");
}

function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$jsdelete="Loadjs('$page?delete-all=yes');";
    $t=time();
	$add="Loadjs('$page?rule-id-js=0',true);";
    $users=new usersMenus();
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
	$ARRAY["CMD"]="/proxy/acls/browsers";
	$ARRAY["TITLE"]="{GLOBAL_ACCESS_CENTER}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-acls5-restart')";

    if($users->AsDansGuardianAdministrator) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
        $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");
        $topbuttons[] = array($jsdelete, ico_trash, "{delete_all}");
    }
    // Recherche cote navigateur: awc_filter_js() compare le texte de la ligne
    // entiere, donc le motif d'user-agent, l'editeur ET la categorie.
    $html[]=awc_search_bar("browsers-search","{search}");

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>{pattern}</th>";
	$html[]="<th class='awc-nowrap'>{vendor}</th>";
	$html[]="<th>{category}</th>";
	$html[]="<th class='center'>{enabled}</th>";
    $html[]="<th class='awc-nowrap'>{whitelist}</th>";
    $html[]="<th class='awc-nowrap'>{deny}</th>";
    $html[]="<th class='awc-nowrap'>{bypass_webfilter}</th>";
    $html[]="<th class='awc-nowrap'>{no_cache}</th>";

    $html[]="<th></th>";

	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='browsers-rows'>";
	$ROWS=0;

	$jsAfter="LoadAjax('table-proxyheader-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$results=acls_api_get("/proxy/browsers/rules");
	if(!is_array($results)){$results=[];}
	if(count($results)==0){
        $cl=new useragents();
        $cl->checkTable();
        $results=acls_api_get("/proxy/browsers/rules");
        if(!is_array($results)){$results=[];}
    }
	$TRCLASS=null;


	foreach($results as $index=>$ligne) {
		$TRCLASS=null;
		$md=md5(serialize($ligne));
		$ID=intval($ligne["id"]);
        $MUTED=null;
		// Pentest 2026-11-04 : pattern/editor/category (donnee externe) affiche sans encodage (XSS stockee).
		$pattern=req_html($ligne["pattern"] ?? "");

		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID&md=$md')","AsDansGuardianAdministrator");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if(intval($ligne["enabled"] ?? 1)==0){$MUTED=" text-muted";}

        $pattern=$tpl->td_href($pattern,null,$js);

		$bypass=$tpl->icon_check($ligne["bypass"],"Loadjs('$page?enable-js=$ID&field=bypass')",null,"AsDansGuardianAdministrator");
        $bypassWebF=$tpl->icon_check($ligne["bypassWebF"],"Loadjs('$page?enable-js=$ID&field=bypassWebF')",null,"AsDansGuardianAdministrator");
        $bypassWebC=$tpl->icon_check($ligne["bypassWebC"],"Loadjs('$page?enable-js=$ID&field=bypassWebC')",null,"AsDansGuardianAdministrator");
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID&field=enabled')",null,"AsDansGuardianAdministrator");
        $deny=$tpl->icon_check($ligne["deny"],"Loadjs('$page?enable-js=$ID&field=deny')",null,"AsDansGuardianAdministrator");

        $editor=req_html($ligne["editor"] ?? "");
        $category=req_html($ligne["category"] ?? "");

		$ROWS++;
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=99% nowrap><strong>$pattern</strong></td>";
		$html[]="<td style='width:1%' nowrap>$editor</td>";
        $html[]="<td style='width:1%' nowrap>$category</td>";
		$html[]="<td class='center' style='width:1%' nowrap>$enabled</td>";
        $html[]="<td class='center' style='width:1%' nowrap>$bypass</td>";
        $html[]="<td class='center' style='width:1%' nowrap>$deny</td>";
        $html[]="<td class='center' style='width:1%' nowrap>$bypassWebF</td>";
        $html[]="<td class='center' style='width:1%' nowrap>$bypassWebC</td>";
		$html[]="<td class='center' style='width:1%' nowrap>$delete</td>";
		$html[]="</tr>";

	}

    $TINY_ARRAY["TITLE"]="{browsers_rules}";
    $TINY_ARRAY["ICO"]=ico_ie;
    $TINY_ARRAY["EXPL"]="{browsers_ntlm_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>".$tpl->FormatNumber($ROWS)." {rules}</span>"
	       ."<span class='awc-pg' id='browsers-pager'></span></div>";
	$html[]="</div></div>";

	// Le <script> SORT du tableau: il etait dans $html, donc il traversait
	// _ENGINE_parse_body(), dont la regex #{(.+?)}# lit chaque corps de fonction
	// JavaScript comme un jeton -- et il y a CINQ icon_check() par ligne, donc
	// autant de BtSwitch<id>(){...} a analyser puis chercher en memcached.
	// ICON_SCRIPTS_COMPILE() et non $tpl->ICON_SCRIPTS: il draine aussi la pile
	// globale que icon_check() alimente en parallele.
	$script="<script>\n$headsjs\nNoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n</script>";

	// CSS et JS concatenes AUTOUR de l'analyse; le filtre APRES le pager, qui
	// lui passe la main par tb.__awcRedraw.
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .$script
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"browsers-rows","browsers-pager")
	    .awc_filter_js("browsers-search","browsers-rows","browsers-pager");
    return true;
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	$rulename=$tpl->CLEAN_BAD_XSS_SQL(utf8_decode($_POST["rulename"] ?? ""));
	$headervalue=base64_encode($_POST["headervalue"] ?? "");

	list($ok,$data)=acls_api_send("POST","/proxy/headers/acls",[
		"ruleName"    => $rulename,
		"zType"       => $_POST["ztype"] ?? "",
		"headerName"  => $_POST["headername"] ?? "",
		"headerValue" => $headervalue,
		"aclPort"     => intval($_POST["aclport"] ?? 0),
	]);
	if(!$ok){echo $data ?? "error";return;}
}

function rule_delete($ID){
	list($ok,$data)=acls_api_send("DELETE","/proxy/headers/acls/$ID");
	if(!$ok){echo "alert('".req_js($data ?? 'error')."');";return;}
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
	$ligne=acls_api_get("/proxy/headers/acls/$ID");
	if(!is_array($ligne)){return "";}
	$aclport=$ligne["aclPort"] ?? 0;
	$ztype=$ligne["zType"] ?? "";
	// Pentest 2026-11-04 : headerName/headerValue (donnee externe) affiche sans encodage (XSS stockee).
	$headername=req_html($ligne["headerName"] ?? "");
	$headervalue=req_html($ligne["headerValue"] ?? "");

	$PORTS_DIRECTIONS=$acls->PORTS_DIRECTIONS();
	$method[]="{when_connecting_to_method} &laquo;{$PORTS_DIRECTIONS[$aclport]}&raquo;, ";
	$objects=$acls->getobjectsNameFromAclrule(intval($ID),"black","squid_http_headers_link");
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

