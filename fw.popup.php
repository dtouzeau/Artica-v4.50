<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){
    echo "<HR>";
    print_r($_GET);
}
$tpl=new template_admin();
$text=null;
$title=null;
$body=null;
$color_class="alert alert-danger";
$icon_div=null;$URL=null;$F=null;
$SAMBA_ERROR_CLICK=$tpl->_ENGINE_parse_body("{SAMBA_ERROR_CLICK}");
if(isset($_GET["subtitle"])) {
    $error = url_decode_special_tool($_GET["subtitle"]);
    $text = $tpl->_ENGINE_parse_body($error);
}
if(isset($_GET["title"])){
    $title=$tpl->_ENGINE_parse_body($_GET["title"]);
}
$textcss=null;



if(isset($_GET["array"])){
    $xcontent=$_GET["array"];
    if($GLOBALS["VERBOSE"]){echo "<hr>BASE64: $xcontent<hr>\n";}
    $scontent=base64_decode($xcontent);
    if($GLOBALS["VERBOSE"]){echo "<hr>DECODED: [$scontent]<hr>\n";}
    $array_help=unserialize($scontent);
    if(isset($_GET["verbose"])){
        print_r($array_help);
    }
    if(isset($array_help["textcss"])){
        $textcss=" ".$array_help["textcss"];
    }

    $title=$tpl->_ENGINE_parse_body($array_help["TITLE"]);
    if(preg_match("#file:(.+)#",$array_help["content"],$re)){
        $array_help["content"]=@file_get_contents(PROGRESS_DIR."/".$re[1]);
    }
    $BodyDecoded=$array_help["content"];
    if(preg_match("#^B64:(.+)#",$BodyDecoded,$re)){
        $BodyDecoded=base64_decode($re[1]);
    }
    $body = $tpl->_ENGINE_parse_body($BodyDecoded);
    $strlen=strlen($body);
    $ico=$array_help["ico"];
    if(isset($array_help["URL"])){$URL=$array_help["URL"];}
    $color_class=null;
    if($ico<>null){
        $icon_div="<i class='$ico modal-icon$textcss'></i>";
    }
}
if($strlen>800){
    $body="<div style='overflow-x:hidden; and overflow-y:auto;height:150px'>$body</div>";
}

$cancel=$tpl->_ENGINE_parse_body("{close}");
if($URL<>null){
    $F="<p style='margin-top:9px'><a href=\"javascript:blur();\" OnClick=\"javascript:$URL;\" class='btn btn-sm btn-outline btn-primary' style='font-size:22px'>Wiki: $SAMBA_ERROR_CLICK</a></p>";
}
$id=time();
$Close="OnClick=\"document.getElementById('artica-modal-dialog').innerHTML='';\"";

$html[]="<div id=\"$id\" class=\"modal inmodal in\" style=\"display: block; z-index:9999 !important\">
		<div class=\"modal-dialog\">
			<div class=\"modal-content animated bounceInRight\">
				<div class=\"modal-header$textcss\">
					<button data-dismiss=\"modal\" class=\"close\" 
					type=\"button\" $Close><span aria-hidden=\"true\">×</span><span class=\"sr-only\" >Close</span></button>$icon_div
					<h4 class=\"text-capitalize modal-title$textcss\" style='margin-top:20px'>$title</h4>
					<div class=\"$color_class$textcss\">$text$F</div>
				</div>";
$html[]="<div class=\"modal-body\">";
$html[]=$body;
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