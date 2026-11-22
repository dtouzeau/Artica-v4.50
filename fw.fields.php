<?php
// Post SP10 - 13/10/2026
# v10/10/2026
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();

// Pentest 2026-11-04 : id/f-id/OnOpen/params/post-it/field-array-hash/field-numeric/value-decode etaient recopies sans encodage dans la page (XSS).
foreach(array("id","f-id") as $xssk){if(isset($_GET[$xssk])){$_GET[$xssk]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET[$xssk]);}}
if(isset($_GET["OnOpen"])){$_GET["OnOpen"]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["OnOpen"]);}
foreach(array("params","post-it","field-array-hash","field-numeric","value-decode") as $xssk){if(isset($_GET[$xssk])){$_GET[$xssk]=preg_replace('#[^A-Za-z0-9+/= %]#','',(string)$_GET[$xssk]);}}
if(isset($_GET["type"])){switchtype();exit;}
if(isset($_GET["post-it"])){post_it();exit;}
if(isset($_GET["field-array-hash"])){x_field_array_hash();exit;}
if(isset($_GET["field-numeric"])){x_field_numeric();exit;}
if(isset($_GET["value-decode"])){value_decode();exit;}
if(isset($_POST["SaveField"])){SaveField();exit;}

writelogs("Unable to understand requests",__FUNCTION__,__FILE__,__LINE__);
// Pentest 2026-11-04 : les tableaux "params" arrivent du navigateur (base64 serialise,
// non signe) et un lien direct vers cette page rend du HTML. On ne garde de params[5]
// que la forme posee par le template (id:StxXXXSave), le nom reste un identifiant et
// l'explication passe par le filtre HTML.
function fields_params_clean($params){
    if(!is_array($params)){$params=array();}
    for($i=0;$i<8;$i++){if(!isset($params[$i])){$params[$i]=null;}}
    if(!is_array($params[0])){$params[0]=array();}
    $params[1]=preg_replace('#[^A-Za-z0-9_|:.-]#','',(string)$params[1]);
    $params[4]=html_safe_fragment((string)$params[4]);
    if(!preg_match('#^id:[A-Za-z0-9_]+$#',(string)$params[5])){$params[5]=null;}
    return $params;
}
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
    $params=fields_params_clean(unserialize(base64_decode($_GET["field-array-hash"]),array("allowed_classes"=>false)));
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
    $params=fields_params_clean(unserialize(base64_decode($_GET["field-numeric"]),array("allowed_classes"=>false)));
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
    $params=fields_params_clean(unserialize(base64_decode($_GET["value-decode"]),array("allowed_classes"=>false)));
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
    echo "<strong $syle $mouse $OnClick>".req_html($ValueText)."</strong>"; // Pentest 2026-11-04 (XSS)
}
function SaveField():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $key=$_POST["key"];
    $srcCommand=$_POST["srcvalue"];
    $value=$_POST["value"];

    $params=unserialize(base64_decode($_POST["params"]),array("allowed_classes"=>false));
    $name=$params[1];
    $srcvalue=$params[2];
    $security=$params[6];
    $frmwcmd=$params[7];

    // $params vient du navigateur : son droit ($security) et sa commande
    // ($frmwcmd) sont donc choisis par le client. Ecrire un parametre noyau et
    // une cle de reglage exige l'administrateur systeme, quoi qu'annonce $params.
    if(!$tpl->IsSecurity("AsSystemAdministrator")){
        echo $tpl->_ENGINE_parse_body("{no_privileges}");
        return false;
    }
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
        // $frmwcmd (params[7]) n'est jamais execute : il vient du client, et la
        // commande partait en root via getGoFramework. Aucun appelant ne le fournit.
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
    $params=fields_params_clean(unserialize(base64_decode($_GET["post-it"]),array("allowed_classes"=>false)));
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
    // Pentest 2026-11-04 : name/value (decodes de post-it) et saved etaient recopies sans encodage dans la page (XSS).
    $FieldTableJs[]="\tXHR.appendData('key','".req_js($name)."');";
    $FieldTableJs[]="\tXHR.appendData('srcvalue','".req_js($value)."');";
    $FieldTableJs[]="\tXHR.appendData('params','{$_GET["post-it"]}');";
    if($SavedValue==null) {
        $FieldTableJs[] = "\tXHR.appendData('value',document.getElementById('$id').value);";
    }else{
        $FieldTableJs[] = "\tXHR.appendData('value','".req_js($SavedValue)."');";
    }
    $FieldTableJs[]="\tXHR.sendAndLoad('fw.fields.php', 'POST',xStx{$t}Save);";
    $FieldTableJs[]="}";
    $FieldTableJs[]="Stx{$t}Save();";
    header("content-type: application/x-javascript");
    echo @implode("\n",$FieldTableJs);
    return true;
}