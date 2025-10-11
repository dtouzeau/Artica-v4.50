<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["file-uploaded"])){import_uploaded_js();exit;}
if(isset($_GET["export-download"])){export_download();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-search"])){table_search();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-options"])){rule_options();exit;}
if(isset($_POST["rule-options"])){rule_options_save();exit;}
if(isset($_GET["move-js"])){rule_move_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){rule_delete();exit;}
if(isset($_GET["rules-proxy-secure"])){rule_proxies_secure();exit;}
if(isset($_GET["rules-proxy-enable"])){rules_proxies_enabled();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["parameters"])){rule_parameters();exit;}
if(isset($_POST["parameters-save"])){rule_parameters_save();exit;}

if(isset($_GET["rule-proxies"])){rule_proxies();exit;}
if(isset($_GET["rules-proxies-edit"])){rule_proxies_edit();exit;}
if(isset($_GET["rule-proxies-table"])){rule_proxies_table();exit;}
if(isset($_GET["rule-proxies-newjs"])){rule_proxies_new_js();exit;}
if(isset($_GET["rule-proxies-newpopup"])){rule_proxies_new_popup();exit;}
if(isset($_GET["rules-proxies-move"])){rule_proxies_move();exit;}
if(isset($_GET["rules-proxy-unlink"])){rule_proxies_unlink();exit;}
if(isset($_GET["rules-move"])){rules_move();exit;}
if(isset($_POST["rule-proxy"])){rule_proxies_new_save();exit;}
if(isset($_GET["builded-script-js"])){builded_script_js();exit;}
if(isset($_GET["builded-script-popup"])){builded_script_popup();exit;}
if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{wpad_rules}",
        "fa fa-align-justify",
        "{wpad_service_explain}",
        "$page?table=yes",
        "proxypac-rules","progress-proxypac-restart",false,"table-loader-proxy-pac");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_PROXY_PAC}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function export_download():bool{
    $ruleid=intval($_GET["export-download"]);
    $destfile=PROGRESS_DIR."/pac.rule.$ruleid.gz";
    $fsize = filesize($destfile);
    header('Content-type: application/x-gzip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"pac.rule.$ruleid.gz\"");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($destfile);
    @unlink($destfile);
    return true;
}
function builded_script_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["builded-script-js"]);
    $tpl->js_dialog3("{proxy_pac}", "$page?builded-script-popup=$id");
    return true;
}
function import_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tpl->js_dialog3("{import}:{rule}", "$page?import-popup=yes&function=$function");
    return true;
}
function import_popup():bool{
    $function=$_GET["function"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div id='import-rule-progress'></div>";
    $html[]="<p>{import}:{rule} {APP_PROXY_PAC}</p>";
    $html[]="<div class=center>".$tpl->button_upload("{upload_your_file_here} (*.gz)",$page,null,"&function=$function")."</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function import_uploaded_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);
    $function=$_GET["function"];

    $js=$tpl->framework_buildjs("proxypac.php?import=$fileencode",
        "pac.rule.import.progress",
        "pac.rule.import.log",
        "import-rule-progress","dialogInstance3.close();$function();"
    );
    header("content-type: application/x-javascript");
    echo "$js\n";

    return true;
}
function builded_script_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=intval($_GET["builded-script-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wpad_rules WHERE ID='$ID'");
    $title=$ligne["rulename"];
    $script_file="/home/squid/proxy_pac_rules/$ID/proxy.pac";
    $form[]=$tpl->field_textareacode("dfszfc",null,@file_get_contents($script_file));
    echo $tpl->form_outside($title,$form,null,null);
    return true;

}



function rule_proxies_new_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $id=intval($_GET["rule-proxies-newjs"]);
    $tpl->js_dialog3("{new_proxy}", "$page?rule-proxies-newpopup=$id&function=$function");
    return true;
}
function rule_proxies_move(){
    header("content-type: application/x-javascript");
    $zmd5=$_GET["rules-proxies-move"];
    $direction=$_GET["direction"];
    $function=$_GET["function"];

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wpad_destination WHERE zmd5='$zmd5'");
    $aclid=$ligne["aclid"];
    $LastOrder=$ligne["zorder"];
    $proxyserver=$ligne["proxyserver"];
    $proxyport=$ligne["proxyport"];

    if($direction=="up"){
        $NewOrder=$ligne["zorder"]-1;
    }else{
        $NewOrder=$ligne["zorder"]+1;
    }

    if(isset($_GET["rules-proxies-zorder"])){
        if(is_numeric($_GET["rules-proxies-zorder"])){
            $NewOrder=$_GET["rules-proxies-zorder"];
        }
    }

    $q->QUERY_SQL("UPDATE wpad_destination SET zorder='$NewOrder' WHERE zmd5='$zmd5'");
    $q->QUERY_SQL("UPDATE wpad_destination SET zorder='$LastOrder' WHERE zorder='$NewOrder' AND aclid='$aclid' AND zmd5<>'$zmd5'");

    $sql="SELECT *  FROM wpad_destination WHERE aclid='$aclid' ORDER BY `zorder`";
    $results = $q->QUERY_SQL($sql);
    $c=0;
    foreach($results as $index=>$ligne) {
        $zmd5=$ligne["zmd5"];
        $q->QUERY_SQL("UPDATE wpad_destination SET zorder='$c' WHERE zmd5='$zmd5'");
        $c++;

    }
    $page=CurrentPageName();
    admin_tracks("Proxy.pac:Move proxy.pac proxy $proxyserver:$proxyport $aclid to $direction");
    echo "$function();";

}
function rule_proxies_new_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["rule-proxies-newpopup"]);
    $function=$_GET["function"];
    $proxyserver=null;
    $proxyport=8080;
    $bt="{add}";
    $title="{new_proxy}";
    if(isset($_GET["md"])){
        $tpl->field_hidden("md",$_GET["md"]);
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM wpad_destination WHERE zmd5='{$_GET["md"]}'");
        $proxyserver=$ligne["proxyserver"];
        $proxyport=$ligne["proxyport"];
        $bt="{apply}";
        $title="$proxyserver {listen_port} $proxyport";
    }

    $js[]="LoadAjaxSilent('rule-proxies-table','$page?rule-proxies-table=$id');";
    $js[]="dialogInstance3.close();";
    $js[]="$function();";

    $form[]=$tpl->field_hidden("rule-proxy", $id);
    $form[]=$tpl->field_text("hostname", "{hostname}", $proxyserver,true);
    $form[]=$tpl->field_numeric("port","{port}",$proxyport);
    $form[]=$tpl->field_checkbox("secure","{UseSSL}",intval($ligne["secure"]));

    echo $tpl->form_outside($title, @implode("\n", $form),null,$bt, @implode("", $js),"AsSquidAdministrator");
}
function rule_proxies_new_save(){
    $tpl=new template_admin();
    $ID=$_POST["rule-proxy"];
    if(!is_numeric($ID)){echo "No ID?\n";}
    if($ID==0){echo "No ID -> 0 ?\n";}
    $hostname=$_POST["hostname"];
    $port=$_POST["port"];
    if(!is_numeric($port)){$port="3128";}
    if($hostname==null){$hostname="1.2.3.4";}
    $secure=$_POST["secure"];
    $zmd5=md5("$ID$hostname$port");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");


    if(isset($_POST["md"])){
        $q->QUERY_SQL("UPDATE wpad_destination SET proxyserver='{$hostname}'
,proxyport='$port',secure='$secure' WHERE zmd5='{$_POST["md"]}'");

        if(!$q->ok){echo "jserror: ".$tpl->javascript_parse_text($q->mysql_error);return;}
        admin_tracks("Proxy.pac:Saving proxy.pac proxy  $hostname:$port");
        return;
    }

    $q->QUERY_SQL("INSERT INTO wpad_destination (zmd5,aclid,proxyserver,proxyport,zorder,secure,enabled)
			VALUES ('$zmd5','$ID','$hostname','$port',0,$secure,1)");
    if(!$q->ok){echo "jserror: ".$tpl->javascript_parse_text($q->mysql_error);return;}

    admin_tracks("Proxy.pac:Creating new proxy.pac proxy $hostname:$port");

}

function rule_proxies(){
    $page=CurrentPageName();
    $function=$_GET["function"];
    $id=intval($_GET["rule-proxies"]);
    $html="<div id='rule-proxies-table' style='margin-top:20px'></div>
	<script>LoadAjaxSilent('rule-proxies-table','$page?rule-proxies-table=$id&function=$function');</script>
	";
    echo $html;
}

function rule_proxies_edit(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=$_GET["rules-proxies-edit"];
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wpad_destination WHERE zmd5='$md'");

    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    $proxyserver=$ligne["proxyserver"];
    $proxyport=$ligne["proxyport"];
    $id=intval($ligne["aclid"]);
    //proxyserver,proxyport
    $tpl->js_dialog3("{$proxyserver}:$proxyport", "$page?rule-proxies-newpopup=$id&md=$md");
}

function rule_proxies_table(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();

	$id=intval($_GET["rule-proxies-table"]);
	$add="Loadjs('$page?rule-proxies-newjs=$id&function=$function');";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_proxy} </label>";
	$html[]="</div>";
	$html[]="<table id='table-proxypac-proxies-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true data-type='text'>{proxy}</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>Secure Proxy</th>";
	$html[]="<th data-sortable=false style='width:1%'>{order}</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>{enable}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$sql="SELECT * FROM wpad_destination WHERE aclid=$id ORDER BY zorder";
	
	$results=$q->QUERY_SQL($sql);
	$TRCLASS=null;
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$proxyserver=$ligne["proxyserver"];
		$proxyport=$ligne["proxyport"];
		$mkey=$ligne["zmd5"];
        $secure_ico=null;

        $proxyserver=$tpl->td_href($proxyserver,null,"Loadjs('$page?rules-proxies-edit=$mkey&function=$function')");
		$up=$tpl->icon_up("Loadjs('$page?rules-proxies-move=$mkey&direction=up&function=$function')");
		$down=$tpl->icon_down("Loadjs('$page?rules-proxies-move=$mkey&direction=down&function=$function')");
		$delete=$tpl->icon_delete("Loadjs('$page?rules-proxy-unlink=$mkey&function=$function')","AsSquidAdministrator");

		$secure=$tpl->icon_check($ligne["secure"],
            "Loadjs('$page?rules-proxy-secure=$mkey')",
            null,"AsSquidAdministrator");

        if(intval($ligne["secure"])==1){
            $secure_ico="&nbsp;<span class='label label-primary'>Secure Proxy</span>";
        }

        $enabled=$tpl->icon_check($ligne["enabled"],
            "Loadjs('$page?rules-proxy-enable=$mkey&function=$function')",
            null,"AsSquidAdministrator");

	
		$html[]="<tr class='$TRCLASS' id='$mkey'>";
		$html[]="<td><strong>{$proxyserver}:{$proxyport}{$secure_ico}</strong></td>";
        $html[]="<td style='width:1%;' nowrap class='center'>$secure</td>";
		$html[]="<td style='width:1%' nowrap>$up&nbsp;&nbsp;$down</td>";
        $html[]="<td style='width:1%;' nowrap class='center'>$enabled</td>";
		$html[]="<td style='width:1%' class='center' nowrap>$delete</center></td>";
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
	
</script>";	

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function rule_proxies_secure(){
    $md=$_GET["rules-proxy-secure"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT secure,proxyserver,proxyport FROM wpad_destination WHERE zmd5='$md'");
    $proxyserver=$ligne["proxyserver"];
    $proxyport=$ligne["proxyport"];
    $secure=intval($ligne["secure"]);
    if($secure==1){$secure=0;}else{$secure=1;}
    $q->QUERY_SQL("UPDATE wpad_destination set secure=$secure WHERE zmd5='$md'");
    admin_tracks("Proxy.pac: Set Proxy $proxyserver:$proxyport to Secure Proxy = $secure");

}
function rules_proxies_enabled(){
    $md=$_GET["rules-proxy-enable"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $function=$_GET["function"];
    $tpl=new template_admin();
    $ligne=$q->mysqli_fetch_array("SELECT enabled,proxyserver,proxyport FROM wpad_destination WHERE zmd5='$md'");
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        return false;
    }
    $proxyserver=$ligne["proxyserver"];
    $proxyport=$ligne["proxyport"];
    $secure=intval($ligne["enabled"]);
    if($secure==1){
        admin_tracks("Proxy.pac: Set Proxy $proxyserver:$proxyport to inactive");
        $secure=0;
    }else{
        admin_tracks("Proxy.pac: Set Proxy $proxyserver:$proxyport to active");
        $secure=1;
    }
    $q->QUERY_SQL("UPDATE wpad_destination set enabled=$secure WHERE zmd5='$md'");
    $page=CurrentPageName();
    echo "$function();";
    return true;
}

function enabled_js(){
	$aclid=$_GET["enabled-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM wpad_rules WHERE ID='$aclid'");
	$enabled=$ligne["enabled"];
	if($enabled==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE wpad_rules SET enabled=$enabled WHERE ID=$aclid");
	if(!$q->ok){echo $q->mysql_error;}
}

function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["delete-rule-js"]);
	
	$md=$_GET["md"];
	$jsafet="$('#$md').remove();";
	
	$tpl->js_confirm_delete("{$_GET["name"]} #$aclid", "delete-rule", $aclid,$jsafet);

	
}

function rule_delete(){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ID=$_POST["delete-rule"];
    admin_tracks("Proxy.pac:Delete proxy.pac rule $ID");
	$q->QUERY_SQL("DELETE FROM `wpad_rules` WHERE ID='$ID'");
	$q->QUERY_SQL("DELETE FROM `wpad_sources_link` WHERE aclid='$ID'");
    $q->QUERY_SQL("DELETE FROM `wpad_black_link` WHERE aclid='$ID'");
	$q->QUERY_SQL("DELETE FROM `wpad_white_link` WHERE aclid='$ID'");
	$q->QUERY_SQL("DELETE FROM `wpad_destination` WHERE aclid='$ID'");
	$q->QUERY_SQL("DELETE FROM `wpad_events` WHERE aclid='$ID'");
}
function rule_proxies_unlink(){
    $function=$_GET["function"];
	$tpl=new template_admin();
	$zmd5=$_GET["rules-proxy-unlink"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM wpad_destination where `zmd5`='$zmd5'");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    admin_tracks("Proxy.pac:Unlink proxy.pac proxy $zmd5");
	
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "$('#$zmd5').remove();\n$function();";
	
	
}



function rule_id_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["ruleid-js"];
	$title="{new_rule}";
    $function=$_GET["function"];
	if($id>0){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$ligne=$q->mysqli_fetch_array("SELECT rulename FROM wpad_rules WHERE ID='$id'");
		$title="{rule}: $id {$ligne["rulename"]}";
	}
	$title=$tpl->javascript_parse_text($title);
	$tpl->js_dialog($title,"$page?rule-popup=$id&function=$function");
}



function rule_options():bool{
    $function=$_GET["function"];
	$ID=intval($_GET["rule-options"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
    if(!$q->FIELD_EXISTS("office365","wpad_rules")){
        $q->QUERY_SQL("ALTER TABLE wpad_rules add office365 INTEGER DEFAULT 0");
    }


	$ligne=array();
	$button="{apply}";
	$ligne["enabled"]=1;
	$ligne["zorder"]=1;
	$btname="{add}";
    $ligne["rulename"]="{new_rule}";
	$BootstrapDialog="BootstrapDialog1.close();$function();";
	if($ID>0){
		$btname="{apply}";
		$ligne=$q->mysqli_fetch_array("SELECT * FROM wpad_rules WHERE ID='$ID'");
		$BootstrapDialog="$function();";
	}
	$tpl->field_hidden("rule-options", $ID);
	$form[]=$tpl->field_text("rulename","{rule_name}",$ligne["rulename"],true,null,false);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true,'{enabled}');
	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"]);
	$form[]=$tpl->field_checkbox("dntlhstname","{dnot_proxy_localnames}",$ligne["dntlhstname"],false,'{dnot_proxy_localnames_explain}');
	$form[]=$tpl->field_checkbox("isResolvable","{dnot_proxy_lisResolvable}",$ligne["isResolvable"],false,'{dnot_proxy_lisResolvable_explain}');

    $form[]=$tpl->field_checkbox("office365","{dnot_proxy_office365}",$ligne["office365"],false,'{dnot_proxy_office365_explain}');

	$form[]=$tpl->field_checkbox("FinishbyDirect","{return_direct_mode}",$ligne["FinishbyDirect"],false,'{wpad_return_direct_mode}');
    $form[]=$tpl->field_checkbox("PAC_LBL","{load_balancer}",$ligne["LBL"],false,'{load_balancer}');

	$form[]=$tpl->field_section("{outofoffice_policy}");
	$form[]=$tpl->field_checkbox("NomadeMode","{outofoffice_enable}",$ligne["NomadeMode"],"NomadeResolve","{wpad_out_of_office_explain}");
    $form[]=$tpl->field_text("NomadeResolve","{hostname_to_resolve}",$ligne["NomadeResolve"]);

    $exports=$tpl->framework_buildjs(
        "proxypac.php?export=$ID",
        "pac.rule.export.progress",
        "pac.rule.export.log",
        "progress-ppacrule-$ID",
        "document.location.href='/$page?export-download=$ID'");

    $tpl->form_add_button("{export}",$exports);
    $html[]="<div id='progress-ppacrule-$ID'></div>";
	$html[]=$tpl->form_outside("",$form,null,$btname,"$BootstrapDialog","AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}




function rule_options_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
	$users=new usersMenus();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	if(!$users->AsSquidAdministrator){echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");return;}

	if($_POST["NomadeResolve"]<>null){
        $ip=new IP();
        if($ip->isValid($_POST["NomadeResolve"])){
            "jserror: Cannot be an IP address!";
            return;
        }
    }

	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");

	$_POST["rulename"]=sqlite_escape_string2($_POST["rulename"]);
	
	
	if(intval($_POST["rule-options"])==0){
		
		$sql="INSERT INTO wpad_rules (zorder,enabled,rulename,dntlhstname,isResolvable,FinishbyDirect,NomadeMode,NomadeResolve,LBL,office365)
				VALUES ('{$_POST["zorder"]}','{$_POST["enabled"]}','{$_POST["rulename"]}','{$_POST["dntlhstname"]}','{$_POST["isResolvable"]}','{$_POST["FinishbyDirect"]}','{$_POST["NomadeMode"]}','{$_POST["NomadeResolve"]}','{$_POST["PAC_LBL"]}','{$_POST["office365"]}')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html(true);}
		return;
	}
	
	
	
	
	$sql="UPDATE wpad_rules SET
			zorder              ='{$_POST["zorder"]}',
			enabled             ='{$_POST["enabled"]}',
			rulename            ='{$_POST["rulename"]}',
			dntlhstname         ='{$_POST["dntlhstname"]}',
			isResolvable        ='{$_POST["isResolvable"]}',
			FinishbyDirect      ='{$_POST["FinishbyDirect"]}',
			NomadeMode          = '{$_POST["NomadeMode"]}',
			NomadeResolve       = '{$_POST["NomadeResolve"]}',
			office365           = '{$_POST["office365"]}',
            LBL       = '{$_POST["PAC_LBL"]}'
		WHERE ID='{$_POST["rule-options"]}'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);}

    admin_tracks("Proxy.pac:Save proxy.pac rule {$_POST["rule-options"]} options");
}

function rule_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["rule-popup"]);
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `wpad_black_link` (`zmd5` TEXT NOT NULL PRIMARY KEY , `aclid` INTEGER , `negation` INTEGER , `gpid` INT UNSIGNED , `zorder` INTEGER )");


    if($id>0){
        $array["{settings}"]="$page?rule-options=$id&function=$function";

        $refresh_js="LoadAjaxSilent('proxy-pac-rule-sources','fw.proxy.acls.objects.php?rule-id=$id&TableLink=wpad_sources_link&function=$function');";
        $refresh_enc=base64_encode("$function()");

        $array["{objects}"]="fw.proxy.acls.objects.php?rule-id=$id&TableLink=wpad_sources_link&RefreshTable=$refresh_enc&ProxyPac=1&function=$function";



        $array["{whitelist}"]="fw.proxy.acls.objects.php?rule-id=$id&TableLink=wpad_white_link&RefreshTable=$refresh_enc&ProxyPac=1";

        //$array["{force}"]="fw.proxy.acls.objects.php?rule-id=$id&TableLink=wpad_black_link&RefreshTable=$refresh_enc&ProxyPac=1&function=$function";

        $array["{proxy_servers}"]="$page?rule-proxies=$id&function=$function";


    }else{
        $array["{new_rule}"]="$page?rule-options=0&function=$function";
    }


    echo $tpl->tabs_default($array);


}



function rules_move(){
	$ID=$_GET["rules-move"];
	$direction=$_GET["direction"];
	
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM wpad_rules WHERE ID='$ID'");
	$LastOrder=$ligne["zorder"];
	
	if($direction=="up"){
		$NewOrder=$ligne["zorder"]-1;
		$LastOrder=$ligne["zorder"]+1;
	}else{
		$NewOrder=$ligne["zorder"]+1;
		$LastOrder=$ligne["zorder"]-1;
	}
	
	if(isset($_POST["rules-zorder"])){
		if(is_numeric($_POST["rules-zorder"])){
			$NewOrder=$_POST["rules-zorder"];
			$LastOrder=$ligne["zorder"]+1;
		}
	}
	if($NewOrder<0){$NewOrder=0;}
	if($LastOrder<0){$LastOrder=0;}
	
	$q->QUERY_SQL("UPDATE wpad_rules SET zorder='$NewOrder' WHERE ID='$ID'");
	$q->QUERY_SQL("UPDATE wpad_rules SET zorder='$LastOrder' WHERE zorder='$NewOrder' AND ID<>$ID");
	
	$sql="SELECT *  FROM wpad_rules ORDER BY `zorder`";
	$results = $q->QUERY_SQL($sql);
	$c=0;
	foreach($results as $index=>$ligne) {
		$zmd5=$ligne["ID"];
		$q->QUERY_SQL("UPDATE wpad_rules SET zorder='$c' WHERE ID='$zmd5'");
		$c++;
	
	}

    admin_tracks("Proxy.pac:Move proxy.pac rule $ID to $direction");
	
}
///home/squid/proxy_pac_rules/$ID/proxy.pac

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page,null,null,null,"&table-search=yes");

}



function table_search(){
    include_once(dirname(__FILE__)."/ressources/proxypac.sqlite.inc");
    $tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$users=new usersMenus();
	$eth_sql=null;
	$token=null;
	$class=null;
    $function=$_GET["function"];
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
    $nothing=$tpl->icon_nothing();
    $search=trim($_GET["search"]);
    sqlite_patch_tables();
    $tfile=PROGRESS_DIR."/proxy.pac.rules";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("proxypac.php?rules-conf=yes");

    $ACTUAL_RULES=array();
    $trules=explode("\n",@file_get_contents($tfile));
    foreach ($trules as $line){
        $tt=explode("|",$line);
        if(!is_numeric($tt[0])){continue;}
        $ACTUAL_RULES[$tt[0]]=true;
    }



	$add="Loadjs('$page?ruleid-js=0&function=$function',true);";
	if(!$users->AsSquidAdministrator){$add="alert('$ERROR_NO_PRIVS2')";}


    $jsrestart=$tpl->framework_buildjs("/proxypac/reconfigure",
        "autoconfiguration.apply.progress",
        "/autoconfiguration.apply.log",
        "progress-proxypac-restart","$function()","$function()"
    );

    $jsSimul="Loadjs('fw.proxypac.simul.php')";

	$html[]="<table id='table-proxypac-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true data-type='text'>$rulename</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>{status}</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>{enable}</th>";
	$html[]="<th data-sortable=false style='width:1%' class='center' nowrap>{order}</th>";
	$html[]="<th data-sortable=false nowrap>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="$function();";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$ProxyPacLockScript=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacLockScript"));

    $TRCLASS=null;

    if($search<>null){
        $acls=new squid_acls();
        $sqlprox=new mysql_squid_builder();
        $searchs_groups=$acls->search_inacls($search);
        if(count($searchs_groups)>0){
            $serachgp=array();
            foreach ($searchs_groups as $gpid=>$none){
                $ligne=$q->mysqli_fetch_array("SELECT GroupName,description,GroupType FROM webfilters_sqgroups WHERE ID=$gpid");
                $GroupName=$ligne["GroupName"];
                $description=$ligne["description"];
                $GroupType=$ligne["GroupType"];
                VERBOSE("-$GroupName- -$description- -$GroupType-",__LINE__);
                $type=$sqlprox->acl_GroupType[$GroupType];
                $ico=$sqlprox->acl_GroupTypeIcon[$GroupType];
                $jsdest=grouplink($gpid,"wpad_sources_link");
                if($description<>null){$description=" <small>($description)</small>";}
                $js=$tpl->td_href("$GroupName","{edit}: $GroupName ($type)<hr>$description<hr>",$jsdest);
                $serachgp[]=" <strong>&laquo;<i class='$ico'></i>&nbsp;$js&raquo;</strong><small>($type)</small>";
            }
            $acls_objects_found=$tpl->_ENGINE_parse_body("{acls_objects_found}");
            $acls_objects_found=str_replace("%s",count($serachgp),$acls_objects_found);
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $html[]="<tr class='$TRCLASS' id='0000'>";
            $html[]="<td>$acls_objects_found&nbsp;".@implode(", ",$serachgp)."</td>";
            $html[]="<td style='width:1%' class='center' nowrap>$nothing</center></td>";
            $html[]="<td style='width:1%' class='center' nowrap>$nothing</center></td>";
            $html[]="<td style='width:1%' nowrap>$nothing</td>";
            $html[]="<td style='width:1%' nowrap>$nothing</td>";
            $html[]="<td style='width:1%' class='center' nowrap>$nothing</center></td>";
            $html[]="</tr>";

        }
    }

	$results=$q->QUERY_SQL("SELECT * FROM wpad_rules ORDER BY zorder");

	foreach($results as $index=>$ligne) {
		$md=md5(serialize($ligne).$index);
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$rulename=$ligne["rulename"];
		$rulenameenc=urlencode($rulename);
		$ID=$ligne["ID"];
		$addlogtype=null;
		$check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$ID&function=$function')");
		$up=$tpl->icon_up("Loadjs('$page?rules-move=$ID&direction=up')","AsSquidAdministrator");
		$down=$tpl->icon_down("Loadjs('$page?rules-move=$ID&direction=down')","AsSquidAdministrator");
		$js="Loadjs('$page?ruleid-js=$ID&function=$function',true);";
		$delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js={$ID}&name=$rulenameenc&md=$md&function=$function')","AsSquidAdministrator");
		$explain=$tpl->_ENGINE_parse_body(explainArule($ID));
        $script_file="/home/squid/proxy_pac_rules/$ID/proxy.pac";
        if(is_file($script_file)) {
            $download = $tpl->icon_download("Loadjs('$page?builded-script-js=$ID')", "AsSquidAdministrator");
        }else{
            $download = $tpl->icon_download();
        }

        $tstatus="<span class='label label-default'>{inactive}</span>";
        if(isset($ACTUAL_RULES[$ID])){
            $tstatus="<span class='label label-primary'>{active2}</span>";
        }
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>".head_rule($tpl->td_href($ligne["rulename"],null,$js),$explain)."</td>";

        $html[]="<td style='width:1%' class='center' nowrap>$tstatus</center></td>";
        $html[]="<td style='width:1%' class='center' nowrap>$download</center></td>";
        $html[]="<td style='width:1%' class='center' nowrap>$check</center></td>";
		$html[]="<td style='width:1%' nowrap>$up&nbsp;&nbsp;$down</td>";
		$html[]="<td style='width:1%' class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";

	}


    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td>".head_rule("{default}","{by_default_set_proxy_parameters} {a_direct_connection_to_internet_proxy}")."</td>";
    $html[]="<td style='width:1%' class='center' nowrap>$nothing</center></td>";
    $html[]="<td style='width:1%' class='center' nowrap>$nothing</center></td>";
    $html[]="<td style='width:1%' nowrap>$nothing</td>";
    $html[]="<td style='width:1%' class='center' nowrap>$nothing</center></td>";
    $html[]="</tr>";

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
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);

    $importjs="Loadjs('$page?import-js=yes&function=$function');";

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>";
    $btns[] = "<label class=\"btn btn btn-primary\" OnClick=\"$function();\"><i class='fas fa-sync-alt'></i> {refresh} </label>";
    $btns[] = "<label class=\"btn btn btn-warning\" OnClick=\"$importjs\"><i class='fas fa-file-import'></i> {import} </label>";
    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"$jsSimul\"><i class='fas fa-vial'></i> {test_your_rules} </label>";
    $btns[]="</div>";


    $TINY_ARRAY["TITLE"]="{wpad_rules}";
    $TINY_ARRAY["ICO"]="fa fa-align-justify";
    $TINY_ARRAY["EXPL"]="{wpad_service_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $html[]= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function head_rule($rulename,$explain):string{
    return "<div style='vertical-align: middle;display: table-cell;font-size: 15px;padding-bottom: 6px;padding-top: 3px;'><i class='fa-duotone fa-scroll-old fa-1x'></i>&nbsp;$rulename</div><div style='margin-left: 20px;border-left:2px solid #C5C5C5;padding-left:5px'>$explain</div>";
}
function isRights():bool{
    $users=new usersMenus();
    if($users->AsSquidAdministrator){return true;}
    if($users->AsDansGuardianAdministrator){return true;}
    return false;
}
function grouplink($gpid,$TableLink):string{
    $function=$_GET["function"];
    $RefreshTable=base64_encode("$function();");
    return "Loadjs('fw.rules.items.php?groupid=$gpid&js-after=$RefreshTable&function=$function&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=1&firewall=0')";
}

function CountOfwhitelist($ID):int{
    $q = new lib_sqlite("/home/artica/SQLITE/acls.db");

    $sql = "SELECT wpad_white_link.gpid,wpad_white_link.negation,wpad_white_link.zmd5 as mkey,
	wpad_white_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_white_link,webfilters_sqgroups
	WHERE wpad_white_link.gpid=webfilters_sqgroups.ID
	AND wpad_white_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_white_link.zorder";

    $results = $q->QUERY_SQL($sql);
    if(!$results){return 0;}
    return count($results);
}

function explainArule($ID){

    $tpl=new template_admin();
    $qProxy=new mysql_squid_builder(true);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $h=array();
    if(!isset($_GET["t"])){$_GET["t"]=time();}

    $sql="CREATE TABLE IF NOT EXISTS `pac_except` (
            `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`ruleid` INTEGER NOT NULL,
			`type` TEXT,
			`pattern` TEXT ,
			`enabled` INTEGER NOT NULL DEFAULT 1)";
    $q->QUERY_SQL($sql);

    $ligne=$q->mysqli_fetch_array("SELECT * FROM wpad_rules WHERE ID='$ID'");
    $dntlhstname=$ligne["dntlhstname"];
    $isResolvable=$ligne["isResolvable"];
    $FinishbyDirect=$ligne["FinishbyDirect"];
    $NomadeMode=intval($ligne["NomadeMode"]);
    $NomadeResolve=null;
    if(!is_null($ligne["NomadeResolve"])) {
        $NomadeResolve = trim($ligne["NomadeResolve"]);
    }
    $office365=intval($ligne["office365"]);


    $sql="SELECT wpad_sources_link.gpid,wpad_sources_link.negation,wpad_sources_link.zmd5 as mkey,
	wpad_sources_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_sources_link,webfilters_sqgroups
	WHERE wpad_sources_link.gpid=webfilters_sqgroups.ID
	AND wpad_sources_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_sources_link.zorder";

    if($GLOBALS['VERBOSE']){echo "\n<HR>$sql</hr>\n";}
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){return "</a><br><span class='text-danger'>$q->mysql_error<br>\n$sql</span>";}
    if(count($results)==0){return "</a><br><span class='text-danger'>{no_source_defined} for #$ID</span>";}


    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $not=null;
        $pacproxs=array();
        $force_pp="";
        $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
        $negation=$ligne["negation"];
        if($negation==1){$not="{not} ";}
        $pacpxy=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["pacpxy"]);
        if(!is_array($pacpxy)){$pacpxy=array();}


        if(count($pacpxy)>0){
            foreach ($pacpxy as $index=>$pacline){
                $proxyserver=$pacline["hostname"];
                if($proxyserver=="0.0.0.0"){
                    $pacproxs=array();
                    $pacproxs[]="{direct_to_internet}";
                    break;
                }
                $proxyport=$pacline["port"];
                $pacproxs[]="$proxyserver:$proxyport";
            }
            $force_pp=" {then_force_proxy_parameters}: ".@implode("{or} ",$pacproxs);
        }




        $GroupType=$qProxy->acl_GroupType[$ligne["GroupType"]];
        $js=grouplink($gpid,"wpad_sources_link");
        $f[]=$not.$tpl->td_href($GroupName,"{edit}: $GroupName ($GroupType)",$js)." ($GroupType)</a>$force_pp";

    }




    $sql="SELECT * FROM `wpad_destination` WHERE aclid=$ID AND enabled=1 ORDER BY zorder";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){$g[]= "</a><br><span style='color:#C52B06;;text-decoration:none'>$q->mysql_error<br>\n$sql</span>";}
    $PPNAME=array();
    if(count($results)>0){
        foreach($results as $index=>$ligne) {
            $secure_ico=null;
            $secure=intval($ligne["secure"]);
            if($secure==1){$secure_ico="&nbsp;<small>(Secure Proxy)</small>";}

            $PPNAME[]="{$ligne["proxyserver"]}:{$ligne["proxyport"]}$secure_ico";
            $g[]="{$ligne["proxyserver"]}:{$ligne["proxyport"]}$secure_ico";
        }

        if($FinishbyDirect==1){$g[]="{direct_connection}";}

    }else{
        $g[]="{direct_connection} #$ID";

    }



    $sql="SELECT wpad_black_link.gpid,wpad_black_link.negation,wpad_black_link.zmd5 as mkey,
	wpad_black_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_black_link,webfilters_sqgroups
	WHERE wpad_black_link.gpid=webfilters_sqgroups.ID
	AND wpad_black_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_black_link.zorder";


    $results = $q->QUERY_SQL($sql);
    $FORCED=array();
    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $not=null;
        $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
        $negation=$ligne["negation"];
        if($negation==1){$not="{not} ";}
        $GroupType=$qProxy->acl_GroupType[$ligne["GroupType"]];

        $jsdest=grouplink($gpid,"wpad_black_link");
        $js=$tpl->td_href("{$GroupName}","{edit}: {$GroupName} ($GroupType)",$jsdest);
        $FORCED[]="$not$js ($GroupType)</a>";

    }



    $sql="SELECT wpad_white_link.gpid,wpad_white_link.negation,wpad_white_link.zmd5 as mkey,
	wpad_white_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_white_link,webfilters_sqgroups
	WHERE wpad_white_link.gpid=webfilters_sqgroups.ID
	AND wpad_white_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_white_link.zorder";

    if($GLOBALS['VERBOSE']){echo "\n<HR>$sql</hr>\n";}
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){return "</a><br><span class='text-danger'>$q->mysql_error<br>\n$sql</span>";}

    $hi=array();
    if($office365==1){
        $hi[]=" <strong>{dnot_proxy_office365}</strong>";
    }

    if($dntlhstname==1){
        $hi[]=" {dnot_proxy_localnames}";
    }
    if($isResolvable==1){
        $hi[]=" {isResolvable}";
    }


    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $not=null;
        $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
        $negation=$ligne["negation"];
        if($negation==1){$not="{not} ";}
        $link="Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$gpid&table-org=table-items-{$_GET["t"]}',true);";
        $GroupType=$qProxy->acl_GroupType[$ligne["GroupType"]];

        $jsdest=grouplink($gpid,"wpad_white_link");
        $js=$tpl->td_href("{$GroupName}","{edit}: {$GroupName} ($GroupType)",$jsdest);

        $h[]="$not$js ($GroupType)</a>";

    }


    if(count($h)==0){$h[]="{none}";}

    $ligne_except=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM pac_except WHERE ruleid=$ID");
    $except_count=intval($ligne_except["tcount"]);
    $except=$tpl->td_href("{exceptfor} $except_count {items}","{click_to_edit}","Loadjs('fw.proxypac.except.php?ruleid=$ID')");




    $pac_notresolve_explain=null;
    if($NomadeMode==1){
        if($NomadeResolve<>null){
            $pac_notresolve_explain=$tpl->_ENGINE_parse_body("{pac_notresolve_explain}")." ";
            $pac_notresolve_explain=str_replace("%host",$NomadeResolve,$pac_notresolve_explain);
        }
    }
    if(CountOfwhitelist($ID)==0){
        $f[]=$tpl->_ENGINE_parse_body("{bypass_proxy_internal}");
    }


    $FINAL[]="</a>{if_a_computer_matches} ".@implode("&nbsp;{and}&nbsp;", $f)." ".$pac_notresolve_explain.$except;
    $thenfinal="{then_set_proxy_parameters}";

    if(count($FORCED)>0){
        if(count($PPNAME)>0) {
            $FINAL[] = "{and_if_it_request_using} " . @implode("&nbsp;{or}&nbsp;", $FORCED);
            $FINAL[] = "{then_force_proxy_parameters} " . @implode($PPNAME).".";
            $thenfinal="{by_default_set_proxy_parameters}";
        }
    }

    $FINAL[]="$thenfinal ".@implode("&nbsp;{or}&nbsp;", $g);
    $FINAL[]=@implode("&nbsp;{or}&nbsp;", $hi)." {and_do_not_use_proxy_for} ".@implode("&nbsp;{or}&nbsp;", $h)."</span>";
    return @implode("<br>",$FINAL);

}

function proxy_objects($aclid){
    $qProxy=new mysql_squid_builder(true);
    $tpl=new template_admin();
    $tablelink="outgoingaddr_sqacllinks";
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

    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $js="Loadjs('fw.proxy.objects.php?object-js=yes&gpid=$gpid')";
        $neg_text="{is}";
        if($ligne["negation"]==1){$neg_text="{is_not}";}
        $GroupName=$ligne["GroupName"];
        $tt[]=$neg_text." <a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$GroupName</a> (".$qProxy->acl_GroupType[$ligne["GroupType"]].")";
    }

    if(count($tt)>0){
        return @implode("<br>{and} ", $tt);

    }else{
        return "{all}";
    }


}