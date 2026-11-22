<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style()/awc_pager_js(): la charte des tableaux d'entites. L'include
// DOIT rester INCONDITIONNEL et au premier niveau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// L'amorce de $GLOBALS["CLASS_SOCKETS"] etait ecrite DEUX fois, a l'identique.
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["refresh-statues"])){td_status();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["new-port-js"])){new_port_js();exit;}
if(isset($_GET["delete-port-js"])){delete_port_js();exit;}
if(isset($_POST["delete-port"])){delete_port_confirm();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["new-js"])){new_js();exit;}
if(isset($_GET["new-popup"])){new_popup();exit;}
if(isset($_GET["new-select"])){new_select();exit;}

// TOUT UN BLOC RECOPIE DE fw.bridges.php, mort ici de bout en bout:
//   ?rule-settings= et $_POST["ID"]  -> rule_settings()/rule_save() N'EXISTENT
//                                       PAS dans ce fichier (deux fatals).
//   ?delete-rule-js= / delete-remove -> delete_js()/delete_remove() agissent sur
//                                       pnic_bridges, une table que cette page
//                                       ne montre pas; le seul producteur de ce
//                                       parametre etait une icone de suppression
//                                       batie sur $ligne["ID"], colonne ABSENTE
//                                       du SELECT (voir table()): l'URL partait
//                                       avec un identifiant vide, donc
//                                       "WHERE ID=0", donc rien.
//   ?NoFirewall= / ?counters-js= / ?masq-sources= / $_POST["masqid"]
//                                    -> aucun de ces parametres n'est produit
//                                       nulle part, dans ce fichier ni ailleurs.
// Le tout portait cinq requetes SQL a valeurs interpolees sur une table hors
// sujet. Supprime plutot que laisse en embuscade. La suppression d'un
// commutateur n'a de toute facon aucune route cote daemon: /openvswitch/
// n'expose que install, uninstall, restart, status, metrics, port/add et
// port/delete.
if(isset($_GET["openvswtich-status"])){openvswtich_status();exit;}



page();

// Un nom de commutateur est un identifiant: il repart dans un segment d'URL
// REST, dans des identifiants du DOM et dans du JavaScript emis par td_status().
function ovs_switch_name($v):string{
    return preg_replace('#[^A-Za-z0-9_.:@-]#','',(string)$v);
}

function new_port_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Le nom de commutateur part dans un SEGMENT d'URL REST.
    $switch=ovs_switch_name((string)($_GET["new-port-js"] ?? ""));
    if($switch===""){return false;}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvswitch/port/add/".rawurlencode($switch)));
    // "->Status" sur un json_decode() rate est une TypeError FATALE en PHP 8:
    // la reponse n'est pas toujours du JSON (route absente, daemon arrete).
    if(!is_object($json) || empty($json->Status)){
        $err=(is_object($json) && isset($json->Error))?$json->Error:"{failed}";
        $tpl->js_error($err);
        return false;
    }
    admin_tracks("Open Vswitch, create a new port for $switch");
    // Un gestionnaire Loadjs qui ne rend RIEN ne dit pas si le clic a porte: le
    // port apparait dans la colonne « interfaces » au prochain rafraichissement
    // (toutes les N secondes), donc rien ne bouge a l'ecran dans l'instant et
    // l'operateur reclique. On nomme l'operation et on relance le tableau.
    $tpl->js_ok("{new_interface}","LoadAjax('table-main-table-openvswtiches','$page?table=yes');");
    return true;
}
function start():bool{
    $page=CurrentPageName();
    $t=time();
    $tpl=new template_admin();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width: 450px;vertical-align: top'><div id='openvswtich-status'></div></td>";
    $html[]="<td style='width: 99%;vertical-align: top'>";
    $html[]=$tpl->search_block($page,"","","","&table=yes");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $js=$tpl->RefreshInterval_js("openvswtich-status",$page,"openvswtich-status=yes");
    $html[]="<script>$js</script>";
    echo implode("\n",$html);
    return true;
}
function openvswtich_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/openvswitch/status");

    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }



    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);

    $jsrestart=$tpl->framework_buildjs(
        "/openvswitch/restart",
        "openvswitch.restart.progress",
        "openvswitch.restart.log",
        "openvswtich-restart"
    );
    $jshelp="s_PopUpFull('https://wiki.articatech.com/ssh/sshproxy',1024,768,'SSH Proxy')";

    echo  $tpl->SERVICE_STATUS($bsini, "APP_OPENVSWITCH",$jsrestart);
/*
    $topbuttons[]=array($jsrestart,ico_retweet,"{reconfigure_service}");
    $topbuttons[]=array($jshelp,"fas fa-question-circle","WIKI");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{APP_SSHPROXY}";
    $TINY_ARRAY["ICO"]=ico_terminal;
    $TINY_ARRAY["EXPL"]="{APP_SSHPROXY_EXPLAIN}";
    $TINY_ARRAY["URL"]="sshproxy";

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
*/
}
function delete_port_js():bool{
    $tpl = new template_admin();
    $switch = $_GET["delete-port-js"];
    $iface = $_GET["iface"];
    return $tpl->js_confirm_delete("$switch/$iface", "delete-port", "$switch|$iface");
}
function delete_port_confirm():bool{
    $tpl = new template_admin();
    $pp = $_POST["delete-port"];
    $tr=explode("|",$pp);
    $switch = $tr[0];
    $iface = $tr[1];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvswitch/port/delete/$switch/$iface"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Open Vswitch, delete port $iface from $switch");

}
function new_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $function=$_GET["function"];
	return $tpl->js_dialog1("{new_virtual_switch}","$page?new-popup=yes&function=$function",650);

}

function td_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openvswitch/metrics"));

    // Chaque nom part dans getElementById('<nom>-status'), c'est-a-dire dans du
    // JavaScript emis: il etait repris BRUT depuis l'URL.
    $switches=array();
    foreach(explode(",",(string)($_GET["refresh-statues"] ?? "")) as $sw){
        $sw=ovs_switch_name($sw);
        if($sw!==""){$switches[]=$sw;}
    }
    $js=array();
    foreach ($switches as $SwitchName){
        $status=base64_encode($tpl->_ENGINE_parse_body(td_switch_status($SwitchName,$json)));
        list($pkts,$bytes)=td_switch_rxtx($SwitchName,$json);
        $td_switch_interfaces=base64_encode($tpl->_ENGINE_parse_body(td_switch_interfaces($SwitchName,$json)));

        $js[]="if( document.getElementById('$SwitchName-status') ){";
        $js[]="document.getElementById('$SwitchName-status').innerHTML=base64_decode('$status');";
        $js[]="}";
        $js[]="if( document.getElementById('$SwitchName-rx') ){";
        $js[]="document.getElementById('$SwitchName-rx').innerHTML='$pkts';";
        $js[]="}";
        $js[]="if( document.getElementById('$SwitchName-tx') ){";
        $js[]="document.getElementById('$SwitchName-tx').innerHTML='$bytes';";
        $js[]="}";
        $js[]="if( document.getElementById('$SwitchName-interfaces') ){";
        $js[]="document.getElementById('$SwitchName-interfaces').innerHTML=base64_decode('$td_switch_interfaces');";
        $js[]="}";

    }
    header("content-type: application/x-javascript");
    echo implode("\n",$js);

}
function td_switch_rxtx($switch,$json):array{
    $tpl=new template_admin();
    // "->Status" puis property_exists() sur un json_decode() rate: les deux
    // levent une TypeError sur null en PHP 8. L'ordre etait de surcroit
    // inverse -- le deref precedait la verification.
    if(!ovs_switchs($json)){return array("-","-");}
    foreach ($json->vswitchs->switchs as $Name=>$vswitch){
        if($Name<>$switch){
            continue;
        }
        $rx=$tpl->FormatNumber($vswitch->metrics->packetCount);
        $tx=FormatBytes($vswitch->metrics->byteCount/1024);
        return array("$rx","$tx");

    }
    return array("-","-");
}
// Vrai quand la reponse porte reellement la liste des commutateurs.
function ovs_switchs($json):bool{
    if(!is_object($json)){return false;}
    if(empty($json->Status)){return false;}
    if(!property_exists($json,"vswitchs")){return false;}
    if(!is_object($json->vswitchs)){return false;}
    if(!isset($json->vswitchs->switchs)){return false;}
    return true;
}

// Les interfaces d'un commutateur. C'est une CELLULE MULTI-VALEURS: une
// pastille par interface dans un conteneur flex, jamais la grille
// <table><tr><td> d'origine -- imbriquee dans une cellule, elle ne peut pas
// passer a la ligne et fait exploser la colonne.
function ovs_iface_chip($tpl,$page,$switch,$ifaces):string{
    $portName=(string)($ifaces->name ?? "");
    if($portName===""||$portName===$switch){return "";}

    $linkState=(string)($ifaces->linkState ?? "");
    $up=($linkState==="up");
    $addr=(string)($ifaces->addr ?? "");
    $cdir=(string)($ifaces->cdir ?? "");

    // Le port propre du commutateur (suffixe h0) ne s'edite ni ne se supprime.
    $own=(bool)preg_match("#h0$#",$portName);

    // La condition d'origine etait NIEE: "if(!preg_match('#h([0-9]+)$#',...))"
    // puis "$Index=intval($re[1])" -- quand le motif ne correspond PAS, $re est
    // vide et $re[1] n'existe pas (avertissement PHP 8), $Index tombe a 0, et
    // le port perdait A LA FOIS son lien d'edition et son bouton de
    // suppression. C'est-a-dire pour TOUTE interface dont le nom ne finit pas
    // par hN: les cartes physiques rattachees au commutateur, exactement celles
    // qu'on veut pouvoir detacher. Le cas utile -- le port propre du
    // commutateur -- est deja couvert par le test h0 juste au-dessus.

    $name=$own?htmlspecialchars($portName)
        :$tpl->td_href(htmlspecialchars($portName),"",
            "Loadjs('fw.network.interfaces.php?nic-config-js=".rawurlencode($portName)."&md=');");

    $h ="<span class='awc-chip' style='margin-right:14px'>";
    $h.="<span class='awc-chip-ico'><i class='".ico_nic."'></i></span>";
    $h.="<span><span class='awc-name'>$name</span> ";
    $h.=$up?"<span class='awc-badge awc-badge-on'>{active2}</span>"
           :"<span class='awc-tag'>{inactive2}</span>";

    $meta=array();
    if(strlen($addr)>3){$meta[]=htmlspecialchars($addr."/".$cdir);}
    // metrics peut manquer: "->rx_bytes" sur null est une TypeError en PHP 8.
    if(isset($ifaces->metrics) && is_object($ifaces->metrics)){
        $meta[]="RX ".FormatBytes(intval($ifaces->metrics->rx_bytes ?? 0)/1024);
        $meta[]="TX ".FormatBytes(intval($ifaces->metrics->tx_bytes ?? 0)/1024);
    }
    if(count($meta)>0){$h.="<div class='awc-meta awc-mono'>".@implode(" &middot; ",$meta)."</div>";}
    $h.="</span>";
    if(!$own){
        $h.="<a class='awc-act awc-act-danger' href=\"javascript:blur();\" "
           ."OnClick=\"Loadjs('$page?delete-port-js=".rawurlencode($switch)
           ."&iface=".rawurlencode($portName)."');\"><i class='fas fa-trash-can'></i></a>";
    }
    return $h."</span>";
}

function td_switch_interfaces($switch,$json):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!ovs_switchs($json)){return "";}
    $out=array();
    foreach ($json->vswitchs->switchs as $Name=>$vswitch){
        if($Name<>$switch){continue;}
        if(!isset($vswitch->ports)||!is_array($vswitch->ports)){continue;}
        foreach ($vswitch->ports as $ports){
            if(!is_object($ports)){continue;}
            if(($ports->name ?? "")==$switch){continue;}
            if(!isset($ports->interfaces)){continue;}
            foreach ($ports->interfaces as $ifaces){
                if(!is_object($ifaces)){continue;}
                $chip=ovs_iface_chip($tpl,$page,$switch,$ifaces);
                if($chip!==""){$out[]=$chip;}
            }
        }
    }
    if(count($out)===0){return "<span class='awc-none'>{no_data}</span>";}
    return "<div class='awc-chips'>".@implode("",$out)."</div>";
}
function td_switch_status($switch,$json):string{
    // Meme garde que les deux autres lecteurs: le deref precedait la
    // verification, et "#2" ne disait rien a personne.
    if(!is_object($json)){
        return "<span class='awc-badge awc-badge-alert'>{failed}</span>";
    }
    if(empty($json->Status)){
        $e=isset($json->Error)?htmlspecialchars((string)$json->Error):"{failed}";
        return "<span class='awc-badge awc-badge-alert'>$e</span>";
    }
    if(!ovs_switchs($json)){
        return "<span class='awc-badge awc-badge-alert'>{failed}</span>";
    }

    foreach ($json->vswitchs->switchs as $Name=>$vswitch){
        if($Name<>$switch){
            continue;
        }
        return "<span class='awc-badge awc-badge-on'>{active2}</span>";

    }
    return "<span class='awc-tag'>{inactive2}</span>";
}

function new_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $BootstrapDialog="";
    $function=$_GET["function"];

    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT Interface,IPADDR,NETMASK,NICNAME FROM nics WHERE virtualbridge=0");
    if(!$q->ok){
        echo $tpl->div_warning($q->mysql_error);
        return false;
    }
    $tpl->table_form_section("","{new_virtual_switch_explain}");

    foreach ($results as $index=>$ligne) {
        $nic=$ligne["Interface"];
        $topbuttons[] = array("Loadjs('$page?new-select=$nic&function=$function');", ico_arrow_right, "{select}");
        $text=$nic." ".$ligne["IPADDR"]."/".$ligne["NETMASK"];
        $tpl->table_form_field_info($ligne["NICNAME"],"<small>$text</small>",ico_nic,$topbuttons);

    }
    echo $tpl->table_form_compile();
    return true;

}
function new_select(){
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $interface=$_GET["new-select"];
    // Le nom d'interface vient du formulaire et partait brut dans le WHERE.
    $q->QUERY_SQL_PARAMS("UPDATE nics SET virtualbridge=1 WHERE Interface=?",array($interface));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "dialogInstance1.close();\n";
    echo "$function();";
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html=$tpl->page_header("{APP_OPENVSWITCH}",
        "fas fa-bezier-curve",
        "{APP_OPENVSWITCH_EXPLAIN}",
        "$page?start=yes",
        "virtual-switches",
        "openvswtich-restart",false,
        "table-routers-loader"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {interfaces_connectors}",$html);
        echo $tpl->build_firewall();
        return;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

// Une ligne de commutateur virtuel. Les quatre identifiants "<nom>-status",
// "-rx", "-tx" et "-interfaces" sont des CIBLES: td_status() y ecrit par
// getElementById toutes les N secondes. Les perdre rend la ligne muette sans
// aucune erreur nulle part.
function ovs_row($tpl,$page,$SwitchName,$nic,$nicName,$function):string{
    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?new-port-js=".rawurlencode($SwitchName)."')",ico_plus,"{new_interface}");
    $bts=$tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));

    $h ="<tr id='ovs-".htmlspecialchars($SwitchName)."'>";
    $h.="<td style='width:1%' nowrap><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-bezier-curve'></i></span>";
    $h.="<div><div class='awc-name'>".htmlspecialchars($SwitchName)."</div>";
    $h.="<div class='awc-meta'>".htmlspecialchars($nicName)." (".htmlspecialchars($nic).")</div>";
    $h.="</div></div></td>";
    $h.="<td class='center' style='width:1%' nowrap><span id='".htmlspecialchars($SwitchName)."-status'>...</span></td>";
    $h.="<td><div id='".htmlspecialchars($SwitchName)."-interfaces' class='awc-meta'></div></td>";
    $h.="<td class='center' style='width:1%' nowrap><span class='awc-seen' id='".htmlspecialchars($SwitchName)."-rx'></span></td>";
    $h.="<td class='center' style='width:1%' nowrap><span class='awc-seen' id='".htmlspecialchars($SwitchName)."-tx'></span></td>";
    $h.="<td style='width:1%' nowrap><div class='awc-acts' style='opacity:1'>$bts</div></td>";
    return $h."</tr>";
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    // Whitelist: $function repart dans des OnClick et des $jsafter, sans
    // echappement, et il est influencable par une URL fabriquee.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));

    // start() rend un search_block dont l'URL porte "&table=yes": la recherche
    // arrivait donc ICI, mais $_GET["search"] n'etait jamais lu -- on tapait, le
    // tableau se rechargeait a l'identique.
    $search=mb_strtolower(trim((string)($_GET["search"] ?? "")));
    if($search==="*"){$search="";}

    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT Interface,IPADDR,NETMASK,NICNAME FROM nics WHERE virtualbridge=1");
    if(!$q->ok){
        echo $tpl->div_warning($q->mysql_error);
        return false;
    }
    if(!is_array($results)){$results=array();}
    $total=count($results);

    $rows=array();
    $jsSwitchs=array();
    foreach ($results as $ligne){
        $nic=(string)($ligne["Interface"] ?? "");
        if($nic===""){continue;}
        $nicName=(string)($ligne["NICNAME"] ?? "");
        $SwitchName="switch".$nic;
        if($search!==""){
            $hay=mb_strtolower($SwitchName." ".$nic." ".$nicName);
            if(mb_strpos($hay,$search)===false){continue;}
        }
        $jsSwitchs[]=$SwitchName;
        $rows[]=ovs_row($tpl,$page,$SwitchName,$nic,$nicName,$function);
	}
	$c=count($rows);

	// L'icone de suppression par ligne visait "?delete-rule-js={$ligne["ID"]}",
	// or le SELECT ci-dessus ne ramene PAS de colonne ID (il lit nics, pas
	// pnic_bridges): l'URL partait avec un identifiant vide -- deux
	// avertissements PHP 8 par ligne -- et le gestionnaire finissait en
	// "WHERE ID=0", donc ne supprimait rien. Le daemon n'expose de toute facon
	// aucune route de suppression de commutateur (/openvswitch/ n'a que
	// install, uninstall, restart, status, metrics, port/add, port/delete):
	// le bouton est retire plutot que laisse a promettre ce qu'il ne fait pas.

    $html=array();
	// L'identifiant du <table> est la SENTINELLE de RefreshInterval_Loadjs:
	// ArticaRefresh.tick() detruit le minuteur -- en silence, avant la premiere
	// requete -- si getElementById ne le trouve pas. Il est conserve tel quel.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table id='table-main-table-openvswtiches' class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{virtual_switch}</th>";
    $html[]="<th class='center' nowrap>{status}</th>";
    $html[]="<th>{interfaces}</th>";
    $html[]="<th class='center' nowrap>{packets}</th>";
    $html[]="<th class='center' nowrap>{bandwidth}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='ovs-rows'>";
    if($c===0){
        $html[]="<tr><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    // Le <tfoot> annoncait colspan='11' pour SEPT colonnes, et son
    // <ul class='pagination'> etait la cible de footable.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $c".($search!==""?" / $total":"")." {items}</span>"
           ."<span class='awc-pg' id='ovs-pager'></span></div>";
    $html[]="</div></div>";

    // $topbuttons etait REUTILISE: rempli dans la boucle avec le bouton
    // « nouvelle interface » de CHAQUE ligne, puis complete APRES la boucle --
    // le TINY heritait donc du bouton de la DERNIERE ligne en plus du sien, et
    // restait indefini quand aucun commutateur n'existait.
    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?new-js&function=$function')",ico_plus,"{new_virtual_switch}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_OPENVSWITCH}";
    $TINY_ARRAY["ICO"]="fas fa-bezier-curve";
    $TINY_ARRAY["EXPL"]="{APP_OPENVSWITCH_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $zswitchs=urlencode(@implode(",",$jsSwitchs));
    $refresh=$tpl->RefreshInterval_Loadjs("table-main-table-openvswtiches",$page,"refresh-statues=$zswitchs");

    // Le <script> reste HORS de l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body lit chaque bloc d'accolades JS comme un {token}.
    $js=array();
    $js[]="<script>";
    $js[]="NoSpinner();";
    $js[]=$tpl->ICON_SCRIPTS_COMPILE();
    $js[]=$refresh;
    $js[]=$headsjs;
    $js[]="</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .@implode("\n",$js)
        .awc_pager_js(50,"ovs-rows","ovs-pager");
    return true;
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}