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
function container_move(){
	$q=new lib_sqlite(NginxGetDB()); // sqlite_num_rows
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["container-move"];
	$OrgID=$ID;
	$dir=$_GET["dir"];
	$table="ngx_subdir_items";
	$sql="SELECT zorder FROM `$table` WHERE ID='$ID'";

	$results=$q->QUERY_SQL($sql);$ligne=$results[0];
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	$CurrentOrder=$ligne["zorder"];

	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}

	$sql="UPDATE `$table` SET zorder='$CurrentOrder' WHERE zorder='$NextOrder'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}


	$sql="UPDATE `$table` SET zorder=$NextOrder WHERE ID='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}

	$results=$q->QUERY_SQL("SELECT ID FROM `$table` ORDER by zorder");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}
	$c=1;
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		$sql="UPDATE `$table` SET zorder='$c' WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}
		$c++;
	}


}


function table_start(){
	$page       = CurrentPageName();
	$ID         = intval($_GET["directory_id"]);
	$serviceid  = intval($_GET["serviceid"]);
	$md5        = $_GET["md5"];
	//directory_id=$ID&serviceid=$serviceid&md5=$md5



	echo "<div id='ngx_subdir_items-$ID'></div>
	<script>LoadAjax('ngx_subdir_items-$ID','$page?table=$ID&serviceid=$serviceid&md5=$md5')</script>";
}
function delete_js():bool{
	$page           = CurrentPageName();
	$tpl            = new template_admin();
	$ID             = $_GET["delete"];
	$md5            = $_GET["md5"];
	$directoryid    = intval($_GET["directoryid"]);
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT serviceid FROM ngx_directories WHERE ID=$directoryid");
    $serviceid=$ligne["serviceid"];

	$ligne=$q->mysqli_fetch_array("SELECT `item` FROM ngx_subdir_items WHERE ID=$ID");
	$tpl->js_confirm_delete("{$ligne["item"]}", "delete", "$ID","$('#$md5').remove();LoadAjaxSilent('itemsofdir-$directoryid','fw.nginx.directories.php?items=$directoryid');Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid')");

   return true;
}
function get_servicename($serviceid=0):string{
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$serviceid");
    return strval($ligne["servicename"]);
}
function  delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_POST["delete"];
	$q=new lib_sqlite(NginxGetDB());
	$q->QUERY_SQL("DELETE FROM `ngx_subdir_items` WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
}


function id_js(){
	$page           = CurrentPageName();
	$tpl            = new template_admin();
	$ID             = $_GET["id-js"];
	$serviceid      = $_GET["serviceid"];
	$md5            = $_GET["md5"];
    $directoryid    = intval($_GET["directoryid"]);
	$title="{new_item}: $directoryid {rule}: $serviceid";
	if($ID==0){$title="{new_rule}";}
	$tpl->js_dialog4($title, "$page?id-popup=$directoryid&serviceid=$serviceid&md5=$md5");
}
function id_popup(){
	$page           = CurrentPageName();
	$tpl            = new template_admin();
    $directoryid    = $_GET["id-popup"];
	$serviceid      = $_GET["serviceid"];
	$md5            = $_GET["md5"];
    $title          = "{new_item}";
    $btname         = "{add}";

    $tt[]="dialogInstance4.close()";
    $tt[]="LoadAjax('ngx_subdir_items-$directoryid','$page?table=$directoryid&serviceid=$serviceid&md5=$md5')";
    $tt[]="LoadAjaxSilent('itemsofdir-$directoryid','fw.nginx.directories.php?items=$directoryid')";
    $tt[]="Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid')";
    $js=@implode(";",$tt);

	

	
	$form[]=$tpl->field_hidden("ID", 0);
	$form[]=$tpl->field_hidden("serviceid", $serviceid);
    $form[]=$tpl->field_hidden("directoryid", $directoryid);
	$form[]=$tpl->field_text("ipaddr", "{ipaddr}", null);
	echo $tpl->form_outside($title, $form,"{ngx_stream_access_module}",$btname,"$js","AsSystemWebMaster");
	
}

function id_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$serviceid      = intval($_POST["serviceid"]);
    $directoryid    = intval($_POST["directoryid"]);

	$q=new lib_sqlite(NginxGetDB());
	if($serviceid==0){echo "Service ID missing or null\n";return;}
    if($directoryid==0){echo "Directory ID missing or null\n";return;}
	
	$item=trim($_POST["ipaddr"]);
	if($item<>"*"){
		$ipclass=new IP();
		if(!$ipclass->isIPAddressOrRange($item)){
			echo "Wrong item $item";return;
		}
	}

	$q->QUERY_SQL("INSERT OR IGNORE INTO ngx_subdir_items(directoryid,serviceid,item) VALUES ($directoryid,$serviceid,'$item')");
	if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);}
}

function table(){
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $serviceid  = intval($_GET["serviceid"]);
    $md5        = $_GET["md5"];
    $directoryid= intval($_GET["table"]);

    $topbuttons[] = array("Loadjs('$page?id-js=0&directoryid=$directoryid&serviceid=$serviceid&md5=$md5');",
        ico_plus,"{new_item}");
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="</div>";
	$html[]="<table id='table-ngx_subdir_items-$directoryid' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{item}</th>";
	$html[]="<th data-sortable=false>{order}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";

	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	

	$q=new lib_sqlite(NginxGetDB());
	
	
	$sql="CREATE TABLE IF NOT EXISTS `ngx_subdir_items` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`zorder` INTEGER,
		`directoryid` INTEGER,
		`serviceid` INTEGER,
		`item` text
	)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "$q->mysql_error (".__LINE__.")\n$sql\n";}
	$q->QUERY_SQL("CREATE INDEX IF NOT EXISTS KeyService ON ngx_subdir_items (serviceid,zorder,directoryid)");
	
	
	
	$results=$q->QUERY_SQL("SELECT * FROM ngx_subdir_items WHERE directoryid='$directoryid' ORDER BY zorder");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}


	$TRCLASS=null;
	foreach ($results as $md5=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		
		$md5        = md5(serialize($ligne));
		$ID         = $ligne["ID"];
		$item       = trim($ligne["item"]);
		$zorder     = $ligne["zorder"];
        $mv_up      = $tpl->icon_up("Loadjs('$page?container-move={$ligne["ID"]}&dir=0')","AsSystemWebMaster");
        $mv_down    = $tpl->icon_down("Loadjs('$page?container-move={$ligne["ID"]}&dir=1')","AsSystemWebMasters");

        if($item=="*"){$item="{all}";}
		$html[]="<tr class='$TRCLASS' id='$md5'>";
		$html[]="<td nowrap><strong>$item</strong></td>";
	
		
		if($zorder<2){$mv_up=null;}
		$html[]="<td style='width:1%' class='center' nowrap>$mv_up&nbsp;&nbsp;$mv_down</td>";
		
		
		$html[]="<td style='width:1%' class='center'>". $tpl->icon_delete("Loadjs('$page?delete=$ID&md5=$md5&directoryid=$directoryid')","AsSystemWebMaster")."</td>";
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
	$(document).ready(function() { $('#table-ngx_subdir_items-$directoryid').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	echo $tpl->_ENGINE_parse_body($html);

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