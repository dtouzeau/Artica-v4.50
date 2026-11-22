<?php
// Post SP10 - 13/10/2026
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.categories.inc');
include_once("ressources/class.ldap-extern.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["list"])){table();exit;}
if(isset($_GET["category-add-js"])){categoy_add_js();exit;}
if(isset($_GET["category-add"])){categoy_add();exit;}
if(isset($_GET["category-post-js"])){categoy_post();exit;}
if(isset($_GET["category-del-js"])){category_del();exit;}	
	//category-del-js=$categorykey&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md

page();

/**
 * Les trois valeurs que toutes les fonctions de cette page recoivent par l'URL.
 *
 * Elles partaient BRUTES dans des requetes SQL (dont un DELETE), dans des URL
 * et dans des chaines JavaScript. Ce sont toutes des entiers: l'identifiant de
 * regle, le mode (0 = liste noire, 1 = liste blanche) et l'identifiant de
 * categorie.
 */
function dnscat_ruleid():int{ return intval($_GET["ID"] ?? 0); }
function dnscat_modeblk():int{ return intval($_GET["modeblk"] ?? 0); }
/** L'id de ligne a retirer du DOM: un md5, donc hexadecimal. */
function dnscat_md():string{ return preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? "")); }

function categoy_add_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog2("{categories}", "$page?category-add=yes&ID=".dnscat_ruleid()."&modeblk=".dnscat_modeblk());
	
	
}


function category_del(){
	header("content-type: application/x-javascript");
	// Ces trois valeurs partaient BRUTES dans un DELETE.
	$category=intval($_GET["category-del-js"] ?? 0);
	$ID=dnscat_ruleid();
	$modeblk=dnscat_modeblk();
	$md=dnscat_md();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE category='$category' AND modeblk='$modeblk' AND webfilter_id='$ID'");
	if(!$q->ok){$tpl->js_error($q->mysql_error);return;}
	if($md!==""){echo "$('#{$md}').remove();\n";}
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";
}

function categoy_post(){
	header("content-type: application/x-javascript");
	// Memes trois valeurs, memes injections: SELECT puis INSERT.
	$category=intval($_GET["category-post-js"] ?? 0);
	$ID=dnscat_ruleid();
	$modeblk=dnscat_modeblk();
	$md=dnscat_md();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilter_blks WHERE category='$category' AND modeblk='$modeblk' AND webfilter_id='$ID'");
	if(!is_array($ligne)){$ligne=array();}
	if(intval($ligne["ID"] ?? 0)>0){return;}

	$q->QUERY_SQL("INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ID','$category','$modeblk')");
	if(!$q->ok){$tpl->js_error($q->mysql_error);return;}
	admin_tracks("DNS filtering rule $ID: added category $category (modeblk=$modeblk)");

	if($md!==""){echo "$('#{$md}').remove();\n";}
	echo "RefreshUfdbCategoriesList();\n";
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";
	
	
}

function page(){
	$page=CurrentPageName();
	// $_GET["ID"] et $_GET["modeblk"] partaient BRUTS dans cette chaine
	// JavaScript: un "?ID=..." forge s'executait dans la session.
	$ID=dnscat_ruleid();
	$modeblk=dnscat_modeblk();
	echo "<div id='ufdb-categories-list' style='margin-top:20px'></div>
	<script>
		function RefreshUfdbCategoriesList(){
			LoadAjax('ufdb-categories-list','$page?list=yes&ID=$ID&modeblk=$modeblk');
		}
		RefreshUfdbCategoriesList();
	</script>	
	";
	
	
}

function categoy_add(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	
	$ruleid=dnscat_ruleid();
	$modeblk=dnscat_modeblk();
	// Ces deux valeurs partaient BRUTES dans le WHERE.
	$results=$q->QUERY_SQL("SELECT `category` FROM webfilter_blks WHERE `webfilter_id`=$ruleid AND modeblk=$modeblk");
	if(!is_array($results)){$results=array();}

	$cats=array();
	foreach ($results as $ligne){$cats[$ligne["category"]]=true;}
	
	
	$dans=new dansguardian_rules();
	
	$q=new postgres_sql();
	if(!$q->TABLE_EXISTS("personal_categories")){
		$categories=new categories();
		$categories->initialize();
		
	}
	$sql="SELECT *  FROM personal_categories ORDER BY categoryname";
	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$sql="SELECT *  FROM personal_categories WHERE free_category=1 ORDER BY categoryname";
	}
	
	
	$category=$tpl->_ENGINE_parse_body("{category}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	// La requete etait executee DEUX FOIS, le premier resultat jete.
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars((string)$q->mysql_error)));return;}
	
	$TRCLASS=null;
	
	$html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=false class='text-capitalize' >&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$category</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$description}</th>";
	$html[]="<th data-sortable=false>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='dnscat-add-rows'>";

	$rows=array();
	$users=new usersMenus();
	
	$UfdbMasterCache=@unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"),array("allowed_classes"=>false));
	if(!is_array($UfdbMasterCache)){$UfdbMasterCache=array();}
	
	while ($ligne = pg_fetch_assoc($results)) {
		$category_id=$ligne["category_id"];
		$categoryname=$ligne["categoryname"];
		$categorykey=$ligne["categorykey"];
		$categorytable=$ligne["categorytable"];
		$items=$ligne["items"];
		$category_id=$ligne["category_id"];
		$category_description=$ligne["category_description"];
		$category_icon=$ligne["category_icon"];
		$master_category=$ligne["master_category"];
		$official_category=$ligne["official_category"];
		$free_category=$ligne["free_category"];
		$ISOFFICIAL=false;
		if($official_category==1){$ISOFFICIAL=true;}
		if($free_category==1){$ISOFFICIAL=true;}
		if($category_icon==null){$category_icon="20-categories-personnal.png";}
		if(isset($cats[$category_id])){continue;}
		$elements=null;
		
		
		$category_table=$categorytable;
		if(!$ISOFFICIAL){
			$category_table_elements=$items;
		}else{
			$category_table_elements=$UfdbMasterCache[$category_id]["items"];
		}
		$DBTXT=array();
		$database_items=null;
		$license=null;
		$img="{$category_icon}";
		$val=0;
		$md=md5($ligne["categorykey"]);
		// utf8_encode() convertit de l'ISO-8859-1 vers l'UTF-8, or la base est
		// deja en UTF-8: il double-encodait les accents des descriptions. Il est
		// de surcroit deprecie depuis PHP 8.2.
		$ligne['description']=(string)$category_description;

		$js="Loadjs('$page?category-post-js=".intval($category_id)."&ID=$ruleid&modeblk=$modeblk&md=$md')";
		$button="<button class='btn btn-primary btn-xs' OnClick=\"javascript:$js\">{select}</button>";
		if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
			if($official_category==1){
				$license=" <i class='text-danger'>({license_error})</i>";
				$button="<button class='btn btn-default btn-xs' OnClick=\"javascript:blur()\">{select}</button>";
				
			}
		}
		if($category_table_elements>0){$elements="<br><strong><small>".FormatNumber($category_table_elements)." {items}</small></strong>";}
		
		$rows[]="<tr id='$md'>"
			."<td style='width:1%'><img src='".htmlspecialchars($img)."'></td>"
			."<td style='width:1%' nowrap><span class='awc-name'>".$tpl->_ENGINE_parse_body($categoryname)."</span></td>"
			."<td>".$tpl->_ENGINE_parse_body("{$ligne['description']} $license{$elements}")."</td>"
			.$tpl->_ENGINE_parse_body("<td style='text-align:right' nowrap>$button</td>")
			."</tr>";

	}

	$c=count($rows);
	if($c==0){
		$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	// Le <tfoot> portait un pager footable qu'AUCUN init n'a jamais alimente sur
	// cette page: un <ul class='pagination'> vide en pied de tableau.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='dnscat-add-pager'></span>";
	$html[]="</div></div></div>";

	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"dnscat-add-rows","dnscat-add-pager");

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	
	
	$tableProd="webfilter_blks";
	$items=$tpl->_ENGINE_parse_body("{items}");
	$compile=$tpl->_ENGINE_parse_body("{compile}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$catz=new mysql_catz();
	$TRCLASS=null;
	
	
	
	$ruleid=dnscat_ruleid();
	$modeblk=dnscat_modeblk();
	// Ces deux valeurs partaient BRUTES dans le WHERE.
	$results = $q->QUERY_SQL("SELECT `category` FROM $tableProd WHERE `webfilter_id`=$ruleid AND modeblk=$modeblk");
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars((string)$q->mysql_error)));
		return;
	}
	if(!is_array($results)){$results=array();}

	$html=array();
	$html[]="
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?category-add-js=yes&ID=$ruleid&modeblk=$modeblk')\"><i class='fa fa-plus'></i> {add_categories} </label>
			</div>";
	$html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>";
	$html[]="<th style='width:1%'>&nbsp;</th>";
	$html[]="<th>$category</th>";
	$html[]="<th>$description</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='dnscat-rows'>";

	$postgres=new postgres_sql();
	$rows=array();
	foreach ($results as $ligne){
		$category_id=intval($ligne["category"]);
		$ligne2=pg_fetch_array($postgres->QUERY_SQL("SELECT * FROM personal_categories WHERE category_id='$category_id'"));
		if(!is_array($ligne2)){$ligne2=array();}
		$categoryname=$tpl->_ENGINE_parse_body((string)($ligne2["categoryname"] ?? ""));
		$category_icon=(string)($ligne2["category_icon"] ?? "");
		// utf8_encode() double-encodait les accents (la base est deja en UTF-8)
		// et est deprecie depuis PHP 8.2.
		$descr=$tpl->_ENGINE_parse_body((string)($ligne2["category_description"] ?? ""));

		$md=md5("{$category_id}{$ruleid}");
		$jsdel="Loadjs('$page?category-del-js=$category_id&ID=$ruleid&modeblk=$modeblk&md=$md')";

		$rows[]="<tr id='$md'>"
			."<td style='width:1%'><img src='".htmlspecialchars($category_icon)."'></td>"
			."<td><span class='awc-name'>$categoryname</span></td>"
			."<td>$descr</td>"
			."<td><div class='awc-acts'>".$tpl->icon_delete($jsdel,"AsDnsAdministrator")."</div></td>"
			."</tr>";

	}
	$c=count($rows);
	if($c==0){
		$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	// Meme <tfoot> mort que dans categoy_add(): un pager footable sans init.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='dnscat-pager'></span>";
	$html[]="</div></div></div>";

	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"dnscat-rows","dnscat-pager");
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}