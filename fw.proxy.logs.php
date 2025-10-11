<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
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
if(isset($_GET["download-file"])){download_file();exit;}
if(isset($_GET["download-final"])){download_final();exit;}
page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{logs_center}",ico_list,
        "{logs_center_explain}","$page?table=yes","proxy-logs-center",
        "progress-firehol-restart",false,"table-loader-proxy-logscenter");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function download_file():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $fname=$_GET["download-file"];
    header("content-type: application/x-javascript");

    echo $tpl->framework_buildjs("squid2.php?compress-access=$fname",
        "squid.access.compress.progress",
        "squid.access.compress.log",
        "progress-firehol-restart",
        "document.location.href='/$page?download-final=$fname'"
    );
    return true;
}
function download_final():bool{
    $fname=base64_decode($_GET["download-final"]);
    $targetfile=PROGRESS_DIR."/$fname.gz";
    $psize=filesize($targetfile);
    header('Content-type: application/gz');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$fname.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$psize);
    ob_clean();
    flush();
    readfile($targetfile);
    @unlink($targetfile);
    return true;
}

function enabled_js():bool{
	$aclid=$_GET["enabled-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM squid_logs_acls WHERE aclid='$aclid'");
	$enabled=$ligne["enabled"];
	if($enabled==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE squid_logs_acls SET enabled=$enabled WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;}
    return true;
}

function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["delete-rule-js"]);
	header("content-type: application/x-javascript");
	
	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete} {$_GET["name"]} ?");
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

function rule_delete(){
	$aclid=$_POST["delete-rule"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM logs_sqacllinks WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM squid_logs_acls WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;return;}
}




function rule_id_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["ruleid-js"];
	$title="{new_rule}";
	
	if($id>0){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_logs_acls WHERE aclid='$id'");
		$title="{rule}: $id {$ligne["rulename"]}";
	}
	$title=$tpl->javascript_parse_text($title);
	$tpl->js_dialog($title,"$page?rule-popup=$id");
}



function rule_settings():bool{
	$aclid=intval($_GET["rule-settings"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne["enabled"]=1;
	$ligne["zorder"]=1;
	$btname="{add}";
	$title="{new_rule}";
	$BootstrapDialog="BootstrapDialog1.close();";
	if($aclid>0){
		$btname="{apply}";
		
		$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_logs_acls WHERE aclid='$aclid'");
		$title=$ligne["rulename"];
		$BootstrapDialog=null;
	}
	
	$logtype[0]="{write_logs_locally}";
	$logtype[1]="{do_not_log}";
	$logtype[2]="{send_to_syslog}";

	$tpl->field_hidden("rule-save", $aclid);
	$form[]=$tpl->field_text("rulename","{rulename}",$ligne["rulename"],true);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_array_hash($logtype, "logtype", "{type}", intval($ligne["logtype"]),true,null,false);	
	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"]);
	echo $tpl->form_outside($title,@implode("\n", $form),"{log_rule}",$btname,"LoadAjax('table-loader-proxy-logscenter','$page?table=yes');$BootstrapDialog","license");

    return true;
}

function rule_parameters_save():bool{
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$aclid=$_POST["parameters-save"];
	$ligne=$q->mysqli_fetch_array("SELECT logconfig FROM squid_logs_acls WHERE aclid='$aclid'");
	$logconfig=unserialize(base64_decode($ligne["logconfig"]));
    foreach ($_POST as $key=>$val){
		$logconfig[$key]=url_decode_special_tool($val);
	}
	
	$logconfig_new=base64_encode(serialize($logconfig));
	$q->QUERY_SQL("UPDATE `squid_logs_acls` SET `logconfig`='$logconfig_new' WHERE aclid='$aclid'");
	if(!$q->ok){echo $q->mysql_error_html(true);}
    return true;
}


function rule_parameters():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $form=array();
	$aclid=$_GET["parameters"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT rulename,logconfig,logtype FROM squid_logs_acls WHERE aclid='$aclid'");
	$logtype=$ligne["logtype"];
	$logconfig=unserialize(base64_decode($ligne["logconfig"]));
	$rulename=$ligne["rulename"];
    if($logconfig["LOGFILENAME"]==null){$logconfig["LOGFILENAME"]="access$aclid.log";}
	if($logconfig["SYSLOG_FACILITY"]==null){$logconfig["SYSLOG_FACILITY"]="local6";}
	
	$tpl->field_hidden("parameters-save", $aclid);
	
	if($logtype==0){
		$form[]=$tpl->field_text("LOGFILENAME","{filename}",$logconfig["LOGFILENAME"],true,null,false);
	}
	if($logtype==2){
		$facility["kern"]="kern";
		$facility["user"]="user";
		$facility["mail"]="mail";
		$facility["daemon"]="daemon";
		$facility["auth"]="auth";
		$facility["syslog"]="syslog";
		$facility["lpr"]="lpr";
		$facility["news"]="news";
		$facility["authpriv"]="authpriv";
		$facility["local0"]="local0";
		$facility["local1"]="local1";
		$facility["local2"]="local2";
		$facility["local3"]="local3";
		$facility["local4"]="local4";
		$facility["local5"]="local5";
		$facility["local6"]="local6";
		$facility["local7"]="local7";
		$form[]=$tpl->field_array_hash($facility, "SYSLOG_FACILITY", "Syslog facility", $logconfig["SYSLOG_FACILITY"]);
		
		
	}
	
	
	echo $tpl->form_outside($rulename,@implode("\n", $form),"{log_rule}","{apply}","LoadAjax('table-loader-proxy-logscenter','$page?table=yes');","license");
    
    return true;
	
}

function rule_main_save(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	if(!$users->AsSquidAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}
	
	$aclid=$_POST["rule-save"];
	$rulename=url_decode_special_tool($_POST["rulename"]);
	$rulename=mysql_escape_string2($rulename);
	$logfilepath=url_decode_special_tool($_POST["logfilepath"]);
	$logfilepath=mysql_escape_string2($logfilepath);
	
	$sql="CREATE TABLE IF NOT EXISTS `squid_logs_acls` (
		`aclid` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
		`rulename` VARCHAR( 128 ) NOT NULL ,
		`enabled` SMALLINT( 1 ) NOT NULL ,
		`logtype` SMALLINT( 1 ) NOT NULL ,
		`logconfig` TEXT ,
		`zorder`  SMALLINT( 3 ) NOT NULL,
		INDEX ( `aclid` ,`rulename`),
		KEY `enabled`(`enabled`),
		KEY `zorder`(`zorder`)
		)  ENGINE = MYISAM;";
	
	$q->QUERY_SQL($sql);
	
	
	
	if($aclid==0){
		$sqlB="INSERT INTO `squid_logs_acls` (`rulename`,`enabled` ,`zorder`,`logtype`) 
		VALUES ('$rulename','{$_POST["enabled"]}','{$_POST["zorder"]}','{$_POST["logtype"]}')";
	}else{
		$sqlB="UPDATE `squid_logs_acls` SET `rulename`='$rulename',`enabled`='{$_POST["enabled"]}',
		`zorder`='{$_POST["zorder"]}',logtype='{$_POST["logtype"]}' WHERE aclid='$aclid'";
	}
	

	$q->QUERY_SQL($sqlB);
	if(!$q->ok){echo $q->mysql_error_html(true);}
}

function rule_tab(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["rule-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT rulename,logtype FROM squid_logs_acls WHERE aclid='$aclid'");
	
	
	
	$array["{rule}"]="$page?rule-settings=$aclid";
	if($aclid>0){
		$logtype=$ligne["logtype"];
		if($logtype<>1){
			$array["{parameters}"]="$page?parameters=$aclid";
		}
		$array["{objects}"]="fw.proxy.objects.php?aclid=$aclid&main-table=logs_sqacllinks&fast-acls=0";
	
	}
	echo $tpl->tabs_default($array);
	
	
}



function rule_move_js(){
		header("content-type: application/x-javascript");
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$tpl=new template_admin();
		$dir=$_GET["dir"];
		$aclid=intval($_GET["aclid"]);
		$ligne=$q->mysqli_fetch_array("SELECT zorder FROM squid_logs_acls WHERE aclid='$aclid'");
		$zorder=intval($ligne["zorder"]);
		echo "// Current order = $zorder\n";
		
		if($dir=="up"){
			$zorder=$zorder-1;
			if($zorder<0){$zorder=0;}
	
		}
		else{
			$zorder=$zorder+1;
		}
		echo "// New order = $zorder\n";
		$q->QUERY_SQL("UPDATE squid_logs_acls SET zorder='$zorder' WHERE aclid='$aclid'");
		if(!$q->ok){
			$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);
			echo "alert('$q->mysql_error');";return;
		}
	
		$c=0;
		$results=$q->QUERY_SQL("SELECT aclid FROM squid_logs_acls ORDER BY zorder");
		foreach($results as $index=>$ligne) {
			$aclid=$ligne["aclid"];
			echo "// $aclid New order = $c";
			$q->QUERY_SQL("UPDATE squid_logs_acls SET zorder='$c' WHERE aclid='$aclid'");
			$c++;
		}
	
	

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $aclid=0;
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$users=new usersMenus();
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
    $LogsWarninStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop"));
	$SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
	
	if($LogsWarninStop==1){$html[]=$tpl->_ENGINE_parse_body("<div class='alert alert-danger'>{squid_logs_urgency_section}</div>");}
	if($SquidUrgency==1){$html[]=$tpl->_ENGINE_parse_body("<div class='alert alert-danger'>{proxy_in_emergency_mode}</div>");}

	$add="Loadjs('$page?ruleid-js=0',true);";
	if(!$users->AsSquidAdministrator){$add="alert('$ERROR_NO_PRIVS2')";}


	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
	$ARRAY["CMD"]="squid2.php?global-logging-center=yes";
	$ARRAY["TITLE"]="{GLOBAL_ACCESS_CENTER}";
	$ARRAY["REFRESH-MENU"]=1;
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>";
    $btn[]="</div>";
	$html[]="<table id='table-firewall-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";



	$html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="<th data-sortable=true style='width:1%'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rulename</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader-proxy-logscenter','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	
	$zlogtype[0]="{write_logs_locally}";
	$zlogtype[1]="{do_not_log}";
	$zlogtype[2]="{send_to_syslog}";

	$isRights=isRights();
	$results=$q->QUERY_SQL("SELECT * FROM squid_logs_acls ORDER BY zorder");
	$TRCLASS=null;
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
			$rulename=$ligne["rulename"];
			$rulenameenc=urlencode($rulename);
			$aclid=$ligne["aclid"];
			$addlogtype=null;
			$logtype=$ligne["logtype"];
			$logconfig=unserialize(base64_decode($ligne["logconfig"]));
			$LOGFILENAME=$logconfig["LOGFILENAME"];
			if($LOGFILENAME==null){$LOGFILENAME="access$aclid.log";}
			if($logconfig["SYSLOG_FACILITY"]==null){$logconfig["SYSLOG_FACILITY"]="local6";}
			
			if($logtype==0){
				$addlogtype=" {to} /var/log/squid/$LOGFILENAME";
				if(is_file("/var/log/squid/$LOGFILENAME")){
					$xsize=filesize("/var/log/squid/$LOGFILENAME");
					$xsize=$xsize/1024;
					$LOGFILENAME_TEXT=$LOGFILENAME." (".FormatBytes($xsize).")";
                    $LOGPATH=$tpl->td_href("/var/log/squid/$LOGFILENAME_TEXT","{download}","Loadjs('$page?download-file=".base64_encode($LOGFILENAME)."');");
                    $addlogtype =" {to} $LOGPATH";
				}
			
			}
			if($logtype==2){$addlogtype=" {to} syslog facility {$logconfig["SYSLOG_FACILITY"]}";}
			$check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$aclid')");

			$up=$tpl->icon_up("Loadjs('$page?move-js=yes&aclid=$aclid&dir=up')");
			$down=$tpl->icon_down("Loadjs('$page?move-js=yes&aclid=$aclid&dir=down')");
			$js="Loadjs('$page?ruleid-js=$aclid',true);";
			$delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js=$aclid&name=$rulenameenc')");
			
			$explain=$tpl->_ENGINE_parse_body("{for_objects} ".proxy_objects($aclid)." {then} {$zlogtype[$ligne["logtype"]]}$addlogtype");
			if(!$isRights){
				$up=null;
				$down=null;
				$delete=null;
			}

			$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
			$html[]="<td class=\"center\" id='$index'><button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" ><i class='fa fa-paste'></i></button></td>";
			$html[]="<td class=\"center\">$check</td>";
			$html[]="<td style='vertical-align:middle'>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$rulename:</a>&nbsp;&raquo;&nbsp;$explain</span></td>";
			$html[]="<td class=\"center\">$up&nbsp;&nbsp;$down</td>";
			$html[]="<td class=center>$delete</td>";
			$html[]="</tr>";

	}
	
	$FATAL_SQUID_ACCESS_LOG=null;
	$SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
	if($SquidNoAccessLogs==1){$FATAL_SQUID_ACCESS_LOG="<p class=text-danger>{FATAL_SQUID_ACCESS_LOG}</p>";}
	
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
	$html[]="<td class=\"center\">-</td>";
	$html[]="<td class=\"center\">-</td>";

    $LOGPATH=$tpl->td_href("/var/log/squid/access.log","{download}","Loadjs('$page?download-file=".base64_encode("access.log")."')");

	$html[]="<td style='vertical-align:middle'>".$tpl->_ENGINE_parse_body("<strong>{default}</strong>:</a>&nbsp;&raquo;&nbsp;{for_objects} {all} {then} {write_logs_locally} {to}")." $LOGPATH</span>$FATAL_SQUID_ACCESS_LOG</td>";
	$html[]="<td class=\"center\">&nbsp;&nbsp;</td>";
	$html[]="<td class=center><center>-</center></td>";
	$html[]="</tr>";
	

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";



    $TINY_ARRAY["TITLE"]="{logs_center}";
    $TINY_ARRAY["ICO"]=ico_list;
    $TINY_ARRAY["EXPL"]="{logs_center_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->_ENGINE_parse_body(@implode("",$btn));
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



	$html[]="

	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	
</script>";

	echo @implode("\n", $html);

}
function isRights():bool{
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
    return false;
}

function proxy_objects($aclid){
    $tablelink="logs_sqacllinks";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$sql="SELECT
	$tablelink.gpid,
	$tablelink.zmd5,
	$tablelink.negation,
	$tablelink.zOrder,
	webfilters_sqgroups.GroupType,
	webfilters_sqgroups.GroupName,
	webfilters_sqgroups.ID
	FROM $tablelink,webfilters_sqgroups
	WHERE $tablelink.gpid=webfilters_sqgroups.ID
	AND $tablelink.aclid=$aclid
	ORDER BY $tablelink.zorder";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
    $tt=array();
	foreach($results as $index=>$ligne) {
		$gpid=$ligne["gpid"];
		$js="Loadjs('fw.proxy.objects.php?object-js=yes&gpid=$gpid')";
		$neg_text="{is}";
		if($ligne["negation"]==1){$neg_text="{is_not}";}
		$GroupName=$ligne["GroupName"];
		$tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold' id='t$index'>$GroupName</a> (".$q->acl_GroupType[$ligne["GroupType"]].")";
	}
	
	if(count($tt)>0){
		return @implode("<br>{and} ", $tt);
		
	}else{
		return "{all}";
	}
	
	
}