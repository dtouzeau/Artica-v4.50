<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["statistics-domain"])){statistics_domain();exit;}
if(isset($_GET["statistics-domain-table"])){statistics_domain_table();exit;}
if(isset($_GET["statistics-urls"])){statistics_urls();exit;}
if(isset($_GET["statistics-urls-table"])){statistics_urls_table();exit;}
if(isset($_GET["statistics-popup"])){statistics_settings();exit;}
if(isset($_GET["statistics-table-disabled"])){statistics_table_disabled();exit;}
if(isset($_GET["delete-familysite"])){delete_familysite_js();}
if(isset($_POST["delete-familysite"])){delete_familysite_perform();}
if(isset($_GET["delete-uri"])){delete_uri();exit;}
if(isset($_GET["delete-sitename"])){delete_sitename();exit;}
if(isset($_POST["delete-sitename"])){delete_sitename_perform();exit;}
page();


function delete_familysite_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $familysite=$_GET["delete-familysite"];
    $md=$_GET["md"];

    $jsafter="$('#$md').remove();";

    $tpl->js_confirm_delete($familysite,"delete-familysite",$familysite,$jsafter);

}

function delete_sitename(){
    $sitename=$_GET["delete-sitename"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=$_GET["md"];
    $jsafter="$('#$md').remove();";
    $tpl->js_confirm_delete($sitename,"delete-sitename",$sitename,$jsafter);
}
function delete_sitename_perform(){
    $sock=new sockets();
    $familysiteenc=urlencode($_POST["delete-sitename"]);
    $sock->getFrameWork("squid2.php?purge-delete=$familysiteenc");
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM squidpurge WHERE sitename='{$_POST["delete-sitename"]}'");
}
function delete_uri(){
    $uri=urlencode($_GET["delete-uri"]);
    $domain=urlencode($_GET["domain"]);
    $md=$_GET["md"];
    $sock=new sockets();
    $sock->getFrameWork("squid2.php?purge-deleteuri=$uri&domain=$domain");
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM squidpurge WHERE path='{$_GET["delete-uri"]}' AND sitename='{$_GET["domain"]}'");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();";

}
function delete_familysite_perform(){
    $sock=new sockets();
    $familysiteenc=urlencode($_POST["delete-familysite"]);
    $sock->getFrameWork("squid2.php?purge-delete=$familysiteenc");
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM squidpurge WHERE familysite='{$_POST["delete-familysite"]}'");
}


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html="
<div class=\"row border-bottom white-bg dashboard-header\">
<div class=\"col-sm-12\">
<h1 class=ng-binding>{stored_objects}</h1>
<p>{PROXY_CACHE_STORED_OBJECTS_EXPLAIN}</p>
</div>
</div>



<div class='row'><div id='progress-squidcaching-items'></div>
<div class='ibox-content'>
<div id='table-loader-stored-objects'></div>
</div>

<script>
LoadAjax('table-loader-stored-objects','$page?table-start=yes');
</script>";


    echo $tpl->_ENGINE_parse_body($html);
}

function table_start(){
    $page=CurrentPageName();
    echo "<div id='squid-purge-table'></div><script>LoadAjaxSilent('squid-purge-table','$page?table=yes');</script>";
}

function table(){

    $page=CurrentPageName();
    $tpl=new template_admin();


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.purge.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.purge.progress.log";
    $ARRAY["CMD"]="squid2.php?stored-objects=yes";
    $ARRAY["TITLE"]="{analyze_caches}";
    $ARRAY["AFTER"]="LoadAjaxSilent('squid-purge-table','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-squidcaching-items')";
    $t=time();

    $html[]=$tpl->_ENGINE_parse_body("
	
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart\"><i class='fa fa-bolt'></i> {analyze_caches} </label>
			<label class=\"btn btn btn-info\" OnClick=\"Loadjs('fw.proxy.cache.status.php?statistics=yes')\"><i class='fas fa-cogs'></i> {settings} </label>
			</div>
			<div class=\"btn-group\" data-toggle=\"buttons\">
			</div>");

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{objects}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT SUM(size) as size,COUNT(familysite) as hits,familysite FROM squidpurge GROUP BY familysite order by size desc");

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    if($results) {
        while ($ligne = pg_fetch_assoc($results)) {
            if ($TRCLASS == "footable-odd") {
                $TRCLASS = null;
            } else {
                $TRCLASS = "footable-odd";
            }
            $zmd5 = md5(serialize($ligne));
            $familysite = $ligne["familysite"];
            $size = FormatBytes($ligne["size"] / 1024);
            $hits = FormatNumber($ligne["hits"]);
            $familysiteenc = urlencode($familysite);

            $familysite = $tpl->td_href($familysite, "{stored_objects}", "Loadjs('$page?statistics-domain=$familysiteenc')");


            $delete = $tpl->icon_delete("Loadjs('$page?delete-familysite=$familysiteenc&md=$zmd5')", "AsProxyMonitor");


            $html[] = "<tr class='$TRCLASS' id='$zmd5'>";
            $html[] = "<td nowrap><i class=\"fas fa-globe\"></i>&nbsp;$familysite</td>";
            $html[] = "<td width=1% nowrap>$size</td>";
            $html[] = "<td width=1% nowrap>$hits</td>";
            $html[] = "<td width=1% nowrap>$delete</td>";
            $html[] = "</tr>";

        }
        $html[] = "</tbody>";
        $html[] = "<tfoot>";
        $html[] = "<tr>";
        $html[] = "<td colspan='4'>";
        $html[] = "<ul class='pagination pull-right'></ul>";
        $html[] = "</td>";
        $html[] = "</tr>";

    }
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function statistics_urls(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $domain=$_GET["statistics-urls"];
    $domainenc=urlencode($domain);
    $tpl->js_dialog3("{stored_objects}: $domain", "$page?statistics-urls-table=$domainenc",990);

}

function statistics_urls_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $domain=$_GET["statistics-urls-table"];
    $sql="SELECT size,path FROM squidpurge WHERE sitename='$domain' order by size desc LIMIT 250";
    $idtable=md5($domain."urls");
    $html[]="<table id='table-$idtable-items' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{urls}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";


    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new postgres_sql();
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
    $TRCLASS=null;

    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $zmd5=md5(serialize($ligne));
        $path=$ligne["path"];
        $size=$ligne["size"];
        $size=FormatBytes($ligne["size"]/1024);
        $strlen=strlen($path);
        $pathenc=urlencode($path);

        if($strlen>90){$path=$tpl->td_href(substr($path,0,87)."...",$path);}
        $del=$tpl->icon_delete("Loadjs('$page?delete-uri=$pathenc&domain=$domain&md=$zmd5')","AsProxyMonitor");

        $html[]="<tr class='$TRCLASS' id='$zmd5'>";
        $html[]="<td nowrap>$path</td>";
        $html[]="<td width=1% nowrap>$size</td>";
        $html[]="<td width=1% nowrap>$del</td>";
        $html[]="</tr>";

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
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$idtable-items').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


}
function statistics_domain_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $domain=$_GET["statistics-domain-table"];
    $sql="SELECT SUM(size) as size,COUNT(sitename) as hits,sitename FROM squidpurge WHERE familysite='$domain' GROUP BY sitename order by size desc ";
    $idtable=md5($domain);
    $html[]="<table id='table-$idtable-items' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{objects}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new postgres_sql();
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
    $TRCLASS=null;

    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $zmd5=md5(serialize($ligne));
        $sitename=$ligne["sitename"];
        $size=FormatBytes($ligne["size"]/1024);
        $hits=FormatNumber($ligne["hits"]);
        $sitenameenc=urlencode($sitename);

        $del=$tpl->icon_delete("Loadjs('$page?delete-sitename=$sitenameenc&md=$zmd5')","AsProxyMonitor");

        $familysite=$tpl->td_href($sitename,"{stored_objects}","Loadjs('$page?statistics-urls=$sitename')");


        $html[]="<tr class='$TRCLASS' id='$zmd5'>";
        $html[]="<td nowrap>$familysite</td>";
        $html[]="<td width=1% nowrap>$size</td>";
        $html[]="<td width=1% nowrap>$hits</td>";
        $html[]="<td width=1% nowrap>$del</td>";
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
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$idtable-items').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function statistics_domain(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $domain=$_GET["statistics-domain"];
    $domainenc=urlencode($domain);
    $tpl->js_dialog1("{stored_objects}: $domain", "$page?statistics-domain-table=$domainenc");
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}