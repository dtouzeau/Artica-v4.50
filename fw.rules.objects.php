<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Verrou de la politique Artica Meta "firewall" : regles tenues = objets lies en lecture seule
// (liens, negation, activation et suppression des groupes cites : refus 423 cote squid).
include_once(dirname(__FILE__)."/ressources/class.firewall.meta.inc");
if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["object-save"])){if(firewall_meta_holds("rules")){echo firewall_meta_refuse_post();exit;}save_object();exit;}
// Pentest 2026-11-04 : ruleid et id etaient recopies sans encodage dans la page (XSS).
if(isset($_GET["ruleid"])){$_GET["ruleid"]=intval($_GET["ruleid"]);}
if(isset($_GET["id"])){$_GET["id"]=preg_replace('#[^A-Za-z0-9_-]#','',(string)$_GET["id"]);}
if(isset($_GET["build-table"])){build_table();exit;}
// Regles tenues par Artica Meta : aucune ecriture (le formulaire n'est pas servi, les POST sont refuses).
if(firewall_meta_holds("rules")){
    if(isset($_GET["new-object"]) || isset($_GET["link-object"]) || isset($_GET["delete-confirm"])){echo firewall_meta_banner("rules");exit;}
    if(isset($_POST["object-link"])){echo firewall_meta_refuse_post();exit;}
    if(isset($_POST["delete-unlink"]) || isset($_POST["delete-remove"])){firewall_meta_refuse_text();exit;}
    if(isset($_GET["delete-js"]) || isset($_GET["enabled-js"]) || isset($_GET["negation-js"])){firewall_meta_refuse_js();exit;}
}
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

// Attaching/detaching/toggling a source or destination object changes what a
// firewall rule matches. Trigger the daemon's COALESCED rebuild so a rule and
// its objects converge without the operator hunting for the manual Apply
// button (the daemon debounces bursts into one iptables rebuild ~6s later).
function firewall_objects_notify_daemon(){
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/reconfigure/debounced");
}

function build_page(){
	$ID=intval($_GET["rule-id"]);
	$direction=intval($_GET["direction"]);
	$function=$_GET["function"];
    $NoButtons  = false;
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();

    if($direction==0) {
        // Fetch iptables rule list and find the matching ID's MOD field
        $rules = acls_api_get("/firewall/iptables-rules");
        if(is_array($rules)){
            foreach($rules as $rule){
                if(intval($rule["id"])==$ID){
                    if($rule["mod"]=="IPFEED"){ $NoButtons=true; }
                    break;
                }
            }
        }
    }

        $html[]="<div class=row style='margin-top:20px'>";
	if(firewall_meta_holds("rules")){
	    $NoButtons=true;
	    $html[]=firewall_meta_banner("rules",$tpl);
	}
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
	$function=$_GET["function"];
	$groupid=intval($_GET["delete-js"]);
	$g = acls_api_get("/proxy/acls/sqlite/group/$groupid");
	$GroupName = is_array($g) ? $g["groupName"] : "[$groupid]";
	$GroupType  = is_array($g) ? $g["groupType"]  : "";
	$title="{$GroupName} {{$GroupType}} - {delete}/{unlink}";
	$tpl->js_dialog_confirm($title, "$page?delete-confirm=$groupid&js-after=".jsafter_token($_GET["js-after"])."&ruleid={$_GET["ruleid"]}&function=$function");
}

function delete_confirm(){
	$tpl            = new template_admin();
	$page           = CurrentPageName();
	$groupid        = intval($_GET["delete-confirm"]);

	$t              = time();
    $function       = $_GET["function"];
    $jsfunc         = "Loadjs('fw.rules.php?fill={$_GET["ruleid"]}');";
    if($function<>null){$function=";$function()";}
    $jsAfter        = $tpl->jsToTry(jsafter_sanitize(base64_decode($_GET["js-after"])).$function);
	$group_unlink_delete_explain=$tpl->_ENGINE_parse_body("{group_unlink_delete_explain}");
	$g = acls_api_get("/proxy/acls/sqlite/group/$groupid");
	$GroupName = is_array($g) ? $g["groupName"] : "[$groupid]";
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
	$gpid   = intval($_POST["delete-unlink"]);
	$ruleid = intval($_POST["ruleid"]);
	// Resolve zmd5 for the link row (firewallfilter_sqacllinks, keyed by aclid+gpid).
	// No direction filter here: we want to unlink regardless of direction.
	$links = acls_api_get("/proxy/acls/sqlite/links?table=firewallfilter_sqacllinks&aclid=$ruleid");
	if(!is_array($links)){
		echo "Failed to retrieve link list";
		return;
	}
	$zmd5 = null;
	foreach($links as $lnk){
		if(intval($lnk["gpid"])==$gpid){ $zmd5=$lnk["mkey"]; break; }
	}
	if($zmd5===null){
		// Link not found — already deleted or never existed; treat as success
		return;
	}
	[$ok,$err] = acls_api_send("DELETE","/proxy/acls/sqlite/link?table=firewallfilter_sqacllinks&zmd5=".urlencode($zmd5));
	if(!$ok){ echo $err; return; }
	firewall_objects_notify_daemon();
}

function delete_remove(){
	$gpid=intval($_POST["delete-remove"]);
	// Cascade-delete group + all its items, gpslinks and link-table references
	[$ok,$err] = acls_api_send("DELETE","/proxy/acls/sqlite/group/$gpid");
	if(!$ok){ echo $err; return; }
	firewall_objects_notify_daemon();
}

function enabled_js():bool{
	$gpid   = intval($_GET["enabled-js"]);
    $ID     = intval($_GET["ruleid"]);
	// Fetch current state then toggle
	$g = acls_api_get("/proxy/acls/sqlite/group/$gpid");
	if(!is_array($g)){ echo "Failed to load group"; return false; }
	$enabled = intval($g["enabled"]);
	$enabled = ($enabled==1) ? 0 : 1;
	[$ok,$err] = acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid/enable",["enabled"=>$enabled]);
	if(!$ok){ echo $err; return false; }
	firewall_objects_notify_daemon();
	echo "Loadjs('fw.rules.php?fill=$ID');";
    return true;
}


function link_object(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$function=$_GET["function"];
	$ID=intval($_GET["ID"]);
	$direction=intval($_GET["direction"]);
	$btname="{link_object}";
	$title="{link_object}";
    $MAIN=array();
    $jsfunc=null;
	$groups = acls_api_get("/proxy/acls/sqlite/groups/all");
	if(is_array($groups)){
		foreach($groups as $g){
			$MAIN[$g["id"]]=$tpl->_ENGINE_parse_body("{$g["groupName"]}: {{$g["groupType"]}}");
		}
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
	$gpid  = intval($_POST["gpid"]);
	$aclid = intval($_POST["object-link"]);
	$direction = intval($_POST["direction"]);
	[$ok,$err] = acls_api_send("POST","/proxy/acls/sqlite/link",[
		"table"     => "firewallfilter_sqacllinks",
		"aclid"     => $aclid,
		"gpid"      => $gpid,
		"direction" => $direction,
	]);
	if(!$ok){ echo $err; return false; }
	firewall_objects_notify_daemon();
    return true;
}

function save_object(){
	$aclid     = intval($_POST["object-save"]);
	$direction = intval($_POST["direction"]);
    $tpl       = new template_admin();
	$GroupName = url_decode_special_tool($_POST["GroupName"]);
	$GroupName = $tpl->utf8_decode($GroupName);
	$GroupType = $_POST["GroupType"];

	// Create group and immediately link it to the firewall rule
	[$ok,$err] = acls_api_send("POST","/proxy/acls/sqlite/group",[
		"name"      => $GroupName,
		"type"      => $GroupType,
		"linkAclid" => $aclid,
		"linkTable" => "firewallfilter_sqacllinks",
	]);
	if(!$ok){ echo $err; return; }
	firewall_objects_notify_daemon();
}

function negation_js(){
    header("content-type: application/x-javascript");
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $mkey           = $_GET["negation-js"];
    $id             = $_GET["id"];

    $data = acls_api_send("PUT","/proxy/acls/sqlite/link/negation?table=firewallfilter_sqacllinks&zmd5=".urlencode($mkey));
    [$ok,$resp] = $data;
    if(!$ok){
        $tpl->js_mysql_alert($resp);
        return;
    }
    $aclid   = intval($resp["aclid"] ?? 0);
    $negation= intval($resp["negation"] ?? 0);
    firewall_objects_notify_daemon();
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
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th class='center'>ID</th>";
    $html[]="<th class='center'>{is}</th>";
	$html[]="<th>$objects</th>";
	$html[]="<th class='center'>$type</th>";
	$html[]="<th class='center'>$items</th>";
	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='rulesobjects-rows'>";

	$jsAfter=base64_encode("if(document.getElementById('fw-objects-table') ){LoadAjax('fw-objects-table','$page?build-table=yes&ID=$ID&direction=$direction&function=$function');}");

	if($direction==0) {
        // Fetch iptables rule MOD via the REST API
        $rules = acls_api_get("/firewall/iptables-rules");
        $MOD = "";
        if(is_array($rules)){
            foreach($rules as $rule){
                if(intval($rule["id"])==$ID){ $MOD=$rule["mod"]; break; }
            }
        }
        if($MOD=="IPFEED"){
            $filtering="false";
            $FireholIPSetsEntries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireholIPSetsEntries"));
            $FireholIPSetsEntries=FormatNumber($FireholIPSetsEntries);
            $wljs=$tpl->td_href("{whitelists}",null,"Loadjs('fw.ipfeeds.white.php')");
            $q=new postgres_sql();
            $FireholIPSetsWEntries=FormatNumber($q->COUNT_ROWS_LOW("ipset_whitelists"));

            $TRCLASS=null;
            $html[]="<td $td1>$nothing</span></td>";
            $html[]="<td $td1>{is}</span></td>";
            $html[]="<td $tdn><strong>{CybercrimeIPFeeds}</strong></td>";
            $html[]="<td $td1>{src}</td>";
            $html[]="<td $td1>$FireholIPSetsEntries</td>";
            $html[]="<td $td1>$nothing</td>";
            $html[]="<td $td1>$nothing</td>";
            $html[]="<td $td1>$nothing</td>";
            $html[]="</tr>";
            $TRCLASS=null;
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

    // Fetch linked groups via REST API (firewallfilter_sqacllinks JOIN webfilters_sqgroups).
    // Pass direction so the backend applies AND l.direction=? for correct per-direction rendering.
    $links = acls_api_get("/proxy/acls/sqlite/links?table=firewallfilter_sqacllinks&aclid=$ID&direction=$direction");
    if(!is_array($links)){ $links=[]; }
    $metaRules=firewall_meta_holds("rules");
    $wsec=firewall_meta_sec($metaRules,null);

	foreach ($links as $ligne) {
		$TRCLASS=null;
		$mkey      = $ligne["mkey"];
		$text_is   = "{is}";
		$gpid      = $ligne["gpid"];
		$groupID   = $ligne["groupID"];
		$GroupName = $ligne["groupName"];
		$GroupType = $ligne["groupType"];
		$enabled   = intval($ligne["enabled"]);
		$negation  = intval($ligne["negation"]);

		$html[]="<tr class='$TRCLASS'>";
		if($negation==1){$text_is="{is_not}";}
        $text_is_ico=$metaRules?"<span id='text-is-$gpid'>$text_is</span>":$tpl->td_href("<span id='text-is-$gpid'>$text_is</span>","{click_to_switch}","Loadjs('$page?negation-js=$mkey&id=text-is-$gpid')");

		$MAIN=$tpl->table_object($groupID);
		$GNAME=$MAIN["GROUPNAME"];
		$ITEMS=$MAIN["ITEMS"];
		$TYPE=$MAIN["TYPE"];

		$jsedit="Loadjs('fw.rules.items.php?groupid=$groupID&js-after=$jsAfter&function=$function')";
		$edit=$tpl->icon_parameters($jsedit);
		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$groupID&js-after=$jsAfter&ruleid=$ID&function=$function')",$wsec);
		$chkenabled=$tpl->icon_check($enabled,"Loadjs('$page?enabled-js=$groupID&function=$function&ruleid=$ID')",null,$wsec);

		$html[]="<td $td1>$groupID</td>";
        $html[]="<td $td1>$text_is_ico</td>";
		$html[]="<td $tdn>".$tpl->td_href($GNAME,null,$jsedit)."</td>";
		$html[]="<td $td1>$TYPE</td>";
		$html[]="<td $td1>$ITEMS</td>";
		$html[]="<td $td1>$edit</td>";
		$html[]="<td $td1>$chkenabled</td>";
		$html[]="<td $td1>$delete</td>";
		$html[]="</tr>";

	}

	$html[]="</tbody>";
	$html[]="";
	$html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='rulesobjects-pager'></span></div></div></div>";

	// icon_check() rend un bouton dont la fonction BtSwitch<id>() est produite a part et
	// accumulee: sans ce bloc la case a cocher s'affiche et ne repond a aucun clic.
	// Concatene APRES l'analyse: les blocs {...} du JavaScript seraient pris pour des
	// {jetons} par _ENGINE_parse_body.
	$iconjs="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n</script>";

	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .$iconjs
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"rulesobjects-rows","rulesobjects-pager");

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
