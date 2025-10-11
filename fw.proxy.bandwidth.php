<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["jstiny"])){js_tiny();exit;}
if(isset($_GET["js-example"])){js_example();exit;}
if(isset($_GET["js-example-create"])){js_example_create();exit;}
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
if(isset($_POST["none"])){exit;}
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"]);exit;}

page();


function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{bandwidth_rules_mini}","fas fa-filter",
        "{bandwidth_rules_mini_explain}","$page?table=yes","proxy-acls-bandwidth",
    "progress-acls3-restart",false,"table-acls3-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{bandwidth_rules_mini}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
	
}

function rule_delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["rule-delete-js"];
	$md=$_GET["md"];
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";
}

function js_example(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_prompt("{example}","{example_create_ask}","fas fa-hat-wizard",$page,null,
        "Loadjs('$page?js-example-create')");
}
function js_example_create(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $FF[]="`ID`";
    $FF[]="`rulename`";
    $FF[]="`limit_network`";
    $FF[]="`network_max_size`";
    $FF[]="`network_bandwidth`";
    $FF[]="`limit_client`";
    $FF[]="`client_maxsize`";
    $FF[]="`client_bandwidth`";
    $FF[]="`enabled`";
    $FF[]="`zorder`";

    $FA[]="1";
    $FA[]="'Limit to 1MBs, heavy sites'";
    $FA[]="'1'";
    $FA[]="'0'";
    $FA[]="'1024'";
    $FA[]="'1'";
    $FA[]="'1'";
    $FA[]="'512'";
    $FA[]=1;
    $FA[]=0;



    $sql="INSERT OR IGNORE INTO squid_http_bandwidth_acls (".@implode(",", $FF).") VALUES (".@implode(",", $FA).")";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}


    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDBANDWIDTH_1'");

    if(intval($ligne["ID"])==0){
        $sql="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`)
	VALUES ('Websites for bandwidth limit','dstdomain','1','','WIZARDBANDWIDTH_1','0',0);";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='WIZARDBANDWIDTH_1'");
    }
    if(intval($ligne["ID"])==0){
        $tpl->js_mysql_alert("Unable to find WIZARDBANDWIDTH_1 group");
        return;
    }

    $gpid=intval($ligne["ID"]);
    $f["ea.com"]=true;
    $f["eamobile.com"]=true;
    $f["microsoft.com"]=true;
    $f["apple.com"]=true;
    $f["playfirst.com"]=true;
    $f["msecnd.net"]=true;
    $f["windowsupdate.com"]=true;
    $f["googlevideo.com"]=true;
    $f["youtube.com"]=true;
    $f["steamcommunity.com"]=true;
    $f["steampowered.com"]=true;
    $f["facebook.com"]=true;
    $f["steamstatic.com"]=true;
    $f["^video.googleusercontent.com"]=true;
    $f["nflxvideo.net"]=true;
    $f["netflix.com"]=true;
    $f["nflximg.net"]=true;
    $f["nflxext.com"]=true;

    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$gpid");
    $date=date("Y-m-d H:i:s");
    $uid=$_SESSION["uid"];
    foreach ($f as $item=>$none) {
        $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) VALUES ('$gpid','$item','$date','$uid',1)");

    }

    $aclid=1;
    $md5=md5($aclid.$gpid);
    $q->QUERY_SQL("DELETE FROM squid_http_bandwidth_link WHERE zmd5='$md5'");
    $q->QUERY_SQL("INSERT OR IGNORE INTO squid_http_bandwidth_link (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid',1)");

    header("content-type: application/x-javascript");
    echo "LoadAjax('table-acls3-rules','$page?table=yes');";

}

function rule_enable(){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	header("content-type: application/x-javascript");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM squid_http_bandwidth_acls WHERE ID='{$_GET["enable-js"]}'");
	if(intval($ligne["enabled"])==0){
		
		$js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
		$enabled=1;
		}
		else{
			$js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
			$enabled=0;
		}
	
	$q->QUERY_SQL("UPDATE squid_http_bandwidth_acls SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return;}
    admin_tracks("Set a bandwidth ACL rule ID {$_GET["enable-js"]} to enable=$enabled");
	echo $js;
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval(trim($_GET["rule-id-js"]));
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_http_bandwidth_acls WHERE ID='$ID'");
	$tpl->js_dialog5("{rule}: $ID {$ligne["rulename"]}","$page?rule-tabs=$ID",950);
}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";
	$RefreshTable=base64_encode("LoadAjax('table-acls3-rules','$page?table=yes');");
	$array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=squid_http_bandwidth_link&RefreshTable=$RefreshTable";
	echo $tpl->tabs_default($array);
	
}


function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-settings"]);
	$tpl->CLUSTER_CLI=true;

	if($ID==0){echo $tpl->FATAL_ERROR_SHOW_128("WRONG ID NUMBER");return;}

	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_http_bandwidth_acls WHERE ID='$ID'");

    $form[]=$tpl->field_hidden("rule-save", "$ID");
    $form[]=$tpl->field_hidden("network_max_size", 0);
    $form[]=$tpl->field_hidden("client_maxsize", 0);
    $form[]=$tpl->field_text("rulename", "{rulename}",$tpl->utf8_encode($ligne["rulename"]), true);

    $form[]=$tpl->field_section("{global_limitation}");
    $form[]=$tpl->field_checkbox("limit_network","{limit_network}",$ligne["limit_network"],"network_max_size,network_bandwidth");
  //  $form[]=$tpl->field_numeric("network_max_size","{maximum_illimited_size} (MB)",$ligne["network_max_size"],"{maximum_illimited_size_explain}");
    $form[]=$tpl->field_kbps("network_bandwidth","{max_download_rate}",$ligne["network_bandwidth"]);

    $form[]=$tpl->field_section("{per_client_limit}");
    $form[]=$tpl->field_checkbox("limit_client","{limitb_client}",$ligne["limit_client"],"client_maxsize,client_bandwidth");
 //   $form[]=$tpl->field_numeric("client_maxsize","{maximum_illimited_size} (MB)",$ligne["client_maxsize"],"{maximum_illimited_size_explain}");
    $form[]=$tpl->field_kbps("client_bandwidth","{max_download_rate}",$ligne["client_bandwidth"]);
	$jsafter="LoadAjax('table-acls3-rules','$page?table=yes');";

	$html=$tpl->form_outside($tpl->utf8_encode($ligne["rulename"]), @implode("\n", $form),"{form_bandwidth_explain}","{apply}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$ID=$_POST["rule-save"];
	$rulename=$q->sqlite_escape_string2($tpl->utf8_decode($_POST["rulename"]));



    $FF[]="`rulename`";
    $FF[]="`limit_network`";
    $FF[]="`network_max_size`";
    $FF[]="`network_bandwidth`";
    $FF[]="`limit_client`";
    $FF[]="`client_maxsize`";
    $FF[]="`client_bandwidth`";


    $FA[]="'$rulename'";
    $FA[]="'{$_POST["limit_network"]}'";
    $FA[]="'{$_POST["network_max_size"]}'";
    $FA[]="'{$_POST["network_bandwidth"]}'";
    $FA[]="'{$_POST["limit_client"]}'";
    $FA[]="'{$_POST["client_maxsize"]}'";
    $FA[]="'{$_POST["client_bandwidth"]}'";


	
	foreach ($FF as $index=>$key){$FB[]="$key={$FA[$index]}";}
	
	$sql="UPDATE squid_http_bandwidth_acls SET ".@implode(",", $FB)." WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
    admin_tracks("update $rulename bandwidth ACL rule settings");
	
	$c=0;
	$sql="SELECT ID FROM squid_http_bandwidth_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_http_bandwidth_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}
	
	
}

function new_rule_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	return $tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function HEADERS_ARRAY():array{
    $HEADERSZ=array();
	$ql=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT * FROM http_headers ORDER BY HeaderName";
	$resultsHeaders=$ql->QUERY_SQL($sql);
	foreach ($resultsHeaders as $index=>$ligneHeaders){
		$HEADERSZ[$ligneHeaders["HeaderName"]]=$ligneHeaders["HeaderName"];
	}
	return $HEADERSZ;
}

function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;

	$form[]=$tpl->field_hidden("newrule", "yes");
    $form[]=$tpl->field_hidden("network_max_size", 0);
    $form[]=$tpl->field_hidden("client_maxsize", 0);

	$form[]=$tpl->field_text("rulename", "{rule_name}", null,true);

    $form[]=$tpl->field_section("{global_limitation}");
	$form[]=$tpl->field_checkbox("limit_network","{limit_network}",0,"network_bandwidth");
    //$form[]=$tpl->field_numeric("network_max_size","{maximum_illimited_size} (MB)",0,"{maximum_illimited_size_explain}");
    $form[]=$tpl->field_kbps("network_bandwidth","{max_download_rate}",512,null);

    $form[]=$tpl->field_section("{per_client_limit}");
    $form[]=$tpl->field_checkbox("limit_client","{limitb_client}",0,"client_maxsize,client_bandwidth");
    //$form[]=$tpl->field_numeric("client_maxsize","{maximum_illimited_size} (MB)",0,"{maximum_illimited_size_explain}");
    $form[]=$tpl->field_kbps("client_bandwidth","{max_download_rate}",512,null);


	$jsafter="LoadAjax('table-acls3-rules','$page?table=yes');BootstrapDialog1.close();BootstrapDialog1.close();";
	
	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),"{form_bandwidth_explain}","{add}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}



function rule_move(){
	$tpl=new template_admin();
	$ID=$_GET["acl-rule-move"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT zorder FROM squid_http_bandwidth_acls WHERE `ID`='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
	$xORDER_ORG=intval($ligne["zorder"]);
	$xORDER=$xORDER_ORG;
	
	
	if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE squid_http_bandwidth_acls SET zorder=$xORDER WHERE `ID`='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if($_GET["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE squid_http_bandwidth_acls SET zorder=$xORDER2 WHERE `ID`<>'$ID' AND zorder=$xORDER";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	}
	if($_GET["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE squid_http_bandwidth_acls SET zorder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	}

	$c=0;
	$sql="SELECT ID FROM squid_http_bandwidth_acls ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);

	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_http_bandwidth_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if($GLOBALS["VERBOSE"]){echo "UPDATE squid_http_bandwidth_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}\n";}
		$c++;
	}
    $sql="SELECT ID FROM squid_http_bandwidth_acls WHERE enabled=1 ORDER BY zorder";
    $results = $q->QUERY_SQL($sql);
    $c=1;
    foreach($results as $index=>$ligne) {
        $q->QUERY_SQL("UPDATE squid_http_bandwidth_acls SET delay_pool_number=$c WHERE `ID`={$ligne["ID"]}");
        if($GLOBALS["VERBOSE"]){echo "UPDATE squid_http_bandwidth_acls SET delay_pool_number=$c WHERE `ID`={$ligne["ID"]}\n";}
        $c++;
    }

}


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

	

    $html[]="<table id='table-webfilter-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%'>$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rulename}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	if($PowerDNSEnableClusterSlave==0) {
        $html[] = "<th data-sortable=true class='text-capitalize center'>$enabled</th>";
        $html[] = "<th data-sortable=false></th>";
        $html[] = "<th data-sortable=false></th>";

    }
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-proxyheader-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	
	$results=$q->QUERY_SQL("SELECT * FROM squid_http_bandwidth_acls ORDER BY zorder");
	$TRCLASS=null;
	
	
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$MUTED=null;
		$md=md5(serialize($ligne));
		$ID=$ligne["ID"];
		$ligne['rulename']=$tpl->utf8_encode($ligne['rulename']);
		
		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID&md=$md')","AsSquidAdministrator");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if($ligne["enabled"]==0){$MUTED=" text-muted";}
		
	
		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');","AsSquidAdministrator");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');","AsSquidAdministrator");
		$enable_js="Loadjs('$page?enable-js=$ID')";
		
		$html[]="<tr class='$TRCLASS{$MUTED}' id='$md'>";
		$html[]="<td id=$index class=\"center\" style='width:1%' nowrap>{$ligne["zorder"]}</td>";
		$html[]="<td style='vertical-align:middle;;width:1%' nowrap>". $tpl->td_href($ligne["rulename"],"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
        if($PowerDNSEnableClusterSlave==0) {
            $html[] = "<td class='center' style='width:1%'  nowrap>" . $tpl->icon_check($ligne["enabled"], $enable_js,null,"AsSquidAdministrator") . "</td>";
            $html[] = "<td style='vertical-align:middle;width:1%' class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
            $html[] = "<td style='vertical-align:middle;width:1%' class='center' nowrap>$delete</td>";
        }
		$html[]="</tr>";
	
	}
	
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
<script>
Loadjs('$page?jstiny=yes')
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-webfilter-rules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) }); 
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function js_tiny(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $add="Loadjs('$page?newrule-js=yes',true);";
    $users=new usersMenus();

    $jsrestart=$tpl->framework_buildjs("/proxy/acls/bandwidth","squid.bandwww.progress","squid.bandwww.txt","progress-acls3-restart","LoadAjax('table-acls3-rules','$page?table=yes');");

    $jshelp="s_PopUpFull('https://wiki.articatech.com/proxy-service/acls/bandwidth-limiting',1024,768,'Bandwidth Limiting')";

    if($PowerDNSEnableClusterSlave==0) {
        if($users->AsSquidAdministrator) {
            $topbuttons[] = array($add, ico_plus, "{new_rule}");
        }
    }
    if($users->AsSquidAdministrator) {
        $topbuttons[] = array($jsrestart, ico_save, "{apply_rules}");
    }
    $topbuttons[] = array($jshelp, "fas fa-question-circle", "Wiki");
    $jsexample="Loadjs('$page?js-example=yes');";

    if($PowerDNSEnableClusterSlave==0) {

        $ligne = $q->mysqli_fetch_array("SELECT * FROM squid_http_bandwidth_acls WHERE ID=1");
        if (strlen(trim($ligne["rulename"])) == 0) {
            $topbuttons[] = array($jsexample, "fas fa-hat-wizard", "{example}");

        }
    }
    $TINY_ARRAY["TITLE"]="{bandwidth_rules_mini}";
    $TINY_ARRAY["ICO"]="fas fa-filter";
    $TINY_ARRAY["EXPL"]="{bandwidth_rules_mini_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}


function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");


	$TempName=md5(time());
	$rulename=sqlite_escape_string2($tpl->utf8_decode($_POST["rulename"]));

	$ligne=$q->mysqli_fetch_array("SELECT ID FROM squid_http_bandwidth_acls ORDER BY ID LIMIT 1" );
    $ID=intval($ligne["ID"]);
	if($ID==0){
        $FF[]="`ID`";
    }
	$FF[]="`rulename`";
	$FF[]="`limit_network`";
	$FF[]="`network_max_size`";
	$FF[]="`network_bandwidth`";
	$FF[]="`limit_client`";
	$FF[]="`client_maxsize`";
	$FF[]="`client_bandwidth`";
	$FF[]="`enabled`";
	$FF[]="`zorder`";

	if($ID==0){
        $FA[]="10";
    }
	$FA[]="'$TempName'";
	$FA[]="'{$_POST["limit_network"]}'";
	$FA[]="'{$_POST["network_max_size"]}'";
	$FA[]="'{$_POST["network_bandwidth"]}'";
	$FA[]="'{$_POST["limit_client"]}'";
	$FA[]="'{$_POST["client_maxsize"]}'";
	$FA[]="'{$_POST["client_bandwidth"]}'";
	$FA[]=1;
	$FA[]=0;
	

	
	$sql="INSERT INTO squid_http_bandwidth_acls (".@implode(",", $FF).") VALUES (".@implode(",", $FA).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	admin_tracks("Create a new $rulename bandwidth ACL rule ");
	
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM squid_http_bandwidth_acls WHERE rulename='$TempName'");
	$LASTID=$ligne["ID"];
	
	$q->QUERY_SQL("UPDATE squid_http_bandwidth_acls SET rulename='$rulename' WHERE ID='$LASTID'");
	
	$c=0;
	$sql="SELECT ID FROM squid_http_bandwidth_acls WHERE enabled=1 ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE squid_http_bandwidth_acls SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}

	$sql="SELECT ID FROM squid_http_bandwidth_acls ORDER BY zorder";
    $results = $q->QUERY_SQL($sql);
    $c=1;
    foreach($results as $index=>$ligne) {
        $q->QUERY_SQL("UPDATE squid_http_bandwidth_acls SET delay_pool_number=$c WHERE `ID`={$ligne["ID"]}");
        if($GLOBALS["VERBOSE"]){echo "($index) UPDATE squid_http_bandwidth_acls SET delay_pool_number=$c WHERE `ID`={$ligne["ID"]}\n";}
        $c++;
    }
}

function rule_delete($ID):bool{
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM squid_http_bandwidth_link WHERE aclid='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	$q->QUERY_SQL("DELETE FROM squid_http_bandwidth_acls WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
    admin_tracks("Delete a bandwidth ACL rule ID $ID");
	return true;
	
}
function EXPLAIN_THIS_RULE($ID){
    $tpl=new templates();
    $THEN=array();
	
	$acls=new squid_acls_groups();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_http_bandwidth_acls WHERE ID=$ID");
    $objects=$acls->getobjectsNameFromAclrule($ligne['ID'],"black","squid_http_bandwidth_link");
	if(count($objects)>0){$THEN[]="{for_objects} ". @implode(", {and} ", $objects)." {then}";}
    $limit_network=intval($ligne["limit_network"]);
	$network_max_size=intval($ligne["network_max_size"]);
	$network_bandwidth=intval($ligne["network_bandwidth"]);
	$limit_client=intval($ligne["limit_client"]);
	$client_maxsize=intval($ligne["client_maxsize"]);
	$client_bandwidth=intval($ligne["client_bandwidth"]);
    $NOLIMIT1=false;
    $value_mbps=round($network_bandwidth/125);
    $unit="Mpbps";
    if($value_mbps>1000){
        $unit="Gbps";
        $value_mbps=round($network_bandwidth/125000);}


    if($limit_network==1){

        if($network_max_size>0){
            $THEN[]="{after_download_of} {$network_max_size}MB";
        }
        if($network_bandwidth>0) {
            $THEN[] = "{limit_bandwidth} $network_bandwidth KO/s ($value_mbps$unit)";
        }else{
            $NOLIMIT1=true;
            $THEN[] = "{do_not_limit_bandwidth}";
        }
    }

    if($limit_network==1){
        if($limit_client==1){
            $THEN[]="{and}";
        }
    }

    if($limit_client==1){

        $value_mbps=round($client_bandwidth/125);
        $unit="Mpbps";
        if($value_mbps>1000){
            $unit="Gbps";
            $value_mbps=round($client_bandwidth/125000);}

        if($client_maxsize>0){
            $THEN[]="{after_download_of} {$client_maxsize}MB";
        }
        if($client_bandwidth>0) {
            $THEN[] = "{limitb_client} $client_bandwidth KO/s ($value_mbps$unit)";
        }else{
            if(!$NOLIMIT1){
                $THEN[] = "{do_not_limit_bandwidth}";
            }
        }
    }

    $THEN_TEXT=@implode(" ",$THEN);

    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body($THEN_TEXT);
    }

    $page=CurrentPageName();
    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>$THEN_TEXT</span>");



}

