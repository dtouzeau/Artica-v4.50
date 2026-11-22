<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");
// Kit de tableaux awc-* : include INCONDITIONNEL, au premier niveau. Place dans
// le bootstrap ci-dessus il ne s'executerait jamais (class.template-admin.inc a
// deja charge class.sockets.inc), et la page mourrait sur un
// "Call to undefined function awc_table_style()" -- un spinner qui ne part plus.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Les confirmations (js_dialog_confirm_action) postent ces marqueurs : le droit
// est verifie ICI, cote serveur, avant que la progression ne soit lancee.
if(isset($_POST["debupd-install"])){debupd_confirm_post("install");exit;}
if(isset($_POST["debupd-fullupgrade"])){debupd_confirm_post("fullupgrade");exit;}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["update-system-js"])){update_system_js();exit;}
if(isset($_GET["upgrade-system-js"])){upgrade_system_js();exit;}
if(isset($_GET["upgrade-system-js2"])){upgrade_system_js2();exit;}
if(isset($_GET["purge-obsolete-js"])){purge_obsolete_js();exit;}
if(isset($_GET["purge-obsolete-js2"])){purge_obsolete_js2();exit;}
if(isset($_GET["purge-obsolete-popup"])){purge_obsolete_popup();exit;}
if(isset($_GET["update-system-js2"])){update_system_js2();exit;}
if(isset($_GET["table-main"])){table_main();exit;}
if(isset($_GET["check-system-js"])){check_system_js();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["main-page"])){page();exit;}
table();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{OS} {update}", "fa-duotone fa-box-open", "{debupd_header_explain}", "$page?table-main=yes", "os-update", "debian-restart-updates", false, "debian-system-updates");

    $tpl=new template_admin("{OS} {update}",$html);
    echo $tpl->build_firewall();


}
function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->js_dialog1("{pkg_upgrade_interface}", "$page?table=yes&byjs=yes");

}

function table(){
	$page=CurrentPageName();
    $tpl=new template_admin();
    if(isset($_GET["byjs"])) {
        // En dialogue, les actions vivent dans le corps de la page (cartes de
        // methode, bandeau) : plus de boutons a recopier dans le sous-titre.
        $html = $tpl->page_header("{OS} {update}", "fa-duotone fa-box-open", "{debupd_header_explain}", "$page?table-main=yes&byjs=yes", null, "debian-system-updates", false, "debian-system-updates");
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    echo "<div id='debian-system-updates'></div>";
    echo "<script>LoadAjaxTiny('debian-system-updates','$page?table-main=yes');</script>";
    return true;
}

// L'hote de la barre de progression, distinct du conteneur de contenu.
//
// fw.progress.php ecrit sa barre dans le div passe en `mainid`, PUIS le vide
// cinq secondes apres la fin (Clean$time(): innerHTML='') pour la faire
// disparaitre. Les operations de cette page lui donnaient autrefois
// « debian-system-updates », c'est-a-dire le conteneur de contenu -- que leur
// propre rappel AFTER venait de recharger : le tableau revenait, puis la zone
// entiere se vidait cinq secondes plus tard, sans erreur ni requete.
//
// Le div est emis PAR LE FRAGMENT : il existe donc aussi bien sur la page que
// dans le dialogue (?byjs=yes), et un rechargement du contenu le remplace par un
// exemplaire neuf et vide -- que Clean peut vider sans consequence.
//
// Une FONCTION, pas une constante : le bloc d'aiguillage en tete de fichier
// appelle table_main() puis exit, bien AVANT qu'un define() pose plus bas ait
// eu la moindre chance de s'executer (Error fatale en PHP 8).
function debupd_progress_div():string{
    return "debupd-progress";
}

// Ce que fait chaque operation une fois terminee : recharger le contenu et le
// bandeau de notifications. Une seule definition pour tous les appels.
function debupd_after_js():string{
    $page=CurrentPageName();
    return "LoadAjaxSilent('debian-system-updates','$page?table-main=yes');"
        ."LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
}

// Mettre a jour ou purger touche au systeme entier : droit le plus large, verifie
// cote serveur sur CHAQUE point d'entree (confirmation ET lancement), parce que
// cacher le bouton n'a jamais empeche personne d'appeler l'URL.
function debupd_is_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}

// Un `?xxx-js2=yes` appele sans le droit repond par une alerte, jamais par la
// progression. Retourne true quand l'appel doit s'arreter la.
function debupd_js_denied():bool{
    if(debupd_is_admin()){return false;}
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $tpl->js_error("{ERROR_NO_PRIVS}");
    return true;
}

// Reponse aux confirmations : vide si l'operateur a le droit (la confirmation
// enchaine alors sur ?xxx-js2=yes), message d'erreur sinon.
function debupd_confirm_post($what){
    $tpl=new template_admin();
    if(!debupd_is_admin()){
        echo $tpl->post_error("{ERROR_NO_PRIVS}");
        return;
    }
    if($what=="install"){admin_tracks("OS update: confirmed the installation of the pending Debian package updates");}
    if($what=="fullupgrade"){admin_tracks("OS update: confirmed a full Debian system upgrade (dist-upgrade)");}
}

// Les confirmations disent les consequences AVANT l'action : la precedente
// (js_confirm_execute) ne faisait que repeter le titre du bouton.
function update_system_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $count=count(debupd_model()["rows"]);
    $text=str_replace("%s",$count,$tpl->_ENGINE_parse_body("{debupd_confirm_install}"));
	$tpl->js_dialog_confirm_action($text,"debupd-install","none","Loadjs('$page?update-system-js2=yes')");
}
function upgrade_system_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog_confirm_action("{debupd_confirm_full}","debupd-fullupgrade","none","Loadjs('$page?upgrade-system-js2=yes')");
}
function update_system_js2():bool{
    if(debupd_js_denied()){return false;}
    $tpl=new template_admin();
    echo $tpl->framework_buildjs("/system/debian/upgrade","upgrade.progress","upgrade.log",debupd_progress_div(),debupd_after_js());
    return true;
}

// Le compteur est LU, jamais calcule ici : le daemon publie MAIN_APT_OBSOLETE_JSON
// (tache systeme 88, ou le bouton {check_software_updates} qui rafraichit au
// passage). Faire forker apt a la console couterait une seconde par rendu de
// page, et deux definitions de « perime » finiraient par diverger.
function debupd_obsolete_json(){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MAIN_APT_OBSOLETE_JSON"));
    if(!is_object($json)){return null;}
    return $json;
}
function obsolete_packages_count():int{
    $json=debupd_obsolete_json();
    if(!is_object($json)){return 0;}
    if(!property_exists($json,"count")){return 0;}
    return intval($json->count);
}

function purge_obsolete_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $count=obsolete_packages_count();
    // A modal rather than a bare confirm: the operator sees WHAT will be
    // removed, and why the previous purge left something behind. The count
    // alone left a customer with a button stuck at "(2)" and no reason
    // anywhere (ticket 1792869893).
    $tpl->js_dialog2("{debupd_cleanup_title} ($count)","$page?purge-obsolete-popup=yes",900);
}

function obsolete_reason_label($reason):string{
    $labels=array(
        "autoremove"=>"{obsolete_reason_autoremove}",
        "residual-config"=>"{obsolete_reason_residual_config}",
        "superseded-kernel"=>"{obsolete_reason_superseded_kernel}",
    );
    if(isset($labels[$reason])){return $labels[$reason];}
    return htmlspecialchars((string)$reason);
}

function purge_obsolete_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=debupd_obsolete_json();
    $packages=array();
    if(is_object($json) && isset($json->packages) && is_array($json->packages)){
        $packages=$json->packages;
    }

    $html=array();
    $html[]=$tpl->div_explain("{debupd_cleanup_title}||{purge_obsolete_packages_explain}");

    if(is_object($json) && isset($json->last_purge) && is_object($json->last_purge) && trim((string)$json->last_purge->error)<>""){
        $when=$tpl->time_to_date(intval($json->last_purge->at),true);
        $html[]=$tpl->div_error("<strong>{last_purge_failed}</strong> (".htmlspecialchars($when).")<br>".htmlspecialchars((string)$json->last_purge->error));
    }

    $rows=array();
    foreach($packages as $p){
        $name=htmlspecialchars((string)$p->name);
        $version=htmlspecialchars((string)$p->version);
        $rows[]="<tr><td><div class='awc-ident'><span class='awc-ico'><i class='fa fa-box'></i></span>"
            ."<span><span class='awc-name'>$name</span><div class='awc-meta'>$version</div></span></div></td>"
            ."<td class='awc-mono'>".obsolete_reason_label((string)$p->reason)."</td></tr>";
    }

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr><th>{package}</th><th>{debupd_why_removable}</th></tr></thead>";
    $html[]="<tbody id='obsolete-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='2'><div class='awc-empty'>{nothing_to_do}</div></td></tr>";
    }else{
        $html[]=implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>".count($rows)." {items}</span><span class='awc-pg' id='obsolete-pager'></span></div>";
    $html[]="</div></div>";

    if(count($rows)>0){
        $label=str_replace("%s",count($rows),$tpl->_ENGINE_parse_body("{debupd_remove_n}"));
        $html[]="<div style='text-align:right;margin-top:15px'>";
        $html[]=$tpl->button_autnonome($label,"dialogInstance2.close();Loadjs('$page?purge-obsolete-js2=yes');","fa fa-trash-can","AsSystemAdministrator",0,"btn-danger");
        $html[]="</div>";
    }
    $html[]="<script>NoSpinner();</script>";

    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).awc_pager_js(15,"obsolete-rows","obsolete-pager");
    return true;
}

function purge_obsolete_js2():bool{
    if(debupd_js_denied()){return false;}
    $tpl=new template_admin();
    admin_tracks("OS update: started the purge of obsolete Debian packages");
    echo $tpl->framework_buildjs("/system/debian/obsolete/purge","aptobsolete.progress","aptobsolete.log",debupd_progress_div(),debupd_after_js());
    return true;
}

function upgrade_system_js2():bool{
    if(debupd_js_denied()){return false;}
    $tpl=new template_admin();
    echo $tpl->framework_buildjs("/system/debian/distupgrade","upgrade.progress","upgrade.log",debupd_progress_div(),debupd_after_js());
    return true;
}

// Le calcul du plan passe par l'API, plus par le shell.
//
// L'ancien chemin (framework/aptget.php -> exec.apt-get.php -> one-shot) ecrivait
// MAIN_APT_GET_JSON dans Valkey sans toucher la memoire du daemon en cours, que
// GET_INFO lit AVANT Valkey : la barre atteignait 100 %, la page affichait
// toujours l'ancien plan. Avec /system/debian/checkupgrade le calcul tourne DANS
// le daemon, donc la page relit ce qui vient d'etre calcule.
function check_system_js():bool{
	$tpl=new template_admin();
	echo $tpl->framework_buildjs(
		"/system/debian/checkupgrade",
		"aptget.progress",
		"aptget.log",
		debupd_progress_div(),
		debupd_after_js());
	return true;
}

// ---------------------------------------------------------------------------
// Le plan de mise a jour, tel que le daemon le publie.
//
// MAIN_APT_GET_JSON porte quatre cartes indexees par nom de paquet, toutes
// alimentees par la MEME ligne `Inst` d'apt (plan `--just-print dist-upgrade`) :
//
//   packages_list      la version DEJA INSTALLEE (semantique historique)
//   candidate_versions celle que la mise a jour installerait
//   origins            l'archive d'origine -- le seul marqueur fiable de securite
//   sections           la taxonomie apt (libs, admin, net...)
//
// Les trois dernieres sont recentes : une appliance qui porte un daemon anterieur
// renvoie un objet sans ces champs, et la page doit s'afficher quand meme, en
// degradant. D'ou les property_exists() plutot qu'un acces direct.
//
// Trois etats, pas deux : « aucun plan publie » n'est PAS « a jour ». Une
// appliance neuve, ou dont le calcul n'a jamais abouti, n'a pas de cle du tout ;
// lui afficher la coche verte serait affirmer une verification qui n'a pas eu lieu.
function debupd_payload_state(&$json):string{
    $raw=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MAIN_APT_GET_JSON"));
    $json=null;
    if($raw==""){return "unknown";}
    $decoded=json_decode($raw);
    if(!is_object($decoded)){return "unknown";}
    if(!property_exists($decoded,"package_number")){return "unknown";}
    if(intval($decoded->package_number)==0){return "uptodate";}
    if(!property_exists($decoded,"packages_list") || !is_object($decoded->packages_list)){return "unknown";}
    $json=$decoded;
    return "pending";
}

// Une carte du JSON ramenee a un tableau PHP, vide si le champ n'existe pas.
function debupd_map($json,$field):array{
    if(!is_object($json)){return array();}
    if(!property_exists($json,$field)){return array();}
    if(!is_object($json->{$field})){return array();}
    return get_object_vars($json->{$field});
}

// Seule l'ORIGINE atteste d'une mise a jour de securite. Debian ecrit
// « Debian-Security:13/stable », Ubuntu « Ubuntu:24.04/noble-security » : le
// separateur et la position changent, le mot non.
function debupd_is_security($origin):bool{
    return stripos((string)$origin,"security")!==false;
}

// Le miroir est lu dans les sources apt, les DEUX formats confondus : Debian 12+
// ecrit du deb822 (« URIs: ») dans sources.list.d/*.sources, les anciennes
// installations gardent des lignes « deb http://... » dans sources.list.
function debupd_mirror_read():string{
    $files=array("/etc/apt/sources.list");
    $globbed=@glob("/etc/apt/sources.list.d/*.sources");
    if(is_array($globbed)){$files=array_merge($files,$globbed);}
    $globbed=@glob("/etc/apt/sources.list.d/*.list");
    if(is_array($globbed)){$files=array_merge($files,$globbed);}

    foreach($files as $file){
        if(!@is_readable($file)){continue;}
        $lines=explode("\n",(string)@file_get_contents($file));
        foreach($lines as $line){
            $line=trim($line);
            if($line==""){continue;}
            if(substr($line,0,1)=="#"){continue;}
            $uri="";
            if(preg_match("#^URIs:\s*(\S+)#i",$line,$re)){$uri=$re[1];}
            if(preg_match("#^deb(?:-src)?\s+(?:\[[^\]]*\]\s+)?(\S+)#i",$line,$re)){$uri=$re[1];}
            if($uri==""){continue;}
            // Un depot de securite n'est pas le miroir principal.
            if(stripos($uri,"security")!==false){continue;}
            $host=@parse_url($uri,PHP_URL_HOST);
            if($host==""){continue;}
            return $host;
        }
    }
    return "";
}

// La date du dernier « apt update » est la date de l'index apt lui-meme :
// /var/cache/apt/pkgcache.bin est reecrit a chaque rafraichissement (cron
// aptget-update du daemon, toutes les 4 h). Aucun autre horodatage n'existe :
// le plan MAIN_APT_GET_JSON n'en porte pas.
function debupd_index_read():int{
    $file="/var/cache/apt/pkgcache.bin";
    if(!@is_file($file)){return 0;}
    $t=@filemtime($file);
    if($t===false){return 0;}
    return intval($t);
}

// php_uname("r") donne le noyau de la machine qui execute la console -- et la
// console tourne SUR l'appliance.
function debupd_os_name():string{
    if(!@is_readable("/etc/os-release")){return "";}
    $os=(string)@file_get_contents("/etc/os-release");
    if(!preg_match('#^PRETTY_NAME="?([^"\n]+)#m',$os,$re)){return "";}
    // « GNU/Linux » n'apporte rien et pousse la version a la ligne.
    return trim(str_replace("GNU/Linux ","",$re[1]));
}

// ---------------------------------------------------------------------------
// Le modele : tout ce que la page affiche, lu UNE fois. Chaque widget en recoit
// une copie et ne parle jamais au daemon, sinon deux tuiles finiraient par
// montrer deux instantanes differents.
function debupd_model():array{
    if(isset($GLOBALS["DEBUPD_MODEL"])){return $GLOBALS["DEBUPD_MODEL"];}
    $json=null;
    $m=array();
    $m["state"]=debupd_payload_state($json);
    $m["rows"]=array();
    $m["security"]=0;

    if($m["state"]=="pending"){
        $packages=get_object_vars($json->packages_list);
        $candidates=debupd_map($json,"candidate_versions");
        $origins=debupd_map($json,"origins");
        $sections=debupd_map($json,"sections");
        $descs=debupd_map($json,"Descriptions");
        foreach($packages as $name=>$current){
            $origin=isset($origins[$name])?(string)$origins[$name]:"";
            $sec=debupd_is_security($origin);
            if($sec){$m["security"]++;}
            $m["rows"][]=array(
                "name"=>(string)$name,
                "current"=>(string)$current,
                "candidate"=>isset($candidates[$name])?(string)$candidates[$name]:"",
                "section"=>isset($sections[$name])?(string)$sections[$name]:"",
                "security"=>$sec,
                "desc"=>isset($descs[$name])?(string)$descs[$name]:"",
            );
        }
        // Les correctifs de securite d'abord, puis l'ordre alphabetique : c'est
        // ce qu'on vient chercher, il ne doit pas etre a la page 3.
        usort($m["rows"],function($a,$b){
            if($a["security"]<>$b["security"]){return $a["security"]?-1:1;}
            return strcmp($a["name"],$b["name"]);
        });
        if(count($m["rows"])==0){$m["state"]="uptodate";}
    }

    $m["disabled"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableOsSystemUpdate"))==1;

    $m["obsolete"]=0;
    $m["purge_error"]="";
    $obs=debupd_obsolete_json();
    if(is_object($obs)){
        if(property_exists($obs,"count")){$m["obsolete"]=intval($obs->count);}
        if(isset($obs->last_purge) && is_object($obs->last_purge)){
            $m["purge_error"]=trim((string)$obs->last_purge->error);
        }
    }

    $m["index_time"]=debupd_index_read();
    $m["mirror"]=debupd_mirror_read();
    $m["kernel"]=php_uname("r");
    $m["os"]=debupd_os_name();
    $m["admin"]=debupd_is_admin();
    $GLOBALS["DEBUPD_MODEL"]=$m;
    return $m;
}

// ---------------------------------------------------------------------------
// Petits utilitaires d'affichage.

// Un age relatif, traduit AVANT la substitution : remplacer %s dans le jeton brut
// laisserait un {jeton} qui ne correspond plus a rien.
function debupd_age($t):string{
    $tpl=new template_admin();
    if($t<=0){return $tpl->_ENGINE_parse_body("{never}");}
    $age=time()-$t;
    if($age<0){$age=0;}
    if($age<3600){
        $n=max(1,intval($age/60));
        return str_replace("%s",$n,$tpl->_ENGINE_parse_body("{debupd_ago_min}"));
    }
    if($age<86400){
        return str_replace("%s",intval($age/3600),$tpl->_ENGINE_parse_body("{debupd_ago_hours}"));
    }
    return str_replace("%s",intval($age/86400),$tpl->_ENGINE_parse_body("{debupd_ago_days}"));
}

// Plus de deux jours sans index frais alors que le daemon le relit toutes les
// 4 h : quelque chose bloque apt (miroir injoignable, verrou), on le signale.
function debupd_index_stale($t):bool{
    if($t<=0){return true;}
    return (time()-$t)>2*86400;
}

function debupd_check_button($m,$extra=""):string{
    $page=CurrentPageName();
    if($m["disabled"]){
        return "<button type='button' class='ak-btn $extra' disabled title=\"{debupd_disabled_title}\">"
            ."<i class='fas fa-arrows-rotate'></i> {debupd_check_now}</button>";
    }
    return "<button type='button' class='ak-btn $extra debupd-btn-o' OnClick=\"Loadjs('$page?check-system-js=yes');\">"
        ."<i class='fas fa-arrows-rotate'></i> {debupd_check_now}</button>";
}

// ---------------------------------------------------------------------------
// Les widgets. Un widget = une fonction.

// 1. Bandeau d'accroche : le benefice en langage courant, et des puces qui sont
// des verites du produit. La puce « toutes les 48 h » LIT le reglage : quand
// DisableOsSystemUpdate vaut 1, le calcul automatique ne tourne pas.
function debupd_banner($m):string{
    $chips=array();
    $chips[]="<span class='debupd-chip'><i class='fas fa-lock'></i> {debupd_chip_repos}</span>";
    $chips[]="<span class='debupd-chip'><i class='fas fa-gear'></i> {debupd_chip_config}</span>";
    if(!$m["disabled"]){
        $chips[]="<span class='debupd-chip'><i class='fas fa-clock'></i> {debupd_chip_48h}</span>";
    }
    $when=$m["index_time"]>0?date("Y-m-d H:i",$m["index_time"]):"";

    $h ="<div class='ak-card debupd-hero'>";
    $h.="<div class='ak-ico ak-ico-lg ak-ico-sq ak-ok'><i class='fas fa-shield-halved'></i></div>";
    $h.="<div class='debupd-hero-main'>";
    $h.="<div class='debupd-hero-t'>{debupd_hero_title}</div>";
    $h.="<div class='debupd-hero-p'>{debupd_hero_text}</div>";
    $h.="<div class='ak-chips debupd-chips'>".implode("",$chips)."</div>";
    $h.="</div>";
    $h.="<div class='debupd-hero-side'>";
    $h.=debupd_check_button($m,"ak-btn-lg");
    $h.="<div class='debupd-hero-s' title='".htmlspecialchars($when)."'>{debupd_repos_read}: ".htmlspecialchars(debupd_age($m["index_time"]))."</div>";
    $h.="</div>";
    $h.="</div>";
    return $h;
}

// Alerte : le calcul automatique est coupe par un reglage. Seulement dans ce cas.
function debupd_disabled_alert($m):string{
    if(!$m["disabled"]){return "";}
    return "<div class='ak-banner ak-warn debupd-gap'><i class='fas fa-triangle-exclamation'></i>"
        ."<div class='ak-banner-m'><strong>{debupd_disabled_title}</strong><br>{debupd_disabled_text}</div></div>";
}

// 2a. Tuile « mises a jour disponibles ». Sans plan publie : un tiret, pas un 0.
function debupd_kpi_updates($m):string{
    $value="&#8212;";
    $sub="{debupd_kpi_updates_unknown}";
    if($m["state"]=="uptodate"){$value="0";$sub="{debupd_kpi_updates_none}";}
    if($m["state"]=="pending"){$value=count($m["rows"]);$sub="{debupd_kpi_updates_sub}";}
    $h ="<div class='ak-card debupd-kpi'>";
    $h.="<div class='debupd-kpi-h'><span class='ak-kicker'>{debupd_kpi_updates}</span><i class='fas fa-box-archive debupd-kpi-i'></i></div>";
    $h.="<div class='debupd-kpi-row'><span class='debupd-kpi-v'>$value</span><span class='debupd-kpi-s'>$sub</span></div>";
    return $h."</div>";
}

// 2b. Tuile « correctifs de securite » : rouge seulement s'il y en a.
function debupd_kpi_security($m):string{
    if($m["state"]=="unknown"){
        $cls="";$value="&#8212;";$sub="{debupd_kpi_updates_unknown}";
    }elseif($m["security"]>0){
        $cls=" debupd-kpi-err";$value=$m["security"];$sub="{debupd_kpi_sec_sub}";
    }else{
        $cls=" debupd-kpi-ok";$value="0";$sub="{debupd_kpi_sec_none}";
    }
    $h ="<div class='ak-card debupd-kpi$cls'>";
    $h.="<div class='debupd-kpi-h'><span class='ak-kicker'>{debupd_kpi_security}</span><i class='fas fa-shield-exclamation debupd-kpi-i'></i></div>";
    $h.="<div class='debupd-kpi-row'><span class='debupd-kpi-v'>$value</span><span class='debupd-kpi-s'>$sub</span></div>";
    return $h."</div>";
}

// 2c. Tuile « systeme d'exploitation » : nom de l'OS et noyau en service.
function debupd_kpi_os($m):string{
    $os=$m["os"]==""?"{unknown}":htmlspecialchars($m["os"]);
    $h ="<div class='ak-card debupd-kpi'>";
    $h.="<div class='debupd-kpi-h'><span class='ak-kicker'>{debupd_kpi_os}</span><i class='fas fa-microchip debupd-kpi-i'></i></div>";
    $h.="<div class='debupd-os'>$os</div>";
    $h.="<div class='debupd-os-k'><span class='ak-chip'>".htmlspecialchars($m["kernel"])."</span> <span class='debupd-kpi-s'>{debupd_running_kernel}</span></div>";
    return $h."</div>";
}

function debupd_kpis($m):string{
    return "<div class='ak-grid-3 debupd-gap'>".debupd_kpi_updates($m).debupd_kpi_security($m).debupd_kpi_os($m)."</div>";
}

// 3. Bande de maintenance : les paquets perimes n'ont RIEN a voir avec les mises
// a jour en attente -- un systeme parfaitement a jour traine tres bien cinq
// anciens noyaux. Elle s'affiche donc dans les trois etats.
function debupd_obsolete_strip($m):string{
    if($m["obsolete"]<=0){return "";}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $lead=str_replace("%s",$m["obsolete"],$tpl->_ENGINE_parse_body("{debupd_obsolete_lead}"));
    $open="Loadjs('$page?purge-obsolete-js=yes');";
    $h ="<div class='debupd-strip debupd-gap'>";
    $h.="<div class='ak-ico ak-ico-sm ak-ico-sq ak-off'><i class='fas fa-broom'></i></div>";
    $h.="<div class='debupd-strip-m'><strong>$lead</strong> <span class='ak-muted'>{debupd_obsolete_what}</span>";
    if($m["purge_error"]<>""){
        $h.="<div class='debupd-strip-err'><i class='fas fa-circle-exclamation'></i> {debupd_last_cleanup_failed} &#8212; "
            ."<a href=\"javascript:blur();\" OnClick=\"$open\">{debupd_see_details}</a></div>";
    }
    $h.="</div>";
    if($m["admin"]){
        $h.="<button type='button' class='ak-btn debupd-btn-danger' OnClick=\"$open\"><i class='fas fa-trash-can'></i> {debupd_review_cleanup}</button>";
    }
    return $h."</div>";
}

// 4a. Carte de methode « correctifs » (recommandee) : met a jour EXACTEMENT les
// paquets de la liste, un par un (APUpgradeSoftwares). Le nombre est donc juste.
function debupd_method_fixes($m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $n=count($m["rows"]);
    $text=str_replace("%s",$n,$tpl->_ENGINE_parse_body("{debupd_fixes_text}"));
    $btn=str_replace("%s",$n,$tpl->_ENGINE_parse_body("{debupd_install_n}"));
    $h ="<div class='debupd-method debupd-method-rec'>";
    $h.="<span class='debupd-rec'>{debupd_recommended}</span>";
    $h.="<div class='debupd-method-t'>{debupd_fixes_title}</div>";
    $h.="<div class='debupd-method-s'>{update_software_packages}</div>";
    $h.="<div class='debupd-method-p'>$text</div>";
    $h.="<div class='debupd-method-f'>";
    if($m["admin"]){
        $h.="<button type='button' class='ak-btn ak-btn-primary ak-btn-lg' OnClick=\"Loadjs('$page?update-system-js=yes');\"><i class='fas fa-download'></i> $btn</button>";
    }else{
        $h.="<span class='ak-note'><i class='fas fa-lock'></i> {debupd_admin_only}</span>";
    }
    $h.="</div></div>";
    return $h;
}

// 4b. Carte « mise a niveau complete » (dist-upgrade) : peut ajouter ou retirer
// des paquets, donc aucun nombre sur le bouton. Le redemarrage n'est requis QUE
// si un nouveau noyau est installe : la maquette disait « Requires appliance
// reboot » sans condition, ce qui est faux.
function debupd_method_full($m):string{
    $page=CurrentPageName();
    $h ="<div class='debupd-method'>";
    $h.="<div class='debupd-method-t'>{debupd_full_title}</div>";
    $h.="<div class='debupd-method-s'>{pkg_upgrade_system} (dist-upgrade)</div>";
    $h.="<div class='debupd-method-p'>{debupd_full_text}</div>";
    $h.="<div class='debupd-method-f'>";
    if($m["admin"]){
        $h.="<button type='button' class='ak-btn ak-btn-lg debupd-btn-o' OnClick=\"Loadjs('$page?upgrade-system-js=yes');\"><i class='fas fa-layer-group'></i> {debupd_run_full}</button>";
        $h.="<span class='ak-note'>{debupd_full_note}</span>";
    }else{
        $h.="<span class='ak-note'><i class='fas fa-lock'></i> {debupd_admin_only}</span>";
    }
    $h.="</div></div>";
    return $h;
}

function debupd_methods($m):string{
    if($m["state"]<>"pending"){return "";}
    $h ="<div class='ak-card ak-card-pad debupd-gap'>";
    $h.="<div class='debupd-sec-t'>{debupd_methods_title}</div>";
    $h.="<div class='debupd-sec-s'>{debupd_methods_sub}</div>";
    $h.="<div class='debupd-methods'>".debupd_method_fixes($m).debupd_method_full($m)."</div>";
    $h.="<div class='debupd-info'><i class='fas fa-circle-info'></i> {debupd_progress_note}</div>";
    return $h."</div>";
}

// 5. Une ligne = un paquet. La description arrive d'apt sous la forme
// « resume<br>texte long » : contenu AMONT, chaque morceau est echappe. Le texte
// long est borne a deux lignes PAR LA CSS : le filtre continue de chercher dedans.
function debupd_description($desc):string{
    $desc=str_replace("Description-en:","",(string)$desc);
    $lines=array();
    foreach(explode("<br>",$desc) as $p){
        $p=trim($p);
        if($p=="" || $p=="."){continue;}
        $lines[]=htmlspecialchars($p);
    }
    if(count($lines)==0){return "";}
    $summary=array_shift($lines);
    $h="<div class='debupd-sumline'>$summary</div>";
    if(count($lines)>0){$h.="<div class='debupd-desc'>".implode(" ",$lines)."</div>";}
    return $h;
}

// Ancienne version barree, nouvelle accentuee. Sans version candidate (daemon
// anterieur, ou paquet NOUVEAU que le dist-upgrade ajoute), une seule version,
// non barree : pas de fleche vers rien.
function debupd_version_cell($current,$candidate):string{
    $current=trim((string)$current);
    $candidate=trim((string)$candidate);
    if($candidate=="" || $candidate==$current){
        return "<div class='debupd-vers'><span class='debupd-vl'>{debupd_version}</span> <span class='debupd-new'>".htmlspecialchars($current)."</span></div>";
    }
    $h="<div class='debupd-vers'>";
    if($current<>""){
        $h.="<div><span class='debupd-vl'>{debupd_installed}</span> <span class='debupd-old'>".htmlspecialchars($current)."</span></div>";
    }
    $h.="<div><span class='debupd-vl'>{debupd_new}</span> <span class='debupd-new'><i class='fas fa-angles-up'></i> ".htmlspecialchars($candidate)."</span></div>";
    return $h."</div>";
}

function debupd_row($r):string{
    $tags="";
    if($r["security"]){
        $tags.="<span class='ak-tag ak-err'><i class='fas fa-shield'></i> {security}</span>";
    }else{
        $tags.="<span class='ak-tag ak-off'>{debupd_standard}</span>";
    }
    if(trim($r["section"])<>""){
        $tags.="<span class='ak-tag debupd-sect' title=\"{debupd_section_title}\">".htmlspecialchars($r["section"])."</span>";
    }
    $h ="<tr data-pkg='1' data-sec='".($r["security"]?"1":"0")."'>";
    $h.="<td><div class='debupd-pkgline'><span class='debupd-pkg'>".htmlspecialchars($r["name"])."</span>$tags</div>";
    $h.=debupd_description($r["desc"])."</td>";
    $h.="<td class='debupd-vcell'>".debupd_version_cell($r["current"],$r["candidate"])."</td>";
    return $h."</tr>";
}

// 6. La liste. Filtre segmente (Tout / Securite seulement) + recherche + pager,
// tous trois pilotes par UN seul script (debupd_js) : l'ancien couple
// awc_pager_js/awc_filter_js ne savait pas combiner un second critere.
function debupd_list($m):string{
    if($m["state"]<>"pending"){return "";}
    $n=count($m["rows"]);
    $sec=$m["security"];
    $rows=array();
    foreach($m["rows"] as $r){$rows[]=debupd_row($r);}
    $secDisabled=$sec==0?" disabled":"";

    $h ="<div class='ak-card debupd-gap debupd-list'>";
    $h.="<div class='debupd-list-h'>";
    $h.="<div class='debupd-list-t'>{debupd_list_title} <span class='ak-count'>$n</span></div>";
    $h.="<div class='debupd-list-tools'>";
    $h.="<div class='ak-seg debupd-seg' id='debupd-seg' role='radiogroup' aria-label=\"{debupd_list_title}\">";
    $h.="<button type='button' class='ak-seg-b on' role='radio' aria-checked='true' data-mode='all'>{debupd_all} ($n)</button>";
    $h.="<button type='button' class='ak-seg-b' role='radio' aria-checked='false' data-mode='sec'$secDisabled>{debupd_security_only} ($sec)</button>";
    $h.="</div>";
    $h.="<label class='ak-search debupd-search'><i class='fas fa-magnifying-glass'></i><input type='text' id='debupd-q' placeholder=\"{search_package}\" aria-label=\"{search_package}\"></label>";
    $h.="</div></div>";
    $h.="<table class='ak-tbl debupd-tbl'><thead><tr><th>{package}</th><th class='ak-end'>{debupd_version}</th></tr></thead>";
    $h.="<tbody id='debupd-rows'>".implode("\n",$rows)."</tbody></table>";
    $h.="<div class='ak-empty-c debupd-nomatch' id='debupd-nomatch' hidden><i class='fas fa-filter-circle-xmark'></i>"
        ."<div>{debupd_no_match}</div><button type='button' class='ak-link' OnClick=\"DebupdClearFilter();\">{debupd_clear_filter}</button></div>";
    $h.="<div class='ak-card-f debupd-foot'><span id='debupd-shown'></span><span class='ak-pg' id='debupd-pager'></span></div>";
    return $h."</div>";
}

// 7. Etat « a jour » ou « jamais verifie ». Deux textes differents : le second
// est une invitation, pas une coche verte.
function debupd_empty_state($m):string{
    if($m["state"]=="pending"){return "";}
    $when=$m["index_time"]>0?date("Y-m-d H:i",$m["index_time"]):"";
    $age=htmlspecialchars(debupd_age($m["index_time"]));
    $h ="<div class='ak-card ak-card-pad debupd-gap debupd-empty'>";
    if($m["state"]=="uptodate"){
        $h.="<i class='fas fa-circle-check debupd-empty-ok'></i>";
        $h.="<div class='debupd-empty-t'>{debupd_uptodate_title}</div>";
        $h.="<div class='debupd-empty-p'>{no_pending_upgrade_explain}</div>";
    }else{
        $h.="<i class='fas fa-magnifying-glass debupd-empty-q'></i>";
        $h.="<div class='debupd-empty-t'>{debupd_unknown_title}</div>";
        $h.="<div class='debupd-empty-p'>{debupd_unknown_text}</div>";
    }
    $h.="<div class='debupd-empty-p' title='".htmlspecialchars($when)."'>{debupd_repos_read}: $age";
    if(!$m["disabled"]){$h.=" &#183; {debupd_next_check}";}
    $h.="</div>";
    $h.="<div class='debupd-empty-a'>".debupd_check_button($m,"ak-btn-lg")."</div>";
    return $h."</div>";
}

// 8. D'ou viennent les mises a jour. Le noyau, deja dans la tuile OS, n'y est
// pas repete.
function debupd_source_card($icon,$label,$value,$sub,$tag=""):string{
    $h ="<div class='ak-card debupd-src'>";
    $h.="<div class='ak-ico ak-ico-sm ak-ico-sq ak-off'><i class='$icon'></i></div>";
    $h.="<div class='debupd-src-b'><div class='debupd-src-l'>$label $tag</div>";
    $h.="<div class='debupd-src-v'>$value</div><div class='debupd-src-s'>$sub</div></div>";
    return $h."</div>";
}
function debupd_source_mirror($m):string{
    $host=$m["mirror"]==""?"{unknown}":htmlspecialchars($m["mirror"]);
    return debupd_source_card("fas fa-globe","{debupd_source}",$host,"{apt_mirror}");
}
function debupd_source_index($m):string{
    $t=$m["index_time"];
    if($t<=0){
        return debupd_source_card("fas fa-clock","{debupd_repos_check}","{never}","{apt_last_index}",
            "<span class='ak-tag ak-warn'>{debupd_run_check}</span>");
    }
    $age=time()-$t;
    $note=$age<86400?"{today}":str_replace("%s",intval($age/86400),"%s {debupd_days}");
    $tone=debupd_index_stale($t)?"ak-warn":"ak-off";
    return debupd_source_card("fas fa-clock","{debupd_repos_check}",date("Y-m-d H:i",$t),"{apt_last_index}",
        "<span class='ak-tag $tone'>$note</span>");
}
function debupd_sources($m):string{
    return "<div class='ak-grid-2 debupd-gap'>".debupd_source_mirror($m).debupd_source_index($m)."</div>";
}

// ---------------------------------------------------------------------------
// Feuille de style propre a la page (maquette stitch_artica_os_update_console_redesign).
// Le gros du travail vient du kit ak-* (angular.css.php). Emise HORS de
// _ENGINE_parse_body() : sa regex #{(.+?)}# prendrait chaque regle pour un jeton.
function debupd_style():string{
    if(isset($GLOBALS["DEBUPD_CSS_DONE"])){return "";}
    $GLOBALS["DEBUPD_CSS_DONE"]=true;
    $acc="#005447";$accDark="#003d33";$soft="#e6eeed";$line="#bfd4d1";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$acc=$a["accent"];}
        if(!empty($a["accent_dark"])){$accDark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    $f="ui-monospace,SFMono-Regular,Menlo,Consolas,monospace";
    return "<style>
.debupd{max-width:1320px}
.debupd-gap{margin-top:16px}
.debupd-hero{display:flex;align-items:center;gap:20px;padding:22px 24px;border-left:4px solid $acc}
.debupd-hero-main{flex:1 1 auto;min-width:0}
.debupd-hero-t{font-size:20px;font-weight:600;color:#0f172a;margin-bottom:6px}
.debupd-hero-p{font-size:14px;color:#475569;line-height:1.55;max-width:780px}
.debupd-chips{margin-top:12px}
.debupd-chip{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;background:$soft;color:$acc;font-size:13px;font-weight:600}
.debupd-hero-side{display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex:0 0 auto}
.debupd-hero-s{font-size:13px;color:#64748b;cursor:help}
.debupd-btn-o{border-color:$line;color:$acc}
.debupd-btn-o:hover{background:$soft;color:$accDark}
.debupd-btn-danger{border-color:#fecaca;color:#b91c1c;background:#fff}
.debupd-btn-danger:hover{background:#fef2f2;color:#b91c1c}
.debupd-kpi{padding:18px 20px;min-height:112px}
.debupd-kpi-h{display:flex;justify-content:space-between;align-items:flex-start}
.debupd-kpi-i{font-size:16px;color:#94a3b8}
.debupd-kpi-row{display:flex;align-items:baseline;gap:14px;margin-top:10px}
.debupd-kpi-v{font-size:32px;font-weight:700;line-height:1;color:#0f172a}
.debupd-kpi-s{font-size:13px;color:#64748b}
.debupd-kpi-err{border-color:#fecaca;background:#fffafa}
.debupd-kpi-err .ak-kicker,.debupd-kpi-err .debupd-kpi-v,.debupd-kpi-err .debupd-kpi-i{color:#b91c1c}
.debupd-kpi-ok .debupd-kpi-v{color:#047857}
.debupd-os{font-size:16px;font-weight:600;color:#0f172a;margin-top:8px}
.debupd-os-k{margin-top:6px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.debupd-strip{display:flex;align-items:center;gap:14px;padding:14px 18px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc}
.debupd-strip-m{flex:1 1 auto;min-width:0;font-size:14px;color:#0f172a}
.debupd-strip-err{margin-top:4px;font-size:13px;color:#b91c1c}
.debupd-strip-err a{color:#b91c1c;text-decoration:underline}
.debupd-sec-t{font-size:16px;font-weight:600;color:#0f172a}
.debupd-sec-s{font-size:13px;color:#64748b;margin-top:2px}
.debupd-methods{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:18px}
.debupd-method{position:relative;display:flex;flex-direction:column;border:1px solid #e2e8f0;border-radius:10px;padding:20px}
.debupd-method-rec{border:2px solid $acc}
.debupd-rec{position:absolute;top:-11px;right:16px;background:$acc;color:#fff;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:2px 10px;border-radius:999px}
.debupd-method-t{font-size:16px;font-weight:600;color:#0f172a}
.debupd-method-s{font-size:13px;color:#64748b;margin-top:2px}
.debupd-method-p{font-size:14px;color:#334155;line-height:1.6;margin-top:12px;flex:1 1 auto}
.debupd-method-f{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border-top:1px solid #f1f5f9;margin-top:16px;padding-top:16px}
.debupd-info{display:flex;align-items:center;gap:8px;margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;font-size:13px;color:#475569}
.debupd-list{overflow:hidden}
.debupd-list-h{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid #e2e8f0}
.debupd-list-t{font-size:16px;font-weight:600;color:#0f172a;display:flex;align-items:center;gap:10px}
.debupd-list-tools{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.debupd-seg .ak-seg-b.on{background:$acc;color:#fff}
.debupd-seg .ak-seg-b[disabled]{opacity:.5;cursor:not-allowed}
.debupd-search{width:320px}
.debupd-tbl td{vertical-align:top;padding-top:14px;padding-bottom:14px}
.debupd-pkgline{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.debupd-pkg{font-family:$f;font-size:13px;font-weight:700;color:#0f172a;word-break:break-all}
.debupd-sect{cursor:help}
.debupd-sumline{font-size:13px;color:#334155;margin-top:4px}
.debupd-desc{font-size:13px;color:#64748b;line-height:1.5;margin-top:2px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.debupd-vcell{text-align:right;white-space:nowrap;width:1%}
.debupd-vers{line-height:1.7}
.debupd-vl{font-size:13px;color:#94a3b8;text-transform:uppercase;letter-spacing:.03em}
.debupd-old{font-family:$f;font-size:13px;color:#94a3b8;text-decoration:line-through}
.debupd-new{font-family:$f;font-size:13px;font-weight:700;color:$acc}
.debupd-new svg,.debupd-new i{font-size:11px}
.debupd-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 20px;font-size:13px;color:#475569}
.debupd-nomatch{padding:28px}
.debupd-empty{text-align:center;padding:36px 24px}
.debupd-empty-ok{font-size:48px;color:#047857}
.debupd-empty-q{font-size:44px;color:#94a3b8}
.debupd-empty-t{font-size:18px;font-weight:600;color:#0f172a;margin:14px 0 6px 0}
.debupd-empty-p{font-size:14px;color:#64748b;max-width:620px;margin:4px auto 0 auto}
.debupd-empty-a{margin-top:18px}
.debupd-src{display:flex;align-items:center;gap:14px;padding:14px 18px}
.debupd-src-b{min-width:0}
.debupd-src-l{font-size:13px;color:#64748b;display:flex;align-items:center;gap:8px}
.debupd-src-v{font-family:$f;font-size:13px;font-weight:700;color:#0f172a;overflow-wrap:anywhere}
.debupd-src-s{font-size:13px;color:#94a3b8}
.debupd .ak-btn-primary{background:$acc;border-color:$acc}
.debupd .ak-btn-primary:hover{background:$accDark;border-color:$accDark}
.debupd button:focus-visible{outline:2px solid $acc;outline-offset:2px}
@media(max-width:1200px){.debupd-methods{grid-template-columns:1fr}.debupd-hero{flex-wrap:wrap}.debupd-hero-side{align-items:flex-start}}
@media(prefers-reduced-motion:no-preference){.debupd-tbl tbody tr{transition:background .15s}}
</style>";
}

// Filtre segmente + recherche + pagination, en une seule passe : les lignes
// visibles sont celles qui passent les DEUX filtres, puis on en coupe une page.
// Les libelles sont traduits en PHP et poses en innerHTML (les accents du
// dictionnaire sont des entites HTML).
function debupd_js():string{
    $tpl=new template_admin();
    $showing=json_encode($tpl->_ENGINE_parse_body("{debupd_showing}"));
    return "<script>
(function(){
 var tb=document.getElementById('debupd-rows'); if(!tb){return;}
 var inp=document.getElementById('debupd-q'), box=document.getElementById('debupd-pager');
 var shown=document.getElementById('debupd-shown'), nomatch=document.getElementById('debupd-nomatch');
 var tpl=$showing, size=25, cur=1, mode='all', rows=[];
 for(var i=0;i<tb.rows.length;i++){ if(tb.rows[i].getAttribute('data-pkg')){rows.push(tb.rows[i]);} }
 function match(tr,q){
  if(mode=='sec' && tr.getAttribute('data-sec')!='1'){return false;}
  if(q==''){return true;}
  return (tr.textContent||'').toLowerCase().indexOf(q)>=0;
 }
 function draw(){
  var q=inp?inp.value.replace(/^\\s+|\\s+\$/g,'').toLowerCase():'';
  var vis=[];
  for(var i=0;i<rows.length;i++){ if(match(rows[i],q)){vis.push(rows[i]);} rows[i].style.display='none'; }
  var pages=Math.max(1,Math.ceil(vis.length/size)); if(cur>pages){cur=pages;}
  var from=(cur-1)*size, to=Math.min(vis.length,from+size);
  for(var j=from;j<to;j++){ vis[j].style.display=''; }
  if(nomatch){ nomatch.hidden=(vis.length>0); }
  if(shown){ shown.innerHTML=vis.length==0?'':tpl.replace('%1',from+1).replace('%2',to).replace('%3',vis.length); }
  if(!box){return;}
  if(pages<=1){box.innerHTML='';return;}
  var h='<a href=\"javascript:blur();\" class=\"'+(cur==1?'off':'')+'\" data-p=\"'+(cur-1)+'\" aria-label=\"&lsaquo;\">&lsaquo;</a>';
  var end=Math.min(pages,cur+2), start=Math.max(1,end-4); end=Math.min(pages,start+4);
  for(var p=start;p<=end;p++){ h+='<a href=\"javascript:blur();\" class=\"'+(p==cur?'cur':'')+'\" data-p=\"'+p+'\">'+p+'</a>'; }
  h+='<a href=\"javascript:blur();\" class=\"'+(cur==pages?'off':'')+'\" data-p=\"'+(cur+1)+'\" aria-label=\"&rsaquo;\">&rsaquo;</a>';
  box.innerHTML=h;
  var as=box.getElementsByTagName('a');
  for(var k=0;k<as.length;k++){ as[k].onclick=function(){ var p=parseInt(this.getAttribute('data-p'),10); if(p>=1&&p<=pages&&p!=cur){cur=p;draw();} return false; }; }
 }
 var seg=document.getElementById('debupd-seg');
 if(seg){
  var bs=seg.getElementsByTagName('button');
  for(var b=0;b<bs.length;b++){
   bs[b].onclick=function(){
    if(this.disabled){return;}
    for(var c=0;c<bs.length;c++){ bs[c].className='ak-seg-b'; bs[c].setAttribute('aria-checked','false'); }
    this.className='ak-seg-b on'; this.setAttribute('aria-checked','true');
    mode=this.getAttribute('data-mode'); cur=1; draw();
   };
  }
 }
 if(inp){ inp.onkeyup=function(){cur=1;draw();}; inp.onchange=inp.onkeyup; }
 window.DebupdClearFilter=function(){
  if(inp){inp.value='';}
  if(seg){ var bs=seg.getElementsByTagName('button'); if(bs.length){ bs[0].onclick(); return; } }
  cur=1; draw();
 };
 draw();
})();
</script>";
}

// ---------------------------------------------------------------------------
// Le TINY rend les elements d'en-tete (titre, icone, explication, boutons) dans
// les div #tiny-* poses par page_header.
//
// Il est VOLONTAIREMENT supprime en mode dialogue (?byjs=yes) : tiny-page ecrit
// dans #tiny-button, qui n'existe que sur la page porteuse. Emis depuis une
// modale, il detourne l'en-tete de la page qui est DERRIERE.
//
// Les actions vivent desormais dans le corps (bandeau, cartes de methode) : le
// TINY ne garde que le rafraichissement de l'affichage.
function debupd_tiny($m):string{
    if(isset($_GET["byjs"])){return "";}
    $tpl=new template_admin();
    $page=CurrentPageName();

    $expl="{debupd_header_explain}";
    $count=count($m["rows"]);
    if($count>0){
        $expl="$count {upgradable_packages}";
        if($m["security"]>0){$expl.=" &#8212; {$m["security"]} {security_updates}";}
    }
    $topbuttons=array();
    $topbuttons[]=array("LoadAjaxSilent('debian-system-updates','$page?table-main=yes');",ico_refresh,"{refresh}");

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{OS} {update}";
    $TINY_ARRAY["ICO"]="fa-duotone fa-box-open";
    $TINY_ARRAY["EXPL"]=$expl;
    $TINY_ARRAY["URL"]="os-update";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

function table_main(){
    $tpl=new template_admin();
    $m=debupd_model();

    $html=array();
    $html[]="<div class='ak debupd'>";
    $html[]="<div id='".debupd_progress_div()."'></div>";
    $html[]=debupd_banner($m);
    $html[]=debupd_disabled_alert($m);
    $html[]=debupd_kpis($m);
    $html[]=debupd_obsolete_strip($m);
    $html[]=debupd_methods($m);
    $html[]=debupd_list($m);
    $html[]=debupd_empty_state($m);
    $html[]=debupd_sources($m);
    $html[]="</div>";

    $js="<script>NoSpinner();\n";
    // L'ancienne page vidait cette div a chaque rendu ; conserve, elle appartient
    // au bandeau de mise a jour qui englobe la page.
    $js.="if(document.getElementById('header-update-page')){document.getElementById('header-update-page').innerHTML='';}\n";
    $js.=debupd_tiny($m)."\n</script>";

    // CSS et JS concatenes AUTOUR de l'analyse : la regex #{(.+?)}# du moteur
    // prend chaque corps de regle CSS et chaque bloc JavaScript pour un {jeton}.
    echo debupd_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$js
        .debupd_js();
}
