<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["main"])){main();exit;}
if(isset($_GET["newitem-js"])){newitem_js();exit;}
if(isset($_GET["new-item-popup"])){newitem_popup();exit;}
if(isset($_POST["newitem"])){newitem_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){newitem_delete();exit;}
page();



function newitem_js(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_item}");
	$tpl->js_dialog($title, "$page?new-item-popup=yes");
	
}

function delete_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$item=$_GET["delete-js"];
	// js_confirm_delete pose lui-meme le content-type et ne declenche son $jsafter que
	// si la reponse du POST est vide: newitem_delete() n'echo rien en cas de succes.
	// On recharge la table entiere (le compteur du pied et le pager doivent suivre).
	return $tpl->js_confirm_delete($item,"delete",$item,
		"LoadAjax('table-loader-recursor','$page?main=yes');");
}



function newitem_popup(){
		$page=CurrentPageName();
		$tpl=new template_admin();
        $form[]=$tpl->field_text("newitem", "{network_item}", null);
		$html[]=$tpl->form_outside("{new_item}", @implode("\n", $form),"{pdns_network_item_add}","{add}",
				"LoadAjax('table-loader-recursor','$page?main=yes');BootstrapDialog1.close();","AsDnsAdministrator");
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function newitem_save():bool{
    $tpl=new template_admin();
    $ActiveDirectoryRestRestrict=explode("\n",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestRestrict"));
    $item=url_decode_special_tool($_POST["newitem"]);
    $item=$tpl->CLEAN_BAD_CHARSNET($item);

    if($item==null){
        echo "No posted data\n";
        return false;
    }
    if(!is_array($ActiveDirectoryRestRestrict)){$ActiveDirectoryRestRestrict=array();}
    $ActiveDirectoryRestRestrict[]=$item;

    foreach ($ActiveDirectoryRestRestrict as $items){
        $newitels[$items]=$items;
    }
    $fins=array();
    foreach ($newitels as $item){
        $fins[]=$item;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryRestRestrict",@implode("\n",$fins));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reload");

    return admin_tracks("Web API service: Add new allowed node $item");

}
function newitem_delete():bool{
	$item=url_decode_special_tool($_POST["delete"]);
    $ActiveDirectoryRestRestrict=explode("\n",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestRestrict"));
    $newitels=array();
    foreach ($ActiveDirectoryRestRestrict as $items){
        $newitels[$items]=$items;
    }
    unset($newitels[$item]);
    $fins=array();
    foreach ($newitels as $item){
        $fins[]=$item;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryRestRestrict",@implode("\n",$fins));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reload");
    return admin_tracks("Web API service: Addin removing allowed node $item");
}

function page(){
	$page=CurrentPageName();
	$html="<div id='progress-recursor-restart' style='margin-top:10px'></div>
	<div id='table-loader-recursor'></div>
	<script>
	LoadAjax('table-loader-recursor','$page?main=yes');
	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
/**
 * Reseaux implicites appliques par le service quand aucune restriction n'est enregistree.
 * Ils sont affiches en lecture seule: rien n'est stocke, donc rien a supprimer.
 */
function adrest_defaults():array{
    return array("192.168.0.0/16","10.0.0.0/8","172.16.0.0/12","127.0.0.0/8");
}

/** Un reseau ouvert a tout l'Internet merite un signalement visuel. */
function adrest_is_open($cidr):bool{
    $cidr=trim($cidr);
    return ($cidr=="0.0.0.0/0" || $cidr=="::/0");
}

/**
 * Ligne de metadonnees sous l'adresse: version IP, largeur du masque, nature du reseau.
 * Volontairement en notation normalisee (IPv4, /16, RFC1918) plutot qu'en prose: ces
 * identifiants sont les memes dans toutes les langues, donc aucun token a creer.
 */
function adrest_network_meta($cidr):string{
    $cidr=trim($cidr);
    $parts=explode("/",$cidr);
    $addr=$parts[0];
    $bits=array();
    $bits[]=(strpos($addr,":")!==false)?"IPv6":"IPv4";
    if(isset($parts[1])){if(trim($parts[1])!==""){$bits[]="/".intval($parts[1]);}}
    if(preg_match('#^10\.#',$addr)){$bits[]="RFC1918";}
    elseif(preg_match('#^192\.168\.#',$addr)){$bits[]="RFC1918";}
    elseif(preg_match('#^172\.(1[6-9]|2[0-9]|3[01])\.#',$addr)){$bits[]="RFC1918";}
    elseif(preg_match('#^127\.#',$addr)){$bits[]="{loopback}";}
    elseif($addr=="::1"){$bits[]="{loopback}";}
    return @implode(" &middot; ",$bits);
}

/** Pastille d'etat: autorise, valeur par defaut, ou reseau grand ouvert. */
function adrest_badge($stored,$isopen):string{
    if($isopen){return "<span class='awc-badge adr-badge-warn'>{warning}</span>";}
    if(!$stored){return "<span class='awc-badge awc-badge-off'>{default}</span>";}
    return "<span class='awc-badge awc-badge-on'>{allowed}</span>";
}

/** Cellule d'actions: suppression uniquement pour une entree reellement enregistree. */
function adrest_actions($tpl,$cidr,$stored):string{
    $page=CurrentPageName();
    if(!$stored){return "<div class='awc-acts'></div>";}
    if(!$tpl->IsSecurity("AsDnsAdministrator")){return "<div class='awc-acts'></div>";}
    $js="Loadjs('$page?delete-js=".urlencode($cidr)."')";
    return "<div class='awc-acts'>"
        ."<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" "
        ."onclick=\"$js\"><i class='fas fa-trash-can'></i></a></div>";
}

/** Une ligne du tableau: bloc d'identite, etat, actions. */
function adrest_row($tpl,$cidr,$stored):string{
    $isopen=adrest_is_open($cidr);
    $off=$stored?"":" awc-ico-off";
    $offname=$stored?"":" awc-name-off";
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico$off'><i class='fas fa-network-wired'></i></span>";
    $h.="<span><span class='awc-name$offname'>".htmlspecialchars($cidr)."</span>";
    $h.="<div class='awc-meta'>".adrest_network_meta($cidr)."</div>";
    $h.="</span></div></td>";
    $h.="<td class='center'>".adrest_badge($stored,$isopen)."</td>";
    $h.="<td>".adrest_actions($tpl,$cidr,$stored)."</td>";
    $h.="</tr>";
    return $h;
}

/**
 * CSS propre a la page: uniquement la pastille d'alerte.
 * Le reste vient de awc_table_style() (class.statistics_appliance.inc), partage.
 * Concatene autour du parsing, jamais dedans: _ENGINE_parse_body prendrait chaque
 * corps de regle CSS pour un {token}.
 */
function adrest_style():string{
    return "<style>
.adr-badge-warn{background:#865300;color:#fff}
</style>";
}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?newitem-js=yes')",ico_plus,$new_entry);

    $resultsTemp=explode("\n",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestRestrict"));
    if(!is_array($resultsTemp)){$resultsTemp=array();}
    $results=array();
    foreach ($resultsTemp as $line){
        if(trim($line)==null){continue;}
        $results[]=$line;
    }

    VERBOSE("COunt(results)=".count($results),__LINE__);

    // Aucune restriction enregistree: le service applique ses reseaux implicites.
    // On les montre en lecture seule plutot qu'un tableau vide, qui laisserait croire
    // que l'API est ouverte a tous.
    $stored=(count($results)>0);
    $rows=array();
    if($stored){
        foreach ($results as $item){ $rows[]=adrest_row($tpl,$item,true); }
    }else{
        foreach (adrest_defaults() as $item){ $rows[]=adrest_row($tpl,$item,false); }
    }
    $total=count($rows);

    $html=array();
    if(!$stored){
        $html[]=$tpl->div_info("{PowerDNS-allow-from-default}");
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{networks}</th>";
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='awc-rows'>";
    if($total==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $total / $total {networks}</span>";
    $html[]="<span class='awc-pg' id='awc-pager'></span>";
    $html[]="</div>";
    $html[]="</div></div>";

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{SQUID_AD_RESTFULL}";
    $TINY_ARRAY["ICO"]="fad fa-monitor-heart-rate";
    $TINY_ARRAY["EXPL"]="{PowerDNS-allow-from}";
    $TINY_ARRAY["URL"]="ad-webapi";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Pas de footable ici: il injecterait ses propres classes et sa pagination.
    // NoSpinner() reste obligatoire, sinon le loader ne s'arrete jamais.
    $html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$jstiny
</script>";

    // Le <script> du pager arrive apres le tableau, donc les <tr> existent deja.
    echo awc_table_style().adrest_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .awc_pager_js(25);
}