<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
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
		$q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM whitelist WHERE ID='$ID'");
		$title=$ligne["VirusName"];
	}

	$tpl->js_dialog1($title, "$page?popup=$ID");

}

function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["delete-rule-js"]);
	$q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM whitelist WHERE ID='$ID'");
	$md5=$_GET["md"];
	$title=$ligne["VirusName"];
	$tpl->js_confirm_delete($title, "delete", $ID,"$('#$md5').remove()");
}
function rule_delete(){
	$ID=intval($_POST["delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");
	$q->QUERY_SQL("DELETE FROM whitelist WHERE ID='$ID'");
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM whitelist WHERE ID='$ID'");
	$btn="{add}";
	$jsafter="dialogInstance1.close();LoadAjax('table-clamavw','$page?table=yes');";
	if($ID>0){
		$btn="{apply}";
		$jsafter="dialogInstance1.close();";
	}
	
	$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_text("VirusName", "{virusname}", $ligne["VirusName"]);
	echo $tpl->form_outside("{whitelist}:{$ligne["VirusName"]}", $form,"{signature_exclusions_explain}",$btn,$jsafter,"AsOrgAdmin");
	
}
function rule_enable_js():bool{
	$ID=intval($_GET["enable-rule-js"]);
	$q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM whitelist WHERE ID='$ID'");
	$enabled=intval($ligne["enabled"]);
	if($enabled==1){
		$sql="UPDATE whitelist SET enabled=0 WHERE ID=$ID;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
        return false;
	}
	$sql="UPDATE whitelist SET enabled=1 WHERE ID=$ID;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	return true;
}

function rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=$_POST["ID"];
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="Manager";}
	$q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");
	$date=date("Y-m-d H:i:s");
	if($ID==0){
		$sql="INSERT INTO whitelist (VirusName,uid,zdate,enabled) VALUES ('{$_POST["VirusName"]}','$uid','$date','1')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
	}else{
		$ligne=$q->mysqli_fetch_array("SELECT VirusName FROM whitelist WHERE ID='$ID'");
		$oldv=$ligne["VirusName"];
		if($oldv<>$_POST["VirusName"]){
			$sql="UPDATE whitelist SET VirusName='{$_POST["VirusName"]}', uid='$uid' , zdate='$date' WHERE ID=$ID;";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error;}
		}
	}
	
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
	$q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");
	
	$sql="CREATE TABLE IF NOT EXISTS `whitelist` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`uid` TEXT,`VirusName` TEXT ,`zdate` TEXT,`enabled` INTEGER NOT NULL DEFAULT 1 )";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;
	}

	$t=time();
	$add="Loadjs('$page?rule-js=0',true);";

    $jsrestart=$tpl->framework_buildjs("/clamd/reconfigure",
    "clamd.progress",
        "clamd.progress.logs",
        "progress-clamavw-restart"
    );
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart;\"><i class='fa fa-save'></i> {apply_rules} </label>";
	$html[]="</div>";
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true data-type='text'>{date}</th>";
	$html[]="<th data-sortable=true data-type='text'>{virusname}</th>";
	$html[]="<th data-sortable=true data-type='text'>{enabled}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-loader-webhttp-rules','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	
	
	
	$TRCLASS=null;
	
	$results=$q->QUERY_SQL("SELECT * FROM whitelist ORDER BY VirusName");
	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize($ligne));
		$VirusName=$ligne["VirusName"];
		$ID=$ligne["ID"];
		$zdate=$ligne["zdate"];
		$enabled=$ligne["enabled"];
		$uid=$ligne["uid"];

		$js="Loadjs('$page?rule-js=$ID',true);";
		
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td id='$index' style='width:1%' nowrap><strong>$zdate<br><small>{by} $uid</small></strong></td>";
		$html[]="<td><strong>". $tpl->td_href($VirusName,null,$js)."</strong></td>";
		$html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_check($enabled,"Loadjs('$page?enable-rule-js=$ID')",null,"AsOrgAdmin")."</center></td>";
		$html[]="<td style='width:1%' class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-rule-js=$ID&md=$zmd5')","AsSystemAdministrator") ."</center></td>";
		$html[]="</tr>";		
		
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } )});
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}