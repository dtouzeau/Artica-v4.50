<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["newrule-js"])){new_rule_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_GET["newrule-popup"])){new_rule_popup();exit;}
if(isset($_POST["newrule"])){new_rule_save();exit;}

if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}

if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}
if(isset($_GET["acl-rule-move"])){rule_move();exit;}
if(isset($_GET["explain-this-rule"])){echo EXPLAIN_THIS_RULE($_GET["explain-this-rule"]);exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();

	$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{tcp_outgoing_mark}</h1><p>{tcp_outgoing_mark_explain}</div>

</div>



<div class='row'><div id='progress-acls4-restart'></div>
			<div class='ibox-content'>

			 	<div id='table-acls4-rules'></div>

			</div>
</div>




<script>
LoadAjax('table-acls4-rules','$page?table=yes');

</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_delete_js(){
	header("content-type: application/x-javascript");
	$ID=intval($_GET["rule-delete-js"]);
	$md=$_GET["md"];
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";


}
function rule_enable(){
	header("content-type: application/x-javascript");
	$ID=intval($_GET["enable-js"]);
	$row=acls_api_get("/proxy/tcp-outgoing-mark/$ID");
	if(!is_array($row)){echo "alert('Rule not found');";return;}
	$current=intval($row["enabled"] ?? 0);
	$enabled=($current==0)?1:0;
	if($enabled==0){
		$js="$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
	}else{
		$js="$( \"#acl-$ID\" ).removeClass( \"text-muted\" );";
	}
	list($ok,$data)=acls_api_send("PUT","/proxy/tcp-outgoing-mark/$ID",["enabled"=>$enabled]);
	if(!$ok){echo "alert('".addslashes($data ?? 'error')."')";return;}
	echo $js;
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-id-js"]);
	$row=acls_api_get("/proxy/tcp-outgoing-mark/$ID");
	$aclname=is_array($row) ? ($row["ruleName"] ?? "") : "";
	$tpl->js_dialog("{rule}: $ID $aclname","$page?rule-tabs=$ID");
}



function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-tabs"]);
	$array["{rule}"]="$page?rule-settings=$ID";

	$RefreshTable=base64_encode("LoadAjax('table-acls4-rules','$page?table=yes');");

	$array["{proxy_objects}"]="fw.proxy.acls.objects.php?rule-id=$ID&TableLink=tcp_outgoing_mark_links&RefreshTable=$RefreshTable";
	echo $tpl->tabs_default($array);

}


function rule_settings(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-settings"]);
    $explain=null;

    $EnableLinkBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
    if($EnableLinkBalancer==1) {
        $raw=acls_api_get("/network/link-balance");
        if(is_array($raw)){
            foreach($raw as $item){
                $iface=$item["interface"] ?? "";
                $mark=$item["mark"] ?? "";
                if($iface===""){continue;}
                $nic=new system_nic($iface);
                if($nic->enabled==0){continue;}
                $t[]="{forwarding_packets_to} $iface ($nic->NICNAME/$nic->netzone)  {use_mark} <strong>$mark</strong>";
            }
            $explain=@implode("<br>",$t);
        }
    }

	$row=acls_api_get("/proxy/tcp-outgoing-mark/$ID");
	if(!is_array($row)){echo "{error}";return;}
	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("rulename", "{rule_name}", $row["ruleName"] ?? "",true);
	$form[]=$tpl->field_numeric("mark", "{MARK}",$row["mark"] ?? 0);
	$jsafter="LoadAjax('table-acls4-rules','$page?table=yes');";
	$html=$tpl->form_outside($row["ruleName"] ?? "", @implode("\n", $form),$explain,"{apply}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();

	$ID=intval($_POST["rule-save"]);
	$rulename=$tpl->CLEAN_BAD_XSS_SQL(utf8_decode($_POST["rulename"] ?? ""));

	$body=[
		"ruleName" => $rulename,
		"mark"     => intval($_POST["mark"] ?? 0),
		"enabled"  => intval($_POST["enabled"] ?? 1),
	];

	list($ok,$data)=acls_api_send("PUT","/proxy/tcp-outgoing-mark/$ID",$body);
	if(!$ok){echo ($data ?? "error")."\n";return;}
}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function HEADERS_ARRAY(){
	$raw=acls_api_get("/http-headers");
	$HEADERSZ=[];
	if(is_array($raw)){
		foreach($raw as $item){
			$n=$item["HeaderName"] ?? $item["headerName"] ?? "";
			if($n!==""){$HEADERSZ[$n]=$n;}
		}
	}
	return $HEADERSZ;
}

function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $explain=null;

    $EnableLinkBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
    if($EnableLinkBalancer==1) {
        $raw=acls_api_get("/network/link-balance");
        if(is_array($raw)){
            foreach($raw as $item){
                $iface=$item["interface"] ?? "";
                $mark=$item["mark"] ?? "";
                if($iface===""){continue;}
                $nic=new system_nic($iface);
                if($nic->enabled==0){continue;}
                $t[]="{forwarding_packets_to} $iface ($nic->NICNAME/$nic->netzone)  {use_mark} <strong>$mark</strong>";
            }
            $explain=@implode("<br>",$t);
        }
    }

	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
    $form[]=$tpl->field_numeric("mark", "{MARK}",20);
	$jsafter="LoadAjax('table-acls4-rules','$page?table=yes');BootstrapDialog1.close();BootstrapDialog1.close();";

	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),$explain,"{add}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}



function rule_move(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$ID=intval($_GET["acl-rule-move"]);
	$dir=intval($_GET["acl-rule-dir"]);
	list($ok,$data)=acls_api_send("PUT","/proxy/tcp-outgoing-mark/$ID/move",["dir"=>$dir]);
	if(!$ok){echo "alert('".$tpl->javascript_parse_text($data ?? 'error')."');";return;}
}


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");

	$t=time();
	$add="Loadjs('$page?newrule-js=yes',true);";

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
	$ARRAY["CMD"]="/proxy/outgoing-mark/apply";
	$ARRAY["TITLE"]="{GLOBAL_ACCESS_CENTER}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-acls4-restart')";

    $users=new usersMenus();
    if($users->AsSquidAdministrator) {
        $html[] = $tpl->_ENGINE_parse_body("

	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
        <label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>
     </div>
     ");
    }

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>$order</th>";
	$html[]="<th>$rulename</th>";
	$html[]="<th>{$description}</th>";
	$html[]="<th class='center'>$enabled</th>";



	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="<th></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='tcpoutgoingm-rows'>";

	$jsAfter="LoadAjax('table-proxyheader-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$results=acls_api_get("/proxy/tcp-outgoing-mark");
	if(!is_array($results)){$results=[];}

	$TRCLASS=null;


	foreach($results as $index=>$ligne) {
		$TRCLASS=null;
		$MUTED=null;
		$md=md5(serialize($ligne));
		$ID=intval($ligne["id"]);
		$rulename_val=utf8_encode($ligne["ruleName"] ?? "");

		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID&md=$md')","AsSquidAdministrator");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if(intval($ligne["enabled"] ?? 1)==0){$MUTED=" text-muted";}


		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');","AsSquidAdministrator");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');","AsSquidAdministrator");


		$html[]="<tr class='$TRCLASS{$MUTED}' id='$md'>";
		$html[]="<td class=\"center\" style='width:1%' nowrap>".intval($ligne["xOrder"] ?? 0)."</td>";
		$html[]="<td style='vertical-align:middle' style='width:1%' nowrap>". $tpl->td_href($rulename_val,"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
		$html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')",null,"AsSquidAdministrator")."</td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$up&nbsp;&nbsp;$down</td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$delete</td>";
		$html[]="</tr>";

	}



	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='tcpoutgoingm-pager'></span></div>";
	$html[]="</div></div>";
	$html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"tcpoutgoingm-rows","tcpoutgoingm-pager");
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	$rulename=$tpl->CLEAN_BAD_XSS_SQL(utf8_decode($_POST["rulename"] ?? ""));

	list($ok,$data)=acls_api_send("POST","/proxy/tcp-outgoing-mark",[
		"ruleName" => $rulename,
		"mark"     => intval($_POST["mark"] ?? 0),
	]);
	if(!$ok){echo $data ?? "error";return;}
}

function rule_delete($ID){
	list($ok,$data)=acls_api_send("DELETE","/proxy/tcp-outgoing-mark/$ID");
	if(!$ok){echo "alert('".addslashes($data ?? 'error')."');";return false;}
	return true;

}
function EXPLAIN_THIS_RULE($ID){
	$acls=new squid_acls_groups();
	$tpl=new templates();
	$ligne=acls_api_get("/proxy/tcp-outgoing-mark/$ID");
	if(!is_array($ligne)){return "";}
	$mark=$ligne["mark"] ?? "";
    $objects=$acls->getobjectsNameFromAclrule(intval($ID),"black","tcp_outgoing_mark_links");
	if(count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}
    $method[]="{then} {use_mark} &laquo;0x{$mark}&raquo;";
	$page=CurrentPageName();

	if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body(@implode(" ", $method));
    }

	return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>".@implode(" ", $method)."</span>");
}
