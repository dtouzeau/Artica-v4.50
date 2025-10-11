<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["discovered"])){discovered();exit;}

js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ipaddr=$_GET["ipaddr"];
    $ipaddr_en=urlencode($ipaddr);
    $tpl->js_dialog6("$ipaddr","$page?discovered=$ipaddr_en",850);
}

function discovered(){
    $ipaddr=$_GET["discovered"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>VLAN</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ports}</th>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT * FROM prads_time WHERE ipaddr='$ipaddr' ORDER BY discovered DESC");

    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $tt=array();
        $time = strtotime($ligne["discovered"]);
        $zdate = $tpl->time_to_date($time, true);
        $portstext=unserialize($ligne["portstext"]);
        foreach ($portstext as $port=>$none){
            $tt[]="{$port}";
        }
        //print_r($ligne);
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td width='1%' nowrap><i class='fad fa-clock'></i>&nbsp;$zdate</td>";
        $html[] = "<td width='1%' nowrap>{$ligne["vlan"]}</td>";
        $html[] = "<td width='50%' nowrap>$ipaddr</td>";
        $html[] = "<td width='99%'>".@implode(", ",$tt)."</td>";
        }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<div class='center'>";

    $html[]="<script>
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    $(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);

}