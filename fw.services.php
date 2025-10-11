<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["service-popup"])){service_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["service-save"])){service_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-rule"])){delete_confirm();exit;}
page();

function service_js(){
	$page=CurrentPageName();
	$service=$_GET["service-js"];
	$service_text=$service;
	$tpl=new template_admin();
	if($service==null){$service_text="{new_service}";}
	$tpl->js_dialog1("{service2}: $service","$page?service-popup=$service");
}

function delete_js(){
	$t=time();
	$tpl=new template_admin();
	$delete=$tpl->javascript_parse_text("{delete} {service2}:");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "

var xSave$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	LoadAjax('table-loader','$page?table=yes');
}
				
function Save$t(){
	if(!confirm('$delete {$_GET["delete-rule-js"]} ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-rule', '{$_GET["delete-rule-js"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);

}
	
Save$t();";
	
	
	

}

function delete_confirm(){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$q->QUERY_SQL("DELETE FROM firehol_services_def WHERE service='{$_POST["delete-rule"]}'");
	if(!$q->ok){echo "$q->mysql_error";return;}	
	
}

function service_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$service=trim($_GET["service-popup"]);
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$backjs=null;
	
	$ligne=$q->mysqli_fetch_array("SELECT * FROM firehol_services_def WHERE service='$service'");
	
	if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error</div>";}
	
	$explain="{service_firewall_explain}";
	if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
	
	if($service<>null){
		$title="{service2} $service";
		$btname="{apply}";
		$tpl->field_hidden("service-save", $service);
	}else{
		$title="{new_service}";
		$btname="{add}";
		$form[]=$tpl->field_text("service-save", "{service_name2}", null,true);
	}

	$tpl->field_hidden("client_port",$ligne["client_port"]);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_textareacode("server_port","{ports}",@implode("\n",explode(" ",$ligne["server_port"])));
	//$form[]=$tpl->field_textareacode("client_port","{local_ports}",@implode("\n",explode(" ",$ligne["client_port"])));
	echo $tpl->form_outside($title,@implode("\n", $form),$explain,$btname,
			"LoadAjax('table-loader','$page?table=yes');dialogInstance1.close();");


}
function page(){
	$page=CurrentPageName();
	$tpl=new templates();

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{firewall_services}</h1>
	<p>{firewall_services_explain}</p>
	
	</div>
	
	</div>
		

		
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
		
		
		
	<script>
	LoadAjax('table-loader','$page?table=yes');
		
	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$eth_sql=null;
	$token=null;
	$class=null;
	$type=$tpl->javascript_parse_text("{type}");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
	


	$t=time();
	$add="Loadjs('$page?service-js=',true);";

    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure","firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart",
        "");




    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_service} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_firewall_rules} </label>
			</div>
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{enabled}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$type</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Ports</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);


    checkTables();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="SELECT server_port,service,enabled FROM firehol_services_def ORDER by service";
	$results=$q->QUERY_SQL($sql);



	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}

    if(count($results)==0){echo $tpl->FATAL_ERROR_SHOW_128("No service in database!!! bug!!!!");}

	$TRCLASS=null;
foreach ($results as $index=>$ligne){
	$square_class="text-navy";
	$text_class="font-bold";
	$square="<i class='fas fa-check'></i>";
	$ACTION="cloud-goto-32.png";
	if($ligne["enabled"]==0){
		$text_class=" text-muted";
		$ACTION="cloud-goto-32-grey.png";
		$square_class=null;
		$square=$tpl->icon_nothing();
	}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js={$ligne["service"]}')");
        $server_port=$ligne["server_port"];
		
		$html[]="<tr class='$TRCLASS'>";
        $html[]="<td class='center' width='1%' nowrap>$square</td>";
		$html[]="<td class=\"$text_class\" width='1%' nowrap>&nbsp;<span class='$text_class'>".$tpl->td_href($ligne["service"],null,"Loadjs('$page?service-js={$ligne["service"]}')")."</span></td>";
		$html[]="<td class=\"$text_class\"><span class='$text_class'>{$server_port}</span></td>";
		$html[]="<td class='center' width='1%' nowrap>$delete</td>";
		$html[]="</tr>";

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true } } ); });
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}
function service_save(){
	$_POST["service"]=$_POST["service-save"];
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$_POST["service"]=$tpl->CleanServiceName($_POST["service"]);
	$_POST["client_port"]=trim($_POST["client_port"]);
	$_POST["client_port"]=str_replace("  ", " ", $_POST["client_port"]);
	$_POST["client_port"]=str_replace("\n", " ", $_POST["client_port"]);
	$_POST["server_port"]=trim($_POST["server_port"]);
	$_POST["server_port"]=str_replace("  ", " ", $_POST["server_port"]);
	$_POST["server_port"]=str_replace("\n", " ", $_POST["server_port"]);

	
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

    foreach($_POST as $num=>$dr){$_POST[$num]=sqlite_escape_string2(($dr));}
	$ADD="INSERT INTO `firehol_services_def` (service,server_port,client_port,helper,enabled) VALUES ('{$_POST["service"]}','{$_POST["server_port"]}','{$_POST["client_port"]}','','{$_POST["enabled"]}')";
	$EDIT="UPDATE firehol_services_def SET `client_port`='{$_POST["client_port"]}',server_port='{$_POST["server_port"]}',`enabled`='{$_POST["enabled"]}' WHERE `service`='{$_POST["service"]}'";
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="SELECT `service`FROM `firehol_services_def` WHERE `service`='{$_POST["service"]}'";
	$ligne=$q->mysqli_fetch_array($sql);
	$sql=$ADD;
	if($ligne["service"]<>null){$sql=$EDIT;}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
}

function checkTables():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $CountOFfirehol_services_def=$q->COUNT_ROWS("firehol_services_def");
    if($GLOBALS["VERBOSE"]){echo "firehol_services_def -> $CountOFfirehol_services_def item(s)\n";}
    if($CountOFfirehol_services_def>50){return true;}
    $data=base64_decode(@file_get_contents("/usr/share/artica-postfix/ressources/databases/firehol.services.db"));
    $services=unserialize($data);
    if(count($services)<2){
        echo "<H2 style='color:#d32d2d'>FATAL no services database</H2><hr>$data";
        return false;
    }


    foreach($services as $service=>$array){
        $helper=null;
        $server_port=$q->sqlite_escape_string2($array["server"]["ports"]);
        $client_port=$q->sqlite_escape_string2($array["client"]["ports"]);
        if(isset($array["helper"])){$helper=mysql_escape_string2($array["helper"]);}
        $f[]="('$service','$server_port','$client_port','$helper',1)";
    }
    $sql="INSERT INTO `firehol_services_def` (service,server_port,client_port,helper,enabled) VALUES ". @implode(",", $f);
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."<hr>\n$sql\n";return false;}

    return true;

}