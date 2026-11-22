<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$users=new usersMenus();if(!$users->AsSquidAdministrator){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["HotSpotTemplateID"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["hotspot-web-status"])){status();exit;}
if(isset($_GET["wizard-js"])){wizard_js();exit;}
if(isset($_GET["wizard-popup"])){wizard_popup();exit;}
if(isset($_POST["iface_internet"])){wizard_save1();exit;}
if(isset($_GET["wizard-popup2"])){wizard_popup2();exit;}
if(isset($_POST["guest_network"])){wizard_save2();exit;}
page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $HOTSPOTWEB_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOTWEB_VERSION");
    if($HOTSPOTWEB_VERSION==null){$HOTSPOTWEB_VERSION="4.x";}

    $html=$tpl->page_header("{web_portal_authentication} v$HOTSPOTWEB_VERSION",
        "fas fa-tachometer-alt","{HotSpot_text}","$page?table=yes",
        "hotspot-status","progress-hotspot-restart",false,"table-hotspot");


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{hotspot_auth}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{general_settings}"]="$page?table=yes";
    $array["{networks}"]="fw.proxy.hotspot.network.php";
    $array["{active_directory_groups}"]="fw.proxy.hotspot.adgroups.php";
    $array["{skins}"]="fw.proxy.hotspot.textes.php";
    $array["{skins} WIFI4EU"]="fw.proxy.hotspot.wifi4eu.php";
    echo $tpl->tabs_default($array);
}
function table(){
    $page=CurrentPageName();
	$tpl=new template_admin();
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));

    // La colonne de vignettes etait une pile de <tr>/<td> dans un <table> de
    // MISE EN PAGE imbrique dans un autre. Cinq </td> manquaient (les vignettes
    // "plugin", "URI de redirection" et "authentification" fermaient un </tr>
    // sans fermer leur cellule), et la sortie anticipee du bloc Go_Shield
    // refermait le tableau interieur en laissant l'exterieur ouvert.
    $tiles=array();
    $tiles[]=hsp_tile_dhcp();
    $tiles[]=hsp_tile_dns();
    // Juste apres le cache DNS: c'est son prerequis, et les lire cote a cote
    // fait comprendre d'un coup pourquoi la detection reste jaune.
    $tiles[]=hsp_tile_captive_detect();
    $tiles[]=hsp_tile_transparent();

    // Le connecteur Shield absent coupe la chaine: les tuiles qui suivent
    // decrivent le portail lui-meme, qui ne peut rien servir sans lui. On
    // s'arrete donc ici -- mais en emettant le pied de page, ce que la sortie
    // anticipee d'origine oubliait (la page perdait titre et assistant).
    if($Go_Shield_Server_Enable==0){
        $Go_Shield_Connector_Addr=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Addr"));
        if(empty($Go_Shield_Connector_Addr)){$Go_Shield_Connector_Addr="127.0.0.1";}
        if($Go_Shield_Connector_Addr=="127.0.0.1"){
            $tiles[]=hsp_tile_shield();
        }
        echo hsp_style().$tpl->_ENGINE_parse_body(hotspot_status_panel($tiles));
        hotspot_status_footer($tpl,$page);
        return false;
    }

    // Pas de tuile « service web »: la vignette de gauche EST ce service, avec
    // son etat, son uptime, sa memoire et son bouton de redemarrage. La tuile
    // repetait l'etat et la version (deja dans le titre de page) pour n'ajouter
    // que les ports d'ecoute, repris par la tuile du portail. La supprimer
    // economise au passage un appel /hotspot/status par rendu de la grille --
    // la vignette le fait deja de son cote, toutes les 3 secondes.
    $tiles[]=hsp_tile_plugin();
    $tiles[]=hsp_tile_portal();
    $tiles[]=hsp_tile_auth();
    $tiles[]=hsp_tile_network();

    echo hsp_style().$tpl->_ENGINE_parse_body(hotspot_status_panel($tiles));

    hotspot_status_footer($tpl,$page);
	return false;


}

/**
 * Le panneau d'etat: la vignette de service a gauche, la grille de tuiles a
 * droite. Remplace deux <table> de mise en page imbriques.
 *
 * La maquette garde cette composition (une colonne « hero » etroite, la grille
 * sur le reste): la vignette de service porte le redemarrage, l'uptime et la
 * memoire, que les tuiles ne repetent pas.
 */
function hotspot_status_panel($tiles):string{
    $h ="<div class='ak hsp-panel'>";
    $h.="<div id='hotspot-web-status' class='hsp-hero'></div>";
    $h.="<div class='hsp-grid'>";
    $h.=@implode("\n",$tiles);
    $h.="</div></div>";
    return $h;
}

/* ------------------------------------------------------------------------- *
 *  Tuiles d'etat du portail captif, d'apres la maquette
 *  /home/claude/images/stitch_refonte_tuiles_statut_hotspot.zip
 *
 *  Composants PORTES A LA PAGE, prefixe hsp-. widget_h() et SERVICE_STATUS()
 *  restent le design PARTAGE de la console (la vignette de service de la
 *  colonne de gauche, et toutes les autres pages): cette page ne les redefinit
 *  pas, elle pose ses propres tuiles a cote.
 *
 *  Une fonction par tuile (regle « un widget = une fonction »).
 *
 *  Regle de contenu: le pied porte une donnee REELLE quand elle existe, sinon
 *  un lien vers la page qui configure ce composant. Les mesures de la maquette
 *  (« Leases: 36 active », « Cache Hit: 94.2% », « Workers: 4 active ») sont
 *  des exemples de mise en page, pas des donnees de cette appliance: les
 *  recopier ferait un tableau de bord qui ment.
 * ------------------------------------------------------------------------- */

/**
 * Les accents des tuiles suivent la marque posee sur l'appliance et retombent
 * sur l'emeraude de la maquette quand il n'y en a pas -- ce sont exactement les
 * valeurs par defaut de brand_ui_accents(), donc l'aspect ne bouge pas sur un
 * boitier de serie. class.brand.inc peut manquer la ou il n'est pas encore
 * deploye: garde function_exists et repli identique, comme table_form_css().
 */
function hsp_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

/**
 * La feuille de style des tuiles, emise UNE fois par requete.
 *
 * A concatener AUTOUR de _ENGINE_parse_body(), jamais dedans: sa regex
 * #{(.+?)}# prend chaque corps de regle CSS pour un {jeton} et declenche une
 * recherche memcached par regle.
 *
 * Pas de font-family: la maquette demande Inter, qu'aucune feuille de la
 * console ne charge -- une police absente serait remplacee au hasard du poste.
 */
function hsp_style():string{
    if(!empty($GLOBALS["HSP_CSS_DONE"])){return "";}
    $GLOBALS["HSP_CSS_DONE"]=true;
    $ac=hsp_accents();
    $A=$ac["accent"];$AD=$ac["accent_dark"];$AS=$ac["accent_soft"];$AL=$ac["accent_line"];
    return "<style>
/* Le composant declare son propre modele de boite: sans cela ses hauteurs
   dependraient du reset de la page hote. */
.hsp-panel{display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-top:10px}
.hsp-hero{flex:0 0 240px;max-width:100%}
/* CSS grid et non flex: grid-auto-rows:1fr egalise les rangees ENTRE elles, donc
   une tuile avec un pied sur deux lignes n'est pas plus haute que sa voisine. */
.hsp-grid{flex:1 1 420px;min-width:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:1fr;gap:16px}
.hsp-card{display:flex;flex-direction:column;padding:16px;transition:border-color .2s,box-shadow .2s}
.hsp-card:hover{border-color:$AL;box-shadow:0 4px 12px -2px rgba(15,23,42,.08)}
.hsp-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
.hsp-id{display:flex;align-items:center;gap:12px;min-width:0}
/* La taille de l'icone est portee par le CONTENEUR: Font Awesome tourne en mode
   SVG+JS, il remplace le <i> par un <svg> et un selecteur descendant « i » ne
   matcherait plus rien -- l'icone tomberait aux 13px du corps de page. */
.hsp-txt{min-width:0}
.hsp-lab{font-size:11px;font-weight:600;line-height:14px;letter-spacing:.05em;text-transform:uppercase;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.hsp-st{display:flex;align-items:center;gap:8px;margin-top:3px;min-width:0}
.hsp-val{font-size:16px;font-weight:700;line-height:22px;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.hsp-badge{flex:0 0 auto;max-width:45%;font-size:10px;font-weight:500;line-height:14px;padding:3px 8px;border-radius:4px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* Plaque en bas: les tuiles d'une rangee font la meme hauteur, donc sans cela un
   pied court laisserait un vide au MILIEU de la tuile au lieu du bas. */
.hsp-bot{margin-top:auto;padding-top:16px}
.hsp-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:12px;border-top:1px solid #f1f5f9;font-size:12px;line-height:16px;color:#64748b}
.hsp-fv{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.hsp-foot strong{color:#0f172a;font-weight:600}
.hsp-a{flex:0 0 auto;color:$AD!important;text-decoration:none!important;font-weight:500;white-space:nowrap;cursor:pointer}
.hsp-a:hover{text-decoration:underline!important}
/* Les tons. Le chip, la pastille et la valeur portent le meme etat: une tuile
   grise dont la pastille serait verte serait illisible. */
.hsp-err .hsp-val{color:#b91c1c}
.hsp-off .hsp-val{color:#94a3b8}
/* Compte impair: la derniere tuile prend la largeur entiere, plutot que de
   laisser une demi-rangee vide a cote d'elle (meme choix que le kit pxs-*). Sans
   effet quand la grille est deja sur une colonne. */
.hsp-grid>.hsp-card:last-child:nth-child(odd){grid-column:1 / -1}
/* Replis. La sequence n'est VOLONTAIREMENT pas monotone (mesure au pixel:
   2 colonnes >1180, 1 colonne de 1180 a 900, 2 de nouveau de 900 a 700, 1 en
   dessous): entre 1180 et 900 la vignette de service mange 240px et la grille
   n'a plus la place pour deux tuiles, alors que sous 900 elle passe pleine
   largeur au-dessus et rend cette place. Le critere est la largeur reelle de la
   GRILLE, pas celle de la fenetre. */
@media(max-width:1180px){.hsp-grid{grid-template-columns:minmax(0,1fr)}}
@media(max-width:900px){.hsp-panel{display:block}.hsp-hero{width:100%;margin-bottom:16px}
.hsp-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.hsp-grid{grid-template-columns:minmax(0,1fr)}}
</style>";
}

/**
 * Une tuile: pastille d'icone, intitule, etat, badge de meta, pied.
 *
 * $t: LABEL, STATE (le texte d'etat), TONE parmi on|warn|err|off, ICON, BADGE,
 * FOOT (la donnee reelle), LINK (array NAME/JS).
 */
function hsp_tile(array $t):string{
    $tone=isset($t["TONE"])?preg_replace('/[^a-z]/','',(string)$t["TONE"]):"off";
    if($tone==""){$tone="off";}
    $ico=isset($t["ICON"])?(string)$t["ICON"]:"fas fa-circle";
    $akt=array("on"=>"ok","warn"=>"warn","err"=>"err");
    $h ="<div class='ak-card hsp-card hsp-$tone ak-".(isset($akt[$tone])?$akt[$tone]:"off")."'>";
    $h.="<div class='hsp-head'><div class='hsp-id'>";
    $h.="<span class='ak-ico ak-ico-sq'><i class='$ico'></i></span>";
    $h.="<div class='hsp-txt'>";
    $h.="<div class='hsp-lab'>".$t["LABEL"]."</div>";
    $h.="<div class='hsp-st'><span class='hsp-val'>".$t["STATE"]."</span><span class='ak-dot'></span></div>";
    $h.="</div></div>";
    if(isset($t["BADGE"]) && trim((string)$t["BADGE"])!==""){
        $h.="<span class='hsp-badge'>".$t["BADGE"]."</span>";
    }
    $h.="</div>";
    $foot=isset($t["FOOT"])?(string)$t["FOOT"]:"";
    $link="";
    if(isset($t["LINK"]) && is_array($t["LINK"]) && isset($t["LINK"]["NAME"])){
        $ljs=isset($t["LINK"]["JS"])?$t["LINK"]["JS"]:"blur();";
        $link="<a class='hsp-a' href=\"javascript:blur();\" OnClick=\"$ljs\">"
             .$t["LINK"]["NAME"]."&nbsp;&rsaquo;</a>";
    }
    if($foot!=="" || $link!==""){
        $h.="<div class='hsp-bot'><div class='hsp-foot'><span class='hsp-fv'>$foot</span>$link</div></div>";
    }
    $h.="</div>";
    return $h;
}

/** Le lien de pied vers une autre page de la console. */
function hsp_goto($php):array{
    return array("NAME"=>"{configure}","JS"=>"LoadAjaxSilent('MainContent','$php');");
}

/**
 * Le suffixe CIDR d'un masque de sous-reseau: "255.255.255.0" -> "/24".
 *
 * La table dhcpd stocke le masque en notation longue. Tel quel dans le badge il
 * donnait « 192.168.1.0/255.255.255.0 » (mesure sur 192.168.1.248): 25
 * caracteres pour une information qui en vaut trois, et le badge mangeait la
 * ligne d'intitule.
 *
 * Rend "" plutot qu'un suffixe FAUX quand le masque est absent ou n'en est pas
 * un: un masque valide est une suite de 1 suivie d'une suite de 0, donc on
 * refuse 255.0.255.0 au lieu de repondre /16. Un badge qui n'affiche que le
 * reseau est correct; un badge qui affiche un prefixe invente ne l'est pas.
 */
function hsp_cidr_suffix($netmask):string{
    $netmask=trim((string)$netmask);
    if($netmask===""){return "";}
    // Deja en CIDR: certaines interfaces stockent directement "24".
    if(preg_match('/^\d{1,2}$/',$netmask)){return "/".intval($netmask);}
    $p=explode(".",$netmask);
    if(count($p)!=4){return "";}
    $bin="";
    foreach($p as $o){
        $o=trim($o);
        if(!preg_match('/^\d{1,3}$/',$o)){return "";}
        $v=intval($o);
        if($v>255){return "";}
        $bin.=str_pad(decbin($v),8,"0",STR_PAD_LEFT);
    }
    if(!preg_match('/^1*0*$/',$bin)){return "";}
    return "/".substr_count($bin,"1");
}

/** Tuile: le serveur DHCP qui adresse les clients du reseau invite. */
function hsp_tile_dhcp():string{
    $on=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"))==1;
    // La classe n'est pas dans la chaine auto-incluse par class.template-admin.inc.
    include_once(dirname(__FILE__)."/ressources/class.dhcpd-socks.inc");
    $badge="";$foot="";$link=hsp_goto("fw.dhcp.configuration.php");
    if($on){
        $d=new dhcp_socks();
        $subnet=trim($d->GET("subnet"));$netmask=trim($d->GET("netmask"));
        if($subnet!==""){$badge=htmlspecialchars($subnet.hsp_cidr_suffix($netmask));}
        // dhcpd-pools peut etre absent du boitier: le jeton reste vide et la
        // tuile retombe sur la plage configuree, puis sur le lien.
        $pools=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_POOLS_JSON"));
        $used=0;$defined=0;
        if(is_object($pools) && isset($pools->summary)){
            $used=intval($pools->summary->used ?? 0);
            $defined=intval($pools->summary->defined ?? 0);
        }
        if($defined>0){
            // Le jeton partage {leases} est en minuscules ("leases"), alors que
            // {pool} est capitalise: on le met donc APRES le nombre, ou la casse
            // n'a pas d'importance, plutot qu'en tete de phrase.
            $foot="<strong>$used</strong> / $defined {leases}";
            $link=array("NAME"=>"{configure}","JS"=>"LoadAjaxSilent('MainContent','fw.dhcp.status.php');");
        }else{
            $r1=trim($d->GET("range1"));$r2=trim($d->GET("range2"));
            if($r1!=="" && $r2!==""){$foot="{pool}: <strong>".htmlspecialchars("$r1 - $r2")."</strong>";}
        }
    }
    return hsp_tile(array(
        "LABEL"=>"{APP_DHCP}","ICON"=>ico_networks,
        "STATE"=>$on?"{active2}":"{inactive2}","TONE"=>$on?"on":"off",
        "BADGE"=>$badge,"FOOT"=>$foot,"LINK"=>$link));
}

/** Tuile: le cache DNS que les clients interrogent avant de s'authentifier. */
function hsp_tile_dns():string{
    $on=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"))==1;
    // Le port DNS n'est pas un reglage d'Artica (le generateur n'emet aucune
    // directive « port: »), donc pas de badge de port ici: la version, elle,
    // est ecrite par le service lui-meme.
    $ver=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion"));
    return hsp_tile(array(
        "LABEL"=>"{APP_UNBOUND}","ICON"=>ico_server,
        "STATE"=>$on?"{active2}":"{inactive2}","TONE"=>$on?"on":"off",
        "BADGE"=>$ver!==""?htmlspecialchars($ver):"",
        "FOOT"=>"","LINK"=>hsp_goto("fw.dns.unbound.php")));
}

/**
 * Tuile: la reponse automatique aux sondes de detection de portail captif.
 *
 * Android, iOS et Windows interrogent une URL connue en rejoignant un reseau;
 * y repondre est ce qui fait surgir la fenetre d'authentification toute seule.
 *
 * DEUX prerequis, et la tuile reste JAUNE tant que les deux ne sont pas reunis:
 * le cache DNS, qui detourne ces URLs vers le portail, et le reglage lui-meme.
 * Le second ne vaut rien sans le premier -- form_browsers_redirect_popup() de
 * fw.proxy.hotspot.php refuse d'ailleurs d'afficher la case a cocher quand
 * unbound est absent.
 *
 * Jaune et non rouge: rien n'est casse, le portail reste joignable pour qui
 * ouvre un navigateur de lui-meme. C'est l'ouverture automatique qui manque.
 */
function hsp_tile_captive_detect():string{
    $dns=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"))==1;
    $redirects=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHotSpotBrowsersRedirects"))==1;
    list($tone,$badge)=hsp_captive_state($dns,$redirects);
    return hsp_tile(array(
        "LABEL"=>"{CaptivePortalDetectionURLs}","ICON"=>"fas fa-compass",
        "STATE"=>$tone==="on"?"{active2}":"{not_configured}","TONE"=>$tone,
        "BADGE"=>$badge,"FOOT"=>"","LINK"=>hsp_goto("fw.proxy.hotspot.php")));
}

/**
 * Le ton et le badge de la tuile precedente, isoles pour etre verifiables.
 *
 * Le badge nomme LEQUEL des deux prerequis bloque: « non configure » sans dire
 * quoi oblige a ouvrir la page pour le decouvrir. Le cache DNS passe en
 * premier quand les deux manquent -- c'est le prerequis de fond, le reglage
 * n'est meme pas proposable sans lui, donc l'annoncer en premier evite
 * d'envoyer activer une case qui n'existe pas encore.
 *
 * @return array array($tone, $badge)
 */
function hsp_captive_state($dns,$redirects):array{
    if($dns && $redirects){return array("on","");}
    if(!$dns){return array("warn","{missing} {APP_UNBOUND}");}
    return array("warn","{disabled}");
}

/** Tuile: l'interception transparente, sans laquelle rien n'atteint le portail. */
function hsp_tile_transparent():string{
    list($http,$https,$ports,$sports)=TransparentStatus();
    $tone=hsp_transparent_tone($http,$https);

    // L'etat suit le TON, pas le compte de protocoles. En avertissement le HTTP
    // passe et le portail repond: annoncer « non configure » serait faux, et
    // enverrait reconfigurer une interception qui marche. Le badge porte alors
    // ce qui manque, l'ambre porte le fait qu'il manque quelque chose.
    $state="{active2}";$badge="HTTP / HTTPS";
    if($tone==="warn"){
        $badge="{missing} SSL";
    }
    if($tone==="err"){
        $state="{not_configured}";
        $miss=array();
        if($http==0){$miss[]="HTTP";}
        if($https==0){$miss[]="SSL";}
        $badge="{missing} ".@implode(" / ",$miss);
    }
    $all=array_merge($ports,$sports);
    sort($all,SORT_NUMERIC);
    $foot="";
    if(count($all)>0){$foot="{listen_ports}: <strong>".htmlspecialchars(@implode(", ",$all))."</strong>";}
    return hsp_tile(array(
        "LABEL"=>"{transparent}","ICON"=>ico_firewall,
        "STATE"=>$state,"TONE"=>$tone,"BADGE"=>$badge,
        "FOOT"=>$foot,"LINK"=>hsp_goto("fw.proxy.transparent.php")));
}

/** Tuile: le connecteur Shield absent, qui coupe la chaine en amont du portail. */
function hsp_tile_shield():string{
    return hsp_tile(array(
        "LABEL"=>"{KSRN_SERVER2}","ICON"=>"far fa-times-circle",
        "STATE"=>"{disabled}","TONE"=>"err","BADGE"=>"",
        "FOOT"=>"","LINK"=>hsp_goto("fw.proxy.status.php")));
}

/** Tuile: le raccordement du proxy au portail. */
function hsp_tile_plugin():string{
    $on=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotRedirectUI"))!=="";
    $iface=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBindInterface"));
    return hsp_tile(array(
        "LABEL"=>"{artica_proxy_plugin}","ICON"=>$on?"fas fa-link":"fad fa-unlink",
        "STATE"=>$on?"{connected}":"{disconnected}","TONE"=>$on?"on":"err",
        "BADGE"=>$iface!==""?htmlspecialchars($iface):"",
        "FOOT"=>"","LINK"=>hsp_goto("fw.proxy.hotspot.php")));
}

/** Tuile: l'adresse publique de la page d'authentification. */
function hsp_tile_portal():string{
    $url=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotRedirectUI"));
    $on=($url!=="");

    // Le badge porte les PORTS D'ECOUTE, la donnee recuperee de la tuile
    // « service web » supprimee. Ils ont leur place ici: c'est la tuile du
    // portail lui-meme, et le port SSL n'apparait nulle part ailleurs --
    // l'URL, elle, ne montre que le port en clair.
    //
    // Ils remplacent le badge « SSL », qui etait redondant avec le pied: l'URL
    // y est affichee en entier, https:// compris.
    $listen=hsp_listen_ports();
    $badge=@implode(" / ",$listen);

    $foot=$on?("<strong>".htmlspecialchars($url)."</strong>"):"{error_no_redirect_uri}";

    return hsp_tile(array(
        "LABEL"=>"{HotSpotRedirectUI}","ICON"=>"fas fa-external-link",
        "STATE"=>$on?"{defined}":"{not_defined}","TONE"=>$on?"on":"err",
        "BADGE"=>$badge,"FOOT"=>$foot,"LINK"=>hsp_goto("fw.proxy.hotspot.php")));
}

/**
 * Les ports sur lesquels le portail ecoute: le port en clair, puis le port SSL
 * quand il est actif.
 *
 * 8025 est le defaut du daemon (HotSpot/Wizard.go) quand le reglage vaut 0: le
 * reprendre ici evite d'afficher « port 0 » sur une appliance ou personne n'a
 * jamais touche au champ, alors que le portail ecoute bel et bien.
 */
function hsp_listen_ports():array{
    $port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenPort"));
    if($port==0){$port=8025;}
    $listen=array($port);
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenEnableSSL"))==1){
        $sslport=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenSSLPort"));
        if($sslport>0){$listen[]=$sslport;}
    }
    return $listen;
}

/** Tuile: les methodes par lesquelles un visiteur peut ouvrir une session. */
function hsp_tile_auth():string{
    $on=$GLOBALS["CLASS_SOCKETS"]->HOTSPOT_IS_AUTH_EXISTS();
    $methods=hsp_auth_methods();
    $sessions=hsp_sessions_count();
    $foot="";$link=hsp_goto("fw.proxy.hotspot.php");
    if($sessions>=0){
        $foot="{active_sessions}: <strong>$sessions</strong>";
        $link=array("NAME"=>"{sessions}","JS"=>"LoadAjaxSilent('MainContent','fw.proxy.hotspot.sessions.php');");
    }
    return hsp_tile(array(
        "LABEL"=>"{authentication}","ICON"=>"fad fa-user-check",
        "STATE"=>$on?"{defined}":"{no_auth_defined}","TONE"=>$on?"on":"err",
        "BADGE"=>@implode(" / ",$methods),"FOOT"=>$foot,"LINK"=>$link));
}

/** Tuile: les interfaces et le reseau que l'assistant a poses. */
function hsp_tile_network():string{
    $w=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOT_WIZARD"));
    $guest="";$inet="";$net="";
    if(is_object($w)){
        $guest=trim((string)($w->iface_guest ?? ""));
        $inet=trim((string)($w->iface_internet ?? ""));
        $n=trim((string)($w->guest_network ?? ""));
        $m=trim((string)($w->guest_netmask ?? ""));
        if($n!=="" && $m!==""){$net="$n/$m";}
    }
    $on=($guest!=="");
    $foot="";
    if($on){
        // La fleche est une ENTITE: elle se concatene apres l'echappement, jamais
        // avant -- htmlspecialchars() sur "&rarr;" rendrait "&amp;rarr;", donc la
        // fleche s'afficherait en toutes lettres.
        $foot=htmlspecialchars($guest);
        if($inet!=="" && $inet!==$guest){
            $foot=htmlspecialchars($guest)."&nbsp;&rarr;&nbsp;".htmlspecialchars($inet);
        }
        $foot="<strong>$foot</strong>";
    }
    return hsp_tile(array(
        "LABEL"=>"{interfaces}","ICON"=>"fas fa-network-wired",
        "STATE"=>$on?"{configured}":"{not_configured}","TONE"=>$on?"on":"off",
        "BADGE"=>$net!==""?htmlspecialchars($net):"",
        "FOOT"=>$foot,
        "LINK"=>array("NAME"=>"{hotspotwizard}","JS"=>"Loadjs('".CurrentPageName()."?wizard-js=yes');")));
}

/**
 * Les methodes d'authentification actives, pour le badge de la tuile.
 *
 * Les memes gardes que HOTSPOT_IS_AUTH_EXISTS() (class.sockets.inc:1919):
 * l'annuaire AD ne compte pas sans la fonctionnalite ni la licence Corporate,
 * le LDAP local pas sans OpenLDAP. Sans elles, le badge annoncerait une methode
 * que l'etat de la tuile, lui, ne reconnait pas.
 */
function hsp_auth_methods():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $out=array();
    if(intval($s->GET_INFO("HotSpotAuthentAD"))==1){
        if(intval($s->GET_INFO("EnableActiveDirectoryFeature"))==1 && $s->CORP_LICENSE()){
            $out[]="{active_directory}";
        }
    }
    if(intval($s->GET_INFO("HotSpotAuthentLocalLDAP"))==1){
        if(intval($s->GET_INFO("EnableOpenLDAP"))==1){$out[]="LDAP";}
    }
    if(intval($s->GET_INFO("HotSpotAuthentVoucher"))==1){$out[]="{vouchers_rooms}";}
    if(intval($s->GET_INFO("HotSpotAutoLogin"))==1){$out[]="{self_register}";}
    if(intval($s->GET_INFO("HotSpotWIFI4EU_ENABLE"))==1){$out[]="WIFI4EU";}
    return $out;
}

/**
 * Le nombre de sessions ouvertes, ou -1 quand la base du portail n'existe pas
 * encore (elle est creee au premier demarrage du service). -1 et non 0: « zero
 * session » et « le portail n'a jamais tourne » ne se disent pas pareil.
 */
function hsp_sessions_count():int{
    if(!is_file("/home/squid/hotspot/database.db")){return -1;}
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM sessions WHERE enabled=1");
    if(!$q->ok){return -1;}
    return intval($ligne["tcount"] ?? 0);
}

/**
 * Boutons d'en-tete (TINY) et rafraichissement periodique de la vignette de
 * service. Etait duplique entre la sortie anticipee et la sortie normale de
 * table() -- en realite la sortie anticipee ne l'emettait PAS, donc quand le
 * connecteur Shield etait absent la page perdait son titre et son assistant.
 */
function hotspot_status_footer($tpl,$page){
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('fw.proxy.hotspot.status.php?wizard-js=yes');", ico_wizard, "{hotspotwizard}");
    // Rebuilds what an existing hotspot keeps from the previous version:
    // unbound (captive-portal probes), the proxy rules for clients not logged
    // in yet, and the portal service. Runs in the daemon; the bar follows
    // hotspot-reconfigure.progress.
    $jsreconfigure=$tpl->framework_buildjs("/hotspot/reconfigure-all",
        "hotspot-reconfigure.progress","hotspot-reconfigure.progress.log","progress-hotspot-restart",
        "LoadAjax('hotspot-web-status','$page?hotspot-web-status=yes');",null,
        "{hotspot_reconfigure_all_confirm_api}","AsSquidAdministrator");
    $topbuttons[] = array($jsreconfigure, ico_refresh, "{hotspot_reconfigure_all}");

    $HOTSPOTWEB_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOTWEB_VERSION");
    if($HOTSPOTWEB_VERSION==null){$HOTSPOTWEB_VERSION="4.x";}

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{web_portal_authentication} v$HOTSPOTWEB_VERSION";
    $TINY_ARRAY["ICO"]="fas fa-tachometer-alt";
    $TINY_ARRAY["EXPL"]="{HotSpot_text}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $status=$tpl->RefreshInterval_js("hotspot-web-status",$page,"hotspot-web-status=yes");

    // Bloc de script NON analyse: la regex #{(.+?)}# de _ENGINE_parse_body
    // prendrait chaque corps d'accolade JavaScript pour un {token}.
    echo "<script>NoSpinner();\n$jstiny\n$status</script>";
}
function status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API('/hotspot/status'));

    // Les widget_* rendent DEJA du HTML analyse et portent leur propre feuille
    // de style (widget_engine: svctile_css().parse(...)). Les repasser dans
    // _ENGINE_parse_body ferait lire chaque regle CSS comme un {jeton}, soit une
    // recherche memcached par regle. On les echo donc tels quels.
    if(!is_object($json) || !isset($json->Status)){
        echo $tpl->widget_rouge("{protocol_error}","{error}");
        return false;
    }

    if(!$json->Status){
        // Error peut manquer: en PHP 8 y acceder leve un warning et rend null,
        // donc une vignette rouge sans aucun libelle.
        $err=property_exists($json,"Error")?trim((string)$json->Error):"";
        if($err===""){$err="{protocol_error}";}
        echo $tpl->widget_rouge($err,"{error}");
        return false;
    }

    // Info peut manquer alors que Status vaut true: loadString(null) laisserait
    // un INI vide, donc sans section, donc le cas traite juste en dessous.
    $info=property_exists($json,"Info")?(string)$json->Info:"";
    $ini=new Bs_IniHandler();
    $ini->loadString($info);

    // SERVICE_STATUS() rend SA FEUILLE DE STYLE ET RIEN D'AUTRE quand la section
    // demandee manque (class.template-admin.inc:14483, « return $svccss »). Le
    // div se remplit alors de ~15 Ko de CSS et reste VISUELLEMENT VIDE: c'est
    // indiscernable d'un rafraichissement qui ne serait jamais parti, et on
    // accuse le daemon alors qu'il a repondu. On tranche donc ici, tant qu'on
    // sait encore pourquoi.
    //
    // Jaune et non rouge: le daemon a repondu, il n'avait simplement pas d'etat
    // pour ce service (INI vide, service jamais installe, cache en cours de
    // reecriture). « Je ne sais pas » n'est pas « c'est en panne ».
    if(!isset($ini->_params["HOTSPOT_WEB"])){
        echo $tpl->widget_jaune("{APP_HOTSPOT}","{no_data}");
        return false;
    }

    $jrestart=$tpl->framework_buildjs("/hotspot/restart",
        "hotspot-web.progress","hotspot.log","progress-hotspot-restart",
        "LoadAjax('hotspot-web-status','$page?hotspot-web-status=yes');",
        "LoadAjax('hotspot-web-status','$page?hotspot-web-status=yes');");

   echo $tpl->SERVICE_STATUS($ini, "HOTSPOT_WEB",$jrestart);
   return true;
}
function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $EnableSquidMicroHotSpot=intval($_POST["EnableSquidMicroHotSpot"]);
	if($_POST["HotSpotWIFI4EU_ENABLE"]==1){
        $_POST["HotSpotEntrepriseTemplate"]=0;
        $_POST["HotSpotAutoLogin"]=0;
        $_POST["HotSpotAuthentAD"]=0;
        $_POST["HotSpotAuthentLocalLDAP"]=0;
        $_POST["HotSpotAuthentVoucher"]=0;

    }
	if($_POST["HotSpotRedirectUI2"]<>null){
		$_POST["HotSpotRedirectUI"]=$_POST["HotSpotRedirectUI2"];
		unset($_POST["HotSpotRedirectUI2"]);
	}

    if($EnableSquidMicroHotSpot==1) {
        if ($_POST["HotSpotRedirectUI"] == null) {
            echo $tpl->post_error("{you_need_uri_redirection}");
            return false;

        }
    }


	$tpl->SAVE_POSTs();
	
	
	
}
function wizard_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog4("{START_WIZARD}","$page?wizard-popup=yes",850);
}
function  wizard_popup():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOT_WIZARD"));
    if(!$json){
        $array["iface_internet"]="";
        $array["iface_guest"]="";
        $array["guest_network"]="";
        $array["guest_netmask"]="";
        $tmp=json_encode($array);
        $json=json_decode($tmp);
    }

    $html[]="<div id='hotspot-wizard-progress'>".$tpl->div_explain("{APP_HOTSPOT}||{hotspot_wizardv2}")."</div>";
    $html[]="<div id='hotspot-wizard-div'>";
    $form[]=$tpl->field_interfaces("iface_internet","{listen_interface} (Internet)",$json->iface_internet);
    $form[]=$tpl->field_interfaces("iface_guest","{listen_interface} ({guest_network})",$json->iface_guest);
    $html[]=$tpl->form_outside("",$form,"","{next}","LoadAjax('hotspot-wizard-div','$page?wizard-popup2=yes');");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function wizard_save1():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOT_WIZARD"));
    if(!$json){
        $array["iface_internet"]="";
        $array["iface_guest"]="";
        $array["guest_network"]="";
        $array["guest_netmask"]="";
        $tmp=json_encode($array);
        $json=json_decode($tmp);
    }
    if($_POST["iface_guest"]==null){
        $_POST["iface_guest"]=$_POST["iface_internet"];
    }
    if($_POST["iface_internet"]==null){
        $_POST["iface_internet"]=$_POST["iface_guest"];
    }
    $json->iface_internet=$_POST["iface_internet"];
    $json->iface_guest=$_POST["iface_guest"];
    $tmp=json_encode($json);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HOTSPOT_WIZARD",$tmp);
    return admin_tracks("Saving HotSpot wizard inet OUT=$json->iface_internet IN=$json->iface_guest");
}

function wizard_popup2():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOT_WIZARD"));
    $iface=new system_nic($json->iface_guest);
    if($json->guest_network==null){
        $guest_netmask=$iface->GetCDIRNetwork($iface->IPADDR,$iface->NETMASK);
        $tr=explode("/",$guest_netmask);
        $json->guest_network=$tr[0];
        $json->guest_netmask=$tr[1];
    }

        $jsafter=$tpl->framework_buildjs("/proxy/hotspot/wizard","hotspot-wizard.progress","hostspot.wizard.log","hotspot-wizard-progress","document.location.href='/hotspot-config'");

    $form[]=$tpl->field_ipv4("guest_network","{from_ip}",$json->guest_network);
    $form[]=$tpl->field_numeric("guest_netmask","{netmask}",$json->guest_netmask);
    $html[]=$tpl->form_outside("{guest_network}",$form,"","{next}",$jsafter);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function wizard_save2():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOT_WIZARD"));
    $json->guest_network=$_POST["guest_network"];
    $json->guest_netmask=$_POST["guest_netmask"];
    $tmp=json_encode($json);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HOTSPOT_WIZARD",$tmp);
    return admin_tracks("Saving HotSpot wizard Guest =$json->guest_network/$json->guest_netmask");
}

/**
 * L'etat de l'interception transparente: HTTP present, HTTPS present, et les
 * DEUX listes de ports.
 *
 * Les listes etaient deja construites puis jetees -- la fonction ne rendait que
 * deux booleens, alors que la tuile a besoin des ports eux-memes.
 *
 * hiddenID=0 exclut les lignes internes, comme le fait le daemon
 * (squid/proxy_transparent_crud.go:73): sans ce filtre la page comptait des
 * ports qu'aucune interface n'expose.
 */
function TransparentStatus():array{
    $EnableSquidTransparent=0;
    $EnableSquidTransparentSSL=0;
    $sport=array();
    $port=array();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="SELECT port,sslcertificate FROM transparent_ports WHERE enabled=1 AND hiddenID=0";
    $results = $q->QUERY_SQL($sql);
    if(is_array($results)){
        foreach($results as $index=>$ligne){
            $sslcertificate=$ligne["sslcertificate"];

            if(strlen($sslcertificate)>1){
                $sport[]=intval($ligne["port"]);
                continue;
            }
            $port[]=intval($ligne["port"]);
        }
    }
    if(count($port)>0){$EnableSquidTransparent=1;}
    if(count($sport)>0){$EnableSquidTransparentSSL=1;}

    return array($EnableSquidTransparent,$EnableSquidTransparentSSL,$port,$sport);


}

/**
 * Le ton de la tuile « interception transparente » selon ce qui est en place.
 *
 * La regle n'est PAS « combien de protocoles sont interceptes »: c'est
 * l'interception HTTP qui est redhibitoire. C'est par elle que la redirection
 * vers le portail atteint le navigateur -- sans elle personne n'arrive sur la
 * page d'authentification, que le HTTPS soit intercepte ou non. Donc HTTPS seul
 * vaut erreur, exactement comme rien du tout; seule l'absence de SSL alors que
 * le HTTP passe est un avertissement (le portail repond, mais les visiteurs
 * dont le navigateur ne demande que du HTTPS restent muets).
 *
 * Ecrit sur $http d'abord, pas en enumerant les quatre combinaisons: la raison
 * du rouge se lit alors dans le code, et ajouter un protocole plus tard ne
 * demande pas de reprendre une table de verite.
 *
 * @param int $http  1 si au moins un port HTTP est intercepte
 * @param int $https 1 si au moins un port HTTPS est intercepte
 * @return string on|warn|err|off
 */
function hsp_transparent_tone($http,$https):string{
    if($http==0){return "err";}
    if($https==0){return "warn";}
    return "on";
}