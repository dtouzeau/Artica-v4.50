<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// Verrou Artica Meta (politique sslproxy) : regles Meta en lecture seule
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
if(isset($_GET["filltable"])){filltable();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();



	if(isset($_GET["main-page"])){

            $html=$tpl->page_header("{ssl_protocol}&nbsp;&raquo;&nbsp;{PROXY_ACLS}"
                ,"fas fa-file-certificate","{PROXY_ACLS_EXPLAIN}","$page?table=yes","proxy-ssl-rules",
                "progress-acls-restart",false,"table-acls-sslErrs-rules");

    }else{
        $html="<div class='row'><div id='progress-acls-restart'></div>
                <div class='ibox-content'>
                    <div id='table-acls-sslErrs-rules'></div>
                </div>
    </div><script>
        LoadAjax('table-acls-sslErrs-rules','$page?table=yes');
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
    $ligne=acls_api_get("/proxy/cert-errors/$ID");
    if(!is_array($ligne)){
        header("content-type: application/x-javascript");
        echo "// cert-error $ID not found\n";
        return;
    }
    $crypt=intval($ligne["crypt"] ?? 0);
    $enabled=intval($ligne["enabled"] ?? 0);
    if($crypt==1 OR (intval($ligne["trust"] ?? 0)==1)){$ACCESSEnabled=1;}
    $squid_acls_groups=new squid_acls_groups();
    $objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"sslproxy_cert_error_sqacllinks");
    $and_text=$tpl->javascript_parse_text(" {and} ");




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

    $TTEXT=array();
    $please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");

    if(count($objects)>0) {
        $explain=$squid_acls_groups->ACL_MULTIPLE_EXPLAIN($ID,$ACCESSEnabled,0,"sslproxy_cert_error_sqacllinks")." {then} ".@implode($and_text, $TTEXT);

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
	if(sslproxy_meta_row("sslproxy_cert_error",$ID)){echo "alert(".json_encode(sslproxy_meta_text()).");";return;}
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";


}
function rule_enable(){
    $page=CurrentPageName();
	$tpl=new template_admin();
    $ID=intval($_GET["enable-js"]);
    if(sslproxy_meta_row("sslproxy_cert_error",$ID)){sslproxy_meta_refuse_js();return;}
    $ligne=acls_api_get("/proxy/cert-errors/$ID");
    if(!is_array($ligne)){
        header("content-type: application/x-javascript");
        echo "// cert-error $ID not found\n";
        return;
    }
	$enabled_src=intval($ligne["enabled"] ?? 0);
	if($enabled_src==0){
		$js="$( \"#acl-{$ID}\" ).removeClass( \"text-muted\" );";
		$enabled=1;
	}else{
	    $js="$( \"#acl-{$ID}\" ).addClass( \"text-muted\" );";
		$enabled=0;
	}

	list($ok,$err)=acls_api_send("PUT","/proxy/cert-errors/$ID",["enabled"=>$enabled]);
	if(!$ok){
        header("content-type: application/x-javascript");
        echo "alert('".$tpl->javascript_parse_text($err)."');";
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
	$rule=acls_api_get("/proxy/acls/sqlite/rule/$ID");
	$aclname=is_array($rule) ? ($rule["aclname"] ?? "") : "";
	$tpl->js_dialog("{rule}: $ID $aclname","$page?rule-tabs=$ID");
}
function default_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{default}","$page?default-popup=yes");

}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";
	$RefreshTable=base64_encode("LoadAjax('table-acls-sslErrs-rules','$page?table=yes');");
	$array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=sslproxy_cert_error_sqacllinks&RefreshTable=$RefreshTable";
	echo $tpl->tabs_default($array);

}



function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-settings"]);
	$ligne=acls_api_get("/proxy/cert-errors/$ID");
	if(!is_array($ligne)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }


	$metaBanner="";
	if(sslproxy_meta_row("sslproxy_cert_error",$ID)){
		sslproxy_meta_lock_form($tpl);
		$metaBanner="<div class='alert alert-info' style='margin:10px 0'><i class='fa-solid fa-lock'></i>&nbsp;{sslproxy_meta_row_locked}</div>";
	}
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"] ?? 0,true);
	$form[]=$tpl->field_text("description", "{rule_name}", $ligne["description"] ?? "",true);
    $Types[1]="{sslproxy_cert_error_deny_explain}";
    $Types[2]="{sslproxy_cert_error_allow_explain}";
    $form[]=$tpl->field_array_checkboxes2Columns($Types, "ruleType",$ligne["ruleType"] ?? 1);

	$jsafter="LoadAjax('table-acls-sslErrs-rules','$page?table=yes');";

	$html=$metaBanner.$tpl->form_outside("{rule} ".htmlspecialchars($ligne["description"] ?? ""), @implode("\n", $form),null,"{apply}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
	$ID=intval($_POST["ID"]);
	unset($_POST["ID"]);
	if(sslproxy_meta_row("sslproxy_cert_error",$ID)){sslproxy_meta_refuse_post();return;}
    $payload=array();
    if(isset($_POST["description"])){
        $payload["description"]=url_decode_special_tool($_POST["description"]);
    }
    if(isset($_POST["enabled"])){
        $payload["enabled"]=intval($_POST["enabled"]);
    }
    if(isset($_POST["ruleType"])){
        $payload["ruleType"]=intval($_POST["ruleType"]);
    }
	list($ok,$err)=acls_api_send("PUT","/proxy/cert-errors/$ID",$payload);
	if(!$ok){echo htmlspecialchars($err);}
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

    $Types[1]="{sslproxy_cert_error_deny_explain}";
    $Types[2]="{sslproxy_cert_error_allow_explain}";

	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_hidden("enabled","1");
    $form[]=$tpl->field_text("description", "{rule_name}", null,true);
    $form[]=$tpl->field_array_checkboxes2Columns($Types, "ruleType", 2);
    $jsafter="LoadAjax('table-acls-sslErrs-rules','$page?table=yes');BootstrapDialog1.close();";

	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),null,"{add}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}



function rule_move(){
	$tpl=new template_admin();
	$ID=intval($_GET["acl-rule-move"]);
	if(sslproxy_meta_row("sslproxy_cert_error",$ID)){sslproxy_meta_refuse_js();return;}
	$dir=intval($_GET["acl-rule-dir"]);
	list($ok,$err)=acls_api_send("PUT","/proxy/cert-errors/$ID/move",["dir"=>$dir]);
	if(!$ok){
        header("content-type: application/x-javascript");
        echo "alert('".$tpl->javascript_parse_text($err)."');";
    }
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
    "LoadAjax('table-acls-sslErrs-rules','$page?table=yes');");


	$html[]=sslproxy_meta_banner($tpl,"sslproxy_cert_error");
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
	$html[]="<tbody id='certerr-rows'>";

	$squid_acls_groups=new squid_acls_groups();

	$results=acls_api_get("/proxy/cert-errors");
	if(!is_array($results)){
        $html[]="<tr><td colspan='8' class='awc-empty'>".$tpl->_ENGINE_parse_body("{error}")."</td></tr>";
        // Sortie anticipee: elle doit fermer AUSSI les div du conteneur awc.
        $html[]="</tbody></table></div></div></div>";
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return;
    }
	$TRCLASS=null;
	$acls=new squid_acls_groups();
	$and_text=$tpl->javascript_parse_text(" {and} ");

	$sslconf=explode("\n",@file_get_contents("/etc/squid3/ssl.bump.conf"));
    $PRODS=array();
	foreach ($sslconf as $line){
	    $line=trim($line);
	    if($line==null){continue;}
	    if(!preg_match("#SSLPROXY_CERT_ID:([0-9]+)#",$line,$re)){continue;}
	    $PRODS[$re[1]]=true;
    }

	$td1=$tpl->table_td1prc();

	foreach($results as $index=>$ligne) {

		$TRCLASS=null;
		$ACCESSEnabled=0;
		$ruletype=intval($ligne["ruleType"] ?? 1);
		$enabled=intval($ligne["enabled"] ?? 0);
		$ID=intval($ligne["id"] ?? 0);
		$rulename=$tpl->utf8_encode($ligne["description"] ?? "");
		$please_specify_an_object=$tpl->_ENGINE_parse_body("{please_specify_an_object}");
        $inprod="<span class='label'>{inactive2}</span>";

        if(isset($PRODS[$ID])){
            $inprod="<span class='label label-primary'>{active2}</span>";
        }

		$TTEXT=array();
		if($ruletype==2){
			$ACCESSEnabled=1;
			$TTEXT[]="<strong>{trust_ssl}</strong>";
		}else{
			$TTEXT[]="<strong>{deny_access}</strong>";
		}

		$objects=$squid_acls_groups->getobjectsNameFromAclrule($ID,null,"sslproxy_cert_error_sqacllinks");
        VERBOSE("Objects: ".count($objects),__LINE__);

		if(count($objects)>0){
			$explain=$acls->ACL_MULTIPLE_EXPLAIN($ID,$ACCESSEnabled,0,"sslproxy_cert_error_sqacllinks")." {then} ".@implode($and_text, $TTEXT);
        }else{
            VERBOSE("Objects: NO_OBJECT",__LINE__);
			$explain="<div class=text-danger'>$please_specify_an_object</div>";
		}

		$ssl_img=icon_status($ruletype,$enabled,$objects);
        if($ssl_img==null){$ssl_img=$tpl->icon_nothing();}


		$isMeta=sslproxy_meta_row("sslproxy_cert_error",$ID);
		$wsec=sslproxy_meta_sec($isMeta);
		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')",$wsec);
		$js="Loadjs('$page?rule-id-js=$ID')";

        $up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');",$wsec);
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');",$wsec);
        $rulename=$tpl->utf8_decode($rulename);

		$html[]="<tr class='$TRCLASS' id='acl-$ID'>";
		$html[]="<td $td1>$inprod</td>";
		$html[]="<td $td1><span id='ssl-rule-icon-$ID'>{$ssl_img}</span></td>";
		$html[]="<td style='text-align:left' nowrap>". $tpl->td_href($rulename,$isMeta?"{view}":"{click_to_edit}",$js).sslproxy_meta_badge("sslproxy_cert_error",$ID)."</td>";
		$html[]="<td style='vertical-align:middle'><span id='ssl-rule-text-$ID'>$explain</span></td>";
		$html[]="<td $td1>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')",null,$wsec)."</td>";
		$html[]="<td $td1>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td $td1>$delete</td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
		$html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='certerr-pager'></span></div></div></div>";


    //

    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/proxy-service/ssl/certificate-validation',1024,768,'certificate validation rules')";
    $topbuttons[] = array($add, ico_plus, "{new_rule}");
    $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");
    $topbuttons[] = array($jshelp, ico_support, "WIKI");

    $TINY_ARRAY["TITLE"]="{certificate_validation}";
    $TINY_ARRAY["ICO"]="fas fa-file-certificate";
    $TINY_ARRAY["EXPL"]="{proxy_certificate_validation}";
    $TINY_ARRAY["URL"]="proxy-ssl-rules";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



	$html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$jstiny
</script>";

	// awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"certerr-rows","certerr-pager");
}

function icon_status($ruletype=0,$enabled=0,$objects=array()){

    if($ruletype==2){

        $ssl_img="<span class='label label-primary'>{trust_ssl}</span>";

    }else{
        $ssl_img="<span class='label label-danger'>{deny_access}</span>";

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
    $description=url_decode_special_tool($_POST["description"] ?? "");
    $ruleType=intval($_POST["ruleType"] ?? 2);
    $enabled=intval($_POST["enabled"] ?? 1);
	list($ok,$err)=acls_api_send("POST","/proxy/cert-errors",[
        "description"=>$description,
        "ruleType"=>$ruleType,
        "enabled"=>$enabled,
        "zDate"=>date("Y-m-d H:i:s"),
    ]);
	if(!$ok){echo htmlspecialchars($err);}
}

function rule_delete($ID){
    $ID=intval($ID);
	list($ok,$err)=acls_api_send("DELETE","/proxy/cert-errors/$ID");
	return $ok;
}
