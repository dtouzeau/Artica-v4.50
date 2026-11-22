<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
// awc_table_style() / awc_pager_js(): inclusion inconditionnelle, sinon fatal au rendu.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Verrou d'une politique Meta dnscache : collection restrictions et cle DNSAllowOpenResolver.
include_once(dirname(__FILE__)."/ressources/class.unbound.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["main"])){main();exit;}
if(isset($_GET["newitem-js"])){newitem_js();exit;}
if(isset($_GET["new-item-popup"])){newitem_popup();exit;}
if(isset($_POST["newitem"])){newitem_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){newitem_delete();exit;}
if(isset($_GET["tinypage-dnsdist"])){tinypage_dnsdist();exit;}
if(isset($_GET["tinypage-unbound"])){tinypage_unbound();exit;}
if(isset($_GET["ViaSpopup"])){ViaSpopup();exit;}
if(isset($_GET["DNSAllowOpenResolver-js"])){DNSAllowOpenResolver_js();exit;}
page();



function newitem_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(unbound_meta_refuse_js_collection("restrictions")){return false;}
	$title=$tpl->javascript_parse_text("{new_item}");
    $tinydnsdist=null;
    if(isset($_GET["tinydnsdist"])){
        $tinydnsdist="&tinydnsdist=yes";
    }
	return $tpl->js_dialog3($title, "$page?new-item-popup=yes&$tinydnsdist");
	
}
function ViaSpopup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{networks_restrictions}");
    return $tpl->js_dialog($title, "$page?tinypage-dnsdist=yes");
}

function delete_js():bool{
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$id=(string)$_GET["delete-js"];
	$ask=addslashes($tpl->javascript_parse_text("{delete}")." ".$id);
	$idjs=addslashes($id);
    $tinydnsdist="LoadAjax('table-loader-recursor','$page?main=yes');";
    if(isset($_GET["tinyunbound"])){
       $tinydnsdist="LoadAjax('table-loader-recursor','$page?main=yes&tinyunbound=yes');";
    }
    if(isset($_GET["tinydnsdist"])){
       $tinydnsdist="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');LoadAjax('table-loader-recursor','$page?main=yes&tinydnsdist=yes');";
    }
	
	$html="			
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}	
	$tinydnsdist
}
function Save$t(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$idjs');
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
	
Save$t()";
	echo $html;
	return true;
}



function newitem_popup():bool{
		$page=CurrentPageName();
		$tpl=new template_admin();
        $tt[]="dialogInstance3.close();";
        if(isset($_GET["tinydnsdist"])){
            $tt[]="LoadAjax('dnsdist-table-start','fw.dns.dnsdist.settings.php');";
            $tt[]="LoadAjax('table-loader-recursor','$page?main=yes&tinydnsdist=yes');";
        }else{
            $tt[]="LoadAjax('table-loader-recursor','$page?main=yes');";
        }

    $js=@implode("",$tt);
        $form[]=$tpl->field_text("newitem", "{network_item}", null);
		$html[]=$tpl->form_outside(null,  $form,"{PowerDNS-allow-from}<hr>{pdns_network_item_add}","{add}",
				$js,"AsDnsAdministrator");
		echo $tpl->_ENGINE_parse_body( $html);
        return true;
}
function newitem_save():bool{
    $tpl=new template_admin();
    if(!pdns_restrict_can_edit()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $meta=unbound_meta_refuse_collection("restrictions");
    if($meta!==""){echo $meta;return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$item=url_decode_special_tool($_POST["newitem"]);
    $item=$tpl->CLEAN_BAD_CHARSNET($item);

	if($item==null){
		echo $tpl->post_error("No posted data");
		return false;
	}
	$q->QUERY_SQL_PARAMS("INSERT OR IGNORE INTO pdns_restricts (`address`) VALUES(?)",array($item));
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    return admin_tracks_post("Adding DNS Network restrictions");
}
function newitem_delete():bool{
    $tpl=new template_admin();
    if(!pdns_restrict_can_edit()){echo "Permission denied";return false;}
    // xSave de delete_js() montre tout texte recu (alert) : texte brut, pas de "jserror:".
    if(unbound_meta_collection_locked("restrictions")){echo $tpl->javascript_parse_text("{unbound_meta_managed_locked}");return false;}
	$item=url_decode_special_tool($_POST["delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL_PARAMS("DELETE FROM pdns_restricts WHERE address=?",array($item));
	if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks("Remove $item network DNS restriction");

}

function tinypage_dnsdist():bool{
    $page=CurrentPageName();
    $html[]="<div id='progress-recursor-restart'></div>";
    $html[]="<div id='table-loader-recursor'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('table-loader-recursor','$page?main=yes&tinydnsdist=yes');";
    $html[]="</script>";

	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tinypage_unbound(){
    $page=CurrentPageName();
    $html[]="<div id='progress-recursor-restart'></div>";
    $html[]="<div id='table-loader-recursor'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('table-loader-recursor','$page?main=yes&tinyunbound=yes');";
    $html[]="</script>";

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><p>{PowerDNS-allow-from}</p></div>
	</div>
    <div class='row'><div id='progress-recursor-restart'></div>
	<div class='ibox-content'>
	<div id='table-loader-recursor'></div>
	</div>
	</div>
	<script>
	LoadAjax('table-loader-recursor','$page?main=yes');
	</script>";
    echo $tpl->_ENGINE_parse_body($html);

}
function DNSAllowOpenResolver_js():bool{
    $DNSAllowOpenResolver=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSAllowOpenResolver"));
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $page=CurrentPageName();
    if(unbound_meta_key_locked("DNSAllowOpenResolver")){
        header("content-type: application/x-javascript");
        (new template_admin())->js_error("{unbound_meta_managed_locked}");
        return false;
    }
    if($DNSAllowOpenResolver==1){
        $text="OFF";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSAllowOpenResolver",0);
    }else{
        $text="ON";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSAllowOpenResolver",1);
    }
    if($EnableDNSDist==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/php/restart");
    }
    if($UnboundEnabled==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/restart");
    }

    $js="LoadAjax('table-loader-recursor','$page?main=yes');";

    if(isset($_GET["tinydnsdist"])) {
        $js="LoadAjax('table-loader-recursor','$page?main=yes&tinydnsdist=yes');";
    }

    if(isset($_GET["tinyunbound"])){
        $js="LoadAjax('table-loader-recursor','$page?main=yes&tinyunbound=yes');";
    }

    echo "$js";
    return admin_tracks("Sert Open resolver DNS service to $text");
}
function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $jstiny=null;

    $jsrestart=$tpl->framework_buildjs("pdns.php?restart-recusor=yes",
    "recusor.restart.progress",
    "recusor.restart.log",
    "progress-recursor-restart",
    "document.getElementById('progress-recursor-restart').innerHTML='';");


	if($UnboundEnabled==1){
        $jsrestart=$tpl->framework_buildjs("/unbound/restart",
            "unbound.restart.progress","unbound.restart.log",
                "progress-recursor-restart",
            "document.getElementById('progress-recursor-restart').innerHTML='';");
    }

    if($EnableDNSDist==1){
        $jsrestart=$tpl->framework_buildjs("/dnsfw/service/php/restart",
            "dnsdist.restart","dnsdist.restart.log",
            "progress-recursor-restart",
            "document.getElementById('progress-recursor-restart').innerHTML='';"
        );

    }


	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
    $DelAdd="";$tinyadd="";

    if(isset($_GET["tinydnsdist"])) {
        $tinyadd = "&tinydnsdist=yes";
        $DelAdd = "&tinydnsdist=yes";
    }

    if(isset($_GET["tinyunbound"])){
        $tinyadd = "&tinyunbound=yes";
        $DelAdd = "&tinyunbound=yes";
    }

        $DNSAllowOpenResolver=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSAllowOpenResolver"));
        // Politique Meta : ajout masque si la liste est tenue, bascule inerte si la cle l'est.
        $metaList=unbound_meta_collection_locked("restrictions");
        $metaOpen=unbound_meta_key_locked("DNSAllowOpenResolver");
        $openJs=$metaOpen?"blur()":"Loadjs('$page?DNSAllowOpenResolver-js=yes$tinyadd')";
        if($DNSAllowOpenResolver==0) {
            if(!$metaList){$topbuttons[] = array("Loadjs('$page?newitem-js=yes$tinyadd')", ico_plus, $new_entry);}
            $topbuttons[] = array($openJs, ico_shield_disabled, "{open_resolver_mode}: OFF");
        }else{
            $topbuttons[] = array($openJs, ico_shield, "{open_resolver_mode}: ON");
        }
        $topbuttons[] = array($jsrestart, ico_save, "{reconfigure_service}");


    if(isset($_GET["tinydnsdist"])) {
        $html[]=$tpl->th_buttons($topbuttons);
    }

    if(isset($_GET["tinyunbound"])){
        $title = "{networks_restrictions}";
        $TINY_ARRAY["TITLE"] =$title;
        $TINY_ARRAY["ICO"] = "fa fas fa-database";
        $TINY_ARRAY["EXPL"] = "{PowerDNS-allow-from}";
        $TINY_ARRAY["BUTTONS"] = $tpl->table_buttons($topbuttons);
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    }

    if($metaList || $metaOpen){$html[]=unbound_meta_banner($tpl);}
    if($DNSAllowOpenResolver==1){
        $html[]=$tpl->div_info("{open_resolver_mode}||{open_resolver_mode_explain}");
        $html[]="<script>$jstiny\nNoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return;
    }

    $rows=array();
    $builtin=false;
    $results=$q->QUERY_SQL("SELECT address FROM pdns_restricts");
    if(!is_array($results)){$results=array();}
    foreach ($results as $ligne){
        $rows[]=pdns_restrict_row(trim((string)$ligne["address"]),false,$DelAdd,$metaList);
    }
    // Aucune entree: le service applique les plages RFC 1918, affichees
    // comme telles et sans bouton de suppression.
    if(count($rows)==0){
        $builtin=true;
        foreach (array("192.168.0.0/16","10.0.0.0/8","172.16.0.0/12","127.0.0.0/8") as $address){
            $rows[]=pdns_restrict_row($address,true,$DelAdd);
        }
    }

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table id='table-dns-forward-zones' class='awc-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>{network_item}</th>";
    $html[]="<th class='center'>{status}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody id='pdns-restrict-rows'>";
    $html[]=@implode("\n",$rows);
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]=$builtin?"<span>{PowerDNS-allow-from-default}</span>":"<span>".count($rows)." $items</span>";
    $html[]="<span class='awc-pg' id='pdns-restrict-pager'></span>";
    $html[]="</div></div></div>";

    // Scripts concatenes APRES l'analyse: _ENGINE_parse_body lirait les
    // blocs {...} du JS comme des {jetons}.
    $script="<script>$jstiny\nNoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(25,"pdns-restrict-rows","pdns-restrict-pager");
}

function pdns_restrict_can_edit():bool{
    $users=new usersMenus();
    return $users->AsDnsAdministrator || $users->AsSystemAdministrator;
}

// $readonly : liste tenue par une politique Meta, la ligne n'a pas de bouton de suppression.
function pdns_restrict_row(string $address,bool $builtin,string $DelAdd,bool $readonly=false):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=md5($address);
    $name=htmlspecialchars($address,ENT_QUOTES);
    $unsupported=($address=="0.0.0.0/0");

    if($builtin){
        $badge="<span class='awc-badge awc-badge-off'>{default}</span>";
    }elseif($unsupported){
        $badge="<span class='awc-badge awc-badge-alert'>{not_supported}</span>";
    }else{
        $badge="<span class='awc-badge awc-badge-on'>{allowed}</span>";
    }

    $acts="";
    if(!$builtin && !$readonly && pdns_restrict_can_edit()){
        $acts="<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" "
            ."OnClick=\"Loadjs('$page?delete-js=".urlencode($address)."&id=$md$DelAdd')\">"
            ."<i class='fas fa-trash-can'></i></a>";
    }

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico".($builtin?" awc-ico-off":"")."'><i class='".ico_networks."'></i></span>";
    $h.="<span><span class='awc-name".($builtin?" awc-name-off":"")."'>$name</span>";
    $h.="</span></div></td>";
    $h.="<td class='center' style='width:1%' nowrap>$badge</td>";
    $h.="<td style='width:1%' nowrap><div class='awc-acts'>$acts</div></td>";
    $h.="</tr>";
    return $h;
}
