<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Page d'entree de la section « Authentification » de la messagerie. Elle
// existait dans le menu (class.left-menus.inc) mais le fichier ne contenait que
// son ouverture PHP: cliquer l'entree affichait une page blanche.
//
// Comme fw.postfix.incoming.intro.php, elle est purement explicative -- aucun
// appel REST, donc aucun chiffre qui puisse etre perime -- et ses tuiles suivent
// la CHAINE, ici celle d'une session SMTP:
//   1. la liste blanche laisse relayer SANS authentification,
//   2. les adresses autorisees decident d'ou une authentification est acceptee,
//   3. les comptes disent qui peut s'authentifier,
//   4. la liaison login/expediteur dit au nom de qui il peut ecrire.
// Puis les deux fournisseurs d'identite ranges ailleurs dans l'arbre (Active
// Directory, OAuth2), qu'on ne trouve pas depuis ce sous-menu.

$users=new usersMenus();
if(!$users->AsPostfixAdministrator){exit();}

if(isset($_GET["build-page"])){build_page();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval(isset($_GET["instance-id"])?$_GET["instance-id"]:0);

    $html=$tpl->page_header("{authentication}",ico_users,"{postfix_auth_intro}",
        "$page?build-page=yes&instance-id=$ID",   // 4: contenu auto-LoadAjax
        "postfix-auth-$ID",                       // 5: route lisible
        "progress-postfix-auth-restart",false,
        "postfix-auth-intro-$ID");                // 8: div de chargement

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{authentication}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

/**
 * Feuille de style des tuiles, reprise a l'identique de
 * fw.postfix.incoming.intro.php et fw.postfix.statistics.intro.php: les trois
 * pages d'entree de la messagerie doivent se ressembler, sinon l'operateur croit
 * changer d'application en passant de l'une a l'autre. Prefixe distinct (pauth-)
 * pour rester autonome.
 *
 * Elle est retournee A PART et concatenee HORS de _ENGINE_parse_body: la regex
 * #{(.+?)}# du moteur lirait chaque corps de regle CSS comme un {jeton} et
 * viderait la feuille. Meme precaution que table_form_compile().
 */
function pauth_css():string{
    return "<style>
.pauth-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;padding:8px 4px}
.pauth-card{background:#fff;padding:24px;border:1px solid #e5e7eb;border-radius:12px;
 box-shadow:0 1px 2px rgba(0,0,0,.05);display:flex;flex-direction:column;justify-content:space-between;
 transition:box-shadow .2s}
.pauth-card:hover{box-shadow:0 4px 10px rgba(0,0,0,.08)}
.pauth-head{display:flex;align-items:flex-start;margin-bottom:16px}
.pauth-ico{width:48px;height:48px;border-radius:8px;display:flex;align-items:center;justify-content:center;
 background:#f0fdfa;color:#0d9488;font-size:20px;flex-shrink:0}
.pauth-ico-danger{background:#fef2f2;color:#ef4444}
.pauth-txt{margin-left:16px}
.pauth-title{font-size:18px;font-weight:700;color:#1f2937;line-height:1.3}
.pauth-desc{font-size:14px;color:#6b7280;margin-top:4px;line-height:1.6}
.pauth-btn{display:flex;align-items:center;justify-content:center;width:100%;margin-top:8px;padding:8px 12px;
 background:#f9fafb;color:#0d9488;border:1px solid #ccfbf1;border-radius:8px;font-weight:600;font-size:14px;
 cursor:pointer;transition:background .15s,color .15s}
.pauth-btn:hover{background:#14b8a6;color:#fff;border-color:#14b8a6}
.pauth-btn i{margin-left:8px;font-size:12px}
@media(max-width:1100px){.pauth-grid{grid-template-columns:minmax(0,1fr)}}
</style>";
}

/**
 * Une tuile de consultation.
 *
 * MenuRoot() du menu gauche fait deux choses: surligner l'entree cliquee et
 * charger la page dans #MainContent. Depuis une tuile il n'y a pas d'entree de
 * menu a surligner, donc on ne garde que la seconde -- appeler MenuRoot avec
 * $(this) ajouterait la classe active au <li> qui se trouve porter le bouton.
 */
function pauth_tile($ico,$title,$desc,$target,$danger=false):string{
    $cls=$danger?"pauth-ico pauth-ico-danger":"pauth-ico";
    $js="LoadAjaxSilent('MainContent','$target')";
    return "<div class='pauth-card'>
      <div class='pauth-head'>
        <div class='$cls'><i class='$ico'></i></div>
        <div class='pauth-txt'><div class='pauth-title'>$title</div><div class='pauth-desc'>$desc</div></div>
      </div>
      <div class='pauth-btn' onclick=\"$js\">{open}<i class='fas fa-arrow-right'></i></div>
    </div>";
}

/**
 * Liste blanche d'authentification: les adresses qui relaient SANS s'authentifier.
 *
 * En rouge parce que c'est le seul reglage de la section qui ouvre une exception
 * au lieu d'en fermer une. Une entree trop large fait un relais ouvert, et le
 * message part sans qu'aucun nom de compte n'apparaisse dans le journal -- donc
 * sans rien a chercher le jour ou l'on se demande qui a emis.
 */
function pauth_tile_whitelist($ID):string{
    return pauth_tile("fas fa-shield-halved","{postfix.auth.whitelist.title}",
        "{postfix_auth_whitelist_explain}",
        "fw.postfix.auth.whitelist.php?instance-id=$ID",true);
}

/** Adresses depuis lesquelles une authentification est acceptee. */
function pauth_tile_network($ID):string{
    return pauth_tile("fas fa-user-lock","{postfix.auth.network.title}",
        "{postfix_auth_network_explain}",
        "fw.postfix.auth.network.php?instance-id=$ID");
}

/** Les comptes SASL locaux: qui peut s'authentifier. */
function pauth_tile_accounts($ID):string{
    return pauth_tile("fas fa-user-shield","{saslusers_title}",
        "{postfix_auth_accounts_explain}",
        "fw.postfix.saslusers.php?instance-id=$ID");
}

/** Liaison login -> adresses d'expedition autorisees (smtpd_sender_login_maps). */
function pauth_tile_login_maps($ID):string{
    return pauth_tile(ico_users,"{smtp_sender_login_maps}",
        "{postfix_auth_login_maps_explain}",
        "fw.postfix.smtpd_sender_login_maps.php?instance-id=$ID");
}

/**
 * Authentification deleguee a un annuaire Active Directory.
 *
 * Meme garde-fou que class.left-menus.inc, volontairement recopie: la
 * fonctionnalite doit etre activee, et l'entree n'existe que sur l'instance
 * principale. Si l'un des deux bouge, les deux doivent bouger ensemble.
 */
function pauth_tile_activedirectory():string{
    return pauth_tile("fab fa-windows","{active_directory_authentication}",
        "{postfix_auth_ad_explain}",
        "fw.postfix.activedirectory.php");
}

/**
 * OAuth2 / XOAUTH2. Range dans le menu « flux sortant » parce qu'il sert a
 * s'authentifier AUPRES d'un relais externe, mais c'est de l'authentification
 * SMTP: la tuile est ici pour qu'on la trouve, la page reste ou elle est.
 */
function pauth_tile_oauth($ID):string{
    return pauth_tile("fas fa-envelope-circle-check","{postfix_oauth}",
        "{postfix_auth_oauth_explain}",
        "fw.postfix.oauth.php?instance-id=$ID");
}

function build_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval(isset($_GET["instance-id"])?$_GET["instance-id"]:0);

    // Ordre = celui dans lequel les controles s'appliquent a une session, pas
    // celui du sous-menu: on sait ainsi a quel etage regarder quand un client
    // passe ou ne passe pas.
    $tiles=array();
    $tiles[]=pauth_tile_whitelist($ID);
    $tiles[]=pauth_tile_network($ID);
    $tiles[]=pauth_tile_accounts($ID);
    $tiles[]=pauth_tile_login_maps($ID);

    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($EnableActiveDirectoryFeature==1 && $ID==0){
        $tiles[]=pauth_tile_activedirectory();
    }

    $tiles[]=pauth_tile_oauth($ID);

    $html="<div class='pauth-grid'>".@implode("\n",$tiles)."</div>";

    $topbuttons=array();
    $topbuttons[]=array("LoadAjaxSilent('postfix-auth-intro-$ID','$page?build-page=yes&instance-id=$ID')",
        ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_POSTFIX}: {authentication}";
    $TINY_ARRAY["ICO"]=ico_users;
    $TINY_ARRAY["EXPL"]="{postfix_auth_intro}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // La feuille de style est concatenee AUTOUR de l'analyse, jamais dedans: la
    // regex #{(.+?)}# du moteur prendrait chaque corps de regle CSS pour un
    // {jeton} et viderait la feuille (meme precaution que table_form_compile()).
    echo pauth_css()
        .$tpl->_ENGINE_parse_body($html)
        ."<script>NoSpinner();\n$tiny</script>";
}
