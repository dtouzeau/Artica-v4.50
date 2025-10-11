<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["addprivs"])){switchsitename();exit;}
if(isset($_GET["gpid"])){table();exit;}
if(isset($_GET["id-js"])){id_js();exit;}
if(isset($_GET["id-popup"])){id_popup();exit;}
if(isset($_GET["delete"])){delete_js();exit;}
if(isset($_POST["ID"])){id_save();exit;}
if(isset($_POST["delete"])){delete();exit;}
table_start();

function table_start():bool{
	$page=CurrentPageName();
	$ID=$_GET["service"];
	echo "<div id='adminprivs-$ID' style='margin-top: 10px'></div>
	<script>LoadAjax('adminprivs-$ID','$page?gpid=$ID')</script>";
    return true;
}
function switchsitename():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
	$serverid=$_GET["addprivs"];
    $group=$_GET["gpid"];
    $q=new lib_sqlite(NginxGetDB());
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM adminprivs WHERE serviceid=$serverid AND item='$group'");
    $ID=intval($ligne["ID"]);
    if($ID>0){
        $q->QUERY_SQL("DELETE FROM adminprivs WHERE ID=$ID");
        return true;
    }
    $q->QUERY_SQL("INSERT INTO adminprivs (serviceid,item) VALUES ('$serverid','$group')");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    return admin_tracks("Add $group privileges for service $serverid");

}
function delete():bool{
    $ID=$_POST["delete"];
    $q=new lib_sqlite(NginxGetDB());
	$q->QUERY_SQL("DELETE FROM `adminprivs` WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return false;}
    return true;
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


function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $gpid=$_GET["gpid"];
    $gpidMd5=md5($gpid);
	$html[]="<table id='table-adminprivs-$gpidMd5' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{allow}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	

	$q=new lib_sqlite(NginxGetDB());
	
	
	$sql="CREATE TABLE IF NOT EXISTS `adminprivs` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT, serviceid INTEGER, item text )";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "$q->mysql_error (".__LINE__.")\n$sql\n";}
	$q->QUERY_SQL("CREATE INDEX IF NOT EXISTS KeyService ON adminprivs (serviceid,item)");
    $services=array();
	$results=$q->QUERY_SQL("SELECT * FROM adminprivs WHERE item='$gpid'");
    foreach ($results as $index=>$ligne){
        $services[$ligne["serviceid"]]=$ligne["ID"];
    }
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return false;}


    $gpidEnc=urlencode($gpid);
    $results=$q->QUERY_SQL("SELECT * FROM nginx_services ORDER BY zorder");
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md5=md5(serialize($ligne));
		$ID=$ligne["ID"];
        $Enabled=0;
        if(isset($services[$ID])){
            $Enabled=1;
        }
        $serversnames=extract_hosts($ligne["hosts"]);
        $servicename=$ligne["servicename"];

        $check=$tpl->icon_check($Enabled,"Loadjs('$page?addprivs=$ID&gpid=$gpidEnc')",null,"AsWebMaster");

		$html[]="<tr class='$TRCLASS' id='$md5'>";
        $html[]="<td style='width:1%;vertical-align: top !important;' nowrap><i class='".ico_earth."'></i>";
		$html[]="<td><strong style='font-size:larger'>$servicename</strong>$serversnames</td>";
		$html[]="<td style='width:1%' nowrap>$check</td>";
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
	$(document).ready(function() { $('#table-adminprivs-$gpidMd5').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
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
function extract_hosts($hosts):string{
    $f=array();

    $Zhosts=explode("||",$hosts);

    foreach ($Zhosts as $servername){
        $catch_all=null;
        $servername=trim($servername);
        if($servername==null){continue;}
        if($servername=="*"){
            $servername=".*";
            $catch_all="&nbsp;&nbsp;<span class='label label-warning'>{catch_all}</span>";
        }
        $f[]="<div style='margin-top:2px;margin-left:10px'><small><i class='".ico_link."'></i>&nbsp;$servername</small>$catch_all</div>";
        }


    return @implode("", $f);
}