<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["refresh-ssl-cache"])){refresh_ssl_cached();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["search"])){table_rows();exit;}


if(isset($_GET["rebuild-ssl-cache"])){rebuild_ssl_cache_confirm();exit;}
if(isset($_POST["rebuild-ssl-cache"])){rebuild_ssl_cache_confirm_track();exit;}
if(isset($_GET["search"])){search_form();exit;}
if(isset($_GET["delete"])){delete_certificate();exit;}

search_form();

function rebuild_ssl_cache_confirm_track(){
    admin_tracks("Clean the Proxy SSL Cache");
}

function rebuild_ssl_cache_confirm(){
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $action_empty_cache_ask=$tpl->_ENGINE_parse_body("{action_empty_cache_ask}");
    $action_empty_cache_ask=str_replace("%s","{certificates}",$action_empty_cache_ask);
    $function=$_GET["function"];
    $rebuild_ssl_cache=$tpl->framework_buildjs("/proxy/ssl/cache/rebuild",
    "squid.access.center.progress","squid.access.center.progress.log",
    "rebuild-squisslcache","$function()");


    $tpl->js_confirm_execute($action_empty_cache_ask,"rebuild-ssl-cache","yes",$rebuild_ssl_cache);

}


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$html[]="<div id='div-ssl-proxy-caches' style='margin-top:10px'></div>";
    $html[]="<script>LoadAjax('div-ssl-proxy-caches','$page?search=yes');</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function search_form():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:15px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}
function delete_certificate():bool{
    $ID=intval($_GET["delete"]);
    $tpl=new template_admin();
    $json=json_decode( $GLOBALS["CLASS_SOCKETS"]->REST_API("proxy/ssl/delcert/$ID"));
    $q=new lib_sqlite("/home/artica/SQLITE/ssl_db.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM certificates WHERE ID=$ID");
    $sitename=$ligne["sitename"];
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    header("content-type: application/x-javascript");
    echo ("$('#acl-$ID').remove();");
    return admin_tracks("Deleted proxy $sitename generated ssl certificate");
}

function table_rows(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $function=$_GET["function"];
    $search=$_GET["search"];

    $html[]="<div id='rebuild-squisslcache' style='margin-top:15px'></div>";
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{hostname}</th>";
    $html[]="<th class='awc-nowrap'>{from_date}</th>";
    $html[]="<th class='awc-nowrap'>{to_date}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='sslcertifica-rows'>";

    $jsAfter="LoadAjax('table-firewall-rules','$page?table=yes&eth={$_GET["eth"]}');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
    $q=new lib_sqlite("/home/artica/SQLITE/ssl_db.db");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    // Pentest 2026-11-04 : search etait concatene dans la requete.
    $sqlparams=array();
    $sql="SELECT * FROM certificates ORDER BY sitename";
    if(strlen($search)>2){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT * FROM certificates WHERE sitename LIKE ? ORDER BY sitename";
        $sqlparams=array($search);
    }
    $results=$q->QUERY_SQL_PARAMS($sql,$sqlparams);
    $ico=ico_certificate;
    $TRCLASS=null;

    foreach($results as $index=>$ligne) {
        $TRCLASS=null;
        $sitename=$ligne["sitename"];
        $validfrom=intval($ligne["validfrom"]);
        $validto=intval($ligne["validto"]);
        $issuer=$ligne["issuer"];
        $ID=$ligne["ID"];
        if(trim($sitename)==null){continue;}
        $distance=distanceOfTimeInWords(time(),$validto);
        $delete=$tpl->icon_delete("Loadjs('$page?delete=$ID');");
        $html[]="<tr class='$TRCLASS' id='acl-$ID'>";
        $html[]="<td><strong><i class='$ico'></i>&nbsp;$sitename</strong></td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->time_to_date($validfrom,true)."</td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->time_to_date($validto,true)."<br><small>{expire_in}: $distance</small></td>";
        $html[]="<td  style='text-align:left'>$issuer</td>";
        $html[]="</tr>";
    }


    $html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='sslcertifica-pager'></span></div>";
	$html[]="</div></div>";
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
";

    $buttons="<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?refresh-ssl-cache=yes&function=$function');\"><i class='fad fa-repeat-alt'></i> {rescan} </label>
    	<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?rebuild-ssl-cache=yes&function=$function');\"><i class='fas fa-empty-set'></i> {rebuild_ssl_cache} </label>
     </div>";

    $TINY_ARRAY["TITLE"]="{ssl_protocol}&nbsp;&raquo;&nbsp;{certificates}";
    $TINY_ARRAY["EXPL"]="{global_ssl_certificate_proxy_list}";
    $TINY_ARRAY["URL"]="proxy-whitelists";
    $TINY_ARRAY["BUTTONS"]=$buttons;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]=$jstiny;
    $html[]="</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"sslcertifica-rows","sslcertifica-pager");
}



function refresh_ssl_cached(){
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ssl/certificates");
    echo "$function();\n";

}




