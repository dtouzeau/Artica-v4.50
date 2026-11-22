<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/settings.exists.inc"); // artica_setting_exists(): settings are root-only, see the file
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["restart-js"])){restart_js();exit;}
if(isset($_POST["ENABLE_SERVER"])){SAVE_SERVER();exit;}
if(isset($_GET["main-start"])){main_start();exit;}
if(isset($_GET["main-flat"])){main_flat();exit;}
if(isset($_GET["openvpn-status"])){openvpn_status();exit;}
if(isset($_GET["openvpn-conns"])){openvpn_conns();exit;}

if(isset($_GET["section-nic-js"])){section_nic_js();exit;}
if(isset($_GET["section-net-js"])){section_net_js();exit;}
if(isset($_GET["section-proto-js"])){section_proto_js();exit;}
if(isset($_GET["section-failover-js"])){section_failover_js();exit;}
if(isset($_GET["section-dhcp-js"])){section_dhcp_js();exit;}
if(isset($_GET["section-firewall-js"])){section_firewall_js();exit;}
if(isset($_GET["section-infos-js"])){section_infos_js();exit;}
if(isset($_GET["section-log-js"])){section_log_js();exit;}


if(isset($_GET["section-nic-popup"])){section_nic_popup();exit;}
if(isset($_GET["section-net-popup"])){section_net_popup();exit;}
if(isset($_GET["section-proto-popup"])){section_proto_popup();exit;}
if(isset($_GET["section-failover-popup"])){section_failover_popup();exit;}
if(isset($_GET["section-dhcp-popup"])){section_dhcp_popup();exit;}
if(isset($_GET["section-firewall-popup"])){section_firewall_popup();exit;}
if(isset($_GET["section-infos-popup"])){section_infos_popup();exit;}
if(isset($_GET["section-log-popup"])){section_log_popup();exit;}

page();
function openvpn_status():bool{
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $data = $sock->REST_API("/openvpn/service/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error","{error}"));
        return true;
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsRestart="Loadjs('$page?restart-js=yes');";
    echo $tpl->SERVICE_STATUS($ini, "APP_OPENVPN",$jsRestart);

    echo "<script>\n";
    echo "function OpenVPNStatusCadence(){\n";
    echo "LoadAjaxSilent('openvpn-status','$page?openvpn-status=yes');\n";
    echo "}\n";
    echo "if ( document.getElementById('openvpn-status') ){\n";
    echo "setTimeout(\"OpenVPNStatusCadence()\",3000);\n";
    echo "}\n";
    echo "</script>\n";
    return true;
}
function restart_js():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsVPNManager){
        return $tpl->js_no_privileges();
    }
    $sock=new sockets();
    $sock->REST_API("/openvpn/service/restart");
    return $tpl->js_executed_background("{success}");
}

function jsTiny(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $TINY_ARRAY=array();
    $version="&nbsp;";
    $error="{openvpn_whatis}";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/openvpn/service/version");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        $error="<span class='text-danger font-boldfw'>".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}</span>";

    }else {
        if (!$json->Status) {
            $TINY_ARRAY["DANGER"]=true;
            $ico=ico_engine_warning;
            $error="<span class='text-danger font-bold'><i class='$ico'></i>&nbsp;$json->Error</span>";
        }else{
            $version=sprintf(" v%s",$json->Info);
        }
    }

    // Les actions de la page vivent dans l'en-tete (TINY), pas en pied de carte:
    // le pied se trouve a ~1000px du haut une fois les 8 sections rendues.
    $topbuttons=array();

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/openvpn.reconfigure.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/openvpn.enable.log";
    $ARRAY["CMD"]="/openvpn/service/reconfigure";
    $ARRAY["TITLE"]="{reconfigure_service} {APP_OPENVPN}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-openvpn-restart')";


    $topbuttons[]=array($jsrestart,ico_configure,"{reconfigure_service}");
    $topbuttons[]=array("LoadAjaxSilent('openvpn-flat','$page?main-flat=yes');",ico_refresh,"{refresh}");

    $TINY_ARRAY["TITLE"]="{OPENVPN_SERVER_SETTINGS}$version";
    $TINY_ARRAY["ICO"]="fas fa-cogs";
    $TINY_ARRAY["EXPL"]="{APP_OPENVPN_TEXT}<br>$error";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}



function section_js():string{
    $page=CurrentPageName();
    $js[]="dialogInstance2.close();";
    $js[]="LoadAjaxSilent('openvpn-flat','$page?main-flat=yes');";
    return @implode(";",$js);
}

function section_nic_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{server_settings}","$page?section-nic-popup=yes");
}
function section_net_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{network_parameters}","$page?section-net-popup=yes");
}
function section_proto_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{protocol}","$page?section-proto-popup=yes");
}
function section_dhcp_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{DHCP_SERVICE_OPENVPN}","$page?section-dhcp-popup=yes");
}
function section_firewall_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{FIREWALL_SERVICE_OPENVPN}","$page?section-firewall-popup=yes");
}
function section_infos_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{service_informations}","$page?section-infos-popup=yes");
}
function section_log_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{events}","$page?section-log-popup=yes");
}
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{OPENVPN_SERVER_SETTINGS}",
        "fas fa-cogs","{APP_OPENVPN_TEXT}<br>{openvpn_whatis}","$page?main-start=yes",
        "openvpn-settings","progress-openvpn-restart",false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function main_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html=array();
    $html[]="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'>
		    <div id='openvpn-status' style='margin-top:15px'></div>
		    <div id='openvpn-conns'></div>
		</td>
		<td	style='vertical-align:top;width:90%;padding-left:10px'>
		    <div id='openvpn-flat'></div>
		</td>
	</tr>
	</table>";

    // Trois fragments, trois cadences independantes: le widget de service bat
    // toutes les 3s (son propre setTimeout), les compteurs de session toutes les
    // 30s, et la carte de parametres n'est rechargee qu'apres une ecriture.
    // Un rafraichissement commun redessinerait la carte pendant que l'operateur
    // la lit. Le <script> est concatene APRES l'analyse: RefreshInterval_js()
    // emet "if(window.ArticaRefresh){...}", que le moteur lirait comme un {token}.
    $js=array();
    $js[]="LoadAjaxSilent('openvpn-status','$page?openvpn-status=yes');";
    $js[]="LoadAjaxSilent('openvpn-flat','$page?main-flat=yes');";
    // ArticaRefresh charge lui-meme au premier tick (schedule() des l'appel):
    // un LoadAjaxSilent en plus demanderait deux fois le meme fragment.
    $js[]=$tpl->RefreshInterval_js("openvpn-conns",$page,"openvpn-conns=yes",30);
    echo $tpl->_ENGINE_parse_body($html)."<script>".@implode("\n",$js)."</script>";
    return true;

}
/* ==========================================================================
 * Bibliotheque .ovs-* -- refonte visuelle de la carte de parametres.
 *
 * La carte reste batie avec la famille table_form_* (convention "flat":
 * c'est la SEULE facon d'afficher des parametres dans cette console). Ce qui
 * change, ce sont les VALEURS: elles passaient en "<small>a, b, c</small>",
 * une phrase a virgules qu'il faut lire en entier pour trouver un reglage.
 * Elles deviennent des pastilles typees -- verte = actif, grise = inactif,
 * bleue = valeur numerique, mono = adresse, rouge = non configure -- que
 * l'oeil trie sans les lire.
 *
 * Regle du fichier: une fonction par ligne de la carte, une par carte.
 * ========================================================================== */

/**
 * Feuille scopee des pastilles. JAMAIS a travers _ENGINE_parse_body():
 * son motif #{(.+?)}#is lirait chaque corps de regle CSS comme un {token}
 * et declencherait une recherche memcached par regle.
 */
function ovs_style():string{
    if(isset($GLOBALS["OVS_CSS"])){return "";}
    $GLOBALS["OVS_CSS"]=true;
    $css=".ovs-chips{display:flex;flex-wrap:wrap;gap:6px;align-items:center;text-transform:none;line-height:1.4;min-width:0}\n";
    // Pas de white-space:nowrap: en grille deux colonnes une chip plus large que
    // sa colonne DEBORDERAIT sur la voisine (mesure: "10.8.0.0/255.255.255.0"
    // sortait de 60px et recouvrait le libelle d'a cote). Elle se replie.
    $css.=".ovs-chip{display:inline-flex;flex-wrap:wrap;align-items:center;gap:2px 5px;border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600;border:1px solid transparent;max-width:100%;min-width:0;white-space:normal;overflow-wrap:break-word}\n";
    $css.=".ovs-val{font-weight:600}\n";
    $css.=".ovs-chip-ok{background:#e8f8f2;border-color:#bfe9d9;color:#0f7a5a}\n";
    $css.=".ovs-chip-off{background:#f2f4f6;border-color:#e0e4e8;color:#7b8794}\n";
    $css.=".ovs-chip-info{background:#eef4ff;border-color:#d5e3ff;color:#2b5cb8}\n";
    $css.=".ovs-chip-warn{background:#fff6e8;border-color:#ffe0b8;color:#a35c00}\n";
    $css.=".ovs-chip-err{background:#fdecee;border-color:#f8ccd2;color:#b3253a}\n";
    $css.=".ovs-chip-mono{background:#f6f7f9;border-color:#e2e6ea;color:#3d4650;font-family:monospace;font-weight:500}\n";
    $css.=".ovs-dot{width:6px;height:6px;border-radius:50%;background:currentColor;flex:0 0 6px}\n";
    $css.=".ovs-arw{color:#b3bcc5;font-size:10px;font-weight:700}\n";
    $css.=".ovs-lbl{color:#8a939c;font-size:11px;font-weight:400}\n";
    $css.=".ovs-foot{padding:8px 6px 0;color:#98a2ad;font-size:11.5px;text-align:right}\n";
    return "<style>\n$css</style>\n";
}

/**
 * Feuille de la carte des connexions. Fragment separe = requete separee:
 * elle porte sa propre garde et sa propre copie.
 */
function ovs_style_cx():string{
    if(isset($GLOBALS["OVS_CSS_CX"])){return "";}
    $GLOBALS["OVS_CSS_CX"]=true;
    $css=".ovs-cx{background:#fff;border:1px solid #e7eaee;border-radius:8px;padding:13px 15px;margin-top:14px}\n";
    $css.=".ovs-cx-h{margin:0 0 9px;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#8a939c}\n";
    $css.=".ovs-cx-r{display:flex;justify-content:space-between;align-items:baseline;gap:8px;padding:6px 0;border-top:1px solid #f0f2f5}\n";
    $css.=".ovs-cx-r:first-of-type{border-top:0;padding-top:0}\n";
    $css.=".ovs-cx-k{color:#676a6c;font-size:12px;min-width:0}\n";
    $css.=".ovs-cx-v{font-family:monospace;font-size:12.5px;font-weight:600;color:#2f3a45;white-space:nowrap}\n";
    $css.=".ovs-cx-v-on{color:#0f7a5a}\n";
    $css.=".ovs-cx-e{color:#98a2ad;font-size:12px;font-style:italic}\n";
    return "<style>\n$css</style>\n";
}

function ovs_chip($text,$tone="info"):string{
    $dot="";
    if($tone=="ok" OR $tone=="off"){$dot="<span class='ovs-dot'></span>";}
    return "<span class='ovs-chip ovs-chip-$tone'>$dot$text</span>";
}

/** Vert quand l'option est active, gris quand elle ne l'est pas. */
function ovs_chip_bool($on,$label):string{
    if(intval($on)==1){return ovs_chip($label,"ok");}
    return ovs_chip($label,"off");
}

function ovs_chips(array $chips):string{
    if(count($chips)==0){return ovs_chip("{none}","off");}
    return "<span class='ovs-chips'>".@implode("",$chips)."</span>";
}

/** a -> b -> c : le chemin que suit une session, dans l'ordre. */
function ovs_flow(array $parts):string{
    $f=array();
    foreach($parts as $p){
        if(trim((string)$p)==""){continue;}
        if(count($f)>0){$f[]="<span class='ovs-arw'>&#9656;</span>";}
        $f[]=ovs_chip($p,"mono");
    }
    return "<span class='ovs-chips'>".@implode("",$f)."</span>";
}

/**
 * Pastille "libelle + valeur". Les deux sont des enfants flex SEPARES: colles
 * par un &nbsp; ils formaient un seul mot insecable, et une paire longue
 * ("From tcp/ip address 10.8.0.0/255.255.255.0") debordait de sa colonne au
 * lieu de se replier -- mesure sur la grille deux colonnes a 486px.
 */
function ovs_chip_kv($label,$value,$tone="info"):string{
    return "<span class='ovs-chip ovs-chip-$tone'><span class='ovs-lbl'>$label</span>"
        ."<span class='ovs-val'>$value</span></span>";
}

/* --------------------------------------------------------------------------
 * Une fonction par ligne. Chacune retourne le HTML de la VALEUR; le libelle,
 * l'icone et le crayon d'edition restent a la charge de table_form_*.
 * -------------------------------------------------------------------------- */

/**
 * Ligne 1 -- ou le service ecoute, et par ou sortent les sessions.
 * Retourne array(valeur, erreur, sous-titre): l'avertissement est un SOUS-TITRE
 * du libelle, pas une valeur. Mis dans la valeur il heritait de l'alignement a
 * droite de la colonne et rendait un pave de six lignes centre au milieu de la
 * carte (constate sur la box, ou RedirectGateway=1 sans interface de sortie).
 */
function ovs_value_listen(openvpn $vpn,$OpenVPNTunInterface,$OpenVPNRedirectGateway):array{
    $proto=trim((string)$vpn->main_array["GLOBAL"]["LISTEN_PROTO"]);
    if($proto==""){$proto="udp";}
    $listen=sprintf("%s://%s:%s",$proto,
        $vpn->main_array["GLOBAL"]["LOCAL_BIND"],
        $vpn->main_array["GLOBAL"]["LISTEN_PORT"]);
    $out=$vpn->main_array["GLOBAL"]["IPTABLES_ETH"];

    // "Prendre tout le trafic" sans interface de sortie: le daemon n'a pas de
    // quoi poser la regle de masquerade, les sessions montent et ne routent rien.
    // La chaine s'arrete alors visiblement a tun0, et la pastille passe en rouge.
    $error=false;
    $explain="";
    if(intval($OpenVPNRedirectGateway)==1 AND trim((string)$out)==""){
        $error=true;
        $explain="{openvpn_handle_all_traffic_not_interface}";
    }
    return array(ovs_flow(array($listen,$OpenVPNTunInterface,$out)),$error,$explain);
}

/** Ligne 2 -- le certificat serveur, confronte au magasin de certificats. */
function ovs_value_certificate($OpenVPNCertificate):array{
    $cn=trim((string)$OpenVPNCertificate);
    if(strlen($cn)<2){
        return array(ovs_chips(array(ovs_chip("{not_configured}","err"))),true);
    }
    // L'etat affiche est celui du magasin, pas une supposition: un certificat
    // choisi puis supprime laisserait sinon une ligne verte pointant sur rien.
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $row=null;
    if(preg_match("#^SUB:([0-9]+)$#",$cn,$re)){
        $row=$cdb->getById(intval($re[1]));
        if(is_object($row)){$cn=(string)($row->commonName ?? $cn);}
    }else{
        $row=$cdb->getByCommonName($cn);
    }
    $chips=array(ovs_chip($cn,"mono"));
    if(is_object($row)){
        $chips[]=ovs_chip("{valid}","ok");
        return array(ovs_chips($chips),false);
    }
    $chips[]=ovs_chip("{missing}","err");
    return array(ovs_chips($chips),true);
}

/** Ligne 3 -- les limites de dimensionnement. */
function ovs_value_network($MaxClients,$OpenVPNTUNMTU,$OpenVPNTCPLIMIT,$OpenVPNFragments,$OpenVPNMSSFIX):string{
    $chips=array();
    $chips[]=ovs_chip_kv("{QOSMaxClients}",intval($MaxClients),"info");
    if(intval($OpenVPNTUNMTU)>0){$chips[]=ovs_chip_kv("MTU",intval($OpenVPNTUNMTU),"info");}
    if(intval($OpenVPNTCPLIMIT)>0){$chips[]=ovs_chip_kv("TCP",intval($OpenVPNTCPLIMIT),"info");}
    if(intval($OpenVPNFragments)>0){$chips[]=ovs_chip_kv("{internal_fragmentation_algorithm}",intval($OpenVPNFragments)." bytes","info");}
    if(intval($OpenVPNMSSFIX)>0){$chips[]=ovs_chip_kv("MSSFIX",intval($OpenVPNMSSFIX)." bytes","info");}
    return ovs_chips($chips);
}

/** Ligne 4 -- compression et renegociation de cle. */
function ovs_value_protocol($OpenVPNUseLZO,$OpenVPNRenegSec):string{
    $chips=array();
    $chips[]=ovs_chip_bool($OpenVPNUseLZO,"{LZOC}");
    if(intval($OpenVPNRenegSec)>0){
        $chips[]=ovs_chip_kv("{renegsec}",intval($OpenVPNRenegSec)." {seconds}","info");
    }
    return ovs_chips($chips);
}

/** Ligne 5 -- serveurs de repli et detection de lien mort. */
function ovs_value_failover(sockets $sock):string{
    $remote2=trim((string)$sock->GET_INFO("OpenVPNRemote2"));
    $remote3=trim((string)$sock->GET_INFO("OpenVPNRemote3"));
    $chips=array();
    if(strlen($remote2)>2){
        $chips[]=ovs_chip("{enabled}","ok");
        $chips[]=ovs_chip($remote2,"mono");
        if(strlen($remote3)>2){$chips[]=ovs_chip($remote3,"mono");}
    }else{
        $chips[]=ovs_chip("{disabled}","off");
    }
    // Le keepalive se regle ICI (et nulle part ailleurs): il s'affiche donc sur
    // la ligne dont le crayon ouvre sa fenetre.
    $ka=intval($sock->GET_INFO("OpenVPNKeepaliveInterval"));
    $kt=intval($sock->GET_INFO("OpenVPNKeepaliveTimeout"));
    if($ka>0){
        $v=$ka."s";
        if($kt>0){$v.="&nbsp;/&nbsp;".$kt."s";}
        $chips[]=ovs_chip_kv("{keepalive_interval}",$v,"info");
    }
    return ovs_chips($chips);
}

/** Ligne 6 -- le bail distribue aux sessions et ce qu'elles ont le droit de faire. */
function ovs_value_dhcp(openvpn $vpn,$OpenVPNClientCertNotRequired,$OpenVPNAuthenticate,$OpenVPNRedirectGateway,$OpenVPNBlockLocal):string{
    $chips=array();
    $ip=new IP();
    $cidr=$ip->mask2cdr($vpn->main_array["GLOBAL"]["NETMASK"]);
    $chips[]=ovs_chip_kv("{network2}",$vpn->main_array["GLOBAL"]["IP_START"]."/$cidr","mono");

    $dns=array();
    if(strlen($vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_1"])>3){$dns[]=$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_1"];}
    if(strlen($vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_2"])>3){$dns[]=$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_2"];}
    if(count($dns)>0){$chips[]=ovs_chip_kv("DNS",@implode(", ",$dns),"mono");}

    if(strlen($vpn->main_array["GLOBAL"]["WAKEUP_IP"])>3){
        $chips[]=ovs_chip_kv("{wake_up_ip}",$vpn->main_array["GLOBAL"]["WAKEUP_IP"],"mono");
    }

    $chips[]=ovs_chip_bool($OpenVPNAuthenticate,"{enable_authentication}");
    $chips[]=ovs_chip_bool($OpenVPNRedirectGateway,"{strict_traffic}");
    $chips[]=ovs_chip_bool($OpenVPNBlockLocal,"{OpenVPNBlockLocal}");
    return ovs_chips($chips);
}

/** Ligne 7 -- l'adresse que les clients composent depuis l'exterieur. */
function ovs_value_service(openvpn $vpn):array{
    $public=trim((string)$vpn->main_array["GLOBAL"]["PUBLIC_IP"]);
    $port=$vpn->main_array["GLOBAL"]["CLIENT_NAT_PORT"];
    if(trim((string)$port)==""){$port=$vpn->main_array["GLOBAL"]["LISTEN_PORT"];}
    $chips=array();
    $error=false;
    if(strlen($public)==0){
        $chips[]=ovs_chip("{not_configured}","err");
        $error=true;
    }else{
        $chips[]=ovs_chip_kv("{public_ip_addr}","$public:$port","mono");
    }
    if(intval($vpn->main_array["GLOBAL"]["USE_RPROXY"])==1){
        $chips[]=ovs_chip_kv("{reverse_proxy}",$vpn->main_array["GLOBAL"]["PROXYADDR"].":".$vpn->main_array["GLOBAL"]["PROXYPORT"],"mono");
    }
    return array(ovs_chips($chips),$error);
}

/** Ligne 8 -- verbosite et conservation legale. */
function ovs_value_logs($OpenVPNVerboseMode,$OpenVPNLegalLogs):string{
    if(intval($OpenVPNVerboseMode)<2){$OpenVPNVerboseMode=2;}
    $chips=array();
    $chips[]=ovs_chip_kv("{log_level}",intval($OpenVPNVerboseMode),"info");
    $chips[]=ovs_chip_bool($OpenVPNLegalLogs,"{legal_logs}");
    return ovs_chips($chips);
}

/**
 * Pied de carte -- la date reelle de derniere ecriture des reglages. Le fichier
 * n'existe pas tant que le serveur n'a jamais ete configure: dans ce cas on
 * n'affiche rien plutot qu'une date inventee.
 */
function ovs_foot():string{
    $f="/etc/artica-postfix/settings/Daemons/ArticaOpenVPNSettings";
    if(!is_file($f)){return "";}
    $mtime=@filemtime($f);
    if(intval($mtime)==0){return "";}
    $ago=distanceOfTimeInWords($mtime,time());
    return "<div class='ovs-foot'>{ovs_settings_changed}&nbsp;$ago</div>";
}

/* -------------------------------------------------------------------------- */

function main_flat(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $vpn=new openvpn();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    buildDefaults();

    $OpenVPNCertificate=$sock->GET_INFO("OpenVPNCertificate");
    $MaxClients=intval($sock->GET_INFO("OpenVPNMaxClients"));
    $OpenVPNTUNMTU=intval($sock->GET_INFO("OpenVPNTUNMTU"));
    $OpenVPNTCPLIMIT=intval($sock->GET_INFO("OpenVPNTCPLIMIT"));
    $OpenVPNRenegSec=intval($sock->GET_INFO("OpenVPNRenegSec"));
    $OpenVPNFragments=intval($sock->GET_INFO("OpenVPNFragments"));
    $OpenVPNMSSFIX=intval($sock->GET_INFO("OpenVPNMSSFIX"));
    $OpenVPNUseLZO=intval($sock->GET_INFO("OpenVPNUseLZO"));
    $OpenVPNVerboseMode=intval($sock->GET_INFO("OpenVPNVerboseMode"));
    $OpenVPNLegalLogs=intval($sock->GET_INFO("OpenVPNLegalLogs"));
    $OpenVPNTunInterface=$sock->GET_INFO("OpenVPNTunInterface");
    $OpenVPNRedirectGateway=$sock->GET_INFO("OpenVPNRedirectGateway");
    $OpenVPNBlockLocal=$sock->GET_INFO("OpenVPNBlockLocal");
    $OpenVPNAuthenticate=$sock->GET_INFO("OpenVPNAuthenticate");
    $OpenVPNClientCertNotRequired=$sock->GET_INFO("OpenVPNClientCertNotRequired");

    if($MaxClients==0){$MaxClients=500;}
    if($OpenVPNTunInterface==null){$OpenVPNTunInterface="tun0";}
    if(intval($vpn->main_array["GLOBAL"]["LISTEN_PORT"])==0){$vpn->main_array["GLOBAL"]["LISTEN_PORT"]=1194;}
    if(trim($vpn->main_array["GLOBAL"]["IP_START"])==null){$vpn->main_array["GLOBAL"]["IP_START"]="10.8.0.0";}
    if(trim($vpn->main_array["GLOBAL"]["NETMASK"])==null){$vpn->main_array["GLOBAL"]["NETMASK"]="255.255.255.0";}
    if(intval($vpn->main_array["GLOBAL"]["PROXYPORT"])<10){$vpn->main_array["GLOBAL"]["PROXYPORT"]=8080;}
    if(trim($vpn->main_array["GLOBAL"]["LOCAL_BIND"])==null){$vpn->main_array["GLOBAL"]["LOCAL_BIND"]="eth0";}

    // Quatre groupes de deux lignes, compiles en deux colonnes: huit lignes a la
    // file sont un mur qu'il faut lire de haut en bas pour trouver un reglage.
    $tpl->table_form_section("{server_settings}");

    list($vListen,$eListen,$xListen)=ovs_value_listen($vpn,$OpenVPNTunInterface,$OpenVPNRedirectGateway);
    $tpl->table_form_field_js("Loadjs('$page?section-nic-js=yes')","AsVPNManager");
    $tpl->table_form_field_text("{openvpn_local}",$vListen,ico_nic,$eListen);

    list($vCert,$eCert)=ovs_value_certificate($OpenVPNCertificate);
    $tpl->table_form_field_js("Loadjs('$page?section-nic-js=yes')","AsVPNManager");
    $tpl->table_form_field_text("{certificate}",$vCert,ico_certificate,$eCert);

    // Un avertissement de 230 caracteres n'entre ni dans une valeur ni dans un
    // sous-titre: en grille deux colonnes le libelle ne fait que ~110px et le
    // texte s'y etirait sur dix-huit lignes. table_form_field_warn() rend une
    // ligne PLEINE LARGEUR, ce pour quoi elle existe.
    if($xListen<>""){$tpl->table_form_field_warn($xListen);}

    $tpl->table_form_section("{ovs_transport}");

    $tpl->table_form_field_js("Loadjs('$page?section-net-js=yes')","AsVPNManager");
    $tpl->table_form_field_text("{network_parameters}",
        ovs_value_network($MaxClients,$OpenVPNTUNMTU,$OpenVPNTCPLIMIT,$OpenVPNFragments,$OpenVPNMSSFIX),ico_timeout);

    $tpl->table_form_field_js("Loadjs('$page?section-proto-js=yes')","AsVPNManager");
    $tpl->table_form_field_text("{protocol}",
        ovs_value_protocol($OpenVPNUseLZO,$OpenVPNRenegSec),ico_proto);

    $tpl->table_form_section("{ovs_sessions}");

    $tpl->table_form_field_js("Loadjs('$page?section-failover-js=yes')","AsVPNManager");
    $tpl->table_form_field_text("{failover_reconnect}",ovs_value_failover($sock),ico_shield);

    $tpl->table_form_field_js("Loadjs('$page?section-dhcp-js=yes')","AsVPNManager");
    $tpl->table_form_field_text("{DHCP_SERVICE_OPENVPN}",
        ovs_value_dhcp($vpn,$OpenVPNClientCertNotRequired,$OpenVPNAuthenticate,$OpenVPNRedirectGateway,$OpenVPNBlockLocal),ico_users);

    $tpl->table_form_section("{ovs_operations}");

    list($vSvc,$eSvc)=ovs_value_service($vpn);
    $tpl->table_form_field_js("Loadjs('$page?section-infos-js=yes')","AsVPNManager");
    $tpl->table_form_field_text("{service_informations}",$vSvc,ico_infoi,$eSvc);

    // Le bouton "voir les evenements" occupe la ligne: table_form_field_text()
    // debranche alors le clic global, sinon un clic sur le lien ouvrirait AUSSI
    // la fenetre de journalisation.
    $logbtn=array();
   // $logbtn[]=array("LoadAjaxSilent('MainContent','fw.openvpn.events.php');",ico_eye,"{ovs_view_events}");
    $tpl->table_form_field_js("Loadjs('$page?section-log-js=yes')","AsVPNManager",$logbtn);
    $tpl->table_form_field_text("{events}",ovs_value_logs($OpenVPNVerboseMode,$OpenVPNLegalLogs),ico_eye);

    // table_form_compile() renvoie deja "$css.parse($html)"; ovs_style() et le
    // pied se concatenent AUTOUR, jamais dedans.
    echo ovs_style().$tpl->table_form_compile(2).$tpl->_ENGINE_parse_body(ovs_foot())
        ."<script>".jsTiny()."</script>";
    return true;
}

/**
 * Carte "connexions actives" -- sous le widget de service. Compte les sessions
 * et somme leur trafic depuis /openvpn/session/scan, la meme source que
 * fw.openvpn.sessions.php: deux pages ne peuvent donc pas afficher deux
 * verites differentes.
 */
function openvpn_conns():bool{
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $MaxClients=intval($sock->GET_INFO("OpenVPNMaxClients"));
    if($MaxClients==0){$MaxClients=500;}

    $json=json_decode($sock->REST_API("/openvpn/session/scan"));
    $users=array();
    $ok=false;
    if(is_object($json) AND !empty($json->Status)){
        $ok=true;
        if(isset($json->List->Users) AND is_array($json->List->Users)){$users=$json->List->Users;}
    }
    echo ovs_style_cx().$tpl->_ENGINE_parse_body(ovs_card_connections($users,$MaxClients,$ok));
    return true;
}

function ovs_card_connections(array $users,$MaxClients,$ok):string{
    $h=array();
    $h[]="<div class='ovs-cx'>";
    $h[]="<h3 class='ovs-cx-h'>{ovs_active_connections}</h3>";
    if(!$ok){
        // Service arrete ou fichier de statut absent: on le dit, on n'affiche
        // pas trois zeros qui se lisent comme "personne n'est connecte".
        $h[]="<div class='ovs-cx-e'>{ovs_no_data}</div>";
        $h[]="</div>";
        return @implode("\n",$h);
    }
    $rx=0;$tx=0;
    foreach($users as $object){
        $rx=$rx+floatval(isset($object->BytesReceived)?$object->BytesReceived:0);
        $tx=$tx+floatval(isset($object->BytesSent)?$object->BytesSent:0);
    }
    $count=count($users);
    $tone=$count>0?" ovs-cx-v-on":"";
    $h[]=ovs_cx_row("{ovs_connected_clients}","$count / $MaxClients",$tone);
    // FormatBytes() attend des KILO-octets, l'API renvoie des octets.
    $h[]=ovs_cx_row("{received} (RX)",FormatBytes($rx/1024,true),"");
    $h[]=ovs_cx_row("{sent} (TX)",FormatBytes($tx/1024,true),"");
    $h[]="</div>";
    return @implode("\n",$h);
}

function ovs_cx_row($label,$value,$tone=""):string{
    return "<div class='ovs-cx-r'><span class='ovs-cx-k'>$label</span>"
        ."<span class='ovs-cx-v$tone'>$value</span></div>";
}
function section_log_popup():bool{
    $tpl=new template_admin();
    for($i=2;$i<12;$i++){
        $VERB[$i]=$i;
    }
    $OpenVPNLegalLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNLegalLogs"));
    $OpenVPNVerboseMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVerboseMode"));
    if($OpenVPNVerboseMode<2){$OpenVPNVerboseMode=2;}
    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_array_hash($VERB,"OpenVPNVerboseMode","{log_level}",$OpenVPNVerboseMode,false);
    $myform=$tpl->form_outside(null, $form,null,"{apply}",section_js(),"AsVPNManager",true);
    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}

function section_net_popup(){
    $tpl=new template_admin();
    $OpenVPNFragments=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNFragments"));
    $MaxClients=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNMaxClients"));
    $OpenVPNTUNMTU=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTUNMTU"));
    $OpenVPNTCPLIMIT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTCPLIMIT"));
    $OpenVPNInternalFragmentationAlgorithm=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNInternalFragmentationAlgorithm"));
    $OpenVPNMSSFIX=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNMSSFIX"));

    if($MaxClients==0){$MaxClients=500;}

    $TCP_LIMIT[64]=64;
    $TCP_LIMIT[256]=256;
    $TCP_LIMIT[512]=512;
    $TCP_LIMIT[1024]=1024;
    $TCP_LIMIT[0]="{default}";


    $MTUS[1024]=1024;
    $MTUS[1472]=1472;
    $MTUS[1500]=1500;
    $MTUS[2000]=2000;
    $MTUS[6000]=6000;
    $MTUS[9000]=9000;
    $MTUS[12000]=12000;
    $MTUS[24000]=24000;
    $MTUS[36000]=36000;
    $MTUS[48000]=48000;
    $MTUS[60000]=60000;
    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_numeric("OpenVPNMaxClients", "{MaxClients}", $MaxClients);
    $form[]=$tpl->field_array_hash($TCP_LIMIT,"OpenVPNTCPLIMIT","{OpenVPNTCPLIMIT}",$OpenVPNTCPLIMIT,
        false,"{OpenVPNTCPLIMIT_EXPLAIN}");
    $form[]=$tpl->field_array_hash($MTUS,"OpenVPNTUNMTU","{MTU}",$OpenVPNTUNMTU,false,"{MTU_EXPLAIN}");
    $form[]=$tpl->field_numeric("OpenVPNFragments", "{internal_fragmentation_algorithm} (bytes)", $OpenVPNFragments,"{openvpn_fragment_explain}");
    $form[]=$tpl->field_numeric("OpenVPNMSSFIX", "MSSFIX (bytes)", $OpenVPNMSSFIX,"{openvpn_mssfix_explain}");

    $myform=$tpl->form_outside(null, $form,null,"{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
}
function openvpn_nic_key($value):string{
    // The two NIC selects list interface NAMES, and the daemon converts the
    // stored name to an address itself (ipclass.InterfaceToIPv4). Appliances
    // configured before the ArrayNics fix hold an IP ADDRESS instead, because
    // the option values were built from the wrong half of the interface map.
    // Such a value matches no option: the select would silently fall back to
    // "{none}/{default}" and the next save would erase the setting. Translate
    // a known local IP back to its interface name; leave anything else alone.
    $value=trim((string)$value);
    if($value==""){return "";}
    static $interfaces=null;
    if($interfaces===null){
        $net=new networking();
        $interfaces=$net->Local_interfaces();
    }
    if(isset($interfaces[$value])){return $value;}
    $found=array_search($value,$interfaces,true);
    if($found!==false){return (string)$found;}
    return $value;
}

function section_nic_popup():bool{
    $tpl=new template_admin();
    $vpn=new openvpn();
    $squid_reverse=new squid_reverse();
    $sslcertificates=$squid_reverse->ssl_certificates_list();
    for($i=0;$i<256;$i++){
        $TunInterfaces["tun{$i}"]="tun{$i}";
    }
    $OpenVPNTunInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTunInterface");
    if($OpenVPNTunInterface==null){$OpenVPNTunInterface="tun0";}
    $OpenVPNCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNCertificate");

    $form[]=$tpl->field_hidden("ENABLE_SERVER",$vpn->main_array["GLOBAL"]["ENABLE_SERVER"]);
    $form[]=$tpl->field_array_hash($tpl->ArrayNics(true),"LOCAL_BIND","{openvpn_local}",openvpn_nic_key($vpn->main_array["GLOBAL"]["LOCAL_BIND"]),false,"{openvpn_local_text}");

    $form[]=$tpl->field_array_hash($TunInterfaces,"OpenVPNTunInterface","{listen_interface}",$OpenVPNTunInterface,false);

    $form[]=$tpl->field_array_hash($tpl->ArrayNics(),"IPTABLES_ETH","{openvpn_access_interface}",openvpn_nic_key($vpn->main_array["GLOBAL"]["IPTABLES_ETH"]),
        false,"{openvpn_access_interface_text}");

    $form[]=$tpl->field_numeric("LISTEN_PORT", "{listen_port}", $vpn->main_array["GLOBAL"]["LISTEN_PORT"]);
    $form[]=$tpl->field_array_hash(array("tcp"=>"TCP","udp"=>"UDP"),"LISTEN_PROTO","{protocol}",$vpn->main_array["GLOBAL"]["LISTEN_PROTO"],false);

    $form[]=$tpl->field_array_hash($sslcertificates,"OpenVPNCertificate","{certificate}",$OpenVPNCertificate,false);



    $myform=$tpl->form_outside(null, $form,null,"{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}

function section_firewall_popup(){
    $tpl=new template_admin();
    $EnableOpenVPNFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNFirewall"));
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $openvpn_firewall_ports_allow=array();
    $openvpn_firewall_ports_deny=array();
    $sql="SELECT ports FROM vpn_global_fw_rules_allow ORDER BY ports";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $openvpn_firewall_ports_allow[]=$ligne["ports"];
    }

    $sql="SELECT ports FROM vpn_global_fw_rules_deny ORDER BY ports";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $openvpn_firewall_ports_deny[]=$ligne["ports"];
    }
    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_checkbox("EnableOpenVPNFirewall","{EnableOpenVPNFirewall}",
        $EnableOpenVPNFirewall,false,"{EnableOpenVPNFirewall_explain}");
    $form[]=$tpl->field_textareacode("openvpn_firewall_ports_allow", "{ports_allow}", @implode("\n", $openvpn_firewall_ports_allow));
    $form[]=$tpl->field_textareacode("openvpn_firewall_ports_deny", "{ports_deny}", @implode("\n", $openvpn_firewall_ports_deny));
    $myform=$tpl->form_outside(null, $form,"{FIREWALL_SERVICE_OPENVPN_EXPLAIN}","{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
}

function section_infos_popup(){

    $tpl=new template_admin();
    $vpn=new openvpn();
    $tpl=new template_admin();

    $CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["CLIENT_NAT_PORT"];
    if($CLIENT_NAT_PORT==null){$CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["LISTEN_PORT"];}

    if(intval($vpn->main_array["GLOBAL"]["PROXYPORT"])<20){
        $vpn->main_array["GLOBAL"]["PROXYPORT"]=3128;
    }
    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_ipaddr("PUBLIC_IP","{public_ip_addr}",$vpn->main_array["GLOBAL"]["PUBLIC_IP"],false);
    $form[]=$tpl->field_numeric("CLIENT_NAT_PORT", "{listen_port}", $CLIENT_NAT_PORT);
    $form[]=$tpl->field_checkbox("USE_RPROXY","{reverse_proxy}",$vpn->main_array["GLOBAL"]["USE_RPROXY"],"PROXYADDR,PROXYPORT","{OPENVPN_EXPLAIN_PROXY}");
    $form[]=$tpl->field_text("PROXYADDR", "{proxy_addr}", $vpn->main_array["GLOBAL"]["PROXYADDR"]);
    $form[]=$tpl->field_numeric("PROXYPORT", "{proxy_port}", $vpn->main_array["GLOBAL"]["PROXYPORT"]);
    $myform=$tpl->form_outside(null, $form,"{openvpn_ippub_explain}","{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
}

function section_proto_popup(){
    $tpl=new template_admin();
    $OpenVPNRenegSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNRenegSec"));
    $OpenVPNUseLZO=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNUseLZO"));

    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_numeric("OpenVPNRenegSec", "{renegsec} ({seconds})", $OpenVPNRenegSec);
    //$form[]=$tpl->field_numeric("OpenVPNPing", "{OpenVPNPing} ({seconds})", $OpenVPNPing,"{OpenVPNPing_explain}");
    $form[]=$tpl->field_checkbox("OpenVPNUseLZO","{UseLZO}",$OpenVPNUseLZO,false);
    $myform=$tpl->form_outside(null, $form,null,"{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
}

function section_failover_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{failover_reconnect}","$page?section-failover-popup=yes");
}

function section_failover_popup(){
    // Reconnect / failover policy baked into every generated client .ovpn. Saved by SAVE_SERVER()
    // via SAVE_POSTs() (field names == setting keys read by openvpn.loadSettings). Changing these
    // requires rebuilding client configurations to take effect on existing clients.
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_text("OpenVPNRemote2","{secondary_server} ({ip_or_hostname})",$sock->GET_INFO("OpenVPNRemote2"));
    $form[]=$tpl->field_numeric("OpenVPNRemote2Port","{secondary_server} {port}",intval($sock->GET_INFO("OpenVPNRemote2Port")));
    $form[]=$tpl->field_text("OpenVPNRemote3","{third_server} ({ip_or_hostname})",$sock->GET_INFO("OpenVPNRemote3"));
    $form[]=$tpl->field_numeric("OpenVPNRemote3Port","{third_server} {port}",intval($sock->GET_INFO("OpenVPNRemote3Port")));
    $form[]=$tpl->field_checkbox("OpenVPNRemoteRandom","{randomize_servers} (remote-random)",intval($sock->GET_INFO("OpenVPNRemoteRandom")));
    $form[]=$tpl->field_numeric("OpenVPNConnectRetry","{connect_retry} ({seconds})",intval($sock->GET_INFO("OpenVPNConnectRetry")));
    $form[]=$tpl->field_numeric("OpenVPNConnectRetryMax","{connect_retry_max} (0={infinite})",intval($sock->GET_INFO("OpenVPNConnectRetryMax")));
    $form[]=$tpl->field_numeric("OpenVPNKeepaliveInterval","{keepalive_interval} ({seconds})",intval($sock->GET_INFO("OpenVPNKeepaliveInterval")));
    $form[]=$tpl->field_numeric("OpenVPNKeepaliveTimeout","{keepalive_timeout} ({seconds})",intval($sock->GET_INFO("OpenVPNKeepaliveTimeout")));
    $form[]=$tpl->field_text("OpenVPNResolvRetry","resolv-retry",$sock->GET_INFO("OpenVPNResolvRetry"));
    $myform=$tpl->form_outside(null, $form,"{failover_reconnect_explain}","{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
}
function section_dhcp_popup(){
    $tpl=new template_admin();
    $vpn=new openvpn();
    $OpenVPNAuthenticate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNAuthenticate");
    $OpenVPNClientCertNotRequired=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNClientCertNotRequired");
    $EnableOpenVPNEndUserPage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNEndUserPage"));
    $OpenVPNRedirectGateway=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNRedirectGateway");
    $OpenVPNClientTOClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNClientTOClient"));
    $OpenVPNBlockLocal=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNBlockLocal");
    if(trim($vpn->main_array["GLOBAL"]["IP_START"])==null){$vpn->main_array["GLOBAL"]["IP_START"]="10.8.0.0";}
    if(trim($vpn->main_array["GLOBAL"]["NETMASK"])==null){$vpn->main_array["GLOBAL"]["NETMASK"]="255.255.255.0";}

    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_ipaddr("IP_START","{from_ip_address}",$vpn->main_array["GLOBAL"]["IP_START"],false);
    $form[]=$tpl->field_ipaddr("NETMASK","{netmask}",$vpn->main_array["GLOBAL"]["NETMASK"],false);
    $form[]=$tpl->field_ipaddr("VPN_DNS_DHCP_1","{dns_server} 1",$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_1"],false);
    $form[]=$tpl->field_ipaddr("VPN_DNS_DHCP_2","{dns_server} 2",$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_2"],false);

    $form[]=$tpl->field_ipaddr("WAKEUP_IP","{wake_up_ip}",$vpn->main_array["GLOBAL"]["WAKEUP_IP"],false,"{vpn_server_wakeupip_client_explain}");

    $form[]=$tpl->field_checkbox("REMOVE_SERVER_DEFAULT_ROUTE","{remove_server_route}",
        $vpn->main_array["GLOBAL"]["REMOVE_SERVER_DEFAULT_ROUTE"],false,"{remove_server_route_vpn_explain}");

  //  $form[]=$tpl->field_checkbox("EnableOpenVPNEndUserPage","{EnableOpenVPNEndUserPage}",$EnableOpenVPNEndUserPage,false,"{EnableOpenVPNEndUserPage_explain}");
  $form[]=$tpl->field_checkbox("OpenVPNClientCertNotRequired","{OpenVPNClientCertNotRequired}",$OpenVPNClientCertNotRequired,false,"{OpenVPNClientCertNotRequired_explain}");
   $form[]=$tpl->field_checkbox("OpenVPNAuthenticate","{enable_authentication}",$OpenVPNAuthenticate,false,"{enable_authentication_vpn_explain}");

    // block-local est SUBORDONNE a redirect-gateway: openvpn/serverConfig.go ne
    // l'emet que dans la branche `if Config.RedirectGateway == 1`, donc un
    // block-local coche seul ne produit aucune directive. La dependance est
    // exprimee ici, en grisant la case dependante (4e argument), et non plus en
    // reecrivant OpenVPNRedirectGateway a 1 dans SAVE_SERVER() -- ce qui rendait
    // ce reglage IMPOSSIBLE a desactiver des que block-local etait actif (les
    // deux cases appartiennent au meme formulaire, et un field_checkbox poste
    // toujours sa valeur, 0 comme 1).
    $form[]=$tpl->field_checkbox("OpenVPNRedirectGateway","{OpenVPNRedirectGateway2}",
        $OpenVPNRedirectGateway,"OpenVPNBlockLocal","{OpenVPNRedirectGateway_explain2}");

    $form[]=$tpl->field_checkbox("OpenVPNBlockLocal","{OpenVPNBlockLocal}",
        $OpenVPNBlockLocal,false,"{OpenVPNBlockLocal_explain}");

   // $form[]=$tpl->field_checkbox("OpenVPNClientTOClient","{OpenVPNClientTOClient}",$OpenVPNClientTOClient,false,"{OpenVPNClientTOClient_explain}");



    $myform=$tpl->form_outside(null, $form,"{LOCAL_NETWORK} {SERVER_MODE_TUNE}","{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}




function SAVE_SERVER():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");


    $tpl->SAVE_POSTs();

    if(isset($_POST["openvpn_firewall_ports_allow"])) {
        $allowports = explode("\n", $_POST["openvpn_firewall_ports_allow"]);

        foreach ($allowports as $line) {
            $line = trim(strtolower($line));
            if ($line == null) {
                continue;
            }
            if (!is_numeric($line)) {
                echo "jserror:Only numeric values in firewall ports";
                die();
            };
            $line = $q->sqlite_escape_string2($line);
            $allowportarray[] = "('$line')";

        }
        $q->QUERY_SQL("DELETE FROM `vpn_global_fw_rules_allow`");
        if(count($allowportarray)>0){
            $q->QUERY_SQL("INSERT INTO `vpn_global_fw_rules_allow` (`ports`) VALUES ".@implode(",", $allowportarray));
            if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
        }
    }
    if(isset($_POST["openvpn_firewall_ports_deny"])) {
        $denyports = explode("\n", $_POST["openvpn_firewall_ports_deny"]);
        foreach ($denyports as $line) {
            $line = trim(strtolower($line));
            if ($line == null) {
                continue;
            }
            if (!is_numeric($line)) {
                echo "jserror:Only numeric values in firewall ports";
                die();
            };
            $line = $q->sqlite_escape_string2($line);
            $denyportarray[] = "('$line')";

        }

        $q->QUERY_SQL("DELETE FROM `vpn_global_fw_rules_deny`");
        if (count($denyportarray) > 0) {
            $q->QUERY_SQL("INSERT INTO `vpn_global_fw_rules_deny` (`ports`) VALUES " . @implode(",", $denyportarray));
            if (!$q->ok) {
                echo $tpl->post_error($q->mysql_error);
                return false;
            }
        }
    }

	$ini=new Bs_IniHandler();
	$ini->loadString($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaOpenVPNSettings"));
	reset($_POST);foreach ($_POST as $key=>$val){
		$ini->_params["GLOBAL"][$key]=url_decode_special_tool($val);
	}
	$sock->SaveConfigFile($ini->toString(), "ArticaOpenVPNSettings");


    $OpenVPNWizard=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNWizard")));
	foreach ($_POST as $num=>$ligne){
		$ligne=url_decode_special_tool($ligne);
		$OpenVPNWizard[$num]=$ligne;
	}
	$sock->SaveConfigFile(base64_encode(serialize($OpenVPNWizard)), "OpenVPNWizard");
    $sock->REST_API("/openvpn/service/restart");

	return true;
}

/**
 * Pose les valeurs par defaut, UNE SEULE FOIS, pour les reglages jamais definis.
 *
 * Le garde etait « if(!artica_setting_exists("<cle>")) ».
 * Il ne pouvait PAS fonctionner depuis la console : ce repertoire est en
 * 0700 root:root, or le serveur web tourne en www-data, et is_file() ne
 * distingue pas « absent » de « interdit » -- il rend false dans les deux cas,
 * sans le moindre avertissement. Le garde restait donc ouvert en permanence, et
 * chaque rendu de la carte reimposait le defaut.
 *
 * Symptome vecu (2026-09-15, assist2.artica.center) : « Faire passer le trafic
 * Internet par le VPN » impossible a desactiver. Le 0 partait pourtant bien
 * jusqu'a Valkey ; c'est le rechargement de la carte, declenche par section_js()
 * juste apres l'enregistrement, qui le remettait a 1. Les SEPT reglages
 * ci-dessous etaient figes sur leur defaut de la meme facon.
 *
 * Le garde porte desormais sur le REGLAGE, seul etat que www-data puisse
 * reellement lire. Une cle jamais posee rend "" (verifie sur appliance) ;
 * une cle posee a "0" rend "0" -- que === "" distingue, contrairement a
 * empty() qui les confondrait et ramenerait le defaut sur toute valeur nulle.
 */
function buildDefaults():bool{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $defaults=array(
        "OpenVPNInternalFragmentationAlgorithm" => 1,
        "OpenVPNPing"                           => 10,
        "OpenVPNPingRestart"                    => 120,
        "OpenVPNFragments"                      => 0,
        "OpenVPNUseLZO"                         => 1,
        "OpenVPNVerboseMode"                    => 3,
        "OpenVPNRedirectGateway"                => 1,
    );
    foreach($defaults as $key=>$value){
        if(trim((string)$sock->GET_INFO($key))!==""){continue;}
        $sock->SET_INFO($key,$value);
    }
    return true;
}