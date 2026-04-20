<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["container-move"])){container_move();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["id-js"])){id_js();exit;}
if(isset($_GET["id-popup"])){id_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["ID"])){id_save();exit;}
if(isset($_POST["delete"])){delete();exit;}

table_start();
function container_move():bool{
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$ID=intval($_GET["container-move"]);
	$dir=$_GET["dir"];
	$table="ngx_stream_access_module";
    $serviceid=intval($_GET["serviceid"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/$table/ID/$ID"),true);
    if(!is_array($json) || !$json["Status"] || count($json["rows"])==0){return false;}
    $ligne=$json["rows"][0];
	$CurrentOrder=intval($ligne["zorder"]);
    $serviceid=intval($ligne["serviceid"]);

	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"update","table"=>$table,"set"=>["zorder"=>$CurrentOrder],"where"=>["zorder"=>$NextOrder,"serviceid"=>$serviceid]]);

	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"update","table"=>$table,"set"=>["zorder"=>$NextOrder],"where"=>["ID"=>$ID]]);

	$json2=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/$table/serviceid/$serviceid"),true);
	$c=1;
	if(is_array($json2) && $json2["Status"]){
		foreach ($json2["rows"] as $ligne){
			$rid=intval($ligne["ID"]);
			$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"update","table"=>$table,"set"=>["zorder"=>$c],"where"=>["ID"=>$rid]]);
			$c++;
		}
	}
    echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');";
    return true;

}


function table_start():bool{
	$page=CurrentPageName();
	$ID=$_GET["service"];
	echo "<div id='ngx_stream_access_module-$ID' style='margin-top: 10px'></div>
	<script>LoadAjax('ngx_stream_access_module-$ID','$page?table=$ID')</script>";
    return true;
}
function delete_js():bool{
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$ID=intval($_GET["delete"]);
	$md5=$_GET["md5"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_stream_access_module/ID/$ID"),true);
    $item=""; $serviceid=0;
    if(is_array($json) && $json["Status"] && !empty($json["rows"])){
        $item=$json["rows"][0]["item"]; $serviceid=intval($json["rows"][0]["serviceid"]);
    }
	return $tpl->js_confirm_delete("$item", "delete", "$ID","$('#$md5').remove();Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');");
}
function delete():bool{
    $ID=intval($_POST["delete"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_stream_access_module/ID/$ID"),true);
    $serviceid=0; $item="";
    if(is_array($json) && $json["Status"] && !empty($json["rows"])){
        $serviceid=intval($json["rows"][0]["serviceid"]); $item=$json["rows"][0]["item"];
    }

	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"delete","table"=>"ngx_stream_access_module","where"=>["ID"=>$ID]]);
    $servicename=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Remove Access item $item from $servicename");
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}

function id_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$ID=$_GET["id-js"];
	$serviceid=$_GET["serviceid"];
	$md5=$_GET["md5"];
	$title="{rule}: $ID";
	if($ID==0){$title="{new_rule}";}
	return $tpl->js_dialog3($title, "$page?id-popup=$ID&serviceid=$serviceid&md5=$md5");
}
function id_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$ID=$_GET["id-popup"];
	$serviceid=$_GET["serviceid"];
	$md5=$_GET["md5"];
	$title="{new_item}";
	
	$btname="{add}";
	$ligne=array();
	if($ID>0){
		$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_stream_access_module/ID/$ID"),true);
		if(is_array($json) && $json["Status"] && !empty($json["rows"])){$ligne=$json["rows"][0];}
		$btname="{apply}";
		$title=isset($ligne["item"]) ? "{$ligne["item"]}" : "";
		$serviceid=isset($ligne["serviceid"]) ? intval($ligne["serviceid"]) : $serviceid;
	}
	$js="dialogInstance3.close();LoadAjax('ngx_stream_access_module-$serviceid','$page?table=$serviceid');Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');";
	
	$accepttypes[0]["LABEL"]="{deny}";
	$accepttypes[0]["VALUE"]="0";
	
	$accepttypes[1]["LABEL"]="{allow}";
	$accepttypes[1]["VALUE"]="1";
	
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_hidden("serviceid", $serviceid);
	$form[]=$tpl->field_text("ipaddr", "{ipaddr}", $ligne["item"]);
	$form[]=$tpl->field_checkbox_toogle("allow", "{rule}", intval($ligne["allow"]), $accepttypes);
	echo $tpl->form_outside($title, $form,"{ngx_stream_access_module}",$btname,"$js","AsSystemWebMaster");
	return true;
}

function id_save():bool{
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$tpl->CLEAN_POST_XSS();
	$ID=$_POST["ID"];
	$serviceid=intval($_POST['serviceid']);
	if($serviceid==0){echo "Service ID missing or null\n";}
	
	$item=trim($_POST["ipaddr"]);
	if($item<>"*"){
		$ipclass=new IP();
		if(!$ipclass->isIPAddressOrRange($item)){
            echo $tpl->post_error("Wrong item $item");
			return false;
		}
	}
	
	$allow=intval($_POST["allow"]);
	
	
	
	if($ID==0){
		$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"insert","table"=>"ngx_stream_access_module","values"=>["serviceid"=>$serviceid,"item"=>$item,"allow"=>$allow]]);
        $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
		return false;
	}

	$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", ["action"=>"update","table"=>"ngx_stream_access_module","set"=>["item"=>$item,"allow"=>$allow],"where"=>["ID"=>$ID]]);
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return  admin_tracks_post("Set Access item $item for site #$serviceid");

	
}

function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid=intval($_GET["table"]);
    $topbuttons[] = array("Loadjs('$page?id-js=0&serviceid=$serviceid&md5=')", ico_plus, "{new_rule}");
    $html[]=$tpl->th_buttons($topbuttons);
	$html[]="<table id='table-ngx_stream_access_module-{$_GET["table"]}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{item}</th>";
	$html[]="<th data-sortable=false>{order}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	

	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_stream_access_module/serviceid/$serviceid"),true);
	$results=(is_array($json) && $json["Status"]) ? $json["rows"] : array();

	$STATUS[0]="<span class='label label-danger'>{deny}</span>";
	$STATUS[1]="<span class='label label-primary'>{allow}</span>";
	
	
	$TRCLASS=null;
	foreach ($results as $md5=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md5=md5(serialize($ligne));
		$ID=$ligne["ID"];
		$item=trim($ligne["item"]);
		$zorder=$ligne["zorder"];
		$js="Loadjs('$page?id-js=$ID&md5=$md5')";
		if($item=="*"){$item="{all}";}
		$html[]="<tr class='$TRCLASS' id='$md5'>";
		$html[]="<td style='width:1%' nowrap>{$STATUS[$ligne["allow"]]}</td>";
		$html[]="<td nowrap>".$tpl->td_href("$item",null,$js)."</td>";
		$mv_up=$tpl->icon_up("Loadjs('$page?container-move={$ligne["ID"]}&dir=0&serviceid=$serviceid')","AsSystemWebMaster");
		$mv_down=$tpl->icon_down("Loadjs('$page?container-move={$ligne["ID"]}&dir=1&serviceid=$serviceid')","AsSystemWebMasters");
		if($zorder<2){$mv_up=null;}
		$html[]="<td style='width:1%' class='center' nowrap>$mv_up&nbsp;&nbsp;$mv_down</td>";
		
		
		$html[]="<td style='width:1%'><center>". $tpl->icon_delete("Loadjs('$page?delete=$ID&md5=$md5&serviceid=$serviceid')","AsSystemWebMaster")."</center></td>";
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
	$(document).ready(function() { $('#table-ngx_stream_access_module-{$_GET["table"]}').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}

