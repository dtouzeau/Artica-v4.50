<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$id=md5(serialize($_GET));
$page=basename(__FILE__);
$title=base64_decode($_GET["title"]);
$icon=base64_decode($_GET["icon"]);
$subtitle=base64_decode($_GET["subtitle"]);
$jsafter=base64_decode($_GET["jsafter"]);
$defaultvalue=base64_decode($_GET["defaultvalue"]);
$videoid=base64_decode($_GET["video"]);
$tpl=new template_admin();
$cancel=$tpl->_ENGINE_parse_body("{close}");



$view="s_PopUp(\"https://youtu.be/$videoid\",800,800)";

$Close="OnClick=\"javascript:document.getElementById('artica-modal-dialog').innerHTML='';\"";

$html[]="<div id=\"$id\" class=\"modal inmodal in\" style=\"display: block; z-index:9999 !important\">
		<div class=\"modal-dialog\">
			<div class=\"modal-content animated bounceInRight\">
				<div class=\"modal-header\">
					<button data-dismiss=\"modal\" class=\"close\" 
					type=\"button\" $Close><span aria-hidden=\"true\">×</span><span class=\"sr-only\" >Close</span></button>
					<h4 class=\"modal-title\"><a href=\"javascript:$view\">$title</a></h4>
					<small class=\"font-bold\">$subtitle</small>
				</div>";
$html[]="<div class=\"modal-body\">";
$html[]="	<div class=\"ibox-content\">";
$html[]="<figure>";
$html[]="<iframe width='560' height='315' src='https://www.youtube.com/embed/$videoid?rel=0&amp;showinfo=0' frameborder='0' allow='autoplay; encrypted-media' allowfullscreen></iframe>";
$html[]="</figure>";
$html[]="</div>";
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