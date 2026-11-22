<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$logfile=urlencode($_GET["logfile"]);
	$title=urlencode($_GET["title"]);
	$tpl->js_dialog($_GET["title"], "$page?popup=$logfile&title=$title");
	
}

function popup(){
	$tpl=new template_admin();
	
	if(is_file(PROGRESS_DIR."/squid-config-failed.tar.gz")){
        $form[]=$tpl->_ENGINE_parse_body("<div style='text-align:right;margin:10px'>".$tpl->button_autnonome("{support_package}","document.location.href='ressources/logs/web/squid-config-failed.tar.gz'","fas fa-ambulance",null,0,"btn-warning")."</div>");

    }
    // Pentest 2026-11-04 : le controle portait sur le chemin canonicalise mais
    // la lecture se faisait sur le chemin brut (../ passait), et le contenu du
    // fichier etait recopie tel quel dans le <textarea>.
    $path=realpath((string)$_GET["popup"]);
	// Seuls les journaux de progression sont lisibles (les sources et la config de
	// la console sont sous le meme prefixe) ; le dossier peut etre un lien symbolique.
	$okdirs=array("/usr/share/artica-postfix/ressources/logs/");
	$rl=realpath("/usr/share/artica-postfix/ressources/logs");if($rl!==false){$okdirs[]="$rl/";}
	$rl=realpath(PROGRESS_DIR);if($rl!==false){$okdirs[]="$rl/";}
	$ok=false;if($path!==false && is_file($path)){foreach($okdirs as $d){if(strpos($path,$d)===0){$ok=true;}}}
	if(!$ok){die(" Directory Traversal not permitted");}
	$form[]="<textarea style='width:100%;height:700px;overflow:auto'>".req_html(@file_get_contents($path))."</textarea>";
	
	echo @implode("\n",$form);
}

function canonicalize($address)
{
    $address = explode('/', $address);
    $keys = array_keys($address, '..');

    foreach($keys AS $keypos => $key)
    {
        array_splice($address, $key - ($keypos * 2 + 1), 2);
    }

    $address = implode('/', $address);
    $address = str_replace('./', '', $address);
    return $address;
}