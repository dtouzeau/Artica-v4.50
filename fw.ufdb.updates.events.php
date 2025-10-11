<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){search();exit;}

start();

function start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div class='row'><div class='ibox-content'>";
    $html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/blacklists_events.db","events","table-loader","&table=yes");
    $html[]="<div id='table-loader'></div>
	</div>
	</div>";

echo $tpl->_ENGINE_parse_body($html);
    
}

function search()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $t=time();

    $_GET["search"] = trim($_GET["search"]);
    $search = $tpl->query_pattern(trim(strtolower($_GET["search"])));
    $q = new lib_sqlite("/home/artica/SQLITE/blacklists_events.db");

    $sql = "CREATE TABLE IF NOT EXISTS `events` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`zdate` INTEGER,
		`TASK` VARCHAR( 64 ),
		`level` INTEGER,
		`subject` TEXT,
		`message` TEXT )";

    $q->QUERY_SQL($sql);


    $sql = "SELECT * FROM events {$search["Q"]} ORDER BY zdate DESC LIMIT {$search["MAX"]}";
    $results = $q->QUERY_SQL($sql);


    $html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{events}</th>";

    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";

    if (!$q->ok) {

        echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";
    }

    $severityCL[0] = "label-danger";
    $severityCL[1] = "label-warning";
    $severityCL[2] = "label-primary";

    $severityTX[0] = "text-danger";
    $severityTX[1] = "text-warning";
    $severityTX[2] = "text-primary";
    $curs = "OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='auto'\"";

    $TRCLASS = null;
    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $text_class = null;
        $id = md5(serialize($ligne));
        $zdate = $tpl->time_to_date($ligne["zdate"], true);
        $severity_class = $severityCL[$ligne["level"]];
        $subject=$ligne["subject"];

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\" width=1% nowrap><div class='label $severity_class' style='font-size:13px;padding:10px;width:100%' $curs OnClick=\"blur()\" >$zdate</a></div></td>";
        $html[]="<td class=\"$text_class\">$subject</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table><div><i>$sql</i></div>";

    $TINY_ARRAY["TITLE"]="{webfiltering_databases}: {update_events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="";
    $TINY_ARRAY["URL"]="webfiltering-databases";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
LoadAjaxSilent('btns-white','fw.ufdb.databases.php?btns-white=');
    $jstiny
</script>";

echo $tpl->_ENGINE_parse_body($html);

}