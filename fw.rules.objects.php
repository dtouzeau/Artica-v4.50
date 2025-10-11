<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["object-save"])){save_object();exit;}
if(isset($_GET["build-table"])){build_table();exit;}
if(isset($_GET["new-object"])){new_object();exit;}
if(isset($_GET["link-object"])){link_object();exit;}
if(isset($_POST["object-link"])){save_link_object();exit;}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["delete-unlink"])){delete_unlink();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["negation-js"])){negation_js();exit;}
build_page();

function build_page(){
	$ID=intval($_GET["rule-id"]);
	$direction=intval($_GET["direction"]);
	$function=$_GET["function"];
    $NoButtons  = false;
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();

    if($direction==0) {
        $q = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne2 = $q->mysqli_fetch_array("SELECT MOD FROM iptables_main WHERE ID='$ID'");
        if (!$q->ok) {
            echo $q->mysql_error_html(true);
        }
        $MOD = $ligne2["MOD"];
        if ($MOD == "IPFEED") {
            $NoButtons = true;
        }
    }

        $html[]="<div class=row style='margin-top:20px'>";
	if(!$NoButtons) {
        $topbuttons[] = array("NewObject$t();", ico_plus, "{new_object}");
        $topbuttons[] = array("LinkObject$t();", ico_link, "{link_object}");
        $html[]=$tpl->th_buttons($topbuttons);

    }
	$html[]="<div id='fw-objects-table'></div>";
	$html[]="</div>
<script>
	LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID&direction=$direction&function=$function');
	
	function NewObject$t(){
		document.getElementById('fw-objects-table').innerHTML='&nbsp;';
		LoadAjax('fw-objects-table','$page?new-object=yes&ID=$ID&direction=$direction&function=$function');
	}
	function LinkObject$t(){
		document.getElementById('fw-objects-table').innerHTML='&nbsp;';
		LoadAjax('fw-objects-table','$page?link-object=yes&ID=$ID&direction=$direction&function=$function');
	}	
	
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function delete_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$function=$_GET["function"];
	$groupid=$_GET["delete-js"];
	$ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
	$title="{$ligne["GroupName"]} {{$ligne["GroupType"]}} - {delete}/{unlink}";
	$tpl->js_dialog_confirm($title, "$page?delete-confirm=$groupid&js-after={$_GET["js-after"]}&ruleid={$_GET["ruleid"]}&function=$function");
}

function delete_confirm(){
	$tpl            = new template_admin();
	$page           = CurrentPageName();
	$groupid        = $_GET["delete-confirm"];

	$q              = new lib_sqlite("/home/artica/SQLITE/acls.db");
	$t              = time();
    $function       = $_GET["function"];
    $jsfunc         = "Loadjs('fw.rules.php?fill={$_GET["ruleid"]}');";
    if($function<>null){$function=";$function()";}
    $jsAfter        = $tpl->jsToTry(base64_decode($_GET["js-after"]).$function);
	$group_unlink_delete_explain=$tpl->_ENGINE_parse_body("{group_unlink_delete_explain}");
	$ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
	$GroupName=$ligne["GroupName"];
	$group_unlink_delete_explain=str_replace('%GPNAME', $GroupName, $group_unlink_delete_explain);
	$html="<div class=row>
		
		<div class=\"alert alert-danger\">$group_unlink_delete_explain</div>
		
		<table style='width:100%'>
		<tr>
			<td style='text-align:center;width:50%'>
				<button class='btn btn-danger btn-lg' type='button' OnClick=\"Remove$t()\">{delete}</button>
			</td>			
			<td style='text-align:center;width:50%'>
				<button class='btn btn-danger btn-lg' type='button' OnClick=\"Disconnect$t()\">{unlink}</button>
			</td>
		</tr>
		</table>
		</div>
<script>
		
		var xPost$t= function (obj) {
			var res=obj.responseText;
			if(res.length>3){alert(res);return;}
			DialogConfirm.close();
			$jsAfter
			$jsfunc
		}
		function Disconnect$t(){
			var XHR = new XHRConnection();
		    XHR.appendData('delete-unlink', '$groupid');
		    XHR.appendData('ruleid', '{$_GET["ruleid"]}');
		    XHR.sendAndLoad('$page', 'POST',xPost$t);  			
		}
		function Remove$t(){
			var XHR = new XHRConnection();
		    XHR.appendData('delete-remove', '$groupid');
		    XHR.appendData('ruleid', '{$_GET["ruleid"]}');
		    XHR.sendAndLoad('$page', 'POST',xPost$t);  			
		}		
</script>	
";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function delete_unlink(){
	$gpid=$_POST["delete-unlink"];
	$ruleid=$_POST["ruleid"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="DELETE FROM firewallfilter_sqacllinks WHERE gpid=$gpid AND aclid=$ruleid";
	if(!$q->QUERY_SQL($sql)){echo $q->mysql_error;}
}

function delete_remove(){
	$gpid=intval($_POST["delete-remove"]);

	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	writelogs("DELETE FROM webfilters_sqitems WHERE gpid='$gpid'",__FUNCTION__,__FILE__,__LINE__);
	if(!$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$gpid'")){
		writelogs("MySQL Error: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		echo $q->mysql_error;
		return;
	}
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	writelogs("DELETE FROM webfilters_sqgroups WHERE ID='$gpid'",__FUNCTION__,__FILE__,__LINE__);
	if(!$q->QUERY_SQL("DELETE FROM webfilters_sqgroups WHERE ID='$gpid'")){
		writelogs("MySQL Error: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		echo $q->mysql_error;
		return;
	}
    if($q->TABLE_EXISTS("webfilters_gpslink")){
        $q->QUERY_SQL("DELETE FROM webfilters_gpslink WHERE groupid='$gpid'");
        $q->QUERY_SQL("DELETE FROM webfilters_gpslink WHERE gpid='$gpid'");
    }

	
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	writelogs("DELETE FROM firewallfilter_sqacllinks WHERE gpid='$gpid'",__FUNCTION__,__FILE__,__LINE__);
	$sql="DELETE FROM firewallfilter_sqacllinks WHERE gpid='$gpid'";
	if(!$q->QUERY_SQL($sql)){
		writelogs("MySQL Error: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		echo $q->mysql_error;}
	
	
}
function enabled_js(){
	$gpid   = $_GET["enabled-js"];
    $ID     = $_GET["ruleid"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM webfilters_sqgroups WHERE ID='$gpid'");
	$enabled=$ligne["enabled"];
	if($enabled==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE webfilters_sqgroups SET enabled=$enabled WHERE ID=$gpid");
	if(!$q->ok){echo $q->mysql_error;}
	echo "Loadjs('fw.rules.php?fill=$ID');";
}


function link_object(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$function=$_GET["function"];
	$ID=intval($_GET["ID"]);
	$direction=intval($_GET["direction"]);
	$btname="{link_object}";
	$title="{link_object}";
    $MAIN=array();
    $jsfunc=null;
	$results=$q->QUERY_SQL("SELECT ID,GroupName,GroupType FROM webfilters_sqgroups ORDER BY GroupName");
	if(!$q->ok){echo $q->mysql_error_html();}
	foreach ($results as $index=>$ligne) {
		$ligne["GroupName"]=utf8_encode($ligne["GroupName"]);
		$MAIN[$ligne["ID"]]=$tpl->_ENGINE_parse_body("{$ligne["GroupName"]}: {{$ligne["GroupType"]}}");
		
	}
	if($function<>null){
        $jsfunc="$function();";
    }
	$backjs="LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID&direction=$direction&function=$function');Loadjs('fw.rules.php?fill=$ID');";
	$tpl->field_hidden("object-link", $ID);
	$tpl->field_hidden("direction", $direction);
	$form[]=$tpl->field_array_hash($MAIN,"gpid","{object}",null,true);
	$tpl->form_add_button("{cancel}",$backjs);
	echo $tpl->form_outside($title,@implode("\n", $form),null,$btname,$backjs);
}

function new_object():bool{
	$page=CurrentPageName();
	$ID=intval($_GET["ID"]);
	$qProxy=new mysql_squid_builder(true);
	$direction=intval($_GET["direction"]);
	$tpl=new template_admin();
	$title="{new_object}";
	$btname="{add}";
	$function=$_GET["function"];
	$backjs="LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID&direction=$direction&function=$function');";

	$funcjs="Loadjs('fw.rules.php?fill=$ID');";
	$tpl->field_hidden("object-save", $ID);
	$tpl->field_hidden("direction", $direction);
	$form[]=$tpl->field_text("GroupName","{groupname}","{new_group}");
	if($direction==0) {
        $form[] = $tpl->field_array_hash($qProxy->acl_GroupType_Firewall_in,
            "GroupType", "{type}", null, true);
    }
    if($direction==1) {
        $form[] = $tpl->field_array_hash($qProxy->acl_GroupType_Firewall_out,
            "GroupType", "{type}", null, true);
    }


	$tpl->form_add_button("{cancel}",$backjs);
	$html=$tpl->form_outside($title,@implode("\n", $form),null,$btname,"$backjs;$funcjs");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__." bytes: ".strlen($html)."<br>\n";}
	echo $html;
    return true;
}

function save_link_object():bool{
	$gpid=$_POST["gpid"];
	$aclid=$_POST["object-link"];
	$direction=$_POST["direction"];	
	$md5=md5($aclid.$gpid.$direction);
	$sql="INSERT INTO firewallfilter_sqacllinks (zmd5,aclid,gpid,zOrder,direction) VALUES('$md5','$aclid','$gpid',1,$direction)";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return false;}
    return true;
}

function save_object(){
	$ID=$_POST["object-save"];
	$direction=$_POST["direction"];
    $tpl=new template_admin();
	$GroupName=url_decode_special_tool($_POST["GroupName"]);
	$GroupName=$tpl->utf8_decode($GroupName);
	$GroupName=mysql_escape_string2($GroupName);
	$GroupType=$_POST["GroupType"];
	
	$sqladd="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`)
	VALUES ('$GroupName','$GroupType','1','','','0',0);";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	if(!$q->QUERY_SQL($sqladd)){echo $q->mysql_error;}
	
	$aclid=$ID;
	$gpid=$q->last_id;
	$md5=md5($aclid.$gpid.$direction);
	$sql="INSERT INTO firewallfilter_sqacllinks (zmd5,aclid,gpid,zOrder,direction) VALUES('$md5','$aclid','$gpid',1,$direction)";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}
function negation_js(){
    header("content-type: application/x-javascript");
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $mkey           = $_GET["negation-js"];
    $q              = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $table          = "firewallfilter_sqacllinks";
    $sql            = "SELECT aclid,negation FROM $table WHERE zmd5='$mkey'";
    $ligne          = $q->mysqli_fetch_array($sql);
    $id             = $_GET["id"];
    $aclid          = $ligne["aclid"];

    echo "//$sql --> negation == {$ligne["negation"]},ID={$ligne["aclid"]}\n";
    if(intval($ligne["negation"])==0){$negation=1;}else{$negation=0;}

    $sql="UPDATE $table SET `negation`='$negation' WHERE zmd5='$mkey'";
    $q->QUERY_SQL($sql);

    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return;
    }
    $text_is="{is}";
    if($negation==1){$text_is="{is_not}";}
    $text_is=$tpl->_ENGINE_parse_body($text_is);
    $text_is=str_replace("'","\'",$text_is);
    echo $tpl->jsToTry("document.getElementById('$id').innerHTML='$text_is';");
    echo "Loadjs('fw.rules.php?fill=$aclid');\n";

}

function build_table(){
	$ID=intval($_GET["ID"]);
	$direction=intval($_GET["direction"]);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$objects=$tpl->_ENGINE_parse_body("{objects}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$type=$tpl->_ENGINE_parse_body("{type}");
    $function=$_GET["function"];
    $TRCLASS=null;
    $nothing=$tpl->icon_nothing();
    $filtering="true";
    $td1="style='vertical-align:middle' class='center' width=1% nowrap";
	$tdn="style='vertical-align:middle'";


	

	$html=array();
	$html[]="<table id='table-firewall-objects' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='center'>ID</th>";
    $html[]="<th data-sortable=true class='center'>{is}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$objects</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>$type</th>";
	$html[]="<th data-sortable=true class='text-capitalize center'>$items</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter=base64_encode("if(document.getElementById('fw-objects-table') ){LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID&direction=$direction&function=$function');}");

	if($direction==0) {
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne2 = $q->mysqli_fetch_array("SELECT MOD FROM iptables_main WHERE ID='$ID'");
        if(!$q->ok){echo $q->mysql_error_html(true);}
        $MOD=$ligne2["MOD"];
        if($MOD=="IPFEED"){
            $filtering="false";
            $FireholIPSetsEntries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireholIPSetsEntries"));
            $FireholIPSetsEntries=FormatNumber($FireholIPSetsEntries);
            $wljs=$tpl->td_href("{whitelists}",null,"Loadjs('fw.ipfeeds.white.php')");
            $q=new postgres_sql();
            $FireholIPSetsWEntries=FormatNumber($q->COUNT_ROWS_LOW("ipset_whitelists"));

            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $html[]="<td $td1>$nothing</span></td>";
            $html[]="<td $td1>{is}</span></td>";
            $html[]="<td $tdn><strong>{CybercrimeIPFeeds}</strong></td>";
            $html[]="<td $td1>{src}</td>";
            $html[]="<td $td1>$FireholIPSetsEntries</td>";
            $html[]="<td $td1>$nothing</td>";
            $html[]="<td $td1>$nothing</td>";
            $html[]="<td $td1>$nothing</td>";
            $html[]="</tr>";
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $html[]="<td $td1>$nothing</span></td>";
            $html[]="<td $tdn>$wljs</td>";
            $html[]="<td $td1>{src}</td>";
            $html[]="<td $td1>$FireholIPSetsWEntries</td>";
            $html[]="<td $td1>$nothing</td>";
            $html[]="<td $td1>$nothing</td>";
            $html[]="<td $td1>$nothing</td>";
            $html[]="</tr>";
        }

    }

    $sql="SELECT firewallfilter_sqacllinks.gpid,firewallfilter_sqacllinks.negation,
	firewallfilter_sqacllinks.zOrder,firewallfilter_sqacllinks.zmd5 as mkey,
	webfilters_sqgroups.* FROM firewallfilter_sqacllinks,webfilters_sqgroups
	WHERE firewallfilter_sqacllinks.gpid=webfilters_sqgroups.ID
	AND firewallfilter_sqacllinks.aclid=$ID
	AND firewallfilter_sqacllinks.direction='$direction'
	ORDER BY firewallfilter_sqacllinks.zOrder";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$results = $q->QUERY_SQL($sql);

	foreach ($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$mkey=$ligne["mkey"];
		$text_is="{is}";
		$gpid=$ligne["gpid"];
		$html[]="<tr class='$TRCLASS'>";
		if($ligne["negation"]==1){$text_is="{is_not}";}
        $text_is_ico=$tpl->td_href("<span id='text-is-$gpid'>$text_is</span>","{click_to_switch}","Loadjs('$page?negation-js=$mkey&id=text-is-$gpid')");
	
		$MAIN=$tpl->table_object($ligne["ID"]);
		$GROUPNAME=$MAIN["GROUPNAME"];
		$ITEMS=$MAIN["ITEMS"];
		$TYPE=$MAIN["TYPE"];
	
		$jsedit="Loadjs('fw.rules.items.php?groupid={$ligne["ID"]}&js-after=$jsAfter&function=$function')";
		$edit=$tpl->icon_parameters($jsedit);
		$delete=$tpl->icon_delete("Loadjs('$page?delete-js={$ligne["ID"]}&js-after=$jsAfter&ruleid=$ID&function=$function')");
		$enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js={$ligne["ID"]}&function=$function&ruleid=$ID')");
	
		$html[]="<td $td1>{$ligne["ID"]}</td>";
        $html[]="<td $td1>$text_is_ico</td>";
		$html[]="<td $tdn>".$tpl->td_href($GROUPNAME,null,$jsedit)."</td>";
		$html[]="<td $td1>$TYPE</td>";
		$html[]="<td $td1>$ITEMS</td>";
		$html[]="<td $td1>$edit</td>";
		$html[]="<td $td1>$enabled</td>";
		$html[]="<td $td1>$delete</td>";
		$html[]="</tr>";
	
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	
	$html[]="
<script>
	$(document).ready(function() { $('#table-firewall-objects').footable( { \"filtering\": { \"enabled\": $filtering }, \"sorting\": { \"enabled\": false },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}