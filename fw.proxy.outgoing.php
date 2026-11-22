<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_main_save();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["parameters"])){rule_parameters();exit;}
if(isset($_POST["parameters-save"])){rule_parameters_save();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{outgoing_address}",
        "fas fa-ethernet","{outgoing_address_explain}","$page?table=yes","proxy-outgoing-addr","progress-squid-outgoing-restart",
        false,"table-loader-proxy-outgoingaddr");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{outgoing_address}",$html);
        echo $tpl->build_firewall();
        return;
    }



	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function enabled_js(){
	$aclid=intval($_GET["enabled-js"]);
    $row=acls_api_get("/proxy/outgoing-addr/acls/$aclid");
    if(!is_array($row)){echo "entry not found";return;}
    $enabled=intval($row["enabled"] ?? 0);
    if($enabled==1){$enabled=0;}else{$enabled=1;}
    acls_api_send("PUT","/proxy/outgoing-addr/acls/$aclid",["enabled"=>$enabled]);
}

function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["delete-rule-js"]);
	header("content-type: application/x-javascript");

	// Pentest 2026-11-04 : name etait recopie sans encodage dans la page (XSS).
	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete} ".req_js($_GET["name"] ?? "")." ?");
	$t=time();
	$html="

	var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#row-parent-$aclid').remove();
	}

function Action$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-rule','$aclid');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}

	Action$t();";
	echo $html;

}

function rule_delete():bool{
	$aclid=intval($_POST["delete-rule"]);
    $row=acls_api_get("/proxy/outgoing-addr/acls/$aclid");
    $RULENAME=$row["ruleName"] ?? "";
    [$ok,$err]=acls_api_send("DELETE","/proxy/outgoing-addr/acls/$aclid");
    if(!$ok){echo $err;return false;}
    admin_tracks("Delete Proxy outgoing address RULE $RULENAME  id=$aclid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/outgoing");
    return true;
}




function rule_id_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	// Pentest 2026-11-04 : ruleid-js etait recopie sans encodage dans la page (XSS).
	$id=intval($_GET["ruleid-js"]);
	$title="{new_rule}";

	if($id>0){
        $row=acls_api_get("/proxy/outgoing-addr/acls/$id");
        if(is_array($row)){$title="{rule}: $id {$row["ruleName"]}";}
	}
	$title=$tpl->javascript_parse_text($title);
	return $tpl->js_dialog($title,"$page?rule-popup=$id");
}



function rule_settings(){
	$aclid=intval($_GET["rule-settings"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
	$ligne=["enabled"=>1,"xOrder"=>1,"ruleName"=>"","eth"=>"","config"=>"","interface"=>""];
	$btname="{add}";
	$title="{new_rule}";
	$BootstrapDialog="BootstrapDialog1.close();";
	if($aclid>0){
		$btname="{apply}";
        $row=acls_api_get("/proxy/outgoing-addr/acls/$aclid");
        if(is_array($row)){$ligne=$row;$title=$row["ruleName"];}
		$BootstrapDialog=null;
	}

	$ip=new networking();
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
    $Interfaces=array();
	foreach ($interfaces as $eth){
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$Interfaces[$eth]="$eth $nic->IPADDR - $nic->NICNAME";

	}

	$tpl->field_hidden("rule-save", $aclid);
	$form[]=$tpl->field_text("rulename","{rule_name}",$ligne["ruleName"],true);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);


    $form[]=$tpl->field_interfaces("interface","{outgoing_interface}",$ligne["interface"]);
	$form[]=$tpl->field_array_hash($Interfaces, "eth", "{or} {outgoing_address}", $ligne["eth"],false,null,false);
	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["xOrder"]);
	echo $tpl->form_outside($title,@implode("\n", $form),"{outgoing_interface_explain_proxy}",$btname,"LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');$BootstrapDialog","AsSquidAdministrator");
}




function rule_main_save():bool{

	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){
        echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
        return false;
    }

	$aclid=intval($_POST["rule-save"]);
    $interface=$_POST["interface"] ?? "";
	$rulename=$_POST["rulename"] ?? "";

	if($aclid==0){
        $body=[
            "ruleName"=>$rulename,
            "enabled"=>intval($_POST["enabled"] ?? 1),
            "xOrder"=>intval($_POST["zorder"] ?? 1),
            "eth"=>$_POST["eth"] ?? "",
            "interface"=>$interface,
        ];
        [$ok,$data]=acls_api_send("POST","/proxy/outgoing-addr/acls",$body);
        if(!$ok){echo $data;return false;}
	}else{
        $body=[
            "ruleName"=>$rulename,
            "enabled"=>intval($_POST["enabled"] ?? 1),
            "xOrder"=>intval($_POST["zorder"] ?? 1),
            "eth"=>$_POST["eth"] ?? "",
            "interface"=>$interface,
        ];
        [$ok,$err]=acls_api_send("PUT","/proxy/outgoing-addr/acls/$aclid",$body);
        if(!$ok){echo $err;return false;}
	}

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/outgoing");
    admin_tracks("Set Proxy outgoing address $rulename interface {$_POST["eth"]} id=$aclid");
    return true;

}

function rule_tab(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["rule-popup"]);



	$array["{rule}"]="$page?rule-settings=$aclid";
	if($aclid>0){
		$array["{objects}"]="fw.proxy.objects.php?aclid=$aclid&main-table=outgoingaddr_sqacllinks&fast-acls=0";

	}
	echo $tpl->tabs_default($array);


}



function rule_move_js(){
		header("content-type: application/x-javascript");
		$tpl=new template_admin();
		$dir=$_GET["dir"];
		$aclid=intval($_GET["aclid"]);
        // dir=up→1 (decrement order), dir=down→0 (increment order)
        $apiDir=($dir=="up")?1:0;
        [$ok,$err]=acls_api_send("PUT","/proxy/outgoing-addr/acls/$aclid/move",["dir"=>$apiDir]);
        if(!$ok){
            $errmsg=$tpl->javascript_parse_text($err);
            echo "alert('$errmsg');";return;
        }
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
    $SquidServerPersistentConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidServerPersistentConnections"));
    $jsAfter="LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');";
    if($SquidServerPersistentConnections==1){
        echo $tpl->div_error("{tcp_outgoing_persistent}");
    }
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

	$t=time();
	$add="Loadjs('$page?ruleid-js=0',true);";
	if(!$users->AsSquidAdministrator){$add="alert('$ERROR_NO_PRIVS2')";}

    $jsrestart=$tpl->framework_buildjs("/proxy/acls/outgoing",
        "/squid.access.center.progress","squid.access.center.progress.log","progress-squid-outgoing-restart",$jsAfter);

    $jsonRules=array();
    $CurrRules=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/status/outgoing"));
    if(is_object($json) && property_exists($json,"Rules")) {$jsonRules = $json->Rules; }
    foreach ($jsonRules as $rule=>$none) { $CurrRules[$rule]=true; }


    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>$rulename</th>";
    $html[]="<th class='awc-nowrap'>{status}</th>";
    $html[]="<th class='awc-nowrap'>{enabled}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody id='outg-rows'>";


	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

    $aclid=0;
	$isRights=isRights();
	$allRules=acls_api_get("/proxy/outgoing-addr/acls") ?? [];
	foreach($allRules as $ligne) {
		$aclid=intval($ligne["id"]);
		$html[]=outgoing_rule_row($ligne,$CurrRules,$isRights,$PowerDNSEnableClusterSlave);
	}

    // Regles issues des ports du proxy (API REST): elles ne sont pas modifiables
    // ici, d'ou une ligne sans action.
    $portsData=acls_api_get("/proxy/ports/list") ?? [];
    foreach ($portsData as $portRow){
        $outgoing_addr=$portRow["outgoingAddr"] ?? ($portRow["outgoing_addr"] ?? "");
        if($outgoing_addr<>null && strlen($outgoing_addr)>0){
            $nic=new system_nic($outgoing_addr);
            $port=intval($portRow["port"] ?? 0);
            $Interfaces="$nic->IPADDR - $nic->NICNAME";
            $html[]=outgoing_static_row("row-parent-".intval($portRow["id"] ?? 0),
                "{when_using_the_proxy_port} <strong>$port</strong>",
                "{then} {acl_tcp_outgoing_address} $Interfaces {to_reach_internet}",
                "");
        }
    }

    // Port transparent
    $transpPorts=[];
    foreach ($portsData as $portRow){
        if(intval($portRow["enabled"] ?? 0)==1 && intval($portRow["transparent"] ?? 0)==1 && intval($portRow["useSSL"] ?? ($portRow["UseSSL"] ?? 0))==0){
            $transpPorts[]=$portRow["port"];
        }
    }
	if(count($transpPorts)>0){
		$SquidTransparentInterfaceIN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentInterfaceIN"));
		$SquidTransparentInterfaceOUT=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentInterfaceOUT"));
		if($SquidTransparentInterfaceOUT<>null){
			if($SquidTransparentInterfaceIN==null){$SquidTransparentInterfaceIN="{all}";}
			$html[]=outgoing_static_row("row-parent-xtransp",
				"{default} &middot; {when_using_the_proxy_port} <strong>{$SquidTransparentInterfaceIN}:{transparent}</strong>",
				"{then} {acl_tcp_outgoing_address} <strong>$SquidTransparentInterfaceOUT</strong> {to_reach_internet}",
				"");
        }
	}

	// Regle implicite de repli, toujours active.
	$html[]=outgoing_static_row("row-parent-default","{default}",
		"{for_objects} {all} {then} {use_all_interfaces} {to_reach_internet}",
		"<span class='awc-badge awc-badge-on'>{active2}</span>");

	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='outg-pager'></span></div>";
	$html[]="</div></div>";


    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
    }
    $topbuttons[] = array($jsrestart, ico_refresh, "{apply_rules}");
    $topbuttons[] = array("LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');", ico_retweet, "{refresh}");


    $TINY_ARRAY["TITLE"]="{outgoing_address}";
    $TINY_ARRAY["ICO"]=ico_nic;
    $TINY_ARRAY["EXPL"]="{outgoing_address_explain}";
    $TINY_ARRAY["URL"]="proxy-outgoing-addr";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// Le bloc <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
	$script="<script>
	NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
	$jstiny
</script>";

	// awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .$script
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"outg-rows","outg-pager");
    return true;

}

/**
 * Une regle d'adresse sortante.
 * L'ancienne version lisait $CurrRules[$aclid] sans verifier la cle: une regle
 * absente de l'etat renvoye par le daemon -- le cas normal d'une regle jamais
 * appliquee -- emettait un avertissement PHP 8 par ligne.
 */
function outgoing_rule_row($ligne,$CurrRules,$isRights,$PowerDNSEnableClusterSlave):string{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$aclid=intval($ligne["id"]);
	$rulename=(string)$ligne["ruleName"];
	$rulenameenc=urlencode($rulename);
	$Interfaces="{ip}:".$ligne["eth"];
	$interface=(string)$ligne["interface"];
	if(strlen($interface)>2){
		$nic=new system_nic($interface);
		$Interfaces="($interface) $nic->IPADDR - $nic->NICNAME";
	}
	$js="Loadjs('$page?ruleid-js=$aclid',true);";
	$check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$aclid')");
	$up=$tpl->icon_up("Loadjs('$page?move-js=yes&aclid=$aclid&dir=up')");
	$down=$tpl->icon_down("Loadjs('$page?move-js=yes&aclid=$aclid&dir=down')");
	$delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js={$aclid}&name=$rulenameenc')");
	if(!$isRights){$up="";$down="";$delete="";}
	if($PowerDNSEnableClusterSlave==1){$check="&nbsp;";$up="";$down="";$delete="";}

	$active=!empty($CurrRules[$aclid]);
	$status=$active
		?"<span class='awc-badge awc-badge-on'>{active2}</span>"
		:"<span class='awc-badge awc-badge-off'>{inactive2}</span>";

	$explain="{for_objects} ".proxy_objects($aclid)." {then} {acl_tcp_outgoing_address} $Interfaces {to_reach_internet}";

	$h ="<tr id='row-parent-$aclid'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico".($active?"":" awc-ico-off")."'><i class='fas fa-network-wired'></i></span>";
	$h.="<div><div class='awc-name'><a href=\"javascript:blur();\" OnClick=\"$js\">".htmlspecialchars($rulename)."</a></div>";
	$h.="<div class='awc-meta'>$explain</div>";
	$h.="</div></div></td>";
	$h.="<td class='awc-nowrap'>$status</td>";
	$h.="<td class='awc-nowrap'>$check</td>";
	$h.="<td><div class='awc-acts'>$up$down$delete</div></td>";
	$h.="</tr>";
	return $h;
}

/**
 * Une regle non modifiable ici: port du proxy, port transparent, ou repli.
 * Elles etaient rendues par trois blocs presque identiques de cinq cellules,
 * chacun avec ses propres icon_nothing() de remplissage.
 */
function outgoing_static_row($rowid,$title,$explain,$status):string{
	$h ="<tr id='$rowid'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico awc-ico-off'><i class='fas fa-network-wired'></i></span>";
	$h.="<div><div class='awc-name'>$title</div>";
	$h.="<div class='awc-meta'>$explain</div>";
	$h.="</div></div></td>";
	$h.="<td class='awc-nowrap'>$status</td>";
	$h.="<td class='awc-nowrap'><span class='awc-none'>&mdash;</span></td>";
	$h.="<td>&nbsp;</td>";
	$h.="</tr>";
	return $h;
}
function isRights(){
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}

}

function proxy_objects($aclid){
	$qProxy=new mysql_squid_builder(true);
	$tablelink="outgoingaddr_sqacllinks";
    $links=acls_api_get("/proxy/acls/sqlite/links?table=$tablelink&aclid=$aclid");
    if(!is_array($links)){return "{all}";}
    $tt=array();
    foreach($links as $ligne) {
        $gpid=intval($ligne["gpid"] ?? 0);
        if($gpid==0){continue;}
        $group=acls_api_get("/proxy/acls/sqlite/group/$gpid");
        if(!is_array($group)){continue;}
        $js="Loadjs('fw.proxy.objects.php?object-js=yes&gpid=$gpid')";
        $neg_text="{is}";
        if(intval($ligne["negation"] ?? 0)==1){$neg_text="{is_not}";}
        $GroupName=htmlspecialchars($group["GroupName"] ?? "");
        $GroupType=$group["GroupType"] ?? "";
        $tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$GroupName</a> (".$qProxy->acl_GroupType[$GroupType].")";
    }

    if(count($tt)>0){
        return @implode("<br>{and} ", $tt);
    }
    return "{all}";
}

function rule_parameters():bool{ return true; }
function rule_parameters_save():bool{ return true; }
