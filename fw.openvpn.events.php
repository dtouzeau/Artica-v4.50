<?php
// Post SP10 - 13/10/2026
//Artica Service Pack 8 - Sep 2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style() / awc_pager_js() : include INCONDITIONNEL, au premier niveau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Repartiteurs morts RETIRES (2026-09-15) : rule_js(), rule_popup() et main()
// n'etaient definies NI ici NI nulle part pour CETTE page -- les homonymes des
// 85/56/42 autres fichiers sont des fonctions distinctes, PHP etant par requete.
// Toute URL portant ?ruleid-js=, ?rule-popup= ou ?main= rendait un fatal
// « Call to undefined function » : un spinner qui ne se vide jamais.
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["client-js"])){client_js();exit;}
if(isset($_GET["main-start"])){main_start();exit;}

page();

function client_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md5=$_GET["client-js"];
    return $tpl->js_dialog2("{events}","$page?main-start=yes&zmd5=$md5",1024);
}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_OPENVPN}",
        ico_eye,"{APP_OPENVPN_SEARCH_TEXT}","$page?main-start=yes",
        "openvpn-events","progress-openvpn-events",false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function main_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $zmd5="";
    if(isset($_GET["zmd5"])){
        $zmd5=sprintf("&zmd5=%s",urlencode($_GET["zmd5"]));
    }
    // « &main=yes » retire : search() ne l'a jamais lu, il ne servait qu'a
    // pouvoir declencher le repartiteur fatal ?main= si l'ordre changeait.
    echo $tpl->search_block($page,"","","",$zmd5);
    return true;
}

function xPreg($line):array{
    $line=trim($line);

    if(preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+openvpn\[([0-9]+)\]:\s+(.+)#",$line,$re)){
       return $re;
    }

    VERBOSE("NO MATCH 1",__LINE__);
    if(preg_match("#^([0-9\-]+)\s+([0-9:]+)\s+(.+)#",$line,$re)){
        $re[5]="000";
        $re[6]=$re[3];
        $re[3]="";
        return $re;
    }


    VERBOSE("NO MATCH 2",__LINE__);
    if(preg_match("#^([A-Za-z]+)\s+([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+([0-9]+)\s+(.+)#",$line,$re)){
        $re[3]=sprintf("%s %s",$re[3],$re[4]);
        $re[4]="Client";
        $re[5]="Client";
        return $re;
    }

    VERBOSE("NO MATCH 3",__LINE__);
    return array();
}

/**
 * Feuille du tableau d'evenements.
 *
 * Emise HORS de _ENGINE_parse_body() : sa regex #{(.+?)}# lit chaque corps de
 * regle CSS comme un {token}. Classes page-scopees, et non l'echelle partagee
 * awc-badge-on/off : ici la quasi-totalite des lignes est neutre, et 300
 * pastilles pleines feraient un mur dans la colonne. Le neutre est donc une
 * pastille CLAIRE, seuls ok/warn/err sont pleins.
 *
 * Ne jamais utiliser « awc-badge » seul : cette classe ne porte que la forme et
 * color:#fff -- sans modificateur, c'est du blanc sur blanc, une colonne qui
 * semble vide.
 */
function ovpnev_css():string{
    if(isset($GLOBALS["OVPNEV_CSS"])){return "";}
    $GLOBALS["OVPNEV_CSS"]=true;
    return "<style>
.ovpnev-b{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.02em;
 text-transform:uppercase;padding:3px 7px;border-radius:4px;white-space:nowrap;}
.ovpnev-b-neutral{background:#e8ecec;color:#3d4947;}
.ovpnev-b-ok{background:#006a62;color:#fff;}
.ovpnev-b-warn{background:#865300;color:#fff;}
.ovpnev-b-err{background:#ba1a1a;color:#fff;}
.ovpnev-when{white-space:nowrap;color:#3d4947;font-size:13px;}
.ovpnev-pid{color:#6d7a77;font-size:13px;}
.ovpnev-msg{color:#111d23;font-size:13px;line-height:1.5;word-break:break-word;}
</style>";
}

/**
 * Classe UNE ligne d'evenement : rend array(libelle, severite, contenu).
 *
 * Remplace la cascade de str_replace() sur une chaine HTML que faisait le code
 * precedent (« label-default »->« label-warning », « >SERVER< »->« >Success< »,
 * huit passes se reecrivant l'une l'autre). Deux consequences de cette cascade
 * disparaissent ici :
 *   - elle ne remplacait que « >SERVER< », donc une ligne CLIENT ne recevait
 *     JAMAIS les libelles Success / Certificate / Reset / Restart ;
 *   - l'ordre des passes decidait silencieusement du gagnant.
 * Desormais le libelle specifique l'emporte, quel que soit le cote.
 */
function ovpnev_classify($isClient,$content):array{
    // Jetons, pas d'anglais en dur. La casse est portee par le CSS
    // (text-transform:uppercase), pas par le libelle -- une traduction longue
    // reste ainsi lisible. PLUGIN et MGR restent litteraux : c'est le
    // vocabulaire d'OpenVPN, identique dans toutes les langues.
    $label=$isClient?"{client}":"{server}";
    $sev="neutral";

    if(preg_match("#\[PLUGIN\]:(.+)#",$content,$re)){$label="PLUGIN";$content=$re[1];}
    if(preg_match("#MANAGEMENT:(.+)#",$content,$re)){$label="MGR";$content=$re[1];}
    if(preg_match("#WARNING:\s+(.+)#i",$content,$re)){$sev="warn";$content=$re[1];}
    if(preg_match("#process exiting#i",$content)){$sev="warn";}
    if(preg_match("#nitialization Sequence Completed#i",$content)){$sev="ok";$label="{success}";}
    if(strpos(" $content","VERIFY OK: depth")>0){$sev="ok";$label="{certificate}";}
    if(strpos(" $content","soft,connection-reset")>0){$sev="err";$label="{reset}";}
    if(strpos(" $content","restarting [")>0){$sev="err";$label="{restart}";}
    if(preg_match("#(fatal error|connection failed|Connection timed out)#i",$content)){$sev="err";}

    return array($label,$sev,trim($content));
}

function search(){
	$tpl=new template_admin();
	$sock=new sockets();
	$page=CurrentPageName();

	// Liste blanche OBLIGATOIRE : $function part brut dans les OnClick du TINY.
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$search=(string)(isset($_GET["search"])?$_GET["search"]:"");
	// Une recherche vide devient le motif « . » cote daemon, donc TOUT le
	// journal : c'est precisement le cas qui impose le plafond ci-dessous.
	if(strlen($search)<2){$search=".";}

	$EndPoint="/openvpn/service/grep/".base64_encode($search);
	$zmd5="";
	if(isset($_GET["zmd5"])){
		$zmd5=(string)$_GET["zmd5"];
		$EndPoint=sprintf("/openvpn/client/events/%s/%s",$zmd5,base64_encode($search));
	}

	$data=$sock->REST_API($EndPoint);
	$json=json_decode($data);
	if(json_last_error()>JSON_ERROR_NONE){
		echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>".json_last_error_msg());
		return false;
	}
	if(!is_object($json) || !isset($json->Status) || !$json->Status){
		echo $tpl->div_error(is_object($json)&&isset($json->Error)?$json->Error:"{error}");
		return false;
	}

	$logs=(isset($json->Logs) && is_array($json->Logs))?$json->Logs:array();
	$total=count($logs);
	// PLAFOND. La pagination navigateur ne tient que quelques centaines de
	// lignes : 1416 lignes rendent 3,6 Mo de fragment et Chrome repond
	// RESULT_CODE_HUNG (cf. CLAUDE.md, tableaux awc). On garde les PLUS
	// RECENTES -- la fin du journal -- parce que c'est ce qu'on vient lire.
	$LIMIT=300;
	if($total>$LIMIT){$logs=array_slice($logs,-$LIMIT);}

	$rows=array();
	foreach($logs as $line){
		$line=trim($line);
		if($line==null){continue;}
		$re=xPreg($line);
		if(count($re)<2){VERBOSE("$line NO MATCH",__LINE__);continue;}
		$date=trim(sprintf("%s %s %s",$re[1],$re[2],$re[3]));
		$pid=$re[5];
		list($label,$sev,$content)=ovpnev_classify($re[4]=="Client",$re[6]);

		// $date, $pid et $content viennent du JOURNAL, pas du moteur de
		// traduction : les echapper est correct ici, et necessaire -- un nom
		// commun de certificat porteur de balisage arriverait tel quel.
		// Pentest 2026-11-04 : ligne de journal OpenVPN (donnee externe) ; htmlspecialchars UTF-8 rendait "" sur du Latin-1 (XSS stockee).
		$r ="<tr>";
		$r.="<td class='ovpnev-when'><i class='fa fa-clock'></i>&nbsp;".req_html($date)."</td>";
		$r.="<td class='ovpnev-pid'>".req_html($pid)."</td>";
		$r.="<td class='center'><span class='ovpnev-b ovpnev-b-$sev'>$label</span></td>";
		$r.="<td class='ovpnev-msg'>".req_html($content)."</td>";
		$r.="</tr>";
		$rows[]=$r;
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
	$html[]="<th>{date}</th><th>PID</th><th class='center'>{type}</th><th style='width:99%'>{events}</th>";
	$html[]="</tr></thead><tbody id='ovpnev-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_item}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	// « affichees / trouvees » : sans ce compteur, un journal tronque a 300
	// lignes est indiscernable d'un journal qui en contient 300.
	$html[]="<span>{showing} ".count($rows)." / $total {items}</span>";
	$html[]="<span class='awc-pg' id='ovpnev-pager'></span>";
	$html[]="</div></div></div>";

	$topbuttons=array();
	$refresh="LoadAjaxSilent('table-loader','$page?main-start=yes".($zmd5!==""?"&zmd5=".urlencode($zmd5):"")."');";
	$topbuttons[]=array($refresh,ico_refresh,"{refresh}");
	$TINY=array();
	$TINY["TITLE"]="{APP_OPENVPN} &raquo; {events}";
	$TINY["ICO"]=ico_eye;
	$TINY["EXPL"]="{APP_OPENVPN_SEARCH_TEXT}";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

	// Le TINY part APRES le tableau, dans le meme <script> : les boutons
	// doivent exister dans le DOM avant l'animation d'icone.
	$js="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny</script>";

	// CSS et JS concatenes AUTOUR du parse, jamais dedans.
	echo ovpnev_css().awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n",$html))
	    .$js
	    .awc_pager_js(50,"ovpnev-rows","ovpnev-pager");
	return true;
}


