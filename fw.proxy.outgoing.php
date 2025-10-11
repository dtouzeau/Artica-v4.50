<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
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
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{outgoing_address}",
        "fas fa-ethernet","{outgoing_address_explain}","$page?table=yes","proxy-outgoing-addr","progress-squid-outgoing-restart",
        false,"table-loader-proxy-outgoingaddr");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{outgoing_address}",$html);
        echo $tpl->build_firewall();
        return;
    }



	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function enabled_js(){
	$aclid=$_GET["enabled-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM squid_outgoingaddr_acls WHERE aclid='$aclid'");
	$enabled=$ligne["enabled"];
	if($enabled==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE squid_outgoingaddr_acls SET enabled=$enabled WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;}
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

function rule_delete():bool{
	$aclid=$_POST["delete-rule"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_outgoingaddr_acls WHERE aclid='$aclid'");
    $RULENAME=$ligne["rulename"];
	$q->QUERY_SQL("DELETE FROM outgoingaddr_sqacllinks WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;return false;}
	$q->QUERY_SQL("DELETE FROM squid_outgoingaddr_acls WHERE aclid=$aclid");
	if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("Delete Proxy outgoing address RULE $RULENAME  id=$aclid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/outgoing");
    return true;
}




function rule_id_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["ruleid-js"];
	$title="{new_rule}";
	
	if($id>0){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$ligne=$q->mysqli_fetch_array("SELECT rulename FROM squid_outgoingaddr_acls WHERE aclid='$id'");
		$title="{rule}: $id {$ligne["rulename"]}";
	}
	$title=$tpl->javascript_parse_text($title);
	return $tpl->js_dialog($title,"$page?rule-popup=$id");
}



function rule_settings(){
	$aclid=intval($_GET["rule-settings"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne["enabled"]=1;
	$ligne["zorder"]=1;
	$btname="{add}";
	$title="{new_rule}";
	$BootstrapDialog="BootstrapDialog1.close();";
	if($aclid>0){
		$btname="{apply}";
		
		$ligne=$q->mysqli_fetch_array("SELECT * FROM squid_outgoingaddr_acls WHERE aclid='$aclid'");
		$title=$ligne["rulename"];
		$BootstrapDialog=null;
	}
	
	$ip=new networking();
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
    $Interfaces=array();
	foreach ($interfaces as $eth){
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$Interfaces[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	
	}

	$tpl->field_hidden("rule-save", $aclid);
	$form[]=$tpl->field_text("rulename","{rule_name}",$ligne["rulename"],true);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);


    $form[]=$tpl->field_interfaces("interface","{outgoing_interface}",$ligne["interface"]);
	$form[]=$tpl->field_array_hash($Interfaces, "eth", "{or} {outgoing_address}", $ligne["eth"],false,null,false);
	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"]);
	echo $tpl->form_outside($title,@implode("\n", $form),"{outgoing_interface_explain_proxy}",$btname,"LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');$BootstrapDialog","AsSquidAdministrator");
}




function rule_main_save():bool{

	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
	$users=new usersMenus();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	if(!$users->AsSquidAdministrator){
        echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
        return false;
    }
	
	$aclid=$_POST["rule-save"];
    $interface=$_POST["interface"];
	$rulename=$q->sqlite_escape_string2($_POST["rulename"]);

	if($aclid==0){
		$sqlB="INSERT INTO `squid_outgoingaddr_acls` (`rulename`,`enabled` ,`zorder`,`eth`,interface) 
		VALUES ('$rulename','{$_POST["enabled"]}','{$_POST["zorder"]}','{$_POST["eth"]}','$interface')";
	}else{
		$sqlB="UPDATE `squid_outgoingaddr_acls` SET `rulename`='$rulename',`enabled`='{$_POST["enabled"]}',
		`zorder`='{$_POST["zorder"]}',eth='{$_POST["eth"]}', 
		 interface='$interface' WHERE aclid='$aclid'";
	}
	

	$q->QUERY_SQL($sqlB);
	if(!$q->ok){echo $q->mysql_error_html(true);return false;}

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/outgoing");
    admin_tracks("Set Proxy outgoing address $rulename interface {$_POST["eth"]} id=$aclid");
    return true;

}

function rule_tab(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["rule-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT rulename,logtype FROM squid_outgoingaddr_acls WHERE aclid='$aclid'");
	
	
	
	$array["{rule}"]="$page?rule-settings=$aclid";
	if($aclid>0){
		$array["{objects}"]="fw.proxy.objects.php?aclid=$aclid&main-table=outgoingaddr_sqacllinks&fast-acls=0";
	
	}
	echo $tpl->tabs_default($array);
	
	
}



function rule_move_js(){
		header("content-type: application/x-javascript");
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$tpl=new template_admin();
		$dir=$_GET["dir"];
		$aclid=intval($_GET["aclid"]);
		$ligne=$q->mysqli_fetch_array("SELECT zorder FROM squid_outgoingaddr_acls WHERE aclid='$aclid'");
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
		$q->QUERY_SQL("UPDATE squid_outgoingaddr_acls SET zorder='$zorder' WHERE aclid='$aclid'");
		if(!$q->ok){
			$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);
			echo "alert('$q->mysql_error');";return;
		}
	
		$c=0;
		$results=$q->QUERY_SQL("SELECT aclid FROM squid_outgoingaddr_acls ORDER BY zorder");
		foreach($results as $index=>$ligne) {
			$aclid=$ligne["aclid"];
			echo "// $aclid New order = $c";
			$q->QUERY_SQL("UPDATE squid_outgoingaddr_acls SET zorder='$c' WHERE aclid='$aclid'");
			$c++;
		}
	
	

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$users=new usersMenus();
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
    $SquidServerPersistentConnections=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidServerPersistentConnections"));
    $jsAfter="LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');";
    if($SquidServerPersistentConnections==1){
        echo $tpl->div_error("{tcp_outgoing_persistent}");
    }
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	
	$t=time();
	$add="Loadjs('$page?ruleid-js=0',true);";
	if(!$users->AsSquidAdministrator){$add="alert('$ERROR_NO_PRIVS2')";}

    $jsrestart=$tpl->framework_buildjs("/proxy/acls/outgoing",
        "/squid.access.center.progress","squid.access.center.progress.log","progress-squid-outgoing-restart",$jsAfter);

    $jsonRules=array();
    $CurrRules=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/status/outgoing"));
    if(property_exists($json,"Rules")) {$jsonRules = $json->Rules; }
    foreach ($jsonRules as $rule=>$none) { $CurrRules[$rule]=true; }


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
	$html[]="<th data-sortable=false style='width:1%'></th>";

	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rulename</th>";
    $html[]="<th data-sortable=true style='width:1%'></th>";
	$html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);


	$isRights=isRights();
	$results=$q->QUERY_SQL("SELECT * FROM squid_outgoingaddr_acls ORDER BY zorder");
	$TRCLASS=null;
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
			$rulename=$ligne["rulename"];
			$rulenameenc=urlencode($rulename);
			$aclid=intval($ligne["aclid"]);
            $Interfaces="{ip}:".$ligne["eth"];
            $interface=$ligne["interface"];
            if(strlen($interface)>2) {
                $nic = new system_nic($interface);
                $Interfaces = "($interface) $nic->IPADDR - $nic->NICNAME";
            }
			$check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$aclid')");
			$up=$tpl->icon_up("Loadjs('$page?move-js=yes&aclid=$aclid&dir=up')");
			$down=$tpl->icon_down("Loadjs('$page?move-js=yes&aclid=$aclid&dir=down')");
			$js="Loadjs('$page?ruleid-js=$aclid',true);";
			$delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js={$aclid}&name=$rulenameenc')");
			
			$explain=$tpl->_ENGINE_parse_body("{for_objects} ".proxy_objects($aclid)." {then} {acl_tcp_outgoing_address} $Interfaces {to_reach_internet}");
			if(!$isRights){
				$up="&nbsp;";
				$down="&nbsp;";
				$delete="&nbsp;";
			}

            $status="<span class='label label-default'>{inactive2}</span>";
            if($CurrRules[$aclid]){
                $status="<span class='label label-primary'>{active2}</span>";
            }
            if($PowerDNSEnableClusterSlave==1){
                $check="&nbsp;";
                $up="&nbsp;";
                $down="&nbsp;";
                $delete="&nbsp;";
            }

        $status=$tpl->td_href($status,"",$js);
			$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
			$html[]="<td class=\"center\" style='width: 1%' nowrap>$status</td>";
			$html[]="<td style='vertical-align:middle'>&laquo;&nbsp;<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$rulename:</a><br>$explain</span></td>";
            $html[]="<td style='width: 1%' nowrap class=\"center\">$check</td>";
			$html[]="<td style='width: 1%' nowrap class=\"center\" nowrap>$up&nbsp;&nbsp;$down</td>";
			$html[]="<td style='width: 1%' nowrap class=center>$delete</td>";
			$html[]="</tr>";

	}
	
	$ql=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$sql="SELECT * FROM proxy_ports WHERE enabled=1";
	$results = $ql->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		if($ligne["outgoing_addr"]<>null){
			$nic=new system_nic($ligne["outgoing_addr"]);
			$port=intval($ligne["port"]);
			$Interfaces="$nic->IPADDR - $nic->NICNAME";
            $aclid=$ligne["ID"];
			if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
			$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
			$html[]="<td class=\"center\">".$tpl->icon_nothing()."</td>";
			$html[]="<td style='vertical-align:middle'>".$tpl->_ENGINE_parse_body("{when_using_the_proxy_port}  <strong>$port</strong>:</a>&nbsp;&raquo;&nbsp;{then} {acl_tcp_outgoing_address} $Interfaces {to_reach_internet}")."</td>";
            $html[]="<td class=\"center\">".$tpl->icon_nothing()."</td>";
			$html[]="<td class=\"center\">&nbsp;&nbsp;</td>";
			$html[]="<td class=center>".$tpl->icon_nothing()."</td>";
			$html[]="</tr>";
		}
			
	}
	
	$ql=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$sql="SELECT port FROM proxy_ports WHERE enabled=1 AND transparent=1 AND UseSSL=0";
	$results = $ql->QUERY_SQL($sql);
    $port=array();
	foreach ($results as $index=>$ligne){
        $port[]=$ligne["port"];
    }
	if(count($port)>0){
		$SquidTransparentInterfaceIN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentInterfaceIN"));
		$SquidTransparentInterfaceOUT=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentInterfaceOUT"));
		
		if($SquidTransparentInterfaceOUT<>null){
			if($SquidTransparentInterfaceIN==null){$SquidTransparentInterfaceIN="{all}";}
			if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
			$html[]="<tr class='$TRCLASS' id='row-parent-xtransp'>";
			$html[]="<td class=\"center\">".$tpl->icon_nothing()."</td>";

			$html[]="<td style='vertical-align:middle'>".$tpl->_ENGINE_parse_body("<strong>{default}</strong>:</a>&nbsp;&raquo;&nbsp;{when_using_the_proxy_port}  <strong>{$SquidTransparentInterfaceIN}:{transparent}</strong>: {then} {acl_tcp_outgoing_address} <strong>$SquidTransparentInterfaceOUT</strong> {to_reach_internet}")."</td>";
            $html[]="<td class=\"center\">".$tpl->icon_nothing()."</td>";
			$html[]="<td class=\"center\">&nbsp;&nbsp;</td>";
			$html[]="<td class=center>".$tpl->icon_nothing()."</td>";
			$html[]="</tr>";
        }
	}
    $status="<span class='label label-primary'>{active2}</span>";
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
	$html[]="<td class=\"center\">$status</td>";

	$html[]="<td style='vertical-align:middle'>".$tpl->_ENGINE_parse_body("<strong>{default}</strong>:</a>&nbsp;&raquo;&nbsp;{for_objects} {all} {then} {use_all_interfaces} {to_reach_internet}")."</td>";
    $html[]="<td class=\"center\">".$tpl->icon_nothing()."</td>";
	$html[]="<td class=\"center\">&nbsp;&nbsp;</td>";
	$html[]="<td class=center>".$tpl->icon_nothing()."</td>";
	$html[]="</tr>";
	
	
	

	$html[]="</tbody>";
	$html[]="</table>";


    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
    }
    $topbuttons[] = array($jsrestart, ico_refresh, "{apply_rules}");
    $topbuttons[] = array("LoadAjax('table-loader-proxy-outgoingaddr','$page?table=yes');", ico_retweet, "{refresh}");


    $TINY_ARRAY["TITLE"]="{outgoing_address}";
    $TINY_ARRAY["ICO"]=ico_nic;
    $TINY_ARRAY["EXPL"]="{outgoing_address_explain}";
    $TINY_ARRAY["URL"]="proxy-outgoing-addr";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": false },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
</script>";

	echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function isRights(){
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}

}

function proxy_objects($aclid){
	$qProxy=new mysql_squid_builder(true);
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
    $tt=array();
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