<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["AclsUsePages"])){$tpl=new template_admin();$tpl->SAVE_POSTs();exit;}
if(isset($_GET["search"])){table_builder();exit;}
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"],$_GET["enabled"],$_GET["aclgroup"]);exit;}
if(isset($_GET["opts"])){opts_js();exit;}
if(isset($_GET["opts-popup"])){opts_popup();exit;}
if(isset($_GET["tiny-js"])){TINY_PAGE();exit;}
if(isset($_GET["file-uploaded"])){import_uploaded_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["externalALCLDAPRecursive"])){externalALCLDAPRecursive();exit;}
if(isset($_GET["externalALCLDAPRecursive-popup"])){externalALCLDAPRecursive_popup();exit;}
if(isset($_GET["externalALCLDAPRecursive-switch"])){externalALCLDAPRecursive_switch();exit;}

if(isset($_GET["proxy-acls-bugs"])){proxy_acls_bugs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_GET["new-group-rule-js"])){new_rule_group_js();exit;}
if(isset($_GET["notify-message"])){notify_message();exit;}
if(isset($_POST["notify-message"])){notify_message_save();exit;}
if(isset($_GET["web-error-page"])){notify_error_page();exit;}
if(isset($_POST["new-group-rule"])){new_rule_group_save();exit;}

if(isset($_POST["notify-error-page"])){notify_error_page_save();exit;}
if(isset($_GET["SquidAclFinishDeny"])){SquidAclFinishDeny_js();exit;}
if(isset($_GET["export-acls"])){export_acls();exit;}
if(isset($_GET["export-rule-js"])){export_rule_js();exit;}
if(isset($_GET["export-rule-exported"])){export_rule_exported();exit;}
if(isset($_GET["export-rule-download"])){export_rule_download();exit;}
if(isset($_GET["rules-table-start"])){groups_rules_table_start();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}
if(isset($_GET["fill"])){fillthisRule();exit;}
if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["duplicate-js"])){duplicate_js();exit;}
if(isset($_GET["change-order"])){change_order_js();exit;}
if(isset($_POST["NewOrder"])){change_order_save();exit;}
if(isset($_POST["rule-delete-confirm"])){rule_delete_confirm();exit;}

page();

function SquidAclFinishDeny_js():bool{
    $SquidAclFinishDeny=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAclFinishDeny"));
    if($SquidAclFinishDeny==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidAclFinishDeny",1);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/localnets");
        return admin_tracks("Modify the final proxy acl rules by deny all except trusted network");
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidAclFinishDeny",0);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/localnets");
    return admin_tracks("Modify the final proxy acl rules by accepting all nodes");
}
function opts_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{options}","$page?opts-popup=yes&function={$_GET["function"]}");
}
function opts_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $AclsUsePages=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUsePages"));
    $AclsUseRows=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUseRows"));
    if($AclsUseRows==0){$AclsUseRows=30;}
    $form[]=$tpl->field_checkbox("AclsUsePages","{page_interval}",$AclsUsePages);
    $form[]=$tpl->field_numeric("AclsUseRows","{rows}",$AclsUseRows);
    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance2.close();$function()");
return true;
}
function export_rule_js(){
    $page=CurrentPageName();
    $ID=intval($_GET["export-rule-js"]);
    $tpl=new template_admin();
    $q              = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM suricata_sqacllinks WHERE ID='$ID'");
    $ligne["aclname"]=$tpl->utf8_encode($ligne["aclname"]);

    admin_tracks("Exporting Rule ACL ID:$ID {$ligne["aclname"]}");
    header("content-type: application/x-javascript");
    echo $tpl->framework_buildjs("squid2.php?acls-export-rule=$ID",
        "acls.parse",
        "acls.logs","export-progress-$ID","Loadjs('$page?export-rule-exported=$ID')");
}
function export_rule_exported(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["export-rule-exported"]);
    $div="export-progress-$ID";

    $fname=PROGRESS_DIR."/rule.$ID.acl";
    if(!is_file($fname)){
        echo $tpl->js_error("rule.$ID.acl {no_such_file}");
        return false;
    }
    header("content-type: application/x-javascript");
    echo "document.getElementById('$div').innerHTML='';\n";
    echo "document.location.href=\"$page?export-rule-download=$ID\"\n";
    return true;
}
function export_rule_download(){
    $ID=intval($_GET["export-rule-download"]);
    $tfile=PROGRESS_DIR."/rule.$ID.acl";
    $basename=basename($tfile);
    if(!is_file($tfile)){die();}

    $fsize=@filesize($tfile);
    $timestamp =filemtime($tfile);
    $etag = md5($tfile . $timestamp);


    $tsstring = gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
    header("Content-Length: ".$fsize);
    header('Content-type: application/x-sqlite3');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$basename\"");
    header("Cache-Control: no-cache, must-revalidate");
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
    header("Last-Modified: $tsstring");
    header("ETag: \"{$etag}\"");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($tfile);
    @unlink($tfile);
}
function import_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{import}","$page?import-popup=yes",550);
}
function externalALCLDAPRecursive():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gprule         = intval($_GET["gprule"]);
    $function       = $_GET["function"];
    return $tpl->js_dialog1("{AD_LDAP_RECURSIVE}","$page?externalALCLDAPRecursive-popup=yes&gprule=$gprule&function=$function",550);
}
function externalALCLDAPRecursive_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gprule         = intval($_GET["gprule"]);
    $function       = $_GET["function"];
    $externalALCLDAPRecursive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("externalALCLDAPRecursive"));
    $html[]=$tpl->div_explain("{externalALCLDAPRecursive}");
    $html[]="<div class=center style='margin:30px'>";
    if($externalALCLDAPRecursive==1) {
        $html[] = $tpl->button_autnonome("{disable_feature}", "Loadjs('$page?externalALCLDAPRecursive-switch=yes&gprule=$gprule&function=$function')", ico_check, "AsFirewallManager", "335");
    }else{
        $html[] = $tpl->button_autnonome("{enable_feature}", "Loadjs('$page?externalALCLDAPRecursive-switch=yes&gprule=$gprule&function=$function')", ico_check, "AsFirewallManager", "335");
    }
    $html[] = "</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function externalALCLDAPRecursive_switch():bool{
    $page=CurrentPageName();
    $externalALCLDAPRecursive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("externalALCLDAPRecursive"));
    if ($externalALCLDAPRecursive==1) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("externalALCLDAPRecursive",0);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/reload");

    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("externalALCLDAPRecursive",1);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/reload");
    }
    $gprule         = intval($_GET["gprule"]);
    $function       = $_GET["function"];
    $toTiny="Loadjs('$page?tiny-js=yes&gprule=$gprule&function=$function')";
    header("content-type: application/x-javascript");
    echo "dialogInstance1.close();\n";
    echo $toTiny;
    return true;
}
function import_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]=$tpl->div_explain("{import}||{import_acl_explain}");
    $html[]="<div class='center' style='margin: 30px'>".$tpl->button_upload("{upload_a_file}",$page)."</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function import_uploaded_js(){
    $tpl=new template_admin();
    $filename   = $_GET["file-uploaded"];
    $fileencode = urlencode($filename);
    $page=CurrentPageName();

    admin_tracks("$filename was uploaded to Proxy ACLs rules");
    header("content-type: application/x-javascript");
    $js=$tpl->framework_buildjs("squid2.php?acls-import-file=$fileencode",
        "acls.parse",
        "acls.logs","progress-acls-restart","LoadAjax('table-acls-rules','$page?table=yes');");

    echo "dialogInstance1.close();\n$js\n";

}
function export_acls(){
    $tfile="/home/artica/SQLITE/acls.db";
    $basename=basename($tfile);
    if(!is_file($tfile)){die();}

    $fsize=@filesize($tfile);
    $timestamp =filemtime($tfile);
    $etag = md5($tfile . $timestamp);


    $tsstring = gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
    header("Content-Length: ".$fsize);
    header('Content-type: application/x-sqlite3');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$basename\"");
    header("Cache-Control: no-cache, must-revalidate");
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
    header("Last-Modified: $tsstring");
    header("ETag: \"{$etag}\"");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($tfile);
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{SURICATA_ACLS}","fad fa-shield-alt","{SURICATA_ACLS_EXPLAIN}<div id='proxy-acls-bugs'></div>","$page?table=yes","suricata-acls-access","progress-acls-restart",false,"table-acls-rules");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{PROXY_ACLS}",$html);
        echo $tpl->build_firewall();
        return;
    }
	echo $tpl->_ENGINE_parse_body($html);
	
}
function change_order_js(){
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $ID             = intval($_GET["change-order"]);
    $q              = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne          = $q->mysqli_fetch_array("SELECT * FROM suricata_sqacls WHERE ID='$ID'");
    $jsafter        = "LoadAjax('table-acls-rules','$page?table=yes');";

    $tpl->js_prompt("{order}","{give_new_position}","fas fa-sort-numeric-up-alt",$page,"NewOrder",$jsafter,$ligne["xORDER"],"$ID");

}
function change_order_save(){
    $xORDER     = $_POST["NewOrder"];
    $RuleID     = $_POST["KeyID"];
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("UPDATE suricata_sqacllinks SET xORDER=$xORDER WHERE ID=$RuleID");
    if(!$q->ok){echo $q->mysql_error;}

}
function rule_delete_js(){
    $ID         = $_GET["rule-delete-js"];
    $md         = "acl-$ID";
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT aclname,aclgroup FROM suricata_sqacllinks WHERE ID='$ID'");

    if(intval($ligne["aclgroup"])==1){
        $tpl=new template_admin();
        $aclname=$ligne["aclname"];
        $tpl->js_confirm_delete("{group_of_rules}<br><strong>$aclname</strong><br>{group_of_rules_delete_ask}","rule-delete-confirm",$ID,"$('#$md').remove();");
        return;
    }

	header("content-type: application/x-javascript");


	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";
}
function rule_delete_confirm():bool{
    $ID         = $_POST["rule-delete-confirm"];
    if(!rule_delete($ID)){return false;}
    admin_tracks("Remove Proxy acl id $ID");
    return true;
}
function proxy_acls_bugs():bool{
    $SQUID_ACLS_BUGS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_ACLS_BUGS"));
    if($SQUID_ACLS_BUGS==0){return true;}
    $tpl=new template_admin();
    $error=$tpl->_ENGINE_parse_body("{acls_check_errors}");
    $error=str_replace("%s",$SQUID_ACLS_BUGS,$error);
    $bt=$tpl->button_autnonome("{view}", "Loadjs('fw.proxy.acls.bugs.php')", "fas fa-bug","AsFirewallManager",100,"btn-danger");
    $html[]="<div style='float:right;margin-right:50px;margin-bottom:20px'>$bt</div>";
    $html[]=$error;
    echo $tpl->_ENGINE_parse_body($tpl->div_error(@implode("\n",$html)));

    return true;

}
function duplicate_rule($ID_SRC,$NewAClGPID=0){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ID_SRC=intval($ID_SRC);
    $ligne=$q->mysqli_fetch_array("SELECT * FROM suricata_sqacllinks WHERE ID='$ID_SRC'");

    $aclname=$ligne["aclname"]." (copied)";
    $enabled=$ligne["enabled"];
    $acltpl=$ligne["acltpl"];
    $xORDER=intval($ligne["xORDER"])+1;
    $aclport=$ligne["aclport"];
    $aclgroup=$ligne["aclgroup"];
    $aclgpid=$ligne["aclgpid"];
    $zExplain=$ligne["zExplain"];
    $zTemplate=$ligne["zTemplate"];
    $description=$ligne["description"];

    if($NewAClGPID>0){
        $aclgpid=$NewAClGPID;
    }

    $TempName=md5(time());

    $sql="INSERT INTO suricata_sqacllinks (aclname,enabled,acltpl,xORDER,aclport,aclgroup,aclgpid,zExplain,zTemplate,description)
	VALUES ('$TempName',$enabled,'$acltpl','$xORDER','$aclport','$aclgroup','$aclgpid','$zExplain','$zTemplate','$description')";
    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return 0;}

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM suricata_sqacllinks WHERE aclname='$TempName'");
    $LASTID=intval($ligne["ID"]);
    if($LASTID>0) {
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?explain-this-rule=$LASTID");
    }

    $q->QUERY_SQL("UPDATE suricata_sqacllinks SET aclname='$aclname' WHERE ID='$LASTID'");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return 0;}



    $acls=new squid_acls_groups();
    if(!$acls->aclrule_edittype($LASTID,$httpaccess,1)){
        $tpl->js_mysql_alert( "ERROR aclrule_edittype($LASTID,{$httpaccess},1)");
        return 0;
    }

    $acls->DUPLICATE_ACLS_OBJECTS("webfilters_sqacllinks",$ID_SRC,$LASTID);

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?explain-this-rule=$LASTID");
    admin_tracks("Duplicate $aclname proxy rule");
    return $LASTID;

}
function duplicate_js(){
    $ID_SRC=intval($_GET["duplicate-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    if(!$q->FIELD_EXISTS("suricata_sqacllinks","zExplain")){
        $q->QUERY_SQL("ALTER TABLE suricata_sqacllinks ADD zExplain TEXT");
    }
    if(!$q->FIELD_EXISTS("suricata_sqacllinks","zTemplate")){
        $q->QUERY_SQL("ALTER TABLE suricata_sqacllinks ADD zTemplate TEXT");
    }
    if(!$q->FIELD_EXISTS("suricata_sqacllinks","description")){
        $q->QUERY_SQL("ALTER TABLE suricata_sqacllinks ADD description TEXT");
    }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT aclgroup FROM suricata_sqacllinks WHERE ID='$ID_SRC'");
    $DuplicatedID=duplicate_rule($ID_SRC);
    if($DuplicatedID==0){return;}
    $aclgroup=$ligne["aclgroup"];
    if($aclgroup==1){
        $results=$q->QUERY_SQL("SELECT ID FROM suricata_sqacllinks WHERE aclgpid=$ID_SRC");
        foreach ($results as $index=>$ligne){
            $ID_SRC=$ligne["ID"];
            $NewID=duplicate_rule($ID_SRC,$DuplicatedID);
            if($NewID==0){return;}
        }


        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?explain-this-rule=$DuplicatedID");
    }


    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-acls-rules','$page?table=yes');";


}
function rule_enable(){
    $page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	header("content-type: application/x-javascript");
	$ligne=$q->mysqli_fetch_array("SELECT aclname,enabled FROM suricata_sqacls WHERE ID='{$_GET["enable-js"]}'");
    $aclname=$ligne["aclname"];
	if(intval($ligne["enabled"])==0){
		
		$js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
		$enabled=1;}else{
			$js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
			$enabled=0;
		}
	
	$q->QUERY_SQL("UPDATE suricata_sqacls SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return;}

    admin_tracks("Change $aclname proxy rule activation to $enabled");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?explain-this-rule={$_GET["enable-js"]}");
	echo $js;
	echo "\nLoadjs('$page?fill={$_GET["enable-js"]}');\n";
}
function rule_js(){
	$page       = CurrentPageName();
	$tpl        = new template_admin();
	$ID         = $_GET["rule-id-js"];
	$q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne      = $q->mysqli_fetch_array("SELECT aclname,aclgroup,aclgpid FROM suricata_sqacls WHERE ID='$ID'");
	$size       = null;
    $aclgroup   = intval($ligne["aclgroup"]);
    $aclgpid    = intval($ligne["aclgpid"]);
    $aclname    = $ligne["aclname"];
    if(strlen($aclname)>50){
        $aclname=substr($aclname,0,47)."...";
    }


    if($aclgroup==1){
        $size=1024;
        $ligne["aclname"]="{group_of_rules}: {$aclname}";
    }

    if($aclgpid>0){
        $tpl->js_dialog2("{rule}: $ID {$aclname}","$page?rule-tabs=$ID",$size);
        return;
    }

	$tpl->js_dialog1("{rule}: $ID {$aclname}","$page?rule-tabs=$ID",$size);
}
function new_rule_group_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $tpl->js_prompt("{new_group_of_rules}","{rulename}","fa fa-plus",$page,"new-group-rule",
        "LoadAjax('table-acls-rules','$page?table=yes');");

}
function rule_tabs():bool{
    $ID         = intval($_GET["rule-tabs"]);
    $page       = CurrentPageName();
	$tpl        = new template_admin();

    $RefreshFunction=base64_encode("Loadjs('$page?fill=$ID')");
	$array["{rule}"]="$page?rule-settings=$ID";
    $array["{proxy_objects}"] = "fw.proxy.acls.objects.php?rule-id=$ID&RefreshFunction=$RefreshFunction&IDS=1&TableLink=suricata_sqacllinks";
	echo $tpl->tabs_default($array);
    return true;
}
function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $f["http"]="http";
    $f["ftp"]="ftp";
    $f["smtp"]="smtp";
    $f["tls"]="tls";
    $f["ssh"]="ssh";
    $f["imap"]="imap";
    $f["smb"]="smb";
    $f["dcerpc"]="dcerpc";
    $f["dns"]="dns";
    $f["modbus"]="modbus";
    $f["enip"]="enip";
    $f["dnp3"]="dnp3";
    $f["nfs"]="nfs";
    $f["ntp"]="ntp";
    $f["ftp-data"]="ftp-data";
    $f["tftp"]="tftp";
    $f["ike"]="ike";
    $f["krb5"]="krb5";
    $f["quic"]="quic";
    $f["dhcp"]="dhcp";
    $f["sip"]="sip";
    $f["rfb"]="rfb";
    $f["mqtt"]="mqtt";
    $f["pgsql"]="pgsql";
    $f["telnet"]="telnet";
    $f["websocket"]="websocket";
    $f["ldap"]="ldap";
    $f["doh2"]="doh2";
    $f["rdp"]="rdp";
    $f["http2"]="http2";
    $f["bittorrent-dht"]="bittorrent-dht";
    $f["pop3"]="pop3";
    $f["mdns"]="mdns";
    $f["snmp"]="snmp";

    $target["src"]="{src}";
    $target["dst"]="{dst}";

    $proto["ip"]="{all}";
    $proto["tcp"]="{tcp}";
    $proto["udp"]="{udp}";

	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    if(!$q->FIELD_EXISTS("suricata_sqacls","description")){
        $q->QUERY_SQL("ALTER TABLE suricata_sqacls ADD description TEXT");
    }
    $flow[""]="{all}";
    $flow["to_client"]="{flow_to_client}";
    $flow["to_server"]="{flow_to_server}";


    $ID=intval($_GET["rule-settings"]);
	$ligne=$q->mysqli_fetch_array("SELECT * FROM suricata_sqacls WHERE ID=$ID");
    $ligne["aclname"]=$tpl->utf8_encode($ligne["aclname"]);
	$form[]=$tpl->field_hidden("rule-save", "$ID");
    $form[]=$tpl->field_text("aclname", "{rule_name}", $ligne["aclname"],true);
    $form[]=$tpl->field_array_hash($proto, "proto", "{protocol}", $ligne["proto"]);
    $form[]=$tpl->field_array_hash($flow, "flow", "{flow}", $ligne["flow"]);
    $form[]=$tpl->field_array_hash($f, "ApplayerProtocol", "{ApplicationLayerProtocol}", $ligne["ApplayerProtocol"]);
    $form[]=$tpl->field_array_hash($target, "target", "{target}", $ligne["target"]);
    $form[]=$tpl->field_numeric("count", "{NumberOfMatches}", $ligne["count"]);
    $form[]=$tpl->field_numeric("seconds", "{during} ({seconds})", $ligne["seconds"]);
	$form[]=$tpl->field_checkbox("enabled", "{enabled}", $ligne["enabled"],true);
   	$jsafter="Loadjs('$page?fill=$ID');";

    $tpl->form_add_button("{export}","Loadjs('$page?export-rule-js=$ID')");
	$html="<div id='export-progress-$ID' style='margin-top:5px'></div>".
        $tpl->form_outside($ligne["aclname"], @implode("\n", $form),null,"{apply}",$jsafter,"AsFirewallManager");
	echo $tpl->_ENGINE_parse_body($html);
	
}
function rule_save(){

	$tpl    = new template_admin();
	$q      = new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ID     = $_POST["rule-save"];
	$tpl->CLEAN_POST_XSS();
	

	$aclname=sqlite_escape_string2(remove_acc($_POST["aclname"]));
	$description=base64_encode($_POST["description"]);

    if(isset($_POST["access"])) {
        $acl = new squid_acls_groups();
        if (!$acl->aclrule_edittype($ID, $_POST["access"], 1)) {
            echo "js:error:aclrule_edittype($ID,{$_POST["access"]},1)\n";
            return;
        }
    }
	
	
	$sql="UPDATE suricata_sqacls SET 
				`enabled`='{$_POST["enabled"]}',
				`seconds`='{$_POST["seconds"]}',
				`count`='{$_POST["count"]}',
				`proto`='{$_POST["proto"]}',
				`flow`='{$_POST["flow"]}',
				`target`='{$_POST["target"]}',
				`description`='$description',
				`aclname`='$aclname' WHERE ID=$ID";

	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "js:error:".$tpl->javascript_parse_text($q->mysql_error);return;}
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?explain-this-rule=$ID");
	$c=0;
	$sql="SELECT ID FROM suricata_sqacls ORDER BY xORDER";
	$results = $q->QUERY_SQL($sql);
	
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE suricata_sqacls SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?explain-this-rule={$ligne["ID"]}");
    admin_tracks("Modify settings of $aclname proxy rule");

	
	
}
function new_rule_js():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $rulename   = null;
	$gprule     = intval($_GET["gprule"]);
    $function   = $_GET["function"];

	if($gprule>0){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT aclname FROM suricata_sqacllinks WHERE ID='$gprule'");
        $rulename=":".$ligne["aclname"];
    }

	$title="{new_rule}$rulename";
	return $tpl->js_dialog($title,"$page?newrule-popup=yes&gprule=$gprule&function=$function");
}
function new_rule_popup():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $gprule     = intval($_GET["gprule"]);
    $jsafter    = array();

    $f["http"]="http";
    $f["ftp"]="ftp";
    $f["smtp"]="smtp";
    $f["tls"]="tls";
    $f["ssh"]="ssh";
    $f["imap"]="imap";
    $f["smb"]="smb";
    $f["dcerpc"]="dcerpc";
    $f["dns"]="dns";
    $f["modbus"]="modbus";
    $f["enip"]="enip";
    $f["dnp3"]="dnp3";
    $f["nfs"]="nfs";
    $f["ntp"]="ntp";
    $f["ftp-data"]="ftp-data";
    $f["tftp"]="tftp";
    $f["ike"]="ike";
    $f["krb5"]="krb5";
    $f["quic"]="quic";
    $f["dhcp"]="dhcp";
    $f["sip"]="sip";
    $f["rfb"]="rfb";
    $f["mqtt"]="mqtt";
    $f["pgsql"]="pgsql";
    $f["telnet"]="telnet";
    $f["websocket"]="websocket";
    $f["ldap"]="ldap";
    $f["doh2"]="doh2";
    $f["rdp"]="rdp";
    $f["http2"]="http2";
    $f["bittorrent-dht"]="bittorrent-dht";
    $f["pop3"]="pop3";
    $f["mdns"]="mdns";
    $f["snmp"]="snmp";

    $function=$_GET["function"];
    $jsafter[]="BootstrapDialog1.close();";

    if($function<>null){
        $jsafter[]="$function()";
    }
	if($gprule>0){
	    $tpl->field_hidden("aclgpid","$gprule");
        $jsafter[]="LoadAjax('GroupOfRules{$gprule}','$page?table=yes&gprule=$gprule');";
        $jsafter[]="Loadjs('$page?fill=$gprule');";

    }else{
        $tpl->field_hidden("aclgpid","0");

    }
    $form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("aclname", "{rule_name}", null,true);
    $form[]=$tpl->field_array_hash($f, "ApplayerProtocol", "{ApplicationLayerProtocol}", "");



    $html=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        @implode(";",$jsafter),"AsFirewallManager");
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}
function rule_move(){
	$tpl=new template_admin();
	$ID=$_GET["acl-rule-move"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT xORDER,aclname,aclgpid,aclport FROM suricata_sqacllinks WHERE `ID`='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
	$xORDER_ORG=intval($ligne["xORDER"]);
	$aclgpid=intval($ligne["aclgpid"]);
	$xORDER=$xORDER_ORG;
	$aclname=$ligne["aclname"];
	
	if($_GET["acl-rule-dir"]==1){
	    $xORDER=$xORDER_ORG-1;
	}
	if($_GET["acl-rule-dir"]==0){
	    $xORDER=$xORDER_ORG+1;

	}

    $sql="UPDATE suricata_sqacllinks SET xORDER=$xORDER WHERE `ID`='$ID'";
    $q->QUERY_SQL($sql);
    admin_tracks("Move Proxy acl order of $aclname from $xORDER_ORG to $xORDER");

    $sql="UPDATE suricata_sqacllinks SET
		xORDER=$xORDER_ORG WHERE `ID`<>'$ID' AND xORDER=$xORDER AND aclgpid=$aclgpid";
    $q->QUERY_SQL($sql);

	$c=1;
	$sql="SELECT ID FROM suricata_sqacllinks WHERE aclgpid=$aclgpid ORDER BY xORDER";
	$results = $q->QUERY_SQL($sql);

	foreach($results as $index=>$ligne) {
	    echo "// ID {$ligne["ID"]} became $c\n";
		$q->QUERY_SQL("UPDATE suricata_sqacllinks SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
		if($GLOBALS["VERBOSE"]){echo "UPDATE suricata_sqacllinks SET xORDER=$c WHERE `ID`={$ligne["ID"]}\n";}
		$c++;
	}

    $page=CurrentPageName();
    echo "Loadjs('$page?fill=$ID');\n";
}
function table():bool{
    if(!isset($_GET["gprule"])){$_GET["gprule"]=0;}
    $gprule         = intval($_GET["gprule"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!isset($_SESSION["ACL_SEARCH"])){$_SESSION["ACL_SEARCH"]=null;}
    $options["WRENCH"]="Loadjs('$page?opts=yes&function=%s')";
    echo $tpl->search_block($page,null,null,"value=".$_SESSION["ACL_SEARCH"],"&gprule=$gprule",$options);
    return true;
}
function  getCurrentRules():array{
    $MAIN=array();
    $f=explode("\n",@file_get_contents("/etc/squid3/http_access.conf"));
    foreach ($f as $line){
        if(!preg_match("#STATUS=\[(.+?)\]#",$line,$re)){continue;}
        $HEADS=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($re[1]);
        if(!isset($HEADS["STATUS_RULES"])){$HEADS["STATUS_RULES"]=array();}
        foreach ($HEADS["STATUS_RULES"] as $ruleid=>$none){
            $MAIN[$ruleid]=true;
        }

    }
    return $MAIN;
}
function TINY_PAGE($return=false):string{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $gprule         = intval($_GET["gprule"]);
    $function       = $_GET["function"];

    $add="Loadjs('$page?newrule-js=yes&gprule=$gprule&function=$function',true);";
    $jsimport="Loadjs('$page?import-js=yes');";
    $users=new usersMenus();
    $topbuttons=array();

    if($users->AsFirewallManager) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
        if ($gprule == 0) {
            if ($function <> null) {
                $topbuttons[] = array("$function()", ico_refresh, "{reload}");
            }

            $topbuttons[] = array("document.location.href='$page?export-acls=yes'", ico_export, "{export}");
            $topbuttons[] = array($jsimport, ico_import, "{import}");
        }
    }


    if($return){
        return $tpl->th_buttons($topbuttons);
    }

    $TINY_ARRAY["TITLE"]="{SURICATA_ACLS}";
    $TINY_ARRAY["ICO"]="fad fa-shield-alt";
    $TINY_ARRAY["EXPL"]="{SURICATA_ACLS_EXPLAIN}";
    $TINY_ARRAY["URL"]="suricata-acls-access";
    $TINY_ARRAY["BUTTONS"]=$tpl->th_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    header("content-type: application/x-javascript");
    echo $jstiny;
    return "";
}
function table_builder():bool{

    if(!isset($_GET["gprule"])){$_GET["gprule"]=0;}
    $tpl            = new template_admin();
	$page           = CurrentPageName();
	$eth_sql        = null;
	$token          = null;
	$class          = null;
	$order          = $tpl->_ENGINE_parse_body("{order}");
	$rulename       = $tpl->_ENGINE_parse_body("{rulename}");
	$description    = $tpl->_ENGINE_parse_body("{description}");
	$enabled        = $tpl->_ENGINE_parse_body("{enabled}");
    $gprule         = intval($_GET["gprule"]);
    $search         = $_GET["search"];
    $function       = $_GET["function"];

	$class=null;
    $getCurrentRules=getCurrentRules();
	$t=md5(time()."{$_GET["gprule"]}".microtime(true));

    $data1="class='text-capitalize' style='width:1%;text-align:center'";
    if($gprule>0){
        $html[]=TINY_PAGE(true);
    }
    $AclsUsePages=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUsePages"));
    $limit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AclsUseRows"));
    if($limit==0){$limit=120;}


    $TableClass="";
    if($AclsUsePages==1){
        $TableClass="footable ";
    }

    $html[]="<table id='table-webfilter-rules-$t' class=\"{$TableClass}table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th $data1>$order</th>";
    $html[]="<th $data1>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$rulename</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$description}</th>";
    $html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false>{copy}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>$enabled</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-firewall-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    if(!$q->FIELD_EXISTS("suricata_sqacls","zExplain")){$q->QUERY_SQL("ALTER TABLE suricata_sqacls ADD zExplain TEXT");}
    if(!$q->FIELD_EXISTS("suricata_sqacls","description")){$q->QUERY_SQL("ALTER TABLE suricata_sqacls ADD description TEXT");}

    VERBOSE("search:$search",__LINE__);
    $_SESSION["ACL_SEARCH"]=$search;
    if(preg_match("#(rows|max|limit)=([0-9]+)#i",$search,$re)){
        $limit=$re[2];
        $search=str_replace("$re[1]=$re[2]","",$search);
    }
    $sql="SELECT * FROM suricata_sqacls ORDER BY xORDER LIMIT $limit";

    if($search<>null){
        if(strpos($search,"*")==0){
            $search="*$search*";
        }
        if(strpos(" $search","*")>0){
            $search=str_replace("*","%",$search);
            $sql="SELECT * FROM suricata_sqacllinks AND (aclname LIKE '$search') ORDER BY xORDER LIMIT $limit";

        }

        if(is_numeric($search)){
            $sql="SELECT * FROM suricata_sqacls WHERE ID=$search ORDER BY xORDER LIMIT $limit";
        }

    }
    $results=array();
    list($search2,$searchgroups)=search_all_items($search);

    VERBOSE($sql,__LINE__);
    $results = $q->QUERY_SQL($sql);

    if(count($search2)>0){
        foreach ($search2 as $index=>$ligne){
            $results[]=$ligne;
        }
    }


    $TRCLASS=null;
    $nothing=$tpl->icon_nothing();
    if(count($searchgroups)>0){
        foreach ($searchgroups as $gpid=>$GroupName){
            $ff[]="&laquo;".$tpl->td_href("$GroupName","Loadjs('fw.rules.items.php?groupid=$gpid$function',true);")."&raquo;&nbsp;";
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='acl--1'>";
        $html[]="<td class=\"center\" style='width:1%' nowrap >$nothing</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap>&nbsp;</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap>{follow_group_found}:</td>";
        $html[]="<td class='left' style='width:99%'>".@implode(", ",$ff)."</td>";
        $html[]="<td class='center' style='width:1%' nowrap>$nothing</td>";
        $html[]="<td class='center' style='width:1%' nowrap>$nothing</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$nothing</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$nothing</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$nothing</center></td>";
        $html[]="</tr>";

    }


    $already_isset=array();
	foreach($results as $index=>$ligne) {
		$MUTED=null;
		$ID=intval($ligne["ID"]);
        if(isset($already_isset[$ID])){continue;}
        $already_isset[$ID]=true;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $explain = EXPLAIN_THIS_RULE($ligne['ID'], $ligne["enabled"], $ligne["aclgroup"]);
    	$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if($ligne["enabled"]==0){$MUTED=" text-muted";}
    	$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");

        $rule_status="<span class='label label-default'>{inactive}</span>";
        if(isset($getCurrentRules[$ID])){
            $rule_status="<span class='label label-primary'>{active2}</span>";
        }
        if(count($getCurrentRules)==0){
            $rule_status="<span class='label label-default'>{unknown}</span>";
        }
        if($ligne["aclgroup"]>0){
            $rule_status="<span class='label label-primary'>{active2}</span>";
            if($ligne["enabled"]==0){
                $rule_status="<span class='label label-default'>{inactive}</span>";
            }
        }

        $aclname = $ligne["aclname"];
        if(strlen($aclname)>50){
            $aclname=substr($aclname,0,47)."...";
        }
		$row_order=$tpl->td_href(" <span class=\"label label-default\" id='acl-order-$ID'>{$ligne["xORDER"]}</span>",
            null,"Loadjs('$page?change-order=$ID');");



		$html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
		$html[]="<td class=\"center\" style='width:1%' nowrap >$row_order</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap>$rule_status</td>";
		$html[]="<td style='vertical-align:middle;width:1%'  nowrap>". $tpl->td_href($aclname,"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
        $html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_refresh("LoadAjaxTiny
        ('explain-this-rule-$ID','$page?explain-this-rule=$ID&enabled={$ligne["enabled"]}&aclgroup={$ligne["aclgroup"]}')","AsFirewallManager")."</td>";
        $html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_copy("Loadjs('$page?duplicate-js=$ID')","AsFirewallManager")."</td>";
		$html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')",null,"AsFirewallManager")."</td>";
		$html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
		$html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";
	
	}

    $MUTED=null;
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $SquidAclFinishDeny=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAclFinishDeny"));
    if($SquidAclFinishDeny==1){$AclFinishDeny=0;$MUTED=" text-muted";}else{$AclFinishDeny=1;}
    $html[]="<tr class='$TRCLASS{$MUTED}' id='acl-NONE'>";
    $html[]="<td class=\"center\" style='width:1%' nowrap ></td>";
    $html[]="<td class=\"center\" style='width:1%' nowrap ></td>";
    $html[]="<td style='vertical-align:middle;width:1%'  nowrap>{finally}</td>";
    $html[]="<td style='vertical-align:middle'>{do_nothing}</td>";
    $html[]="<td class='center' style='width:1%' nowrap>$nothing</td>";
    $html[]="<td class='center' style='width:1%' nowrap>$nothing</td>";
    $html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_check($AclFinishDeny,"Loadjs('$page?SquidAclFinishDeny=yes')",null,"AsFirewallManager")."</td>";
    $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$nothing</center></td>";
    $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$nothing</center></td>";
    $html[]="</tr>";
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='9'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";



    $toTiny="Loadjs('$page?tiny-js=yes&gprule=$gprule&function=$function')";
    if($gprule>0){$toTiny=null;}

    $html[]="<script>";
    if($AclsUsePages==1){
$html[]="$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    }

    $html[]="$toTiny
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    LoadAjax('proxy-acls-bugs','$page?proxy-acls-bugs=yes');
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function search_all_items($search){
    if($search==null){return array(array(),array());}
    if(strpos("  $search","*")==0){
        $search="*$search*";
    }
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);
    $search=str_replace("%%","%",$search);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT suricata_sqacllinks.*,webfilters_sqgroups.GroupName,webfilters_sqgroups.ID as gpid FROM suricata_sqacllinks,webfilters_sqacllinks,webfilters_sqgroups,webfilters_sqitems
    WHERE webfilters_sqacllinks.aclid=suricata_sqacllinks.ID
    AND webfilters_sqacllinks.gpid=webfilters_sqgroups.ID
    AND webfilters_sqitems.gpid=webfilters_sqgroups.ID
    AND ( webfilters_sqitems.pattern LIKE '$search' OR webfilters_sqgroups.GroupName LIKE '$search')";


    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        return array(array(),array());
    }
    $COMPRESSRULES=array();
    $COMPRESS_GROUPS=array();
    foreach ($results as $index=>$ligne){
        $ruleid=$ligne["ID"];
        $gpid=$ligne["gpid"];
        $COMPRESS_GROUPS[$gpid]=$ligne["GroupName"];
        $COMPRESSRULES[$ruleid]=$ligne;
    }

    return array($COMPRESSRULES,$COMPRESS_GROUPS);


}
function fillthisRule(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ID=intval($_GET["fill"]);
    $sql="SELECT * FROM suricata_sqacllinks WHERE `ID`='$ID'";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
    $acls=new squid_acls_groups();
    $enabled=$ligne["enabled"];
    $aclgroup=$ligne["aclgroup"];
    $aclgpid    = intval($ligne["aclgpid"]);
    $page       = CurrentPageName();

    $FINAL=$tpl->_ENGINE_parse_body($acls->ACL_MULTIPLE_EXPLAIN($ID,$enabled,$aclgroup));
    echo "if(document.getElementById('explain-this-rule-$ID')){\n";
    $FINAL=str_replace("'","\'",$FINAL);
    $FINAL=str_replace("\n","\\n",$FINAL);
    echo "document.getElementById('explain-this-rule-$ID').innerHTML='$FINAL';\n";
    echo "}\n";

    $results = $q->QUERY_SQL("SELECT * FROM suricata_sqacllinks ORDER BY xORDER");
   

   foreach($results as $index=>$ligne) {
        $ID=$ligne["ID"];
        echo "if(document.getElementById('acl-order-$ID')){document.getElementById('acl-order-$ID').innerHTML='{$ligne["xORDER"]}'};\n";

    }

   if($aclgpid>0){
       echo "Loadjs('$page?fill=$aclgpid');\n";
   }


}
function EXPLAIN_THIS_RULE($ID,$enabled,$aclgroup){
    $ID=intval($ID);
    if($ID==0){
        return "<span style='color:red'>??? wrong ID??</span>";
    }
    $acls=new squid_acls_groups();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FINAL=$acls->ACL_MULTIPLE_EXPLAIN($ID,$enabled,$aclgroup);

    if(isset($_GET["explain-this-rule"])){
        $explain = base64_encode("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID&enabled=$enabled&aclgroup=$aclgroup'>$FINAL</span>");
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $q->QUERY_SQL("UPDATE suricata_sqacllinks SET zExplain='$explain' WHERE ID=$ID");
        return $tpl->_ENGINE_parse_body($FINAL);
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID&enabled=$enabled&aclgroup=$aclgroup'>$FINAL</span>");


}

function groups_rules_table_start(){
    $ID     = intval($_GET["rules-table-start"]);
    $page   = CurrentPageName();
    echo "<div id='GroupOfRules{$ID}' style='margin-top:15px'></div><script>LoadAjax('GroupOfRules{$ID}','$page?table=yes&gprule=$ID&no-tiny=yes');</script>";
}
function new_rule_save():bool{
	$q          =new lib_sqlite("/home/artica/SQLITE/acls.db");
	$tpl        = new template_admin();
	$tpl->CLEAN_POST_XSS();
	$aclport    = 0;
	$aclname    =sqlite_escape_string2($_POST["aclname"]);
	$TempName   =md5(time());
	$aclgpid    = 0;
	$ApplayerProtocol=$_POST["ApplayerProtocol"];
	$sql="INSERT INTO suricata_sqacls (aclname,enabled,ApplayerProtocol,xORDER,aclport,aclgroup,aclgpid)
	VALUES ('$TempName',1,'$ApplayerProtocol','0','$aclport','0','$aclgpid')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
        writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo $q->mysql_error_html(true);return false;}
	
	
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM suricata_sqacls WHERE aclname='$TempName'");
	$LASTID=$ligne["ID"];
	$q->QUERY_SQL("UPDATE suricata_sqacls SET aclname='$aclname' WHERE ID='$LASTID'");
	
	$c=0;
	$sql="SELECT ID FROM suricata_sqacls ORDER BY xORDER";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE suricata_sqacls SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return false;}
		$c++;
	}
    return admin_tracks("Create new IDS ACL rule $aclname");
}
function rule_delete($ID):bool{
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->QUERY_SQL("SELECT aclname FROM suricata_sqacllinks WHERE ID=$ID");
    $aclname=$ligne["aclname"];
    if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	$q->QUERY_SQL("DELETE FROM suricata_sqacls WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	$sql="SELECT ID,enabled FROM suricata_sqacllinks WHERE aclgpid=$ID";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne) {rule_delete($ligne["ID"]);}
	admin_tracks("Remove $aclname ACL IDS rule");
    return true;
}
