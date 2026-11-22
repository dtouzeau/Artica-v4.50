<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["SquidTemplateSimple"])){SquidTemplateSimple();exit;}
if(isset($_GET["Zoom-js"])){ZOOM_JS();exit;}
if(isset($_GET["TEMPLATE_CONTENT"])){TEMPLATE_CONTENT();exit;}
if(isset($_POST["TEMPLATE_TITLE"])){TEMPLATE_SAVE();exit;}
if(isset($_GET["help-js"])){helpjs();exit;}
table();

function ZOOM_JS():bool{
	$tpl=new template_admin();
	$TEMPLATE_TITLE=$_GET["Zoom-js"];
	$page=CurrentPageName();
	return $tpl->js_dialog1($TEMPLATE_TITLE, "$page?TEMPLATE_CONTENT=yes&TEMPLATE_TITLE=$TEMPLATE_TITLE");
}
function helpjs():bool{
	$tpl=new template_admin();
	$F["%a"]="User identity ";
	$F["%B"]="URL with FTP %2f hack ";
	$F["%c"]="Proxy error code ";
	$F["%d"]="seconds elapsed since request received (not yet implemented) ";
	$F["%D"]="proxy-generated error details.\\nMay contain other error page formating codes.\\nCurrently only SSL connection failures are detailed.";
	$F["%e"]="errno ";
	$F["%E"]="strerror() ";
	$F["%f"]="FTP request line ";
	$F["%F"]="FTP reply line ";
	$F["%g"]="FTP server message ";
	$F["%h"]="cache hostname ";
	$F["%H"]="server host name ";
	$F["%i"]="client IP address ";
	$F["%I"]="server IP address (NP: upper case i) ";
	$F["%l"]="Local site CSS stylesheet. (proxy-3.1 and later) (NP: lower case L) ";
	$F["%L"]="contents of err_html_text config option ";
	$F["%M"]="Request Method ";
	$F["%m"]="Error message returned by external auth helper ";
	$F["%o"]="Message returned by external acl helper ";
	$F["%p"]="URL port";
	$F["%P"]="Protocol ";
	$F["%R"]="Full HTTP Request ";
	$F["%S"]="proxy default signature.";
	$F["%s"]="caching proxy software with version ";
	$F["%t"]="local time ";
	$F["%T"]="UTC ";
	$F["%U"]="URL without password ";
	$F["%u"]="URL with password.";
	$F["%W"]="Extended error page data URL-encoded for mailto links. ";
	$F["%w"]="cachemgr email address ";
	$F["%z"]="DNS server error message ";
	$F["%Z"]="Message generated during the process which failed. May be ASCII-formatted. Use within HTML PRE tags.";

	
	$tr[]="<div class='awc-card'><table class='awc-tbl awc-tbl-compact'><tbody>";
	foreach ($F as $key=>$value){
		$tr[]=squidtpl_code_row($key,$value);
	}
	$tr[]="</tbody></table></div>";

	$html=awc_table_style()
		."<div style='overflow-x:hidden;overflow-y:scroll;height:450px'>".@implode("", $tr)."</div>";

	$tpl->js_display_results($html);
    return true;

}


function TEMPLATE_CONTENT():bool{
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$TEMPLATE_TITLE=$_GET["TEMPLATE_TITLE"];
	$SquidHTTPTemplateLanguage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage"));
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}

	$xtpl=new template_simple($_GET["TEMPLATE_TITLE"],$SquidHTTPTemplateLanguage);
	$form[]=$tpl->field_hidden("TEMPLATE_TITLE", $TEMPLATE_TITLE);
	$form[]=$tpl->field_text("TITLE", "{subject}",utf8_decode_switch($xtpl->TITLE));
	$form[]=$tpl->field_textareacode("BODY","{content}",utf8_decode_switch($xtpl->BODY));
	$tpl->form_add_button("{help}", "Loadjs('$page?help-js')");
	echo $tpl->form_outside("$TEMPLATE_TITLE ($SquidHTTPTemplateLanguage)", $form,null,"{apply}",null,"AsSquidAdministrator",true);
    return true;
}
function TEMPLATE_SAVE():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $TEMPLATE_TITLE=$_POST["TEMPLATE_TITLE"];
	$SquidHTTPTemplateLanguage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage"));
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}
	$xtpl=new template_simple($_POST["TEMPLATE_TITLE"],$SquidHTTPTemplateLanguage);
	foreach ($_POST as $num=>$ligne){
		$xtpl->$num=$ligne;
	}

	$xtpl->Save();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/template/build/$TEMPLATE_TITLE");
    return admin_tracks("Modified proxy error page template $TEMPLATE_TITLE");
}

function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$SquidHTTPTemplateLanguage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage"));
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}

	// L'en-tete declarait TROIS colonnes et chaque ligne en emettait QUATRE (une
	// derniere cellule vide): les donnees etaient decalees d'une colonne par
	// rapport aux titres. Le colspan du pied valait 3 pour quatre cellules.
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{template_name}</th>";
	$html[]="<th>{subject}</th>";
	$html[]="<th class='center'>{view2}</th>";
	$html[]="</tr></thead><tbody id='st-rows'>";

	// unserialize() rend FALSE quand le fichier est absent ou illisible, et
	// ecrire une cle dans un booleen leve une Error fatale en PHP 8.
	$templates=unserialize((string)@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
	if(!is_array($templates)){$templates=array();}
	if(!isset($templates[$SquidHTTPTemplateLanguage]) || !is_array($templates[$SquidHTTPTemplateLanguage])){
		$templates[$SquidHTTPTemplateLanguage]=array();
	}
	$templates[$SquidHTTPTemplateLanguage]["ERR_BLACKLISTED_SITE"]["TITLE"]="ERROR: Blacklisted Website";
	$templates[$SquidHTTPTemplateLanguage]["ERR_PARANOID"]["TITLE"]="ERROR: Banned access";
    $templates[$SquidHTTPTemplateLanguage]["ERR_CICAP_VIRUS"]["TITLE"]="Malware %VVN found in document";

	$MAIN=$templates[$SquidHTTPTemplateLanguage];


    $TemplateConfig=unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("TemplateConfig"));
    if(!is_array($TemplateConfig)){$TemplateConfig=array();}
    if(count($TemplateConfig)<3){
        $TemplateConfig=unserialize((string)@file_get_contents("/usr/share/artica-postfix/ressources/databases/TemplateConfig"));
        if(!is_array($TemplateConfig)){$TemplateConfig=array();}
    }

    $c=0;
    foreach ($MAIN as $TEMPLATE_TITLE=>$subarray){
		$c++;
		$zmd5=md5(serialize("$TEMPLATE_TITLE".serialize($subarray)));

		$title=utf8_decode_switch($TemplateConfig[$TEMPLATE_TITLE][$SquidHTTPTemplateLanguage]["TITLE"] ?? null);
		if($title==null){ $title=$subarray["TITLE"] ?? ""; }
		$xtpl=new template_simple($TEMPLATE_TITLE,$SquidHTTPTemplateLanguage);
		$subtitle=utf8_decode_switch($xtpl->TITLE);
		// Le nom du gabarit partait BRUT dans l'URL du lien.
        $linkJS="Loadjs('$page?Zoom-js=".urlencode($TEMPLATE_TITLE)."&lang=".urlencode($SquidHTTPTemplateLanguage)."')";

		$html[]=squidtpl_row($tpl,$zmd5,$TEMPLATE_TITLE,$title,
			($subtitle<>$title)?$subtitle:"",$linkJS);

	}
	if($c==0){
		$html[]="<tr><td colspan='3' class='awc-empty'>{no_data}</td></tr>";
	}

	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c</span>";
	$html[]="<span class='awc-pg' id='st-pager'></span>";
	$html[]="</div></div></div>";

    $TINY_ARRAY["TITLE"]="{proxy_error_pages}";
    $TINY_ARRAY["ICO"]="fad fa-page-break";
    $TINY_ARRAY["EXPL"]="{proxy_errors_pages_explain}";
    $TINY_ARRAY["URL"]="proxy-errors";

    $jsApply=$tpl->framework_buildjs("/proxy/templates",
        "squid.templates.single.progress","squid.templates.single.log","progress-pxtempl-restart");

    $topbuttons[] = array($jsApply, ico_save, "{build_templates}");

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// Feuille de style, script et pager concatenes APRES l'analyse: la regex
	// #{(.+?)}# de _ENGINE_parse_body prend chaque corps d'accolade CSS ou
	// JavaScript pour un {token}.
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n",$html))
	    ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n$jstiny</script>"
	    .awc_pager_js(50,"st-rows","st-pager");
    return true;

}
function utf8_decode_switch($value):string{

    // Le test is_null() venait APRES deux strpos() sur la meme valeur: passer
    // null a strpos() est deprecie depuis PHP 8.1.
    if(is_null($value)){
        return "";
    }
    if( (strpos($value,"Ã©")>0) OR  (strpos($value,"Ãª")>0) ){
        $value=str_replace("Ã´","ô",$value);
        $value=str_replace("Ã©","é",$value);
        $value=str_replace("Ãª","ê",$value);
        $value=str_replace("Ã","à",$value);

    }
    if(PHP_MAJOR_VERSION>7) {
        return $value;
    }
    $tpl=new template_admin();
    return $tpl->utf8_decode($value);
}

/**
 * Un code de substitution d'un gabarit d'erreur Squid.
 * Les libelles sont ceux de la documentation Squid, en anglais: ils ne
 * transitent pas par la table de traduction.
 */
function squidtpl_code_row($key,$value):string{
    return "<tr><td class='awc-kv-k awc-mono'>".htmlspecialchars($key)."</td>"
        ."<td>".htmlspecialchars($value)."</td></tr>";
}

/**
 * Un gabarit de page d'erreur.
 * Chaque ligne emettait une QUATRIEME cellule vide alors que l'en-tete n'en
 * declarait que trois: le sujet et la loupe etaient decales d'une colonne.
 * Le nom du gabarit et le sujet viennent de fichiers de donnees et partaient
 * bruts dans le HTML.
 */
function squidtpl_row($tpl,$zmd5,$name,$title,$subtitle,$linkJS):string{
    $h ="<tr id='$zmd5'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fad fa-page-break'></i></span>";
    $h.="<span class='awc-name'>".$tpl->td_href(htmlspecialchars($name),"{view2}",$linkJS)."</span>";
    $h.="</div></td>";
    $h.="<td><span class='awc-name'>".$tpl->td_href(htmlspecialchars($title),"{view2}",$linkJS)."</span>";
    if(trim($subtitle)<>""){
        $h.="<div class='awc-meta'>&laquo;&nbsp;".htmlspecialchars($subtitle)."&nbsp;&raquo;</div>";
    }
    $h.="</td>";
    $h.="<td class='center'>".$tpl->icon_loupe(1,$linkJS)."</td>";
    $h.="</tr>";
    return $h;
}
