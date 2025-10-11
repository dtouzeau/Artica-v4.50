<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.memcached.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}

page();


function page(){
	$page=CurrentPageName();
	echo "<div style='margin-top:10px' id='ufdbweb-unblock-div'></div>
	<script>LoadAjax('ufdbweb-unblock-div','$page?table=yes');</script>
	";
}

function delete_js(){
	$md5=$_GET["delete-js"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM ufdbunlock WHERE `md5`='$md5'");
	$uid=$ligne["uid"];
	$ipaddr=$ligne["ipaddr"];
	$www=$ligne["www"];
	$tpl->js_confirm_delete("$uid/$ipaddr ($www)", "delete", $md5,"$('#$md5').remove()");
	
}
function empty_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_confirm_empty("{unblock_list}", "empty", "yes","LoadAjax('ufdbweb-unblock-div','$page?table=yes');");
}
function empty_table(){
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL("DELETE FROM ufdbunlock");
    admin_tracks("Empty all unblock data");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/whitelists/nohupcompile");
}

function delete(){
	$md5=$_POST["delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL("DELETE FROM ufdbunlock WHERE `md5`='$md5'");
	if(!$q->ok){echo $q->mysql_error;return;}
    admin_tracks("Remove unblock rule $md5");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/whitelists/nohupcompile");


}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	//logintime` INTEGER , `finaltime` INTEGER , `uid` TEXT, `MAC` TEXT, `www` TEXT , `ipaddr` TEXT ,details TEXT

	$btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?empty-js=yes&function={$_GET["function"]}');\"><i class='fas fa-trash-alt'></i> {empty} </label>";
    $btns[]="</div>";
	$html[]="<table id='ufdbweb-unblock-table' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >{website}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{member}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{start}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{end}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";

	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$sql="SELECT * FROM `ufdbunlock` ORDER BY finaltime LIMIT 500";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		$zmd5=$ligne["md5"];
		$logintime=$ligne["logintime"];
		$finaltime=$ligne["finaltime"];
		$uid=$ligne["uid"];
		if($uid=="unknown"){$uid=null;}
		if($uid==null){$uid=$tpl->icon_nothing();}
		$ipaddr=$ligne["ipaddr"];
		$www=$ligne["www"];
		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$zmd5')","AsProxyMonitor");
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td><strong>$www</strong></td>";
		$html[]="<td>$uid/$ipaddr</td>";
		$html[]="<td width=1% nowrap>".$tpl->time_to_date($logintime,true)."</td>";
		$html[]="<td width=1% nowrap>".$tpl->time_to_date($finaltime,true)."</td>";
		$html[]="<td width=1% nowrap>".distanceOfTimeInWords(time(),$finaltime)."</td>";
		$html[]="<td width=1% class='center' nowrap>$delete</center></td>";
		$html[]="</tr>";
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $TINY_ARRAY["TITLE"]="{unblock_list}";
    $TINY_ARRAY["ICO"]="fa-solid fa-cloud-check";
    $TINY_ARRAY["EXPL"]="{unblock_list_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#ufdbweb-unblock-table').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}