<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["disable-all-js"])){disable_all();exit;}
if(isset($_GET["table-list"])){table();exit;}
if(isset($_GET["option-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}
if(isset($_POST["DnsCryptEnableUniqueProvider"])){save_options();exit;}
main();

// Masquer une icone ne protege rien: le repartiteur en tete de fichier repond
// a toute session authentifiee. Les trois ecritures de cette page se gardent
// donc cote serveur.
function dnscp_require_admin($tpl):bool{
    $users=new usersMenus();
    if($users->AsDnsAdministrator){return true;}
    $tpl->js_no_privileges();
    return false;
}

function enable_js(){
    $tpl=new template_admin();
    if(!dnscp_require_admin($tpl)){return;}
	$name=isset($_GET["enable-js"])?(string)$_GET["enable-js"]:"";
	if($name==""){return;}
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	// Le nom du resolveur etait interpole TROIS fois dans "WHERE name='$name'",
	// sans le moindre nettoyage: ?enable-js=' OR '1'='1 basculait toute la
	// table. QUERY_SQL_PARAMS() prepare et lie -- c'est une METHODE distincte,
	// pas un second argument de QUERY_SQL().
	// La bascule se fait en UNE instruction: le couple SELECT puis UPDATE
	// laissait deux clics rapides se croiser.
	$q->QUERY_SQL_PARAMS("UPDATE DnsCryptResolvers SET `Enabled`=CASE WHEN `Enabled`=1 THEN 0 ELSE 1 END WHERE Name=?",array($name));
	if (!$q->ok) {
		$tpl->js_mysql_alert($q->mysql_error);
	}
}

function disable_all(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!dnscp_require_admin($tpl)){return;}
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $q->QUERY_SQL("UPDATE DnsCryptResolvers SET Enabled=0");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);
        return;
    }
    echo "LoadAjax('dnscrypt-proxy-list','$page?table-list=yes');";
}

function options_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{options}","$page?options-popup=yes");
}

function options_popup(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $sock=new sockets();
    $DnsCryptEnableUniqueProvider=intval($sock->GET_INFO("DnsCryptEnableUniqueProvider"));
    $DnsCryptQueryLogging=intval($sock->GET_INFO("DnsCryptQueryLogging"));
    $DnsCryptProvider=trim($sock->GET_INFO("DnsCryptProvider"));
    $providers=array();
    $sql="SELECT *  FROM DnsCryptResolvers ORDER by name";
    $results = $q->QUERY_SQL($sql);

    if(!is_array($results)){$results=array();}
    foreach ($results as $ligne){
        // La colonne FullName N'EXISTE PAS: DnsCryptResolvers ne porte que
        // Name, proto, Description, DNSSECValidation, NoLogs, NoFilter, zOrder,
        // ResolverAddress, ProviderPublicKey, Enabled (exec.DnsCryptProxy.php).
        // Chaque libelle de la liste deroulante etait donc VIDE.
        $name=isset($ligne["Name"])?(string)$ligne["Name"]:"";
        if($name==""){continue;}
        $providers[$name]=$name;
    }

    $html[]=$tpl->field_section("{unique_provider}","{DnsCryptEnableUniqueProvider}");
    $html[]=$tpl->field_checkbox("DnsCryptEnableUniqueProvider","{unique_provider}",$DnsCryptEnableUniqueProvider,false);
    $html[]=$tpl->field_array_hash($providers,"DnsCryptProvider","{providers}",$DnsCryptProvider);
    $html[]=$tpl->field_section("{events}","");
    $html[]=$tpl->field_checkbox("DnsCryptQueryLogging","{log_cqueries}",$DnsCryptQueryLogging,false);




    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
    $ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
    $ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";

    echo $tpl->form_outside("{options}",$html,null,"{apply}","BootstrapDialog1.close();$jsrestart","AsDnsAdministrator");
}

/**
 *
 */
function save_options(){
    $tpl=new template_admin();
    if(!dnscp_require_admin($tpl)){return;}
    // SAVE_POSTs() sans argument ecrit TOUTE cle postee dans les reglages: le
    // formulaire n'en compte que trois.
    $KEYS=array("DnsCryptEnableUniqueProvider","DnsCryptProvider","DnsCryptQueryLogging");
    foreach($KEYS as $k){
        if(!isset($_POST[$k])){continue;}
        if(is_array($_POST[$k])){continue;}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($k,(string)$_POST[$k]);
    }
    admin_tracks("DNSCrypt-proxy: saved options");
}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
	$ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
	$ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.update.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.update.progress.log";
    $ARRAY["CMD"]="dnscrypt-proxy.php?update=yes";
    $ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {update2}";
    $ARRAY["AFTER"]="LoadAjax('dnscrypt-proxy-list','$page?table-list=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsupdate="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";


	$html[]="
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:15px'>
			<label class='btn btn btn-primary' OnClick=\"Loadjs('$page?disable-all-js=yes');\"><i class='fas fa-comment-minus'></i> {disable_all} </label>
			<label class='btn btn btn-info' OnClick=\"$jsupdate\"><i class='fas fa-download'></i> {update2} </label>
			<label class='btn btn btn-primary' OnClick=\"Loadjs('$page?option-js=yes');\"><i class='fas fa-wrench'></i> {options} </label>
			<label class='btn btn btn-info' OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {reconfigure_service} </label>
			</div>";


    $html[]="<div id='dnscrypt-proxy-list'></div>";
    $html[]="<script>LoadAjax('dnscrypt-proxy-list','$page?table-list=yes');</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

// --- Cellules du tableau: une cellule = une fonction ------------------------
// Les libelles viennent de la liste publique de resolveurs telechargee par
// dnscrypt-proxy.php?update=yes: c'est de la donnee tierce, elle est echappee.
function dnscp_cell_enable($tpl,$enabled,$js):string{
    return "<td class='center' style='width:1%;vertical-align:middle'>"
        .$tpl->icon_check($enabled,$js,null,"AsDnsAdministrator")."</td>";
}
function dnscp_cell_name($name,$proto):string{
    $h="<td nowrap style='width:1%'><span class='awc-name'>".htmlspecialchars((string)$name)."</span>";
    if(strlen((string)$proto)>0){
        $h.="<div class='awc-meta'>".htmlspecialchars((string)$proto)."</div>";
    }
    return $h."</td>";
}
// Les trois criteres qui font choisir un resolveur -- validation DNSSEC,
// absence de journalisation, absence de filtrage -- etaient stockes en base et
// n'etaient affiches nulle part.
function dnscp_badges($ligne):string{
    $b=array();
    if(intval($ligne["DNSSECValidation"] ?? 0)==1){$b[]="<span class='awc-badge awc-badge-on'>DNSSEC</span>";}
    if(intval($ligne["NoLogs"] ?? 0)==1){$b[]="<span class='awc-badge awc-badge-on'>{no_logs}</span>";}
    if(intval($ligne["NoFilter"] ?? 0)==1){$b[]="<span class='awc-tag'>{no_filter}</span>";}
    if(count($b)==0){return "";}
    return "<div style='margin-top:4px'>".@implode("&nbsp;",$b)."</div>";
}
function dnscp_cell_desc($description,$address,$badges):string{
    // Le <small> ouvert ici n'a jamais ete ferme.
    $h="<td><span class='awc-meta'>".htmlspecialchars((string)$description)."</span>";
    if(strlen((string)$address)>0){
        $h.="<div class='awc-mono'>".htmlspecialchars((string)$address)."</div>";
    }
    $h.=$badges;
    return $h."</td>";
}

function table(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");

    $results = $q->QUERY_SQL("SELECT *  FROM DnsCryptResolvers ORDER by Name");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
    if(!is_array($results)){$results=array();}

    $rows=array();
    foreach ($results as $ligne){
        $md=md5(serialize($ligne));
        $name=isset($ligne["Name"])?(string)$ligne["Name"]:"";
        // $Location et $url etaient lues sur des colonnes qui n'existent pas,
        // et n'etaient de toute facon jamais affichees.
        $rows[]="<tr id='$md'>"
            .dnscp_cell_enable($tpl,$ligne["Enabled"] ?? 0,"Loadjs('$page?enable-js=".rawurlencode($name)."&id=$md')")
            .dnscp_cell_name($name,$ligne["proto"] ?? "")
            .dnscp_cell_desc($ligne["Description"] ?? "",$ligne["ResolverAddress"] ?? "",dnscp_badges($ligne))
            ."</tr>";
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th class='center' nowrap>{enable}</th>";
    $html[]="<th nowrap>{name}</th>";
    $html[]="<th>{description}</th>";
    $html[]="</tr></thead><tbody id='dnscp-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    // Le <tfoot> ne portait qu'un <ul class='pagination'> jamais alimente:
    // l'init footable ciblait $('#table-<time>'), un identifiant qu'AUCUN
    // element de la page ne portait -- ni pagination, ni tri, ni filtrage.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='dnscp-pager'></span>";
    $html[]="</div></div></div>";

    // ICON_SCRIPTS_COMPILE() et non $tpl->ICON_SCRIPTS: icon_check() alimente
    // en parallele $GLOBALS["TPL_ICON_SCRIPTS"], et seule la version COMPILE
    // draine les deux piles. Le script reste hors de _ENGINE_parse_body().
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"dnscp-rows","dnscp-pager");
}
