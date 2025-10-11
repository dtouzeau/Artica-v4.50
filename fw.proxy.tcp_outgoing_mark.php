<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
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
	$ID=$_GET["rule-delete-js"];
	$md=$_GET["md"];
	if(!rule_delete($ID)){return;}
	echo "$('#$md').remove();";
	
	
}
function rule_enable(){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	header("content-type: application/x-javascript");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM tcp_outgoing_mark WHERE ID='{$_GET["enable-js"]}'");
	if(intval($ligne["enabled"])==0){
		
		$js="$( \"#acl-{$_GET["enable-js"]}\" ).removeClass( \"text-muted\" );";
		$enabled=1;
		}
		else{
			$js="$( \"#acl-{$_GET["enable-js"]}\" ).addClass( \"text-muted\" );";
			$enabled=0;
		}
	
	$q->QUERY_SQL("UPDATE tcp_outgoing_mark SET enabled='$enabled' WHERE ID='{$_GET["enable-js"]}'");
	if(!$q->ok){echo "alert('".$q->mysql_error."')";return;}
	echo $js;
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["rule-id-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT aclname FROM tcp_outgoing_mark WHERE ID='$ID'");
	$tpl->js_dialog("{rule}: $ID {$ligne["aclname"]}","$page?rule-tabs=$ID");
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
	$ID=$_GET["rule-settings"];
    $explain=null;

    $EnableLinkBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
    if($EnableLinkBalancer==1) {
        $q = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $results = $q->QUERY_SQL("SELECT Interface,mark FROM link_balance");
        foreach ($results as $index=>$ligne) {
            $nic=new system_nic($ligne["Interface"]);
            if($nic->enabled==0){continue;}
            $Interface = $ligne["Interface"];
            $mark = $ligne["mark"];
            $t[] = "{forwarding_packets_to} $Interface ($nic->NICNAME/$nic->netzone)  {use_mark} <strong>$mark</strong>";
        }
        $explain=@implode("<br>",$t);
    }




	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM tcp_outgoing_mark WHERE ID='{$_GET["rule-settings"]}'");
	$form[]=$tpl->field_hidden("rule-save", "$ID");
	$form[]=$tpl->field_text("rulename", "{rule_name}", $ligne["rulename"],true);
	$form[]=$tpl->field_numeric("mark", "{MARK}",$ligne["mark"]);
	$jsafter="LoadAjax('table-acls4-rules','$page?table=yes');";
	$html=$tpl->form_outside($ligne["rulename"], @implode("\n", $form),$explain,"{apply}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$ID=$_POST["rule-save"];
	$rulename=$q->sqlite_escape_string2(utf8_decode($_POST["rulename"]));
	$TempName=md5(time());
	
	$FF[]="`rulename`";
	$FF[]="`mark`";
	$FF[]="`zorder`";
	$FF[]="`enabled`";
	
	$FA[]="'$rulename'";
	$FA[]="'{$_POST["mark"]}'";
	$FA[]="'{$_POST["zorder"]}'";
	$FA[]="'{$_POST["enabled"]}'";
	
	foreach ($FF as $index=>$key){$FB[]="$key={$FA[$index]}";}
	
	$sql="UPDATE tcp_outgoing_mark SET ".@implode(",", $FB)." WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	
	$c=0;
	$sql="SELECT ID FROM tcp_outgoing_mark ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE tcp_outgoing_mark SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}
	
	
}

function new_rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_rule}";
	$tpl->js_dialog($title,"$page?newrule-popup=yes");
}

function HEADERS_ARRAY(){
	$ql=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT * FROM http_headers ORDER BY HeaderName";
	$resultsHeaders=$ql->QUERY_SQL($sql);
	foreach ($resultsHeaders as $index=>$ligneHeaders){
		$HEADERSZ[$ligneHeaders["HeaderName"]]=$ligneHeaders["HeaderName"];
	}
	return $HEADERSZ;
}

function new_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $explain=null;

    $EnableLinkBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLinkBalancer"));
    if($EnableLinkBalancer==1) {
        $q = new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $results = $q->QUERY_SQL("SELECT Interface,mark FROM link_balance");
        foreach ($results as $index=>$ligne) {
            $nic=new system_nic($ligne["Interface"]);
            if($nic->enabled==0){continue;}
            $Interface = $ligne["Interface"];
            $mark = $ligne["mark"];
            $t[] = "{forwarding_packets_to} $Interface ($nic->NICNAME/$nic->netzone)  {use_mark} <strong>$mark</strong>";
        }
        $explain=@implode("<br>",$t);
    }
	

	$form[]=$tpl->field_hidden("newrule", "yes");
	$form[]=$tpl->field_text("rulename", "{rule_name}", null,true);
    $form[]=$tpl->field_numeric("mark", "{MARK}",20);
	$jsafter="LoadAjax('table-acls4-rules','$page?table=yes');BootstrapDialog1.close();BootstrapDialog1.close();";
	
	$html=$tpl->form_outside("{new_rule}", @implode("\n", $form),$explain,"{add}",$jsafter,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}



function rule_move(){
	$tpl=new template_admin();
	$ID=$_GET["acl-rule-move"];
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT zorder FROM tcp_outgoing_mark WHERE `ID`='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($GLOBALS["VERBOSE"]){echo "$ID, order={$ligne["xORDER"]};\n";}
	$xORDER_ORG=intval($ligne["zorder"]);
	$xORDER=$xORDER_ORG;
	
	
	if($_GET["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_GET["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE tcp_outgoing_mark SET zorder=$xORDER WHERE `ID`='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if($_GET["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE tcp_outgoing_mark SET zorder=$xORDER2 WHERE `ID`<>'$ID' AND zorder=$xORDER";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}

	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	}
	if($_GET["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE tcp_outgoing_mark SET zorder=$xORDER2 WHERE `ID`<>'{$_GET["acl-rule-move"]}'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	}

	$c=0;
	$sql="SELECT ID FROM tcp_outgoing_mark ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);

	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE tcp_outgoing_mark SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if($GLOBALS["VERBOSE"]){echo "UPDATE tcp_outgoing_mark SET zorder=$c WHERE `ID`={$ligne["ID"]}\n";}
		$c++;
	}


}


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	$eth_sql=null;
	$token=null;
	$class=null;
	$order=$tpl->_ENGINE_parse_body("{order}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$groups=$tpl->_ENGINE_parse_body("{proxy_objects}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$options=$tpl->_ENGINE_parse_body("{options}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$js="OnClick=\"javascript:LoadAjax('table-firewall-rules','$page?table=yes&eth=');\"";

	
	$class=null;

	
	
	$t=time();
	$add="Loadjs('$page?newrule-js=yes',true);";
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
	$ARRAY["CMD"]="squid2.php?outgoingmark=yes";
	$ARRAY["TITLE"]="{GLOBAL_ACCESS_CENTER}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-acls4-restart')";

    $users=new usersMenus();
    if($users->AsSquidAdministrator) {
        $html[] = $tpl->_ENGINE_parse_body("
	
	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
        <label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>
     </div>
     ");
    }
	
	$html[]="<table id='table-webfilter-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%'>$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rulename</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center'>$enabled</th>";
	
	

	
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-proxyheader-rules','$page?table=yes');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$results=$q->QUERY_SQL("SELECT * FROM tcp_outgoing_mark ORDER BY zorder");
	$TRCLASS=null;
	
	
	foreach($results as $index=>$ligne) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$MUTED=null;
		$md=md5(serialize($ligne));
		$ID=$ligne["ID"];
		$ligne['rulename']=utf8_encode($ligne['rulename']);
		
		$delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID&md=$md')","AsSquidAdministrator");
		$js="Loadjs('$page?rule-id-js=$ID')";
		if($ligne["enabled"]==0){$MUTED=" text-muted";}
		
	
		$explain=EXPLAIN_THIS_RULE($ID);
		$up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');","AsSquidAdministrator");
		$down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');","AsSquidAdministrator");
		
		
		$html[]="<tr class='$TRCLASS{$MUTED}' id='$md'>";
		$html[]="<td class=\"center\" width=1% nowrap>{$ligne["zorder"]}</td>";
		$html[]="<td style='vertical-align:middle' width=1% nowrap>". $tpl->td_href($ligne["rulename"],"{click_to_edit}",$js)."</td>";
		$html[]="<td style='vertical-align:middle'>$explain</td>";
		$html[]="<td class='center' width=1% nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')",null,"AsSquidAdministrator")."</td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$up&nbsp;&nbsp;$down</td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$delete</td>";
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
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-webfilter-rules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); }); 
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function new_rule_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	
	$rulename=sqlite_escape_string2(utf8_decode($_POST["rulename"]));
	$headervalue=base64_encode($_POST["headervalue"]);
	$TempName=md5(time());
	
	$FF[]="`rulename`";
	$FF[]="`mark`";
	$FF[]="`zorder`";
	$FF[]="`enabled`";
	
	$FA[]="'$TempName'";
	$FA[]="'{$_POST["mark"]}'";
	$FA[]=99999;
	$FA[]=1;
	

	
	$sql="INSERT INTO tcp_outgoing_mark (".@implode(",", $FF).") VALUES (".@implode(",", $FA).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	
	$ligne=$q->mysqli_fetch_array("SELECT ID FROM tcp_outgoing_mark WHERE rulename='$TempName'");
	$LASTID=$ligne["ID"];
	
	$q->QUERY_SQL("UPDATE tcp_outgoing_mark SET rulename='$rulename' WHERE ID='$LASTID'");
	
	$c=0;
	$sql="SELECT ID FROM tcp_outgoing_mark ORDER BY zorder";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne) {
		$q->QUERY_SQL("UPDATE tcp_outgoing_mark SET zorder=$c WHERE `ID`={$ligne["ID"]}");
		if(!$q->ok){echo $q->mysql_error_html(true);return;}
		$c++;
	}
}

function rule_delete($ID){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM tcp_outgoing_mark_links WHERE aclid='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	$q->QUERY_SQL("DELETE FROM tcp_outgoing_mark WHERE ID='$ID'");
	if(!$q->ok){echo "alert('".$q->mysql_error."');";return;}
	return true;
	
}
function EXPLAIN_THIS_RULE($ID){
	$acls=new squid_acls_groups();
	$tpl=new templates();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM tcp_outgoing_mark WHERE ID=$ID");
	$mark=$ligne["mark"];
    $objects=$acls->getobjectsNameFromAclrule($ligne['ID'],"black","tcp_outgoing_mark_links");
	if(count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}
    $method[]="{then} {use_mark} &laquo;0x{$mark}&raquo;";
	$page=CurrentPageName();

	if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body(@implode(" ", $method));
    }

	return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>".@implode(" ", $method)."</span>");
}

