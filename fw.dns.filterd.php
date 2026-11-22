<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["UfdbListenInterface"])){Save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
// (les dispatchs ?ufdbdebug-popup= et ?ufdbdebug= appelaient ufdbdebug_popup()
// et ufdbdebug_js(), absentes de ce fichier ET de ses includes -- elles vivent
// dans d'autres pages, donc dans d'autres requetes. Toute requete les portant
// mourait sur un "Call to undefined function". Aucun appelant ici.)


page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$APP_DNSFILTERD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSFILTERD_VERSION");


	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_DNSFILTERD} $APP_DNSFILTERD_VERSION </h1>
			<p>{APP_DNSFILTERD_EXPLAIN}</p>
		</div>
	</div>
	<div class='row'>
		<div id='progress-dnsfilter-restart'></div>
		<div class='ibox-content' style='min-height:600px'>
			<div id='table-dnsfilterd'></div>
		</div>
	</div>



	<script>
	LoadAjax('table-dnsfilterd','$page?tabs=yes');
	$.address.state('/');
	$.address.value('/dnsfilter');
	$.address.title('Artica: DNS Filter Parameters');
	</script>";

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: DNS Filter Parameters",$html);
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$HideCorporateFeatures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCorporateFeatures"));
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$array["{service_status}"]="$page?table=yes";
	$array["{rules}"]="fw.dns.filterd.rules.php";
	$array["{whitelists}"]="fw.dns.filterd.whitelists.php";
	$array["{blacklists}"]="fw.dns.filterd.blacklists.php";
	$array["{events}"]="fw.dns.filterd.events.php";
	echo $tpl->tabs_default($array);
}


function table(){
	
	$f=explode("\n",@file_get_contents("/etc/dnsfilterd/dnsfilterd.conf"));
	$CountOfPattern=0;
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#domainlist\s+\"#", $line)){$CountOfPattern++;continue;}
	}
	
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/dnsfilterd.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/dnsfilterd.restart.log";
	$ARRAY["CMD"]="dnsfilterd.php?restart=yes";
	$ARRAY["TITLE"]="{restarting}";
	$ARRAY["AFTER"]="LoadAjax('table-dnsfilterd','$page?tabs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$dnsfilterd_restart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-dnsfilter-restart')";
	
	
	$mem=new lib_memcached();
	$UFDB_CONNECTS=intval($mem->getKey("UFDB_CONNECTS"));
	$DNSFILTER_HITS=intval($mem->getKey("DNSFILTER_HITS"));
	$UFDB_CONNECTS=$UFDB_CONNECTS+$DNSFILTER_HITS;
	
	$UFDB_CONNECTS=FormatNumber($UFDB_CONNECTS);
	$DNSFILTER_HITS=FormatNumber($DNSFILTER_HITS);
	
	
	$EnableDNSFilterdRest=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterdRest"));


	$dnsfiltersocks=new dnsfiltersocks();
	$UfdbListenPort=intval($dnsfiltersocks->GET_INFO("UfdbListenPort"));
	$UfdbListenInterface=trim($dnsfiltersocks->GET_INFO("UfdbListenInterface"));
	$DefaultIpRedirection=$dnsfiltersocks->GET_INFO("DefaultIpRedirection");
	$dns_neg_ttl=intval($dnsfiltersocks->GET_INFO("dns_neg_ttl"));
	if($dns_neg_ttl==0){$dns_neg_ttl=3600;}
	if($DefaultIpRedirection==null){$DefaultIpRedirection="127.0.0.1";}
	$UfdbgclientSockTimeOut=intval($dnsfiltersocks->GET_INFO("UfdbgclientSockTimeOut"));
	if($UfdbgclientSockTimeOut==0){$UfdbgclientSockTimeOut=2;}
	$SquidGuardClientEnableMemory=intval($dnsfiltersocks->GET_INFO("SquidGuardClientEnableMemory"));
	$SquidGuardClientMaxMemorySeconds=intval($dnsfiltersocks->GET_INFO("SquidGuardClientMaxMemorySeconds"));
	$DebugFilter=intval($dnsfiltersocks->GET_INFO("DebugFilter"));
	$Threads=intval($dnsfiltersocks->GET_INFO("UfdbGuardThreads"));
	if($Threads==0){$Threads=64;}
	if($Threads>140){$Threads=140;}
	if($SquidGuardClientMaxMemorySeconds==0){$SquidGuardClientMaxMemorySeconds=300;}
	
	if($UfdbListenPort==0){$UfdbListenPort=3979;}
	if($UfdbListenInterface==null){$UfdbListenInterface="lo";}
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$sock->getFrameWork("dnsfilterd.php?status=yes");
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/dnsfilterd.status");
	
	if($CountOfPattern>0){
		$CountOfPattern_text=$tpl->widget_h("green",ico_database,$CountOfPattern,"{loaded_databases}");
	}
	if($CountOfPattern==0){
		$CountOfPattern_text=$tpl->widget_h("green","fas fa-engine-warning",0,"{no_database_selected}");
	}
	
	// La colonne de gauche est assemblee A PART et n'est PAS reanalysee.
	//
	// SERVICE_STATUS() renvoie deja du contenu analyse, precede de sa feuille de
	// style et suivi de son <script>; widget_h() et widget_style1() renvoient
	// svctile_css() suivi de leur balisage. Tout cela partait dans $html, donc
	// dans _ENGINE_parse_body(), dont la regex #{(.+?)}# lit chaque corps de
	// regle CSS comme un {token} et declenche une recherche memcached par regle
	// -- une collision corrompant la feuille entiere. Le premier appel de la
	// serie est widget_h() (plus haut): c'est lui qui emet le <style>, la garde
	// $GLOBALS["SVCTILE_CSS_DONE"] faisant taire les suivants.
	$left =$CountOfPattern_text;
	$left.=$tpl->SERVICE_STATUS($ini, "APP_DNSFILTERD",$dnsfilterd_restart);
	$left.=$tpl->SERVICE_STATUS($ini, "APP_DNSFILTERD_TAIL",null);
	$left.=$tpl->widget_style1("navy-bg","fas fa-filter","{requests}",$UFDB_CONNECTS);
	$left.=$tpl->widget_style1("lazur-bg","fas fa-filter","{cached_requests}",$DNSFILTER_HITS);

	// Le <table> imbrique de la colonne de gauche n'avait qu'une ligne et une
	// cellule: il n'apportait aucune mise en page.
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px;vertical-align:top'>";
	$html[]="<div class=\"ibox\" style='border-top:0px'><div class=\"ibox-content\" style='border-top:0px'>";
	$html[]="%%LEFTCOL%%";
	$html[]="</div></div>";
	$html[]="</td>";
	$html[]="<td style='vertical-align:top'>";


	$sock=new sockets();
	if($sock->CORP_LICENSE()){
	    if($EnableDNSFilterdRest==1){
            $DNSFilterRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFilterRESTFulAPIKey"));
            $form[]=$tpl->field_text("DNSFilterRESTFulAPIKey", "REST {API_KEY}", $DNSFilterRESTFulAPIKey);

        }
    }

	$form[]=$tpl->field_interfaces("UfdbListenInterface","{interface}",$UfdbListenInterface);
	$form[]=$tpl->field_numeric("UfdbListenPort","{listen_port}",$UfdbListenPort);
	$form[]=$tpl->field_numeric("UfdbGuardThreads","Threads",$Threads);
	
	$form[]=$tpl->field_checkbox("DebugFilter","{debug_mode}",$DebugFilter);
	$form[]=$tpl->field_checkbox("SquidGuardClientEnableMemory","{cache_results}",$SquidGuardClientEnableMemory,false,"");
	$form[]=$tpl->field_numeric("SquidGuardClientMaxMemorySeconds","{cache_time} ({seconds})",$SquidGuardClientMaxMemorySeconds);
	
	
	$form[]=$tpl->field_numeric("UfdbgclientSockTimeOut","{socket_timeout} {seconds}",$UfdbgclientSockTimeOut);
	$form[]=$tpl->field_ipaddr("DefaultIpRedirection","{ipaddr_on_blocked_domains}",$DefaultIpRedirection);
	$form[]=$tpl->field_numeric("dns_neg_ttl", "{negative_dns_ttl} ({seconds})", $dns_neg_ttl);
	
	
	
	$html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",$dnsfilterd_restart,"AsDnsAdministrator");
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	
	// Le marqueur est remplace APRES l'analyse: la colonne de gauche traverse
	// ainsi le rendu sans que sa feuille de style ni ses scripts soient relus
	// comme des {tokens}.
	echo str_replace("%%LEFTCOL%%",$left,$tpl->_ENGINE_parse_body(@implode("\n",$html)));
}

/**
 * Les SEULES cles que Save() accepte d'ecrire.
 *
 * La boucle d'origine parcourait TOUT $_POST: n'importe quel champ poste
 * devenait un reglage du filtre DNS. Une liste blanche explicite est la seule
 * facon de borner ce que ce formulaire peut toucher.
 */
function dnsfilterd_savable(){
	return array(
		"UfdbListenInterface"=>true,
		"UfdbListenPort"=>true,
		"UfdbGuardThreads"=>true,
		"DebugFilter"=>true,
		"SquidGuardClientEnableMemory"=>true,
		"SquidGuardClientMaxMemorySeconds"=>true,
		"UfdbgclientSockTimeOut"=>true,
		"DefaultIpRedirection"=>true,
		"dns_neg_ttl"=>true,
	);
}

function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	// Cette cle vit dans Valkey, pas dans la configuration du filtre.
	if(isset($_POST["DNSFilterRESTFulAPIKey"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSFilterRESTFulAPIKey",$_POST["DNSFilterRESTFulAPIKey"]);
        unset($_POST["DNSFilterRESTFulAPIKey"]);
    }

	$allowed=dnsfilterd_savable();
	$dnsfiltersocks=new dnsfiltersocks();
	$written=array();
	$failed="";
	foreach ($_POST as $key=>$value){
		if(!isset($allowed[$key])){continue;}
		if(!$dnsfiltersocks->SET_INFO($key, $value)){
			// L'ancien code faisait un "return" nu au premier echec: la sauvegarde
			// s'arretait a mi-chemin ET repondait une page vide, que form_outside
			// interprete comme un SUCCES. L'operateur voyait la modale se fermer
			// sur un enregistrement partiel.
			$failed=$key;
			break;
		}
		$written[]="$key=$value";
	}

	if($failed!==""){
		echo $tpl->post_error("{failed}: $failed");
		return;
	}
	if(count($written)>0){
		admin_tracks("DNS filtering settings: ".@implode(", ",$written));
	}
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
