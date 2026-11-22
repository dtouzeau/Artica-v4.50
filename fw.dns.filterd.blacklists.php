<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-id"])){item_id();exit;}
if(isset($_GET["item-enable"])){item_enable();exit;}
if(isset($_GET["item-delete"])){item_delete();exit;}
if(isset($_POST["ID"])){item_save();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	echo "<div id='dnsfilter-black-div' style='margin-top:10px'></div><script>LoadAjax('dnsfilter-black-div','$page?table=yes');</script>";
}

function item_enable(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-enable"]);
	// La reponse est evaluee comme du JavaScript par Loadjs, branche d'erreur
	// comprise: poser l'en-tete AVANT tout retour anticipe.
	header("content-type: application/x-javascript");
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM webfilter_blacklists WHERE ID=$ID");
	if(!is_array($ligne)){$ligne=array();}
	if(intval($ligne["enabled"] ?? 0)==1){$enabled=0;}else{$enabled=1;}
	$sql="UPDATE webfilter_blacklists SET enabled=$enabled WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
}

function item_delete(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL("DELETE FROM webfilter_blacklists WHERE ID=$ID");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	header("content-type: application/x-javascript");
	// $md est reinjecte tel quel dans un selecteur jQuery: c'est un md5, donc
	// restreint aux caracteres hexadecimaux.
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	if($md===""){return;}
	echo "$('#{$md}').remove();";
	
}

function item_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-js"]);
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	if($ID==0){$title="{new_item}";}else{
		$ligne=$q->mysqli_fetch_array("SELECT pattern FROM webfilter_blacklists WHERE ID=$ID");
		if(!is_array($ligne)){$ligne=array();}
		// Le motif vient de la base et partait BRUT dans le titre de la modale.
		$title="{item} ".htmlspecialchars((string)($ligne["pattern"] ?? ""))." - $ID";}
	$tpl->js_dialog1($title, "$page?item-id=$ID");
}

function item_id(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-id"]);	
	$title="{new_item}";
	$bt="{add}";
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$jsafter="LoadAjax('dnsfilter-black-div','$page?table=yes');dialogInstance1.close();";
	$ligne=$q->mysqli_fetch_array("SELECT * FROM webfilter_blacklists WHERE ID=$ID");
	// Une entree neuve n'a pas de ligne: sans ces cles, PHP 8 emet une notice
	// par champ et field_array_hash ne selectionne rien.
	if(!is_array($ligne)){$ligne=array();}
	if(!isset($ligne["type"])){$ligne["type"]=1;}
	if(!isset($ligne["pattern"])){$ligne["pattern"]="";}
	if($ID>0){$title="{item}: ".htmlspecialchars((string)$ligne["pattern"]);$bt="{apply}";}
	$ztype[0]="{client_source_ip_address}";
	$ztype[1]="{destination_domain}";

	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_array_hash($ztype, "ztype", "{type}", $ligne["type"]);
	$form[]=$tpl->field_text("pattern", "{item}", $ligne["pattern"],true);
	// form_outside() n'analyse QUE le libelle de son bouton: sans cet appel, le
	// titre et les libelles de champ s'affichaient en {tokens} litteraux.
	echo $tpl->_ENGINE_parse_body(
		$tpl->form_outside($title, $form,"{dnsfilterd_black_explain}",$bt,$jsafter,"AsDnsAdministrator")
	);
	
	
}

function item_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["ID"]);
	$pattern=trim((string)($_POST["pattern"] ?? ""));
	$ztype=intval($_POST["ztype"] ?? 0);
	if($pattern===""){echo $tpl->post_error("{item}: {cannot_be_a_null_value}");return;}

	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	// Le motif partait TEL QUEL dans l'INSERT et dans l'UPDATE: une simple quote
	// suffisait a detourner la requete. QUERY_SQL_PARAMS() prepare et lie au
	// lieu de concatener.
	if($ID==0){
		$q->QUERY_SQL_PARAMS("INSERT INTO webfilter_blacklists (`pattern`,`type`,`enabled`) VALUES (?,?,1)",
			array($pattern,$ztype));
		if(!$q->ok){echo $tpl->post_error($q->mysql_error);return;}
		// Sans ce return, l'INSERT etait suivi d'un UPDATE ... WHERE ID=0 --
		// une requete sans effet, mais executee a chaque creation.
		admin_tracks("DNS filtering blacklist: added $pattern");
		return;
	}
	$q->QUERY_SQL_PARAMS("UPDATE webfilter_blacklists SET `pattern`=?,`type`=? WHERE ID=$ID",
		array($pattern,$ztype));
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return;}
	admin_tracks("DNS filtering blacklist: updated ID=$ID to $pattern");
}


function table(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$add="Loadjs('$page?item-js=0');";
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress.log";
	$ARRAY["CMD"]="dnsfilterd.php?compile-rules=yes";
	$ARRAY["TITLE"]="{apply_webiltering_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-dnsfilter-restart')";
	$t=time();

	$html=array();
	$html[]="
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_dnsfiltering_rules} </label>
			</div>";

	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `webfilter_blacklists` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`pattern` TEXT,`type` INTEGER,enabled INTEGER DEFAULT 1)");

	$results=$q->QUERY_SQL("SELECT * FROM webfilter_blacklists ORDER BY pattern");
	// foreach() sur autre chose qu'un tableau est FATAL sous PHP 8.
	if(!is_array($results)){$results=array();}

	$ztype=array();
	$ztype[0]="{client_source_ip_address}";
	$ztype[1]="{destination_domain}";

	$rows=array();
	foreach ($results as $ligne){
		$rows[]=dnsfbl_row($ligne,$ztype,$tpl,$page);
	}
	$c=count($rows);

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>";
	$html[]="<th>{items}</th>";
	$html[]="<th>{type}</th>";
	$html[]="<th class='center'>{enable}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='dnsfbl-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	// Le <tfoot> portait un pager footable jamais alimente: l'init ciblait
	// #table-<time>, un id qu'AUCUN <table> de cette page ne porte.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='dnsfbl-pager'></span>";
	$html[]="</div></div></div>";

	// Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body lisait la configuration JSON de footable comme des
	// {tokens}. ICON_SCRIPTS_COMPILE() est OBLIGATOIRE: ce tableau porte des
	// icon_check(), qui alimentent aussi $GLOBALS["TPL_ICON_SCRIPTS"] -- seule
	// la version COMPILE draine les deux piles, et les lignes sont desormais
	// baties par leur propre fonction.
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(50,"dnsfbl-rows","dnsfbl-pager");

}

/**
 * Une entree de liste noire.
 *
 * L'id md5 de la <tr> est CONSERVE: item_delete() fait $('#<md>').remove() pour
 * retirer la ligne sans recharger le tableau.
 */
function dnsfbl_row($ligne,$ztype,$tpl,$page):string{
	$ID=intval($ligne["ID"]);
	$md=md5(serialize($ligne));
	$pattern=(string)($ligne["pattern"] ?? "");
	$type=intval($ligne["type"] ?? 0);
	$enabled=intval($ligne["enabled"] ?? 0);

	// $ztype[$ligne["type"]] etait indexe sans garde: un type hors de {0,1}
	// produisait un avertissement PHP et une cellule vide.
	$typeText=isset($ztype[$type])?$ztype[$type]:"<span class='awc-none'>&mdash;</span>";
	$ico=($type==0)?"fas fa-network-wired":"fas fa-globe";

	$h ="<tr id='$md'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='".($enabled==1?"awc-ico":"awc-ico awc-ico-off")."'><i class='$ico'></i></span>";
	// Le motif vient de la base et partait BRUT dans le HTML.
	$h.="<span><span class='awc-name'>"
	   .$tpl->td_href(htmlspecialchars($pattern),"{click_to_edit}","Loadjs('$page?item-js=$ID');")."</span>";
	$h.="<div class='awc-meta awc-mono'>ID: $ID</div>";
	$h.="</span></div></td>";
	$h.="<td>$typeText</td>";
	$h.="<td class='center'>".$tpl->icon_check($enabled,"Loadjs('$page?item-enable=$ID')",null,"AsDnsAdministrator")."</td>";
	$h.="<td><div class='awc-acts'>"
	   .$tpl->icon_delete("Loadjs('$page?item-delete=$ID&md=$md')","AsDnsAdministrator")
	   ."</div></td>";
	$h.="</tr>";
	return $h;
}


