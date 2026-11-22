<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
// l2sw_reserved_ports(): cards held by an L2 switch (fw.network.l2switch.php), read-only here.
include_once(dirname(__FILE__)."/ressources/class.network.l2switch.inc");

$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_UNIX"]=new unix();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : les noms d'interface (eth, nic-*, load-bond-info-*, value, ...) etaient recopies sans encodage dans la page (XSS).
// Ils sont ramenes une fois ici a l'alphabet d'un nom d'interface (eth0, eth0:1, ens18.10, veth3, bond0...).
foreach(array("eth","value","ModifyMacVlanName-popup","load-bond-info-js","load-bond-info-popup","OSPF","enable-interface-js",
    "proxyarp","nic-config-js","nic-name-interface","nic-address","nic-gateway","nic-bond","nic-virtuals","nic-vlans",
    "nic-config-tab","mii-tools","nic-security","nic-config","nic-enable-interface","nic-disable-interface",
    "nic-disable-dhcp","nic-enable-dhcp","nic-use-span-enable","nic-use-span-disable","nic-checksum-offloading",
    "nic-internet-check","nic-name-interface-popup","nic-address-popup","nic-gateway-popup","nic-config2","nic-apply",
    "nic-features","nic-vlans-list","nic-virtuals-list","nic-virtuals-apply","multipath-section","multipath-table",
    "multipath-apply","delete-bond-js","delete-macvlan") as $k){
    if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9_.:@-]/','',(string)$_GET[$k]);}
}
// Pentest 2026-11-04 : ping-result/ping-popup etaient recopies sans encodage dans la page (XSS) : base64 seulement.
foreach(array("ping-result","ping-popup") as $k){
    if(isset($_GET[$k])){$_GET[$k]=preg_replace('#[^A-Za-z0-9+/= ]#','',(string)$_GET[$k]);}
}
if(isset($_GET["nic-apply"])){nic_apply_interface();exit;}
if(isset($_POST["macvlansource"])){ModifyMacVlanName_save();exit;}
if(isset($_GET["ModifyMacVlanName-js"])){ModifyMacVlanName_js();exit;}
if(isset($_GET["ModifyMacVlanName-popup"])){ModifyMacVlanName_popup();exit;}

if(isset($_GET["nic-enable-interface"])){nic_enable_interface();exit;}
if(isset($_GET["nic-disable-interface"])){nic_disable_interface();exit;}
if(isset($_GET["nic-disable-dhcp"])){nic_disable_dhcp();exit;}
if(isset($_GET["nic-enable-dhcp"])){nic_enable_dhcp();exit;}
if(isset($_GET["nic-checksum-offloading"])){nic_checksum_offloading();exit;}
if(isset($_GET["nic-use-span-enable"])){nic_use_span_enable();exit;}
if(isset($_GET["nic-use-span-disable"])){nic_use_span_disable();exit;}
if(isset($_GET["nic-name-interface"])){nic_name_interface_js();exit;}
if(isset($_GET["nic-name-interface-popup"])){nic_name_interface_popup();exit;}
if(isset($_GET["nic-internet-check"])){nic_internet_check_save();exit;}
if(isset($_POST["NicName"])){nic_name_interface_save();exit;}

if(isset($_GET["nic-address"])){nic_addr_js();exit;}
if(isset($_GET["nic-address-popup"])){nic_addr_popup();exit;}
if(isset($_POST["NicAddr"])){nic_addr_save();exit;}

if(isset($_GET["nic-gateway"])){nic_gateway_js();exit;}
if(isset($_GET["nic-gateway-popup"])){nic_gateway_popup();exit;}
if(isset($_POST["NicGateway"])){nic_gateway_save();exit;}



if(isset($_GET["carrier-change-explain-js"])){carrier_change_explain_js();exit;}
if(isset($_GET["carrier-change-explain-popup"])){carrier_change_explain_popup();exit;}
if(isset($_GET["masq-interface"])){Masquerade_switch();exit;}
if(isset($_GET["ArpScanner"])){ArpScanner();exit;}
if(isset($_GET["refresh-tables"])){echo refreshjs();exit;}
if(isset($_GET["section-nics"])){section_nic_only();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["OSPF"])){Save_OSPF();exit;}
if(isset($_GET["AsGateway-options"])){AsGateway_options_js();exit;}
if(isset($_GET["AsGateway-options-popup"])){AsGateway_options_popup();exit;}
if(isset($_GET["AsGateway-options-table"])){AsGateway_options_table();exit;}

if(isset($_POST["AsGatewayOpts"])){AsGateway_options_save();exit;}
if(isset($_GET["nic-config-deny"])){nic_config_deny();exit;}
if(isset($_GET["ping-result"])){ping_results_js();exit;}
if(isset($_GET["ping-popup"])){ping_results_popup();exit;}

if(isset($_GET["add-macvlan-js"])){macvlan_js();exit;}
if(isset($_GET["add-macvlan-popup"])){macvlan_popup();exit;}
if(isset($_POST["macvlan"])){macvlan_save();exit;}
if(isset($_GET["delete-macvlan"])){macvlan_delete();exit;}
if(isset($_POST["delete-macvlan"])){macvlan_delete_perform();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["status2"])){status2();exit;}
if(isset($_GET["ghosts-js"])){ghosts_js();exit;}
if(isset($_GET["ghosts-popup"])){ghosts_popup();exit;}
if(isset($_GET["ghost-remove-js"])){ghost_remove_js();exit;}
if(isset($_POST["ghost-remove"])){ghost_remove_perform();exit;}
if(isset($_GET["ghost-reassign-js"])){ghost_reassign_js();exit;}
if(isset($_POST["ghost-reassign"])){ghost_reassign_perform();exit;}
if(isset($_GET["status2-vlan-build-js"])){status2_vlan_build_js();exit;}
// Trois repartiteurs morts: enable_signature(), enable_firewall() et
// rule_settings() ne sont definies NI ici NI ailleurs -- les trois URL
// repondaient un fatal « Call to undefined function ». Le couple
// enable-signature/enable-firewall est un passe-partout recopie tel quel dans
// des dizaines de fw.*.php; il est retire ici, la campagne complete est un
// autre chantier.
if(isset($_GET["nic-config-tab"])){nic_config_tab();exit;}
if(isset($_POST["save_nic"])){nic_save();exit;}
if(isset($_POST["IfConfigdelayed"])){option_save();exit;}
if(isset($_POST["save_nic_virtual"])){nic_virtuals_save();exit;}
if(isset($_POST["SysCtlEnable"])){nic_security_save();exit;}
if(isset($_GET["nic-config-js"])){nic_config_js();exit;}
if(isset($_GET["nic-config"])){nic_config();exit;}
if(isset($_GET["nic-config2"])){nic_config2();exit;}
if(isset($_GET["nic-security"])){nic_security();exit;}
if(isset($_GET["nic-features"])){nic_features();exit;}
if(isset($_GET["mii-tools"])){nic_mii_tool();exit;}

if(isset($_GET["AsGateway-unlock-js"])){AsGateway_unlock_js();exit;}
if(isset($_GET["AsGateway-unlock-popup"])){AsGateway_unlock_popup();exit;}
if(isset($_GET["AsGateway-unlock-perform"])){AsGateway_unlock_perform();exit;}

if(isset($_GET["AsGateway-js"])){AsGateway_js();exit;}
if(isset($_POST["AsGateway"])){AsGateway_save();exit;}
if(isset($_GET["netoptimize-js"])){NetOptimize_js();exit;}
if(isset($_POST["netoptimize"])){NetOptimize_save();exit;}

if(isset($_GET["YourFirewall-js"])){YourFirewall_js();exit;}
if(isset($_POST["YourFirewall"])){YourFirewall_save();exit;}
if(isset($_GET["enableIPV6-js"])){EnableIPV6_js();exit;}
if(isset($_POST["EnableIPV6Toggle"])){EnableIPV6_save();exit;}

if(isset($_GET["nic-virtuals"])){nic_virtuals();exit;}
if(isset($_GET["nic-virtuals-list"])){nic_virtuals_search();exit;}

if(isset($_GET["nic-virtual-js"])){nic_virtuals_js();exit;}
if(isset($_GET["nic-virtual"])){nic_virtuals_popup();exit;}
if(isset($_GET["nic-virtual-delete-js"])){nic_virtuals_delete_js();exit;}
if(isset($_GET["nic-virtuals-apply"])){nic_virtuals_apply();exit;}
if(isset($_POST["delete_nic_virtual"])){nic_virtuals_delete();exit;}

//Bond
if(isset($_GET["nic-bond"])){nic_bond();exit;}
if(isset($_POST["save-bond-interface"])){save_bond_interface();exit;}
if(isset($_POST["addbondinterface"])){AddBondSave();exit;}
if(isset($_GET["add-bond-js"])){AddBond();exit;}
if(isset($_GET["delete-bond-js"])){DeleteBond();exit;}
if(isset($_POST["delete-bond"])){DeleteBondSave();exit;}
if(isset($_GET["load-bond-info-js"])){load_bond_info_js();exit;}
if(isset($_GET["load-bond-info-popup"])){load_bond_info_popup();exit;}

if(isset($_GET["nic-vlan-start"])){nic_vlan_start();exit;}
if(isset($_GET["nic-vlan-stop"])){nic_vlan_stop();exit;}


if(isset($_GET["nic-vlans"])){nic_vlans();exit;}
if(isset($_GET["nic-vlans-list"])){nic_vlans_list();exit;}
if(isset($_GET["nic-vlan-js"])){nic_vlan_js();exit;}
if(isset($_GET["nic-vlan"])){nic_vlan_popup();exit;}
if(isset($_GET["nic-vlan-enable"])){nic_vlan_enable();exit;}
if(isset($_GET["nic-vlan-masquerade"])){nic_vlan_masquerade();exit;}
if(isset($_POST["save_nic_vlan"])){nic_vlan_save();exit;}
if(isset($_GET["nic-vlan-delete-js"])){nic_vlan_delete_js();exit;}
if(isset($_POST["delete_nic_vlan"])){nic_vlan_delete();exit;}

if(isset($_GET["sysctl-js"])){sysctl_js();exit;}
if(isset($_GET["sysctl-popup"])){sysctl_popup();exit;}

if(isset($_GET["sysctl2-js"])){sysctl2_js();exit;}
if(isset($_GET["sysctl2-popup"])){sysctl2_popup();exit;}
if(isset($_GET["sysctl-proxyjs"])){sysctl2_proxy();exit;}


if(isset($_POST["NicProfile"])){saveNicProfile();exit;}

if(isset($_POST["EnableSystemNetworkOptimize"])){EnableSystemNetworkOptimize();exit;}
if(isset($_GET["disabled-interfaces-js"])){disabled_interfaces_js();exit;}
if(isset($_GET["disabled-interfaces-popup"])){disabled_interfaces_popup();exit;}
if(isset($_GET["disabled-interfaces-table"])){disabled_interfaces_table();exit;}
if(isset($_GET["enable-interface-js"])){disabled_interfaces_save();exit;}
if(isset($_GET["isFW"])){isFW();exit;}
if(isset($_GET["proxyarp"])){proxyarp();exit;}
if(isset($_GET["MPTCP-js"])){MTPTCP_JS();exit;}
if(isset($_GET["MTPTCP-popup"])){MTPTCP_POPUP();exit;}
if(isset($_GET["multipath-section"])){multip_path_section();exit;}
if(isset($_GET["multipath-table"])){multip_path_table();exit;}
if(isset($_GET["multip-path-js"])){multipath_js();exit;}
if(isset($_GET["mulipath-popup"])){multipath_popup();exit;}
if(isset($_POST["multip-path-gateway"])){multipath_save();exit;}
if(isset($_GET["multip-path-delete"])){multipath_delete_js();exit;}
if(isset($_POST["multip_path_delete"])){multipath_delete();exit;}
if(isset($_GET["multipath-probe"])){multipath_probe();exit;}
if(isset($_GET["multipath-apply"])){multipath_apply();exit;}

// enable-watchdog / disable-watchdog: supprimes avec la surveillance Internet
// par interface. Aucun bouton de la console ne construisait ces URL, et les
// handlers n'ecrivaient que nics.watchdog, colonne qu'aucun code ne lit.


if(isset($_GET["options-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}


page();

/* =====================================================================
 * Socle des tableaux d'entites de cette page.
 * Le cadre, la carte et le pager viennent de awc_table_style() /
 * awc_pager_js() (class.statistics_appliance.inc, partages). Seules les
 * pastilles specifiques au reseau sont definies ici.
 * ===================================================================== */

/**
 * CSS propre a la page.
 * Concatene autour du parsing, jamais dedans: _ENGINE_parse_body prendrait
 * chaque corps de regle CSS pour un {token}.
 */
function netif_style():string{
    return "<style>
.nif-st-up{background:#26a69a;color:#fff}
.nif-st-down{background:#ba1a1a;color:#fff}
.nif-st-warn{background:#865300;color:#fff}
.nif-st-off{background:#e8ecec;color:#3d4947}
.nif-tgs{display:-webkit-box;display:-ms-flexbox;display:flex;-ms-flex-wrap:wrap;flex-wrap:wrap;max-width:250px}
.nif-tg{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;
padding:3px 8px;border-radius:4px;margin:0 5px 4px 0;text-decoration:none !important;white-space:nowrap}
.nif-tg-on{background:#006a62;color:#fff !important}
.nif-tg-off{background:#e8ecec;color:#6d7a77 !important}
.nif-tg-warn{background:#865300;color:#fff !important}
.nif-tg-err{background:#ba1a1a;color:#fff !important}
.nif-tg:hover{opacity:.82}
.nif-opts{display:-webkit-box;display:-ms-flexbox;display:flex;-ms-flex-wrap:wrap;flex-wrap:wrap;max-width:300px}
.nif-opt{display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;-webkit-box-align:center;
-ms-flex-align:center;align-items:center;margin:0 14px 5px 0;white-space:nowrap}
.nif-opt-l{font-size:10px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;color:#6d7a77;margin-right:5px}
.nif-tr{font-size:13px;color:#3d4947;line-height:1.55;white-space:nowrap}
.nif-tr b{color:#111d23;font-weight:600}
.nif-err{display:block;font-size:12px;color:#ba1a1a;margin-top:2px}
.nif-icocol{display:-webkit-inline-box;display:-ms-inline-flexbox;display:inline-flex;
-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;
-webkit-box-align:center;-ms-flex-align:center;align-items:center;-ms-flex:0 0 auto;flex:0 0 auto}
.nif-icobadge{display:block;margin-top:7px;line-height:1;text-align:center;white-space:nowrap}
.nif-icobadge .label{display:inline-block;float:none}
</style>";
}

/** En-tete + ouverture du tableau d'entites. */
function netif_table_open($columns,$rowsId):string{
    $h=array();
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr>";
    foreach($columns as $col){ $h[]=$col; }
    $h[]="</tr></thead><tbody id='$rowsId'>";
    return @implode("\n",$h);
}

/** Pied du tableau: compteur a gauche, pager a droite. */
function netif_table_close($shown,$total,$unit,$pagerId):string{
    $h=array();
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'>";
    $h[]="<span>{showing} $shown / $total $unit</span>";
    $h[]="<span class='awc-pg' id='$pagerId'></span>";
    $h[]="</div></div></div>";
    return @implode("\n",$h);
}

/** Ligne vide normalisee (jamais un <tbody> vide). */
function netif_table_empty($colspan):string{
    return "<tr><td colspan='$colspan'><div class='awc-empty'>{no_data}</div></td></tr>";
}

/** Pastille d'etat. $kind: up | down | warn | off. */
function netif_state_badge($kind,$label):string{
    return "&nbsp;<span class='awc-badge nif-st-$kind'>$label</span>";
}

/**
 * Bloc d'identite: pastille d'icone, nom (lien si $js), metadonnees.
 * $meta est un tableau de fragments deja echappes; ils sont joints par un point median.
 */
// $namesuffix: pastille rendue A DROITE DU NOM, sur la meme ligne (ex. "Liee a
// bond0"). Elle est volontairement placee APRES le </span> du nom et donc HORS
// du <a> construit ci-dessous: concatenee au nom, elle deviendrait cliquable et
// prendrait le style du lien; concatenee apres netif_ident(), elle retombait
// sous l'adresse MAC, contre le bord gauche de la cellule.
function netif_ident($ico,$name,$meta=array(),$js=null,$off=false,$badge=null,$namesuffix=null):string{
    $icocls=$off?"awc-ico awc-ico-off":"awc-ico";
    $namecls=$off?"awc-name awc-name-off":"awc-name";
    if($js<>null){
        $name="<a href=\"javascript:blur();\" OnClick=\"$js\">$name</a>";
    }
    // $ico accepte soit une classe ("fas fa-x"), soit une icone deja rendue
    // ("<i class='...'></i>"): table() calcule la sienne au fil de 40 cas particuliers.
    $icohtml=(substr(trim($ico),0,1)=="<")?$ico:"<i class='$ico'></i>";
    $h ="<div class='awc-ident'>";
    // $badge (DOWN, MIRROR...) est empile SOUS la pastille et centre sur elle.
    // Concatene apres le bloc d'identite, il retombait en pied de cellule contre
    // le bord gauche, colle a l'icone.
    if($badge<>null && strlen(trim(strval($badge)))>0){
        $h.="<span class='nif-icocol'><span class='$icocls'>$icohtml</span>"
           ."<span class='nif-icobadge'>$badge</span></span>";
    }else{
        $h.="<span class='$icocls'>$icohtml</span>";
    }
    $h.="<span><span class='$namecls'>$name</span>";
    if($namesuffix<>null && strlen(trim(strval($namesuffix)))>0){
        $h.=$namesuffix;
    }
    if(count($meta)>0){
        $h.="<div class='awc-meta'>".@implode(" &middot; ",$meta)."</div>";
    }
    $h.="</span></div>";
    return $h;
}

/**
 * Groupe de bascules compactes.
 * $toggles: array(array("label"=>..,"on"=>bool,"js"=>..)) — remplace une colonne
 * par option, qui faisait exploser la largeur du tableau.
 */
function netif_toggles($toggles):string{
    if(!is_array($toggles)){return "";}
    if(count($toggles)==0){return "";}
    $h=array();
    $h[]="<div class='nif-tgs'>";
    foreach($toggles as $t){
        if(!isset($t["label"])){continue;}
        // "state" prime sur "on": certaines options ont plus de deux etats
        // (DHCP: non installe / actif / arrete / en marche).
        $state=(isset($t["on"]) && $t["on"])?"on":"off";
        if(isset($t["state"])){$state=$t["state"];}
        $cls="nif-tg nif-tg-$state";
        $js=isset($t["js"])?$t["js"]:"";
        if(strlen(trim($js))>0){
            $h[]="<a class='$cls' href=\"javascript:blur();\" OnClick=\"$js\">{$t["label"]}</a>";
        }else{
            $h[]="<span class='$cls'>{$t["label"]}</span>";
        }
    }
    $h[]="</div>";
    return @implode("",$h);
}

/**
 * Groupe d'options deja rendues: libelle court + widget existant.
 * Utilise par table(), dont les cinq colonnes d'options sont neutralisees par une
 * quarantaine de cas particuliers (SPAN, bond, veth, virtualbridge...). On collapse
 * les colonnes sans re-deriver les booleens, pour ne rien casser de cette logique.
 */
function netif_optgroup($items):string{
    $h=array();
    $h[]="<div class='nif-opts'>";
    foreach($items as $label=>$w){
        if(is_null($w)){continue;}
        if(strlen(trim(strval($w)))==0){continue;}
        $h[]="<span class='nif-opt'><span class='nif-opt-l'>$label</span>$w</span>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

/** Cellule d'actions alignee a droite; $items est un tableau de liens deja construits. */
function netif_acts($items):string{
    if(!is_array($items)){$items=array($items);}
    $out=array();
    foreach($items as $i){
        if(is_null($i)){continue;}
        if(strlen(trim($i))==0){continue;}
        $out[]=$i;
    }
    return "<div class='awc-acts'>".@implode("",$out)."</div>";
}

/** Cellule de trafic: RX / TX / rejets, plus les icones de graphiques. */
function netif_traffic($rx,$tx,$drop,$extra=""):string{
    $l=array();
    if($rx>0){$l[]="<b>RX</b> ".FormatBytes($rx/1024,true);}
    if($tx>0){$l[]="<b>TX</b> ".FormatBytes($tx/1024,true);}
    if($drop>0){$l[]="<b style='color:#ba1a1a'>DROP</b> ".FormatNumber($drop);}
    if(count($l)==0 && strlen(trim($extra))==0){return "<span class='awc-muted'>&mdash;</span>";}
    $h="";
    if(count($l)>0){$h="<div class='nif-tr'>".@implode("<br>",$l)."</div>";}
    if(strlen(trim($extra))>0){$h.="<div style='margin-top:4px'>$extra</div>";}
    return $h;
}

/**
 * Fiche d'interface de la galerie d'etat (maquette stitch_network_status2).
 * Partagee par status2(), status2_vlan(), status2_ghosts() et
 * status2_samba_instances(): une seule forme de carte, quatre sources.
 *
 * $c (toutes les cles sont optionnelles sauf name):
 *   state   up|warn|down|off   couleur de la pastille et du filtre de legende
 *   kind    ""|vlan            vlan = liseré gauche (interface enfant)
 *   ico     classe FA de la tuile, name (deja echappe), js (lien du nom)
 *   pills   pastilles a droite du nom (HTML deja rendu)
 *   line    fragments de la ligne sous le nom (deja echappes)
 *   mac     adresse MAC (brute), maclabel (libelle, defaut MAC)
 *   bars    array(array("hl"=>bool,"label"=>..,"value"=>..)) bandeaux d'information
 *   toggles bascules (netif_toggles), note: texte en italique a la place
 *   rx/tx/drop compteurs en octets, foot: mention a droite du pied
 *   acts    boutons en haut a droite, search: texte du filtre
 */
function netif_card($c):string{
    $state=isset($c["state"])?$c["state"]:"off";
    $off=($state!="up");
    $cls="nif-card nif-card-$state";
    if(isset($c["kind"]) && $c["kind"]=="vlan"){$cls.=" nif-card-vlan";}
    $ico=isset($c["ico"])?$c["ico"]:ico_nic;
    $name=$c["name"];
    if(isset($c["js"]) && strlen(trim(strval($c["js"])))>0){
        $name="<a href=\"javascript:blur();\" OnClick=\"{$c["js"]}\">$name</a>";
    }
    $search=isset($c["search"])?$c["search"]:strip_tags($c["name"]);
    $search=htmlspecialchars(strtolower($search),ENT_QUOTES);

    $h=array();
    $h[]="<article class='$cls' data-state='$state' data-search=\"$search\">";
    $h[]="<div class='nif-card-body'>";
    $h[]="<div class='nif-card-head'>";
    $h[]="<div class='nif-card-id'>";
    $h[]="<span class='nif-card-ico'><i class='$ico'></i></span>";
    $h[]="<div class='nif-card-txt'>";
    $h[]="<div class='nif-card-title'><span class='nif-card-name".($off?" nif-card-name-off":"")."'>$name</span>";
    if(isset($c["pills"]) && is_array($c["pills"])){
        foreach($c["pills"] as $p){
            if(strlen(trim(strval($p)))==0){continue;}
            $h[]=$p;
        }
    }
    $h[]="</div>";
    if(isset($c["line"]) && is_array($c["line"]) && count($c["line"])>0){
        $h[]="<div class='nif-card-line'>".@implode("<span class='nif-sep'>&bull;</span>",$c["line"])."</div>";
    }
    if(isset($c["mac"]) && strlen(trim(strval($c["mac"])))>0){
        $ml=isset($c["maclabel"])?$c["maclabel"]:"MAC";
        $h[]="<div class='nif-card-mac'><span class='nif-card-macl'>$ml:</span>".htmlspecialchars(strval($c["mac"]))."</div>";
    }
    $h[]="</div></div>";
    if(isset($c["acts"]) && strlen(trim(strval($c["acts"])))>0){
        $h[]="<div class='nif-card-acts'>{$c["acts"]}</div>";
    }
    $h[]="</div>";
    if(isset($c["bars"]) && is_array($c["bars"])){
        foreach($c["bars"] as $b){
            $bc=(isset($b["hl"]) && $b["hl"])?"nif-bar nif-bar-hl":"nif-bar";
            $h[]="<div class='$bc'><span class='nif-bar-l'>{$b["label"]}</span><span class='nif-bar-v'>{$b["value"]}</span></div>";
        }
    }
    $h[]="</div>";

    $h[]="<div class='nif-card-foot'>";
    if(isset($c["toggles"]) && strlen(trim(strval($c["toggles"])))>0){
        $h[]=$c["toggles"];
    }elseif(isset($c["note"]) && strlen(trim(strval($c["note"])))>0){
        $h[]="<div class='nif-card-note'>{$c["note"]}</div>";
    }
    $rx=floatval(isset($c["rx"])?$c["rx"]:0);
    $tx=floatval(isset($c["tx"])?$c["tx"]:0);
    $drop=floatval(isset($c["drop"])?$c["drop"]:0);
    $h[]="<div class='nif-card-traffic'><div class='nif-card-rxtx'>";
    if($rx==0 && $tx==0){
        $h[]="<span class='nif-card-silent'>{nif_no_traffic}</span>";
    }else{
        $h[]="<span><span class='nif-k'>RX</span><strong>".FormatBytes($rx/1024,true)."</strong></span>";
        $h[]="<span><span class='nif-k'>TX</span><strong>".FormatBytes($tx/1024,true)."</strong></span>";
    }
    $h[]="</div>";
    $foot=isset($c["foot"])?$c["foot"]:"";
    if($drop>0){
        $foot="<span class='nif-card-drop'>DROP ".FormatNumber($drop)."</span>";
    }
    $h[]="<span class='nif-card-fnote'>$foot</span>";
    $h[]="</div></div>";
    $h[]="</article>";
    return @implode("",$h);
}

/** Pastille compacte a cote du nom. $kind: up|warn|down|off|tag. */
function netif_pill($kind,$label,$js=null,$title=null):string{
    $tones=array("up"=>" ak-ok","warn"=>" ak-warn","down"=>" ak-err","off"=>" ak-off");
    $tc=isset($tones[$kind])?$tones[$kind]:"";
    $t="";
    if($title<>null){$t=" title=\"$title\"";}
    if($js<>null && strlen(trim($js))>0){
        return "<a class='ak-tag$tc' href=\"javascript:blur();\" OnClick=\"$js\"$t>$label</a>";
    }
    return "<span class='ak-tag$tc'$t>$label</span>";
}

/** Petit bouton icone en haut a droite d'une fiche (graphiques, rapport bond). */
function netif_card_icon($ico,$js,$title):string{
    return "<a class='ak-act' href=\"javascript:blur();\" OnClick=\"$js\" title=\"$title\"><i class='$ico'></i></a>";
}

/** Bouton « Modifier » d'une fiche. */
function netif_card_edit($js):string{
    return "<a class='nif-cedit' href=\"javascript:blur();\" OnClick=\"$js\"><i class='fa-solid fa-pen-to-square'></i>{modify}</a>";
}

/** Bouton secondaire d'une fiche (Appliquer d'un VLAN). */
function netif_card_button($label,$js):string{
    return "<a class='nif-cbtn2' href=\"javascript:blur();\" OnClick=\"$js\">$label</a>";
}

/** En-tete de la galerie: titre, filtre, legende des couleurs. */
function netif_cards_open():string{
    $h=array();
    $h[]="<section class='nif-gallery'>";
    $h[]="<div class='nif-gal-head'>";
    $h[]="<div class='nif-gal-title'><span>{nif_cards_title}</span><span class='nif-gal-sub'>{nif_cards_order}</span></div>";
    $h[]="<div class='nif-gal-tools'>";
    $h[]="<label class='ak-search nif-gal-search'><i class='fa-solid fa-magnifying-glass'></i>"
        ."<input type='text' id='nif-cards-filter' placeholder=\"{nif_filter_placeholder}\" OnKeyUp=\"NifCardsFilter(this.value)\"></label>";
    $h[]="<div class='nif-gal-legend'>"
        ."<span><i class='ak-dot ak-ok'></i>{nif_legend_up}</span>"
        ."<span><i class='ak-dot ak-warn'></i>{nif_legend_warn}</span>"
        ."<span><i class='ak-dot ak-off'></i>{nif_legend_off}</span></div>";
    $h[]="</div></div>";
    $h[]="<div class='nif-grid' id='nif-cards'>";
    return @implode("",$h);
}

/** Pied de la galerie: compteur filtre / total. */
function netif_cards_close($total):string{
    $h=array();
    if($total==0){
        $h[]="<div class='awc-empty nif-grid-full'>{no_data}</div>";
    }
    $h[]="<div class='awc-empty nif-grid-full' id='nif-cards-nomatch' style='display:none'>{nif_no_match}</div>";
    $h[]="</div>";
    $h[]="<div class='nif-gal-foot'><strong>{showing} <span id='nif-cards-shown'>$total</span> / $total {interfaces}</strong></div>";
    $h[]="</section>";
    return @implode("",$h);
}

/** Filtre client de la galerie. Concatene APRES _ENGINE_parse_body (accolades JS). */
function netif_cards_js():string{
    return "<script>
function NifCardsFilter(v){
    v=String(v||'').toLowerCase().replace(/^\\s+|\\s+$/g,'');
    var c=document.querySelectorAll('#nif-cards .nif-card');
    var n=0;
    for(var i=0;i<c.length;i++){
        var ok=(v==='' || String(c[i].getAttribute('data-search')||'').indexOf(v)>-1);
        c[i].style.display=ok?'':'none';
        if(ok){n++;}
    }
    var s=document.getElementById('nif-cards-shown'); if(s){s.innerHTML=n;}
    var e=document.getElementById('nif-cards-nomatch'); if(e){e.style.display=(n===0 && c.length>0)?'':'none';}
}
</script>";
}

/** Feuille de style de la galerie. Couleurs d'accent = palette de marque. */
function netif_cards_style():string{
    if(isset($GLOBALS["NETIF_CARDS_STYLE"])){return "";}
    $GLOBALS["NETIF_CARDS_STYLE"]=true;
    $accent="#10b981";$dark="#047857";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.30)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.nif-gallery{margin:18px 0 10px 0;color:#111d23}
.nif-gallery *{box-sizing:border-box}
.nif-gal-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:0 4px 12px 4px}
.nif-gal-title span:first-child{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#3d4947}
.nif-gal-sub{font-size:12px;color:#6d7a77;margin-left:8px}
.nif-gal-tools{display:flex;flex-wrap:wrap;align-items:center;gap:14px}
.nif-gal-search{min-width:300px}
.nif-gal-legend{display:flex;gap:12px;font-size:12px;color:#6d7a77}
.nif-gal-legend span{display:inline-flex;align-items:center;gap:5px}
.nif-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:16px;align-items:stretch}
.nif-grid-full{grid-column:1/-1}
.nif-card{background:#fff;border:1px solid #bcc9c6;border-radius:8px;padding:16px;display:flex;flex-direction:column;justify-content:space-between;transition:border-color .15s;min-width:0}
.nif-card:hover{border-color:$accent}
.nif-card-vlan{border-left:4px solid $accent}
.nif-card-off,.nif-card-down{border-color:rgba(188,201,198,.8)}
.nif-card-body>*+*{margin-top:10px}
.nif-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.nif-card-id{display:flex;align-items:flex-start;gap:10px;min-width:0}
.nif-card-ico{width:32px;height:32px;border-radius:6px;flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;font-size:14px;margin-top:2px;background:#e3f0f8;color:#3d4947;border:1px solid rgba(188,201,198,.5)}
.nif-card-up .nif-card-ico{background:$soft;color:$dark;border-color:$line}
.nif-card-off .nif-card-ico,.nif-card-down .nif-card-ico{background:#f4f7f6;color:#6d7a77}
.nif-card-txt{min-width:0}
.nif-card-title{display:flex;flex-wrap:wrap;align-items:center;gap:6px}
.nif-card-name{font-size:16px;font-weight:700;color:#111d23;margin-right:2px}
.nif-card-name a{color:#111d23;text-decoration:none}
.nif-card-name a:hover{color:$accent;text-decoration:underline}
.nif-card-name-off,.nif-card-name-off a{color:#6d7a77}
.nif-card-line{font-size:12px;color:#3d4947;margin-top:3px;display:flex;flex-wrap:wrap;align-items:center;gap:2px 0}
.nif-sep{color:#bcc9c6;margin:0 7px}
.nif-card-line strong{color:#111d23;font-weight:600}
.nif-strong{color:#111d23;font-weight:500}
.nif-card-mac{font-size:11px;color:#3d4947;margin-top:3px}
.nif-card-macl{font-size:10px;font-weight:500;text-transform:uppercase;color:#6d7a77;margin-right:4px}
.nif-card-acts{display:flex;align-items:center;gap:5px;flex:0 0 auto}
.nif-cedit{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:5px;background:$accent;color:#fff!important;font-size:11px;font-weight:600;text-decoration:none!important;white-space:nowrap}
.nif-cedit:hover{background:$dark}
.nif-cbtn2{display:inline-flex;align-items:center;padding:3px 9px;border-radius:5px;background:#f4f7f6;color:#3d4947!important;border:1px solid #bcc9c6;font-size:11px;font-weight:500;text-decoration:none!important;white-space:nowrap}
.nif-cbtn2:hover{background:#e1e8e6}
.nif-bar{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;font-size:11px;color:#6d7a77;background:#f4f7f6;padding:5px 10px;border-radius:5px;border:1px solid rgba(188,201,198,.4)}
.nif-bar-v{color:#111d23;font-weight:500;text-align:right;word-break:break-all}
.nif-bar-hl{background:$soft;border-color:$line;color:$dark;font-size:12px}
.nif-bar-hl .nif-bar-l{font-weight:500}
.nif-bar-hl .nif-bar-v{font-weight:700}
.nif-muted-i{color:#6d7a77;font-style:italic;font-weight:400}
.nif-card-foot{padding-top:12px}
.nif-card-foot .nif-tgs{max-width:none}
.nif-card-foot .nif-tg{font-size:10px;padding:2px 8px;border:1px solid transparent}
.nif-card-foot .nif-tg-on{background:$accent;color:#fff!important}
.nif-card-foot .nif-tg-off{background:#f4f7f6;color:#6d7a77!important;border-color:rgba(188,201,198,.4)}
.nif-card-note{font-size:11px;color:#6d7a77;font-style:italic;margin-bottom:4px}
.nif-card-traffic{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:8px;padding-top:8px;border-top:1px solid rgba(188,201,198,.4);font-size:12px}
.nif-card-rxtx{display:flex;gap:16px;color:#3d4947}
.nif-card-rxtx strong{color:#111d23;font-weight:600}
.nif-k{font-size:10px;text-transform:uppercase;color:#6d7a77;margin-right:4px}
.nif-card-silent{color:#6d7a77;font-style:italic}
.nif-card-fnote{font-size:11px;color:#6d7a77;text-align:right}
.nif-card-drop{color:#ba1a1a;font-weight:600}
.nif-gal-foot{margin-top:14px;padding-top:10px;border-top:1px solid rgba(188,201,198,.5);font-size:12px;color:#111d23}
@media(max-width:700px){.nif-gal-search{min-width:0;width:100%}.nif-grid{grid-template-columns:1fr}}
</style>";
}

/**
 * Neutralise une valeur destinee a un litteral SQL.
 * lib_sqlite n'expose que QUERY_SQL() (pas de requete preparee), donc on s'appuie
 * sur le filtre maison, qui retire quotes, points-virgules, backslash et %.
 */
function netif_sql_safe($tpl,$value):string{
    return $tpl->CLEAN_BAD_XSS_SQL(strval($value));
}

// Garde de privilege pour les handlers qui ECRIVENT.
//
// Cette page n'a volontairement pas de garde global: la liste des interfaces
// (?status=yes) reste consultable sans etre administrateur systeme, et l'onglet
// de detail est deja masque par tabs(). Mais masquer un bouton ne protege pas
// un point d'entree: "AsSystemAdministrator" passe a form_outside n'est qu'un
// argument d'AFFICHAGE, et le dispatch en tete de fichier repond a n'importe
// quelle session authentifiee. Tout handler qui modifie la configuration doit
// donc appeler cette fonction en premier.
//
// $mode dit COMMENT rendre le refus, parce que les trois declencheurs de cette
// page ne lisent pas la reponse de la meme facon:
//   false  -- soumission form_outside: son xSave DESHABILLE le prefixe
//             "jserror:" et ouvre la fenetre rouge. C'est post_error().
//   true   -- gestionnaire Loadjs: la reponse est evaluee comme du JavaScript,
//             il faut donc EMETTRE du JS. C'est js_no_privileges().
//   "bare" -- js_confirm_delete()/js_confirm_execute(): leur xDelete affiche
//             « content: results », c'est-a-dire la reponse BRUTE, sans
//             toucher au prefixe. Un post_error() y afficherait litteralement
//             « jserror:... » a l'operateur.
//
// $flag choisit le drapeau: TOUS les handlers ne relevent pas de
// l'administrateur systeme. Les bascules pare-feu de cette page -- « interface
// protegee », masquerade, proxy ARP -- sont deja rendues par
// icon_check(...,"AsFirewallManager"): leur imposer AsSystemAdministrator cote
// serveur rendrait le bouton visible et le clic refuse, ce qui est pire que
// l'absence de garde. Le drapeau serveur reprend donc celui de l'affichage,
// handler par handler. Un drapeau mal orthographie vaut null, donc REFUS
// silencieux: les deux noms utilises ici sont verifies dans
// class.users.menus.inc.
function netif_require_admin($mode=false,$flag="AsSystemAdministrator"):bool{
    $users=new usersMenus();
    if($users->$flag){return true;}
    $tpl=new template_admin();
    if($mode==="bare"){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");}
    elseif($mode){$tpl->js_no_privileges();}
    else{echo $tpl->post_error("{ERROR_NO_PRIVS}");}
    return false;
}

function load_bond_info_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{$_GET["load-bond-info-js"]} {info}","$page?load-bond-info-popup={$_GET["load-bond-info-js"]}");
}
function load_bond_info_popup(){
    $tpl=new template_admin();
    $eth=$_GET["load-bond-info-popup"];
    if(!bond_valid_name($eth)){
        echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger'>{invalid_interface}</div>");
        return;
    }
    // The kernel report is read by the daemon (GET /system/network/bond/stats).
    // It used to be fetched through framework/network.php bond_stats(), which
    // stored the file in Valkey under "Bond-Stats-<bond>" just so this function
    // could read it back — a round-trip through the settings store for data
    // nothing else consumes. framework/ is deprecated anyway.
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/bond/stats/$eth"));
    if(!is_object($json)){
        echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger'>No answer from the Artica daemon</div>");
        return;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body("<div class='alert alert-warning'>".htmlspecialchars($json->Error)."</div>");
        return;
    }
    echo "<div style='font-family:monospace;font-size:12px'>".nl2br(htmlspecialchars($json->stats))."</div>";
}
function ArpScanner():bool{
    if(!netif_require_admin(true)){return false;}
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableARPScanner",$_GET["ArpScanner"]);
    header("content-type: application/x-javascript");
    echo "LoadAjax('netz-interfaces-status','$page?status2=yes');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/myself/restart");
    return true;
}
function Masquerade_switch():bool{
    if(!netif_require_admin(true,"AsFirewallManager")){return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=preg_replace('/[^A-Za-z0-9._:-]/','',(string)$_GET["masq-interface"]);
    $MASQ_js="Loadjs('$page?masq-interface=$eth');";
    $id="MASQ$eth";
    $cur=fwapi_data(fwapi_get("nics/$eth/firewall"));
    if(!is_array($cur)){$tpl->js_error("{fw_iface_unknown}");return false;}
    $new=intval($cur["firewall_masquerade"])==1?0:1;
    // Le daemon propage aux ponts qui sortent par $eth et, à la désactivation,
    // désactive les masquerades de fw.masquerade.php sur cette interface.
    $e=fwapi_error(fwapi_send("PUT","nics/$eth/firewall",array("firewall_masquerade"=>$new)));
    if($e!=""){$tpl->js_error($e);return false;}
    if($new==1){
        $dicontent=base64_encode($tpl->button_medium("MASQUERADE","ok",$MASQ_js));
    }else{
        $dicontent=base64_encode($tpl->_ENGINE_parse_body($tpl->button_medium("{inactive}","default",$MASQ_js)));
    }
    echo "var _me=document.getElementById('$id'); if(_me){_me.innerHTML=base64_decode('$dicontent');}";
    return admin_tracks(($new==1?"Enable":"Disable")." MASQUERADE on Interface $eth");
}

function nic_config_deny():bool{
    $tpl=new template_admin();
    return $tpl->js_error("{nic_config_deny}");
}

function ModifyMacVlanName_js():bool{
    $tpl=new template_admin();
    $value=$_GET["value"];
    $page=CurrentPageName();
    $md=$_GET["md"];
    return $tpl->js_dialog8("{rename} $value","$page?ModifyMacVlanName-popup=$value&md=$md",550);
}

function ModifyMacVlanName_popup():bool{
    $value=$_GET["ModifyMacVlanName-popup"];
    $md=$_GET["md"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(preg_match("#^veth([0-9]+)$#",$value,$matches)){
        $index=$matches[1];
    }
    $form[]=$tpl->field_hidden("macvlansource",$value);
    $form[]=$tpl->field_numeric("macvlansuffix","{suffix2}",$index);
    $security="AsSystemAdministrator";
    $js="dialogInstance8.close();LoadAjaxSilent('div-works-$value','$page?nic-config2=$value&md=$md');";
    $html[]=$tpl->form_outside("", $form,"{modSuffixInterface}","{apply}",$js.refreshjs($value),$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ModifyMacVlanName_save():bool{
    if(!netif_require_admin()){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $macvlansource=$_POST["macvlansource"];
    $macvlansuffix=$_POST["macvlansuffix"];
    $macvlanNext="veth$macvlansuffix";
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="UPDATE nics SET Interface='$macvlanNext' WHERE Interface='$macvlansource'";
    writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
    // Pentest 2026-11-04 : macvlansource/macvlansuffix etaient concatenes dans la requete.
    $q->QUERY_SQL_PARAMS("UPDATE nics SET Interface=? WHERE Interface=?",array($macvlanNext,$macvlansource));
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/macvlan/delete/$macvlansource");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/macvlan/build/$macvlanNext");
    return admin_tracks("Change interface name from $macvlansource to $macvlanNext");
}



function options_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
   return $tpl->js_dialog("{options}","$page?options-popup=yes");
}
function ping_results_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $data=$_GET["ping-result"];
    return $tpl->js_dialog("PING","$page?ping-popup=$data");
}

function options_popup(){
    $tpl=new template_admin();

    $bondEnabled = intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableInterfaceBond")));


    $IfConfigdelayed=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IfConfigdelayed")));
    $form[]=$tpl->field_checkbox("IfConfigdelayed","{delayed_startup}",$IfConfigdelayed,false,"{delayed_startup_network}");
    $form[]=$tpl->field_checkbox("EnableInterfaceBond","{bond}",$bondEnabled,false,"{bond_explain}");
    $rf=refreshjs();
    $hml[]=$tpl->form_outside("{options}", @implode("\n", $form),null,"{apply}","$rf","AsSystemAdministrator");
    echo @implode("\n", $hml);
}
function option_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $delayed=intval($_POST["IfConfigdelayed"]);
    $bonding=intval($_POST["EnableInterfaceBond"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/delayed?enabled=$delayed&bonding=$bonding");
}

function Save_OSPF(){
    if(!netif_require_admin(true)){return false;}
    $eth    = $_GET["OSPF"];
    $caz    = new system_nic($eth);
    $ospf   = $caz->ospf_enable;
    if($ospf==1){$ospf=0;}else{$ospf=1;}
    $caz->ospf_enable=$ospf;
    $caz->NoReboot=true;
    $caz->SaveNic();


}

function disabled_interfaces_save(){
    if(!netif_require_admin(true)){return false;}
	$page=CurrentPageName();
	$eth=$_GET["enable-interface-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	
	
	// Pentest 2026-11-04 : enable-interface-js etait concatene dans la requete.
	$q->QUERY_SQL_PARAMS("UPDATE nics SET `enabled`='1' WHERE `Interface`=?",array($eth));
	header("content-type: application/x-javascript");
	if(!$q->ok){
		echo "alert('MySQL error ! ".$q->mysql_error."');\n";
		return;
	}
	
	echo "// eth:$eth\n";
	echo "LoadAjaxSilent('disabled_interfaces_popup','$page?disabled-interfaces-table=yes');\n";
	echo "LoadAjax('network-interfaces-table','$page?table=yes');\n";
}
function proxyarp(){
    if(!netif_require_admin(true,"AsFirewallManager")){return false;}
    $eth=$_GET["proxyarp"];
    $nicz=new system_nic($eth);
    if($nicz->proxyarp==0){$nicz->proxyarp=1;}else{$nicz->proxyarp=0;}
    $nicz->NoReboot=true;
    if(!$nicz->SaveNic()){
        echo $nicz->mysql_error;
        return false;
    }
    admin_tracks("Define interface $eth as Proxy ARP to $nicz->proxyarp");
    $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("net.ipv4.conf.$eth.proxy_arp",$nicz->proxyarp);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    return true;
}
function isFW(){
    if(!netif_require_admin(true,"AsFirewallManager")){return false;}
	$eth=preg_replace('/[^A-Za-z0-9._:-]/','',(string)$_GET["isFW"]);
	$tpl=new template_admin();
	$cur=fwapi_data(fwapi_get("nics/$eth/firewall"));
	if(!is_array($cur)){$tpl->js_error("{fw_iface_unknown}");return false;}
	$new=intval($cur["isFW"])==1?0:1;
	$e=fwapi_error(fwapi_send("PUT","nics/$eth/firewall",array("isFW"=>$new)));
	if($e!=""){$tpl->js_error($e);return false;}
	return admin_tracks("Define interface $eth as Firewall to $new");
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $users=new usersMenus();
    $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));


    $array["{YOUR_INTERFACES}"]="$page?status=yes";
    if($users->AsSystemAdministrator) {
        if($DisableNetworking==0) {
            $array["{nic_infos} ({details})"] = "$page?table-start=yes";
        }
    }
	$array["{open_ports}"]="fw.openports.php";
    echo $tpl->tabs_default($array);

}

function nic_config_js(){
    $md=$_GET["md"];
	$page=CurrentPageName();
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

	$eth=$_GET["nic-config-js"];
	// A port of an L2 switch is configured by its switch, not here.
	$L2RESERVED=l2sw_reserved_ports();
	if(isset($L2RESERVED[$eth])){
		echo "LoadAjax('MainContent','fw.network.l2switch.php?switch-id=".intval($L2RESERVED[$eth]["switch_id"])."');";
		return;
	}
	$nicz=new system_nic($eth,true);
	$title="$nicz->netzone: $nicz->NICNAME ($eth)";
	$tpl->js_dialog1($title, "$page?nic-config-tab=$eth&md=$md",1024);
}
function nic_name_interface_js():bool{
    $md=$_GET["md"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["nic-name-interface"];
    return $tpl->js_dialog5("$eth {name}", "$page?nic-name-interface-popup=$eth&md=$md",750);
}
function nic_addr_js():bool{
    $md=$_GET["md"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["nic-address"];
    return $tpl->js_dialog5("$eth {address}", "$page?nic-address-popup=$eth&md=$md",750);
}
function nic_gateway_js():bool{
    $md=$_GET["md"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["nic-gateway"];
    return $tpl->js_dialog5("$eth {gateway}", "$page?nic-gateway-popup=$eth&md=$md",750);
}


function disabled_interfaces_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

	return $tpl->js_dialog1("{disabled_interfaces}", "$page?disabled-interfaces-popup=yes");
	
}

function sysctl_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

	return $tpl->js_dialog2("{kernelnetworkoptimization}", "$page?sysctl-popup=yes");
}

function sysctl2_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    return $tpl->js_dialog2("{kernelnetworkoptimization}", "$page?sysctl2-popup=yes");
}

function MTPTCP_JS():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        return $tpl->js_no_privileges();
    }

	return $tpl->js_dialog2("{APP_MULTIPATH_TCP}", "$page?MTPTCP-popup=yes");
}


function MTPTCP_POPUP():bool{
	$tpl=new template_admin();
	$APP_MULTIPATH_TCP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MULTIPATH_TCP_VERSION");
	
	// Compteurs SNMP en lecture seule: c'est une liste de valeurs, pas d'entites.
	// table_form est le rendu prevu pour ce cas; deux colonnes car ils sont nombreux.
	$tpl->table_form_columns(2);
	$tpl->table_form_section("{APP_MULTIPATH_TCP} v$APP_MULTIPATH_TCP_VERSION","/proc/net/mptcp_net/snmp");

	$count=0;
	$f=explode("\n",@file_get_contents("/proc/net/mptcp_net/snmp"));
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^(.+?)\s+([0-9]+)#", $line,$re)){continue;}
		$tpl->table_form_field_text(htmlspecialchars($re[1]),FormatNumber($re[2]),ico_field);
		$count++;
	}
	if($count==0){
		$tpl->table_form_field_warn("{no_data}");
	}

	echo $tpl->table_form_compile();
	echo "<script>NoSpinner();</script>";
	return true;
}


function EnableSystemNetworkOptimize(){
	$tpl=new template_admin();
	$ipv6err=netif_ipv6_from_form();
	$tpl->SAVE_POSTs();
	if($ipv6err<>""){echo $tpl->post_error($ipv6err);}
}

/**
 * La case « Enable IPv6 » des deux formulaires de reglages noyau (sysctl_popup,
 * sysctl2_popup) ne s'ecrit plus par /setinfo comme les autres champs : seul le
 * reglage changeait, ni le noyau, ni le reseau, ni les services ne suivaient.
 * On la retire du POST et, si elle change, on passe par la meme route que le
 * widget IPv6 (/system/network/ipv6/enable/{0|1}, articarest rest_ipv6.go),
 * qui reconfigure tout en arriere-plan.
 * Retourne "" ou le message d'erreur a afficher APRES l'enregistrement des
 * autres champs (un refus IPv6 ne doit pas perdre le reste du formulaire).
 */
function netif_ipv6_from_form():string{
    if(!isset($_POST["EnableipV6"])){return "";}
    $want=intval($_POST["EnableipV6"])==1?1:0;
    unset($_POST["EnableipV6"]);
    $cur=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
    if($want==$cur){return "";}
    admin_tracks($want==1?"Enable IPv6 on the system":"Disable IPv6 on the system");
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/ipv6/enable/$want");
    $json=json_decode((string)$data);
    if(!is_object($json)){
        $raw=trim(str_replace(array("'","\\"),array(" "," "),(string)$data));
        return $raw==""?"{error}":substr($raw,0,300);
    }
    if(!$json->Status){
        return property_exists($json,"Error")&&$json->Error<>""?(string)$json->Error:"{error}";
    }
    return "";
}

function disabled_interfaces_popup(){
	$page=CurrentPageName();
	echo "<div id='disabled_interfaces_popup'></div><script>LoadAjaxSilent('disabled_interfaces_popup','$page?disabled-interfaces-table=yes');</script>";
	
}

function disabled_interfaces_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$TRCLASS=null;
	
	$qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$sql="SELECT Interface FROM nics WHERE enabled=0";
	$results=$qlite->QUERY_SQL($sql);

	$rows=array();
	foreach ($results as $index=>$ligne){
		$eth=$ligne["Interface"];
		$nicz=new system_nic($eth);
		$enable=$tpl->icon_check(0,"Loadjs('$page?enable-interface-js=$eth')",null,"AsSystemAdministrator");
		$meta=array();
		$meta[]=htmlspecialchars($eth);
		if(strlen(trim(strval($nicz->IPADDR)))>0){$meta[]=htmlspecialchars($nicz->IPADDR);}
		$h ="<tr>";
		// Interface desactivee: pastille et libelle grises, coherent avec l'action absente.
		$h.="<td>".netif_ident(ico_nic,htmlspecialchars("$nicz->netzone: $nicz->NICNAME"),$meta,null,true)."</td>";
		$h.="<td class='center'>".netif_state_badge("off","{disabled}")."</td>";
		$h.="<td>".netif_acts(array($enable))."</td>";
		$h.="</tr>";
		$rows[]=$h;
	}

	$html=array();
	$html[]=netif_table_open(array(
		"<th>{interface}</th>",
		"<th class='center'>{status}</th>",
		"<th style='text-align:right'>{actions}</th>"),"nif-rows-disabled");
	if(count($rows)==0){
		$html[]=netif_table_empty(3);
	}else{
		$html[]=@implode("\n",$rows);
	}
	$total=count($rows);
	$html[]=netif_table_close($total,$total,"{interfaces}","nif-pager-disabled");
	// Le bloc <script> etait DANS $html, donc analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body lit chaque corps de fonction JavaScript comme un
	// {token} -- icon_check() en emet TROIS par case a cocher, ce qui fait
	// autant d'interrogations memcached, et un corps qui correspondrait a une
	// vraie cle serait purement et simplement remplace DANS le JavaScript.
	// Et ICON_SCRIPTS_COMPILE() plutot que la pile d'instance: icon_check()
	// alimente en parallele $GLOBALS["TPL_ICON_SCRIPTS"], que seule la version
	// COMPILE draine -- la pile d'instance est vide des qu'une ligne est batie
	// par une fonction qui fait son propre new template_admin().
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style().netif_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(25,"nif-rows-disabled","nif-pager-disabled");

}

function status(){
    $page=CurrentPageName();


    echo "<div id='netz-interfaces-status'></div>
    <script>
    function InterfaceMainStatusLoad(){
    LoadAjaxSilent('netz-interfaces-status','$page?status2=yes');
    }\nInterfaceMainStatusLoad();</script>";
}

/** Adresses IPv6 d'une interface, en texte brut "adresse/prefixe" (non echappe). */
function status_ipv6_list($nic):array{
    $f=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/ip6/".urlencode($nic)));
    if(!is_array($json) && !is_object($json)){return $f;}
    foreach ($json as $ipcl) {
        if(!is_object($ipcl)){continue;}
        if(!property_exists($ipcl,"IpAddr")){continue;}
        $cdir=property_exists($ipcl,"NetMask")?$ipcl->NetMask:"";
        $f[]=$ipcl->IpAddr.(strlen(strval($cdir))>0?"/$cdir":"");
    }
    return $f;
}
function status2_dhcp_standard($nicz):string{
    if($nicz->dhcp==0){return "";}
    return "&nbsp;<span class='label label-success'>DHCP</span>";
}
/**
 * Etat DHCP d'une interface, en bascule compacte.
 * Meme logique que status2_dhcp(), mais rendue en pastille a 4 etats
 * (non installe / declare / arrete / en marche) au lieu d'un bouton.
 * Retourne array($state,$js).
 */
function status2_dhcp_toggle($Interface,$nicz):array{
    $UDHCPD_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UDHCPD_INSTALLED"));
    $udhcpd_status=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/udhcpd/status"));
    if(is_object($json)){
        if(property_exists($json,"Info")){
            foreach($json->Info as $key=>$pid){$udhcpd_status[$key]=$pid;}
        }
    }
    if($UDHCPD_INSTALLED==1){
        if(intval($nicz->udhcpd)==1){
            $js="Loadjs('fw.udhcpd.php?interface=$Interface');";
            if(isset($udhcpd_status[$Interface])){
                if($udhcpd_status[$Interface]>0){return array("on",$js);}
                return array("err",$js);
            }
            return array("warn",$js);
        }
    }
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
    if($EnableDHCPServer==1 OR $EnableKEA==1){
        $dhcp=new dhcpd(0,1,$Interface);
        if($dhcp->service_enabled==1){
            return array("on","Loadjs('fw.dhcp.configuration.php?spopup-interface=$Interface')");
        }
    }
    return array("off","");
}

function status2_dhcp($Interface,$nicz):string{
    $tpl=new template_admin();
    $UDHCPD_INSTALLED   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UDHCPD_INSTALLED"));
    $UDHCPD = $tpl->button_medium("{not_installed}", "none", null);


    if(!isset($GLOBALS["UDHCP_STATUS"])) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/udhcpd/status"));
        foreach ($json->Info as $key => $pid) {
            $udhcpd_status[$key] = $pid;
        }
    }
    if($UDHCPD_INSTALLED==1){
        $udhcp_js="Loadjs('fw.udhcpd.php?interface=$Interface');";
        if($nicz->udhcpd==1){
            $UDHCPD = $tpl->button_medium("{active2}","warning",$udhcp_js);
            if(isset($udhcpd_status[$Interface])){
                $UDHCPD = $tpl->button_medium("{stopped}","error",$udhcp_js);
                if($udhcpd_status[$Interface]>0){
                    $UDHCPD = $tpl->button_medium("{running}","ok",$udhcp_js);
                }
            }
            return $UDHCPD;
        }
    }
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
    if($EnableDHCPServer==1 OR $EnableKEA==1){
        $dhcp       = new dhcpd(0, 1, $Interface);
        if($dhcp->service_enabled==1){
            $udhcp_js="Loadjs('fw.dhcp.configuration.php?spopup-interface=$Interface')";
            return $tpl->button_medium("{running}","ok",$udhcp_js);

        }


    }
    return $UDHCPD;
}

function Widget_isGateway():string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $users              = new usersMenus();
    $EnableArticaAsGateway=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway")));

    $MustBeAgateway=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MustBeAgateway"));
    // Le daemon publie dans IsagetwayWhy le motif du mode passerelle. Quand
    // l'exploitant a force le mode a OFF alors qu'une cause tenait encore
    // (modale de deverrouillage), ce motif est prefixe : la divergence est
    // assumee, mais elle doit rester VISIBLE -- sinon plus personne ne sait,
    // six mois plus tard, pourquoi le pont ne route pas.
    $IsagetwayWhy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsagetwayWhy"));
    $ForcedOffMark="{gateway_forced_off_but}|";
    $ForcedOffWhy="";
    if(substr($IsagetwayWhy,0,strlen($ForcedOffMark))==$ForcedOffMark){
        $ForcedOffWhy=trim(substr($IsagetwayWhy,strlen($ForcedOffMark)));
    }
    $EnableRedSocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedSocks"));
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));

    if($Enablehacluster==1){
        $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
        if($HaClusterTransParentMode==1){
            $MustBeAgateway=1;
        }
    }
    if($EnableRedSocks==1){
        $MustBeAgateway=1;
    }

    $LockButton=false;
    if ($MustBeAgateway==1) {
        $EnableArticaAsGateway=1;
        $LockButton=true;
    }
    if($EnableArticaAsGateway==0) {
        $button["name"]="{enable}";
        $button["js"]="Loadjs('$page?AsGateway-js=1')";

        $button2["name"]="{options}";
        $button2["ico"]="fa fa-wrench";
        $button2["js"]="Loadjs('$page?AsGateway-options=yes')";

        if(!$users->AsSystemAdministrator){
            $button["js"]="blur()";
            $button2["js"]="blur();";
        }
        if($ForcedOffWhy<>""){
            // Jaune, pas gris : le mode est bien eteint, mais pas parce que la
            // machine n'en a plus besoin -- parce qu'un humain l'a decide contre
            // elle. Les deux etats ne se lisent pas pareil.
            return $tpl->widget_h("yellow", "far fa-ethernet", "{disabled}",
                "{act_as_gateway}: {gateway_forced_off_but} $ForcedOffWhy",$button,$button2);
        }
        return $tpl->widget_h("gray", "far fa-ethernet", "{disabled}", "{act_as_gateway}",$button,$button2);

    }

        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/isgateway"));

        $button["name"]="{disable}";
        $button["js"]="Loadjs('$page?AsGateway-js=0')";

        $button2["name"]="{options}";
        $button2["ico"]="fa fa-wrench";
        $button2["js"]="Loadjs('$page?AsGateway-options=yes')";

        if($LockButton){
            $button["name"]="{locked}";
            $button["js"]="Loadjs('$page?AsGateway-unlock-js=yes');";
            $button["ico"]=ico_lock;
        }

        if(!$users->AsSystemAdministrator){
            $button["js"]="blur()";
            $button2["js"]="blur();";
        }


        if($json->ip_forward==0){
            return  $tpl->widget_h("yellow", "far fa-ethernet", "{not_saved}", "{act_as_gateway}",$button,$button2);
        }
        return $tpl->widget_h("green", "far fa-ethernet", "{enabled}", "{act_as_gateway}", $button, $button2);



}

function status2(){
    $security           = "AsSystemAdministrator";
	$tpl                = new template_admin();
	$t                  = time();
	$page               = CurrentPageName();
    $users              = new usersMenus();
    $pattern_exclude    ="^(dummy|teql|ip6tnl|tunl|gre|ifb|sit|gretap|erspan)[0-9]+";
    $GW                 = Widget_isGateway();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/listnics"));
    $zPhysicalsInterfaces=unserialize(base64_decode($data->nics));

    $DefaultGateways=array();
    if(property_exists($data, "Gateways")){
        foreach($data->Gateways as $gateway){
            $interface=$gateway->interface;
            $DefaultGateways[$interface]=array(
                "gw"=>$gateway->gatewayIP,
                "metric"=>$gateway->metric
            );

        }
    }
    $MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
	$EnableMunin        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
	$EnableOSPFD        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOSPFD"));
	$EnableTailScaleService = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTailScaleService"));
    $TRCLASS=null;

	foreach ($zPhysicalsInterfaces as $Interface){
        $PhysicalsInterfaces[$Interface]=true;
    }

    $EnableVLANs=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVLANs");
	$qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="SELECT Interface FROM nics WHERE enabled=1";
	$results=$qlite->QUERY_SQL($sql);

	if(!$qlite->ok){echo $qlite->mysql_error_html();}
	VERBOSE("$sql ".count($results)." items",__LINE__);
	if($GLOBALS["VERBOSE"]){print_r($results);}
	
	$IntefaceCount=0;


    $Interface="";
    $EnableSystemNetworkOptimize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSystemNetworkOptimize"));
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));

    if($EnableSystemNetworkOptimize==0) {
        $button["ico"]=ico_check;
        $button["name"]="{install}";
        $button["js"]="Loadjs('$page?netoptimize-js=1')";
        if(!$users->AsSystemAdministrator){$button=array();}
        $OPTZ = $tpl->widget_h("gray", "fas fa-signal", "{disabled}", "{SystemNetworkOptimize}",$button);
    }else{
        $button["ico"]="fas fa-signal";
        $button["name"]="{disable}";
        $button["js"]="Loadjs('$page?netoptimize-js=0')";
        if(!$users->AsSystemAdministrator){$button=array();}
        $OPTZ = $tpl->widget_h("green", "fas fa-signal", "{enabled}", "{SystemNetworkOptimize}",$button);
    }
    if($FireHolEnable==0) {
        $button["ico"]=ico_check;
        $button["name"]="{install}";
        $button["js"]="Loadjs('$page?YourFirewall-js=1')";
        if(!$users->AsFirewallManager){$button=array();}
        $FW = $tpl->widget_h("gray", "fad fa-shield-alt", "{disabled}", "{your_firewall}",$button);
    }else{
        $button["ico"]="fad fa-shield-alt";
        $button["name"]="{disable}";
        $button["js"]="Loadjs('$page?YourFirewall-js=0')";
        if(!$users->AsFirewallManager){$button=array();}
        $FW = $tpl->widget_h("green", "fad fa-shield-alt", "{enabled}", "{your_firewall}",$button);
    }
    $EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
    // Meme droit que EnableIPV6_js() : un bouton visible qui repond
    // « pas les privileges » est pire qu'un bouton absent.
    $button=array();
    if($EnableipV6==0){
        $button["ico"]=ico_check;
        $button["name"]="{enable}";
        $button["js"]="Loadjs('$page?enableIPV6-js=1')";
        if(!$users->AsSystemAdministrator){$button=array();}
        $IPV6 = $tpl->widget_h("gray", ico_networks,
            "{inactive2}", "IPv6",$button);
    }else{
        $button["ico"]=ico_disabled;
        $button["name"]="{disable}";
        $button["js"]="Loadjs('$page?enableIPV6-js=0')";
        if(!$users->AsSystemAdministrator){$button=array();}
        $IPV6 = $tpl->widget_h("green",
            ico_networks, "{active2}", "IPv6",$button);
    }



	
	$html[]="<div style='margin-top:20px'>";
    $html[]="<table style='width:100%'>
	    <tr>
	    <td style='vertical-align:top;width:200px;padding:8px'>$GW</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$OPTZ</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$IPV6</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$FW</td>
	    </tr>
	   </table>";
    $html[]=netif_cards_open();

    $OpenVswitchEnable  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVswitchEnable"));
    $EnableVnStat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVnStat"));
    $PARPROUTED_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PARPROUTED_INSTALLED"));

    if($EnableTailScaleService==1){
        $results[]=array(
            "Interface"=>"tailscale0",
        );
    }

    $rows=array();
    $ALREADY_INTERFACE=array();
    $EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
    $EnableDebianAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDebianAgent"));
	foreach($results as $index=>$ligne) {
        $md=md5(serialize($ligne));
        if (!isset($PhysicalsInterfaces[$ligne["Interface"]])) {
            VERBOSE(" ------------------- Interface $Interface Not a physical interface", __LINE__);
            continue;
        }
        $Interface = $ligne["Interface"];
        $text_class = null;
        $ALREADY_INTERFACE[$Interface] = true;
        if (preg_match("#$pattern_exclude#", $Interface)) {
            continue;
        }
        VERBOSE($ligne["Interface"], __LINE__);
        $MAIN=array("SPEED"=>0,"TX"=>0,"RX"=>0,"DROP"=>0,"STATE"=>"");
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/info/$Interface"));


        if(property_exists($json,"State")){
            $MAIN["STATE"]=$json->State;
        }

        if(property_exists($json,"Stats")){
            $MAIN["SPEED"]=$json->Stats->SPEED;
            $MAIN["TX"]=$json->Stats->TX;
            $MAIN["RX"]=$json->Stats->RX;
            $MAIN["DROP"]=$json->Stats->DROP;
        }
        $nicz = new system_nic($Interface);
        $NICNAME = $nicz->NICNAME;
        $UseSPAN = $nicz->UseSPAN;
        $speed = intval($MAIN["SPEED"]);
        $TX = $MAIN["TX"];
        $RX = $MAIN["RX"];
        $DROP = $MAIN["DROP"];
        $stateKind = "warn";
        $PingGateway = "";

        VERBOSE(" ------------------- $index Interface $Interface", __LINE__);
        if (isset($STATUS_INTERFACE[$Interface])) {
            VERBOSE(" -------------------$index  Interface $Interface ALREADY SET", __LINE__);
            continue;
        }


        $JS = "Loadjs('fw.network.interfaces.php?nic-config-js=$Interface&md=$md');";
        if ($Interface == "tailscale0") {
            $JS = "Loadjs('fw.network.interfaces.php?nic-config-deny=$Interface');";
        }


        $speed_text = "";

        if ($speed > 0) {
            $speed_text = $speed . "Mbits/sec";
            if ($speed == 10000) {
                $speed_text = "10 Gigabits/s";
            }
        }




        if($MAIN["STATE"]=="up") {
            $PingGateway = netif_pill("up","UP");
        }

        $NetStatus=new NetStatus($Interface);
        if($OpenVswitchEnable==0){
            $nicz->virtualbridge=0;
        }

        if ($NetStatus->InterfaceRunning){
            $stateKind="up";
            $text_icon="{active2}";
            if(($UseSPAN==0) AND ($nicz->virtualbridge==0)){
                $PingGateway=status2_pingGateway($Interface,$nicz->GATEWAY);
            }
        }else {
            $stateKind="warn";
            $text_icon = "{inactive}";
        }
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        // $Interface vient de la base, mais il transite par l'URL sur d'autres chemins:
        // on l'assainit avant de le poser dans un litteral SQL.
        $ifq=netif_sql_safe($tpl,$Interface);
        $ligne3=$q->mysqli_fetch_array("SELECT firewall_masquerade from nics WHERE Interface='$ifq'");
        $firewall_masquerade=intval($ligne3["firewall_masquerade"]);
        $MASQ_js="Loadjs('$page?masq-interface=$Interface');";

        $carrier_changes=intval(@file_get_contents("/sys/class/net/$Interface/carrier_changes"));

        if($UseSPAN==1){
            $stateKind="warn";
            $text_icon="{mirror}";
        }

        // carrier_changes compte les transitions (montee + descente): deux par coupure.
        $carrierCuts=0;
        if($carrier_changes>2){$carrierCuts=intval($carrier_changes/2);}

        // Fiche: le nom porte le lien de configuration, le reste devient
        // metadonnee, pastilles et bandeaux (maquette stitch_network_status2).
        $isBond=preg_match("#^bond[0-9]+#", $Interface)?true:false;
        $line=array();
        if(strlen(trim(strval($NICNAME)))>0){$line[]="<span class='nif-strong'>".htmlspecialchars(strval($NICNAME))."</span>";}
        $ipShown=trim(strval($NetStatus->IPADDR));
        if($ipShown=="0.0.0.0"){$ipShown="";}
        if($ipShown<>""){$line[]="<strong>".htmlspecialchars($ipShown)."</strong>";}
        else{$line[]="<span class='nif-muted-i'>{nif_no_address}</span>";}
        if($speed>0){$line[]="<span class='ak-muted'>$speed_text</span>";}

        $pills=array();
        $pills[]=netif_pill($stateKind=="up"?"up":"warn",$text_icon);
        if($MAIN["STATE"]=="down"){$pills[]=netif_pill("off","DOWN");}
        $pills[]=$PingGateway;
        if($carrierCuts>0){
            $pills[]=netif_pill("warn","<i class='fa-solid fa-plug'></i> $carrierCuts",
                "Loadjs('$page?carrier-change-explain-js=yes')");
        }
        if($nicz->macvlan==1){$pills[]=netif_pill("tag","Macvlan ".htmlspecialchars(strval($nicz->physical)));}
        if($nicz->ipvlan==1){$pills[]=netif_pill("tag","IPvlan ".htmlspecialchars(strval($nicz->physical)));}
        if($isBond){$pills[]=netif_pill("tag","{bond}");}
        if($nicz->dhcp==1){$pills[]=netif_pill("tag","{nif_dhcp_client}");}

        $bars=array();
        if(isset($DefaultGateways[$Interface])) {
            $bars[]=array("hl"=>true,
                "label"=>"<i class='fa-solid fa-arrow-right'></i> {default_gateway}: <strong>".htmlspecialchars(strval($DefaultGateways[$Interface]["gw"]))."</strong>",
                "value"=>"{metric} ".htmlspecialchars(strval($DefaultGateways[$Interface]["metric"])));
        }else{
            $gw=trim(strval($nicz->GATEWAY));
            if($gw=="" || $gw=="0.0.0.0"){
                $bars[]=array("label"=>"{gateway}","value"=>"<span class='nif-muted-i'>{nif_no_gateway}</span>");
            }else{
                $bars[]=array("label"=>"{gateway}","value"=>htmlspecialchars($gw));
            }
        }
        if($EnableipV6==1){
            $v6=status_ipv6_list($Interface);
            if(count($v6)>0){$bars[]=array("label"=>"IPv6","value"=>htmlspecialchars(@implode(", ",$v6)));}
        }

        $cardActs=array();
        if($isBond){$cardActs[]=netif_card_icon("fa-solid fa-magnifying-glass","Loadjs('$page?load-bond-info-js=$Interface')","{bond} {info}");}
        if($MUNIN_CLIENT_INSTALLED==1 AND $EnableMunin==1 AND $users->AsSystemAdministrator){
            $cardActs[]=netif_card_icon("fa-solid fa-chart-column","Loadjs('fw.network.interfaces.munin.php?interface=$Interface')","Munin");
        }
        if($EnableVnStat==1){
            $cardActs[]=netif_card_icon("fa-solid fa-chart-line","Loadjs('fw.system.vnstati.php?interface=$Interface')","vnStat");
        }
        if($EnableDebianAgent==0){
            $cardActs[]=netif_card_icon("fa-solid fa-chart-area","Loadjs('fw.rrd.php?iface={$Interface}')","{statistics}");
        }
        if($users->AsSystemAdministrator){$cardActs[]=netif_card_edit($JS);}

        $cardIco=ico_nic;
        if($isBond){$cardIco="fa-solid fa-link";}
        if($Interface=="tailscale0"){$cardIco="fa-solid fa-fingerprint";}
        if(!$NetStatus->InterfaceRunning){$cardIco="fa-solid fa-plug-circle-xmark";}

        // Une bascule par option au lieu d'une colonne par option.
        $toggles=array();
        $toggles[]=array("label"=>"MASQ","on"=>$firewall_masquerade,
            "js"=>$users->AsSystemAdministrator?$MASQ_js:"");
        if($EnableOSPFD==1){
            $toggles[]=array("label"=>"OSPF","on"=>intval($nicz->ospf_enable),
                "js"=>$users->AsSystemAdministrator?"Loadjs('$page?OSPF=".urlencode($Interface)."')":"");
        }
        if($PARPROUTED_INSTALLED==1){
            $toggles[]=array("label"=>"ARP","on"=>intval($nicz->parprouted),
                "js"=>$users->AsSystemAdministrator?"Loadjs('fw.parprouted.php?interface=$Interface');":"");
        }
        list($dhcpState,$dhcpJs)=status2_dhcp_toggle($Interface,$nicz);
        $toggles[]=array("label"=>"DHCP","state"=>$dhcpState,
            "js"=>$users->AsSystemAdministrator?$dhcpJs:"");
        if($UseSPAN==1){$toggles=array(array("label"=>"SPAN","state"=>"warn"));}

        // L'ancien bouton « Appliquer » par ligne lancait la meme reconfiguration
        // globale que le bouton {apply_network_configuration} de l'en-tete: il
        // n'est pas repris sur les fiches, l'en-tete le porte deja.
        $rows[]=netif_card(array(
            "state"=>$stateKind,
            "ico"=>$cardIco,
            "name"=>htmlspecialchars($Interface),
            "js"=>$JS,
            "pills"=>$pills,
            "line"=>$line,
            "mac"=>strval($NetStatus->MacAddr),
            "bars"=>$bars,
            "toggles"=>netif_toggles($toggles),
            "rx"=>$RX,"tx"=>$TX,"drop"=>$DROP,
            "acts"=>@implode("",$cardActs),
            "search"=>"$Interface $NICNAME $ipShown ".($isBond?"bond":"").($UseSPAN==1?" span":""),
        ));
        $IntefaceCount++;
        $STATUS_INTERFACE[$Interface]=true;
        if($EnableVLANs==1){
            VERBOSE("STATUS VLAN OF $Interface..",__LINE__);
            $vlanRows=status2_vlan($Interface);
            foreach($vlanRows as $vr){ $rows[]=$vr; }
        }
	}
    foreach(status2_ghosts($ALREADY_INTERFACE) as $gr){ $rows[]=$gr; }
    foreach(status2_samba_instances() as $sr){ $rows[]=$sr; }

    $html[]=@implode("\n",$rows);
    $total=count($rows);
    $html[]=netif_cards_close($total);
    $html[]="</div>";
    $topbuttons=array();

    $jsrestart=$tpl->framework_buildjs("/system/network/reconfigure-restart",
        "reconfigure-newtork.progress",
        "exec.virtuals-ip.php.html","progress-firehol-restart",
        "LoadAjax('netz-interfaces-status','$page?status2=yes');"
    ); // apply_network_configuration

    $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
    $EnableWebBandwhich=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebBandwhich"));
    $EnableARPScanner=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableARPScanner"));
    if($EnableWebBandwhich==1) {
        $topbuttons[] = array("s_PopUp('/bandwhich/',1024,768,);",
            ico_monitor, "{network_monitor}");
    }
    if($users->AsSystemAdministrator) {
        if ($DisableNetworking == 0) {
            $topbuttons[] = array($jsrestart,
                ico_save, "{apply_network_configuration}");
        }
    }

    if($EnableARPScanner==1){
        $topbuttons[] = array("Loadjs('$page?ArpScanner=0')",
            ico_monitor, "{APP_NETDISCOVER} ({active2})");
    }else{
        $topbuttons[] = array("Loadjs('$page?ArpScanner=1')",
            ico_monitor, "{APP_NETDISCOVER} ({inactive2})");
    }


    $btns=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{network_interfaces}";
    $TINY_ARRAY["ICO"]="fa fa-exchange";
    $TINY_ARRAY["EXPL"]="{network_interfaces_explain}";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



	// Le bloc <script> etait DANS $html, donc analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body lit chaque corps de fonction JavaScript comme un
	// {token} -- icon_check() en emet TROIS par case a cocher, ce qui fait
	// autant d'interrogations memcached, et un corps qui correspondrait a une
	// vraie cle serait purement et simplement remplace DANS le JavaScript.
	// Et ICON_SCRIPTS_COMPILE() plutot que la pile d'instance: icon_check()
	// alimente en parallele $GLOBALS["TPL_ICON_SCRIPTS"], que seule la version
	// COMPILE draine -- la pile d'instance est vide des qu'une ligne est batie
	// par une fonction qui fait son propre new template_admin().
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny\n</script>";

    if($DisableNetworking==0) {
        if ($IntefaceCount == 0) {
            echo $tpl->FATAL_ERROR_SHOW_128("{no_interface_saved_explain}");
        }
    }
	echo awc_table_style().netif_style().netif_cards_style()
		.$tpl->_ENGINE_parse_body(status2_config_ghosts().@implode("\n",(array)$html))
		.netif_cards_js()
		.$script;
}
// ---- cartes fantômes : citées par la configuration, absentes du noyau ----
// Moteur : articarest ipclass/RemoveInterface (ghosts.go), routes
// /system/network/ghosts et /system/network/ghost/remove/{name}.

function ghosts_list():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/ghosts"));
    if(!is_object($json) || empty($json->success) || !isset($json->data) || !is_array($json->data)){return array();}
    return $json->data;
}

// status2_config_ghosts : bandeau au-dessus des cartes, rien quand il n'y a
// aucun fantôme.
function status2_config_ghosts():string{
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){return "";}
    $ghosts=ghosts_list();
    if(count($ghosts)==0){return "";}
    $page=CurrentPageName();
    $names=array();
    foreach($ghosts as $g){$names[]="<strong>".htmlspecialchars((string)$g->name)."</strong>";}
    $btn="<button class='btn btn-warning btn-sm' style='margin-left:10px' OnClick=\"Loadjs('$page?ghosts-js=yes')\">".
        "<i class='fa-solid fa-ghost'></i> {nif_ghosts_review}</button>";
    return "<div class='alert alert-warning'><i class='fa-solid fa-ghost'></i> {nif_ghosts_banner}: ".
        @implode(", ",$names)."$btn</div>";
}

function ghosts_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{nif_ghosts_title}","$page?ghosts-popup=yes",950);
}

function ghosts_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->div_error("{ERROR_NO_PRIVS}");return false;}
    $ghosts=ghosts_list();
    $h=array();
    $h[]=$tpl->div_explain("{nif_ghosts_title}||{nif_ghosts_help}");
    if(count($ghosts)==0){
        $h[]="<p>{nif_ghosts_none}</p>";
        echo $tpl->_ENGINE_parse_body(@implode("\n",$h));
        return true;
    }
    // Cartes présentes sans configuration : seules cibles possibles d'une
    // réaffectation (eth0 → ens192 après une migration).
    $targets=array();
    $tj=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/ghost/targets"));
    if(is_object($tj) && !empty($tj->success) && isset($tj->data) && is_array($tj->data)){
        foreach($tj->data as $tn){if(preg_match('/^[A-Za-z0-9_-]{1,15}$/',(string)$tn)){$targets[]=(string)$tn;}}
    }
    foreach($ghosts as $g){
        $name=(string)$g->name;
        if(!preg_match('/^[A-Za-z0-9_-]{1,15}$/',$name)){continue;}
        $usages=isset($g->usages) && is_array($g->usages) ? $g->usages : array();
        $removable=false;
        foreach($usages as $u){if(!empty($u->removed)){$removable=true;}}
        $md=md5($name);
        $acts=array();
        if(count($targets)>0){
            $opts=array();
            foreach($targets as $tn){$opts[]="<option value='$tn'>$tn</option>";}
            $acts[]="<select id='ghost-target-$md' class='form-control' style='display:inline-block;width:auto;margin-left:10px'>".
                @implode("",$opts)."</select>";
            $acts[]="<button class='btn btn-primary btn-sm' style='margin-left:5px' OnClick=\"Loadjs('$page?ghost-reassign-js=".
                urlencode($name)."&target='+encodeURIComponent(document.getElementById('ghost-target-$md').value))\">".
                "<i class='fa-solid fa-right-left'></i> {nif_ghost_reassign}</button>";
        }
        if($removable){
            $acts[]="<button class='btn btn-danger btn-sm' style='margin-left:10px' OnClick=\"Loadjs('$page?ghost-remove-js=".
                urlencode($name)."')\"><i class='".ico_trash."'></i> {remove}</button>";
        }
        $h[]="<h3 style='margin-top:20px'><i class='fa-solid fa-ghost'></i> ".htmlspecialchars($name).@implode("",$acts)."</h3>";
        $h[]="<table class='table table-bordered'><thead><tr><th>{database}</th><th>{table}</th>".
            "<th>{rows}</th><th>{nif_ghost_effect}</th></tr></thead><tbody>";
        foreach($usages as $u){
            $eff=!empty($u->removed)
                ? "<span class='label label-danger'>{nif_ghost_rows_removed}</span>"
                : "<span class='label label-warning'>{nif_ghost_rows_kept}</span>";
            $h[]="<tr><td>".htmlspecialchars((string)$u->db)."</td><td>".
                htmlspecialchars((string)$u->table.".".(string)$u->column)."</td><td>".intval($u->rows)."</td><td>$eff</td></tr>";
        }
        $h[]="</tbody></table>";
        $settings=isset($g->settings) && is_array($g->settings) ? $g->settings : array();
        if(count($settings)>0){
            $list=array();
            foreach($settings as $k){$list[]="<code>".htmlspecialchars((string)$k)."</code>";}
            $h[]="<div class='alert alert-warning'>{nif_ghost_settings}: ".@implode(", ",$list)."</div>";
        }
    }
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h));
    return true;
}

function ghost_remove_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $name=(string)$_GET["ghost-remove-js"];
    if(!preg_match('/^[A-Za-z0-9_-]{1,15}$/',$name)){return false;}
    return $tpl->js_confirm_delete("{network_interface} $name","ghost-remove",$name,
        "dialogInstance2.close();LoadAjax('netz-interfaces-status','$page?status2=yes');");
}

function ghost_reassign_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $name=(string)$_GET["ghost-reassign-js"];
    $target=(string)$_GET["target"];
    if(!preg_match('/^[A-Za-z0-9_-]{1,15}$/',$name) || !preg_match('/^[A-Za-z0-9_-]{1,15}$/',$target)){return false;}
    return $tpl->js_confirm_execute("{nif_ghost_reassign}: $name → $target","ghost-reassign","$name|$target",
        "dialogInstance2.close();LoadAjax('netz-interfaces-status','$page?status2=yes');");
}

// ghost_reassign_perform : la configuration de la carte fantôme passe sur une
// carte présente. Rien en sortie en cas de succès.
function ghost_reassign_perform(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return;}
    $parts=explode("|",(string)$_POST["ghost-reassign"]);
    if(count($parts)!=2 || !preg_match('/^[A-Za-z0-9_-]{1,15}$/',$parts[0]) || !preg_match('/^[A-Za-z0-9_-]{1,15}$/',$parts[1])){
        echo $tpl->_ENGINE_parse_body("{invalid_interface}");return;
    }
    list($name,$target)=$parts;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/system/network/ghost/reassign/".rawurlencode($name)."/".rawurlencode($target)));
    if(!is_object($json) || empty($json->success)){
        $e=(is_object($json) && isset($json->error)) ? (string)$json->error : "{error}";
        echo $tpl->_ENGINE_parse_body($e);
        return;
    }
    admin_tracks("Reassigned the configuration of ghost network interface $name to $target");
}

// ghost_remove_perform : rien en sortie en cas de succès (la confirmation
// affiche toute sortie comme une erreur).
function ghost_remove_perform(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return;}
    $name=(string)$_POST["ghost-remove"];
    if(!preg_match('/^[A-Za-z0-9_-]{1,15}$/',$name)){echo $tpl->_ENGINE_parse_body("{invalid_interface}");return;}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/system/network/ghost/remove/".rawurlencode($name)));
    if(!is_object($json) || empty($json->success)){
        $e=(is_object($json) && isset($json->error)) ? (string)$json->error : "{error}";
        echo $tpl->_ENGINE_parse_body($e);
        return;
    }
    $n=0;
    if(isset($json->data->removed) && is_array($json->data->removed)){
        foreach($json->data->removed as $u){$n+=intval($u->rows);}
    }
    admin_tracks("Removed ghost network interface $name and $n dependent configuration row(s)");
}

function ping_results_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=base64_decode($_GET["ping-popup"]);
    $json = json_decode($data);
    // Pentest 2026-11-04 : le JSON de ping-popup vient de l'URL et etait recopie sans encodage dans la page (XSS).
    if(!is_object($json)){$json=new stdClass();$json->Status=false;$json->Error="";$json->Info="";}
    if (!$json->Status) {
        echo $tpl->div_error(req_html($json->Error ?? "")."||".req_html($json->Info ?? ""));
        return;
    }
    echo $tpl->_ENGINE_parse_body($tpl->div_explain("PING:||".req_html($json->Info ?? "")));
}
function status2_pingGateway($iface,$gateway):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if($gateway=="0.0.0.0"){
        return "";
    }
    if(strlen($gateway)<4){
        return "";
    }

    $sock=new sockets();
    $data=$sock->REST_API("/system/network/ping/$gateway/$iface");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return "";
    }
    $b64=base64_encode($data);

    // Pastille de fiche (netif_pill), cliquable vers le detail du ping.
    if (!$json->Status) {
        return netif_pill("down","PING ..","Loadjs('$page?ping-result=$b64')");
    }
    return netif_pill("up","PING OK","Loadjs('$page?ping-result=$b64')");
}

/**
 * Interfaces vues par le systeme mais absentes de la configuration Artica.
 * Retourne un tableau de lignes deja formatees (meme gabarit que status2()).
 */
function status2_ghosts($ALREADY_INTERFACES):array{
    $rows=array();
    $tpl=new template_admin();
    $sock=new sockets();
    $page=CurrentPageName();
    $data=$sock->REST_API("/system/network/interfaces");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        VERBOSE("json status == FALSE",__LINE__);
        return $rows;
    }
    if(!is_object($json)){return $rows;}
    if(!property_exists($json,"interfaces")){return $rows;}

    foreach($json->interfaces as $index=>$ligne){
        $Interface=$ligne->Name;
        if($Interface=="lo"){continue;}
        VERBOSE("Interface == $Interface",__LINE__);
        if(isset($ALREADY_INTERFACES[$Interface])){
            continue;
        }

        $NICNAME=$Interface;
        $RX=$ligne->RxBytes;
        $TX=$ligne->TxBytes;
        $DROP=$ligne->Drop;
        $speed_text="";
        if ($ligne->Speed > 0) {
            $speed_text = $ligne->Speed . "Mbits/sec";
            if ($ligne->Speed == "10000") {
                $speed_text = "10 Gigabits/s";
            }
        }

        $gateway=$ligne->Gateway;
        $PingStatys="";
        $IPADDR="";
        if(!is_array($ligne->Addresses)){
            $ligne->Addresses=array();
        }
        $others="";
        if(count($ligne->Addresses)>0) {
            $IPADDR = $ligne->Addresses[0];
            unset($ligne->Addresses[0]);
        }
        if(count($ligne->Addresses)>0){
            $others=sprintf("(%s)",@implode(", ",$ligne->Addresses));
        }

        $stateKind="off";
        $stateLabel="{inactive}";
        if(strtoupper($ligne->Status)=="UP"){
            $stateKind="up";
            $stateLabel="{active2}";
            $PingStatys=status2_pingGateway($Interface,$gateway);
        }

        $JS=null;
        // eth0 (Debian 12) mais aussi ens192/enp0s3/eno1 (noms prévisibles,
        // Debian 13) : sans clic, une carte non enregistrée ne pouvait pas
        // être configurée (ticket RWE 2026-10-28, appliance migrée en ens*).
        if(preg_match("#^(eth[0-9]+|en[a-z][a-z0-9]+|em[0-9]+|p[0-9]+p[0-9]+)$#",$Interface)) {
            $md = md5($Interface);
            $JS = "Loadjs('fw.network.interfaces.php?nic-config-js=$Interface&md=$md');";
            if ($Interface == "tailscale0") {
                $JS = "Loadjs('fw.network.interfaces.php?nic-config-deny=$Interface');";
            }
        }
        if(preg_match("#^(.+?):([0-9]+)$#",$NICNAME,$m)){
            $JS="Loadjs('$page?nic-virtual-js={$m[2]}&eth={$m[1]}&function=InterfaceMainStatusLoad')";
        }

        $line=array();
        if(strlen(trim(strval($IPADDR)))>0){$line[]="<strong>".htmlspecialchars(strval($IPADDR))."</strong>";}
        else{$line[]="<span class='nif-muted-i'>{nif_no_address}</span>";}
        if(strlen(trim($others))>0){$line[]="<span class='ak-muted'>".htmlspecialchars($others)."</span>";}
        if(strlen(trim($speed_text))>0){$line[]="<span class='ak-muted'>$speed_text</span>";}

        $bars=array();
        $gw=trim(strval($gateway));
        if($gw<>"" && $gw<>"0.0.0.0"){
            $bars[]=array("label"=>"{gateway}","value"=>htmlspecialchars($gw));
        }

        // Interface non geree par Artica: aucune bascule ni action a proposer.
        $rows[]=netif_card(array(
            "state"=>$stateKind,
            "ico"=>"fa-solid fa-microchip",
            "name"=>htmlspecialchars($Interface),
            "js"=>$JS,
            "pills"=>array(netif_pill($stateKind=="up"?"up":"off",$stateLabel),$PingStatys,
                netif_pill("tag","{nif_not_registered}")),
            "line"=>$line,
            "mac"=>property_exists($ligne,"Mac")?strval($ligne->Mac):"",
            "bars"=>$bars,
            "note"=>"{nif_not_registered_explain}",
            "rx"=>$RX,"tx"=>$TX,"drop"=>$DROP,
            "search"=>"$Interface $IPADDR $others",
        ));
    }

    return $rows;
}

/**
 * Lignes en lecture seule pour les adresses portees par les instances du serveur
 * de fichiers (Files Sharing).
 *
 * Elles n'apparaissent dans aucune des sources habituelles: chaque instance vit
 * dans son propre namespace reseau, donc son lien ipvlan et son adresse sont
 * invisibles depuis l'hote (ni listnics, ni /system/network/interfaces). Sans ces
 * lignes, une adresse pourtant attribuee parait libre, et rien n'empeche de la
 * reaffecter a une autre interface — le conflit ne se voit qu'apres coup, cote
 * client SMB.
 *
 * Volontairement sans bascule ni action: ces adresses se gerent depuis la section
 * Files Sharing, pas ici. Ce tableau ne fait que les rendre visibles.
 *
 * La condition d'activation est la PRESENCE DU SOCKET du daemon, pas un reglage:
 * GET_INFO("SAMBA_APPLIANCE") passe par articarest et ne suit pas le commutateur
 * (mesure sur appliance: cle valkey a 0, GET_INFO renvoie toujours 1), et
 * $users->SAMBA_APPLIANCE teste un fichier /etc/artica-postfix/SAMBA_APPLIANCE
 * qui n'existe pas sur une appliance ou la fonction tourne pourtant. Le socket,
 * lui, ne ment pas: pas de daemon, pas d'instances, donc pas de lignes.
 */
function status2_samba_instances():array{
    $rows=array();
    $socket="/run/samba/artica-samba.sock";
    if(!file_exists($socket)){return $rows;}
    $inc=dirname(__FILE__)."/ressources/class.artica-samba.inc";
    if(!is_file($inc)){return $rows;}
    include_once($inc);
    if(!class_exists("ArticaSamba")){return $rows;}

    try{
        $samba=new ArticaSamba($socket);
        $instances=$samba->listInstances();
    }catch(\Throwable $e){
        // Socket present mais daemon injoignable (arret en cours, socket
        // orphelin): le tableau des interfaces doit rester affiche, on se
        // contente de ne pas ajouter ces lignes.
        VERBOSE("samba instances: ".$e->getMessage(),__LINE__);
        return $rows;
    }
    if(!is_array($instances)){return $rows;}

    foreach($instances as $inst){
        if(!is_array($inst)){continue;}
        // Une instance peut exister sans adresse (creee sans interface): rien a
        // signaler dans un tableau d'adresses.
        $ip=trim(strval(isset($inst["ip_address"])?$inst["ip_address"]:""));
        if(strlen($ip)==0){continue;}
        $name=trim(strval(isset($inst["name"])?$inst["name"]:""));
        if(strlen($name)==0){$name=trim(strval(isset($inst["id"])?$inst["id"]:""));}
        $parent=trim(strval(isset($inst["interface"])?$inst["interface"]:""));

        $line=array();
        $line[]="<strong>".htmlspecialchars($ip)."</strong>";
        if(strlen($parent)>0){$line[]="<span class='ak-muted'>{nif_bound_to} <strong>".htmlspecialchars($parent)."</strong></span>";}

        switch(strval(isset($inst["state"])?$inst["state"]:"")){
            case "running":  $kind="up";   $label="{running}";  break;
            case "starting": $kind="warn"; $label="{starting}"; break;
            case "failed":   $kind="down"; $label="{failed}";   break;
            default:         $kind="off";  $label="{stopped}";  break;
        }

        // Compteurs de l'interface de l'instance. Ils vivent dans le namespace
        // reseau de l'instance, donc seul le daemon peut les lire (via
        // /proc/<pid>/net/dev) — mais ils arrivent deja dans "runtime" de la
        // liste, pas besoin d'un appel /status par ligne.
        $RX=0;$TX=0;$DROP=0;
        $rt=(isset($inst["runtime"]) && is_array($inst["runtime"]))?$inst["runtime"]:array();
        if(count($rt)>0){
            $RX=intval(isset($rt["net_rx_bytes"])?$rt["net_rx_bytes"]:0);
            $TX=intval(isset($rt["net_tx_bytes"])?$rt["net_tx_bytes"]:0);
            $DROP=intval(isset($rt["net_dropped"])?$rt["net_dropped"]:0);
        }

        // Lecture seule: ces adresses se gerent depuis Files Sharing.
        $rows[]=netif_card(array(
            "state"=>$kind,
            "ico"=>"fa-solid fa-folder-open",
            "name"=>htmlspecialchars($name),
            "pills"=>array(netif_pill($kind=="up"?"up":($kind=="down"?"down":($kind=="warn"?"warn":"off")),$label),
                netif_pill("tag","{nif_samba_instance}")),
            "line"=>$line,
            "note"=>"{nif_samba_instance_explain}",
            "rx"=>$RX,"tx"=>$TX,"drop"=>$DROP,
            "search"=>"$name $ip $parent samba",
        ));
    }

    return $rows;
}

function status2_vlan_build_js(){
    $page               = CurrentPageName();
    $tpl=new template_admin();

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/vlan/build");

    sleep(2);
    echo $tpl->js_display_results("{nic_settings} {done}");
    echo "LoadAjax('netz-interfaces-status','$page?status2=yes');\n";
}

/**
 * VLAN rattaches a une interface, sous le meme gabarit que status2().
 * Retourne un tableau de lignes (auparavant un fragment de <table> qui n'emettait
 * que 12 <td> sur les 16 colonnes de l'en-tete).
 */
function status2_vlan($Interface):array{
    $rows=array();
    $tpl=new template_admin();
    $page               = CurrentPageName();
    $UDHCPD_INSTALLED   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UDHCPD_INSTALLED"));

    $q                  = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ifq                = netif_sql_safe($tpl,$Interface);
    $sql                = "SELECT * FROM nics_vlan WHERE nic='$ifq' ORDER BY ID"; //
    $results            = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);return $rows;}
    if(count($results)==0){return $rows;}

    $jsrestart="document.getElementById('netz-interfaces-status').innerHTML='<div class=center><img src=img/Eclipse-0.9s-400px.gif alt=a></div>';Loadjs('$page?status2-vlan-build-js=yes')";

    $udhcpd_status=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/udhcpd/status"));
    if(is_object($json)){
        if(property_exists($json,"Info")){
            foreach ($json->Info as $key=>$pid){$udhcpd_status[$key]=$pid;}
        }
    }

    $users=new usersMenus();
    $apply="";
    if($users->AsSystemAdministrator){$apply=netif_card_button("{apply}",$jsrestart);}

    if(!$q->FIELD_EXISTS("nics_vlan","udhcpd")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `udhcpd` INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("nics_vlan","udhcpd_conf")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `udhcpd_conf` TEXT NOT NULL default 'YTowOnt9'");
    }

    foreach ($results as $index=>$ligne){
        $udhcpd     = intval($ligne["udhcpd"]);
        $masquerade = intval($ligne["masquerade"]);
        $nic        = "vlan{$ligne["ID"]}";
        $IPADDR     = "";

        // Counters come straight from the daemon. framework/network.php wrote
        // them into ONE shared file while being called once per interface in
        // this very loop, so two concurrent page loads could swap answers.
        $MAIN=array("TX"=>0,"RX"=>0,"DROP"=>0);
        $countersJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/counters/".urlencode($nic)));
        if(is_object($countersJson) && !empty($countersJson->Status)){
            $MAIN=array("TX"=>$countersJson->TX,"RX"=>$countersJson->RX,"DROP"=>$countersJson->DROP);
        }
        $TX         = isset($MAIN["TX"])?$MAIN["TX"]:0;
        $RX         = isset($MAIN["RX"])?$MAIN["RX"]:0;
        $DROP       = isset($MAIN["DROP"])?$MAIN["DROP"]:0;

        $NetStatus=new NetStatus($nic);
        if ($NetStatus->InterfaceRunning){
            $IPADDR=$NetStatus->IPADDR;
            $stateKind="up";
            $text_icon="{active2}";
        }else{
            $stateKind="warn";
            $text_icon="{inactive}";
        }

        $dhcpState="off";
        $dhcpJs="";
        if($UDHCPD_INSTALLED==1){
            $dhcpJs="Loadjs('fw.udhcpd.php?interface=$nic');";
            if($udhcpd==1){
                $dhcpState="warn";
                if(isset($udhcpd_status[$nic])){
                    $dhcpState=($udhcpd_status[$nic]>0)?"on":"err";
                }
            }
        }

        $toggles=array();
        $toggles[]=array("label"=>"MASQ","on"=>$masquerade);
        $toggles[]=array("label"=>"DHCP","state"=>$dhcpState,"js"=>$dhcpJs);

        $JS="Loadjs('fw.network.interfaces.php?nic-vlan-js={$ligne["ID"]}&eth=$Interface');";
        if(strlen(trim(strval($IPADDR)))==0){$IPADDR=trim(strval($ligne["ipaddr"]));}
        $line=array();
        $line[]="<span class='ak-muted'>{parent}: <strong>".htmlspecialchars($Interface)."</strong></span>";
        if(strlen(trim(strval($IPADDR)))>0){$line[]="<strong>".htmlspecialchars(strval($IPADDR))."</strong>";}
        $vid=intval(isset($ligne["vlanid"])?$ligne["vlanid"]:0);
        if($vid>0){$line[]="<span class='ak-muted'>VID $vid</span>";}

        $bars=array();
        $gw=trim(strval(isset($ligne["gateway"])?$ligne["gateway"]:""));
        if($gw=="" || $gw=="0.0.0.0"){
            $bars[]=array("label"=>"{gateway}","value"=>"<span class='nif-muted-i'>{nif_no_gateway}</span>");
        }else{
            $bars[]=array("label"=>"{gateway}","value"=>htmlspecialchars($gw));
        }

        $acts=array($apply);
        if($users->AsSystemAdministrator){$acts[]=netif_card_edit($JS);}
        $rows[]=netif_card(array(
            "state"=>$stateKind,
            "kind"=>"vlan",
            "ico"=>"fa-solid fa-layer-group",
            "name"=>htmlspecialchars($nic),
            "js"=>$JS,
            "pills"=>array(netif_pill($stateKind=="up"?"up":"warn",$text_icon),
                netif_pill("tag","VLAN ".htmlspecialchars($Interface))),
            "line"=>$line,
            "maclabel"=>"MAC",
            "mac"=>strval($NetStatus->MacAddr),
            "bars"=>$bars,
            "toggles"=>netif_toggles($toggles),
            "rx"=>$RX,"tx"=>$TX,"drop"=>$DROP,
            "foot"=>"802.1Q",
            "search"=>"$nic $Interface $IPADDR vlan $vid",
        ));
    }

    return $rows;
}

function sysctl_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$NetCoreRmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreRmemMax"));
	$NetIpv4TcpMemLow=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMemLow"));
	$NetIpv4TcpMemPress=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMemPress"));
	$NetIpv4TcpMemPages=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMemPages"));
	$DisableTCPEn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableTCPEn"));
	$EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
	$DisableTCPWindowScaling=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableTCPWindowScaling"));
	if($NetCoreRmemMax==0){$NetCoreRmemMax=212992;}
	if($NetIpv4TcpMemLow==0){$NetIpv4TcpMemLow=187212;}
	if($NetIpv4TcpMemPress==0){$NetIpv4TcpMemPress=249617;}
	if($NetIpv4TcpMemPages==0){$NetIpv4TcpMemPages=374424;}
	
	$EnableKernelBBR=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKernelBBR"));
	$EnableSystemNetworkOptimize=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSystemNetworkOptimize")));
	$EnableArticaAsGateway=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway")));
	
	
	
	$rmem_max[0]="{reset}";
	$rmem_max["4096"]="4096 Bytes";
	$rmem_max["16384"]="16K";
	$rmem_max["87380"]="85K";
	$rmem_max["187212"]="180K";
	$rmem_max["212992"]="208K";
	$rmem_max["249617"]="240K";
	$rmem_max["262144"]="256K";
	$rmem_max["374424"]="365K";
	$rmem_max["524288"]="512K";
	$rmem_max["2097152"]="2M";
	$rmem_max["4194304"]="4M";
	$rmem_max["6291456"]="6M";
	$rmem_max["8388608"]="8M";
	$rmem_max["16777216"]="16M";
	$rmem_max["67108864"]="64M";
	$rmem_max["134217728"]="128M";
	
	$NetIpv4TcpRmemMin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpRmemMin"));
	$NetIpv4TcpRmemDef=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpRmemDef"));
	$NetIpv4TcpRmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpRmemMax"));
	if($NetIpv4TcpRmemMin==0){$NetIpv4TcpRmemMin=4096;}
	if($NetIpv4TcpRmemDef==0){$NetIpv4TcpRmemDef=87380;}
	if($NetIpv4TcpRmemMax==0){$NetIpv4TcpRmemMax=6291456;}
	
	
	$NetIpv4TcpWmemMin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpWmemMin"));
	$NetIpv4TcpWmemDef=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpWmemDef"));
	$NetIpv4TcpWmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpWmemMax"));
	if($NetIpv4TcpWmemMin==0){$NetIpv4TcpWmemMin=4096;}
	if($NetIpv4TcpWmemDef==0){$NetIpv4TcpWmemDef=16384;}
	if($NetIpv4TcpWmemMax==0){$NetIpv4TcpWmemMax=4194304;}
	

	//https://wwwx.cs.unc.edu/~sparkst/howto/network_tuning.php
	//http://vietlux.blogspot.fr/2012/07/squid-proxy-tuning-for-high-perfomance.html
	//https://fasterdata.es.net/host-tuning/linux/
	
	$form[]=$tpl->field_checkbox("EnableSystemNetworkOptimize","{EnableSystemNetworkOptimize}",$EnableSystemNetworkOptimize);
	
	$form[]=$tpl->field_checkbox("EnableipV6","{enable_ipv6}",$EnableipV6,false,"{enable_ipv6_text}");
	$form[]=$tpl->field_checkbox("EnableArticaAsGateway","{ARTICA_AS_GATEWAY}",$EnableArticaAsGateway,false,"{ip_forward_text}");

	if(!netif_bbr_cluster_managed()){
		$form[]=$tpl->field_checkbox("EnableKernelBBR","Bottleneck Bandwidth and RTT","$EnableKernelBBR",false,"{BBR_EXPLAIN}");
	}
	$form[]=$tpl->field_checkbox("DisableTCPWindowScaling","{DisableTCPWindowScaling}",$DisableTCPWindowScaling,false,"{DisableTCPWindowScaling_explain}");
	$form[]=$tpl->field_checkbox("DisableTCPEn","{DisableTCPEn}",$DisableTCPEn,false,"{DisableTCPEn_explain}");
	
	$form[]=$tpl->field_section("TCP TIME_WAIT");
	
	$NetIpv4TcpTwReuse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpTwReuse"));
	$NetIpv4TcpTwRecycle=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpTwRecycle"));
	
	
	
	$NetIpv4TcpFinTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpFinTimeOut"));
	if($NetIpv4TcpFinTimeOut==0){$NetIpv4TcpFinTimeOut=60;}
	$form[]=$tpl->field_checkbox("NetIpv4TcpTwReuse","{tcp_time_wait_reuse}",$NetIpv4TcpTwReuse,false,"{tcp_time_wait_reuse_explain}");
	$form[]=$tpl->field_checkbox("NetIpv4TcpTwRecycle","{tcp_time_wait_recycle}",$NetIpv4TcpTwRecycle,false,"{tcp_time_wait_recycle_explain}");
	
	for($i=4;$i<61;$i++){$tcp_fin_timeout[$i]="$i {seconds}";}
	$form[]=$tpl->field_array_hash($tcp_fin_timeout, "NetIpv4TcpFinTimeOut", "{tcp_fin_timeout}", $NetIpv4TcpFinTimeOut,false,"{tcp_fin_timeout_explain}");

	
	
	
	$form[]=$tpl->field_section("{TCP_Autotuning_settings}","{net_ipv4_tcp_mem_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetCoreRmemMax", "{NetCoreRmemMax}", $NetCoreRmemMax,false,"{NetCoreRmemMax_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpMemLow", "{low_threshold}", $NetIpv4TcpMemLow,false,"{NetIpv4TcpMemLow_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpMemPress", "{pressuring_memory}", $NetIpv4TcpMemPress,false,"{NetIpv4TcpMemPress}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpMemPages", "{max_memory_pages}", $NetIpv4TcpMemPages,false,"{NetIpv4TcpMemPress}");

	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpRmemMin", "{NetIpv4TcpRmemMin}", $NetIpv4TcpRmemMin,false,"{NetIpv4TcpRmemMin_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpRmemDef", "{NetIpv4TcpRmemDef}", $NetIpv4TcpRmemDef,false,"{NetIpv4TcpRmemDef_explain}");

	$form[]=$tpl->field_section(null,"{net_ipv4_tcp_wmem_explain}");
	
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpWmemMin", "{NetIpv4TcpWmemMin}", $NetIpv4TcpWmemMin,false,"{NetIpv4TcpRmemMin_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpWmemDef", "{NetIpv4TcpWmemDef}", $NetIpv4TcpWmemDef,false,"{NetIpv4TcpWmemDef_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpWmemMax", "{NetIpv4TcpWmemMax}", $NetIpv4TcpWmemMax,false,"{NetIpv4TcpWmemMax_explain}");



	$hml[]=$tpl->form_outside("{kernelnetworkoptimization}", @implode("\n", $form),null,"{apply}","","AsSystemAdministrator");
	echo @implode("\n", $hml);
	
}

function saveNicProfile(){
    $tpl=new template_admin();
    // Avant toute autre ecriture : retire EnableipV6 du POST (voir netif_ipv6_from_form).
    $ipv6err=netif_ipv6_from_form();
if (!isset($_POST["NetIpv4TcpWindowScaling"])){
    $_POST["NetIpv4TcpMem"]="1149471 1532631 52298942";
    $_POST["NetIpv4TcpWMem"]="4096 65536 16777216";
    $_POST["NetIpv4TcpRMem"]="4096 87380 16777216";
    $_POST["NetCoreWmemMax"]=16777216;
    $_POST["NetCoreRmemMax"]=16777216;
    if ($_POST["NicProfile"]==10) {
        $_POST["NetIpv4TcpWMem"]="4096 65536 33554432";
        $_POST["NetIpv4TcpRMem"]="4096 87380 33554432";
        $_POST["NetCoreWmemMax"]=67108864;
        $_POST["NetCoreRmemMax"]=67108864;
    }
    $_POST["NetIpv4TcpReordering"]=3;
    $_POST["NetIpv4TcpMaxSynBackLog"]=4096;
    $_POST["NetCoreNetdevMaxBacklog"]=65536;
    $_POST["NetCoreRmemDefault"]=12582912;
    $_POST["NetCoreWmemDefault"]=12582912;
    $_POST["NetIpv4TcpTimestamps"]=1;
    $_POST["NetCoreOptmemMax"]=20480;
    $_POST["NetIpv4TcpLowLatency"]=0;
    $_POST["NetIpv4TcpNoMetricsSave"]=0;
    $_POST["NetIpv4TcpMaxTWBuckets"]=1440000;
    $_POST["NetIpv4TcpKeepAliveTime"]=3600;
    $_POST["NetIpv4TcpRfc1337"]=1;
    $_POST["NetIpv4TcpSynackRetries"]=5;
    $_POST["NetIpv4TcpSynRetries"]=5;
    $_POST["NetIpv4TcpFinTimeOut"]=30;
    $_POST["NetIpv4TcpTwRecycle"]=0;
    $_POST["NetIpv4TcpTwReuse"]=1;
    $_POST["NetIpv4TcpEcn"]=2;
    $_POST["NetIpv4TcpWindowScaling"]=1;
    $_POST["NetIpv4UdpMem"]="262144 524288 1048576";
    $_POST["NetIpv4UdpRmemMin"]=16384;
    $_POST["NetIpv4UdpWmemMin"]=16384;
    if ($_POST["NicProfile"]==10) {
        $_POST["NetIpv4UdpMem"]="1048576 2097152 4194304";
        $_POST["NetIpv4UdpRmemMin"]=65536;
        $_POST["NetIpv4UdpWmemMin"]=65536;
    }
    $tpl->SAVE_POSTs();
}
if(isset($_POST["NetIpv4TcpWindowScaling"])) {
    $tpl->SAVE_POSTs();

}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    if($ipv6err<>""){echo $tpl->post_error($ipv6err);}
}
function sysctl2_proxy():bool{
    if(!netif_require_admin(true)){return false;}
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NicProfile",10);
    $Configs=array(
        "NetCoreSomaxConn"=>4096,
        "NetCoreNetdevMaxBacklog"=>5000,
        "NetCoreRmemMax"=>16777216,
        "NetCoreWmemMax"=>16777216,
        "NetIpv4TcpRMem"=>"4096 87380 16777216",
        "NetIpv4TcpWMem"=>"4096 65536 16777216",
        "NetIpv4TcpTwReuse"=>1,
        "NetIpv4TcpFinTimeOut"=>15,
        "NetIpv4TcpWindowScaling"=>1,
        "NetIpv4TcpMaxSynBackLog"=>4096,
        "NetIpv4TcpSynackRetries"=>2,
        "netipv4tcpfastopen"=>1,
        "ChangeKernelTcp"=>1,
        "EnableSystemOptimize"=>1,
        "EnableSystemNetworkOptimize"=>1,
    );

    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE `nics` SET checksum_offloading=1");
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        return false;
    }
    foreach ($Configs as $Key=>$Value) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($Key,$Value);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    echo "Loadjs('$page?sysctl2-js=yes');";

    return admin_tracks_post("Save Kernel optimization for Proxy service");

}

function sysctl2_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $NicProfile=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NicProfile"));

    $NicProfileArray[1]="1GE";
    $NicProfileArray[10]="10GE";

    //net.ipv4.tcp_mem
    $NetIpv4TcpMem = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMem");
    if ($NicProfile==0){
        $NetIpv4TcpMem="1149471 1532631 2298942";
    }
    //net.ipv4.tcp_wmem
    $NetIpv4TcpWMem = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpWMem");
    if ($NicProfile==0){
        $NetIpv4TcpWMem="4096 65536 16777216";
    }
    //net.ipv4.tcp_rmem
    $NetIpv4TcpRMem = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpRMem");
    if ($NicProfile==0){
        $NetIpv4TcpRMem="4096 87380 16777216";
    }
    //net.core.wmem_max
    $NetCoreWmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreWmemMax"));
    if($NetCoreWmemMax==0){
        $NetCoreWmemMax=16777216;
    }
    //net.core.rmem_max
    $NetCoreRmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreRmemMax"));
    if($NetCoreRmemMax==0){
        $NetCoreRmemMax=16777216;
    }
    //net.core.netdev_max_backlog
    $NetCoreNetdevMaxBacklog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreNetdevMaxBacklog"));
    if($NetCoreNetdevMaxBacklog==0){
        $NetCoreNetdevMaxBacklog=65536;
    }

    //net.core.wmem_default
    $NetCoreWmemDefault=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreWmemDefault"));
    if($NetCoreWmemDefault==0){
        $NetCoreWmemDefault=31457280;
    }

    //net.core.rmem_default
    $NetCoreRmemDefault=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreRmemDefault"));
    if($NetCoreRmemDefault==0){
        $NetCoreRmemDefault=31457280;
    }

    //net.ipv4.tcp_timestamps
    $NetIpv4TcpTimestamps=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpTimestamps"));
    if($NicProfile==0){
        $NetIpv4TcpTimestamps=1;
    }

    //net.core.optmem_max
    $NetCoreOptmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreOptmemMax"));
    if($NetCoreOptmemMax==0){
        $NetCoreOptmemMax=20480;
    }

    //net.ipv4.tcp_max_syn_backlog
    $NetIpv4TcpMaxSynBackLog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMaxSynBackLog"));
    if($NetIpv4TcpMaxSynBackLog==0){
        $NetIpv4TcpMaxSynBackLog=4096;
    }

    //net.ipv4.tcp_low_latency
    $NetIpv4TcpLowLatency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpLowLatency"));

    //net.ipv4.tcp_no_metrics_save
    $NetIpv4TcpNoMetricsSave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpNoMetricsSave"));

    //net.ipv4.tcp_max_tw_buckets
    $NetIpv4TcpMaxTWBuckets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMaxTWBuckets"));
    if($NetIpv4TcpMaxTWBuckets==0){
        $NetIpv4TcpMaxTWBuckets=1440000;
    }

    //net.ipv4.tcp_reordering
    $NetIpv4TcpReordering=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpReordering"));
    if($NicProfile==0){
        $NetIpv4TcpReordering=3;
    }

    //net.ipv4.tcp_keepalive_time
    $NetIpv4TcpKeepAliveTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpKeepAliveTime"));
    if($NetIpv4TcpKeepAliveTime==0){
        $NetIpv4TcpKeepAliveTime=3600;
    }

    //net.ipv4.tcp_rfc1337
    $NetIpv4TcpRfc1337=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpRfc1337"));
    if($NicProfile==0){
        $NetIpv4TcpRfc1337=1;
    }

    //net.ipv4.tcp_synack_retries
    $NetIpv4TcpSynackRetries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpSynackRetries"));
    if($NicProfile==0){
        $NetIpv4TcpSynackRetries=5;
    }

    //net.ipv4.tcp_syn_retries
    $NetIpv4TcpSynRetries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpSynRetries"));
    if($NicProfile==0){
        $NetIpv4TcpSynRetries=5;
    }

    //net.ipv4.tcp_ecn
    $NetIpv4TcpEcn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpEcn"));
    if($NicProfile==0){
        $NetIpv4TcpEcn=2;
    }
    //net.ipv4.tcp_tw_reuse
    $NetIpv4TcpTwReuse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpTwReuse"));
    if($NicProfile==0){
        $NetIpv4TcpTwReuse=1;
    }
    //net.ipv4.tcp_tw_recycle
    $NetIpv4TcpTwRecycle=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpTwRecycle"));
    //net.ipv4.tcp_fin_timeout
    $NetIpv4TcpFinTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpFinTimeOut"));
    if($NicProfile==0){$NetIpv4TcpFinTimeOut=30;}
    //net.ipv4.tcp_window_scaling
    $NetIpv4TcpWindowScaling=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpWindowScaling"));
    if($NicProfile==0){$NetIpv4TcpWindowScaling=1;}
    //net.ipv4.tcp_congestion_control
    // Pas de defaut de profil pour BBR : sysctl.Build() lit EnableKernelBBR tel
    // quel (aucune surcharge NicProfile==0 cote Go). Afficher 1 ici mentait sur
    // l'etat applique, et le premier "Appliquer" activait BBR sans decision.
    $EnableKernelBBR=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKernelBBR"));
    $EnableArticaAsGateway=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway")));
    $EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
    $ChangeKernelTcp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChangeKernelTcp"));
    $netipv4tcpfastopen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("netipv4tcpfastopen"));
    $netipv4tcp_sack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("netipv4tcp_sack"));
    $netipv4tcp_slow_start_after_idle=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("netipv4tcp_slow_start_after_idle"));
    if (!file_exists("/etc/artica-postfix/settings/Daemons/netipv4tcpfastopen")){
        $netipv4tcpfastopen=3;
    }
    if (!file_exists("/etc/artica-postfix/settings/Daemons/netipv4tcp_sack")){
        $netipv4tcp_sack=1;
    }
    $NetIpv4TcpMtuProbing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMtuProbing"));


    $NetIpv4UdpMem=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4UdpMem"));
    if($NetIpv4UdpMem==""){
        $NetIpv4UdpMem="262144 524288 1048576";
    }
    $NetIpv4UdpRmemMin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4UdpRmemMin"));
    if($NetIpv4UdpRmemMin==0){
        $NetIpv4UdpRmemMin=16384;
    }
    $NetIpv4UdpWmemMin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4UdpWmemMin"));
    if($NetIpv4UdpWmemMin==0){
        $NetIpv4UdpWmemMin=16384;
    }

    //https://wwwx.cs.unc.edu/~sparkst/howto/network_tuning.php
    //http://vietlux.blogspot.fr/2012/07/squid-proxy-tuning-for-high-perfomance.html
    //https://fasterdata.es.net/host-tuning/linux/
    $form[]=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/tune-sysctl','1024','800')","fa-solid fa-headset",null,null,"btn-blue");
    $form[]="&nbsp;";
    $form[]=$tpl->button_inline("{bti_optimize_proxy}","Loadjs('$page?sysctl-proxyjs=yes')",ico_speed,null,null);


    $form[]=$tpl->field_section("{kernelnetworkoptimization}",null);
    $form[]=$tpl->field_array_select($NicProfileArray, "NicProfile", "{nic_profile}", $NicProfile,"{nic_profile_explain}","dialogInstance2.close();");
    $form[]=$tpl->field_checkbox("EnableipV6","{enable_ipv6}",$EnableipV6,false,"{enable_ipv6_text}");
    $form[]=$tpl->field_checkbox("EnableArticaAsGateway","{ARTICA_AS_GATEWAY}",$EnableArticaAsGateway,false,"{ip_forward_text}");
    if(!netif_bbr_cluster_managed()){
        $form[]=$tpl->field_checkbox("EnableKernelBBR","Bottleneck Bandwidth and RTT",$EnableKernelBBR,false,"{BBR_EXPLAIN}");
    }
    $form[]=$tpl->field_checkbox("NetIpv4TcpMtuProbing","{NetIpv4TcpMtuProbing}",$NetIpv4TcpMtuProbing,false,"{NetIpv4TcpMtuProbing_explain}");

    $form[]=$tpl->field_section("{ChangeKernelTcpTitle}","{ChangeKernelTcpExplain}",true);

    $form[]=$tpl->field_checkbox("ChangeKernelTcp","{ChangeKernelTcp}",$ChangeKernelTcp,"NetIpv4TcpReordering,NetIpv4TcpWindowScaling,NetIpv4TcpEcn,NetIpv4TcpTwReuse,NetIpv4TcpTwRecycle,NetIpv4TcpFinTimeOut,NetIpv4TcpSynRetries,NetIpv4TcpSynackRetries,NetIpv4TcpRfc1337,NetIpv4TcpKeepAliveTime,NetIpv4TcpMaxTWBuckets,NetIpv4TcpNoMetricsSave,NetIpv4TcpLowLatency,NetCoreOptmemMax,NetIpv4TcpTimestamps,NetCoreRmemDefault,NetCoreWmemDefault,NetCoreNetdevMaxBacklog,NetIpv4TcpMaxSynBackLog,NetCoreRmemMax,NetCoreWmemMax,NetIpv4TcpRMem,NetIpv4TcpWMem,NetIpv4TcpMem,netipv4tcpfastopen,netipv4tcp_slow_start_after_idle,netipv4tcp_sack");

    $form[]=$tpl->field_checkbox("NetIpv4TcpWindowScaling","{NetIpv4TcpWindowScaling}",$NetIpv4TcpWindowScaling,false,"{NetIpv4TcpWindowScalingExplain}");
    for($i=0;$i<3;$i++){$tcp_ecn[$i]="$i";}
    $form[]=$tpl->field_array_hash($tcp_ecn,"NetIpv4TcpEcn","{NetIpv4TcpEcn}",$NetIpv4TcpEcn,false,"{NetIpv4TcpEcnExplain}");
    for($i=0;$i<3;$i++){$tcp_twreuse[$i]="$i";}
    $form[]=$tpl->field_array_hash($tcp_twreuse,"NetIpv4TcpTwReuse","{tcp_time_wait_reuse}",$NetIpv4TcpTwReuse,false,"{tcp_time_wait_reuse_explain}");
    for($i=0;$i<4;$i++){$tcp_fastopen[$i]="$i";}
    $form[]=$tpl->field_array_hash($tcp_fastopen,"netipv4tcpfastopen","{netipv4tcpfastopen}",$netipv4tcpfastopen,false,"{netipv4tcpfastopen_explain}");
    $form[]=$tpl->field_checkbox("netipv4tcp_slow_start_after_idle","{tcp_slow_start_after_idle}",$netipv4tcp_slow_start_after_idle,false,"{tcp_slow_start_after_idle_explain}");
    $form[]=$tpl->field_checkbox("netipv4tcp_sack","{netipv4tcp_sack}",$netipv4tcp_sack,false,"{netipv4tcp_sack_explain}");
    $form[]=$tpl->field_checkbox("NetIpv4TcpTwRecycle","{tcp_time_wait_recycle}",$NetIpv4TcpTwRecycle,false,"{tcp_time_wait_recycle_explain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpFinTimeOut", "{tcp_fin_timeout}", $NetIpv4TcpFinTimeOut,"{tcp_fin_timeout_explain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpSynRetries", "{NetIpv4TcpSynRetries}", $NetIpv4TcpSynRetries,"{NetIpv4TcpSynRetriesExplain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpSynackRetries", "{NetIpv4TcpSynackRetries}", $NetIpv4TcpSynackRetries,"{NetIpv4TcpSynackRetriesExplain}");
    $form[]=$tpl->field_checkbox("NetIpv4TcpRfc1337","{NetIpv4TcpRfc1337}",$NetIpv4TcpRfc1337,false,"{NetIpv4TcpRfc1337Explain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpKeepAliveTime", "{NetIpv4TcpKeepAliveTime}", $NetIpv4TcpKeepAliveTime,"{NetIpv4TcpKeepAliveTimeExplain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpMaxTWBuckets", "{NetIpv4TcpMaxTWBuckets}", $NetIpv4TcpMaxTWBuckets,"{NetIpv4TcpMaxTWBucketsExplain}");
    $form[]=$tpl->field_checkbox("NetIpv4TcpNoMetricsSave","{NetIpv4TcpNoMetricsSave}",$NetIpv4TcpNoMetricsSave,false,"{NetIpv4TcpNoMetricsSaveExplain}");
    $form[]=$tpl->field_checkbox("NetIpv4TcpLowLatency","{NetIpv4TcpLowLatency}",$NetIpv4TcpLowLatency,false,"{NetIpv4TcpLowLatencyExplain}");

    $form[]=$tpl->field_numeric("NetIpv4TcpReordering","{NetIpv4TcpReordering}",$NetIpv4TcpReordering,"{NetIpv4TcpReorderingExplain}");

    $form[]=$tpl->field_numeric("NetCoreOptmemMax", "{NetCoreOptmemMax}", $NetCoreOptmemMax,"{NetCoreOptmemMaxExplain}");
    for($i=0;$i<3;$i++){$tcp_timestamps[$i]="$i";}
    $form[]=$tpl->field_array_hash($tcp_timestamps, "NetIpv4TcpTimestamps", "{NetIpv4TcpTimestamps}", $NetIpv4TcpTimestamps,false,"{NetIpv4TcpTimestampsExplain}");
    $form[]=$tpl->field_numeric("NetCoreRmemDefault", "{NetCoreRmemDefault}", $NetCoreRmemDefault,"{NetCoreRmemDefaultExplain}");
    $form[]=$tpl->field_numeric("NetCoreWmemDefault", "{NetCoreWmemDefault}", $NetCoreWmemDefault,"{NetCoreWmemDefaultExplain}");
    $form[]=$tpl->field_numeric("NetCoreNetdevMaxBacklog", "{NetCoreNetdevMaxBacklog}", $NetCoreNetdevMaxBacklog,"{NetCoreNetdevMaxBacklogExplain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpMaxSynBackLog", "{NetIpv4TcpMaxSynBackLog}", $NetIpv4TcpMaxSynBackLog,"{NetIpv4TcpMaxSynBackLogExplain}");
    $form[]=$tpl->field_numeric("NetCoreRmemMax", "{NetCoreRmemMax1}", $NetCoreRmemMax,"{NetCoreRmemMaxExplain1}");
    $form[]=$tpl->field_numeric("NetCoreWmemMax", "{NetCoreWmemMax}", $NetCoreWmemMax,"{NetCoreWmemMaxExplain}");
    $form[]=$tpl->field_text("NetIpv4TcpRMem", "{NetIpv4TcpRMem}", $NetIpv4TcpRMem,false,"{NetIpv4TcpRMemExplain}");
    $form[]=$tpl->field_text("NetIpv4TcpWMem", "{NetIpv4TcpWMem}", $NetIpv4TcpWMem,false,"{NetIpv4TcpWMemExplain}");
    $form[]=$tpl->field_text("NetIpv4TcpMem", "{NetIpv4TcpMem}", $NetIpv4TcpMem,false,"{NetIpv4TcpMemExplain}");
    $form[]=$tpl->field_text("NetIpv4UdpMem", "{NetIpv4UdpMem}", $NetIpv4UdpMem,false,"{NetIpv4UdpMemExplain}");
    $form[]=$tpl->field_numeric("NetIpv4UdpRmemMin", "{NetIpv4UdpRmemMin}", $NetIpv4UdpRmemMin,"{NetIpv4UdpRmemMinExplain}");
    $form[]=$tpl->field_numeric("NetIpv4UdpWmemMin", "{NetIpv4UdpWmemMin}", $NetIpv4UdpWmemMin,"{NetIpv4UdpWmemMinExplain}");




    $jsrestart="dialogInstance2.close();";

    $hml[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",$jsrestart,"AsSystemAdministrator");
    echo @implode("\n", $hml);
    return true;
}

function nic_virtuals_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["eth"];
    $nicid_text="";
	$nicid=intval($_GET["nic-virtual-js"]);
	$nicz=new system_nic($eth);
	if($nicid==0){$nicid_text="{new_interface}";}
    $function=$_GET["function"];
	$title="$nicz->netzone: $nicz->NICNAME ($eth) $nicid_text";
	return $tpl->js_dialog2($title, "$page?nic-virtual=$nicid&eth=$eth&function=$function");
}
function nic_vlan_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["eth"];
    $nicid=intval($_GET["nic-vlan-js"]);
    $nicz=new system_nic($eth);
    if($nicid==0){$nicid_text="{new_vlan}";}
    $title="VLAN: $nicz->netzone: $nicz->NICNAME ($eth) $nicid_text";
    return $tpl->js_dialog2($title, "$page?nic-vlan=$nicid&eth=$eth");
}
function nic_virtuals_delete_js(){
	$nicid=intval($_GET["nic-virtual-delete-js"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["eth"];
    $rf=refreshjs();
	$tpl->js_confirm_delete("{interface} {$_GET["eth"]}:$nicid ", "delete_nic_virtual", $nicid,
	"$rf;LoadAjaxSilent('nics-virtuals','$page?nic-virtuals-list=".urlencode($eth)."');");
}
function nic_vlan_delete_js():bool{
    $nicid=intval($_GET["nic-vlan-delete-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=$_GET["md"];
    $rf=refreshjs();
   return $tpl->js_confirm_delete("{interface} vlan$nicid ", "delete_nic_vlan", $nicid,
        "$('#$md').remove();$rf");
}

function nic_vlan_delete(){
    // js_confirm_* affiche la reponse BRUTE: mode "bare", pas post_error().
    if(!netif_require_admin("bare")){return false;}

    $ID=intval($_POST["delete_nic_vlan"]);
    $qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $qlite->QUERY_SQL("DELETE FROM nics_vlan WHERE ID=$ID");
    if(!$qlite->ok){echo $qlite->mysql_error;exit;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/vlan/build");

}

function nic_virtuals_delete(){
    // js_confirm_* affiche la reponse BRUTE: mode "bare", pas post_error().
    if(!netif_require_admin("bare")){return false;}
	$ID=intval($_POST["delete_nic_virtual"]);
	$qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$sql="SELECT nic,ipaddr FROM nics_virtuals WHERE ID='$ID'";
	$results=$qlite->QUERY_SQL($sql);
	
	$ligne=$results[0];
	$ipaddr=$ligne["ipaddr"];
	$eth="{$ligne["nic"]}:{$ID}";
	
	
	
	
	$sql="DELETE FROM nics_virtuals WHERE ID=$ID";
	$qlite->QUERY_SQL($sql);
	if(!$qlite->ok){echo $qlite->mysql_error;return;}
	



    // The label (eno1:3) is not a link: interface/down refused it and the
    // address stayed in the kernel. Rebuilding the aliases of the interface
    // removes every labelled address and adds back those still in the table.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/virtual/interfaces/build/".urlencode(strval($ligne["nic"])));
		
		
}

function nic_vlan_masquerade(){
    if(!netif_require_admin(true,"AsFirewallManager")){return false;}
    $ID=intval($_GET["nic-vlan-masquerade"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL default 0");
    }
    $ligne=$q->mysqli_fetch_array("SELECT masquerade FROM nics_vlan WHERE ID='$ID'");
    $enabled=intval($ligne["masquerade"]);

    if($enabled==1){
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $q->QUERY_SQL("UPDATE nics_vlan SET `masquerade`=0 WHERE ID=$ID");
        if(!$q->ok){$tpl->js_error($q->mysql_error);exit;}
        admin_tracks("Disable masquerade for vlan interface $ID");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/vlan/build");
        return;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics_vlan SET masquerade=1 WHERE ID='$ID'");
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        exit;
    }

    admin_tracks("Enable masquerade for vlan interface $ID");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/vlan/build");

}

function nic_vlan_enable(){
    if(!netif_require_admin(true)){return false;}
    $ID=intval($_GET["nic-vlan-enable"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL default 0");
    }

    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM nics_vlan WHERE ID='$ID'");
    $enabled=intval($ligne["enabled"]);
    $sock=new sockets();
    if($enabled==1){

        $data=$sock->REST_API("/system/network/vlan/unlink/$ID");
        $json=json_decode($data);
        if(!$json->Status){
            $tpl->js_error("unlink/$ID API error $json->Error");
            return false;
        }

        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $q->QUERY_SQL("UPDATE nics_vlan SET `enabled`=0 WHERE ID=$ID");
        if(!$q->ok){$tpl->js_error($q->mysql_error);exit;}
        return admin_tracks("Disable vlan interface $ID");
    }

    $data=$sock->REST_API("/system/network/vlan/start/$ID");
    $json=json_decode($data);
    if(!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }


    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics_vlan SET enabled=1 WHERE ID='$ID'");
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        exit;
    }
    return admin_tracks("Enable vlan interface $ID");
}

function nic_vlan_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["eth"];
    $nicid=intval($_GET["nic-vlan"]);
    if($nicid==0){$nicid_text="{new_vlan}";}
    $nicz=new system_nic($eth);
    $ligne=array();
    $title="VLAN: $nicz->netzone: $nicz->NICNAME ($eth) $nicid_text";
    $rf=refreshjs();
    $jsafter="$rf;LoadAjax('nics-vlans','$page?nic-vlans-list=$eth');LoadAjax('netz-interfaces-status','$page?status2=yes');dialogInstance2.close();";
    $title_button="{add}";

    if($nicid>0){
        $rf=refreshjs();
        $jsafter="$rf;LoadAjax('nics-vlans','$page?nic-vlans-list=$eth');";
        $sql="SELECT * FROM nics_vlan WHERE ID='$nicid'";
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){
            $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL default 0");
        }

        $results=$q->QUERY_SQL($sql);
        $ligne=$results[0];
        $title_button="{apply}";
    }

    if(isset($_GET["default-datas"])){
        $default_array=unserialize(base64_decode($_GET["default-datas"]),array("allowed_classes"=>false));
        if(is_array($default_array)){
            // Pentest 2026-11-04 : default-datas etait recopie sans encodage dans la page (XSS) : noms d'interface et adresses seulement.
            foreach(array("NIC","IP","GW","NETMASK") as $dk){$default_array[$dk]=preg_replace('#[^A-Za-z0-9_.:@/-]#','',(string)($default_array[$dk] ?? ""));}
            $ligne["nic"]=$default_array["NIC"];
            if(preg_match("#(.+?)\.([0-9]+)$#",$default_array["IP"],$re)){
                if($re[2]>254){$re[2]=1;}
                $re[2]=$re[2]+1;
                $ligne["ipaddr"]="{$re[1]}.{$re[2]}";
                $ligne["gateway"]=$default_array["GW"];
                $ligne["netmask"]=$default_array["NETMASK"];
            }
        }
    }

    for($i=1;$i<4095;$i++){
        $VLANS[$i]=$i;
    }


    if($ligne["netmask"]==null){$ligne["netmask"]=$nicz->NETMASK;}
    $form[]=$tpl->field_hidden("save_nic_vlan", $eth);
    $form[]=$tpl->field_hidden("ID", $nicid);
    $form[]=$tpl->field_ipaddr("ipaddr", "{tcp_address}", $ligne["ipaddr"],true);
    $form[]=$tpl->field_array_hash($VLANS,"vlanid", "nonull:VLAN ID", $ligne["vlanid"],true);
    $form[]=$tpl->field_ipaddr("netmask", "{netmask}", $ligne["netmask"],true);
    $form[]=$tpl->field_cdir("cdir", "CDIR", $ligne["cdir"],true);
    $form[]=$tpl->field_checkbox("ForceGateway","{use_a_gateway}",$ligne["ForceGateway"]);
    $form[]=$tpl->field_checkbox("masquerade","MASQUERADE",$ligne["masquerade"]);
    $form[]=$tpl->field_ipaddr("gateway", "{gateway}", $ligne["gateway"]);
    $form[]=$tpl->field_numeric("metric","{metric}",$ligne["metric"]);
    $security="AsSystemAdministrator";
    $html=$tpl->form_outside($title, @implode("\n", $form),null,$title_button,$jsafter,$security);
    echo $tpl->_ENGINE_parse_body($html);

}
function nic_vlan_save():bool{
    if(!netif_require_admin()){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="CREATE TABLE IF NOT EXISTS `nics_vlan` (
			  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `nic` TEXT NOT NULL,
			  `org` TEXT NOT NULL,
			  `ipaddr` TEXT NOT NULL,
			  `netmask` TEXT NOT NULL,
			  `cdir` TEXT NOT NULL,
			  `gateway` TEXT NOT NULL,
			  `broadcast` TEXT NOT NULL,
			  `network` TEXT NOT NULL,
			  `vlanid` INTEGER NOT NULL,
			  `metric` INTEGER NOT NULL,
			  `udhcpd` INTEGER NOT NULL DEFAULT 0,
			  `ForceGateway` INTEGER NOT NULL DEFAULT 0,            
			  `masquerade` INTEGER NOT NULL DEFAULT 0,
			  `enabled` INTEGER NOT NULL DEFAULT 1)";
    $q->QUERY_SQL($sql);
    if(!$q->FIELD_EXISTS("nics_vlan","ForceGateway")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `ForceGateway` INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("nics_vlan","udhcpd")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `udhcpd` INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL DEFAULT 0");
    }

    $nic=$_POST["save_nic_vlan"];
    $ID=intval($_POST["ID"]);
    $ipaddr=$_POST["ipaddr"];
    $netmask=$_POST["netmask"];
    $ForceGateway=$_POST["ForceGateway"];
    $gateway=$_POST["gateway"];
    $network=$_POST["cdir"];
    $broadcast=$_POST["broadcast"];
    $enabled=1;
    $org="local";
    $metric=$_POST["metric"];
    $vlanid=$_POST["vlanid"];
    $masquerade=intval($_POST["masquerade"]);


    // Pentest 2026-11-04 : les champs du formulaire VLAN etaient concatenes dans la requete
    // (vlanid/metric/ForceGateway sans apostrophes dans l'UPDATE).
    if($ID==0){
        $sql="INSERT INTO nics_vlan(  `nic`,`org`,`ipaddr`,`netmask`,`cdir`,`gateway`,`broadcast`,`network` ,  `vlanid` ,  `metric` ,  `ForceGateway`, `enabled`,`masquerade`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?);";
        $q->QUERY_SQL_PARAMS($sql,array($nic,$org,$ipaddr,$netmask,$network,$gateway,$broadcast,$network,$vlanid,$metric,$ForceGateway,$enabled,$masquerade));
        if(!$q->ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($q->mysql_error));return false;}

        $sock=new sockets();
        $sock->REST_API("/system/network/vlan/build");
        return admin_tracks("Create new vlan interface $ID $ipaddr/$netmask masquerade=$masquerade");


    }

    $sql="UPDATE nics_vlan SET `nic` =?,  `org` =?,  `ipaddr` =?,
    `netmask` =?,  `cdir` =?,  
    `gateway` =?,  `broadcast` =?,
    `network` =?,  `vlanid` =?,`masquerade`=?,
    `metric` =?, `ForceGateway` =? WHERE ID=$ID";
    $q->QUERY_SQL_PARAMS($sql,array($nic,$org,$ipaddr,$netmask,$network,$gateway,$broadcast,$network,intval($vlanid),$masquerade,intval($metric),intval($ForceGateway)));
    if(!$q->ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($q->mysql_error));return false;}

    $sock=new sockets();
    $sock->REST_API("/system/network/vlan/reconfigure/$ID");
    return admin_tracks("Update vlan interface $ID $ipaddr/$netmask masquerade=$masquerade");
}

function nic_virtuals_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["eth"];
    $nicid_text="";
    $function=preg_replace('/[^A-Za-z0-9_]/','',strval($_GET["function"] ?? ""));
	$nicid=intval($_GET["nic-virtual"]);	
	if($nicid==0){$nicid_text="{new_interface}";}
	$nicz=new system_nic($eth);
	$title="$nicz->netzone: $nicz->NICNAME ($eth) $nicid_text";
    $ligne=array();
	if(strlen($function)<3){
        $function="blur";
    }
	$users=new usersMenus();

	
	if($users->LinuxDistriCode=="DEBIAN"){
		if(preg_match("#Debian\s+([0-9]+)\.#",$users->LinuxDistriFullName,$re)){
			$DEBIAN_MAJOR=$re[1];
			if($DEBIAN_MAJOR==6){$FailOver=1;}
		}
	
	}
    $rf=refreshjs();
	$jsafter="$rf;$function();dialogInstance2.close();";
	$title_button="{add}";
	
	if($nicid>0){
		$jsafter="$rf;$function();dialogInstance2.close();";
		$sql="SELECT * FROM nics_virtuals WHERE ID='$nicid'";
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$results=$q->QUERY_SQL($sql);
		$ligne=$results[0];
		$title_button="{apply}";
	}
	
	if(isset($_GET["default-datas"])){
		$default_array=unserialize(base64_decode($_GET["default-datas"]),array("allowed_classes"=>false));
		if(is_array($default_array)){
            // Pentest 2026-11-04 : default-datas etait recopie sans encodage dans la page (XSS) : noms d'interface et adresses seulement.
            foreach(array("NIC","IP","GW","NETMASK") as $dk){$default_array[$dk]=preg_replace('#[^A-Za-z0-9_.:@/-]#','',(string)($default_array[$dk] ?? ""));}
			$ligne["nic"]=$default_array["NIC"];
			if(preg_match("#(.+?)\.([0-9]+)$#",$default_array["IP"],$re)){
				if($re[2]>254){$re[2]=1;}
				$re[2]=$re[2]+1;
				$ligne["ipaddr"]="$re[1].$re[2]";
				$ligne["gateway"]=$default_array["GW"];
				$ligne["netmask"]=$default_array["NETMASK"];
			}
		}
	}
	
	if($ligne["metric"]==0){$ligne["metric"]=100+$_GET["ID"];}

	if($ligne["netmask"]==null){$ligne["netmask"]=$nicz->NETMASK;}
	$form[]=$tpl->field_hidden("save_nic_virtual", $eth);
	$form[]=$tpl->field_hidden("ID", $nicid);
	$form[]=$tpl->field_ipaddr("ipaddr", "{tcp_address}", $ligne["ipaddr"],true);
	$form[]=$tpl->field_ipaddr("netmask", "{netmask}", $ligne["netmask"],true);
	$form[]=$tpl->field_cdir("cdir", "CDIR", $ligne["cdir"],true);
	$form[]=$tpl->field_checkbox("ForceGateway","{use_a_gateway}",$ligne["ForceGateway"]);
	$form[]=$tpl->field_ipaddr("gateway", "{gateway}", $ligne["gateway"]);
	$form[]=$tpl->field_numeric("metric","{metric}",$ligne["metric"]);
	$form[]=$tpl->field_checkbox("failover","failover",$ligne["failover"]);
	
	
	
	$security="AsSystemAdministrator";
	$html=$tpl->form_outside($title, @implode("\n", $form),null,$title_button,$jsafter,$security);
	echo $tpl->_ENGINE_parse_body($html);

	
}
function nic_virtuals_save():bool{
	if(!netif_require_admin()){return false;}
	$tpl=new template_admin();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return false;}
	foreach ($_POST as $key=>$val){$_POST[$key]=url_decode_special_tool($val);}reset($_POST);
	$_POST["nic"]=$_POST["save_nic_virtual"];
	
	if($_POST["nic"]==null){echo $tpl->_ENGINE_parse_body("{nic}=null");exit;}
	if(!mpx_valid_eth($_POST["nic"])){echo $tpl->post_error("{invalid_interface}");return false;}
	$ipaddr=trim(strval($_POST["ipaddr"]));
	if(!filter_var($ipaddr,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) || $ipaddr=="0.0.0.0"){
		echo $tpl->post_error("{tcp_address}: ".$ipaddr);return false;
	}
	if(!filter_var(trim(strval($_POST["netmask"])),FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
		echo $tpl->post_error("{netmask}: ".strval($_POST["netmask"]));return false;
	}
	$gwPosted=trim(strval($_POST["gateway"]));
	if($gwPosted<>"" && $gwPosted<>"0.0.0.0" && !filter_var($gwPosted,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
		echo $tpl->post_error("{gateway}: ".$gwPosted);return false;
	}
	// Address already used by another host? framework/cmd.php?ping= never
	// worked (it reads ?ip=, so it always answered FALSE). The daemon asks
	// ARP instead; it refuses the appliance's own addresses, so editing a
	// live alias without changing its address is not a conflict.
	$neigh=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/neighbor/".urlencode($ipaddr))),true);
	if(is_array($neigh) && !empty($neigh["Status"]) && ($neigh["state"] ?? "")=="complete"){
		echo $tpl->post_error("$ipaddr ({$neigh["mac"]}): {ip_already_exists_in_the_network}");
		return false;
	}
	
	if($_POST["failover"]==1){
		$_POST["gateway"]=$_POST["ipaddr"];
		$_POST["netmask"]="255.255.255.255";
		$_POST["ForceGateway"]=0;
		
	}
	if($_POST["metric"]==0){$_POST["metric"]=lastmetric();}
	
	$NoGatewayForVirtualNetWork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoGatewayForVirtualNetWork");
	if(!is_numeric($NoGatewayForVirtualNetWork)){$NoGatewayForVirtualNetWork=0;}	
	
	if($NoGatewayForVirtualNetWork==1){$_POST["gateway"]=null;}
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

	
	// Requetes PREPAREES: ces neuf valeurs viennent de $_POST et etaient
	// interpolees telles quelles, WHERE ID compris et meme pas entre quotes.
	// Une charge du type ID="1 OR 1=1" reecrivait TOUTES les adresses
	// virtuelles de la machine, et une valeur contenant une quote pouvait
	// ajouter ses propres affectations de colonnes. url_decode_special_tool()
	// applique plus haut DECODE, il n'echappe rien.
	$nic=strval($_POST["nic"]);
	$ID=intval($_POST["ID"]);

	$sql="INSERT INTO nics_virtuals (nic,org,ipaddr,netmask,cdir,gateway,ForceGateway,failover,metric,openvpn_nic,ipv6)
	VALUES(?,'none',?,?,?,?,?,?,?,'0','0')";
	$params=array($nic,
		strval($_POST["ipaddr"]),
		strval($_POST["netmask"]),
		strval($_POST["cdir"]),
		strval($_POST["gateway"]),
		intval($_POST["ForceGateway"]),
		intval($_POST["failover"]),
		intval($_POST["metric"]));

	if($ID>0){
		$sql="UPDATE nics_virtuals SET nic=?,
		ipaddr=?,
		netmask=?,
		cdir=?,
		gateway=?,
		ForceGateway=?,
		failover=?,
		metric=?
		WHERE ID=?";
		$params[]=$ID;
	}

    writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
    $q->QUERY_SQL_PARAMS($sql,$params);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}


    $sock=new sockets();
    // Le nom d'interface part dans un CHEMIN d'URL: il doit etre encode, sinon
    // un "/" ou un "?" dans la valeur change la route appelee.
    $sock->REST_API("/system/network/virtual/interfaces/build/".urlencode($nic));


	return admin_tracks("Create or Update Virtual Network Interface from {$_POST["nic"]} {$_POST["ipaddr"]}");
}
function lastmetric(){
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$sql="SELECT metric as tcount FROM `nics` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
	$results=$q->QUERY_SQL($sql);$ligne=$results[0];
	$hash[$ligne["metric"]]=$ligne["metric"];

	$sql="SELECT metric as tcount FROM `nics_vlan` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
	$results=$q->QUERY_SQL($sql);$ligne=$results[0];
	$hash[$ligne["metric"]]=$ligne["metric"];

	$sql="SELECT metric as tcount FROM `nic_virtuals` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
	$results=$q->QUERY_SQL($sql);$ligne=$results[0];
	$hash[$ligne["metric"]]=$ligne["metric"];

	krsort($hash[$ligne["metric"]]);
	foreach ($hash as $a=>$b){
        VERBOSE("$a=>$b",__LINE__);
        $f[]=$b;
    }

	return $f[0]+1;

}

function nic_bond():bool{
    $eth=$_GET["nic-bond"];
    $tpl=new template_admin();
    if(!bond_valid_name($eth)){
        echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger'>Invalid bond interface name</div>");
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="SELECT * FROM `bond_interfaces` WHERE `nic`='".$q->sqlite_escape_string2($eth)."'";
    $results=$q->QUERY_SQL($sql);

    // A bond that was just created has no row yet on older appliances (the
    // daemon now seeds one). Without this guard every field below raises an
    // "undefined array key" warning and bond-id silently becomes 0, which used
    // to make the save INSERT a second row for the same interface.
    if(!isset($results[0]) || !is_array($results[0])){
        $results[0]=array("ID"=>0,"nic"=>$eth,"members"=>"","primary_member"=>"",
            "mode"=>0,"miimon"=>100,"downdelay"=>200,"updelay"=>200,
            "xmit_hash_policy"=>"","lacp_rate"=>"");
    }

    if(intval($results[0]["miimon"])  == 0) {$results[0]["miimon"] =100;}
    if(intval($results[0]["downdelay"])  == 0) {$results[0]["downdelay"] =200;}
    if(intval($results[0]["updelay"])  == 0) {$results[0]["updelay"] =100;}

    // Members already enslaved by ANOTHER bond must not be offered here:
    // picking one silently steals it from the other bond at the next apply,
    // and the kernel then refuses the second enslavement without the console
    // ever reporting it.
    $takenByOtherBonds=array();
    $allBonds=$q->QUERY_SQL("SELECT nic,members FROM `bond_interfaces`");
    if(is_array($allBonds)){
        foreach ($allBonds as $bondRow){
            if($bondRow["nic"]==$eth){continue;}
            foreach (explode(",",$bondRow["members"]) as $takenMember){
                $takenMember=trim($takenMember);
                if($takenMember==""){continue;}
                $takenByOtherBonds[$takenMember]=$bondRow["nic"];
            }
        }
    }

    $listInterfaces=array();
    $availableInterfaces=$GLOBALS["CLASS_UNIX"]->NETWORK_ALL_INTERFACES();
    foreach ($availableInterfaces as $index=>$values)
    {
        if($index=='lo' || preg_match('/bond/i', $index)|| preg_match('/tun/i', $index)){
           unset($availableInterfaces[$index]);
           continue;
       }
        if(isset($takenByOtherBonds[$index])){
            unset($availableInterfaces[$index]);
        }

    }
    foreach ($availableInterfaces as $key=>$item) {
        $listInterfaces[$key] = $key;
    }
    $selectMembers = array();
    $selectMembersExplode = explode(",",$results[0]["members"]);
    foreach ($selectMembersExplode as $members){
        $members=trim($members);
        if($members==""){continue;}
        $selectMembers["$members"]="$members";
    }
    $modes[0]="Balance-rr";
    $modes[1]="Active-Backup";
    $modes[2]="Balance XOR";
    $modes[3]="Broadcast";
    $modes[4]="IEEE 802.3ad / LACP";
    $modes[5]="Transmit Load Balancing";
    $modes[6]="Adaptive Load Balancing";

    $xmit_hash_policy["layer2"]="layer2";
    $xmit_hash_policy["layer2+3"]="layer2+3";
    $xmit_hash_policy["layer3+4"]="layer3+4";
    $xmit_hash_policy["encap2+3"]="encap2+3";
    $xmit_hash_policy["encap3+4"]="encap3+4";
    $lacp_rate[0]="slow";
    $lacp_rate[1]="fast";
    $form[]=$tpl->field_hidden("save-bond-interface", $eth);
    $form[]=$tpl->field_hidden("bond-id", intval($results[0]["ID"]));
    $form[]=$tpl->field_section("{bond}","{bond_explain}");
    // The primary member is only meaningful in modes 1/5/6 and must be
    // clearable, otherwise the first entry stays selected for LACP/XOR modes
    // and the form rejects its own default value.
    $primaryList=array(""=>"{none}");
    foreach ($listInterfaces as $key=>$item){$primaryList[$key]=$item;}
    $form[]=$tpl->field_picklist($listInterfaces,"members","{bond_members}",$selectMembers);
    $form[]=$tpl->field_array_hash($primaryList,"primary_member","{primary_member}",$results[0]["primary_member"],false,"{primary_member_explain}");
    $form[]=$tpl->field_array_hash($modes,"bond_mode","{bond_mode}",intval($results[0]["mode"]),true,"{bond_mode_explain}");
    $form[]=$tpl->field_numeric("bond_miimon","{bond_miimon}",intval($results[0]["miimon"]),"{bond_miimon_explain}");
    $form[]=$tpl->field_numeric("bond_downdelay","{bond_downdelay}",intval($results[0]["downdelay"]),"{bond_downdelay_explain}");
    $form[]=$tpl->field_numeric("bond_updelay","{bond_updelay}",intval($results[0]["updelay"]),"{bond_updelay_explain}");
    $form[]=$tpl->field_section("{bond_extra}","{bond_extra_explain}");
    $form[]=$tpl->field_array_hash($xmit_hash_policy,"xmit_hash_policy","{xmit_hash_policy}",$results[0]["xmit_hash_policy"],false,"{xmit_hash_policy_explain}");
    $form[]=$tpl->field_array_hash($lacp_rate,"lacp_rate","{lacp_rate}",$results[0]["lacp_rate"],false,"{lacp_rate_explain}");
    $priv           = "AsSystemAdministrator";
    //$jsRestart      = "restart_js()";
    $rf=refreshjs();

    $jsafter="$rf;dialogInstance2.close();";
    $myform         = $tpl->form_outside(null, $form,null,"{apply}",$jsafter,$priv);
    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}

// bond_valid_name gates every path that reaches the database or the daemon
// with an interface name coming from the browser.
function bond_valid_name($eth):bool{
    return preg_match("#^bond[0-9]{1,4}$#", (string)$eth)==1;
}

// bond_valid_member accepts only names the kernel currently exposes. Members
// are the one bond field that is free text in the POST, so validating them
// against the live interface list is what keeps them out of the SQL below.
function bond_valid_member($member):bool{
    if(!preg_match("#^[a-zA-Z0-9_.:-]{1,15}$#", (string)$member)){return false;}
    return is_dir("/sys/class/net/".$member);
}

function save_bond_interface(){
    if(!netif_require_admin()){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $eth=$_POST["save-bond-interface"];
    $ID=intval($_POST["bond-id"]);
    $mode=intval($_POST["bond_mode"]);
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    //Error Detection
    if(!bond_valid_name($eth)){
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{invalid_interface}"));return;
    }
    if(empty($_POST["members"])){
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{select_at_least_one_member}"));return;
    }

    // Every value below is interpolated into SQL, so each one is constrained to
    // its own finite domain first: interface names must exist in /sys/class/net,
    // the numeric knobs go through intval() and the two enums are matched
    // against their allowed values.
    $membersList=array();
    foreach (explode(",",$_POST["members"]) as $member){
        $member=trim($member);
        if($member==""){continue;}
        if(!bond_valid_member($member)){
            echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{invalid_interface}").": $member");return;
        }
        $membersList[$member]=$member;
    }
    if(count($membersList)==0){
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{select_at_least_one_member}"));return;
    }
    $primary_member=trim($_POST["primary_member"]);
    if($primary_member<>"" && !bond_valid_member($primary_member)){
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{invalid_interface}").": $primary_member");return;
    }
    $xmit_hash_policy=trim($_POST["xmit_hash_policy"]);

    $allowedHash=array(""=>true,"layer2"=>true,"layer2+3"=>true,"layer3+4"=>true,"encap2+3"=>true,"encap3+4"=>true);
    if(!isset($allowedHash[$xmit_hash_policy])){
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{xmit_hash_policy}").": $xmit_hash_policy");return;
    }
    $miimon=intval($_POST["bond_miimon"]);
    $downdelay=intval($_POST["bond_downdelay"]);
    $updelay=intval($_POST["bond_updelay"]);
    if($mode<0 || $mode>6){
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{bond_mode}").": $mode");return;
    }

    // Precedence trap: the original test read `!$mode==1 || !$mode==5 || ...`,
    // which PHP evaluates as `(!$mode)==1 || ...`. It was therefore only ever
    // true for mode 0, so a primary member could be saved for LACP or XOR —
    // where the daemon silently drops it, leaving the console showing a value
    // the kernel never received.
    if($mode!=1 && $mode!=5 && $mode!=6){
        if($primary_member<>""){
            echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{primary_interfaces_is_only_supported_in_active_backup_mode}"));return;
        }

    }
    if($mode==1 || $mode==5 || $mode==6){
        if($primary_member==""){
            echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{active_backup_mode_needs_one_primary_interface}"));return;
        }
        if(!isset($membersList[$primary_member])) {
            echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{primary_member_is_not_in_member_list}"));return;
        }
    }

    $lacp_rate=trim($_POST["lacp_rate"]);
    if($mode==2 || $mode==4 || $mode==5 || $mode==6) {
        if($xmit_hash_policy==""){
            echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{xmit_hash_policy_mandatory}"));return;
        }
        if(intval($lacp_rate)>1){$lacp_rate="0";}
        if($lacp_rate==""){
            echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{lacp_rate_mandatory}"));return;
        }
        $lacp_rate=strval(intval($lacp_rate));
    }

    if($mode==0 || $mode==1 || $mode==3) {
        if($xmit_hash_policy<>""){
            echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{xmit_hash_policy_not_supported}"));return;
        }
        if($lacp_rate<>""){
            echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{lacp_rate_not_supported}"));return;
        }
    }

    // Members that were part of the bond but are not any more must get their
    // standalone nics row back. Without this the NIC stayed deleted from the
    // table: gone from the interface list, ignored by the builder, and only
    // recoverable by editing SQLite by hand.
    $previousMembers=array();
    $previousRow=$q->QUERY_SQL("SELECT members FROM `bond_interfaces` WHERE `nic`='".$q->sqlite_escape_string2($eth)."'");
    if(isset($previousRow[0]["members"])){
        foreach (explode(",",$previousRow[0]["members"]) as $previousMember){
            $previousMember=trim($previousMember);
            if($previousMember==""){continue;}
            $previousMembers[$previousMember]=$previousMember;
        }
    }

    $membersSQL=$q->sqlite_escape_string2(@implode(",",array_keys($membersList)));
    $ethSQL=$q->sqlite_escape_string2($eth);
    $primarySQL=$q->sqlite_escape_string2($primary_member);
    $hashSQL=$q->sqlite_escape_string2($xmit_hash_policy);
    $lacpSQL=$q->sqlite_escape_string2($lacp_rate);

    // Decide INSERT vs UPDATE from the table, not from the hidden bond-id: a
    // stale form (opened before the row existed) or a double submit used to
    // insert a SECOND row for the same interface. The daemon then kept only
    // one of them in BondInterfaces() while AllBondInterfaces() returned the
    // bond twice.
    $existing=$q->QUERY_SQL("SELECT ID FROM `bond_interfaces` WHERE `nic`='$ethSQL'");
    if(isset($existing[0]["ID"])){$ID=intval($existing[0]["ID"]);}

    $sql="INSERT INTO bond_interfaces (nic,members,primary_member,mode,miimon,downdelay,updelay,xmit_hash_policy,lacp_rate) VALUES('$ethSQL','$membersSQL','$primarySQL','$mode','$miimon','$downdelay','$updelay','$hashSQL','$lacpSQL');";

    if($ID>0){
        $sql="UPDATE bond_interfaces SET nic='$ethSQL',
		members='$membersSQL',
		primary_member='$primarySQL',
		mode='$mode',
		miimon='$miimon',
		downdelay='$downdelay',
		updelay='$updelay',
        xmit_hash_policy='$hashSQL',
        lacp_rate='$lacpSQL'
		WHERE ID={$ID}";
    }


    writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);

    $q->QUERY_SQL($sql);
    if(!$q->ok){if(preg_match("#Unknown col#i", $q->mysql_error)){$q->BuildTables();$q->QUERY_SQL($sql);}}
    if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}

    foreach ($membersList as $nicToReset) {
        $nicSQL=$q->sqlite_escape_string2($nicToReset);
        $q->QUERY_SQL("DELETE from `nics` where `Interface`='$nicSQL'");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ETHCONFIG-$nicToReset","");
        $nicInfo = $GLOBALS["CLASS_UNIX"]->InterfaceToIP("$nicToReset");
        if(is_array(($nicInfo))){
            if($nicInfo["IP"]!=="0.0.0.0"){
                writelogs("RESET IFACE {$nicInfo["IP"]} {$nicInfo["NETMASK"]} $nicToReset",__FUNCTION__,__FILE__,__LINE__);
                $ippref = $GLOBALS["CLASS_UNIX"]->CleanInterfacesForBondMembers($nicToReset,$nicInfo["IP"],$nicInfo["NETMASK"]);
                // framework/ is deprecated: this used to shell out to
                // exec.virtuals-ip.php --reset-nic, itself running two `ip`
                // commands. The daemon does it directly and validates both
                // arguments instead of interpolating them into a shell string.
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/reset/".urlencode($nicToReset)."?cidr=".urlencode($ippref));
            }
        }
    }

    foreach ($previousMembers as $droppedMember){
        if(isset($membersList[$droppedMember])){continue;}
        bond_restore_member_row($q,$droppedMember);
    }

    // Enroler des membres rend leurs regles de routage caduques et en cree le
    // besoin pour le bond : ce sont les membres qui portaient une regle
    // "RouteForethX", et c'est desormais le bond qui porte les routes.
    // CheckAllRoutesRules() cree la regle manquante pour chaque interface
    // presente dans nics — donc pour le bond — et est idempotent.
    // fw.network.routing.php masque de son cote les regles des membres.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/iproute/checks");
}

// bond_restore_member_row re-creates the standalone nics row of an interface
// that just left a bond, mirroring what the daemon does when a whole bond is
// deleted (/system/network/bond/delete).
function bond_restore_member_row($q,$member):bool{
    if(!bond_valid_member($member)){return false;}
    $memberSQL=$q->sqlite_escape_string2($member);
    $exists=$q->QUERY_SQL("SELECT Interface FROM `nics` WHERE `Interface`='$memberSQL'");
    if(isset($exists[0]["Interface"])){return true;}
    $metric=$q->QUERY_SQL("SELECT MAX(metric) AS m FROM `nics`");
    $newMetric=intval(isset($metric[0]["m"])?$metric[0]["m"]:0)+1;
    $q->QUERY_SQL("INSERT INTO nics (NICNAME,netzone,enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,
        BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw,
        NoInternetCheck,checksum_offloading)
        VALUES ('$memberSQL','$memberSQL',1,'0.0.0.0','0.0.0.0','0.0.0.0',1000,1500,'$memberSQL',1,'','','','','','0.0.0.0','','',$newMetric,0,'','',0,1)");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ETHCONFIG-$member","");
    writelogs("$member released from its bond, standalone row restored",__FUNCTION__,__FILE__,__LINE__);
    return $q->ok;
}
function nic_virtuals(){

	$eth=$_GET["nic-virtuals"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!mpx_valid_eth($eth)){echo $tpl->div_error("{invalid_interface}");return;}
    echo "<div id='nics-virtuals'></div><script>LoadAjaxSilent('nics-virtuals','$page?nic-virtuals-list=".urlencode($eth)."');</script>";
}
function nic_vlans():bool{
    $page=CurrentPageName();
    $eth=$_GET["nic-vlans"];
    $tpl=new template_admin();

    $topbuttons[] = array("Loadjs('$page?nic-vlan-js=0&eth=$eth');", ico_plus, "{new_vlan}");
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    $html[]="</div>";

    $html[]="<div id='nics-vlans'></div>
	<script>
		LoadAjax('nics-vlans','$page?nic-vlans-list=$eth');
	</script>	
	";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nic_config_tab(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $iconic=ico_nic;
	$eth=$_GET["nic-config-tab"];
    $icospe=ico_speed;
    $md=$_GET["md"];
	$miiJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/mii-tools/".urlencode($eth)));
	$MIITOOLS=array();
	if(is_object($miiJson) && !empty($miiJson->status)){
		$MIITOOLS["{flow_control}"]=!empty($miiJson->flow_control);
	}
    $EnableVLANs=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVLANs");
    $FIRECRACKER_VM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FIRECRACKER_VM"));
	$bonding = false;
    if(preg_match("#^bond[0-9]+#", $eth)){
        $bonding = true;
    }
	$array["<i class='$iconic'></i>&nbsp;$eth"]="$page?nic-config=$eth&md=$md";
    $EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
    if($EnableipV6==1){
        $array["IPv6"]="fw.network.interfaces.ipv6.php?nic=$eth&md=$md";
    }
    if($FIRECRACKER_VM==1){
        $bonding=false;

    }

    if($bonding) {
        $array["{bond}"]="$page?nic-bond=$eth&md=$md";
    }

    if($FIRECRACKER_VM==0) {
        if (preg_match("#^(wlan|wlp)[0-9]+#", $eth)) {
            $array["Wifi"] = "fw.network.wifi.php?nic=$eth&md=$md";
        }
    }

    if($FIRECRACKER_VM==0) {
        $array["Multipath"] = "$page?multipath-section=$eth&md=$md";
        $array["{security}"] = "$page?nic-security=$eth&md=$md";
        $array["{features}"] = "$page?nic-features=$eth&md=$md";
        if (!$bonding) {
            $array["{ip_aliasing}"] = "$page?nic-virtuals=$eth&md=$md";
        }
        $array["{mirror}"] = "fw.network.interfaces.mirror.php?eth=$eth&md=$md";
        if ($EnableVLANs == 1) {
            $array["VLAN"] = "$page?nic-vlans=$eth&md=$md";
        }

        if (isset($MIITOOLS["{flow_control}"])) {
            if ($MIITOOLS["{flow_control}"]) {
                $array["<i class='$icospe'></i>&nbsp;$eth"] = "$page?mii-tools=$eth&md=$md";
            }
        }
    }
	
	//$array["{infos}"]="$page?infos=yes&mac=$mac&CallBackFunction=$CallBackFunction";
	
	echo $tpl->tabs_default($array);
}


function nic_mii_tool(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["mii-tools"];

	$miiJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/mii-tools/".urlencode($eth)));
	if(!is_object($miiJson) || empty($miiJson->status)){
		$err=is_object($miiJson)?htmlspecialchars($miiJson->error ?? '{error}'):'{error}';
		echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
		return;
	}

	$form_miitolsA=array(''=>'{select}');
	$form_miitolsHT=array('HD'=>'Half duplex','FD'=>'Full duplex');
	$MII_DEFAULT='';

	$caps=(array)($miiJson->capabilities ?? array());
	foreach($caps as $val=>$b){
		$caption=$val;
		if(strlen($miiJson->info ?? '')>0 && strpos($miiJson->info,$val)!==false){
			$MII_DEFAULT=$val;
		}
		if(preg_match("#([0-9]+)(.*?)-([A-Z]+)#",$val,$re)){
			$ht=isset($form_miitolsHT[$re[3]])?$form_miitolsHT[$re[3]]:'';
			$caption="{$re[1]} {$re[2]} $ht";
		}
		$form_miitolsA[$val]=$caption;
	}

	$explflw='';
	if(!empty($miiJson->flow_control)){$explflw=' {flow_control}';}

	$form=array();
	$form[]=$tpl->field_checkbox("autonegotiation","Autonegotiation",$miiJson->autoneg?1:0);
	$form[]=$tpl->field_checkbox("flow-control","{flow_control}",$miiJson->flow_control?1:0);
	$form[]=$tpl->field_array_hash($form_miitolsA,"media","{type}",$MII_DEFAULT);
	$security="AsSystemAdministrator";
	$info=htmlspecialchars($miiJson->info ?? '');
	$html=$tpl->form_outside(null,implode("\n",$form),"$info $explflw","{apply}","LoadAjax('network-interfaces-table','$page?table=yes');",$security);
	echo $tpl->_ENGINE_parse_body($html);
}

function nic_security(){
    $md=$_GET["md"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$EnableipV6=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworksManagement");
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	$Interface=$_GET["nic-security"];
	$niClass=new system_nic($Interface);
	
	$title="$niClass->netzone: $niClass->NICNAME ($Interface)";
	$form[]=$tpl->field_hidden("nic_security", $Interface);
	$form[]=$tpl->field_checkbox("SysCtlEnable","{enabled}",$niClass->SysCtlEnable,true);
	$form[]=$tpl->field_checkbox("LogMartians","{log_martians}",$niClass->LogMartians,false,"{log_martians_text}");
	$form[]=$tpl->field_checkbox("AcceptSourceRoute","{accept_source_route}",$niClass->AcceptSourceRoute,false,"{accept_source_route_text}");
	$form[]=$tpl->field_checkbox("forwarding","{tcp_forwarding}",$niClass->forwarding,false,"{tcp_forwarding}");
	$form[]=$tpl->field_checkbox("MCForwarding","{MCForwarding}",$niClass->MCForwarding,false,"{MCForwarding}");
	$form[]=$tpl->field_checkbox("AcceptRedirects","{AcceptRedirects}",$niClass->AcceptRedirects,false,"{AcceptRedirects}");
	$form[]=$tpl->field_checkbox("SendRedirects","{SendRedirects}",$niClass->SendRedirects,false,"{SendRedirects}");
	$security="AsSystemAdministrator";
	$html=$tpl->form_outside("{security}", @implode("\n", $form),null,"{apply}","LoadAjax('network-interfaces-table','$page?table=yes');",$security);
	echo $tpl->_ENGINE_parse_body($html);

}
function nic_config():bool{
    $page=CurrentPageName();
    $eth=$_GET["nic-config"];
    $md=$_GET["md"];
    $html="<div id='progress-interface-$eth'></div>
    <div id='div-works-$eth'></div>
    <script>LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');</script>";
    echo $html;
    return true;
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_enable_interface():bool{
    if(!netif_require_admin(true)){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-enable-interface"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    $ligne=$q->mysqli_fetch_array("SELECT Interface FROM nics WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!isset($ligne["Interface"])){$ligne["Interface"]="";}
    if(strlen($ligne["Interface"])<3){
        $q->QUERY_SQL("INSERT INTO nics (enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw) VALUES (1,'0.0.0.0','255.255.255.0','',1000,1500,'".$q->sqlite_escape_string2($eth)."',1,'','','','','','0.0.0.0','','',1,0,'','')");
    }

    $q->QUERY_SQL("UPDATE nics SET enabled=1 WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Enable the Network Interface $eth");
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_disable_interface():bool{
    if(!netif_require_admin(true)){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-disable-interface"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics SET enabled=0 WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Disable the Network Interface $eth");
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_disable_dhcp():bool{
    if(!netif_require_admin(true)){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-disable-dhcp"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics SET dhcp=0 WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Disable the DHCP for the Network Interface $eth");
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_enable_dhcp():bool{
    if(!netif_require_admin(true)){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-enable-dhcp"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics SET dhcp=1 WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Enable the DHCP for the Network Interface $eth");
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_use_span_enable():bool{
    if(!netif_require_admin(true)){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-use-span-enable"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");


    $ligne=$q->mysqli_fetch_array("SELECT Interface FROM nics WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!isset($ligne["Interface"])){$ligne["Interface"]="";}
    if(strlen($ligne["Interface"])<3){
        $q->QUERY_SQL("INSERT INTO nics (enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw) VALUES (1,'0.0.0.0','255.255.255.0','',1000,1500,'".$q->sqlite_escape_string2($eth)."',1,'','','','','','0.0.0.0','','',1,0,'','')");
    }


    $q->QUERY_SQL("UPDATE nics SET UseSPAN=1 WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!$q->ok){
        writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Enable the SPAN Mode for the Network Interface $eth");
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_use_span_disable():bool{
    if(!netif_require_admin(true)){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-use-span-disable"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics SET UseSPAN=0 WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Disable the SPAN mode for the Network Interface $eth");
}


// The daemon owns the offloads decision: on by default (best throughput),
// mandatory on vmxnet3 / virtio / e1000 inside a VM, always off on a SPAN
// port, and disabled per interface by the administrator otherwise.
// A daemon older than the per-interface option has no offloads_mode field
// and no /system/network/interface/offloads route (the console is often
// updated before the daemon): "legacy" then keeps the former checksum option,
// which that daemon still applies, instead of a toggle answering 404.
function nic_offloads_state($eth):array{
    $out=array("mandatory"=>false,"disabled"=>false,"span"=>false,"driver"=>"","legacy"=>true);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/features/".urlencode($eth)));
    if(!is_object($json) || empty($json->Status)){
        return $out;
    }
    $out["legacy"]=!property_exists($json,"offloads_mode");
    $out["mandatory"]=!empty($json->offloads_mandatory);
    $out["disabled"]=!empty($json->offloads_disabled);
    $out["span"]=!empty($json->span);
    $out["driver"]=isset($json->driver)?(string)$json->driver:"";
    return $out;
}
function nic_checksum_offloading():bool{
    if(!netif_require_admin(true)){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=(string)$_GET["nic-checksum-offloading"];
    $md=preg_replace('/[^A-Za-z0-9]/','',(string)$_GET["md"]);
    // The name reaches a URL and a JS string below: interface names only.
    if(!preg_match('/^[A-Za-z0-9._:@-]{1,15}$/',$eth)){
        return $tpl->js_error("{invalid_interface}");
    }
    $st=nic_offloads_state($eth);
    if($st["legacy"]){
        return nic_checksum_offloading_legacy($tpl,$page,$eth,$md,$st["mandatory"],$st["driver"]);
    }
    $state=$st["disabled"]?"enable":"disable";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/system/network/interface/offloads/".urlencode($eth)."/$state",array()));
    if(!is_object($json)){
        return $tpl->js_error("{error} REST API");
    }
    if(empty($json->Status)){
        return $tpl->js_error(isset($json->Error)?(string)$json->Error:"{error}");
    }
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Network offloads {$state}d on the Network Interface $eth");
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_checksum_offloading_legacy($tpl,$page,$eth,$md,$mandatory,$driver):bool{
    if($mandatory){
        return $tpl->js_error("{nic_offloads_mandatory} ($driver)");
    }
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->mysqli_fetch_array("SELECT Interface,checksum_offloading FROM nics WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!$q->ok){
        return $tpl->js_error("L.".__LINE__.": ".$q->mysql_error);
    }
    if(!isset($ligne["Interface"])){
        $ligne["Interface"]="";
    }
    $enable=intval($ligne["checksum_offloading"])==0?1:0;
    $sql="UPDATE nics SET checksum_offloading=$enable WHERE Interface='".$q->sqlite_escape_string2($eth)."'";
    if(strlen($ligne["Interface"])<3){
        $sql="INSERT INTO nics (NICNAME,netzone,enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw,NoInternetCheck,checksum_offloading) VALUES ('".$q->sqlite_escape_string2($eth)."','".$q->sqlite_escape_string2($eth)."',1,'0.0.0.0','0.0.0.0','0.0.0.0',0,1500,'".$q->sqlite_escape_string2($eth)."',1,'','','','','','0.0.0.0','','',1,0,'','',0,1)";
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        return $tpl->js_error("L.".__LINE__.": ".$q->mysql_error);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    return admin_tracks("Set the TCP/IP Checksum Offloading to $enable for the Network Interface $eth");
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_internet_check_save():bool{
    if(!netif_require_admin(true)){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $md=$_GET["md"];
    $eth=$_GET["nic-internet-check"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    if(!$q->FIELD_EXISTS("nics","NoInternetCheck")){
        $q->QUERY_SQL("ALTER TABLE nics ADD COLUMN NoInternetCheck INTEGER NOT NULL DEFAULT 0");
    }
    $ligne=$q->mysqli_fetch_array("SELECT NoInternetCheck,Interface FROM nics WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!$q->ok){
        return $tpl->js_error("L.".__LINE__.": ".$q->mysql_error);
    }

    if(!isset($ligne["Interface"])){
        $ligne["Interface"]="";
    }
    if(strlen($ligne["Interface"])<3){
        $sql="INSERT INTO nics (NICNAME,netzone,enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw,NoInternetCheck) VALUES ('".$q->sqlite_escape_string2($eth)."','".$q->sqlite_escape_string2($eth)."',1,'0.0.0.0','0.0.0.0','0.0.0.0',0,1500,'".$q->sqlite_escape_string2($eth)."',1,'','','','','','0.0.0.0','','',1,0,'','',1)";
    }else{
        $NoInternetCheck=intval($ligne["NoInternetCheck"]);
        if($NoInternetCheck==1){
            $NoInternetCheck=0;
        }else{
            $NoInternetCheck=1;
        }
        $sql="UPDATE nics SET NoInternetCheck='".$q->sqlite_escape_string2($NoInternetCheck)."' WHERE Interface='".$q->sqlite_escape_string2($eth)."'";
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        return   $tpl->js_error("L.".__LINE__." :".$q->mysql_error);

    }
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    return admin_tracks("Update interface set not check internet to $NoInternetCheck");
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_name_interface_save():bool{
    if(!netif_require_admin()){return false;}
    $eth=$_POST["NicName"];
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $NICNAME=$_POST["NICNAME"];
    $netzone=$_POST["netzone"];
    $ligne=$q->mysqli_fetch_array("SELECT Interface FROM nics WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!isset($ligne["Interface"])){
        $ligne["Interface"]="";
    }
    $sql="UPDATE nics SET NICNAME='".$q->sqlite_escape_string2($NICNAME)."',netzone='".$q->sqlite_escape_string2($netzone)."'  WHERE Interface='".$q->sqlite_escape_string2($eth)."'";

    if(strlen($ligne["Interface"])<3){
        $sql="INSERT INTO nics (NICNAME,netzone,enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw) VALUES ('".$q->sqlite_escape_string2($NICNAME)."','".$q->sqlite_escape_string2($netzone)."',1,'0.0.0.0','0.0.0.0','0.0.0.0',0,1500,'".$q->sqlite_escape_string2($eth)."',1,'','','','','','0.0.0.0','','',1,0,'','')";

    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo  $tpl->post_error("--------- $eth ---------\n".$q->mysql_error."\n$sql");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    return admin_tracks("Update interface name $eth to $NICNAME/$netzone");
}
function nic_name_interface_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $md=$_GET["md"];
    $eth=$_GET["nic-name-interface-popup"];
    $nic=new system_nic($eth,true);
    $form[]=$tpl->field_hidden("NicName",$eth);
    $form[]=$tpl->field_text("NICNAME", "{name}", $nic->NICNAME);
    $form[]=$tpl->field_text("netzone", "{netzone}", $nic->netzone);
    $js[]="dialogInstance5.close();";
    $js[]="LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $js[]=refreshjs($eth);

    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",@implode(";",$js),$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nic_addr_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $md=$_GET["md"];
    $eth=$_GET["nic-address-popup"];
    $nic=new system_nic($eth,true);
    $form[]=$tpl->field_hidden("NicAddr",$eth);
    $form[]=$tpl->field_ipaddr("IPADDR", "{tcp_address}", $nic->IPADDR);
    $form[]=$tpl->field_ipaddr("NETMASK", "{netmask}", $nic->NETMASK);
    $form[]=$tpl->field_ipaddr("BROADCAST", "{broadcast}", $nic->BROADCAST);
    $form[]=$tpl->field_numeric("mtu","MTU",$nic->mtu);
    $form[]=$tpl->field_numeric("txqueuelen","{txqueuelen}",$nic->txqueuelen);
    $js[]="dialogInstance5.close();";
    $js[]="LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $js[]=refreshjs($eth);

    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",@implode(";",$js),$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nic_gateway_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $md=$_GET["md"];
    $eth=$_GET["nic-gateway-popup"];
    $nic=new system_nic($eth,true);
    $form[]=$tpl->field_hidden("NicGateway",$eth);
    $form[]=$tpl->field_ipaddr("GATEWAY", "{gateway}", $nic->GATEWAY);
    $form[]=$tpl->field_checkbox("defaultroute","{default_gateway}",$nic->defaultroute,false);
    $form[]=$tpl->field_numeric("metric","{metric}",$nic->metric);
    $js[]="dialogInstance5.close();";
    $js[]="LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $js[]=refreshjs($eth);

    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",@implode(";",$js),$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_addr_save():bool{
    if(!netif_require_admin()){return false;}
    $eth=$_POST["NicAddr"];
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $IPADDR=$_POST["IPADDR"];
    $NETMASK=$_POST["NETMASK"];
    $BROADCAST=$_POST["BROADCAST"];
    $MTU=intval($_POST["MTU"]);
    $txqueuelen=intval($_POST["txqueuelen"]);

    $ligne=$q->mysqli_fetch_array("SELECT Interface FROM nics WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!isset($ligne["Interface"])){$ligne["Interface"]="";}
    $sql="UPDATE nics SET `IPADDR`='".$q->sqlite_escape_string2($IPADDR)."',`NETMASK`='".$q->sqlite_escape_string2($NETMASK)."',`BROADCAST`='".$q->sqlite_escape_string2($BROADCAST)."',
                txqueuelen=$txqueuelen, mtu=$MTU WHERE Interface='".$q->sqlite_escape_string2($eth)."'";
    if(strlen($ligne["Interface"])<3){
        $sql="INSERT INTO nics (enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw) VALUES (1,'".$q->sqlite_escape_string2($IPADDR)."','".$q->sqlite_escape_string2($NETMASK)."','".$q->sqlite_escape_string2($BROADCAST)."',$txqueuelen,$MTU,'".$q->sqlite_escape_string2($eth)."',1,'','','','','','0.0.0.0','','',1,0,'','')";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo  $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    return admin_tracks("Update $eth network interface address to $IPADDR/$NETMASK - $BROADCAST");
}
// Pentest 2026-11-04 : l'interface et les champs du formulaire etaient concatenes dans la requete.
function nic_gateway_save():bool{
    if(!netif_require_admin()){return false;}
    $eth=$_POST["NicGateway"];
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $GATEWAY=$_POST["GATEWAY"];
    $defaultroute=intval($_POST["defaultroute"]);
    $metric=intval($_POST["metric"]);
    if($defaultroute==1){
        $q->QUERY_SQL("UPDATE nics SET `defaultroute`='0' WHERE defaultroute='1'");
    }
    $q->QUERY_SQL("UPDATE nics SET `defaultroute`='".$q->sqlite_escape_string2($defaultroute)."',`metric`='".$q->sqlite_escape_string2($metric)."',`GATEWAY`='".$q->sqlite_escape_string2($GATEWAY)."'  WHERE Interface='".$q->sqlite_escape_string2($eth)."'");
    if(!$q->ok){
        echo  $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    return admin_tracks("Update $eth network interface gateway address to $GATEWAY default route to $defaultroute and metric $metric");
}
// Interface settings panel (nic-config2): four sections on two columns,
// same data and same actions as the former flat list.
function nic_config2():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $FIRECRACKER_VM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FIRECRACKER_VM"));
    $security="AsSystemAdministrator";
    $md=preg_replace('/[^A-Za-z0-9]/','',(string)$_GET["md"]);
    $eth=(string)$_GET["nic-config2"];
    // The name reaches JavaScript strings below: interface names only.
    if(!preg_match('/^[A-Za-z0-9._:@-]{1,15}$/',$eth)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{invalid_interface}"));
        return false;
    }
    $canEdit=$tpl->IsSecurity($security) && $FIRECRACKER_VM==0;
    $nic=new system_nic($eth,true);
    $tcp_addr=null;$mac_addr=null;$netmask=null;$gateway=null;
    $InternetCheck=$nic->NoInternetCheck==0?1:0;

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/info/".urlencode($eth)));
    if(json_last_error()>JSON_ERROR_NONE){
        writelogs("/system/network/interface/info/$eth Error:".json_last_error_msg(),__FUNCTION__,__FILE__,__LINE__);
        echo $tpl->_ENGINE_parse_body($tpl->div_error(json_last_error_msg()));
    }elseif(is_object($json) && !empty($json->Status)){
        $tcp_addr=$json->IpAddr;
        $mac_addr=$json->MacAddr;
        $netmask=$json->NetMask;
        $gateway=$json->Gateway;
    }

    $notices=array();
    if($nic->NotSaved){
        if(preg_match("#^veth#",$eth)){
            echo $tpl->_ENGINE_parse_body($tpl->div_error("{this_interface_not_saved}"));
            return true;
        }
        $notices[]=$tpl->div_warning("{this_interface_not_saved}");
    }
    $deleteJs="";
    if($nic->macvlan==1 || $nic->ipvlan==1){
        $kind=$nic->macvlan==1?"Macvlan":"IPvlan";
        $info=str_replace("Macvlan",$kind,$tpl->_ENGINE_parse_body("{macvlan_info}"));
        $notices[]="<div class='nicx-note'><strong>".htmlspecialchars("$eth: $kind")."</strong> ".str_replace("%s","<strong>".htmlspecialchars((string)$nic->physical)."</strong>",$info)."</div>";
        $deleteJs="Loadjs('$page?delete-macvlan=$eth')";
    }
    // A bond could be created but never removed: there was no delete path
    // anywhere in the console, so getting rid of one meant editing SQLite by
    // hand and leaving the members enslaved to a master with no configuration.
    if(preg_match("#^bond[0-9]+$#",$eth)){
        $deleteJs="Loadjs('$page?delete-bond-js=$eth')";
    }
    $js=function($q) use($page,$eth,$md,$canEdit){
        return $canEdit?"Loadjs('$page?$q=$eth&md=$md');":"";
    };

    // --- General & hardware
    $general=array();
    if($nic->macvlan==1){
        $general[]=nicx_row("fa-solid fa-network-wired","info","Macvlan","",nicx_code($eth),$canEdit?"Loadjs('$page?ModifyMacVlanName-js=yes&value=$eth&md=$md');":"");
    }
    if($nic->enabled==0){
        $general[]=nicx_row("fa-solid fa-power-off","off","{enabled}","{nicx_enabled_sub}",nicx_state(false),$js("nic-enable-interface"));
        nicx_output($tpl,$notices,array(nicx_section("fa-solid fa-microchip","blue","{nicx_general}","{nicx_general_sub}","",$general)),array(),"",$deleteJs,$security);
        return true;
    }
    $general[]=nicx_row("fa-solid fa-check","on","{enabled}","{nicx_enabled_sub}",nicx_state(true),$js("nic-disable-interface"));
    $general[]=nicx_row("fa-solid fa-tag","info","{name}","{nicx_name_sub}",nicx_code("$nic->NICNAME - $nic->netzone"),$js("nic-name-interface"));
    $macVal="";
    $macCopy="";
    if(strlen((string)$mac_addr)>0){
        $macEsc=htmlspecialchars((string)$mac_addr,ENT_QUOTES);
        $macVal=nicx_code($mac_addr);
        $macCopy="<button type='button' class='ak-act' title='Copy' OnClick=\"event.stopPropagation();if(navigator.clipboard){navigator.clipboard.writeText('$macEsc');}\"><i class='fa-regular fa-copy'></i></button>";
    }
    $general[]=nicx_row("fa-solid fa-microchip","info","{ComputerMacAddress}","{nicx_mac_sub}",$macVal,"",$macCopy);

    // --- Advanced features
    $advanced=array();
    if($nic->UseSPAN==1){
        $advanced[]=nicx_row("fa-solid fa-right-left","on","{free_mode} (SPAN)","{nicx_span_sub}",nicx_state(true),$js("nic-use-span-disable"));
    }else{
        $advanced[]=nicx_row("fa-solid fa-right-left","off","{free_mode} (SPAN)","{nicx_span_sub}",nicx_state(false),$js("nic-use-span-enable"));
    }
    $off=nic_offloads_row_data($page,$eth,$md,$nic,$canEdit);
    $advanced[]=nicx_row("fa-solid fa-gauge-high",$off["on"]?"on":"off",$off["title"],$off["sub"],nicx_state($off["on"]),$off["js"]);

    $left=array(
        nicx_section("fa-solid fa-microchip","blue","{nicx_general}","{nicx_general_sub}","",$general),
        nicx_section("fa-solid fa-sliders","teal","{nicx_advanced}","{nicx_advanced_sub}","",$advanced),
    );
    if($nic->UseSPAN==1){
        nicx_output($tpl,$notices,$left,array(),"",$deleteJs,$security);
        return true;
    }

    // --- IP configuration & routing
    $ip=array();
    $right=array();
    $footState="";
    $applyJs="";
    if($nic->dhcp==1){
        $ip[]=nicx_row("fa-solid fa-rotate","on","{use_dhcp}","{nicx_dhcp_sub}",nicx_state(true),$js("nic-disable-dhcp"));
        if(strlen((string)$tcp_addr)>0 && $tcp_addr<>"0.0.0.0"){
            $ip[]=nicx_row("fa-solid fa-ethernet","info","{address}","{nicx_dhcp_lease}",nicx_code($tcp_addr),"");
        }
        $ipBadge="<span class='ak-tag ak-info'>DHCP</span>";
    }else{
        $ip[]=nicx_row("fa-solid fa-rotate","off","{use_dhcp}","{nicx_dhcp_sub}",nicx_state(false),$js("nic-enable-dhcp"));
        if(!is_null($nic->IPADDR)){$nic->IPADDR=trim($nic->IPADDR);}
        if($nic->GATEWAY=="0.0.0.0"){$nic->GATEWAY="";}
        if(is_null($nic->IPADDR) && !is_null($tcp_addr)){$nic->IPADDR=$tcp_addr;}
        if(is_null($nic->NETMASK) && !is_null($netmask)){$nic->NETMASK=$netmask;}
        if(is_null($nic->GATEWAY) && !is_null($gateway)){$nic->GATEWAY=$gateway;}
        if($nic->IPADDR=="0.0.0.0"){$nic->IPADDR="";}
        if(strlen((string)$nic->IPADDR)==0){
            $ip[]=nicx_row("fa-solid fa-ethernet","off","{address}","",nicx_state(false),$js("nic-address"));
        }else{
            $ClassIP=new IP();
            $cdr=$ClassIP->mask2cdr($nic->NETMASK);
            $val=nicx_code("$nic->IPADDR/$cdr");
            if($nic->mtu>900){$val.="<span class='nicx-badge nicx-badge-solid'>MTU ".intval($nic->mtu)."</span>";}
            $sub="";
            if(strlen((string)$nic->BROADCAST)>3){$sub="{broadcast} ".htmlspecialchars((string)$nic->BROADCAST);}
            $ip[]=nicx_row("fa-solid fa-ethernet","info","{address}",$sub,$val,$js("nic-address"));
            // Stored address against the one the system runs right now.
            if(strlen((string)$tcp_addr)>0 && $tcp_addr<>"0.0.0.0"){
                if($tcp_addr==$nic->IPADDR){
                    $footState="<span class='ak-dot ak-ok'></span>{nicx_applied}";
                }else{
                    $footState="<span class='ak-dot ak-warn'></span>".str_replace("%s","<strong>".htmlspecialchars($tcp_addr)."</strong>",$tpl->_ENGINE_parse_body("{nicx_not_applied}"));
                }
            }
        }
        $IP=new IP();
        if(!$IP->isValid($nic->GATEWAY)){$nic->GATEWAY="";}
        if($nic->GATEWAY==""){
            $ip[]=nicx_row("fa-solid fa-route","off","{gateway}","",nicx_state(false),$js("nic-gateway"));
        }else{
            $val="";
            if($nic->defaultroute==1){$val.="<span class='nicx-badge nicx-badge-teal'>{default_gateway}</span>";}
            if($nic->metric>0){$val.="<span class='nicx-badge nicx-badge-dark'>{metric} ".intval($nic->metric)."</span>";}
            $ip[]=nicx_row("fa-solid fa-route","info","{gateway}",nicx_mono($nic->GATEWAY),$val,$js("nic-gateway"));
        }
        $ipBadge="<span class='ak-tag ak-info'>{nicx_static}</span>";
        if($FIRECRACKER_VM==0){$applyJs="Loadjs('$page?nic-apply=$eth&md=$md');";}
    }
    $right[]=nicx_section("fa-solid fa-globe","blue","{nicx_ip}","{nicx_ip_sub}",$ipBadge,$ip);

    // --- Connectivity
    $conn=array(nicx_row("fa-solid fa-earth-europe",$InternetCheck==1?"on":"off","{internet_access}","{nicx_inet_sub}",nicx_state($InternetCheck==1),$js("nic-internet-check")));
    $right[]=nicx_section("fa-solid fa-earth-europe",$InternetCheck==1?"green":"teal","{nicx_connectivity}","{nicx_connectivity_sub}","",$conn);

    nicx_output($tpl,$notices,$left,$right,$footState,$deleteJs,$security,$applyJs);
    return true;
}
// Offloads row of the panel: same rules as the former table row (legacy
// daemon, mandatory virtual card, SPAN port, per-interface option).
function nic_offloads_row_data($page,$eth,$md,$nic,$canEdit):array{
    $st=nic_offloads_state($eth);
    $driver=htmlspecialchars($st["driver"]);
    $toggle=$canEdit?"Loadjs('$page?nic-checksum-offloading=$eth&md=$md');":"";
    if($st["legacy"]){
        if($st["mandatory"]){
            return array("title"=>"TCP/IP Checksum Offloading","sub"=>"{nic_offloads_mandatory} ($driver)","on"=>true,"js"=>"");
        }
        return array("title"=>"TCP/IP Checksum Offloading","sub"=>"","on"=>intval($nic->checksum_offloading)==1,"js"=>$toggle);
    }
    if($st["mandatory"]){
        return array("title"=>"{nic_offloads}","sub"=>"{nic_offloads_mandatory} ($driver)","on"=>true,"js"=>"");
    }
    if($st["span"]){
        return array("title"=>"{nic_offloads}","sub"=>"{nic_offloads_span}","on"=>false,"js"=>"");
    }
    return array("title"=>"{nic_offloads}","sub"=>"{nic_offloads_explain}","on"=>!$st["disabled"],"js"=>$toggle);
}
function nicx_code($v):string{
    return "<code class='nicx-code'>".htmlspecialchars((string)$v)."</code>";
}
function nicx_mono($v):string{
    return "<span class='ak-mono'>".htmlspecialchars((string)$v)."</span>";
}
function nicx_state($on):string{
    if($on){
        return "<span class='ak-pill ak-ok'><span class='ak-dot'></span>{active2}</span>";
    }
    return "<span class='ak-pill ak-off'><span class='ak-dot'></span>{inactive}</span>";
}
// One setting: icon, title, subtitle, value and the edit button. $js empty =
// read-only (no privilege, mandatory value, Firecracker VM).
function nicx_row($icon,$tone,$title,$sub,$value,$js,$act=""):string{
    $click=$js<>""?" nicx-row-js\" OnClick=\"if(event.target.closest('button,a,input,select,textarea')){return;}$js":"";
    $edit="<div class='nicx-act'>".($act<>""?$act:"").($js<>""?"<button type='button' class='ak-act' OnClick=\"$js\"><i class='fa-solid fa-pen'></i></button>":"")."</div>";
    $subHtml=$sub<>""?"<div class='nicx-sub'>$sub</div>":"";
    return "<div class=\"nicx-row$click\"><div class='nicx-chip nicx-chip-$tone'><i class='$icon'></i></div>"
        ."<div class='nicx-txt'><div class='nicx-title'>$title</div>$subHtml</div>"
        ."<div class='nicx-val'>$value</div>$edit</div>";
}
function nicx_section($icon,$tone,$title,$sub,$badge,$rows):string{
    return "<section class='ak-card nicx-sec nicx-sec-$tone'><div class='nicx-sec-head'><div class='nicx-sec-ico nicx-chip-$tone'><i class='$icon'></i></div>"
        ."<div class='nicx-sec-txt'><div class='nicx-sec-title'>$title</div><div class='nicx-sub'>$sub</div></div>$badge</div>"
        ."<div class='nicx-sec-body'>".implode("",$rows)."</div></section>";
}
function nicx_output($tpl,$notices,$left,$right,$footState,$deleteJs,$security,$applyJs=""):void{
    $html=array();
    $html[]="<div class='nicx' style='margin-top:10px'>";
    $html[]=implode("",$notices);
    $html[]="<div class='nicx-grid".(count($right)==0?" nicx-grid-1":"")."'>";
    $html[]="<div class='nicx-col'>".implode("",$left)."</div>";
    if(count($right)>0){$html[]="<div class='nicx-col'>".implode("",$right)."</div>";}
    $html[]="</div>";
    $buttons="";
    if($deleteJs<>"" && $tpl->IsSecurity($security)){
        $buttons.="<button type='button' class='ak-btn ak-btn-danger' OnClick=\"$deleteJs\"><i class='fa-solid fa-trash'></i> {delete}</button>";
    }
    if($applyJs<>"" && $tpl->IsSecurity($security)){
        $buttons.="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"$applyJs\"><i class='fa-solid fa-check'></i> {apply}</button>";
    }
    if($footState<>"" || $buttons<>""){
        $html[]="<div class='nicx-foot'><div class='nicx-foot-state'>$footState</div><div class='nicx-foot-btns'>$buttons</div></div>";
    }
    $html[]="</div>";
    // The stylesheet stays outside the parser: no token lookup per CSS rule.
    echo nicx_style().$tpl->_ENGINE_parse_body(implode("",$html));
}
function nicx_style():string{
    $c=array();
    $c[]=".nicx{font-size:13px;color:#1e293b}";
    $c[]=".nicx-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start}";
    $c[]=".nicx-grid-1{grid-template-columns:minmax(0,640px)}";
    $c[]="@media (max-width:900px){.nicx-grid{grid-template-columns:1fr}}";
    $c[]=".nicx-col{display:flex;flex-direction:column;gap:18px;min-width:0}";
    $c[]=".nicx-sec-green{border-color:#a7f3d0}";
    $c[]=".nicx-sec-head{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#f8fafc;border-bottom:1px solid #f1f5f9}";
    $c[]=".nicx-sec-green .nicx-sec-head{background:#ecfdf5;border-bottom-color:#d1fae5}";
    $c[]=".nicx-sec-ico{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex:0 0 auto}";
    $c[]=".nicx-sec-txt{flex:1 1 auto;min-width:0}";
    $c[]=".nicx-sec-title{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#1e293b}";
    $c[]=".nicx-sec-body{padding:12px;display:flex;flex-direction:column;gap:10px}";
    $c[]=".nicx-row{display:flex;align-items:center;gap:12px;padding:11px 12px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;transition:border-color .15s,box-shadow .15s}";
    $c[]=".nicx-row-js{cursor:pointer}";
    $c[]=".nicx-row-js:hover{border-color:#94a3b8;box-shadow:0 1px 3px rgba(15,23,42,.08)}";
    $c[]=".nicx-chip{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:14px;flex:0 0 auto;border:1px solid #e2e8f0;background:#f1f5f9;color:#475569}";
    $c[]=".nicx-chip-on{background:#ecfdf5;color:#059669;border-color:#a7f3d0}";
    $c[]=".nicx-chip-off{background:#f1f5f9;color:#94a3b8}";
    $c[]=".nicx-chip-blue{background:#eff6ff;color:#2563eb;border-color:#dbeafe}";
    $c[]=".nicx-chip-teal{background:#f0fdfa;color:#0d9488;border-color:#ccfbf1}";
    $c[]=".nicx-chip-green{background:#d1fae5;color:#047857;border-color:#a7f3d0}";
    $c[]=".nicx-txt{flex:1 1 auto;min-width:0}";
    $c[]=".nicx-title{font-weight:600;color:#1e293b;line-height:1.3}";
    $c[]=".nicx-sub{font-size:11.5px;color:#64748b;margin-top:2px;line-height:1.35}";
    $c[]=".nicx-val{display:flex;align-items:center;gap:6px;flex:0 0 auto;max-width:60%;flex-wrap:wrap;justify-content:flex-end}";
    $c[]=".nicx-act{flex:0 0 30px;display:flex;justify-content:center}";
    $c[]=".nicx-code{font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:2px 8px;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:230px}";
    $c[]=".nicx-badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;white-space:nowrap}";
    $c[]=".nicx-badge-solid{background:#2563eb;color:#fff}";
    $c[]=".nicx-badge-teal{background:#0d9488;color:#fff}";
    $c[]=".nicx-badge-dark{background:#334155;color:#fff}";
    $c[]=".nicx-note{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;margin-bottom:14px}";
    $c[]=".nicx-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:18px;padding-top:14px;border-top:1px solid #e2e8f0}";
    $c[]=".nicx-foot-state{display:flex;align-items:center;gap:8px;color:#64748b;font-size:12px}";
    $c[]=".nicx-foot-btns{display:flex;gap:10px;margin-left:auto}";
    return "<style>".implode("",$c)."</style>";
}
function nic_apply_interface():bool{
    $eth=$_GET["nic-apply"];
    $md=$_GET["md"];
    $iface=urlencode($eth);
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/build/$iface");
    header("content-type: application/x-javascript");
    $html[]="if ( document.getElementById('div-works-$eth') ){";
    $html[]="LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $html[]="}";
    $html[]="if ( document.getElementById('netz-interfaces-status') ){";
    $html[]="LoadAjaxSilent('netz-interfaces-status','fw.network.interfaces.php?status2=yes');";
    $html[]="}";
    echo @implode("\n",$html);
    return admin_tracks("Apply network configuration of $eth");
}

function nic_config2_old(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $md=$_GET["md"];
	$eth=$_GET["nic-config2"];
	
	$nic=new system_nic($eth,true);
	if($nic->NoInternetCheck==0){$InternetCheck=1;}else{$InternetCheck=0;}

    if($nic->macvlan==1) {
        $form[]=$tpl->field_text_button("ModifyMacVlanName","{name}",$eth);
    }
    if($nic->macvlan==1){
        $tpl->form_add_button("{delete}", "Loadjs('$page?delete-macvlan=$eth&md=$md')");
        $macvlan_info=$tpl->_ENGINE_parse_body("{macvlan_info}");
        $macvlan_info=str_replace("%s","<strong>$nic->physical</strong>",$macvlan_info);
        $html[]="<div style='margin-top:5px'>";
        $html[]=$tpl->div_explain("$eth: Macvlan||$macvlan_info");
        $html[]="</div>";
    }
    if($nic->ipvlan==1){
        $tpl->form_add_button("{delete}", "Loadjs('$page?delete-macvlan=$eth')");
        $macvlan_info=str_replace("Macvlan","IPvlan",$tpl->_ENGINE_parse_body("{macvlan_info}"));
        $macvlan_info=str_replace("%s","<strong>$nic->physical</strong>",$macvlan_info);
        $html[]="<div style='margin-top:5px'>";
        $html[]=$tpl->div_explain("$eth: IPvlan||$macvlan_info");
        $html[]="</div>";
    }
    $security="AsSystemAdministrator";
	$html[]=$tpl->form_outside("",  $form,null,"{apply}",refreshjs($eth),$security);
	echo $tpl->_ENGINE_parse_body($html);
}

function refreshjs($eth=null):string{
    $page=CurrentPageName();
    $md="";
    if(isset($_GET['md'])){
        $md=$_GET['md'];
    }

    $MAINZ["netz-interfaces-status"]="$page?status2=yes";
    $MAINZ["network-interfaces-table"]="$page?table=yes";
    if($eth<>null){$MAINZ["div-works-$eth"]="$page?nic-config2=$eth&md=$md";}
    foreach ($MAINZ as $div=>$js) {
        $jsa[] = "if( document.getElementById('$div') ){";
        $jsa[] = "LoadAjax('$div','$js');";
        $jsa[] = "}";
        }

    return @implode("",$jsa);

}

// Features tab: `ethtool -k` read through the daemon, grouped by family
// (checksums, scatter-gather/TSO, GSO/GRO, VLAN, tunnels, others), with a
// filter and active/inactive counters. Read only.
function nic_features(){
    $tpl=new template_admin();
    $nic=$_GET["nic-features"];
    if(!preg_match('/^[A-Za-z0-9_.:@-]{1,32}$/',$nic)){
        echo $tpl->div_error("{invalid_interface}");
        return;
    }
    $featuresJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/features/".urlencode($nic)));
    $features=array();
    if(is_object($featuresJson) && !empty($featuresJson->Status)){
        $features=nicf_parse((string)$featuresJson->features);
    }
    if(count($features)==0){
        echo nicx_style().nicf_style().$tpl->_ENGINE_parse_body("<div class='nicx' style='margin-top:10px'><div class='nicx-note'><i class='fa-solid fa-circle-info'></i> {nicf_none}</div></div>");
        return;
    }

    $groups=nicf_groups();
    $byGroup=array();
    foreach ($features as $name=>$f){
        $byGroup[nicf_group_of($name)][$name]=$f;
    }
    $totalOn=0;$totalOff=0;
    $cols=array(array(),array());
    $weight=array(0,0);
    foreach ($groups as $gkey=>$g){
        if(!isset($byGroup[$gkey])){continue;}
        $rows=array();$on=0;$off=0;
        foreach ($byGroup[$gkey] as $name=>$f){
            if($f["on"]){$on++;}else{$off++;}
            $rows[]=nicf_row($name,$f);
        }
        $totalOn+=$on;$totalOff+=$off;
        $badge="<span class='nicf-count'>$on {active2} &bull; $off {inactive}</span>";
        $col=$weight[0]<=$weight[1]?0:1;
        $cols[$col][]=nicx_section($g["icon"],$g["tone"],$g["title"],$g["sub"],$badge,$rows);
        $weight[$col]+=count($rows)+3;
    }

    $id="nicf".substr(md5($nic),0,8);
    $html=array();
    $html[]="<div class='nicx nicf' id='$id' style='margin-top:10px'>";
    $html[]="<div class='nicf-bar'>";
    $html[]="<label class='ak-search nicf-search'><i class='fa-solid fa-magnifying-glass'></i><input type='text' class='nicf-input' placeholder=\"{nicf_filter}\" OnInput=\"NicfFilter('$id',this.value)\"></label>";
    $html[]="<div class='nicf-totals'><span class='nicf-total nicf-total-on'><span class='ak-dot ak-ok'></span>$totalOn {active2}</span>";
    $html[]="<span class='nicf-total'><span class='ak-dot ak-off'></span>$totalOff {inactive}</span></div>";
    $html[]="</div>";
    $html[]="<div class='nicx-grid'>";
    $html[]="<div class='nicx-col'>".implode("",$cols[0])."</div>";
    $html[]="<div class='nicx-col'>".implode("",$cols[1])."</div>";
    $html[]="</div>";
    $html[]="<div class='nicf-empty' style='display:none'>{nicf_no_match}</div>";
    $html[]="<div class='nicx-foot'><div class='nicx-foot-state'><i class='fa-solid fa-circle-info' style='color:#2563eb'></i>";
    $html[]="<span><strong>{nicf_readonly}</strong> &bull; {nicf_readonly_sub} <code class='nicx-code'>ethtool -k ".htmlspecialchars($nic)."</code></span></div></div>";
    $html[]="</div>";

    $js="<script>function NicfFilter(id,v){var r=document.getElementById(id);if(!r){return;}v=(v||'').toLowerCase().trim();var any=false;"
        ."r.querySelectorAll('.nicx-sec').forEach(function(s){var n=0;s.querySelectorAll('.nicf-row').forEach(function(row){"
        ."var ok=v===''||row.textContent.toLowerCase().indexOf(v)>-1;row.style.display=ok?'':'none';if(ok){n++;}});"
        ."s.style.display=n>0?'':'none';if(n>0){any=true;}});var e=r.querySelector('.nicf-empty');if(e){e.style.display=any?'none':'';}}</script>";
    // The stylesheet and the script stay outside the parser.
    echo nicx_style().nicf_style().$js.$tpl->_ENGINE_parse_body(implode("",$html));
}
// nicf_parse reads `ethtool -k` output: name => on, fixed. Indented
// sub-features are kept as features of their own.
function nicf_parse($out):array{
    $res=array();
    foreach (explode("\n",$out) as $line){
        $line=trim($line);
        if(!preg_match('/^([A-Za-z0-9_-]+):\s+(on|off)(.*)$/',$line,$re)){continue;}
        $res[$re[1]]=array("on"=>$re[2]=="on","fixed"=>strpos($re[3],"fixed")!==false,"requested"=>preg_match('/requested on/',$re[3])==1);
    }
    return $res;
}
function nicf_groups():array{
    return array(
        "csum"=>array("icon"=>"fa-solid fa-calculator","tone"=>"blue","title"=>"{nicf_g_csum}","sub"=>"{nicf_g_csum_sub}"),
        "gro"=>array("icon"=>"fa-solid fa-compress","tone"=>"indigo","title"=>"{nicf_g_gro}","sub"=>"{nicf_g_gro_sub}"),
        "tso"=>array("icon"=>"fa-solid fa-scissors","tone"=>"blue","title"=>"{nicf_g_tso}","sub"=>"{nicf_g_tso_sub}"),
        "vlan"=>array("icon"=>"fa-solid fa-tag","tone"=>"violet","title"=>"{nicf_g_vlan}","sub"=>"{nicf_g_vlan_sub}"),
        "tnl"=>array("icon"=>"fa-solid fa-route","tone"=>"teal","title"=>"{nicf_g_tnl}","sub"=>"{nicf_g_tnl_sub}"),
        "other"=>array("icon"=>"fa-solid fa-microchip","tone"=>"amber","title"=>"{nicf_g_other}","sub"=>"{nicf_g_other_sub}"),
    );
}
// nicf_group_of: first matching family wins (VLAN before checksums, tunnels
// before GSO, so tx-gre-csum-segmentation lands with tunnels).
function nicf_group_of($name):string{
    if(strpos($name,"vlan")!==false){return "vlan";}
    if(preg_match('/^esp-|gre|ipxip|udp_tnl|tunnel|^receive-hashing$|^ntuple|fcoe-segmentation|^tls-|macsec|l2-fwd|hw-tc|^hsr-/',$name)){return "tnl";}
    if(preg_match('/checksum|^rx-fcs$/',$name)){return "csum";}
    if(preg_match('/^generic-|large-receive|gro|highdma|gso|udp-segmentation|sctp-segmentation/',$name)){return "gro";}
    if(preg_match('/scatter|tcp|nocache/',$name)){return "tso";}
    return "other";
}
// Known features carry a translated description token nicf_d_<name>.
function nicf_described():array{
    return array("rx-checksumming","tx-checksumming","tx-checksum-ipv4","tx-checksum-ipv6","tx-checksum-ip-generic","tx-checksum-sctp","tx-checksum-fcoe-crc","rx-fcs",
        "scatter-gather","tx-scatter-gather","tx-scatter-gather-fraglist","tcp-segmentation-offload","tx-tcp-segmentation","tx-tcp6-segmentation","tx-tcp-ecn-segmentation","tx-tcp-mangleid-segmentation","tx-nocache-copy",
        "generic-segmentation-offload","generic-receive-offload","large-receive-offload","rx-gro-hw","rx-gro-list","rx-udp-gro-forwarding","highdma","tx-gso-robust","tx-gso-partial","tx-gso-list","tx-udp-segmentation",
        "rx-vlan-offload","tx-vlan-offload","rx-vlan-filter","vlan-challenged",
        "receive-hashing","ntuple-filters","tx-gre-segmentation","tx-udp_tnl-segmentation","tx-ipxip4-segmentation","tx-ipxip6-segmentation","esp-hw-offload","tls-hw-tx-offload","hw-tc-offload",
        "loopback","rx-all");
}
function nicf_row($name,$f):string{
    $sub="";
    if(in_array($name,nicf_described())){
        $sub="<div class='nicx-sub'>{nicf_d_".str_replace("-","_",$name)."}</div>";
    }
    $lock=$f["fixed"]?"<i class='fa-solid fa-lock nicf-lock' title=\"{nicf_fixed}\"></i>":"";
    if($f["on"]){
        $state="<span class='ak-pill ak-ok'><i class='fa-solid fa-check'></i> {active2}</span>";
    }else{
        $state="<span class='ak-pill ak-off'>{inactive}</span>";
    }
    return "<div class='nicf-row'><div class='nicx-txt'><div class='nicf-name'>".htmlspecialchars($name)."</div>$sub</div>"
        ."<div class='nicf-right'>$lock$state</div></div>";
}
function nicf_style():string{
    $c=array();
    $c[]=".nicf-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #e2e8f0}";
    $c[]=".nicf-search{flex:0 1 420px;min-width:220px}";
    $c[]=".nicf-totals{display:flex;gap:8px;margin-left:auto}";
    $c[]=".nicf-total{display:inline-flex;align-items:center;gap:7px;padding:5px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;white-space:nowrap}";
    $c[]=".nicf-total-on{background:#ecfdf5;color:#047857;border-color:#a7f3d0}";
    $c[]=".nicf-count{flex:0 0 auto;font-size:11px;font-weight:600;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:3px 9px;white-space:nowrap}";
    $c[]=".nicf .nicx-sec-body{gap:0;padding:4px 16px 8px}";
    $c[]=".nicf-row{display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid #f1f5f9}";
    $c[]=".nicf-row:last-child{border-bottom:0}";
    $c[]=".nicf-name{font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px;font-weight:700;color:#0f172a;word-break:break-all}";
    $c[]=".nicf-right{display:flex;align-items:center;gap:8px;flex:0 0 auto}";
    $c[]=".nicf-lock{color:#cbd5e1;font-size:11px}";
    $c[]=".nicx-chip-indigo{background:#e6eeed;color:#005447;border-color:#ccddda}";
    $c[]=".nicx-chip-violet{background:#f2f6f6;color:#005447;border-color:#ccddda}";
    $c[]=".nicx-chip-amber{background:#fffbeb;color:#d97706;border-color:#fde68a}";
    $c[]=".nicf-empty{text-align:center;color:#64748b;padding:24px}";
    return "<style>".implode("",$c)."</style>";
}

function nic_security_save(){
    if(!netif_require_admin()){return false;}
	
	$niClass=new system_nic($_POST["nic_security"]);
	unset($_POST['nic_security']);
	reset($_POST);
	foreach ($_POST as $key=>$val){
		$val=url_decode_special_tool($val);
		$niClass->$key=$val;
	}
	$niClass->SaveNic();
}

function nic_save(){
	if(!netif_require_admin()){return;}
	$L2RESERVED=l2sw_reserved_ports();
	if(isset($_POST["save_nic"]) && isset($L2RESERVED[(string)$_POST["save_nic"]])){
		echo (new template_admin())->post_error("{l2sw_reserved_readonly} ".htmlspecialchars($L2RESERVED[(string)$_POST["save_nic"]]["switch"]));
		return;
	}
	$DNS_1=null;$DNS_2=null;
	$tpl=new templates();
	$ip=new networking();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}
	
	foreach ($_POST as $key=>$val){$_POST[$key]=url_decode_special_tool($val);}reset($_POST);
		
	
	
	if(isset($_POST["NICNAME"])){
		$NICNAME=trim(url_decode_special_tool($_POST["NICNAME"]));
	}
    if($_POST["netzone"]==null){
		echo "Network Zone must be defined\n";
		return;
	}

	

	$UseSPAN=intval($_POST["UseSPAN"]);
	$nic=trim($_POST["save_nic"]);
	$IPADDR=trim($_POST["IPADDR"]);
	$NETMASK=trim($_POST["NETMASK"]);
	$GATEWAY=trim($_POST["GATEWAY"]);
	$BROADCAST=trim($_POST["BROADCAST"]);
	if($GATEWAY=="no"){$GATEWAY="0.0.0.0";}
	if($GATEWAY==null){$GATEWAY="0.0.0.0";}
	if(isset($_POST["DNS_1"])){$DNS_1=$_POST["DNS_1"];}
	if(isset($_POST["DNS_2"])){$DNS_2=$_POST["DNS_2"];}

	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

	if($q->TABLE_EXISTS("nic_virtuals")) {
        // Pentest 2026-11-04 : IPADDR etait concatene dans la requete.
        $sql = "SELECT ipaddr FROM nic_virtuals WHERE ipaddr=?";
        $results = $q->QUERY_SQL_PARAMS($sql,array($IPADDR));
        $ligne = $results[0];
        if ($ligne["ipaddr"] <> null) {
            echo $tpl->javascript_parse_text("{already_used}: $IPADDR (Virtual)");
            return;
        }
    }
    if($q->TABLE_EXISTS("nics_vlan")) {
        // Pentest 2026-11-04 : IPADDR etait concatene dans la requete.
        $sql = "SELECT ipaddr FROM nics_vlan WHERE ipaddr=?";
        $results = $q->QUERY_SQL_PARAMS($sql,array($IPADDR));
        $ligne = $results[0];
        if ($ligne["ipaddr"] <> null) {
            echo $tpl->javascript_parse_text("{already_used}: $IPADDR (VLAN)");
            return;
        }
    }

	if(intval($_POST["enabled"])==1){
		if($UseSPAN<>1){
			if($_POST["dhcp"]<>1){
				if(!$ip->checkIP($IPADDR)){echo "CheckIP: Address: $IPADDR = False;\n";return;}
				if(!$ip->checkIP($NETMASK)){echo "CheckIP: NetMask $NETMASK = False;\n";return;}
				if($GATEWAY<>"0.0.0.0"){
					if(!$ip->checkIP($GATEWAY)){echo "CheckIP: Gateway $GATEWAY = False;\n";return;}
				}
			}
			if($DNS_1<>null){
				if(!$ip->checkIP($DNS_1)){echo "CheckIP: DNS 1 $DNS_1 = False;\nOr set null value to remove this message";return;}
			}
				
			if($DNS_2<>null){
				if(!$ip->checkIP($DNS_2)){echo "CheckIP: DNS 2 $DNS_2 = False;\nOr set null value to remove this message";return;}
			}
				
		}
	}

	$htmltools=new htmltools_inc();
	$_POST["netzone"]=$htmltools->StripSpecialsChars($_POST["netzone"]);

	if($_POST["InternetCheck"]==1){$InternetCheck=0;}else{$InternetCheck=1;}


	$nics=new system_nic($nic,true);
	$text[]="$NICNAME $nic $IPADDR";
	if($NICNAME<>null){ $nics->NICNAME=$NICNAME; }
	$nics->eth=$nic;
	$nics->IPADDR=$IPADDR;
	$nics->NETMASK=$NETMASK;
	$nics->GATEWAY=$GATEWAY;
	$nics->BROADCAST=$BROADCAST;
	$nics->UseSPAN=$UseSPAN;
	if($DNS_1<>null){$nics->DNS1=$DNS_1; }
	if($DNS_2<>null){ $nics->DNS2=$DNS_2; }
	$nics->dhcp=$_POST["dhcp"];
	$nics->metric=$_POST["metric"];
	$nics->enabled=intval($_POST["enabled"]);
    $nics->txqueuelen=intval($_POST["txqueuelen"]);
	$nics->netzone=$_POST["netzone"];
	$nics->NoInternetCheck=$InternetCheck;
	// The form has no checksum_offloading field: reading it reset the option
	// to 0 at every save. Only overwrite it when it is really posted.
	if(isset($_POST["checksum_offloading"])){
		$nics->checksum_offloading=intval($_POST["checksum_offloading"]);
	}
	$nics->mtu=$_POST["mtu"];

	if(isset($_POST["defaultroute"])){
		$nics->defaultroute=$_POST["defaultroute"];
	}

	if(isset($_POST["Bridged"])){
		$nics->Bridged=$_POST["Bridged"];
		$text[]="Bridged, ";
	}
	if(isset($_POST["BridgedTo"])){
		$nics->BridgedTo=$_POST["BridgedTo"];
		$text[]="{$_POST["BridgedTo"]}";
	}

	if(isset($_POST["defaultroute"])){
		$nics->defaultroute=$_POST["defaultroute"];
	}


	if($_POST["noreboot"]=="noreboot"){
		$nics->NoReboot=true;
		if($nics->SaveNic()){
			return;
		}
	}

	$nics->SaveNic();
	
	// Requete PREPAREE: `enabled` venait de $_POST sans aucun filtre. Le
	// second argument "artica_backup" etait un vestige MySQL, ignore par
	// lib_sqlite.
	$q->QUERY_SQL_PARAMS("UPDATE `nics` SET `enabled`=? WHERE `Interface`=?",
		array(intval($_POST["enabled"]),$nic));
	if(!$q->ok){echo $q->mysql_error;return;}


}
function section_nic_only(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
    $btn=array();
    $TINY_ARRAY["TITLE"]="{network_interfaces}: {details}";
    $TINY_ARRAY["ICO"]="fa-duotone fa-ethernet";
    $TINY_ARRAY["EXPL"]="{network_interfaces_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html=$tpl->page_header("{network_interfaces}",
        "fa-duotone fa-ethernet","{network_interfaces_explain}",
    "$page?table=yes","nics","progress-firehol-restart",false,"table-loader-interfaces"
    );


        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;




}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    //

    $html=$tpl->page_header("{network_interfaces}","fa fa-exchange","{network_interfaces_explain}","$page?tabs=yes","interfaces","progress-firehol-restart",false,"table-loader-interfaces");




if(isset($_GET["main-page"])){
	$tpl=new template_admin(null,$html);
	echo $tpl->build_firewall();

}


	echo $tpl->_ENGINE_parse_body($html);

}
function _nic_enabled_not_set($nicz,$ACTUAL_IPADDR):bool{

    if($nicz->dhcp==1){return false;}
    if(is_null($nicz->IPADDR)){$nicz->IPADDR="";}
    if($nicz->UseSPAN==1){
        VERBOSE("_nic_enabled_not_set [$nicz->IPADDR] --> USE SPAN = TRUE",__LINE__);
        return false;
    }

    if(trim($nicz->IPADDR)==null){
        VERBOSE("_nic_enabled_not_set [$nicz->IPADDR] --> IS NULL",__LINE__);
        return true;
    }
    if($ACTUAL_IPADDR<>null) {
        if ($nicz->IPADDR == $ACTUAL_IPADDR) {
            VERBOSE("_nic_enabled_not_set [$nicz->IPADDR] ==== $ACTUAL_IPADDR",__LINE__);
            return false;
        }
    }
    $IP=new IP();
    if($IP->isValid($nicz->IPADDR)){return false;}

    return true;

}
function _nic_enabled_not_configured($nicz,$ACTUAL_IPADDR):bool{
    VERBOSE("_nic_enabled_not_configured...",__LINE__);
    if ($nicz->enabled == 0) {
        VERBOSE("This NIC is disabled",__LINE__);
        return false;
    }

    if ($nicz->dhcp == 1) {
        return false;
    }
    if ($nicz->UseSPAN == 1) {
        return false;
    }
    VERBOSE("Configured IP = {$nicz->IPADDR} receive IP: $ACTUAL_IPADDR",__LINE__);
    if ($nicz->IPADDR == $ACTUAL_IPADDR) {
        return false;
    }

    return true;

}
function table_start(){
    $page           = CurrentPageName();
    echo "<div id='network-interfaces-table' style='margin-top: 15px'></div><script>LoadAjax('network-interfaces-table','$page?table=yes');</script>";

}

function table(){
	$tpl                = new template_admin();
	$page               = CurrentPageName();
    $ALREADY_MASQ       = array();
    $q                  = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results            = $q->QUERY_SQL("SELECT nic FROM firehol_masquerade WHERE enabled=1");
    foreach ($results as $index=>$ligne){$ALREADY_MASQ[$ligne["nic"]]=true;}
    $q                  = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $PERISTENT=array();
    $res=$q->QUERY_SQL("SELECT * FROM persistent");
    if(!$q->ok){
        VERBOSE("<H1>$q->mysql_error</H1>");
    }
    foreach ($res as $index=>$ligne){
        $PERISTENT[$ligne["MacAddr"]]=$ligne["Iface"];
    }

    if(!isset($_GET["search"])){$_GET["search"]="";}
	$qico               = "<i class=\"fad fa-question\"></i>&nbsp;";
	$muted              = "style='color:#b9b9b9 !important'";
	$DisableNetworking  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
    $EnableVLANs        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVLANs"));
    $OpenVswitchEnable  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVswitchEnable"));
	$users              = new usersMenus();
	$ComputerMacAddress = $tpl->javascript_parse_text("{ComputerMacAddress}");
    $LshwCnetwork       = base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LshwCnetwork"));
	$MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
    $LSHW               = json_decode($LshwCnetwork);
    if(!$LSHW){
        $LSHW=array();
    }
    $EnableMunin        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    $bondEnabled        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableInterfaceBond"));
    // Cards held by an L2 switch: shown read-only, linked to their switch.
    $L2RESERVED         = l2sw_reserved_ports();

    // The daemon returns the list inline; it used to be serialized into
    // PROGRESS_DIR/MASQUERADE.eths by framework/network.php just to be read
    // back here, which also meant a stale file whenever iptables-save failed.
    $MASQUERADES=array();
    $masqJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/masquerade"));
    if(is_object($masqJson) && !empty($masqJson->Status) && isset($masqJson->interfaces)){
        foreach ($masqJson->interfaces as $masqIface){$MASQUERADES["$masqIface"]=true;}
    }


	foreach ($LSHW as $index=>$lshw_class) {
	    if(!property_exists($lshw_class,"logicalname")){continue;}
        $logicalname=$lshw_class->logicalname;
	    if(property_exists($lshw_class,"product")){
	        $HARDWARES[$logicalname]["product"]=$lshw_class->product;
        }
        if(property_exists($lshw_class,"vendor")){
            $HARDWARES[$logicalname]["vendor"]=$lshw_class->vendor;
        }

        if(property_exists($lshw_class,"capabilities")){
            if(property_exists($lshw_class->capabilities,"100bt-fd")){
                $HARDWARES[$logicalname]["MAXCP"]="100Mbit/s";
            }
            if(property_exists($lshw_class->capabilities,"1000bt-fd")){
                $HARDWARES[$logicalname]["MAXCP"]="1Gbit/s";
            }
            if(property_exists($lshw_class->capabilities,"10000bt-fd")){
                $HARDWARES[$logicalname]["MAXCP"]="10Gbit/s";
            }
        }

        if(property_exists($lshw_class,"configuration")){
            if(property_exists($lshw_class->configuration,"driver")){
                $HARDWARES[$logicalname]["driver"]=$lshw_class->configuration->driver;
            }

        }
    }

	if(!$MUNIN_CLIENT_INSTALLED){$EnableMunin=0;}

    $jsrestart=$tpl->framework_buildjs("/system/network/reconfigure-restart",
        "reconfigure-newtork.progress",
        "exec.virtuals-ip.php.html","progress-firehol-restart","LoadAjax('network-interfaces-table','$page?table=yes');"
    ); // apply_network_configuration

	$apply=$tpl->_ENGINE_parse_body("{apply_network_configuration}");

	if(!isset($_GET["eth"])){$_GET["eth"]=null;}


    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	
	if($users->AsSystemAdministrator) {
        if ($DisableNetworking == 0) {
            $btn[] = "<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart;\"><i class='fa fa-repeat'></i> $apply </label>";
        }
    }
	
	$sql="SELECT COUNT(Interface) as tcount FROM nics WHERE enabled=0";
	$ligne=$q->mysqli_fetch_array($sql);
	
	if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error</div>";}
	$CountOfDisabled=$ligne["tcount"];

	if($CountOfDisabled>0){
		$btn[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?disabled-interfaces-js=yes');\"><i class='fa fa-circle-o'></i> $CountOfDisabled {disabled_interfaces} </label>";
	}

    $btn[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?sysctl2-js=yes');\"><i class='fa fa-fighter-jet'></i> {kernelnetworkoptimization} </label>";


    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?options-js=yes');\"><i class='fas fa-cogs'></i> {options} </label>";
	

	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MULTIPATH_TCP_ENABLED"))==1){
        $btn[]="<label class=\"btn btn btn-default\" OnClick=\"Loadjs('$page?MPTCP-js=yes');\"><i class='far fa-exchange'></i> {APP_MULTIPATH_TCP} </label>";
		
	}

	if($EnableMunin==1){
        $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.network.interfaces.netstats.php');\">
            <i class='fas fa-chart-bar'></i> {statistics} </label>";
	}
    $btn[]="<label class=\"btn btn btn-blue\" OnClick=\"s_PopUp('https://wiki.articatech.com/en/network','1024','800');\">
            <i class='fa-solid fa-headset'></i> {online_help} </label>";
    if (!$bondEnabled){
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?add-macvlan-js=yes');\"><i class='fa fa-project-diagram'></i> {new_virtual_interface} </label>";
}
    if ($bondEnabled){
        $btn[]="<label><div class=\"dropdown\">
  <button class=\"btn btn btn-primary dropdown-toggle\" style='border-bottom-left-radius: 0;border-top-left-radius: 0' type=\"button\" id=\"dropdownMenuButton\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
  {new_interface} <span class=\"caret\"></span>
  </button>
  <ul class=\"dropdown-menu\">
	<li><a class=\"dropdown-item\" href=\"#\" OnClick=\"Loadjs('$page?add-macvlan-js=yes');\">{new_virtual_interface}</a></li>
	<li><a class=\"dropdown-item\" href=\"#\" OnClick=\"Loadjs('$page?add-bond-js=yes');\">{add_bond_interface}</a></li>
  </ul>
</div></label>";}

    $btn[]="</div>";

	$_SESSION["DHCPL_SEARCH"]=trim(strtolower($_GET["search"]));

	// Masque, MAC et les cinq colonnes d'options rejoignent le bloc d'identite et la
	// cellule d'options: 11 colonnes -> 4.
	$html[]=netif_table_open(array(
		"<th>{interface}</th>",
		"<th>{tcp_address}</th>",
		"<th>{gateway}</th>",
		"<th>{options}</th>"),"nif-rows-details");
	$rows=array();

	$jsAfter="LoadAjax('table-loader-interfaces','$page?table=yes&eth={$_GET["eth"]}');";
    $EnableIwConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwConfig"));
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$proc_net_dev=explode("\n",@file_get_contents("/proc/net/dev"));

	foreach ($proc_net_dev as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(.+?):\s+[0-9]+#",$line,$re)){continue;}
        if($re[1]=="lo"){continue;}
        VERBOSE("/proc/net/dev: Interface {$re[1]}",__LINE__);
        $datas[]=trim($re[1]);
    }

    $IwConfigInterfaceS=array();

	if($EnableIwConfig==1){
	    VERBOSE("/usr/sbin/iw dev",__LINE__);
	    exec("/usr/sbin/iw dev 2>&1",$IwConfigResults);
	    foreach ($IwConfigResults as $IwConfigLine){
            $IwConfigLine=trim($IwConfigLine);
            if($IwConfigLine==null){continue;}
            if(!preg_match("#^Interface\s+(.+)#",$IwConfigLine,$re)){
                VERBOSE($IwConfigLine." NO MATCH",__LINE__);
                continue;
            }
            $IwConfigInterfaceS[$re[1]]=True;
        }
    }


	$tcp=new networking();
	$IPBANS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaIpListBanned")));
    if(!$IPBANS){
        $IPBANS=array();
    }
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$sql="SELECT Interface FROM nics ORDER BY metric";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error</div>";}
	foreach ($results as $index=>$ligne){
        $datas[]=$ligne["Interface"];
        $MYSQL_NIC[$ligne["Interface"]]=$ligne["Interface"];}
	
	$FIREHOLE=false;
	$FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
	if($FireHolEnable==1){$FIREHOLE=true;}
	$TRCLASS=null;

    $bondMembers=array();
    $bondNic=array();
    $bondStatusMap=array();
    if($bondEnabled==1){
        $sqlBond="SELECT * FROM `bond_interfaces`";
        $resultsBond=$q->QUERY_SQL($sqlBond);
        if($q->ok) {
            foreach ($resultsBond as $index => $ligne) {
                $explodeMember = explode(",",$ligne["members"]);
                $bondNic["{$ligne["nic"]}"]="{$ligne["nic"]}";
                // $bondMembers maps MEMBER -> owning bond. It used to store the
                // bond name under the single scalar key "bond" (plus an "md5"
                // key), so with two bonds every member was labelled as
                // belonging to the last row read, and the freshness check below
                // compared against the wrong interface entirely.
                foreach ($explodeMember as $members) {
                    $members=trim($members);
                    if($members==""){continue;}
                    $bondMembers["$members"]="{$ligne["nic"]}";

                }

            }
        }
        // The daemon owns the "is this bond in production" verdict.
        //
        // The console used to compute md5(serialize($sqlite_row)) and compare it
        // against the fingerprint the daemon had stored in Valkey — which the
        // daemon computed from a phpserialize of its own Go struct. The two can
        // never be equal (Go scans every column as a string, PDO returns native
        // ints for the INTEGER columns), so the comparison always failed and
        // every bond permanently displayed "you need to apply network
        // configuration for this interface to make it in production".
        // GET /system/network/bond/status also reports what the KERNEL actually
        // has, so a bond whose master vanished on reboot is no longer announced
        // as healthy just because Valkey still holds a matching fingerprint.
        $bondStatusJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/bond/status"));
        if(is_object($bondStatusJson) && isset($bondStatusJson->bonds)){
            foreach ($bondStatusJson->bonds as $bondName=>$bondEntry){
                $bondStatusMap["$bondName"]=$bondEntry;
            }
        }
    }
    foreach ($datas as $val){$H[$val]=$val;}



    ksort($H);
    $datas=array();
    foreach ($H as $val=>$none){$datas[]=$val;}

	foreach ($datas as $val){
        $NETADDR_TEXT="";
        $metric_text="";
        $NIC_DISABLED=false;
	    VERBOSE("LOOP: Interface $val",__LINE__);
		if(trim($val)==null){continue;}
		if(preg_match('#master#',$val)){continue;}
        if(preg_match("#^(ovs-|switch)#",$val)){continue;}
		if(preg_match("#^(erspan|dummy|teql|ip6tnl|tunl|gre|ifb|sit|gretap|vlan)[0-9]+#",$val)){continue;}
		if(preg_match("#^virt#", $val)){$FORCE_GATEWAY=true;}
		if(preg_match("#wccp[0-9]+#", $val)){$WCCP_INTERFACE=true;}
		if($EnableIwConfig==0){
		    if(preg_match("#(wlan|wlp)[0-9]+#", $val)){
                VERBOSE("LOOP: Interface $val EnableIwConfig == 0, Continue",__LINE__);
		        continue;}
		}
        $wire                   = null;
		$text_class             = null;
		$metric_warning         = null;
		$FIREHOLE_ROW           = $tpl->icon_nothing();
        $macvlan_icon           = null;
		$access_internet        = "-";
		$error                  = null;
		$bond_badge             = null;
		$why                    = null;
		$nicz                   = new system_nic($val);
		$NETADDR                = array();
		$MUST_CHANGE            = false;
		$NOT_CONFIGURED=false;
        $text_class_ico="text-navy";
		if($nicz->Bridged==1){continue;}
		if(isset($MYSQL_NIC[$val])){
			if($nicz->enabled==0){
                VERBOSE("LOOP: Interface $val ! ! Disabled !!! Abort",__LINE__);
                $NIC_DISABLED=True;
                $text_class="text-muted";
                $text_class_ico=$text_class;

			}
		}
        $md=md5($val);
        if($nicz->macvlan==1){
            $macvlan_icon="&nbsp;<span class='label label-info'>Macvlan ".$nicz->physical."</span>";
        }
        if($nicz->ipvlan==1){
            $macvlan_icon="&nbsp;<span class='label label-info'>IPvlan ".$nicz->physical."</span>";
        }

		if($FIREHOLE){
			$FIREHOLE_ROW=$tpl->icon_check($nicz->isFW,
                "Loadjs('$page?isFW=$val')",
                null,"AsFirewallManager",null,true);
		}

        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        // Pentest 2026-11-04 : le nom d'interface etait concatene dans la requete.
        $r3=$q->QUERY_SQL_PARAMS("SELECT firewall_masquerade from nics WHERE Interface=?",array($val));
        $ligne3=isset($r3[0])?$r3[0]:array();
        if(!isset($ligne3["firewall_masquerade"])){ $ligne3["firewall_masquerade"]=0; }
        $firewall_masquerade=intval($ligne3["firewall_masquerade"]);

        // Use the complete/reliable handler Masquerade_switch() (?masq-interface=): it reads the
        // flag via direct SQL (no ETHCONFIG cache trap) and also syncs pnic_bridges + firehol_masquerade
        // and calls /firewall/routers/buildnohup. Its echo is guarded so the missing MASQ<eth> span in
        // this table context is a no-op (icon_check toggles the icon optimistically on click).
        $MASQUERADE=$tpl->icon_check($firewall_masquerade, "Loadjs('$page?masq-interface=$val')", null,"AsFirewallManager",null,true);

        if($firewall_masquerade==1){
            if(!isset($MASQUERADES[$val])){
                $error="$error&nbsp;<span class='label label-warning'>Masquerade {not_configured}</span>";
            }
        }
        $PROXY_ARP=$tpl->icon_check($nicz->proxyarp,
            "Loadjs('$page?proxyarp=$val')",
            null,"AsFirewallManager",null,true);

		if(isset($ALREADY_MASQ[$val])){
            $MASQUERADE="<span class='fas fa-check'></span>";
        }



		unset($MYSQL_NIC[$val]);
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/nicstatus/$val"));
        $nicinfos=$data->Info;
        $IfaceState="";
        $IfaceStateIco="";
        $tbl=explode(";",$nicinfos);
		if($GLOBALS["VERBOSE"]){ foreach ($tbl as $indexDebug=>$LIneDebug){ VERBOSE("LOOP: Interface $val NicInfo[$indexDebug] = $LIneDebug",__LINE__); } }

        if(isset($IPBANS[$tbl[0]])) {
            if ($IPBANS[$tbl[0]]) {
                continue;
            }
        }
		$dhcp_text=null;
        if(isset($tbl[6])) {
            $IfaceState = $tbl[6];
        }

        if($IfaceState=="down" && $nicz->enabled==1){
            $IfaceStateIco="&nbsp;<span class='label label-warning'>DOWN</span>";
        }

		$tcp->ifconfig(trim($val));

		if(isset($IwConfigInterfaceS[$val])){
            $wire=" (wireless)";
        }

		$gateway    = $nicz->GATEWAY;
		VERBOSE("GATEWAY: $val -- $gateway", __LINE__);
		if($nicz->defaultroute==1){$defaultroute_text="<i class='fas fa-check-square-o'></i>";}
		if($nicz->FireQOS==1){$qos_text="{enabled}";$qos_color="black";}
		if($nicz->enabled==0){$color="#8E8E8E";$qos_color="#8E8E8E";}
        if($nicz->IPADDR=="127.0.0.1"){$nicz->IPADDR=null;}
		if($nicz->IPADDR=="0.0.0.0"){$nicz->IPADDR=null;}
        if($nicz->NETMASK=="0.0.0.0"){$nicz->NETMASK=null;}
        if($nicz->GATEWAY=="no"){$nicz->GATEWAY=null;}
        $NetStatus = new NetStatus($val);

        $ACTUAL_IPADDR=$NetStatus->IPADDR;
        $ACTUAL_NETMASK=$NetStatus->NETMASK;
        $ACTUAL_GATEWAY=$NetStatus->GATEWAY;
        $ComputerMacAddress=$NetStatus->MacAddr;
        $ComputerMacAddressError="";
        if(preg_match("#^veth#",$val)){
            unset($PERISTENT[$ComputerMacAddress]);
        }

        // A NIC enslaved to a bond reports the BOND's MAC address, not its own:
        // that is how bonding works (all slaves share the master's address
        // unless fail_over_mac is set). Looking that MAC up in the persistent-net
        // table therefore resolves to the bond, and the row displayed a red
        // "expected: bond0" error on a perfectly healthy member. Bond masters
        // were already excluded; their members must be too.
        $isBondRelated = preg_match("#^bond[0-9]+#", $val) || array_key_exists($val, $bondMembers);
        if($nicz->macvlan==0 && $nicz->ipvlan==0 && !$isBondRelated){
        if(isset($PERISTENT[$ComputerMacAddress])) {
            $PERISTENT_INTERFACE = trim($PERISTENT[$ComputerMacAddress]);
            if (strlen($PERISTENT_INTERFACE) > 1) {
                if ($PERISTENT_INTERFACE <> $val) {
                    $tt = new system_nic($PERISTENT_INTERFACE);
                    $ComputerMacAddressError = "<br><span class='label label-danger'>{expected}: $PERISTENT_INTERFACE $tt->NICNAME</span>";
                }
            }
        }
        }
        $ComputerMacAddress=$tpl->td_href($ComputerMacAddress,"","Loadjs('fw.network.persistent-net.php')");
		if($gateway=="no"){$gateway="0.0.0.0";}
		if( _nic_enabled_not_set($nicz,$ACTUAL_IPADDR)){
		    VERBOSE("_nic_enabled_not_set --> TRUE",__LINE__);
            $nicz->enabled=0;
            $text_class="text-muted";
            $text_class_ico=$text_class;
        }
        if(_nic_enabled_not_configured($nicz,$ACTUAL_IPADDR)) {
            $why = $why . " $nicz->IPADDR / {$ACTUAL_IPADDR}";
            $NOT_CONFIGURED = true;
            $text_class_ico="text-warning";
        }
        if($nicz->NotSaved){
            $NOT_CONFIGURED = true;
        }

        if($bondEnabled==1){
            if(preg_match("#^bond[0-9]+#", $val)){
                if (array_key_exists($val,  $bondNic)) {
                    // Absent from the status payload = the daemon does not know
                    // this bond (yet): treat it as "needs apply" rather than
                    // silently claiming it is in production.
                    $inSync=false;
                    $bondWhy=" to make it in production";
                    if(isset($bondStatusMap["$val"])){
                        $entry=$bondStatusMap["$val"];
                        $inSync=(isset($entry->in_sync) && $entry->in_sync);
                        if(!$inSync){
                            if(isset($entry->exists) && !$entry->exists){
                                $bondWhy=" (the bond does not exist in the kernel)";
                            }elseif(isset($entry->missing_members) && count($entry->missing_members)>0){
                                $bondWhy=" (members not enslaved: ".@implode(", ",$entry->missing_members).")";
                            }elseif(isset($entry->configured_members) && count($entry->configured_members)==0){
                                $bondWhy=" (no member selected)";
                            }
                        }
                    }
                    if(!$inSync){
                        $why = $why . $bondWhy;
                        $NOT_CONFIGURED = false;
                        $MUST_CHANGE=true;
                        $text_class_ico="text-warning";
                    }
                }
            }
        }
        VERBOSE("<H2>$val</H2> OpenVswitchEnable=$OpenVswitchEnable, virtualbridge=$nicz->virtualbridge",__LINE__);
        $VirtualBridge="";



        if($nicz->enabled == 1) {

            $dhcp_text = status2_dhcp_standard($nicz);

            if ($nicz->dhcp == 0) {
                if ($ACTUAL_IPADDR == null) {
                    $text_class_ico="text-warning";
                    $error = "$error<br><strong class=small>{waiting_network_reload}</strong>";
                }
            }
            if ($nicz->dhcp == 1) {
                $ip = new IP();
                if ($ip->isValid($ACTUAL_IPADDR)) {
                    $MUST_CHANGE = false;
                }
            }

            if($ACTUAL_IPADDR<>null){$NETADDR[]="<strong>$ACTUAL_IPADDR</strong>";}


            $NETADDR=table_virtuals_list($val,$NETADDR);
            if($EnableVLANs==1) {
                $NETADDR=table_vlan_list($val,$NETADDR);

            }
            if($bondEnabled==1){
                $NETADDR=table_bond_list($val,$NETADDR);
            }


        }
		if($nicz->enabled==0){$MUST_CHANGE=false;}
		if($nicz->enabled==1){
			
			if($NOT_CONFIGURED){
			    $access_internet="<i class='fas fa-exclamation-triangle'></i>";
			    $error=$error."<br><small class='text-warning'>{this_interface_is_not_configured} $why</small>";}

			if($MUST_CHANGE){
				$access_internet="<i class='fas fa-exclamation-triangle'></i>";
				if(!$NOT_CONFIGURED){
				    $error=$error."<br><small class='text-warning'>{need_to_apply_network_settings_interface} $why</small>";
				}
			}
		}else{
            $PROXY_ARP=$tpl->icon_nothing();
        }

        $hardware_textes=array();
		$hardware_text=null;
        if(isset($HARDWARES[$val])){
            if(isset($HARDWARES[$val]["MAXCP"])){
                $hardware_textes[]="<strong>{$HARDWARES[$val]["MAXCP"]}</strong>";
            }
            if(isset($HARDWARES[$val]["product"])){
                $hardware_textes[]="{$HARDWARES[$val]["product"]}";
            }
            if(isset($HARDWARES[$val]["vendor"])){
                $hardware_textes[]="{$HARDWARES[$val]["vendor"]}";
            }
            if(isset($HARDWARES[$val]["driver"])){
                $hardware_textes[]="({$HARDWARES[$val]["driver"]})";
            }

        }else{
            VERBOSE("HARDWARES[$val] not set",__LINE__);
        }

        VERBOSE("HARDWARES[$val] == ".count($hardware_textes),__LINE__);
        if( count($hardware_textes)>0 ){
            $hardware_text="<br><small>".@implode(" ",$hardware_textes)."</small>";
        }


		$url="Loadjs('$page?nic-config-js=$val&md=$md');";
		if($DisableNetworking==1){$url="blur()";}
		if($val=="tailscale0"){
		    $url="Loadjs('fw.network.interfaces.php?nic-config-deny=tailscale0');";
		}
		$href="<a href=\"javascript:blur()\" OnClick=\"$url\" style='font-weight:bold' class='$text_class'>";

        if(!is_array($NETADDR)){$NETADDR=array();}

        if($nicz->UseSPAN==0) {
            if (count($NETADDR) == 0) {
                $NETADDR_TEXT = $tpl->icon_nothing();
                if ($nicz->IPADDR <> null) {
                    $NETADDR_TEXT = "<span $muted>$qico$nicz->IPADDR</span>";
                    $ComputerMacAddress = "<span $muted>$qico$ComputerMacAddress</span>";
                } else {
                    $ComputerMacAddress = "<span $muted>$qico$ComputerMacAddress</span>";
                }

            } else {
                $NETADDR_TEXT = @implode("<br>", $NETADDR);
            }
        }

		if(trim($ACTUAL_NETMASK)==null){
		    $ACTUAL_NETMASK=$tpl->icon_nothing();
		    if($nicz->NETMASK<>null){
                $ACTUAL_NETMASK = "<span $muted>{$qico}$nicz->NETMASK</span>";
            }
		}

        $gateway_strong=null;
        $gateway_strong2=null;
        $gateway_text=null;
        $NoMetric=false;
        if($gateway=="0.0.0.0"){$NoMetric=true;$gateway=$ACTUAL_GATEWAY; $nicz->metric=null;}
        if($nicz->GATEWAY==""){$NoMetric=true;}
        if($nicz->enabled == 0) {$NoMetric=true;}
        if($nicz->UseSPAN==1) {$NoMetric=true;}

		if(!$NoMetric){
            if (!isset($METRICz[$nicz->metric])) {
                if($nicz->UseSPAN==0) {$METRICz[$nicz->metric] = true;}
            } else {
                $metric_warning = "<br><span class='label label-warning'>{metric_already_used}</span>";
            }
        }

		if($nicz->defaultroute==1){
		    $gateway_strong="<strong>";
		    $gateway_strong2="</strong>";
		    $gateway_text="&nbsp;<i>({default_gateway})</i>";
		}
		if($nicz->metric>0){
            $metric_text="<br><small>{metric} $nicz->metric</small>";
        }

        if(trim($ACTUAL_GATEWAY)==null){
            $gateway_strong=null;
            $gateway_strong2=null;
            $gateway=$tpl->icon_nothing();
            if($nicz->GATEWAY<>null){
                $gateway = "<span $muted>{$qico}$nicz->GATEWAY$gateway_text$metric_text</span>";
                $gateway_text=null;
                $metric_text=null;
            }else{
                $metric_text=null;
            }
        }

		$MULTIPATH_GATEWAYS=MULTIPATH_GATEWAYS($val);
		if(count($MULTIPATH_GATEWAYS)>0){
			VERBOSE("LOOP: Interface $val MULTIPATH_GATEWAYS($val) >0 == ".count($MULTIPATH_GATEWAYS),__LINE__);
			$gateway=@implode(", ", $MULTIPATH_GATEWAYS);
			VERBOSE("LOOP: Interface $val MULTIPATH_GATEWAYS($val) >0 == '$gateway'",__LINE__);
		}
        $icon="<i class=\"$text_class_ico fas fa-ethernet\"></i>";

        if($NIC_DISABLED){
            $IfaceStateIco="";
            $text_class="text-muted";
            $text_class_ico=$text_class;
            $metric_warning=null;
            $error="&nbsp;<span class='label'>{disabled}</span>";
            $icon="<i class='fal fa-ethernet'></i>";
        }
        if($wire<>null){
            $icon="<i class=\"fas fa-wifi $text_class\"></i>";
        }

        if($val=="tailscale0"){
            $metric_warning=null;
            $metric_text=null;$gateway_text=null;$error=null;$hardware_text=null;
        }
        if(preg_match("#veth[0-9]+h$#",$val)){
            $metric_warning=null;
            $href=null;
            $metric_text=null;$gateway_text=null;$error=null;$hardware_text=null;
            $ComputerMacAddress=$NetStatus->MacAddr;
            $FIREHOLE_ROW       =$tpl->icon_nothing();
            $PROXY_ARP          = $tpl->icon_nothing();
            $MASQUERADE         = $tpl->icon_nothing();
            $access_internet    = $tpl->icon_nothing();
        }

        if($ComputerMacAddress==null){$ComputerMacAddress=$tpl->icon_nothing();}


        if($nicz->enabled==1){
            if($nicz->mirror==1){
                $wire= "&nbsp;&nbsp;<span class='label label-inverse'>{mirror} $nicz->mirrorgateway</span>";
            }
        }

        if($nicz->UseSPAN==1){
            $FIREHOLE_ROW       =$tpl->icon_nothing();
            $PROXY_ARP          = $tpl->icon_nothing();
            $MASQUERADE         = $tpl->icon_nothing();
            $access_internet    = $tpl->icon_nothing();
            $error              = null;
            $gateway            = $tpl->icon_nothing();
            $NETADDR_TEXT       = "<strong>0.0.0.0</strong>";
            $wire               = "&nbsp;&nbsp;"."<span class='label label-inverse'>{mirror}</span>";
            $text_class         = null;
            $icon               ="<i class=\"text-info fas fa-ethernet\"></i>";
        }
        if($bondEnabled==1) {
            if (array_key_exists($val, $bondMembers)) {
                $text_class = "text-info";
                $metric_warning = null;
                // La pastille part dans $bond_badge, rendu a DROITE DU NOM par
                // netif_ident(). $error reste vide comme avant: il porte aussi
                // des avertissements multi-lignes qui, eux, doivent rester sous
                // l'identite, et l'affectation d'origine servait de plus a
                // effacer un eventuel message precedent pour un membre de bond.
                $error = null;
                $bond_badge = "&nbsp;<span class='label'>{bonded_to} {$bondMembers[$val]}</span>";
                $icon = "<i class='fa fa-project-diagram'></i>";
                $href = null;
                $FIREHOLE_ROW = $tpl->icon_nothing();
                $PROXY_ARP = $tpl->icon_nothing();
                $MASQUERADE = $tpl->icon_nothing();
                $access_internet = $tpl->icon_nothing();
                $gateway = $tpl->icon_nothing();
            }
        }
        if($OpenVswitchEnable==1){
            if($nicz->virtualbridge==1){
                $icoArr=ico_arrow_right;
                $NOT_CONFIGURED = false;
                $MUST_CHANGE=false;
                $VirtualBridge="&nbsp;<span class='label label-blue'>{virtual_switch} {bonded_to} switch$val &nbsp;<i class='$icoArr'></i>&nbsp;{$val}h0</span>";
                $error = "";
                $text_class="";
                $icon="<i class='text-info fas fa-bezier-curve'></i>";
                $NETADDR_TEXT=$tpl->icon_nothing();
                $ACTUAL_NETMASK=$tpl->icon_nothing();
                $gateway_text="";
                $gateway_strong="";
                $gateway=$tpl->icon_nothing();
                $metric_text="";
                $gateway_strong2="";
                $access_internet=$tpl->icon_nothing();
            }
            if(preg_match("#^switch#",$val)){
                $metric_warning="";
                $ComputerMacAddressError="";
                $href="";
            }
            if(preg_match("#^(.+?)h([0-9]+)$#",$val,$m)){
                $error = "";
                $Index = intval($m[2]);
                if($m[2]==0) {
                    $metric_text = "";
                    $metric_warning = "";
                    $ComputerMacAddressError = "";
                    $url = "Loadjs('$page?nic-config-js=$m[1]&md=$md');";
                    // Loadjs('fw.network.interfaces.php?nic-config-js=$m[1]&md=$md');";
                    if ($DisableNetworking == 1) {
                        $url = "blur()";
                    }
                    $href = "<a href=\"javascript:blur()\" OnClick=\"$url\" style='font-weight:bold' class='$text_class'>";

                    $text_class = "font-bold";
                    $access_internet = $tpl->icon_nothing();
                }
            }
        }


		// Port of an L2 switch: the switch owns the card (VLANs, STP, guards),
		// the row is read-only and its link opens the switch.
		if(isset($L2RESERVED[$val])){
			$l2sw=$L2RESERVED[$val];
			$url="LoadAjax('MainContent','fw.network.l2switch.php?switch-id=".intval($l2sw["switch_id"])."');";
			$bond_badge=strval($bond_badge)."&nbsp;<span class='label label-info'>{l2sw_reserved_by} ".htmlspecialchars($l2sw["switch"])."</span>";
			$error=null;
			$metric_warning=null;
			$FIREHOLE_ROW=$tpl->icon_nothing();
			$PROXY_ARP=$tpl->icon_nothing();
			$MASQUERADE=$tpl->icon_nothing();
			$access_internet=$tpl->icon_nothing();
			$gateway=$tpl->icon_nothing();
			$gateway_text=null;
			$metric_text=null;
			$gateway_strong=null;
			$gateway_strong2=null;
		}
		$meta=array();
		if(strlen(trim(strip_tags(strval($ACTUAL_NETMASK))))>0){$meta[]=$ACTUAL_NETMASK;}
		if(strlen(trim(strip_tags(strval($ComputerMacAddress))))>0){$meta[]=$ComputerMacAddress.$ComputerMacAddressError;}

		// $IfaceStateIco (le bandeau DOWN) passe en 6e argument: il est empile sous
		// la pastille et centre, au lieu d'etre concatene a la suite du bloc.
		$ident=netif_ident($icon,"$nicz->netzone: $nicz->NICNAME ($val)",$meta,$url,($nicz->enabled==0),$IfaceStateIco,$bond_badge)
			."$VirtualBridge$wire$metric_warning$error$hardware_text$macvlan_icon";

		$opts=netif_optgroup(array(
			"{internet_access}"=>$access_internet,
			"{APP_PROXY_ARP}"=>$PROXY_ARP,
			"MASQ"=>$MASQUERADE,
			"{firewall}"=>$FIREHOLE_ROW));

		$h ="<tr>";
		$h.="<td>$ident</td>";
		$h.="<td class='awc-mono'>$NETADDR_TEXT $dhcp_text</td>";
		$h.="<td class='awc-mono'>$gateway_strong$gateway$gateway_strong2$metric_text<small>$gateway_text</small></td>";
		$h.="<td>$opts</td>";
		$h.="</tr>";
		$rows[]=$h;


	}

	if(count($rows)==0){
		$html[]=netif_table_empty(4);
	}else{
		$html[]=@implode("\n",$rows);
	}
	$totalNics=count($rows);
	$html[]=netif_table_close($totalNics,$totalNics,"{interfaces}","nif-pager-details");

    $TINY_ARRAY["TITLE"]="{network_interfaces}: {details}";
    $TINY_ARRAY["ICO"]="fa-duotone fa-ethernet";
    $TINY_ARRAY["EXPL"]="{network_interfaces_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	// Le bloc <script> etait DANS $html, donc analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body lit chaque corps de fonction JavaScript comme un
	// {token} -- icon_check() en emet TROIS par case a cocher, ce qui fait
	// autant d'interrogations memcached, et un corps qui correspondrait a une
	// vraie cle serait purement et simplement remplace DANS le JavaScript.
	// Et ICON_SCRIPTS_COMPILE() plutot que la pile d'instance: icon_check()
	// alimente en parallele $GLOBALS["TPL_ICON_SCRIPTS"], que seule la version
	// COMPILE draine -- la pile d'instance est vide des qu'une ligne est batie
	// par une fonction qui fait son propre new template_admin().
	$script="<script>\n"
		."$.address.state('/');\n"
		."$.address.value('/nics');\n"
		."NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny\n</script>";

	echo awc_table_style().netif_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"nif-rows-details","nif-pager-details");

}

function table_bond_list($eth,$NETADDR){
    // Pentest 2026-11-04 : le nom d'interface etait concatene dans la requete.
    $sql = "SELECT * FROM bond_interfaces WHERE nic=?";
    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $resultsVirtuals = $q->QUERY_SQL_PARAMS($sql,array($eth));
    if (!$q->ok) {
        echo "<div class='alert alert-danger'>" . $q->mysql_error_html(true) . "</div>";
    }
    foreach ($resultsVirtuals as $index => $ligne) {
        $ifaces = explode(",",$ligne["members"]);
        foreach ($ifaces as $members){

            $NETADDR[] = "<i style='font-size:11px'>Members: {$members}</i>";
        }

    }
    return $NETADDR;
}
function table_virtuals_list($eth,$NETADDR)
{
    // Pentest 2026-11-04 : le nom d'interface etait concatene dans la requete.
    $sql = "SELECT ipaddr,ID FROM nics_virtuals WHERE nic=?";
    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $resultsVirtuals = $q->QUERY_SQL_PARAMS($sql,array($eth));
    if (!$q->ok) {
        echo "<div class='alert alert-danger'>" . $q->mysql_error_html(true) . "</div>";
    }
    foreach ($resultsVirtuals as $index => $ligne) {
        $NETADDR[] = "<i style='font-size:11px'>virtual: {$ligne["ipaddr"]}</i>";
    }
    return $NETADDR;
}
function table_vlan_list($eth,$NETADDR)
{
    $qvlan = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    // Pentest 2026-11-04 : le nom d'interface etait concatene dans la requete.
    $resultsVLANs = $qvlan->QUERY_SQL_PARAMS("SELECT * FROM nics_vlan WHERE nic=? ORDER BY ID",array($eth));
    if (!$qvlan->ok) {
        echo "<div class='alert alert-danger'>" . $qvlan->mysql_error_html(true) . "</div>";
    }
    VERBOSE("SELECT * FROM nics_vlan WHERE nic='$eth' ORDER BY ID = ".count($resultsVLANs)." lines...");

    foreach ($resultsVLANs as $index => $ligne) {
        $NETADDR[] = "<i style='font-size:11px'>vlan: {$ligne["ipaddr"]}</i>";
    }

    return $NETADDR;}


function nic_vlan_info($json,$ID){
    $array=$json->Interfaces;
    foreach ($array as $main){
        if ($main->ID==$ID){
            return $main;
        }
    }
    $Info=array();
    $Info["isError"]=true;
    $Info["error"]="Interface not found";
    return json_encode($Info);

}
function nic_vlan_start():bool{
    //nic-vlan-start=$ID&eth=$eth
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["nic-vlan-start"]);
    $eth=$_GET["eth"];
    $sock=new sockets();
    $data=$sock->REST_API("/system/network/vlan/start/$ID");
    $json=json_decode($data);
    if(!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('nics-vlans','$page?nic-vlans-list=$eth');";
    return admin_tracks("Configure VLAN ID $ID on $eth");
}
function nic_vlan_stop():bool{
    //nic-vlan-start=$ID&eth=$eth
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["nic-vlan-stop"]);
    $eth=$_GET["eth"];
    $sock=new sockets();
    $data=$sock->REST_API("/system/network/vlan/stop/$ID");
    $json=json_decode($data);
    if(!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('nics-vlans','$page?nic-vlans-list=$eth');";
    return admin_tracks("Bring down VLAN ID $ID on $eth");
}
function nic_vlans_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $text_class=null;
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    $sock=new sockets();
    $data=$sock->REST_API("/system/network/vlan/list");
    $json=json_decode($data);
    if(!$json->Status){
        echo $tpl->div_error("{error}||$json->Error");
    }

    if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){$q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL default 0");}

    // $eth vient de l'URL et finit dans un litteral SQL.
    $eth=netif_sql_safe($tpl,$_GET["nic-vlans-list"]);

    $sql="SELECT * FROM nics_vlan WHERE nic='$eth' ORDER BY ID"; //
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);return false;}
    $net=new networking();

    $rows=array();
    foreach ($results as $index=>$ligne){
        $md=md5(serialize($ligne));
        $ID=$ligne["ID"];
        $enabled=intval($ligne["enabled"]);
        $JsonInfo=nic_vlan_info($json,$ID);

        $vlanid=$ligne["vlanid"];
        $eth_text="vlan$ID";
        if($ligne["cdir"]==null){
            $ligne["cdir"]=$net->array_TCP[$ligne["nic"]];
        }

        $Action=$tpl->icon_stop("Loadjs('$page?nic-vlan-stop=$ID&eth=$eth')");
        $state="up";
        $stateLabel="{running}";
        $Error="";

        $url="Loadjs('$page?nic-vlan-js=$ID&eth=$eth');";
        $delete=$tpl->icon_delete("Loadjs('$page?nic-vlan-delete-js=$ID&md={$md}')","AsSystemAdministrator");

        if($JsonInfo->isError){
            $state="down"; $stateLabel="{inactive2}";
            $Error="<span class='nif-err'>".htmlspecialchars(strval($JsonInfo->error))."</span>";
            $Action=$tpl->icon_run("Loadjs('$page?nic-vlan-start=$ID&eth=$eth')");
        }
        if(!$JsonInfo->isUp){
            $state="warn"; $stateLabel="{down}";
            $Action=$tpl->icon_run("Loadjs('$page?nic-vlan-start=$ID&eth=$eth')");
        }

        $iparray=array();
        foreach ($JsonInfo->currentIPs as $ipaddrs){
            $iparray[]=htmlspecialchars(strval($ipaddrs));
        }
        if(count($iparray)==0){
            $state="warn"; $stateLabel="No IP";
        }
        if($enabled==0){
            $state="off"; $stateLabel="{disabled}";
            $Action="";
        }

        $RxBytes=FormatBytes($JsonInfo->stats->RxBytes/1024,true);
        $TxBytes=FormatBytes($JsonInfo->stats->TxBytes/1024,true);

        // Une bascule par option plutot qu'une colonne par option: 10 colonnes -> 6.
        $toggles=array(
            array("label"=>"MASQ","on"=>intval($ligne["masquerade"]),
                  "js"=>"Loadjs('$page?nic-vlan-masquerade=$ID')"),
            array("label"=>"{enabled}","on"=>$enabled,
                  "js"=>"Loadjs('$page?nic-vlan-enable=$ID')"));

        $meta=array();
        if(strlen(trim(strval($JsonInfo->macAddress)))>0){$meta[]=htmlspecialchars(strval($JsonInfo->macAddress));}
        if(count($iparray)>0){$meta[]=@implode(", ",$iparray);}

        $h ="<tr>";
        $h.="<td>".netif_ident(ico_nic,"$eth_text.$vlanid",$meta,$url,($enabled==0)).$Error."</td>";
        $h.="<td class='awc-mono' nowrap>".htmlspecialchars(strval($ligne["gateway"]))."</td>";
        $h.="<td class='nif-tr'><b>TX</b> $TxBytes<br><b>RX</b> $RxBytes</td>";
        $h.="<td>".netif_toggles($toggles)."</td>";
        $h.="<td class='center'>".netif_state_badge($state,$stateLabel)."</td>";
        $h.="<td>".netif_acts(array($Action,$delete))."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $html=array();
    $html[]=netif_table_open(array(
        "<th>VLAN</th>",
        "<th>{gateway}</th>",
        "<th>TX/RX</th>",
        "<th>{options}</th>",
        "<th class='center'>{status}</th>",
        "<th style='text-align:right'>{actions}</th>"),"nif-rows-vlan");
    if(count($rows)==0){
        $html[]=netif_table_empty(6);
    }else{
        $html[]=@implode("\n",$rows);
    }
    $total=count($rows);
    $html[]=netif_table_close($total,$total,"VLAN","nif-pager-vlan");
	// Le bloc <script> etait DANS $html, donc analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body lit chaque corps de fonction JavaScript comme un
	// {token} -- icon_check() en emet TROIS par case a cocher, ce qui fait
	// autant d'interrogations memcached, et un corps qui correspondrait a une
	// vraie cle serait purement et simplement remplace DANS le JavaScript.
	// Et ICON_SCRIPTS_COMPILE() plutot que la pile d'instance: icon_check()
	// alimente en parallele $GLOBALS["TPL_ICON_SCRIPTS"], que seule la version
	// COMPILE draine -- la pile d'instance est vide des qu'une ligne est batie
	// par une fonction qui fait son propre new template_admin().
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style().netif_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(25,"nif-rows-vlan","nif-pager-vlan");
    return true;
}

// IP aliases tab: one card per row of nics_virtuals. The kernel state comes
// from the address LABELS read by the daemon (GET /system/network/aliases):
// an alias "eno1:3" is live when the kernel carries an address labelled so.
function nic_virtuals_search(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=strval($_GET["nic-virtuals-list"]);
	if(!mpx_valid_eth($eth)){echo $tpl->div_error("{invalid_interface}");return;}
	$users=new usersMenus();
	$admin=$users->AsSystemAdministrator;
	$ethu=urlencode($eth);
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$ethq=netif_sql_safe($tpl,$eth);
	$results=$q->QUERY_SQL("SELECT * FROM nics_virtuals WHERE nic='$ethq' ORDER BY ID");
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	if(!is_array($results)){$results=array();}

	$aliases=array();
	$live_json=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/aliases")),true);
	$liveKnown=is_array($live_json) && isset($live_json["aliases"]) && is_array($live_json["aliases"]);
	if($liveKnown){$aliases=$live_json["aliases"];}

	$id="alx".substr(md5($eth),0,8);
	$cards=array();$nOn=0;$nOff=0;$maxID=0;
	foreach ($results as $ligne){
		$ID=intval($ligne["ID"]);
		if($ID>$maxID){$maxID=$ID;}
		$label="$eth:$ID";
		$ipv6=intval($ligne["ipv6"])==1;
		// An IPv6 address carries no label: its absence from the map proves nothing.
		$live=$ipv6 || array_key_exists($label,$aliases);
		if($live){$nOn++;}else{$nOff++;}
		$ip=trim(strval($ligne["ipaddr"]));
		$mask=trim(strval($ligne["netmask"]));
		$prefix="";$bcast="";
		if(filter_var($mask,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
			$prefix=strval(substr_count(decbin(ip2long($mask)),"1"));
			if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
				$bcast=long2ip((ip2long($ip)&ip2long($mask))|(~ip2long($mask)&0xFFFFFFFF));
			}
		}
		$gw=trim(strval($ligne["gateway"]));
		$hasGw=intval($ligne["ForceGateway"])==1 && filter_var($gw,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) && $gw<>"0.0.0.0";
		$failover=intval($ligne["failover"])==1;
		$cid="$id-$ID";
		$h=array();
		$h[]="<div class='mpx-card".($live?" mpx-card-main":"")."' id='$cid' data-name='$ID' data-ip='".htmlspecialchars($ip)."' data-live='".($live?1:0)."'>";
		$icon=$live?"fa-solid fa-server":"fa-solid fa-triangle-exclamation";
		$tone=$live?"teal":"off";
		if($ipv6){$icon="fa-solid fa-globe";}
		$tags="<span class='ak-chip'>ID #$ID</span>";
		if($failover){$tags.="<span class='ak-pill ak-info'>Failover</span>";}
		if($ipv6){$tags.="<span class='ak-pill ak-info'>IPv6</span>";}
		$state=$live?"<span class='ak-pill ak-ok alx-state'><span class='ak-dot ak-ok'></span>{active2}</span>"
			:"<span class='ak-pill ak-off alx-state'><span class='ak-dot ak-off'></span>{inactive2}</span>";
		$h[]="<div class='mpx-head'><div class='mpx-ico nicx-chip-$tone'><i class='$icon'></i></div>";
		$h[]="<div class='nicx-txt'><div class='alx-name'>".htmlspecialchars($label)." $tags</div><div class='nicx-sub'>".($live?"{alx_in_kernel}":"{alx_not_in_kernel}")."</div></div>";
		$h[]="<div class='mpx-tags'>$state</div></div>";

		$ipHtml="<span class='alx-ip'>".htmlspecialchars($ip)."</span>".($prefix<>""?"<span class='alx-prefix".($live?"":" alx-prefix-off")."'>/$prefix</span>":"");
		$gwHtml=$hasGw?"<span class='alx-mono'>".htmlspecialchars($gw)."</span> <span class='alx-hint'>({metric} ".intval($ligne["metric"]).")</span>"
			:"<em class='alx-muted'>{alx_no_gateway}</em>";
		$h[]="<div class='alx-fields'>";
		$h[]="<div><span class='alx-k'>".($ipv6?"{alx_ipv6}":"{alx_ipv4}")."</span>$ipHtml</div>";
		$h[]="<div><span class='alx-k'>{netmask}</span><span class='alx-mono'>".htmlspecialchars($mask)."</span></div>";
		$h[]="<div><span class='alx-k'>{gateway}</span>$gwHtml</div>";
		$h[]="<div><span class='alx-k'>{alx_broadcast}</span><span class='alx-mono'>".($bcast<>""?$bcast:"&mdash;")."</span></div>";
		$h[]="</div>";

		$h[]="<div class='mpx-foot'>";
		if(!$live && $admin){
			$h[]="<button type='button' class='alx-activate' OnClick=\"Loadjs('$page?nic-virtuals-apply=$ethu')\"><i class='fa-solid fa-check'></i> {alx_activate}</button>";
		}elseif($hasGw){
			$h[]="<button type='button' class='ak-btn' OnClick=\"AlxProbe('$cid','".htmlspecialchars($gw)."')\"><i class='fa-solid fa-bolt'></i> {alx_ping_gateway}</button><span class='alx-probe' id='$cid-probe'></span>";
		}
		$h[]="<span class='mpx-acts'>";
		if($admin){
			$h[]="<button type='button' class='ak-act' title=\"{edit}\" OnClick=\"Loadjs('$page?nic-virtual-js=$ID&eth=$ethu&function=NicVirtualsReload')\"><i class='fa-solid fa-pen-to-square'></i></button>";
			$h[]="<button type='button' class='ak-act ak-danger' title=\"{delete}\" OnClick=\"Loadjs('$page?nic-virtual-delete-js=$ID&eth=$ethu')\"><i class='fa-solid fa-trash'></i></button>";
		}
		$h[]="</span></div></div>";
		$cards[]=implode("",$h);
	}
	$total=count($cards);

	$html=array();
	$html[]="<div class='nicx mpx alx' id='$id' style='margin-top:10px'>";
	$html[]="<div class='mpx-bar'>";
	if($admin){
		$html[]="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?nic-virtual-js=0&eth=$ethu&function=NicVirtualsReload')\"><i class='fa-solid fa-plus'></i> {alx_new}</button>";
	}
	$html[]="<span class='mpx-algo'><span class='ak-dot ak-ok'></span><strong>$total</strong> {alx_configured} <span class='mpx-sep'>|</span> <strong class='mpx-ok'>$nOn</strong> {alx_active} <span class='mpx-sep'>|</span> <strong>$nOff</strong> {alx_inactive}</span>";
	if($total>0){
		$html[]="<div class='mpx-tools'><label class='ak-search nicf-search'><i class='fa-solid fa-magnifying-glass'></i><input type='text' class='nicf-input' placeholder=\"{alx_search}\" OnInput=\"AlxFilter('$id',this.value)\"></label>";
		$html[]="<select class='mpx-sort' OnChange=\"AlxSort('$id',this.value)\"><option value='name'>{alx_sort_name}</option><option value='ip'>{alx_sort_ip}</option><option value='live'>{alx_sort_status}</option></select></div>";
	}
	$html[]="</div>";
	if(!$liveKnown){
		$html[]="<div class='nicx-note'><i class='fa-solid fa-circle-info'></i> {alx_live_unknown}</div>";
	}
	$html[]="<div class='mpx-grid'>".implode("",$cards);
	if($admin){
		$next=htmlspecialchars("$eth:".($maxID+1));
		$html[]="<div class='alx-create' OnClick=\"Loadjs('$page?nic-virtual-js=0&eth=$ethu&function=NicVirtualsReload')\"><div class='alx-plus'><i class='fa-solid fa-plus'></i></div>";
		$html[]="<strong>{alx_create} $next</strong><div class='nicx-sub'>{alx_create_explain}</div><span class='alx-cfg'>{alx_configure} <i class='fa-solid fa-chevron-right'></i></span></div>";
	}
	$html[]="</div>";
	$html[]="<div class='mpx-note'><i class='fa-solid fa-circle-info'></i><div><strong>{alx_info_title} :</strong> {alx_info} <code class='nicx-code'>".htmlspecialchars($eth)."</code>. {alx_info2}</div></div>";
	$html[]="</div>";

	$t=new template_admin();
	$js=array();
	$js[]="<script>NoSpinner();";
	$js[]="function NicVirtualsReload(){LoadAjaxSilent('nics-virtuals','$page?nic-virtuals-list=$ethu');}";
	$js[]="function AlxFilter(id,v){var r=document.getElementById(id);if(!r){return;}v=(v||'').toLowerCase().trim();";
	$js[]="r.querySelectorAll('.mpx-card').forEach(function(c){c.style.display=(v===''||c.textContent.toLowerCase().indexOf(v)>-1)?'':'none';});}";
	$js[]="function AlxSort(id,k){var r=document.getElementById(id);if(!r){return;}var g=r.querySelector('.mpx-grid');var a=Array.prototype.slice.call(g.querySelectorAll('.mpx-card'));";
	$js[]="var ipn=function(s){return s.split('.').reduce(function(x,o){return x*256+(parseInt(o,10)||0);},0);};var add=g.querySelector('.alx-create');";
	$js[]="a.sort(function(x,y){if(k==='ip'){return ipn(x.getAttribute('data-ip'))-ipn(y.getAttribute('data-ip'));}";
	$js[]="if(k==='live'){return parseInt(y.getAttribute('data-live'),10)-parseInt(x.getAttribute('data-live'),10);}";
	$js[]="return parseInt(x.getAttribute('data-name'),10)-parseInt(y.getAttribute('data-name'),10);});a.forEach(function(c){g.appendChild(c);});if(add){g.appendChild(add);}}";
	$js[]="function AlxProbe(cid,ip){var e=document.getElementById(cid+'-probe');if(!e){return;}e.className='alx-probe';e.innerHTML=".json_encode($t->javascript_parse_text("{mpx_checking}")).";";
	$js[]="$.ajax({url:'$page?multipath-probe='+encodeURIComponent(ip),dataType:'json',cache:false}).done(function(r){";
	$js[]="if(!r||!r.ok){e.innerHTML=".json_encode($t->javascript_parse_text("{mpx_unknown}")).";return;}";
	$js[]="if(r.reachable){e.className='alx-probe mpx-ok';e.innerHTML=(r.received>0?r.rtt.toFixed(1)+' ms, ':'')+r.loss.toFixed(0)+'% '+".json_encode($t->javascript_parse_text("{mpx_loss}")).";}";
	$js[]="else{e.className='alx-probe mpx-warn';e.innerHTML=".json_encode($t->javascript_parse_text("{mpx_unreachable}")).";}";
	$js[]="}).fail(function(){e.innerHTML=".json_encode($t->javascript_parse_text("{mpx_unknown}")).";});}";
	$js[]="</script>";

	echo nicx_style().nicf_style().mpx_style().alx_style().$tpl->_ENGINE_parse_body(implode("",$html)).implode("",$js);
}
// ?nic-virtuals-apply=<eth>: rebuild the aliases of the interface (the
// daemon removes the labelled addresses, then adds those of the table).
function nic_virtuals_apply():bool{
	if(!netif_require_admin(true)){return false;}
	$eth=strval($_GET["nic-virtuals-apply"]);
	if(!mpx_valid_eth($eth)){return false;}
	$page=CurrentPageName();
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/virtual/interfaces/build/".urlencode($eth));
	header("content-type: application/x-javascript");
	echo "LoadAjaxSilent('nics-virtuals','$page?nic-virtuals-list=".urlencode($eth)."');\n";
	return admin_tracks("Rebuild the IP aliases of $eth");
}
function alx_style():string{
	$c=array();
	$c[]=".alx-name{font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:16px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;flex-wrap:wrap}";
	$c[]=".alx-state{text-transform:uppercase}";
	$c[]=".alx-fields{display:grid;grid-template-columns:1fr 1fr;gap:14px 18px;border-top:1px solid #f1f5f9;padding-top:14px}";
	$c[]=".alx-k{display:block;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;margin-bottom:4px}";
	$c[]=".alx-ip{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:15px;font-weight:700;color:#0f172a}";
	$c[]=".alx-prefix{margin-left:8px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;font-weight:600;background:#ccfbf1;color:#0f766e;border-radius:6px;padding:2px 7px}";
	$c[]=".alx-prefix-off{background:#f1f5f9;color:#64748b}";
	$c[]=".alx-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#334155}";
	$c[]=".alx-hint{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;color:#94a3b8}";
	$c[]=".alx-muted{color:#94a3b8}";
	$c[]=".alx-activate{border:1px solid #a7f3d0;background:#ecfdf5;color:#047857;border-radius:8px;padding:6px 14px;font-size:12.5px;font-weight:600;cursor:pointer}";
	$c[]=".alx-activate:hover{background:#d1fae5}";
	$c[]=".alx-probe{margin-left:10px;font-size:12px;color:#64748b}";
	$c[]=".alx-create{border:2px dashed #cbd5e1;border-radius:14px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:8px;padding:28px;cursor:pointer;color:#334155;min-height:220px}";
	$c[]=".alx-create:hover{border-color:#5eead4;background:#f0fdfa}";
	$c[]=".alx-plus{width:48px;height:48px;border-radius:12px;background:#f0fdfa;color:#0d9488;display:flex;align-items:center;justify-content:center;font-size:20px}";
	$c[]=".alx-cfg{margin-top:6px;border:1px solid #e2e8f0;border-radius:8px;padding:6px 16px;color:#0f766e;font-weight:600;font-size:12.5px;background:#fff}";
	return "<style>".implode("",$c)."</style>";
}

// Multipath tab: one card per gateway sharing the default route of the
// interface. The daemon (GET /system/network/interface/multipath/{if}) says
// what the network build makes of the rows and what the kernel runs; RTT and
// loss come from GET /system/network/gateway/probe/{ip}, called per card
// after the page is shown.
function multip_path_section():bool{
	$page=CurrentPageName();
	$eth=$_GET["multipath-section"];
	if(!mpx_valid_eth($eth)){return false;}
	echo "<div id='multipath-div'></div><script>LoadAjax('multipath-div','$page?multipath-table=".urlencode($eth)."');</script>";
	return true;
}
function mpx_valid_eth($eth):bool{
	return preg_match('/^[A-Za-z0-9_.:@-]{1,32}$/',strval($eth))==1;
}
// mpx_state reads the daemon; an older daemon (no route) gets the same answer
// computed from the table, without the kernel side.
function mpx_state($eth):array{
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/multipath/".urlencode($eth)),true);
	if(is_array($json) && !empty($json["Status"]) && isset($json["gateways"])){
		$json["legacy"]=false;
		return $json;
	}
	$nic=new system_nic($eth);
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$ethq=netif_sql_safe(new template_admin(),$eth);
	$results=$q->QUERY_SQL("SELECT ID,gateway,weight FROM `multipath` WHERE `nic`='$ethq' ORDER BY ID");
	$gws=array();
	if(is_array($results) && count($results)>0){
		$ifgw=trim(strval($nic->GATEWAY));
		if(filter_var($ifgw,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) && $ifgw<>"0.0.0.0"){
			$gws[]=array("id"=>0,"gateway"=>$ifgw,"source"=>"interface","weight"=>1,"effective_weight"=>1,"role"=>"shared","in_kernel"=>false);
		}
		foreach ($results as $ligne){
			$w=intval($ligne["weight"]);
			$g=array("id"=>intval($ligne["ID"]),"gateway"=>trim($ligne["gateway"]),"source"=>"multipath","weight"=>$w,"effective_weight"=>max(1,min(256,$w)),"role"=>"shared","in_kernel"=>false);
			if(!filter_var($g["gateway"],FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){$g["role"]="ignored";$g["effective_weight"]=0;}
			$gws[]=$g;
		}
		$total=0;
		foreach ($gws as $g){if($g["role"]=="shared"){$total+=$g["effective_weight"];}}
		foreach ($gws as $i=>$g){$gws[$i]["share_pct"]=($g["role"]=="shared" && $total>0)?$g["effective_weight"]*100/$total:0;}
	}
	return array("Status"=>true,"legacy"=>true,"interface"=>$eth,"interface_gateway"=>strval($nic->GATEWAY),"mode"=>"ecmp","hash"=>"","use_neigh"=>false,"gateways"=>$gws,"applied"=>true);
}
function multip_path_table(){
	$eth=$_GET["multipath-table"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!mpx_valid_eth($eth)){echo $tpl->div_error("{invalid_interface}");return;}
	$users=new usersMenus();
	$admin=$users->AsSystemAdministrator;
	$st=mpx_state($eth);
	$ethu=urlencode($eth);
	$id="mpx".substr(md5($eth),0,8);
	$gws=$st["gateways"];
	$ecmp=$st["mode"]=="ecmp";

	$html=array();
	$html[]="<div class='nicx mpx' id='$id' style='margin-top:10px'>";
	// Toolbar
	$html[]="<div class='mpx-bar'>";
	if($admin){
		$html[]="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?multip-path-js=0&eth=$ethu')\"><i class='fa-solid fa-plus'></i> {new_gateway}</button>";
	}
	if($ecmp){
		$hash=$st["hash"]<>""?" &bull; {mpx_hash} ".htmlspecialchars($st["hash"]):"";
		$html[]="<span class='mpx-algo'><span class='ak-dot ak-ok'></span>{mpx_algorithm} : <strong>Weighted ECMP</strong>$hash</span>";
	}else{
		$html[]="<span class='mpx-algo'><span class='ak-dot ak-warn'></span>{mpx_algorithm} : <strong>{mpx_algo_priority}</strong></span>";
	}
	if(count($gws)>0){
		$html[]="<div class='mpx-tools'><label class='ak-search nicf-search'><i class='fa-solid fa-magnifying-glass'></i><input type='text' class='nicf-input' placeholder=\"{mpx_filter}\" OnInput=\"MpxFilter('$id',this.value)\"></label>";
		$html[]="<select class='mpx-sort' OnChange=\"MpxSort('$id',this.value)\"><option value='weight'>{mpx_sort_weight}</option><option value='ip'>{mpx_sort_ip}</option><option value='rtt'>{mpx_sort_rtt}</option></select></div>";
	}
	$html[]="</div>";

	if(count($gws)==0){
		$gw=htmlspecialchars(strval($st["interface_gateway"]));
		$html[]="<div class='ak-empty mpx-empty'><i class='fa-solid fa-route'></i><div><strong>{mpx_none}</strong><div class='nicx-sub'>{mpx_none_explain}".($gw<>"" && $gw<>"0.0.0.0"?" <code class='nicx-code'>$gw</code>":"")."</div></div></div>";
		$html[]="</div>";
		echo nicx_style().nicf_style().mpx_style().$tpl->_ENGINE_parse_body(implode("",$html));
		return;
	}

	$html[]="<div class='mpx-grid'>";
	$probes=array();
	foreach ($gws as $i=>$g){
		$cid="$id-$i";
		$ip=htmlspecialchars($g["gateway"]);
		$role=$g["role"];
		$ifgw=$g["source"]=="interface";
		$tone="blue";$icon="fa-solid fa-globe";$badge="<span class='ak-pill ak-info'>Multipath</span>";
		if($ifgw){$tone="teal";$icon="fa-solid fa-server";$badge="<span class='ak-pill ak-ok'>{default}</span>";}
		if($role=="standby"){$tone="amber";$icon="fa-solid fa-triangle-exclamation";$badge="<span class='ak-pill ak-warn'>Standby</span>";}
		if($role=="active"){$badge="<span class='ak-pill ak-ok'>{active2}</span>";}
		if($role=="unused"){$tone="off";$badge="<span class='ak-pill ak-off'>{mpx_unused}</span>";}
		if($role=="ignored"){$tone="red";$icon="fa-solid fa-circle-xmark";$badge="<span class='ak-pill ak-err'>{mpx_ignored}</span>";}
		$sub=$ifgw?"{mpx_interface_gw}":"{mpx_multipath_gw}";

		$h=array();
		$h[]="<div class='mpx-card".($ifgw?" mpx-card-main":"")."' id='$cid' data-ip='$ip' data-weight='".intval($g["effective_weight"])."' data-rtt='999999'>";
		$h[]="<div class='mpx-head'><div class='mpx-ico nicx-chip-$tone'><i class='$icon'></i><span class='mpx-led' id='$cid-led'></span></div>";
		$h[]="<div class='nicx-txt'><div class='mpx-ip'>$ip</div><div class='nicx-sub'>$sub</div></div>";
		$h[]="<div class='mpx-tags'>$badge<span class='ak-state' id='$cid-st'>".(($role=="ignored")?"":"<span class='ak-dot ak-off'></span>{mpx_checking}")."</span></div></div>";

		$h[]="<div class='mpx-box'>";
		$h[]="<div class='mpx-line'><span>{weight}</span><span class='mpx-w'>".intval($g["weight"])."</span></div>";
		$pct=floatval($g["share_pct"] ?? 0);
		if($role=="shared"){
			$load=round($pct)."% {mpx_of_connections}";$bar=$pct;$barc=$ifgw?"#14b8a6":"#2563eb";
		}elseif($role=="active"){
			$load="{mpx_all_traffic}";$bar=100;$barc="#14b8a6";
		}elseif($role=="standby"){
			$load="{mpx_backup_only}";$bar=0;$barc="#f59e0b";
		}else{
			$load=htmlspecialchars(strval($g["reason"] ?? ""));
			if($role=="ignored" && $load==""){$load="{mpx_bad_address}";}
			if($role=="unused"){$load="{mpx_unused_explain}";}
			$bar=0;$barc="#94a3b8";
		}
		$h[]="<div class='mpx-load'><span>{mpx_load}</span><strong>$load</strong></div>";
		$h[]="<div class='mpx-track'><div style='width:".round($bar,1)."%;background:$barc'></div></div>";
		$kernel=$st["legacy"]?"<span class='mpx-muted'>&mdash;</span>":($g["in_kernel"]?"<span class='mpx-ok'>{mpx_in_kernel}</span>":"<span class='mpx-warn'>{mpx_not_in_kernel}</span>");
		if($role=="ignored" || $role=="unused"){$kernel="<span class='mpx-muted'>&mdash;</span>";}
		$h[]="<div class='mpx-metrics'><div><span>RTT</span><b id='$cid-rtt'>&hellip;</b></div><div><span>{mpx_loss}</span><b id='$cid-loss'>&hellip;</b></div><div><span>{mpx_kernel}</span><b>$kernel</b></div></div>";
		$h[]="</div>";

		$h[]="<div class='mpx-foot'>";
		if($role<>"ignored"){
			$h[]="<button type='button' class='ak-btn' OnClick=\"MpxProbe('$cid','$ip')\"><i class='fa-solid fa-bolt'></i> Ping</button>";
			$probes[]="MpxProbe('$cid','$ip');";
		}
		$h[]="<span class='mpx-acts'>";
		if($admin && !$ifgw){
			$h[]="<button type='button' class='ak-act' title=\"{edit}\" OnClick=\"Loadjs('$page?multip-path-js=".intval($g["id"])."&eth=$ethu')\"><i class='fa-solid fa-pen-to-square'></i></button>";
			$h[]="<button type='button' class='ak-act ak-danger' title=\"{delete}\" OnClick=\"Loadjs('$page?multip-path-delete=".intval($g["id"])."&eth=$ethu')\"><i class='fa-solid fa-trash'></i></button>";
		}
		$h[]="</span></div></div>";
		$html[]=implode("",$h);
	}
	$html[]="</div>";

	// How the kernel uses the cards
	if($ecmp){
		$rule="{mpx_rule_ecmp} ".($st["hash"]=="L4"?"{mpx_hash_l4}":"{mpx_hash_l3}")." ";
		if(!$st["legacy"]){$rule.=$st["use_neigh"]?"{mpx_neigh_on}":"{mpx_neigh_off}";}
	}else{
		$rule="{mpx_rule_priority}";
	}
	$html[]="<div class='mpx-note'><i class='fa-solid fa-circle-info'></i><div><strong>{mpx_rule_title} :</strong> $rule</div></div>";

	// Count + applied state
	$n=count($gws);
	$state="";$apply="";
	if(!$st["legacy"]){
		if($st["applied"]){
			$state="<span class='ak-dot ak-ok'></span>{mpx_applied}";
		}else{
			$state="<span class='ak-dot ak-warn'></span>{mpx_not_applied}";
			if($admin){$apply="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?multipath-apply=$ethu')\"><i class='fa-solid fa-check'></i> {apply}</button>";}
		}
	}
	$html[]="<div class='nicx-foot'><div class='nicx-foot-state'><span class='ak-count'>$n</span> {mpx_configured} <span id='$id-shown'></span>";
	if($state<>""){$html[]="<span class='mpx-sep'>&bull;</span>$state";}
	$html[]="</div><div class='nicx-foot-btns'>$apply</div></div>";
	$html[]="</div>";

	echo nicx_style().nicf_style().mpx_style().mpx_script($page).$tpl->_ENGINE_parse_body(implode("",$html))
		."<script>".implode("",$probes)."</script>";
}
function mpx_script($page):string{
	$tpl=new template_admin();
	$on=$tpl->javascript_parse_text("{mpx_online}");
	$off=$tpl->javascript_parse_text("{mpx_unreachable}");
	$na=$tpl->javascript_parse_text("{mpx_unknown}");
	$chk=$tpl->javascript_parse_text("{mpx_checking}");
	$js=array();
	$js[]="<script>";
	$js[]="function MpxSet(id,cls,txt){var e=document.getElementById(id);if(e){e.innerHTML=txt;e.className=cls;}}";
	$js[]="function MpxProbe(cid,ip){var st=document.getElementById(cid+'-st');if(!st){return;}";
	$js[]="st.innerHTML='<span class=\"ak-dot ak-off\"></span>'+".json_encode($chk).";st.className='ak-state';";
	$js[]="$.ajax({url:'$page?multipath-probe='+encodeURIComponent(ip),dataType:'json',cache:false}).done(function(r){";
	$js[]="var c=document.getElementById(cid);var led=document.getElementById(cid+'-led');";
	$js[]="if(!r||!r.ok){MpxSet(cid+'-st','ak-state',".json_encode($na).");MpxSet(cid+'-rtt','','&mdash;');MpxSet(cid+'-loss','','&mdash;');return;}";
	$js[]="if(r.reachable){MpxSet(cid+'-st','ak-state ak-ok','<span class=\"ak-dot ak-ok\"></span>'+".json_encode($on).");if(led){led.className='mpx-led mpx-led-on';}}";
	$js[]="else{MpxSet(cid+'-st','ak-state ak-err','<span class=\"ak-dot ak-err\"></span>'+".json_encode($off).");if(led){led.className='mpx-led mpx-led-off';}}";
	$js[]="MpxSet(cid+'-rtt','',r.received>0?(r.rtt.toFixed(1)+' ms'):'&mdash;');";
	$js[]="MpxSet(cid+'-loss',r.loss>0?'mpx-warn':'mpx-ok',r.loss.toFixed(0)+'%');";
	$js[]="if(c){c.setAttribute('data-rtt',r.received>0?r.rtt:999999);}";
	$js[]="}).fail(function(){MpxSet(cid+'-st','ak-state',".json_encode($na).");MpxSet(cid+'-rtt','','&mdash;');MpxSet(cid+'-loss','','&mdash;');});}";
	$js[]="function MpxFilter(id,v){var r=document.getElementById(id);if(!r){return;}v=(v||'').trim();var n=0,t=0;";
	$js[]="r.querySelectorAll('.mpx-card').forEach(function(c){t++;var ok=v===''||c.getAttribute('data-ip').indexOf(v)>-1;c.style.display=ok?'':'none';if(ok){n++;}});";
	$js[]="var s=document.getElementById(id+'-shown');if(s){s.innerHTML=v===''?'':'('+n+' / '+t+')';}}";
	$js[]="function MpxSort(id,k){var r=document.getElementById(id);if(!r){return;}var g=r.querySelector('.mpx-grid');var a=Array.prototype.slice.call(g.children);";
	$js[]="var ipn=function(s){return s.split('.').reduce(function(x,o){return x*256+parseInt(o,10);},0);};";
	$js[]="a.sort(function(x,y){if(k==='ip'){return ipn(x.getAttribute('data-ip'))-ipn(y.getAttribute('data-ip'));}";
	$js[]="if(k==='rtt'){return parseFloat(x.getAttribute('data-rtt'))-parseFloat(y.getAttribute('data-rtt'));}";
	$js[]="return parseInt(y.getAttribute('data-weight'),10)-parseInt(x.getAttribute('data-weight'),10);});a.forEach(function(c){g.appendChild(c);});}";
	$js[]="</script>";
	return implode("",$js);
}
// ?multipath-probe=<ip>: JSON for one card.
function multipath_probe(){
	header("content-type: application/json");
	$ip=strval($_GET["multipath-probe"]);
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator || !filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
		echo json_encode(array("ok"=>false));return;
	}
	$r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/gateway/probe/".urlencode($ip)),true);
	if(!is_array($r) || empty($r["Status"])){echo json_encode(array("ok"=>false));return;}
	echo json_encode(array("ok"=>true,"reachable"=>!empty($r["reachable"]),"received"=>intval($r["received"]),
		"rtt"=>floatval($r["rtt_ms"]),"loss"=>floatval($r["loss_pct"]),"neighbor"=>strval($r["neighbor"])));
}
// ?multipath-apply=<eth>: rebuild the interface (asynchronous on the daemon
// side), then reload the tab once the build had time to run.
function multipath_apply():bool{
	if(!netif_require_admin(true)){return false;}
	$eth=$_GET["multipath-apply"];
	if(!mpx_valid_eth($eth)){return false;}
	$page=CurrentPageName();
	$ethu=urlencode($eth);
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/build/$ethu");
	header("content-type: application/x-javascript");
	echo "setTimeout(function(){LoadAjaxSilent('multipath-div','$page?multipath-table=$ethu');},6000);\n";
	return admin_tracks("Apply multipath gateways of $eth");
}

function multipath_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["multip-path-js"]);
	$eth=$_GET["eth"];
	if(!mpx_valid_eth($eth)){return;}
	$title="{new_gateway}";
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$ligne=$q->mysqli_fetch_array("SELECT gateway from `multipath` WHERE ID=$ID");
		$title=htmlspecialchars(strval($ligne["gateway"]));
	}
	$tpl->js_dialog4("$eth: $title", "$page?mulipath-popup=$ID&eth=".urlencode($eth));
}
function multipath_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["eth"];
	if(!mpx_valid_eth($eth)){return;}
	$ID=intval($_GET["mulipath-popup"]);
	$bt="{add}";
	$ligne=array("gateway"=>"","weight"=>1);
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$ligne=$q->mysqli_fetch_array("SELECT * from `multipath` WHERE ID=$ID");
		$bt="{apply}";
	}
	$weight=max(1,min(256,intval($ligne["weight"])));
	$jsafter="LoadAjaxSilent('multipath-div','$page?multipath-table=".urlencode($eth)."');";
	$tpl->field_hidden("multipath-eth", $eth);
	$tpl->field_hidden("multipath-id", $ID);
	$form[]=$tpl->field_ipaddr("multip-path-gateway", "{gateway}", $ligne["gateway"]);
	$form[]=$tpl->field_numeric("weight","{weight} (1-256)",$weight,"{mpx_weight_explain}");
	echo $tpl->form_outside("", $form,null,$bt,"dialogInstance4.close();$jsafter","AsSystemAdministrator");
}
// mpx_in_network: the gateway must sit in the interface network, or the
// kernel refuses the nexthop at the next build ("Nexthop has invalid gateway").
function mpx_in_network($gateway,$ip,$mask):bool{
	if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) || !filter_var($mask,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){return true;}
	$m=ip2long($mask);
	return (ip2long($gateway)&$m)==(ip2long($ip)&$m);
}
function multipath_save():bool{
	if(!netif_require_admin()){return false;}
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["multipath-id"]);
	$gateway=trim(strval($_POST["multip-path-gateway"]));
	$weight=intval($_POST["weight"]);
	$eth=strval($_POST["multipath-eth"]);
	if(!mpx_valid_eth($eth)){echo $tpl->post_error("{invalid_interface}");return false;}
	if(!filter_var($gateway,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) || $gateway=="0.0.0.0"){
		echo $tpl->post_error("{gateway}: ".$gateway);return false;
	}
	if($weight<1 || $weight>256){echo $tpl->post_error("{weight}: 1-256");return false;}
	$nic=new system_nic($eth);
	if(!mpx_in_network($gateway,strval($nic->IPADDR),strval($nic->NETMASK))){
		echo $tpl->post_error("$gateway: {mpx_not_in_network} $nic->IPADDR/$nic->NETMASK");return false;
	}
	if($gateway==trim(strval($nic->GATEWAY))){
		echo $tpl->post_error("$gateway: {mpx_is_interface_gw}");return false;
	}
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$ethq=netif_sql_safe($tpl,$eth);
	$dup=$q->mysqli_fetch_array("SELECT ID FROM `multipath` WHERE nic='$ethq' AND gateway='$gateway' AND ID<>$ID");
	if(intval($dup["ID"] ?? 0)>0){echo $tpl->post_error("$gateway: {mpx_already}");return false;}
	if($ID==0){
		$sql="INSERT INTO `multipath` (nic,weight,gateway) VALUES ('$ethq',$weight,'$gateway')";
	}else{
		$sql="UPDATE `multipath` SET gateway='$gateway',weight=$weight WHERE ID=$ID";
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
	return admin_tracks("Multipath gateway $gateway weight $weight saved on $eth");
}
function multipath_delete_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["multip-path-delete"]);
	$eth=$_GET["eth"];
	if(!mpx_valid_eth($eth)){return;}
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$ligne=$q->mysqli_fetch_array("SELECT gateway FROM `multipath` WHERE ID=$ID");
	$tpl->js_confirm_delete("{gateway} ".htmlspecialchars(strval($ligne["gateway"])),"multip_path_delete",$ID,
		"LoadAjaxSilent('multipath-div','$page?multipath-table=".urlencode($eth)."');");
}
function multipath_delete():bool{
	if(!netif_require_admin("bare")){return false;}
	$ID=intval($_POST["multip_path_delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$ligne=$q->mysqli_fetch_array("SELECT nic,gateway FROM `multipath` WHERE ID=$ID");
	$q->QUERY_SQL("DELETE FROM `multipath` WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return false;}
	return admin_tracks("Remove multipath gateway {$ligne["gateway"]} from {$ligne["nic"]}");
}
function mpx_style():string{
	$c=array();
	$c[]=".mpx-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px}";
	$c[]=".mpx-algo{display:inline-flex;align-items:center;gap:8px;border:1px solid #e2e8f0;border-radius:9px;padding:7px 14px;background:#fff;color:#475569;font-size:12.5px}";
	$c[]=".mpx-algo strong{color:#1e293b}";
	$c[]=".mpx-tools{display:flex;gap:10px;margin-left:auto;flex-wrap:nowrap;align-items:center}";
	$c[]=".mpx-tools .nicf-search{flex:0 1 240px;min-width:180px}";
	$c[]=".mpx-sort{border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;font-size:12.5px;background:#fff;color:#1e293b;height:auto}";
	$c[]=".mpx-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px}";
	$c[]=".mpx-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:14px;transition:box-shadow .15s}";
	$c[]=".mpx-card:hover{box-shadow:0 2px 8px rgba(15,23,42,.08)}";
	$c[]=".mpx-card-main{border:2px solid #5eead4}";
	$c[]=".mpx-head{display:flex;align-items:flex-start;gap:12px}";
	$c[]=".mpx-ico{position:relative;width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:17px;flex:0 0 auto;border:1px solid #e2e8f0}";
	$c[]=".mpx-led{position:absolute;top:-4px;right:-4px;width:11px;height:11px;border-radius:50%;background:#cbd5e1;border:2px solid #fff}";
	$c[]=".mpx-led-on{background:#10b981}.mpx-led-off{background:#ef4444}";
	$c[]=".mpx-ip{font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:16px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
	$c[]=".mpx-head .nicx-txt{min-width:0}";
	$c[]=".mpx-tags{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex:0 0 auto}";
	$c[]=".nicx-chip-red{background:#fef2f2;color:#dc2626;border-color:#fecaca}";
	$c[]=".mpx-box{background:#f8fafc;border:1px solid #f1f5f9;border-radius:11px;padding:14px;display:flex;flex-direction:column;gap:10px}";
	$c[]=".mpx-line{display:flex;justify-content:space-between;align-items:center;color:#475569;font-size:12.5px}";
	$c[]=".mpx-w{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:700;border:1px solid #e2e8f0;background:#fff;border-radius:7px;padding:2px 10px;color:#0f172a}";
	$c[]=".mpx-load{display:flex;justify-content:space-between;gap:10px;color:#475569;font-size:12.5px}";
	$c[]=".mpx-load strong{color:#1e293b;text-align:right}";
	$c[]=".mpx-track{height:7px;border-radius:999px;background:#e2e8f0;overflow:hidden}.mpx-track div{height:100%;border-radius:999px}";
	$c[]=".mpx-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;border-top:1px solid #e2e8f0;padding-top:10px}";
	$c[]=".mpx-metrics span{display:block;color:#64748b;font-size:11.5px}.mpx-metrics b{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px;font-weight:500;color:#0f172a}.mpx-metrics b span{font-family:inherit;font-size:12px}";
	$c[]=".mpx-ok{color:#059669}.mpx-warn{color:#d97706}.mpx-muted{color:#94a3b8}";
	$c[]=".mpx-foot{display:flex;align-items:center;justify-content:space-between;border-top:1px solid #f1f5f9;padding-top:12px}";
	$c[]=".mpx-acts{display:flex;gap:4px;margin-left:auto}";
	$c[]=".mpx-note{display:flex;gap:12px;align-items:flex-start;margin-top:18px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:14px 18px;color:#334155;line-height:1.5}";
	$c[]=".mpx-note i{color:#0284c7;font-size:16px;margin-top:2px}.mpx-note strong{color:#0c4a6e}";
	$c[]=".mpx-sep{color:#cbd5e1}";
	$c[]=".mpx-empty>i{font-size:22px;color:#94a3b8}";
	return "<style>".implode("",$c)."</style>";
}
function MULTIPATH_GATEWAYS($eth){
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$sql="CREATE TABLE IF NOT EXISTS `multipath` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`nic` TEXT NOT NULL,`weight` INTEGER NOT NULL DEFAULT 1, `gateway` TEXT NOT NULL)";
	$q->QUERY_SQL($sql);
	// Pentest 2026-11-04 : le nom d'interface etait concatene dans la requete.
	$sql="SELECT * FROM `multipath` WHERE `nic`=? ORDER BY `weight`";
	$results=$q->QUERY_SQL_PARAMS($sql,array($eth));
	if(count($results)==0){return array();}
	$ipclass=new IP();

	$GATEWAYS=array();

	foreach ($results as $index=>$ligne){
		$ligne["gateway"]=trim($ligne["gateway"]);
		$weight=intval($ligne["weight"]);
		if($weight==0){$weight=1;}
		if(!$ipclass->isIPAddress($ligne["gateway"])){continue;}
		$GATEWAYS[$ligne["gateway"]]=$weight;
	}

	if(count($GATEWAYS)==0){
		VERBOSE("GATEWAY: NOT SET", __LINE__);
		$nic=new system_nic($eth);
		VERBOSE("GATEWAY: $eth default=$nic->GATEWAY", __LINE__);
		return array($nic->GATEWAY);}

	$nic=new system_nic($eth);
	VERBOSE("GATEWAY: $eth default=$nic->GATEWAY", __LINE__);
	$GATEWAYS[$nic->GATEWAY]=$nic->metric;

	$f=array();
	foreach ($GATEWAYS as $gateway=>$weight){
		VERBOSE("GATEWAY: $gateway ($weight)", __LINE__);
		$f[]="$gateway";

	}
	
	return $f;

}

function AddBond() {
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $rf=refreshjs();
    $jsafter="$rf;LoadAjax('netz-interfaces-status','$page?status2=yes');";
    $tpl->js_confirm_execute("{add_bond_interface_confirmation}","addbondinterface",1,$jsafter);
    return true;
}

function AddBondSave():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/bond/add"));
    // The daemon answers with an empty body when it cannot even read
    // /sys/class/net/bonding_masters; json_decode() then returns null and
    // dereferencing ->Status raises a fatal error instead of showing the
    // operator what went wrong.
    if(!is_object($json)){
        echo "Bond creation failed: no answer from the Artica daemon";
        return false;
    }
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    // The daemon picks the name (first free bondN) and creates the kernel
    // master, the bond_interfaces row and the nics row in one go.
    $bond=isset($json->bond)?$json->bond:"bond";
    return admin_tracks("Add new bond interface $bond");

}

function DeleteBond():bool{
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $eth=$_GET["delete-bond-js"];
    $rf=refreshjs();
    $jsafter="$rf;LoadAjax('netz-interfaces-status','$page?status2=yes');";
    $tpl->js_confirm_execute("{delete_bond_interface_confirmation} $eth","delete-bond",$eth,$jsafter);
    return true;
}

function DeleteBondSave():bool{
    $eth=$_POST["delete-bond"];
    if(!preg_match("#^bond[0-9]+$#", $eth)){
        echo "Invalid bond interface name";
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/bond/delete/$eth"));
    if(!is_object($json)){
        echo "Bond removal failed: no answer from the Artica daemon";
        return false;
    }
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Remove bond interface $eth");
}

function macvlan_js():bool{
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $tpl->js_dialog("{new_virtual_interface}","$page?add-macvlan-popup=yes");
    return true;
}
function macvlan_popup():bool{
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $type=array();
    $type["macvlan"]="macvlan";
    $type["ipvlan"]="ipvlan";
    $form[]=$tpl->field_hidden("macvlan","yes");
    $form[]=$tpl->field_interfaces("interface","nooloopNoDefphy:{physical_network_interface}");
    $form[]=$tpl->field_array_hash($type,'veth_type','{type}',$type["macvlan"],true,null,null,false);
    $jsrestart="BootstrapDialog1.close();LoadAjax('network-interfaces-table','$page?table=yes');";
    $hml[]=$tpl->form_outside("{virtual_interfaces}", @implode("\n", $form),"-{macvlan_explain}<br><br>-{ipvlan_explain}","{add}",$jsrestart,"AsSystemAdministrator");
    echo @implode("\n", $hml);
    return true;
}
function macvlan_save():bool{
    $tpl    = new template_admin();
    $catz    = new system_nic();
    if(!$catz->macvlan_create($_POST["interface"],$_POST["veth_type"])){
        echo $tpl->post_error($catz->mysql_error);
    }
    return true;
}
function macvlan_delete(){
    $eth=$_GET["delete-macvlan"];
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $md=$_GET["md"];
    $tpl->js_confirm_delete("Macvlan $eth","delete-macvlan",$eth,"dialogInstance1.close();$('#$md').remove();");
}
function macvlan_delete_perform(){
    $eth=$_POST["delete-macvlan"];
    admin_tracks("Remove macvlan interface $eth");
    $nic=new system_nic($eth);
    $nic->RemoveInterface($eth);

}
function carrier_change_explain_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
   return $tpl->js_dialog2("{about2}", "$page?carrier-change-explain-popup=yes");
}
function carrier_change_explain_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]=$tpl->div_explain("{carrier_changes}");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function AsGateway_js():bool{
    $enable = $_GET["AsGateway-js"];
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $jsafter="LoadAjax('netz-interfaces-status','$page?status2=yes');";
    if($enable==1){
        $tpl->js_confirm_execute("{enable_artica_as_gateway_ask}","AsGateway",1,$jsafter);
    }else{
        $tpl->js_confirm_execute("{disable_artica_as_gateway_ask}","AsGateway",0,$jsafter);
    }
    return true;
}
function AsGateway_unlock_js():bool{
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    return $tpl->js_dialog3("{AsGatewayUnlock}","$page?AsGateway-unlock-popup=yes",550);
}
function AsGateway_unlock_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $IsagetwayWhy=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsagetwayWhy");

    $AsGatewayUnlock_explain=$tpl->_ENGINE_parse_body("{AsGatewayUnlock_explain}");
    $AsGatewayUnlock_explain=str_replace("%p",$IsagetwayWhy,$AsGatewayUnlock_explain);
    $jsUnlock="Loadjs('$page?AsGateway-unlock-perform=yes')";
    $buton=$tpl->button_autnonome("{unlock}",$jsUnlock,ico_unlock,
        "AsSystemAdministrator",350,"btn-primary",80);

    $div="<div style='margin-top:20px;width:100%;margin-right:20px;text-align:right'>$buton</div>";
    $html[]=$tpl->div_explain("{AsGatewayUnlock}||$AsGatewayUnlock_explain$div");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function AsGateway_unlock_perform():bool{
    $page=CurrentPageName();

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/sysctl/gateway/unlock");
    header("content-type: application/x-javascript");
    echo "dialogInstance3.close();\n";
    echo "LoadAjaxSilent('netz-interfaces-status','$page?status2=yes');";
    return admin_tracks("Network: Enforce the Gateway mode to OFF");
}

function YourFirewall_progress($enable=0):string{
    $page       = CurrentPageName();
    $config[1]  = "/firewall/install";
    $config[0]  = "/firewall/uninstall";
    $jsafter    = "LoadAjax('netz-interfaces-status','$page?status2=yes');";
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/firehol.reconfigure.progress";
    $ARRAY["LOG_FILE"]      = PROGRESS_DIR . "/firehol.reconfigure.log";
    $ARRAY["CMD"]           = $config[$enable];
    $ARRAY["TITLE"]         = "{your_firewall}";
    $ARRAY["AFTER"]         = $jsafter;
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=netz-interfaces-status')";
    return $jsrestart;


}
/**
 * Bascule globale d'IPv6 : confirmation, puis la route du daemon
 * /system/network/ipv6/enable/{0|1} (articarest, rest_ipv6.go) qui ecrit
 * EnableipV6 et reconfigure sysctl, le reseau et les services qui en
 * dependent. La barre suit network.ipv6.progress ; le journal, network.ipv6.log.
 */
function EnableIPV6_js():bool{
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $enable=intval($_GET["enableIPV6-js"])==1?1:0;
    $jsafter=$tpl->framework_buildjs("/system/network/ipv6/enable/$enable",
        "network.ipv6.progress","network.ipv6.log","netz-interfaces-status",
        "LoadAjax('netz-interfaces-status','$page?status2=yes');",
        null,null,"AsSystemAdministrator");
    $ask=$enable==1?"{enable_ipv6_ask}":"{disable_ipv6_ask}";
    $tpl->js_confirm_execute($ask,"EnableIPV6Toggle",$enable,$jsafter);
    return true;
}

// POST de js_confirm_execute : trace d'audit seulement, le daemon fait le
// travail. js_confirm_* affiche la reponse BRUTE : rien en cas de succes.
function EnableIPV6_save():bool{
    if(!netif_require_admin("bare")){return false;}
    $enable=intval($_POST["EnableIPV6Toggle"])==1?1:0;
    admin_tracks($enable==1?"Enable IPv6 on the system":"Disable IPv6 on the system");
    return true;
}

function YourFirewall_save():bool{
    if($_POST["YourFirewall"]==0){
        admin_tracks("Uninstall the Firewall service");
        return true;
    }
    admin_tracks("Install the Firewall service");
    return true;
}

function AsGateway_options_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
   return $tpl->js_dialog2("{options}", "$page?AsGateway-options-popup=yes");

}
function AsGateway_options_popup():bool{
    $page=CurrentPageName();
    echo "<div id='AsGatewayOptionsPopup'></div><script>LoadAjax('AsGatewayOptionsPopup','$page?AsGateway-options-table=yes');</script>";
    return true;
}
function AsGateway_options_table():bool{
    $tpl    = new template_admin();
    $EnableVPNPPTCompliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVPNPPTCompliance"));
    $DisableNetworking =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
    $page=CurrentPageName();

    $RpFilterSaved = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RpFilterSaved"));
	$rpFilterValue = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RpFilterValue"));
    if($rpFilterValue==0){
        if($RpFilterSaved==0){
            $rpFilterValue=1;
        }
    }
    $val=0;if ($rpFilterValue==0){$val=1;}
    $form[]=$tpl->BigCircleCheckbox("AsGatewayOpts:yes|rpfilter_0","{IPspoofingprotection}/{rpfilter_title0}","{rpfilter_0}",$val,
    "LoadAjaxSilent('AsGatewayOptionsPopup','$page?AsGateway-options-table=yes')");

    $val=0;if ($rpFilterValue==1){$val=1;}
    $form[]=$tpl->BigCircleCheckbox("AsGatewayOpts:yes|rpfilter_1","{IPspoofingprotection}/{rpfilter_title1}","{rpfilter_1}",$val,
        "LoadAjaxSilent('AsGatewayOptionsPopup','$page?AsGateway-options-table=yes')");
    $val=0;if ($rpFilterValue==2){$val=1;}
    $form[]=$tpl->BigCircleCheckbox("AsGatewayOpts:yes|rpfilter_2","{IPspoofingprotection}/{rpfilter_title2}","{rpfilter_2}",$val,
        "LoadAjaxSilent('AsGatewayOptionsPopup','$page?AsGateway-options-table=yes')");


    $form[]=$tpl->BigCircleCheckbox("AsGatewayOpts:yes|EnableVPNPPTCompliance","{EnableVPNPPTCompliance}","{EnableVPNPPTCompliance_explain}",$EnableVPNPPTCompliance);

    $form[]=$tpl->BigCircleCheckbox("AsGatewayOpts:yes|DisableNetworking","{DenyHaproxyConf}","{DenyHaproxyConfExpl}",$DisableNetworking);

    echo $tpl->_ENGINE_parse_body($form);
    return true;

}
function AsGateway_options_save():bool{
    unset($_POST["AsGatewayOpts"]);
    $tpl    = new template_admin();
    $tpl->CLEAN_POST();
    foreach ($_POST as $key=>$value){
        if(preg_match("#rpfilter_([0-9])#",$key,$re)){
            $rpfilter=intval($re[1]);
            unset($_POST[$key]);
            if($value==1){
                echo "// RpFilterValue = $rpfilter\n";
                $_POST["RpFilterValue"]=$rpfilter;
                $_POST["RpFilterSaved"]=1;
            }
        }
    }


    $tpl->SAVE_POSTs();


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");

    admin_tracks_post("Set Network Global options");

    return true;
}


function YourFirewall_js():bool{
    $enable = $_GET["YourFirewall-js"];
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $jsafter=YourFirewall_progress($enable);
    if($enable==1){
        $tpl->js_confirm_execute("{activate_firewall_ask}","YourFirewall",1,$jsafter);
    }else{
        $tpl->js_confirm_execute("{disable_firewall_ask}","YourFirewall",0,$jsafter);
    }
    return true;

}


function NetOptimize_save(){
    // js_confirm_* affiche la reponse BRUTE: mode "bare", pas post_error().
    if(!netif_require_admin("bare")){return false;}
    admin_tracks("Switch Kernel network optimization to {$_POST["netoptimize"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSystemNetworkOptimize",$_POST["netoptimize"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
}

function NetOptimize_js(){
    $enable = $_GET["netoptimize-js"];
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $jsafter="LoadAjax('netz-interfaces-status','$page?status2=yes');";
    if($enable==1){
        $tpl->js_confirm_execute("{SystemNetworkOptimize_enable_explain}","netoptimize",1,$jsafter);
    }else{
        $tpl->js_confirm_execute("{SystemNetworkOptimize_disable_explain}","netoptimize",0,$jsafter);
    }
    return true;
}

function AsGateway_save(){
    // js_confirm_* affiche la reponse BRUTE: mode "bare", pas post_error().
    if(!netif_require_admin("bare")){return false;}
    admin_tracks("Switch gateway mode to {$_POST["AsGateway"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableArticaAsGateway",$_POST["AsGateway"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");

}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

/** Vrai quand le LB HaCluster impose BBR a ce noeud : la case locale serait
 *  trompeuse (sysctl.Build() fait gagner le cluster). */
function netif_bbr_cluster_managed():bool{
    // Load-balancer HaCluster : le reglage du cluster s'applique aussi a lui.
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"))==1){
        return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterBBRManaged"))==1;
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"))!=1){return false;}
    $m=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterBBRMode"));
    return $m==1 || $m==2;
}
