<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style(): pagination partagee des tableaux de la console.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Verrou de la politique Artica Meta "ClamAV" : exclusions Meta en lecture seule.
include_once(dirname(__FILE__)."/ressources/class.clamav.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_GET["enable-rule-js"])){rule_enable_js();exit;}
if(isset($_POST["delete"])){rule_delete();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["popup"])){popup();exit;}
page();

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-js"]);
	$title="{new_item}";

	if($ID>0){
		$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/whitelist/$ID"),true);
		if(is_array($json) && isset($json["data"]["VirusName"])){$title=$json["data"]["VirusName"];}
	}

	$tpl->js_dialog1($title, "$page?popup=$ID");

}

function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["delete-rule-js"]);
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/whitelist/$ID"),true);
	if(is_array($json) && clamav_meta_row($json["data"] ?? null)){clamav_meta_refuse_js();return;}
	$md5=$_GET["md"];
	$title=isset($json["data"]["VirusName"])?$json["data"]["VirusName"]:"";
	$page=CurrentPageName();
	$tpl->js_confirm_delete($title, "delete", $ID,"LoadAjaxSilent('table-clamavw','$page?table=yes');");
}
function rule_delete(){
	$ID=intval($_POST["delete"]);
	$json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/clamd/whitelist/$ID"),true);
	// exclusion posee par Artica Meta : le demon refuse (423), le message est rendu a l'appelant
	if(is_array($json) && isset($json["Error"]) && $json["Error"]<>""){echo ($json["Error"]==="managed by Artica Meta")?clamav_meta_text("{clamav_meta_row_locked}"):(string)$json["Error"];}
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["popup"]);
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/whitelist/$ID"),true);
	$ligne=(is_array($json) && isset($json["data"]) && is_array($json["data"]))?$json["data"]:array("VirusName"=>"");
	if($ID>0 && clamav_meta_row($ligne)){
		// exclusion posee par Artica Meta : fiche en lecture seule, aucune ecriture
		$html=array();
		$html[]=clamav_meta_banner($tpl,true,"{clamav_meta_row_locked}");
		$html[]="<div style='font-size:15px;padding:10px'><strong>".htmlspecialchars((string)$ligne["VirusName"])."</strong>".clamav_meta_badge($ligne)."</div>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	$btn="{add}";
	$jsafter="dialogInstance1.close();LoadAjax('table-clamavw','$page?table=yes');";
	if($ID>0){
		$btn="{apply}";
		$jsafter="dialogInstance1.close();LoadAjax('table-clamavw','$page?table=yes');";
	}
	
	$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_text("VirusName", "{virusname}", $ligne["VirusName"]);
	// En-tete concret au-dessus du formulaire (div_explain), plus de titre "whitelist:" tronque.
	$html=array();
	$html[]=$tpl->div_explain("{clwl_new_title}||{clwl_new_explain}");
	$html[]=$tpl->form_outside("", $form,"",$btn,$jsafter,"AsOrgAdmin");
	echo $tpl->_ENGINE_parse_body($html);
	
}
function rule_enable_js():bool{
	$ID=intval($_GET["enable-rule-js"]);
	$row=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/whitelist/$ID"),true);
	if(is_array($row) && clamav_meta_row($row["data"] ?? null)){return clamav_meta_refuse_js();}
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/whitelist/$ID/toggle"),true);
	if(is_array($json) && isset($json["Error"]) && $json["Error"]<>""){
		$tpl=new template_admin();
		$tpl->js_error($json["Error"]);
		return true;
	}
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "LoadAjaxSilent('table-clamavw','$page?table=yes');";
	return true;
}

function rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="Manager";}
	$_POST["uid"]=$uid;
	$ID=intval($_POST["ID"] ?? 0);
	if($ID>0){
		$row=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/whitelist/$ID"),true);
		if(is_array($row) && clamav_meta_row($row["data"] ?? null)){clamav_meta_refuse_post("{clamav_meta_row_locked}");return;}
	}
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/clamd/whitelist", $_POST),true);
	if(!is_array($json)){echo $tpl->post_error("{error}: /clamd/whitelist");return;}
	if(isset($json["Error"]) && $json["Error"]<>""){echo $tpl->post_error($json["Error"]);}
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ClamAVDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");

    $html=$tpl->page_header("{signature_exclusions}",
        "fas fa-thumbs-up","{APP_CLAMAV} $ClamAVDaemonVersion<br>{APP_CLAMAV_WHITE_TEXT}","$page?table=yes","clamav-white",
        "progress-clamavw-restart",false,"table-clamavw");



	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: ClamAV Whitelist",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	// Vue « exclusions de signatures » (maquette stitch_proxy_antivirus_exclusions_name).
	// Schema owned by the daemon (ClamTools.PatchTables); antivirus.db is a pooled DB,
	// never opened directly by PHP.
	$add="Loadjs('$page?rule-js=0',true);";
	$jsrestart=$tpl->framework_buildjs("/clamd/reconfigure","clamd.progress","clamd.progress.logs","progress-clamavw-restart");

	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/whitelist"),true);
	$apiError="";
	if(!is_array($json)){$apiError="{error}: /clamd/whitelist";}
	elseif(isset($json["Status"]) && !$json["Status"]){$apiError=(string)($json["Error"] ?? "{error}");}
	$results=(is_array($json) && isset($json["data"]) && is_array($json["data"]))?$json["data"]:array();
	$metaRows=false;
	foreach($results as $r){if(clamav_meta_row($r)){$metaRows=true;break;}}

	// Couverture: total des signatures chargees, lu sur la meme source que l'onglet des bases.
	$coverage=0;
	$pj=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/pattern-status"),true);
	if(is_array($pj) && isset($pj["data"]) && is_array($pj["data"])){
		foreach($pj["data"] as $r){$coverage+=intval($r["signatures"] ?? 0);}
	}

	$total=count($results); $active=0; $third=0; $official=0; $disabled=0; $last=null;
	foreach($results as $r){
		$on=intval($r["enabled"] ?? 0)==1;
		if($on){$active++;}else{$disabled++;}
		if(clwl_is_third((string)($r["VirusName"] ?? ""))){$third++;}else{$official++;}
		if($last===null || strcmp((string)($r["zdate"] ?? ""),(string)($last["zdate"] ?? ""))>0){$last=$r;}
	}
	usort($results,function($a,$b){ return strcmp((string)($b["zdate"] ?? ""),(string)($a["zdate"] ?? "")); });
	$pct=$total>0?intval(round($active*100/$total)):0;

	$h=array();
	$h[]="<div class='clwl ak'>";
	if($apiError!=""){$h[]=$tpl->div_error($apiError);}
	if(clamav_meta_whitelist_held() || $metaRows){$h[]=clamav_meta_banner($tpl,$metaRows,"{clamav_meta_whitelist_explain}");}

	// Indicateurs
	$lastTxt="-"; $lastBy="";
	if($last!==null){
		$ts=strtotime((string)($last["zdate"] ?? ""));
		$lastTxt=$ts!==false?htmlspecialchars(distanceOfTimeInWords($ts,time())):htmlspecialchars((string)$last["zdate"]);
		$lastBy="<span class='ak-dot ak-ok clwl-dot'></span>{by} <span>".htmlspecialchars(clwl_uid($last))."</span>";
	}
	$h[]="<div class='ak-grid-4'>";
	$h[]="<div class='ak-fig clwl-kpi'><div class='clwl-kpi-top'><span class='ak-fig-l'>{clwl_active}</span><span class='ak-ico ak-ico-sm ak-ok'><i class='fas fa-circle-check'></i></span></div>"
		."<div class='clwl-kpi-val'><span class='clwl-kpi-num'>$active</span><span class='clwl-kpi-unit'>/ $total {clwl_configured}</span></div>"
		."<div class='ak-bar'><span style='width:$pct%'></span></div>"
		."<div class='clwl-kpi-sub'><span>$pct % {clwl_applied}</span><span class='clwl-teal'>whitelist.ign2</span></div></div>";
	$h[]="<div class='ak-fig clwl-kpi'><div class='clwl-kpi-top'><span class='ak-fig-l'>{clwl_third_rules}</span><span class='ak-ico ak-ico-sm ak-off'><i class='fas fa-puzzle-piece'></i></span></div>"
		."<div class='clwl-kpi-val'><span class='clwl-kpi-num'>$third</span><span class='clwl-kpi-unit'>{clwl_third_unit}</span></div>"
		."<div class='clwl-kpi-sub'><span>{clwl_third_sub}</span></div></div>";
	$h[]="<div class='ak-fig clwl-kpi'><div class='clwl-kpi-top'><span class='ak-fig-l'>{clwl_last_change}</span><span class='ak-ico ak-ico-sm ak-off'><i class='fas fa-clock'></i></span></div>"
		."<div class='clwl-kpi-val'><span class='clwl-kpi-mid'>$lastTxt</span></div>"
		."<div class='clwl-kpi-sub'><span>$lastBy</span></div></div>";
	$h[]="<div class='ak-fig clwl-kpi clwl-kpi-dark'><div class='clwl-kpi-top'><span class='ak-fig-l'>{clwl_coverage}</span><span class='ak-ico ak-ico-sm ak-ok'><i class='fas fa-shield-halved'></i></span></div>"
		."<div class='clwl-kpi-num'>".($coverage>0?number_format($coverage,0,","," "):"-")."</div>"
		."<div class='clwl-kpi-sub'><span><i class='fas fa-check'></i> {clwl_coverage_sub}</span></div></div>";
	$h[]="</div>";

	// Barre de filtres
	$chips=array(array("all","{clwl_all}",$total),array("official","{clwl_official}",$official),
		array("third","{clwl_third}",$third),array("off","{clwl_disabled}",$disabled));
	$h[]="<div class='ak-tools clwl-toolbar'><div class='ak-seg clwl-chips'>";
	foreach($chips as $c){
		$on=$c[0]=="all"?" on":"";
		$h[]="<a href=\"javascript:blur();\" class='ak-seg-b clwl-chip$on' data-f='{$c[0]}'>{$c[1]} <span class='clwl-chip-n'>{$c[2]}</span></a>";
	}
	$h[]="</div><div class='ak-search clwl-search'><i class='fas fa-magnifying-glass'></i><input type='text' id='clwl-q' placeholder=\"{clwl_search_ph}\"></div></div>";

	// Tableau
	$h[]="<div class='ak-card'><div class='clwl-scroll'><table class='ak-tbl clwl-tbl'><thead><tr>";
	$h[]="<th>{clwl_col_signature}</th><th>{clwl_col_origin}</th><th>{clwl_col_added}</th><th>{clwl_col_status}</th><th class='ak-end'>{action}</th>";
	$h[]="</tr></thead><tbody id='clwl-rows'>";
	foreach($results as $r){ $h[]=clwl_row($tpl,$page,$r); }
	$h[]="</tbody></table></div>";
	if($total==0){
		$h[]="<div class='clwl-empty'><div class='clwl-empty-ico'><i class='fas fa-shield-halved'></i></div>"
			."<div class='clwl-empty-t'>{clwl_empty_title}</div><div class='clwl-empty-p'>{clwl_empty_text}</div>"
			."<a class='ak-btn ak-btn-primary' href=\"javascript:blur();\" OnClick=\"$add\"><i class='fas fa-plus'></i> {clwl_add_first}</a></div>";
	}else{
		$h[]="<div class='ak-card-f'><span>{showing} <b id='clwl-shown'>$total</b> / $total {clwl_exclusions}</span><span class='awc-pg' id='clwl-pager'></span></div>";
	}
	$h[]="</div>";

	// Bonne pratique
	$h[]="<div class='ak-card ak-card-pad'><div class='clwl-help-h'><i class='fas fa-circle-info'></i> {clwl_help_title}</div><div class='clwl-help-grid'>";
	$h[]="<div class='clwl-side clwl-side-on'><div class='clwl-side-h'><span><i class='fas fa-circle-check'></i> {clwl_help_sig}</span><span class='ak-tag ak-ok'>{recommended}</span></div>"
		."<div class='clwl-side-t'>{clwl_help_sig_t}</div><p>{clwl_help_sig_p}</p><div class='clwl-side-f'><i class='fas fa-check'></i> {clwl_help_sig_f}</div></div>";
	$h[]="<div class='clwl-side'><div class='clwl-side-h'><span class='clwl-amber'><i class='fas fa-circle-exclamation'></i> {clwl_help_site}</span><span class='ak-tag ak-warn clwl-tag-warn'>{clwl_restricted}</span></div>"
		."<div class='clwl-side-t'>{clwl_help_site_t}</div><p>{clwl_help_site_p}</p><div class='clwl-side-f'><span>{clwl_help_site_f}</span>"
		."<a class='clwl-link' href='/ecapav'>{clwl_ecap_exclusions} <i class='fas fa-chevron-right'></i></a></div></div>";
	$h[]="</div></div>";
	$h[]="</div>";

	// Boutons d'en-tete: TINY (fw.progress.php), comme les autres pages de la console.
	$ClamAVDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");
	$topbuttons=array();
	$topbuttons[]=array($add, ico_plus, "{new_item}");
	$topbuttons[]=array($jsrestart, ico_save, "{apply_rules}");
	$topbuttons[]=array("LoadAjax('table-clamavw','$page?table=yes');", ico_refresh, "{refresh}");
	$TINY_ARRAY=array();
	$TINY_ARRAY["TITLE"]="{signature_exclusions}";
	$TINY_ARRAY["ICO"]="fas fa-thumbs-up";
	$TINY_ARRAY["EXPL"]="{APP_CLAMAV} $ClamAVDaemonVersion<br>{APP_CLAMAV_WHITE_TEXT}";
	$TINY_ARRAY["URL"]="clamav-white";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// CSS et JS hors de _ENGINE_parse_body: leurs blocs {...} y seraient lus comme des jetons.
	echo clwl_style().$tpl->_ENGINE_parse_body(@implode("\n",$h))
		."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny</script>"
		.clwl_filter_js(15);
}

// Signature d'une base tierce: ClamAV suffixe .UNOFFICIAL tout ce qui ne vient pas de ses bases officielles.
function clwl_is_third(string $name):bool{
	return preg_match('#\.UNOFFICIAL$#i',trim($name))==1;
}

function clwl_uid(array $r):string{
	$uid=trim((string)($r["uid"] ?? ""));
	if($uid=="" || $uid=="-100"){return "Manager";}
	return $uid;
}

function clwl_row($tpl,string $page,array $r):string{
	$ID=intval($r["ID"] ?? 0);
	$name=(string)($r["VirusName"] ?? "");
	$on=intval($r["enabled"] ?? 0)==1;
	$third=clwl_is_third($name);
	$js="Loadjs('$page?rule-js=$ID',true);";
	$ts=strtotime((string)($r["zdate"] ?? ""));
	$date=$ts!==false?htmlspecialchars($tpl->time_to_date($ts,true)):htmlspecialchars((string)($r["zdate"] ?? ""));
	$badge=$third?"<span class='ak-tag ak-off clwl-badge'>{clwl_third}</span>":"<span class='ak-tag ak-ok clwl-badge'>{clwl_official}</span>";
	$kind=$third?"third":"official";
	$off=$on?"":" clwl-off";
	$state=$on?"<span class='ak-state ak-ok'>{active2}</span>":"<span class='ak-state ak-off'>{inactive2}</span>";
	if(clamav_meta_row($r)){
		// exclusion posee par Artica Meta : ni bascule, ni suppression ; le nom ouvre la fiche en lecture seule
		$check="<span title=\"{clamav_meta_row_locked}\"><i class='fa-solid fa-lock'></i></span>";
		return "<tr class='clwl-row$off' data-kind='$kind' data-on='".($on?1:0)."'>"
			."<td><div class='clwl-ident'><span class='ak-ico ak-ico-sq clwl-ico'><i class='fas fa-ban'></i></span>"
			."<a class='clwl-name' href=\"javascript:blur();\" OnClick=\"$js\">".htmlspecialchars($name)."</a>".clamav_meta_badge($r)."</div></td>"
			."<td>$badge</td>"
			."<td><div class='clwl-date'>$date</div><div class='clwl-by'>{by} <span>Artica Meta</span></div></td>"
			."<td><div class='clwl-status'>$check$state</div></td>"
			."<td style='text-align:right'></td>"
			."</tr>";
	}
	return "<tr class='clwl-row$off' data-kind='$kind' data-on='".($on?1:0)."'>"
		."<td><div class='clwl-ident'><span class='ak-ico ak-ico-sq clwl-ico'><i class='fas fa-ban'></i></span>"
		."<a class='clwl-name' href=\"javascript:blur();\" OnClick=\"$js\">".htmlspecialchars($name)."</a></div></td>"
		."<td>$badge</td>"
		."<td><div class='clwl-date'>$date</div><div class='clwl-by'>{by} <span>".htmlspecialchars(clwl_uid($r))."</span></div></td>"
		."<td><div class='clwl-status'>".$tpl->icon_check($on?1:0,"Loadjs('$page?enable-rule-js=$ID')",null,"AsOrgAdmin")."$state</div></td>"
		."<td style='text-align:right'><a class='ak-act ak-danger' title=\"{delete}\" href=\"javascript:blur();\" OnClick=\"Loadjs('$page?delete-rule-js=$ID')\"><i class='fas fa-trash-can'></i></a></td>"
		."</tr>";
}

// Filtre par pastille + recherche + pagination sur les lignes visibles.
function clwl_filter_js(int $size):string{
	$js='
<script>
(function(){
 var tb=document.getElementById("clwl-rows"); if(!tb){return;}
 var rows=tb.rows, box=document.getElementById("clwl-pager"), shown=document.getElementById("clwl-shown");
 var q=document.getElementById("clwl-q"), f="all", cur=1, size=__SIZE__;
 function match(tr){
   if(f=="official" && tr.getAttribute("data-kind")!="official"){return false;}
   if(f=="third" && tr.getAttribute("data-kind")!="third"){return false;}
   if(f=="off" && tr.getAttribute("data-on")!="0"){return false;}
   var s=q?q.value.replace(/^\s+|\s+$/g,"").toLowerCase():"";
   return s=="" || (tr.textContent||"").toLowerCase().indexOf(s)>=0;
 }
 function draw(){
   var hits=[]; for(var i=0;i<rows.length;i++){ rows[i].style.display="none"; if(match(rows[i])){hits.push(rows[i]);} }
   var pages=Math.max(1,Math.ceil(hits.length/size)); if(cur>pages){cur=pages;}
   for(var j=0;j<hits.length;j++){ if(j>=(cur-1)*size && j<cur*size){hits[j].style.display="";} }
   if(shown){shown.innerHTML=hits.length;}
   if(!box){return;}
   if(pages<2){box.innerHTML="";return;}
   var h="<a href=\"javascript:blur();\" class=\""+(cur==1?"awc-pg-off":"")+"\" data-p=\""+(cur-1)+"\">&lsaquo;</a>";
   for(var p=1;p<=pages;p++){ h+="<a href=\"javascript:blur();\" class=\""+(p==cur?"awc-pg-cur":"")+"\" data-p=\""+p+"\">"+p+"</a>"; }
   h+="<a href=\"javascript:blur();\" class=\""+(cur==pages?"awc-pg-off":"")+"\" data-p=\""+(cur+1)+"\">&rsaquo;</a>";
   box.innerHTML=h;
   var as=box.getElementsByTagName("a");
   for(var k=0;k<as.length;k++){ as[k].onclick=function(){ var n=parseInt(this.getAttribute("data-p"),10); if(n>=1&&n<=pages&&n!=cur){cur=n;draw();} return false; }; }
 }
 var chips=document.querySelectorAll(".clwl-chip");
 for(var c=0;c<chips.length;c++){ chips[c].onclick=function(){
   for(var d=0;d<chips.length;d++){chips[d].className=chips[d].className.replace(" on","");}
   this.className+=" on"; f=this.getAttribute("data-f"); cur=1; draw(); return false; }; }
 if(q){ q.oninput=q.onkeyup=function(){cur=1;draw();}; }
 draw();
})();
</script>';
	return str_replace("__SIZE__",(string)intval($size),$js);
}

function clwl_style():string{
	if(isset($GLOBALS["CLWL_STYLE"])){return "";}
	$GLOBALS["CLWL_STYLE"]=true;
	// Tuiles, carte, table, pastilles, segmente, recherche, boutons : kit ak- (angular.css.php).
	// La pagination reste celle d'awc (awc_table_style).
	return awc_table_style()."<style>
.clwl{margin-top:10px;display:flex;flex-direction:column;gap:20px}
.clwl a.ak-btn{text-decoration:none}
.clwl-kpi{flex-direction:column;align-items:stretch;justify-content:flex-start;gap:10px;min-height:140px}
.clwl-kpi-dark{background:#00504a;border-color:#00504a;color:#fff}
.clwl-kpi-dark .ak-fig-l,.clwl-kpi-dark .clwl-kpi-sub,.clwl-kpi-dark .clwl-kpi-num{color:#fff}
.clwl-kpi-dark .ak-ico{background:rgba(255,255,255,.15);color:#fff}
.clwl-kpi-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.clwl-kpi-val{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}
.clwl-kpi-num{font-size:32px;font-weight:700;line-height:38px;color:#111d23}
.clwl-kpi-mid{font-size:24px;font-weight:700;line-height:30px}
.clwl-kpi-unit{font-size:14px;color:#3d4947}
.clwl-kpi-sub{display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:13px;color:#6d7a77;margin-top:auto}
.clwl-teal{color:#006a62}
.clwl-amber{color:#865300}
.clwl-dot{margin-right:6px;vertical-align:middle}
.clwl-toolbar{justify-content:space-between;gap:16px;margin:0;padding:12px 16px}
.clwl-chips{flex-wrap:wrap}
.clwl-chip-n{font-weight:400;opacity:.75}
.clwl-search{min-width:280px;flex:1;max-width:460px}
.clwl-scroll{overflow-x:auto}
.clwl-tbl tbody td{padding-top:14px;padding-bottom:14px}
.clwl-tbl tbody tr:hover{background:#fbfeff}
.clwl-ident{display:flex;align-items:center;gap:14px}
.clwl-name{font-size:15px;font-weight:700;color:#111d23!important;text-decoration:none!important;word-break:break-all}
.clwl-name:hover{color:#006a62!important;text-decoration:underline!important}
.clwl-date{font-size:14px;font-weight:600;white-space:nowrap}
.clwl-by{font-size:12px;color:#6d7a77;margin-top:2px}
.clwl-status{display:flex;align-items:center;gap:10px}
.clwl-off .clwl-name{color:#9aa5a3!important;text-decoration:line-through!important}
.clwl-off .clwl-ico{background:#eef2f2;color:#9aa5a3}
.clwl-off .clwl-badge{opacity:.6}
.clwl-empty{padding:44px 20px;text-align:center}
.clwl-empty-ico{font-size:40px;color:#006a62;width:84px;height:84px;border-radius:24px;background:var(--ak-soft);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px}
.clwl-empty-t{font-size:18px;font-weight:700}
.clwl-empty-p{font-size:14px;color:#3d4947;margin:8px auto 18px;max-width:560px;line-height:21px}
.clwl-help-h{font-size:18px;font-weight:700;padding-bottom:14px;margin-bottom:18px;border-bottom:1px solid #e3f0f8}
.clwl-help-h i{color:#006a62;margin-right:6px}
.clwl-help-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.clwl-side{border:1px solid #e3f0f8;border-radius:12px;padding:18px 20px;display:flex;flex-direction:column;gap:10px}
.clwl-side-on{border:2px solid #84d5cb}
.clwl-side-h{display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#00504a}
.clwl-side-t{font-size:16px;font-weight:700}
.clwl-side p{font-size:14px;line-height:22px;color:#3d4947;margin:0;flex:1}
.clwl-side-f{display:flex;justify-content:space-between;align-items:center;gap:10px;border-top:1px solid #e3f0f8;padding-top:12px;font-size:13px;color:#6d7a77}
.clwl-side-on .clwl-side-f{color:#00504a;font-weight:600;justify-content:flex-start}
.clwl-tag-warn{text-transform:none;letter-spacing:0}
.clwl-link{font-weight:700;color:#006a62!important;text-decoration:none!important;white-space:nowrap}
.clwl-link:hover{text-decoration:underline!important}
@media(max-width:820px){.clwl-help-grid{grid-template-columns:1fr}.clwl-tbl thead{display:none}.clwl-tbl tbody td{display:block;padding:6px 16px;border-top:0}.clwl-tbl tbody tr{display:block;padding:10px 0;border-top:1px solid rgba(188,201,198,.3)}.clwl-search{min-width:0}}
</style>";
}
