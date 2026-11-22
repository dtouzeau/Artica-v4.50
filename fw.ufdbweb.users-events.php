<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.memcached.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}

page();


function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,"&table=yes");
    echo "</div>";
}

function empty_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_confirm_empty("{requests_list}", "empty", "yes","LoadAjax('ufdbweb-requests-div','$page?table=yes');");
}
function empty_table(){
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL("DELETE FROM webfilters_usersasks");
}

function delete_js(){
	$md5=$_GET["delete-js"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	// Pentest 2026-11-04 : delete-js etait concatene dans la requete.
	$results=$q->QUERY_SQL_PARAMS("SELECT * FROM webfilters_usersasks WHERE `zmd5`=?",array((string)$md5));
	$ligne=(is_array($results) && isset($results[0]))?$results[0]:array();
	$uid=$ligne["uid"];
	$ipaddr=$ligne["ipaddr"];
	$www=$ligne["sitename"];
	$tpl->js_confirm_delete("$uid/$ipaddr ($www)", "delete", $md5,"$('#$md5').remove()");
	
}

function delete(){
	$md5=$_POST["delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	// Pentest 2026-11-04 : delete etait concatene dans la requete.
	$q->QUERY_SQL_PARAMS("DELETE FROM webfilters_usersasks WHERE `zmd5`=?",array((string)$md5));
	if(!$q->ok){echo $q->mysql_error;return;}
    admin_tracks("Remove unblock ticket $md5");
}



function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	//zmd5,ipaddr,sitename,uid,`info`,zdate
    $h="data-sortable=true class='text-capitalize' data-type='text'";
	$html[]="<table id='ufdbweb-usrsevnts-table' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th $h >{created}</th>";
	$html[]="<th $h >{website}</th>";
	$html[]="<th $h>{member}</th>";
    $html[]="<th $h >{category}</th>";
	$html[]="<th $h >{rule}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


    $sql="SELECT * FROM webfilterev ORDER BY zDate LIMIT 500";

	$search=(string)($_GET["search"] ?? "");
	$params=array();
    if(strlen($search)>1){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT * FROM webfilterev WHERE (
            (domain LIKE $1) OR 
            (categoryname LIKE $1) OR
            (clientaddr LIKE $1) OR
            (clientname LIKE $1) OR
            (clientuser LIKE $1) OR
            (webrulename LIKE $1) )
            ORDER BY zDate LIMIT 500";
        // Pentest 2026-11-04 : search etait concatene dans la requete.
        $params=array($search);

    }
	
	$q=new postgres_sql();

	if(count($params)>0){$results=$q->QUERY_SQL_PARAMS($sql,$params);}else{$results=$q->QUERY_SQL($sql);}
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	$s="style='width:1%' nowrap";
	$TRCLASS=null;
    while($ligne=@pg_fetch_assoc($results)){
		// Pentest 2026-11-04 : domaine/client/categorie/regle (donnee externe) affiche sans encodage (XSS stockee).
		$domain=req_html($ligne["domain"]);
		$time=strtotime($ligne["zdate"]);
		$categoryname=req_html($ligne["categoryname"]);
        $webrulename=req_html($ligne["webrulename"]);
		$clientaddr=req_html($ligne["clientaddr"]);
        $clientname=req_html($ligne["clientname"]);
        $clientuser=req_html($ligne["clientuser"]);
        $zmd5=md5(serialize($ligne));
        $zDate=$tpl->time_to_date($time,true);
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
        $html[]="<td $s>$zDate</td>";
        $html[]="<td $s><strong>$domain</strong></td>";
        $usr=array();
        if(strlen($clientname)>0){$usr[]=$clientname;}
        if(strlen($clientuser)>0){$usr[]=$clientuser;}
        if(strlen($clientaddr)>0){$usr[]=$clientaddr;}
		$html[]="<td>". @implode(" | ",$usr)."</td>";
		$html[]="<td $s>$categoryname</td>";
		$html[]="<td $s>$webrulename</td>";
		$html[]="</tr>";
		
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

    $TINY_ARRAY["TITLE"]="{user_events}";
    $TINY_ARRAY["ICO"]="fad fa-user-lock";
    $TINY_ARRAY["EXPL"]="{web_page_error_usrs_evnts}";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#ufdbweb-usrsevnts-table').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
</script>";	
	echo $tpl->_ENGINE_parse_body($html);
	
}