<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");

if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $name=$_GET["name"];
    $nics=$_GET["nics"];
    $id=$_GET["id"];
    $value=$_GET["value"];
    $suffix=$_GET["suffix-form"];
    $tpl->js_dialog13("{interfaces}","$page?popup=yes&name=$name&nics=$nics&id=$id&value=$value&uffix-form=$suffix");
}
function popup(){
    $tpl=new template_admin();
    $nics=$_GET["nics"];
    $fieldid=$_GET["id"];
    $SelectedValue=$_GET["value"];
    $t=time();
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"20\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false colspan='2'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{nic}</th>";
    $html[]="<th data-sortable=false>{select}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $func=array();

    $Hash=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($nics);

    foreach ($Hash as $key=>$value){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $bt_color="btn-primary";

        if(trim(strtolower($SelectedValue))==trim(strtolower($key))) {
            $bt_color="btn-warning";
        }

        $idrow=md5($key.$value.$fieldid);
        $nic=new system_nic($key);
        $text=base64_encode($nic->NICNAME." (".$nic->IPADDR.")");
        $valuehtml=$tpl->_ENGINE_parse_body($value);
        $button_select=$tpl->button_autnonome("&nbsp;{select}",
            "select$idrow()",
            "fas fa-hand-pointer","",0,$bt_color,"small");
        $func[]="";
        $func[]="function select{$idrow}(){";
        $func[]="\tif(!document.getElementById('$fieldid')){alert('$fieldid no found!');return;}";
        $func[]="\tif( document.getElementById('btnlabel-$fieldid')){";
        $func[]="\t\tdocument.getElementById('btnlabel-$fieldid').textContent = base64_decode('$text');";
        $func[]="\t}";
        $func[]="\tdocument.getElementById('$fieldid').value=\"$key\";";
        $func[]="\tdialogInstance13.close();";
        $func[]="}";
        $func[]="";

        $html[]="<tr class='$TRCLASS' id='$idrow'>";
        $html[]="<td style='width:1%' nowrap><i class=\"".ico_nic."\"></i></td>";
        $html[]="<td style='width:1%' nowrap><strong style='font-size:14px'>$key</strong></td>";
        $html[]="<td><span style='font-size:14px'>$valuehtml</span></td>";
        $html[]="<td width=1% nowrap >$button_select</td>";
    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]=@implode("\n",$func);
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);



}