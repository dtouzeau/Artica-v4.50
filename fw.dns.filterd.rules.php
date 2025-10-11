<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["single-page"])){page_single();}
if(isset($_GET["SearchIpsources"])){sources_search();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["delete"])){source_item_delete();exit;}
if(isset($_POST["description"])){source_item_save();exit;}
if(isset($_POST["UfdbReloadBySchedule"])){save();exit;}
if(isset($_GET["source-add-js"])){source_add_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete-rule"])){delete_rule();exit;}
if(isset($_POST["rule-order"])){move_rule();exit;}
if(isset($_GET["sources-js"])){sources_js();exit;}
if(isset($_GET["sources-popup"])){sources_popup();exit;}
if(isset($_POST["sources"])){sources_save();exit;}
if(isset($_GET["sources-add-popup"])){source_add_popup();exit;}
if(isset($_GET["sources-item-js"])){source_item_js();exit;}
if(isset($_GET["sources-item-popup"])){source_item_popup();exit;}
if(isset($_GET["sources-item-delete-js"])){source_item_delete_js();exit;}
page();



function delete_js(){
	$tpl=new template_admin();
	$js="$('#{$_GET["md"]}').remove();";
	$tpl->js_confirm_delete($_GET["rule"], "delete-rule", $_GET["delete-js"],$js);
	
}
function delete_rule(){
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ID=intval($_POST["delete-rule"]);
	$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilter_rules WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilter_ipsources WHERE ruleid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->QUERY_SQL("DELETE FROM ufdb_page_rules WHERE webruleid='$ID'");

}

function sources_js(){
	$ID=intval($_GET["sources-js"]);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];
	$tpl->js_dialog($rulename. "{sources}", "$page?sources-popup=$ID");
}
function source_add_js(){
    $ID=intval($_GET["source-add-js"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
    $ligne=$q->mysqli_fetch_array($sql);
    $rulename=$ligne["groupname"];
    $function=$_GET["function"];
    $tpl->js_dialog2($rulename. " {sources} {new_item}", "$page?sources-add-popup=$ID&function=$function",650);
}

function source_item_delete_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $ipaddr=$_GET["sources-item-delete-js"];
    $ruleid=$_GET["ruleid"];
    $md=$_GET["md"];

    $value=base64_encode(serialize($_GET));

    $tpl->js_confirm_delete($ipaddr,"delete",$value,"$('#$md').remove();LoadAjax('table-loader-dnsfilterd-rules','$page?table=yes');");

}
function source_item_delete(){

    $GET=unserialize(base64_decode($_POST["delete"]));
    $ipaddr=$GET["sources-item-delete-js"];
    $ruleid=$GET["ruleid"];
    if($ipaddr==null){
        echo "IP Address is null!";
        return;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $q->QUERY_SQL("DELETE FROM webfilter_ipsources WHERE ipaddr='$ipaddr' AND ruleid='$ruleid'");
    if(!$q->ok){echo "$q->mysql_error";}
}

function source_item_js(){

    $tpl=new template_admin();
    $page=CurrentPageName();

    $ipaddr=$_GET["sources-item-js"];
    $ruleid=$_GET["ruleid"];
    $function=$_GET["function"];
    $ipaddrenc=urlencode($ipaddr);

    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $sql="SELECT groupname FROM webfilter_rules WHERE ID=$ruleid";
    $ligne=$q->mysqli_fetch_array($sql);
    $rulename=$ligne["groupname"];


    $tpl->js_dialog2($rulename. " {sources} $ipaddr", "$page?sources-item-popup=$ipaddrenc&function=$function&ruleid=$ruleid",650);

}

function sources_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ruleid=intval($_GET["sources-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");

	if(!$q->FIELD_EXISTS("webfilter_ipsources","description")){
	    $q->QUERY_SQL("ALTER TABLE webfilter_ipsources ADD description TEXT");
    }
	
	$sql="SELECT groupname FROM webfilter_rules WHERE ID=$ruleid";
	$ligne=$q->mysqli_fetch_array($sql);
	$rulename=$ligne["groupname"];


	$html[]="<H2>$rulename</H2>";
	$html[]=$tpl->search_block($page,null,null,"webfilter-ipsources-$ruleid","&SearchIpsources=$ruleid");
	$html[]="<div id='webfilter-ipsources-$ruleid'></div>";
	echo $tpl->_ENGINE_parse_body($html);
	return;
}
function source_item_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["ruleid"]);
    $function=$_GET["function"];
    $ipaddr=$_GET["sources-item-popup"];
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $ligne=$q->mysqli_fetch_array("SELECT description FROM webfilter_ipsources WHERE ruleid=$ruleid AND ipaddr='$ipaddr'");

    $form[]=$tpl->field_hidden("ruleid", $ruleid);
    $form[]=$tpl->field_hidden("ipaddr", $ipaddr);
    $form[]=$tpl->field_text("description", "{description}", $ligne["description"],true);
    echo $tpl->form_outside("$ipaddr", $form,null,"{apply}",
        "dialogInstance2.close();LoadAjax('table-loader-dnsfilterd-rules','$page?table=yes');$function();","AsDnsAdministrators",true);

}

function source_item_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=$_POST["ruleid"];
    $ipaddr=$_POST["ipaddr"];
    $description=$_POST["description"];


    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $description=$q->sqlite_escape_string2($description);
    $q->QUERY_SQL("UPDATE webfilter_ipsources SET description='$description' WHERE ipaddr='$ipaddr' AND ruleid='$ruleid'");
    if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "jserror:$q->mysql_error";}

}


function source_add_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=$_GET["sources-add-popup"];
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");

    $sql="SELECT groupname FROM webfilter_rules WHERE ID=$ruleid";
    $ligne=$q->mysqli_fetch_array($sql);
    $rulename=$ligne["groupname"];
    $function=$_GET["function"];

    $form[]=$tpl->field_hidden("ruleid", $ruleid);
    $form[]=$tpl->field_textareacode("sources", null, null);
    echo $tpl->form_outside("$rulename {sources}", $form,"{DNSFILTERD_SOURCES_EXPLAIN}","{add}",
        "dialogInstance2.close();LoadAjax('table-loader-dnsfilterd-rules','$page?table=yes');$function();","AsDnsAdministrators",true);
}

function sources_search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ruleid=intval($_GET["SearchIpsources"]);
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $function=$_GET["function"];
    $t=time();

    $html[]="
		<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?source-add-js=$ruleid&function=$function')\"><i class='fa fa-plus'></i> {new_item} </label> </label>
		</div>
		";




    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=false width=1%>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    $search=trim($_GET["search"]);
    if($search==null) {
        $results = $q->QUERY_SQL("SELECT * FROM webfilter_ipsources WHERE ruleid='$ruleid' ORDER BY ipaddr");
    }else{
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $results = $q->QUERY_SQL("SELECT * FROM webfilter_ipsources WHERE (ruleid='$ruleid') AND 
        ( (ipaddr LIKE '$search') OR (description LIKE '$search') ) ORDER BY ipaddr");
    }


	foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $description=$ligne["description"];
        $ipaddr=$ligne["ipaddr"];
        $ipaddrenc=urlencode($ipaddr);
        $md=md5(serialize($ligne));
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%' nowrap><strong>".$tpl->td_href($ipaddr,null,"Loadjs('$page?sources-item-js=$ipaddrenc&function=$function&ruleid=$ruleid')")."</strong></td>";
        $html[]="<td>$description</td>";
        $html[]="<td>". $tpl->icon_delete("Loadjs('$page?sources-item-delete-js=$ipaddrenc&ruleid=$ruleid&md=$md')","AsDnsAdministrator")."</td>";
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
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";

	echo $tpl->_ENGINE_parse_body($html);
}





function sources_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$td=explode("\n",$_POST["sources"]);
	$ruleid=$_POST["ruleid"];
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");

	$ipClass=new IP();
	
	foreach ($td as $line){
        $description="Saved ".date("Y-m-d H:i:s");

        if(strpos($line,"#")>0){
            $zline=explode("#",$line);
            $line=$zline[0];
            unset($zline[0]);
            $description=@implode(" ",$zline);
        }

	    if(preg_match("#^(.+?)\s+(.+)#",$line,$re)){
            $line=$re[1];
	        $description=$re[2];
        }

		if(!$ipClass->isIPAddressOrRange($line)){continue;}
		$q->QUERY_SQL("INSERT INTO webfilter_ipsources (ruleid,ipaddr,description) VALUES ($ruleid,'$line','$description')");
		if(!$q->ok){echo $q->mysql_error;return;}
		
	}
	
}

function page_single(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $APP_DNSFILTERD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSFILTERD_VERSION");


    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_DNSFILTERD} $APP_DNSFILTERD_VERSION </h1>
			<p>{filtering_rules}</p>
		</div>
	</div>
	<div class='row'>
	    <div id='dnsfilterd-progress' style=''></div>		
		<div id='progress-dnsfilter-restart'></div>
		<div class='ibox-content' style='min-height:600px'>
			<div id='table-dnsfilterd'>
			
			
	<div id='table-loader-dnsfilterd-rules'>
            </div>
		</div>
	</div>



	<script>
	LoadAjax('table-loader-dnsfilterd-rules','$page?table=yes');
	$.address.state('/');
	$.address.value('/dnsfilter-rules');
	$.address.title('Artica: DNS Filter Parameters');
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: DNS Filter Rules",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);


}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div id='dnsfilterd-progress' style='margin-top:5px'></div>		
	<div id='table-loader-dnsfilterd-rules'></div>
	<script>
	LoadAjax('table-loader-dnsfilterd-rules','$page?table=yes');

	</script>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	//$TimeSpace=$webfilter->TimeToText(unserialize(base64_decode($ligne["TimeSpace"])));
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$groups=$tpl->_ENGINE_parse_body("{sources}");
	$blacklists=$tpl->_ENGINE_parse_body("{blacklists}");
	$whitelists=$tpl->_ENGINE_parse_body("{whitelists}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$order=$tpl->javascript_parse_text("{order}");
	$TRCLASS=null;
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress.log";
	$ARRAY["CMD"]="dnsfilterd.php?compile-rules=yes";
	$ARRAY["TITLE"]="{apply_webiltering_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=dnsfilterd-progress')";
	
	$add="Loadjs('fw.dns.filterd.rules.edit.php?ID=-1')";
	
	$html[]=$tpl->_ENGINE_parse_body("
		<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>
			<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_dnsfiltering_rules} </label>
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.ufdb.databases.update.php')\"><i class='".ico_download."'></i> {update_webfiltering_artica_databases} </label>
		</div>
		<div class=\"btn-group\" data-toggle=\"buttons\"></div>");



	
	$html[]="<table id='table-filtragewebrules-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >$order</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rule_text</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$groups</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>". $tpl->_ENGINE_parse_body($whitelists)."</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>". $tpl->_ENGINE_parse_body($blacklists)."</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1%>". $tpl->_ENGINE_parse_body("{duplicate}")."</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1%>". $tpl->_ENGINE_parse_body("{move}")."</th>";
	$html[]="<th data-sortable=false width=1%>$delete</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$sock=new dnsfiltersocks();
	$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	$DefaultPosition=$ligne["defaultPosition"];
	if(!is_numeric($DefaultPosition)){$DefaultPosition=0;}
	
	if($DefaultPosition==0){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]=DefaultRule($TRCLASS);
	}
	
	$no_category_has_been_added=$tpl->_ENGINE_parse_body("{no_category_has_been_added}");
	$endofrule_TEXTS["any"]="<i class='text-info'>".$tpl->_ENGINE_parse_body("{ufdb_explain_any}")."</i>";
	$endofrule_TEXTS["none"]="<i class='text-danger'>".$tpl->_ENGINE_parse_body("{ufdb_explain_none}")."</i>";
	
	$sql="SELECT * FROM webfilter_rules ORDER BY zOrder";
	$results=$q->QUERY_SQL($sql);
	$webfilter=new webfilter_rules("dns.db");
	$t=time();
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ID=$ligne["ID"];
		$md5=md5($ligne["ID"]);
		$endofrule_text=null;
		$EnableGoogleSafeSearch_text=null;
		$ligne["groupname"]=utf8_encode($ligne["groupname"]);
		$endofrule=$ligne["endofrule"];
		if($endofrule==null){$endofrule="any";}
		$md=md5(serialize($ligne));
	
		$MAIN_EXPLAIN_TR=array();
	
	
	
		$js="DansGuardianEditRule('{$ligne["ID"]}','{$ligne["groupname"]}');";
		if($GLOBALS["VERBOSE"]){echo "<HR>webfilter->rule_time_list_from_ruleid({$ligne["ID"]})<HR><br>\n";}
	
		$CountDeBlack=intval($webfilter->COUNTDEGBLKS($ligne["ID"]));
		$CountDewhite=intval($webfilter->COUNTDEGBWLS($ligne["ID"]));
	
		$CountDeAll=intval($CountDeBlack+$CountDewhite);
		if($CountDeAll==0){
		
			$MAIN_EXPLAIN_TR[]="<i class='text-danger'>$no_category_has_been_added</i>";
		}
	
		$color="black";
		if($ligne["enabled"]==0){$color="#8a8a8a";}
	
	
		if($ligne["groupmode"]==0){
			$MAIN_EXPLAIN_TR[]="<i class='text-danger'>{all_websites_are_banned}</span>";
		}
		if($ligne["groupmode"]==2){
			$MAIN_EXPLAIN_TR[]="<i class='text-info'>{everything_is_allowed}</span>";
		}
	
	
	
	
		
		$jsGroups="<a href=\"javascript:blur();\"
		OnClick=\"javascript:document.getElementById('anim-img-{$ligne["ID"]}').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-groups={$ligne["ID"]}&ID={$ligne["ID"]}&t=$t');\"
		style='text-decoration:underline;font-weight:bold'>";
	

	
	
		$TimeSpace=$webfilter->rule_time_list_explain($ligne["TimeSpace"],$ligne["ID"],$t);
		$TimeSpace=str_replace('\n\n', "<br>", $TimeSpace);
	
		$styleupd="style='border:0px;margin:0px;padding:0px;background-color:transparent'";
		$up=imgsimple("arrow-up-32.png","","RuleDansUpDown('{$ligne['ID']}',1)");
		$down=imgsimple("arrow-down-32.png","","RuleDansUpDown('{$ligne['ID']}',0)");
		$zorder="<table $styleupd><tr><td $styleupd>$down</td $styleupd><td $styleupd>$up</td></tr></table>";
	
	
		$LigneCount=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM webfilter_ipsources WHERE ruleid={$ligne['ID']}");
		
		$CountDeGroups=intval($LigneCount["tcount"]);
	
		if($ligne["AllSystems"]==1){$jsGroups="*";$CountDeGroups=$tpl->icon_nothing();}
	
		$MAIN_EXPLAIN_TR[]=$endofrule_TEXTS[$endofrule];
		if($TimeSpace<>null){$MAIN_EXPLAIN_TR[]=$TimeSpace;}
	
	
		$MAIN_EXPLAIN_TEXT=$tpl->_ENGINE_parse_body("<br>".@implode("<br>", $MAIN_EXPLAIN_TR));
		if($ligne["enabled"]==0){$MAIN_EXPLAIN_TEXT=null;}
		if(trim($ligne["groupname"])==null){$ligne["groupname"]="noname";}
		
		if($CountDewhite<2){$CountDewhite="$CountDewhite</strong> {category}";}else{$CountDewhite="$CountDewhite</strong> {categories}";}
		if($CountDeBlack<2){$CountDeBlack="$CountDeBlack</strong> {category}";}else{$CountDeBlack="$CountDeBlack</strong> {categories}";}
		$groupnameenc=urlencode($ligne["groupname"]);
		
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>{$ligne["zOrder"]}</td>";
		$html[]="<td>". $tpl->td_href($ligne["groupname"],null,"Loadjs('fw.dns.filterd.rules.edit.php?ID={$ligne['ID']}')")."&nbsp;&nbsp;$MAIN_EXPLAIN_TEXT</td>";
		$html[]="<td>". $tpl->td_href("$CountDeGroups {sources}",null,"Loadjs('$page?sources-js={$ligne['ID']}')")."</td>";
		
		$html[]="<td><strong>".$tpl->_ENGINE_parse_body($CountDewhite)."</strong></td>";
		$html[]="<td><strong>".$tpl->_ENGINE_parse_body($CountDeBlack)."</strong></td>";
		
		$html[]="<td>". $tpl->icon_copy("Loadjs('fw.dns.filterd.rules.duplicate.php?from={$ligne['ID']}&t=$t')","AsDnsAdministrator")."</td>";
		$html[]="<td>". $tpl->icon_up("RuleGroupUpDown$t({$ligne['ID']},1);").$tpl->icon_down("RuleGroupUpDown$t({$ligne['ID']},0);","AsDnsAdministrator")."</td>";
		$html[]="<td>". $tpl->icon_delete("Loadjs('$page?delete-js={$ligne['ID']}&rule=$groupnameenc&md=$md')","AsDnsAdministrator")."</td>";
		$html[]="</tr>";
	

		}
	
		if($DefaultPosition==1){
			if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
			$html[]=DefaultRule($TRCLASS);
		}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='8'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-filtragewebrules-rules').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";


$html[]="
var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	
}		
function RuleGroupUpDown$t(ID,direction){
		var XHR = new XHRConnection();
		XHR.appendData('rule-order', ID);
		XHR.appendData('direction', direction);
		XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
	}";

$html[]="</script>";
	
	echo @implode("\n", $html);
	
}

function save(){
	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
	}
}

function DefaultRule($TRCLASS){
	$t=$_GET["t"];
	$sock=new sockets();
	$page=CurrentPageName();
	$webfilter=new webfilter_rules("dns.db");
	$tpl=new template_admin();
	$tmplate=$tpl->_ENGINE_parse_body("{template}");

	$color="black";
	$sock=new dnsfiltersocks();
	$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	$EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
	if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}
	if(!is_numeric($ligne["groupmode"])){$ligne["groupmode"]=1;}

	$endofrule_TEXTS["any"]="<i class='text-info'>".$tpl->_ENGINE_parse_body("{ufdb_explain_any}")."</i>";
	$endofrule_TEXTS["none"]="<i class='text-danger'>".$tpl->_ENGINE_parse_body("{ufdb_explain_none}")."</i>";

	if($ligne["endofrule"]==null){$ligne["endofrule"]="any";}
	$endofrule_text=null;

	$CountDeBlack=intval($webfilter->COUNTDEGBLKS(0));
	$CountDewhite=intval($webfilter->COUNTDEGBWLS(0));
	$CountDeAll=intval($CountDeBlack+$CountDewhite);

	if($CountDeAll==0){
		$MAINTR[]=$tpl->_ENGINE_parse_body("<i class='text-danger'>{no_category_has_been_added}</i>");
	}

	$MAINTR[]="<i>{ufdb_explain_default_rule}</i>";


	if($EnableGoogleSafeSearch==0){
		if($ligne["GoogleSafeSearch"]==1){
			$EnableGoogleSafeSearch_text=$tpl->javascript_parse_text(
					"<i>{EnableGoogleSafeSearch}</i>");
		}
			
	}

	if($ligne["groupmode"]==0){
		$MAINTR[]="<i class='text-danger'>{all_websites_are_banned}</span>";
	}
	if($ligne["groupmode"]==2){
		$MAINTR[]="<i class='text-info'>{everything_is_allowed}</span>";
	}



	$js="DansGuardianEditRule('0','default')";
	$jsblack="<a href=\"javascript:blur();\"
	OnClick=\"javascript:document.getElementById('anim-img-0').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID=0&modeblk=0&group=&TimeID=&t=$t');\"
	style='text-decoration:underline;font-weight:bold;color:$color'>";


	$jswhite="<a href=\"javascript:blur();\"
	OnClick=\"javascript:document.getElementById('anim-img-0').innerHTML='<img src=img/wait.gif>';Loadjs('dansguardian2.edit.php?js-blacklist-list=yes&RULEID=0&modeblk=1&group=&TimeID=&t=$t');\"
	style='text-decoration:underline;font-weight:bold;color:$color'>";




	$delete="&nbsp;";
	$sock=new dnsfiltersocks();
	$ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
	$TimeSpace=$webfilter->rule_time_list_explain($ligne["TimeSpace"],0,$t);
	$TimeSpace=str_replace('\n\n', "<br>", $TimeSpace);
	$MAINTR[]=$endofrule_TEXTS[$ligne["endofrule"]];
	if($EnableGoogleSafeSearch_text<>null){$MAINTR[]=$EnableGoogleSafeSearch_text;}
	if($TimeSpace<>null){$MAINTR[]=$TimeSpace;}

	$MAINTRTEXT=$tpl->_ENGINE_parse_body(@implode("<br>", $MAINTR));

	if($CountDewhite<2){$CountDewhite="$CountDewhite</strong> {category}";}else{$CountDewhite="$CountDewhite</strong> {categories}";}
	if($CountDeBlack<2){$CountDeBlack="$CountDeBlack</strong> {category}";}else{$CountDeBlack="$CountDeBlack</strong> {categories}";}
	$MAINTRTEXT=str_replace("<br>\n<br>", "<br>", $MAINTRTEXT);
	$MAINTRTEXT=str_replace("<br><br>", "<br>", $MAINTRTEXT);
	$html[]="<tr class='$TRCLASS'>";
	$html[]="<td>". $tpl->icon_nothing()."</td>";
	$html[]="<td>". $tpl->td_href("{default}",null,"Loadjs('fw.dns.filterd.rules.edit.php?ID=0')")."&nbsp;&nbsp;$MAINTRTEXT</td>";
	$html[]="<td>". $tpl->icon_nothing()."</td>";
	$html[]="<td><strong>$CountDewhite</strong></td>";
	$html[]="<td><strong>$CountDeBlack</strong></td>";
	$html[]="<td>". $tpl->icon_copy("Loadjs('fw.dns.filterd.rules.duplicate.php?from=0')")."</td>";
	$html[]="<td>". $tpl->icon_nothing()."</td>";
	$html[]="<td>". $tpl->icon_nothing()."</td>";
	$html[]="</tr>";

	return $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function move_rule(){

	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT zOrder FROM webfilter_rules WHERE `ID`='{$_POST["rule-order"]}'";
	$ligne=$q->mysqli_fetch_array($sql);
	$xORDER_ORG=$ligne["zOrder"];
	$xORDER=$xORDER_ORG;
	if($_POST["direction"]==1){$xORDER=$xORDER_ORG-1;}
	if($_POST["direction"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE webfilter_rules SET zOrder=$xORDER WHERE `ID`='{$_POST["rule-order"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;;return;}
	

	if($_POST["direction"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilter_rules SET zOrder=$xORDER2 WHERE `ID`<>'{$_POST["rule-order"]}' AND zOrder=$xORDER";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if($_POST["direction"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilter_rules SET zOrder=$xORDER2 WHERE `ID`<>'{$_POST["rule-order"]}' AND zOrder=$xORDER";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}

	$c=0;
	$sql="SELECT ID FROM webfilter_rules ORDER BY zOrder";
	$results = $q->QUERY_SQL($sql);

	foreach ($results as $index=>$ligne){
		$q->QUERY_SQL("UPDATE webfilter_rules SET zOrder=$c WHERE `ID`={$ligne["ID"]}");
		$c++;
	}


}