<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}

if(isset($_GET["page"])){page();exit;}
if(isset($_GET["search"])){search();exit;}
js();

function js(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["gpid"]);
    $tpl->js_dialog10("{items}","$page?page=$gpid");

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["page"]);
    echo $tpl->search_block($page,null,null,null,"&gpid=$gpid");
}

function search(){
    $tpl=new template_admin();
    $gpid=intval($_GET["gpid"]);
    $search=(string)$_GET["search"];
    // articarest squid/urlsdb_search.go: the list is read by the daemon and the
    // search stays data (squid2.php?urlsdb-search pasted it into a root shell).
    $resp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/urlsdb/$gpid/search?q=".rawurlencode($search)),true);
    $urls=array();
    if(is_array($resp) && !empty($resp["success"]) && isset($resp["data"]["urls"]) && is_array($resp["data"]["urls"])){
        $urls=$resp["data"]["urls"];
    }
    $t=time();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{urls}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='rulesitemsur-rows'>";

    $TRCLASS=null;
    foreach ($urls as $url){
        $url=trim((string)$url);
        if($url===""){continue;}
        // A list entry is data: escaped, and its braces kept away from the token engine.
        $url=str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars($url,ENT_QUOTES));
        $html[]= "<tr class='$TRCLASS'>";
        $html[]= "<td><strong>$url</strong></td>";
        $html[]= "</tr>";
    }


    $html[]="</tbody>";
    $html[]="";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='rulesitemsur-pager'></span></div></div></div>";
    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"rulesitemsur-rows","rulesitemsur-pager");

}