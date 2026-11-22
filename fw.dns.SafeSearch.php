<?php
//Artica Service Pack 8 - Sep 2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// Cas id=unbound : groupe safesearch de PUT /unbound/api/settings (spec 019 lot 4c).
include_once(dirname(__FILE__)."/ressources/class.unbound.api.inc");
include_once(dirname(__FILE__)."/ressources/class.unbound.settings.inc");
// Verrou d'une politique Meta dnscache : moteurs tenus en lecture seule, refus cote serveur.
include_once(dirname(__FILE__)."/ressources/class.unbound.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table-flat"])){table_flat();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_POST["safesearch-toggle"])){toggle_save();exit;}
if(isset($_POST["safesearch-apply"])){apply_now();exit;}
if(isset($_GET["api-notices-js"])){api_notices_js();exit;}
page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("SafeSearch(s)","fas fa-filter","{safesearch_page_explain}",
        "$page?table-start=yes","safesearch","progress-unbound-restart",false,"table-loader-safesearch");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);

}

/**
 * Catalogue des moteurs.
 *
 * `domain` est la cible REELLE de la reecriture DNS posee par le daemon
 * (unbound/Configurator.go::SafeSearchs, dnscache/dnscache.go::SafeSearchs) --
 * pas une valeur d'illustration. L'afficher sous le nom du moteur repond a la
 * seule question qu'un administrateur DNS se pose devant cette page: vers quoi
 * la requete part-elle une fois l'option active.
 */
function safesearch_engines():array{
    $e=array();
    $e[]=array("key"=>"EnableGoogleSafeSearch","name"=>"Google SafeSearch",
        "domain"=>"forcesafesearch.google.com","icon"=>"fab fa-google","color"=>"#4285f4",
        "desc"=>"{safesearch_google_desc}");
    $e[]=array("key"=>"EnableQwantSafeSearch","name"=>"Qwant SafeSearch",
        "domain"=>"safeapi.qwant.com","icon"=>"fas fa-magnifying-glass","color"=>"#f9645f",
        "desc"=>"{safesearch_qwant_desc}");
    $e[]=array("key"=>"EnableBraveSafeSearch","name"=>"Brave SafeSearch",
        "domain"=>"safesearch.brave.com","icon"=>"fab fa-brave","color"=>"#fb542b",
        "desc"=>"{safesearch_brave_desc}");
    $e[]=array("key"=>"EnableBingSafeSearch","name"=>"Bing SafeSearch",
        "domain"=>"strict.bing.com","icon"=>"fab fa-microsoft","color"=>"#0b7ec4",
        "desc"=>"{safesearch_bing_desc}");
    $e[]=array("key"=>"EnableYoutubeSafeSearch","name"=>"Youtube (strict)",
        "domain"=>"restrict.youtube.com","icon"=>"fab fa-youtube","color"=>"#e11d48",
        "desc"=>"{safesearch_youtube_strict_desc}","note"=>"{safesearch_youtube_exclusive}");
    $e[]=array("key"=>"EnbaleYoutubeModerate","name"=>"Youtube (moderate)",
        "domain"=>"restrictmoderate.youtube.com","icon"=>"fab fa-youtube","color"=>"#d97706",
        "desc"=>"{safesearch_youtube_moderate_desc}","note"=>"{safesearch_youtube_exclusive}");
    $e[]=array("key"=>"EnableDuckduckgoSafeSearch","name"=>"Duckduckgo SafeSearch",
        "domain"=>"safe.duckduckgo.com","icon"=>"fas fa-duck","color"=>"#de5833",
        "desc"=>"{safesearch_duckduckgo_desc}");
    $e[]=array("key"=>"EnableYandexSafeSearch","name"=>"Yandex SafeSearch",
        "domain"=>"familysearch.yandex.ru","icon"=>"fab fa-yandex","color"=>"#fc3f1d",
        "desc"=>"{safesearch_yandex_desc}");
    $e[]=array("key"=>"EnablePixabaySafeSearch","name"=>"Pixabay SafeSearch",
        "domain"=>"safesearch.pixabay.com","icon"=>"fas fa-images","color"=>"#2ec66d",
        "desc"=>"{safesearch_pixabay_desc}");
    return $e;
}

/**
 * Moteurs qui s'excluent mutuellement.
 *
 * Le daemon ne peut poser qu'un seul CNAME pour youtube: si les deux options
 * valent 1, `restrictmoderate` ecrase `restrict` (unbound/Configurator.go).
 * L'ancienne page laissait cocher les deux -- l'administrateur croyait activer
 * le mode strict et obtenait le mode modere, sans rien pour le lui dire.
 * Activer l'un eteint donc l'autre, cote serveur ET a l'ecran.
 */
function safesearch_exclusive():array{
    return array(
        "EnableYoutubeSafeSearch"=>"EnbaleYoutubeModerate",
        "EnbaleYoutubeModerate"=>"EnableYoutubeSafeSearch");
}

/**
 * Le resolveur qui applique reellement ces reecritures, et la route qui le
 * regenere.
 *
 * Les memes cles sont lues par trois moteurs (unbound, le micro-cache local,
 * dnsdist), mais la page n'est proposee que hors dnsdist (fw-left-menus-dns.php).
 * L'ancienne version n'appelait que /unbound/reconfigure: sur une appliance
 * servie par le micro-cache local, cocher une case n'atteignait jamais le
 * resolveur. La detection du micro-cache reprend l'idiome deja en place dans
 * fw.dns.servers.php (cle Valkey + fichier temoin).
 */
function safesearch_resolver():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    if(intval($sock->GET_INFO("UnboundEnabled"))==1){
        return array("id"=>"unbound","label"=>"Unbound","route"=>"/unbound/reconfigure");
    }
    $DoNotUseLocalDNSCache=intval($sock->GET_INFO("DoNotUseLocalDNSCache"));
    if(is_file("/etc/artica-postfix/DoNotUseLocalDNSCache")){$DoNotUseLocalDNSCache=1;}
    if($DoNotUseLocalDNSCache==0){
        return array("id"=>"dnscache","label"=>"{safesearch_local_dns_cache}","route"=>"/dnscache/restart");
    }
    return array("id"=>"","label"=>"","route"=>"");
}

/**
 * Les deux appelants n'ecrivent pas la meme chose: fw.hacluster.status.php
 * envoie `byHacluster=yes`, table_start() reemet `byHacluster=1`. Un
 * intval()==1 rendrait donc faux sur l'onglet du cluster -- la grille
 * repartirait en mode normal ET emettrait un TINY qui vole l'en-tete de la
 * page HaCluster. Seule la presence compte, "0" mis a part.
 */
function safesearch_hacluster():bool{
    if(!isset($_GET["byHacluster"])){return false;}
    $v=trim((string)$_GET["byHacluster"]);
    return ($v!=="" && $v!=="0");
}

/**
 * La raison pour laquelle la grille est verrouillee, ou "" si elle ne l'est
 * pas. La plus bloquante d'abord.
 *
 * UNE seule source pour l'AFFICHAGE et pour le REFUS cote serveur. Deux
 * fonctions distinctes derivent toujours dans le meme sens: l'affichage se
 * durcit et le handler reste ouvert -- exactement le motif « masquer un
 * controle n'est pas un controle de droits ».
 */
function safesearch_lock($ha):string{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    if(!$sock->CORP_LICENSE()){return "{no_license}";}
    if($ha){
        $HaClusterProxyUseBackendDNSDIST=intval($sock->GET_INFO("HaClusterProxyUseBackendDNSDIST"));
        $HaClusterUseLocalDNSCache=intval($sock->GET_INFO("HaClusterUseLocalDNSCache"));
        if($HaClusterProxyUseBackendDNSDIST!=1 && $HaClusterUseLocalDNSCache!=1){
            return "{hacluster_no_feature_nodns}";
        }
    }
    return "";
}

/**
 * Le mode embarque tel que les handlers POST le recoivent (le JS envoie
 * toujours 1 ou 0, contrairement aux appelants GET -- cf. safesearch_hacluster).
 */
function safesearch_hacluster_post():bool{
    return (isset($_POST["byHacluster"]) && intval($_POST["byHacluster"])==1);
}

/**
 * Le garde cote serveur des deux handlers qui ecrivent.
 *
 * Masquer une tuile n'est pas un controle de droits: le bloc de dispatch
 * repond a toute session authentifiee. Renvoie le message d'erreur a echoer,
 * ou "" quand l'appel est legitime.
 */
function safesearch_denied($ha):string{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsDnsAdministrator){return $tpl->post_error("{ERROR_NO_PRIVS}");}
    $lock=safesearch_lock($ha);
    if($lock!==""){return $tpl->post_error($lock);}
    return "";
}

function table_start(){
    $page=CurrentPageName();
    $addon="";
    if(safesearch_hacluster()){
        $addon="&byHacluster=1";
    }
    echo "<div id='safesearch-table'></div><script>LoadAjax('safesearch-table','$page?table-flat=yes$addon')</script>";
}

function table_flat(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $ha=safesearch_hacluster();

    // Meme verrou que celui applique par les handlers d'ecriture.
    $locked=safesearch_lock($ha);

    $engines=safesearch_engines();
    $values=array();
    foreach($engines as $e){
        $v=intval($sock->GET_INFO($e["key"]));
        // Sans licence Corp le daemon force tout a 0 (SafeSearchs() abandonne):
        // afficher les valeurs stockees ferait croire a une protection active.
        if($locked!==""){$v=0;}
        $values[$e["key"]]=$v;
    }

    $resolver=array("id"=>"","label"=>"","route"=>"");
    if(!$ha){$resolver=safesearch_resolver();}

    $html=array();
    $keys=array();
    foreach($engines as $e){$keys[]=$e["key"];}
    $html[]="<div class='ssx ak'>";
    $metaBanner=unbound_meta_banner($tpl,$keys);
    if($metaBanner!==""){$html[]=$metaBanner;}
    $html[]=ssx_hint_bar($resolver,$ha,$locked);
    $html[]="<div class='ssx-grid'>";
    foreach($engines as $e){
        // Moteur tenu par une politique Meta : tuile verrouillee, valeur reelle montree.
        $html[]=ssx_tile($e,$values[$e["key"]],$locked!=="" || unbound_meta_key_locked($e["key"]));
    }
    $html[]="</div>";
    $html[]="</div>";

    // CSS et JS sont concatenes AUTOUR du parse: leurs blocs {...} seraient
    // lus comme des {tokens} par _ENGINE_parse_body.
    $js=ssx_js($page,$ha,$resolver,$locked!=="");
    if(!$ha){$js.=ssx_tiny($page);}
    echo ssx_css().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>$js</script>";
}

/**
 * Le bandeau au-dessus de la grille: l'astuce d'interaction a gauche, le
 * resolveur qui applique les regles a droite.
 *
 * Nommer ce resolveur n'est pas decoratif. Quand aucun service DNS ne tourne,
 * les bascules sont enregistrees mais rien ne les applique -- l'ancienne page
 * ne le disait nulle part, et une option cochee passait pour une protection en
 * place.
 */
function ssx_hint_bar($resolver,$ha,$locked):string{
    $h=array();
    $h[]="<div class='ssx-hint'>";
    // Verrouillee, la grille ne repond a aucun clic: l'astuce qui invite a en
    // faire un n'a plus rien a decrire. Un espaceur garde la mention de droite
    // a sa place.
    if($locked===""){
        $h[]="<span class='ssx-tip'><i class='fas fa-circle-info'></i>";
        $h[]="<span><b>{tip}</b> {safesearch_tile_tip}</span></span>";
    }else{
        $h[]="<span class='ssx-tip'></span>";
    }

    if($locked!==""){
        $h[]="<span class='ssx-engine ssx-engine-ko'><i class='fas fa-lock'></i> $locked</span>";
    }elseif($ha){
        $h[]="<span class='ssx-engine'><i class='fas fa-sitemap'></i> {safesearch_rules_applied_by} HaCluster</span>";
    }elseif($resolver["route"]===""){
        $h[]="<span class='ssx-engine ssx-engine-ko'><i class='fas fa-triangle-exclamation'></i> {safesearch_no_resolver}</span>";
    }else{
        $h[]="<span class='ssx-engine'><i class='fas fa-server'></i> {safesearch_rules_applied_by} ".$resolver["label"]."</span>";
    }
    $h[]="<span class='ssx-status' id='ssx-status'></span>";
    $h[]="</div>";
    return @implode("\n",$h);
}

/**
 * Une tuile = un moteur.
 *
 * Les deux libelles d'etat sont rendus tous les deux et c'est la classe
 * ssx-on/ssx-off de la tuile qui en masque un: le JS n'a jamais a fabriquer du
 * texte traduit, il ne deplace qu'une classe.
 */
function ssx_tile($engine,$value,$locked):string{
    $key=$engine["key"];
    $state=(intval($value)==1)?"ssx-on":"ssx-off";
    $cls="ssx-tile $state";
    $attrs="";
    if($locked){
        $cls.=" ssx-lock";
    }else{
        $cls.=" ssx-live";
        $attrs=" role='button' tabindex='0' OnClick=\"SSXToggle('$key')\" OnKeyDown=\"SSXKey(event,'$key')\"";
    }

    // Le guillemet double delimite l'attribut: une traduction francaise y met
    // des apostrophes, et le moteur de traduction ne les echappe pas.
    $note="";
    if(isset($engine["note"]) && $engine["note"]!==""){$note=" title=\"".$engine["note"]."\"";}

    $h =array();
    $h[]="<div class='$cls' id='ssx-t-$key'$note$attrs>";
    $h[]="<div class='ssx-head'>";
    $h[]="<span class='ssx-ico' style='color:".$engine["color"]."'><i class='".$engine["icon"]."'></i></span>";
    $h[]="<span class='ssx-id'>";
    $h[]="<span class='ssx-name'>".htmlspecialchars($engine["name"])."</span>";
    $h[]="<span class='ak-code ssx-dom'>".htmlspecialchars($engine["domain"])."</span>";
    $h[]="</span>";
    $h[]="<span class='ak-sw".($state=="ssx-on"?" on":"")."' aria-hidden='true'></span>";
    $h[]="</div>";
    $h[]="<div class='ssx-desc'>".$engine["desc"]."</div>";
    $h[]="<div class='ssx-foot'>";
    $h[]="<span class='ak-pill ak-ok ssx-b-on'><span class='ak-dot'></span>{active2}</span>";
    $h[]="<span class='ak-pill ak-off ssx-b-off'><span class='ak-dot'></span>{inactive2}</span>";
    $h[]="</div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

/**
 * La feuille de style de la grille.
 *
 * L'accent vert vient de brand_ui_accents(): sur une appliance en marque
 * blanche il devient la couleur du client, sans toucher a cette page. Les
 * valeurs en dur ne servent que de repli quand class.brand.inc est absent.
 */
function ssx_css():string{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
              "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)",
              "on_fg"=>"#ffffff","label"=>"#1e293b");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    $accent=$ac["accent"];
    $dark=$ac["accent_dark"];
    $soft=$ac["accent_soft"];
    $line=$ac["accent_line"];

    $c=array();
    $c[]="<style type='text/css'>";
    // Pastilles d'etat, code du domaine : kit ak- (angular.css.php).
    $c[]=".ssx{padding:4px 2px 12px 2px}";

    // -- bandeau d'astuce / resolveur
    $c[]=".ssx-hint{display:-webkit-box;display:-ms-flexbox;display:flex;-ms-flex-wrap:wrap;flex-wrap:wrap;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;";
    $c[]="grid-gap:12px;gap:12px;padding:0 4px 14px 4px;font-size:12px;color:#64748b;line-height:18px}";
    $c[]=".ssx-tip{display:-webkit-box;display:-ms-flexbox;display:flex;";
    $c[]="-webkit-box-align:start;-ms-flex-align:start;align-items:flex-start;grid-gap:8px;gap:8px;";
    $c[]="-webkit-box-flex:1;-ms-flex:1 1 320px;flex:1 1 320px;min-width:0}";
    $c[]=".ssx-tip i{color:$accent;margin-top:2px}";
    $c[]=".ssx-tip b{color:#334155;font-weight:600}";
    $c[]=".ssx-engine{display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;grid-gap:6px;gap:6px;";
    $c[]="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;color:#64748b}";
    $c[]=".ssx-engine-ko{color:#9f1239}";
    $c[]=".ssx-status{display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;grid-gap:6px;gap:6px;font-size:11px}";
    $c[]=".ssx-st-run{color:#334155}";
    $c[]=".ssx-st-ok{color:$dark}";
    $c[]=".ssx-st-err{color:#9f1239}";

    // -- grille
    $c[]=".ssx-grid{display:-ms-grid;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));";
    $c[]="grid-gap:16px;gap:16px}";
    $c[]="@media (max-width:1250px){.ssx-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}";
    $c[]="@media (max-width:760px){.ssx-grid{grid-template-columns:minmax(0,1fr)}}";

    // -- tuile
    $c[]=".ssx-tile{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px 14px 20px;";
    $c[]="-webkit-box-shadow:0 1px 3px 0 rgba(15,23,42,.04),0 1px 2px -1px rgba(15,23,42,.03);";
    $c[]="box-shadow:0 1px 3px 0 rgba(15,23,42,.04),0 1px 2px -1px rgba(15,23,42,.03);";
    $c[]="-webkit-transition:all .2s cubic-bezier(.4,0,.2,1);transition:all .2s cubic-bezier(.4,0,.2,1);";
    $c[]="-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}";
    $c[]=".ssx-live{cursor:pointer}";
    $c[]=".ssx-live:hover{-webkit-transform:translateY(-2px);transform:translateY(-2px);";
    $c[]="-webkit-box-shadow:0 10px 15px -3px rgba(15,23,42,.06),0 4px 6px -4px rgba(15,23,42,.04);";
    $c[]="box-shadow:0 10px 15px -3px rgba(15,23,42,.06),0 4px 6px -4px rgba(15,23,42,.04)}";
    $c[]=".ssx-live:active{-webkit-transform:scale(.995);transform:scale(.995)}";
    $c[]=".ssx-live:focus{outline:none;border-color:$accent;-webkit-box-shadow:0 0 0 3px $soft;box-shadow:0 0 0 3px $soft}";
    $c[]=".ssx-on{border-color:$line}";
    $c[]=".ssx-lock{opacity:.55;cursor:default}";
    $c[]=".ssx-busy{opacity:.6}";

    // -- entete de tuile
    $c[]=".ssx-head{display:-webkit-box;display:-ms-flexbox;display:flex;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;grid-gap:14px;gap:14px}";
    $c[]=".ssx-ico{width:44px;height:44px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;";
    $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;";
    $c[]="-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;";
    $c[]="font-size:20px;-webkit-box-flex:0;-ms-flex:0 0 auto;flex:0 0 auto}";
    $c[]=".ssx-id{-webkit-box-flex:1;-ms-flex:1 1 auto;flex:1 1 auto;min-width:0;display:block}";
    $c[]=".ssx-name{display:block;font-size:15px;font-weight:700;color:#0f172a;line-height:21px;";
    $c[]="overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".ssx-dom{margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";

    // -- corps et pied
    $c[]=".ssx-desc{margin:16px 0 14px 0;font-size:13px;line-height:20px;color:#334155;min-height:40px}";
    $c[]=".ssx-foot{border-top:1px solid #f1f5f9;padding-top:12px}";
    $c[]=".ssx-on .ssx-b-off{display:none}";
    $c[]=".ssx-off .ssx-b-on{display:none}";
    $c[]="</style>";
    return @implode("\n",$c);
}

/**
 * Les boutons d'en-tete.
 *
 * Jamais quand la grille est servie dans l'onglet de fw.hacluster.status.php:
 * le TINY ecrit dans le #tiny-button de la PAGE, il volerait l'en-tete du
 * cluster.
 */
function ssx_tiny($page):string{
    $tpl=new template_admin();
    $topbuttons=array();
    $topbuttons[]=array("SSXApplyNow()",ico_play,"{apply}");
    $topbuttons[]=array("SSXRefresh()",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="SafeSearch(s)";
    $TINY["ICO"]="fas fa-filter";
    $TINY["EXPL"]="{safesearch_page_explain}";
    $TINY["URL"]="safesearch";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "\nLoadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
}

/**
 * Le comportement de la grille.
 *
 * Deux temps volontairement separes:
 *  - le clic ENREGISTRE la cle tout de suite, donc la tuile ne ment jamais sur
 *    ce qui est en base;
 *  - la reconfiguration du resolveur est GROUPEE, ~1,5 s apres le dernier clic.
 *    Basculer cinq moteurs ne recharge unbound qu'une fois au lieu de cinq.
 *
 * La chaine est construite en apostrophes simples: en guillemets doubles PHP
 * mangerait chaque $identifiant du JavaScript.
 */
function ssx_js($page,$ha,$resolver,$locked):string{
    $tpl=new template_admin();

    $excl=safesearch_exclusive();
    $applied=$tpl->javascript_parse_text("{safesearch_applied}");
    if($resolver["label"]!==""){
        $applied.=" (".$tpl->javascript_parse_text($resolver["label"]).")";
    }

    $js='
NoSpinner();
(function(){
 var PAGE="__PAGE__", HA=__HA__, LOCKED=__LOCKED__, DELAY=1500, API=__API__;
 var EXCL=__EXCL__;
 var T={run:"__T_RUN__",ok:"__T_OK__"};
 var timer=null, inflight=0;

 function cls(el){return " "+el.className+" ";}
 function isOn(el){return cls(el).indexOf(" ssx-on ")>=0;}
 function paint(el,on){
   if(!el){return;}
   el.className=el.className.replace(/\s*ssx-(on|off)\b/g,"")+(on?" ssx-on":" ssx-off");
   var sw=el.getElementsByClassName("ak-sw")[0];
   if(sw){sw.className="ak-sw"+(on?" on":"");}
 }
 function tiles(){return document.getElementsByClassName("ssx-tile");}
 function snapshot(){
   var out=[],t=tiles();
   for(var i=0;i<t.length;i++){out.push([t[i].id,isOn(t[i])?1:0]);}
   return out;
 }
 function restore(s){
   for(var i=0;i<s.length;i++){paint(document.getElementById(s[i][0]),s[i][1]==1);}
 }
 function status(kind,txt){
   var b=document.getElementById("ssx-status");
   if(!b){return;}
   if(kind===""){b.innerHTML="";b.className="ssx-status";return;}
   var ico="<i class=\"fas fa-circle-notch fa-spin\"></i>";
   if(kind=="ok"){ico="<i class=\"fas fa-circle-check\"></i>";}
   if(kind=="err"){ico="<i class=\"fas fa-triangle-exclamation\"></i>";}
   b.className="ssx-status ssx-st-"+kind;
   b.innerHTML=ico+" <span>"+txt+"</span>";
 }
 function failure(res){
   var t=(res===null||res===undefined)?"":String(res);
   t=t.replace(/^\s+|\s+$/g,"");
   if(t.indexOf("jserror:")===0){t=t.substring(8);}
   status("err",t===""?"__T_FAIL__":t);
 }
 // Contrat plus strict que celui de form_outside (qui tolere 3 caracteres):
 // les deux handlers de cette page ne repondent QUE par post_error(), donc
 // toute sortie est une erreur. La tolerance de form_outside fait passer "KO"
 // ou "-1" pour un succes -- ici elle n aurait servi qu a cela.
 function ok(res){
   var t=(res===null||res===undefined)?"":String(res);
   return t.replace(/^\s+|\s+$/g,"")==="";
 }

 function schedule(){
   if(timer){clearTimeout(timer);}
   status("run",T.run);
   timer=setTimeout(function(){timer=null;apply();},DELAY);
 }
 function apply(){
   if(inflight>0){schedule();return;}
   status("run",T.run);
   $.post(PAGE+"?safesearch-apply=yes",{"safesearch-apply":"yes","byHacluster":HA?1:0},function(res){
     if(!ok(res)){failure(res);return;}
     status("ok",T.ok);
     setTimeout(function(){status("","");},5000);
   }).fail(function(){failure("");});
 }

 window.SSXToggle=function(key){
   if(LOCKED){return;}
   var el=document.getElementById("ssx-t-"+key);
   if(!el || cls(el).indexOf(" ssx-live ")<0 || cls(el).indexOf(" ssx-busy ")>=0){return;}
   // API : une bascule a la fois (articarest refuse une ecriture concurrente, 409).
   if(API && inflight>0){return;}
   var previous=snapshot();
   var next=isOn(el)?0:1;
   paint(el,next==1);
   // Le serveur eteint lui aussi le moteur exclusif: les deux cotes appliquent
   // la meme regle, l ecran n a donc pas a attendre la reponse pour etre juste.
   if(next==1 && EXCL[key]){paint(document.getElementById("ssx-t-"+EXCL[key]),false);}
   el.className=el.className+" ssx-busy";
   inflight++;
   $.post(PAGE,{"safesearch-toggle":key,"value":next,"byHacluster":HA?1:0},function(res){
     inflight--;
     el.className=el.className.replace(/\s*ssx-busy\b/g,"");
     if(!ok(res)){restore(previous);failure(res);return;}
     // API (unbound) : la bascule est deja appliquee et prouvee par articarest,
     // aucune reconfiguration groupee a lancer.
     if(API){
       status("ok",T.ok);
       setTimeout(function(){status("","");},5000);
       Loadjs(PAGE+"?api-notices-js=yes");
       return;
     }
     schedule();
   }).fail(function(){
     inflight--;
     el.className=el.className.replace(/\s*ssx-busy\b/g,"");
     restore(previous);failure("");
   });
 };
 window.SSXKey=function(ev,key){
   var k=ev.which||ev.keyCode;
   if(k!=13 && k!=32){return;}
   if(ev.preventDefault){ev.preventDefault();}
   window.SSXToggle(key);
 };
 window.SSXApplyNow=function(){
   if(timer){clearTimeout(timer);timer=null;}
   apply();
 };
 window.SSXRefresh=function(){
   LoadAjax("safesearch-table",PAGE+"?table-flat=yes"+(HA?"&byHacluster=1":""));
 };
})();';

    $js=str_replace("__PAGE__",$page,$js);
    $js=str_replace("__HA__",($ha?"true":"false"),$js);
    $js=str_replace("__LOCKED__",($locked?"true":"false"),$js);
    $js=str_replace("__API__",(safesearch_uses_api($ha,$resolver)?"true":"false"),$js);
    $js=str_replace("__EXCL__",json_encode($excl),$js);
    $js=str_replace("__T_RUN__",addslashes($tpl->javascript_parse_text("{safesearch_applying}")),$js);
    $js=str_replace("__T_OK__",addslashes($applied),$js);
    $js=str_replace("__T_FAIL__",addslashes($tpl->javascript_parse_text("{failed}")),$js);
    return $js;
}

/**
 * Bascule d'un moteur. N'echo rien en cas de succes: le JS traite toute
 * reponse non vide comme une erreur et remet la tuile dans son etat d'avant.
 */
function toggle_save(){
    $tpl=new template_admin();
    $ha=safesearch_hacluster_post();
    $denied=safesearch_denied($ha);
    if($denied!==""){echo $denied;return;}

    // Filtre a la LECTURE, a la forme des cles legitimes. post_error() echappe
    // deja sa sortie (post_error_html), mais la page ne doit pas dependre d'un
    // helper distant pour l'hygiene de sa propre entree: ce que le bandeau
    // d'erreur reaffiche ne doit jamais pouvoir ressembler a du balisage.
    $key=preg_replace('/[^A-Za-z0-9_]/','',(string)$_POST["safesearch-toggle"]);
    $value=intval(isset($_POST["value"])?$_POST["value"]:0);
    if($value!==1){$value=0;}

    $names=array();
    foreach(safesearch_engines() as $e){$names[$e["key"]]=$e["name"];}
    if(!isset($names[$key])){echo $tpl->post_error("{unknown} : $key");return;}

    $writes=array($key=>$value);
    $excl=safesearch_exclusive();
    if($value==1 && isset($excl[$key])){$writes[$excl[$key]]=0;}
    // Une cle tenue par une politique Meta (moteur ou son exclusif) : rien n'est ecrit.
    $meta=unbound_meta_refuse_keys(array_keys($writes));
    if($meta!==""){echo $meta;return;}

    if(safesearch_uses_api($ha,safesearch_resolver())){
        // unbound : PUT /unbound/api/settings/safesearch ecrit, verifie par
        // unbound-checkconf, recharge et prouve, ou refuse (409 esclave ou
        // cache arrete, 422) sans rien ecrire.
        $split=unbound_settings::split($writes,array());
        $res=unbound_settings::apply(new unbound_api(),$split["groups"]);
        if(!$res["ok"]){ echo $tpl->post_error($res["error"]);return; }
        unbound_settings::queue($res);
        $track=array();
        foreach($writes as $k=>$v){$track[]=$names[$k]." = ".($v==1?"1":"0");}
        admin_tracks("SafeSearch(s): ".@implode(", ",$track));
        return;
    }

    $track=array();
    foreach($writes as $k=>$v){
        if(!$GLOBALS["CLASS_SOCKETS"]->SET_INFO($k,$v)){
            echo $tpl->post_error("{failed} : $k");
            return;
        }
        $track[]=$names[$k]." = ".($v==1?"1":"0");
    }
    admin_tracks("SafeSearch(s): ".@implode(", ",$track));

    if($ha){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    }
}

/**
 * Vrai quand les bascules passent par l'API du cache DNS : resolveur unbound,
 * hors onglet HaCluster (qui notifie ses noeuds) et hors micro-cache local
 * (service dnscache, sans API de reglages).
 */
function safesearch_uses_api($ha,$resolver):bool{
    if($ha){return false;}
    return is_array($resolver) && isset($resolver["id"]) && $resolver["id"]==="unbound";
}

function api_notices_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    echo unbound_api::notices_js($tpl->javascript_parse_text("{warning}",0,true),function($s) use ($tpl){
        return $tpl->javascript_parse_text($s,0,true);
    });
}

/**
 * Regenere la configuration du resolveur qui applique reellement les regles.
 * Appelee une fois apres une rafale de bascules, ou par le bouton {apply}.
 */
function apply_now(){
    $tpl=new template_admin();
    $ha=safesearch_hacluster_post();
    $denied=safesearch_denied($ha);
    if($denied!==""){echo $denied;return;}

    if($ha){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all"));
        if(!is_object($json) || !isset($json->Status) || !$json->Status){
            echo $tpl->post_error("{failed} : /hacluster/server/notify/all");
        }
        return;
    }

    $resolver=safesearch_resolver();
    if($resolver["route"]===""){
        echo $tpl->post_error("{safesearch_no_resolver}");
        return;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($resolver["route"]));
    if(!is_object($json)){
        echo $tpl->post_error("{failed} : ".$resolver["route"]);
        return;
    }
    if(isset($json->Status) && !$json->Status){
        $err="{failed} : ".$resolver["route"];
        if(property_exists($json,"Error") && trim((string)$json->Error)!==""){$err=(string)$json->Error;}
        echo $tpl->post_error($err);
        return;
    }
    admin_tracks("SafeSearch(s): DNS configuration rebuilt by ".$resolver["id"]." (".$resolver["route"].")");
}
