<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["entity-js"])){entity_js();exit;}
if(isset($_GET["entity-popup"])){entity_popup();exit;}
if(isset($_POST["entityid"])){entity_save();exit;}
if(isset($_GET["entity-del"])){entity_del();exit;}
if(isset($_POST["entity-del"])){entity_del_perform();exit;}
if(isset($_GET["fill"])){fill();exit;}
page();

function entity_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $ID=$_GET["entity-js"];
    $ligne=$q->mysqli_fetch_array("SELECT * FROM statscom_entity WHERE entityid=$ID");
    $entityname=$ligne["entityname"];

    $tpl->js_dialog1($entityname,"$page?entity-popup=$ID",650);
    echo "FootableRemoveEmpty();";
}

function entity_del(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $ID=$_GET["entity-del"];
    $ligne=$q->mysqli_fetch_array("SELECT * FROM statscom_entity WHERE entityid=$ID");
    $entityname=$ligne["entityname"];
    $tpl->js_confirm_delete($entityname,"entity-del",$ID,"LoadAjax('statscom-entities','$page?table=yes');");
}

function entity_del_perform(){

    $ID=$_POST["entity-del"];
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM statscom_proxies WHERE uniqueid=$ID");
    $q->QUERY_SQL("DELETE FROM statscom_hsites WHERE uniqueid=$ID");
    $q->QUERY_SQL("DELETE FROM statscom WHERE entityid=$ID");
    $q->QUERY_SQL("DELETE FROM statscom_entity WHERE entityid=$ID");



}

function fill(){
    $ID=$_GET["fill"];
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT * FROM statscom_entity WHERE entityid=$ID");
$entitylabel=$ligne["entitylabel"];
    echo "FootableRemoveEmpty();\n";
    echo "if( document.getElementById('entitylabel-$ID') ){\n";
    echo "\tdocument.getElementById('entitylabel-$ID').innerHTML='$entitylabel';\n}\n";


}

function entity_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $ID=$_GET["entity-popup"];
    $ligne=$q->mysqli_fetch_array("SELECT * FROM statscom_entity WHERE entityid=$ID");
    $entityname=$ligne["entityname"];
    $form[]=$tpl->field_text("entitylabel","{name}",$ligne["entitylabel"],true);
    $tpl->field_hidden("entityid",$ID);
    echo $tpl->form_outside($entityname,$form,null,"{apply}","Loadjs('$page?fill=$ID')","AsSquidAdministrator");
}

function entity_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new postgres_sql();
    $ID=$_POST["entityid"];
    $entitylabel=$_POST["entitylabel"];
    $q->QUERY_SQL("UPDATE statscom_entity SET entitylabel='$entitylabel' WHERE entityid=$ID");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);}

}

function page(){
    $page=CurrentPageName();
    echo "<div id='statscom-entities'></div>
<script>LoadAjax('statscom-entities','$page?table=yes');</script>";
}

function table(){
    $t=time();
    $q=new postgres_sql();
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" style='margin-top:20px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>{size}</th>";
    $html[]="<th data-sortable=false>{hits}</th>";
    $html[]="<th data-sortable=false>DEL</th>";
    $html[]="</tr>";

    $td1=$tpl->table_td1prc();
    $TRCLASS=null;
    $results=$q->QUERY_SQL("SELECT * FROM statscom_entity ORDER BY entitylabel");
    //
    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["entityid"];
        $entityname=$tpl->td_href($ligne["entityname"],null,"Loadjs('$page?entity-js=$ID');");
        $entitylabel=$ligne["entitylabel"];
        $hits=$tpl->FormatNumber($ligne["hits"]);
        $size=FormatBytes($ligne["size"]/1024);
        $del=$tpl->icon_delete("Loadjs('$page?entity-del=$ID')");
        $html[]= "<tr class='$TRCLASS' id='acl-$ID'>";
        $html[]= "<td style='width:1%;' nowrap><i class='fas fa-server'></i>&nbsp;$entityname</td>";
        $html[]= "<td><strong id='entitylabel-$ID'>$entitylabel</strong></td>";
        $html[]= "<td $td1>$size</td>";
        $html[]= "<td $td1>$hits</td>";
        $html[]= "<td $td1>$del</td>";
        $html[]= "</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } )
});
setTimeout('FootableRemoveEmpty()', 2000);
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


