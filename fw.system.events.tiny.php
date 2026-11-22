<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
// Pentest 2026-11-04 M-02 : file finissait dans du JavaScript et dans du SQL.
// Valeurs reelles : un nom de script (proxy-after-reboot, exec.x.php...).
if(isset($_GET["file"])){$_GET["file"]=preg_replace('/[^A-Za-z0-9_.-]/','',strval($_GET["file"]));}
if(isset($_GET["table"])){table();exit;}
js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog7("{events}","$page?table=yes&file={$_GET["file"]}",950);
}

function table()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $t=time();
    $q = new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $curs = "OnMouseOver=\"this.style.cursor='pointer';\" OnMouseOut=\"this.style.cursor='auto'\"";

    $severityCL[0] = "label-danger";
    $severityCL[1] = "label-warning";
    $severityCL[2] = "label-primary";

    $severityTX[0] = "text-danger";
    $severityTX[1] = "text-warning";
    $severityTX[2] = "text-primary";

    $html[] = "<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{events}</th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    $sql = "SELECT ID,zDate,subject,severity,function,line,filename, LENGTH(content) as content  FROM squid_admin_mysql WHERE filename='{$_GET["file"]}' ORDER BY zDate DESC LIMIT 250";

    $results = $q->QUERY_SQL($sql);
    $TRCLASS = null;

    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $text_class = null;
        $id = md5(serialize($ligne));
        $zdate = $tpl->time_to_date($ligne["zDate"], true);
        $severity_class = $severityCL[$ligne["severity"]];
        $js = "Loadjs('$page?ShowID-js=".intval($ligne["ID"])."&function=".req_url($_GET["function"] ?? "")."')";
        $link = "<span><i class='fa fa-search ' id='$id'></i>&nbsp;<a href=\"javascript:blur();\"
		OnClick=\"$js\" class='{$severityTX[$ligne["severity"]]}' style='font-weight:bold'>";
        if (!isset($ligne["hostname"])) {
            $ligne["hostname"] = null;
        }
        if ($ligne["content"] == 0) {
            $link = "<span style='font-weight:bold'>";
            $js = "blur()";
        }

        // Pentest 2026-11-04 : sujet/hote/fonction de l'evenement systeme (donnee externe) affiches sans encodage (XSS stockee).
        // Le sujet peut porter une mise en forme ecrite par Artica : filtre HTML ; le reste en texte.
        $text = $link . html_safe_fragment($tpl->_ENGINE_parse_body($ligne["subject"])) . $tpl->_ENGINE_parse_body("</a></span>
		<div style='font-size:10px'>{host}:".req_html($ligne["hostname"])." {function}:".req_html($ligne["function"]).", {line}:".intval($ligne["line"])."</div>");


        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td class=\"$text_class\" style='width:1%' nowrap><div class='label $severity_class' style='font-size:13px;padding:10px;width:100%' $curs OnClick=\"$js\" >$zdate</a></div></td>";
        $html[] = "<td class=\"$text_class\" style='width:99%'>$text</td>";
        $html[] = "</tr>";


    }

    $html[] = "</tbody>";
    $html[] = "<tfoot>";

    $html[] = "<tr>";
    $html[] = "<td colspan='3'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[] = "
	<script>
    NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
    $(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
    </script>";

    echo $tpl->_ENGINE_parse_body($html);

}