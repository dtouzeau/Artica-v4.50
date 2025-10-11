<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$GLOBALS["redsocks_family"]["redsocks"]="TCP {to} Proxy";
$GLOBALS["redsocks_family"]["redudp"]="UDP {to} Socks5";

$GLOBALS["target_type"]["socks5"]="Proxy socks5";
$GLOBALS["target_type"]["http-connect"]="{APP_PROXY} (CONNECT)";
$GLOBALS["target_type"]["http-relay"]="{APP_PROXY} ({relay})";

$GLOBALS["transparentmethod"][0]="NAT (Transparent)";
$GLOBALS["transparentmethod"][1]="Tproxy (Transparent Proxy)";

if(isset($_GET["service-enable"])){service_enable();exit;}
if(isset($_GET["service-delete"])){service_delete_js();exit;}
if(isset($_POST["service-delete"])){service_delete();exit;}
if(isset($_GET["service-move"])){service_move();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["rules-start"])){rules_start();exit;}
if(isset($_GET["rules-table"])){rules_table();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["service-wizard"])){service_wizard();exit;}
if(isset($_POST["new-service"])){service_wizard_save();exit;}
if(isset($_GET["lastid-js"])){service_wizard_lastid();exit;}
if(isset($_GET["service-tabs"])){service_tabs();exit;}
if(isset($_GET["service-parameters"])){service_parameters();exit;}
if(isset($_POST["service-id"])){service_parameters_save();exit;}


page();

function service_delete_js(){
	$md=$_GET["md"];
	$ID=intval($_GET["service-delete"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT servicename FROM `redsocks` WHERE ID=$ID");
	$servicename=$ligne["servicename"];
	$tpl->js_confirm_delete($servicename, "service-delete", $ID,"$('#$md').remove()");

}
function service_enable(){
	$ID=intval($_GET["service-enable"]);
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM `redsocks` WHERE ID=$ID");
	$enable=intval($ligne["enabled"]);
	if($enable==1){$enable=0;}else{$enable=1;}
	$q->QUERY_SQL("UPDATE `redsocks` SET `enabled`=$enable WHERE ID=$ID");
}
function service_move(){
	header("content-type: application/x-javascript");
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$tpl=new template_admin();
	$dir=$_GET["dir"];
	$ID=intval($_GET["id"]);
	$ligne=$q->mysqli_fetch_array("SELECT zorder,servicename FROM `redsocks` WHERE ID='$ID'");
	if(!$q->ok){$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "alert('$q->mysql_error');";return;}

	$zorder=intval($ligne["zorder"]);
	$CurOrder=$zorder;
	echo "// {$ligne["servicename"]} [$ID] Current order = $zorder\n";

	if($dir=="up"){
		$zorder=$zorder-1;
		if($zorder<0){$zorder=0;}
	}
	else{
		$zorder=$zorder+1;
	}
	echo "//$ID --> {$ligne["servicename"]} New order = $zorder\n";

	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL("UPDATE `redsocks` SET zorder='$zorder' WHERE ID='$ID'");
	if(!$q->ok){$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "alert('$q->mysql_error');";return;}

	$q->QUERY_SQL("UPDATE `redsocks` SET zorder='$CurOrder' WHERE zorder='$zorder' AND ID<>'$ID'");




	$c=0;
	$results=$q->QUERY_SQL("SELECT ID,servicename,zorder FROM `redsocks` ORDER BY zorder");
	foreach($results as $indedx=>$ligne){
		$ID=$ligne["ID"];
		echo "// {$ligne["servicename"]} ($ID) New order = $c was {$ligne["zorder"]}\n";
		$q->QUERY_SQL("UPDATE `redsocks` SET zorder='$c' WHERE ID='$ID'");
		if(!$q->ok){$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "alert('$q->mysql_error');";return;}
		$c++;
	}
}

function service_delete(){
	$ID=intval($_POST["service-delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL("DELETE FROM `redsocks` WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDSOCKS_VERSION");
	$explain=$tpl->_ENGINE_parse_body("{APP_REDSOCKS_EXPLAIN}");

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_REDSOCKS} v$PrivoxyVersion &raquo;&raquo; {services}</h1>
	<p>$explain</p>
	</div>
	</div>

	<div class='row'>
	<div id='progress-redsocks-restart'></div>
	<div class='ibox-content' style='min-height:600px'>
	<div id='table-redsocks-table'></div>
	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/redsocks');
	LoadAjax('table-redsocks-table','$page?tabs=yes');

	</script>";

	if(isset($_GET["main-page"])){
	$tpl=new template_admin("{APP_REDSOCKS} v$PrivoxyVersion",$html);
	echo $tpl->build_firewall();
	return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
	$HideCorporateFeatures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCorporateFeatures"));
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$array["{service_status}"]="$page?table=yes";
	$array["{rules}"]="$page?rules-start=yes";
	$array["{events}"]="fw.redsocks.events.php";
	echo $tpl->tabs_default($array);
}

function rules_start(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	echo "<div id='redsocks-table-rules'></div><script>LoadAjax('redsocks-table-rules','$page?rules-table=yes');</script>";
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();

	$html="<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='redsocks-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>LoadAjaxSilent('redsocks-status','$page?status=yes');</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function status(){
    $tpl=new template_admin();
	$data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/redsocks/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error().
            "<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $jsrestart3proxy=$tpl->framework_buildjs(
        "/redsocks/restart",
        "redsocks.progress",
        "redsocks.progress.log",
        "progress-redsocks-restart");

    echo $tpl->SERVICE_STATUS($ini, "APP_REDSOCKS",$jsrestart3proxy);
}

function rules_table(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$TRCLASS=null;
	$t=time();
	$Enable3Proxy=intval($sock->GET_INFO("Enable3Proxy"));
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	

    $jsrestart=$tpl->framework_buildjs(
        "/redsocks/restart",
        "redsocks.progress",
        "redsocks.progress.log",
        "progress-redsocks-restart");

	
	$add="Loadjs('$page?service-js=0')";
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_service} </label>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_parameters} </label>";
	$html[]="</div>";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\"></div>";
	
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{service2}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1% nowrap>{listen_port}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1% nowrap>{transparent_method}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1% nowrap>{transparent_ports}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1% nowrap>{destination}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1% nowrap>{enable}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1% nowrap>{move}</th>";
	$html[]="<th data-sortable=false width=1% nowrap>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$sql="SELECT * FROM `3proxy_services` WHERE enabled=1 AND redsocks=1 ORDER BY zorder";
	$results=$q->QUERY_SQL($sql);
	
	$szredsocks_type[1]="http-connect";
	$szredsocks_type[2]="http-relay";
	
	if($Enable3Proxy==1){
	$icon_nothing=$tpl->icon_nothing();
	foreach ($results as $index=>$ligne){
			if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
			$ID=$ligne["ID"];
			$servicename=$ligne["servicename"];
			$nexthope_port=intval($ligne["listen_port"]);
			$listen_interface=$ligne["listen_interface"];
			$local_port=intval($ligne["redsocks_port"]);
			if($listen_interface==null){$listen_interface="eth0";}
			$service_type=$ligne["service_type"];
			$redsocks_type=$ligne["redsocks_type"];
			if(intval($redsocks_type)==0){$redsocks_type=1;}
			$md=md5(serialize($ligne));
			$transparent_type="NAT";
			$hooked_ports=str_replace("\n", ",",base64_decode($ligne["transparentport"]));
			if($service_type==2){$proxy_type="socks5";}
			
			if($service_type==100){$proxy_type=$szredsocks_type[$redsocks_type];}
			
				
			$html[]="<tr class='$TRCLASS' id='$md'>";
			$html[]="<td><strong>". $tpl->td_href($servicename,null,"Loadjs('fw.3proxy.services.php?service-js={$ligne['ID']}')")."</strong></td>";
			$html[]="<td width=1% nowrap>$local_port</td>";
			$html[]="<td width=1% nowrap>$transparent_type</td>";
			$html[]="<td width=1% nowrap>$hooked_ports</td>";
			$html[]="<td width=1% nowrap>$proxy_type://$listen_interface:$nexthope_port</td>";
			$html[]="<td width=1% class='center' nowrap>$icon_nothing</center></td>";
			$html[]="<td width=1% class='center' nowrap>$icon_nothing</center></td>";
			$html[]="<td width=1% class='center' nowrap>$icon_nothing</center></td>";
			$html[]="</tr>";
			
		}
	
	}
	$sql="SELECT * FROM `redsocks` ORDER BY zorder";
	$results=$q->QUERY_SQL($sql);	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$ID=$ligne["ID"];
		$servicename=$ligne["servicename"];
		$redsocks_interface=$ligne["redsocks_interface"];
		if($redsocks_interface==null){$redsocks_interface="{all}";}
		$proxy_type=$GLOBALS["target_type"][$ligne["target_type"]];
		$transparentmethod=$ligne["transparentmethod"];	
		$hooked_ports=str_replace("\n", ", ", base64_decode($ligne["transparentport"]));
		$md=md5(serialize($ligne));
		
		$up=$tpl->icon_up("Loadjs('$page?service-move=yes&id={$ligne["ID"]}&dir=up')","AsFirewallManager");
		$down=$tpl->icon_down("Loadjs('$page?service-move=yes&id={$ligne["ID"]}&dir=down')","AsFirewallManager");
		
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>". $tpl->td_href($servicename,null,"Loadjs('$page?service-js={$ligne['ID']}')")."</strong></td>";
		$html[]="<td width=1% nowrap>{$redsocks_interface}:{$ligne["redsocks_port"]}</td>";
		$html[]="<td width=1% nowrap>{$GLOBALS["transparentmethod"][$transparentmethod]}</td>";
		$html[]="<td width=1% nowrap>{$hooked_ports}</td>";
		$html[]="<td width=1% nowrap>$proxy_type://{$ligne["target_ip"]}:{$ligne["target_port"]}</td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_check($ligne["enabled"],"Loadjs('$page?service-enable=$ID')",null,"AsFirewallManager")."</center></td>";
		$html[]="<td width=1% class='center' nowrap>$up&nbsp;$down</center></td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_delete("Loadjs('$page?service-delete={$ligne['ID']}&md=$md')","AsFirewallManager")."</center></td>";
		$html[]="</tr>";
		
	}
	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='8'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
}

function service_js(){
	$ID=intval($_GET["service-js"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	if($ID==0){
		$tpl->js_dialog1("{new_service}", "$page?service-wizard=0",900);
		return;
	}
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM `redsocks` WHERE ID=$ID");
	$md=md5(serialize($ligne));
	$servicename=$ligne["servicename"];
	$enabled=$ligne["enabled"];
	$listen_port=$ligne["redsocks_port"];
	$outgoing_interface=$ligne["outgoing_interface"];
	$listen_interface=$ligne["listen_interface"];
	$redsocks_family=$ligne["redsocks_family"];
	$service_type=$GLOBALS["ZTYPES"][$ligne["service_type"]];
	$tpl->js_dialog1("$servicename:: $listen_port {$GLOBALS["redsocks_family"][$redsocks_family]}", "$page?service-tabs=$ID");

}

function service_wizard(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$form[]=$tpl->field_hidden("new-service", "yes");
	$form[]=$tpl->field_text("servicename", "{service_name2}", "New service",true);
	$form[]=$tpl->field_array_checkboxes($GLOBALS["redsocks_family"], "redsocks_family", "redsocks");
	echo $tpl->form_outside("{new_service}", $form,null,"{add}",
			"dialogInstance1.close();LoadAjax('redsocks-table-rules','$page?rules-table=yes');Loadjs('$page?lastid-js=yes');","AsFirewallManager");
}
function service_wizard_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$FIREHOLE=false;
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$service_type=$_POST["redsocks_family"];

	$servicename=$q->sqlite_escape_string2($_POST["servicename"]);
	$listen_port=rand(1024,65535);

	$q->QUERY_SQL("INSERT INTO `redsocks` (redsocks_family,servicename,redsocks_port) VALUES ('$service_type','$servicename','$listen_port')");
	if(!$q->ok){echo $q->mysql_error;return;}

	$_SESSION["WIZARD_REDSOCKS_LAST_ID"]=$q->last_id;
}
function service_wizard_lastid(){
	header("content-type: application/x-javascript");
	if(isset($_SESSION["WIZARD_REDSOCKS_LAST_ID"])){$_GET["service-js"]=$_SESSION["WIZARD_REDSOCKS_LAST_ID"];service_js();}
}

function service_tabs(){
	$HideCorporateFeatures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCorporateFeatures"));
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$ID=$_GET["service-tabs"];
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM `redsocks` WHERE ID=$ID");
	$array[$ligne["servicename"]]="$page?service-parameters=$ID";
	$array["{ports}"]="fw.socks.transparent.php?rule-ports=$ID";
	$array["{sources}"]="fw.socks.transparent.php?rule-sources=$ID";
	$array["{destinations}"]="fw.socks.transparent.php?rule-dst=$ID";
	$array["{whitelisted_src_networks}"]="fw.socks.transparent.php?rule-sourcesex=$ID";
	$array["{whitelisted_destination_networks}"]="fw.socks.transparent.php?rule-dstex=$ID";
	echo $tpl->tabs_default($array);	
}

function service_parameters(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$ID=$_GET["service-parameters"];
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM `redsocks` WHERE ID=$ID");	
	$servicename=$ligne["servicename"];
	$redsocks_family=$ligne["redsocks_family"];
	$title="$servicename ({$GLOBALS["redsocks_family"][$redsocks_family]})";
	$jafter="LoadAjax('redsocks-table-rules','$page?rules-table=yes');dialogInstance1.close();";
	
	$tpl->field_hidden("service-id", $ID);
	$tpl->field_hidden("redsocks_family", $ligne["redsocks_family"]);
	
	
	$form[]=$tpl->field_interfaces("redsocks_interface", "{listen_interface}", $ligne["redsocks_interface"]);
	$form[]=$tpl->field_numeric("redsocks_port","{listen_port}",$ligne["redsocks_port"]);
	$form[]=$tpl->field_array_hash($GLOBALS["transparentmethod"], "transparentmethod","{method}", $ligne["transparentmethod"]);

	
	
	if(trim( $ligne["target_type"])==null){$ligne["target_type"]="socks5";}
	if(intval($ligne["target_port"])==0){$ligne["target_port"]=3128;}
	$tpl->field_section("{destination}");
	$form[]=$tpl->field_array_hash($GLOBALS["target_type"], "target_type","{proxy_type}", $ligne["target_type"]);
	$form[]=$tpl->field_text("target_ip", "{destination_address}", $ligne["target_ip"],true);
	$form[]=$tpl->field_numeric("target_port","{destination_port}",$ligne["target_port"]);
	
	echo $tpl->form_outside("$title", $form,null,"{apply}",$jafter,"AsFirewallManager");
	
}

function service_parameters_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=$_POST["service-id"];
	
	$sql="UPDATE redsocks SET
	redsocks_interface='{$_POST["redsocks_interface"]}',
	redsocks_port='{$_POST["redsocks_port"]}',
	target_type='{$_POST["target_type"]}',
	target_ip='{$_POST["target_ip"]}',
	target_port='{$_POST["target_port"]}',
	transparentmethod='{$_POST["transparentmethod"]}',
	target_ip='{$_POST["target_ip"]}',
	target_port='{$_POST["target_port"]}' WHERE ID=$ID";
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
			
			
	
}



