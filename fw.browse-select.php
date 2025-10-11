<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["interfaces"])){content();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	//field-id=$id&Hash=$Encoded&field-id2=$id2&value=$DefaultValue&js=$jsafter
    $label=urlencode($_GET["label"]);
	$title=$tpl->javascript_parse_text("{select} {$_GET["label"]}");
    $DefaultValue=urlencode($_GET["value"]);
	$tpl->js_dialog6($title, "$page?popup=yes&page={$_GET["page"]}&name={$_GET["name"]}&field-id2={$_GET["field-id2"]}&field-id={$_GET["field-id"]}&Hash={$_GET["Hash"]}&value={$DefaultValue}&js={$_GET["js"]}&label=$label",500);
}

function popup(){
	$tpl=new template_admin();
	$page=$_GET["page"];
    $t=time();

    $fieldid2=$_GET["field-id2"];
    $fieldid=$_GET["field-id"];
    $SelectedValue=$_GET["value"];
    $name=$_GET["name"];
    $js=base64_decode($_GET["js"]);

    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"20\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$_GET["label"]}</th>";
    $html[]="<th data-sortable=false>{select}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $Hash=unserializeb64($_GET["Hash"]);

    foreach ($Hash as $key=>$value){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $bt_color="btn-primary";

        if(trim(strtolower($SelectedValue))==trim(strtolower($key))) {
            $bt_color="btn-warning";
        }

        $idrow=md5($fieldid2.$value.$fieldid);
        $valuejs=$tpl->javascript_parse_text($value);
        $valuehtml=$tpl->_ENGINE_parse_body($value);
        $button_select=$tpl->button_autnonome("&nbsp;{select}",
            "select{$idrow}()",
            "fas fa-hand-pointer","",0,$bt_color,"small");
        $func[]="";
        $func[]="var X_{$idrow} = function (obj) {";
        $func[]="\tvar results=trim(obj.responseText);";
        $func[]="\tif(results.length>0){alert(results);return;}";
        $func[]="\tdialogInstance6.close();";
        $func[]="\t$js;";
        $func[]="}";
        $func[]="function select{$idrow}(){";
        $func[]="\tif(!document.getElementById('$fieldid')){alert('$fieldid no found!');return;}";
        $func[]="\tif(!document.getElementById('$fieldid2')){alert('$fieldid2 no found!');return;}";
        $func[]="\tdocument.getElementById('$fieldid').value=\"$key\";";
        $func[]="\tdocument.getElementById('$fieldid2').value=\"$valuejs\";";
        $func[]="\tvar XHR = new XHRConnection();";
        $func[]="\tXHR.appendData('$name','$key');";
        $func[]="\tXHR.sendAndLoad('$page', 'POST',X_{$idrow});";
        $func[]="}";
        $func[]="";

        $html[]="<tr class='$TRCLASS' id='$idrow'>";
        $html[]="<td width=1% nowrap><i class=\"fas fa-info-circle\"></i></td>";
        $html[]="<td><strong>$valuehtml</strong></td>";
        $html[]="<td width=1% nowrap >$button_select</td>";
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
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]=@implode("\n",$func);
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}


