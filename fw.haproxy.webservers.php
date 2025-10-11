<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["backend-js"])){backend_js();exit;}
if(isset($_GET["backend-popup"])){backend_popup();exit;}
if(isset($_POST["backendname"])){backends_save();exit;}
if(isset($_GET["backend-enable-js"])){backends_enable();exit;}
if(isset($_GET["backend-delete-js"])){backend_delete_js();exit;}
if(isset($_POST["backend-delete"])){backend_delete();exit;}
page();

function page(){
	$page=CurrentPageName();
	$servicenameenc=urlencode($_GET["servicename"]);
	echo "<div id='backend-list'></div><script>LoadAjax('backend-list','$page?table=$servicenameenc')</script>";
}
function backend_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$new_backend=$tpl->_ENGINE_parse_body("{new_backend}");
	if($_GET["backend-js"]==null){$title="{$_GET["servicename"]}&raquo;$new_backend";}else{$title="{$_GET["servicename"]}&raquo;{$_GET["backendname"]}";}
	$backendenc=urlencode($_GET["backend-js"]);
	$servicename=urlencode($_GET["servicename"]);
	$tpl->js_dialog2($title, "$page?backend-popup=$backendenc&servicename=$servicename");
}
function backends_enable(){
	$hap=new haproxy_backends($_GET["servicename"], $_GET["backend-enable-js"]);
	if($hap->enabled==1){$hap->enabled=0;}else{$hap->enabled=1;}
	$hap->save();
}
function backend_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$servicename=$_GET["servicename"];
	$backendname=$_GET["backend-delete-js"];
	$value="$servicename|$backendname";
	
	$js[]="$('#{$_GET["md"]}').remove()";
	$js[]="LoadAjax('table-haproxy-services','fw.haproxy.services.php?table=yes');";
	$tpl->js_confirm_delete("$servicename/$backendname" , "backend-delete", $value,@implode(";", $js));
	
}

function backend_delete(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$f=explode("|",$_POST["backend-delete"]);
	$hap=new haproxy_backends($f[0], $f[1]);
	$hap->DeleteBackend();
	
}

function backend_popup(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$servicename=$_GET["servicename"];
	$servicenameenc=urlencode($servicename);
	$backendname=$_GET["backend-popup"];
	$smtp_disable=0;
	$hapServ=new haproxy_multi($servicename);
	if($hapServ->loadbalancetype==2){$smtp_disable=1;}
	$UseSMTPProto=$hapServ->MainConfig["UseSMTPProto"];
	if(!is_numeric($UseSMTPProto)){$UseSMTPProto=0;}
	
	$hapMaster=new haproxy_multi($servicename);
	
	$hap=new haproxy_backends($servicename,$backendname);
	$remove_this_backend=$tpl->javascript_parse_text("{remove_this_backend}");
	
	$jsafter[]="LoadAjax('table-haproxy-services','fw.haproxy.services.php?table=yes');";
	$jsafter[]="LoadAjax('backend-list','$page?table=$servicenameenc');";
	
	$buttonname="{apply}";
	if($backendname==null){$buttonname="{add}";$toolbox=null;}
	if(!is_numeric($hap->MainConfig["inter"])){$hap->MainConfig["inter"]=60000;}
	if(!is_numeric($hap->MainConfig["fall"])){$hap->MainConfig["fall"]=3;}
	if(!is_numeric($hap->MainConfig["rise"])){$hap->MainConfig["rise"]=2;}
	if(!is_numeric($hap->MainConfig["maxconn"])){$hap->MainConfig["maxconn"]=10000;}
	if(!is_numeric($hap->MainConfig["asSquidArtica"])){$hap->MainConfig["asSquidArtica"]=0;}
	
	$ip=new networking();
	$Interfaces=$ip->Local_interfaces();
	$Interfaces[null]="{default}";
	unset($Interfaces["lo"]);
	
	
	$form[]=$tpl->field_info("servicename", "{servicename}", $servicename,true);
	if($backendname<>null){
		$form[]=$tpl->field_info("backendname", "{backendname}", $backendname);
		$form[]=$tpl->field_info("sitename", "{sitename}", $hap->sitename);
		$title=$backendname;
	
	}else{
		$form[]=$tpl->field_text("backendname", "{sitename}", null,true,"{haproxy_website_explain}");
		
		$title="{new_backend}";
		$jsafter[]="dialogInstance2.close();";
	}
	
	$jsafter[]="if( document.getElementById('table-haproxy-bckstatus') ){ LoadAjaxSilent('table-haproxy-bckstatus','fw.haproxy.monitor.php?table=yes');}";
	
	if($hap->listen_port<2){$hap->listen_port=80;}
	
	
	
	$form[]=$tpl->field_array_hash($Interfaces, "localInterface", "{outgoing_address}", $hap->localInterface,false,"{haproxy_local_interface_help}");
	$form[]=$tpl->field_ipaddr("listen_ip", "{destination_address}", $hap->listen_ip,true);
	$form[]=$tpl->field_numeric("listen_port","{destination_port}", $hap->listen_port);
	$form[]=$tpl->field_text("HostHeader", "{HostHeader}", $hap->MainConfig["HostHeader"]);

	
	
	
	$form[]=$tpl->field_section("{timeouts}");
	$form[]=$tpl->field_numeric("bweight","{weight}", $hap->bweight);
	$form[]=$tpl->field_numeric("maxconn","{max_connections}", $hap->MainConfig["maxconn"]);
	$form[]=$tpl->field_numeric("inter","{check_interval} ({milliseconds})", $hap->MainConfig["inter"]);
	$form[]=$tpl->field_numeric("fall","{failed_number} ({attempts})", $hap->MainConfig["fall"]);
	$form[]=$tpl->field_numeric("rise","{success_number} ({attempts})", $hap->MainConfig["rise"]);	
	$html=$tpl->form_outside($title, @implode("\n", $form),null,$buttonname,@implode("", $jsafter),"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}


function table(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql();
	$servicename=$_GET["table"];
	$servicenameenc=urlencode($_GET["table"]);
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/recusor.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/recusor.restart.log";
	$ARRAY["CMD"]="pdns.php?restart-recusor=yes";
	$ARRAY["TITLE"]="{reconfigure_service} {APP_PDNS_RECURSOR}";
	
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?backend-js=&servicename=$servicenameenc');\">";
	$html[]="<i class='fa fa-plus'></i> {new_backend} </label>";
	$html[]="</div>";
	$html[]="<table id='table-haproxy-backends' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{backends}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{sitename}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{destination}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{weight}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{active2}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$sql="SELECT *  FROM `haproxy_backends` WHERE servicename='$servicename' ORDER BY bweight";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	$TRCLASS=null;$ligne=null;
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		
		$md=md5(serialize($ligne));
		$listen_ip=$ligne["listen_ip"];
		$listen_port=$ligne["listen_port"];
		$sitename=$ligne["sitename"];
		$interface="$listen_ip:$listen_port";
		$backendnameenc=urlencode($ligne["backendname"]);
		$disable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?backend-enable-js=$backendnameenc&servicename=$servicenameenc')",null,"AsSquidAdministrator");
		$delete=$tpl->icon_delete("Loadjs('$page?backend-delete-js=$backendnameenc&servicename=$servicenameenc&md=$md')","AsSquidAdministrator");
		$interface=$tpl->td_href($interface,"{$ligne["backendname"]}","Loadjs('$page?backend-js=$backendnameenc&servicename=$servicenameenc')");
		$backendname=$tpl->td_href($ligne["backendname"],"$listen_ip:$listen_port","Loadjs('$page?backend-js=$backendnameenc&servicename=$servicenameenc')");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>$backendname</strong></td>";
		$html[]="<td><strong>$sitename</strong></td>";
		$html[]="<td width=1% nowrap>$interface</td>";
		$html[]="<td style='width:1%' nowrap>{$ligne['bweight']}</a></td>";
		$html[]="<td style='width:1%' nowrap>$disable</td>";
		$html[]="<td style='width:1%' nowrap>$delete</td>";
		$html[]="</tr>";
	
	
	}
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='5'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-haproxy-backends').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}
function backends_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if($_POST["backendname"]==null){
		$_POST["backendname"]=$_POST["sitename"];
	}
	if(!isset($_POST["sitename"])){$_POST["sitename"]=$_POST["backendname"];}
	$_POST["backendname"]=str_replace(".", "_", $_POST["backendname"]);
	$_POST["backendname"]=str_replace(" ", "_", $_POST["backendname"]);
	$_POST["backendname"]=str_replace("/", "_", $_POST["backendname"]);
	if($_POST["backendname"]=="*"){$_POST["backendname"]="_ALL_";}

	$hap=new haproxy_backends($_POST["servicename"],$_POST["backendname"] );
	if(isset($_POST["listen_ip"])){$hap->listen_ip=$_POST["listen_ip"];}
	if(isset($_POST["listen_port"])){$hap->listen_port=$_POST["listen_port"];}
	if(isset($_POST["bweight"])){$hap->bweight=$_POST["bweight"];}
	if(isset($_POST["localInterface"])){$hap->localInterface=$_POST["localInterface"];}
	if(isset($_POST["inter"])){$hap->MainConfig["inter"]=$_POST["inter"];}
	if(isset($_POST["fall"])){$hap->MainConfig["fall"]=$_POST["fall"];}
	if(isset($_POST["rise"])){$hap->MainConfig["rise"]=$_POST["rise"];}
	if(isset($_POST["sitename"])){$hap->sitename=$_POST["sitename"];}
	if(isset($_POST["maxconn"])){$hap->MainConfig["maxconn"]=$_POST["maxconn"];}
	if(isset($_POST["asSquidArtica"])){$hap->MainConfig["asSquidArtica"]=$_POST["asSquidArtica"];}
	if(isset($_POST["FailOverOnly"])){$hap->MainConfig["FailOverOnly"]=$_POST["FailOverOnly"];}
	if(isset($_POST["postfix-send-proxy"])){$hap->MainConfig["postfix-send-proxy"]=$_POST["postfix-send-proxy"];}
	if(isset($_POST["HostHeader"])){$hap->MainConfig["HostHeader"]=$_POST["HostHeader"];}
	
	
	$hap->save();
}