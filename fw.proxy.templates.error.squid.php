<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
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

	
	$tr[]="<table style='width:100%'>";
	foreach ($F as $key=>$value){
		$tr[]="<tr><td nowrap style='width:1%;vertical-align:middle'><strong style='font-size:14px'>$key:</strong></td><td style='text-align:left;font-size:10px;vertical-align:middle'>$value</td></tr>";
	}
	$tr[]="</table>";
	
	$html="<div style='overflow-x:hidden; overflow-y: scroll;height:450px'>".@implode("", $tr)."</div>";
	
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

	$html[]="<table id='table-template-squid-tpl' class=\"table table-stripped\" style='margin-top:10px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{template_name}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{subject}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{view2}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$TRCLASS=null;
	$templates=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
	$templates[$SquidHTTPTemplateLanguage]["ERR_BLACKLISTED_SITE"]["TITLE"]="ERROR: Blacklisted Website";
	$templates[$SquidHTTPTemplateLanguage]["ERR_PARANOID"]["TITLE"]="ERROR: Banned access";
    $templates[$SquidHTTPTemplateLanguage]["ERR_CICAP_VIRUS"]["TITLE"]="Malware %VVN found in document";

	$MAIN=$templates[$SquidHTTPTemplateLanguage];


    $TemplateConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TemplateConfig"));
    if(!is_array($TemplateConfig)){$TemplateConfig=array();}
    if(count($TemplateConfig)<3){
        $TemplateConfig=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/TemplateConfig"));
    }

    foreach ($MAIN as $TEMPLATE_TITLE=>$subarray){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize("$TEMPLATE_TITLE".serialize($subarray)));
		
		
		$subtitle2=null;
		$title=utf8_decode_switch($TemplateConfig[$TEMPLATE_TITLE][$SquidHTTPTemplateLanguage]["TITLE"]);
		if($title==null){ $title=$subarray["TITLE"]; }
		$xtpl=new template_simple($TEMPLATE_TITLE,$SquidHTTPTemplateLanguage);
		$subtitle=utf8_decode_switch($xtpl->TITLE);
		if($subtitle<>$title){$subtitle2="<br><i>&laquo;&nbsp;$subtitle&nbsp;&raquo;&nbsp;<i>";}
        $linkJS="Loadjs('$page?Zoom-js=$TEMPLATE_TITLE&lang=$SquidHTTPTemplateLanguage')";
		$linkZoom=$tpl->icon_loupe(1,$linkJS);
		
		
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td style='width:1%' nowrap>".$tpl->td_href("$TEMPLATE_TITLE","{view2}",$linkJS)."</td>";
		$html[]="<td>".$tpl->td_href("$title","{view2}",$linkJS)."$subtitle2</td>";
		$html[]="<td style='width:1%' nowrap>$linkZoom</td>";
		$html[]="<td></td>";
		$html[]="</tr>";

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $TINY_ARRAY["TITLE"]="{proxy_error_pages}";
    $TINY_ARRAY["ICO"]="fad fa-page-break";
    $TINY_ARRAY["EXPL"]="{proxy_errors_pages_explain}";
    $TINY_ARRAY["URL"]="proxy-errors";

    $jsApply=$tpl->framework_buildjs("/proxy/templates",
        "squid.templates.single.progress","squid.templates.single.log","progress-pxtempl-restart");

    $topbuttons[] = array($jsApply, ico_save, "{build_templates}");

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]=$jstiny;
    $html[]="</script>";

	echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function utf8_decode_switch($value):string{
    if(is_null($value)){
        return "";
    }
    if(PHP_MAJOR_VERSION>7) {
        return $value;
    }
    $tpl=new template_admin();
    return $tpl->utf8_decode($value);
}