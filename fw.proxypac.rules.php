<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// Politique Meta proxypac : regles PAC en lecture seule quand "rules" est tenu.
include_once(dirname(__FILE__)."/ressources/class.proxypac.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
// Pentest 2026-11-04 : les md5 des proxys de destination etaient recopies sans encodage dans la page (XSS).
foreach(array("rules-proxies-move","rules-proxies-edit","rules-proxy-secure","rules-proxy-enable","rules-proxy-unlink") as $k){if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9]/','',(string)$_GET[$k]);}}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["export-download"])){export_download();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-search"])){table_search();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-options"])){rule_options();exit;}
if(isset($_POST["rule-options"])){rule_options_save();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["rules-proxy-secure"])){rule_proxies_secure();exit;}
if(isset($_GET["rules-proxy-enable"])){rules_proxies_enabled();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}

if(isset($_GET["rule-proxies"])){rule_proxies();exit;}
if(isset($_GET["rules-proxies-edit"])){rule_proxies_edit();exit;}
if(isset($_GET["rule-proxies-table"])){rule_proxies_table();exit;}
if(isset($_GET["rule-proxies-newjs"])){rule_proxies_new_js();exit;}
if(isset($_GET["rule-proxies-newpopup"])){rule_proxies_new_popup();exit;}
if(isset($_GET["rules-proxies-move"])){rule_proxies_move();exit;}
if(isset($_GET["rules-proxy-unlink"])){rule_proxies_unlink();exit;}
if(isset($_GET["rules-move"])){rules_move();exit;}
if(isset($_POST["rule-proxy"])){rule_proxies_new_save();exit;}
if(isset($_GET["builded-script-js"])){builded_script_js();exit;}
if(isset($_GET["builded-script-popup"])){builded_script_popup();exit;}
if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_GET["remote-sync-js"])){remote_sync_js();exit;}
page();

// proxypac_unwrap unboxes the aclsSqliteOK envelope ({success:true,data:...})
// returned by every /proxypac/wpad-rules* GET, so callers can read fields off
// the rule directly. Falls through to whatever was passed in if there's no
// "data" key — keeps older endpoints that return bare arrays still working.
function proxypac_unwrap($json){
    if(is_array($json) && array_key_exists("data",$json)){
        return is_array($json["data"]) ? $json["data"] : [];
    }
    return is_array($json) ? $json : [];
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{wpad_rules}",
        "fa fa-align-justify",
        "{wpad_service_explain}",
        "$page?table=yes",
        "proxypac-rules","progress-proxypac-restart",false,"table-loader-proxy-pac");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_PROXY_PAC}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function export_download():bool{
    $ruleid=intval($_GET["export-download"]);
    $destfile=PROGRESS_DIR."/pac.rule.$ruleid.gz";
    $fsize = filesize($destfile);
    header('Content-type: application/x-gzip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"pac.rule.$ruleid.gz\"");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($destfile);
    @unlink($destfile);
    return true;
}
function builded_script_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["builded-script-js"]);
    $tpl->js_dialog3("{proxy_pac}", "$page?builded-script-popup=$id");
    return true;
}
function import_js():bool{
    if(proxypac_meta_refuse_rules_js()){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl->js_dialog3("{import}:{rule}", "$page?import-popup=yes&function=$function");
    return true;
}
function import_popup():bool{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div id='import-rule-progress'></div>";
    $html[]="<p>{import}:{rule} {APP_PROXY_PAC}</p>";
    $html[]="<div class=center>".$tpl->button_upload("{upload_your_file_here} (*.gz)",$page,null,"&function=$function")."</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function builded_script_popup():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["builded-script-popup"]);
    $ruleData=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/wpad-rules/$ID"),true));
    $title=(is_array($ruleData) && isset($ruleData["ruleName"])) ? $ruleData["ruleName"] : "#$ID";

    $jsonR=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/rule/$ID"),true);
    if(!isset($jsonR["Status"])) {
        echo $tpl->div_error($jsonR["Error"]);
        return false;
   }
    if(!$jsonR["Status"]){
        echo $tpl->div_error($jsonR["Error"]);
        return false;
    }
    $form[]=$tpl->field_textareacode("dfszfc",null,$jsonR["Content"]);
    echo $tpl->form_outside($title,$form,null,null);
    return true;
}



function rule_proxies_new_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $id=intval($_GET["rule-proxies-newjs"]);
    $tpl->js_dialog3("{new_proxy}", "$page?rule-proxies-newpopup=$id&function=$function");
    return true;
}
function rule_proxies_move(){
    header("content-type: application/x-javascript");
    $zmd5=$_GET["rules-proxies-move"];
    $direction=$_GET["direction"];
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    if(proxypac_meta_refuse_rules_js()){return;}

    // dir: 0=down (zorder+1), 1=up (zorder-1)
    $dir=($direction=="up") ? 1 : 0;
    acls_api_send("PUT","/proxypac/destinations/$zmd5/move",["dir"=>$dir]);

    admin_tracks("Proxy.pac:Move proxy.pac proxy $zmd5 to $direction");
    echo "$function();";

}
function rule_proxies_new_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["rule-proxies-newpopup"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $proxyserver=null;
    $proxyport=8080;
    $secure_val=0;
    $bt="{add}";
    $title="{new_proxy}";
    if(isset($_GET["md"])){
        $tpl->field_hidden("md",$_GET["md"]);
        $zmd5=$_GET["md"];
        $destList=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/destinations?aclid=$id"),true));
        $ligne=[];
        foreach($destList as $item){
            if(isset($item["zmd5"]) && $item["zmd5"]===$zmd5){ $ligne=$item; break; }
        }
        $proxyserver=$ligne["proxyServer"] ?? null;
        $proxyport=$ligne["proxyPort"] ?? 8080;
        $secure_val=intval($ligne["secure"] ?? 0);
        $bt="{apply}";
        $title="$proxyserver {listen_port} $proxyport";
    }

    $js[]="LoadAjaxSilent('rule-proxies-table','$page?rule-proxies-table=$id');";
    $js[]="dialogInstance3.close();";
    $js[]="$function();";

    $form[]=$tpl->field_hidden("rule-proxy", $id);
    $form[]=$tpl->field_text("hostname", "{hostname}", $proxyserver,true);
    $form[]=$tpl->field_numeric("port","{port}",$proxyport);
    $form[]=$tpl->field_checkbox("secure","{UseSSL}",$secure_val);

    echo $tpl->form_outside($title, @implode("\n", $form),null,$bt, @implode("", $js),"AsSquidAdministrator");
}
function rule_proxies_new_save(){
    $tpl=new template_admin();
    $ID=intval($_POST["rule-proxy"]);
    if($ID==0){echo "No ID -> 0 ?\n"; return;}
    $meta=proxypac_meta_refuse_rules_post();
    if($meta!==""){echo $meta;return;}
    $hostname=$_POST["hostname"];
    $port=$_POST["port"];
    if(!is_numeric($port)){$port="3128";}
    if($hostname==null){$hostname="1.2.3.4";}
    $secure=intval($_POST["secure"]);

    if(isset($_POST["md"])){
        [$ok,$data]=acls_api_send("PUT","/proxypac/destinations/{$_POST["md"]}",
            ["proxyServer"=>$hostname,"proxyPort"=>intval($port),"secure"=>$secure]);
        if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($data));return;}
        admin_tracks("Proxy.pac:Saving proxy.pac proxy  $hostname:$port");
        return;
    }

    [$ok,$data]=acls_api_send("POST","/proxypac/destinations",
        ["aclid"=>$ID,"proxyServer"=>$hostname,"proxyPort"=>intval($port),"secure"=>$secure]);
    if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($data));return;}

    admin_tracks("Proxy.pac:Creating new proxy.pac proxy $hostname:$port");

}

function rule_proxies(){
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $id=intval($_GET["rule-proxies"]);
    $html="<div id='rule-proxies-table' style='margin-top:20px'></div>
	<script>LoadAjaxSilent('rule-proxies-table','$page?rule-proxies-table=$id&function=$function');</script>
	";
    echo $html;
}

function rule_proxies_edit(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=$_GET["rules-proxies-edit"];
    $id=intval($_GET["aclid"] ?? 0);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $proxyserver="";
    $proxyport="";
    $destList=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/destinations?aclid=$id"),true));
    foreach($destList as $item){
        if(isset($item["zmd5"]) && $item["zmd5"]===$md){
            $proxyserver=$item["proxyServer"] ?? "";
            $proxyport=$item["proxyPort"] ?? "";
            break;
        }
    }
    //proxyserver,proxyport
    $tpl->js_dialog3("{$proxyserver}:$proxyport", "$page?rule-proxies-newpopup=$id&md=$md&function=$function");
}

function rule_proxies_table(){
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $page=CurrentPageName();
    $tpl=new template_admin();

	$id=intval($_GET["rule-proxies-table"]);
	$add="Loadjs('$page?rule-proxies-newjs=$id&function=$function');";
	$psec=proxypac_meta_rules_sec("AsSquidAdministrator");
	if(proxypac_meta_rules_held()){
		$html[]=proxypac_meta_banner($tpl,proxypac_meta_rules());
	}else{
		$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
		$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_proxy} </label>";
		$html[]="</div>";
	}
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{proxy}</th>";
	$html[]="<th class='center'>Secure Proxy</th>";
	$html[]="<th class='center'>{order}</th>";
	$html[]="<th class='center'>{enable}</th>";
	$html[]="<th class='center'>&nbsp;</th>";
	$html[]="</tr></thead><tbody id='pacp-rows'>";

    $results=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/destinations?aclid=$id"),true));

	$TRCLASS=null;
	foreach($results as $index=>$ligne) {
		$TRCLASS=null;
		$proxyserver=$ligne["proxyServer"];
		$proxyport=$ligne["proxyPort"];
		$mkey=$ligne["zmd5"];
        $secure_ico=null;

        $proxyserver=$tpl->td_href($proxyserver,null,"Loadjs('$page?rules-proxies-edit=$mkey&aclid=$id&function=$function')");
		$up=$tpl->icon_up("Loadjs('$page?rules-proxies-move=$mkey&aclid=$id&direction=up&function=$function')",$psec);
		$down=$tpl->icon_down("Loadjs('$page?rules-proxies-move=$mkey&aclid=$id&direction=down&function=$function')",$psec);
		$delete=$tpl->icon_delete("Loadjs('$page?rules-proxy-unlink=$mkey&function=$function')",$psec);

		$secure=$tpl->icon_check($ligne["secure"],
            "Loadjs('$page?rules-proxy-secure=$mkey&aclid=$id')",
            null,$psec);

        if(intval($ligne["secure"])==1){
            $secure_ico="&nbsp;<span class='label label-primary'>Secure Proxy</span>";
        }

        $enabled=$tpl->icon_check($ligne["enabled"],
            "Loadjs('$page?rules-proxy-enable=$mkey&aclid=$id&function=$function')",
            null,$psec);


		// La derniere cellule se fermait sur un </center> ORPHELIN.
		$html[]="<tr id='$mkey'>";
		$html[]="<td><strong>$proxyserver:".htmlspecialchars((string)$proxyport)."$secure_ico</strong></td>";
		$html[]="<td class='center awc-nowrap'>$secure</td>";
		$html[]="<td class='center awc-nowrap'>$up&nbsp;$down</td>";
		$html[]="<td class='center awc-nowrap'>$enabled</td>";
		$html[]="<td><div class='awc-acts'>$delete</div></td>";
		$html[]="</tr>";

	}


	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='pacp-pager'></span></div>";
	$html[]="</div></div>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."

</script>";

	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n", $html)).awc_pager_js(100,"pacp-rows","pacp-pager");
}

function rule_proxies_secure(){
    if(proxypac_meta_refuse_rules_js()){return;}
    $md=$_GET["rules-proxy-secure"];
    $aclid=intval($_GET["aclid"] ?? 0);
    $destList=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/destinations?aclid=$aclid"),true));
    $proxyserver="";
    $proxyport="";
    $secure=0;
    foreach($destList as $item){
        if(isset($item["zmd5"]) && $item["zmd5"]===$md){
            $proxyserver=$item["proxyServer"] ?? "";
            $proxyport=$item["proxyPort"] ?? "";
            $secure=intval($item["secure"] ?? 0);
            break;
        }
    }
    $secure=($secure==1)?0:1;
    acls_api_send("PUT","/proxypac/destinations/$md",["secure"=>$secure]);
    admin_tracks("Proxy.pac: Set Proxy $proxyserver:$proxyport to Secure Proxy = $secure");

}
function rules_proxies_enabled(){
    if(proxypac_meta_refuse_rules_js()){return false;}
    $md=$_GET["rules-proxy-enable"];
    $aclid=intval($_GET["aclid"] ?? 0);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl=new template_admin();
    $destList=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/destinations?aclid=$aclid"),true));
    $proxyserver="";
    $proxyport="";
    $enabled=0;
    foreach($destList as $item){
        if(isset($item["zmd5"]) && $item["zmd5"]===$md){
            $proxyserver=$item["proxyServer"] ?? "";
            $proxyport=$item["proxyPort"] ?? "";
            $enabled=intval($item["enabled"] ?? 0);
            break;
        }
    }
    if($enabled==1){
        admin_tracks("Proxy.pac: Set Proxy $proxyserver:$proxyport to inactive");
        $enabled=0;
    }else{
        admin_tracks("Proxy.pac: Set Proxy $proxyserver:$proxyport to active");
        $enabled=1;
    }
    [$ok,$data]=acls_api_send("PUT","/proxypac/destinations/$md",["enabled"=>$enabled]);
    if(!$ok){
        header("content-type: application/x-javascript");
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text($data));
        return false;
    }
    header("content-type: application/x-javascript");
    echo "$function();";
    return true;
}

function enabled_js(){
    if(proxypac_meta_refuse_rules_js()){return;}
    $aclid=intval($_GET["enabled-js"]);
    $ruleData=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/wpad-rules/$aclid"),true));
    $enabled=intval($ruleData["enabled"] ?? 0);
    $enabled=($enabled==1)?0:1;
    [$ok,$data]=acls_api_send("PUT","/proxypac/wpad-rules/$aclid",["enabled"=>$enabled]);
    if(!$ok){echo htmlspecialchars($data);}
}

function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["delete-rule-js"]);
	
	$md=$_GET["md"];
	$jsafet="$('#$md').remove();";
	
	// Pentest 2026-11-04 : name etait recopie sans encodage dans la page (XSS).
	$tpl->js_confirm_delete(req_html($_GET["name"] ?? "")." #$aclid", "delete-rule", $aclid,$jsafet);

	
}

function rule_delete(){
    if(proxypac_meta_refuse_rules_text()){return;}
    $ID=intval($_POST["delete-rule"]);
    admin_tracks("Proxy.pac:Delete proxy.pac rule $ID");
    acls_api_send("DELETE","/proxypac/wpad-rules/$ID");
}
function rule_proxies_unlink(){
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl=new template_admin();
    $zmd5=$_GET["rules-proxy-unlink"];
    if(proxypac_meta_refuse_rules_js()){return;}
    [$ok,$data]=acls_api_send("DELETE","/proxypac/destinations/$zmd5");
    if(!$ok){$tpl->js_mysql_alert($data);return;}

    admin_tracks("Proxy.pac:Unlink proxy.pac proxy $zmd5");

    header("content-type: application/x-javascript");
    echo "$('#$zmd5').remove();\n$function();";

}



function rule_id_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["ruleid-js"]);
    $title="{new_rule}";
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    if($id>0){
        $ruleData=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/wpad-rules/$id"),true));
        $ruleName=(is_array($ruleData) && isset($ruleData["ruleName"])) ? $ruleData["ruleName"] : "";
        $title="{rule}: $id $ruleName";
    }
    $title=$tpl->javascript_parse_text($title);
    $tpl->js_dialog($title,"$page?rule-popup=$id&function=$function");
}



function rule_options():bool{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $ID=intval($_GET["rule-options"]);
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ligne=[];
    $ligne["enabled"]=1;
    $ligne["zorder"]=1;
    $btname="{add}";
    $ligne["rulename"]="{new_rule}";
    $ligne["RemoteEnabled"]=0;
    $ligne["RemoteURL"]="";
    $ligne["RemoteInterval"]=1440;
    $ligne["RemoteInsecure"]=0;
    $ligne["RemoteDirect"]=0;
    $ligne["RemoteUser"]="";
    $BootstrapDialog="BootstrapDialog1.close();$function();";
    if($ID>0){
        $btname="{apply}";
        $ruleData=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/wpad-rules/$ID"),true));
        if(is_array($ruleData) && !empty($ruleData)){
            $ligne["rulename"]=$ruleData["ruleName"] ?? "";
            $ligne["enabled"]=$ruleData["enabled"] ?? 1;
            $ligne["zorder"]=$ruleData["xOrder"] ?? 1;
            $ligne["dntlhstname"]=$ruleData["dntlhstname"] ?? 0;
            $ligne["isResolvable"]=$ruleData["isResolvable"] ?? 0;
            $ligne["FinishbyDirect"]=$ruleData["finishbyDirect"] ?? 0;
            $ligne["NomadeMode"]=$ruleData["nomadeMode"] ?? 0;
            $ligne["NomadeResolve"]=$ruleData["nomadeResolve"] ?? "";
            $ligne["LBL"]=$ruleData["lbl"] ?? 0;
            $ligne["office365"]=$ruleData["office365"] ?? 0;
            $ligne["RemoteEnabled"]=$ruleData["remoteEnabled"] ?? 0;
            // field_text applique htmlspecialchars, puis tout le formulaire repasse par
            // _ENGINE_parse_body qui developpe {token} et [url]: on remplace donc les
            // accolades par %7B/%7D (equivalent dans une URL, et rien a double-encoder).
            $ligne["RemoteURL"]=remote_pac_display_url((string)($ruleData["remoteUrl"] ?? ""));
            $ligne["RemoteInterval"]=$ruleData["remoteInterval"] ?? 1440;
            $ligne["RemoteInsecure"]=$ruleData["remoteInsecure"] ?? 0;
            $ligne["RemoteDirect"]=$ruleData["remoteDirect"] ?? 0;
            $ligne["RemoteUser"]=remote_pac_display_user((string)($ruleData["remoteUser"] ?? ""));
        }
        $BootstrapDialog="$function();";
    }
	$tpl->field_hidden("rule-options", $ID);
	if(proxypac_meta_rules_held()){proxypac_meta_lock_form($tpl);}
	$form[]=$tpl->field_text("rulename","{rule_name}",$ligne["rulename"],true,null,false);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true,'{enabled}');
	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"]);
	$form[]=$tpl->field_checkbox("dntlhstname","{dnot_proxy_localnames}",$ligne["dntlhstname"],false,'{dnot_proxy_localnames_explain}');
	$form[]=$tpl->field_checkbox("isResolvable","{dnot_proxy_lisResolvable}",$ligne["isResolvable"],false,'{dnot_proxy_lisResolvable_explain}');

    $form[]=$tpl->field_checkbox("office365","{dnot_proxy_office365}",$ligne["office365"],false,'{dnot_proxy_office365_explain}');

	$form[]=$tpl->field_checkbox("FinishbyDirect","{return_direct_mode}",$ligne["FinishbyDirect"],false,'{wpad_return_direct_mode}');
    $form[]=$tpl->field_checkbox("PAC_LBL","{load_balancer}",$ligne["LBL"],false,'{load_balancer}');

	$form[]=$tpl->field_section("{outofoffice_policy}");
	$form[]=$tpl->field_checkbox("NomadeMode","{outofoffice_enable}",$ligne["NomadeMode"],"NomadeResolve","{wpad_out_of_office_explain}");
    $form[]=$tpl->field_text("NomadeResolve","{hostname_to_resolve}",$ligne["NomadeResolve"]);
    $form[]=$tpl->field_section("{remote_pac}");
    $form[]=$tpl->field_checkbox("RemoteEnabled","{remote_pac_enable}",$ligne["RemoteEnabled"],false,"{remote_pac_explain}");
    $form[]=$tpl->field_text("RemoteURL","{remote_pac_url}",$ligne["RemoteURL"]);
    $form[]=$tpl->field_numeric("RemoteInterval","{remote_pac_interval}",$ligne["RemoteInterval"],"{remote_pac_interval_explain}");
    $form[]=$tpl->field_checkbox("RemoteInsecure","{remote_pac_insecure}",$ligne["RemoteInsecure"]);
    $form[]=$tpl->field_checkbox("RemoteDirect","{remote_pac_direct}",$ligne["RemoteDirect"]);
    $form[]=$tpl->field_text("RemoteUser","{username}",$ligne["RemoteUser"]);
    // Jamais prerempli: articarest ne renvoie pas le mot de passe. Vide = inchange.
    $form[]=$tpl->field_password("RemotePassword","{password}","");
    $html[]="<div id='progress-ppacrule-$ID'></div>";
	$html[]=$tpl->form_outside("",$form,null,$btname,"$BootstrapDialog","AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}




function rule_options_save(){
    $tpl=new template_admin();
    // CLEAN_POST_XSS retire & % ; # + ... de chaque champ: une URL avec chaine de
    // requete (?a=1&b=2) ou un mot de passe contenant ces caracteres en sortiraient
    // abimes. Ces deux valeurs partent en JSON vers articarest (ni SQL ni HTML):
    // on les lit donc brutes, comme CLEAN_POST_XSS le fait deja pour "password".
    // Les champs de form_outside partent doublement encodes (encodeURIComponent +
    // escape() de XHRConnection.js:79): PHP n'en decode qu'une couche, rawurldecode()
    // (et non urldecode(): un "+" litteral doit rester) retire l'autre. Surtout PAS
    // url_decode_special_tool() en plus: il remplace %25/%20/%26... et on decoderait
    // deux fois ("p%41" deviendrait "pA").
    $RemoteURL=trim(rawurldecode((string)($_POST["RemoteURL"] ?? "")));
    $RemoteUser=trim(rawurldecode((string)($_POST["RemoteUser"] ?? "")));
    $RemotePassword=rawurldecode((string)($_POST["RemotePassword"] ?? ""));
    $tpl->CLEAN_POST_XSS();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}
    $meta=proxypac_meta_refuse_rules_post();
    if($meta!==""){echo $meta;return;}

    // L'URL brute est reaffichee dans le formulaire: on refuse ce qui n'a rien a
    // faire dans une URL et qui serait dangereux en HTML.
    // Les accolades aussi (l'URL repasse par _ENGINE_parse_body: une URL legitime
    // les ecrit %7B / %7D), ainsi que les balises [url] que ce moteur developpe.
    if($RemoteURL<>"" && (preg_match('/[\s"\'<>{}\x00-\x1f\x7f]/',$RemoteURL) || preg_match('#\[/?url\]#i',$RemoteURL))){
        echo $tpl->post_error("{remote_pac_url}: invalid character");
        return;
    }
    // Identifiant: lu brut (un DOMAIN\svc-pac perdrait son antislash), reaffiche dans le formulaire.
    if($RemoteUser<>"" && (preg_match('/[\s"\'<>{}\x00-\x1f\x7f]/',str_replace(" ","",$RemoteUser)) || preg_match('#\[/?url\]#i',$RemoteUser))){
        echo $tpl->post_error("{username}: invalid character");
        return;
    }

    if($_POST["NomadeResolve"]<>null){
        $ip=new IP();
        if($ip->isValid($_POST["NomadeResolve"])){
            echo $tpl->post_error("Cannot be an IP address!");
            return;
        }
    }

    $body=[
        "ruleName"      =>strval($_POST["rulename"]),
        "enabled"       =>intval($_POST["enabled"]),
        "dntlhstname"   =>intval($_POST["dntlhstname"]),
        "isResolvable"  =>intval($_POST["isResolvable"]),
        "finishbyDirect"=>intval($_POST["FinishbyDirect"]),
        "nomadeMode"    =>intval($_POST["NomadeMode"]),
        "nomadeResolve" =>strval($_POST["NomadeResolve"]),
        "lbl"           =>intval($_POST["PAC_LBL"]),
        // office365 etait le SEUL champ du formulaire absent de ce tableau: la
        // case etait bien postee, mais jamais transmise au demon -- lequel
        // ignore en silence un champ qu'il ne connait pas (readJSONBody utilise
        // json.Unmarshal sans DisallowUnknownFields), d'ou une sauvegarde
        // apparemment reussie et un reglage jamais enregistre.
        "office365"     =>intval($_POST["office365"]),
        // Source PAC distante. articarest valide l'URL (http/https, sans
        // identifiants), borne l'intervalle et garde le mot de passe existant
        // quand celui-ci arrive vide.
        "remoteEnabled" =>intval($_POST["RemoteEnabled"] ?? 0),
        "remoteUrl"     =>$RemoteURL,
        "remoteInterval"=>intval($_POST["RemoteInterval"] ?? 1440),
        "remoteInsecure"=>intval($_POST["RemoteInsecure"] ?? 0),
        "remoteDirect"  =>intval($_POST["RemoteDirect"] ?? 0),
        "remoteUser"    =>$RemoteUser,
        "remotePassword"=>$RemotePassword,
    ];

    if(intval($_POST["rule-options"])==0){
        [$ok,$data]=acls_api_send("POST","/proxypac/wpad-rules",$body);
        if(!$ok){echo htmlspecialchars($data);return;}
        return;
    }

    $ID=intval($_POST["rule-options"]);
    [$ok,$err]=acls_api_send("PUT","/proxypac/wpad-rules/$ID",$body);
    if(!$ok){echo htmlspecialchars($err);return;}

    admin_tracks("Proxy.pac:Save proxy.pac rule $ID options");
}

function rule_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["rule-popup"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));

    if($id>0){
        $array["{settings}"]="$page?rule-options=$id&function=$function";

        $refresh_js="LoadAjaxSilent('proxy-pac-rule-sources','fw.proxy.acls.objects.php?rule-id=$id&TableLink=wpad_sources_link&function=$function');";
        $refresh_enc=base64_encode("$function()");

        $array["{objects}"]="fw.proxy.acls.objects.php?rule-id=$id&TableLink=wpad_sources_link&RefreshTable=$refresh_enc&ProxyPac=1&function=$function";



        $array["{whitelist}"]="fw.proxy.acls.objects.php?rule-id=$id&TableLink=wpad_white_link&RefreshTable=$refresh_enc&ProxyPac=1";

        $array["{force}"]="fw.proxy.acls.objects.php?rule-id=$id&TableLink=wpad_black_link&RefreshTable=$refresh_enc&ProxyPac=1&function=$function";

        $array["{proxy_servers}"]="$page?rule-proxies=$id&function=$function";


    }else{
        $array["{new_rule}"]="$page?rule-options=0&function=$function";
    }


    echo proxypac_meta_banner($tpl,proxypac_meta_rules());
    echo $tpl->tabs_default($array);


}



function rules_move(){
    if(proxypac_meta_refuse_rules_js()){return;}
    $ID=intval($_GET["rules-move"]);
    $direction=$_GET["direction"];
    $dir=($direction=="up")?0:1;
    acls_api_send("PUT","/proxypac/wpad-rules/$ID/move",["dir"=>$dir]);
    admin_tracks("Proxy.pac:Move proxy.pac rule $ID to $direction");
}
///home/squid/proxy_pac_rules/$ID/proxy.pac

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page,null,null,null,"&table-search=yes");

}



function table_search():bool{
    $tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
    $nothing=$tpl->icon_nothing();
    $search=trim($_GET["search"]);
    $tfile=PROGRESS_DIR."/proxy.pac.rules";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/rules");

    // Etat des PAC distants, lu une fois sur la socket d'administration du demon.
    $REMOTE=array();
    $syncData=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/sync"),true);
    if(is_array($syncData) && isset($syncData["rules"]) && is_array($syncData["rules"])){
        foreach($syncData["rules"] as $row){ $REMOTE[intval($row["id"] ?? 0)]=$row; }
    }

    $ACTUAL_RULES=array();
    $trules=explode("\n",@file_get_contents($tfile));
    foreach ($trules as $line){
        $tt=explode("|",$line);
        if(!is_numeric($tt[0])){continue;}
        $ACTUAL_RULES[$tt[0]]=true;
    }
	$add="Loadjs('$page?ruleid-js=0&function=$function',true);";
	if(!$users->AsSquidAdministrator){$add="alert('$ERROR_NO_PRIVS2')";}


    $jsrestart=$tpl->framework_buildjs("ppac:/build",
        "autoconfiguration.apply.progress",
        "/autoconfiguration.apply.log",
        "progress-proxypac-restart","$function()","$function()"
    );

    $jsSimul="Loadjs('fw.proxypac.simul.php')";

	// Politique Meta : regles tenues = bandeau, ni ajout ni import, icones d'ecriture inertes.
	$rulesHeld=proxypac_meta_rules_held();
	$rsec=proxypac_meta_rules_sec("AsSquidAdministrator");
	$html[]=proxypac_meta_banner($tpl,proxypac_meta_rules());
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>$rulename</th>";
	$html[]="<th class='center'>&nbsp;</th>";
	$html[]="<th class='center'>{remote_pac}</th>";
	$html[]="<th class='center'>&nbsp;</th>";
	$html[]="<th class='center'>{enable}</th>";
	$html[]="<th class='center'>{order}</th>";
	$html[]="<th class='center'>&nbsp;</th>";
	$html[]="</tr></thead><tbody id='pacrl-rows'>";

	// $GLOBALS["jsAfterEnc"], construit ici, n'etait relu NULLE PART.
	$ProxyPacLockScript=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacLockScript"));

    $TRCLASS=null;

    if($search<>null){
        $acls=new squid_acls();
        $sqlprox=new mysql_squid_builder();
        $searchs_groups=$acls->search_inacls($search);
        if(count($searchs_groups)>0){
            $serachgp=array();
            foreach ($searchs_groups as $gpid=>$none){
                $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
                $GroupName=is_array($grp) ? ($grp["aclname"] ?? "") : "";
                $description=is_array($grp) ? ($grp["description"] ?? "") : "";
                $GroupType=is_array($grp) ? ($grp["groupType"] ?? "") : "";
                VERBOSE("-$GroupName- -$description- -$GroupType-",__LINE__);
                $type=$sqlprox->acl_GroupType[$GroupType];
                $ico=$sqlprox->acl_GroupTypeIcon[$GroupType];
                $jsdest=grouplink($gpid,"wpad_sources_link");
                if($description<>null){$description=" <small>($description)</small>";}
                $js=$tpl->td_href("$GroupName","{edit}: $GroupName ($type)<hr>$description<hr>",$jsdest);
                $serachgp[]=" <strong>&laquo;<i class='$ico'></i>&nbsp;$js&raquo;</strong><small>($type)</small>";
            }
            $acls_objects_found=$tpl->_ENGINE_parse_body("{acls_objects_found}");
            $acls_objects_found=str_replace("%s",count($serachgp),$acls_objects_found);
            $TRCLASS=null;
            $html[]="<tr class='$TRCLASS' id='0000'>";
            $html[]="<td>$acls_objects_found&nbsp;".@implode(", ",$serachgp)."</td>";
            $html[]="<td class='center awc-nowrap'>$nothing</td>";
            $html[]="<td class='center awc-nowrap'>$nothing</td>";
            $html[]="<td class='center awc-nowrap'>$nothing</td>";
            $html[]="<td class='center awc-nowrap'>$nothing</td>";
            $html[]="<td class='center awc-nowrap'>$nothing</td>";
            $html[]="<td class='center awc-nowrap'>$nothing</td>";
            $html[]="</tr>";

        }
    }

    // GET /proxypac/wpad-rules returns the aclsSqliteOK envelope
    // {success:true, data:[WpadRule, ...]}. Without unwrapping, iterating
    // over the raw decode would loop over the envelope keys ("success",
    // "data") and produce two empty rows showing "?????" and "Pas de source
    // for #0".
    $results=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/wpad-rules"),true));

	foreach($results as $index=>$ligne) {
		$md=md5(serialize($ligne).$index);
		$TRCLASS=null;
		$rulename=$ligne["ruleName"] ?? ($ligne["rulename"] ?? "");
		$rulenameenc=urlencode($rulename);
		$ID=intval($ligne["id"] ?? ($ligne["ID"] ?? 0));
        $addlogtype=null;
        $ruleEnabled=intval($ligne["enabled"] ?? 0);
        $check=$tpl->icon_check($ruleEnabled,"Loadjs('$page?enabled-js=$ID&function=$function')",null,$rsec);
        $up=$tpl->icon_up("Loadjs('$page?rules-move=$ID&direction=up')",$rsec);
        $down=$tpl->icon_down("Loadjs('$page?rules-move=$ID&direction=down')",$rsec);
        $js="Loadjs('$page?ruleid-js=$ID&function=$function',true);";
        $delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js={$ID}&name=$rulenameenc&md=$md&function=$function')",$rsec);
        $explain=$tpl->_ENGINE_parse_body(explainArule($ID));

        $jsonR=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/rule/$ID"),true);
        $download="<i class='fad fa-download' style='color:#CCCCCC'></i>";
        if(isset($jsonR["Status"])) {
            if($jsonR["Status"]){
                if(intval($jsonR["size"])>1){
                    $download = $tpl->icon_download("Loadjs('$page?builded-script-js=$ID')", "AsSquidAdministrator");
                }
            }
        }
        $tstatus="<span class='label label-default'>{inactive}</span>";
        if(isset($ACTUAL_RULES[$ID])){
            $tstatus="<span class='label label-primary'>{active2}</span>";
        }
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td>".head_rule($tpl->td_href($rulename,null,$js),$explain)."</td>";

        $html[]="<td class='center awc-nowrap'>$tstatus</td>";
        $html[]="<td class='center awc-nowrap'>".remote_pac_cell($tpl,$page,$ID,$function,$ligne,$REMOTE)."</td>";
        $html[]="<td class='center awc-nowrap'>$download</td>";
        $html[]="<td class='center awc-nowrap'>$check</td>";
		$html[]="<td class='center awc-nowrap'>$up&nbsp;&nbsp;$down</td>";
		$html[]="<td class='center awc-nowrap'>$delete</td>";
		$html[]="</tr>";

	}


    $TRCLASS=null;
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td>".head_rule("{default}","{by_default_set_proxy_parameters} {a_direct_connection_to_internet_proxy}")."</td>";
    $html[]="<td class='center awc-nowrap'>$nothing</td>";
    $html[]="<td class='center awc-nowrap'>$nothing</td>";
    $html[]="<td class='center awc-nowrap'>$nothing</td>";
    $html[]="<td class='center awc-nowrap'>$nothing</td>";
    $html[]="<td class='center awc-nowrap'>$nothing</td>";
    $html[]="<td class='center awc-nowrap'>$nothing</td>";
    $html[]="</tr>";

	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='pacrl-pager'></span></div>";
	$html[]="</div></div>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);

    $importjs="Loadjs('$page?import-js=yes&function=$function');";

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    if(!$rulesHeld){$btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";}
    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>";
    $btns[] = "<label class=\"btn btn btn-primary\" OnClick=\"$function();\"><i class='fas fa-sync-alt'></i> {refresh} </label>";
    if(!$rulesHeld){$btns[] = "<label class=\"btn btn btn-warning\" OnClick=\"$importjs\"><i class='fas fa-file-import'></i> {import} </label>";}
    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"$jsSimul\"><i class='fas fa-vial'></i> {test_your_rules} </label>";
    $btns[]="</div>";


    $TINY_ARRAY["TITLE"]="{wpad_rules}";
    $TINY_ARRAY["ICO"]="fa fa-align-justify";
    $TINY_ARRAY["EXPL"]="{wpad_service_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $html[]= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="</script>";

    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n", $html)).awc_pager_js(100,"pacp-rows","pacp-pager");
return true;
}
function head_rule($rulename,$explain):string{
    return "<div style='vertical-align: middle;display: table-cell;font-size: 15px;padding-bottom: 6px;padding-top: 3px;'><i class='fa-duotone fa-scroll-old fa-1x'></i>&nbsp;$rulename</div><div style='margin-left: 20px;border-left:2px solid #C5C5C5;padding-left:5px'>$explain</div>";
}
function isRights():bool{
    $users=new usersMenus();
    if($users->AsSquidAdministrator){return true;}
    if($users->AsDansGuardianAdministrator){return true;}
    return false;
}
function grouplink($gpid,$TableLink):string{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $RefreshTable=base64_encode("$function();");
    return "Loadjs('fw.rules.items.php?groupid=$gpid&js-after=$RefreshTable&function=$function&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=1&firewall=0')";
}

function CountOfwhitelist($ID):int{
    $results=acls_api_get("/proxy/acls/sqlite/links?table=wpad_white_link&aclid=$ID");
    if(!is_array($results)){return 0;}
    // Count only enabled entries (matches the legacy WHERE enabled=1 filter)
    $n=0;
    foreach($results as $r){ if(intval($r["enabled"] ?? 1)==1){$n++;} }
    return $n;
}

function explainArule($ID){

    $tpl=new template_admin();
    $qProxy=new mysql_squid_builder(true);
    $h=array();
    if(!isset($_GET["t"])){$_GET["t"]=time();}

    $ruleData=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/wpad-rules/$ID"),true));
    $dntlhstname=intval($ruleData["dntlhstname"] ?? 0);
    $isResolvable=intval($ruleData["isResolvable"] ?? 0);
    $FinishbyDirect=intval($ruleData["finishbyDirect"] ?? 0);
    $NomadeMode=intval($ruleData["nomadeMode"] ?? 0);
    $NomadeResolve=trim($ruleData["nomadeResolve"] ?? "");
    if($NomadeResolve===""){ $NomadeResolve=null; }
    $office365=intval($ruleData["office365"] ?? 0);


    $results=acls_api_get("/proxy/acls/sqlite/links?table=wpad_sources_link&aclid=$ID");
    if(!is_array($results)){$results=[];}
    // Filter to enabled groups only (mirrors legacy WHERE enabled=1)
    $results=array_values(array_filter($results,function($r){return intval($r["enabled"] ?? 1)==1;}));
    if(count($results)==0){return "</a><br><span class='text-danger'>{no_source_defined} for #$ID</span>";}


    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $not=null;
        $pacproxs=array();
        $force_pp="";
        $GroupName=$tpl->utf8_encode($ligne["groupName"] ?? "");
        $negation=$ligne["negation"];
        if($negation==1){$not="{not} ";}
        $pacpxy=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["pacpxy"] ?? "");
        if(!is_array($pacpxy)){$pacpxy=array();}


        if(count($pacpxy)>0){
            foreach ($pacpxy as $index=>$pacline){
                $proxyserver=$pacline["hostname"];
                if($proxyserver=="0.0.0.0"){
                    $pacproxs=array();
                    $pacproxs[]="{direct_to_internet}";
                    break;
                }
                $proxyport=$pacline["port"];
                $pacproxs[]="$proxyserver:$proxyport";
            }
            $force_pp=" {then_force_proxy_parameters}: ".@implode("{or} ",$pacproxs);
        }




        $GroupType=$qProxy->acl_GroupType[$ligne["groupType"] ?? ""];
        $js=grouplink($gpid,"wpad_sources_link");
        $f[]=$not.$tpl->td_href($GroupName,"{edit}: $GroupName ($GroupType)",$js)." ($GroupType)</a>$force_pp";

    }




    $destAll=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/destinations?aclid=$ID"),true));
    $results=array_filter($destAll,function($d){return is_array($d) && intval($d["enabled"] ?? 1)==1;});
    $PPNAME=array();
    if(count($results)>0){
        foreach($results as $index=>$ligne) {
            $secure_ico=null;
            $secure=intval($ligne["secure"] ?? 0);
            if($secure==1){$secure_ico="&nbsp;<small>(Secure Proxy)</small>";}

            $ps=$ligne["proxyServer"] ?? "";
            $pp=$ligne["proxyPort"] ?? "";
            $PPNAME[]="{$ps}:{$pp}$secure_ico";
            $g[]="{$ps}:{$pp}$secure_ico";
        }

        if($FinishbyDirect==1){$g[]="{direct_connection}";}

    }else{
        $g[]="{direct_connection} #$ID";

    }



    $blackLinks=acls_api_get("/proxy/acls/sqlite/links?table=wpad_black_link&aclid=$ID");
    if(!is_array($blackLinks)){$blackLinks=[];}
    $blackLinks=array_values(array_filter($blackLinks,function($r){return intval($r["enabled"] ?? 1)==1;}));
    $FORCED=array();
    $FORCED_PROXY=array();
    foreach($blackLinks as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $jsdest=grouplink($gpid,"wpad_black_link");
        $not=null;
        $GroupTypeSrc=$ligne["groupType"] ?? "";
        $GroupName=$tpl->utf8_encode($ligne["groupName"] ?? "");
        $GroupType=$qProxy->acl_GroupType[$GroupTypeSrc];
        $js=$tpl->td_href($GroupName,"{edit}:$index $GroupName ($GroupType)",$jsdest);

        if($GroupTypeSrc=="dstproxy"){
            $FORCED_PROXY[]="$js</a>";
            continue;
        }
        $negation=$ligne["negation"];
        if($negation==1){$not="{not} ";}
        $FORCED[]="$not$js ($GroupType)</a>";

    }



    $whiteLinks=acls_api_get("/proxy/acls/sqlite/links?table=wpad_white_link&aclid=$ID");
    if(!is_array($whiteLinks)){$whiteLinks=[];}
    $whiteLinks=array_values(array_filter($whiteLinks,function($r){return intval($r["enabled"] ?? 1)==1;}));

    $hi=array();
    if($office365==1){
        $hi[]=" <strong>{dnot_proxy_office365}</strong>";
    }

    if($dntlhstname==1){
        $hi[]=" {dnot_proxy_localnames}";
    }
    if($isResolvable==1){
        $hi[]=" {isResolvable}";
    }


    foreach($whiteLinks as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $not=null;
        $GroupName=$tpl->utf8_encode($ligne["groupName"] ?? "");
        $negation=$ligne["negation"];
        if($negation==1){$not="{not} ";}
        $GroupType=$qProxy->acl_GroupType[$ligne["groupType"] ?? ""];
        $jsdest=grouplink($gpid,"wpad_white_link");
        $js=$tpl->td_href($GroupName,"{edit}: $GroupName ($GroupType)",$jsdest);
        $h[]="$not$js ($GroupType)</a>";

    }


    if(count($h)==0){$h[]="{none}";}

    // /proxypac/excepts returns the aclsSqliteOK envelope; count the unwrapped
    // data array, not the envelope keys (which would always count to 2).
    $exceptList=proxypac_unwrap(json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/excepts?ruleID=$ID"),true));
    $except_count=is_array($exceptList)?count($exceptList):0;
    $except=$tpl->td_href("{exceptfor} $except_count {items}","{click_to_edit}","Loadjs('fw.proxypac.except.php?ruleid=$ID')");




    $pac_notresolve_explain=null;
    if($NomadeMode==1){
        if($NomadeResolve<>null){
            $pac_notresolve_explain=$tpl->_ENGINE_parse_body("{pac_notresolve_explain}")." ";
            $pac_notresolve_explain=str_replace("%host",$NomadeResolve,$pac_notresolve_explain);
        }
    }
    if(CountOfwhitelist($ID)==0){
        $f[]=$tpl->_ENGINE_parse_body("{bypass_proxy_internal}");
    }


    $FINAL[]="</a>{if_a_computer_matches} ".@implode("&nbsp;{and}&nbsp;", $f)." ".$pac_notresolve_explain.$except;
    $thenfinal="{then_set_proxy_parameters}";

    if(count($FORCED)>0){
        if( count($PPNAME)>0 OR count($FORCED_PROXY)>0 ) {
            $FINAL[] = "{and_if_it_request_using} " . @implode("&nbsp;{and}&nbsp;", $FORCED);
            if(count($FORCED_PROXY)==0) {
                $FINAL[] = "{then_force_proxy_parameters} " . @implode($PPNAME) . ".";
            }else{
                $FINAL[] = "{then_force_proxy_parameters} <strong>".@implode(", ",$FORCED_PROXY)."</strong>.";
            }
            $thenfinal="{by_default_set_proxy_parameters}";
        }
    }

    $FINAL[]="$thenfinal ".@implode("&nbsp;{or}&nbsp;", $g);
    $FINAL[]=@implode("&nbsp;{or}&nbsp;", $hi)." {and_do_not_use_proxy_for} ".@implode("&nbsp;{or}&nbsp;", $h)."</span>";
    return @implode("<br>",$FINAL);

}

function proxy_objects($aclid){
    $qProxy=new mysql_squid_builder(true);
    $results=acls_api_get("/proxy/acls/sqlite/links?table=outgoingaddr_sqacllinks&aclid=$aclid");
    if(!is_array($results)){return "{all}";}
    $tt=[];

    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $js="Loadjs('fw.proxy.objects.php?object-js=yes&gpid=$gpid')";
        $neg_text="{is}";
        if($ligne["negation"]==1){$neg_text="{is_not}";}
        $GroupName=$ligne["groupName"] ?? "";
        $tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$GroupName</a> (".$qProxy->acl_GroupType[$ligne["groupType"] ?? ""].")";
    }

    if(count($tt)>0){
        return @implode("<br>{and} ", $tt);
    }else{
        return "{all}";
    }

}

// Synchro immediate du PAC distant d'une regle. Le demon repond tout de suite
// ({"status":"started"}) et telecharge en arriere-plan: on rafraichit la table
// un peu plus tard pour montrer le resultat.
function remote_sync_js():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    header("content-type: application/x-javascript");
    if(!$users->AsSquidAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        return false;
    }
    $ID=intval($_GET["remote-sync-js"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $resp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/sync?id=$ID"),true);
    if(!is_array($resp) || ($resp["status"] ?? "")<>"started"){
        $err="proxy_pac_daemon_unreachable";
        if(is_array($resp)){ $err=strval($resp["error"] ?? ($resp["Error"] ?? $err)); }
        $tpl->js_error("{remote_pac_sync_now}: ".remote_pac_untrusted($err));
        return false;
    }
    admin_tracks("Proxy.pac: manual remote PAC synchronization for rule $ID");
    if($function<>""){ echo "setTimeout(function(){ $function(); },2500);"; }
    return true;
}

// Cellule "PAC distant" d'une ligne de la table. $ligne vient d'articarest (ce
// qui est configure), $REMOTE du demon proxy-pac (ce qui est reellement servi).
function remote_pac_cell($tpl,string $page,int $ID,string $function,array $ligne,array $REMOTE):string{
    if(intval($ligne["remoteEnabled"] ?? 0)==0 || trim((string)($ligne["remoteUrl"] ?? ""))==""){
        return $tpl->icon_nothing();
    }
    // Configuree en base mais inconnue du demon: les regles n'ont pas ete appliquees.
    if(!isset($REMOTE[$ID])){
        return "<span class='label label-default'>{remote_pac_apply}</span>";
    }
    $st=$REMOTE[$ID];
    $sync="&nbsp;<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?remote-sync-js=$ID&function=$function');\""
        ." title=\"{remote_pac_sync_now}\"><i class='fas fa-sync-alt'></i></a>";
    $last=intval($st["last_success"] ?? 0);
    $when=$last>0 ? date("Y-m-d H:i",$last) : "{remote_pac_never}";
    $err=trim((string)($st["last_error"] ?? ""));
    if($err<>""){
        $served=(($st["source"] ?? "")=="remote") ? "{remote_pac_served_copy}" : "{remote_pac_served_local}";
        return "<span class='label label-danger' title=\"".remote_pac_untrusted($err)."\">{remote_pac_failed}</span>$sync"
            ."<br><small>$served &middot; $when</small>";
    }
    if($last==0){
        return "<span class='label label-warning'>{remote_pac_waiting}</span>$sync";
    }
    return "<span class='label label-primary'>{remote_pac_synced}</span>$sync<br><small>$when</small>";
}

// Texte venu d'un serveur distant, a imprimer dans du HTML qui passera ensuite
// par _ENGINE_parse_body: htmlspecialchars ne touche pas aux accolades, et le
// moteur developperait {token} APRES l'echappement (des centaines de libelles
// contiennent un guillemet). On neutralise donc aussi les accolades, ainsi que
// les crochets: le moteur developpe aussi [url]..[/url] en <a href="..."> sur
// tout le contenu, ce qui casserait l'attribut title.
function remote_pac_untrusted(string $text,int $max=300):string{
    if(strlen($text)>$max){ $text=substr($text,0,$max)."..."; }
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),htmlspecialchars($text,ENT_QUOTES));
}

// URL stockee -> valeur du champ du formulaire (voir rule_options).
function remote_pac_display_url(string $url):string{
    $url=str_replace(array("{","}"),array("%7B","%7D"),$url);
    return str_ireplace(array("[url]","[/url]"),array("%5Burl%5D","%5B/url%5D"),$url);
}

// Identifiant stocke -> champ du formulaire: aucune notation d'echappement n'existe
// pour un login, on retire donc ce que le moteur de gabarits developperait.
function remote_pac_display_user(string $user):string{
    $user=str_replace(array("{","}"),"",$user);
    return str_ireplace(array("[url]","[/url]"),"",$user);
}
