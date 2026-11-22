<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.manager.inc");
// Pentest 2026-11-04 : view, css, template-js, template-export et delete-js etaient recopies sans encodage dans la page (XSS).
foreach (array("view","css","template-export") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=is_numeric($_GET[$xk])?intval($_GET[$xk]):1;}}
foreach (array("template-js","delete-js") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=intval($_GET[$xk]);}}

if(isset($_GET["main-page"])){main_page();exit;}

if(isset($_GET["filename"])){expose_file();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["template-export"])){TEMPLATE_EXPORT();exit;}

if(isset($_GET["css-js"])){css_js();exit;}
if(isset($_GET["css-popup"])){css_popup();exit;}
if(isset($_POST["css"])){css_save();exit;}

if(isset($_GET["head-js"])){head_js();exit;}
if(isset($_GET["head-popup"])){head_popup();exit;}
if(isset($_POST["head"])){head_save();exit;}

if(isset($_GET["body-js"])){body_js();exit;}
if(isset($_GET["body-popup"])){body_popup();exit;}
if(isset($_POST["body"])){body_save();exit;}

if(isset($_GET["view"])){view_template();exit;}
if(isset($_GET["css"])){view_css();exit;}
if(isset($_GET["v4css"])){v4css();exit;}

if(isset($_GET["new-template"])){new_template();exit;}
if(isset($_POST["new-template"])){new_template_save();exit;}

if(isset($_GET["template-js"])){template_js();exit;}
if(isset($_POST["save-template"])){template_save();exit;}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}

page();

function main_page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{WEB_ERROR_PAGE}: {template_manager}","fas fa-file-alt",
        "{proxy_errors_pages_explain}","$page?table=yes",
        "proxy-errors","progress-pxtempl-restart",false,"proxy-templates-manager");


    if(isset($_GET["main-page"])){$tpl=new template_admin("{WEB_ERROR_PAGE}: {template_manager}",$html);
        echo $tpl->build_firewall();return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function page(){
	$page=CurrentPageName();
	echo "<div id='proxy-templates-manager'></div><script>LoadAjax('proxy-templates-manager','$page?table=yes');</script>";
	
}
function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$templates_manager=new templates_manager(intval($_GET["delete-js"]));
	$js="$('#{$_GET["md"]}').remove();";
	$tpl->js_confirm_delete("{template} &laquo;" .$templates_manager->TemplateName."&raquo;", "delete", $_GET["delete-js"],$js);

}

function delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$templates_manager=new templates_manager(intval($_POST["delete"]));
	$templates_manager->Delete();
	
}
function new_template(){
	$users=new usersMenus();
	$tpl=new template_admin();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$tpl->js_no_license();return;}
	$page=CurrentPageName();
	$jsafter="LoadAjax('proxy-templates-manager','$page?table=yes');";
	$tpl->js_prompt("{new_template}","{template_name}","fas fa-plus","$page","new-template",$jsafter);
}
function template_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$templates_manager=new templates_manager(intval($_GET["template-js"]));
	$temp=$templates_manager->TemplateName;
	$jsafter="LoadAjax('proxy-templates-manager','$page?table=yes');";
	$tpl->js_prompt("{template}","{template_name}","fas fa-edit","$page","save-template",$jsafter,$temp,$_GET["template-js"]);	
	
}

function template_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
	$templates_manager=new templates_manager(intval($_POST["KeyID"]));
	$templates_manager->TemplateName=$tpl->CLEAN_BAD_XSS($_POST["save-template"]);
	$templates_manager->Save();
	if(!$templates_manager->ok){echo $templates_manager->mysql_error_html;}
}

function new_template_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$templates_manager=new templates_manager();
	$templates_manager->TemplateName=$tpl->CLEAN_BAD_XSS($_POST["new-template"]);
	$templates_manager->Create();
	if(!$templates_manager->ok){echo $templates_manager->mysql_error;}
}

function css_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["css-js"]);
	$templates_manager=new templates_manager($ID);
	$title=$templates_manager->TemplateName;
	$tpl->js_dialog1("$title: CSS", "$page?css-popup=$ID");
}
function head_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["head-js"]);
	$templates_manager=new templates_manager($ID);
	$title=$templates_manager->TemplateName;
	$tpl->js_dialog1("$title: HEAD", "$page?head-popup=$ID");
}
function body_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["body-js"]);
	$templates_manager=new templates_manager($ID);
	$title=$templates_manager->TemplateName;
	$tpl->js_dialog1("$title: BODY", "$page?body-popup=$ID");
}
function head_popup(){
	$tpl=new template_admin();
	$ID=intval($_GET["head-popup"]);
	$templates_manager=new templates_manager($ID);

	$form[]=$tpl->field_hidden("head", $ID);
	$form[]=$tpl->field_textareacode("content",null, $templates_manager->headContent);
	echo $tpl->form_outside($templates_manager->TemplateName." - HEAD", $form,null,"{apply}","dialogInstance1.close();","AsSquidAdministrator",true);

}
function body_popup(){
	$tpl=new template_admin();
	$ID=intval($_GET["body-popup"]);
	$templates_manager=new templates_manager($ID);

	$form[]=$tpl->field_hidden("body", $ID);
	$form[]=$tpl->field_textareacode("content",null, $templates_manager->BodyContent);
	echo $tpl->form_outside($templates_manager->TemplateName." - BODY", $form,null,"{apply}","dialogInstance1.close();","AsSquidAdministrator",true);

}
function css_popup(){
	$tpl=new template_admin();
	$ID=intval($_GET["css-popup"]);
	$templates_manager=new templates_manager($ID);
	$len=strlen( $templates_manager->CssContent);
	$form[]=$tpl->field_hidden("css", $ID);
	$form[]=$tpl->field_textareacode("content", null, $templates_manager->CssContent);
	echo $tpl->form_outside($templates_manager->TemplateName." - CSS ({$len}Bytes)", $form,null,"{apply}","dialogInstance1.close();","AsSquidAdministrator",true);
	
}

function css_save(){
	$tpl=new template_admin();
	$ID=intval($_POST["css"]);
	$tpl->CLEAN_POST();
	$templates_manager=new templates_manager($ID);
	$templates_manager->CssContent=$_POST["content"];
	$templates_manager->Save();
	if(!$templates_manager->ok){echo $templates_manager->mysql_error_html;}
}
function head_save(){
	$tpl=new template_admin();
	$ID=intval($_POST["head"]);
	$tpl->CLEAN_POST();
	$templates_manager=new templates_manager($ID);
	$templates_manager->headContent=$_POST["content"];
	$templates_manager->Save();
	if(!$templates_manager->ok){echo $templates_manager->mysql_error_html;}
}
function body_save(){
	$tpl=new template_admin();
	$ID=intval($_POST["body"]);
	$tpl->CLEAN_POST();
	$templates_manager=new templates_manager($ID);
	$templates_manager->BodyContent=$_POST["content"];
	$templates_manager->Save();
	if(!$templates_manager->ok){echo $templates_manager->mysql_error_html;}
}
function table(){

	$page=CurrentPageName();
	$tpl=new template_admin();

    $jsApply=$tpl->framework_buildjs("/proxy/templates",
        "squid.templates.single.progress","squid.templates.single.log","progress-pxtempl-restart");

    $topbuttons[] = array("Loadjs('$page?new-template=yes');", ico_plus, "{new_template}");
    $topbuttons[] = array("Loadjs('fw.proxy.templates.manager.files.php');", "fas fa-images", "{file_manager}");
    $topbuttons[] = array("Loadjs('fw.proxy.templates.upload.php');", ico_download, "{import_template}");
    $topbuttons[] = array($jsApply, ico_save, "{build_templates}");

	$CORP=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
	// proxy.db est lu par le daemon : la console n'ouvre plus la base.
	list($listed,$results)=templates_manager_api("GET","/proxy/templates-manager");
	if(!$listed){
		echo $tpl->div_error(strval($results));
		return;
	}
	$rows=array();
	foreach ((is_array($results)?$results:array()) as $ligne){
		$rows[]=pxtpl_row(array("ID"=>$ligne["id"] ?? 0,"TemplateName"=>$ligne["templateName"] ?? ""),$page,$CORP,$tpl);
	}
	$shown=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{template_name}</th>";
	$html[]="<th class='center'>{modify}</th>";
	$html[]="<th class='center'>{actions}</th>";
	$html[]="</tr></thead><tbody id='pxtpl-rows'>";
	if($shown==0){
		$html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown</span>";
	$html[]="<span class='awc-pg' id='pxtpl-pager'></span>";
	$html[]="</div></div></div>";

    $TINY_ARRAY["TITLE"]="{WEB_ERROR_PAGE}: {template_manager}";
    $TINY_ARRAY["ICO"]="fad fa-page-break";
    $TINY_ARRAY["EXPL"]="{proxy_errors_pages_explain}";
    $TINY_ARRAY["URL"]="proxy-errors";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
	$script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n$jstiny\n</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(50,"pxtpl-rows","pxtpl-pager");
}

/**
 * Un modele de page d'erreur. La ligne emettait ONZE cellules pour HUIT
 * en-tetes: le bouton CSS etait repete deux fois de plus en fin de ligne, suivi
 * d'une cellule vide, et deux cellules portaient un "</center>" sans ouvrant.
 * Le decalage poussait les colonnes hors de l'en-tete. Les trois boutons
 * d'edition tiennent maintenant une seule colonne, l'identifiant passe en
 * sous-titre du nom.
 */
function pxtpl_row($ligne,$page,$CORP,$tpl):string{
	$zmd5=md5(serialize($ligne));
	$ID=intval($ligne["ID"]);
	// Deja en UTF-8 (JSON du daemon).
	$TemplateName=strval($ligne["TemplateName"]);
	if(trim($TemplateName)==""){$TemplateName="{unknown}";}

	$edit="";
	if($CORP){
		$edit =pxtpl_edit_button($page,$ID,"css-js","CSS");
		$edit.=pxtpl_edit_button($page,$ID,"head-js","HEAD");
		$edit.=pxtpl_edit_button($page,$ID,"body-js","BODY");
	}else{
		$edit="<span class='awc-none'>&mdash;</span>";
	}

	$view=$tpl->td_href("<i class='fas fa-eye'></i>","{view2}","s_PopUpFull('$page?view=$ID',1024,768)");
	$export=$tpl->icon_download("direct:$page?template-export=$ID");
	$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$zmd5')","AsSquidAdministrator");

	$h ="<tr id='$zmd5'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fad fa-page-break'></i></span>";
	$h.="<span><span class='awc-name'>";
	$h.=$tpl->td_href(htmlspecialchars((string)$TemplateName),null,"Loadjs('$page?template-js=$ID')")."</span>";
	$h.="<div class='awc-meta awc-mono'>#$ID</div></span>";
	$h.="</div></td>";
	$h.="<td class='center'><div class='awc-chips' style='justify-content:center'>$edit</div></td>";
	$h.="<td class='center'><div class='awc-acts'><span class='awc-act'>$view</span>$export$delete</div></td>";
	$h.="</tr>";
	return $h;
}

/** Un bouton d'edition d'une des trois parties du modele. */
function pxtpl_edit_button($page,$ID,$action,$label):string{
	return "<a href=\"javascript:blur();\" class='awc-btn' style='padding:4px 9px;font-size:11px' "
		."OnClick=\"Loadjs('$page?$action=$ID');\">$label</a>";
}

/**
 * L'apercu d'un modele de page d'erreur.
 *
 * Les deux tableaux du corps ci-dessous font partie de l'ARTEFACT PREVISUALISE,
 * pas de l'interface: ils sont rendus avec la feuille de style du modele du
 * client pour montrer a quoi ressemblera la page de blocage. Les habiller en
 * tableau d'entites ferait mentir l'apercu -- il montrerait le style de la
 * console au lieu de celui du modele. Ils restent donc inchanges.
 */
function  view_template(){
    $footer=null;
	$page=CurrentPageName();
	$tpl=new templates_manager($_GET["view"]);

	$jquery="\n\n<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-1.8.3.js\"></script>\n<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-ui-1.8.22.custom.min.js\"></script>";
	$css="<link href=\"/$page?css=".intval($_GET["view"])."\" rel=\"stylesheet\"  type=\"text/css\"/>";

    $parser=new templates_objects();
    $tpl->headContent=$parser->ParseContent($tpl->headContent);
    $tpl->BodyContent=$parser->ParseContent($tpl->BodyContent);
    $template=$tpl->headContent."\n".$tpl->BodyContent;

	$TITLE="Dynicamic Title";

	$f[]="
	<H1>$TITLE</h1>
	<H2>Sub-title</h2>
	<p>Text paragraph</p>

	<H3>Form:</h3>
	<form><table style='width:100%'>
	<tr>
	<td><strong>Label1:</strong></td>
	<td><input type='text' name=toto value='data'></td>

	</tr>
	<tr>
	<td colspan=2 align='right'><hr><input type=button id=button value='Submit'></td>
	</tr>
	<tr>
	<td colspan=2 class='ButtonCell'><center>
	<a data-loading-text=\"Chargement...\"
	style=\"text-transform:capitalize\"
	class=\"Button2014 Button2014-success Button2014-lg\"
	id=\"".time()."\"
	onclick=\"blur();\" href=\"javascript:Blurz()\">&laquo;&nbsp;Button&nbsp;&raquo;</a>
	</center>
</td>
</tr>
	</table></form>
<hR>
<H1 style='background-color:white;color:black;border-radius: 5px 5px 5px 5px;;padding:5px;'>Web filtering:</H1>
<div id=\"wrapper\">
    <h1 class=bad></h1>
    <h2>This web page is blocked</h2>
    <h2>Web-Filtering porn (Artica Database)</h2>
    <p>Text explain.<br></p>
    <h3>Sub-title</h3>
    <p>Explain 2.</p>

    <div id=\"info\">
    <table width='100%'>
        <tr><td class=\"info_title\">Proxy server:</td><td class=\"info_content\">proxy.company.biz</td></tr>
        <tr><td class=\"info_title\">Application:</td><td class=\"info_content\">Version 2.39.081100</td></tr>
        <tr><td class=\"info_title\">Member:</td><td class=\"info_content\">david.touzeau, 192.168.1.236, PC0051</td></tr>
        <tr><td class=\"info_title\">Policy:</td><td class=\"info_content\">default, porn (Artica Database)</td></tr>
        <tr>
            <td class=\"info_title\" nowrap>Requested URL:</td>
            <td class=\"info_content\">
                <div class=\"break-word\">http://www.youporn.com/</div>
            </td>
        </tr>
    </table>
    </div>
</div>
<hr>
 <h1 style='background-color:white;color:black;border-radius: 5px 5px 5px 5px;;padding:5px;'>Proxy Error example</h1>
<div id='titles'>
	<h1>ERROR</h1>
	<h2>The requested URL could not be retrieved</h2>
</div>
<hr>
<div id='content'>
<p>
<blockquote id='error'>
	<p>The DNS server returned:</p>
	<blockquote id='data'>
		<pre>Name Error: The domain name does not exist.</pre>
	</blockquote>
	<p>This means that the cache was not able to resolve the hostname presented in the URL. Check if the address is correct.</p>
	<br>
	</blockquote>
</div>
<hr>	
<div id='footer'>
	<p>Generated Sun, 17 Jun 2018 11:06:15 GMT by xxx.xxxx.xx (xxxx)</p>
	<center>Artica Proxy, version x.xx.xxxxxx</center>
</div> ";
	$f[]="<script>checkIfTopMostWindow();</script>";
	$f[]="</body>";
	$f[]="</html>";
	$final_content=@implode("\n", $f);
	$template=str_replace("%DYNAMIC_CONTENT%", $final_content, $template);
	$template=str_replace("%FOOTER%", $footer, $template);
	$template=str_replace("%CSS%", $css, $template);
	$template=str_replace("%TITLE_HEAD%", $TITLE, $template);
	$template=str_replace("%JQUERY%", $jquery, $template);
    $template=str_replace("%V4HEADS%", "<link href=\"$page?v4css=yes\" rel=\"stylesheet\">", $template);




	echo $template;
}

function v4css(){
    header("Content-type: text/css");


    $f[]="/usr/share/artica-postfix/angular/bootstrap.min.css";
    $f[]="/usr/share/artica-postfix/angular/font-awesome/css/all.min.css";
    $f[]="/usr/share/artica-postfix/angular/animate.css";
    $f[]="/usr/share/artica-postfix/angular/style.css";

    foreach ($f as $path){

        if(!readfile($path)){
            echo "/*!\n*$path No such file\n*/\n";
        }
        echo "\n";
    }


}

function expose_file(){

    // Le nom allait tel quel dans la requete SQL.
    $ligne=templates_manager_file(strval($_GET["filename"]));
    if(count($ligne)==0){header("HTTP/1.1 404 Not Found");return;}
    $content=base64_decode(strval($ligne["contentFile"] ?? ""));
    $contenttype=strval($ligne["contentType"] ?? "");
    if(!preg_match('#^[A-Za-z0-9.+-]+/[A-Za-z0-9.+-]+$#',$contenttype)){$contenttype="application/octet-stream";}

    header("Content-type: $contenttype");
    header("Content-Length: ".strlen($content));
    echo $content;
}

function view_css(){
	header("Content-type: text/css");
    echo "/*!\n*Template id: ".intval($_GET["css"])."\n*/\n"; // Pentest 2026-11-04 : identifiant numerique

	$tpl=new templates_manager($_GET["css"]);
	$data=$tpl->CssContent;
	
	$Dirname=PROGRESS_DIR."/ViewTemplates";
	@mkdir($Dirname);
	
	if(preg_match_all("#\[file=(.*?)\]#is", $data, $re)){
		foreach ($re[1] as $num=>$filename){
			// [file=...] vient du contenu du modele : ni requete construite, ni chemin.
			$filename=basename(trim(strval($filename)));
			if($filename=="" || $filename=="." || $filename==".."){continue;}
			$ligne=templates_manager_file($filename);
			$fdata=base64_decode(strval($ligne["contentFile"] ?? ""));
			@file_put_contents("$Dirname/$filename", $fdata);
			if($GLOBALS["VERBOSE"]){echo "#preg_match_all.$num {$filename} -> [file={$filename}]\n";}
			$data=str_replace("[file={$filename}]", "ressources/logs/web/ViewTemplates/{$filename}", $data);
		}
	}else{
		if($GLOBALS["VERBOSE"]){echo "#preg_match_all NONE!\n";}
	}
	
	echo $data;

}

function TEMPLATE_EXPORT(){
	
	$tpl=new templates_manager($_GET["template-export"]);
	$CssContent=$tpl->CssContent;
	$headContent=$tpl->headContent;
	$BodyContent=$tpl->BodyContent;
	$TemplateName=$tpl->TemplateName;

	$t=time();
	$Dirname=PROGRESS_DIR."/$t";
	@mkdir($Dirname);

	@file_put_contents("$Dirname/tpl.name", $TemplateName);
	@file_put_contents("$Dirname/css.css", $CssContent);
	@file_put_contents("$Dirname/head.html", $headContent);
	@file_put_contents("$Dirname/body.html", $BodyContent);

	if(preg_match_all("#\[file=(.*?)\]#s", $CssContent, $re)){
		foreach ($re[1] as $num=>$filename){
			TEMPLATE_EXPORT_SavePic($filename,$Dirname);

		}
	}
	if(preg_match_all("#\[file=(.*?)\]#s", $headContent, $re)){
		foreach ($re[1] as $num=>$filename){
			TEMPLATE_EXPORT_SavePic($filename,$Dirname);

		}
	}
	if(preg_match_all("#\[file=(.*?)\]#s", $BodyContent, $re)){
		foreach ($re[1] as $num=>$filename){
			TEMPLATE_EXPORT_SavePic($filename,$Dirname);
		}
	}


	// Pentest 2026-11-04 : le nom du modele (saisi en console, lu en base)
	// allait tel quel dans un shell_exec. Nom de fichier reduit a un
	// identifiant, arguments passes par escapeshellarg().
	$TemplateName=preg_replace('#[^A-Za-z0-9_.-]#', "", str_replace(" ", "", $TemplateName));
	$TemplateName=ltrim($TemplateName,".-");
	if($TemplateName==""){$TemplateName="template";}
	$compressfile=PROGRESS_DIR."/$TemplateName.tar.gz";
	// Memes noms de membres qu'avec l'ancien "tar *" (sans ./), attendus a l'import.
	$members="";
	foreach(scandir($Dirname) as $m){if($m=="."||$m==".."){continue;}$members.=" ".escapeshellarg($m);}
	exec("/bin/tar -czf ".escapeshellarg($compressfile)." -C ".escapeshellarg($Dirname)." --$members 2>&1");
	exec("/bin/rm -rf ".escapeshellarg($Dirname));

	$fsize=@filesize($compressfile);

	header("Content-Length: ".$fsize);
	header('Content-type: application/gzip');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$TemplateName.tar.gz\"");
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile($compressfile);
	@unlink($compressfile);



}
function TEMPLATE_EXPORT_SavePic($fileSource,$BaseDir){

	// [file=...] vient du contenu du modele : pas de chemin, pas d'apostrophe.
	$fileSource=basename(trim(strval($fileSource)));
	if($fileSource=="" || $fileSource=="." || $fileSource==".."){return;}
	$template_path="$BaseDir/files";
	@mkdir($template_path,0755,true);

	if(is_file("$template_path/$fileSource")){return;}

	$ligne=templates_manager_file($fileSource);
	if(count($ligne)==0){return;}

	$contenttype=strval($ligne["contentType"] ?? "");
	$content=base64_decode(strval($ligne["contentFile"] ?? ""));

	if($contenttype=="text/css"){
		if(preg_match_all("#\[file=(.*?)\]#s", $content, $re)){
			foreach ($re[1] as $num=>$filename){
				TEMPLATE_EXPORT_SavePic($filename,$BaseDir);
			}
		}
	}

	@file_put_contents("$template_path/$fileSource.type", $contenttype);
	@file_put_contents("$template_path/$fileSource", $content);

}