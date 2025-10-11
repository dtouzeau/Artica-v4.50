<?php

include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();

if(isset($_GET["type"])){switchtype();exit;}
if(isset($_GET["post-it"])){post_it();exit;}
if(isset($_GET["field-array-hash"])){x_field_array_hash();exit;}
if(isset($_GET["field-numeric"])){x_field_numeric();exit;}
if(isset($_GET["value-decode"])){value_decode();exit;}
if(isset($_POST["SaveField"])){SaveField();exit;}

writelogs("Unable to understand requests",__FUNCTION__,__FILE__,__LINE__);
function switchtype(){

    $type=$_GET["type"];
    if($type=="setinfo"){
        return setinfo();
    }
}

function  setinfo():bool{
    $page=CurrentPageName();
    $field=$_GET["field"];
    $id=$_GET["id"];
    $params=$_GET["params"];

    $f[]="if( document.getElementById('$id') ){";

    if($field=="field_array_hash"){
        $f[]="LoadAjaxSilent('$id','$page?field-array-hash=$params');";
    }
    if($field=="field_numeric"){
        $f[]="LoadAjaxSilent('$id','$page?field-numeric=$params');";
    }

    $f[]="}";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    return true;
    //fw.fields.php?type=setinfo&field=$field_src_md&read=$read_md'
}
function x_field_array_hash():bool{
    $tpl=new template_admin();
    $params=unserialize(base64_decode($_GET["field-array-hash"]));
    $hash=$params[0];
    $name=$params[1];
    $value=$params[2];
    $notnull=$params[3];
    $explain=$params[4];

    $js=$params[5];

    $field_src=$tpl->field_array_hash($hash,$name,null,$value,$notnull,$explain,$js);
    echo $field_src;
    return true;
}
function x_field_numeric(){
    $tpl=new template_admin();
    $params=unserialize(base64_decode($_GET["field-numeric"]));
   // $hash=$params[0];
    $name=$params[1];
    $value=$params[2];
    //$notnull=$params[3];
    $explain=$params[4];
    $js=$params[5];

    $field_src=$tpl->field_numeric($name,null,$value,$explain,$js);
    echo $field_src;
    return true;

}
function value_decode(){
    $params=unserialize(base64_decode($_GET["value-decode"]));
    $hash=$params[0];
    $value=$params[2];
    $OnOpen=$_GET["OnOpen"];
    $security=$params[6];
    $tpl=new template_admin();
    $syle="style='font-size:large'";
    if($tpl->IsSecurity($security)){
        $mouse=" OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
    }
    $OnClick="OnClick=\"javascript:$OnOpen();\"";
    $ValueText=$tpl->field_read_get_value($value,$hash);
    VERBOSE("$value ValueText = $ValueText",__LINE__);
    echo "<strong $syle $mouse $OnClick>$ValueText</strong>";
}
function SaveField():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $key=$_POST["key"];
    $srcCommand=$_POST["srcvalue"];
    $value=$_POST["value"];

    $params=unserialize(base64_decode($_POST["params"]));
    $name=$params[1];
    $srcvalue=$params[2];
    $security=$params[6];
    $frmwcmd=$params[7];

    if($security<>null){
        if(!$tpl->IsSecurity($security)){
            echo $tpl->_ENGINE_parse_body("{no_privileges}");
            return false;
        }
    }


    if(preg_match("#^sysctl:(.+)#",$srcCommand,$re)){
        writelogs("POST: srcommand=<$srcCommand> key=<$re[1]>",__FUNCTION__,__FILE__,__LINE__);
        $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET($re[1],$value);
        writelogs("POST: SET_INFO=<$key>-<$value>",__FUNCTION__,__FILE__,__LINE__);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key,$value);
        admin_tracks("Saving Kernel value $name/$re[1] from $srcvalue to $value");
        if($frmwcmd<>null){
            $GLOBALS["CLASS_SOCKETS"]->getGoFramework($frmwcmd);
        }
        return true;
    }

    return true;
}

function post_it(){
    $tpl=new template_admin();
    $params_encode=$_GET["post-it"];
    $fid=$_GET["f-id"];
    $id=$_GET["id"];
    $SavedValue=null;
    if(isset($_GET["saved"])){
        $SavedValue=$_GET["saved"];
    }
    $params=unserialize(base64_decode($_GET["post-it"]));
    $hash=$params[0];
    $name=$params[1];
    $value=$params[2];
    $notnull=$params[3];
    $explain=$params[4];
    $js=$params[5];
    $security=$params[6];
    $frmwcmd=$params[7];
    $t=time();
    $OnOpen=$_GET["OnOpen"];
    $FieldTableJs[]="var xStx{$t}Save = function (obj) {";
    $FieldTableJs[]="\tvar results=obj.responseText;";
    $FieldTableJs[]="\tif(results.length>3){";
    $FieldTableJs[]="\t\tswal({ title: \"Oops...\", text: results, html: true, type: \"warning\" });";
    $FieldTableJs[]="\t\treturn;";
    $FieldTableJs[]="\t}";
    $FieldTableJs[]="\tLoadAjaxSilent('f-$fid','fw.fields.php?value-decode=$params_encode&OnOpen=$OnOpen');";
    $FieldTableJs[]="}";
    $FieldTableJs[]="";
    $FieldTableJs[]="function Stx{$t}Save(){";

    if($security<>null){
        if(!$tpl->IsSecurity($security)){
            $no_privileges=base64_encode($tpl->_ENGINE_parse_body("{no_privileges}"));
            $FieldTableJs[]="\t\tswal({ title: \"Oops...\", text: base64_decode('$no_privileges'), html: true, type: \"warning\" });";
            $FieldTableJs[]="\t\treturn;";
        }
    }

    $FieldTableJs[]="\tvar XHR = new XHRConnection();";
    $FieldTableJs[]="\tXHR.appendData('SaveField','yes');";
    $FieldTableJs[]="\tXHR.appendData('key','$name');";
    $FieldTableJs[]="\tXHR.appendData('srcvalue','$value');";
    $FieldTableJs[]="\tXHR.appendData('params','{$_GET["post-it"]}');";
    if($SavedValue==null) {
        $FieldTableJs[] = "\tXHR.appendData('value',document.getElementById('$id').value);";
    }else{
        $FieldTableJs[] = "\tXHR.appendData('value','$SavedValue');";
    }
    $FieldTableJs[]="\tXHR.sendAndLoad('fw.fields.php', 'POST',xStx{$t}Save);";
    $FieldTableJs[]="}";
    $FieldTableJs[]="Stx{$t}Save();";
    header("content-type: application/x-javascript");
    echo @implode("\n",$FieldTableJs);
    return true;
}