<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style()/awc_pager_js(): la charte des tableaux d'entites. L'include
// DOIT rester INCONDITIONNEL et au premier niveau -- place dans un
// if(!class_exists("sockets")) il ne serait jamais evalue, et la page mourrait
// sur « Call to undefined function awc_table_style() » au moment du rendu.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.confirm.inc");
// Verrou de la politique Artica Meta "firewall" (reglages du catalogue, colonnes pare-feu des cartes).
include_once(dirname(__FILE__)."/ressources/class.firewall.meta.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["nic-form-default-popup"])){nic_form_default_popup();exit;}
if(isset($_GET["nic-fw-default"])){nic_form_default_js();exit;}
if(isset($_GET["firewall-status"])){firewall_status();exit;}
if(isset($_POST["nic-default"])){nic_form_default_save();exit;}
if(isset($_POST["nic-settings"])){nic_settings();exit;}
if(isset($_POST["FireHoleStoreEvents"])){logs_settings_save();exit;}
// "if(isset($_GET["main"])){main();exit;}" visait une fonction main() qui
// n'existe pas dans ce fichier: l'URL repondait un fatal « Call to undefined
// function main() ». Aucun appelant ne l'utilisait.
if(isset($_GET["flat"])){flat();exit;}
if(isset($_GET["nic-fw-edit"])){nic_form_settings();exit;}
if(isset($_GET["nic-form-settings-build"])){nic_form_settings_build();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["interfaces-start"])){interfaces_start();exit;}
if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["modules-start"])){modules_start();exit;}
if(isset($_GET["modules-table"])){modules_table();exit;}
if(isset($_GET["fw-parms-js"])){fw_params_js();exit;}
if(isset($_GET["fw-parms-popup"])){fw_params_popup();exit;}
if(isset($_GET["fw-confirm-js"])){fw_confirm_js();exit;}
if(isset($_GET["fw-confirm-popup"])){fw_confirm_popup();exit;}
if(isset($_POST["fw-confirm-settings"])){fw_confirm_save();exit;}
if(isset($_GET["fw-transit-js"])){fw_transit_js();exit;}
if(isset($_GET["fw-transit-popup"])){fw_transit_popup();exit;}
if(isset($_POST["fw-transit-policy"])){fw_transit_save();exit;}
if(isset($_GET["fw-autoban-js"])){fw_autoban_js();exit;}
if(isset($_GET["fw-autoban-popup"])){fw_autoban_popup();exit;}
if(isset($_POST["fw-autoban-hits"])){fw_autoban_save();exit;}
page();


function tabs(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $array["{interfaces}"]="$page?interfaces-start=yes";
    $array["{modules}"]="fw.settings.php?modules-start=yes";
    echo firewall_confirm_banner();
    echo $tpl->tabs_default($array);
}

// Le formulaire est garde par "AsSystemAdministrator": la meme exigence cote
// serveur, sinon le repartiteur en tete de fichier repond a toute session
// authentifiee -- masquer un bouton ne protege rien.
function fwset_require_admin($tpl):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return true;}
    $tpl->js_no_privileges();
    return false;
}

function nic_settings(){
	$tpl=new template_admin();
	if(!fwset_require_admin($tpl)){return;}
	$eth=preg_replace('#[^A-Za-z0-9_.:@-]#','',(string)($_POST["nic-settings"] ?? ""));
	if($eth===""){return;}
	// Colonnes pare-feu de la carte tenues par Artica Meta.
	if(firewall_meta_holds_iface($eth)){echo firewall_meta_refuse_post();return;}
	// Masquerade tenu : la case de la carte ne change pas (propagee a firehol_masquerade).
	if(isset($_POST["firewall_masquerade"]) && firewall_meta_holds("masquerade")){
	    $cur=new system_nic($eth);
	    if(intval($_POST["firewall_masquerade"])!=intval($cur->firewall_masquerade)){echo firewall_meta_refuse_post();return;}
	    unset($_POST["firewall_masquerade"]);
	}
	// Seuls les champs postés sont envoyés : le daemon applique une mise à
	// jour partielle de la ligne nics, puis reconstruit le pare-feu.
	$data=array();
	foreach(array("firewall_policy","firewall_policy_out","firewall_ping") as $k){
		if(isset($_POST[$k]) && !is_array($_POST[$k])){$data[$k]=(string)$_POST[$k];}
	}
	foreach(array("isFW","firewall_behavior","firewall_masquerade","firewall_artica","denydhcp","AntiDDOS","xtIpv4options") as $k){
		if(isset($_POST[$k]) && !is_array($_POST[$k])){$data[$k]=intval($_POST[$k]);}
	}
	$e=fwapi_error(fwapi_send("PUT","nics/".rawurlencode($eth)."/firewall",$data));
	if($e!=""){echo $tpl->post_error($e);return;}
	admin_tracks("Firewall: saved interface $eth settings");
}

function firewall_status():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();


    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/isactive"));

    if(!property_exists($data,"Status")){
        $status=$tpl->widget_rouge("{firewall_status}","API ERROR","");
        echo $tpl->_ENGINE_parse_body($status);
        return true;
    }

    if(!$data->Status){
          $jsrestart=$tpl->framework_buildjs(
            "/firewall/reconfigure","firehol.reconfigure.progress",
            "firehol.reconfigure.log",
            "progress-firehol-restart",
        "LoadAjax('table-loader','$page?tabs=yes');");

        $btn[0]["name"]="{start}";
        $btn[0]["icon"]=ico_play;
        $btn[0]["js"]=$jsrestart;
        $status=$tpl->widget_rouge("{firewall_status}","{inactive}",$btn);
    }else{
        $time=$data->Since;
        $jsrestart=$tpl->framework_buildjs(
            "/firewall/stop","
            firehol.reconfigure.progress",
            "firehol.reconfigure.log",
            "progress-firehol-restart",
            "LoadAjax('table-loader','$page?tabs=yes');");

        $btn[0]["name"]="{stop}";
        $btn[0]["icon"]="fas fa-stop";
        $btn[0]["js"]=$jsrestart;

        $jsrestart=$tpl->framework_buildjs(
            "/firewall/reconfigure",
            "firehol.reconfigure.progress",
            "firehol.reconfigure.log",
            "progress-firehol-restart",
            "LoadAjax('table-loader','$page?tabs=yes');");


        $btn[1]["name"]="{restart}";
        $btn[1]["icon"]="fas fa-sync";
        $btn[1]["js"]=$jsrestart;
        $sdate=$tpl->time_to_date($time,true);
        $status=$tpl->widget_vert("{firewall_status}","{active2}<br><small style='color:inherit;opacity:.75;font-size: 12px'>{last_config}:&nbsp;$sdate</small>",$btn);
    }

    $APP_XKERNEL=$tpl->widget_vert("{APP_XKERNEL}","{installed}");
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/xtables"));
    if(!$json->Status) {
        if (property_exists($json, "Version")) {
            $kernver = $json->Version;
            $js="Loadjs('fw.system.upgrade-software.php?product=APP_XKERNEL');";
            $btn=array();
            $btn[0]["name"] = "{fix_it}";
            $btn[0]["icon"] = ico_cd;
            $btn[0]["js"] = $js;
            $APP_XKERNEL=$tpl->widget_jaune("{APP_XKERNEL} $kernver","{not_installed}",$btn);
        }
    }

    echo $tpl->_ENGINE_parse_body($status.$APP_XKERNEL);
    return true;
}

function modules_start(){
    $page       = CurrentPageName();
    $addon=null;
    if(isset($_GET["OnlyLoaded"])){$addon="&OnlyLoaded=yes";}
    echo        "<div id='iptables-modules-start' style='margin-top:10px'></div><script>LoadAjax('iptables-modules-start','$page?modules-table=yes$addon')</script>";
}

function page(){
	$page               = CurrentPageName();
	$tpl                = new template_admin();
	$IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_VERSION");
$html=$tpl->page_header("{firewall_parameters} v$IPTABLES_VERSION",
    "fa-solid fa-block-brick-fire","{firewallexplain}","$page?tabs=yes","firewall-parameters",
        "progress-firehol-restart",false,"table-loader");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{firewall_parameters}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);

}

function logs_settings_save():bool{
	$tpl=new template_admin();
	// Reglages tenus par Artica Meta : une valeur changee est refusee, une valeur
	// inchangee n'est pas reecrite (le demon la refuserait en 423).
	foreach(firewall_meta_held_settings(array_keys($_POST)) as $k){
	    if(!firewall_meta_setting_same($k,$_POST[$k])){echo firewall_meta_refuse_post();return false;}
	    unset($_POST[$k]);
	}
	$tpl->SAVE_POSTs();
    return admin_tracks_post("Save global firewall interface settings");
}
function nic_form_settings():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();	
	$val=$_GET["nic-fw-edit"];
	$nic=new system_nic($val);
	return $tpl->js_dialog1("$val $nic->NICNAME $nic->IPADDR", "$page?nic-form-settings-build=$val");
}
function nic_form_default_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{default}", "$page?nic-form-default-popup=yes");
}
function nic_form_default_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $l=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireWallDefaultInterfacesParams"));
    $xtIpv4optionsInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("xtIpv4optionsInstalled"));

    if(!isset($l["firewall_policy"])){
        $l["firewall_policy"]="accept";
        $l["firewall_behavior"]=0;
        $l["firewall_artica"]=1;
        $l["AntiDDOS"]=0;
        $l["denydhcp"]=0;
        $l["firewall_ping"]="accept";
        $l["isFW"]=1;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FireWallDefaultInterfacesParams",base64_encode(serialize($l)));
    }

    $BEHA["reject"]="{finally_deny_all}";
    $BEHA["accept"]="{finally_allow_all}";

    $BEHA2[0]="{not_defined}";
    $BEHA2[1]="{act_as_lan}";
    $BEHA2[2]="{act_as_wan}";

    $BEHA3["accept"]="{accept}";
    $BEHA3["trusted"]="{accept_trusted}";
    $BEHA3["deny"]="{deny}";

    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure","firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart",
        "LoadAjax('table-loader','$page?tabs=yes');");


    $form[]=$tpl->field_hidden("nic-default", "yes");
    $form[]=$tpl->field_hidden("isFW", 1);
    $form[]=$tpl->field_array_hash($BEHA, "firewall_policy", "{firewall_policy}", $l["firewall_policy"]);
    $form[]=$tpl->field_array_hash($BEHA2, "firewall_behavior", "{firewall_behavior}", $l["firewall_behavior"]);
    $form[]=$tpl->field_array_hash($BEHA3, "firewall_ping", "{firewall_ping}", $l["firewall_ping"]);
    $form[]=$tpl->field_checkbox("denydhcp", "{deny} DHCP", $l["denydhcp"]);
    $form[]=$tpl->field_checkbox("AntiDDOS", "Anti-DDOS", $l["AntiDDOS"]);
    $form[]=$tpl->field_checkbox("firewall_artica", "{accept_artica_w}", $l["firewall_artica"]);
    if($xtIpv4optionsInstalled==0){
        $form[]=$tpl->field_checkbox("xtIpv4optionsEnabled", "{suspicious_header_filter} (<small><strong>{missing_module}</strong></small>)", 0,false,"{suspicious_header_filter_explain}",true);
    }else{
        $form[]=$tpl->field_checkbox("xtIpv4optionsEnabled", "{suspicious_header_filter} ", $l["xtIpv4optionsEnabled"],false,"{suspicious_header_filter_explain}");
    }

    $html[]=$tpl->form_outside("{default}", $form,null,"{apply}","dialogInstance1.close();LoadAjax('Firewall-interfaces-table','$page?interfaces=yes');$jsrestart","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nic_form_default_save():bool{
    $l=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireWallDefaultInterfacesParams"));
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    foreach ($_POST as $key => $value) {
        $l[$key]=$value;
    }
    $newl=serialize($l);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FireWallDefaultInterfacesParams", base64_encode($newl));
    return admin_tracks_post("Save Firewall default rule");
}
function nic_form_settings_build(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$val=$_GET["nic-form-settings-build"];
	$BEHA["reject"]="{finally_deny_all}";
	$BEHA["accept"]="{finally_allow_all}";
		
	$BEHA2[0]="{not_defined}";
	$BEHA2[1]="{act_as_lan}";
	$BEHA2[2]="{act_as_wan}";

    $BEHA3["accept"]="{accept}";
    $BEHA3["trusted"]="{accept_trusted}";
    $BEHA3["deny"]="{deny}";
		
	$masquerade[0]="{none}";
	$masquerade[1]="{masquerading}";
	$masquerade[2]="{masquerading_invert}";
	$nic=new system_nic($val);
    $xtIpv4optionsInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("xtIpv4optionsInstalled"));
    // Carte tenue par Artica Meta : formulaire en lecture seule.
    $metaNic=firewall_meta_holds_iface($val);
    $metaMasq=!$metaNic && firewall_meta_holds("masquerade");
    $metaBanner=$metaNic?firewall_meta_banner("iface:$val",$tpl):firewall_meta_banner("masquerade",$tpl);
    if($metaNic){firewall_meta_lock_form($tpl);}


	
	
	$form[]=$tpl->field_hidden("nic-settings", $val);
	$form[]=$tpl->field_checkbox("isFW", "{activate_firewall_nic}", $nic->isFW,true,"{activate_firewall_nic_explain}");
	$form[]=$tpl->field_array_hash($BEHA, "firewall_policy", "{firewall_policy}", $nic->firewall_policy);
	// Politique sortante : colonne écrite par le daemon seul, lue par l'API.
	$fwnic=fwapi_data(fwapi_get("nics/".rawurlencode($val)."/firewall"));
	$policyOut=is_array($fwnic)?(string)($fwnic["firewall_policy_out"] ?? ""):"";
	if($policyOut===""){$policyOut="accept";}
	$form[]=$tpl->field_array_hash($BEHA, "firewall_policy_out", "{firewall_policy_out}", $policyOut,false,"{firewall_policy_out_explain}");
	$form[]=$tpl->field_array_hash($BEHA2, "firewall_behavior", "{firewall_behavior}", $nic->firewall_behavior);
    $form[]=$tpl->field_array_hash($BEHA3, "firewall_ping", "{firewall_ping}", $nic->firewall_ping);
    $form[]=$tpl->field_checkbox("denydhcp", "{deny} DHCP", $nic->denydhcp);
    $form[]=$tpl->field_checkbox("AntiDDOS", "Anti-DDOS", $nic->AntiDDOS);
    $form[]=$tpl->field_checkbox("firewall_masquerade", "{masquerading}", $nic->firewall_masquerade,false,null,$metaMasq);
	$form[]=$tpl->field_checkbox("firewall_artica", "{accept_artica_w}", $nic->firewall_artica);

    if($xtIpv4optionsInstalled==0){
        $form[]=$tpl->field_checkbox("xtIpv4options", "{suspicious_header_filter} (<small><strong>{missing_module}</strong></small>)", 0,false,"{suspicious_header_filter_explain}",true);
    }else{
        $form[]=$tpl->field_checkbox("xtIpv4options", "{suspicious_header_filter} ", $nic->xtIpv4options,false,"{suspicious_header_filter_explain}");
    }

	$html[]=$metaBanner;
	$html[]=$tpl->form_outside("$val $nic->NICNAME $nic->IPADDR", @implode("\n", $form),null,"{apply}","dialogInstance1.close();LoadAjax('Firewall-interfaces-table','$page?interfaces=yes');","AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}

// Tableau « posture du pare-feu » (maquette stitch_firewall_rules_opts) : une
// tuile par protection, une carte pour la journalisation. Seules les valeurs
// que la page lit déjà sont montrées : la maquette inventait un remplissage du
// journal, un répertoire d'archive et une chaîne nftables qui n'existent pas.
// Les couleurs viennent de brand_ui_accents() (pas du vert clair de la maquette).
function flat(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $admin=$users->AsSystemAdministrator?true:false;
    $s=$GLOBALS["CLASS_SOCKETS"];

    $st=array();
    $st["dns"]=intval($s->GET_INFO("DNSAmplificationProtection"));
    $st["logall"]=intval($s->GET_INFO("FireHoleLogAllEvents"));
    $st["nolocal"]=intval($s->GET_INFO("FirewallSyslogDoNotStorelogsLocally"));
    $st["backup"]=intval($s->GET_INFO("FireHoleStoreEvents"));
    $st["maxsize"]=intval($s->GET_INFO("FireHoleStoreEventsMaxSize"));
    if($st["maxsize"]==0){$st["maxsize"]=100;}
    // null = l'API du pare-feu n'a pas répondu : la tuile le dit au lieu de
    // présenter une valeur par défaut comme un état réel.
    $st["confirm"]=fwapi_data(fwapi_get("versions/settings"));
    $st["transit"]=fwapi_data(fwapi_get("transit"));
    $st["autoban"]=fwapi_data(fwapi_get("autoban"));

    $js=array(
        "params"=>$admin?"Loadjs('$page?fw-parms-js=yes')":"",
        "confirm"=>$admin?"Loadjs('$page?fw-confirm-js=yes')":"",
        "transit"=>$admin?"Loadjs('$page?fw-transit-js=yes')":"",
        "autoban"=>$admin?"Loadjs('$page?fw-autoban-js=yes')":"",
    );

    $html=array();
    // Reglages du catalogue tenus par Artica Meta (bandeau deja traduit, hors _ENGINE_parse_body).
    $metaBanner=firewall_meta_banner("settings:".implode(",",firewall_meta_catalog_keys()),$tpl);
    $html[]="<div class='fwo-board'>";
    $html[]=fwo_hero($st,$js["params"]);
    $html[]="<div class='fwo-grid'>";
    $html[]=fwo_tile_dns($st,$js["params"]);
    $html[]=fwo_tile_autoban($tpl,$st,$js["autoban"]);
    $html[]=fwo_tile_confirm($st,$js["confirm"]);
    $html[]=fwo_tile_transit($st,$js["transit"]);
    $html[]="</div>";
    $html[]=fwo_events($st,$js["params"]);
    $html[]="</div>";
    echo fwo_style().$metaBanner.$tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// Les cinq protections du bandeau : "on", "weak" (choix valide mais plus
// faible) ou "na" (API muette).
function fwo_levels($st):array{
    $l=array();
    $l["dns"]=$st["dns"]==1?"on":"weak";
    $l["autoban"]=is_array($st["autoban"])?(intval($st["autoban"]["enabled"] ?? 0)==1?"on":"weak"):"na";
    $l["confirm"]=is_array($st["confirm"])?(empty($st["confirm"]["confirm"])?"weak":"on"):"na";
    $l["transit"]=is_array($st["transit"])?((($st["transit"]["policy"] ?? "")=="reject")?"on":"weak"):"na";
    $l["backup"]=$st["backup"]==1?"on":"off";
    return $l;
}

// 300 -> « 5 min », 3600 -> « 1 h » ; la valeur brute reste dans le title.
function fwo_dur($sec):string{
    $sec=intval($sec);
    if($sec>=3600 && $sec%3600==0){return ($sec/3600)." h";}
    if($sec>=60 && $sec%60==0){return ($sec/60)." min";}
    return "$sec s";
}

function fwo_switch($on):string{
    $c=$on?"ak-sw on":"ak-sw";
    return "<span class='$c' aria-hidden='true'></span>";
}

function fwo_pill($kind,$label):string{
    $tones=array("on"=>"ok","weak"=>"warn","off"=>"off");
    $tone=isset($tones[$kind])?$tones[$kind]:"off";
    return "<span class='ak-pill ak-$tone'>$label</span>";
}

// Ouverture commune des tuiles : cliquable et crayon pour un administrateur,
// statique sinon (masquer le crayon ne protège rien, les handlers vérifient).
function fwo_tile_open($js,$extra=""):string{
    if($js==""){return "<div class='ak-card fwo-tile $extra'>";}
    return "<div class='ak-card fwo-tile fwo-click $extra' OnClick=\"$js\"><span class='fwo-pen'><i class='fa-solid fa-pencil'></i></span>";
}

function fwo_tile_na($ico,$title):string{
    return "<div class='ak-card fwo-tile fwo-tile-na'>
        <div class='fwo-top'><span class='fwo-ico fwo-ico-off'><i class='$ico'></i></span></div>
        <div class='fwo-title'>$title</div>
        <div class='fwo-na'><i class='fa-solid fa-plug-circle-xmark'></i> {fw_api_no_answer}</div>
    </div>";
}

function fwo_hero($st,$js):string{
    $levels=fwo_levels($st);
    $on=0;
    foreach($levels as $v){if($v=="on"){$on++;}}
    $total=count($levels);
    $names=array("dns"=>"{DNSAmplificationProtection}","autoban"=>"{fw_autoban}",
        "confirm"=>"{fw_confirm_changes}","transit"=>"{fw_transit_policy}","backup"=>"{backup_firewall_events}");
    $short=array("dns"=>"DNS","autoban"=>"{fwo_seg_autoban}","confirm"=>"{fwo_seg_rollback}",
        "transit"=>"{fwo_seg_transit}","backup"=>"{fwo_seg_logs}");
    $segs=array();
    foreach($levels as $k=>$v){
        $segs[]="<div class='fwo-seg' title=\"{$names[$k]}\"><span class='fwo-seg-bar fwo-seg-$v'></span><span class='fwo-seg-lbl'>{$short[$k]}</span></div>";
    }
    $shield="fwo-shield";
    if($on==$total){$shield="fwo-shield fwo-shield-full";}
    if($on==0){$shield="fwo-shield fwo-shield-none";}
    $btn="";
    if($js<>""){
        $btn="<button type='button' class='ak-btn' OnClick=\"$js\"><i class='fa-solid fa-gear'></i> {parameters}</button>";
    }
    return "<div class='ak-card fwo-hero'>
        <div class='fwo-hero-id'>
            <span class='$shield'><i class='fa-solid fa-shield-halved'></i></span>
            <div><div class='fwo-hero-title'>{fwo_title}</div>
            <div class='fwo-hero-sub'><strong>$on / $total</strong> {fwo_protections_active}</div></div>
        </div>
        <div class='fwo-segs'>".@implode("",$segs)."</div>
        <div class='fwo-hero-act'>$btn</div>
    </div>";
}

function fwo_tile_dns($st,$js):string{
    $on=$st["dns"]==1;
    $pill=$on?fwo_pill("on","{fwo_protected}"):fwo_pill("weak","{off}");
    $ico=$on?"fwo-ico":"fwo-ico fwo-ico-weak";
    // Seuils réels de la chaîne DNS-RATE-LIMIT (articarest afirewall/dns_amplification.go).
    $chips="<div class='fwo-chips'>
        <div><span>ANY</span><b>1/s</b></div>
        <div><span>{fwo_root}</span><b>1/s</b></div>
        <div><span>{fwo_total}</span><b>10/s</b></div>
    </div>";
    if(!$on){$chips="<div class='fwo-warn'>{fwo_dns_off}</div>";}
    return fwo_tile_open($js)."
        <div class='fwo-top'><span class='$ico'><i class='fa-solid fa-shield-virus'></i></span>".fwo_switch($on)."</div>
        <div class='fwo-head'><div class='fwo-title'>{DNSAmplificationProtection}</div>$pill</div>
        <div class='fwo-text'>{fwo_dns_explain}</div>
        $chips
        <div class='fwo-foot'>{fwo_dns_foot}</div>
    </div>";
}

function fwo_tile_autoban($tpl,$st,$js):string{
    $ab=$st["autoban"];
    if(!is_array($ab)){return fwo_tile_na("fa-solid fa-user-lock","{fw_autoban}");}
    $on=intval($ab["enabled"] ?? 0)==1;
    $hits=intval($ab["hits"] ?? 20);
    $win=intval($ab["window"] ?? 300);
    $ban=intval($ab["time"] ?? 3600);
    $pill=$on?fwo_pill("on","{active2}"):fwo_pill("weak","{off}");
    $ico=$on?"fwo-ico":"fwo-ico fwo-ico-weak";
    if($on){
        // La phrase est traduite d'abord, les nombres posés ensuite : un
        // jeton contenant déjà le HTML des valeurs ne serait plus traduisible.
        $sentence=$tpl->_ENGINE_parse_body("{fwo_autoban_sentence}");
        $sentence=str_replace(
            array("%h","%w","%t"),
            array("<b class='fwo-num'>$hits</b>","<b class='fwo-num' title='$win {seconds}'>".fwo_dur($win)."</b>",
                "<b class='fwo-num' title='$ban {seconds}'>".fwo_dur($ban)."</b>"),$sentence);
        $body="<div class='fwo-text'>$sentence</div>
        <div class='fwo-chips'>
            <div><span>{fw_autoban_hits}</span><b>$hits</b></div>
            <div><span>{window}</span><b>".fwo_dur($win)."</b></div>
            <div><span>{ban}</span><b>".fwo_dur($ban)."</b></div>
        </div>";
    }else{
        $body="<div class='fwo-text'>{fw_autoban_explain}</div><div class='fwo-warn'>{fwo_autoban_off}</div>";
    }
    return fwo_tile_open($js)."
        <div class='fwo-top'><span class='$ico'><i class='fa-solid fa-user-lock'></i></span>".fwo_switch($on)."</div>
        <div class='fwo-head'><div class='fwo-title'>{fw_autoban}</div>$pill</div>
        $body
        <div class='fwo-foot'><i class='fa-solid fa-shield-halved'></i> {fwo_trusted_never}</div>
    </div>";
}

function fwo_tile_confirm($st,$js):string{
    $c=$st["confirm"];
    if(!is_array($c)){return fwo_tile_na("fa-solid fa-rotate-left","{fw_confirm_changes}");}
    $on=!empty($c["confirm"]);
    $delay=intval($c["delay"] ?? 180);
    $pill=$on?fwo_pill("on","{fwo_safety_on}"):fwo_pill("weak","{off}");
    $ico=$on?"fwo-ico":"fwo-ico fwo-ico-weak";
    if($on){
        $dial=sprintf("%d:%02d",intval($delay/60),$delay%60);
        $body="<div class='fwo-dial'><span><i class='fa-solid fa-stopwatch'></i> <b>$dial</b></span><span class='fwo-dial-sub'>($delay s) {fwo_to_confirm}</span></div>
        <div class='fwo-text'>{fwo_confirm_explain}</div>";
        $foot="<span class='ak-dot ak-ok'></span> {fwo_rollback_armed}";
    }else{
        $body="<div class='fwo-text'>{fwo_confirm_explain}</div><div class='fwo-warn'>{fwo_confirm_off}</div>";
        $foot="<span class='ak-dot ak-warn'></span> {fwo_rollback_disarmed}";
    }
    return fwo_tile_open($js)."
        <div class='fwo-top'><span class='$ico'><i class='fa-solid fa-rotate-left'></i></span>".fwo_switch($on)."</div>
        <div class='fwo-head'><div class='fwo-title'>{fw_confirm_changes}</div>$pill</div>
        $body
        <div class='fwo-foot'>$foot</div>
    </div>";
}

function fwo_tile_transit($st,$js):string{
    $t=$st["transit"];
    if(!is_array($t)){return fwo_tile_na("fa-solid fa-route","{fw_transit_policy}");}
    $reject=($t["policy"] ?? "")=="reject";
    $seg="<span class='fwo-segctl'><span class='".($reject?"":"fwo-segctl-weak")."'>{accept}</span><span class='".($reject?"fwo-segctl-on":"")."'>{reject}</span></span>";
    $pill=$reject?fwo_pill("on","{reject}"):fwo_pill("weak","{accept}");
    $ico=$reject?"fwo-ico":"fwo-ico fwo-ico-weak";
    $card=$reject
        ?"<div class='fwo-note fwo-note-on'><i class='fa-solid fa-lock'></i> {fwo_transit_reject}</div>"
        :"<div class='fwo-note fwo-note-weak'><i class='fa-solid fa-lock-open'></i> {fwo_transit_accept}</div>";
    return fwo_tile_open($js)."
        <div class='fwo-top'><span class='$ico'><i class='fa-solid fa-route'></i></span>$seg</div>
        <div class='fwo-head'><div class='fwo-title'>{fw_transit_policy}</div>$pill</div>
        $card
        <div class='fwo-foot'>{fwo_transit_foot}</div>
    </div>";
}

function fwo_event_row($ico,$title,$text,$on):string{
    $c=$on?"fwo-row":"fwo-row fwo-row-off";
    return "<div class='$c'><span class='fwo-row-ico'><i class='$ico'></i></span>
        <div class='fwo-row-txt'><div class='fwo-row-title'>$title</div><div class='fwo-row-sub'>$text</div></div>".fwo_switch($on)."</div>";
}

function fwo_events($st,$js):string{
    $local=$st["nolocal"]==1?false:true;
    $rows=array();
    $rows[]=fwo_event_row("fa-solid fa-terminal","{log_all_events}","{firewall_log_all_events}",$st["logall"]==1);
    $rows[]=fwo_event_row("fa-solid fa-hard-drive","{fwo_keep_local}",$local?"{fwo_keep_local_on}":"{fwo_keep_local_off}",$local);
    $rows[]=fwo_event_row("fa-solid fa-box-archive","{backup_firewall_events}","{FireHoleStoreEvents_explain}",$st["backup"]==1);
    $size=$st["maxsize"];
    $pill=$st["backup"]==1?fwo_pill("on","{fwo_archive_on}"):fwo_pill("off","{fwo_archive_off}");
    return fwo_tile_open($js,"fwo-events")."
        <div class='fwo-ev-head'>
            <div class='fwo-hero-id'><span class='fwo-ico'><i class='fa-solid fa-scroll'></i></span>
            <div><div class='fwo-title'>{fwo_events_title}</div><div class='fwo-hero-sub'>{fwo_events_sub}</div></div></div>
            $pill
        </div>
        <div class='fwo-rows'>".@implode("",$rows)."</div>
        <div class='fwo-threshold'>
            <span class='fwo-row-ico'><i class='fa-solid fa-gauge'></i></span>
            <div class='fwo-row-txt'><div class='fwo-row-title'>{export_log_if_size_exceed}</div><div class='fwo-row-sub'>{FireHoleStoreEventsMaxSize_explain}</div></div>
            <span class='fwo-size'>$size MB</span>
        </div>
    </div>";
}

function fwo_style():string{
    if(isset($GLOBALS["FWO_STYLE"])){return "";}
    $GLOBALS["FWO_STYLE"]=true;
    $accent="#10b981";$dark="#047857";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.fwo-board{margin:18px 0 10px 0;color:#111d23}
.fwo-board *{box-sizing:border-box}
.fwo-hero{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:20px;padding:18px 20px;margin-bottom:16px}
.fwo-hero-id{display:flex;align-items:center;gap:14px;min-width:220px}
.fwo-shield{width:48px;height:48px;border-radius:50%;background:$soft;color:$dark;display:inline-flex;align-items:center;justify-content:center;font-size:20px;flex:0 0 auto}
.fwo-shield-full{background:$accent;color:#fff;box-shadow:0 0 0 6px $soft}
.fwo-shield-none{background:#fef3c7;color:#b45309}
.fwo-hero-title{font-size:18px;font-weight:600;line-height:24px}
.fwo-hero-sub{font-size:12px;color:#6d7a77;margin-top:2px}
.fwo-hero-sub strong{color:#111d23;font-variant-numeric:tabular-nums}
.fwo-segs{flex:1 1 380px;max-width:620px;display:grid;grid-template-columns:repeat(5,1fr);gap:8px}
.fwo-seg{display:flex;flex-direction:column;gap:6px;min-width:0}
.fwo-seg-bar{display:block;height:8px;border-radius:999px;background:#d7e4ec}
.fwo-seg-on{background:$accent}.fwo-seg-weak{background:#f59e0b}.fwo-seg-off,.fwo-seg-na{background:#cfd8dc}
.fwo-seg-lbl{font-size:10px;letter-spacing:.06em;text-transform:uppercase;color:#6d7a77;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fwo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:16px}
.fwo-tile{position:relative;padding:18px;display:flex;flex-direction:column;gap:10px;min-width:0;transition:border-color .15s,transform .15s}
.fwo-click{cursor:pointer}
.fwo-click:hover{border-color:$accent;transform:translateY(-2px)}
.fwo-pen{position:absolute;top:10px;right:10px;width:26px;height:26px;border-radius:6px;background:#eef3f6;color:#3d4947;display:none;align-items:center;justify-content:center;font-size:11px}
.fwo-click:hover .fwo-pen{display:inline-flex}
.fwo-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding-right:30px}
.fwo-ico{width:40px;height:40px;border-radius:8px;background:$soft;color:$dark;display:inline-flex;align-items:center;justify-content:center;font-size:17px;flex:0 0 auto}
.fwo-ico-weak{background:#fef3c7;color:#b45309}
.fwo-ico-off{background:#eef2f3;color:#6d7a77}
.fwo-head{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.fwo-title{font-size:15px;font-weight:600;line-height:20px;color:#111d23}
.fwo-board .ak-pill{text-transform:uppercase}
.fwo-text{font-size:12px;line-height:18px;color:#3d4947}
.fwo-num{display:inline-block;padding:0 5px;border-radius:4px;background:$soft;color:$dark;font-weight:700;font-variant-numeric:tabular-nums}
.fwo-chips{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
.fwo-chips div{background:#f2f7fa;border-radius:6px;padding:6px 4px;text-align:center;min-width:0}
.fwo-chips span{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#6d7a77;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fwo-chips b{display:block;font-size:13px;margin-top:2px;color:#111d23;font-variant-numeric:tabular-nums}
.fwo-warn{font-size:12px;line-height:17px;padding:8px 10px;border-radius:8px;background:#fef3c7;color:#92400e}
.fwo-dial{display:flex;align-items:center;justify-content:space-between;gap:8px;background:#f2f7fa;border-radius:8px;padding:8px 12px}
.fwo-dial span:first-child{color:$dark;font-size:14px}
.fwo-dial b{font-size:20px;font-variant-numeric:tabular-nums}
.fwo-dial-sub{font-size:12px;color:#6d7a77}
.fwo-note{display:flex;gap:8px;align-items:flex-start;font-size:12px;line-height:17px;padding:10px 12px;border-radius:8px}
.fwo-note-on{background:$soft;color:$dark}
.fwo-note-weak{background:#fef3c7;color:#92400e}
.fwo-segctl{display:inline-flex;background:#eef3f6;border-radius:8px;padding:2px;font-size:12px;font-weight:600}
.fwo-segctl span{padding:4px 10px;border-radius:6px;color:#6d7a77;text-transform:capitalize}
.fwo-segctl .fwo-segctl-on{background:$accent;color:#fff}
.fwo-segctl .fwo-segctl-weak{background:#f59e0b;color:#fff}
.fwo-foot{margin-top:auto;padding-top:8px;font-size:11px;line-height:16px;color:#6d7a77;display:flex;align-items:center;gap:6px}
.fwo-na{font-size:12px;line-height:17px;color:#6d7a77;background:#f2f4f5;border-radius:8px;padding:10px 12px;display:flex;gap:8px;align-items:flex-start}
.fwo-tile-na{border-style:dashed}
.fwo-ev-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding-right:30px}
.fwo-rows{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}
.fwo-row,.fwo-threshold{display:flex;align-items:flex-start;gap:12px;background:#f2f7fa;border-radius:8px;padding:12px 14px}
.fwo-threshold{align-items:center}
.fwo-row-ico{color:$dark;font-size:16px;width:20px;text-align:center;flex:0 0 auto;padding-top:1px}
.fwo-row-off .fwo-row-ico{color:#6d7a77}
.fwo-row-txt{flex:1 1 auto;min-width:0}
.fwo-row-title{font-size:13px;font-weight:600;color:#111d23}
.fwo-row-sub{font-size:12px;line-height:17px;color:#6d7a77;margin-top:2px}
.fwo-size{font-size:18px;font-weight:700;color:$dark;white-space:nowrap;font-variant-numeric:tabular-nums}
@media (max-width:600px){.fwo-hero{padding:14px}.fwo-segs{flex-basis:100%}.fwo-tile{padding:14px}}
</style>";
}

function fw_transit_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // after: JS function NAME of the calling page to run once saved (whitelisted, it lands in $jsafter).
    $after=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["after"] ?? ""));
    return $tpl->js_dialog2("{fw_transit_policy}","$page?fw-transit-popup=yes&after=$after",650);
}

function fw_transit_popup():bool{
    $tpl=new template_admin();
    $transit=fwapi_data(fwapi_get("transit"));
    if(!is_array($transit)){echo $tpl->div_error("{fw_api_no_answer}");return false;}
    $form[]=$tpl->field_array_hash(array("accept"=>"{accept}","reject"=>"{reject}"),"fw-transit-policy","{fw_transit_policy}",(string)($transit["policy"] ?? "accept"),true);
    $html[]=firewall_meta_banner("settings:FirewallTransitPolicy",$tpl);
    if(firewall_meta_holds_setting("FirewallTransitPolicy")){firewall_meta_lock_form($tpl);}
    $html[]=$tpl->div_explain("{fw_transit_policy}||{fw_transit_policy_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",
        "dialogInstance2.close();LoadAjaxSilent('Firewall-interfaces-settings','fw.settings.php?flat=yes');".fwset_after_js(),"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// Refresh hook of the page that opened the dialog from elsewhere (fw.bridges.php).
function fwset_after_js():string{
    $after=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["after"] ?? ""));
    if($after==""){return "";}
    return "if(typeof $after==='function'){ $after(); }";
}

function fw_transit_save():bool{
    $tpl=new template_admin();
    if(!fwset_require_admin($tpl)){return false;}
    if(firewall_meta_holds_setting("FirewallTransitPolicy")){echo firewall_meta_refuse_post();return false;}
    $v=($_POST["fw-transit-policy"] ?? "")=="reject"?"reject":"accept";
    $e=fwapi_error(fwapi_send("PUT","transit",array("policy"=>$v)));
    if($e!=""){echo $tpl->post_error($e);return false;}
    return admin_tracks_post("Set firewall transit policy to $v");
}

function fw_autoban_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{fw_autoban}","$page?fw-autoban-popup=yes",650);
}

function fw_autoban_popup():bool{
    $tpl=new template_admin();
    $ab=fwapi_data(fwapi_get("autoban"));
    if(!is_array($ab)){echo $tpl->div_error("{fw_api_no_answer}");return false;}
    $form[]=$tpl->field_checkbox("fw-autoban-enabled","{fw_autoban}",intval($ab["enabled"] ?? 0),true);
    $form[]=$tpl->field_numeric("fw-autoban-hits","{fw_autoban_hits}",intval($ab["hits"] ?? 20));
    $form[]=$tpl->field_numeric("fw-autoban-window","{fw_autoban_window}",intval($ab["window"] ?? 300));
    $form[]=$tpl->field_numeric("fw-autoban-time","{fw_autoban_time}",intval($ab["time"] ?? 3600));
    $html[]=firewall_meta_banner("settings:".fwset_autoban_keys(),$tpl);
    if(count(firewall_meta_held_settings(explode(",",fwset_autoban_keys())))>0){firewall_meta_lock_form($tpl);}
    $html[]=$tpl->div_explain("{fw_autoban}||{fw_autoban_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",
        "dialogInstance2.close();LoadAjaxSilent('Firewall-interfaces-settings','fw.settings.php?flat=yes');","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// Reglages ecrits ensemble par PUT /firewall/config/autoban (le demon refuse si l'un est tenu).
function fwset_autoban_keys():string{
    return "FirewallAutoBan,FirewallAutoBanHits,FirewallAutoBanWindow,FirewallAutoBanTime";
}

function fw_autoban_save():bool{
    $tpl=new template_admin();
    if(!fwset_require_admin($tpl)){return false;}
    if(count(firewall_meta_held_settings(explode(",",fwset_autoban_keys())))>0){echo firewall_meta_refuse_post();return false;}
    $data=array(
        "enabled"=>intval($_POST["fw-autoban-enabled"] ?? 0)==1?1:0,
        "hits"=>intval($_POST["fw-autoban-hits"] ?? 20),
        "window"=>intval($_POST["fw-autoban-window"] ?? 300),
        "time"=>intval($_POST["fw-autoban-time"] ?? 3600),
    );
    $e=fwapi_error(fwapi_send("PUT","autoban",$data));
    if($e!=""){echo $tpl->post_error($e);return false;}
    return admin_tracks_post("Set firewall automatic blocking: enabled={$data["enabled"]} {$data["hits"]}/{$data["window"]}s ban {$data["time"]}s");
}

function fw_confirm_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{fw_confirm_changes}","$page?fw-confirm-popup=yes",650);
}

function fw_confirm_popup():bool{
    $tpl=new template_admin();
    $confirm=fwapi_data(fwapi_get("versions/settings"));
    if(!is_array($confirm)){echo $tpl->div_error("{fw_api_no_answer}");return false;}
    $form[]=$tpl->field_hidden("fw-confirm-settings","yes");
    $form[]=$tpl->field_checkbox("fw-confirm","{fw_confirm_changes}",empty($confirm["confirm"])?0:1,false,"");
    $form[]=$tpl->field_numeric("fw-confirm-delay","{fw_confirm_delay} ({seconds})",intval($confirm["delay"] ?? 180),"");
    $html[]=$tpl->div_explain("{fw_confirm_changes}||{fw_confirm_changes_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",
        "dialogInstance2.close();LoadAjaxSilent('Firewall-interfaces-settings','fw.settings.php?flat=yes');","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function fw_confirm_save():bool{
    $tpl=new template_admin();
    if(!fwset_require_admin($tpl)){return false;}
    $data=array("confirm"=>intval($_POST["fw-confirm"] ?? 0)==1,"delay"=>intval($_POST["fw-confirm-delay"] ?? 180));
    $e=fwapi_error(fwapi_send("PUT","versions/settings",$data));
    if($e!=""){echo $tpl->post_error($e);return false;}
    return admin_tracks_post("Save firewall change confirmation settings");
}

function fw_params_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{parameters}","$page?fw-parms-popup=yes",650);
}
function fw_params_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $FireHoleStoreEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleStoreEvents"));
	$FireHoleStoreEventsMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleStoreEventsMaxSize"));
	$FireHoleLogAllEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleLogAllEvents"));
	if($FireHoleStoreEventsMaxSize==0){$FireHoleStoreEventsMaxSize=100;}

    $DNSAmplificationProtection=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSAmplificationProtection"));

    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure","firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart",
        "LoadAjax('table-loader','$page?tabs=yes');");



    // Reglages tenus par Artica Meta : champ grise (et refuse a l'enregistrement s'il change).
    $mh=function($k){return firewall_meta_holds_setting($k);};
    $form[]=$tpl->field_checkbox("DNSAmplificationProtection","{DNSAmplificationProtection}",$DNSAmplificationProtection,false,"",$mh("DNSAmplificationProtection"));


    $FirewallSyslogDoNotStorelogsLocally=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FirewallSyslogDoNotStorelogsLocally"));


    $form[]=$tpl->field_checkbox("FireHoleLogAllEvents","{log_all_events}",$FireHoleLogAllEvents,false,"{firewall_log_all_events}",$mh("FireHoleLogAllEvents"));
    $form[] = $tpl->field_checkbox("FirewallSyslogDoNotStorelogsLocally",
        "{not_store_log_locally}", $FirewallSyslogDoNotStorelogsLocally,false,null,$mh("FirewallSyslogDoNotStorelogsLocally"));
	$form[]=$tpl->field_checkbox("FireHoleStoreEvents","{backup_firewall_events}",$FireHoleStoreEvents,false,"{FireHoleStoreEvents_explain}",$mh("FireHoleStoreEvents"));
	$form[]=$tpl->field_numeric("FireHoleStoreEventsMaxSize","{export_log_if_size_exceed} (MB)",$FireHoleStoreEventsMaxSize,"{FireHoleStoreEventsMaxSize_explain}",null,$mh("FireHoleStoreEventsMaxSize"));
	$fwParamKeys=array("DNSAmplificationProtection","FireHoleLogAllEvents","FirewallSyslogDoNotStorelogsLocally","FireHoleStoreEvents","FireHoleStoreEventsMaxSize");
	if(count(firewall_meta_held_settings($fwParamKeys))==count($fwParamKeys)){firewall_meta_lock_form($tpl);}
	$html[]=firewall_meta_banner("settings:".implode(",",$fwParamKeys),$tpl);


	$html[]=$tpl->form_outside(null, $form,null,'{apply}',"dialogInstance2.close();LoadAjax('Firewall-interfaces-settings','$page?flat=yes');$jsrestart","AsSystemAdministrator",true);
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
return true;
			
						
}
// ---------------------------------------------------------------------------
// Onglet Modules (maquette stitch_firewall_modules_inventory_redesign).
// Lecture seule : GET /firewall/kernel-modules (articarest, cache 10 min) rend
// INSTALLED/LOADED/DESC/filename/depends/ALIASES par module. La liste reunit
// les ~190 noms netfilter fixes ET tous les modules charges de la machine :
// la famille "other" isole ces derniers. Un module "(builtin)" n'apparait pas
// dans lsmod (LOADED=0) mais il est toujours actif.
// ---------------------------------------------------------------------------
function fwmod_tr($token):string{
    // Cache par requete : la bulle d'etat est rendue sur ~200 lignes.
    static $cache=array();
    if(!isset($cache[$token])){ $tpl=new template_admin(); $cache[$token]=$tpl->_ENGINE_parse_body("{".$token."}"); }
    $t=$cache[$token];
    $args=func_get_args(); array_shift($args);
    foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
    return $t;
}

// Texte d'un token pour un attribut title="" : sans <br>, guillemets neutralises.
function fwmod_attr($token):string{
    $t=preg_replace('#<br\s*/?>#i'," ",fwmod_tr($token));
    return str_replace('"',"&quot;",trim($t));
}

function fwmod_esc($v):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}

function fwmod_family($name):string{
    $n=strtolower((string)$name);
    $core=array("ip_tables","ip6_tables","x_tables","ip_set","nfnetlink","nfnetlink_log","nfnetlink_queue",
        "nf_tables","nf_tables_set","arp_tables","ebtables");
    if(in_array($n,$core,true)){return "core";}
    if(preg_match('#^(iptable_|ip6table_|arptable_|ebtable_|ip_set_)#',$n)){return "core";}
    if(strpos($n,"nft_")===0){return "nft";}
    if(preg_match('#^(xt_|ipt_|ip6t_|ebt_|arpt_)#',$n)){return "xt";}
    if(strpos($n,"nf_")===0){return "ct";}
    return "other";
}

// Groupes dans l'ordre d'affichage : token du titre, token du sous-titre.
function fwmod_groups():array{
    return array(
        "core"=>array("fwmod_g_core","fwmod_g_core_sub"),
        "ct"=>array("fwmod_g_ct","fwmod_g_ct_sub"),
        "xt"=>array("fwmod_g_xt","fwmod_g_xt_sub"),
        "nft"=>array("fwmod_g_nft","fwmod_g_nft_sub"),
        "other"=>array("fwmod_g_other","fwmod_g_other_sub"),
    );
}

// Fonctions visibles du produit et le module dont chacune depend.
function fwmod_features():array{
    return array(
        array("fas fa-earth-europe","fwmod_feat_country","xt_geoip"),
        array("fas fa-shapes","fwmod_feat_apps","xt_ndpi"),
        array("fas fa-lock","fwmod_feat_tls","xt_tls"),
        array("fas fa-arrows-turn-to-dots","fwmod_feat_tproxy","xt_TPROXY"),
        array("fas fa-gauge-high","fwmod_feat_connlimit","xt_connlimit"),
        array("fas fa-shuffle","fwmod_feat_nat","nf_nat"),
    );
}

function fwmod_model():array{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/kernel-modules"),true);
    $src=(is_array($json) && is_array($json["Modules"] ?? null)) ? $json["Modules"] : array();
    $M=array("ok"=>count($src)>0,"rows"=>array(),"byname"=>array(),
        "n"=>array("active"=>0,"ready"=>0,"missing"=>0,"unknown"=>0),
        "fw_total"=>0,"fw_active"=>0,"groups"=>array());
    foreach(array_keys(fwmod_groups()) as $g){
        $M["groups"][$g]=array("active"=>0,"ready"=>0,"missing"=>0,"unknown"=>0,"total"=>0);
    }
    ksort($src,SORT_NATURAL|SORT_FLAG_CASE);
    foreach($src as $name=>$m){
        if(!is_array($m)){continue;}
        $name=(string)$name;
        $file=trim((string)($m["filename"] ?? ""));
        $builtin=($file==="(builtin)");
        if(intval($m["LOADED"] ?? 0)==1 || $builtin){ $state="active"; }
        elseif(!array_key_exists("INSTALLED",$m)){ $state="unknown"; }
        elseif(!empty($m["INSTALLED"])){ $state="ready"; }
        else{ $state="missing"; }

        $depends=array();
        foreach(explode(",",(string)($m["depends"] ?? "")) as $d){ $d=trim($d); if($d!==""){$depends[]=$d;} }
        $aliases=array();
        if(isset($m["ALIASES"]) && is_array($m["ALIASES"])){
            foreach($m["ALIASES"] as $a){
                $a=trim((string)$a);
                // Les alias pci:/input:/usb: decrivent du materiel, pas le module.
                if($a==="" || preg_match('#^(pci|input|usb|of|acpi|platform):#',$a)){continue;}
                $aliases[]=$a;
            }
        }
        $fam=fwmod_family($name);
        $row=array("name"=>$name,"state"=>$state,"family"=>$fam,"builtin"=>$builtin,
            "desc"=>trim((string)($m["DESC"] ?? "")),"file"=>$file,"depends"=>$depends,"aliases"=>$aliases);
        $M["rows"][]=$row;
        $M["byname"][strtolower($name)]=$state;
        $M["n"][$state]++;
        $M["groups"][$fam][$state]++;
        $M["groups"][$fam]["total"]++;
        if($fam!=="other"){
            $M["fw_total"]++;
            if($state==="active"){$M["fw_active"]++;}
        }
    }
    return $M;
}

function fwmod_pill($state):string{
    $ico=array("active"=>"fas fa-circle-check","ready"=>"fas fa-circle-pause",
        "missing"=>"fas fa-triangle-exclamation","unknown"=>"fas fa-circle-question");
    if(!isset($ico[$state])){$state="unknown";}
    return "<span class='fwmod-pill fwmod-$state' title=\"".fwmod_attr("fwmod_{$state}_tip")."\">"
        ."<span class='fwmod-pill-i'><i class='{$ico[$state]}' aria-hidden='true'></i></span>{fwmod_$state}</span>";
}

function fwmod_banner():string{
    $chips=array(array("fas fa-eye","fwmod_chip_ro"),array("fas fa-bolt","fwmod_chip_auto"),array("fas fa-clock","fwmod_chip_cache"));
    $c=array();
    foreach($chips as $ch){ $c[]="<span class='fwmod-chip'><span class='fwmod-chip-i'><i class='{$ch[0]}' aria-hidden='true'></i></span>{{$ch[1]}}</span>"; }
    return "<section class='fwmod-banner'><div class='fwmod-banner-ico'><i class='fas fa-microchip' aria-hidden='true'></i></div>"
        ."<div class='fwmod-banner-body'><h2>{fwmod_title}</h2><p>{fwmod_intro}</p>"
        ."<div class='fwmod-chips'>".@implode("",$c)."</div></div></section>";
}

function fwmod_tile($state,$ico,$value,$help,$extraCls=""):string{
    return "<button type='button' class='fwmod-tile $extraCls' OnClick=\"FwmodSeg('s','$state')\" title=\"".fwmod_attr("fwmod_{$state}_tip")."\">"
        ."<span class='fwmod-tile-h'><span>{fwmod_$state}</span><span class='fwmod-tile-i fwmod-t-$state'><i class='$ico' aria-hidden='true'></i></span></span>"
        ."<span class='fwmod-tile-v'>$value</span><span class='fwmod-tile-help'>$help</span></button>";
}

function fwmod_tile_active(array $M):string{
    return fwmod_tile("active","fas fa-circle-check","<b class='fwmod-t-active'>{$M["n"]["active"]}</b> <small>{fwmod_modules_unit}</small>","{fwmod_active_help}");
}
function fwmod_tile_ready(array $M):string{
    return fwmod_tile("ready","fas fa-circle-pause","<b>{$M["n"]["ready"]}</b> <small>{fwmod_modules_unit}</small>","{fwmod_ready_help}");
}
function fwmod_tile_missing(array $M):string{
    $n=$M["n"]["missing"];
    $v=$n>0?"<b class='fwmod-t-missing'>$n</b> <small>{fwmod_modules_unit}</small>":"<b class='fwmod-t-zero'>{fwmod_none}</b>";
    return fwmod_tile("missing","fas fa-triangle-exclamation",$v,"{fwmod_missing_help}");
}

// Anneau en SVG pur (aucune dependance) : modules pare-feu actifs / modules pare-feu connus.
function fwmod_tile_firewall(array $M):string{
    $t=$M["fw_total"]; $a=$M["fw_active"];
    $pct=$t>0?round($a*100/$t,1):0;
    $donut="<svg class='fwmod-donut' viewBox='0 0 36 36' role='img' aria-label='$a / $t'>"
        ."<circle cx='18' cy='18' r='15.9155' fill='none' stroke='#e2e8f0' stroke-width='4'/>"
        ."<circle cx='18' cy='18' r='15.9155' fill='none' class='fwmod-donut-v' stroke-width='4' stroke-dasharray='$pct 100' transform='rotate(-90 18 18)'/></svg>";
    $all=count($M["rows"]);
    return "<button type='button' class='fwmod-tile' OnClick=\"FwmodSeg('f','fw')\">"
        ."<span class='fwmod-tile-h'><span>{fwmod_fw_modules}</span>$donut</span>"
        ."<span class='fwmod-tile-v'><b>$t</b> <small>".fwmod_tr("fwmod_fw_of",$all)."</small></span>"
        ."<span class='fwmod-tile-help'>".fwmod_tr("fwmod_fw_help",$a)."</span></button>";
}

function fwmod_tiles(array $M):string{
    return "<section class='fwmod-tiles'>".fwmod_tile_active($M).fwmod_tile_ready($M).fwmod_tile_missing($M).fwmod_tile_firewall($M)."</section>";
}

function fwmod_feature(array $M,array $f):string{
    $state=$M["byname"][strtolower($f[2])] ?? "unknown";
    $warn=$state==="missing"?"<span class='fwmod-feat-warn'>{fwmod_feat_unavail}</span>":"";
    return "<div class='fwmod-feat".($state==="missing"?" is-missing":"")."'>"
        ."<div class='fwmod-feat-top'><span class='fwmod-feat-i'><i class='{$f[0]}' aria-hidden='true'></i></span>"
        ."<span class='fwmod-feat-t'><span class='fwmod-feat-n'>{{$f[1]}}</span>$warn</span></div>"
        ."<div class='fwmod-feat-b'><span class='fwmod-mono fwmod-feat-m'>".fwmod_esc($f[2])."</span>".fwmod_pill($state)."</div></div>";
}

function fwmod_features_card(array $M):string{
    $r=array();
    foreach(fwmod_features() as $f){ $r[]=fwmod_feature($M,$f); }
    return "<section class='fwmod-card fwmod-feats'><div class='fwmod-card-h'><h3>{fwmod_feat_title}</h3><p>{fwmod_feat_sub}</p></div>"
        ."<div class='fwmod-feat-grid'>".@implode("",$r)."</div></section>";
}

function fwmod_seg($g,array $items,$on):string{
    $b=array();
    foreach($items as $k=>$label){
        $is=($k===$on);
        $n=$g==="s"?" <span class='fwmod-n' id='fwmod-n-$k'>0</span>":"";
        $b[]="<button type='button' class='fwmod-seg-b".($is?" on":"")."' data-g='$g' data-k='$k' aria-pressed='".($is?"true":"false")."' OnClick=\"FwmodSeg('$g','$k')\">$label$n</button>";
    }
    return @implode("",$b);
}

function fwmod_filters($initState):string{
    $s=fwmod_seg("s",array("all"=>"{fwmod_all}","active"=>"{fwmod_active}","ready"=>"{fwmod_ready}","missing"=>"{fwmod_missing}"),$initState);
    $f=fwmod_seg("f",array("all"=>"{fwmod_all}","fw"=>"{fwmod_fam_fw}","other"=>"{fwmod_fam_other}"),"all");
    return "<section class='fwmod-card fwmod-bar'>"
        ."<div class='fwmod-search'><label class='fwmod-search-f'><span class='fwmod-search-i'><i class='fas fa-magnifying-glass' aria-hidden='true'></i></span>"
        ."<input type='search' id='fwmod-q' placeholder=\"".fwmod_attr("fwmod_search_ph")."\" aria-label=\"".fwmod_attr("fwmod_search_ph")."\" OnInput='FwmodQueue()'></label>"
        ."<span class='fwmod-help'>{fwmod_search_help}</span></div>"
        ."<div class='fwmod-segs'><div class='fwmod-seg' role='group' aria-label=\"".fwmod_attr("fwmod_state")."\">$s</div>"
        ."<div class='fwmod-seg' role='group' aria-label=\"".fwmod_attr("fwmod_family")."\">$f</div></div>"
        ."<div class='fwmod-bar-r'><span id='fwmod-count'></span><button type='button' class='fwmod-link' id='fwmod-clear' hidden OnClick='FwmodClear()'>{fwmod_clear}</button></div>"
        ."</section>";
}

function fwmod_chips(array $list,$clickable):string{
    if(count($list)===0){return "<span class='fwmod-muted'>{fwmod_none}</span>";}
    $o=array();
    foreach($list as $v){
        $e=fwmod_esc($v);
        if($clickable){ $o[]="<button type='button' class='fwmod-dchip fwmod-mono' OnClick=\"FwmodFind(this.textContent)\">$e</button>"; }
        else{ $o[]="<span class='fwmod-dchip fwmod-mono is-static'>$e</span>"; }
    }
    return @implode("",$o);
}

function fwmod_row(array $r,array $featByMod):string{
    $id="fwmod-d-".md5($r["name"]);
    $q=strtolower($r["name"]." ".$r["desc"]." ".($featByMod[strtolower($r["name"])] ?? ""));
    $desc=$r["desc"]!==""?fwmod_esc($r["desc"]):"<i class='fwmod-muted'>{fwmod_no_desc}</i>";
    $bi=$r["builtin"]?"<span class='fwmod-tag'><span class='fwmod-tag-i'><i class='fas fa-microchip' aria-hidden='true'></i></span>{fwmod_builtin}</span>":"";
    $file=$r["builtin"]?"{fwmod_file_builtin}":($r["file"]!==""?"<span class='fwmod-mono'>".fwmod_esc($r["file"])."</span>":"<span class='fwmod-muted'>{fwmod_none}</span>");
    return "<div class='fwmod-row' data-s='{$r["state"]}' data-f='{$r["family"]}' data-q=\"".fwmod_esc($q)."\">"
        ."<div class='fwmod-row-m'>".fwmod_pill($r["state"])
        ."<div class='fwmod-row-id'><div><span class='fwmod-mono fwmod-name'>".fwmod_esc($r["name"])."</span>$bi</div><div class='fwmod-desc'>$desc</div></div>"
        ."<button type='button' class='fwmod-det' aria-expanded='false' aria-controls='$id' OnClick=\"FwmodDetails(this,'$id')\">{fwmod_details}<span class='fwmod-chev'><i class='fas fa-chevron-right' aria-hidden='true'></i></span></button></div>"
        ."<div class='fwmod-drawer' id='$id' hidden><dl>"
        ."<dt>{fwmod_needs}</dt><dd>".fwmod_chips($r["depends"],true)."</dd>"
        ."<dt>{fwmod_aka}</dt><dd>".fwmod_chips($r["aliases"],false)."</dd>"
        ."<dt>{fwmod_file}</dt><dd>$file</dd></dl></div></div>";
}

function fwmod_group(array $M,$g,array $tok,array $rows,array $featByMod):string{
    $c=$M["groups"][$g];
    if($c["total"]===0){return "";}
    $open=$g!=="other";
    $mis=$c["missing"]>0?"<span class='fwmod-t-missing'>".fwmod_tr("fwmod_g_missing",$c["missing"])."</span>":fwmod_tr("fwmod_g_missing",0);
    $r=array();
    foreach($rows as $row){ if($row["family"]===$g){ $r[]=fwmod_row($row,$featByMod); } }
    return "<section class='fwmod-card fwmod-group".($open?" open":"")."' data-g='$g'>"
        ."<button type='button' class='fwmod-group-h' aria-expanded='".($open?"true":"false")."' OnClick='FwmodToggle(this)'>"
        ."<span class='fwmod-gchev'><i class='fas fa-chevron-down' aria-hidden='true'></i></span>"
        ."<span class='fwmod-group-t'><b>{{$tok[0]}}</b><small>{{$tok[1]}}</small></span>"
        ."<span class='fwmod-group-c'><span class='fwmod-gc-a'>".fwmod_tr("fwmod_g_active",$c["active"])."</span> · "
        .fwmod_tr("fwmod_g_ready",$c["ready"])." · $mis</span></button>"
        ."<div class='fwmod-group-b'>".@implode("",$r)
        ."<div class='fwmod-more-w'><button type='button' class='fwmod-more' OnClick=\"FwmodMore('$g')\">{fwmod_more}</button></div></div></section>";
}

function fwmod_list(array $M):string{
    $featByMod=array();
    foreach(fwmod_features() as $f){ $featByMod[strtolower($f[2])]=preg_replace('#<br\s*/?>#i'," ",fwmod_tr($f[1])); }
    $h=array();
    foreach(fwmod_groups() as $g=>$tok){ $h[]=fwmod_group($M,$g,$tok,$M["rows"],$featByMod); }
    $h[]="<div class='fwmod-card fwmod-empty' id='fwmod-nomatch' hidden><span class='fwmod-empty-i'><i class='fas fa-filter-circle-xmark' aria-hidden='true'></i></span>"
        ."<h3>{fwmod_nomatch}</h3><button type='button' class='fwmod-btn' OnClick='FwmodClear()'>{fwmod_clear}</button></div>";
    return "<div id='fwmod-groups'>".@implode("",$h)."</div>";
}

function fwmod_error($reload):string{
    return "<section class='fwmod-err' role='alert'><span class='fwmod-err-i'><i class='fas fa-plug-circle-xmark' aria-hidden='true'></i></span>"
        ."<div><h3>{fwmod_err_title}</h3><p>{fwmod_err_text}</p>"
        ."<button type='button' class='fwmod-btn' OnClick=\"$reload\"><span class='fwmod-btn-i'><i class='fas fa-rotate' aria-hidden='true'></i></span>{fwmod_retry}</button></div></section>";
}

function fwmod_js($initState):string{
    $j=function($tok){ return json_encode(html_entity_decode(preg_replace('#<br\s*/?>#i'," ",fwmod_tr($tok)),ENT_QUOTES,"UTF-8")); };
    return "<script>NoSpinner();
window.FwmodShown=".$j("fwmod_showing").";
window.FwmodF={s:'$initState',f:'all',q:''};window.FwmodInit='$initState';window.FwmodLim={};
window.FwmodSeg=function(g,k){window.FwmodF[g]=k;var bs=document.querySelectorAll('#fwmod .fwmod-seg-b[data-g='+g+']');for(var i=0;i<bs.length;i++){var on=bs[i].getAttribute('data-k')===k;bs[i].className='fwmod-seg-b'+(on?' on':'');bs[i].setAttribute('aria-pressed',on?'true':'false');}
 if(g==='f'&&k==='other'){var o=document.querySelector('#fwmod .fwmod-group[data-g=other]');if(o&&o.className.indexOf(' open')<0){FwmodToggle(o.querySelector('.fwmod-group-h'));}}
 window.FwmodLim={};FwmodApply();};
window.FwmodQueue=function(){clearTimeout(window.FwmodQT);window.FwmodQT=setTimeout(function(){var e=document.getElementById('fwmod-q');window.FwmodF.q=e?e.value.trim().toLowerCase():'';window.FwmodLim={};FwmodApply();},200);};
window.FwmodFind=function(v){var e=document.getElementById('fwmod-q');if(!e){return;}e.value=v;window.FwmodF.q=String(v).toLowerCase();FwmodSeg('s','all');e.focus();};
window.FwmodClear=function(){var e=document.getElementById('fwmod-q');if(e){e.value='';}window.FwmodF.q='';FwmodSeg('f','all');FwmodSeg('s','all');};
window.FwmodToggle=function(b){var s=b.parentNode,open=s.className.indexOf(' open')<0;s.className=open?s.className+' open':s.className.replace(' open','');b.setAttribute('aria-expanded',open?'true':'false');};
window.FwmodDetails=function(b,id){var d=document.getElementById(id);if(!d){return;}var open=d.hidden;d.hidden=!open;b.setAttribute('aria-expanded',open?'true':'false');b.className='fwmod-det'+(open?' open':'');};
window.FwmodMore=function(g){window.FwmodLim[g]=(window.FwmodLim[g]||50)+50;FwmodApply();};
window.FwmodApply=function(){
 var F=window.FwmodF,cs={all:0,active:0,ready:0,missing:0},total=0,shown=0,i;
 var gs=document.querySelectorAll('#fwmod .fwmod-group');
 for(var gi=0;gi<gs.length;gi++){var G=gs[gi],g=G.getAttribute('data-g'),lim=window.FwmodLim[g]||50,rows=G.querySelectorAll('.fwmod-row'),m=0,vis=0;
  for(i=0;i<rows.length;i++){var r=rows[i],s=r.getAttribute('data-s'),f=r.getAttribute('data-f');total++;
   var okF=(F.f==='all'||(F.f==='fw'&&f!=='other')||(F.f==='other'&&f==='other'));
   var okQ=(F.q===''||r.getAttribute('data-q').indexOf(F.q)>=0);
   if(okF&&okQ){cs.all++;if(cs[s]!==undefined){cs[s]++;}}
   var ok=okF&&okQ&&(F.s==='all'||s===F.s);
   if(ok){m++;}
   if(ok&&vis<lim){r.style.display='';vis++;}else{r.style.display='none';}}
  shown+=m;G.style.display=m?'':'none';
  var mw=G.querySelector('.fwmod-more-w');if(mw){mw.style.display=(m>vis)?'':'none';}}
 for(var k in cs){var e=document.getElementById('fwmod-n-'+k);if(e){e.textContent=cs[k];}}
 var c=document.getElementById('fwmod-count');if(c){c.textContent=window.FwmodShown.replace('%s',shown).replace('%s',total);}
 var nm=document.getElementById('fwmod-nomatch');if(nm){nm.hidden=shown>0;}
 var cl=document.getElementById('fwmod-clear');if(cl){cl.hidden=(F.q===''&&F.f==='all'&&F.s==='all');}
};
FwmodApply();
</script>";
}

function fwmod_style():string{
    $A="#10b981";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){ $a=brand_ui_accents(brand_palette()); if(!empty($a["accent"])){$A=$a["accent"];} }
    return "<style>
#fwmod{color:#0f172a;font-size:14px;line-height:1.45}
#fwmod *{box-sizing:border-box}
#fwmod h2,#fwmod h3,#fwmod p,#fwmod dl,#fwmod dd{margin:0}
#fwmod button{font-family:inherit}
#fwmod button:focus-visible,#fwmod input:focus-visible{outline:2px solid $A;outline-offset:2px}
.fwmod-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace}
.fwmod-muted{color:#64748b}
.fwmod-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);margin-bottom:16px}
.fwmod-banner{display:flex;gap:20px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-left:4px solid $A;border-radius:12px;padding:22px 24px;margin-bottom:16px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.fwmod-banner-ico{flex:0 0 56px;height:56px;border-radius:50%;background:#f1f5f9;color:$A;display:inline-flex;align-items:center;justify-content:center;font-size:26px}
#fwmod .fwmod-banner h2{font-size:20px;font-weight:600;margin-bottom:6px}
.fwmod-banner p{color:#475569;max-width:900px}
.fwmod-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.fwmod-chip{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;color:#334155;border-radius:999px;padding:5px 12px;font-size:13px}
.fwmod-chip-i{color:#475569;font-size:12px;display:inline-flex}
.fwmod-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:16px}
.fwmod-tile{display:flex;flex-direction:column;gap:6px;text-align:left;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;cursor:pointer;box-shadow:0 1px 2px rgba(15,23,42,.06);transition:border-color .15s}
.fwmod-tile:hover{border-color:#cbd5e1}
.fwmod-tile-h{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.03em}
.fwmod-tile-i{font-size:18px;display:inline-flex}
.fwmod-tile-v b{font-size:28px;font-weight:700;line-height:1.1}
.fwmod-tile-v small{font-size:13px;color:#64748b;font-weight:500}
.fwmod-tile-help{font-size:13px;color:#64748b}
.fwmod-t-active{color:#15803d}.fwmod-t-ready{color:#475569}.fwmod-t-missing{color:#b45309}.fwmod-t-unknown{color:#64748b}.fwmod-t-zero{color:#64748b;font-size:22px!important}
.fwmod-donut{width:28px;height:28px}
.fwmod-donut-v{stroke:#15803d}
.fwmod-card-h{display:flex;justify-content:space-between;align-items:baseline;gap:16px;flex-wrap:wrap;padding:16px 20px 0}
.fwmod-card-h h3{font-size:15px;font-weight:600}
.fwmod-card-h p{font-size:13px;color:#64748b}
.fwmod-feat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:10px;padding:14px 20px 18px}
.fwmod-feat{display:flex;flex-direction:column;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px}
.fwmod-feat.is-missing{background:#fffbeb;border-color:#fde68a}
.fwmod-feat-i{color:#475569;font-size:16px;display:inline-flex;flex:0 0 18px;justify-content:center}
.fwmod-feat-top{display:flex;align-items:flex-start;gap:10px}
.fwmod-feat-t{flex:1;min-width:0;display:flex;flex-direction:column;overflow-wrap:anywhere}
.fwmod-feat-b{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-left:28px}
.fwmod-feat-n{font-size:14px;font-weight:500}
.fwmod-feat-warn{font-size:13px;color:#b45309}
.fwmod-feat-m{font-size:13px;color:#64748b;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:1px 6px;white-space:nowrap}
.fwmod-pill{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:3px 10px;font-size:13px;font-weight:600;white-space:nowrap}
.fwmod-pill-i{font-size:12px;display:inline-flex}
.fwmod-pill.fwmod-active{background:#dcfce7;color:#15803d}
.fwmod-pill.fwmod-ready{background:#f1f5f9;color:#475569}
.fwmod-pill.fwmod-missing{background:#fef3c7;color:#b45309}
.fwmod-pill.fwmod-unknown{background:#f1f5f9;color:#64748b}
.fwmod-bar{display:flex;flex-wrap:wrap;align-items:flex-start;gap:12px 20px;padding:14px 20px}
.fwmod-search{display:flex;flex-direction:column;gap:4px;flex:0 1 340px;min-width:240px}
.fwmod-search-f{position:relative;margin:0;font-weight:400}
.fwmod-search-i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b;font-size:13px;display:inline-flex}
.fwmod-search input{width:100%;height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px 0 34px;font-size:14px;background:#fff;color:#0f172a}
.fwmod-help{font-size:13px;color:#64748b}
.fwmod-segs{display:flex;flex-wrap:wrap;gap:10px}
.fwmod-seg{display:inline-flex;background:#f1f5f9;border-radius:10px;padding:3px;gap:2px}
.fwmod-seg-b{border:0;background:transparent;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:500;color:#475569;cursor:pointer;white-space:nowrap}
.fwmod-seg-b.on{background:#fff;color:#0f172a;box-shadow:0 1px 2px rgba(15,23,42,.12);font-weight:600}
.fwmod-n{display:inline-block;min-width:20px;padding:0 6px;margin-left:4px;border-radius:999px;background:rgba(15,23,42,.06);font-size:13px;text-align:center}
.fwmod-bar-r{margin-left:auto;display:flex;align-items:center;gap:14px;font-size:13px;color:#64748b;min-height:38px}
.fwmod-link{border:0;background:none;color:$A;font-weight:600;font-size:13px;cursor:pointer;padding:0}
.fwmod-group-h{display:flex;align-items:center;gap:12px;width:100%;border:0;background:#f8fafc;border-radius:12px;padding:12px 18px;text-align:left;cursor:pointer}
.fwmod-group.open .fwmod-group-h{border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0}
.fwmod-gchev{display:inline-flex;color:#475569;transition:transform .15s;transform:rotate(-90deg)}
.fwmod-group.open .fwmod-gchev{transform:none}
.fwmod-group-t{flex:1;min-width:0;display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 10px}
.fwmod-group-t b{font-size:15px;font-weight:600}
.fwmod-group-t small{font-size:13px;color:#64748b}
.fwmod-group-c{font-size:13px;color:#64748b;white-space:nowrap}
.fwmod-gc-a{color:#15803d;font-weight:600}
.fwmod-group-b{display:none}
.fwmod-group.open .fwmod-group-b{display:block}
.fwmod-row{border-bottom:1px solid #f1f5f9}
.fwmod-row:last-of-type{border-bottom:0}
.fwmod-row-m{display:grid;grid-template-columns:112px minmax(0,1fr) auto;align-items:center;gap:14px;padding:10px 18px}
.fwmod-row:hover{background:#f8fafc}
.fwmod-name{font-size:14px;font-weight:600}
.fwmod-desc{font-size:13px;color:#475569;overflow-wrap:anywhere}
.fwmod-tag{display:inline-flex;align-items:center;gap:4px;margin-left:8px;background:#e6eeed;color:#005447;border-radius:6px;padding:1px 7px;font-size:13px;font-weight:600;vertical-align:1px}
.fwmod-tag-i{font-size:11px;display:inline-flex}
.fwmod-det{display:inline-flex;align-items:center;gap:6px;border:0;background:none;color:#475569;font-size:13px;cursor:pointer;padding:6px 8px;border-radius:6px;white-space:nowrap}
.fwmod-det:hover{background:#f1f5f9;color:#0f172a}
.fwmod-chev{display:inline-flex;font-size:11px;transition:transform .15s}
.fwmod-det.open .fwmod-chev{transform:rotate(90deg)}
.fwmod-drawer{padding:4px 18px 14px 144px}
.fwmod-drawer dl{display:grid;grid-template-columns:120px minmax(0,1fr);gap:8px 12px;font-size:13px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px}
.fwmod-drawer dt{color:#64748b;font-weight:600}
.fwmod-drawer dd{display:flex;flex-wrap:wrap;gap:6px;overflow-wrap:anywhere;min-width:0}
.fwmod-dchip{border:1px solid #cbd5e1;background:#fff;border-radius:6px;padding:1px 8px;font-size:13px;color:#0f172a;cursor:pointer}
.fwmod-dchip:hover{border-color:$A}
.fwmod-dchip.is-static{cursor:default;color:#475569;background:#f1f5f9;border-color:#e2e8f0}
.fwmod-more-w{text-align:center;padding:12px}
.fwmod-more,.fwmod-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid #cbd5e1;background:#fff;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;color:#0f172a;cursor:pointer}
.fwmod-more:hover,.fwmod-btn:hover{border-color:$A}
.fwmod-btn-i{display:inline-flex;font-size:12px}
.fwmod-empty{text-align:center;padding:32px 20px}
.fwmod-empty-i{display:inline-flex;font-size:28px;color:#94a3b8;margin-bottom:8px}
#fwmod .fwmod-empty h3{font-size:15px;font-weight:600;margin-bottom:12px}
.fwmod-err{display:flex;gap:16px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:20px 24px}
.fwmod-err-i{display:inline-flex;font-size:24px;color:#b91c1c}
#fwmod .fwmod-err h3{font-size:15px;font-weight:600;color:#991b1b;margin-bottom:4px}
#fwmod .fwmod-err p{color:#7f1d1d;margin-bottom:12px}
@media (prefers-reduced-motion:reduce){#fwmod *{transition:none!important}}
</style>";
}

function modules_table():bool{
    $tpl     = new template_admin();
    $page    = CurrentPageName();
    // La fenetre "Loaded modules" (fw.system.information.php) passe OnlyLoaded :
    // elle ouvre la meme vue, filtre "Active" preselectionne.
    $addon   = isset($_GET["OnlyLoaded"])?"&OnlyLoaded=yes":"";
    $init    = $addon!==""?"active":"all";
    $M       = fwmod_model();
    $reload  = "LoadAjax('iptables-modules-start','$page?modules-table=yes$addon')";

    if(!$M["ok"]){
        echo fwmod_style().$tpl->_ENGINE_parse_body("<div id='fwmod'>".fwmod_banner().fwmod_error($reload)."</div>")."<script>NoSpinner();</script>";
        return true;
    }
    $html="<div id='fwmod'>".fwmod_banner().fwmod_tiles($M).fwmod_features_card($M).fwmod_filters($init).fwmod_list($M)."</div>";
    // CSS et JS HORS de _ENGINE_parse_body : la regex des {tokens} lirait chaque bloc d'accolades.
    echo fwmod_style().$tpl->_ENGINE_parse_body($html).fwmod_js($init);
    return true;
}
function interfaces_start():bool{

    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:250px;padding-left: 10px;padding-right: 10px'>";
    $html[]="<div id='Firewall-status'>". $tpl->spinner()."</div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align: top;width:99%'>";
    $html[]="<div id='Firewall-interfaces-table'>". $tpl->spinner()."</div>";
    $html[]="<div id='Firewall-interfaces-settings'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    // Le <table> de mise en page ouvert plus haut n'etait JAMAIS ferme: seule
    // la reparation du parseur HTML du navigateur rendait la page lisible.
    $html[]="</table>";
    $html[]="<script>";

    $IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_VERSION");
    $uninstall=$tpl->framework_buildjs("/firewall/uninstall",
    "firehol.reconfigure.progress",
    "firehol.reconfigure.log","progress-firehol-restart","document.location.href='/index'");

    $topbuttons[] = array($uninstall, ico_trash, "{uninstall}");

    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure","firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart",
        "LoadAjax('Firewall-interfaces-table','$page?interfaces=yes')");


    $topbuttons[] = array($jsrestart, ico_refresh, "{apply_firewall_rules}");


    $TINY_ARRAY["TITLE"]="{firewall_parameters} v$IPTABLES_VERSION";
    $TINY_ARRAY["ICO"]="fa-solid fa-block-brick-fire";
    $TINY_ARRAY["EXPL"]="{firewallexplain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);


    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]=$jstiny;
    $html[]="LoadAjax('Firewall-interfaces-table','$page?interfaces=yes');";
    $html[]="LoadAjax('Firewall-interfaces-settings','$page?flat=yes');";
    $html[]="</script>";

    echo @implode("\n",$html);
    return true;

}
// Les pastilles d'etat, partagees par la ligne d'une interface et la ligne
// « valeurs par defaut ». Les deux construisaient AUPARAVANT les memes neuf
// cellules dans deux fonctions distinctes, avec deux copies de $BEHA/$BEHA2 --
// deux definitions qui n'attendaient qu'une divergence.
function fwset_policy_badge($v):string{
    $M=array("reject"=>"<span class='awc-badge awc-badge-alert'>{deny_all}</span>",
             "accept"=>"<span class='awc-badge awc-badge-on'>{allow_all}</span>");
    // Lecture gardee: firewall_policy vaut "" sur une carte jamais configuree,
    // et l'index absent rendait une cellule vide plus un avertissement PHP 8.
    return isset($M[(string)$v])?$M[(string)$v]:"<span class='awc-tag'>{unknown}</span>";
}
// Politique sortante d'une interface (colonne écrite par le daemon, lue par l'API).
function fwset_policy_out(string $iface):string{
    $d=fwapi_data(fwapi_get("nics/".rawurlencode($iface)."/firewall"));
    return is_array($d)?(string)($d["firewall_policy_out"] ?? ""):"";
}
function fwset_zone_badge($v):string{
    $M=array(0=>"<span class='awc-tag'>{none}</span>",
             1=>"<span class='awc-badge awc-badge-on'>LAN</span>",
             2=>"<span class='awc-badge awc-badge-warn'>WAN</span>");
    return isset($M[intval($v)])?$M[intval($v)]:"<span class='awc-tag'>{unknown}</span>";
}
function fwset_flag($on):string{
    return $on?"<i class='fas fa-check' style='color:#26a69a'></i>"
              :"<i class='far fa-square' style='color:#bcc9c6'></i>";
}
function fwset_ban($on):string{
    return $on?"<i class='fas fa-ban' style='color:#ba1a1a'></i>"
              :"<i class='far fa-square' style='color:#bcc9c6'></i>";
}

// UNE ligne d'interface, batie a partir d'un jeu de drapeaux normalise. La
// ligne « par defaut » et les lignes reelles passent par ici: neuf cellules
// decrites une seule fois.
function fwset_iface_row($ident,$f):string{
    $isFW=!empty($f["isFW"]);
    $h ="<tr>";
    $h.="<td>$ident</td>";
    $h.="<td class='center' style='width:1%' nowrap>".fwset_flag($isFW)."</td>";
    // Sans pare-feu sur l'interface, politique et zone ne s'appliquent pas:
    // afficher « refuser tout » y ferait lire une regle qui n'existe pas.
    // Politique sortante restrictive : signalée à côté de la politique entrante.
    $out=(($f["policy_out"] ?? "")==="reject")?" <span class='awc-badge awc-badge-alert'>OUT {deny_all}</span>":"";
    $h.="<td class='center' style='width:1%' nowrap>".($isFW?fwset_policy_badge($f["policy"]).$out:fwset_flag(false))."</td>";
    $h.="<td class='center' style='width:1%' nowrap>".($isFW?fwset_zone_badge($f["zone"]):fwset_flag(false))."</td>";
    $h.="<td class='center' style='width:1%' nowrap>".fwset_flag(!empty($f["headers"]))."</td>";
    $h.="<td class='center' style='width:1%' nowrap>".fwset_flag(!empty($f["masq"]))."</td>";
    $h.="<td class='center' style='width:1%' nowrap>".fwset_ban($isFW && !empty($f["ddos"]))."</td>";
    $h.="<td class='center' style='width:1%' nowrap>".fwset_ban(!empty($f["denydhcp"]))."</td>";
    $h.="<td class='center' style='width:1%' nowrap>".($isFW?fwset_flag(!empty($f["artica"])):fwset_flag(false))."</td>";
    return $h."</tr>";
}

// Le bloc d'identite d'une interface: pastille d'icone, nom, adresse en
// sous-titre. La pastille s'eteint quand l'interface n'est pas protegee.
function fwset_iface_ident($tpl,$page,$label,$sub,$js,$isFW):string{
    $icoCls=$isFW?"awc-ico":"awc-ico awc-ico-off";
    $h ="<div class='awc-ident'>";
    $h.="<span class='$icoCls'><i class='fas fa-ethernet'></i></span>";
    $h.="<div><div class='awc-name'>".$tpl->td_href($label,"{click_to_edit}",$js)."</div>";
    if($sub!==""){ $h.="<div class='awc-meta awc-mono'>".htmlspecialchars($sub)."</div>"; }
    return $h."</div></div>";
}

function fwset_mark($name){
    $s=strtolower(preg_replace('/[^A-Za-z0-9]/','',(string)$name));
    if($s===""){return "";}
    return htmlspecialchars(substr($s,0,3));
}

function fwset_chip($on,$label){
    $cls=$on?"ak-pill fwset-chip-on":"ak-pill ak-off";
    return "<span class='$cls'>$label</span>";
}

function fwset_policy_span($v){
    if($v==="reject"){return "<span class='ak-pill ak-err'><span class='ak-dot'></span>{deny_all}</span>";}
    if($v==="accept"){return "<span class='ak-pill ak-ok'><span class='ak-dot'></span>{allow_all}</span>";}
    return "<span class='ak-pill ak-off'>{unknown}</span>";
}

function fwset_zone_span($z){
    $z=intval($z);
    if($z===1){return "<span class='ak-pill ak-ok'>LAN</span>";}
    if($z===2){return "<span class='ak-pill ak-warn'>WAN</span>";}
    return "<span class='ak-pill ak-off'>{fwset_zone_none}</span>";
}

function fwset_facts($c){
    $on=array();
    if(empty($c["isFW"])){
        $on=array("headers"=>false,"masq"=>false,"ddos"=>false,"denydhcp"=>false,"artica"=>false);
    }else{
        $on=$c;
    }
    $h=array();
    $h[]=fwset_chip(!empty($on["headers"]),"{headers}");
    $h[]=fwset_chip(!empty($on["masq"]),"{fwset_masq}");
    if(!empty($on["ddos"])){$h[]="<span class='ak-pill ak-err'><i class='fas fa-ban'></i> {fwset_ddos_on}</span>";}
    else{$h[]=fwset_chip(false,"DDOS");}
    if(!empty($on["denydhcp"])){$h[]="<span class='ak-pill ak-err'><i class='fas fa-ban'></i> {fwset_dhcp_deny}</span>";}
    else{$h[]=fwset_chip(false,"DHCP");}
    $h[]=fwset_chip(!empty($on["artica"]),"{fwset_console}");
    return @implode("",$h);
}

function fwset_card($c){
    $isFW=!empty($c["isFW"]);
    $isDef=!empty($c["def"]);
    $zone=intval(isset($c["zone"])?$c["zone"]:0);
    $tile="fwset-tile fwset-tile-off";
    if($isFW && $zone===2){$tile="fwset-tile fwset-tile-wan";}
    elseif($isFW){$tile="fwset-tile fwset-tile-on";}
    $card="fwset-card";
    if($isDef){$card.=" fwset-card-def";}
    elseif(!$isFW){$card.=" fwset-card-off";}
    $mark=$isDef?"<i class='fas fa-asterisk fwset-tile-ico'></i>":fwset_mark(isset($c["mark"])?$c["mark"]:"");
    $label=isset($c["label"])?$c["label"]:"";
    $js=isset($c["js"])?$c["js"]:"";
    // The whole card opens the editor: only the name used to, and a click on
    // the tile, the policy or the chips did nothing. The name keeps its link
    // look but carries no handler of its own, so a click on it bubbles to the
    // card once instead of opening the dialog twice.
    $name="<a href=\"javascript:blur();\">$label</a>";
    $nick="";
    if(!$isDef && isset($c["nick"]) && trim((string)$c["nick"])!==""){
        $nick=" <span class='fwset-nick'>".htmlspecialchars((string)$c["nick"])."</span>";
    }
    $sub="";
    if($isDef){$sub="<div class='fwset-sub'>{fwset_default_sub}</div>";}
    elseif(isset($c["ip"]) && trim((string)$c["ip"])!==""){
        $sub="<div class='fwset-ip'>".htmlspecialchars((string)$c["ip"])."</div>";
    }
    $badge=$isFW
        ?"<span class='fwset-badge fwset-badge-on'>{fwset_protected}</span>"
        :"<span class='fwset-badge fwset-badge-off'>{fwset_unprotected}</span>";
    if($isFW){
        $out=((string)(isset($c["policy_out"])?$c["policy_out"]:""))==="reject"
            ?"<span class='ak-pill ak-err'>{fwset_out_deny}</span>":"";
        $mid="<div class='fwset-polrow'>".fwset_policy_span(isset($c["policy"])?$c["policy"]:"").$out.fwset_zone_span($zone)."</div>";
    }else{
        $mid="<div class='fwset-none'>{fwset_no_rule}</div>";
    }
    $factsL=$isDef?"{fwset_facts_default}":"{fwset_facts}";
    $open="";
    if($js!==""){
        // No braces here: the card goes through _ENGINE_parse_body, which
        // reads any {…} as a language token.
        $open=" role='button' tabindex='0' OnClick=\"$js\" OnKeyDown=\"if(event.key==='Enter')$js\"";
    }
    return "<div class='$card'$open><div class='fwset-top'><span class='$tile'>$mark</span>"
        ."<div class='fwset-id'><div class='fwset-name'>$name$nick</div>$sub</div>$badge</div>"
        ."$mid<div class='fwset-facts'><div class='fwset-facts-l'>$factsL</div>"
        ."<div class='fwset-chips'>".fwset_facts($c)."</div></div></div>";
}

function fwset_style():string{
    if(!empty($GLOBALS["FWSET_STYLE"])){return "";}
    $GLOBALS["FWSET_STYLE"]=1;
    $soft="rgba(16,185,129,.12)";
    $accent="#10b981";
    $dark="#0d9488";
    $line="rgba(16,185,129,.35)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.fwset-head{display:flex;align-items:baseline;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 12px}
.fwset-kicker{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6d7a77}
.fwset-hint{font-size:12px;color:#6d7a77}
.fwset-grid{width:100%;border-collapse:separate;border-spacing:0}
.fwset-grid,.fwset-grid tbody{display:block}
#fwset-nic-rows{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fwset-grid tr,.fwset-grid td{display:block}
.fwset-card{background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:16px 18px;color:#111d23;box-shadow:0 1px 3px rgba(15,23,42,.05);height:100%}
.fwset-card *{box-sizing:border-box}
.fwset-card[role=button]{cursor:pointer;transition:border-color .15s,box-shadow .15s}
.fwset-card[role=button]:hover,.fwset-card[role=button]:focus{border-color:$accent;box-shadow:0 2px 8px rgba(15,23,42,.10);outline:none}
.fwset-card-off{background:#fff}
.fwset-card-off .fwset-name a{color:#6d7a77}
.fwset-card-def{background:#fff;border-style:dashed;border-width:2px}
.fwset-top{display:flex;align-items:flex-start;gap:12px}
.fwset-tile{width:36px;height:36px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex:0 0 auto;border:1px solid #bcc9c6;background:#e3f0f8;color:#3d4947}
.fwset-tile-on{background:$soft;color:$dark;border-color:$line}
.fwset-tile-wan{background:#ffddb9;color:#865300;border-color:#865300}
.fwset-tile-off{background:#e3f0f8;color:#6d7a77;border-color:#bcc9c6}
.fwset-tile-ico{font-size:15px}
.fwset-id{flex:1;min-width:0}
.fwset-name{font-size:16px;font-weight:700;line-height:22px}
.fwset-name a{color:#111d23;text-decoration:none}
.fwset-name a:hover{color:$dark}
.fwset-nick{font-size:12px;font-weight:400;color:#6d7a77}
.fwset-ip,.fwset-sub{font-size:12px;line-height:16px;color:#3d4947;margin-top:2px}
.fwset-sub{color:#6d7a77}
.fwset-badge{font-size:12px;font-weight:700;padding:4px 10px;border-radius:8px;white-space:nowrap;flex:0 0 auto}
.fwset-badge-on{background:$accent;color:#fff}
.fwset-badge-off{background:#e3f0f8;color:#3d4947;border:1px solid #bcc9c6}
.fwset-polrow{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:12px;padding-top:10px;border-top:1px solid #e3f0f8}
.fwset-none{margin-top:12px;padding-top:10px;border-top:1px solid #e3f0f8;font-size:12px;color:#6d7a77}
.fwset-chip-on{background:$accent;color:#fff}
.fwset-facts{margin-top:12px;padding-top:10px;border-top:1px solid #e3f0f8}
.fwset-facts-l{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6d7a77;margin-bottom:8px}
.fwset-chips{display:flex;flex-wrap:wrap;gap:6px}
.fwset-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:12px;font-size:12px;color:#6d7a77}
.fwset-foot b{color:#111d23}
@media(max-width:900px){#fwset-nic-rows{grid-template-columns:1fr}}
</style>";
}

function interfaces(){
    $page               = CurrentPageName();
    $tpl                = new template_admin();

    $datas=TCP_LIST_NICS_W();
    if(!is_array($datas)){$datas=array();}

    $rows=array();
    foreach ($datas as $val){
        $val=trim((string)$val);
        if($val===""){continue;}
        $nic=new system_nic($val);
        if($nic->enabled==0){continue;}
        $nick=trim((string)$nic->NICNAME);
        if(strcasecmp($nick,$val)===0){$nick="";}
        $rows[]="<tr><td>".fwset_card(array(
            "label"=>htmlspecialchars($val).(firewall_meta_holds_iface($val)?firewall_meta_badge():""),
            "mark"=>$val,
            "nick"=>$nick,
            "ip"=>(string)$nic->IPADDR,
            "js"=>"Loadjs('$page?nic-fw-edit=".rawurlencode($val)."')",
            "isFW"=>($nic->isFW==1),
            "policy"=>$nic->firewall_policy,
            "policy_out"=>fwset_policy_out($val),
            "zone"=>$nic->firewall_behavior,
            "headers"=>($nic->xtIpv4options==1),
            "masq"=>($nic->firewall_masquerade==1),
            "ddos"=>($nic->AntiDDOS==1),
            "denydhcp"=>($nic->isFW==1 && $nic->denydhcp==1),
            "artica"=>($nic->firewall_artica==1),
        ))."</td></tr>";
    }
    $c=count($rows);
    $rows[]=getRowDefault();

    $html=array();
    $metaBanner=firewall_meta_banner("interfaces",$tpl);
    $html[]="<div class='fwset-head'><span class='fwset-kicker'>{fwset_postures}</span>"
           ."<span class='fwset-hint'>{fwset_edit_hint}</span></div>";
    $html[]="<table class='fwset-grid'><tbody id='fwset-nic-rows'>";
    $html[]=@implode("\n",$rows);
    $html[]="</tbody></table>";
    $html[]="<div class='fwset-foot'><span>{showing} <b>$c</b> {items} — {fwset_except_default}</span>"
           ."<span class='awc-pg' id='fwset-nic-pager'></span></div>";

    // "Firewall-status" est une SENTINELLE: ArticaRefresh.tick() detruit le
    // minuteur -- en silence, avant la premiere requete -- si l'identifiant
    // n'est pas dans le DOM. Il vit dans le fragment voisin, emis par
    // interfaces_start().
    $js=$tpl->RefreshInterval_js("Firewall-status",$page,"firewall-status=yes");
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$js\n</script>";

    echo fwset_style().awc_table_style().$metaBanner
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"fwset-nic-rows","fwset-nic-pager");
}

// La ligne « valeurs par defaut » applique aux interfaces sans reglage propre.
function getRowDefault():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $D=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireWallDefaultInterfacesParams"));
    if(!is_array($D)){$D=array();}
    // Le garde ne posait les valeurs par defaut QUE si firewall_policy
    // manquait: un enregistrement partiel laissait les autres cles absentes, et
    // chacune de leurs lectures etait un avertissement PHP 8 puis une cellule
    // fausse. Chaque cle a desormais son propre repli.
    $DEF=array("firewall_policy"=>"accept","firewall_behavior"=>0,"firewall_artica"=>1,
               "AntiDDOS"=>0,"denydhcp"=>0,"isFW"=>1,"xtIpv4optionsEnabled"=>0,
               "firewall_ping"=>"accept");
    $missing=false;
    foreach($DEF as $k=>$v){ if(!isset($D[$k])){$D[$k]=$v;$missing=true;} }
    if($missing){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FireWallDefaultInterfacesParams",base64_encode(serialize($D)));
    }

    return "<tr><td>".fwset_card(array(
        "def"=>true,
        "label"=>"{default}",
        "js"=>"Loadjs('$page?nic-fw-default=yes')",
        "isFW"=>!empty($D["isFW"]),
        "policy"=>$D["firewall_policy"],
        "policy_out"=>"",
        "zone"=>$D["firewall_behavior"],
        "headers"=>!empty($D["xtIpv4optionsEnabled"]),
        "masq"=>false,
        "ddos"=>!empty($D["AntiDDOS"]),
        "denydhcp"=>!empty($D["denydhcp"]),
        "artica"=>!empty($D["firewall_artica"]),
    ))."</td></tr>";
}
