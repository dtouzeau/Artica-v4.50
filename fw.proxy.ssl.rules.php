<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// Verrou Artica Meta (politique sslproxy) : regles Meta et reglages tenus en lecture seule
include_once(dirname(__FILE__)."/ressources/class.sslproxy.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["default-js"])){default_js();exit;}
if(isset($_GET["default-popup"])){default_popup();exit;}
if(isset($_POST["ProxyDefaultUncryptSSL"])){ProxyDefaultUncryptSSL_save();exit;}
if(isset($_GET["filltable"])){filltable();exit;}
if(isset($_GET["AllowSquidWhitelistsSSL-js"])){AllowSquidWhitelistsSSL_js();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();



	if(isset($_GET["main-page"])){

            $html=$tpl->page_header("{ssl_protocol}&nbsp;&raquo;&nbsp;{PROXY_ACLS}"
                ,"fas fa-file-certificate","{PROXY_ACLS_EXPLAIN}","$page?table=yes","proxy-ssl-rules",
                "progress-acls-restart",false,"table-acls-ssl-rules");

    }else{
        $html="<div class='row'><div id='progress-acls-restart'></div>
                <div class='ibox-content'>
                    <div id='table-acls-ssl-rules'></div>
                </div>
    </div><script>
        LoadAjax('table-acls-ssl-rules','$page?table=yes');
    </script>";


    }




	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function filltable(){
    $ACCESSEnabled=0;
    $tpl=new template_admin();
    $uncrypt_ssl=$tpl->javascript_parse_text("{uncrypt_websites}");
    $trust_ssl=$tpl->javascript_parse_text("{trust_ssl}");
    $ID=intval($_GET["filltable"]);

    $data = acls_api_get("/proxy/ssl-rules/$ID");
    if(!is_array($data)){
        header("content-type: application/x-javascript");
        echo "// ssl-rules get failed for ID=$ID\n";
        return;
    }
    $crypt   = intval($data["crypt"] ?? 0);
    $enabled = intval($data["enabled"] ?? 0);

    if($crypt==1 OR intval($data["trust"] ?? 0)==1){$ACCESSEnabled=1;}
    $squid_acls_groups=new squid_acls_groups();
    $objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"sslrules_sqacllinks");
    $and_text=$tpl->javascript_parse_text(" {and} ");




    if($crypt==1){
        $uncrypt_ssl_text=$uncrypt_ssl;

        $ACCESSEnabled=1;
        $TTEXT[]="<strong>$uncrypt_ssl_text</strong>";
    }else{

        $TTEXT[]="<strong>{do_not_encrypt_websites}</strong>";
    }

    if(intval($data["trust"] ?? 0)==1){
        $TTEXT[]="<strong>$trust_ssl</strong>";
        $ACCESSEnabled=1;
    }

    $TTEXT=array();
    $please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");

    if(count($objects)>0) {
        $explain=$squid_acls_groups->ACL_MULTIPLE_EXPLAIN($ID,$ACCESSEnabled,0,"sslrules_sqacllinks")." {then} ".@implode($and_text, $TTEXT);

    }else{
        $explain="<div class=text-danger'>$please_specify_an_object</div>";
    }
    $img=$tpl->_ENGINE_parse_body(icon_status($crypt,$enabled,$objects));
    $explain=$tpl->_ENGINE_parse_body($explain);
    header("content-type: application/x-javascript");
    echo "document.getElementById('ssl-rule-icon-$ID').innerHTML=\"$img\";\n";
    echo "document.getElementById('ssl-rule-text-$ID').innerHTML=\"$explain\";\n";
}

function rule_delete_js(){
	header("content-type: application/x-javascript");
	$ID=intval($_GET["rule-delete-js"]);
	$md="acl-$ID";
	if(sslproxy_meta_row("ssl_rules",$ID)){echo "alert(".json_encode(sslproxy_meta_text()).");";return;}
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";


}
function rule_enable(){
    $page=CurrentPageName();
	$tpl=new template_admin();
    $ID=intval($_GET["enable-js"]);
    if(sslproxy_meta_row("ssl_rules",$ID)){sslproxy_meta_refuse_js();return;}

    $data = acls_api_get("/proxy/ssl-rules/$ID");
    if(!is_array($data)){
        header("content-type: application/x-javascript");
        echo "// ssl-rules get failed for ID=$ID\n";
        return;
    }
    $enabled_src = intval($data["enabled"] ?? 0);
    $enabled = ($enabled_src == 0) ? 1 : 0;

    if($enabled_src==0){
        $js="$( \"#acl-$ID\" ).removeClass( \"text-muted\" );";
    }else{
        $js="$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
    }

    list($ok, $err) = acls_api_send("PUT", "/proxy/ssl-rules/$ID", ["enabled" => $enabled]);
    if(!$ok){
        header("content-type: application/x-javascript");
        echo "alert('".$tpl->javascript_parse_text($err)."');\n";
        return;
    }

    header("content-type: application/x-javascript");
	echo "// ID = $ID, src=$enabled_src, enabled =$enabled\n";
	echo $js."\n";
	echo "Loadjs('$page?filltable=$ID');\n";
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval(trim($_GET["rule-id-js"]));

    $data = acls_api_get("/proxy/acls/sqlite/rule/$ID");
    $aclname = is_array($data) ? ($data["aclname"] ?? "") : "";
	$tpl->js_dialog("{rule}: $ID $aclname","$page?rule-tabs=$ID");
}
function default_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{default}","$page?default-popup=yes",650);

}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";
	$RefreshTable=base64_encode("LoadAjax('table-acls-ssl-rules','$page?table=yes');");
	$array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=sslrules_sqacllinks&RefreshTable=$RefreshTable";
	echo $tpl->tabs_default($array);

}
function default_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ProxyDefaultUncryptSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDefaultUncryptSSL"));
    $jsafter="LoadAjax('table-acls-ssl-rules','$page?table=yes');dialogInstance2.close();";
    if(sslproxy_meta_key_held("ProxyDefaultUncryptSSL")){
        $state=$ProxyDefaultUncryptSSL==1?"{uncrypt_ssl}":"{do_not_encrypt_websites}";
        echo $tpl->_ENGINE_parse_body(sslproxy_meta_banner($tpl,"","{sslproxy_meta_settings_explain}")
            ."<p><strong>{default_rule2}:</strong> $state</p>");
        return true;
    }


    echo $tpl->_ENGINE_parse_body($tpl->BigCircleCheckbox(
        "ProxyDefaultUncryptSSL|default:yes",
        "{default_rule2}: {uncrypt_ssl}","{uncrypt_ssl_explain}",$ProxyDefaultUncryptSSL,$jsafter));
    return true;

}
function ProxyDefaultUncryptSSL_save():bool{
    if(sslproxy_meta_key_held("ProxyDefaultUncryptSSL")){return sslproxy_meta_refuse_text();}
    $sock=new sockets();
    $sock->SET_INFO("ProxyDefaultUncryptSSL",$_POST["ProxyDefaultUncryptSSL"]);
    return admin_tracks("Proxy: Set to uncrypt default ssl sites to {$_POST["ProxyDefaultUncryptSSL"]}");
}

function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-settings"]);

    $data = acls_api_get("/proxy/ssl-rules/$ID");
    if(!is_array($data)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

	$metaBanner="";
	if(sslproxy_meta_row("ssl_rules",$ID)){
		sslproxy_meta_lock_form($tpl);
		$metaBanner="<div class='alert alert-info' style='margin:10px 0'><i class='fa-solid fa-lock'></i>&nbsp;{sslproxy_meta_row_locked}</div>";
	}
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",intval($data["enabled"] ?? 0),true);
	$form[]=$tpl->field_checkbox("crypt","{uncrypt_ssl}",intval($data["crypt"] ?? 0),false,"{uncrypt_ssl_explain}");
	$form[]=$tpl->field_checkbox("trust","{trust_ssl}",intval($data["trust"] ?? 0),false,"{trust_ssl_explain}");
	$form[]=$tpl->field_text("description", "{rule_name}", $data["description"] ?? "",true);
	$jsafter="LoadAjax('table-acls-ssl-rules','$page?table=yes');";

	$html=$metaBanner.$tpl->form_outside("{rule} ".htmlspecialchars($data["description"] ?? ""), @implode("\n", $form),null,"{apply}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
	$ID=intval($_POST["ID"]);
	unset($_POST["ID"]);
	if(sslproxy_meta_row("ssl_rules",$ID)){sslproxy_meta_refuse_post();return;}

    $body = [];
    $body["description"] = url_decode_special_tool($_POST["description"] ?? "");
    $body["enabled"]     = intval($_POST["enabled"] ?? 0);
    $body["crypt"]       = intval($_POST["crypt"] ?? 0);
    $body["trust"]       = intval($_POST["trust"] ?? 0);

    list($ok, $err) = acls_api_send("PUT", "/proxy/ssl-rules/$ID", $body);
    if(!$ok){ echo htmlspecialchars($err); }
}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}
function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();


	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_hidden("enabled","1");

	$form[]=$tpl->field_checkbox("crypt","{uncrypt_ssl}",1,false,"{uncrypt_ssl_explain}");
	$form[]=$tpl->field_checkbox("trust","{trust_ssl}",0,false,"{trust_ssl_explain}");
	$form[]=$tpl->field_text("description", "{rule_name}", null,true);
	$jsafter="LoadAjax('table-acls-ssl-rules','$page?table=yes');BootstrapDialog1.close();";

	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),null,"{add}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}



function rule_move(){
	$tpl=new template_admin();
	$ID=intval($_GET["acl-rule-move"]);
	$dir=intval($_GET["acl-rule-dir"]);
    if(sslproxy_meta_row("ssl_rules",$ID)){sslproxy_meta_refuse_js();return;}

    list($ok, $err) = acls_api_send("PUT", "/proxy/ssl-rules/$ID/move", ["dir" => $dir]);
    if(!$ok){
        header("content-type: application/x-javascript");
        echo "alert('".$tpl->javascript_parse_text($err)."');";
        return;
    }
    if($GLOBALS["VERBOSE"]){ echo "// rule_move ID=$ID dir=$dir OK\n"; }
}
function AllowSquidWhitelistsSSL_js():bool{
    if(sslproxy_meta_key_held("AllowSquidWhitelistsSSL")){return sslproxy_meta_refuse_js();}
    $AllowSquidWhitelistsSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidWhitelistsSSL"));
    if($AllowSquidWhitelistsSSL==0) {
        $AllowSquidWhitelistsSSL=1;
    }else{
        $AllowSquidWhitelistsSSL=0;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowSquidWhitelistsSSL",$AllowSquidWhitelistsSSL);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ssl/build");
    header("content-type: application/x-javascript");
    return admin_tracks("Set SSL decrypt Whitelist Rules to $AllowSquidWhitelistsSSL");
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$add="Loadjs('$page?newrule-js=yes',true);";

    $jsrestart=$tpl->framework_buildjs(
        "/proxy/ssl/build",
        "squid.ssl.rules.articarest.progress",
        "squid.ssl.rules.progress.log",
        "progress-acls-restart",
    "LoadAjax('table-acls-ssl-rules','$page?table=yes');");


	$html[]=sslproxy_meta_banner($tpl,"ssl_rules");
	$html[]=$tpl->_ENGINE_parse_body("
	<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>");
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th>{status}</th>";
	$html[]="<th>{type}</th>";
	$html[]="<th class='awc-nowrap'>{rulename}</th>";
	$html[]="<th>{description}</th>";
	$html[]="<th class='center'>{enabled}</th>";
    $html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='sslrules-rows'>";

	$squid_acls_groups=new squid_acls_groups();

    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ssl-rules");
    $resp = json_decode($raw, true);
    $results = (is_array($resp) && !empty($resp["success"]) && is_array($resp["data"])) ? $resp["data"] : [];

	$TRCLASS=null;
	$acls=new squid_acls_groups();
	$uncrypt_ssl=$tpl->javascript_parse_text("{uncrypt_websites}");
	$pass_ssl=$tpl->javascript_parse_text("{pass_connect_ssl}");
	$trust_ssl=$tpl->javascript_parse_text("{trust_ssl}");
	$and_text=$tpl->javascript_parse_text(" {and} ");

    $DEFAULTRULE=0;
    $DEFAULTRULEF=false;
	$sslconf=explode("\n",@file_get_contents("/etc/squid3/ssl.bump.conf"));
    $PRODS=array();
	foreach ($sslconf as $line){
	    $line=trim($line);
	    if($line==null){continue;}
        if(preg_match("#ProxyDefaultUncryptSSL:.*?([0-9])#",$line,$re)){
            $DEFAULTRULEF=true;
            $DEFAULTRULE=intval($re[1]);
        }
	    if(!preg_match("#id:([0-9]+)#",$line,$re)){continue;}
	    $PRODS[$re[1]]=true;
    }

	$td1=$tpl->table_td1prc();
    $TRCLASS=null;
    $AllowSquidWhitelistsSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidWhitelistsSSL"));
    $inprod="<span class='label label-primary'>{active2}</span>";
    $ssl_img="<span class='label'>{do_not_encrypt_websites}</span>";

    if($AllowSquidWhitelistsSSL==0) {
        $inprod = "<span class='label'>{inactive2}</span>";
    }
    $html[]="<tr class='$TRCLASS' id='acl-a1'>";
    $html[]="<td $td1>$inprod</td>";
    $html[]="<td $td1><span id='ssl-rule-icon-a1'>$ssl_img</span></td>";
    $html[]="<td style='text-align:left' nowrap>{whitelist}</td>";
    $html[]="<td style='vertical-align:middle'><span id='ssl-rule-text-a1'>{AllowSquidWhitelistsSSL}</span></td>";
    $html[]="<td $td1>".$tpl->icon_check($AllowSquidWhitelistsSSL,"Loadjs('$page?AllowSquidWhitelistsSSL-js=yes')",null,sslproxy_meta_sec(sslproxy_meta_key_held("AllowSquidWhitelistsSSL")))."</td>";
    $html[]="<td $td1></center></td>";
    $html[]="<td $td1></td>";
    $html[]="</tr>";



	foreach($results as $index=>$ligne) {

		$TRCLASS=null;
		$ACCESSEnabled=0;
		$crypt    = intval($ligne["crypt"] ?? 0);
		$enabled  = intval($ligne["enabled"] ?? 0);
		$ID       = intval($ligne["id"] ?? 0);
		$rulename = $tpl->utf8_encode($ligne["description"] ?? "");
		$please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");
        $inprod="<span class='label'>{inactive2}</span>";

        if(isset($PRODS[$ID])){
            $inprod="<span class='label label-primary'>{active2}</span>";
        }

		$TTEXT=array();
		if($crypt==1){
			$uncrypt_ssl_text=$uncrypt_ssl;

			$ACCESSEnabled=1;
			$TTEXT[]="<strong>$uncrypt_ssl_text</strong>";
		}else{

			$TTEXT[]="<strong>{do_not_encrypt_websites}</strong>";
		}

		if(intval($ligne["trust"] ?? 0)==1){
			$TTEXT[]="<strong>$trust_ssl</strong>";
			$ACCESSEnabled=1;
		}


		$objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"sslrules_sqacllinks");

		if(count($objects)>0){
			$explain=$acls->ACL_MULTIPLE_EXPLAIN($ID,$ACCESSEnabled,0,"sslrules_sqacllinks")." {then} ".@implode($and_text, $TTEXT);
        }else{

			$explain="<div class=text-danger'>$please_specify_an_object</div>";
		}

		$ssl_img=icon_status($crypt,$enabled,$objects);
        if($ssl_img==null){$ssl_img=$tpl->icon_nothing();}


		$isMeta=sslproxy_meta_row("ssl_rules",$ID);
		$wsec=sslproxy_meta_sec($isMeta);
		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')",$wsec);
		$js="Loadjs('$page?rule-id-js=$ID')";
	    $up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');",$wsec);
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');",$wsec);
        $rulename=$tpl->utf8_decode($rulename);

		$html[]="<tr class='$TRCLASS' id='acl-$ID'>";
		$html[]="<td $td1>$inprod</td>";
		$html[]="<td $td1><span id='ssl-rule-icon-$ID'>$ssl_img</span></td>";
		$html[]="<td style='text-align:left' nowrap>". $tpl->td_href($rulename,$isMeta?"{view}":"{click_to_edit}",$js).sslproxy_meta_badge("ssl_rules",$ID)."</td>";
		$html[]="<td style='vertical-align:middle'><span id='ssl-rule-text-$ID'>$explain</span></td>";
		$html[]="<td $td1>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')",null,$wsec)."</td>";
		$html[]="<td $td1>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td $td1>$delete</td>";
		$html[]="</tr>";
	}


    $ProxyDefaultUncryptSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDefaultUncryptSSL"));
    $labelDefault="{default}";
	$labelPrimary="label-primary";
    if(!$DEFAULTRULEF){
        $labelPrimary="label-default";
        $labelDefault="{inactive}";
    }else{
        if($DEFAULTRULE<>$ProxyDefaultUncryptSSL){
            $labelPrimary="label-warning";
            $labelDefault="{not_saved}";
        }
    }
	$ssl_img="<span class='label'>&nbsp;&nbsp;&nbsp;&nbsp;{do_nothing}&nbsp;&nbsp;&nbsp;&nbsp;</span>";
	$explain="{for_everyone} {then} {do_not_encrypt_websites}";

    if($ProxyDefaultUncryptSSL==1){
        $ssl_img="<span class='label label-primary'>{uncrypt_ssl}</span>";
        $explain="{for_everyone} {then} {uncrypt_websites}";
    }


	$TRCLASS=null;
	$html[]="<tr style='vertical-align:middle' class='$TRCLASS' id='acl-0'>";
    $html[]="    <td $td1><span class='label $labelPrimary'>$labelDefault</span></td>";
	$html[]="    <td $td1>$ssl_img</td>";
	$html[]="    <td>".$tpl->td_href("{default}",null,"Loadjs('$page?default-js=yes')")."</td>";
	$html[]="    <td style='vertical-align:middle'>$explain</td>";
	$html[]="    <td $td1>".$tpl->icon_nothing()."</td>";
	$html[]="    <td $td1>".$tpl->icon_nothing()."</td>";
	$html[]="    <td $td1>".$tpl->icon_nothing()."</td>";
	$html[]="</tr>";

	$html[]="</tbody>";
	$html[]="";
	$html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='sslrules-pager'></span></div></div></div>";

    $btn="
	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
        <label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>
     </div>	";


    $TINY_ARRAY["TITLE"]="{ssl_protocol}&nbsp;&raquo;&nbsp;{PROXY_ACLS}";
    $TINY_ARRAY["ICO"]="fas fa-file-certificate";
    $TINY_ARRAY["EXPL"]="{PROXY_ACLS_EXPLAIN}";
    $TINY_ARRAY["URL"]="proxy-ssl-rules";
    $TINY_ARRAY["BUTTONS"]=$btn;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



	$html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$jstiny
</script>";

	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"sslrules-rows","sslrules-pager");
}

function icon_status($crypt=0,$enabled=0,$objects=array()){

    if($crypt==1){

        $ssl_img="<span class='label label-primary'>{uncrypt_websites}</span>";

    }else{
        $ssl_img="<span class='label'>{do_not_encrypt_websites}</span>";

    }

    if(count($objects)==0){
        $ssl_img="<div class='label label-danger'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{error}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>";
    }


    if($enabled==0){
          $ssl_img="<span class='label'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{disabled}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
    }

    return $ssl_img;

}

function new_rule_save(){
	unset($_POST["newrule"]);
    $body = [];
    $body["description"] = url_decode_special_tool($_POST["description"] ?? "");
    $body["enabled"]     = intval($_POST["enabled"] ?? 1);
    $body["crypt"]       = intval($_POST["crypt"] ?? 0);
    $body["trust"]       = intval($_POST["trust"] ?? 0);

    list($ok, $err) = acls_api_send("POST", "/proxy/ssl-rules", $body);
    if(!$ok){ echo htmlspecialchars($err); }
}

function rule_delete($ID){
    $ID=intval($ID);
    list($ok, $err) = acls_api_send("DELETE", "/proxy/ssl-rules/$ID");
    return $ok;
}
