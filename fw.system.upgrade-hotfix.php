<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// awc_table_style(): feuille de style partagee des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["jsafter"])){jsafter();exit;}
js();

function jsafter(){

    header("content-type: application/x-javascript");
    $f[]="if(document.getElementById('table-loader-versions-service')){";
    $f[]="\tLoadAjax('table-loader-versions-service','fw.versions.php?table=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('main-dashboard-status')){";
    $f[]="\tLoadAjaxSilent('main-dashboard-status','fw.system.status.php');";
    $f[]="}";
    $f[]="if(document.getElementById('unbound-status')){";
    $f[]="LoadAjaxSilent('unbound-status','fw.dns.unbound.php?unbound-status=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('dnsdist-left')){";
    $f[]="LoadAjax('dnsdist-left','fw.dns.unbound.php?dnsdist-status-left=yes');";
    $f[]="}";
    $f[]="Loadjs('fw.icon.top.php')";
    $f[]="dialogInstance11.close();";
    echo @implode("\n",$f)."\n";

}

function js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	return $tpl->js_dialog11("{new_available_hotfix}", "$page?popup=yes",670);
}


function popup():bool{
	$tpl  = new template_admin();
	$page = CurrentPageName();

	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/artica/hotfix/devs"));
	if (json_last_error()> JSON_ERROR_NONE) {
		echo $tpl->_ENGINE_parse_body($tpl->div_error(json_last_error_msg()));
		return false;   // la fonction est declaree :bool et rendait la chaine vide
	}
	// L'ancienne version parcourait $json->Hotfixes sans aucune verification, puis
	// appelait krsort($MAIN) sur une variable jamais definie quand la liste etait
	// vide: deux erreurs fatales en PHP 8.
	$hotfixes=(is_object($json) && isset($json->Hotfixes) && is_array($json->Hotfixes))?$json->Hotfixes:array();

	$MAIN=array();
	foreach ($hotfixes as $class) {
		if(!is_object($class) || !isset($class->versionBin)){continue;}
		$MAIN[(string)$class->versionBin]=array(
			"version"=>(string)($class->version ?? ""),
			"size"   =>(string)($class->size ?? ""),
			"url"    =>(string)($class->url ?? ""),
			"time"   =>intval($class->time ?? 0));
	}
	krsort($MAIN);

	// L'identifiant de la div de progression etait bati sur $_GET["popup"], donc
	// sur une valeur d'URL qui repartait BRUTE dans un attribut id ET dans le
	// JavaScript construit par framework_buildjs(). Un prefixe fixe suffit: cette
	// page n'affiche qu'une seule liste.
	$html=array();
	$html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{version}</th>";
	$html[]="<th class='center'>{size}</th>";
	$html[]="<th class='center'>&nbsp;</th>";
	$html[]="</tr></thead><tbody>";

	if(count($MAIN)==0){
		$html[]="<tr><td colspan='3' class='awc-empty'>{no_data}</td></tr>";
	}
	foreach ($MAIN as $vbin=>$main){
		$html[]=hotfix_row($tpl,$page,$vbin,$main);
	}

	$html[]="</tbody></table></div></div></div>";
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}

/**
 * Un correctif disponible, suivi de sa propre barre de progression.
 * L'ancienne version fermait la boite par dialogInstance2.close() alors que
 * cette page l'ouvre avec js_dialog11: dialogInstance2 n'existant pas, l'appel
 * levait une ReferenceError qui interrompait le bloc AVANT le Loadjs(jsafter)
 * -- rien n'etait rafraichi apres l'installation d'un correctif.
 * Le nombre de colonnes du colspan etait par ailleurs 5 pour 4 cellules.
 */
function hotfix_row($tpl,$page,$vbin,array $main):string{
	$divid="hotfix-".preg_replace('/[^A-Za-z0-9_.-]/','_',(string)$vbin)."-progress-install";
	$jsrestart=$tpl->framework_buildjs("/system/artica/hotfix/update/devs/".rawurlencode($vbin),
		"system.hotfix.progress","system.hotfix.progress.txt",
		$divid,"Loadjs('$page?jsafter=yes');");
	$bton=$tpl->button_autnonome("{install_upgrade}",$jsrestart,
		"fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

	// L'ancienne version collait un "s" a la suite de la date: time_to_date()
	// etait suivi de la chaine litterale "s", vestige d'une interpolation.
	$h ="<tr>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-download'></i></span>";
	$h.="<span><div class='awc-name'><a href=\"".htmlspecialchars($main["url"])."\">"
	   .htmlspecialchars($main["version"])."</a></div>";
	$h.="<div class='awc-meta'>".$tpl->time_to_date($main["time"],true)."</div></span>";
	$h.="</div></td>";
	$h.="<td class='center awc-nowrap awc-mono'>".htmlspecialchars($main["size"])."</td>";
	$h.="<td class='center awc-nowrap'>$bton</td>";
	$h.="</tr>";
	$h.="<tr><td colspan='3'><div style='width:100%;margin:5px 0' id='$divid'></div></td></tr>";
	return $h;
}


function file_uploaded(){
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    // $_GET["product"] et $_GET["key"] partaient BRUTS dans un chemin REST puis
    // dans l'identifiant d'une div injecte en JavaScript.
    $product=preg_replace('/[^A-Za-z0-9_.-]/','',(string)($_GET["product"] ?? ""));
    $key=preg_replace('/[^A-Za-z0-9_.-]/','',(string)($_GET["key"] ?? ""));
    $file=urlencode(base64_encode("/usr/share/artica-postfix/ressources/conf/upload/"
        .basename((string)$_GET["file-uploaded"])));
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.installsoft.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.installsoft.progress.txt";
    $ARRAY["CMD"]="/system/softwares/install-local/$product/$key/$file";
    $ARRAY["TITLE"]="{installing} $product";
    $ARRAY["AFTER"]="LoadAjax('table-loader-versions-service','fw.versions.php?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    echo "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=$product-$key-progress-install')";
}


