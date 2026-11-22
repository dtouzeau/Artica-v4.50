<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();

function xgen(){
	$tpl=new template_admin();

    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }
    if(isset($_SERVER["HTTP_X_META_PREFIX"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_META_PREFIX"]."/";
    }


    $f[]="<ul class='nav nav-third-level'>";


    $f[] = $tpl->LeftMenu(array(
            "PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.system.cve.php",
            "ICO" => "fas fa-bullseye-pointer",
            "TEXT" => "CVE")
    );

    $f[] = $tpl->LeftMenu(array(
            "PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.system.aide.php",
            "ICO" => "fas fa-shield-halved",
            "TEXT" => "AIDE")
    );
    $f[] = $tpl->LeftMenu(array(
            "PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.auditd.php",
            "ICO" => "fas fa-user-shield",
            "TEXT" => "Auditd")
    );

    // Second facteur TOTP de la console. Sans cette entree, fw.system.2fa.php
    // n'etait accessible qu'en tapant son URL: la mitigation du §12 de la
    // conception -- "les cles d'API contournent le second facteur, donc la page
    // les RENDS VISIBLES" -- ne mitigeait rien, et l'interrupteur de politique
    // comme la reinitialisation d'une identite etaient tout aussi introuvables.
    // L'icone est celle de la page elle-meme (page_header), le jeton celui de
    // son titre.
    $f[] = $tpl->LeftMenu(array(
            "PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.system.2fa.php",
            // fa-user-lock et non fa-shield-halved: AIDE, deux lignes plus
            // haut dans ce meme sous-menu, porte deja fa-shield-halved. Deux
            // icones identiques sur quatre entrees annulent ce a quoi sert une
            // icone dans un menu. Meme icone que page_header() de la page.
            "ICO" => "fas fa-user-lock",
            // Jeton COURT, distinct du titre de la page. Mesure sur appliance
            // dans ce menu large de 220px: "Two-factor authentication" passe
            // sur deux lignes (51,1px de haut contre 32,6px pour CVE, AIDE et
            // Auditd) et casse l'alignement de la colonne. Un jeton -- et non
            // le litteral "2FA" -- pour qu'une langue qui prefere une autre
            // abreviation (A2F, 2FA...) puisse la donner.
            "TEXT" => "{console_2fa_menu}")
    );

	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}