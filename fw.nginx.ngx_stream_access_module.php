<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
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
    $q=new lib_sqlite(NginxGetDB());
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$ID=$_GET["container-move"];
	$dir=$_GET["dir"];
	$table="ngx_stream_access_module";
    $serviceid=intval($_GET["serviceid"]);
	$sql="SELECT serviceid,zorder FROM `$table` WHERE ID='$ID'";

	$results=$q->QUERY_SQL($sql);$ligne=$results[0];
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
	$CurrentOrder=$ligne["zorder"];
    $serviceid=intval($ligne["serviceid"]);

	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$sql="UPDATE `$table` SET zorder='$CurrentOrder' WHERE zorder='$NextOrder' AND serviceid=$serviceid";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return false;}


	$sql="UPDATE `$table` SET zorder=$NextOrder WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return false;}

	$results=$q->QUERY_SQL("SELECT ID FROM `$table` WHERE serviceid=$serviceid ORDER by zorder");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return false;}
	$c=1;
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$sql="UPDATE `$table` SET zorder='$c' WHERE ID='$ID'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return false;}
		$c++;
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
	$ID=$_GET["delete"];
	$md5=$_GET["md5"];
    $q=new lib_sqlite(NginxGetDB());
	$ligne=$q->mysqli_fetch_array("SELECT item,serviceid FROM ngx_stream_access_module WHERE ID=$ID");
    $serviceid=$ligne["serviceid"];
	return $tpl->js_confirm_delete("{$ligne["item"]}", "delete", "$ID","$('#$md5').remove();Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');");
}
function delete():bool{
    $ID=$_POST["delete"];
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT item,serviceid FROM ngx_stream_access_module WHERE ID=$ID");
    $serviceid=$ligne["serviceid"];
    $item=$ligne["item"];

	$q->QUERY_SQL("DELETE FROM `ngx_stream_access_module` WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return false;}
    $servicename=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Remove Access item $item from $servicename");
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
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
	$q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM ngx_stream_access_module WHERE ID=$ID");
		$btname="{apply}";
		$title="{$ligne["item"]}";
		$serviceid=$ligne["serviceid"];
		
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
	$md5=$_POST["md5"];
	$ID=$_POST["ID"];
	$serviceid=intval($_POST['serviceid']);
    $q=new lib_sqlite(NginxGetDB());
	
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
		$q->QUERY_SQL("INSERT OR IGNORE INTO ngx_stream_access_module(serviceid,item,allow) 
				VALUES ($serviceid,'$item',$allow)");
		if(!$q->ok){echo $q->mysql_error;}
        $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
		return false;
	}
	
	$q->QUERY_SQL("UPDATE ngx_stream_access_module SET item='$item',allow='$allow' WHERE ID=$ID");
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
	if(!$q->ok){echo $q->mysql_error;return false;}
    return  admin_tracks_post("Set Access item for site #$serviceid");

	
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

	

	$q=new lib_sqlite(NginxGetDB());
	
	
	$sql="CREATE TABLE IF NOT EXISTS `ngx_stream_access_module` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT, `zorder` INTEGER, `serviceid` INTEGER, `allow` INTEGER, `item` text )";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "$q->mysql_error (".__LINE__.")\n$sql\n";}
	$q->QUERY_SQL("CREATE INDEX IF NOT EXISTS KeyService ON ngx_stream_access_module (serviceid,zorder)");
	
	
	
	$results=$q->QUERY_SQL("SELECT * FROM ngx_stream_access_module WHERE serviceid='$serviceid' ORDER BY zorder");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return false;}

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
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}