<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
// awc_table_style() / awc_pager_js() : inclusion INCONDITIONNELLE (jamais dans le bloc sockets).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
// Verrou de la politique Artica Meta "firewall" : services tenus = catalogue en lecture seule.
include_once(dirname(__FILE__)."/ressources/class.firewall.meta.inc");
// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["service-popup"])){service_settings();exit;}
if(firewall_meta_holds("services")){
    if(isset($_POST["service-save"]) || isset($_POST["delete-rule"])){echo firewall_meta_refuse_post();exit;}
    if(isset($_GET["delete-rule-js"])){firewall_meta_refuse_js();exit;}
    if(isset($_GET["enable-js"])){
        $n=fwsvc_name($_GET["enable-js"]);
        echo "var _sw=document.getElementById('fwsvc-sw-$n');if(_sw){_sw.classList.toggle('on');}\n";
        firewall_meta_refuse_js();exit;
    }
}
if(isset($_POST["service-save"])){service_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-rule"])){delete_confirm();exit;}
if(isset($_GET["enable-js"])){service_enable_js();exit;}
page();

function service_js(){
	$page=CurrentPageName();
	// Meme jeu de caracteres que le daemon (reServiceName) : le nom part dans du JS.
	$service=fwsvc_name($_GET["service-js"] ?? "");
	$service_text=$service;
	$tpl=new template_admin();
	if($service==""){$service_text="{new_service}";}
	$tpl->js_dialog1("{service2}: $service_text","$page?service-popup=$service");
}

function delete_js(){
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	$service=fwsvc_name($_GET["delete-rule-js"] ?? "");
	$tpl->js_confirm_delete("{service2} ".htmlspecialchars($service),"delete-rule",$service,fwsvc_reload_js());
}

function fwsvc_name($v):string{
	return preg_replace('/[^A-Za-z0-9_-]/','',trim((string)$v));
}

// Rafraichit le tableau en gardant le filtre. Le formulaire s'ouvre aussi depuis
// fw.rules.php, ou la fonction n'existe pas : d'ou le typeof.
function fwsvc_reload_js():string{
	return "if(typeof FwServicesReload==='function'){FwServicesReload();}";
}

function delete_confirm(){
	$name=(string)$_POST["delete-rule"];
	$e=fwapi_error(fwapi_send("DELETE","services/".rawurlencode($name)));
	if($e!=""){echo (new template_admin())->post_error($e);}
}

function service_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$service=fwsvc_name($_GET["service-popup"] ?? "");
	
	$ligne=array("enabled"=>1,"server_port"=>"","client_port"=>"");
	if($service<>null){
		$resp=fwapi_get("services/".rawurlencode($service));
		$cur=fwapi_data($resp);
		if(!is_array($cur)){
			// Lecture en echec : pas de formulaire enregistrable, il ecraserait le service.
			echo $tpl->div_error(fwapi_error($resp));
			return;
		}
		$ligne=array("enabled"=>intval($cur["enabled"] ?? 0),
			"server_port"=>(string)($cur["serverPort"] ?? ""),"client_port"=>(string)($cur["clientPort"] ?? ""));
	}

	$explain="{service_firewall_explain}";
	
	// Services tenus par Artica Meta : fiche en lecture seule, pas de creation.
	if(firewall_meta_holds("services")){
		echo firewall_meta_banner("services",$tpl);
		if($service==null){return;}
		firewall_meta_lock_form($tpl);
	}
	if($service<>null){
		$title="{service2} $service";
		$btname="{apply}";
		$tpl->field_hidden("service-save", $service);
	}else{
		$title="{new_service}";
		$btname="{add}";
		$form[]=$tpl->field_text("service-save", "{service_name2}", null,true);
	}

	$tpl->field_hidden("client_port",$ligne["client_port"]);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_textareacode("server_port","{ports}",@implode("\n",explode(" ",$ligne["server_port"])));
	//$form[]=$tpl->field_textareacode("client_port","{local_ports}",@implode("\n",explode(" ",$ligne["client_port"])));
	echo $tpl->form_outside($title,@implode("\n", $form),$explain,$btname,
			fwsvc_reload_js()."dialogInstance1.close();");


}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=$tpl->page_header("{firewall_services}",ico_table,"{firewall_services_explain}",
		"$page?table=yes","firewall-services","progress-firehol-restart",false,"table-loader");
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{firewall_services}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);
}

// Contenu de la page : le bandeau de filtres (tête du tableau) puis l'hôte du
// tableau, rechargé seul pour garder la saisie et le focus.
function table(){
	if(isset($_GET["search"])){
		fwsvc_table();
		return;
	}
	$page=CurrentPageName();
	$tpl=new template_admin();
	$segs=array();
	foreach(fwsvc_protocols() as $k=>$label){
		$cur=$k==""?" on":"";
		$segs[]="<button type='button' class='ak-seg-b fwsvc-seg$cur' data-p='$k' OnClick=\"FwsvcProto('$k')\">$label <span id='fwsvc-n-".($k==""?"all":$k)."'></span></button>";
	}
	$html=array();
	$html[]="<div class='ak ak-tools fwsvc-bar'>";
	$html[]="<span class='fwsvc-lbl'>{filters}:</span><div class='ak-seg'>".@implode("",$segs)."</div>";
	$html[]="<span class='ak-grow'></span>";
	$html[]="<label class='ak-search fwsvc-q'><i class='fas fa-magnifying-glass'></i>";
	$html[]="<input type='text' id='fwsvc-q' placeholder=\"{fwsvc_search_placeholder}\" OnKeyUp='FwsvcSearch()'></label>";
	$html[]="<select id='fwsvc-st' class='ak-sel' OnChange='FwServicesReload()'>";
	$html[]="<option value=''>{fwr_all_statuses}</option><option value='enabled'>{enabled}</option><option value='disabled'>{disabled}</option>";
	$html[]="</select></div>";
	$html[]="<div id='fwsvc-host'></div>";
	$html[]="<div class='ak ak-banner ak-ok fwsvc-tip'><span class='ic'><i class='fas fa-circle-info'></i></span><div>{fwsvc_tip}</div></div>";

	// Une fonction de rechargement : modales et suppressions l'appellent via
	// fwsvc_reload_js(), elle relit les filtres donc les conserve.
	$script="<script>
window.__fwsvcProto='';
function FwServicesReload(){
    var g=function(id){var e=document.getElementById(id);return e?e.value:'';};
    LoadAjaxSilent('fwsvc-host','$page?search='+encodeURIComponent(g('fwsvc-q'))+'&proto='+encodeURIComponent(window.__fwsvcProto)+'&st='+encodeURIComponent(g('fwsvc-st')));
}
function FwsvcProto(p){
    window.__fwsvcProto=p;
    var b=document.querySelectorAll('.fwsvc-seg');
    for(var i=0;i<b.length;i++){b[i].classList.toggle('on',b[i].getAttribute('data-p')===p);}
    FwServicesReload();
}
function FwsvcSearch(){clearTimeout(window.__fwsvcT);window.__fwsvcT=setTimeout(FwServicesReload,350);}
function FwsvcCounts(c){for(var k in c){var e=document.getElementById('fwsvc-n-'+k);if(e){e.textContent='('+c[k]+')';}}}
FwServicesReload();
</script>";
	echo fwsvc_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
}

function fwsvc_protocols():array{
	return array(""=>"{all}","tcp"=>"TCP","udp"=>"UDP","icmp"=>"ICMP","raw"=>"{fwsvc_raw_protocols}");
}

// Familles d'un service d'après ses ports serveur (tcp/80 udp/53 icmp/any 50/any…).
// Un service tcp+udp compte dans les deux ; « raw » = protocole IP nommé ou numéroté.
function fwsvc_families(string $ports):array{
	$fam=array();
	foreach(preg_split('/\s+/',trim($ports)) as $tok){
		if($tok==""){continue;}
		$proto=strtolower(strstr($tok,"/",true));
		if($proto===false || $proto===""){continue;}
		if($proto=="tcp" || $proto=="udp"){$fam[$proto]=true;continue;}
		if(strpos($proto,"icmp")===0){$fam["icmp"]=true;continue;}
		$fam["raw"]=true;
	}
	return $fam;
}

function fwsvc_accents():array{
	$ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
		"accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$ac=brand_ui_accents(brand_palette());
	}
	return $ac;
}

function fwsvc_style():string{
	if(isset($GLOBALS["FWSVC_STYLE"])){return "";}
	$GLOBALS["FWSVC_STYLE"]=true;
	$ac=fwsvc_accents();
	$a=$ac["accent"];$ad=$ac["accent_dark"];$as=$ac["accent_soft"];
	// Seules les regles propres a la page : le reste vient du kit ak- (angular.css.php).
	$c=array();
	$c[]=".fwsvc-skin,.fwsvc-skin *{box-sizing:border-box}";
	$c[]=".fwsvc-bar{margin-top:10px}";
	$c[]=".fwsvc-lbl{color:#94a3b8;font-size:14px;margin-right:6px}";
	$c[]=".fwsvc-q{flex:0 1 340px;min-width:200px}";
	$c[]=".fwsvc-skin .awc-card{border-radius:12px;border-color:#e2e8f0}";
	$c[]=".fwsvc-skin .awc-tbl td{vertical-align:middle;padding-top:14px;padding-bottom:14px}";
	$c[]=".fwsvc-name{font-weight:700;color:#0f172a;font-size:15px}";
	$c[]=".fwsvc-name.off{color:#94a3b8}";
	$c[]=".fwsvc-port{margin:2px 4px 2px 0}.fwsvc-udp{--ak-tfg:#005447;--ak-tbg:#f2f6f6}";
	$c[]=".fwsvc-meta{font-size:13px;color:#94a3b8;margin-top:3px}";
	$c[]=".ak-act.fwsvc-del{color:#ba1a1a}.ak-act.fwsvc-dis{color:#cbd5e1;cursor:not-allowed;background:transparent}";
	$c[]=".fwsvc-tip{align-items:flex-start;margin-top:16px;padding:16px 20px;line-height:1.55;color:#334155}";
	$c[]=".fwsvc-tip .ic{flex:0 0 32px;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff;color:$ad;font-size:15px}";
	return "<style>".@implode("\n",$c)."</style>";
}

function fwsvc_row(array $svc):string{
	$page=CurrentPageName();
	$name=(string)($svc["service"] ?? "");
	$nameHtml=htmlspecialchars($name);
	$nameJs=rawurlencode($name);
	$enabled=intval($svc["enabled"] ?? 0)==1;
	$rules=intval($svc["rules"] ?? 0);
	$edit="Loadjs('$page?service-js=$nameJs')";
	$meta=firewall_meta_holds("services");

	$ports=array();
	foreach(preg_split('/\s+/',trim((string)($svc["serverPort"] ?? ""))) as $p){
		if($p==""){continue;}
		$proto=strtolower((string)strstr($p,"/",true));
		$cls=array("tcp"=>"ak-ok","udp"=>"fwsvc-udp")[$proto] ?? (strpos($proto,"icmp")===0?"ak-info":"");
		$ports[]="<span class='ak-chip fwsvc-port $cls'>".htmlspecialchars($p)."</span>";
	}
	$portsHtml=count($ports)>0?@implode("",$ports):"<span class='awc-none'>-</span>";
	$helper=trim((string)($svc["helper"] ?? ""));

	$use=$rules>0
		?"<span class='ak-pill ak-ok'><span class='ak-dot'></span>$rules ".($rules>1?"{fwsvc_rules}":"{fwsvc_rule}")."</span>"
		:"<span class='ak-pill ak-off'>{fwsvc_unused}</span>";
	// articarest antérieur au compteur : on ne sait pas, on ne prétend pas « inutilisé ».
	if(!array_key_exists("rules",$svc)){$use="<span class='awc-none'>-</span>";}
	// Le daemon refuse de supprimer un service utilisé : on ne propose pas le clic.
	$del=$meta?"":($rules>0
		?"<span class='ak-act fwsvc-del fwsvc-dis' title='{fwsvc_delete_in_use}'><i class='".ico_trash."'></i></span>"
		:"<a class='ak-act ak-danger fwsvc-del' title='{delete}' OnClick=\"Loadjs('$page?delete-rule-js=$nameJs')\"><i class='".ico_trash."'></i></a>");

	$h=array();
	$h[]="<tr>";
	$h[]="<td style='width:1%'>".($meta?"<span class='ak-sw ak-locked".($enabled?" on":"")."'></span>":"<button type='button' class='ak-sw".($enabled?" on":"")."' id='fwsvc-sw-$nameHtml' OnClick=\"this.classList.toggle('on');Loadjs('$page?enable-js=$nameJs')\"></button>")."</td>";
	$h[]="<td><span class='fwsvc-name".($enabled?"":" off")."'>$nameHtml</span>";
	if($helper!=""){$h[]="<div class='fwsvc-meta'>conntrack: ".htmlspecialchars($helper)."</div>";}
	$h[]="</td>";
	$h[]="<td>$portsHtml</td>";
	$h[]="<td>$use</td>";
	$h[]="<td style='text-align:right;white-space:nowrap'><a class='ak-act' title='{fwsvc_edit}' OnClick=\"$edit\"><i class='fas fa-pen-to-square'></i></a>$del</td>";
	$h[]="</tr>";
	return @implode("",$h);
}

function fwsvc_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$search=str_replace("*","",trim((string)($_GET["search"] ?? "")));
	$proto=(string)($_GET["proto"] ?? "");
	if(!array_key_exists($proto,fwsvc_protocols())){$proto="";}
	$st=(string)($_GET["st"] ?? "");

	$resp=fwapi_get("services");
	$list=fwapi_data($resp);
	if(!is_array($list)){
		echo $tpl->_ENGINE_parse_body($tpl->div_error(fwapi_error($resp)));
		return;
	}
	$counts=array("all"=>0,"tcp"=>0,"udp"=>0,"icmp"=>0,"raw"=>0);
	$rows=array();
	foreach($list as $svc){
		if(!is_array($svc)){continue;}
		$fam=fwsvc_families((string)($svc["serverPort"] ?? ""));
		// Les compteurs des familles suivent la recherche et le statut, pas la famille choisie.
		if($search!=""){
			$hay=($svc["service"] ?? "")." ".($svc["serverPort"] ?? "")." ".($svc["helper"] ?? "");
			if(stripos($hay,$search)===false){continue;}
		}
		$on=intval($svc["enabled"] ?? 0)==1;
		if($st=="enabled" && !$on){continue;}
		if($st=="disabled" && $on){continue;}
		$counts["all"]++;
		foreach($fam as $f=>$none){$counts[$f]++;}
		if($proto!="" && !isset($fam[$proto])){continue;}
		$rows[]=fwsvc_row($svc);
	}
	$total=count($list);

	$html=array();
	$metaBanner=firewall_meta_banner("services",$tpl);
	$html[]="<div class='awc-wrap fwsvc-skin'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{status}</th><th>{service_name2}</th><th>{fwsvc_protocol_ports}</th><th>{fwsvc_usage}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='fwsvc-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='5' class='awc-empty'>{no_data}</td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} <strong>".count($rows)."</strong> / <strong>$total</strong> {services}</span>";
	$html[]="<span class='awc-pg' id='fwsvc-pager'></span></div>";
	$html[]="</div></div>";

	$topbuttons=array();
	if(!firewall_meta_holds("services")){$topbuttons[]=array("Loadjs('$page?service-js=')",ico_plus,"{new_service}");}
	$topbuttons[]=array("FwServicesReload()",ico_refresh,"{refresh}");
	$TINY=array();
	$TINY["TITLE"]="{firewall_services}";
	$TINY["ICO"]=ico_table;
	$TINY["EXPL"]="{firewall_services_explain}";
	$TINY["URL"]="firewall-services";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);

	$js=array();
	$js[]="<script>";
	$js[]="NoSpinner();";
	$js[]="if(typeof FwsvcCounts==='function'){FwsvcCounts(".json_encode($counts).");}";
	$js[]="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
	$js[]="</script>";

	// CSS et JS concatenes AUTOUR du parse, jamais dedans.
	echo awc_table_style().fwsvc_style().$metaBanner.$tpl->_ENGINE_parse_body(@implode("\n",$html)).@implode("\n",$js).awc_pager_js(25,"fwsvc-rows","fwsvc-pager");
}

// Bascule activé/désactivé : relit le service (ports, helper) et le réécrit.
function service_enable_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$name=fwsvc_name($_GET["enable-js"] ?? "");
	$revert="var _sw=document.getElementById('fwsvc-sw-$name');if(_sw){_sw.classList.toggle('on');}";
	$cur=fwapi_data(fwapi_get("services/".rawurlencode($name)));
	if(!is_array($cur)){echo $revert;$tpl->js_error("{fw_api_no_answer}");return;}
	$enabled=intval($cur["enabled"] ?? 0)==1?0:1;
	$data=array("service"=>$name,"serverPort"=>(string)($cur["serverPort"] ?? ""),
		"clientPort"=>(string)($cur["clientPort"] ?? ""),"enabled"=>$enabled,"helper"=>(string)($cur["helper"] ?? ""));
	$e=fwapi_error(fwapi_send("POST","services",$data));
	if($e!=""){echo $revert;$tpl->js_error($e);return;}
	admin_tracks("Firewall service $name ".($enabled==1?"enabled":"disabled"));
	echo fwsvc_reload_js();
}
function service_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	// Pas de CleanServiceName : il retire « _ » et « - », si bien que modifier
	// microsoft_ds créait un service microsoftds. Le daemon valide le nom.
	$name=trim((string)$_POST["service-save"]);
	// Le helper conntrack (ftp…) d'un service existant n'est pas dans le
	// formulaire : on le conserve.
	$cur=fwapi_data(fwapi_get("services/".rawurlencode($name)));
	$data=array(
		"service"=>$name,
		"serverPort"=>(string)($_POST["server_port"] ?? ""),
		"clientPort"=>(string)($_POST["client_port"] ?? ""),
		"enabled"=>intval($_POST["enabled"] ?? 0),
		"helper"=>is_array($cur)?(string)($cur["helper"] ?? ""):"",
	);
	// Le daemon normalise les ports (nombre seul → tcp/n) et refuse une forme
	// inconnue au lieu de l'ignorer en silence.
	$e=fwapi_error(fwapi_send("POST","services",$data));
	if($e!=""){echo $tpl->post_error($e);}
}
