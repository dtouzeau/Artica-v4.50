<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : ruleid-js/eth etaient recopies sans encodage dans la page (XSS).
if(isset($_GET["ruleid-js"])){$_GET["ruleid-js"]=intval($_GET["ruleid-js"]);}
if(isset($_GET["eth"])){$_GET["eth"]=preg_replace('/[^A-Za-z0-9_.:@-]/','',(string)$_GET["eth"]);}
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
    if(!fw_progress_page_priv("AsProxyMonitor")){return false;}   // task link signed for this session: built only for whom may run it
    $tpl=new template_admin();
    $page=CurrentPageName();
    // download-file = base64 of a log file name of /var/log/squid. It reaches a root shell
    // (articarest /proxy/logs/compress, which checks it again): the decoded name must be a plain file name.
    $fname=fw_proxy_logs_name($_GET["download-file"] ?? "");
    if($fname===""){return fw_progress_bad_value("download-file");}
    header("content-type: application/x-javascript");

    echo $tpl->framework_buildjs("/proxy/logs/compress?name=".rawurlencode($fname),
        "squid.access.compress.progress",
        "squid.access.compress.log",
        "progress-firehol-restart",
        "document.location.href='/$page?download-final=".urlencode($fname)."'"
    );
    return true;
}
// base64 of a squid log file name, re-encoded canonically; "" when the decoded name is not
// a plain file name ([A-Za-z0-9._-], no "..").
function fw_proxy_logs_name($b64):string{
    $b64=str_replace(" ","+",trim((string)$b64));
    $name=base64_decode($b64,true);
    if($name===false || !preg_match('/^[A-Za-z0-9._-]+$/',$name) || strpos($name,"..")!==false){return "";}
    return base64_encode($name);
}
function download_final():bool{
    if(!fw_progress_page_priv("AsProxyMonitor")){return false;}
    // Read from PROGRESS_DIR: a plain file name only (no "../").
    $fname=base64_decode(fw_proxy_logs_name($_GET["download-final"] ?? ""));
    if($fname===""){return false;}
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
	$aclid=intval($_GET["enabled-js"]);
    $row=acls_api_get("/proxy/logs/acls/$aclid");
    if(!is_array($row)){echo "entry not found";return true;}
    $enabled=intval($row["enabled"] ?? 0);
    if($enabled==1){$enabled=0;}else{$enabled=1;}
    acls_api_send("PUT","/proxy/logs/acls/$aclid",["enabled"=>$enabled]);
    return true;
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

function rule_delete(){
	$aclid=intval($_POST["delete-rule"]);
    [$ok,$err]=acls_api_send("DELETE","/proxy/logs/acls/$aclid");
    if(!$ok){echo $err;}
}




function rule_id_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["ruleid-js"];
	$title="{new_rule}";

	if($id>0){
        $row=acls_api_get("/proxy/logs/acls/$id");
        if(is_array($row)){$title="{rule}: $id {$row["ruleName"]}";}
	}
	$title=$tpl->javascript_parse_text($title);
	$tpl->js_dialog($title,"$page?rule-popup=$id");
}



function rule_settings():bool{
	$aclid=intval($_GET["rule-settings"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ligne=["enabled"=>1,"xOrder"=>1,"ruleName"=>"","logType"=>0,"logConfig"=>""];
	$btname="{add}";
	$title="{new_rule}";
	$BootstrapDialog="BootstrapDialog1.close();";
	if($aclid>0){
		$btname="{apply}";
        $row=acls_api_get("/proxy/logs/acls/$aclid");
        if(is_array($row)){$ligne=$row;$title=$row["ruleName"];}
		$BootstrapDialog=null;
	}

	$logtype[0]="{write_logs_locally}";
	$logtype[1]="{do_not_log}";
	$logtype[2]="{send_to_syslog}";

	$tpl->field_hidden("rule-save", $aclid);
	$form[]=$tpl->field_text("rulename","{rulename}",$ligne["ruleName"],true);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_array_hash($logtype, "logtype", "{type}", intval($ligne["logType"]),true,null,false);
	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["xOrder"]);
	echo $tpl->form_outside($title,@implode("\n", $form),"{log_rule}",$btname,"LoadAjax('table-loader-proxy-logscenter','$page?table=yes');$BootstrapDialog","license");

    return true;
}

function rule_parameters_save():bool{
	$aclid=intval($_POST["parameters-save"]);
    $row=acls_api_get("/proxy/logs/acls/$aclid");
    if(!is_array($row)){echo "entry not found";return true;}
	$logconfig=unserialize(base64_decode($row["logConfig"] ?? ""));
    if(!is_array($logconfig)){$logconfig=[];}
    foreach ($_POST as $key=>$val){
		$logconfig[$key]=url_decode_special_tool($val);
	}
	$logconfig_new=base64_encode(serialize($logconfig));
    [$ok,$err]=acls_api_send("PUT","/proxy/logs/acls/$aclid",["logConfig"=>$logconfig_new]);
    if(!$ok){echo $err;}
    return true;
}


function rule_parameters():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $form=array();
	$aclid=intval($_GET["parameters"]);
    $row=acls_api_get("/proxy/logs/acls/$aclid");
    if(!is_array($row)){return true;}
	$logtype=intval($row["logType"] ?? 0);
	$logconfig=unserialize(base64_decode($row["logConfig"] ?? ""));
    if(!is_array($logconfig)){$logconfig=[];}
	$rulename=$row["ruleName"] ?? "";
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
	if(!$users->AsSquidAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}

	$aclid=intval($_POST["rule-save"]);
	$rulename=url_decode_special_tool($_POST["rulename"]);

	if($aclid==0){
        $body=[
            "ruleName"=>$rulename,
            "enabled"=>intval($_POST["enabled"] ?? 1),
            "xOrder"=>intval($_POST["zorder"] ?? 1),
            "logType"=>intval($_POST["logtype"] ?? 0),
        ];
        [$ok,$data]=acls_api_send("POST","/proxy/logs/acls",$body);
        if(!$ok){echo $data;return;}
	}else{
        $body=[
            "ruleName"=>$rulename,
            "enabled"=>intval($_POST["enabled"] ?? 1),
            "xOrder"=>intval($_POST["zorder"] ?? 1),
            "logType"=>intval($_POST["logtype"] ?? 0),
        ];
        [$ok,$err]=acls_api_send("PUT","/proxy/logs/acls/$aclid",$body);
        if(!$ok){echo $err;return;}
	}
}

function rule_tab(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["rule-popup"]);
    $row=acls_api_get("/proxy/logs/acls/$aclid");



	$array["{rule}"]="$page?rule-settings=$aclid";
	if($aclid>0){
		$logtype=intval($row["logType"] ?? 0);
		if($logtype<>1){
			$array["{parameters}"]="$page?parameters=$aclid";
		}
		$array["{objects}"]="fw.proxy.objects.php?aclid=$aclid&main-table=logs_sqacllinks&fast-acls=0";

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
        [$ok,$err]=acls_api_send("PUT","/proxy/logs/acls/$aclid/move",["dir"=>$apiDir]);
        if(!$ok){
            // Pentest 2026-11-04 : message d'erreur REST (donnee externe) place sans encodage dans le JS (XSS stockee).
            $errmsg=req_js($err);
            echo "alert('$errmsg');";return;
        }
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $aclid=0;
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
	$ARRAY["CMD"]="/proxy/logs/apply";
	$ARRAY["TITLE"]="{GLOBAL_ACCESS_CENTER}";
	$ARRAY["REFRESH-MENU"]=1;
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";

    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>";
    $btn[]="</div>";
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";



	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="<th>$rulename</th>";
	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='logs-rows'>";

	$jsAfter="LoadAjax('table-loader-proxy-logscenter','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$zlogtype[0]="{write_logs_locally}";
	$zlogtype[1]="{do_not_log}";
	$zlogtype[2]="{send_to_syslog}";

	$isRights=isRights();
	$allRules=acls_api_get("/proxy/logs/acls") ?? [];
	$TRCLASS=null;
	foreach($allRules as $index=>$ligne) {
		$TRCLASS=null;
			$rulename=$ligne["ruleName"];
			$rulenameenc=urlencode($rulename);
			// Pentest 2026-11-04 : ruleName (donnee externe) affiche sans encodage (XSS stockee).
			$rulenameH=req_html($rulename);
			$aclid=intval($ligne["id"]);
			$addlogtype=null;
			$logtype=intval($ligne["logType"]);
			$logconfig=unserialize(base64_decode($ligne["logConfig"] ?? ""));
            if(!is_array($logconfig)){$logconfig=[];}
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

			$explain=$tpl->_ENGINE_parse_body("{for_objects} ".proxy_objects($aclid)." {then} {$zlogtype[$ligne["logType"]]}$addlogtype");
			if(!$isRights){
				$up=null;
				$down=null;
				$delete=null;
			}

			$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
			$html[]="<td class=\"center\" id='$index'><button type='button' class='btn btn-default btn-bitbucket' OnClick=\"$js\" ><i class='fa fa-paste'></i></button></td>";
			$html[]="<td class=\"center\">$check</td>";
			$html[]="<td style='vertical-align:middle'>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$rulenameH:</a>&nbsp;&raquo;&nbsp;$explain</span></td>";
			$html[]="<td class=\"center\">$up&nbsp;&nbsp;$down</td>";
			$html[]="<td class=center>$delete</td>";
			$html[]="</tr>";

	}

	$FATAL_SQUID_ACCESS_LOG=null;
	$SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
	if($SquidNoAccessLogs==1){$FATAL_SQUID_ACCESS_LOG="<p class=text-danger>{FATAL_SQUID_ACCESS_LOG}</p>";}

	$TRCLASS=null;
	$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
	$html[]="<td class=\"center\">-</td>";
	$html[]="<td class=\"center\">-</td>";

    $LOGPATH=$tpl->td_href("/var/log/squid/access.log","{download}","Loadjs('$page?download-file=".base64_encode("access.log")."')");

	$html[]="<td style='vertical-align:middle'>".$tpl->_ENGINE_parse_body("<strong>{default}</strong>:</a>&nbsp;&raquo;&nbsp;{for_objects} {all} {then} {write_logs_locally} {to}")." $LOGPATH</span>$FATAL_SQUID_ACCESS_LOG</td>";
	$html[]="<td class=\"center\">&nbsp;&nbsp;</td>";
	$html[]="<td class=center><center>-</center></td>";
	$html[]="</tr>";


	$html[]="</tbody>";
	$html[]="";
	$html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='logs-pager'></span></div></div></div>";



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

	echo awc_table_style()
	    .@implode("\n", $html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"logs-rows","logs-pager");

}
function isRights():bool{
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
    return false;
}

function proxy_objects($aclid){
    $tablelink="logs_sqacllinks";
    $qProxy=new mysql_squid_builder(true);
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
