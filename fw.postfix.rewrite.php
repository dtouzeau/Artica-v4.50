<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_POST["import"])){import_save();exit;}
if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_POST["rbl-choose"])){rbl_save();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["delete"])){rule_delete();exit;}
if(isset($_POST["rule-delete"])){rule_delete_perform();exit;}
if(isset($_POST["rule-id"])){rule_save();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["check"])){rule_check();exit;}
if(isset($_GET["table-div"])){table_div();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["table"])){table();exit;}

page();
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
    $instance_id=intval($_GET["instance_id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{import}","$page?import-popup=yes&instance-id=$instance_id",990);
}

function import_popup(){
    $instance_id=intval($_GET["instance_id"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $data=$tpl->javascript_parse_text("String to find,String to replace,{outgoing_mails_only} (0/1),{sender} (0/1),{recipient} (0/1)")."\n\n";
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
    $instance_id=intval($_GET["instance_id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["delete"];
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$ligne=$q->mysqli_fetch_array("SELECT * from smtp_generic_maps WHERE ID='$id'");
	$rulename=$ligne["generic_from"]." * * * * ".$ligne["generic_to"];
	
	$tpl->js_confirm_delete($rulename, "rule-delete",$id,"$('#{$_GET["id"]}').remove()");
}
function rule_delete_perform(){
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$id=$_POST["rule-delete"];
	$q->QUERY_SQL("DELETE FROM smtp_generic_maps WHERE ID='$id'");
	if(!$q->ok){$q->mysql_error;}
}
function rule_popup(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$ruleid=intval($_GET["rule-popup"]);
	$btn="{apply}";
    $js=null;
    $ligne=$q->mysqli_fetch_array("SELECT * from smtp_generic_maps WHERE ID='$ruleid'");
    if($ruleid==0){
        $btn="{add}";
        $title="{new_rule}";}else{$title="{$ligne["generic_from"]}";
        $js="LoadAjax('table-loader-postfix-rewrite-rules','$page?table-div=yes&instance-id=$instance_id');";
    }
    $form[]=$tpl->field_hidden("instance_id",$instance_id);
	$form[]=$tpl->field_hidden("rule-id", "$ruleid");
	$form[]=$tpl->field_text("generic_from", "{source_pattern}", $ligne["generic_from"],true,"{smtp_generic_maps_explain}");
    $form[]=$tpl->field_text("generic_to", "{destination_pattern}", $ligne["generic_to"],true,"{smtp_generic_maps_explain}");
    $form[]=$tpl->field_checkbox("smtp_generic_maps","{outgoing_mails_only}",$ligne["smtp_generic_maps"],false);
    $form[]=$tpl->field_checkbox("sender_canonical_maps","{sender_address}",$ligne["sender_canonical_maps"],false);
    $form[]=$tpl->field_checkbox("recipient_canonical_maps","{recipient_address}",$ligne["recipient_canonical_maps"],false);

	echo $tpl->form_outside("$title ($ruleid)",$form,null,$btn,"dialogInstance1.close();$js");
	
	
}

function import_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instance_id=intval($_POST["instance_id"]);
    $MAIN=explode("\n",$_POST["import"]);
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    foreach ($MAIN as $line){
        $TR=explode(",",$line);
        $generic_from=$TR[0];
        $generic_to=$TR[1];
        $smtp_generic_maps=intval($TR[2]);
        $sender_canonical_maps=intval($TR[3]);
        $recipient_canonical_maps=intval($TR[4]);
        $md5=md5("$generic_from$smtp_generic_maps$recipient_canonical_maps$sender_canonical_maps");
        $sql="INSERT INTO smtp_generic_maps (generic_from,generic_to,zmd5,smtp_generic_maps,recipient_canonical_maps,sender_canonical_maps,instanceid)
		VALUES('$generic_from','{$generic_to}','$md5','{$smtp_generic_maps}','{$recipient_canonical_maps}','{$sender_canonical_maps}',$instance_id);";
        $q->QUERY_SQL($sql);

    }

}

function rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $instance_id=intval($_POST["instance_id"]);
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ID=intval($_POST["rule-id"]);
    $md5=md5($_POST["generic_from"]."{$_POST["smtp_generic_maps"]}{$_POST["recipient_canonical_maps"]}{$_POST["sender_canonical_maps"]}");

    if($ID==0){
        $sql="INSERT INTO smtp_generic_maps (generic_from,generic_to,zmd5,smtp_generic_maps,recipient_canonical_maps,sender_canonical_maps,instanceid)
		VALUES('{$_POST["generic_from"]}','{$_POST["generic_to"]}','$md5','{$_POST["smtp_generic_maps"]}','{$_POST["recipient_canonical_maps"]}','{$_POST["sender_canonical_maps"]}',$instance_id);";
    }else{
        $sql="UPDATE smtp_generic_maps SET generic_from='{$_POST["generic_from"]}',
		generic_to='{$_POST["generic_to"]}',
		zmd5='$md5',
		sender_canonical_maps='{$_POST["sender_canonical_maps"]}',
		recipient_canonical_maps='{$_POST["recipient_canonical_maps"]}',
		smtp_generic_maps='{$_POST["smtp_generic_maps"]}'
		WHERE ID=$ID";

    }


    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:$q->mysql_error<br>$sql";}
	
}

function rule_js(){
    $instance_id=intval($_GET["instance-id"]);
    $sock=new sockets();
	$page=CurrentPageName();
	$title="{new_rule}";
	$tpl=new template_admin();
	$ruleid=$_GET["rule-js"];
	if($ruleid==0){
		$tpl->js_dialog1("{smtp_generic_maps}: {new_rule}", "$page?rule-popup=0");
		return;
	}
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$results=$q->QUERY_SQL("SELECT generic_from from smtp_generic_maps WHERE id='$ruleid'");
	$rulename=$results[0]["generic_from"];
	$tpl->js_dialog1("$rulename", "$page?rule-popup=$ruleid&instance-id=$instance_id");
}
function rule_check(){
	$ID=$_GET["check"];
	$field=$_GET["f"];
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$results=$q->QUERY_SQL("SELECT $field from smtp_generic_maps WHERE ID='$ID'");
	$enabled=$results[0][$field];
	if($enabled==1){$q->QUERY_SQL("UPDATE smtp_generic_maps SET $field=0 WHERE ID='$ID'");return;}
	$q->QUERY_SQL("UPDATE smtp_generic_maps SET $field=1 WHERE ID='$ID'");
}


function table(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    $instance_id=intval($_GET["instance-id"]);
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/smtp_generic_maps";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/smtp_generic_maps.txt";
	$ARRAY["CMD"]="postfix.php?postfix-hash-smtp-generic=yes&instance-id=$instance_id";
	$ARRAY["TITLE"]="{smtp_generic_maps}";
	$prgress=base64_encode(serialize($ARRAY));
	$reconfigure="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-rewrite-rules');";
    $import="Loadjs('$page?import-js=yes&instance-id=$instance_id')";

    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0&instance-id=$instance_id')\">";
    $btn[]="<i class='fa fa-plus'></i> {new_rule} </label>";
    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"$import\">";
    $btn[]="<i class='fas fa-file-import'></i> {import} </label>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"$reconfigure\">";
    $btn[]="<i class='fa fa-save'></i> {apply_configuration} </label>";
    $btn[]="</div>";

	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{source_pattern}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{destination_pattern}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{generic}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{sender}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{recipient}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</th>";
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    if(!$q->FIELD_EXISTS("smtp_generic_maps","instanceid")){
        $q->QUERY_SQL("ALTER TABLE smtp_generic_maps ADD instanceid INTEGER NOT NULL DEFAULT 0");
    }
    $results=$q->QUERY_SQL("SELECT * FROM smtp_generic_maps WHERE instanceid=$instance_id ORDER by ID DESC");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
	

	foreach ($results as $num=>$ligne){
		$id=$ligne["ID"];
		$generic_from=$tpl->td_href($ligne["generic_from"],null,"Loadjs('$page?rule-js=$id&instance-id=$instance_id')");
		$generic_to=$tpl->td_href($ligne["generic_to"],null,"Loadjs('$page?rule-js=$id&instance-id=$instance_id')");

		$iddiv=md5(serialize($ligne));

		$sender_canonical_maps=$tpl->icon_check($ligne["sender_canonical_maps"],"Loadjs('$page?check=$id&f=sender_canonical_maps&instance-id=$instance_id')","AsMailBoxAdministrator");
        $smtp_generic_maps=$tpl->icon_check($ligne["smtp_generic_maps"],"Loadjs('$page?check=$id&f=smtp_generic_maps&instance-id=$instance_id')","AsMailBoxAdministrator");
        $recipient_canonical_maps=$tpl->icon_check($ligne["recipient_canonical_maps"],"Loadjs('$page?check=$id&f=recipient_canonical_maps&instance-id=$instance_id')","AsMailBoxAdministrator");



		
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}	
		$html[]="<tr class='$TRCLASS' id='$iddiv'>";
		$html[]="<td nowrap>$generic_from</td>";
		$html[]="<td nowrap>$generic_to</td>";
		$html[]="<td style='vertical-align:middle' class='center' width=1%>$smtp_generic_maps</td>";
        $html[]="<td style='vertical-align:middle' class='center' width=1%>$sender_canonical_maps</td>";
        $html[]="<td style='vertical-align:middle' class='center' width=1%>$recipient_canonical_maps</td>";
		$html[]="<td style='vertical-align:middle' class='center' width=1%>".$tpl->icon_delete("Loadjs('$page?delete=$id&id=$iddiv&instance-id=$instance_id')","AsPostfixAdministrator")."</td>";
		$html[]="</tr>";
	
	
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $instancename="SMTP Master";
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename="&nbsp;<small>({$ligne["instancename"]})</small>";
    }

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
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}


