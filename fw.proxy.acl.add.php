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
    $value=$_GET["value"];
    $date=date("Y-m-d H:i:s");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$ID'");
    $GroupName=$ligne["GroupName"];


    $sql="INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) VALUES ('$ID','$value','$date','$uid',1)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    echo "dialogInstance9.close();\n";
    echo "dialogInstance1.close();\n";
    echo "Loadjs('fw.proxy.acls.bugs.php?refresh=yes');\n";
    admin_tracks("Adding a new record $value to $GroupName ACL group");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
}


function start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $type=$_GET["type"];
    $value=$_GET["value"];
    $valueencoded=urlencode($value);
    $html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/acls.db",null,"table-$type","&type=$type&value=$valueencoded");
    echo $tpl->_ENGINE_parse_body($html);


}

function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $objects=$tpl->_ENGINE_parse_body("{objects}");
    $search="*{$_GET["search"]}*";
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);
    $value=$_GET["value"];
    $valueencoded=urlencode($value);

    $sql="SELECT * FROM webfilters_sqgroups WHERE GroupType='{$_GET["type"]}' AND GroupName LIKE '$search'";
    //$sql="SELECT * FROM webfilters_sqgroups ";
    VERBOSE($sql,__LINE__);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}

    $html[]="<table id='table-firewall-objects' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$objects ($value)</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{enabled}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($results as $index=>$ligne) {
        $enabled_text="&nbsp;";
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $text_class = null;
        $GroupName = $ligne["GroupName"];
        $enabled = $ligne["enabled"];
        $ID = $ligne["ID"];
        if($enabled==1){
            $enabled_text="<i class='fas fa-check'></i>";}

        $choose="<button class='btn btn-primary btn-xs' type='button' OnClick=\"Loadjs('$page?add=$ID&value=$valueencoded');\">{select_this_object}</button>";


        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:99%'><strong>$GroupName</strong></td>";
        $html[]="<td style='width:1%' class='center' nowrap>$enabled_text</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$choose</td>";
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
    $html[]="$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

