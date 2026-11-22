<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// This page used to open /home/artica/SQLITE/postfix.db directly (lib_sqlite) and build its own
// INSERT/UPDATE/DELETE from $_POST — it even carried its own "ALTER TABLE ... ADD regexp", a second
// owner for a schema the daemon already migrates. Every read and write now goes through the
// artica-postfix daemon on /run/artica-postfix.sock (/postfix/rewrite/rules/*), which owns this
// table: the statements there are parameterised, so an apostrophe in an address is just an
// apostrophe, and the schema has a single owner again.

if(isset($_POST["import"])){import_save();exit;}
if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["delete"])){rule_delete();exit;}
if(isset($_POST["rule-delete"])){rule_delete_perform();exit;}
if(isset($_POST["rule-id"])){rule_save();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-preview"])){rule_preview();exit;}
if(isset($_GET["check"])){rule_check();exit;}
if(isset($_GET["table-div"])){table_div();exit;}
if(isset($_GET["table"])){table();exit;}

page();

// ----------------------------------------------------------------------------------------------
// REST helpers — one place that knows the daemon's payload shape.
// ----------------------------------------------------------------------------------------------

// rewrite_rules_get returns the decoded rules list of one instance (empty array on any failure —
// the table then simply renders with no row instead of a PHP warning).
function rewrite_rules_get($instance_id){
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/rewrite/rules/$instance_id");
    $j=json_decode((string)$resp,true);
    if(!is_array($j) || !isset($j["rules"]) || !is_array($j["rules"])){return array();}
    return $j["rules"];
}

// rewrite_rule_get returns one rule, or an empty array when it does not exist (id 0 = "new rule").
function rewrite_rule_get($instance_id,$id){
    if(intval($id)<1){return array();}
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/rewrite/rules/$instance_id/".intval($id));
    $j=json_decode((string)$resp,true);
    if(!is_array($j) || isset($j["error"])){return array();}
    return $j;
}

// rewrite_rest_error extracts the daemon's error message, or "" when the call succeeded.
// The save/delete handlers echo ONLY this: form_outside and js_confirm_delete treat any non-empty
// response as a failure and run their $jsafter (close + refresh) only on an empty one.
function rewrite_rest_error($resp){
    $j=json_decode((string)$resp,true);
    if(is_array($j) && !empty($j["error"])){return (string)$j["error"];}
    if(!is_array($j) && trim((string)$resp)!=""){return (string)$resp;}
    return "";
}

// ----------------------------------------------------------------------------------------------

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $instance_id=intval($_GET["instance-id"]);
    $html=$tpl->page_header("{smtp_generic_maps} v$POSTFIX_VERSION",
        "fas fa-repeat-alt",
        "{smtp_generic_maps_text}",
        "$page?table-div=yes&instance-id=$instance_id",
        "postfix-rewrite-rules-$instance_id",
        "progress-postfix-rewrite-rules",false,
        "table-loader-postfix-rewrite-rules"
    );

    if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	echo $tpl->_ENGINE_parse_body($html);

}
function import_js(){
    $instance_id=intval($_GET["instance-id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{import}","$page?import-popup=yes&instance-id=$instance_id",990);
}

function import_popup(){
    $instance_id=intval($_GET["instance-id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $data=$tpl->javascript_parse_text("String to find,String to replace,{outgoing_mails_only} (0/1),{sender} (0/1),{recipient} (0/1),{regular_expression} (0/1)")."\n\n";
    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_textareacode("import",null,$data);
    echo $tpl->form_outside("{import}", $form,null,"{import}","dialogInstance1.close();LoadAjax('table-loader-postfix-rewrite-rules','$page?table-div=yes&instance-id=$instance_id');","AsPostfixAdministrator");

}


function table_div(){
	$page=CurrentPageName();
    $instance_id=intval($_GET["instance-id"]);
	echo "<div id='postfix-smtp-rules-div' style='margin-top:20px'></div><script>LoadAjax('postfix-smtp-rules-div','$page?table=yes&instance-id=$instance_id');</script>";
}

function rule_delete(){
    $instance_id=intval($_GET["instance-id"]);
	$tpl=new template_admin();
	$id=intval($_GET["delete"]);
    $rule=rewrite_rule_get($instance_id,$id);
	$rulename=$rule["generic_from"]." * * * * ".$rule["generic_to"];

    // js_confirm_delete only POSTs that one field, so the instance travels inside the value.
    // L'empreinte de ligne est un md5: la restreindre a l'hexadecimal empeche
    // d'injecter un selecteur jQuery arbitraire dans le $jsafter.
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["id"] ?? ""));
	$tpl->js_confirm_delete($rulename, "rule-delete","$instance_id:$id","$('#$md').remove()");
}
function rule_delete_perform(){
    $parts=explode(":",(string)$_POST["rule-delete"]);
    $instance_id=intval($parts[0]);
    $id=isset($parts[1])?intval($parts[1]):0;
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_DELETE("/postfix/rewrite/rules/$instance_id/$id");
    $err=rewrite_rest_error($resp);
    if($err!=""){echo htmlspecialchars($err);}
}
function rule_popup(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ruleid=intval($_GET["rule-popup"]);
	$btn="{apply}";
    $js="LoadAjax('table-loader-postfix-rewrite-rules','$page?table-div=yes&instance-id=$instance_id');";
    $ligne=rewrite_rule_get($instance_id,$ruleid);
    if($ruleid==0){
        $btn="{add}";
        $title="{new_rule}";
    }else{
        $title="{$ligne["generic_from"]}";
    }
    $form[]=$tpl->field_hidden("instance_id",$instance_id);
	$form[]=$tpl->field_hidden("rule-id", "$ruleid");
	$form[]=$tpl->field_text("generic_from", "{source_pattern}", $ligne["generic_from"],true,"{smtp_generic_maps_explain}");
    $form[]=$tpl->field_text("generic_to", "{destination_pattern}", $ligne["generic_to"],true,"{smtp_generic_maps_explain}");
    $form[]=$tpl->field_checkbox("regexp","{regular_expression}",isset($ligne["regexp"])?intval($ligne["regexp"]):0,false);
    $form[]=$tpl->field_checkbox("smtp_generic_maps","{outgoing_mails_only}",isset($ligne["smtp_generic_maps"])?intval($ligne["smtp_generic_maps"]):0,false);
    $form[]=$tpl->field_checkbox("sender_canonical_maps","{sender_address}",isset($ligne["sender_canonical_maps"])?intval($ligne["sender_canonical_maps"]):0,false);
    $form[]=$tpl->field_checkbox("recipient_canonical_maps","{recipient_address}",isset($ligne["recipient_canonical_maps"])?intval($ligne["recipient_canonical_maps"]):0,false);

    // Regex preview/test (feature 014): validate the pattern against Postfix's own
    // engine before applying, so a malformed rule can never reach mail flow.
    $preview ="<div style='margin-top:10px;padding:8px;margin-bottom:10px'>";
    $preview.="<input type='text' id='rewrite-sample' class='form-control' placeholder='jhon.doe+abczyx@domain.tld' style='width:68%;display:inline-block'>";
    $preview.="&nbsp;<button type='button' class='btn btn-info' onclick=\"RewritePreview('$page',$instance_id);\">{preview_test}</button>";
    $preview.="<div style='margin-bottom:4px'><small>{regex_rewrite_explain}</small></div>";
    $preview.="<div id='rewrite-preview-result' style='margin-top:6px;margin-bottom:10px'></div>";
    $preview.="</div>";
    $form[]=$preview;

	echo $tpl->form_outside("$title ($ruleid)",$form,null,$btn,"dialogInstance1.close();$js");

}

function import_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instance_id=intval($_POST["instance_id"]);
    // The daemon parses the CSV: it owns the header-row skip, the per-line validation and the
    // duplicate key, so a bad line is reported instead of aborting the batch.
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/rewrite/rules/$instance_id/import",
        array("payload"=>(string)$_POST["import"]));
    $err=rewrite_rest_error($resp);
    if($err!=""){echo htmlspecialchars($err);}
}

function rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $instance_id=intval($_POST["instance_id"]);
    $ID=intval($_POST["rule-id"]);
    $data=array(
        "generic_from"=>(string)$_POST["generic_from"],
        "generic_to"=>(string)$_POST["generic_to"],
        "smtp_generic_maps"=>intval($_POST["smtp_generic_maps"]),
        "sender_canonical_maps"=>intval($_POST["sender_canonical_maps"]),
        "recipient_canonical_maps"=>intval($_POST["recipient_canonical_maps"]),
        "regexp"=>intval($_POST["regexp"])
    );
    if($ID==0){
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/rewrite/rules/$instance_id",$data);
    }else{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_PUT_JSON("/postfix/rewrite/rules/$instance_id/$ID",$data);
    }
    $err=rewrite_rest_error($resp);
    if($err!=""){echo htmlspecialchars($err);}
}

function rule_js(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ruleid=intval($_GET["rule-js"]);
	if($ruleid==0){
        // The instance MUST travel here: without it the popup posted instance_id=0 and every rule
        // created from instance N landed on the primary instance.
		$tpl->js_dialog1("{smtp_generic_maps}: {new_rule}", "$page?rule-popup=0&instance-id=$instance_id");
		return;
	}
    $rule=rewrite_rule_get($instance_id,$ruleid);
	$rulename=isset($rule["generic_from"])?$rule["generic_from"]:"{rule} $ruleid";
	$tpl->js_dialog1("$rulename", "$page?rule-popup=$ruleid&instance-id=$instance_id");
}

// rule_check flips one switch column of a rule. The column name is whitelisted here as well as in
// the daemon — the former code interpolated $_GET["f"] straight into "SET $field=1".
function rule_check(){
    $instance_id=intval($_GET["instance-id"]);
	$ID=intval($_GET["check"]);
	$field=(string)$_GET["f"];
    $allowed=array("smtp_generic_maps","sender_canonical_maps","recipient_canonical_maps","regexp");
    if(!in_array($field,$allowed,true)){return;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/rewrite/rules/$instance_id/toggle/$ID",
        array("field"=>$field));
    // Loadjs() evaluates the response as JavaScript — echo nothing on success.
}

// rewrite_instance_name resolves the instance label through the daemon's own listing rather than a
// second SQL connection to postfix.db.
function rewrite_instance_name($instance_id){
    if($instance_id<1){return "SMTP Master";}
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/multi/instances");
    $j=json_decode((string)$resp,true);
    if(!is_array($j) || empty($j["instances"])){return "&nbsp;<small>($instance_id)</small>";}
    foreach($j["instances"] as $row){
        // Pentest 2026-11-04 : instancename (donnee externe) affiche sans encodage (XSS stockee).
        if(intval($row["id"])==$instance_id){return "&nbsp;<small>(".req_html($row["instancename"]).")</small>";}
    }
    return "&nbsp;<small>($instance_id)</small>";
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    $instance_id=intval($_GET["instance-id"]);
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/smtp_generic_maps";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp_generic_maps.txt";
	$ARRAY["CMD"]="/postfix/hashtables/smtp-passwords/$instance_id";
	$ARRAY["TITLE"]="{smtp_generic_maps}";
	$prgress=base64_encode(serialize($ARRAY));
	$reconfigure="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-postfix-rewrite-rules');";
    $import="Loadjs('$page?import-js=yes&instance-id=$instance_id')";

    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0&instance-id=$instance_id')\">";
    $btn[]="<i class='fa fa-plus'></i> {new_rule} </label>";
    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"$import\">";
    $btn[]="<i class='fas fa-file-import'></i> {import} </label>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"$reconfigure\">";
    $btn[]="<i class='fa fa-save'></i> {apply_configuration} </label>";
    $btn[]="</div>";

    $rows=array();
    foreach (rewrite_rules_get($instance_id) as $ligne){
        $rows[]=pfxrw_row($tpl,$page,$instance_id,$ligne);
    }

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{source_pattern}</th>";
	$html[]="<th class='center'>{type_of_rule}</th>";
	$html[]="<th class='center'>{generic}</th>";
	$html[]="<th class='center'>{sender}</th>";
	$html[]="<th class='center'>{recipient}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='pfxrw-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$shown=count($rows);
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown / $shown</span>";
	$html[]="<span class='awc-pg' id='pfxrw-pager'></span>";
	$html[]="</div></div></div>";

    $instancename=rewrite_instance_name($instance_id);

    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{smtp_generic_maps} $instancename v$POSTFIX_VERSION";
    $TINY_ARRAY["ICO"]="fas fa-repeat-alt";
    $TINY_ARRAY["EXPL"]="{smtp_generic_maps_text}";
    $TINY_ARRAY["URL"]="postfix-rewrite-rules";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="
	<script>
	$jstiny
	function RewritePreview(page,inst){
		var p=$(\"input[name='generic_from']\").val();
		var r=$(\"input[name='generic_to']\").val();
		var s=$('#rewrite-sample').val();
		$('#rewrite-preview-result').html('...');
		$.post(page+'?rule-preview=yes&instance-id='+inst,{pattern:p,replacement:r,sample:s},function(h){ $('#rewrite-preview-result').html(h);});
	}
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"pfxrw-rows","pfxrw-pager");

}

// rule_preview validates + previews a candidate regexp rule through the
// artica-postfix daemon (postmap engine, feature 014). Echoes an already-parsed
// HTML verdict into the modal's #rewrite-preview-result div.
function rule_preview(){
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $data=array(
        "pattern"=>isset($_POST["pattern"])?$_POST["pattern"]:"",
        "replacement"=>isset($_POST["replacement"])?$_POST["replacement"]:"",
        "sample"=>isset($_POST["sample"])?$_POST["sample"]:""
    );
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/rewrite/regexp/validate/$instance_id",$data);
    $j=json_decode((string)$resp,true);
    if(!is_array($j)){echo $tpl->_ENGINE_parse_body("<span class=\"label label-danger\">{invalid_pattern}</span>");return;}
    if(empty($j["valid"])){
        $err=isset($j["error"])?htmlspecialchars((string)$j["error"]):"";
        echo $tpl->_ENGINE_parse_body("<span class=\"label label-danger\">{invalid_pattern}</span>&nbsp;$err");
        return;
    }
    if(!empty($j["matched"])){
        $prev=isset($j["preview"])?htmlspecialchars((string)$j["preview"]):"";
        echo $tpl->_ENGINE_parse_body("<span class=\"label label-primary\">{preview_result}</span>&nbsp;<code>$prev</code>");
        return;
    }
    echo $tpl->_ENGINE_parse_body("<span class=\"label label-info\">{pattern_valid}</span>");
}

/**
 * Une regle de reecriture: motif source en identite, motif de destination en
 * sous-titre (c'est une paire, pas deux entites), type de correspondance et les
 * trois cartes Postfix ou la regle s'applique.
 */
function pfxrw_row($tpl,$page,$instance_id,$ligne):string{
    $id=intval($ligne["id"]);
    $iddiv=md5(serialize($ligne));
    $regexp=(intval($ligne["regexp"])==1);
    $open="Loadjs('$page?rule-js=$id&instance-id=$instance_id')";

    $sender=$tpl->icon_check($ligne["sender_canonical_maps"],"Loadjs('$page?check=$id&f=sender_canonical_maps&instance-id=$instance_id')",null,"AsMailBoxAdministrator");
    $generic=$tpl->icon_check($ligne["smtp_generic_maps"],"Loadjs('$page?check=$id&f=smtp_generic_maps&instance-id=$instance_id')",null,"AsMailBoxAdministrator");
    $recipient=$tpl->icon_check($ligne["recipient_canonical_maps"],"Loadjs('$page?check=$id&f=recipient_canonical_maps&instance-id=$instance_id')",null,"AsMailBoxAdministrator");
    $del=$tpl->icon_delete("Loadjs('$page?delete=$id&id=$iddiv&instance-id=$instance_id')","AsPostfixAdministrator");

    $h ="<tr id='$iddiv'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-arrow-right-arrow-left'></i></span>";
    $h.="<span><span class='awc-name'><a href=\"javascript:blur();\" OnClick=\"$open\">"
        .htmlspecialchars((string)$ligne["generic_from"])."</a></span>";
    $h.="<div class='awc-meta'>&rarr; ".htmlspecialchars((string)$ligne["generic_to"])."</div>";
    $h.="</span></div></td>";
    $h.="<td class='center'>".($regexp
        ?"<span class='awc-badge' style='background:#006a62'>{regular_expression}</span>"
        :"<span class='awc-badge awc-badge-off'>{exact_match}</span>")."</td>";
    $h.="<td class='center'>$generic</td>";
    $h.="<td class='center'>$sender</td>";
    $h.="<td class='center'>$recipient</td>";
    $h.="<td><div class='awc-acts'>$del</div></td>";
    $h.="</tr>";
    return $h;
}