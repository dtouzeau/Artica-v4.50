<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// Inclusion INCONDITIONNELLE: awc_table_style()/awc_pager_js()/awc_sort_js() vivent ici.
// Placee dans le bloc if(!isset($GLOBALS["CLASS_SOCKETS"])) ci-dessus elle ne s'executerait
// jamais (class.template-admin.inc a deja charge class.sockets.inc) -> "Call to undefined
// function awc_table_style()" au rendu, et un spinner qui ne s'arrete plus.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["delete-js"])){delete_table_js();exit;}
if(isset($_POST["delete"])){delete_table();exit;}
if(isset($_GET["refresh-js"])){refresh_js();exit;}
if(isset($_GET["table"])){table();exit;}
page();


function delete_table_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// Pentest 2026-11-04 : delete-js et id etaient recopies sans encodage dans la page (XSS).
	$table=preg_replace('#[^A-Za-z0-9_.$-]#','',(string)$_GET["delete-js"]);
	$id=preg_replace('#[^A-Za-z0-9_-]#','',(string)$_GET["id"]);
	$tpl->js_confirm_empty("{table}: $table", "delete", $table,"$('#$id').remove()");
	
	
}

function delete_table(){
	$tpl=new template_admin();
	// Pentest 2026-11-15 : TRUNCATE TABLE {$_POST["delete"]} etait concatene brut
	// dans pg_query() -> stacked queries (SQLi, et RCE superuser via COPY TO PROGRAM).
	// En plus, le handler n'avait aucun controle de privilege : seul le bouton d'UI
	// portait IsSecurity("AsDatabaseAdministrator"). On exige ce droit cote serveur,
	// et on valide le nom contre l'inventaire reel (ztables) avant tout usage.
	if(!$tpl->IsSecurity("AsDatabaseAdministrator")){
		header("HTTP/1.1 403 Forbidden");
		echo "{access_denied}";
		return;
	}
	$table=trim((string)$_POST["delete"]);
	$allowed=array();
	$known=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
	$rows=$known->QUERY_SQL("SELECT tablename FROM ztables");
	if(is_array($rows)){foreach($rows as $ligne){$allowed[$ligne["tablename"]]=true;}}
	if(!isset($allowed[$table])){
		header("HTTP/1.1 400 Bad Request");
		echo "{access_denied}";
		return;
	}
	admin_tracks("Remove PostgreSQL table $table");
	$q=new postgres_sql();
	// Nom valide contre la liste blanche ; quoting d'identifiant en defense en profondeur.
	$ident='"'.str_replace('"','""',$table).'"';
	$q->QUERY_SQL("TRUNCATE TABLE $ident");
    if(!$q->ok){echo $q->mysql_error;}
}

function refresh_js(){
    $sock=new sockets();
    $page=CurrentPageName();
    $sock->REST_API("/postgresql/pgsize");
    sleep(2);
    header("content-type: application/x-javascript");
    echo "LoadAjax('tables-pg-size','$page?table=yes');";


}
function page(){
    $page=CurrentPageName();
    echo "<div id='tables-pg-size'></div>
    <script>LoadAjax('tables-pg-size','$page?table=yes');</script>";
}

/**
 * Une table PostgreSQL: identite a gauche (nom cliquable vers le navigateur SQL,
 * volume brut en sous-titre), puis nombre d'enregistrements, taille lisible, date
 * de releve, et l'action de vidage.
 *
 * La cle de tri vit sur le <tr> (data-sort) et vaut TOUJOURS l'octet brut, jamais
 * le texte affiche: "1,2 Go" et "980 Mo" ne se comparent pas lexicalement.
 */
function pgt_row($tpl,$ligne):string{
	$page=CurrentPageName();
	$tablename=$ligne["tablename"];
	$md=md5($tablename);
	$zbytes=floatval($ligne["zbytes"]);
	$total_bytes=FormatBytes($zbytes/1024);
	$rows=FormatNumber($ligne["zrows"]);
	$zdate=$tpl->time_to_date($ligne["zdate"],true);
	$tablenameenc=urlencode($tablename);

	// icon_delete() rendrait un bouton Bootstrap au milieu des actions plates du
	// tableau awc; on garde son controle de privilege (IsSecurity) et on rend le
	// lien au dessin de la table.
	$del="";
	if($tpl->IsSecurity("AsDatabaseAdministrator")){
		$del="<a class='awc-act awc-act-danger' title='{empty}' href=\"javascript:blur();\" "
			."onclick=\"Loadjs('$page?delete-js=$tablenameenc&id=$md')\"><i class='fas fa-eraser'></i></a>";
	}

	$h ="<tr id='$md' data-sort='".intval($zbytes)."'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-table'></i></span>";
	$h.="<span><span class='awc-name'><a href=\"javascript:blur();\" title='{browse}' "
		."onclick=\"Loadjs('fw.postgresql.browse.php?table=$tablenameenc')\">".htmlspecialchars($tablename)."</a></span>";
	$h.="<div class='awc-meta'>".number_format($zbytes,0,"."," ")." bytes</div>";
	$h.="</span></div></td>";
	$h.="<td class='awc-mono' style='width:1%' nowrap>$rows</td>";
	$h.="<td style='width:1%' nowrap><span class='awc-seen'>$total_bytes</span></td>";
	$h.="<td class='awc-mono' style='width:1%' nowrap>".htmlspecialchars($zdate)."</td>";
	$h.="<td style='width:1%'><div class='awc-acts'>$del</div></td>";
	$h.="</tr>";
	return $h;
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");

	$sql="SELECT * FROM ztables ORDER BY zbytes DESC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body($tpl->FATAL_ERROR_SHOW_128($q->mysql_error));
		exit();
	}

	$rows=array();
	$totalbytes=0;
	foreach ($results as $index=>$ligne){
		$totalbytes=$totalbytes+floatval($ligne["zbytes"]);
		$rows[]=pgt_row($tpl,$ligne);
	}
	$total=count($rows);

	$html=array();
	// FooTable fournissait sa propre boite de filtre; le tableau awc ne l'embarque
	// pas, la barre la remplace (meme dessin que search_block, filtrage navigateur).
	$html[]=awc_search_bar("pgt-search");
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{table}</th>";
	$html[]="<th style='width:1%' nowrap>{rows}</th>";
	$html[]="<th style='width:1%' nowrap id='pgt-th-size'>{size} <span id='pgt-th-size-arrow'></span></th>";
	$html[]="<th style='width:1%' nowrap>{updated_on}</th>";
	$html[]="<th style='width:1%' nowrap>{empty}</th>";
	$html[]="</tr></thead><tbody id='pgt-rows'>";
	if($total==0){
		$html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $total / $total {tables} &mdash; ".FormatBytes($totalbytes/1024)."</span>";
	$html[]="<span class='awc-pg' id='pgt-pager'></span>";
	$html[]="</div></div></div>";

    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?refresh-js=yes')", ico_refresh, "{refresh}");
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_POSTGRESQL_VERSION");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="PostgreSQL $version &raquo;&raquo; {tables}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{PostgreSQL_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// Feuille et scripts concatenes AUTOUR du parsing: la regex #{(.+?)}# de
	// _ENGINE_parse_body lit chaque corps de regle CSS et chaque bloc JS comme un jeton.
	// Ordre impose: pager d'abord (il publie tb.__awcRedraw), puis tri, puis filtre.
	$foot="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny</script>";
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"pgt-rows","pgt-pager")
		.awc_sort_js("pgt-th-size","pgt-rows","desc")
		.awc_filter_js("pgt-search","pgt-rows","pgt-pager")
		.$foot;

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
