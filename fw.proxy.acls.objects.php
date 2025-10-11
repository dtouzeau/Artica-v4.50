<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

foreach ($_POST as $key=>$value){
    writelogs("$key = > $value","-",__FILE__,__LINE__);
}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_POST["object-group-save"])){save_object_group();exit;}
if(isset($_POST["object-save"])){save_object();exit;}
if(isset($_GET["search-table"])){search_table();exit;}
if(isset($_GET["new-object"])){new_object();exit;}
if(isset($_GET["new-object-js"])){new_object_js();exit;}
if(isset($_GET["new-object-group"])){new_object_group();exit;}

if(isset($_GET["link-object"])){link_object();exit;}
if(isset($_POST["object-link"])){save_link_object();exit;}
if(isset($_GET["enabled-link-js"])){enable_link_object();exit;}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["delete-unlink"])){delete_unlink();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["move-js"])){move_js();exit;}
if(isset($_GET["negation-js"])){negation_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["popup-order-js"])){popup_order_js();exit;}
if(isset($_POST["zorder"])){popup_order_save();exit;}
if(isset($_GET["fill-orders"])){fill_orders();exit;}
build_page();




function top_buttons():bool{

    $ID=intval($_GET["top-buttons"]);
    $_GET["ID"]=$ID;
    $Suffix=build_suffix();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $TableLink=$_GET["TableLink"];
    $TABLE_PROXY["webfilters_sqacllinks"]=true;
    $ProxyPac=intval($_GET["ProxyPac"]);
    $fastacls=intval($_GET["fastacls"]);
    $AS_PROXY=true;
    //if(!isset($TABLE_PROXY[$TableLink])){$AS_PROXY=false;}
    if($ProxyPac==1){$AS_PROXY=false;}
    if($fastacls==1){$AS_PROXY=false;}

    $topbuttons[] = array("document.getElementById('fw-objects-table').innerHTML='&nbsp;';
		LoadAjax('fw-objects-table','$page?new-object=yes&$Suffix');", ico_plus, "{new_object}");
    $topbuttons[] = array("document.getElementById('fw-objects-table').innerHTML='&nbsp;';
		LoadAjax('fw-objects-table','$page?link-object=yes&$Suffix');", ico_link, "{link_object}");
    if($AS_PROXY) {
        $topbuttons[] = array("document.getElementById('fw-objects-table').innerHTML='&nbsp;';
		LoadAjax('fw-objects-table','$page?new-object-group=yes&$Suffix');", "fad fa-layer-group", "{new_group_of_objects}");
    }

    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function build_page()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $ID = intval($_GET["rule-id"]);
    if ($ID == 0) {
        return false;
    }

    if (!isset($_GET["direction"])) {
        $_GET["direction"] = null;
    }
    if (!isset($_GET["fastacls"])) {
        $_GET["fastacls"] = 0;
    }
    if (!isset($_GET["RefreshTable"])) {
        $_GET["RefreshTable"] = null;
    }
    if (!isset($_GET["ProxyPac"])) {
        $_GET["ProxyPac"] = 0;
    }

    $suffix = build_suffix();
    echo "<div style='margin-top:5px' id='div-proxy-object-id'></div>";
    echo "<div style='margin-top:5px;margin-bottom: 5px;' id='div-proxy-button-$ID'></div>";
    echo "<div id='fw-objects-table'></div>";
    echo $tpl->search_block($page, null, null, null, "&search-table=yes&ID=$ID&$suffix");
    return true;
}

function new_object_js(){
    if(!isset($_GET["firewall"])){$_GET["firewall"]=0;}
    $ID=intval($_GET["rule-id"]);
    $direction=intval($_GET["direction"]);
    $TableLink=$_GET["TableLink"];
    $RefreshTable=$_GET["RefreshTable"];
    $ProxyPac=intval($_GET["ProxyPac"]);
    $FireWall=intval($_GET["firewall"]);
    $fastacls=intval($_GET["fastacls"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog3("{new_object}","$page?new-object=yes&ID=$ID&firewall=$FireWall&direction=$direction&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&Dialog=3&fastacls=$fastacls");


}

function delete_js(){
	$TableLink=$_GET["TableLink"];
	$RefreshTable=$_GET["RefreshTable"];$ProxyPac=intval($_GET["ProxyPac"]);
	$backjs="LoadAjax('table-acls-rules','fw.proxy.acls.php?table=yes&TableLink=$TableLink');";
	if($RefreshTable<>null){$backjs=base64_decode($RefreshTable).";$backjs";}
	if($TableLink==null){$TableLink="webfilters_sqacllinks";}
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$mkey=$_GET["delete-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="DELETE FROM {$TableLink} WHERE zmd5='$mkey'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	echo "$('#$mkey').remove();";
	echo "$backjs";
}

function move_js(){
    $tpl=new template_admin();
    if(!isset($_GET["aclid"])){
        echo "aclid not defined";
        writelogs("aclid not defined",__FUNCTION__,__FILE__,__LINE__);
    }
	$TableLink=$_GET["TableLink"];
	if($TableLink==null){$TableLink="webfilters_sqacllinks";}
	$mkey=$_GET["mkey"];
	$direction=$_GET["move-js"];
	$aclid=$_GET["aclid"];
	$table=$TableLink;
	//up =1, Down=0
	
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT * FROM $table WHERE zmd5='$mkey'";
	$ligne=$q->mysqli_fetch_array($sql);

    $keyOrder="zOrder";
    if(!isset($ligne[$keyOrder])){
        $keyOrder="zorder";

    }

	if($GLOBALS["VERBOSE"]){echo "<strong>$sql [$mkey] = {$ligne[$keyOrder]}</strong><br>\n";}
	
	$OlOrder=$ligne[$keyOrder];

	if($direction=="down"){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE $table SET $keyOrder='$OlOrder' WHERE $keyOrder='$NewOrder' AND aclid='$aclid'";
	if($GLOBALS["VERBOSE"]){echo "<strong>$sql</strong><br>\n";}
	//	echo $sql."\n";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$sql="UPDATE $table SET $keyOrder='$NewOrder' WHERE zmd5='$mkey'";
	if($GLOBALS["VERBOSE"]){echo "<strong>$sql</strong><br>\n";}
	$q->QUERY_SQL($sql);
	//	echo $sql."\n";
	if(!$q->ok){echo $q->mysql_error;}
	
	$results=$q->QUERY_SQL("SELECT zmd5 FROM $table WHERE aclid='$aclid' ORDER BY $keyOrder");
	$c=1;

    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
    }

	foreach ($results as $index=>$ligne){
		$zmd5=$ligne["zmd5"];
		$q->QUERY_SQL("UPDATE $table SET $keyOrder='$c' WHERE zmd5='$zmd5'");
		if($GLOBALS["VERBOSE"]){echo "<strong>LOOP: UPDATE $table SET $keyOrder='$c' WHERE zmd5='$zmd5'</strong><br>\n";}
		$c++;
	
	}
    $lib=new lib_memcached();
    $lib->Delkey("DNSFWOBJS");
    $page=CurrentPageName();
	echo "Loadjs('fw.proxy.acls.php?fill=$aclid');\n";
    echo "Loadjs('$page?fill-orders=$TableLink&aclid=$aclid');\n";
	
}
function popup_order_js():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $TableLink=$_GET["TableLink"];
    $zkey=$_GET["popup-order-js"];
    $aclid=intval($_GET["aclid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT zOrder FROM $TableLink WHERE zmd5='$zkey'";
    $ligne=$q->mysqli_fetch_array($sql);
    if(isset($ligne["zOrder"])){
        $zOrder=$ligne["zOrder"];
    }else{
        $zOrder=$ligne["zorder"];
    }
    echo $tpl->js_prompt("{order}",null,"fa-solid fa-arrow-up-1-9",$page,
        "zorder","Loadjs('$page?fill-orders=$TableLink&aclid=$aclid')",$zOrder,"$zkey:$TableLink:$aclid");
    return true;

}
function popup_order_save():bool{

    $tb=explode(":",$_POST["KeyID"]);
    $aclid=$tb[2];
    $TableLink=$tb[1];
    $zkey=$tb[0];
    $newvalue=$_POST["zorder"];

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT zOrder FROM $TableLink WHERE zmd5='$zkey'";
    $ligne=$q->mysqli_fetch_array($sql);
    $keyOrder="zOrder";
    if(!isset($ligne[$keyOrder])){$keyOrder="zorder";}


    $sql="UPDATE $TableLink SET $keyOrder='$newvalue' WHERE zmd5='$zkey'";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL($sql);

    $results=$q->QUERY_SQL("SELECT zmd5 FROM $TableLink WHERE aclid='$aclid' ORDER BY $keyOrder");
    $c=1;
    foreach ($results as $index=>$ligne){
        $zmd5=$ligne["zmd5"];
        $q->QUERY_SQL("UPDATE $TableLink SET $keyOrder='$c' WHERE zmd5='$zmd5'");
        $c++;

    }
    if(!$q->ok){echo $q->mysql_error;return false;}
    return true;

}

function negation_js(){
    $function=$_GET["function"];
	$TableLink=$_GET["TableLink"];
	if($TableLink==null){$TableLink="webfilters_sqacllinks";}
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$mkey=$_GET["negation-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");

	$sql="SELECT aclid,negation FROM $TableLink WHERE zmd5='$mkey'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($GLOBALS["VERBOSE"]){echo "$sql --> negation == {$ligne["negation"]},ID={$ligne["aclid"]}<br>\n";}

	
	$ID=$ligne["aclid"];
	if(intval($ligne["negation"])==0){$negation=1;}else{$negation=0;}
	$sql="UPDATE $TableLink SET `negation`='$negation' WHERE zmd5='$mkey'";
	$q->QUERY_SQL($sql);
	if($GLOBALS["VERBOSE"]){echo "$sql<br>\n";}
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    $lib=new lib_memcached();
    $lib->Delkey("DNSFWOBJS");
    echo "Loadjs('fw.proxy.acls.php?fill=$ID');\n";
	echo "$function();\n";
	echo "LoadAjax('table-acls-rules','fw.proxy.acls.php?table=yes');\n";
    echo "Loadjs('fw.proxy.acls.bugs.php?refresh=yes');\n";
	
	
}

function delete_confirm(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$groupid=$_GET["delete-confirm"];
	$jsAfter=base64_decode($_GET["js-after"]);
	$q=new mysql_squid_builder();
	$t=time();
	$group_unlink_delete_explain=$tpl->_ENGINE_parse_body("{group_unlink_delete_explain}");
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'","artica_backup"));
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
			Loadjs('fw.proxy.acls.php?fill={$_GET["ruleid"]}');
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


function delete_remove(){
	$gpid=$_POST["delete-remove"];
	$acls=new squid_acls();
	$acls->delete_group($gpid);
	$lib=new lib_memcached();
    $lib->Delkey("DNSFWOBJS");
}
function enabled_js(){
	$TableLink=$_GET["TableLink"];
	$RefreshTable=$_GET["RefreshTable"];
    $fastacls=intval($_GET["fastacls"]);
	$backjs="LoadAjax('table-acls-rules','fw.proxy.acls.php?table=yes&TableLink=$TableLink&fastacls=$fastacls');";
	if($RefreshTable<>null){$backjs=base64_decode($RefreshTable).";$backjs";}
	
	
	if($TableLink==null){$TableLink="webfilters_sqacllinks";}
	header("content-type: application/x-javascript");
	$gpid=$_GET["enabled-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT GroupName,enabled FROM webfilters_sqgroups WHERE ID='$gpid'");
	$GroupName=$ligne["GroupName"];
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	$enabled=intval($ligne["enabled"]);
	if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>webfilters_sqgroups $gpid=$enabled</span><br>\n";}
	if($enabled==1){$enabled=0;}else{$enabled=1;}
	$sql="UPDATE webfilters_sqgroups SET enabled=$enabled WHERE ID=$gpid";
	$q->QUERY_SQL($sql);
	if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>webfilters_sqgroups $sql</span><br>\n";}
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return false;}
	echo "$backjs\n";
    admin_tracks("Set ACL group $GroupName to enable: $enabled");
    $lib=new lib_memcached();
    $lib->Delkey("DNSFWOBJS");
	return true;
}

function enable_link_object(){
    $tpl            = new template_admin();
    $zmd5           = $_GET["enabled-link-js"];
    $TableLink      = $_GET["TableLink"];
    $q              = new lib_sqlite("/home/artica/SQLITE/acls.db");

    $ligne=$q->mysqli_fetch_array("SELECT enabled from $TableLink WHERE zmd5='$zmd5'");
    $enabled=intval($ligne["enabled"]);
    if($enabled==1){
        $q->QUERY_SQL("UPDATE $TableLink set enabled=0 WHERE zmd5='$zmd5'");
        if(!$q->ok){
          echo  $tpl->js_error($q->mysql_error);
          return false;
        }
    }
    $q->QUERY_SQL("UPDATE $TableLink set enabled=1 WHERE zmd5='$zmd5'");
    if(!$q->ok){
        echo  $tpl->js_error($q->mysql_error);
        return false;
    }
   return true;

}


function link_object(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $MAIN=array();
	if(!isset($_GET["ProxyPac"])){$ProxyPac=0;}else{$ProxyPac=intval($_GET["ProxyPac"]);}
	$q              = new lib_sqlite("/home/artica/SQLITE/acls.db");
	$qProxy         = new mysql_squid_builder(true);
	$RefreshTable   = $_GET["RefreshTable"];
	$TableLink      = $_GET["TableLink"];
	$firewall       = intval($_GET["firewall"]);
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    $RefreshFunction=base64_decode($_GET["RefreshFunction"]);
    $js[]="document.getElementById('fw-objects-table').innerHTML=''";
    if(strlen($function)>1){
        $js[]="$function()";
    }
    if(strlen($RefreshFunction)>1){
        $js[]="$RefreshFunction";
    }

	if(!isset($_GET["DnsDist"])){$_GET["DnsDist"]=0;}
	if($TableLink==null){$TableLink="webfilters_sqacllinks";}
    if($TableLink=="dnsdist_sqacllinks"){$_GET["DnsDist"]=1;}
    if($TableLink=="wpad_sources_link"){$_GET["ProxyPac"]=1;}
    $fastacls=intval($_GET["fastacls"]);
	
	$ID=intval($_GET["ID"]);
	if($ID==0){echo "<H1 class=text-danger>ID == 0 !</H1>";return;}
	$direction=intval($_GET["direction"]);
	$btname="{link_object}";
	$title="{link_object}";
	
	$results=$q->QUERY_SQL("SELECT ID,GroupName,GroupType FROM webfilters_sqgroups ORDER BY GroupName");
	if(!$q->ok){echo $q->mysql_error_html();}
	foreach ($results as $index=>$ligne){
		
		if($ProxyPac==1){if(!isset($qProxy->acl_GroupType_WPAD[$ligne["GroupType"]])){continue;}}
        if($_GET["DnsDist"]==1){if(!isset($qProxy->acl_GroupType_DNSDIST[$ligne["GroupType"]])){continue;}}


        $ligne["GroupName"]=$tpl->utf8_encode($ligne["GroupName"]);
		$MAIN[$ligne["ID"]]=$tpl->_ENGINE_parse_body("{$ligne["GroupName"]}: {{$ligne["GroupType"]}}");
		
	}
	$backjs="document.getElementById('fw-objects-table').innerHTML=''";
	if($RefreshTable<>null){$backjs=base64_decode($RefreshTable).";$backjs";}
	$tpl->field_hidden("object-link", $ID);
	$tpl->field_hidden("direction", $direction);
	$tpl->field_hidden("TableLink", $TableLink);
	
	$form[]=$tpl->field_array_hash($MAIN,"gpid","{object}",null,true);
	$tpl->form_add_button("{cancel}",$backjs);
	echo $tpl->form_outside($title,@implode("\n", $form),null,$btname,@implode(";",$js));
}

function new_object_group(){
    $page=CurrentPageName();
    $RefreshTable   = $_GET["RefreshTable"];
    $firewall       = intval($_GET["firewall"]);
    $backjs         = null;
    $TableLink      = $_GET["TableLink"];
    $fastacls=intval($_GET["fastacls"]);
    $RefreshFunction= base64_decode($_GET["RefreshFunction"]);
    if($TableLink==null){$TableLink="webfilters_sqacllinks";}
    $Dialog=0;
    if(isset($_GET["Dialog"])){$Dialog=intval($_GET["Dialog"]);}
    VERBOSE("TableLink = [$TableLink]",__LINE__);
    $ID=intval($_GET["ID"]);
    $direction=intval($_GET["direction"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $title="{new_group_of_objects}";
    $btname="{add}";
    $dnsfw=0;
    if($RefreshTable<>null){$backjs=base64_decode($RefreshTable).";$backjs";}
    $RefreshFunctionEnc=base64_encode($RefreshFunction);
    $tpl->field_hidden("object-group-save", $ID);
    $tpl->field_hidden("TableLink", $TableLink);
    $form[]=$tpl->field_text("GroupName","{groupname}","{groupname}");
    if($ID>0) {
        $backjs="document.getElementById('fw-objects-table').innerHTML=''";
        $tpl->form_add_button("{cancel}", $backjs);
    }
    $html=$tpl->form_outside($title,@implode("\n", $form),null,$btname,$backjs);
    if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__." bytes: ".strlen($html)."<br>\n";}
    echo $html;
}

function new_object():bool{
	$page=CurrentPageName();
    $TableLink="";
    $RefreshFunction="";
	$RefreshTable   = $_GET["RefreshTable"];
    $backjs         = null;
    if(isset($_GET["TableLink"])) {
        $TableLink = $_GET["TableLink"];
    }
    $function="";
    $Dialog=0;
    $ID=intval($_GET["ID"]);
    if(isset($_GET["RefreshFunction"])) {
        $RefreshFunction = base64_decode($_GET["RefreshFunction"]);
    }
    if(isset($_GET["function"])){$function=$_GET["function"];}
    if(isset($_GET["Dialog"])){$Dialog=intval($_GET["Dialog"]);}

    if($TableLink==null){$TableLink="webfilters_sqacllinks";}
    VERBOSE("TableLink = [$TableLink]",__LINE__);

    if(preg_match("#dnsdist_sqacllinks#i",$TableLink)){
        VERBOSE("DnsDist = [1]",__LINE__);
        $TableLink="dnsdist_sqacllinks";
        $_GET["DnsDist"]=1;
    }
    if($TableLink=="dnsfw_acls_link"){
        $_GET["dnsfw"]=1;
    }
    if(!isset($_GET["dnsfw"])){$dnsfw=0;}
    $jsAfter=array();
    if($Dialog>0){
        $jsAfter[]="dialogInstance$Dialog.close();";
    }
    if(strlen($function)>3){
        $jsAfter[]="$function()";
    }
    if(strlen($RefreshFunction)>3){
        $jsAfter[]="$RefreshFunction";
    }
    if(strlen($RefreshTable)>3){
        $jsAfter[]=base64_decode($RefreshTable);
    }
    $jsAfter[]="if( document.getElementById('fw-objects-table') ){ document.getElementById('fw-objects-table').innerHTML='';}";

	$tpl=new template_admin();
	$title="{new_object}";
	$btname="{add}";
    $backjs="document.getElementById('fw-objects-table').innerHTML=''";

	
	$tpl->field_hidden("object-save", $ID);
	$tpl->field_hidden("TableLink", $TableLink);
	$form[]=$tpl->field_text("GroupName","{object_name}","{new_group}");
	$form[]=$tpl->field_acls_groups("GroupType","{type}",null,true);
	if($ID>0) {
        $tpl->form_add_button("{cancel}", $backjs);
    }
	$html=$tpl->form_outside($title,$form,null,$btname,@implode(";",$jsAfter));
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__." bytes: ".strlen($html)."<br>\n";}
	echo $html;
    return true;
}
function save_link_object(){
	$gpid=$_POST["gpid"];
	$aclid=$_POST["object-link"];
	
	$TableLink=$_POST["TableLink"];
	if($TableLink==null){$TableLink="webfilters_sqacllinks";}
	
	$md5=md5($aclid.$gpid);
	$sql="INSERT OR IGNORE INTO $TableLink (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid',1)";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return false;}
    $lib=new lib_memcached();
    $lib->Delkey("DNSFWOBJS");
	return true;
}

function save_object_group(){
    $ID=$_POST["object-group-save"];
    writelogs("Adding new object Group [$ID]",__FUNCTION__,__FILE__,__LINE__);

    if(!isset($_POST["direction"])){$_POST["direction"]=0;}
    $direction=$_POST["direction"];
    $TableLink=$_POST["TableLink"];
    if($TableLink==null){$TableLink="webfilters_sqacllinks";}
    $GroupName=url_decode_special_tool($_POST["GroupName"]);
    $GroupName=utf8_decode($GroupName);
    $GroupName=mysql_escape_string2($GroupName);
    $GroupType="AclsGroup";
    $params=md5("$GroupName$GroupType$ID".time());

    $sqladd="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`) VALUES ('$GroupName','$GroupType','1','0','$params','0',0);";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL($sqladd);
    if(!$q->ok){
        writelogs("ERROR $q->mysql_error $sqladd",__FUNCTION__,__FILE__,__LINE__);
        echo $q->mysql_error;
        return false;
    }

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='$params'");
    $gpid = $ligne["ID"];
    writelogs("New Group $gpid",__FUNCTION__,__FILE__,__LINE__);
    $q->QUERY_SQL("UPDATE webfilters_sqgroups SET params='' WHERE ID=$gpid");
    admin_tracks("Create a new ACL group $GroupName ($gpid) type: $GroupType ");

    if($ID>0) {
        $aclid = $ID;
        $md5 = md5($aclid . $gpid . $direction);
        $sql = "INSERT INTO {$TableLink} (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid',1)";
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            writelogs(" $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
            echo $q->mysql_error;
            return false;
        }
    }

    $lib=new lib_memcached();
    $lib->Delkey("DNSFWOBJS");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;

}

function save_object(){
    include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
	$ID=$_POST["object-save"];
    writelogs("Adding new object [$ID]",__FUNCTION__,__FILE__,__LINE__);

	if(!isset($_POST["direction"])){$_POST["direction"]=0;}
	$direction=$_POST["direction"];
	$TableLink=$_POST["TableLink"];
	if($TableLink==null){$TableLink="webfilters_sqacllinks";}
	$GroupName=url_decode_special_tool(trim($_POST["GroupName"]));
	$GroupName=utf8_decode($GroupName);
	$GroupName=mysql_escape_string2($GroupName);
	$GroupType=$_POST["GroupType"];

	$params=md5("$GroupName$GroupType$ID".time());

	$sqladd="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`) VALUES ('$GroupName','$GroupType','1','0','$params','0',0);";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL($sqladd);
	if(!$q->ok){
        writelogs("ERROR $q->mysql_error $sqladd",__FUNCTION__,__FILE__,__LINE__);
	    echo $q->mysql_error;
	    return false;
	}

	$ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='$params'");
    $gpid = $ligne["ID"];
    writelogs("New Group $gpid",__FUNCTION__,__FILE__,__LINE__);
    $q->QUERY_SQL("UPDATE webfilters_sqgroups SET params='' WHERE ID=$gpid");
    admin_tracks("Create a new ACL group $GroupName ($gpid) type: $GroupType ");

    $IP=new IP();
    $addvalue=null;
    if($gpid>0){
        if($GroupType=="src" OR $GroupType=="dst"){
            if($IP->isIPAddressOrRange($GroupName)){
                $addvalue=$GroupName;
            }

        }
        if($addvalue<>null){
            $zdate=date("Y-m-d H:i:s");
            $uid=$_SESSION["uid"];
            if($uid==-100){$uid="Manager";}
            $description="Added by $uid";
            $sql="INSERT INTO webfilters_sqitems (gpid,pattern,zdate,'description',enabled) 
                    VALUES ('$gpid','$addvalue','$zdate','$description',1)";
            $q->QUERY_SQL($sql);
        }
    }


	if($ID>0) {
        $aclid = $ID;
        $md5 = md5($aclid . $gpid . $direction);
        $sql = "INSERT INTO {$TableLink} (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid',1)";
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            writelogs(" $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
            echo $q->mysql_error;
            return false;
        }
    }

	$lib=new lib_memcached();
	$lib->Delkey("DNSFWOBJS");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
	return true;
}

function build_suffix():string{

    $Blacklist=array("table"=>true,"top-buttons"=>true,"search-table"=>true,"jQueryLjs"=>true,"_"=>true,"search"=>true,"DnsDist"=>true);
    if(!isset($_GET["DnsDist"])){$_GET["DnsDist"]=0;}

    $f=array();
    foreach ($_GET as $key=>$val){
        if(isset($Blacklist[$key])) {continue;}
        $f[]="$key=$val";
    }
    return @implode("&",$f);
}

function search_table():bool{
    if(!isset($_GET["ProxyPac"])){$_GET["ProxyPac"]=0;}
    if(!isset($_GET["firewall"])){$_GET["firewall"]=0;}
    if(!isset($_GET["RefreshTable"])){$_GET["RefreshTable"]=null;}
    if(!isset($_GET["ProxyPac"])){$_GET["ProxyPac"]=0;}
    if(!isset($_GET["DnsDist"])){$DnsDist=0;}else{$DnsDist=intval($_GET["DnsDist"]);}

    $RefreshFunctionGet=null;
   if(isset($_GET["RefreshFunction"])){
       $RefreshFunctionGet=$_GET["RefreshFunction"];
   }
   if(!is_null($RefreshFunctionGet)){
       $RefreshFunctionGet= base64_decode($RefreshFunctionGet);
   }
    $TableLink ="";
	$ID                 = intval($_GET["ID"]);
    $RefreshFunctionEnc = null;
    $function           = $_GET["function"];
    $suffix             = build_suffix();
	$tpl                = new template_admin();
	$page               = CurrentPageName();
    if(isset( $_GET["TableLink"])) {
        $TableLink = $_GET["TableLink"];
    }
	$firewall           = intval($_GET["firewall"]);
	$RefreshTable       = $_GET["RefreshTable"];
	$ProxyPac           = intval($_GET["ProxyPac"]);
    $q                  = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $RefreshFunction    = trim("$function();$RefreshFunctionGet");
    $fastacls=intval($_GET["fastacls"]);


    $objects=$tpl->_ENGINE_parse_body("{objects}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$text_is2=$tpl->_ENGINE_parse_body("{is}")."/".$tpl->_ENGINE_parse_body("{is_not}");
	$text_and=$tpl->_ENGINE_parse_body("{and}")."&nbsp;";

    if($TableLink==null){$TableLink="webfilters_sqacllinks";}

	$sql="SELECT $TableLink.gpid,$TableLink.negation,
	$TableLink.zorder,$TableLink.zmd5 as mkey,
	webfilters_sqgroups.* FROM $TableLink,webfilters_sqgroups
	WHERE $TableLink.gpid=webfilters_sqgroups.ID
	AND $TableLink.aclid=$ID
	ORDER BY $TableLink.zorder";

    if($_GET["search"]<>null){
        $search=$q->SearchAntiXSS($_GET["search"]);
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);

        $sql="SELECT $TableLink.gpid,$TableLink.negation,
            $TableLink.zorder,$TableLink.zmd5 as mkey,
            webfilters_sqgroups.* FROM $TableLink,webfilters_sqgroups
            WHERE $TableLink.gpid=webfilters_sqgroups.ID
            AND $TableLink.aclid=$ID
            AND ( (webfilters_sqgroups.GroupName LIKE '$search') OR (webfilters_sqgroups.description LIKE '$search') )
            ORDER BY $TableLink.zorder";

    }


	
	$html=array();
	$html[]="<table id='table-firewall-objects' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize center' nowrap>{order}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' nowrap>$text_is2</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$objects</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$type</th>";
	$html[]="<th data-sortable=true class='text-capitalize center'>$items</th>";
    $html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	
	$RefreshTabledeced=base64_decode($_GET["RefreshTable"]);
    $jsAfter=base64_encode("LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID&firewall=$firewall&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&acl-build=$ID&fastacls=$fastacls&DnsDist=$DnsDist');$RefreshTabledeced;LoadAjax('table-acls-rules','fw.proxy.acls.php?table=yes');$RefreshFunction");
	
	$results = $q->QUERY_SQL($sql);
	VERBOSE("SQL table $TableLink for group id $ID returns ".count($results)." items",__LINE__);
	if(!$q->ok){
	    echo $q->mysql_error_html();
	    return false;
	}
	$td1=$tpl->table_td1prc();
	$TRCLASS=null;
	$c=0;
	$qlyProxy=new mysql_squid_builder();
	foreach ($results as $index=>$ligne){

        $GroupType=$ligne["GroupType"];
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;

		$and=null;
		$icon=null;
		$text_is="{is}";

		if($ligne["negation"]==1){$text_is="{is_not}";}
		$MAIN=$tpl->table_object($ligne["ID"],$TableLink);
        if(!isset($MAIN["ERROR"])){$MAIN["ERROR"]=null;}
		$GROUPNAME=$MAIN["GROUPNAME"];
        $error=$MAIN["ERROR"];
        $ico=$MAIN["ICO"];
        VERBOSE("[$index] - {$ligne["ID"]} - GROUPNAME=$GROUPNAME",__LINE__);
        $ITEMS=intval($MAIN["ITEMS"]);
        $TYPE=$MAIN["TYPE"];
        $NOITEMS=$MAIN["NOITEMS"];

        $mkey=$ligne["mkey"];
        if(isset($qlyProxy->acl_GroupTypeIcon[$TYPE])){$ico=$qlyProxy->acl_GroupTypeIcon[$TYPE];}

        if($ico<>null){
            $icon="<i class='$ico'></i>&nbsp;";
        }

        if($error<>null){
            $text_class="text-danger";
            $icon="<i class='fas fa-exclamation-square'></i>&nbsp;";
            $error="<br><small class=text-danger>$error</small>";
        }

        if($TableLink=="wpad_sources_link" or $TableLink=="wpad_black_link"){
            $sligne=$q->mysqli_fetch_array("SELECT pacpxy FROM webfilters_sqgroups WHERE ID={$ligne["ID"]}");
            $pacpxy=unserializeb64($sligne["pacpxy"]);
            $pacproxs=array();
            if(count($pacpxy)>0){
                foreach ($pacpxy as $xyz=>$pacline){
                    $proxyserver=$pacline["hostname"];
                    if($proxyserver=="0.0.0.0"){
                        $pacproxs=array();
                        $pacproxs[]="{direct_to_internet}";
                        break;
                    }
                    if($proxyserver==null){continue;}
                    $proxyport=$pacline["port"];
                    $pacproxs[]="$proxyserver:$proxyport";
                }
               if(count($pacproxs)>0){
                   $GROUPNAME="$GROUPNAME (<small>Proxy(s):".@implode(", ",$pacproxs)."</small>)";}
            }
        }


		if($c>0){$and=$text_and;}

		$edit_js="Loadjs('fw.rules.items.php?groupid={$ligne["ID"]}&js-after=$jsAfter&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&firewall=$firewall&RefreshFunction=$RefreshFunctionEnc&fastacls=$fastacls&function=$function')";
		$edit=$tpl->icon_parameters($edit_js);
		$delete=$tpl->icon_unlink("Loadjs('$page?delete-js=$mkey&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&firewall=$firewall&RefreshFunction=$RefreshFunctionEnc&fastacls=$fastacls')");



        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-link-js=$mkey&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&firewall=$firewall&RefreshFunction=$RefreshFunctionEnc&fastacls=$fastacls&function=$function')");



		$up=$tpl->icon_up("Loadjs('$page?move-js=up&aclid=$ID&objectid={$ligne["ID"]}&mkey=$mkey&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&firewall=$firewall&RefreshFunction=$RefreshFunctionEnc&fastacls=$fastacls&function=$function')");
		$down=$tpl->icon_down("Loadjs('$page?move-js=down&aclid=$ID&objectid={$ligne["ID"]}&mkey=$mkey&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&firewall=$firewall&RefreshFunction=$RefreshFunctionEnc&fastacls=$fastacls&function=$function')");

		//

		$add_item=$tpl->icon_add("Loadjs('fw.rules.items.php?new-item-js={$ligne["ID"]}&js-after=$jsAfter&ProxyPac=$ProxyPac&fastacls=$fastacls')","AsDansGuardianAdministrator");

		if($NOITEMS){
		    if($ITEMS==0) {
                $ITEMS = $tpl->icon_nothing();
            }else{
                $ITEMS=$tpl->FormatNumber($ITEMS);
            }
            $add_item="&nbsp;";
		}else{
            $ITEMS=$tpl->FormatNumber($ITEMS);
        }


        if($GroupType=="categories"){  $add_item="&nbsp;";}
        if($GroupType=="spf"){  $add_item="&nbsp;";}
        if($GroupType=="dmarc"){  $add_item="&nbsp;";}
        if($GroupType=="spamc"){  $add_item="&nbsp;";}

        if($GroupType=="AclsGroup"){
            $add_item=$tpl->icon_add("Loadjs('fw.rules.items.objects.php?js=yes&groupid={$ligne["ID"]}&$suffix')","AsDansGuardianAdministrator");
        }
        $ZorderKey="zOrder";
        if(!isset($ligne[$ZorderKey])){
            $ZorderKey="zorder";
        }
        $OrderOfRows=$ligne[$ZorderKey];

        $OrderOfRows_link=$tpl->td_href("<span id='groupacls-order-$mkey'>$OrderOfRows</span>",null,"Loadjs('$page?popup-order-js=$mkey&TableLink=$TableLink&aclid=$ID&function=$function');");

        $html[]="<tr class='$TRCLASS' id='$mkey'>";
        $html[]="<td $td1>$OrderOfRows_link</td>";
        if($GroupType=="spf" || $GroupType=="dmarc" || $GroupType=="spamc"){
            $html[] = "<td $td1><b>$and $text_is</b></td>";

        }
        else {
            $html[] = "<td $td1>" . $tpl->td_href($and . $text_is, "{click_to_switch}", "Loadjs('$page?negation-js=$mkey&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&fastacls=$fastacls&function=$function')") . "</span></td>";
        }
        $html[]="<td><span class='$text_class'>". $tpl->td_href($GROUPNAME,null,$edit_js)."</span>$error</td>";
		$html[]="<td nowrap><span class='$text_class'>$icon$TYPE</span></td>";
		$html[]="<td $td1>$ITEMS</center></span></td>";
        $html[]="<td $td1>$add_item</center></span></td>";
		$html[]="<td $td1>$edit</center></span></td>";
		$html[]="<td $td1>$up&nbsp;&nbsp;$down</center></span></td>";
		$html[]="<td $td1>$delete</center></span></td>";
		$html[]="</tr>";
		$c++;
	}

    if($c==0){

        if($TableLink=="wpad_white_link" &&  strlen($_GET["search"])==0){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $html[]="<td colspan='3'><span class='$text_class'>{bypass_proxy_internal}<br><i>{bypass_proxy2}</i></td>";
            $html[]="<td $td1></td>";
            $html[]="<td $td1></td>";
            $html[]="<td $td1></td>";
            $html[]="<td $td1></td>";
            $html[]="</tr>";
        }
    }

	$html[]="</tbody>";
	$html[]="</table>";

	$html[]="<script>";
	if(isset($_GET["acl-build"])) {
        $html[] = "Loadjs('fw.proxy.acls.php?fill=$ID');";
    }
    $suffix             = build_suffix();
    $html[]="LoadAjaxSilent('div-proxy-button-$ID','$page?top-buttons=$ID&function=$function$suffix');";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}

function fill_orders():bool{
    $aclid=intval($_GET["aclid"]);
    $TableLink=$_GET["fill-orders"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT zmd5,zOrder FROM $TableLink WHERE aclid='$aclid'";
    $results=$q->QUERY_SQL($sql);

    foreach ($results as $inex=>$ligne){
        $zmd5=$ligne["zmd5"];
        $zorderk="zOrder";
        if(!isset($ligne[$zorderk])){$zorderk="zorder";}
        $zOrder=$ligne[$zorderk];
        $f[]="if(document.getElementById('groupacls-order-$zmd5')){";
        $f[]="document.getElementById('groupacls-order-$zmd5').innerHTML='$zOrder';";
        $f[]="}";
    }
    echo @implode("\n",$f);

    return true;
}