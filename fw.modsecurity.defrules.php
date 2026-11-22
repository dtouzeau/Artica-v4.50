<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
//$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["ruleid-js"])){ruleid_js();exit;}
if(isset($_GET["ruleid-popup"])){ruleid_popup();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-whitelist"])){enable_whitelist();exit;}

page();

function ruleid_js():bool{
	$page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/modsecurity_rules.db");
	$tpl=new template_admin();
	$ruleid=intval($_GET["ruleid-js"]);
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rules WHERE ID=$ruleid");
    $rulename=$ligne["rulename"];
    if(strlen($rulename)>100){$rulename=substr($rulename,0,97)."...";}
	$tpl->js_dialog1("$rulename","$page?ruleid-popup=$ruleid",995);
    return true;
}
function ruleid_popup(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/modsecurity_rules.db");
    $tpl=new template_admin();
    $ruleid=intval($_GET["ruleid-popup"]);
    $ligne=$q->mysqli_fetch_array("SELECT rulename,content FROM rules WHERE ID=$ruleid");
    $rulename=$ligne["rulename"];
    $content=$ligne["content"];
    $content=htmlspecialchars($content);
    $content=str_replace(",",",<br>",$content);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/modsec/whitelist?ruleid=$ruleid&serviceid=0"),true);
    $isWhitelisted=false;
    if(is_array($json) && $json["Status"]){
        foreach($json["entries"] as $e){if(strlen($e["spath"])==0){$isWhitelisted=true;break;}}
    }
    if(!$isWhitelisted){
        $type="- {active2}";
        $panel="panel-primary";}
    else{
        $type=" - {inactive}";
        $panel="panel-default";
    }

    $html="<div class=\"panel $panel\">
                                        <div class=\"panel-heading\">
                                           $rulename <strong>$type</strong>
                                        </div>
                                        <div class=\"panel-body\">
                                           $content
                                        </div>

                                    </div>";
    echo $tpl->_ENGINE_parse_body($html);


}

function enable_whitelist(){
    $id=intval($_GET["enable-whitelist"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/modsec/whitelist?ruleid=$id&serviceid=0"),true);
    $isWhitelisted=false;
    if(is_array($json) && $json["Status"]){
        foreach($json["entries"] as $e){if(strlen($e["spath"])==0){$isWhitelisted=true;break;}}
    }
    if($isWhitelisted){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/modsec/whitelist", ["action"=>"delete","ruleid"=>$id,"serviceid"=>0]);
        admin_tracks("Remove WAF rule $id from global whitelist");
    }else{
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/modsec/whitelist", ["action"=>"add","ruleid"=>$id,"serviceid"=>0]);
        admin_tracks("Add WAF rule $id To global whitelist");
    }

    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    // search_block remplace la barre de recherche ecrite a la main: il fournit le
    // champ, le div de resultats (results-<hash>), le rafraichissement differe et
    // surtout le nom de fonction que les mutations rappellent pour redessiner la
    // table SANS perdre le filtre courant.
    $html=array();
    $html[]="<div id='progress-modesec-def'></div>";
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->search_block($page,"","","","");
    $html[]="</div>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}




function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	// Les trois valeurs repartent dans du JavaScript (OnClick, $jsafter, URL de
	// pager): liste blanche au site de lecture, sinon un ?function=... force est
	// du code execute dans la session de l'administrateur.
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$divid=preg_replace('/[^A-Za-z0-9_-]/','',(string)(isset($_GET["div-id"])?$_GET["div-id"]:""));
	$search=trim((string)(isset($_GET["search"])?$_GET["search"]:""));
	$pageNo=intval(isset($_GET["page"])?$_GET["page"]:1);
	if($pageNo<1){$pageNo=1;}
	$psize=100;

	// Les regles neutralisees: une entree de liste blanche sans chemin vaut pour
	// tout le service.
	$MAIN_WHITE=array();
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/modsec/whitelist?serviceid=0"),true);
	if(is_array($json) && isset($json["Status"]) && $json["Status"]){
		if(isset($json["entries"]) && is_array($json["entries"])){
			foreach($json["entries"] as $e){
				if(strlen($e["spath"])==0){$MAIN_WHITE[$e["wfrule"]]=true;}
			}
		}
	}

	$q=new lib_sqlite("/home/artica/SQLITE/modsecurity_rules.db");

	// La recherche etait concatenee dans le SQL apres un simple str_replace des
	// jokers: une apostrophe suffisait a casser la requete, ou a la detourner.
	// Elle voyage desormais en parametre lie -- QUERY_SQL_PARAMS est une methode
	// distincte, PAS un second argument de QUERY_SQL (celui-la est le nom de base
	// historique, ignore par lib_sqlite).
	$where="";
	$params=array();
	if($search<>""){
		$like="%".str_replace("*","%",$search)."%";
		$like=str_replace("%%","%",$like);
		$where=" WHERE (rulename LIKE ?) OR (ID LIKE ?)";
		$params=array($like,$like);
	}

	$total=0;
	$rowsCount=$q->QUERY_SQL_PARAMS("SELECT COUNT(*) AS tot FROM rules$where",$params);
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body($tpl->div_error("<b>{please_click_synchronize}</b><p>".
			htmlspecialchars((string)$q->mysql_error)."</p>"));
		return;
	}
	if(isset($rowsCount[0]["tot"])){$total=intval($rowsCount[0]["tot"]);}

	$pages=1;
	if($total>0){$pages=intval(ceil($total/$psize));}
	if($pageNo>$pages){$pageNo=$pages;}
	$offset=($pageNo-1)*$psize;

	// Tri par ID: les identifiants ModSecurity sont porteurs de sens (la famille
	// 942xxx est l'injection SQL, 941xxx le XSS), donc les regrouper vaut mieux
	// qu'un ordre alphabetique de libelles. La page est decoupee par le SQL, pas
	// par le navigateur: la table compte des milliers de regles et icon_check
	// emet TROIS fonctions JavaScript par ligne.
	$sql="SELECT * FROM rules$where ORDER BY ID LIMIT $psize OFFSET $offset";
	$results=$q->QUERY_SQL_PARAMS($sql,$params);
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body($tpl->div_error("<b>{please_click_synchronize}</b><p>".
			htmlspecialchars((string)$q->mysql_error)."</p>"));
		return;
	}

	$rows=array();
	foreach($results as $ligne){
		$rows[]=defrules_row($page,$ligne,isset($MAIN_WHITE[$ligne["ID"]]));
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{rulename}</th>";
	$html[]="<th class='center awc-nowrap'>{status}</th>";
	// {actions} et non {disabled}: la colonne porte le CONTROLE, l'etat est deja
	// dit par le badge de la colonne precedente. L'en-tete d'origine melangeait
	// les deux et laissait croire que la case cochee valait "regle active".
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='defrules-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} ".count($rows)." / ".$tpl->FormatNumber($total).
		"</span><span class='awc-pg'>".awc_pager_server($pageNo,$pages,"mdr-pg-a")."</span></div>";
	$html[]="</div></div>";

	$TINY_ARRAY=array();
	$TINY_ARRAY["TITLE"]="{WAF_LONG} {global_rules}";
	$TINY_ARRAY["ICO"]="fa fa-bars";
	$TINY_ARRAY["EXPL"]="{ModSecurityGLobalRulesExplain}";
	$TINY_ARRAY["BUTTONS"]="";
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// ICON_SCRIPTS_COMPILE() et non $tpl->ICON_SCRIPTS: chaque ligne est batie par
	// defrules_row() avec SA propre instance, et les fonctions BtSwitch<id> partent
	// avec elle. La case s'afficherait et ne repondrait a aucun clic.
	$js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny\n</script>";

	// Le pager recharge CE fragment: div-id et function viennent de search_block.
	$baseurl="$page?search=".urlencode($search)."&function=$function&div-id=$divid";
	$nav ="<script>\n(function(){\n";
	$nav.=" var base=".json_encode($baseurl).", div=".json_encode($divid).";\n";
	$nav.=" if(div==\"\"){return;}\n";
	$nav.=" var as=document.querySelectorAll(\".mdr-pg-a\");\n";
	$nav.=" for(var i=0;i<as.length;i++){\n";
	$nav.="   as[i].onclick=function(){ LoadAjaxTiny(div, base+\"&page=\"+this.getAttribute(\"data-p\")); return false; };\n";
	$nav.=" }\n})();\n</script>";

	// CSS et JS concatenes AUTOUR de l'analyse: la regex #{(.+?)}# du moteur prend
	// chaque corps de regle CSS et chaque bloc JavaScript pour un {jeton}.
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html).$js)
		.$nav;
}

// Une ligne = une regle. Whitelistee veut dire NEUTRALISEE: la colonne d'origine
// s'appelait "{disabled}" et la case cochee eteint la regle -- l'etat affiche
// suit cette semantique, pas l'inverse.
function defrules_row($page,$ligne,$whitelisted):string{
	$tpl=new template_admin();
	$id=$ligne["ID"];
	$name=(string)$ligne["rulename"];
	if(strlen($name)>110){$name=substr($name,0,107)."...";}

	$h ="<tr>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico".($whitelisted?" awc-ico-off":"")."'><i class='fas fa-shield-halved'></i></span>";
	$h.="<span><span class='awc-name".($whitelisted?" awc-name-off":"")."'>";
	$h.="<a href=\"javascript:blur();\" onclick=\"Loadjs('$page?ruleid-js=".urlencode($id)."')\">".
		htmlspecialchars($name)."</a></span>";
	$h.="<div class='awc-meta'>ID: ".htmlspecialchars((string)$id)."</div>";
	$h.="</span></div></td>";
	$h.="<td class='center'><span class='awc-badge ".
		($whitelisted?"awc-badge-off'>{disabled}":"awc-badge-on'>{enabled}")."</span></td>";
	$h.="<td><div class='awc-acts' style='opacity:1'>".
		$tpl->icon_check($whitelisted?1:0,"Loadjs('$page?enable-whitelist=".urlencode($id)."')","signature-$id").
		"</div></td>";
	$h.="</tr>";
	return $h;
}
function enable(){
	
	$filename=(string)$_POST["filename"];
	$q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
	// Pentest 2026-11-04 : filename (POST) etait concatene dans la requete.
	$rs=$q->QUERY_SQL_PARAMS("SELECT enabled FROM suricata_rules_packages WHERE rulefile=?",array($filename));
	$ligne=isset($rs[0])?$rs[0]:array();
	$enabled=intval($ligne["enabled"]);
	if($enabled==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL_PARAMS("UPDATE suricata_rules_packages SET `enabled`='$enabled' WHERE rulefile=?",array($filename));
	if(!$q->ok){echo $q->mysql_error;}
}