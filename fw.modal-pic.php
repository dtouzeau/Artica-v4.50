<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$id=md5(serialize($_GET));
$page=basename(__FILE__);
$title=base64_decode($_GET["title"]);
$icon=base64_decode($_GET["icon"]);
$subtitle=base64_decode($_GET["subtitle"]);
$jsafter=base64_decode($_GET["jsafter"]);
$defaultvalue=base64_decode($_GET["defaultvalue"]);
$image=base64_decode($_GET["image"]);
$tpl=new template_admin();
$cancel=$tpl->_ENGINE_parse_body("{close}");



$Close="OnClick=\"Loadjs('$page?remove={$_GET["image"]}');document.getElementById('artica-modal-dialog').innerHTML='';\"";

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