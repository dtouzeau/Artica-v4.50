<?php
// patched 18/05/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
    exit();
}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["bypasswebf"])){bypasswebf_js();exit;}
if(isset($_GET["bypasswebf-popup"])){bypasswebf_popup();exit;}
if(isset($_GET["bypasswebf-delete"])){bypasswebf_delete();exit;}

// intval(): l'identifiant partait BRUT dans le chemin REST /proxy/url-rewrite/acls/$ID.
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE(intval($_GET["explain-this-rule"]));exit;}
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


/**
 * Nom de la fonction JavaScript de rafraichissement passee par le tableau.
 * SECURITE: cette valeur partait BRUTE dans des attributs OnClick et dans les
 * $jsafter des modales -- un lien force ?function=0;alert(document.cookie)//
 * executait donc du JavaScript dans la session de l'administrateur (XSS
 * reflechie). search_block ne genere jamais autre chose que "ss<time>".
 */
function urw_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}

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
    $function=urw_function();
    return $tpl->js_dialog("{rules}","$page?bypasswebf-popup=yes&function=$function");
}

function bypasswebf_delete():bool{
    $tpl=new template_admin();
    $site=(string)$_GET["bypasswebf-delete"];
    $function=urw_function();
    // Le nom de site partait BRUT dans le chemin REST, et $_GET["md"] -- un md5 --
    // partait BRUT dans un selecteur jQuery emis en JavaScript.
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/webfilter/bypass/delete/".rawurlencode($site)));
    // json_decode() rend NULL quand le daemon ne repond pas: lire ->Status dessus
    // est une erreur fatale en PHP 8.
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        $tpl->popup_error(is_object($json) && isset($json->Error)?$json->Error:"{unknown_error}");
        return false;
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "$function();";
    return admin_tracks("Remove $site From Web Filter whitelist");
}

function rule_delete_js():bool{
	header("content-type: application/x-javascript");
	$ID=intval($_GET["rule-delete-js"]);
	$md="acl-$ID";

    $row = acls_api_get("/proxy/url-rewrite/acls/$ID");
    $rulename = is_array($row) ? ($row["ruleName"] ?? "") : "";
	if(!rule_delete($ID)){return false;}
	echo "$('#$md').remove();";
	return admin_tracks("Remove URL rewrite ACL rule $rulename ($ID)");
}

function rule_enable():bool{
	header("content-type: application/x-javascript");
	$ID=intval($_GET["enable-js"]);
	$row = acls_api_get("/proxy/url-rewrite/acls/$ID");
    if(!is_array($row)){echo "alert('Failed to fetch rule $ID');";return false;}

    $rulename = $row["ruleName"] ?? "";

	if(intval($row["enabled"])==0){
		$js="$( \"#acl-$ID\" ).removeClass( \"text-muted\" );";
		$enabled=1;
	} else {
		$js="$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
		$enabled=0;
	}

	[$ok, $err] = acls_api_send("PUT","/proxy/url-rewrite/acls/$ID", ["enabled"=>$enabled]);
	if(!$ok){echo "alert('".addslashes($err)."')";return false;}
	echo $js;
    return admin_tracks("Set URL rewrite ACL rule $rulename ($ID) to enable=$enabled");
}

function rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-id-js"]);
    $function=urw_function();
	$row = acls_api_get("/proxy/url-rewrite/acls/$ID");
    $rulename = is_array($row) ? ($row["ruleName"] ?? "") : "";
	return $tpl->js_dialog("{rule}: $ID $rulename","$page?rule-tabs=$ID&function=$function");
}

function rule_tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
    $function=urw_function();
	$array["{rule}"]="$page?rule-settings=$ID&function=$function";
    $RefreshFunction=base64_encode("$function();");
    $array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=squid_url_rewrite_link&RefreshFunction=$RefreshFunction";
	echo $tpl->tabs_default($array);
	return true;
}

function rule_settings():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLUSTER_CLI=true;
	$ID=intval($_GET["rule-settings"]);
	$row = acls_api_get("/proxy/url-rewrite/acls/$ID");
    if(!is_array($row)){echo $tpl->_ENGINE_parse_body($tpl->div_error("Failed to load rule $ID"));return false;}
    $function=urw_function();

	$array_access["url_rewrite_access_deny"]="{pass_trough_thewebfilter_engine}";
    $array_access["url_rewrite_access_allow"]="{force_using_thewebfilter_engine}";
	$explain=null;

	$rulename = $tpl->utf8_encode($row["ruleName"] ?? "");
	$ztype = $row["zType"] ?? "";
	$aclport = $row["aclPort"] ?? 0;

	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("rulename", "{rule_name}", $rulename,true);
	$form[]=$tpl->field_array_hash($array_access, "ztype", "{type}", $ztype);
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", $aclport);
	$jsafter="$function()";
	$html=$tpl->form_outside($rulename, @implode("\n", $form),$explain,"{apply}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function rule_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();

	$ID=intval($_POST["rule-save"]);
	$rulename=$tpl->utf8_decode($_POST["rulename"]);

	$body = [
		"ruleName"    => $rulename,
		"zType"       => $_POST["ztype"] ?? "",
		"aclPort"     => intval($_POST["aclport"] ?? 0),
	];
	// headerName / headerValue etaient envoyes INCONDITIONNELLEMENT, alors que le
	// formulaire d'edition ne comporte aucun de ces deux champs: la valeur postee
	// etait donc toujours la chaine vide. Or UpdateURLRewriteACL() du daemon est
	// une mise a jour PARTIELLE a base de pointeurs -- une cle presente, meme
	// vide, est ecrite. Renommer une regle effacait donc silencieusement son
	// en-tete HTTP. On ne transmet plus ces cles que si elles sont reellement
	// postees.
	if(isset($_POST["headername"])){$body["headerName"]=$_POST["headername"];}
	if(isset($_POST["headervalue"])){$body["headerValue"]=base64_encode($_POST["headervalue"]);}

	[$ok, $err] = acls_api_send("PUT","/proxy/url-rewrite/acls/$ID", $body);
	if(!$ok){echo htmlspecialchars($err);return false;}

	return admin_tracks("Add/update a new webfiltering policy $rulename");
}

function new_rule_js():bool{
    $function=urw_function();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	return $tpl->js_dialog($title,"$page?newrule-popup=yes&function=$function");
}

// HEADERS_ARRAY() etait declaree ici sans etre appelee nulle part: aucun des deux
// formulaires de cette page ne propose de champ d'en-tete HTTP. Cinq autres pages
// declarent leur propre copie de cette fonction, qui reste donc disponible la ou
// elle sert reellement.

function new_rule_popup():bool{
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $function=urw_function();
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
	return true;
}

function rule_move(){
	$tpl=new template_admin();
	$ID=intval($_GET["acl-rule-move"]);
	$dir=intval($_GET["acl-rule-dir"]);
	// dir: 1=up (lower order), 0=down (higher order)
	[$ok, $err] = acls_api_send("PUT","/proxy/url-rewrite/acls/$ID/move", ["dir"=>$dir]);
	if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."');";return;}
	if($GLOBALS["VERBOSE"]){echo "moved $ID dir=$dir\n";}
}

function bypasswebf_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=urw_function();
    $sitename=$tpl->_ENGINE_parse_body("{sitename}");
    $showing=$tpl->_ENGINE_parse_body("{showing}");
    $nodata=$tpl->_ENGINE_parse_body("{no_data}");

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>$sitename</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='bypass-rows'>";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/webfilter/bypass/list"));
    // L'ancienne version parcourait $json->Rows sans aucune verification: une
    // reponse vide du daemon produisait une erreur fatale en PHP 8.
    $rows=(is_object($json) && isset($json->Rows) && is_array($json->Rows))?$json->Rows:array();

    $c=0;
    foreach($rows as $site){
        $site=trim((string)$site);
        if($site==""){continue;}
        $c++;
        $html[]=urw_bypass_row($tpl,$page,$function,$site);
    }
    if($c==0){
        $html[]="<tr><td colspan='2' class='awc-empty'>$nodata</td></tr>";
    }

    // L'ancienne version fermait la boucle par un </tr> ORPHELIN, en dehors de
    // toute ligne, et placait le pied a l'interieur du corps du tableau.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>$showing $c</span>";
    $html[]="<span class='awc-pg' id='bypass-pager'></span>";
    $html[]="</div></div></div>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
        .awc_pager_js(50,"bypass-rows","bypass-pager");
    return true;
}

/**
 * Une ligne de la liste des sites contournant le filtrage web.
 * Le nom du site vient du daemon et partait BRUT dans le HTML comme dans l'URL
 * du Loadjs de suppression. rawurlencode() le rend sur de bout en bout: dans
 * l'URL, dans la chaine JavaScript et dans l'attribut HTML qui la contient.
 */
function urw_bypass_row($tpl,$page,$function,$site):string{
    $md=md5($site);
    $enc=rawurlencode($site);
    $delete=$tpl->icon_delete("Loadjs('$page?bypasswebf-delete=$enc&function=$function&md=$md')");
    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-globe'></i></span>";
    $h.="<span class='awc-name'>".htmlspecialchars($site)."</span>";
    $h.="</div></td>";
    $h.="<td><div class='awc-acts'>$delete</div></td>";
    $h.="</tr>";
    return $h;
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$status_t=$tpl->_ENGINE_parse_body("{status}");
	$showing=$tpl->_ENGINE_parse_body("{showing}");
	$nodata=$tpl->_ENGINE_parse_body("{no_data}");
	$function=urw_function();
	$add="Loadjs('$page?newrule-js=yes&function=$function',true);";

	$jsrestart=$tpl->framework_buildjs("/proxy/webfilter/policies",
		"squid.webfilter.policies.progress",
		"squid.webfilter.policies.log",
		"progress-acls1-restart",
		"$function()",null,null,"AsDansGuardianAdministrator");

	$search=$_GET["search"] ?? null;

	$topbuttons=array();
	$topbuttons[]=array($add,ico_plus,"{new_rule}");
	$topbuttons[]=array($jsrestart,ico_save,"{apply_rules}");
	$topbuttons[]=array("$function()",ico_refresh,"{reload}");
	$help_url="https://wiki.articatech.com/en/proxy-service/web-filtering/policies";
	$topbuttons[]=array("s_PopUpFull('$help_url','1024','900');",ico_support,"WIKI");

	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/webfilter/current/policies"),true);
	if(!is_array($json) || !isset($json["Status"])){
		echo $tpl->div_error("{protocol_error}");
		return false;
	}
	if(!$json["Status"]){
		echo $tpl->div_error(htmlspecialchars((string)($json["Error"] ?? "")));
		return false;
	}
	if(!isset($json["policies"])){
		echo $tpl->div_error("{protocol_error}");
		return false;
	}
	$urlRewriteAccessDenyTemp=$json["urlRewriteAccessDenyTemp"] ?? 0;
	// $AIVABLE etait initialise a array() plus haut, avant quatre sorties
	// anticipees qui rendaient cette premiere affectation inutile.
	$AIVABLE=is_array($json["policies"])?$json["policies"]:array();

	// $GLOBALS["jsAfterEnc"], construit ici, n'etait relu NULLE PART.

	// Nombre de permissions temporaires encore actives.
	$max=time();
	$url_rewrite_temp=0;
	$tempRows=acls_api_get("/proxy/url-rewrite/temp");
	if(is_array($tempRows)){
		foreach($tempRows as $trow){
			if(intval($trow["enabled"] ?? 0)==1 && intval($trow["finaltime"] ?? 0)>$max){
				$url_rewrite_temp++;
			}
		}
	}

	$results=acls_api_get("/proxy/url-rewrite/acls");
	if(!is_array($results)){$results=array();}

	if($search!==null && $search!==""){
		$results=array_values(array_filter($results,function($r) use($search){
			return stripos((string)($r["ruleName"] ?? ""),$search)!==false;
		}));
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	// L'ancien en-tete declarait HUIT colonnes pour des lignes qui n'en
	// comptaient que SEPT: la derniere colonne etait donc systematiquement
	// decalee d'un cran par rapport a son titre.
	$html[]="<th class='center'>$order</th>";
	$html[]="<th>$rulename</th>";
	$html[]="<th class='center'>$status_t</th>";
	$html[]="<th class='center'>$enabled</th>";
	$html[]="<th class='center'>&nbsp;</th>";
	$html[]="</tr></thead><tbody id='urw-rows'>";

	$html[]=urw_temp_row($tpl,$function,$urlRewriteAccessDenyTemp,$url_rewrite_temp);

	$c=0;
	foreach($results as $ligne){
		$ID=intval($ligne["id"] ?? 0);
		if($ID==0){continue;}
		$c++;
		$html[]=urw_rule_row($tpl,$page,$function,$ligne,$ID,isset($AIVABLE[$ID]));
	}
	if($c==0 && $search!==null && $search!==""){
		$html[]="<tr><td colspan='5' class='awc-empty'>$nodata</td></tr>";
	}

	$html[]=urw_default_row($tpl,$results);
	$html[]=urw_bypass_summary_row($tpl,$page,$function);

	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>$showing $c</span>";
	$html[]="<span class='awc-pg' id='urw-pager'></span>";
	$html[]="</div></div></div>";

	$TINY_ARRAY["TITLE"]="{web_filter_policies}";
	$TINY_ARRAY["ICO"]="fa fa-shield";
	$TINY_ARRAY["EXPL"]="{web_filter_policies_explain}";
	$TINY_ARRAY["URL"]="webfiltering-policies";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n$jstiny</script>"
		.awc_pager_js(100,"urw-rows","urw-pager");
	return true;
}

/**
 * Pastille d'etat d'une politique.
 */
function urw_status_badge($active):string{
	if($active){return "<span class='awc-badge awc-badge-on'>{active2}</span>";}
	return "<span class='awc-badge awc-badge-off'>{inactive}</span>";
}

/**
 * Une ligne du tableau des politiques de filtrage web.
 * Les quatre familles de lignes -- permissions temporaires, regles, politique
 * par defaut, contournement -- ont exactement la meme forme; l'ancienne version
 * recopiait les sept cellules quatre fois, avec a chaque fois ses propres
 * </center> orphelins (aucun <center> n'etait jamais ouvert).
 * $name et $meta contiennent du HTML deja construit par l'appelant (liens
 * td_href, explication de regle): c'est a lui de les echapper.
 */
function urw_policy_row(array $r):string{
	$off=!empty($r["off"]);
	$id=htmlspecialchars((string)($r["id"] ?? ""));
	$h ="<tr id='$id'>";
	$h.="<td class='center awc-mono awc-nowrap'>".htmlspecialchars((string)($r["order"] ?? ""))."</td>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico".($off?" awc-ico-off":"")."'><i class='fas ".($r["ico"] ?? "fa-shield-halved")."'></i></span>";
	$h.="<span><div class='awc-name".($off?" awc-name-off":"")."'>".($r["name"] ?? "")."</div>";
	if((string)($r["meta"] ?? "")!==""){$h.="<div class='awc-meta'>".$r["meta"]."</div>";}
	$h.="</span></div></td>";
	$h.="<td class='center awc-nowrap'>".($r["badge"] ?? "")."</td>";
	$h.="<td class='center awc-nowrap'>".($r["enabled"] ?? "")."</td>";
	$h.="<td><div class='awc-acts'>".($r["acts"] ?? "")."</div></td>";
	$h.="</tr>";
	return $h;
}

/**
 * Ligne des permissions temporaires (toujours en tete, ordre 0).
 */
function urw_temp_row($tpl,$function,$active,$count):string{
	$meta=$tpl->_ENGINE_parse_body("{temporary_permissions_webfiltering_explains}");
	$meta=str_replace("%v","<strong>".intval($count)."</strong>",$meta);
	return urw_policy_row(array(
		"id"     =>"acl-temporary",
		"order"  =>"0",
		"ico"    =>"fa-clock",
		"off"    =>!$active,
		"name"   =>$tpl->td_href("{temporary_permissions}","{click_to_edit}",
					"Loadjs('fw.proxy.urlrewrite.temp.php?Mainfunc=$function')"),
		"meta"   =>$meta,
		"badge"  =>urw_status_badge($active),
		"enabled"=>"&nbsp;",
		"acts"   =>"&nbsp;"));
}

/**
 * Une regle de reecriture.
 */
function urw_rule_row($tpl,$page,$function,array $ligne,$ID,$active):string{
	// intval() sur "enabled" et coalescence sur "xOrder": l'ancienne version
	// lisait ces cles sans garde, ce qui emet un avertissement en PHP 8 des que
	// le daemon en omet une.
	$disabled=(intval($ligne["enabled"] ?? 0)==0);
	$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
	$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");
	$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
	return urw_policy_row(array(
		"id"     =>"acl-$ID",
		"order"  =>intval($ligne["xOrder"] ?? 0),
		"ico"    =>"fa-shield-halved",
		"off"    =>$disabled,
		"name"   =>$tpl->td_href(htmlspecialchars($tpl->utf8_encode($ligne["ruleName"] ?? "")),
					"{click_to_edit}","Loadjs('$page?rule-id-js=$ID&function=$function')"),
		"meta"   =>EXPLAIN_THIS_RULE($ID),
		"badge"  =>urw_status_badge($active),
		"enabled"=>$tpl->icon_check(intval($ligne["enabled"] ?? 0),"Loadjs('$page?enable-js=$ID')"),
		"acts"   =>"$up$down$delete"));
}

/**
 * Politique par defaut, toujours derniere appliquee.
 * L'ancienne version affichait ici l'ordre de la DERNIERE regle parcourue --
 * $lastLigne, non defini quand la liste etait vide -- et reutilisait $MUTED,
 * variable de boucle laissee telle quelle apres le foreach.
 */
function urw_default_row($tpl,array $results):string{
	$order=0;
	foreach($results as $r){
		$o=intval($r["xOrder"] ?? 0);
		if($o>$order){$order=$o;}
	}
	$meta="{when_connecting_to_method} {all_methods} {and} {for_everyone} ("
		.$tpl->td_href("{expect_whitelist}",null,"s_PopUpFull('/proxy-whitelists','1024','900');")
		.") {then} <strong>{force_using_thewebfilter_engine}</strong>";
	return urw_policy_row(array(
		"id"     =>"acl-default",
		"order"  =>$order+1,
		"ico"    =>"fa-scale-balanced",
		"name"   =>"{default}",
		"meta"   =>$meta,
		"badge"  =>urw_status_badge(true),
		"enabled"=>$tpl->icon_nothing(),
		"acts"   =>$tpl->icon_nothing()));
}

/**
 * Ligne resumant la liste des sites qui contournent le filtrage.
 */
function urw_bypass_summary_row($tpl,$page,$function):string{
	$label=$tpl->_ENGINE_parse_body("{bypass_webfilter_row}");
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/webfilter/bypass/list"));
	// isset() plutot que is_null($json->Rows): lire une propriete absente emet un
	// avertissement en PHP 8.
	$rows=(is_object($json) && isset($json->Rows) && is_array($json->Rows))?count($json->Rows):0;
	$label=str_replace("%s",$rows,$label);
	return urw_policy_row(array(
		"id"     =>"acl-glb",
		// Vide, pas "&nbsp;" : urw_policy_row() echappe l'ordre, l'entite s'affichait en clair.
		"order"  =>"",
		"ico"    =>"fa-arrow-right-arrow-left",
		"name"   =>"{default}",
		"meta"   =>$tpl->td_href($label,null,"Loadjs('$page?bypasswebf=yes&function=$function')"),
		"badge"  =>urw_status_badge(true),
		"enabled"=>$tpl->icon_nothing(),
		"acts"   =>$tpl->icon_nothing()));
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();

	$rulename=$tpl->utf8_decode($_POST["rulename"]);
	$headervalue=base64_encode($_POST["headervalue"] ?? "");

	$body = [
		"ruleName"    => $rulename,
		"headerName"  => $_POST["headername"] ?? "",
		"zType"       => $_POST["ztype"] ?? "",
		"headerValue" => $headervalue,
		"aclPort"     => intval($_POST["aclport"] ?? 0),
	];

	[$ok, $err] = acls_api_send("POST","/proxy/url-rewrite/acls", $body);
	if(!$ok){echo htmlspecialchars($err);return;}
}

function rule_delete($ID):bool{
	[$ok, $err] = acls_api_send("DELETE","/proxy/url-rewrite/acls/$ID");
	if(!$ok){echo "alert('".addslashes($err)."');";return false;}
	return true;
}

function EXPLAIN_THIS_RULE($ID):string{
	$array_access["url_rewrite_access_deny"]="{pass_trough_thewebfilter_engine}";
	$array_access["url_rewrite_access_allow"]="{force_using_thewebfilter_engine}";

	$acls=new squid_acls_groups();
	$row = acls_api_get("/proxy/url-rewrite/acls/$ID");
	if(!is_array($row)){return "";}
	$aclport = $row["aclPort"] ?? 0;
	$ztype = $row["zType"] ?? "";

	// Les deux tables etaient indexees sans garde: un port ou un type inconnu
	// -- une regle creee par une version anterieure, par exemple -- produisait un
	// avertissement en PHP 8 et une explication tronquee.
	$PORTS_DIRECTIONS=$acls->PORTS_DIRECTIONS();
	$portname=$PORTS_DIRECTIONS[$aclport] ?? "{all_methods}";
	$method=array();
	$method[]="{when_connecting_to_method} &laquo;$portname&raquo;, ";
	$objects=$acls->getobjectsNameFromAclrule(intval($row["id"] ?? 0),"black","squid_url_rewrite_link");
	if(is_array($objects) && count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}
	$method[]="{then} <strong>".($array_access[$ztype] ?? "{unknown}")."</strong>";

    $FINAL=@implode(" ", $method);

    $tpl=new template_admin();
    $page=CurrentPageName();
    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$FINAL</span>");
}
