<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$id=md5(serialize($_GET));
$page=basename(__FILE__);
// Pentest 2026-11-04 : title, subtitle et image etaient decodes puis recopies
// tels quels dans la page (XSS reflechi). Le texte est encode, l'image doit
// etre un chemin local sous /ressources/ ; jsafter, icon et defaultvalue
// n'etaient pas utilises.
$title=req_html(base64_decode($_GET["title"] ?? ""));
$subtitle=req_html(base64_decode($_GET["subtitle"] ?? ""));
$image=strval(base64_decode($_GET["image"] ?? ""));
if(strpos($image,"/ressources/")!==0 || strpos($image,"..")!==false){$image="";}
$image=req_html($image);
$tpl=new template_admin();
$cancel=$tpl->_ENGINE_parse_body("{close}");



$Close="OnClick=\"document.getElementById('artica-modal-dialog').innerHTML='';\"";

$html[]="<div id=\"$id\" class=\"modal inmodal in\" style=\"display: block; z-index:9999 !important\">
		<div class=\"modal-dialog\">
			<div class=\"modal-content animated bounceInRight\">
				<div class=\"modal-header\">
					<button data-dismiss=\"modal\" class=\"close\" 
					type=\"button\" $Close><span aria-hidden=\"true\">×</span><span class=\"sr-only\" >Close</span></button>
					<h4 class=\"modal-title\">$title</h4>
					<small class=\"font-bold\">$subtitle</small>
				</div>";
$html[]="<div class=\"modal-body\">";
$html[]="<center><img src='$image' class='img-thumbnail'></center>";
$html[]="</div>";

$html[]="<div class=\"modal-footer\" style='background-color:white'>
	<button data-dismiss=\"modal\" class=\"btn btn-white\" type=\"button\" $Close>$cancel</button>";
$html[]="</div>";
$html[]="</div>";
$html[]="</div>";
$html[]="</div>";
$html[]="</div>
<script>

</script>";

echo @implode("\n", $html);