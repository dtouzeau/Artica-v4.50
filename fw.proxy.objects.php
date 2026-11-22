<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// Politique Meta : fiche d'un objet tenu (metaid) en lecture seule.
include_once(dirname(__FILE__)."/ressources/class.proxypac.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : aclid/fast-acls/main-table/zmd5/dir etaient recopies sans encodage dans la page (XSS).
foreach(array("aclid","gpid","fast-acls") as $k){
    if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}
}
foreach(array("main-table","zmd5","dir") as $k){
    if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET[$k]);}
}
foreach(array("main-table","table-name") as $k){
    if(isset($_POST[$k])){$_POST[$k]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_POST[$k]);}
}
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

	// Pentest 2026-11-04 : name etait recopie sans encodage dans la page (XSS).
	$delete_personal_cat_ask=$tpl->javascript_parse_text("{unlink_group} ".req_js($_GET["name"])." ?");
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
	$tpl=new template_admin();
	$dirStr=$_GET["dir"];
	$gpid=intval($_GET["gpid"]);
	$aclid=intval($_GET["aclid"]);
	$tablelink=$_GET["main-table"];
	$fastacl=$_GET["fast-acls"];
	$zmd5=$_GET["zmd5"];
	// dir: "up" → 1 (decrement order), "down" → 0 (increment order) per MoveLink convention
	$dir = ($dirStr=="up") ? 1 : 0;
	[$ok,$resp] = acls_api_send("PUT",
		"/proxy/acls/sqlite/link/move?table=".urlencode($tablelink)."&zmd5=".urlencode($zmd5),
		["dir"=>$dir]);
	if(!$ok){
		$msg=$tpl->javascript_parse_text($resp);
		echo "alert('$msg');";
		return;
	}

	echo "
if(document.getElementById('table-loader-proxy-parents') ){ LoadAjax('table-loader-proxy-parents','fw.proxy.parents.php?table=yes'); }
if(document.getElementById('table-loader-proxy-logscenter') ){ LoadAjax('table-loader-proxy-logscenter','fw.proxy.logs.php?table=yes'); }
if(document.getElementById('table-loader-proxy-outgoingaddr') ){ LoadAjax('table-loader-proxy-outgoingaddr','fw.proxy.outgoing.php?table=yes'); }
";
}


function unlink_object(){
	$tablelink=$_POST["table-name"];
	$aclid=intval($_POST["aclid"]);
	$gpid=intval($_POST["gpid"]);
	// Resolve zmd5 then DELETE
	$links = acls_api_get("/proxy/acls/sqlite/links?table=".urlencode($tablelink)."&aclid=$aclid");
	if(!is_array($links)){
		echo "Failed to retrieve link list";
		return;
	}
	$zmd5 = null;
	foreach($links as $lnk){
		if(intval($lnk["gpid"])==$gpid){ $zmd5=$lnk["mkey"]; break; }
	}
	if($zmd5===null){ return; } // Already unlinked
	[$ok,$err] = acls_api_send("DELETE","/proxy/acls/sqlite/link?table=".urlencode($tablelink)."&zmd5=".urlencode($zmd5));
	if(!$ok){ echo $err; }
}

function  negation_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$tablelink=$_GET["main-table"];
	$zmd5=$_GET["zmd5"];
	$aclid=intval($_GET["aclid"]);
	$id="neg-$zmd5";
	$neg[0]=$tpl->javascript_parse_text("{is}");
	$neg[1]=$tpl->javascript_parse_text("{is_not}");

	[$ok,$resp] = acls_api_send("PUT",
		"/proxy/acls/sqlite/link/negation?table=".urlencode($tablelink)."&zmd5=".urlencode($zmd5));
	if(!$ok){
		$msg=$tpl->javascript_parse_text($resp);
		echo "alert('$msg');";
		return;
	}
	$negation=intval($resp["negation"] ?? 0);

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
	$gpid=intval($_GET["gpid"]);
    $qProxy=new mysql_squid_builder();
	$g = acls_api_get("/proxy/acls/sqlite/group/$gpid");
	if(!is_array($g)){ return; }
	$title=$tpl->javascript_parse_text($g["groupName"]);
    if(!$qProxy->acl_ARRAY_NO_ITEM[$g["groupType"]]){
        $tpl->js_dialog2($title,"$page?object-tabs=yes&gpid=$gpid&aclid={$_GET["aclid"]}");
        return;
    }
    $tpl->js_dialog2($title,"$page?object-popup=yes&gpid=$gpid&aclid={$_GET["aclid"]}");

}

function object_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["gpid"]);
    $g = acls_api_get("/proxy/acls/sqlite/group/$gpid");
    if(!is_array($g)){ return; }
    $title=$tpl->javascript_parse_text($g["groupName"]);

    $array[$title]="$page?object-popup=yes&gpid=$gpid&aclid={$_GET["aclid"]}";
    $array["{items}"]="fw.rules.items.php?item-start=$gpid&js-after=$jsafter";
    echo $tpl->tabs_default($array);


}

function object_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$qProxy=new mysql_squid_builder(true);
	$gpid=intval($_GET["gpid"]);
	$aclid=intval($_GET["aclid"]);
	$g = acls_api_get("/proxy/acls/sqlite/group/$gpid");
	if(!is_array($g)){
		echo $tpl->_ENGINE_parse_body($tpl->div_error("{error_loading_group}"));
		return;
	}
	$buttonname="{apply}";
	$DISABLED=false;
	$acltpl="{default}";

	// Fetch squidtpls via REST API
	$MAIN=array();
	$tplList = acls_api_get("/proxy/squidtpls");
	if(is_array($tplList)){
		foreach($tplList as $tplRow){
			$MAIN[$tplRow["zmd5"]]=$tplRow["templateName"];
		}
	}
	$GroupType=$qProxy->acl_GroupType;

	$title=$GroupType[$g["groupType"]];

	$FTEXT=false;


	$form[]=$tpl->field_hidden("save-gpid", $gpid);
	$metaBanner=proxyobject_meta_banner($tpl,$gpid);
	if($metaBanner!==""){
		$tpl->FORM_LOCKED=true;
		$tpl->this_form_locked_explain="<i class='fa-solid fa-lock'></i>&nbsp;{proxyobject_meta_locked}";
	}

	if($g["groupType"]=="proxy_auth_tagad"){
		$form[]=$tpl->field_browse_adgroups("GroupName", "{object_name}", utf8_encode($g["groupName"]));
		$FTEXT=true;
	}

	if(!$FTEXT){
		$form[]=$tpl->field_text("GroupName", "{object_name}", utf8_encode($g["groupName"]),false,null,$DISABLED);
	}

	$form[]=$tpl->field_array_hash($MAIN, "acltpl", "{template}", $g["acltpl"]);
	$form[]=$tpl->field_checkbox("tplreset","{reset_connection}",$g["tplreset"]);

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

	$html[]=$metaBanner;
	$html[]=$tpl->form_outside($title, @implode("\n", $form),$qProxy->acl_GroupType_explain[$g["groupType"]],"{apply}",$jsafter);
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
	$gpid=intval($_POST["save-gpid"]);
	$meta=proxyobject_meta_refuse_post($gpid);
	if($meta!==""){echo $meta;return;}
	$_POST["GroupName"]=url_decode_special_tool($_POST["GroupName"]);

	$body=[];
	if(isset($_POST["acltpl"])){   $body["acltpl"]   = $_POST["acltpl"]; }
	if(isset($_POST["tplreset"])){ $body["tplreset"] = intval($_POST["tplreset"]); }
	if(isset($_POST["GroupName"])){ $body["name"]    = $_POST["GroupName"]; }
	if(isset($_POST["description"])){$body["description"] = $_POST["description"];}

	if(!empty($body)){
		[$ok,$err] = acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",$body);
		if(!$ok){ echo $err; return; }
	}

	if(!isset($_POST["items"])){ return; }

	$g = acls_api_get("/proxy/acls/sqlite/group/$gpid");
	if(!is_array($g)){ echo "Failed to load group"; return; }
	$GroupType=$g["groupType"];

	$qProxy=new mysql_squid_builder(true);
	$items=explode("\n",url_decode_special_tool($_POST["items"]));

	// Delete existing items then insert new ones
	[$ok,$err] = acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$gpid");
	if(!$ok){ echo $err; return; }

	$tt=[];
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
				$tt[]=$proto;
			}

			if($ERROR_PROTO){echo "Allowed methods are:\n";
                foreach ($qProxy->AVAILABLE_METHOD as $TaskType=>$none){
                echo "\t$TaskType\n";
                }
            }
		}
		if($GroupType=="arp"){$pattern=trim(strtoupper( $pattern));$pattern=str_replace("-", ":", $pattern);}
		$pattern=trim($pattern);
		if($pattern==""){ continue; }
		$tt[]=$pattern;
	}

	if(count($tt)>0){
		$itemsPayload = array_map(function($p){return ["pattern"=>$p,"enabled"=>1,"other"=>""];},$tt);
		[$ok,$err] = acls_api_send("POST","/proxy/acls/sqlite/items/bulk",["gpid"=>$gpid,"items"=>$itemsPayload]);
		if(!$ok){ echo $err; }
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
	$qProxy=new mysql_squid_builder(true);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$GroupType=$qProxy->acl_GroupType;

	$fastacl=intval($_GET["fast-acls"]);
	if($fastacl==1){$GroupType=$qProxy->acl_GroupType_fast;}
	$quotaObjects=acls_api_get("/proxy/acls/sqlite/quota-objects");
	if(is_array($quotaObjects)){
		foreach ($quotaObjects as $ligne3){
			if(!is_array($ligne3)){continue;}
			$oid=intval($ligne3["id"] ?? 0);
			$oname=(string)($ligne3["objectName"] ?? "");
			$GroupType["time_quota:$oid"]="{time_quota}:$oname";
		}
	}
	$sessionObjects=acls_api_get("/proxy/acls/sqlite/session-objects");
	if(is_array($sessionObjects)){
		foreach ($sessionObjects as $ligne3){
			if(!is_array($ligne3)){continue;}
			$oid=intval($ligne3["id"] ?? 0);
			$oname=(string)($ligne3["objectName"] ?? "");
			$GroupType["time_session:LOGIN:$oid"]="{session_tracking}:LOGIN:$oname";
			$GroupType["time_session:LOGOUT:$oid"]="{session_tracking}:LOGOUT:$oname";
			$GroupType["time_session:ACTIVE:$oid"]="{session_tracking}:ACTIVE:$oname";
		}
	}

	$form[]=$tpl->field_hidden("link-object", "yes");
	$form[]=$tpl->field_hidden("main-table", $tablelink);
	$form[]=$tpl->field_hidden("aclid", $aclid);
	$form[]=$tpl->field_array_hash($GroupType, "GroupType", "{create_new_object_based_on}", null);

	// Fetch existing groups via REST API
	$MAI=[];
	$allGroups = acls_api_get("/proxy/acls/sqlite/groups/all");
	if(is_array($allGroups)){
		foreach($allGroups as $g){
			$gt=$g["groupType"];
			$gn=$g["groupName"];
			if($fastacl==1){if(!isset($qProxy->acl_GroupType_fast[$gt])){continue;}}
			$MAI[$g["id"]]="$gn (".$qProxy->acl_GroupType[$gt].")";
		}
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
		$GroupName=$tpl->javascript_parse_text("{new_object} ".$qProxy->acl_GroupType[$GroupType]);
		// Create new group via REST API
		[$ok,$resp] = acls_api_send("POST","/proxy/acls/sqlite/group",[
			"name"      => $GroupName,
			"type"      => $GroupType,
			"linkAclid" => $aclid,
			"linkTable" => $tablelink,
		]);
		if(!$ok){ echo is_array($resp) ? json_encode($resp) : $resp; return; }
		$gpid1=intval($resp["id"] ?? 0);
		// Link already created by CreateGroup (linkAclid+linkTable path)
	}

	// Also link an existing group if selected
	if($gpid>0){
		[$ok,$err] = acls_api_send("POST","/proxy/acls/sqlite/link",[
			"table" => $tablelink,
			"aclid" => $aclid,
			"gpid"  => $gpid,
		]);
		if(!$ok){ echo $err; }
	}

}

function isRights(){
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}

}


function table(){

	$aclid=intval($_GET["aclid"]);
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
			<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>");
			$html[]="<thead>";
			$html[]="<tr>";
	        $html[]=$tpl->_ENGINE_parse_body("<th>
            <button type='button' class='btn btn-primary btn-xs' OnClick=\"$add\">
                <i class='fa fa-plus'></i>{new_object}</button>&nbsp;&nbsp;&nbsp;
$groupname</th>");
			$html[]="<th>$type</th>";
			$html[]="<th>$items</th>";
			$html[]="<th>&nbsp;</th>";
			$html[]="<th>&nbsp;</th>";
			$html[]="</tr>";
			$html[]="</thead>";
			$html[]="<tbody id='objects-rows'>";
			$TRCLASS=null;

			// Fetch linked groups via REST API
			$links = acls_api_get("/proxy/acls/sqlite/links?table=".urlencode($tablelink)."&aclid=$aclid");
			if(!is_array($links)){
				echo "<div class='alert alert-danger'>";
				echo "Failed to load link list";
				echo "</div>";
				return;
			}

			foreach ($links as $ligne){
				$TRCLASS=null;
				$GroupTypeText=$tpl->_ENGINE_parse_body($qProxy->GroupTypeToString($ligne["groupType"]));
				$gpid=$ligne["groupID"];
				$zmd5=$ligne["mkey"];
				$negation=intval($ligne["negation"]);
				$GroupName=$ligne["groupName"];
				$GroupType=$ligne["groupType"];
				$ligne2=[];
				if($GroupType=="proxy_auth_ads"){$p=new external_ad_search();$ligne2['tcount']=$p->CountDeUsersByGroupName($GroupName);}
				if($GroupType=="proxy_auth_statad"){$p=new external_ad_search();$ligne2['tcount']=$p->CountDeUsersByGroupName($GroupName);}
				if($GroupType=="proxy_auth_tagad"){$p=new external_ad_search();$ligne2['tcount']=$p->CountDeUsersByGroupName($GroupName);}


				if($GroupType=="proxy_auth_ldap"){
					$p=new ldap_extern();
					preg_match("#^ExtLDAP:(.+?):(.+)#", $GroupName,$re);
					$GroupName=$re[1];
					$DN=base64_decode($re[2]);
					$ligne2['tcount']=$p->CountDeUsersByGroupDN($DN);
				}
				if($GroupType<>"all"){
					if(empty($ligne2['tcount'])){
						// Fetch item count via REST API (no dedicated count endpoint; fetch all and count)
						$allItems = acls_api_get("/proxy/acls/sqlite/items?gpid=$gpid");
						$ligne2['tcount'] = is_array($allItems) ? count($allItems) : 0;
					}
				}else{$ligne2['tcount']="*";}


                $GroupTypeIcon=$qProxy->acl_GroupTypeIcon[$GroupType];

				$gpnameenc=urlencode($GroupName);

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
				$html[]="<td><a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$GroupName</span></td>";
				$html[]="<td><i class='$GroupTypeIcon'></i>&nbsp;<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'>$GroupTypeText</span></td>";
				$html[]="<td class='center' width='1%'><a href=\"javascript:blur();\" OnClick=\"$js\" style='font-weight:bold'><span id='explain-this-rule-$gpid' data='$page?count-items=$gpid&aclid=$aclid'>{$ligne2['tcount']}</span></td>";
				$html[]="<td class='center' width='1%' nowrap>$up&nbsp;&nbsp;$down</td>";
				$html[]="<td class='center' width='1%' nowrap>$delete</td>";
				$html[]="</tr>";

			}
			$html[]="</tbody>";
			$html[]="";
			$html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='objects-pager'></span></div></div></div>";
			$html[]="<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
			echo awc_table_style()
	    .@implode("\n", $html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"objects-rows","objects-pager");
}

function items_count(){
    $gpid=intval($_GET["count-items"]);
    $allItems = acls_api_get("/proxy/acls/sqlite/items?gpid=$gpid");
    echo is_array($allItems) ? count($allItems) : 0;
}

function isReader($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }
    if($users->AsProxyMonitor){
        return true;
    }
    return false;
}
function isWritter($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }

    return false;
}
