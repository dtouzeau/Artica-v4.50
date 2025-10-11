<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["link-js"])){link_js();exit;}
if(isset($_POST["save-gpid"])){object_save();exit;}
if(isset($_GET["link-popup"])){link_popup();exit;}
if(isset($_POST["link-object"])){link_save();exit;}
if(isset($_GET["unlink-object-js"])){unlink_js();exit;}
if(isset($_GET["object-js"])){object_js();exit;}
if(isset($_GET["object-popup"])){object_popup();exit;}
if(isset($_GET["object-tabs"])){object_tabs();exit;}
if(isset($_GET["negation-js"])){negation_js();exit;}
if(isset($_POST["unlink-object"])){unlink_object();exit;}
if(isset($_GET["object-move-js"])){object_move_js();exit;}
if(isset($_GET["count-items"])){items_count();exit;}
main();

function main(){
	$page=CurrentPageName();
	$aclid=intval($_GET["aclid"]);
	
	if($aclid==0){
	echo "<div class='alert alert-danger'>";
	echo "No main rule id";
	echo "</div>";
	return;
	}
	
	$params="?table=yes&aclid=$aclid&fast-acls={$_GET["fast-acls"]}&main-table={$_GET["main-table"]}";
	echo "
	<input type='hidden' id='proxy-acls-objects-table-params' value='$params'>		
	<div id='proxy-acls-objects-table' style='margin-top:20px'></div>
	<script>LoadAjax('proxy-acls-objects-table','$page$params');</script>
	";
}

function unlink_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["aclid"]);
	$gpid=intval($_GET["gpid"]);
	$tablelink=$_GET["main-table"];
	header("content-type: application/x-javascript");
	
	$delete_personal_cat_ask=$tpl->javascript_parse_text("{unlink_group} {$_GET["name"]} ?");
	$t=time();
	$html="
	
var xDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	LoadAjax('proxy-acls-objects-table','$page?table=yes&aclid=$aclid&fast-acls={$_GET["fast-acls"]}&main-table=$tablelink');
	if(document.getElementById('proxies-list') ){ LoadAjax('proxies-list','fw.proxy.parent.php?proxies-list=$aclid'); }
	if(document.getElementById('table-loader-proxy-parents') ){ LoadAjax('table-loader-proxy-parents','fw.proxy.parents.php?table=yes'); }
	if(document.getElementById('table-loader-proxy-logscenter') ){ LoadAjax('table-loader-proxy-logscenter','fw.proxy.logs.php?table=yes'); }
	if(document.getElementById('table-loader-proxy-outgoingaddr') ){ LoadAjax('table-loader-proxy-outgoingaddr','fw.proxy.outgoing.php?table=yes'); }

	
}
	
function Action$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('unlink-object','yes');
	XHR.appendData('aclid','{$aclid}');
	XHR.appendData('gpid','{$gpid}');
	XHR.appendData('table-name','{$tablelink}');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
	
	Action$t();";
	echo $html;
    return true;
}
function object_move_js(){
	header("content-type: application/x-javascript");
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$tpl=new template_admin();
	$dir=$_GET["dir"];
	$gpid=intval($_GET["gpid"]);
	$aclid=intval($_GET["aclid"]);
	$tablelink=$_GET["main-table"];
	$fastacl=$_GET["fast-acls"];
	$zmd5=$_GET["zmd5"];
	$ligne=$q->mysqli_fetch_array("SELECT zorder FROM $tablelink WHERE zmd5='$zmd5'");
	$zorder=intval($ligne["zorder"]);
	echo "// Current order = $zorder\n";
	$id="tr-$zmd5";
	if($dir=="up"){
		$zorder=$zorder-1;
		if($zorder<0){$zorder=0;}
		
	}
	else{
		$zorder=$zorder+1;
	}
	echo "// New order = $zorder\n";
	$q->QUERY_SQL("UPDATE $tablelink SET zorder='$zorder' WHERE zmd5='$zmd5'");
	if(!$q->ok){
		$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);
		echo "alert('$q->mysql_error');";return;
	}
	
	$c=0;
	$results=$q->QUERY_SQL("SELECT zmd5 FROM $tablelink WHERE aclid=$aclid ORDER BY zorder");
	foreach ($results as $index=>$ligne){
		$zmd5=$ligne["zmd5"];
		echo "// $zmd5 New order = $c";
		$q->QUERY_SQL("UPDATE $tablelink SET zorder='$c' WHERE zmd5='$zmd5'");
		$c++;
	}
	
	echo "
if(document.getElementById('table-loader-proxy-parents') ){ LoadAjax('table-loader-proxy-parents','fw.proxy.parents.php?table=yes'); }
if(document.getElementById('table-loader-proxy-logscenter') ){ LoadAjax('table-loader-proxy-logscenter','fw.proxy.logs.php?table=yes'); }	
if(document.getElementById('table-loader-proxy-outgoingaddr') ){ LoadAjax('table-loader-proxy-outgoingaddr','fw.proxy.outgoing.php?table=yes'); }					
";
}


function unlink_object(){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$tablelink=$_POST["table-name"];
	$aclid=intval($_POST["aclid"]);
	$gpid=intval($_POST["gpid"]);
    $q->QUERY_SQL("DELETE FROM `$tablelink` WHERE aclid=$aclid AND gpid=$gpid");
	if(!$q->ok){echo $q->mysql_error;}
}

function  negation_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$tablelink=$_GET["main-table"];
	$zmd5=$_GET["zmd5"];
	$aclid=intval($_GET["aclid"]);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$id="neg-$zmd5";
	$neg[0]=$tpl->javascript_parse_text("{is}");
	$neg[1]=$tpl->javascript_parse_text("{is_not}");
	
	$ligne=$q->mysqli_fetch_array("SELECT negation FROM $tablelink WHERE zmd5='$zmd5'");
	if(!$q->ok){
		$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);
		echo "alert('$q->mysql_error');";return;}
	$negation=intval($ligne["negation"]);
	if($negation==1){$negation=0;}else{$negation=1;}
	$q->QUERY_SQL("UPDATE $tablelink SET negation=$negation WHERE zmd5='$zmd5'");
	if(!$q->ok){
		$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);
		echo "alert('$q->mysql_error');";return;}
	
	
	echo "document.getElementById('$id').innerHTML='".$neg[$negation]."';
	if(document.getElementById('proxies-list') ){ LoadAjax('proxies-list','fw.proxy.parent.php?proxies-list=$aclid'); }
	if(document.getElementById('table-loader-proxy-parents') ){ LoadAjax('table-loader-proxy-parents','fw.proxy.parents.php?table=yes'); }
	if(document.getElementById('table-loader-proxy-logscenter') ){ LoadAjax('table-loader-proxy-logscenter','fw.proxy.logs.php?table=yes'); }
	if(document.getElementById('table-loader-proxy-outgoingaddr') ){ LoadAjax('table-loader-proxy-outgoingaddr','fw.proxy.outgoing.php?table=yes'); }
	";
	
}

function object_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$gpid=intval($_GET["gpid"]);
    $qProxy=new mysql_squid_builder();
	$ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");
	$title=$tpl->javascript_parse_text($ligne["GroupName"]);
    if(!$qProxy->acl_ARRAY_NO_ITEM[$ligne["GroupType"]]){
        $tpl->js_dialog2($title,"$page?object-tabs=yes&gpid=$gpid&aclid={$_GET["aclid"]}");
        return;
    }
    $tpl->js_dialog2($title,"$page?object-popup=yes&gpid=$gpid&aclid={$_GET["aclid"]}");

}

function object_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $gpid=intval($_GET["gpid"]);
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");
    $title=$tpl->javascript_parse_text($ligne["GroupName"]);


    $array[$title]="$page?object-popup=yes&gpid=$gpid&aclid={$_GET["aclid"]}";
    $array["{items}"]="fw.rules.items.php?item-start=$gpid&js-after=$jsafter";
    echo $tpl->tabs_default($array);


}

function object_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$qProxy=new mysql_squid_builder(true);
	$gpid=intval($_GET["gpid"]);
	$aclid=intval($_GET["aclid"]);
	$ligne=$q->mysqli_fetch_array("SELECT * FROM webfilters_sqgroups WHERE ID='$gpid'");
	$buttonname="{apply}";
	$DISABLED=false;
	$acltpl_md5=trim($ligne["acltpl"]);
	$acltpl="{default}";
	
	$qlite=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$results=$qlite->QUERY_SQL("SELECT zmd5,template_name,template_link FROM squidtpls ORDER BY template_name");
	foreach ($results as $index=>$ligne2){
		$templatename=$ligne2["template_name"];
		$MAIN[$ligne2["zmd5"]]="$templatename";
		
	}
	$GroupType=$qProxy->acl_GroupType;
	
	$title=$GroupType[$ligne["GroupType"]];
	
	$FTEXT=false;

	
	$form[]=$tpl->field_hidden("save-gpid", $gpid);
	
	if($ligne["GroupType"]=="proxy_auth_tagad"){
		$form[]=$tpl->field_browse_adgroups("GroupName", "{object_name}", utf8_encode($ligne["GroupName"]));
		$FTEXT=true;
	}
	
	if(!$FTEXT){
		$form[]=$tpl->field_text("GroupName", "{object_name}", utf8_encode($ligne["GroupName"]),false,null,$DISABLED);
	}
	
	$form[]=$tpl->field_array_hash($MAIN, "acltpl", "{template}", $ligne["acltpl"]);
	$form[]=$tpl->field_checkbox("tplreset","{reset_connection}",$ligne["tplreset"]);

	$jsafter="
	if(document.getElementById('proxy-acls-objects-table-params')){
		var params=document.getElementById('proxy-acls-objects-table-params').value;
		LoadAjax('proxy-acls-objects-table','$page'+params);
	}
	
	if(document.getElementById('proxies-list') ){ LoadAjax('proxies-list','fw.proxy.parent.php?proxies-list=$aclid');}
	if(document.getElementById('table-loader-proxy-parents') ){ LoadAjax('table-loader-proxy-parents','fw.proxy.parents.php?table=yes'); }
	if(document.getElementById('table-loader-proxy-logscenter') ){ LoadAjax('table-loader-proxy-logscenter','fw.proxy.logs.php?table=yes'); }
	if(document.getElementById('table-loader-proxy-outgoingaddr') ){ LoadAjax('table-loader-proxy-outgoingaddr','fw.proxy.outgoing.php?table=yes'); }
	";
	
	$html[]=$tpl->form_outside($title, @implode("\n", $form),$qProxy->acl_GroupType_explain[$ligne["GroupType"]],"{apply}",$jsafter);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function object_save(){
	$isRights=isRights();
	$tpl=new template_admin();
	
	if(!$isRights){
		$ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
		echo $ERROR_NO_PRIVS2;
		return;
	}
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$qProxy=new mysql_squid_builder(true);
	$gpid=intval($_POST["save-gpid"]);
	$_POST["GroupName"]=url_decode_special_tool($_POST["GroupName"]);
	
	if(isset($_POST["acltpl"])){$ed[]="acltpl='{$_POST["acltpl"]}'";}
	if(isset($_POST["tplreset"])){$ed[]="tplreset='{$_POST["tplreset"]}'";}
	if(isset($_POST["GroupName"])){$ed[]="GroupName='{$_POST["GroupName"]}'";}
	
	$sql="UPDATE webfilters_sqgroups SET ".@implode(",",$ed)." WHERE ID='$gpid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

    if(isset($_POST["description"])){
        $description=$_POST["description"];
        $description=$q->sqlite_escape_string2($description);
        $q->QUERY_SQL("UPDATE webfilters_sqgroups SET description='$description' WHERE ID='$gpid'");
    }

	
	if(!isset($_POST["items"])){return;}
	
	$ligne=$q->mysqli_fetch_array("SELECT GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");
	$GroupType=$ligne["GroupType"];
	$items=explode("\n",url_decode_special_tool($_POST["items"]));
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$gpid'");
	foreach($items as $pattern){
		if($GroupType=="url_regex_extensions"){if(preg_match("#\.(.+?)$#", $pattern,$re)){$pattern=$re[1];}}
		
		if($GroupType=="dstdomain"){
			if(preg_match("#\/\/#", $pattern)){$URLAR=parse_url($pattern);if(isset($URLAR["host"])){$pattern=$URLAR["host"];}}
			if(preg_match("#^www.(.*)#", $pattern,$re)){$pattern=$re[1];}
			if(preg_match("#(.*?)\/#", $pattern,$re)){$pattern=$re[1];}
		}
		
		if($GroupType=="dst"){$ipClass=new IP();if(!$ipClass->isIPAddressOrRange($pattern)){echo "Not a valid IP {$pattern}\n";continue;}}
		if($GroupType=="src"){$ipClass=new IP();if(!$ipClass->isIPAddressOrRange($pattern)){echo "Not a valid IP {$pattern}\n";continue;}}

        if($GroupType=="method"){
			$pattern=trim(strtoupper( $pattern));
			if(strpos($pattern, " ")>0){
                $INTR=explode(" ",$pattern);
            }else{
                $ff="no exploded\n";
                $INTR[]=$pattern;
            }

            $ERROR_PROTO=false;
            foreach ($INTR as $index=>$proto){
				$proto=trim($proto);
				if($proto==null){continue;}
				if(!isset($qProxy->AVAILABLE_METHOD[$proto])){
                    echo "Unknown Method:[$index]/".count($INTR)." `$proto` $ff\n";$ERROR_PROTO=true;
                    continue;
                }
				$tt[]="('$proto','$gpid','1','')";
			}

			if($ERROR_PROTO){echo "Alowed methods are:\n";
                foreach ($q->AVAILABLE_METHOD as $TaskType=>$none){
                echo "\t$TaskType\n";
                }
            }
		}
		if($GroupType=="arp"){$pattern=trim(strtoupper( $pattern));$pattern=str_replace("-", ":", $pattern);}
		$pattern=str_replace("\\", "\\\\", $pattern);
		$pattern=mysql_escape_string2($pattern);
		$tt[]="('$pattern','$gpid','1','')";
		
		
		
	}
	
	if(count($tt)>0){
		$sqladd="INSERT INTO webfilters_sqitems (pattern,gpid,enabled,other) VALUES ".@implode(",", $tt);
		$q->QUERY_SQL($sqladd);
		if(!$q->ok){echo $q->mysql_error;}
	}
	
}

function link_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tablelink=$_GET["main-table"];
	$aclid=$_GET["aclid"];
	$title=$tpl->javascript_parse_text("{new_object}");
	$tpl->js_dialog2($title,"$page?link-popup=yes&aclid=$aclid&fast-acls={$_GET["fast-acls"]}&main-table=$tablelink");	
}

function link_popup(){
	$aclid=intval($_GET["aclid"]);
	$tablelink=$_GET["main-table"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$qProxy=new mysql_squid_builder(true);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$GroupType=$qProxy->acl_GroupType;
	
	$fastacl=intval($_GET["fast-acls"]);
	if($fastacl==1){$GroupType=$qProxy->acl_GroupType_fast;}
	
	$sql="SELECT ID,objectname  FROM `quota_objects` ORDER BY objectname";
	$results = $q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne3){$GroupType["time_quota:{$ligne3["ID"]}"]="{time_quota}:{$ligne3["objectname"]}"; }
	$sql="SELECT ID,objectname  FROM `sessions_objects` ORDER BY objectname";
	$results = $q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne3){
		$GroupType["time_session:LOGIN:{$ligne3["ID"]}"]="{session_tracking}:LOGIN:{$ligne3["objectname"]}";
		$GroupType["time_session:LOGOUT:{$ligne3["ID"]}"]="{session_tracking}:LOGOUT:{$ligne3["objectname"]}";
		$GroupType["time_session:ACTIVE:{$ligne3["ID"]}"]="{session_tracking}:ACTIVE:{$ligne3["objectname"]}";
	}
	
	$form[]=$tpl->field_hidden("link-object", "yes");
	$form[]=$tpl->field_hidden("main-table", $tablelink);
	$form[]=$tpl->field_hidden("aclid", $aclid);
	$form[]=$tpl->field_array_hash($GroupType, "GroupType", "{create_new_object_based_on}", null);
	
	$sql="SELECT ID,GroupName,GroupType  FROM `webfilters_sqgroups` ORDER BY GroupName";
	$results = $q->QUERY_SQL($sql);
	
	
	
	foreach ($results as $index=>$ligne){
		$GroupType=$ligne["GroupType"];
		$GroupName=$ligne["GroupName"];
		if($fastacl==1){if(!isset($qProxy->acl_GroupType_fast[$GroupType])){continue;}}
		$gpid=$ligne["ID"];
		$MAI[$gpid]="$GroupName (".$qProxy->acl_GroupType[$GroupType].")";
	}
	if(count($MAI)>0){
		$form[]=$tpl->field_array_hash($MAI, "gpid", "{or_use_this_object}", null);
	}

	$jsafter="dialogInstance2.close();LoadAjax('proxy-acls-objects-table','$page?table=yes&aclid=$aclid&fast-acls={$_GET["fast-acls"]}&main-table={$tablelink}');\n";
	$jsafter=$jsafter."
	
	if(document.getElementById('proxies-list') ){ LoadAjax('proxies-list','fw.proxy.parent.php?proxies-list=$aclid');}
	if(document.getElementById('table-loader-proxy-parents') ){ LoadAjax('table-loader-proxy-parents','fw.proxy.parents.php?table=yes'); }
	if(document.getElementById('table-loader-proxy-logscenter') ){ LoadAjax('table-loader-proxy-logscenter','fw.proxy.logs.php?table=yes'); }
	if(document.getElementById('table-loader-proxy-outgoingaddr') ){ LoadAjax('table-loader-proxy-outgoingaddr','fw.proxy.outgoing.php?table=yes'); }
	";
	

	$html[]=$tpl->form_outside("{associate_an_object_to_the_rule}", @implode("\n", $form),null,"{associate}",$jsafter);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function link_save(){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$qProxy=new mysql_squid_builder(true);
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	$tablelink=$_POST["main-table"];
	$aclid=intval($_POST["aclid"]);
	if($aclid==0){echo "No main rule id !";return;}
	if($tablelink==null){echo "No table specified";return;}
	$GroupType=$_POST["GroupType"];
	$gpid=intval($_POST["gpid"]);
	$gpid1=0;
	if($GroupType<>null){
		$GroupName=mysql_escape_string2($tpl->javascript_parse_text("{new_object} ".$qProxy->acl_GroupType[$GroupType]));
		$sql="INSERT INTO `webfilters_sqgroups` (GroupName,GroupType,enabled,tplreset,params) VALUES ('$GroupName','$GroupType',1,0,'')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$gpid1=$q->last_id;
	}
	
	if($gpid1>0){
		$md5=md5($aclid.$gpid1);
		$sql="INSERT OR IGNORE INTO `$tablelink` (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid1',1)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
	}
	if($gpid>0){
		$md5=md5($aclid.$gpid);
		$sql="INSERT OR IGNORE INTO `$tablelink` (zmd5,aclid,gpid,zOrder) VALUES('$md5','$aclid','$gpid',1)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
	}	
	
}

function isRights(){
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
	
}


function table(){
	
	$aclid=intval($_GET["aclid"]);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$qProxy=new mysql_squid_builder(true);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ERROR_NO_PRIVS2=$tpl->javascript_parse_text("{ERROR_NO_PRIVS2}");
	
	$tablelink=$_GET["main-table"];
	$type=$tpl->_ENGINE_parse_body("{type}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$add="Loadjs('$page?link-js=yes&aclid=$aclid&fast-acls={$_GET["fast-acls"]}&main-table=$tablelink')";
	
	$groupname=$tpl->_ENGINE_parse_body("{object}");
	$isRights=isRights();
	
	if(!$isRights){
		$add="alert('$ERROR_NO_PRIVS2');";
		
	}
	
	$neg[0]=$tpl->_ENGINE_parse_body("{is}");
	$neg[1]=$tpl->_ENGINE_parse_body("{is_not}");
	$tableid=md5(time()+microtime(true));
	$html[]=$tpl->_ENGINE_parse_body("
			<table id='$tableid' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
			$html[]="<thead>";
			$html[]="<tr>";
	        $html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true colpan=2 class='text-capitalize' data-type='text'>
            <button type='button' class='btn btn-primary btn-xs' OnClick=\"$add\">
                <i class='fa fa-plus'></i>{new_object}</button>&nbsp;&nbsp;&nbsp;
$groupname</th>");
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$type</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$items</th>";
			$html[]="<th data-sortable=false>&nbsp;</th>";
			$html[]="<th data-sortable=false>&nbsp;</th>";
			$html[]="</tr>";
			$html[]="</thead>";
			$html[]="<tbody>";
			$TRCLASS=null;
			
			$sql="SELECT
			$tablelink.gpid,
			$tablelink.zmd5,
			$tablelink.negation,
			$tablelink.zOrder,
			webfilters_sqgroups.GroupType,
			webfilters_sqgroups.GroupName,
			webfilters_sqgroups.ID
			FROM $tablelink,webfilters_sqgroups
			WHERE $tablelink.gpid=webfilters_sqgroups.ID
			AND $tablelink.aclid=$aclid
			ORDER BY $tablelink.zorder";
			


			$results=$q->QUERY_SQL($sql);
			if(!$q->ok){
				echo "<div class='alert alert-danger'>";
				echo $q->mysql_error_html(true);
				echo "</div>";
				return;}
				
			foreach ($results as $index=>$ligne){
				if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
				$GroupTypeText=$tpl->_ENGINE_parse_body($qProxy->GroupTypeToString($ligne["GroupType"]));
				$delete=null;
				$gpid=$ligne["ID"];
				$zmd5=$ligne["zmd5"];
				$negation=intval($ligne["negation"]);
				if($ligne["GroupType"]=="proxy_auth_ads"){$p=new external_ad_search();$ligne2['tcount']=$p->CountDeUsersByGroupName($ligne['GroupName']);}
				if($ligne["GroupType"]=="proxy_auth_statad"){$p=new external_ad_search();$ligne2['tcount']=$p->CountDeUsersByGroupName($ligne['GroupName']);}
				if($ligne["GroupType"]=="proxy_auth_tagad"){$p=new external_ad_search();$ligne2['tcount']=$p->CountDeUsersByGroupName($ligne['GroupName']);}
				
				
				if($ligne["GroupType"]=="proxy_auth_ldap"){
					$p=new ldap_extern();
					preg_match("#^ExtLDAP:(.+?):(.+)#", $ligne['GroupName'],$re);
					$ligne['GroupName']=$re[1];
					$DN=base64_decode($re[2]);
					$ligne2['tcount']=$p->CountDeUsersByGroupDN($DN);
				}
				if($ligne["GroupType"]<>"all"){
					if($ligne2['tcount']==0){$ligne2=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}'");}
				}else{$ligne2['tcount']="*";}


                $GroupTypeIcon=$qProxy->acl_GroupTypeIcon[$ligne["GroupType"]];
				
				$gpnameenc=urlencode($ligne['GroupName']);
				
				if($isRights){
					$jsNetgation="Loadjs('$page?negation-js=yes&zmd5=$zmd5&main-table=$tablelink&aclid=$aclid')";
					$delete=$tpl->icon_unlink("Loadjs('$page?unlink-object-js=yes&gpid=$gpid&aclid=$aclid&fast-acls={$_GET["fast-acls"]}&main-table=$tablelink&name=$gpnameenc')");
				}else{
					$jsNetgation="alert('$ERROR_NO_PRIVS2');";
					$delete="alert('$ERROR_NO_PRIVS2');";
				}
				
				$js="Loadjs('$page?object-js=yes&gpid=$gpid&aclid=$aclid')";
				
				$up=$tpl->icon_up("Loadjs('$page?object-move-js=yes&dir=up&zmd5=$zmd5&gpid=$gpid&aclid=$aclid&main-table=$tablelink&fast-acls={$_GET["fast-acls"]}')");
				$down=$tpl->icon_down("Loadjs('$page?object-move-js=yes&dir=down&zmd5=$zmd5&gpid=$gpid&aclid=$aclid&main-table=$tablelink&fast-acls={$_GET["fast-acls"]}')");
				$html[]="<tr class='$TRCLASS' id='tr-$zmd5'>";
				
				$html[]="<td><a href=\"javascript:blur();\" OnClick=\"$jsNetgation\" style='font-weight:bold'><span id='neg-$zmd5'>{$neg[$negation]}</span></span></td>";
				$html[]="<td><a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>{$ligne['GroupName']}</span></td>";
				$html[]="<td><i class='$GroupTypeIcon'></i>&nbsp;<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$GroupTypeText</span></td>";
				$html[]="<td class='center' width='1%'><a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'><span id='explain-this-rule-$gpid' data='$page?count-items=$gpid&aclid=$aclid'>{$ligne2['tcount']}</span></td>";
				$html[]="<td class='center' width='1%' nowrap>$up&nbsp;&nbsp;$down</td>";
				$html[]="<td class='center' width='1%' nowrap>$delete</td>";
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
			$html[]="<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
			echo @implode("\n", $html);
}
function items_count(){
    $gpid=intval($_GET["count-items"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$gpid'");
    echo intval($ligne["tcount"]);
}
