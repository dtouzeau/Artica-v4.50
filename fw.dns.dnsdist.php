<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["flat-settings"])){flat_settings();exit;}
if(isset($_GET["set-net-js"])){set_net_js();exit;}
if(isset($_GET["set-net-popup"])){set_net_popup();exit;}
if(isset($_GET["set-tls-js"])){set_tls_js();exit;}
if(isset($_GET["set-tls-popup"])){set_tls_popup();exit;}
if(isset($_GET["set-events-js"])){set_events_js();exit;}
if(isset($_GET["set-events-popup"])){set_events_popup();exit;}
if(isset($_GET["set-cache-js"])){set_cache_js();exit;}
if(isset($_GET["set-cache-popup"])){set_cache_popup();exit;}
// Les quatre modales postent le meme marqueur: la sauvegarde etait jusqu'ici
// dispatchee sur EnableDNSRootInts, un champ que trois d'entre elles n'emettent
// pas -- leur enregistrement serait tombe dans page().
if(isset($_POST["dnsdist-save"])){save();exit;}
if(isset($_GET["unbound-status"])){unbound_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_UNBOUND} v{$UnboundVersion}</h1>
	<p>{didyouknow_unbound}</p>
	</div>

	</div>



	<div class='row'><div id='progress-unbound-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-dns-servers'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/dns-cache');
	LoadAjax('table-loader-dns-servers','$page?tabs=yes');

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	echo $tpl->_ENGINE_parse_body($html);

}
function config_file_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return;}
	$tpl->js_dialog1("{APP_UNBOUND} >> {config_file}", "$page?config-file-popup=yes");

}
function config_file_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();

	$data=@file_get_contents("/etc/unbound/unbound.conf");
	$form[]=$tpl->field_textareacode("configfile", null, $data);


	echo $tpl->form_outside("{config_file}", @implode("", $form),"{display_generated_configuration_file}",null,"","AsSystemAdministrator");

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$MUNIN=false;
	$MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
	$EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
	if($MUNIN_CLIENT_INSTALLED==1){if($EnableMunin==1){$MUNIN=true;}}

	$array["{status}"]="$page?table=yes";
    $array["{networks_restrictions}"]="fw.pdns.restrictions.php?tinypage-unbound=yes";
	$array["{cache}"]="fw.dns.unbound.cache.php";
	
	$EnableDNSCryptProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));
	
	if($EnableDNSCryptProxy==1){
		$array["{APP_DNSCRYPT_PROXY}"]="fw.dnscrypt-proxy.php";
	}
	
	
	
	
	if($MUNIN){
		$array["{statistics}"]="fw.unbound.statistics.php";
	}
	echo $tpl->tabs_default($array);

}

function unbound_status(){
	
	$sock=new sockets();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/status");
	$bsini=new Bs_IniHandler(PROGRESS_DIR."/dnsdist.status");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$EnableDNSCryptProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));
	$PDNSStatsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsEnabled"));
	
    $jsRestart=$tpl->framework_buildjs("/unbound/restart",
        "unbound.restart.progress","unbound.restart.log",
        "progress-unbound-restart","LoadAjaxSilent('unbound-status','$page?unbound-status=yes');");

	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.dsc.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/pdns.dsc.progress.txt";
	$ARRAY["CMD"]="pdns.php?restart-dsc=yes";
	$ARRAY["TITLE"]="{APP_DSC} {restarting_service}";
	$ARRAY["AFTER"]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestartDSC="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-unbound-restart')";
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
	$ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
	$ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
	$ARRAY["AFTER"]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestartDNSCrypt="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-unbound-restart')";
	

	
	
	
	
	$btn_config=$tpl->button_autnonome("{config_file}", "Loadjs('$page?config-file-js=yes')", "fas fa-file-code","AsSystemAdministrator",335);
	
	$final[]=$tpl->SERVICE_STATUS($bsini, "APP_UNBOUND",$jsRestart);

	if($PDNSStatsEnabled==1) {
        $final[] = $tpl->SERVICE_STATUS($bsini, "APP_DSC", $jsRestartDSC);
    }


    if($EnableDNSCryptProxy==1) {
        $final[] = $tpl->SERVICE_STATUS($bsini, "APP_DNSCRYPT_PROXY", $jsRestartDNSCrypt);
    }


    $final[]="$btn_config";
    VERBOSE(count($final)." row",__LINE__);
    // SERVICE_STATUS() renvoie DEJA du contenu analyse, precede de sa feuille de
    // style et suivi de son <script>; button_autnonome() analyse aussi son
    // libelle. Les repasser dans _ENGINE_parse_body() faisait traverser le
    // <style> des tuiles au moteur de traduction, dont la regex #{(.+?)}# lit
    // chaque corps de regle CSS comme un {token} et declenche une recherche
    // memcached par regle -- une collision corrompant la feuille entiere.
    echo @implode("\n",$final);
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	// A gauche l'etat du service, a droite ses reglages: deux volets
	// independants, chacun avec son propre rafraichissement. Le volet d'etat ne
	// doit pas attendre un enregistrement, et un enregistrement ne doit pas
	// redessiner l'etat au milieu de son minuteur.
	$html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='unbound-status' style='margin-top:15px'></div></td>
		<td style='vertical-align:top;width:90%'><div id='flat-dnsdist' style='margin-top:15px'></div></td>
	</tr>
	</table>";

	$js="<script>"
	   ."LoadAjaxSilent('unbound-status','$page?unbound-status=yes');"
	   ."LoadAjaxSilent('flat-dnsdist','$page?flat-settings=yes');"
	   ."</script>";

	echo $tpl->_ENGINE_parse_body($html).$js;
}

function save(){
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$allowed=dnsdist_savable();

	// Les interfaces d'ecoute ne sont pas stockees sous le nom du champ: elles
	// vont dans PowerDNSListenAddr, une par ligne.
	if(isset($_POST["InComingInterfaces"])){
		$array=explode(",",$_POST["InComingInterfaces"]);
		$sock->SaveConfigFile(@implode("\n", $array), "PowerDNSListenAddr");
		unset($_POST["InComingInterfaces"]);
	}

	// Le declenchement de la reconstruction des listes noires se decide sur
	// l'ANCIENNE valeur: elle doit donc etre lue avant d'ecrire.

	// L'ancienne boucle ecrivait TOUT $_POST dans Valkey. Seules les cles que ce
	// formulaire emet reellement sont desormais acceptees; les autres sont
	// ignorees en silence, comme si elles n'avaient pas ete envoyees.
	$written=array();
	foreach ($_POST as $key=>$val){
		if(!isset($allowed[$key])){continue;}
		$val=url_decode_special_tool($val);
		$sock->SET_INFO($key, $val);
		$written[]="$key=$val";
	}

	// Le test d'origine portait sur $_POST["UnboundEnabled"], un champ qu'aucun
	// formulaire de cette page n'emet: la condition lisait un index inexistant.
	if(count($written)>0){
		admin_tracks("Unbound settings: ".@implode(", ", $written));
	}
	// Contrat form_outside: rien n'est ecrit en cas de succes.
}

/**
 * Le catalogue des durees proposees pour les TTL de cache.
 * Les libelles portent des {tokens}: table_form_compile() les resout.
 */
function dnsdist_times(){
    $TIMES=array();
    $TIMES[-1]="{not_used}";
    $TIMES[10]="10 {seconds}";
    $TIMES[20]="20 {seconds}";
    $TIMES[30]="30 {seconds}";
    $TIMES[60]="1 {minute}";
    $TIMES[300]="5 {minutes}";
    $TIMES[900]="15 {minutes}";
    $TIMES[1800]="30 {minutes}";
    $TIMES[3600]="1 {hour}";
    $TIMES[7200]="2 {hours}";
    $TIMES[10800]="3 {hours}";
    $TIMES[14400]="4 {hours}";
    $TIMES[28800]="8 {hours}";
    $TIMES[57600]="16 {hours}";
    $TIMES[86400]="1 {day}";
    $TIMES[172800]="2 {days}";
    $TIMES[604800]="7 {days}";
    return $TIMES;
}

/**
 * Les reglages du service, lus une seule fois et defauts appliques.
 *
 * La carte « flat » et les quatre modales lisaient sinon les memes cles chacune
 * de son cote, avec des defauts qui divergeaient au premier oubli.
 */
function dnsdist_settings(){
    $sock=$GLOBALS["CLASS_SOCKETS"];

    if (method_exists($sock, 'INFO_EXISTS')) {
        if (!$sock->INFO_EXISTS("UnBoundCacheMAXTTL")) { $sock->SET_INFO("UnBoundCacheMAXTTL", 172800); }
        if (!$sock->INFO_EXISTS("UnBoundCacheNEGTTL")) { $sock->SET_INFO("UnBoundCacheNEGTTL", 30); }
    }

    $S=array();
    $S["UnboundDisplayVersion"]=intval($sock->GET_INFO("UnboundDisplayVersion"));
    $S["EnableDNSRootInts"]=intval($sock->GET_INFO("EnableDNSRootInts"));
    $S["ListenOnlyLoopBack"]=intval($sock->GET_INFO("ListenOnlyLoopBack"));
    $S["UnboundOutGoingInterface"]=trim($sock->GET_INFO("UnboundOutGoingInterface"));

    $S["UnboundTLSEnable"]=intval($sock->GET_INFO("UnboundTLSEnable"));
    $S["UnboundTLSCertificate"]=trim($sock->GET_INFO("UnboundTLSCertificate"));
    $S["UnboundTLSPort"]=intval($sock->GET_INFO("UnboundTLSPort"));
    if($S["UnboundTLSPort"]==0){$S["UnboundTLSPort"]=853;}

    $S["EnableUnboundLogQueries"]=intval($sock->GET_INFO("EnableUnboundLogQueries"));
    $S["UnboundLogSyslogDoNotStorelogsLocally"]=intval($sock->GET_INFO("UnboundLogSyslogDoNotStorelogsLocally"));

    $S["UnBoundCacheSize"]=intval($sock->GET_INFO("UnBoundCacheSize"));
    if($S["UnBoundCacheSize"]==0){$S["UnBoundCacheSize"]=100;}
    $S["UnBoundCacheMinTTL"]=intval($sock->GET_INFO("UnBoundCacheMinTTL"));
    $S["UnBoundCacheMAXTTL"]=intval($sock->GET_INFO("UnBoundCacheMAXTTL"));
    if($S["UnBoundCacheMAXTTL"]==0){$S["UnBoundCacheMAXTTL"]=172800;}
    $S["UnBoundCacheNEGTTL"]=intval($sock->GET_INFO("UnBoundCacheNEGTTL"));
    if($S["UnBoundCacheNEGTTL"]==0){$S["UnBoundCacheNEGTTL"]=30;}

    // Les interfaces d'ecoute vivent dans PowerDNSListenAddr, une valeur par
    // ligne; le champ de saisie, lui, travaille en liste separee par virgules.
    $PowerDNSListenAddr=explode("\n",trim($sock->GET_INFO("PowerDNSListenAddr")));
    $S["InComingInterfaces"]=@implode(",", $PowerDNSListenAddr);

    return $S;
}

/**
 * Les SEULES cles que save() accepte d'ecrire.
 *
 * L'ancienne boucle ecrivait TOUT $_POST dans Valkey (`foreach($_POST as $k=>$v)
 * SET_INFO($k,$v)`): n'importe quel champ poste devenait un reglage de
 * l'appliance, y compris des cles appartenant a d'autres modules. Une liste
 * blanche explicite est la seule facon de borner ce que ce formulaire peut
 * toucher. InComingInterfaces n'y figure pas: il est traite a part, car il
 * n'est pas stocke sous ce nom.
 */
function dnsdist_savable(){
    return array(
        "UnboundDisplayVersion"=>true,
        "EnableDNSRootInts"=>true,
        "ListenOnlyLoopBack"=>true,
        "UnboundOutGoingInterface"=>true,
        "UnboundTLSEnable"=>true,
        "UnboundTLSPort"=>true,
        "UnboundTLSCertificate"=>true,
        "EnableUnboundLogQueries"=>true,
        "UnboundLogSyslogDoNotStorelogsLocally"=>true,
        "UnBoundCacheSize"=>true,
        "UnBoundCacheMinTTL"=>true,
        "UnBoundCacheMAXTTL"=>true,
        "UnBoundCacheNEGTTL"=>true,
    );
}

/**
 * Ce que fait une modale apres un enregistrement REUSSI.
 *
 * form_outside ne lance ce code que sur une reponse vide, donc uniquement en
 * cas de succes. On rafraichit la SEULE carte concernee (LoadAjaxSilent, sans
 * spinner: la page en porte deux et leurs spinners se battraient), puis on
 * declenche le redemarrage du service, sans quoi le reglage enregistre ne
 * s'applique pas.
 */
function dnsdist_jsafter(){
    $page=CurrentPageName();
    return "dialogInstance2.close();"
          ."LoadAjaxSilent('flat-dnsdist','$page?flat-settings=yes');"
          ."Loadjs('fw.dns.unbound.restart.php');";
}
/**
 * La carte des reglages, convention « flat » (table_form_*).
 *
 * Quinze champs empiles dans un formulaire unique obligeaient a parcourir toute
 * la colonne pour lire une valeur, et n'affichaient aucun etat: on ne voyait pas
 * si DNS-over-TLS etait actif sans deplier le formulaire. Ici chaque reglage est
 * une ligne (pastille, libelle, valeur ou ACTIF/INACTIF), les groupes sont des
 * intertitres et la grille passe a deux colonnes.
 *
 * table_form_field_js() arme le crayon de la ligne SUIVANTE, et n'est jamais
 * desarme: chaque section reprend donc la main sur son propre ouvreur.
 */
function flat_settings(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $S=dnsdist_settings();
    $TIMES=dnsdist_times();

    $netjs="Loadjs('$page?set-net-js=yes')";
    $tlsjs="Loadjs('$page?set-tls-js=yes')";
    $evtjs="Loadjs('$page?set-events-js=yes')";
    $cchjs="Loadjs('$page?set-cache-js=yes')";

    $tpl->table_form_section("{local_dns_service}");
    $tpl->table_form_field_js($netjs,"AsDnsAdministrator");
    $tpl->table_form_field_bool("{display_servername_version}",$S["UnboundDisplayVersion"],"fas fa-tag");
    $tpl->table_form_field_js($netjs,"AsDnsAdministrator");
    $tpl->table_form_field_bool("{EnableDNSRootInts}||{EnableDNSRootInts_explain}",$S["EnableDNSRootInts"],"fas fa-earth-europe");
    $tpl->table_form_field_js($netjs,"AsDnsAdministrator");
    $tpl->table_form_field_text("{listen_interfaces}",dnsdist_value($S["InComingInterfaces"]),"fas fa-ethernet");
    $tpl->table_form_field_js($netjs,"AsDnsAdministrator");
    $tpl->table_form_field_bool("{listen_only_loopback}",$S["ListenOnlyLoopBack"],"fas fa-circle-nodes");
    $tpl->table_form_field_js($netjs,"AsDnsAdministrator");
    $tpl->table_form_field_text("{outgoing_interface}",dnsdist_value($S["UnboundOutGoingInterface"]),"fas fa-arrow-right-from-bracket");

    $tpl->table_form_section("{DNSOTLS}","{DNSOTLS_EXPLAIN}");
    $tpl->table_form_field_js($tlsjs,"AsDnsAdministrator");
    $tpl->table_form_field_bool("{DNSOTLS_ENABLE}",$S["UnboundTLSEnable"],"fas fa-lock");
    $tpl->table_form_field_js($tlsjs,"AsDnsAdministrator");
    $tpl->table_form_field_text("{listen_port}",intval($S["UnboundTLSPort"]),"fas fa-plug");
    $tpl->table_form_field_js($tlsjs,"AsDnsAdministrator");
    $tpl->table_form_field_text("{certificate}",dnsdist_value($S["UnboundTLSCertificate"]),"fas fa-certificate");

    $tpl->table_form_section("{events}");
    $tpl->table_form_field_js($evtjs,"AsDnsAdministrator");
    $tpl->table_form_field_bool("{log_queries}",$S["EnableUnboundLogQueries"],"fas fa-file-lines");
    $tpl->table_form_field_js($evtjs,"AsDnsAdministrator");
    $tpl->table_form_field_bool("{not_store_log_locally}",$S["UnboundLogSyslogDoNotStorelogsLocally"],"fas fa-hard-drive");

    $tpl->table_form_section("{cache}");
    $tpl->table_form_field_js($cchjs,"AsDnsAdministrator");
    $tpl->table_form_field_text("{cache_size} (MB)",intval($S["UnBoundCacheSize"]),"fas fa-memory");
    $tpl->table_form_field_js($cchjs,"AsDnsAdministrator");
    $tpl->table_form_field_text("{cache-ttl} (Min)",dnsdist_ttl($TIMES,$S["UnBoundCacheMinTTL"]),"fas fa-hourglass-start");
    $tpl->table_form_field_js($cchjs,"AsDnsAdministrator");
    $tpl->table_form_field_text("{cache-ttl} (Max)",dnsdist_ttl($TIMES,$S["UnBoundCacheMAXTTL"]),"fas fa-hourglass-end");
    $tpl->table_form_field_js($cchjs,"AsDnsAdministrator");
    $tpl->table_form_field_text("{negquery-cache-ttl}",dnsdist_ttl($TIMES,$S["UnBoundCacheNEGTTL"]),"fas fa-ban");
    $tpl->table_form_field_js($cchjs,"AsDnsAdministrator");

    // table_form_compile() monte deja son CSS hors de l'analyse et resout les
    // {tokens} du contenu: on ne le repasse pas dans _ENGINE_parse_body().
    echo $tpl->table_form_compile(2);
}

/**
 * Une valeur de reglage prete a l'affichage.
 *
 * Vide se dit explicitement: une cellule blanche ne distingue pas « aucune
 * interface choisie » de « la valeur n'a pas pu etre lue ». htmlspecialchars()
 * ne touche pas aux accolades, donc les {tokens} d'un libelle de duree
 * traversent intacts.
 */
function dnsdist_value($v){
    $v=trim((string)$v);
    if($v===""){return "<i class='text-muted'>{none}</i>";}
    return htmlspecialchars($v);
}

/**
 * Le libelle d'une duree. Une valeur absente du catalogue s'affiche en secondes
 * plutot que de laisser la ligne vide -- le cas se produit des qu'un reglage a
 * ete pose ailleurs que par cette page.
 */
function dnsdist_ttl($TIMES,$value){
    $value=intval($value);
    if(isset($TIMES[$value])){return $TIMES[$value];}
    return "$value {seconds}";
}

function set_net_js():bool{
    $page=CurrentPageName();$tpl=new template_admin();
    return $tpl->js_dialog2("{local_dns_service}","$page?set-net-popup=yes",700);
}
function set_net_popup(){
    $page=CurrentPageName();$tpl=new template_admin();
    $S=dnsdist_settings();
    $form=array();
    $form[]=$tpl->field_hidden("dnsdist-save","net");
    $form[]=$tpl->field_checkbox("UnboundDisplayVersion","{display_servername_version}",$S["UnboundDisplayVersion"]);
    $form[]=$tpl->field_checkbox("EnableDNSRootInts","{EnableDNSRootInts}",$S["EnableDNSRootInts"],false,"{EnableDNSRootInts_explain}");
    $form[]=$tpl->field_interfaces_choose("InComingInterfaces","{listen_interfaces}",$S["InComingInterfaces"]);
    $form[]=$tpl->field_checkbox("ListenOnlyLoopBack","{listen_only_loopback}",$S["ListenOnlyLoopBack"]);
    $form[]=$tpl->field_interfaces("UnboundOutGoingInterface","{outgoing_interface}",$S["UnboundOutGoingInterface"]);
    $html=array();
    $html[]=$tpl->div_explain("{local_dns_service}||{didyouknow_unbound}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),"","{apply}",dnsdist_jsafter(),"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function set_tls_js():bool{
    $page=CurrentPageName();$tpl=new template_admin();
    return $tpl->js_dialog2("{DNSOTLS}","$page?set-tls-popup=yes",700);
}
function set_tls_popup(){
    $tpl=new template_admin();
    $S=dnsdist_settings();
    $form=array();
    $form[]=$tpl->field_hidden("dnsdist-save","tls");
    // Le 4e argument est la liste des champs a griser quand la case retombe, le
    // 5e est le texte d'aide. L'appel d'origine passait "UnboundTLSPort" en 5e:
    // la bulle d'aide affichait le nom d'une variable, et le port ne se grisait
    // jamais.
    $form[]=$tpl->field_checkbox("UnboundTLSEnable","{DNSOTLS_ENABLE}",$S["UnboundTLSEnable"],
        "UnboundTLSCertificate,UnboundTLSPort","{DNSOTLS_EXPLAIN}");
    $form[]=$tpl->field_numeric("UnboundTLSPort","{listen_port}",$S["UnboundTLSPort"]);
    $form[]=$tpl->field_certificate("UnboundTLSCertificate","{certificate}",$S["UnboundTLSCertificate"]);
    $html=array();
    $html[]=$tpl->div_explain("{DNSOTLS}||{DNSOTLS_EXPLAIN}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),"","{apply}",dnsdist_jsafter(),"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function set_events_js():bool{
    $page=CurrentPageName();$tpl=new template_admin();
    return $tpl->js_dialog2("{events}","$page?set-events-popup=yes",700);
}
function set_events_popup(){
    $tpl=new template_admin();
    $S=dnsdist_settings();
    $form=array();
    $form[]=$tpl->field_hidden("dnsdist-save","events");
    $form[]=$tpl->field_checkbox("EnableUnboundLogQueries","{log_queries}",$S["EnableUnboundLogQueries"]);
    $form[]=$tpl->field_checkbox("UnboundLogSyslogDoNotStorelogsLocally","{not_store_log_locally}",$S["UnboundLogSyslogDoNotStorelogsLocally"]);
    $html=array();
    $html[]=$tpl->div_explain("{events}||{log_queries}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),"","{apply}",dnsdist_jsafter(),"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function set_cache_js():bool{
    $page=CurrentPageName();$tpl=new template_admin();
    return $tpl->js_dialog2("{cache}","$page?set-cache-popup=yes",700);
}
function set_cache_popup(){
    $tpl=new template_admin();
    $S=dnsdist_settings();
    $TIMES=dnsdist_times();
    $form=array();
    $form[]=$tpl->field_hidden("dnsdist-save","cache");
    $form[]=$tpl->field_numeric("UnBoundCacheSize","{cache_size} (MB)",$S["UnBoundCacheSize"]);
    $form[]=$tpl->field_array_hash($TIMES,"UnBoundCacheMinTTL","{cache-ttl} (Min)",$S["UnBoundCacheMinTTL"]);
    $form[]=$tpl->field_array_hash($TIMES,"UnBoundCacheMAXTTL","{cache-ttl} (Max)",$S["UnBoundCacheMAXTTL"]);
    $form[]=$tpl->field_array_hash($TIMES,"UnBoundCacheNEGTTL","{negquery-cache-ttl}",$S["UnBoundCacheNEGTTL"]);
    $html=array();
    $html[]=$tpl->div_explain("{cache}||{activate_dns_blacklists_explain}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),"","{apply}",dnsdist_jsafter(),"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}
