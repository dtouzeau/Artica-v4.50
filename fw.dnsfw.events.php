<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


// "if(isset($_GET["table"])){table();exit;}" pointait sur une fonction table()
// qui n'a jamais existe dans ce fichier: l'URL repondait un fatal
// « Call to undefined function table() ». Aucun appelant ne l'utilisait.
if(isset($_GET["search"])){search();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $searchBlock=$tpl->search_block($page,null);
	echo $tpl->_ENGINE_parse_body($searchBlock);

}

function srules():array{
    $FWRULES[0]="PASS";
    $rows = acls_api_get("/dnsfw/acls") ?? [];
    if(!is_array($rows)){$rows=array();}
    foreach ($rows as $ligne){
        $FWRULES[intval($ligne["id"] ?? 0)]=$ligne["ruleName"] ?? "";
    }
    return $FWRULES;
}

// --- Cellules du tableau: une cellule = une fonction ------------------------
// Le nom de domaine vient du journal du pare-feu DNS: il a ete choisi par le
// client qui a emis la requete. Tout est echappe.
// Pentest 2026-11-04 : champs du journal du pare-feu DNS (donnee externe) : htmlspecialchars UTF-8 rendait "" sur du Latin-1, remplace par req_html (XSS stockee).
function dnsfwev_cell_time($zDate):string{
    return "<td style='width:1%' nowrap><span class='awc-meta awc-mono'>".req_html((string)$zDate)."</span></td>";
}
function dnsfwev_cell_src($ip):string{
    // Un </a> orphelin fermait ici un lien qui n'a jamais ete ouvert.
    return "<td style='width:1%' nowrap><span class='awc-mono'>".req_html((string)$ip)."</span></td>";
}
function dnsfwev_cell_query($domain,$qtype):string{
    $h="<td><span class='awc-name'>".req_html((string)$domain)."</span>";
    if(strlen((string)$qtype)>0){
        $h.="&nbsp;<span class='awc-tag'>".req_html((string)$qtype)."</span>";
    }
    return $h."</td>";
}
// ruleid 0 = aucune regle ne s'est appliquee, la requete est passee. Toute
// autre valeur est une regle du pare-feu DNS qui a filtre la requete: les deux
// etats ne peuvent pas porter le meme badge.
function dnsfwev_cell_rule($ruleid,$rulename):string{
    if(intval($ruleid)==0){
        return "<td style='width:1%' nowrap><span class='awc-badge awc-badge-on'>PASS</span></td>";
    }
    $label=(string)$rulename;
    if(strlen($label)==0){$label="#".intval($ruleid);}
    return "<td style='width:1%' nowrap><span class='awc-badge awc-badge-alert'>".req_html($label)."</span></td>";
}

// "rule <motif>" filtre sur le NOM de la regle. Le motif etait remis tel quel
// dans preg_match("#$rsearch#i"): un "rule (" suffisait a produire un motif
// invalide -- preg_match echoue, le filtre ne s'applique pas, et rien ne le
// dit. Et seule la DERNIERE regle correspondante etait retenue: une recherche
// qui en designait trois n'en filtrait qu'une.
function dnsfwev_rule_ids($rsearch,$FWRULES):array{
    $ids=array();
    if($rsearch==null){return $ids;}
    $quoted=preg_quote(trim((string)$rsearch),"#");
    if($quoted==""){return $ids;}
    foreach ($FWRULES as $index => $name) {
        if(preg_match("#$quoted#i",(string)$name)){
            $ids[]=intval($index);
        }else{
            VERBOSE("rsearch=[$rsearch] NO MATCH [$name]",__LINE__);
        }
    }
    return $ids;
}

function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $FWRULES=srules();
	$q=new postgres_sql();

    $rsearch=null;$isrule=false;
    $rawsearch=isset($_GET["search"])?(string)$_GET["search"]:"";
    if(preg_match("#(rule|r.*?gle)(\s+|=)(.+?)(\s+|$)#",$rawsearch,$re)){
        $rsearch=$re[3].$re[4];
        $rawsearch=str_replace($re[1].$re[2].$re[3].$re[4],"",$rawsearch);
    }
    if(preg_match("#is rule#",$rawsearch,$re)){
        $isrule=true;
        $rawsearch=str_ireplace("is rule","",$rawsearch);
    }

    // query_pattern() commence par CLEAN_BAD_XSS_SQL(), qui retire notamment
    // les apostrophes, les parentheses et les points-virgules: c'est ce qui
    // protege l'interpolation de $pattern ci-dessous (postgres_sql::QUERY_SQL
    // n'expose pas de variante a parametres liees).
    $search=$tpl->query_pattern(trim(strtolower($rawsearch)));

    if(!is_array($search)){$search=array();}
    $MAX=intval($search["MAX"] ?? 0);
    $pattern=$search["Q"] ?? null;
    if($MAX==0){$MAX=250;}

    $ruleids=dnsfwev_rule_ids($rsearch,$FWRULES);
    $xrule=null;$xrule1=null;
    if(count($ruleids)>0){
        $in=@implode(",",$ruleids);
        $xrule=" AND ruleid IN ($in)";
        $xrule1="WHERE ruleid IN ($in)";
    }
    if($xrule1==null){
        if($isrule){
            $xrule1="WHERE ruleid > 0";
        }
    }

    $sql="SELECT * FROM dnsfw_access $xrule1 ORDER BY zdate DESC LIMIT ".intval($MAX);

    if($pattern<>null){
        VERBOSE("rsearch=[$rsearch]",__LINE__);

        if($xrule==null){
            if($isrule){
                $xrule=" AND ruleid>0";
            }
        }

        $sql="SELECT * FROM dnsfw_access WHERE (domain LIKE '$pattern' OR qtype LIKE '$pattern' OR TEXT(ipaddr) LIKE '$pattern') $xrule ORDER BY zdate DESC LIMIT ".intval($MAX);
    }

	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }

    $rows=array();
	while ($ligne = pg_fetch_assoc($results)) {
		$ruleid=intval($ligne["ruleid"] ?? 0);
		$rulename=isset($FWRULES[$ruleid])?$FWRULES[$ruleid]:"";
		$rows[]="<tr>"
			.dnsfwev_cell_time($tpl->time_to_date($ligne["zdate"],true))
			.dnsfwev_cell_src($ligne["ipaddr"] ?? "")
			.dnsfwev_cell_query($ligne["domain"] ?? "",$ligne["qtype"] ?? "")
			.dnsfwev_cell_rule($ruleid,$rulename)
			."</tr>";
	}
	$c=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>";
	$html[]="<th nowrap>{time}</th>";
	$html[]="<th nowrap>{src}</th>";
	$html[]="<th nowrap>{query}</th>";
	$html[]="<th nowrap>{rule}</th>";
	$html[]="</tr></thead><tbody id='dnsfwev-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	// Le <tfoot> ne portait qu'un <ul class='pagination'> jamais alimente:
	// l'init footable ciblait $('#table-<time>'), un identifiant qu'AUCUN
	// element de la page ne portait.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='dnsfwev-pager'></span>";
	$html[]="</div></div></div>";

	// La requete SQL etait affichee a chaque recherche, en clair, sous le
	// tableau. Elle n'a d'interet qu'en mode ?verbose.
	if(isset($GLOBALS["VERBOSE"]) && $GLOBALS["VERBOSE"]){
		$html[]="<div class='awc-meta' style='padding:0 16px 10px 16px'>"
			."<span style='font-family:monospace'>".htmlspecialchars($sql)."</span></div>";
	}

	// Le script restait DANS _ENGINE_parse_body(), dont la regex #{(.+?)}# lit
	// chaque bloc d'accolades JS comme un {token}.
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"dnsfwev-rows","dnsfwev-pager");
    return true;

}
