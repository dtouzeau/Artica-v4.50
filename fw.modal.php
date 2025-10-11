<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$ScriptPage=CurrentPageName();
$page=basename(__FILE__);
$title=base64_decode($_GET["title"]);
$icon=base64_decode($_GET["icon"]);
$subtitle=base64_decode($_GET["subtitle"]);
$jsafter=base64_decode($_GET["jsafter"]);
$defaultvalue=base64_decode($_GET["defaultvalue"]);
$js=base64_decode($_GET["js"]);
$field=$_GET["fieldname"];
$id=md5(time());

if($field=="none"){$field=null;}

$tpl=new template_admin();
$cancel=$tpl->_ENGINE_parse_body("{cancel}");
$fill_form=$tpl->_ENGINE_parse_body("{fill_form}");
$field_type="text";
if(preg_match("#password#i",$field)){$field_type="password";}
$Close="OnClick=\"javascript:document.getElementById('artica-modal-dialog').innerHTML='';\"";


$html[]="<small class=\"font-bold\">$subtitle</small><hr>";

$onKeyPress="onkeypress=\"SaveCheck{$id}(event);\"";
if($defaultvalue==null){$defaultvalue="Place content here;";}

if($field<>null) {
    $html[] ="<div class=\"form-group\">";
    $html[] = "    <div id='form$id' class=\"form-control\" style='z-index:999999;color:black'>$defaultvalue</div>";
	$html[] = " </div>";


}
//echo @implode("\n", $html);
$html[]="<script>
    
    if(document.getElementById(\"form$id\")){
        document.getElementById(\"form$id\").contentEditable = \"true\";;
        alert('ok');
     } 
	var x_Save$id= function (obj) {
		var results=obj.responseText;
		if(results.length>3){
		    
		    if(document.getElementById('modal-inside-alert')){
			document.getElementById('modal-inside-alert').innerHTML='<div class=\"alert alert-danger\"><strong>'+results+'</strong></div>';
			}else{
		        alert(results);
		    }
			
			return;
		}
		document.getElementById('artica-modal-dialog').innerHTML='';
		$jsafter
	}
	
	
	function SaveCheck{$id}(e){
		if(checkEnter(e)){ Save{$id}();}
	}

";
$html[]="function Save{$id}(){";
if($field==null) {
$html[]="\t$jsafter;";
$html[]="\treturn;";
}
$html[]="\tvar XHR = new XHRConnection();";
$html[]="\tvar one='';";
$html[]="\tif(document.getElementById('modal-inside-alert')){document.getElementById('modal-inside-alert').innerHTML='';}";
$html[]="\tif(!document.getElementById('form$id')){alert('form$id !!');return;}";
$html[]="\tone=document.getElementById('form$id').value;";
$html[]="\tif(one.length==0){";
$html[]="\tdocument.getElementById('modal-inside-alert').innerHTML='<div class=\"alert alert-danger\"><strong>$fill_form</strong></div>';";
$html[]="\treturn;}";

if($_GET["KeyName"]<>null){
	$html[]="\tXHR.appendData('KeyID','{$_GET["KeyName"]}');";

}
if($field<>null) {$html[]="\tXHR.appendData('$field',one);";}else{
    $html[]="\tXHR.appendData('none','none');";
}

$html[]="\tXHR.sendAndLoad('$js', 'POST',x_Save$id);";
$html[]="}";
$html[]="</script>";

echo @implode("\n", $html);