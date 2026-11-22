<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des
// tableaux d'entites. Include INCONDITIONNEL.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["table"])){table();exit;}
// (les dispatchs ?item-js=, ?item-id=, xkind, ?delete-js= et delete appelaient
// item_js(), item_id(), item_save(), delete_js() et item_delete() -- CINQ
// fonctions qui n'existent ni dans ce fichier, qui n'en definit que trois, ni
// dans ses includes. Toute requete les portant mourait sur un "Call to
// undefined function". Aucun appelant: cette fenetre est en lecture seule.)


js();

function js(){
	$page=CurrentPageName();
	$domain_id=intval($_GET["domain_id"]);
	$tpl=new template_admin();
	$q=new mysql_pdns();
	// Le nom de domaine vient de la base et partait BRUT dans le titre.
	$domainame=htmlspecialchars((string)$q->GetDomainName($domain_id));
	$tpl->js_dialog5("$domainame: {status}", "$page?popup=yes&domain_id=$domain_id",900);
	
}


function popup(){
	$domain_id=intval($_GET["domain_id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$html="<div id='div-dns-status-data'></div><script> LoadAjax('div-dns-status-data','$page?table=yes&domain_id=$domain_id');</script>";
	echo $html;
}

function table(){
	$domain_id=intval($_GET["domain_id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	
	$q=new mysql_pdns();
	$sql="SELECT * FROM pdnsutil_chkzones";
	if($domain_id>0){$sql="SELECT * FROM pdnsutil_chkzones WHERE domain_id=$domain_id";}
	$results = $q->QUERY_SQL($sql);
	// L'erreur etait affichee PUIS l'execution continuait: mysqli_fetch_assoc()
	// sur false est une TypeError sous PHP 8.
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html());return;}

	$error_type=array(1=>"danger",0=>"warning");
	$error_typet=array(1=>"{warning}",0=>"{info}");

	$rows=array();
	while ($ligne = mysqli_fetch_assoc($results)) {
		// $error_type[...] etait indexe sans garde: un type hors de {0,1}
		// produisait un avertissement PHP et une pastille sans couleur.
		$etype=intval($ligne["error_type"]);
		$zerror=isset($error_type[$etype])?$error_type[$etype]:"default";
		$zlabel=isset($error_typet[$etype])?$error_typet[$etype]:"-";
		$md=md5(serialize($ligne));
		// $domain_id etait ECRASE ici, alors que la fonction s'en sert plus haut
		// pour choisir sa requete. On garde une variable distincte.
		$rowdomain=intval($ligne["domain_id"]);
		$domainname=$tpl->td_href(
			htmlspecialchars((string)$q->GetDomainName($rowdomain)),
			"{click_to_edit}",
			"Loadjs('fw.dns.domain.php?domain-id=$rowdomain');");

		// Le message vient de pdnsutil et partait BRUT dans le HTML.
		$rows[]="<tr id='$md'>"
			."<td class='center' style='width:1%' nowrap><span class='label label-$zerror'>$zlabel</span></td>"
			."<td style='width:1%' nowrap><span class='awc-name'>$domainname</span></td>"
			// Pentest 2026-11-04 : message pdnsutil (donnee externe) : htmlspecialchars UTF-8 rendait "" sur du Latin-1, remplace par req_html (XSS stockee).
			."<td>".req_html((string)$ligne["content"])."</td>"
			."</tr>";
	}
	$c=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	// L'id table-dns-status-data est conserve: un id de tableau est une
	// sentinelle dans cette console.
	$html[]="<table id='table-dns-status-data' class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>";
	$html[]="<th class='center'>{level}</th>";
	$html[]="<th>{domain}</th>";
	// Un </center> PARASITE fermait un element jamais ouvert dans cet en-tete.
	$html[]="<th>{info}</th>";
	$html[]="</tr></thead><tbody id='pdnsst-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	// Le <tfoot> portait un pager footable annoncant DEUX colonnes sur un
	// tableau qui en compte trois.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='pdnsst-pager'></span>";
	$html[]="</div></div></div>";

	// Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body lisait la configuration JSON de footable comme des
	// {tokens}.
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"pdnsst-rows","pdnsst-pager");
}