<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["group-move"])){group_move();exit;}
if(isset($_GET["group-unlink"])){group_unlink();exit;}
table_temp();


function table_temp(){
	$page=CurrentPageName();
	echo "<div id='qos-rules-table-div'></div><script>LoadAjaxSilent('qos-rules-table-div','$page?table={$_GET["ruleid"]}')</script>";
}

function group_unlink(){
	$tpl=new template_admin();
	$md5=$_GET["group-unlink"];
	$sql="DELETE FROM qos_sqacllinks WHERE zmd5='$md5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}
	header("content-type: application/x-javascript");
	$jsafter=base64_decode($_GET["jsafter"]);
	echo "$('#$md5').remove();\nLoadAjaxSilent('qos-containers','fw.network.fireqos.php?containers-table=yes');";
	
}

function group_move(){
	$mkey=$_GET["group-move"];
	$direction=$_GET["direction"];
	$aclid=$_GET["aclid"];
	$table="qos_sqacllinks";
	//up =1, Down=0
	$tpl=new template_admin();
	$q=new mysql_squid_builder();
	$sql="SELECT zOrder FROM qos_sqacllinks WHERE zmd5='$mkey'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));

	$OlOrder=$ligne["zOrder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE qos_sqacllinks SET zOrder='$OlOrder' WHERE zOrder='$NewOrder' AND aclid='$aclid'";
	//echo $sql."\n";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}
	$sql="UPDATE qos_sqacllinks SET zOrder='$NewOrder' WHERE zmd5='$mkey'";
	$q->QUERY_SQL($sql,"artica_backup");
	//echo $sql."\n";
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}

	$results=$q->QUERY_SQL("SELECT zmd5 FROM qos_sqacllinks WHERE aclid='$aclid' ORDER BY zOrder");
	$c=1;
	while ($ligne = mysqli_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$sql="UPDATE qos_sqacllinks SET zOrder='$c' WHERE zmd5='$zmd5'";
		//echo "$sql\n";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error."<br>$sql");return;}
		$c++;

	}


}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["table"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$TRCLASS=null;

	$refreshfunction=base64_encode("LoadAjaxSilent('qos-rules-table-div','$page?table=$ID')");
	
	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.firewall.objects.php?table-link=qos_sqacllinks&refresh-function=$refreshfunction&ruleid=$ID');\">
			<i class='fa fa-plus'></i> {new_object} </label>
	
			</div>");
	
	$html[]="<table id='table-fireqos-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{order}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{objects}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{items}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>Mv.</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>Del.</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$sql="SELECT qos_sqacllinks.gpid,qos_sqacllinks.negation,
	qos_sqacllinks.zOrder,qos_sqacllinks.zmd5 as mkey,
	webfilters_sqgroups.* FROM qos_sqacllinks,webfilters_sqgroups
	WHERE qos_sqacllinks.gpid=webfilters_sqgroups.ID AND qos_sqacllinks.aclid=$ID
	ORDER BY qos_sqacllinks.zOrder";	
	$acl=new squid_acls_groups();
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo $q->mysql_error_html(true);}
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		$jsafter="LoadAjaxSilent('qos-rules-table-div','$page?table=$ID');LoadAjaxSilent('qos-containers','fw.network.fireqos.php?containers-table=yes');";
		$val=0;
		$mkey=$ligne["mkey"];
		$arrayF=$acl->FlexArray($ligne['ID'],1,$jsafter);

		if($ligne["zOrder"]==1){$up=null;}
		if($ligne["zOrder"]==0){$up=null;}
		

		$jsafterenc=base64_encode($jsafter);
		
		$delete=$tpl->icon_unlink("Loadjs('$page?group-unlink={$ligne["mkey"]}&jsafter=$jsafterenc')","AsFirewallManager");
		$mv_up=$tpl->icon_up("Loadjs('$page?group-move={$ligne["mkey"]}&aclid={$ID}&dir=0')","AsFirewallManager");
		$mv_down=$tpl->icon_down("Loadjs('$page?group-move={$ligne["mkey"]}&aclid={$ID}&dir=1')","AsFirewallManager");
		
		
		
		$js="Loadjs('fw.rules.items.php?groupid={$ligne["ID"]}&js-after=$jsafter')";
		
		$html[]="<tr class=$TRCLASS id='$mkey'>";
		$html[]="<td width=1% nowrap>{$ligne["zOrder"]}</td>";
		$html[]="<td>{$arrayF["ROW"]}</td>";
		$html[]="<td>{$arrayF["ITEMS"]}</td>";
		$html[]="<td width=1% nowrap>$mv_up&nbsp;$mv_down</td>";
		$html[]="<td width=1%>$delete</td>";
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
	$html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-fireqos-rules').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
</script>";	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}


