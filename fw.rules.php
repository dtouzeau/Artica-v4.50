<?php
// L'attribut s'ecrivait "widht=1%" -- coquille ET valeur non quotee contenant
// un % -- ce qui n'a donc jamais rien largi. La constante n'est plus utilisee
// que par les tableaux de mise en page restants.
define("td1prc" ,  "style='vertical-align:middle' nowrap");

// Un SECOND define() suivait ici, avec un td1prc NON QUOTE: PHP resolvait donc
// la constante deja definie et creait une constante dont le NOM etait sa
// valeur ("widht=1% style=..."). Retire.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.firewall.confirm.inc");
// Verrou de la politique Artica Meta "firewall" : regles tenues = toute la liste en lecture seule.
include_once(dirname(__FILE__)."/ressources/class.firewall.meta.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.iptables.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
// Compteurs par règle, lus auprès du daemon (lot D) : remplace
// IPTABLES_RRULES_STATUS qu'écrivait l'ancien script PHP du pare-feu. Même
// forme de tableau qu'avant ; vide si le daemon ne répond pas (page sans
// compteurs).
function fw_rules_status():array{
    static $cache=null;
    if(is_array($cache)){return $cache;}
    $cache=array();
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/rules/status"),true);
    if(!is_array($json) || empty($json["Status"]) || !is_array($json["Data"] ?? null)){return $cache;}
    $d=$json["Data"];
    foreach (($d["rules"] ?? array()) as $k=>$c){$cache[$k]=$c;}
    foreach (($d["final"] ?? array()) as $k=>$c){$cache[$k]=$c;}
    foreach (($d["router_names"] ?? array()) as $k=>$c){$cache[$k]=$c;}
    foreach (($d["log"] ?? array()) as $k=>$v){$cache["LOG"][$k]=true;}
    foreach (($d["nat"] ?? array()) as $k=>$c){$cache["NAT"][$k]=$c;}
    foreach (($d["routers"] ?? array()) as $k=>$c){$cache["ROUTER"][$k]=$c;}
    if(isset($d["crowdsec"])){$cache["CROWDSEC"]=$d["crowdsec"];}
    if(isset($d["reverseproxy"])){$cache["REVERSEPROXY"]=$d["reverseproxy"];}
    return $cache;
}
$GLOBALS["NAT_TYPE"]=fwapi_nat_types();
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Contrôle de droits côté serveur : form_outside(…,"AsFirewallManager") ne le fait que dans le navigateur.
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
// Paramètres réutilisés à des dizaines d'endroits de cette page, dans du SQL
// (WHERE eth='…'), des URL et du JavaScript (OnClick, $jsafter). Ils sont
// normalisés UNE fois ici plutôt qu'à chaque site : une interface, un
// pseudo-nom (DNAT:5, MASQ:3, ens18ens19), un nom de fonction JS, un id DOM.
fw_rules_sanitize_get();
function fw_rules_sanitize_get(){
    foreach (array("eth","search-interface") as $k){
        if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9._:-]/','',(string)$_GET[$k]);}
    }
    if(isset($_GET["function"])){$_GET["function"]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);}
    if(isset($_GET["md"])){$_GET["md"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["md"]);}
    if(isset($_GET["dir"])){$_GET["dir"]=$_GET["dir"]=="down"?"down":"up";}
    foreach (array("delete-rule-js","delete-confirm") as $k){
        if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}
    }
}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search-form"])){search_form();exit;}
if(isset($_POST["SquidFirewallOutTCP"])){squid_outgoing_save();exit;}
if(isset($_POST["SquidFirewallInTCP"])){squid_outgoing_save();exit;}
if(isset($_GET["SQUIDEnableFirewall"])){squid_enable_save();exit;}

function squid_enable_save(){
    $function=$_GET["function"];
    $SQUIDEnableFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnableFirewall"));
    if($SQUIDEnableFirewall==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnableFirewall",1);
        echo "$function();";
        return admin_tracks("Enable Firewall protection for the proxy service");
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnableFirewall",0);
    echo "$function();";
    return admin_tracks("Disable Firewall protection for the proxy service");

}


if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["enable-ruleid"])){rule_enable();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["rule-flat"])){rule_flat();exit;}
if(isset($_GET["rule-field-js"])){rule_field_js();exit;}
if(isset($_GET["rule-field-popup"])){rule_field_popup();exit;}
if(isset($_POST["rule-field-save"])){rule_field_save();exit;}
if(isset($_GET["rule-nat-js"])){rule_nat_js();exit;}
if(isset($_GET["rule-nat-popup"])){rule_nat_popup();exit;}
if(isset($_POST["rule-nat-save"])){rule_nat_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
// Pentest 2026-11-04 : explain-this-rule etait recopie sans encodage dans la page (XSS).
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE(intval($_GET["explain-this-rule"]));exit;}
if(isset($_GET["fill"])){fill_rule();exit;}
if(isset($_GET["fillup"])){fill_up();exit;}
if(isset($_GET["ipv6-enable-js"])){ipv6_enable_js();exit;}
if(isset($_GET["squid-outgoing-js"])){squid_outgoing_js();exit;}
if(isset($_GET["squid-outgoing-popup"])){squid_outgoing_popup();exit;}
if(isset($_GET["squid-incoming-js"])){squid_incoming_js();exit;}
if(isset($_GET["squid-incoming-popup"])){squid_incoming_popup();exit;}
page();


function page():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $html=$tpl->page_header("{administrate_your_firewall}",ico_firewall, "<strong>{firewall_rules}</strong>: <i>{firewall_about}</i>","$page?search-form=yes",
        "firewall","progress-firehol-restart",false,"search-firewall-function");
    // Modification du pare-feu en attente de confirmation (fwhistory). Pas dans
    // search_form() : avec la recherche active, page_header passe sa sortie au
    // bloc de recherche comme options, et le bandeau n'arrivait jamais dans la page.
    $html=firewall_confirm_banner().firewall_meta_banner("rules",$tpl).$html;

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Web Application firewall {status}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function search_form():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $NICS=LOAD_NICS();
    // Valeurs du choix d'interface : celles que table() attend dans ?eth=
    // (nom d'interface, MASQ:<id>, <DNAT|SNAT|…>:<id>, <de>2<vers> pour un connecteur).
    $opts=array();
    $opts[]=array("","{all_interfaces}");
    foreach ($NICS as $yinter=>$line){
        $opts[]=array($yinter,$line);
    }

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results=$q->QUERY_SQL("SELECT * FROM firehol_masquerade WHERE enabled='1' ORDER BY nic");
    foreach ($results as $ligne){
        $znic=new system_nic($ligne["nic"]);
        if($znic->enabled==0){continue;}
        $opts[]=array("MASQ:".intval($ligne["ID"]),"MASQUERADE $znic->NICNAME ({$ligne["nic"]})");
    }

    $sNAT_TYPE=fwapi_nat_codes();
    $results=$q->QUERY_SQL("SELECT * FROM pnic_nat WHERE enabled=1 ORDER BY ID DESC");
    foreach ($results as $ligne) {
        $NAT_TYPE = intval($ligne["NAT_TYPE"]);
        $dstaddr = $ligne["dstaddr"];
        $dstaddrport = intval($ligne["dstaddrport"]);
        if($NAT_TYPE==3){$dstaddrport=0;}
        if ($dstaddrport > 0) {$dstaddr = "$dstaddr:$dstaddrport";}
        $nic=$NICS[$ligne["nic"]] ?? $ligne["nic"];
        $opts[]=array($sNAT_TYPE[$NAT_TYPE].":".intval($ligne["ID"]),"$nic {$sNAT_TYPE[$NAT_TYPE]} &rarr; $dstaddr");
    }

    if($q->TABLE_EXISTS("pnic_bridges")){
        $results = $q->QUERY_SQL("SELECT * FROM `pnic_bridges` WHERE `enabled`=1 AND NoFirewall=0");
        foreach ($results as $ligne){
            $nic_from=$ligne["nic_from"];$nic_to=$ligne["nic_to"];
            if($nic_from==null){continue;}
            $opts[]=array("{$nic_from}2{$nic_to}","{connector}: $nic_from &rarr; $nic_to");
        }
    }

    $nicOptions=array();
    foreach($opts as $o){
        $v=htmlspecialchars($o[0],ENT_QUOTES);
        $nicOptions[]="<option value='$v'>$o[1]</option>";
    }
    $statusOptions=array(""=>"{fwr_all_statuses}","enabled"=>"{enabled}","disabled"=>"{disabled}",
        "unused"=>"{fw_unused_30d}","shadowed"=>"{fw_shadowed}");
    $stOptions=array();
    foreach($statusOptions as $k=>$v){$stOptions[]="<option value='$k'>$v</option>";}

    $html=array();
    $html[]="<div class='ak ak-tools'>";
    $html[]="<label class='ak-search fwr-q'><i class='fas fa-magnifying-glass'></i>";
    $html[]="<input type='text' id='fwr-q' placeholder=\"{fwr_search_placeholder}\" OnKeyPress=\"if(checkEnter(event)){FwrApply();}\"></label>";
    $html[]="<select id='fwr-eth' class='ak-sel fwr-sel' OnChange='FwrApply()'>".@implode("",$nicOptions)."</select>";
    $html[]="<select id='fwr-st' class='ak-sel fwr-sel' OnChange='FwrApply()'>".@implode("",$stOptions)."</select>";
    $html[]="<span class='ak-grow'></span>";
    $html[]="<button type='button' class='ak-btn ak-btn-primary' OnClick='FwrApply()'>{fwr_apply}</button>";
    $html[]="<button type='button' class='ak-btn fwr-cog' title=\"{firewall_parameters}\" OnClick=\"LoadAjax('MainContent','fw.settings.php')\"><i class='fas fa-sliders'></i></button>";
    $html[]="</div>";
    $html[]="<div id='fwr-host'></div>";

    // Une seule fonction de rechargement, lue par les modales (&function=FwrApply)
    // et par les mutations : elle relit les filtres, donc les conserve.
    $script="<script>
function FwrApply(){
    var g=function(id){var e=document.getElementById(id);return e?e.value:'';};
    LoadAjax('fwr-host','$page?search='+encodeURIComponent(g('fwr-q'))+'&eth='+encodeURIComponent(g('fwr-eth'))+'&filter='+encodeURIComponent(g('fwr-st'))+'&function=FwrApply');
}
FwrApply();
</script>";
	echo fwr_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
	return true;
}

// ── Kit fwr-* (maquette stitch_firewall_rules_manager) ───────────────────────
function fwr_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

function fwr_style():string{
    if(isset($GLOBALS["FWR_STYLE_SENT"])){return "";}
    $GLOBALS["FWR_STYLE_SENT"]=true;
    $ac=fwr_accents();
    $a=$ac["accent"];$ad=$ac["accent_dark"];$as=$ac["accent_soft"];$al=$ac["accent_line"];
    $c=array();
    // Barre de filtres : kit ak- (ak-tools, ak-search, ak-btn) ; restent la largeur et le select.
    $c[]=".fwr-q{flex:0 1 400px;min-width:220px}";
    $c[]=".fwr-sel{max-width:260px}";
    $c[]=".fwr-cog{width:34px;padding:0;font-size:15px}";
    $c[]=".fwr-skin,.fwr-skin *{box-sizing:border-box}";
    // Tableau
    $c[]=".fwr-skin .awc-card{border-radius:12px;border-color:#e2e8f0}";
    $c[]=".fwr-skin .awc-tbl th{font-size:12px;letter-spacing:.06em;color:#64748b;padding:14px 12px}";
    $c[]=".fwr-skin .awc-tbl td{padding:16px 12px;vertical-align:middle;border-bottom:1px solid #f1f5f9}";
    $c[]=".fwr-skin .awc-tbl tbody tr:hover td{background:#f8fafc}";
    $c[]=".fwr-skin .awc-tbl td:first-child{font-family:'JetBrains Mono',ui-monospace,monospace;color:#64748b;white-space:nowrap}";
    $c[]=".fwr-skin .awc-tbl td:nth-child(5),.fwr-skin .awc-tbl td:nth-child(6){font-family:'JetBrains Mono',ui-monospace,monospace;text-align:right;white-space:nowrap;color:#1e293b}";
    $c[]=".fwr-skin .awc-tbl td:nth-child(2){font-size:13px;color:#64748b;line-height:1.5;min-width:280px}";
    // td_href() rend un <span> souligné en style en ligne, pas un <a>.
    $c[]=".fwr-skin .awc-tbl td:nth-child(2) span[id\$='-rname']{font-size:15px}";
    $c[]=".fwr-skin .awc-tbl td:nth-child(2) span[style*='border-bottom']{border-bottom:0 !important}";
    $c[]=".fwr-skin .awc-tbl td:nth-child(2) span[style*='border-bottom']:hover span[id\$='-rname']{text-decoration:underline}";
    $c[]=".fwr-skin .awc-tbl td:nth-child(2) strong{color:#0f172a}";
    $c[]=".fwr-skin .awc-tbl td:nth-child(2) .text-success{color:$ad !important}";
    // Interface
    $c[]=".fwr-nic{font-family:inherit}.fwr-nic svg,.fwr-nic i{color:#94a3b8}";
    // Pastilles : statut (colonne 4) et étiquettes génériques
    $c[]=".fwr-skin .label{display:inline-block;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:600;line-height:1.5;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;text-transform:none}";
    $c[]=".fwr-skin .label-primary{background:$as;color:$ad;border-color:$al}";
    $c[]=".fwr-skin .label-danger{background:#fff1f2;color:#be123c;border-color:#fecdd3}";
    $c[]=".fwr-skin .label-warning{background:#fffbeb;color:#b45309;border-color:#fde68a}";
    $c[]=".fwr-skin .label-info{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}";
    $c[]=".fwr-skin .label-default{background:#f1f5f9;color:#64748b;border-color:#e2e8f0}";
    $c[]=".fwr-skin td:nth-child(4) .label-primary:before{content:'';display:inline-block;width:6px;height:6px;border-radius:50%;background:currentColor;margin-right:6px;vertical-align:middle}";
    $c[]=".fwr-obs{display:block;margin-top:6px;font-size:12px;font-style:italic;color:#94a3b8;white-space:normal;line-height:1.4}";
    $c[]=".fwr-obs .label{font-style:normal}";
    // Direction (colonne 7) : petites boîtes rectangulaires
    $c[]=".fwr-skin td:nth-child(7){white-space:nowrap}";
    $c[]=".fwr-skin td:nth-child(7) .label{border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;letter-spacing:.03em}";
    $c[]=".fwr-skin td:nth-child(7) .label.fwr-out{background:#fffbeb;color:#b45309;border-color:#fde68a}";
    // Interrupteur : kit ak-sw ; reste la variante lecture seule
    $c[]=".ak-sw.fwr-ro{cursor:default;opacity:.75}";
    // Priorité et suppression : boutons carrés, révélés au survol de la ligne
    $c[]=".fwr-skin td:nth-child(9),.fwr-skin td:nth-child(10){white-space:nowrap}";
    $c[]=".fwr-skin .btn-bitbucket{width:32px;height:32px;padding:0;border:1px solid #e2e8f0;border-radius:6px;background:#fff;opacity:.35;transition:opacity .15s}";
    $c[]=".fwr-skin tr:hover .btn-bitbucket{opacity:1}";
    $c[]=".fwr-skin td:nth-child(10) .btn-bitbucket{border-color:#fecdd3;background:#fff1f2}";
    $c[]=".fwr-skin .awc-foot{padding:14px 20px;background:#f8fafc;font-size:13px;color:#64748b;gap:16px;flex-wrap:wrap}";
    $c[]=".fwr-skin .awc-foot strong{color:#0f172a}";
    $c[]=".fwr-skin .fw-section-row td{background:#f8fafc !important;color:#334155;font-size:13px;text-transform:uppercase;letter-spacing:.05em}";
    return "<style>".@implode("\n",$c)."</style>";
}

// Interrupteur : $js est appelé après la bascule visuelle ; en cas de refus le
// répondeur rebascule (fwr_switch_revert_js()).
function fwr_switch(bool $on,string $js="",string $id=""):string{
    $cls="ak-sw".($on?" on":"");
    if($js==""){
        return "<span class='$cls fwr-ro'></span>";
    }
    $idAttr=$id!=""?" id='$id'":"";
    return "<button type='button' role='switch' class='$cls'$idAttr OnClick=\"this.classList.toggle('on');$js\"></button>";
}

function fwr_switch_revert_js(string $id):string{
    return "var _sw=document.getElementById('$id');if(_sw){_sw.classList.toggle('on');}";
}

function fwr_nic(string $inner):string{
    return "<span class='ak-chip fwr-nic'><i class='".ico_nic."'></i>$inner</span>";
}

function squid_outgoing_js():bool{
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog2("{APP_SQUID}: {outgoing}","$page?squid-outgoing-popup=yes&function=$function");
}
function squid_incoming_js():bool{
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog2("{APP_SQUID}: {incoming}","$page?squid-incoming-popup=yes&function=$function");
}

function squid_outgoing_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(isset($_POST["SquidFirewallOutTCP"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidFirewallOutTCP",base64_encode($_POST["SquidFirewallOutTCP"]));
    }

    if(isset($_POST["SquidFirewallOutUDP"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidFirewallOutUDP",base64_encode($_POST["SquidFirewallOutUDP"]));
    }

    if(isset($_POST["SquidFirewallInTCP"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidFirewallInTCP",base64_encode($_POST["SquidFirewallInTCP"]));
    }
    return admin_tracks_post("Save dedicated rules for proxy service");
}

function squid_outgoing_datas():array{
    $SquidFirewallOutUDP=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFirewallOutUDP"));
    $SquidFirewallOutTCP=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFirewallOutTCP"));
    $OutUDP=explode("\n",$SquidFirewallOutUDP);
    $OutTCP=explode("\n",$SquidFirewallOutTCP);
    $OutUDPArray=array();
    $OutTCPArray=array();
    foreach ($OutUDP as $line){
        $line=trim($line);
        if(!preg_match("#^(.+?):([0-9]+)#",$line,$m)){
            continue;
        }
        $OutUDPArray[]=$line;
    }
    if(count($OutUDPArray)==0){
        $OutUDPArray[]="*:53";
    }

    foreach ($OutTCP as $line){
        $line=trim($line);
        if(!preg_match("#^(.+?):([0-9]+)#",$line,$m)){
            continue;
        }
        $OutTCPArray[]=$line;
    }
    if(count($OutTCPArray)==0){
        $OutTCPArray[]="*:80";
        $OutTCPArray[]="*:443";
    }
    return array($OutUDPArray,$OutTCPArray);
}
function squid_outgoing_popup():bool{
    $tpl        = new template_admin();
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}

    if(strlen($function)>2){
        $function="$function()";
    }
    list($OutUDPArray,$OutTCPArray)=squid_outgoing_datas();

    $form[]=$tpl->field_textareacode("SquidFirewallOutTCP","TCP {allow}",@implode("\n",$OutTCPArray));
    $form[]=$tpl->field_textareacode("SquidFirewallOutUDP","UDP {allow}",@implode("\n",$OutUDPArray));
    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance2.close();$function");
    return true;
}

function squid_incoming_data():array{
    $SquidFirewallInTCP=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidFirewallInTCP"));
    $InTCP=explode("\n",$SquidFirewallInTCP);
    $InTCPArray=array();
    $IP=new IP();
    foreach ($InTCP as $line){
        $line=trim($line);
        if(!$IP->IsACDIROrIsValid($line)){
            continue;
        }
        $InTCPArray[]=$line;
    }
    if(count($InTCPArray)==0){
        $InTCPArray[] = "192.168.0.0/16";
        $InTCPArray[] = "10.0.0.0/8";
        $InTCPArray[] = "172.16.0.0/12";
    }
    return $InTCPArray;
}

function squid_incoming_popup():bool{
    $tpl        = new template_admin();
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $InTCPArray=squid_incoming_data();
    if(strlen($function)>2){
        $function="$function()";
    }
    $form[]=$tpl->field_textareacode("SquidFirewallInTCP","TCP {allow}",@implode("\n",$InTCPArray));

    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance2.close();$function");
    return true;
}


// Active le filtrage IPv6 (version à confirmer, comme toute écriture).
function ipv6_enable_js():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}")."');";return false;}
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    if(firewall_meta_holds_setting("FirewallIPv6Filtering")){(new template_admin())->js_error("{firewall_meta_locked}");return false;}
    $e=fwapi_error(fwapi_send("PUT","ipv6",array("filtering"=>1)));
    if($e!=""){echo "alert('".str_replace("'","\\'",$tpl->javascript_parse_text($e))."');";return false;}
    admin_tracks("Enabled IPv6 filtering of the firewall");
    if($function!=""){echo "$function();";}
    return true;
}

function fill_rule(){
    header("content-type: application/x-javascript");
    $ID     = intval($_GET["fill"]);
    $page   = CurrentPageName();
    $q      = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne  = urlencode(base64_encode(serialize($q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ID'"))));
    $f[]="if(document.getElementById('fw-$ID-rname')){";
    $f[]="  LoadAjaxSilent('fw-$ID-rname','$page?fillup=$ID&section=name&ligne=$ligne');";
    $f[]="}";
    $f[]="if(document.getElementById('fw-$ID-expl')){";
    $f[]="  LoadAjaxSilent('fw-$ID-expl','$page?fillup=$ID&section=expl&ligne=$ligne');";
    $f[]="}";
    $f[]="if(document.getElementById('fw-$ID-nic')){";
    $f[]="  LoadAjaxSilent('fw-$ID-nic','$page?fillup=$ID&section=nic&ligne=$ligne');";
    $f[]="}";
    $f[]="if(document.getElementById('fw-$ID-jact')){";
    $f[]="  LoadAjaxSilent('fw-$ID-jact','$page?fillup=$ID&section=act&ligne=$ligne');";
    $f[]="}";


    echo @implode("\n",$f);

}
function fill_up(){
    $ID     = intval($_GET["fillup"]);
    $section= $_GET["section"];
    $tpl    = new template_admin();
    // Pentest 2026-11-04 : ligne etait recopie sans encodage dans la page (XSS).
    // La ligne venait du client (base64 serialize) : relue en base par son ID.
    $qfill  = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne  = $qfill->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ID'");
    if(!is_array($ligne)){$ligne=array();}
    if(!isset($ligne["rulename"])){$ligne["rulename"]="";}
    if(!isset($ligne["eth"])){$ligne["eth"]="";}

    if($section=="name"){
        echo $ligne["rulename"];
        return;
    }
    if($section=="nic"){
        $NICS               = LOAD_NICS(true);
        $NICS_TEXT          = $NICS[$ligne["eth"]];
        echo $NICS_TEXT;
        return;
    }


    if($section=="expl"){
        echo $tpl->_ENGINE_parse_body(EXPLAIN_THIS_RULE($ID));
        return;
    }
    if($section=="act"){
        echo rule_action_status($ligne);
    }
}

function rule_enable(){
	header("content-type: application/x-javascript");
	$ID     = intval($_GET["enable-ruleid"]);
	$tpl    = new template_admin();
	if(firewall_meta_holds("rules")){echo fwr_switch_revert_js("fwr-sw-$ID")."\n";$tpl->js_error("{firewall_meta_locked}");return;}
	$rule   = fwapi_data(fwapi_get("rules/$ID"));
	if(!is_array($rule)){$tpl->js_error("{fw_rule_not_found}");return;}
	$enabled=intval($rule["enabled"])==1?0:1;
	$e=fwapi_error(fwapi_send("POST","rules/$ID/enable",array("enabled"=>$enabled)));
	if($e!=""){
	    // L'interrupteur a déjà basculé côté navigateur : on le remet.
	    echo fwr_switch_revert_js("fwr-sw-$ID")."\n";
	    $tpl->js_error($e);
	    // Règle expirée : ouvrir sa fenêtre pour choisir une nouvelle durée.
	    if(strpos($e,"{fw_rule_expired}")!==false){echo "\nLoadjs('fw.rules.php?ruleid-js=$ID');";}
	}
}

function rule_move_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(firewall_meta_holds("rules")){$tpl->js_error("{firewall_meta_locked}");return;}
	$ID=intval($_GET["id"]);
	$dir=$_GET["dir"]=="down"?"down":"up";
	$e=fwapi_error(fwapi_send("POST","rules/$ID/move",array("dir"=>$dir)));
	if($e!=""){$tpl->js_error($e);return;}
	// Le nouvel ordre n'apparaissait qu'au rechargement suivant.
	echo "if(typeof FwrApply==='function'){FwrApply();}";
}

function delete_js(){
	// Pentest 2026-11-04 : delete-rule-js etait concatene dans la requete.
	$ID=intval($_GET["delete-rule-js"]);
	if(firewall_meta_holds("rules")){firewall_meta_refuse_js();return;}
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
    $md=$_GET["md"];
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM iptables_main WHERE ID='$ID'");
	$tpl->js_dialog_confirm("{delete} {$ligne["rulename"]}", "$page?delete-confirm=$ID&md=$md");
}

function delete_confirm(){
    $md=$_GET["md"];
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$t=time();
	// Pentest 2026-11-04 : delete-confirm etait concatene dans la requete.
	$ID=intval($_GET["delete-confirm"]);
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM iptables_main WHERE ID='$ID'");
	$delete_firewall_rule_ask=$tpl->_ENGINE_parse_body("{delete_firewall_rule_ask}");
	$delete_firewall_rule_ask=str_replace("%RULE", $ligne["rulename"], $delete_firewall_rule_ask);
	
	$html[]="
<div class=row>
	<div class=\"alert alert-danger\">$delete_firewall_rule_ask</div>
	<div style='text-align:right;margin-top:20px'><button class='btn btn-danger btn-lg' type='button' 
	OnClick=\"Remove$t()\">{yes_delete_it}</button></div>
</div>
<script>";
	$html[]="var xPost$t= function (obj) {";
    $html[]="var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	DialogConfirm.close();
	$('#$md').remove();
}

function Remove$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete-remove', '$ID');
	XHR.sendAndLoad('$page', 'POST',xPost$t);
}
</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function delete_remove(){
	if(firewall_meta_holds("rules")){firewall_meta_refuse_text();return;}
	$ID=intval($_POST["delete-remove"]);
    $iptables=new iptables();
    $iptables->delete_rule($ID);
}

function rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-popup"]);
	// patch_firewall_tables() removed: its body is empty (class.patch.tables.fw.inc);
	// firewall.db schema is provisioned by the daemon's afirewall.CheckTables().
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$EnablenDPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPI"));
    if($ID>0) {
        $ligne = $q->mysqli_fetch_array("SELECT rulename,accepttype,isClient FROM iptables_main WHERE ID='$ID'");
        if(preg_match("#<br><small>(.+?)<\/small>#",$ligne["rulename"],$re)){
            $ligne["rulename"]=str_replace("<br><small>{$re[1]}</small>","",$ligne["rulename"]);
        }

        $rulename=$ligne["rulename"];
        if(strlen($rulename)>60) {
            $rulename = substr($rulename, 0, 59)."...";
        }
        $title="{rule}: $rulename";
    }
    $function=$_GET["function"];

    if($ID==0){
        $title="{new_rule} {$_GET["eth"]}";
        if(preg_match("#^([a-z]+[0-9]+)2([a-z]+[0-9]+)$#",$_GET["eth"],$re)) {
            $title = "{new_rule}: {$re[1]} " . $tpl->icon_arrow_right() . " {$re[2]}";
        }

        if(preg_match("#NAT:([0-9]+)#",$_GET["eth"],$re)){
            $NAT_TYPE=$GLOBALS["NAT_TYPE"];
            $ruleid=$re[1];
            $ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$ruleid'");
            $NAT_TYPE_TEXT=$NAT_TYPE[$ligne["NAT_TYPE"]];
            $title = "{new_rule}: $NAT_TYPE_TEXT";
        }
        if(preg_match("#MASQ:([0-9]+)#",$_GET["eth"],$re)){
            $ruleid=$re[1];
            $ligne=$q->mysqli_fetch_array("SELECT * FROM firehol_masquerade WHERE ID='$ruleid'");
            $interface=$ligne["nic"];
            $title = "{new_rule}: Masquerade $interface";
        }

    }
	
	$array[$title]="$page?rule-settings=$ID&eth={$_GET["eth"]}&function=$function";
	if($ID>0){
		
		$array["{firewall_services}"]="fw.rules.services.php?rule-id=$ID&direction=0&eth={$_GET["eth"]}&function=$function";
		if($ligne["isClient"]==0){$array["{inbound_object}"]="fw.rules.objects.php?rule-id=$ID&direction=0&eth={$_GET["eth"]}&function=$function";}
		$array["{outbound_object}"]="fw.rules.objects.php?rule-id=$ID&direction=1&eth={$_GET["eth"]}&function=$function";

		if($EnablenDPI==1){
            $array["{APP_NDPI}"]="fw.rules.ndpi.php?rule-id=$ID&eth={$_GET["eth"]}&function=$function";
        }
		$array["{time_restriction}"]="fw.rules.time.php?rule-id=$ID&eth={$_GET["eth"]}&function=$function";
		$array["{options}"]="fw.rules.options.php?rule-id=$ID&eth={$_GET["eth"]}&function=$function";
		
	}
	
	if($ligne["accepttype"]=="MARK"){unset($array["{firewall_services}"]);}
	
	echo $tpl->tabs_default($array);
	
}
function rule_save(){
    $tpl=new template_admin();
    if(firewall_meta_holds("rules")){echo firewall_meta_refuse_post();return;}
    $tpl->CLEAN_POST_XSS();
	$ID=intval($_POST["rule-save"]);
	// Seuls les champs présents dans le formulaire sont envoyés : en mise à jour,
	// le daemon garde la valeur des autres (règles de flux IP, de masquerade).
	$map=array("rulename"=>"rulename","eth"=>"eth","service"=>"service","accepttype"=>"accepttype",
	           "ForwardTo"=>"forwardto","description"=>"description","section"=>"section","rate_unit"=>"rate_unit",
	           "family"=>"family");
	$ints=array("enabled"=>"enabled","jlog"=>"jlog","isClient"=>"isclient","mark_balance"=>"mark_balance",
	            "conn_limit"=>"conn_limit","rate_limit"=>"rate_limit");
	$data=array();
	foreach($map as $post=>$json){
	    if(isset($_POST[$post])){$data[$json]=(string)$_POST[$post];}
	}
	foreach($ints as $post=>$json){
	    if(isset($_POST[$post])){$data[$json]=intval($_POST[$post]);}
	}
	if(isset($data["rulename"])){$data["rulename"]=url_decode_special_tool($data["rulename"]);}
	foreach(array("description","section") as $k){if(isset($data[$k])){$data[$k]=url_decode_special_tool($data[$k]);}}
	// Les limites n'existent que pour Accept : une autre action les remet à zéro
	// (le daemon refuserait sinon le changement d'action).
	if(isset($data["accepttype"]) && $data["accepttype"]!="ACCEPT"){$data["conn_limit"]=0;$data["rate_limit"]=0;}
	if(isset($_POST["expires_in"])){
	    // 0 = garder l'échéance (création : permanente), -1 = rendre permanente.
	    $n=intval($_POST["expires_in"]);
	    if($n>0){
	        $data["expires"]=time()+$n;
	        // Nouvelle durée sur une règle EXPIRÉE : c'est une réactivation.
	        // Sinon la règle restait désactivée sous un badge « expire dans… ».
	        if($ID>0){
	            $cur=fwapi_data(fwapi_get("rules/$ID"));
	            if(is_array($cur) && !empty($cur["expired"])){$data["enabled"]=1;}
	        }
	    }
	    elseif($n==-1 || $ID==0){$data["expires"]=0;}
	}
	if($ID==0){
	    $resp=fwapi_send("POST","rules",$data);
	}else{
	    $resp=fwapi_send("PUT","rules/$ID",$data);
	}
	$e=fwapi_error($resp);
	if($e!=""){echo $tpl->post_error($e);}
}


function rule_settings(){
	$tpl=new template_admin();
	$ID=intval($_GET["rule-settings"]);
	// Règle existante : vue « flat », chaque ligne ouvre sa modale. La création
	// garde le formulaire complet (tous les champs sont nécessaires d'un coup).
	if($ID>0){
	    $page=CurrentPageName();
	    echo "<div id='fw-rule-flat-$ID'></div><script>LoadAjaxSilent('fw-rule-flat-$ID','$page?rule-flat=$ID');</script>";
	    return;
	}
	// Creation d'une regle : impossible tant que les regles sont tenues par Artica Meta.
	if(firewall_meta_holds("rules")){echo firewall_meta_banner("rules",$tpl);return;}
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

    $CheckActions["DROP"]="{drop}";
    $CheckActions["ACCEPT"]="{accept}";
    $CheckActions["TPROXY"]="{tproxy_method}";
    $CheckActions["MARK"]="{MARK_ITEM}";
    $CheckActions["NEXTHOPE"]="{forward_traffic}";
    $CheckActions["MIRROR"]="{duplicate_traffic}";
    $CheckActions["LOG"]="{fw_action_log}";
    $CheckActions["RETURN"]="{fw_action_return}";
    // Action « envoyer par un lien » : proposée dès qu'un lien existe.
    $LINKS=array();
    $lbList=fwapi_data(fwapi_get("linkbalance"));
    if(is_array($lbList)){
        foreach($lbList as $lb){
            $LINKS[intval($lb["id"])]=htmlspecialchars((string)$lb["interface"]).(intval($lb["enabled"])==1?"":" ({disabled})");
        }
    }
    if(count($LINKS)>0){$CheckActions["lINK"]="{fw_action_link}";}


	$btname="{add}";
	$FisClient=true;
	$NoAction=false;
    $NoRuleName=false;
    $NoJlog=false;
	$MOD                        = null;
    $function=$_GET["function"];
    $outgoing_rule_fw_explain="{outgoing_rule_fw_explain}";
    if($function==null){$function="blur";}
    $eth=$_GET["eth"];
	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ID'");
		$eth=$ligne["eth"];
        if(preg_match("#<br><small>(.+?)<\/small>#",$ligne["rulename"],$re)){
            $outgoing_rule_fw_explain=$re[1];
            $ligne["rulename"]=str_replace("<br><small>{$re[1]}</small>","",$ligne["rulename"]);
            $MOD=$ligne["MOD"];
        }

		$title="{rule} $ID) $eth::{$ligne["rulename"]}";
		$btname="{apply}";
		$_GET["eth"]=null;
	}

    $NICS=LOAD_NICS(true);
	ksort($NICS);
	$sql="SELECT server_port,service FROM firehol_services_def WHERE enabled=1 ORDER by service";
	$results = $q->QUERY_SQL($sql);
    $SERVICES["all"]="{all}";
    $SERVICES["RustDesk"]="RustDesk";
	
	foreach ($results as $index=>$ligne2){
		$ligne2["server_port"]=str_replace(" ", ",", $ligne2["server_port"]);
		$lenght=strlen($ligne2["server_port"]);
		if($lenght>50){$ligne2["server_port"]=substr($ligne2["server_port"], 0,47)."...";}
		$SERVICES[$ligne2["service"]]=$ligne2["service"]." ".$ligne2["server_port"];
	}
	
	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}


	if($ID==0){
	    $BootstrapDialog="dialogInstance1.close();$function();";
	}else{
        $BootstrapDialog="Loadjs('fw.rules.php?fill=$ID');";
    }
	
	$tpl->field_hidden("rule-save", $ID);
    if(preg_match("#^([a-z]+[0-9]+)2([a-z]+[0-9]+)$#",$eth,$re)){
        $FisClient=false;
        $outgoing_rule_fw_explain=null;
        $title="{$re[1]} ".$tpl->icon_arrow_right()." {$re[2]}";
    }

    if(preg_match("#NAT:([0-9]+)#",$eth,$re)){
        $FisClient=false;
        $outgoing_rule_fw_explain=null;
        $CheckActions=array();
        $CheckActions["DROP"]="{drop}";
        $CheckActions["ACCEPT"]="{accept}";

        $ruleid=$re[1];
        $NAT_TYPE=$GLOBALS["NAT_TYPE"];
        $ligne2=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$ruleid'");
        $NAT_TYPE_TEXT=$NAT_TYPE[$ligne2["NAT_TYPE"]];
        $dstaddr=$ligne2["dstaddr"];
        $dstaddrport=intval($ligne2["dstaddrport"]);
        if($ligne2["NAT_TYPE"]==3){$dstaddrport=0;}
        if(intval($dstaddrport)>0){$dstaddr="$dstaddr:$dstaddrport";}
        // Comparait le TABLEAU des libellés ($NAT_TYPE) à 2 et à 1 : toujours
        // faux pour 2, et pour 1 le port était ajouté une seconde fois.
        if(intval($ligne2["NAT_TYPE"])==2){$dstaddr="0.0.0.0:$dstaddrport";}

        $title="$NAT_TYPE_TEXT ".$tpl->icon_arrow_right()." $dstaddr";
    }

    if(preg_match("#MASQ:([0-9]+)#",$eth,$re)){
        $FisClient=false;
        $outgoing_rule_fw_explain=null;
        $NoAction=true;
        $ruleid=$re[1];
        $ligne2=$q->mysqli_fetch_array("SELECT * FROM firehol_masquerade WHERE ID='$ruleid'");
        $Ifname=$NICS[$ligne2["nic"]];
        $title="$Ifname ".$tpl->icon_arrow_right()." Masquerade";

    }

    if($MOD=="IPFEED"){
        $FisClient=false;
        $NoRuleName=true;
        $NoAction=true;
        $NoJlog=true;
    }

    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    if($FisClient){$form[]=$tpl->field_checkbox("isClient","{outgoing_rule}",$ligne["isClient"]);}
	if(!$NoRuleName){$form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"]);}
	if(!$NoRuleName){
	    $form[]=$tpl->field_textarea_normal("description","{description}",$ligne["description"] ?? "","100%","70px");
	    $form[]=$tpl->field_text("section","{section}",$ligne["section"] ?? "",false,"{fw_rule_section_explain}");
	}
	// Famille IP (lot 6a) : pas de NAT/masquerade en IPv6.
	$famEth=(string)($ligne["eth"] ?? ($_GET["eth"] ?? ""));
	if(!$NoRuleName && !preg_match('#^([A-Z]NAT|MASQ):[0-9]+$#',$famEth)){
	    $form[]=$tpl->field_array_hash(array("both"=>"{fw_family_both}","ipv4"=>"{fw_family_ipv4}","ipv6"=>"{fw_family_ipv6}"),
	        "family","{fw_family}",(string)($ligne["family"] ?? "both"),false);
	}

    if($ID>0){
        if($ligne["accepttype"]=="MIRROR"){
            $form[]=$tpl->field_ipaddr("ForwardTo","{redirect_nat}",$ligne["ForwardTo"]);
        }
    }

    // Limites de connexions : règles Accept seulement (refusé ailleurs par le daemon).
    $curAction=(string)($ligne["accepttype"] ?? "ACCEPT");
    // Pas de limite sur une règle NAT/masquerade (préfixe en table nat).
    $limEth=(string)($ligne["eth"] ?? ($_GET["eth"] ?? ""));
    if(!$NoAction && ($ID==0 || $curAction=="ACCEPT") && !preg_match('#^([A-Z]NAT|MASQ):[0-9]+$#',$limEth)){
        $form[]=$tpl->field_section("{fw_limits}","{fw_limits_explain}");
        $form[]=$tpl->field_numeric("conn_limit","{fw_conn_limit}",intval($ligne["conn_limit"] ?? 0),"{fw_conn_limit_explain}");
        $form[]=$tpl->field_numeric("rate_limit","{fw_rate_limit}",intval($ligne["rate_limit"] ?? 0),"{fw_rate_limit_explain}");
        $form[]=$tpl->field_array_hash(array("second"=>"{per_second}","minute"=>"{per_minute}"),"rate_unit","{unit}",($ligne["rate_unit"] ?? "")=="minute"?"minute":"second",false);
    }
    if($MOD!="IPFEED"){
        // Règle temporaire : désactivée par le daemon à l'échéance.
        $expOpts=array("0"=>"{never}","3600"=>"1 {hour}","14400"=>"4 {hours}","28800"=>"8 {hours}",
            "86400"=>"1 {day}","604800"=>"7 {days}","2592000"=>"30 {days}");
        $curExp=intval($ligne["expires"] ?? 0);
        $expExplain="{fw_rule_expires_explain}";
        if($curExp>0){
            $expOpts=array("0"=>"{fw_rule_expires_keep}","-1"=>"{fw_rule_make_permanent}")+array_slice($expOpts,1,null,true);
            $expExplain="{fw_rule_expires_current}: ".date("Y-m-d H:i",$curExp);
        }
        $form[]=$tpl->field_array_hash($expOpts,"expires_in","{fw_rule_expires}","0",false,$expExplain);
    }

    if($_GET["eth"]==null) {
        $form[] = $tpl->field_array_hash($NICS, "eth", "{interface}", $ligne["eth"], false);
    }else{
        $form[] = $tpl->field_hidden("eth", $_GET["eth"]);
    }
	$form[]=$tpl->field_array_hash($SERVICES,"service","{service2}",$ligne["service"]);


	if(!$NoAction) {
        $form[] = $tpl->field_array_hash($CheckActions, "accepttype",
            "{action}",$ligne["accepttype"], true);
    }
    if(!$NoAction && count($LINKS)>0){
        $form[]=$tpl->field_array_hash($LINKS,"mark_balance","{fw_link_target}",intval($ligne["MARK_BALANCE"] ?? 0),false,"{fw_link_target_explain}");
    }
	if(!$NoJlog){$form[]=$tpl->field_checkbox("jlog","{log_all_events}",$ligne["jlog"]);}


	
	echo $tpl->form_outside($title,@implode("\n", $form),$outgoing_rule_fw_explain,$btname,"$BootstrapDialog","AsFirewallManager");
    // Sections existantes proposées à la saisie (datalist).
    $sections=fwapi_data(fwapi_get("rules/sections"));
    if(is_array($sections) && count($sections)>0){
        $opts="";
        foreach($sections as $s){$opts.="<option value=\"".htmlspecialchars((string)$s)."\"></option>";}
        echo "<datalist id='fw-rule-sections'>$opts</datalist><script>(function(){var e=document.querySelector(\"input[name='section']\");if(e){e.setAttribute('list','fw-rule-sections');}})();</script>";
    }
	
}

// ── Vue flat d'une règle + modales par champ ──────────────────────────────────

// Une règle lue par l'API ; null si le daemon ne répond pas.
function fwr_rule(int $ID){
    $r=fwapi_data(fwapi_get("rules/$ID"));
    return is_array($r)?$r:null;
}

// Contexte d'affichage : ce que la règle autorise (mêmes cas que rule_settings()).
function fwr_rule_ctx(array $r):array{
    $eth=(string)($r["eth"] ?? "");
    $mod=(string)($r["mod"] ?? ($r["MOD"] ?? ""));
    $natLike=(bool)preg_match('#^([A-Z]NAT|MASQ):[0-9]+$#',$eth);
    $ctx=array(
        "client"=>!preg_match("#^([a-z]+[0-9]+)2([a-z]+[0-9]+)$#",$eth) && !preg_match("#^(NAT|[A-Z]NAT|MASQ):[0-9]+#",$eth) && $mod!="IPFEED",
        "name"=>$mod!="IPFEED",
        "action"=>$mod!="IPFEED" && !preg_match("#MASQ:[0-9]+#",$eth),
        "jlog"=>$mod!="IPFEED",
        "family"=>$mod!="IPFEED" && !$natLike,
        "limits"=>$mod!="IPFEED" && !$natLike && !preg_match("#MASQ:[0-9]+#",$eth) && (string)($r["accepttype"] ?? "")=="ACCEPT",
        "expires"=>$mod!="IPFEED",
        "eth_edit"=>!$natLike && !preg_match("#^([a-z]+[0-9]+)2([a-z]+[0-9]+)$#",$eth),
        // Une règle rattachée à un NAT change de NAT par la fenêtre de choix (pas de masquerade).
        "nat_edit"=>(bool)preg_match('#^[A-Z]NAT:[0-9]+$#',$eth),
    );
    return $ctx;
}

function fwr_actions():array{
    $a=array("DROP"=>"{drop}","ACCEPT"=>"{accept}","TPROXY"=>"{tproxy_method}","MARK"=>"{MARK_ITEM}",
        "NEXTHOPE"=>"{forward_traffic}","MIRROR"=>"{duplicate_traffic}","LOG"=>"{fw_action_log}","RETURN"=>"{fw_action_return}");
    if(count(fwr_links())>0){$a["lINK"]="{fw_action_link}";}
    return $a;
}

function fwr_links():array{
    if(isset($GLOBALS["FWR_LINKS"])){return $GLOBALS["FWR_LINKS"];}
    $LINKS=array();
    $lbList=fwapi_data(fwapi_get("linkbalance"));
    if(is_array($lbList)){
        foreach($lbList as $lb){
            $LINKS[intval($lb["id"])]=htmlspecialchars((string)$lb["interface"]).(intval($lb["enabled"])==1?"":" ({disabled})");
        }
    }
    $GLOBALS["FWR_LINKS"]=$LINKS;
    return $LINKS;
}

function fwr_services_list():array{
    $SERVICES=array("all"=>"{all}","RustDesk"=>"RustDesk");
    $list=fwapi_data(fwapi_get("services"));
    if(is_array($list)){
        foreach($list as $svc){
            if(!is_array($svc) || intval($svc["enabled"] ?? 0)!=1){continue;}
            $ports=str_replace(" ",",",(string)($svc["serverPort"] ?? ""));
            if(strlen($ports)>50){$ports=substr($ports,0,47)."...";}
            $SERVICES[(string)$svc["service"]]=htmlspecialchars((string)$svc["service"]." ".$ports);
        }
    }
    return $SERVICES;
}

function fwr_expires_options(int $curExp):array{
    $o=array("0"=>"{never}","3600"=>"1 {hour}","14400"=>"4 {hours}","28800"=>"8 {hours}",
        "86400"=>"1 {day}","604800"=>"7 {days}","2592000"=>"30 {days}");
    if($curExp>0){
        $o=array("0"=>"{fw_rule_expires_keep}","-1"=>"{fw_rule_make_permanent}")+array_slice($o,1,null,true);
    }
    return $o;
}

// ── Fiche d'une règle (maquette stitch_firewall_rules_zoom) ─────
// Un modèle (fwz_model) lu une fois ; une fonction par widget. Chaque ligne
// garde sa fenêtre d'édition (rule-field-js, rule-nat-js).

function fwz_model(int $ID){
    $r=fwr_rule($ID);
    if($r===null){return null;}
    $m=array("id"=>$ID,"r"=>$r,"ctx"=>fwr_rule_ctx($r));
    $m["eth"]=(string)($r["eth"] ?? "");
    $m["NICS"]=LOAD_NICS(true);
    $m["nat"]=$m["ctx"]["nat_edit"]?fwr_nat_by_eth($m["eth"]):null;
    $m["ports"]=array();
    $svcs=fwapi_data(fwapi_get("services"));
    if(is_array($svcs)){
        foreach($svcs as $s){
            if(!is_array($s)){continue;}
            $m["ports"][(string)($s["service"] ?? "")]=trim((string)($s["serverPort"] ?? ""));
        }
    }
    $st=fw_rules_status();
    $m["counter"]=isset($st[$ID]) && is_array($st[$ID])?$st[$ID]:null;
    $u=fwapi_rules_usage();
    $m["usage"]=$u[$ID] ?? null;
    $p=fwapi_rule_problems();
    $m["problems"]=isset($p[$ID]) && trim($p[$ID])!==""?explode(" ; ",$p[$ID]):array();
    $users=new usersMenus();
    // Regles tenues par Artica Meta : vue en lecture seule (ni interrupteur ni crayons).
    $m["write"]=(bool)$users->AsFirewallManager && !firewall_meta_holds("rules");
    return $m;
}

function fwz_esc($v):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v));
}

/** Ton, icône et libellé de l'action (couleur jamais seule : le mot est toujours écrit). */
function fwz_action(string $a):array{
    $acts=fwr_actions();
    $label=$acts[$a] ?? fwz_esc($a);
    switch($a){
        case "ACCEPT": return array("ak-ok","fas fa-circle-check",$label);
        case "DROP":   return array("ak-err","fas fa-ban",$label);
        case "LOG":    return array("ak-off","fas fa-file-lines",$label);
        case "RETURN": return array("ak-off","fas fa-arrow-turn-up",$label);
    }
    return array("ak-info","fas fa-route",$label);
}

/** Valeur d'interface : nom + périphérique, connecteur, ou NAT (type + cible). */
function fwz_iface(array $m):string{
    if($m["eth"]===""){return "{all_interfaces}";}
    if($m["nat"]!==null){
        $T=fwapi_nat_types(new template_admin());
        $n=$m["nat"];
        $name=trim((string)($n["rulename"] ?? ""));
        $tgt=fwr_nat_dest($n)." (".(string)($n["nic"] ?? "").")";
        $lbl=$name!==""?"<b>".fwz_esc($name)."</b> ":"";
        return "<span class='ak-pill ak-info'>".fwz_esc(fwr_plain($T[intval($n["nat_type"] ?? 0)] ?? "NAT"))."</span> $lbl<span class='ak-chip'>".fwz_esc($tgt)."</span>";
    }
    $txt=fwr_plain((string)($m["NICS"][$m["eth"]] ?? $m["eth"]));
    if(preg_match('#^(.+?)\s*\(([^()]+)\)$#',$txt,$re)){
        return fwz_esc($re[1])." <span class='ak-chip'>".fwz_esc($re[2])."</span>";
    }
    return fwz_esc($txt);
}

function fwz_service(array $m):string{
    $svc=(string)($m["r"]["service"] ?? "");
    if($svc==="" || $svc==="all"){return "<span class='ak-muted'>{fwz_all_services}</span>";}
    $ports=$m["ports"][$svc] ?? "";
    $chip=$ports!==""?" <span class='ak-chip'>".fwz_esc(strlen($ports)>40?substr($ports,0,37)."…":$ports)."</span>":"";
    return fwz_esc($svc).$chip;
}

function fwz_family(array $m):string{
    $FAM=array("both"=>"{fw_family_both}","ipv4"=>"{fw_family_ipv4}","ipv6"=>"{fw_family_ipv6}");
    return $FAM[(string)($m["r"]["family"] ?? "both")] ?? $FAM["both"];
}

/** La phrase du bandeau : action, service, sens et interface, famille. */
function fwz_sentence(array $m):string{
    $tpl=new template_admin();
    $a=fwz_action((string)($m["r"]["accepttype"] ?? ""));
    $svc=(string)($m["r"]["service"] ?? "");
    $svcTxt=($svc==="" || $svc==="all")?"{fwz_all_traffic}":fwz_esc($svc);
    $if=$m["eth"]===""?"{fwz_any_iface}":fwz_esc(fwr_plain((string)($m["NICS"][$m["eth"]] ?? $m["eth"])));
    if($m["nat"]!==null){$if=fwz_esc(fwr_nat_label($m["nat"]));}
    $out=intval($m["r"]["isclient"] ?? 0)==1;
    $s=$tpl->_ENGINE_parse_body($out?"{fwz_sentence_out}":"{fwz_sentence_in}");
    $s=str_replace(array("%a","%s","%i"),array($a[2],$svcTxt,$if),$s);
    if($m["ctx"]["family"]){$s.=", ".fwz_family($m);}
    return $s;
}

function fwz_status(array $m):string{
    $r=$m["r"];
    if(!empty($r["expired"])){return "<span class='ak-pill ak-warn'><i class='fas fa-hourglass-end'></i> {fwr_expired}</span>";}
    if(intval($r["enabled"] ?? 0)!=1){return "<span class='ak-pill ak-off'><span class='ak-dot'></span>{disabled}</span>";}
    return "<span class='ak-pill ak-ok'><span class='ak-dot'></span>{active2}</span>";
}

function fwz_hero(array $m):string{
    $r=$m["r"];
    $a=fwz_action((string)($r["accepttype"] ?? ""));
    $on=intval($r["enabled"] ?? 0)==1;
    $ID=$m["id"];
    $dis=$m["write"]?"":" disabled";
    $sw="<button type='button' class='ak-sw' role='switch' aria-checked='".($on?"true":"false")."' aria-label=\"{enabled}\"$dis OnClick=\"FwzSet($ID,'enabled','".($on?"0":"1")."')\"></button>";
    return "<section class='ak-card fwz-hero'>"
        ."<span class='ak-ico ak-ico-lg ak-ico-sq ak-solid {$a[0]}' aria-hidden='true'><i class='{$a[1]}'></i></span>"
        ."<div class='fwz-hero-t'><div class='fwz-hero-n'><h3>".fwz_esc($r["rulename"] ?? "")."</h3>".fwz_status($m)."</div>"
        ."<p>".fwz_sentence($m)."</p></div>"
        ."<div class='fwz-hero-sw'><span>{enabled}</span>$sw<b>".($on?"{fwz_on}":"{fwz_off}")."</b></div>"
        ."</section>";
}

function fwz_problems(array $m):string{
    if(count($m["problems"])==0){return "";}
    $li=array();
    foreach($m["problems"] as $p){$li[]="<li>$p</li>";}
    return "<div class='ak-banner ak-warn fwz-gap'><i class='fas fa-triangle-exclamation'></i><div class='ak-banner-m'><b>{fwz_problems_title}</b><ul class='fwz-pb'>".implode("",$li)."</ul></div></div>";
}

function fwz_compact(int $n):string{
    if($n>=1000000){return number_format($n/1000000,1)."M";}
    if($n>=1000){return number_format($n/1000,1)."k";}
    return (string)$n;
}

function fwz_activity(array $m):string{
    $pk="—";$by="—";
    if($m["counter"]!==null){
        $pk=fwz_compact(intval($m["counter"]["pkts"] ?? 0));
        $b=intval($m["counter"]["bytes"] ?? 0);
        $by=$b<1024?"$b B":preg_replace('/^([0-9.,]+)\s*([A-Za-z]+)$/','$1 $2',trim(strip_tags((string)FormatBytes($b/1024,true))));
    }
    $last="—";$sub="";
    if(is_array($m["usage"])){
        $lh=intval($m["usage"]["last_hit"] ?? 0);$fs=intval($m["usage"]["first_seen"] ?? 0);
        if($lh>0){$last=distanceOfTimeInWords($lh,time());$sub=date("Y-m-d H:i",$lh);}
        elseif($fs>0 && time()-$fs<30*86400){$last="{fw_in_observation}";}
        else{$last="{fwz_never}";}
        if(!empty($m["usage"]["unused"])){$sub="<span class='ak-pill ak-off'>{fw_unused_30d}</span>";}
    }
    return "<section class='ak-card fwz-act'>"
        .fwz_stat("fas fa-chart-simple","ak-info","{fwz_packets}",$pk,"")
        .fwz_stat("fas fa-arrow-right-arrow-left","ak-violet","{fwz_traffic_matched}",$by,"")
        .fwz_stat("fas fa-clock","ak-ok","{fwz_last_hit}",$last,$sub)
        ."</section><p class='ak-note fwz-note'><i class='fas fa-circle-info'></i> {fwz_counters_note}</p>";
}

function fwz_stat(string $ico,string $tone,string $label,string $value,string $sub):string{
    $s=$sub!==""?"<div class='fwz-stat-s'>$sub</div>":"";
    return "<div class='fwz-stat'><span class='ak-ico ak-ico-sq $tone' aria-hidden='true'><i class='$ico'></i></span>"
        ."<div><div class='fwz-stat-l'>$label</div><div class='fwz-stat-v'>$value</div>$s</div></div>";
}

/** Une ligne : libellé, valeur, crayon. Sans $js, la ligne est en lecture seule. */
function fwz_row(string $label,string $value,string $js,string $aria,string $sub=""):string{
    $s=$sub!==""?"<div class='fwz-row-s'>$sub</div>":"";
    if($js===""){
        return "<div class='fwz-row'><div class='fwz-row-l'>$label$s</div><div class='fwz-row-v'>$value</div><span class='fwz-pen-x'></span></div>";
    }
    return "<div class='fwz-row fwz-row-e' OnClick=\"$js\"><div class='fwz-row-l'>$label$s</div><div class='fwz-row-v'>$value</div>"
        ."<button type='button' class='ak-btn-icon ak-btn ak-btn-ghost fwz-pen' aria-label=\"{fwz_edit} $aria\" OnClick=\"event.stopPropagation();$js\"><i class='fas fa-pen'></i></button></div>";
}

function fwz_card(string $title,string $sub,string $rows):string{
    $s=$sub!==""?"<span class='fwz-card-s'>$sub</span>":"";
    return "<section class='ak-card fwz-card'><div class='fwz-card-h'><h4>$title</h4>$s</div>$rows</section>";
}

function fwz_onoff(bool $on):string{
    return $on?"<span class='ak-pill ak-ok'><i class='fas fa-check'></i> {fwz_on}</span>":"<span class='ak-pill ak-off'>{fwz_off}</span>";
}

function fwz_traffic(array $m,callable $js):string{
    $r=$m["r"];$c=$m["ctx"];$ID=$m["id"];$page=CurrentPageName();
    $ethJs="";
    if($c["eth_edit"]){$ethJs=$js("eth");}
    if($c["nat_edit"]){$ethJs="Loadjs('$page?rule-nat-js=$ID')";}
    $w=function($j) use($m){ return $m["write"]?$j:""; };
    $rows=fwz_row("{interface}",fwz_iface($m),$w($ethJs),"{interface}");
    $rows.=fwz_row("{service2}",fwz_service($m),$w($js("service")),"{service2}");
    if($c["action"]){
        $act=(string)($r["accepttype"] ?? "");
        $a=fwz_action($act);
        $rows.=fwz_row("{action}","<span class='ak-pill ak-solid {$a[0]}'><i class='{$a[1]}'></i> {$a[2]}</span>",$w($js("accepttype")),"{action}");
        $LINKS=fwr_links();
        if($act=="lINK" && count($LINKS)>0){
            $mb=intval($r["mark_balance"] ?? 0);
            $rows.=fwz_row("{fw_link_target}",$LINKS[$mb] ?? "<span class='ak-muted'>—</span>",$w($js("mark_balance")),"{fw_link_target}");
        }
        if($act=="MIRROR"){
            $f=trim((string)($r["forwardto"] ?? ""));
            $rows.=fwz_row("{redirect_nat}",$f===""?"<span class='ak-muted'>—</span>":"<span class='ak-chip'>".fwz_esc($f)."</span>",$w($js("forwardto")),"{redirect_nat}");
        }
    }
    if($c["family"]){$rows.=fwz_row("{fw_family}",fwz_family($m),$w($js("family")),"{fw_family}");}
    if($c["jlog"]){$rows.=fwz_row("{log_all_events}",fwz_onoff(intval($r["jlog"] ?? 0)==1),$w($js("jlog")),"{log_all_events}");}
    return fwz_card("{fwr_traffic}","{fwz_traffic_sub}",$rows);
}

function fwz_identity(array $m,callable $js):string{
    $r=$m["r"];$c=$m["ctx"];
    $w=function($j) use($m){ return $m["write"]?$j:""; };
    $rows="";
    if($c["name"]){
        $rows.=fwz_row("{rulename}","<b>".fwz_esc($r["rulename"] ?? "")."</b>",$w($js("rulename")),"{rulename}");
        $d=trim((string)($r["description"] ?? ""));
        $rows.=fwz_row("{description}",$d===""?"<span class='ak-muted'>{fwz_no_description}</span>":"<span class='fwz-desc' title=\"".fwz_esc($d)."\">".fwz_esc($d)."</span>",$w($js("description")),"{description}");
        $sec=trim((string)($r["section"] ?? ""));
        $rows.=fwz_row("{section}",$sec===""?"<span class='ak-muted'>{fwz_no_section}</span>":"<span class='ak-tag ak-warn'><i class='far fa-folder'></i> ".fwz_esc($sec)."</span>",$w($js("section")),"{section}");
    }
    if($c["client"]){
        $rows.=fwz_row("{outgoing_rule}",fwz_onoff(intval($r["isclient"] ?? ($r["isClient"] ?? 0))==1),$w($js("isclient")),"{outgoing_rule}");
    }
    $z=intval($r["zorder"] ?? 0);
    $rows.=fwz_row("{fwz_order}","<span class='ak-mono ak-muted'>#$z</span>","","");
    return fwz_card("{fwr_identity}","{fwz_identity_sub}",$rows);
}

function fwz_limits(array $m,callable $js):string{
    $r=$m["r"];
    if(!$m["ctx"]["limits"]){
        return fwz_card("{fw_limits}","","<p class='ak-note fwz-pad'>{fwz_limits_na}</p>");
    }
    $w=function($j) use($m){ return $m["write"]?$j:""; };
    $cl=intval($r["conn_limit"] ?? 0);$rl=intval($r["rate_limit"] ?? 0);
    $unit=($r["rate_unit"] ?? "")=="minute"?"{per_minute}":"{per_second}";
    $rows=fwz_row("{fw_conn_limit}",$cl>0?"<b>$cl</b>":"<span class='ak-muted'>{unlimited}</span>",$w($js("conn_limit")),"{fw_conn_limit}");
    $rows.=fwz_row("{fw_rate_limit}",$rl>0?"<b>$rl</b> $unit":"<span class='ak-muted'>{unlimited}</span>",$w($js("rate_limit")),"{fw_rate_limit}");
    $rows.=fwz_row("{unit}",$unit,$w($js("rate_unit")),"{unit}");
    return fwz_card("{fw_limits}","{fw_limits_explain}",$rows);
}

function fwz_expires(array $m,callable $js):string{
    $r=$m["r"];$ID=$m["id"];
    $exp=intval($r["expires"] ?? 0);
    if($exp>0){
        $v="<span class='ak-tag ".(!empty($r["expired"])?"ak-warn":"ak-off")."'><i class='far fa-calendar'></i> ".date("Y-m-d H:i",$exp)."</span>";
        $sub=!empty($r["expired"])?"{fwr_expired}":distanceOfTimeInWords(time(),$exp);
    }else{
        $v="<span class='ak-tag ak-off'><i class='fas fa-infinity'></i> {never}</span>";
        $sub="";
    }
    $rows=fwz_row("{fwz_auto_expiration}",$v,$m["write"]?$js("expires_in"):"","{fw_rule_expires}",$sub);
    if($m["write"]){
        $q=array("3600"=>"{fwz_1h}","86400"=>"{fwz_1d}","604800"=>"{fwz_7d}");
        $b="";
        foreach($q as $sec=>$lbl){$b.="<button type='button' class='ak-btn ak-btn-sm' OnClick=\"FwzSet($ID,'expires_in','$sec')\">$lbl</button>";}
        $rows.="<div class='fwz-quick'><span>{fwz_quick_delays}</span><div class='ak-actions'>$b</div></div>";
    }
    return fwz_card("{fw_rule_expires}","{fw_rule_expires_explain}",$rows);
}

function fwz_js(int $ID):string{
    $page=CurrentPageName();
    $oops=addslashes((new template_admin())->javascript_parse_text("{error}"));
    $after=fwr_after_js($ID,false);
    return "<script>
function FwzSet(id,k,v){
    var data={'rule-field-save':id}; data[k]=v;
    jQuery.post('$page',data,function(r){
        r=String(r).trim();
        if(r.length>3){ var t=(r.indexOf('jserror:')===0)?r.substring(8):jQuery('<div>').text(r).html(); swal({title:'$oops',text:t,html:true,type:'error'}); return; }
        $after
    }).fail(function(x){ swal({title:'$oops',text:'HTTP '+x.status,html:false,type:'error'}); });
}
</script>";
}

function fwz_style():string{
    if(isset($GLOBALS["FWZ_CSS"])){return "";}
    $GLOBALS["FWZ_CSS"]=true;
    return "<style>
.fwz{padding:4px 2px}
.fwz-hero{display:flex;align-items:center;gap:18px;padding:20px 22px;flex-wrap:wrap}
.fwz-hero-t{flex:1 1 360px;min-width:0}
.fwz-hero-n{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.fwz-hero-n h3{margin:0;font-size:20px;font-weight:600;color:#111d23}
.fwz-hero-t p{margin:6px 0 0;font-size:14px;color:#3d4947}
.fwz-hero-sw{display:flex;align-items:center;gap:10px;font-size:14px;color:#111d23;padding-left:18px;border-left:1px solid #e5e7eb}
.fwz-act{display:grid;grid-template-columns:repeat(3,1fr);margin-top:16px;padding:16px 0}
.fwz-stat{display:flex;align-items:center;gap:14px;padding:0 22px;border-left:1px solid #eef2f7}
.fwz-stat:first-child{border-left:0}
.fwz-stat-l{font-size:13px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#6b7280}
.fwz-stat-v{font-size:22px;font-weight:700;color:#111d23;font-variant-numeric:tabular-nums;margin-top:2px}
.fwz-stat-s{font-size:13px;color:#6b7280;margin-top:2px}
.fwz-note{margin:8px 2px 0}
.fwz-gap{margin-top:16px}
.fwz-pb{margin:4px 0 0;padding-left:18px}
.fwz-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;align-items:start}
.fwz-card{padding:0;overflow:hidden}
.fwz-card-h{display:flex;justify-content:space-between;align-items:baseline;gap:12px;padding:14px 20px;border-bottom:1px solid #eef2f7}
.fwz-card-h h4{margin:0;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#3d4947}
.fwz-card-s{font-size:13px;color:#6b7280;text-align:right}
.fwz-row{display:flex;align-items:center;gap:12px;min-height:52px;padding:8px 12px 8px 20px;border-bottom:1px solid #f3f5f8}
.fwz-row:last-child{border-bottom:0}
.fwz-row-e{cursor:pointer}
.fwz-row-e:hover{background:#fbfeff}
.fwz-row-l{flex:0 0 auto;max-width:48%;font-size:14px;color:#111d23}
.fwz-row-s{font-size:13px;color:#6b7280;margin-top:2px}
.fwz-row-v{flex:1 1 auto;min-width:0;display:flex;justify-content:flex-end;align-items:center;gap:8px;flex-wrap:wrap;text-align:right;font-size:14px;color:#111d23}
.fwz-desc{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#3d4947}
.fwz-pen{opacity:.45;flex:0 0 auto}
.fwz-row-e:hover .fwz-pen,.fwz-pen:focus{opacity:1}
.fwz-pen-x{display:inline-block;width:32px;flex:0 0 auto}
.fwz-quick{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 20px;font-size:13px;color:#6b7280;flex-wrap:wrap}
.fwz-pad{padding:14px 20px;margin:0}
.fwz-quick .ak-actions{margin:0;padding:0;border:0}
@media(max-width:1000px){.fwz-grid{grid-template-columns:1fr}.fwz-act{grid-template-columns:1fr}.fwz-stat{border-left:0;padding:8px 22px}}
</style>";
}

function rule_flat(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["rule-flat"]);
    $m=fwz_model($ID);
    if($m===null){echo $tpl->div_error("{fw_rule_not_found}");return;}
    $js=function($f) use($page,$ID){return "Loadjs('$page?rule-field-js=$ID&field=$f')";};
    $h=array();
    $metaBanner=firewall_meta_banner("rules",$tpl);
    $h[]="<div class='ak fwz'>";
    $h[]=fwz_hero($m);
    $h[]=fwz_activity($m);
    $h[]=fwz_problems($m);
    $h[]="<div class='fwz-grid'>".fwz_traffic($m,$js).fwz_identity($m,$js)."</div>";
    $h[]="<div class='fwz-grid'>".fwz_limits($m,$js).($m["ctx"]["expires"]?fwz_expires($m,$js):"")."</div>";
    $h[]="</div>";
    echo fwz_style().$metaBanner.$tpl->_ENGINE_parse_body(implode("\n",$h)).fwz_js($ID);
}

// Champs éditables : nom => type de modale. La clé est aussi le nom JSON du daemon.
function fwr_rule_fields():array{
    return array("rulename"=>"text","description"=>"textarea","section"=>"text","forwardto"=>"text",
        "enabled"=>"bool","isclient"=>"bool","jlog"=>"bool",
        "conn_limit"=>"int","rate_limit"=>"int",
        "eth"=>"select","service"=>"select","accepttype"=>"select","family"=>"select",
        "rate_unit"=>"select","mark_balance"=>"select","expires_in"=>"select");
}

function rule_field_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["rule-field-js"]);
    $field=(string)($_GET["field"] ?? "");
    if(!isset(fwr_rule_fields()[$field])){return;}
    $titles=array("rulename"=>"{rulename}","description"=>"{description}","section"=>"{section}","forwardto"=>"{redirect_nat}",
        "enabled"=>"{enabled}","isclient"=>"{outgoing_rule}","jlog"=>"{log_all_events}","conn_limit"=>"{fw_conn_limit}",
        "rate_limit"=>"{fw_rate_limit}","eth"=>"{interface}","service"=>"{service2}","accepttype"=>"{action}",
        "family"=>"{fw_family}","rate_unit"=>"{unit}","mark_balance"=>"{fw_link_target}","expires_in"=>"{fw_rule_expires}");
    $tpl->js_dialog4($titles[$field],"$page?rule-field-popup=$ID&field=$field",600);
}

// Après un enregistrement : la vue flat et la ligne du tableau (fill) se mettent à jour.
function fwr_after_js(int $ID,bool $close):string{
    $page=CurrentPageName();
    $js="LoadAjaxSilent('fw-rule-flat-$ID','$page?rule-flat=$ID');Loadjs('$page?fill=$ID');";
    if($close){$js="dialogInstance4.close();".$js;}
    return $js;
}

// Valeur sûre dans une chaîne JS entre apostrophes (les Big* l'interpolent telle quelle).
function fwr_js_str(string $v):string{
    return str_replace(array("\\","'","\r","\n","<"),array("\\\\","\\'","","\\n","\\x3c"),$v);
}

function rule_field_popup(){
    $tpl=new template_admin();
    $ID=intval($_GET["rule-field-popup"]);
    $field=(string)($_GET["field"] ?? "");
    $types=fwr_rule_fields();
    if(!isset($types[$field])){return;}
    $r=fwr_rule($ID);
    if($r===null){echo $tpl->div_error("{fw_rule_not_found}");return;}
    if(firewall_meta_holds("rules")){echo firewall_meta_banner("rules",$tpl);return;}
    $type=$types[$field];
    $marker="rule-field-save:$ID|$field";
    // Big* : enregistré sur Entrée/clic ; erreur « jserror: » montrée, sinon rafraîchissement.
    $bigAfter="if(String(resp).indexOf('jserror:')===0){swal({title:'Oops...',text:'<strong>'+String(resp).substring(8)+'</strong>',html:true,type:'error'});return;}".fwr_after_js($ID,false);

    if($type=="bool"){
        $labels=array("enabled"=>array("{enabled}",""),"isclient"=>array("{outgoing_rule}","{outgoing_rule_fw_explain}"),
            "jlog"=>array("{log_all_events}",""));
        $cur=intval($r[$field] ?? ($field=="isclient"?($r["isClient"] ?? 0):0));
        $after="if(String(resp).indexOf('jserror:')===0){ctrl.input.checked=!ctrl.input.checked;swal({title:'Oops...',text:'<strong>'+String(resp).substring(8)+'</strong>',html:true,type:'error'});return;}".fwr_after_js($ID,false);
        echo $tpl->BigCircleCheckbox($marker,$labels[$field][0],$labels[$field][1],$cur,$after,"AsFirewallManager");
        return;
    }
    if($type=="int"){
        $labels=array("conn_limit"=>array("{fw_conn_limit}","{fw_conn_limit_explain}"),"rate_limit"=>array("{fw_rate_limit}","{fw_rate_limit_explain}"));
        echo $tpl->BigIntegerField($marker,$labels[$field][0],$labels[$field][1],intval($r[$field] ?? 0),$bigAfter,0,1000000,"AsFirewallManager");
        return;
    }
    if($type=="text"){
        $labels=array("rulename"=>array("{rulename}",""),"section"=>array("{section}","{fw_rule_section_explain}"),"forwardto"=>array("{redirect_nat}",""));
        echo $tpl->BigTextField($marker,$labels[$field][0],$labels[$field][1],fwr_js_str((string)($r[$field] ?? "")),$bigAfter,null,255,"AsFirewallManager");
        return;
    }

    // Texte multiligne et listes de choix : pas de widget Big* pour eux, une
    // modale à un seul champ.
    $form=array();
    $tpl->field_hidden("rule-field-save",$ID);
    if($type=="textarea"){
        $form[]=$tpl->field_textarea_normal("description","{description}",(string)($r["description"] ?? ""),"100%","110px");
    }else{
        $opts=array();$cur="";
        switch($field){
            case "eth": $opts=LOAD_NICS(true);ksort($opts);$cur=(string)($r["eth"] ?? "");break;
            case "service": $opts=fwr_services_list();$cur=(string)($r["service"] ?? "");break;
            case "accepttype": $opts=fwr_actions();$cur=(string)($r["accepttype"] ?? "");break;
            case "family": $opts=array("both"=>"{fw_family_both}","ipv4"=>"{fw_family_ipv4}","ipv6"=>"{fw_family_ipv6}");$cur=(string)($r["family"] ?? "both");break;
            case "rate_unit": $opts=array("second"=>"{per_second}","minute"=>"{per_minute}");$cur=($r["rate_unit"] ?? "")=="minute"?"minute":"second";break;
            case "mark_balance": $opts=fwr_links();$cur=intval($r["mark_balance"] ?? 0);break;
            case "expires_in": $opts=fwr_expires_options(intval($r["expires"] ?? 0));$cur="0";break;
        }
        $explain=$field=="expires_in"?(intval($r["expires"] ?? 0)>0?"{fw_rule_expires_current}: ".date("Y-m-d H:i",intval($r["expires"])):"{fw_rule_expires_explain}"):"";
        $form[]=$tpl->field_array_hash($opts,$field,"",$cur,false,$explain);
    }
    echo $tpl->form_outside("",@implode("\n",$form),"","{apply}",fwr_after_js($ID,true),"AsFirewallManager");
}

// Enregistre UN champ : seuls les champs envoyés changent côté daemon.
function rule_field_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){echo $tpl->post_error("{ERROR_NO_PRIVS}");return;}
    if(firewall_meta_holds("rules")){echo firewall_meta_refuse_post();return;}
    $ID=intval($_POST["rule-field-save"]);
    if($ID<1){return;}
    $types=fwr_rule_fields();
    $data=array();
    foreach($types as $field=>$type){
        if(!isset($_POST[$field])){continue;}
        $v=$_POST[$field];
        if($type=="bool" || $type=="int" || $field=="mark_balance"){
            $data[$field]=intval($v);
        }elseif($field=="expires_in"){
            $n=intval($v);
            if($n>0){
                $data["expires"]=time()+$n;
                // Nouvelle durée sur une règle EXPIRÉE : c'est une réactivation.
                $cur=fwr_rule($ID);
                if(is_array($cur) && !empty($cur["expired"])){$data["enabled"]=1;}
            }elseif($n==-1){
                $data["expires"]=0;
            }
        }else{
            // form_outside double-encode (rawurldecode) ; les Big* envoient le texte brut.
            $data[$field]=trim(isset($_POST["description"]) && $field=="description"?rawurldecode(url_decode_special_tool((string)$v)):(string)$v);
        }
    }
    if(count($data)==0){return;}
    // Les limites n'existent que pour Accept (le daemon refuserait sinon le changement d'action).
    if(isset($data["accepttype"]) && $data["accepttype"]!="ACCEPT"){$data["conn_limit"]=0;$data["rate_limit"]=0;}
    // form_outside poste aussi les selects encodés.
    foreach(array("eth","service","accepttype","family","rate_unit") as $k){
        if(isset($data[$k])){$data[$k]=rawurldecode((string)$data[$k]);}
    }
    $e=fwapi_error(fwapi_send("PUT","rules/$ID",$data));
    if($e!=""){echo $tpl->post_error($e);return;}
    admin_tracks("Firewall rule $ID: ".implode(",",array_keys($data))." updated");
}

// ── Choix du NAT d'une règle NAT ───────────────────────────────
// Une règle rattachée à un NAT (eth = DNAT:<id>…) ne passait pas par le choix
// d'interface : la fenêtre liste les NAT de /firewall/config/nat et en pose un.

function fwr_plain(string $v):string{
    $v=preg_replace('#<br\s*/?>#i'," ",$v);
    return trim(preg_replace('/\s+/',' ',html_entity_decode(strip_tags($v),ENT_QUOTES,"UTF-8")));
}

function fwr_nat_list():array{
    if(isset($GLOBALS["FWR_NATS"])){return $GLOBALS["FWR_NATS"];}
    $out=array();
    $list=fwapi_data(fwapi_get("nat"));
    if(is_array($list)){
        foreach($list as $n){
            if(!is_array($n)){continue;}
            $type=intval($n["nat_type"] ?? 0);
            if($type<0 || $type>3){$type=0;}
            $codes=fwapi_nat_codes();
            $n["eth"]=$codes[$type].":".intval($n["id"] ?? 0);
            $out[]=$n;
        }
    }
    $GLOBALS["FWR_NATS"]=$out;
    return $out;
}

function fwr_nat_by_eth(string $eth){
    foreach(fwr_nat_list() as $n){if($n["eth"]===$eth){return $n;}}
    return null;
}

// Destination telle que fw.nat.php l'affiche (adresse:port, 0.0.0.0:port en redirection).
function fwr_nat_dest(array $n):string{
    $type=intval($n["nat_type"] ?? 0);
    $port=intval($n["dstaddrport"] ?? 0);
    $dst=trim((string)($n["dstaddr_name"] ?? ""))!==""?(string)$n["dstaddr_name"]:(string)($n["dstaddr"] ?? "");
    if($type==2){return "0.0.0.0:$port";}
    if($type!=3 && $port>0){$dst.=":$port";}
    return $dst;
}

function fwr_nat_label(array $n):string{
    $tpl=new template_admin();
    $T=fwapi_nat_types($tpl);
    $name=trim((string)($n["rulename"] ?? ""));
    if($name===""){$name=fwr_nat_dest($n);}
    return fwr_plain($T[intval($n["nat_type"] ?? 0)] ?? "NAT")." · $name (".(string)($n["nic"] ?? "").")";
}

function rule_nat_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["rule-nat-js"]);
    $tpl->js_dialog4("{fwr_choose_nat}","$page?rule-nat-popup=$ID",900);
}

function rule_nat_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["rule-nat-popup"]);
    $r=fwr_rule($ID);
    if($r===null){echo $tpl->div_error("{fw_rule_not_found}");return;}
    $cur=(string)($r["eth"] ?? "");
    $users=new usersMenus();
    $canWrite=(bool)$users->AsFirewallManager && !firewall_meta_holds("rules");
    $T=fwapi_nat_types($tpl);
    $NICS=LOAD_NICS();
    $rows=array();
    foreach(fwr_nat_list() as $n){
        $eth=$n["eth"];
        $on=intval($n["enabled"] ?? 0)==1;
        $name=trim((string)($n["rulename"] ?? ""));
        $nic=(string)($n["nic"] ?? "");
        $nicTxt=fwr_plain((string)($NICS[$nic] ?? $nic));
        $badge=$on?"<span class='awc-badge awc-badge-on'>{enabled}</span>":"<span class='awc-badge awc-badge-off'>{disabled}</span>";
        if($eth===$cur){
            $act="<span class='awc-badge awc-badge-off'>{fwr_nat_current}</span>";
        }elseif($canWrite){
            $act="<a class='awc-badge awc-badge-on' href=\"javascript:blur();\" OnClick=\"FwrNatPick($ID,'$eth')\">{fwr_nat_select}</a>";
        }else{
            $act="";
        }
        $meta=$name!==""?htmlspecialchars(fwr_nat_dest($n)):"";
        $title=htmlspecialchars($name!==""?$name:fwr_nat_dest($n));
        $rows[]="<tr><td><div class='awc-ident'><span class='awc-ico".($on?"":" awc-ico-off")."'><i class='fas fa-shuffle'></i></span>"
            ."<span><span class='awc-name".($on?"":" awc-name-off")."'>$title</span>".($meta!==""?"<div class='awc-meta'>$meta</div>":"")."</span></div></td>"
            ."<td class='awc-mono'>".htmlspecialchars(fwr_plain($T[intval($n["nat_type"] ?? 0)] ?? ""))."</td>"
            ."<td class='awc-mono'>".htmlspecialchars($nicTxt)."</td>"
            ."<td class='center'>$badge</td>"
            ."<td class='center awc-nowrap'><div class='awc-acts' style='opacity:1;justify-content:center'>$act</div></td></tr>";
    }
    $html=array();
    $html[]=$tpl->div_explain("{fwr_choose_nat}||{fwr_choose_nat_explain}");
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'><thead><tr>";
    $html[]="<th>{rulename}</th><th>{type}</th><th>{interface}</th><th class='center'>{status}</th><th class='center'></th>";
    $html[]="</tr></thead><tbody id='fwr-nat-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='5'><div class='awc-empty'>{fwr_no_nat}</div></td></tr>";
    }else{
        $html[]=implode("\n",$rows);
    }
    $html[]="</tbody></table></div><div class='awc-foot'><span>".count($rows)." NAT</span><span class='awc-pg' id='fwr-nat-pager'></span></div></div></div>";
    $oops=addslashes($tpl->javascript_parse_text("{error}"));
    $after=fwr_after_js($ID,true);
    $js="<script>NoSpinner();
function FwrNatPick(id,eth){
    jQuery.post('$page',{'rule-nat-save':id,'eth':eth},function(r){
        r=String(r).trim();
        if(r.length>3){ var t=(r.indexOf('jserror:')===0)?r.substring(8):jQuery('<div>').text(r).html(); swal({title:'$oops',text:t,html:true,type:'error'}); return; }
        $after
    }).fail(function(x){ swal({title:'$oops',text:'HTTP '+x.status,html:false,type:'error'}); });
}
</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).$js.awc_pager_js(10,"fwr-nat-rows","fwr-nat-pager");
}

// POST rule-nat-save=<id>, eth=<DNAT|SNAT|RNAT|XNAT>:<id>. N'imprime que l'erreur.
function rule_nat_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){echo $tpl->post_error("{ERROR_NO_PRIVS}");return;}
    if(firewall_meta_holds("rules")){echo firewall_meta_refuse_post();return;}
    $ID=intval($_POST["rule-nat-save"]);
    $eth=(string)($_POST["eth"] ?? "");
    if($ID<1 || !preg_match('#^[A-Z]NAT:[0-9]+$#',$eth)){echo $tpl->post_error("{fw_iface_unknown}");return;}
    $r=fwr_rule($ID);
    if($r===null){echo $tpl->post_error("{fw_rule_not_found}");return;}
    if(!preg_match('#^[A-Z]NAT:[0-9]+$#',(string)($r["eth"] ?? ""))){echo $tpl->post_error("{fw_iface_unknown}");return;}
    if(fwr_nat_by_eth($eth)===null){echo $tpl->post_error("{fw_iface_unknown}: $eth");return;}
    $e=fwapi_error(fwapi_send("PUT","rules/$ID",array("eth"=>$eth)));
    if($e!=""){echo $tpl->post_error($e);return;}
    admin_tracks("Firewall rule $ID: NAT changed from ".(string)$r["eth"]." to $eth");
}

function rule_js(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl=new template_admin();
    $function=$_GET["function"];
	$ruleid=intval($_GET["ruleid-js"]);
    if(!isset($_GET["eth"])){$_GET["eth"]="";}
	if($ruleid==0){
		$ligne["rulename"]="{new_rule}";
	}else{
		$ligne=$q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID='$ruleid'");
        if(preg_match("#<br><small>(.+?)<\/small>#",$ligne["rulename"],$re)){
            $ligne["rulename"]=str_replace("<br><small>{$re[1]}</small>","",$ligne["rulename"]);
        }
	}
	$tpl->js_dialog1("{rule}: $ruleid {$ligne["rulename"]} {$ligne["accepttype"]}","$page?rule-popup=$ruleid&eth={$_GET["eth"]}&function=$function",1100);
}

function rule_action_status($ligne){
    $jlog               = null;
    $ACTION             = null;
    $ID                 = $ligne["ID"];
    $eth                = $ligne["eth"];
    $IPTABLES_RRULES_STATUS = fw_rules_status();

    if(!isset($IPTABLES_RRULES_STATUS[$ID])){
        $ligne["enabled"]=0;
    }

    $Outgoing           = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";

    if(preg_match("#NAT:([0-9]+)#",$eth,$re)){
        $ligne["isClient"]=0;
        $Outgoing           = "<span class='label'>&nbsp;&nbsp;NAT&nbsp;&nbsp;</span>&nbsp;";
    }

    if(preg_match("#MASQ:([0-9]+)#",$eth,$re)){
        $ligne["isClient"]=0;
        $Outgoing           = "<span class='label'>&nbsp;&nbsp;MASQ.&nbsp;&nbsp;</span>&nbsp;";
    }
    if(preg_match("#router:([0-9]+)#",$eth,$re)){
        $ligne["isClient"]=0;
        $Outgoing           = "<span class='label'>&nbsp;&nbsp;FORWARD&nbsp;&nbsp;</span>&nbsp;";
    }
    if(preg_match("#[a-z]+[0-9]+2[a-z]+[0-9]+#",$eth,$re)){
        $ligne["isClient"]=0;
        $Outgoing           = "<span class='label'>&nbsp;&nbsp;FORWARD&nbsp;&nbsp;</span>&nbsp;";
    }


    if($ligne["isClient"]==1){
        $Outgoing           = "<span class='label fwr-out'>OUT</span>&nbsp;";
    }

    if($ligne["jlog"]==1){
        $IPTABLES_RRULES_STATUS = fw_rules_status();
        if(isset($IPTABLES_RRULES_STATUS["LOG"][$ID])) {
            $jlog = "<span class='label label-info'>LOG</span>&nbsp;";
        }else{
            $jlog = "<span class='label'>LOG</span>&nbsp;";
        }
    }

    if($ligne["accepttype"]=="DROP"){
        $ACTION="<span class='label label-danger'>DENY</span>";
        if($ligne["enabled"]==0){
            $ACTION="<span class='label'>NONE</span>";
        }
    }

    if($ligne["accepttype"]=="RETURN"){
        $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    }

    if($ligne["accepttype"]=="ACCEPT"){
        $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    }

    if($ligne["accepttype"]=="LOG"){
        $ACTION="<span class='label label-info'>INFO</span>";
    }
    if($ligne["accepttype"]=="MARK"){
        $ACTION="<span class='label label-info'>MARK</span>";
    }
    if ($ligne["accepttype"] == "lINK") {
        $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    }
    if ($ligne["accepttype"] == "TPROXY") {
        $Outgoing="<span class='label label-primary'>FORWARD</span>&nbsp;";
        $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
        if($ligne["enabled"]==0){
            $Outgoing="<span class='label'>FORWARD</span>&nbsp;";
            $ACTION="<span class='label'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
        }

    }
    if ($ligne["accepttype"] == "MIRROR") {
        $ACTION="<span class='label label-primary'>MIRROR</span>";
    }


    if($ligne["enabled"]==0){
        if($ligne["jlog"]==1){$jlog="<span class='label'>LOG</span>&nbsp;";}
        $ACTION="<span class='label'>NONE</span>";

    }

    return "$Outgoing$jlog$ACTION";

}

function LOAD_NICS($routers=false){

    if(isset($GLOBALS["LOAD_NICS"][$routers])) {
        return $GLOBALS["LOAD_NICS"][$routers];
    }

    $nic        = new networking();
    $tpl        = new template_admin();
    $nicZ       = $nic->Local_interfaces();
    $NICS       = array();
    $NICS[null]="{all_interfaces}";
    foreach ($nicZ as $yinter=>$line){
        if($yinter=="lo"){continue;}
        $znic=new system_nic($yinter);
        if(preg_match("#^dummy#", $yinter)){continue;}
        if(preg_match("#-ifb$#", $yinter)){continue;}
        if($znic->Bridged==1){continue;}
        if($znic->enabled==0){continue;}
        $NICS[$yinter]="$znic->NICNAME ($yinter)";
   }

   if(!$routers){
       $GLOBALS["LOAD_NICS"][$routers]=$NICS;
       return $NICS;
   }
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    if($q->TABLE_EXISTS("pnic_bridges")){
        $sql="SELECT * FROM `pnic_bridges` WHERE `enabled`=1 AND NoFirewall=0";
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne2){
            $nic_from=$ligne2["nic_from"];
            $nic_to=$ligne2["nic_to"];
            $RouterName="{$nic_from}2{$nic_to}";
            $RouterExpl="$nic_from {to} $nic_to";
            if(strlen($ligne2["rulename"])>2){
                $RouterExpl=$ligne2["rulename"];
            }
            $NICS[$RouterName]=$tpl->javascript_parse_text("{connector}: $RouterExpl");
        }
    }

    $results=$q->QUERY_SQL("SELECT * FROM pnic_nat ORDER BY ID DESC");
    $NAT_TYPE=$GLOBALS["NAT_TYPE"];

    $sNAT_TYPE=fwapi_nat_codes();

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $type=$ligne["NAT_TYPE"];
        $NAT_TYPE_TEXT  =$NAT_TYPE[$type];
        $dstaddr=$ligne["dstaddr"];
        $nic=$ligne["nic"];
        $dstaddrport=intval($ligne["dstaddrport"]);
        $rulename=$ligne["rulename"];

        if($type==3){$dstaddrport=0;}
        if(intval($dstaddrport)>0){$dstaddr="$dstaddr:$dstaddrport";}
        if(intval($type)==2){$dstaddr="0.0.0.0:$dstaddrport";}
        $TDef="$nic - $NAT_TYPE_TEXT<br><small>&nbsp;$dstaddr</small>";
        if(strlen($rulename)>1){
            $TDef="$rulename <small>($nic - $NAT_TYPE_TEXT)</small>";
        }
        $NICS["{$sNAT_TYPE[$type]}:$ID"]=$TDef;
    }
    $results=$q->QUERY_SQL("SELECT * FROM firehol_masquerade WHERE enabled='1' ORDER BY nic");

    foreach ($results as $index=>$ligne){
        $ifname=$ligne["nic"];
        $ifname_id=intval($ligne["ID"]);
        $znic=new system_nic($ifname);
        if($znic->enabled==0){continue;}
        $NICS["MASQ:$ifname_id"]="MASQUERADE $znic->NICNAME ($ifname)";
    }


    $GLOBALS["LOAD_NICS"][$routers]=$NICS;
    return $NICS;
}





function table_fw_NAT($eth,$TRCLASS){

    if(preg_match("#([A-Z])NAT:([0-9]+)#",$eth,$re)) { $NatID=$re[2];}
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$NatID'");
    $NAT_TYPE   = $ligne["NAT_TYPE"];
    $interface  = $ligne["nic"];
    $dstaddr    = $ligne["dstaddr"];
    $dstaddrport= $ligne["dstaddrport"];
    $jlog       = $ligne["jlog"];
    $STATUS     = "<span class='label label-white'>{unknown}</span>";
    $NICS       = LOAD_NICS(false);
    $Outgoing   = "<span class='label'>OUT</span>&nbsp;";
    $tpl        = new template_admin();
    $tdfree     = $tpl->table_tdfree();
    $td1prc     = $tpl->table_td1prc();
    $pks        = $tpl->icon_nothing();
    $size       = $tpl->icon_nothing();
    $ACTION     = "<span class='label label-primary'>NAT</span>";
    $sNAT_TYPE=$GLOBALS["NAT_TYPE"];
    $IPTABLES_RRULES_STATUS = fw_rules_status();
    $html=array();
    if($NAT_TYPE==3){
        $ACTION     = "<span class='label label-primary'>FORWARD</span>";
        $STATUS     ="<span class='label label-primary'>{default}</span>";
        $dstaddrport=0;}
    if($dstaddrport>0){
        $dstaddr="$dstaddr:$dstaddrport";
    }

    if($jlog==1){
        $jlog=" {and} {log_all_events}";
        $jlogico = "&nbsp;<span class='label label-info'>LOG</span>&nbsp;";
    }

    if(isset($IPTABLES_RRULES_STATUS["NAT"][$NatID])){
        $MAIN=$IPTABLES_RRULES_STATUS["NAT"][$NatID];
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($MAIN["pkts"]));
        $size               = intval($MAIN["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }

    $html[] = "<tr class='$TRCLASS'>";
    $html[] = "<td $td1prc>".$tpl->icon_nothing()."</td>";


    $html[] = "<td $tdfree><strong>{$sNAT_TYPE[$NAT_TYPE]} &nbsp;-&nbsp; $dstaddr{$jlog}</strong></td>";
    $html[] = "<td $td1prc>".fwr_nic($NICS[$interface] ?? $interface)."</td>";
    $html[] = "<td $td1prc>$STATUS</td>";
    $html[] = "<td style='text-align:right' nowrap>$pks</td>";
    $html[] = "<td $td1prc>$size</td>";
    $html[] = "<td $td1prc>$Outgoing{$jlogico}$ACTION</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "</tr>";


    return @implode("\n",$html);

}
function table_fw_policy($interface,$TRCLASS){
    if($interface==null){return;}

    if(preg_match("#NAT:[0-9]+#",$_GET["eth"],$re)){return table_fw_NAT($_GET["eth"],$TRCLASS);}
    if(preg_match("#([a-z]+[0-9]+)2([a-z]+[0-9])$#",$_GET["eth"],$re)){return;}
   // echo "<H1>table_fw_policy $interface</H1>";
    $tpl        = new template_admin();
    $znic       = new system_nic($interface);
    $default    = "{policy}: {default}";
    $td1prc     = $tpl->table_td1prc();
    $NICS       = LOAD_NICS();
    $STATUS          = "<span class='label label-white'>{default}</span>";
    $IPTABLES_RRULES_STATUS = fw_rules_status();
    $text_class = null;
    $html[] = "<tr class='$TRCLASS'>";
    $html[] = "<td class=\"center\"><span class=''>-</span></td>";
    if (($znic->firewall_policy == 'accept') OR ($znic->firewall_policy == null)) {
        $Outgoing = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
        $ACTION = "<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
        $html[] = "<td class='' style='vertical-align:middle'><strong>$default</strong><br>" . $tpl->_ENGINE_parse_body("{finally_allow_all}") . "</td>";
        }

        if ($znic->firewall_policy == 'reject') {
            $Outgoing = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
            $ACTION = "<span class='label label-danger'>DENY</span>";
            $html[] = "<td class='$text_class' style='vertical-align:middle'><span class='$text_class'><strong>$default</strong><br>" . $tpl->_ENGINE_parse_body("{finally_deny_all}") . "</td>";
        }

    if(isset($IPTABLES_RRULES_STATUS[$interface])){
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS[$interface]["pkts"]));
        $size               = intval($IPTABLES_RRULES_STATUS[$interface]["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }


    $html[]="<td style='text-align:left'>".fwr_nic($NICS[$interface] ?? $interface)."</td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc>$Outgoing$ACTION</td>";
    $html[]="<td $td1prc'>".$tpl->icon_nothing()."</td>";
    $html[]="<td $td1prc>".$tpl->icon_nothing()."</td>";
    $html[]="<td $td1prc>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";

    return @implode("\n",$html);

}

function table_fw_itself($TRCLASS,$eth){
    if($eth<>null) {
        if (!preg_match("#^([eth|wan])#", $eth)) {
            return null;
        }
    }
    if(preg_match("#[a-z]+[0-9]+2[a-z]+[0-9]#",$_GET["eth"])){return null;}

    $IPTABLES_RRULES_STATUS = fw_rules_status();

    $text_class = null;
    $ID         = "1000395";
    $color      = null;
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $null_ico   = $tpl->icon_nothing();
    $ACTION="<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    $tdfree = "class='$text_class' style='vertical-align:middle'";
    $td1prc = "widht=1% class='$text_class center' style='vertical-align:middle' nowrap";
    $STATUS = "<span class='label label-white'>{default}</span>";
    $Outgoing = "<span class='label'>&nbsp;&nbsp;OUT&nbsp;&nbsp;</span>&nbsp;";
    $ethName  = "{all_interfaces}";
    $STATUS             = "<span class='label'>{inactive2}</span>";
    $pks                = $tpl->icon_nothing();
    $size               = $tpl->icon_nothing();
    $subrulename        = null;


    if(isset($IPTABLES_RRULES_STATUS["1000395"])){
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS[$ID]["pkts"]));
        $size               = intval($IPTABLES_RRULES_STATUS[$ID]["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }


    $md=md5(time());
    if($eth<>null) {
        $LOAD_NICS = LOAD_NICS();
        $ethName = $LOAD_NICS[$eth];
    }
    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";
    $items=FormatNumber($q->COUNT_ROWS("firehol_itself"));
    $EXPLAIN=$tpl->_ENGINE_parse_body("{firewall_itself_explain}");
    $EXPLAIN=str_replace("%s",$items,$EXPLAIN);
    $js="Loadjs('fw.rules.itself.php');";
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>0/0</span></td>";
    $html[]="<td $tdfree><span class='$text_class' style='font-weight:bold;$color'>".$tpl->td_href("<span id='fw-$ID-rname' class='text-success'>{default}</span>",null,$js)."<span id='fw-$ID-expl' style='color:black;font-weight:normal'><br>$EXPLAIN</span></td>";
    $html[]="<td>".fwr_nic("<span class='$text_class' id='fw-$ID-nic'>$ethName</span>")."</td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc><span id='fw-$ID-jact'>$Outgoing$ACTION</span></td>";
    $html[]="<td $td1prc>".fwr_switch(true)."</td>";
    $html[]="<td $td1prc><br>$null_ico</td>";
    $html[]="<td $td1prc><br>$null_ico</td>";
    $html[]="</tr>";

    return @implode("\n",$html);
}

function table_fw_router($interface,$interface2,$TRCLASS){
    $log=null;$jlogico=null;
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM pnic_bridges 
    WHERE nic_from='$interface' AND nic_to='$interface2'");


    if($ligne["jlog"]==1){
        $log=" {and} {log_all_events}";
        $jlogico = "&nbsp;<span class='label label-info'>LOG</span>&nbsp;";
    }

    $BEHA[1]="{finally_deny_all}";
    $BEHA[0]="{finally_allow_all}";
    $ID         = $ligne["ID"];
    $policy     = $ligne["policy"];
    $tpl        = new template_admin();
    $default    = "{connector}: {packets_from} {$interface} {should_be_forwarded_to} $interface2";
    $td1prc     = $tpl->table_td1prc();
    $NICS       = LOAD_NICS();
    $STATUS          = "<span class='label label-white'>{unknown}</span>";
    $IPTABLES_RRULES_STATUS = fw_rules_status();
    $text_class = "black";
    $html[] = "<tr class='$TRCLASS'>";
    $html[] = "<td class=\"center\"><span class=''>-</span></td>";
    $Outgoing = "<span class='label'>&nbsp;&nbsp;FORWARD&nbsp;&nbsp;</span>&nbsp;";
    if($policy==0) {
        $ACTION = "<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
        $html[] = "<td class='' style='vertical-align:middle'><strong>$default</strong><br>" . $tpl->_ENGINE_parse_body("{finally_allow_all}") . "$log</td>";
    }

    if($policy==1){
        $Outgoing = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
        $ACTION = "<span class='label label-danger'>DENY</span>";
        $html[] = "<td class='$text_class' style='vertical-align:middle;color:black'><span class='$text_class'><strong>$default</strong><br>" . $tpl->_ENGINE_parse_body("{finally_deny_all}") . "$log</td>";


    }


    if(isset($IPTABLES_RRULES_STATUS["ROUTER"][$ID])){
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS["ROUTER"][$ID]["pkts"]));
        $size               = intval($IPTABLES_RRULES_STATUS["ROUTER"][$ID]["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }


    $html[]="<td $td1prc>".fwr_nic($NICS[$interface] ?? $interface)."</td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc>$Outgoing$jlogico$ACTION</td>";
    $html[]="<td $td1prc'>".$tpl->icon_nothing()."</td>";
    $html[]="<td $td1prc>".$tpl->icon_nothing()."</td>";
    $html[]="<td $td1prc>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";

    return @implode("\n",$html);

}

function table_fw_artica($interface,$TRCLASS){
    $tpl             = new template_admin();
    $znic            = new system_nic($interface);
    $NICS            = LOAD_NICS();
    $accept_artica_w = $tpl->_ENGINE_parse_body("{accept_artica_w} {from_trusted_networks}");
    $ACTION          = "<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    $pks             = $tpl->icon_nothing();
    $size            = $tpl->icon_nothing();
    $text_class      = null;
    if($znic->Bridged==1){return;}
    if($znic->enabled==0){return;}
    if($znic->isFW==0){return;}
    if($_GET["search"]<>null) {return;}
    if($znic->firewall_artica == 0){return;}
    $ArticaHttpsPort                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));

    $tdfree = "class='$text_class' style='vertical-align:middle'";
    $td1prc = $tpl->table_td1prc();
    $STATUS = "<span class='label label-white'>{default}</span>";
    $Outgoing = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $pks=$tpl->FormatNumber($pks);

            $html[] = "<tr class='$TRCLASS'>";
            $html[] = "<td $td1prc><span class='$text_class'>-</span></td>";
            $html[] = "<td $tdfree><span class='$text_class'><strong>$accept_artica_w</strong><br>TCP/$ArticaHttpsPort</td>";
            $html[] = "<td $td1prc>".fwr_nic($NICS[$interface] ?? $interface)."</td>";
            $html[] = "<td $td1prc>$STATUS</td>";
            $html[] = "<td style='text-align:right' nowrap>$pks</td>";
            $html[] = "<td $td1prc>$size</td>";
            $html[] = "<td $td1prc>$Outgoing$ACTION</td>";
            $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
            $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
            $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
            $html[] = "</tr>";

            return @implode("\n",$html);
}

function iptables_status(){

    $sock=new sockets();
    $data=json_decode($sock->REST_API("/system/network/iptables/stats"));
    // Même garde que dans table() : property_exists(null) est fatal en PHP 8.
    if(!is_object($data) || !isset($data->Data) || !is_object($data->Data)){return new stdClass();}
    return $data->Data;
}

function table_fw_trusted_networks($TRCLASS){
    $tpl             = new template_admin();
    $pks             = $tpl->icon_nothing();
    $size            = $tpl->icon_nothing();


    $status=iptables_status();
    if(property_exists($status,"Family")) {
        if(property_exists($status->Family,"TRUSTEDNETS")) {
            $pks=$tpl->FormatNumber($status->Family->TRUSTEDNETS->{0}->pkts);
            $size=FormatBytes($status->Family->TRUSTEDNETS->{0}->bytes/1024);
        }
    }
    $accept_artica_w = $tpl->_ENGINE_parse_body("{accept} {all} {from_trusted_networks}");
    $ACTION          = "<span class='label label-primary'>&nbsp;&nbsp;&nbsp;PASS&nbsp;&nbsp;&nbsp;</span>";
    $text_class      = null;


    $tdfree = "class='$text_class' style='vertical-align:middle'";
    $td1prc = $tpl->table_td1prc();
    $STATUS = "<span class='label label-default'>{default}</span>";
    $Outgoing = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";
    $html[] = "<tr class='$TRCLASS'>";
    $html[] = "<td $td1prc><span class='$text_class'>-</span></td>";
    $html[] = "<td $tdfree><span class='$text_class'><strong style='color:black'>$accept_artica_w</strong></td>";
    $html[] = "<td $tdfree>".fwr_nic("{all_interfaces}")."</td>";
    $html[] = "<td $td1prc>$STATUS</td>";
    $html[] = "<td style='text-align:right' nowrap>$pks</td>";
    $html[] = "<td $td1prc>$size</td>";
    $html[] = "<td $td1prc>$Outgoing$ACTION</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "<td $td1prc>" . $tpl->icon_nothing() . "</td>";
    $html[] = "</tr>";

    return array($TRCLASS,@implode("\n",$html));
}


function table(){
	$tpl    = new template_admin();
	$page   = CurrentPageName();
    $q      = new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$eth_sql=null;
	$token=null;
    $TRCLASS=null;
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
    $EnableLinkBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
	$type=$tpl->_ENGINE_parse_body("{type}");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
	$function=$_GET["function"];
    $NICS=LOAD_NICS(true);

	// Pentest 2026-11-04 : eth etait concatene dans la requete.
	$eth_params=array();
	if($_GET["eth"]<>null){$eth_sql=" WHERE eth=?";$eth_params[]=$_GET["eth"];$token="&eth=".req_url($_GET["eth"]);}
	

	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	$interface=$tpl->_ENGINE_parse_body("{interface}");
    $add="Loadjs('$page?ruleid-js=0$token&function=$function',true);";
	// Regles tenues par Artica Meta : ni creation, ni ordre, ni activation, ni suppression.
	$metaRules=firewall_meta_holds("rules");
	$fwsec=firewall_meta_sec($metaRules);





    if(!$metaRules){$topbuttons[] = array($add, ico_plus, "{new_rule}");}
    $topbuttons[] = array("Loadjs('fw.firewall.tester.php?js=yes')", ico_play, "{fw_test_packet}");
    // Les filtres « inutilisées » / « masquées » sont dans la barre de filtres (statut).
    $topbuttons[] = array("FwrApply()", ico_refresh, "{refresh}");
	


	$th_center="data-sortable=true class='text-capitalize center' data-type='text' width='1%'";
	// Lot 6a : filtrage IPv6 coupé (mise à jour non confirmée, ou retour arrière).
	$ipv6=fwapi_data(fwapi_get("ipv6"));
	if(is_array($ipv6) && intval($ipv6["filtering"] ?? 1)==0){
	    $fwFnB=preg_replace('/[^A-Za-z0-9_]/','',(string)$function);
	    $btn=firewall_meta_holds_setting("FirewallIPv6Filtering")?firewall_meta_badge():$tpl->button_autnonome("{fw_ipv6_filtering_enable}","Loadjs('$page?ipv6-enable-js=yes&function=$fwFnB')",ico_shield,"AsFirewallManager",0,"btn-warning");
	    $html[]="<div class='alert alert-warning' style='margin-bottom:10px'>{fw_ipv6_filtering_off_banner}&nbsp;&nbsp;$btn</div>";
	}
	$html[]="<div class='awc-wrap fwr-skin'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>$order/id</th>";
	$html[]="<th>{fwr_rule_conditions}</th>";
	$html[]="<th>{$interface}</th>";
	$html[]="<th class='center'>{status}</th>";
	$html[]="<th style='text-align:right'>{packets}</th>";
	$html[]="<th style='text-align:right'>{fwr_volume}</th>";
	$html[]="<th class='center'>{direction}</th>";
	$html[]="<th class='center'>{enabled}</th>";
	$html[]="<th class='center' colspan='2'>{priority}</th>";
	$html[]="</tr></thead><tbody id='fwr-rows'>";
	
	$jsAfter="LoadAjax('table-firewall-rules','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/iptables/stats");
    $json=json_decode($data);
    // Daemon injoignable ou réponse sans Data : objet vide, sinon les property_exists()
    // de table_cybercrime/table_all_in/table_proxy sont fatals en PHP 8.
    $JSON_STATUS=(is_object($json) && isset($json->Data) && is_object($json->Data))?$json->Data:new stdClass();


	// Filtre de statut : il ne porte que sur les règles de l'opérateur, les lignes
	// système (flux, Artica, proxy, reverse-proxy…) n'ont ni état ni usage relevé.
	$FWR_ST=(string)($_GET["filter"] ?? "");
	if(!in_array($FWR_ST,array("","enabled","disabled","unused","shadowed"),true)){$FWR_ST="";}
	$sysRows=$FWR_ST=="";
	$shownRules=0;

	if($_GET["search"]==null && $sysRows){

        list($TRCLASS,$ipfeeds)=table_cybercrime($TRCLASS,$JSON_STATUS);
        if($ipfeeds<>null){
            $html[]=$ipfeeds;
        }

        $TRCLASS=null;
        $html[]=table_fw_itself($TRCLASS,$_GET["eth"]);
        list($TRCLASS,$table)=table_fw_trusted_networks($TRCLASS);
        $html[]=$table;
    }



	$subquery=null;
	if($_GET["search"]<>null){
			$MULTIPLEACLS=array();
			$rawSearch=trim((string)$_GET["search"]);
			$_GET["search"]="*{$_GET["search"]}*";
			$_GET["search"]=str_replace("**", "*", $_GET["search"]);
			$_GET["search"]=str_replace("**", "*", $_GET["search"]);
			$_GET["search"]=str_replace("*", "%", $_GET["search"]);
			
			
			// acls.db is API-only — resolve matching aclids via the acls backend (items + groups).
			// Objets dont le CONTENU correspond, puis ceux dont le NOM correspond, reunis
			// par gpid : chaque objet n'est resolu en regles qu'une fois (avant, un objet
			// l'etait autant de fois qu'il avait d'items correspondants).
			$matchedGpids=array();
			$foundByItem=acls_api_get("/proxy/acls/sqlite/groups/by-item?search=".urlencode($rawSearch));
			if(is_array($foundByItem)){foreach($foundByItem as $g){
				if(!is_array($g)){continue;}
				$gpid=intval($g["id"] ?? 0); if($gpid>0){$matchedGpids[$gpid]=true;}
			}}else{
				// Repli : articarest sans groups/by-item (null = route absente). items/search
				// plafonne a 20 ITEMS : un objet riche en correspondances masque les autres.
				$foundItems=acls_api_get("/proxy/acls/sqlite/items/search?search=".urlencode($rawSearch));
				if(is_array($foundItems)){foreach($foundItems as $it){
					if(!is_array($it)){continue;}
					$gpid=intval($it["gpid"] ?? 0); if($gpid>0){$matchedGpids[$gpid]=true;}
				}}
			}
			$foundGroups=acls_api_get("/proxy/acls/sqlite/groups/all?search=".urlencode($rawSearch));
			if(is_array($foundGroups)){foreach($foundGroups as $g){
				if(!is_array($g)){continue;}
				$gpid=intval($g["id"] ?? 0); if($gpid>0){$matchedGpids[$gpid]=true;}
			}}
			foreach($matchedGpids as $gpid=>$none){
				$aclids=acls_api_get("/proxy/acls/sqlite/link/acls-by-gpid?table=firewallfilter_sqacllinks&gpid=$gpid");
				if(is_array($aclids)){foreach($aclids as $aid){$aid=intval($aid); if($aid>0){$MULTIPLEACLS[$aid]=$aid;}}}
			}
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $results=$q->QUERY_SQL_PARAMS("SELECT ID FROM iptables_main WHERE rulename LIKE ? OR description LIKE ? OR section LIKE ?",array($_GET["search"],$_GET["search"],$_GET["search"]));
        // Motif de recherche des services : le texte saisi est échappé (preg_quote),
        // seuls ses « * » deviennent des jokers. Il était interpolé tel quel dans
        // la regex — une saisie invalide la cassait, une saisie hostile la pilotait.
        $serviceRegex="#".str_replace("\\*",".*?",preg_quote("*$rawSearch*","#"))."#i";
        foreach ($results as $index=>$ligne){$MULTIPLEACLS[$ligne["ID"]]=$ligne["ID"];}
	
		$sql="SELECT * FROM iptables_main";
		$results=$q->QUERY_SQL($sql);
		foreach ($results as $index=>$ligne){
			$service=$ligne["service"];
			$services_containers=unserialize(base64_decode($ligne["services_container"]));
			if($service<>null){$services_containers[$service]=true;}
			if(!is_array($services_containers)){$services_containers=array();}
			foreach ($services_containers as $service=>$none){
				if(preg_match($serviceRegex, $service)){
					$MULTIPLEACLS[$ligne["ID"]]=$ligne["ID"];
				}
			}
		}
	
		$sbquery=array();
		if($_GET["eth"]==null){$WHERE="WHERE";}else{$WHERE="AND";}
		foreach ($MULTIPLEACLS as $aclid=>$none){$sbquery[]="(ID=$aclid)";}
		if(count($sbquery)>0){$subquery=" $WHERE ".@implode(" ", $sbquery)."";}
		if(count($sbquery)>1){$subquery=" $WHERE (".@implode(" OR ", $sbquery).")";}
			

	}



	$IPTABLES_RRULES_STATUS = fw_rules_status();
	$sqlsrc                 = "SELECT * FROM iptables_main$eth_sql$subquery ORDER BY zOrder";
	$results                = $q->QUERY_SQL_PARAMS($sqlsrc,$eth_params);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error."<hr>$sqlsrc");}

    /*
    list($TRCLASS,$ipfeeds)=table_crowdsec($TRCLASS,$IPTABLES_RRULES_STATUS);
    if($ipfeeds<>null){
        $html[]=$ipfeeds;
    }
    */



    $FW_PROBLEMS=fwapi_rule_problems();
    // Dernière utilisation (relevée toutes les 5 min) et filtres fermés.
    $FW_USAGE=fwapi_rules_usage();
    $FW_FILTER=(string)($_GET["filter"] ?? "");
    if($FW_FILTER!="unused" && $FW_FILTER!="shadowed"){$FW_FILTER="";}
    $prevSec=null;$prevEth=null;$secRows=false;

	foreach ($results as $index=>$ligne){
		$disabled_explain   = null;
		$text_class         = null;
		$color              = null;
		$md                 = md5(serialize($ligne));
        $ID                 = intval($ligne["ID"]);
        $STATUS             = "<span class='label'>{inactive2}</span>";
        $pks                = $tpl->icon_nothing();
        $size               = $tpl->icon_nothing();
        $subrulename        = null;
        $MOD                = $ligne["MOD"];
        $textBadInterface   = "";

        //echo "<H1>{$ligne["eth"]}</H1>";



        if(isset($IPTABLES_RRULES_STATUS[$ID])){
            $STATUS             ="<span class='label label-primary'>{active2}</span>";
            $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS[$ID]["pkts"]));
            $size               = intval($IPTABLES_RRULES_STATUS[$ID]["bytes"]);
            if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
        }
		
		if($ligne["enabled"]==0) {
            if ($ligne["accepttype"] <> "lINK") {
                $color = "color:#8a8a8a;text-decoration:underline";
            }
        }

		if($EnableLinkBalancer==0){
            if ($ligne["accepttype"] == "lINK") {$color = "color:#8a8a8a;text-decoration:underline";}
        }
        $TRCLASS=null;
		// Sections : un en-tête repliable chaque fois que la section change
		// (l'ordre reste zOrder : une section n'est qu'une étiquette).
		$sec=trim((string)($ligne["section"] ?? ""));
		$ethKey=(string)$ligne["eth"];
		if($sec!==$prevSec || $ethKey!==$prevEth){
		    $prevSec=$sec;$prevEth=$ethKey;
		    if($sec!=""){
		        $secRows=true;
		        $secKey=md5($ethKey."|".$sec);
		        $secLabel=str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars($sec));
		        $html[]="<tr class='fw-section-row'><td colspan='10' style='background:#f3f3f4;font-weight:bold;cursor:pointer' OnClick=\"FwSecToggle('$secKey')\"><i class='fa fa-caret-down' id='fwsec-ico-$secKey'></i>&nbsp;$secLabel</td></tr>";
		    }
		}
		$secAttr=$sec!=""?" data-fwsec='".md5($ethKey."|".$sec)."'":"";

		if($ligne["enabled"]==0){
			$text_class=" text-muted";
		}
		
		
		if($ligne["rulename"]==null){$ligne["rulename"]="No name";}

		if(preg_match("#<br><small>(.+?)<\/small>#",$ligne["rulename"],$re)){
            $subrulename="<br><small>{$re[1]}</small>";
            $ligne["rulename"]=str_replace("<br><small>{$re[1]}</small>","",$ligne["rulename"]);
        }

        $NICS_TEXT = $NICS[$ligne["eth"]];
        // Problèmes calculés par le daemon (fwconfig.RuleProblems) : une seule
        // définition de « cette règle ne peut pas s'appliquer ».
        if(isset($FW_PROBLEMS[$ID])){
            $textBadInterface="<span class='label label-warning'>{$FW_PROBLEMS[$ID]}</span>&nbsp;&nbsp;";
            $text_class=" text-muted";
        }
        if($FW_FILTER=="unused" && empty($FW_USAGE[$ID]["unused"])){continue;}
        if($FW_FILTER=="shadowed" && strpos((string)($FW_PROBLEMS[$ID] ?? ""),"{fw_rule_shadowed}")===false){continue;}
        if($FWR_ST=="enabled" && intval($ligne["enabled"])!=1){continue;}
        if($FWR_ST=="disabled" && intval($ligne["enabled"])==1){continue;}
        $shownRules++;
        $lastUsed="";
        if(isset($FW_USAGE[$ID])){
            $lh=intval($FW_USAGE[$ID]["last_hit"] ?? 0);
            $fs=intval($FW_USAGE[$ID]["first_seen"] ?? 0);
            if($lh>0){$lastUsed="{fw_last_used}: ".distanceOfTimeInWords($lh,time());}
            elseif($fs>0 && time()-$fs<30*86400){$lastUsed="{fw_in_observation}";}
            else{$lastUsed="{fw_last_used}: {fw_never_used}";}
            if(!empty($FW_USAGE[$ID]["unused"])){$lastUsed.=" <span class='label label-default'>{fw_unused_30d}</span>";}
            $lastUsed="<span class='fwr-obs'>$lastUsed</span>";
        }


        if($EnableLinkBalancer==0){
            if ($ligne["accepttype"] == "lINK") {
                $NICS_TEXT = $ligne["eth"] . " - {disabled}";}
        }



        if(!preg_match("#NAT:[0-9]+#",$ligne["eth"])) {
            if ($ligne["accepttype"] <> "lINK") {
                if (!isset($NICS[$ligne["eth"]])) {
                    $NICS_TEXT = $ligne["eth"] . " - {disabled}";
                    $text_class = " text-muted";
                    $disabled_explain = "yes";
                }
            }
        }

		$up=$tpl->icon_up("Loadjs('$page?move-js=yes&id=$ID&dir=up&eth={$ligne["eth"]}')",$fwsec);
		$down=$tpl->icon_down("Loadjs('$page?move-js=yes&id=$ID&dir=down&eth={$ligne["eth"]}')",$fwsec);
		
		if($ligne["zOrder"]==0){$up=null;}
		$js         = "Loadjs('$page?ruleid-js=$ID&function=$function',true);";
		$delete     = $tpl->icon_delete("Loadjs('$page?delete-rule-js=$ID&md=$md')",$fwsec);
		$EXPLAIN    = EXPLAIN_THIS_RULE($ligne,$disabled_explain);
		$tdfree     = $tpl->table_tdfree($text_class);
		$td1prc     = $tpl->table_td1prc($text_class);
		$td_enable  = fwr_switch(intval($ligne["enabled"])==1,$metaRules?"":"Loadjs('$page?enable-ruleid=$ID');","fwr-sw-$ID");

		$rule_action= rule_action_status($ligne);
		if($MOD=="IPFEED"){$delete=$tpl->icon_nothing();}

		$desc=trim((string)($ligne["description"] ?? ""));
		$descHtml=$desc==""?"":"<div class='text-muted small' style='white-space:normal'>".str_replace(array("{","}"),array("&#123;","&#125;"),nl2br(htmlspecialchars($desc)))."</div>";
		$limits="";
		if(intval($ligne["conn_limit"] ?? 0)>0 || intval($ligne["rate_limit"] ?? 0)>0){
		    $lt=array();
		    if(intval($ligne["conn_limit"])>0){$lt[]="&le; ".intval($ligne["conn_limit"])." {fw_conn_simultaneous}";}
		    if(intval($ligne["rate_limit"])>0){$lt[]="&le; ".intval($ligne["rate_limit"])."/".(($ligne["rate_unit"] ?? "")=="minute"?"min":"s");}
		    $limits="&nbsp;<i class='fas fa-tachometer-alt' title='".implode(", ",$lt)."'></i>";
		}
		$fam=(string)($ligne["family"] ?? "both");
		if($fam=="ipv4" || $fam=="ipv6"){$limits.="&nbsp;<span class='label label-info'>".($fam=="ipv4"?"IPv4":"IPv6")."</span>";}
		$html[]="<tr class='$TRCLASS' id='$md'$secAttr>";
		$html[]="<td $td1prc><span class='$text_class'>{$ligne["zOrder"]}/{$ligne["ID"]}</span></td>";
		$html[]="<td $tdfree><span class='$text_class' style='font-weight:bold;$color'>$textBadInterface".
            $tpl->td_href("<span id='fw-$ID-rname' class='text-success'>{$ligne["rulename"]}</span>",null,$js)."{$subrulename}".fw_rule_expiry_badge($ligne)."$limits</span>$descHtml<span id='fw-$ID-expl'>$EXPLAIN</span>
        </td>";
		$html[]="<td $tdfree nowrap>".fwr_nic("<span class='$text_class' id='fw-$ID-nic'>$NICS_TEXT</span>")."</td>";
        $html[]="<td $td1prc>$STATUS$lastUsed</td>";
        $html[]="<td style='text-align:right' nowrap>$pks</td>";
        $html[]="<td $td1prc>$size</td>";
		$html[]="<td $td1prc><span id='fw-$ID-jact'>$rule_action</span></td>";
		$html[]="<td $td1prc>$td_enable</td>";
		$html[]="<td $td1prc>$up&nbsp;&nbsp;$down</td>";
		$html[]="<td $td1prc>$delete</td>";
		$html[]="</tr>";
	
	}
	if($secRows){
	    // Repli des sections, mémorisé dans le navigateur (try/catch : stockage
	    // parfois indisponible).
	    $html[]="<script>
function FwSecToggle(k){var rows=document.querySelectorAll(\"tr[data-fwsec='\"+k+\"']\");if(!rows.length){return;}
var hide=rows[0].style.display!='none';rows.forEach(function(r){r.style.display=hide?'none':'';});
var i=document.getElementById('fwsec-ico-'+k);if(i){i.className=hide?'fa fa-caret-right':'fa fa-caret-down';}
try{var s=JSON.parse(localStorage.getItem('fwRuleSections')||'{}');if(hide){s[k]=1;}else{delete s[k];}localStorage.setItem('fwRuleSections',JSON.stringify(s));}catch(e){}}
try{var s=JSON.parse(localStorage.getItem('fwRuleSections')||'{}');Object.keys(s).forEach(function(k){FwSecToggle(k);});}catch(e){}
</script>";
	}
	reset($nicZ);



  if($sysRows){
    list($TRCLASS,$ipfeeds)=table_nginx($TRCLASS,$IPTABLES_RRULES_STATUS);
    if($ipfeeds<>null){
        $html[]=$ipfeeds;
    }
    list($TRCLASS,$ipfeeds,$tpl)=table_proxy($TRCLASS,$JSON_STATUS,$tpl);
    if($ipfeeds<>null){
        $html[]=$ipfeeds;
    }


    list($TRCLASS,$ipfeeds)=table_all_in($TRCLASS,$JSON_STATUS);
    if($ipfeeds<>null){
        $html[]=$ipfeeds;
    }


	if(count($nicZ)==0){
		$TRCLASS=null;
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td colspan='10'><strong>Interface as no array ?</strong></td>";
		$html[]="</tr>";
		
	}
	
	$nicZ["lo"]="{interface} {loopback}";
    if(!$sysRows){$nicZ=array();}
    foreach ($nicZ as $yinter=>$line){
		if($_GET["eth"]<>null){
			if($yinter<>$_GET["eth"]){continue;}
            $TRCLASS=null;
            $html[]=table_fw_artica($_GET["eth"],$TRCLASS);
		}else {
            $TRCLASS=null;
            $html[]=table_fw_artica($_GET["eth"],$TRCLASS);
        }
    }

    if($_GET["eth"]<>null){
        $TRCLASS=null;
        $html[]=table_fw_policy($_GET["eth"],$TRCLASS);
    }
    if(preg_match("#([a-z]+[0-9]+)2([a-z]+[0-9])$#",$_GET["eth"],$re)){
        $TRCLASS=null;
        $html[]=table_fw_router($re[1],$re[2],$TRCLASS);
    }
  }


    $TINY_ARRAY["TITLE"]="{administrate_your_firewall}";
    $TINY_ARRAY["ICO"]=ico_firewall;
    $TINY_ARRAY["EXPL"]="<strong>{firewall_rules}</strong>: <i>{firewall_about}</i>";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // L'ancien pied -- une <ul class='pagination'> dans un <tfoot> -- etait rempli
    // par footable; le pager awc vit en dehors du tableau.
    $html[]="</tbody></table></div>";
    // Sentinelle de l'indicateur d'application : DANS le fragment rafraîchi, pour
    // que le timer d'ArticaRefresh s'arrête avec le tableau.
    $totalRules=0;
    $cnt=$q->mysqli_fetch_array("SELECT COUNT(*) AS n FROM iptables_main");
    if(is_array($cnt)){$totalRules=intval($cnt["n"] ?? 0);}
    $html[]="<div class='awc-foot'><span>{fwr_rules_shown}: <strong>$shownRules</strong> / <strong>$totalRules</strong></span>"
           ."<span id='fw-apply-status'></span>"
           ."<span class='awc-pg' id='fwr-pager'></span></div>";
    $html[]="</div></div>";

    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $script="<script>$headsjs\nNoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n"
        .$tpl->RefreshInterval_js("fw-apply-status","fw.firewall.apply.status.php","status=yes")."\n</script>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	echo awc_table_style().fwr_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n",$html))
	    .$script
	    .awc_pager_js(100,"fwr-rows","fwr-pager");
return true;
}

function table_crowdsec_enabled():bool{
    $EnableCrowdSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdSec"));
    if($EnableCrowdSec==0){return false;}

    return true;
}
function table_cybercrime($TRCLASS,$JSONSTATUS):array{
    $text_class = null;
    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";

    if(!property_exists($JSONSTATUS,"Family")){
        return array($TRCLASS,"");
    }
    $Family=$JSONSTATUS->Family;


    if(!property_exists($Family,"NFQUEUE_WHITELIST")){
        return array($TRCLASS,"");
    }

    $md=md5(__FUNCTION__);
    $tpl=new template_admin();
    $text_class="text-muted";
    $Outgoing   = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $rule_action="<span class='label label-primary'>ALLOW</span>";
    $tdfree     = $tpl->table_tdfree($text_class);
    $td1prc     = $tpl->table_td1prc($text_class);
    $STATUS     ="<span class='label label-default'>{inactive2}</span>";

    $EnableArticaNFQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue"));
    if($EnableArticaNFQueue==0){
        $TRCLASS=null;
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $td1prc><span class='$text_class'>*/*</span></td>";
        $html[]="<td $tdfree><span class='$text_class'><strong class='text-muted'>{CybercrimeIPFeeds}</strong></td>";
        $html[]="<td $tdfree>".fwr_nic("{all_interfaces}")."</td>";
        $html[]="<td $td1prc>$STATUS</td>";
        $html[]="<td $td1prc>-</td>";
        $html[]="<td $td1prc>-</td>";
        $html[]="<td $td1prc>$Outgoing$rule_action</td>";
        $html[]="<td $td1prc>".fwr_switch(true)."</td>";
        $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
        $html[]="<td $td1prc>-</td>";
        $html[]="</tr>";
        return array($TRCLASS,@implode("\n",$html));
    }
    $text_class="";
    $STATUS     ="<span class='label label-primary'>{active2}</span>";

    $pks_white=0;
    $pks_black=0;
    $size_white=0;
    $size_black=0;
    $STATUS_WHITE     ="<span class='label label-default'>{inactive2}</span>";
    $STATUS_BLACK     ="<span class='label label-default'>{inactive2}</span>";

        if(property_exists($JSONSTATUS->Family,"NFQUEUE_WHITELIST")) {
            $STATUS_WHITE     ="<span class='label label-primary'>{active2}</span>";
            $pks_white=$JSONSTATUS->Family->NFQUEUE_WHITELIST->{"0"}->pkts;
            $size_white=$JSONSTATUS->Family->NFQUEUE_WHITELIST->{"0"}->bytes;
            $size_white=FormatBytes($size_white/1024);
        }
        if(property_exists($JSONSTATUS->Family,"NFQUEUE_BLACKLIST")) {
            $STATUS_BLACK     ="<span class='label label-primary'>{active2}</span>";
            $pks_black=$JSONSTATUS->Family->NFQUEUE_BLACKLIST->{"0"}->pkts;
            $size_black=$JSONSTATUS->Family->NFQUEUE_BLACKLIST->{"0"}->bytes;
            $size_black=FormatBytes($size_black/1024);
        }
    $pks_white=$tpl->FormatNumber($pks_white);
    $pks_black=$tpl->FormatNumber($pks_black);

    $TRCLASS=null;
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>*/*</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{CybercrimeIPFeeds} {legitimate_traffic}</strong></td>";
    $html[]="<td $tdfree>".fwr_nic("{all_interfaces}")."</td>";
    $html[]="<td $td1prc>$STATUS_WHITE</td>";
    $html[]="<td style='text-align:right' nowrap>$pks_white</td>";
    $html[]="<td $td1prc>$size_white</td>";
    $html[]="<td $td1prc>$Outgoing$rule_action</td>";
    $html[]="<td $td1prc>".fwr_switch(true)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";

    $rule_action="<span class='label label-danger'>DENY</span>";
    $TRCLASS=null;
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>*/*</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{CybercrimeIPFeeds} {legitimate_traffic2}</strong></td>";
    $html[]="<td $tdfree>".fwr_nic("{all_interfaces}")."</td>";
    $html[]="<td $td1prc>$STATUS_BLACK</td>";
    $html[]="<td style='text-align:right' nowrap>$pks_black</td>";
    $html[]="<td $td1prc>$size_black</td>";
    $html[]="<td $td1prc>$Outgoing$rule_action</td>";
    $html[]="<td $td1prc>".fwr_switch(true)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";
    return array($TRCLASS,@implode("\n",$html));

}

function table_all_in($TRCLASS,$JSON_STATUS):array{
    $text_class = null;
    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";

    if(!property_exists($JSON_STATUS,"Family")){
        return array($TRCLASS,"");
    }
    $Family=$JSON_STATUS->Family;


    if(!property_exists($Family,"INALL")){
        return array($TRCLASS,"");
    }

    $md=md5(__FUNCTION__);
    $tpl=new template_admin();

    $Outgoing   = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $rule_action="<span class='label label-primary'>ALLOW</span>";
    $tdfree     = $tpl->table_tdfree($text_class);
    $td1prc     = $tpl->table_td1prc($text_class);
    $STATUS     ="<span class='label label-primary'>{active2}</span>";



    if(!property_exists($Family->INALL,"0")){
        return array($TRCLASS,"");
    }

    $pks                = FormatNumber($Family->INALL->{0}->pkts);
    $size               = $Family->INALL->{0}->bytes;
    if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}


    $TRCLASS=null;
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>*/*</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{default} {inbound}</strong></td>";
    $html[]="<td $tdfree>".fwr_nic("{all_interfaces}")."</td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc>$Outgoing$rule_action</td>";
    $html[]="<td $td1prc>".fwr_switch(true)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";

    $Outgoing   = "<span class='label'>OUT</span>&nbsp;";
    $pks                = FormatNumber($Family->OUTALL->{0}->pkts);
    $size               = $Family->OUTALL->{0}->bytes;
    if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}

    $TRCLASS=null;
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>*/*</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{default} {outbound}</strong></td>";
    $html[]="<td $tdfree>".fwr_nic("{all_interfaces}")."</td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc><span id='fw-ipfeeds-jact'>$Outgoing$rule_action</span></td>";
    $html[]="<td $td1prc>".fwr_switch(true)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";


    return array($TRCLASS,@implode("\n",$html));

}

function table_proxy($TRCLASS,$JSONSTATUS,$tpl):array{
    $rule_action="<span class='label label-danger'>DENY</span>";
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $page=CurrentPageName();
    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){
        return array($TRCLASS,"",$tpl);
    }

    $STATUS_OUTGOING="<span class='label label-default'>{disabled}</span>";
    $pks_in=0;
    $pks_out=0;
    $size_out=0;
    $size_in=0;
    if(property_exists($JSONSTATUS,"Family")){
        if(property_exists($JSONSTATUS->Family,"PROXYRULES_IN")) {
            $STATUS_INCOMING     ="<span class='label label-primary'>{active2}</span>";
            $pks_in=$JSONSTATUS->Family->PROXYRULES_IN->{"0"}->pkts;
            $size_in=$JSONSTATUS->Family->PROXYRULES_IN->{"0"}->bytes;
            $size_in=FormatBytes($size_in/1024);
        }
        if(property_exists($JSONSTATUS->Family,"PROXYRULES_OUT")) {
            $STATUS_OUTGOING     ="<span class='label label-primary'>{active2}</span>";
            $pks_out=$JSONSTATUS->Family->PROXYRULES_OUT->{"0"}->pkts;
            $size_out=$JSONSTATUS->Family->PROXYRULES_OUT->{"0"}->bytes;
            $size_out=FormatBytes($size_out/1024);
        }

    }



    $SQUIDEnableFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnableFirewall"));

    $text_class="";
    $TRCLASS=null;
    $md         = md5(time());
    $tdfree     = $tpl->table_tdfree($text_class);
    $td1prc     = $tpl->table_td1prc($text_class);
    $Outgoing           = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";

    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";

    $APP_SQUID_OUTGOING=$tpl->td_href("{APP_SQUID}","","Loadjs('$page?squid-outgoing-js=yes&function=$function')");
    $APP_SQUID_INCOMING=$tpl->td_href("{APP_SQUID}","","Loadjs('$page?squid-incoming-js=yes&function=$function')");

    $InTCP=squid_incoming_data();
    $CountOfInTCP=count($InTCP);
    if($CountOfInTCP==0){
        $CountOfInTCP=3;
    }
    $explain_incoming=$tpl->_ENGINE_parse_body("{squid_fw_incoming_explain}");
    $explain_incoming=str_replace("%s","<strong>$CountOfInTCP</strong>",$explain_incoming);
    list($OutUDPArray,$OutTCPArray)=squid_outgoing_datas();
    $CountOfOut=count($OutUDPArray)+count($OutTCPArray);
    $explain_outgoing=$tpl->_ENGINE_parse_body("{squid_fw_outgoing_explain}");
    $explain_outgoing=str_replace("%s","<strong>$CountOfOut</strong>",$explain_outgoing);

    if($SQUIDEnableFirewall==0){
        $STATUS_INCOMING     ="<span class='label label-default'>{disabled}</span>";
        $STATUS_OUTGOING="<span class='label label-default'>{disabled}</span>";
        $rule_action="<span class='label label-default'>DENY</span>";
    }

    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>0/0</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>$APP_SQUID_INCOMING </strong> ({incoming2})<br><span>$explain_incoming</span></td>";
    $html[]="<td $tdfree><span class='$text_class'>".fwr_nic("{all_interfaces}")."</span></td>";
    $html[]="<td $td1prc>$STATUS_INCOMING</td>";
    $html[]="<td style='text-align:right' nowrap>$pks_in</td>";
    $html[]="<td $td1prc>$size_in</td>";
    $html[]="<td $td1prc><span id='fw-ipfeeds-jact'>$Outgoing$rule_action</span></td>";
    $html[]="<td $td1prc>".fwr_switch(intval($SQUIDEnableFirewall)==1,"Loadjs('$page?SQUIDEnableFirewall=yes&function=$function');")."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";


    $Outgoing           = "<span class='label'>OUT</span>&nbsp;";
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>0/0</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>$APP_SQUID_OUTGOING</strong> ({outgoing})<br>$explain_outgoing</td>";
    $html[]="<td $tdfree><span class='$text_class'>".fwr_nic("{all_interfaces}")."</span></td>";
    $html[]="<td $td1prc>$STATUS_OUTGOING</td>";
    $html[]="<td style='text-align:right' nowrap>$pks_out</td>";
    $html[]="<td $td1prc>$size_out</td>";
    $html[]="<td $td1prc><span id='fw-ipfeeds-jact'>$Outgoing$rule_action</span></td>";
    $html[]="<td $td1prc>".fwr_switch(intval($SQUIDEnableFirewall)==1,"Loadjs('$page?SQUIDEnableFirewall=yes&function=$function');")."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";
    return array($TRCLASS,@implode("\n",$html),$tpl);
}




function table_nginx($TRCLASS,$IPTABLES_RRULES_STATUS):array{
    $tpl=new template_admin();
    $EnableNginx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==0){
        return array($TRCLASS,"");
    }
    $results=fetch_stream_ports_all();
    $INT=array();
    $ports=array();

    foreach ($results as $index=>$ligne){

        $interface=$ligne["interface"];
        $port=intval($ligne["port"]);
        $INT[$interface]=true;
        $ports[$port]=true;
    }
    $ifnames=array();
    foreach ($INT as $interface=>$pl){
        $ifnames[]=$interface;
    }
    $ifports=array();
    foreach ($ports as $port=>$pt){
        $ifports[]=$port;
    }
    if(count($ifports)==0){
        return array($TRCLASS,"");
    }
    $firewall_rulenginx=$tpl->_ENGINE_parse_body("{firewall_rulenginx}");
    $firewall_rulenginx=str_replace("%ifports","<strong>".@implode(",",$ifports)."</strong>",$firewall_rulenginx);
    $firewall_rulenginx=str_replace("%if","<strong>".@implode(",",$ifnames)."</strong>",$firewall_rulenginx);


    $text_class=null;
    $TRCLASS=null;
    $md         = md5($firewall_rulenginx);
    $tdfree     = $tpl->table_tdfree($text_class);
    $td1prc     = $tpl->table_td1prc($text_class);

    if(isset($IPTABLES_RRULES_STATUS["REVERSEPROXY"])){
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS["REVERSEPROXY"]["pkts"]));
        $size               = intval($IPTABLES_RRULES_STATUS["REVERSEPROXY"]["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }
    $Outgoing           = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $rule_action="<span class='label label-primary'>ALLOW</span>";

    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>0/0</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{APP_NGINX}</strong><br><span id='fw-ipfeeds-expl'>$firewall_rulenginx</span></td>";
    $html[]="<td $td1prc><span class='$text_class' id='fw-ipfeeds-nic'>-</span></td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc><span id='fw-ipfeeds-jact'>$Outgoing$rule_action</span></td>";
    $html[]="<td $td1prc>".fwr_switch(true)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";
    return array($TRCLASS,@implode("\n",$html));
}

function table_crowdsec($TRCLASS,$IPTABLES_RRULES_STATUS):array{
    $text_class = null;
    $iconNet="<i class='".ico_nic." $text_class'></i>&nbsp;";
    $tpl=new template_admin();
    $STATUS             = "<span class='label'>{inactive2}</span>";
    $tfile=PROGRESS_DIR."/crowdsec-blacklists.status";
    if(!table_crowdsec_enabled()){
        return array($TRCLASS,"");
    }
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("crowdsec.php?ipset-status=yes");
    $results=explode("\n",@file_get_contents($tfile));
    $entries=0;
   foreach ($results as $line){
        if(preg_match("#Number of entries:\s+([0-9]+)#",$line,$re)){
            $entries=$tpl->FormatNumber($re[1]);
        }

   }

    if(isset($IPTABLES_RRULES_STATUS["CROWDSEC"])){
        $STATUS             ="<span class='label label-primary'>{active2}</span>";
        $pks                = FormatNumber(intval($IPTABLES_RRULES_STATUS["CROWDSEC"]["pkts"]));
        $size               = intval($IPTABLES_RRULES_STATUS["CROWDSEC"]["bytes"]);
        if($size<1024){$size="$size Bytes";}else{$size=FormatBytes($size/1024);}
    }
    $Outgoing           = "<span class='label'>&nbsp;&nbsp;IN&nbsp;&nbsp;</span>&nbsp;";
    $rule_action="<span class='label label-danger'>DENY</span>";


    $TRCLASS=null;
    $md="none";
    $tdfree     = $tpl->table_tdfree($text_class);
    $td1prc     = $tpl->table_td1prc($text_class);

    $APP_CROWDSEC_EXPLAIN=$tpl->_ENGINE_parse_body("{APP_CROWDSEC_EXPLAIN}");
    $APP_CROWDSEC_EXPLAIN=substr($APP_CROWDSEC_EXPLAIN,0,120)."...";
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td $td1prc><span class='$text_class'>0/0</span></td>";
    $html[]="<td $tdfree><span class='$text_class'><strong style='color:black'>{APP_CROWDSEC}</strong> (<strong style='color:black'>$entries {records}</strong>)<br><span id='fw-ipfeeds-expl'>$APP_CROWDSEC_EXPLAIN</span></td>";
    $html[]="<td $td1prc><span class='$text_class' id='fw-ipfeeds-nic'>".fwr_nic("{all_interfaces}")."</span></td>";
    $html[]="<td $td1prc>$STATUS</td>";
    $html[]="<td style='text-align:right' nowrap>$pks</td>";
    $html[]="<td $td1prc>$size</td>";
    $html[]="<td $td1prc><span id='fw-ipfeeds-jact'>$Outgoing$rule_action</span></td>";
    $html[]="<td $td1prc>".fwr_switch(true)."</td>";
    $html[]="<td $td1prc><br>&nbsp;&nbsp;</td>";
    $html[]="<td $td1prc>-</td>";
    $html[]="</tr>";
    return array($TRCLASS,@implode("\n",$html));

}


function EXPLAIN_THIS_RULE($ligne,$disabled=null){
    $function=$_GET["function"];
    $tpl                    = new template_admin();
    $APP_XTABLES_INSTALLED  = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_XTABLES_INSTALLED");

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    if(is_numeric($ligne)){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM iptables_main WHERE ID=".intval($ligne));
    }


    $EnableLinkBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
	$red_color="#d32d2d";
	$color="black";
	$log=null;
	if($ligne["enabled"]==0) {
        if ($ligne["accepttype"] <> "lINK") {
            $red_color = "#8a8a8a";
            $color = "#8a8a8a";
        }
    }

	if($disabled=="yes"){$red_color="#8a8a8a";$color="#8a8a8a";}
	$service_text="service2";
	$service=$ligne["service"];
	$MARK=intval($ligne["MARK"]);
	$eth=$ligne["eth"];
    $EnablenDPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablenDPI"));
	
	$ForwardTo=$ligne["ForwardTo"];
	$ForwardNIC=$ligne["ForwardNIC"];
	$xt_ratelimit=intval($ligne["xt_ratelimit"]);
	$xt_ratelimit_dir=trim($ligne["xt_ratelimit_dir"]);
	
	$services_containers=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["services_container"]);
	if($service<>null){$services_containers[$service]=true;}

    $tservices=array();
	foreach ($services_containers as $service=>$bb){
		if(trim($service)==null){continue;}
        if($service=="RustDesk"){
            $tservices[]="&laquo;<span style='font-weight:bold;color:$color;'>$service</span>&raquo;";
        }
		// Pentest 2026-11-04 : service etait concatene dans la requete.
		$resultsS=$q->QUERY_SQL_PARAMS("SELECT * FROM firehol_services_def WHERE service=?",array($service));
		$ligneS=(is_array($resultsS) && isset($resultsS[0]))?$resultsS[0]:array();
        if(!isset($ligneS["service"])){$ligneS["service"]=0;}
		if($ligneS["service"]==null){continue;}
		if($ligneS["enabled"]==0){continue;}
		$tservices[]="&laquo;<a href=\"javascript:blur();\" OnClick=\"Loadjs('fw.services.php?service-js=$service')\" style='font-weight:bold;color:$color;text-decoration:underline'>$service</a>&raquo;";
	}
	if(count($tservices)>1){$service_text="services";}
	if(count($tservices)>0){$service="<br>{{$service_text}} ".@implode(", ", $tservices);}

	if($ligne["jlog"]==1){
		$log=" {and} {log_all_events}";
	}

    if($EnablenDPI==1){
        $mDPI=mDPI($ligne["ID"]);
        if($mDPI<>null) {
            if($service<>null){$service="$service {and}";}
            $service = "$service<br>{application} &laquo;$mDPI&raquo;";
        }
	}

	if($ligne["accepttype"]=="ACCEPT"){
        $action="{then} {accept}$log $eth";
	    if(preg_match("#XNAT:([0-9]+)#",$eth,$re)){
	        $NAT_ID=$re[1];
            $ligne_nat=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$NAT_ID'");
            $NAT_TYPE_TEXT=$GLOBALS["NAT_TYPE"][$ligne_nat["NAT_TYPE"]];
            $rulename=$ligne["rulename"]." ";
            $action="{then} $rulename$NAT_TYPE_TEXT {$ligne_nat["dstaddr"]}";
        }
        if(preg_match("#DNAT:([0-9]+)#",$eth,$re)){
            $NAT_ID=$re[1];
            $ligne_nat=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$NAT_ID'");
            $NAT_TYPE_TEXT=$GLOBALS["NAT_TYPE"][$ligne_nat["NAT_TYPE"]];
            $rulename=$ligne["rulename"]." ";
            $action="{then} ".$tpl->td_href("$rulename$NAT_TYPE_TEXT","","Loadjs('fw.nat.php?ruleid-js=1&function=$function')")." {$ligne_nat["dstaddr"]}";
        }
    }
    if($ligne["accepttype"]=="TPROXY"){
        if($ligne["ForwardToPort"]==0){$ligne["ForwardToPort"]=8080;}
        $address="{$ligne["ForwardTo"]}:{$ligne["ForwardToPort"]}";
        if(!preg_match("#^[0-9\.]+:[1-9][0-9]+#",$address)){
            $action="<strong class='text-danger'>{Corrupted}</strong>";
        }else{
            $action="{then} {forward_to_proxy} &laquo;$address&raquo;$log";
        }
    }
	if($ligne["accepttype"]=="MARK"){
		
		$action="{then} {mark_packets_with} &laquo;$MARK&raquo;";
		if($ForwardNIC<>null){$action=$action." {using_the_interface} $ForwardNIC";}
		if($ForwardTo<>null){$action=$action." {and_forward_network_packets_to_the_gateway} $ForwardTo";}
		$action=$action.$log;
	}
    if($ligne["accepttype"]=="lINK") {
        $MARK_BALANCE=intval($ligne["MARK_BALANCE"]);
        if($MARK_BALANCE>0) {
            $ligne_mark = $q->mysqli_fetch_array("SELECT Interface FROM link_balance WHERE ID='$MARK_BALANCE'");
            $Interface = trim($ligne_mark["Interface"]);
            $nic = new system_nic($Interface);

            $action = "{then} {redirect_to_the_specified_link}  $Interface $nic->NICNAME";
            if ($ForwardNIC <> null) {
                $action = $action . " {using_the_interface} $ForwardNIC";
            }
        }else{
            $action ="<span class='text-danger'>{then} {redirect_to_the_specified_link} <i class=\"fas fa-engine-warning\"></i>&nbsp;{interface} {not_selected}!</span>";
        }

        if($EnableLinkBalancer==0){
            $action="<span class='text-danger'>$action <i class=\"fas fa-engine-warning\"></i>&nbsp;{APP_LINK_BALANCER} {feature}:{disabled}</span>";
        }

	}
	if($ligne["accepttype"]=="DROP"){
	    $action="{then} <strong style='color:$red_color'>{deny_access}</strong>$log";

        if(preg_match("#XNAT:([0-9]+)#",$eth,$re)){
            $NAT_ID=$re[1];
            $ligne_nat=$q->mysqli_fetch_array("SELECT * FROM pnic_nat WHERE ID='$NAT_ID'");
            if($ligne_nat["NAT_TYPE"]==3) {
                $action = "{then} <strong style='color:$red_color'>{continue}</strong>";
            }
        }


	    if($APP_XTABLES_INSTALLED==1){
            if($xt_ratelimit==1){

                $sttficshap["src"]="{from_x_net_elements}";
                $sttficshap["dst"]="{to_x_net_elements}";

                $elem=$tpl->_ENGINE_parse_body($sttficshap[$xt_ratelimit_dir]);
                $ligne_elements=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM traffic_shaping 
                WHERE ruleid={$ligne["ID"]} AND enabled=1");
                $selmnt=intval($ligne_elements["tcount"]);
                $elem=str_replace("%s",FormatNumber($selmnt),$elem);
                $elem=$tpl->td_href($elem,null,"Loadjs('fw.traffic.shaping.php?ruleid={$ligne["ID"]}')");
                $action="{and} $elem {then} <strong class='text-warning'>{limit_traffic}</strong>$log";

            }

        }

	}
    if($ligne["accepttype"]=="MIRROR"){
        $destIP="&nbsp;<strong style='color:$red_color'>{err_no_destination_addr}</strong>";
        if(strlen($ligne["ForwardTo"])>3){
            $destIP=" {to} {$ligne["ForwardTo"]}";
        }
        $action="{then} <strong style='color:black'>{duplicate_traffic}$destIP</strong>$log";
    }



	$inboud=EXPLAIN_LIST_OBJECTS($ligne["ID"],0,$color);
	if($inboud<>null){
		$inbound_text="{for_inbound_objects} $inboud {and} ";
	}else{
		$inbound_text="{for_all_nodes} {and} ";
	}
	
	if($ligne["isClient"]==1){
		$inbound_text="{from_the_firewall_itself} {and}";
	}

	$outbound=EXPLAIN_LIST_OBJECTS($ligne["ID"],1,$color);
	if($outbound<>null){
		$outbound_text="{to} $outbound {and} ";
	}else{
		$outbound_text="{to_everything} {and} ";
	}

	$ExplainThisTime=ExplainThisTime($ligne);
	if($ExplainThisTime<>null){$ExplainThisTime=" $ExplainThisTime";}

	$intro="<br><span style='color:$color'>$inbound_text $outbound_text$service $action $ExplainThisTime";

	$f[]=$intro;
	$tpl=new template_admin();
	$page=CurrentPageName();
    $FINAL=@implode("<br>", $f);
    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-{$ligne["ID"]}' data='$page?explain-this-rule={$ligne["ID"]}'>$FINAL</span>");


}
function mDPI($ruleid){

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
    $results=$q->QUERY_SQL("SELECT * FROM firehol_ndpi WHERE ruleid=".intval($ruleid));
    if(count($results)==0){return null;}
    $MAIN=array();
    foreach ($results as $index=>$ligne){
        $ndpiname=trim($ligne["ndpiname"]);
        if($ndpiname==null){continue;}
        $MAIN[$ndpiname]=$ndpiname;
    }

    if(count($MAIN)==0){return null;}
    $TT=array();
    foreach ($MAIN as $ndpiname=>$nothing){
        $TT[]=$ndpiname;
    }
    if(count($TT)==0){return null;}
    return @implode(",",$TT);

}
function ExplainThisTime($ligne){
	if(intval($ligne["enablet"] ?? 0)==0){return "{all_times}";}
	$f=array();
	$array_days=array(1=>"monday",2=>"tuesday",3=>"wednesday",4=>"thursday",5=>"friday",6=>"saturday",7=>"sunday");
	$TTIME=@unserialize((string)($ligne["time_restriction"] ?? ""),array("allowed_classes"=>false));
	if(!is_array($TTIME)){$TTIME=array();}

	// Seuls les jours coches : l'ancienne boucle listait les sept des qu'un seul l'etait.
	$DAYS=array();
	foreach ($array_days as $num=>$maks){
		if(intval($TTIME["D{$num}"] ?? 0)==1){$DAYS[]="{{$maks}}";}
	}
	$f[]=count($DAYS)>0?implode(", ", $DAYS):"{fwt_everyday}";

	$from=(string)($TTIME["ftime"] ?? "");
	$to=(string)($TTIME["ttime"] ?? "");
	if(preg_match("#^([0-9]+:[0-9]+)#",$from,$rf) && preg_match("#^([0-9]+:[0-9]+)#",$to,$rt)){
		$f[]="{from_time} {$rf[1]} {to_time} {$rt[1]}";
	}
	return implode("<br>", $f);
}
function EXPLAIN_LIST_OBJECTS($ID,$dir,$color){
    $GPS=array();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne2 = $q->mysqli_fetch_array("SELECT MOD FROM iptables_main WHERE ID='".intval($ID)."'");
    if(!$q->ok){echo $q->mysql_error_html(true);}
    $MOD=$ligne2["MOD"];

    if($dir==0) {

        $q=new postgres_sql();
        $FireholIPSetsWEntries=FormatNumber($q->COUNT_ROWS_LOW("ipset_whitelists"));
        $wljs=$tpl->td_href("{whitelists}",null,"Loadjs('fw.ipfeeds.white.php')");
        if ($MOD == "IPFEED") {
            $FireholIPSetsEntries = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireholIPSetsEntries"));
            $FireholIPSetsEntries = FormatNumber($FireholIPSetsEntries);
            $GPS[] = "<strong>{CybercrimeIPFeeds}</strong> ( $FireholIPSetsEntries {elements} )<br>{and} {notfor} <strong>$wljs</strong> ( $FireholIPSetsWEntries {elements} )";
        }

    }


	// acls.db is API-only — enabled linked groups via the acls backend (direction from caller).
	$links=acls_api_get("/proxy/acls/sqlite/links?table=firewallfilter_sqacllinks&aclid=".intval($ID)."&direction=".intval($dir));
	$results=array();
	if(is_array($links)){foreach($links as $lk){
		if(!is_array($lk)){continue;}
		if(intval($lk["enabled"] ?? 0)!=1){continue;}
		$results[]=array("gpid"=>intval($lk["groupID"] ?? 0),"negation"=>intval($lk["negation"] ?? 0),"GroupName"=>(string)($lk["groupName"] ?? ""),"GroupType"=>(string)($lk["groupType"] ?? ""));
	}}

    $GLOBALS["jsAfterEnc"]=base64_encode("Loadjs('$page?fill=$ID');");

	foreach ($results as $index=>$ligne){
        $text_is="{is}";
        $negation=intval($ligne["negation"]);
		$GroupName=utf8_encode($ligne["GroupName"]);
		$GroupType=$ligne["GroupType"];
		$js_group_final=null;
		$ID=$ligne["gpid"];
        if($negation==1){$text_is="{is_not}";}
        $js_group="Loadjs('fw.rules.items.php?groupid=$ID&js-after={$GLOBALS["jsAfterEnc"]}')";
		$js_group_final=$tpl->td_href($GroupName,null,$js_group);

		if($GroupType=="all"){$js_group_final=null;}

        if($GroupType=="localnet"){

            $qNet=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
            $ligneNet=$qNet->mysqli_fetch_array("SELECT count(*) as tcount FROM networks_infos WHERE enabled=1");
            $items=intval($ligneNet["tcount"]);
            $GPS[]="<strong style='color:$color'>{$text_is}&nbsp;$js_group_final ( $items {elements} )</strong>";
            continue;
        }


        if($GroupType=="fwgeo"){
            $items_api=acls_api_get("/proxy/acls/sqlite/items?gpid=".intval($ID));
            $results_countries=is_array($items_api)?$items_api:array();
            $C=0;
            $qP=new postgres_sql();

            foreach ($results_countries as $indexC=>$ligne_countries){

                $Country=$ligne_countries["pattern"];
                $ligne_sum=$qP->mysqli_fetch_array("SELECT items FROM ipdeny_countgeo 
                                                    WHERE country='".pg_escape_string2($Country)."'");
                if(!$qP->ok){echo $qP->mysql_error_html(true);}
                $C=$C+intval($ligne_sum["items"]);

            }
            $items=FormatNumber($C);
            $GPS[]="<strong style='color:$color'>{$text_is}&nbsp;$js_group_final ( $items {elements} )</strong>";
            continue;
        }

        $main=$tpl->table_object($ID);
        if(isset($main["ITEMS"])){
            $items=FormatNumber($main["ITEMS"]);
            $GPS[]="<strong style='color:$color'>$text_is&nbsp;$js_group_final ( $items {elements} )</strong>";
            continue;
        }
        $cnt=acls_api_get("/proxy/acls/sqlite/items-count?gpid=".intval($ID));
		$items=FormatNumber(is_array($cnt)?intval($cnt["count"] ?? 0):0);
		$GPS[]="<strong style='color:$color'>$text_is&nbsp;$js_group_final ( $items {elements} )</strong>";

	}
	if(count($GPS)==0){return null;}
	return @implode("<br> {or} ", $GPS);
}

function LoadTrustedNetworks():array{
    $q          =new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results    = $q->QUERY_SQL("SELECT * FROM networks_infos WHERE trusted=1 AND enabled=1");
    $TrustedNetworks=array();
    if(count($results)==0){
        $TrustedNetworks[]="10.0.0.0/8";
        $TrustedNetworks[]="172.16.0.0/12";
        $TrustedNetworks[]="192.168.0.0/16";
    }else {
        foreach ($results as $index => $ligne) {
            $TrustedNetworks[] = $ligne["ipaddr"];
        }
    }

	return $TrustedNetworks;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

function SMTP_INTERFACES(){
	$PostfixBinInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixBinInterfaces"));
	if($PostfixBinInterfaces<>null){
		$Interfaces=explode(",",$PostfixBinInterfaces);
		foreach ($Interfaces as $nic){
			$POSTFIX_INTERFACES[$nic]=true;
		}
		return $POSTFIX_INTERFACES;
	}

	


}

// fw_rule_expiry_badge : « expire dans … » (orange) ou « expirée » (gris) pour
// une règle temporaire ; rien pour une règle permanente.
function fw_rule_expiry_badge($ligne):string{
    $exp=intval($ligne["expires"] ?? 0);
    if($exp<=0){return "";}
    $left=$exp-time();
    if($left<=0){return "&nbsp;<span class='label label-default'>{fw_rule_expired_label}</span>";}
    if(intval($ligne["enabled"] ?? 0)!=1){return "";}
    if($left<3600){$txt=ceil($left/60)." min";}
    elseif($left<172800){$txt=ceil($left/3600)." h";}
    else{$txt=ceil($left/86400)." {days}";}
    return "&nbsp;<span class='label label-warning'>{fw_rule_expires_in} $txt</span>";
}
