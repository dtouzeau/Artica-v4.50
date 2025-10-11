<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbguard-tools.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["add"])){add();exit;}

js();

function js(){
    $page=CurrentPageName();
    $type=$_GET["type"];
    $value=$_GET["value"];
    $valueencoded=urlencode($value);
    $tpl=new template_admin();
    $tpl->js_dialog9($value,"$page?start=yes&type=$type&value=$valueencoded");
}

function add(){
    $uid=$_SESSION["uid"];
    $tpl=new template_admin();
    if($uid==-100){$uid="Manager";}
    $ID=$_GET["add"];
    $compile=intval($_GET["compile"]);
    $value=$_GET["value"];
    $q=new mysql_squid_builder();
    if(!$q->categorize($value,$ID,true)){
        $tpl->js_mysql_alert("Website:$value\\n".$q->mysql_error);
        return;
    }

    header("content-type: application/x-javascript");
    if($compile==1){
        echo $tpl->framework_buildjs("/category/compile/$ID",
            "ufdbcat.compile.progress","ufdbcat.compile.txt",
            "compile-proxy-action-category","dialogInstance9.close();dialogInstance1.close();");

        return;

    }
    echo "dialogInstance9.close();\n";
    echo "dialogInstance1.close();\n";


}


function start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=$_GET["type"];
    $value=$_GET["value"];
    $valueencoded=urlencode($value);
    $html[]="<div id='compile-proxy-action-category'></div>";
    $html[]=$tpl->search_block($page,"postgres",null,"table-$type","&type=$type&value=$valueencoded");
    echo $tpl->_ENGINE_parse_body($html);


}

function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();

    $search="*{$_GET["search"]}*";
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);
    $value=$_GET["value"];
    $valueencoded=urlencode($value);
    $t=time();
    $sql="SELECT * FROM personal_categories WHERE official_category='0' AND free_category=0 AND categoryname LIKE '$search' ORDER BY categoryname";
    //$sql="SELECT * FROM webfilters_sqgroups ";
    VERBOSE($sql,__LINE__);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category} ($value)</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{description}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    while ($ligne = pg_fetch_assoc($results)) {
        $enabled_text="&nbsp;";
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }


        $text_class = null;
        $categoryname = $ligne["categoryname"];
        $category_description = $ligne["category_description"];
        $ID = $ligne["category_id"];


        $choose="<button class='btn btn-primary btn-xs' type='button' OnClick=\"Loadjs('$page?add=$ID&value=$valueencoded&compile=0');\">{add}</button>";
        $choose2="<button class='btn btn-warning btn-xs' type='button' OnClick=\"Loadjs('$page?add=$ID&value=$valueencoded&compile=1');\">{add_and_compile}</button>";


        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:99%'><strong>$categoryname</strong></td>";
        $html[]="<td style='width:1%' class='center' nowrap>$category_description</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$choose</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$choose2</td>";
        $html[]="</tr>";

    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table><script>NoSpinner();\n";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);
}
