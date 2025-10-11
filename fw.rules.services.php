<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["object-save"])){save_object();exit;}
if(isset($_GET["build-table"])){build_table();exit;}
if(isset($_GET["new-object"])){new_object();exit;}
if(isset($_GET["link-object"])){link_object();exit;}
if(isset($_POST["object-link"])){save_link_object();exit;}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["delete-unlink"])){delete_unlink();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
build_page();

function build_page(){
	patch_firewall_tables();
	$ID=intval($_GET["rule-id"]);
	$tpl=new template_admin();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$t=time();

    $topbuttons[] = array("NewObject$t();", ico_plus, "{new_service}");
    $topbuttons[] = array("LinkObject$t();", ico_link, "{link_service}");

	
	$html[]="<div class=row style='margin-top:20px'>";
    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="<div id='fw-objects-table'>";
	$html[]="	</div>";
	$html[]="</div>
<script>
	LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID');
	
	function NewObject$t(){
		document.getElementById('fw-objects-table').innerHTML='&nbsp;';
		LoadAjax('fw-objects-table','$page?new-object=yes&ID=$ID');
	}
	function LinkObject$t(){
		document.getElementById('fw-objects-table').innerHTML='&nbsp;';
		LoadAjax('fw-objects-table','$page?link-object=yes&ID=$ID');
	}	
	
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function delete_js(){
    $page   = CurrentPageName();
	$q      = new lib_sqlite("/home/artica/SQLITE/firewall.db");;
	$ID     = $_GET["delete-js"];
	$service= $_GET["service"];
	$ligne  = $q->mysqli_fetch_array("SELECT services_container FROM iptables_main WHERE ID='$ID'");
    $MAIN= $GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["services_container"]);
	$md     = $_GET["md"];
	
	unset($MAIN[$service]);
	$serialized=serialize($MAIN);
	$services_container=base64_encode($serialized);
	$q->QUERY_SQL("UPDATE iptables_main SET services_container='$services_container' WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo "alert('$q->mysql_error');";return;}
	echo "$('#$md').remove();\nLoadjs('fw.rules.php?fill=$ID');";
	
}


function delete_unlink(){
	$gpid=$_POST["delete-unlink"];
	$ruleid=$_POST["ruleid"];
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
	$sql="DELETE FROM firewallfilter_sqacllinks WHERE gpid=$gpid AND aclid=$ruleid";
	if(!$q->QUERY_SQL($sql)){echo $q->mysql_error;}
}

function delete_remove(){
	$gpid=$_POST["delete-remove"];
	$ruleid=$_POST["ruleid"];
	$q=new mysql_squid_builder();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	if(!$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$gpid'")){echo $q->mysql_error;return;}
	if(!$q->QUERY_SQL("DELETE FROM webfilters_sqgroups WHERE ID='$gpid'")){echo $q->mysql_error;return;}

    if($q->TABLE_EXISTS("webfilters_gpslink")){
        $q->QUERY_SQL("DELETE FROM webfilters_gpslink WHERE groupid='$gpid'");
        $q->QUERY_SQL("DELETE FROM webfilters_gpslink WHERE gpid='$gpid'");
    }
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
	$sql="DELETE FROM firewallfilter_sqacllinks WHERE gpid='$gpid'";
	if(!$q->QUERY_SQL($sql)){echo $q->mysql_error;}
	
	
}
function link_object(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
	
	$ID=intval($_GET["ID"]);
	$direction=intval($_GET["direction"]);
	$btname="{link_service}";
	$title="{link_service}";
	
	$sql="SELECT server_port,service FROM firehol_services_def WHERE enabled=1 ORDER by service";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error_html(true);}
	
	foreach ($results as $index=>$ligne2){
		$ligne2["server_port"]=str_replace(" ", ",", $ligne2["server_port"]);
		$lenght=strlen($ligne2["server_port"]);
		if($lenght>50){$ligne2["server_port"]=substr($ligne2["server_port"], 0,47)."...";}
		$SERVICES[$ligne2["service"]]=$ligne2["service"]." ".$ligne2["server_port"];
	}
	
	$backjs="LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID');Loadjs('fw.rules.php?fill=$ID');";
	$tpl->field_hidden("object-link", $ID);
	$form[]=$tpl->field_array_hash($SERVICES,"service","{service2}",null,true);
	$tpl->form_add_button("{cancel}",$backjs);
	echo $tpl->form_outside($title,@implode("\n", $form),null,$btname,$backjs);
}

function new_object(){
	$page=CurrentPageName();
	$ID=intval($_GET["ID"]);
	$direction=intval($_GET["direction"]);
	$q=new mysql_squid_builder();
	$tpl=new template_admin();
	$title="{new_service}";
	$btname="{add_service}";
	$backjs="LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID');Loadjs('fw.rules.php?fill=$ID');";
	
	$explain="{service_firewall_explain}";
	
	$tpl->field_hidden("object-save", $ID);
	$form[]=$tpl->field_text("service","{service_name2}",null,true);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",1,true);
	$form[]=$tpl->field_textareacode("server_port","{ports}",null);
	$form[]=$tpl->field_textareacode("client_port","{local_ports}",null);
	$tpl->form_add_button("{cancel}",$backjs);
	$html=$tpl->form_outside($title,@implode("\n", $form),$explain,$btname,$backjs);
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__." bytes: ".strlen($html)."<br>\n";}
	echo $html;
}

function save_link_object(){
	$service=$_POST["service"];
	$ID=$_POST["object-link"];
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
	$ligne=$q->mysqli_fetch_array("SELECT services_container FROM iptables_main WHERE ID='$ID'");
	$MAIN= $GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["services_container"]);
	
	$MAIN[$_POST["service"]]=true;
	$services_container=base64_encode(serialize($MAIN));
	$q->QUERY_SQL("UPDATE iptables_main SET services_container='$services_container' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error_html(true);}

}

function save_object(){
	$ID=$_POST["object-save"];
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
    foreach ($_POST as $num=>$dr){
        $_POST[$num]=url_decode_special_tool($dr);}
	    $_POST["service"]=$tpl->CleanServiceName($_POST["service"]);
	
	$_POST["client_port"]=trim($_POST["client_port"]);
	$_POST["client_port"]=str_replace("\n", " ", $_POST["client_port"]);
	$_POST["client_port"]=str_replace("  ", " ", $_POST["client_port"]);
	
	$_POST["server_port"]=trim($_POST["server_port"]);
	$_POST["server_port"]=str_replace("\n", " ", $_POST["server_port"]);
	$_POST["server_port"]=str_replace("  ", " ", $_POST["server_port"]);
	
	$ff=explode(" ",$_POST["server_port"]);
	
	foreach ($ff as $port){
		$port=trim($port);
		if($port==null){continue;}
		if(!preg_match("#(.+?)\/(.+)#", $port)){
			if(!is_numeric($port)){continue;}
			if($port<1){continue;}
			$port="tcp/$port";
		}
		
		$f1[]=$port;
	}
	$_POST["server_port"]=@implode(" ", $f1);
	if($_POST["service"]==null){
		echo "<div class='alert alert-danger'>service: null value Line:".__LINE__."</div>";
		return;
	}
	
	reset($_POST);
    foreach ($_POST as $num=>$dr){$_POST[$num]=mysql_escape_string2(($dr));}
	$ADD="INSERT INTO `firehol_services_def` (service,server_port,client_port,helper,enabled) VALUES ('{$_POST["service"]}','{$_POST["server_port"]}','{$_POST["client_port"]}','','{$_POST["enabled"]}')";
	$EDIT="UPDATE firehol_services_def SET `client_port`='{$_POST["client_port"]}',server_port='{$_POST["server_port"]}',`enabled`='{$_POST["enabled"]}' WHERE `service`='{$_POST["service"]}'";
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
	$sql="SELECT `service` FROM `firehol_services_def` WHERE `service`='{$_POST["service"]}'";
	$ligne=$q->mysqli_fetch_array($sql);
	$sql=$ADD;
	if($ligne["service"]<>null){$sql=$EDIT;}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
		
	
	$ligne=$q->mysqli_fetch_array("SELECT services_container FROM iptables_main WHERE ID='$ID'");
	$MAIN= $GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["services_container"]);
	
	$MAIN[$_POST["service"]]=true;
	$services_container=base64_encode(serialize($MAIN));
	$q->QUERY_SQL("UPDATE iptables_main SET services_container='$services_container' WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error_html(true);}
	
}

function build_table(){
	$ID=intval($_GET["ID"]);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$service=$tpl->_ENGINE_parse_body("{firewall_services}");
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");;
	$ligne=$q->mysqli_fetch_array("SELECT services_container FROM iptables_main WHERE ID='$ID'");
	$MAIN=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["services_container"]);

    $html=array();
	$html[]="<table id='table-firewall-objects' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$service</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter=base64_encode("LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID');");
	

	$TRCLASS=null;
    foreach ($MAIN as $service=>$ligne){
		$service=trim($service);
		if($service==null){continue;}
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$id=md5("$service".microtime(true));
		$html[]="<tr class='$TRCLASS' id='$id'>";
		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&service=$service&js-after=$jsAfter&ruleid=$ID&md=$id')");
		$html[]="<td width='99%'><strong><i class=\"fas fa-comments-alt\"></i>&nbsp;$service</strong></td>";
		$html[]="<td width='1%' class='center'>$delete</td>";
		$html[]="</tr>";
	
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	
	$html[]="
<script>
	$(document).ready(function() { $('#table-firewall-objects').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	
}