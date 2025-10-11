<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["port-popup"])){port_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["md5"])){port_save();exit;}
if(isset($_POST["delete"])){delete();exit;}

table_start();


function table_start():bool{
	$page=CurrentPageName();
	$ID=$_GET["service"];
	echo "<div id='stream-listen-ports-$ID'></div>
	<script>LoadAjax('stream-listen-ports-$ID','$page?table=$ID')</script>";
    return true;
}
function delete_js(){
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
	$ID=$_GET["delete"];
	$md5=$_GET["md5"];
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$ligne=$q->mysqli_fetch_array("SELECT `interface`,`port` FROM stream_ports WHERE ID=$ID");
	$tpl->js_confirm_delete("{$ligne["interface"]}:{$ligne["port"]}", "delete", "$ID","$('#$md5').remove()");
}
function  delete():bool{
	$ID=intval($_POST["delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$q->QUERY_SQL("DELETE FROM `stream_ports` WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks("Remove reverse-proxy port #$ID");
}


function port_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$ID=$_GET["port-js"];
	$serviceid=$_GET["serviceid"];
	$md5=$_GET["md5"];
	$title="{listen_port}";
	if($md5==null){$title="{new_entry}";}
	return $tpl->js_dialog2($title, "$page?port-popup=$ID&serviceid=$serviceid&md5=$md5");
}
function port_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$ID=$_GET["port-popup"];
	$serviceid=$_GET["serviceid"];
	$md5=$_GET["md5"];
	$title="";
    $ligne["options"]=base64_encode(serialize(array()));
	
	$btname="{add}";
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT type FROM nginx_services WHERE ID=$serviceid");
    $type=intval($ligne["type"]);


	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM stream_ports WHERE ID=$ID");
		$btname="{apply}";
		$title="{$ligne["interface"]}:{$ligne["port"]}";
		$serviceid=$ligne["serviceid"];
		$md5=$ligne["zmd5"];
	}
	$js="dialogInstance2.close();LoadAjax('stream-listen-ports-$serviceid','$page?table=$serviceid');NgixSitesReload();";

    if($ID==0){
        if(intval($ligne["port"])<5){
            $ligne["port"]=rand(1024,64000);
        }
    }

	$options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_hidden("md5", $md5);
	$form[]=$tpl->field_hidden("serviceid", $serviceid);
	$form[]=$tpl->field_interfaces("interface", "{listen_interface}", $ligne["interface"]);
	$form[]=$tpl->field_numeric("port","{listen_port}",$ligne["port"]);
	
	$form[]=$tpl->field_section("{options}");
    if($type==5){
        $form[]=$tpl->field_hidden("ssl", 0);
    }else {
        $form[] = $tpl->field_checkbox("ssl", "{UseSSL}", $options["ssl"]);
    }
	$form[]=$tpl->field_checkbox("udp","{UDP_PROTOCOL}",$options["udp"]);
    if($type==5) {
        $form[] = $tpl->field_hidden("proxy_protocol", 0);
    }else{
        $form[] = $tpl->field_checkbox("proxy_protocol", "{proxy_protocol}", $options["proxy_protocol"], false, "{proxy_protocol_explain}");
    }
	
	
	echo $tpl->form_outside($title, $form,"",$btname,"$js","AsSystemWebMaster");
	
}

function port_save():bool{
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$tpl->CLEAN_POST();
	$ID=$_POST["ID"];
	$serviceid=intval($_POST['serviceid']);
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$q->QUERY_SQL("DELETE FROM stream_ports WHERE serviceid=0");
    $interface=$_POST["interface"];
	$md5=md5($_POST["interface"].$_POST["port"]);
	$port=intval($_POST["port"]);
	$options=base64_encode(serialize($_POST));
	if($ID==0){
		$q->QUERY_SQL("INSERT OR IGNORE INTO stream_ports(serviceid,interface,port,zmd5,options) VALUES ($serviceid,'$interface',$port,'$md5','$options')");
		if(!$q->ok){echo $q->mysql_error;}
		return false;
	}
	
	$q->QUERY_SQL("DELETE FROM stream_ports WHERE interface='$interface' AND port='$port'");
	$q->QUERY_SQL("INSERT OR IGNORE INTO stream_ports (serviceid,interface,port,zmd5,options) VALUES ($serviceid,'$interface',$port,'$md5','$options')");
	if(!$q->ok){echo $q->mysql_error;}
	return admin_tracks_post("Create a new reverse-proxy Port");
	
}

function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;


	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?port-js=0&serviceid={$_GET["table"]}&md5=');\"><i class='fa fa-plus'></i> {new_entry} </label>";
	$html[]="</div>";
	$html[]="<table id='table-listenport-{$_GET["table"]}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{interfaces}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{listen_ports}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	

	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	$results=$q->QUERY_SQL("SELECT * FROM stream_ports WHERE serviceid='{$_GET["table"]}'");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return false;}

    $TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
		$md5=md5(serialize($ligne));
		$interface=$ligne["interface"];
        $NICNAME="";
		if($interface==null){$interface="{all}";}else{
			$eth=new system_nic($interface);
            $smallexp="";
            if(strlen($eth->NETWORK)>2){
                $smallexp=" <small>($eth->NETWORK)</small>";
            }
			$NICNAME=$eth->NICNAME." - ".$eth->IPADDR .$smallexp;
		}
		$proto="tcp";
		$ID=intval($ligne["ID"]);
		$port=$ligne["port"];
		if($options["udp"]==1){$proto="udp";}
		$js="Loadjs('$page?port-js=$ID&md5=$md5')";
		if(count($options)==0){$options[]=$tpl->icon_nothing();}
		
		$html[]="<tr class='$TRCLASS' id='$md5'>";
		$html[]="<td nowrap>".$tpl->td_href("[$proto] $interface $NICNAME",null,$js)."</td>";
		$html[]="<td style='width:1%' nowrap id='$index'>".$tpl->td_href($port,null,$js)."</td>";
		$html[]="<td style='width:1%' class='center'>". $tpl->icon_delete("Loadjs('$page?delete=$ID&md5=$md5')","AsSystemWebMaster")."</td>";
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
	$(document).ready(function() { $('#table-listenport-{$_GET["table"]}').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}