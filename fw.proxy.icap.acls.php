<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"]);exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["IcapForwardSSL"])){IcapForwardSSL();exit;}
if(isset($_GET["icap-ssl-rule"])){icap_ssl_rule_span();exit;}
if(isset($_GET["icap-default-rule"])){icap_default_rule();exit;}
if(isset($_GET["icap-default-rule-span"])){icap_default_rule_span();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("ICAP: {policies}",
        "fa fa-shield","{web_filter_policies_explain}","$page?table-start=yes","icap-policies",
        "progress-acls1-restart",false,"table-acls1-start");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{web_filter_policies}",$html);
        echo $tpl->build_firewall();
        return;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function table_start(){
    $page=CurrentPageName();
    echo "<div id='table-acls1-rules'></div>";
    echo "<script>LoadAjax('table-acls1-rules','$page?table=yes');</script>";

}

function rule_delete_js(){
	header("content-type: application/x-javascript");
	$ID=intval($_GET["rule-delete-js"]);
	$md="acl-$ID";
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";


}
function rule_enable(){
	header("content-type: application/x-javascript");
	$ID=intval($_GET["enable-js"]);
	$row=acls_api_get("/proxy/icap/acls/$ID");
	if(!is_array($row)){echo "alert('Rule not found');";return;}
	$current=intval($row["enabled"] ?? 0);
	$enabled=($current==0)?1:0;
	if($enabled==0){
		$js="$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
	}else{
		$js="$( \"#acl-$ID\" ).removeClass( \"text-muted\" );";
	}
	list($ok,$data)=acls_api_send("PUT","/proxy/icap/acls/$ID",["enabled"=>$enabled]);
	if(!$ok){echo "alert('".addslashes($data ?? 'error')."')";return;}
	echo $js;
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-id-js"]);
	$row=acls_api_get("/proxy/icap/acls/$ID");
	$aclName=is_array($row) ? ($row["aclName"] ?? "") : "";
	$tpl->js_dialog("{rule}: $ID $aclName","$page?rule-tabs=$ID");
}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";

	$RefreshTable=base64_encode("LoadAjax('table-acls1-rules','$page?table=yes');");

	$array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=squid_icap_acls_link&RefreshTable=$RefreshTable";
	echo $tpl->tabs_default($array);

}


function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLUSTER_CLI=true;
	$ID=intval($_GET["rule-settings"]);
	$row=acls_api_get("/proxy/icap/acls/$ID");
	if(!is_array($row)){echo "{error}";return;}

	$array_access["url_rewrite_access_deny"]="{not_use_icap_engines}";
    $array_access["url_rewrite_access_allow"]="{force_using_icap_engines}";
	$explain=null;

	$rulename=utf8_encode($row["ruleName"] ?? "");
	$headervalue=base64_decode($row["headerValue"] ?? "");
	$ztype=$row["zType"] ?? "";
	$aclport=intval($row["aclPort"] ?? 0);

	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("rulename", "{rule_name}", $rulename,true);
	$form[]=$tpl->field_array_hash($array_access, "ztype", "{type}", $ztype);
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", $aclport);
	$jsafter="LoadAjax('table-acls1-rules','$page?table=yes');";
	$html=$tpl->form_outside($rulename, @implode("\n", $form),$explain,"{apply}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();

	$ID=intval($_POST["rule-save"]);
	$rulename=$tpl->CLEAN_BAD_XSS_SQL(utf8_decode($_POST["rulename"] ?? ""));
	$headervalue=base64_encode($_POST["headervalue"] ?? "");

	$body=[
		"ruleName"    => $rulename,
		"aclName"     => $rulename,
		"zType"       => $_POST["ztype"] ?? "",
		"headerName"  => $_POST["headername"] ?? "",
		"headerValue" => $headervalue,
		"aclPort"     => intval($_POST["aclport"] ?? 0),
		"enabled"     => intval($_POST["enabled"] ?? 1),
	];

	list($ok,$data)=acls_api_send("PUT","/proxy/icap/acls/$ID",$body);
	if(!$ok){echo ($data ?? "error")."\n";return;}
}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function HEADERS_ARRAY(){
	$raw=acls_api_get("/http-headers");
	$HEADERSZ=[];
	if(is_array($raw)){
		foreach($raw as $item){
			$n=$item["HeaderName"] ?? $item["headerName"] ?? "";
			if($n!==""){$HEADERSZ[$n]=$n;}
		}
	}
	return $HEADERSZ;
}

function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;

    $array_access["url_rewrite_access_deny"]="{not_use_icap_engines}";
    $array_access["url_rewrite_access_allow"]="{force_using_icap_engines}";
	$explain=null;

	$HEADERSZ=HEADERS_ARRAY();

	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
	$form[]=$tpl->field_array_hash($array_access, "ztype", "{type}", "url_rewrite_access_deny");
	$form[]=$tpl->field_proxy_ports("aclport", "{method}", 0);
	$jsafter="LoadAjax('table-acls1-rules','$page?table=yes');BootstrapDialog1.close();BootstrapDialog1.close();";

	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),$explain,"{add}",$jsafter,"AsDansGuardianAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}



function rule_move(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$ID=intval($_GET["acl-rule-move"]);
	$dir=intval($_GET["acl-rule-dir"]);
	list($ok,$data)=acls_api_send("PUT","/proxy/icap/acls/$ID/move",["dir"=>$dir]);
	if(!$ok){echo "alert('".$tpl->javascript_parse_text($data ?? 'error')."');";return;}
}

function get_current_rules():array{
    $array=array();
    $f=explode("\n",@file_get_contents("/etc/squid3/icap.conf"));
    foreach ($f as $line){
        if(!preg_match("#ICAP_ACCESSR:.*?([0-9]+)#",$line,$re)){continue;}
        $array[$re[1]]=true;
    }

    return $array;
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
    $t=time();
	$add="Loadjs('$page?newrule-js=yes',true);";


    $jsrestart=$tpl->framework_buildjs("/proxy/icap/builder",
    "squid.access.center.progress",
        "squid.access.center.progress.log","progress-acls2-restart",
        "LoadAjax('table-acls1-rules','$page?table=yes');"
    );


	$btns=$tpl->_ENGINE_parse_body("

	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
        <label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>
        <label class=\"btn btn btn-primary\" OnClick=\"LoadAjax('table-acls1-rules','$page?table=yes');\"><i class='fas fa-sync-alt'></i> {reload} </label>
     </div>");

    $html[]="<div id='progress-acls2-restart'></div>";
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th>{status}</th>";
	$html[]="<th>$order</th>";
	$html[]="<th class='awc-nowrap'>$rulename</th>";
	$html[]="<th class='awc-nowrap'>{$description}</th>";
	$html[]="<th class='center'>$enabled</th>";



	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='icapacls-rows'>";

	$jsAfter="LoadAjax('table-acls1-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

    $results=acls_api_get("/proxy/icap/acls");
    if(!is_array($results)){$results=[];}

	$TRCLASS=null;
	$rules_active=get_current_rules();

	foreach($results as $index=>$ligne) {
		$TRCLASS=null;
		$MUTED=null;
		$ID=intval($ligne["id"]);
		$rulename_val=utf8_encode($ligne["ruleName"] ?? "");
        $status="<span class='label'>{inactive2}</span>";

        if(isset($rules_active[$ID])){
            $status="<span class='label label-primary'>{active2}</span>";
        }

		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if(intval($ligne["enabled"] ?? 1)==0){$MUTED=" text-muted";}


		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");


		$html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
        $html[]="<td class=\"center\" style='width:1%' nowrap>$status</td>";
        $html[]="<td class=\"center\" style='width:1%' nowrap>".intval($ligne["xOrder"] ?? 0)."</td>";
		$html[]="<td style='vertical-align:middle' style='width:1%' nowrap>". $tpl->td_href($rulename_val,"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
		$html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')")."</td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";

	}
    $icap_default_rule=1;
    $ICAPNoTrustWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ICAPNoTrustWhiteLists"));
    if($ICAPNoTrustWhiteLists==1){$icap_default_rule=0;}
    $icap_default_rule_span=icap_default_rule_span(true);
    $icap_default_rule_enable=$tpl->icon_check($icap_default_rule,"Loadjs('$page?icap-default-rule=yes')");
    $TRCLASS=null;


    $IcapForwardSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IcapForwardSSL"));
    $IcapForwardSSL_enable=$tpl->icon_check($IcapForwardSSL,"Loadjs('$page?IcapForwardSSL=yes')");
    $icap_ssl_rule_span=icap_ssl_rule_span(true);
    //LoadAjaxSilent('icap-ssl-rule','$page?icap-ssl-rule=yes');

    $html[]="<tr class='$TRCLASS{$MUTED}' id='acl-ssl-forward'>";
    $html[]="<td class=\"center\" style='width:1%' nowrap><span class='label label-primary'>{active2}</span></td>";
    $html[]="<td class=\"center\" style='width:1%' nowrap>0</td>";
    $html[]="<td style='vertical-align:middle' style='width:1%' nowrap>{check_SSL_protocol}</td>";
    $html[]="<td style='vertical-align:middle'><span id='icap-ssl-rule'>$icap_ssl_rule_span</span></td>";
    $html[]="<td class='center' style='width:1%' nowrap>$IcapForwardSSL_enable</td>";
    $html[]="<td style='vertical-align:middle'style='width:1%;' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle'style='width:1%;' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";

    $TRCLASS=null;
    $html[]="<tr class='$TRCLASS{$MUTED}' id='acl-default-rule'>";
    $html[]="<td class=\"center\" style='width:1%' nowrap><span class='label label-primary'>{active2}</span></td>";
    $html[]="<td class=\"center\" style='width:1%' nowrap>0</td>";
    $html[]="<td style='vertical-align:middle' style='width:1%' nowrap>{finally}</td>";
    $html[]="<td style='vertical-align:middle'><span id='icap-default-rule'>$icap_default_rule_span</span></td>";
    $html[]="<td class='center' style='width:1%' nowrap>$icap_default_rule_enable</td>";
    $html[]="<td style='vertical-align:middle'style='width:1%;' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="<td style='vertical-align:middle'style='width:1%;' nowrap class='center'>".$tpl->icon_nothing()."</td>";
    $html[]="</tr>";



	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='icapacls-pager'></span></div>";
	$html[]="</div></div>";



    $TINY_ARRAY["TITLE"]="ICAP: {policies}";
    $TINY_ARRAY["ICO"]="fa fa-shield";
    $TINY_ARRAY["EXPL"]="{web_filter_policies_explain}";
    $TINY_ARRAY["URL"]="icap-policies";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
function RefreshIcapDefaultRule(){
    $jstiny
}
setTimeout(\"RefreshIcapDefaultRule()\",1000);
</script>";

	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"icapacls-rows","icapacls-pager");
}

function IcapForwardSSL(){
    $page=CurrentPageName();
    $IcapForwardSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IcapForwardSSL"));
    if($IcapForwardSSL==1){
        admin_tracks("Disable forward SSL protocol to ICAP services");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IcapForwardSSL",0);
        echo "LoadAjaxSilent('icap-ssl-rule','$page?icap-ssl-rule=yes');";
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("/proxy/icap/builder");
        return true;
    }
    admin_tracks("Enable forward SSL protocol to ICAP services");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IcapForwardSSL",1);
    echo "LoadAjaxSilent('icap-ssl-rule','$page?icap-ssl-rule=yes');";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("/proxy/icap/builder");
    return true;

}

function icap_default_rule(){
    $page=CurrentPageName();
    $ICAPNoTrustWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ICAPNoTrustWhiteLists"));
    if($ICAPNoTrustWhiteLists==1){
        admin_tracks("Use global whitelists bypass ICAP services");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ICAPNoTrustWhiteLists",0);
        echo "LoadAjaxSilent('icap-default-rule','$page?icap-default-rule-span=yes');";
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("/proxy/icap/builder");
        return true;
    }
    admin_tracks("Disable the use of global whitelists for ICAP services");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ICAPNoTrustWhiteLists",1);
    echo "LoadAjaxSilent('icap-default-rule','$page?icap-default-rule-span=yes');";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("/proxy/icap/builder");
    return true;

}

function icap_ssl_rule_span($return=false):string{
    $tpl=new template_admin();
    $IcapForwardSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IcapForwardSSL"));
    if($IcapForwardSSL==1) {
        $text = "{when_connecting_to_method} {all_methods} {and} {for_everyone} {and} <strong>{ssl_protocol}</strong> {then} <strong>{force_using_icap_engines}</strong>";
    }else{
        $text = "{when_connecting_to_method} {all_methods} {and} {for_everyone} {and} <strong>{ssl_protocol}</strong> {then} <strong>{not_use_icap_engines}</strong>";

    }
    if($return){return $tpl->_ENGINE_parse_body($text);}
    echo $tpl->_ENGINE_parse_body($text);
    return "";
}



function icap_default_rule_span($return=false):string{
    $tpl=new template_admin();
    $expect_whitelist="(".$tpl->td_href("{expect_whitelist}",null,"s_PopUpFull('/proxy-whitelists','1024','900');").")";


    $ICAPNoTrustWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ICAPNoTrustWhiteLists"));
    if($ICAPNoTrustWhiteLists==1){
        $expect_whitelist=null;
    }


    $text="{when_connecting_to_method} {all_methods} {and} {for_everyone} $expect_whitelist {then} <strong>{force_using_icap_engines}</strong>";
    if($return){return $tpl->_ENGINE_parse_body($text);}
    echo $tpl->_ENGINE_parse_body($text);
    return "";
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();

	$rulename=$tpl->CLEAN_BAD_XSS_SQL(utf8_decode($_POST["rulename"] ?? ""));
	$headervalue=base64_encode($_POST["headervalue"] ?? "");

	list($ok,$data)=acls_api_send("POST","/proxy/icap/acls",[
		"ruleName"    => $rulename,
		"aclName"     => $rulename,
		"zType"       => $_POST["ztype"] ?? "",
		"headerName"  => $_POST["headername"] ?? "",
		"headerValue" => $headervalue,
		"aclPort"     => intval($_POST["aclport"] ?? 0),
	]);
	if(!$ok){echo $data ?? "error";return;}
}

function rule_delete($ID){
	list($ok,$data)=acls_api_send("DELETE","/proxy/icap/acls/$ID");
	if(!$ok){echo "alert('".addslashes($data ?? 'error')."');";return false;}
	return true;

}
function EXPLAIN_THIS_RULE($ID){

	$array_access["url_rewrite_access_deny"]="{not_use_icap_engines}";
	$array_access["url_rewrite_access_allow"]="{force_using_icap_engines}";

	$acls=new squid_acls_groups();
	$ligne=acls_api_get("/proxy/icap/acls/$ID");
	if(!is_array($ligne)){return "";}
	$aclport=intval($ligne["aclPort"] ?? 0);
	$ztype=$ligne["zType"] ?? "";

	$PORTS_DIRECTIONS=$acls->PORTS_DIRECTIONS();
	$method[]="{when_connecting_to_method} &laquo;{$PORTS_DIRECTIONS[$aclport]}&raquo;, ";
	$objects=$acls->getobjectsNameFromAclrule(intval($ID),"black","squid_icap_acls_link");
	if(count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}
    if(count($objects)==0){$method[]="{for_everyone}";}
	$method[]="{then} <strong>{$array_access[$ztype]}</strong>";


    $FINAL=@implode(" ", $method);

    $tpl=new template_admin();
    $page=CurrentPageName();
    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$FINAL</span>");
}
