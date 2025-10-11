<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.mem.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["missing-databases-ufdb-alert"])){missing_databases_alert();exit;}
if(isset($_POST["UfdbReloadBySchedule"])){save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_rule();exit;}
if(isset($_POST["rule-order"])){move_rule();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
page();



function delete_js():bool{
    $tpl=new template_admin();
    $js="$('#{$_GET["md"]}').remove();";
    return $tpl->js_confirm_delete($_GET["rule"], "delete", $_GET["delete-js"],$js);

}
function delete_rule(){
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ID=$_POST["delete"];
    $q->QUERY_SQL("DELETE FROM webfilter_assoc_groups WHERE webfilter_id='$ID'");
    if(!$q->ok){echo $q->mysql_error;return;}
    $q->QUERY_SQL("DELETE FROM webfilter_blklnk WHERE webfilter_ruleid='$ID'");
    if(!$q->ok){echo $q->mysql_error;return;}
    $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id='$ID'");
    if(!$q->ok){echo $q->mysql_error;return;}
    $q->QUERY_SQL("DELETE FROM webfilter_rules WHERE ID='$ID'");
    if(!$q->ok){echo $q->mysql_error;return;}
    $q->QUERY_SQL("DELETE FROM ufdb_page_rules WHERE webruleid='$ID'");

}

function page(){
    $tpl=new template_admin();

    $html=$tpl->page_header("{WEB_FILTERING}: {rules}",
    "fa fa-align-justify","{ufdbgdb_rules_explain}",
        "fw.ufdb.rules.php?table-start=yes","webfiltering-rules","progress-ufdbrules-restart",false,"table-loader-ufdbrules-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{WEB_FILTERING}: {rules}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='artica-ufdbrules-table' style='margin-top:10px'></div>
    <script>LoadAjax('artica-ufdbrules-table','$page?table=yes');</script>";
    return true;
}
function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $rule_text=$tpl->_ENGINE_parse_body("{rule}");
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));

    $q->QUERY_SQL("DELETE FROM webfilter_assoc_groups WHERE group_id=''");

    if($EnableUfdbGuard==0){

        $restart=$tpl->framework_buildjs("squid.php?ufdbguard_enable_progress=yes",
            "ufdb.enable.progress",
            "ufdb.enable.progress.log",
            "progress-ufdbrules-restart",
            "location.reload();"
        );

        $button=$tpl->button_autnonome("{install_feature}",$restart,ico_cd,"AsSquidAdministrator");

        $html[]="<div style='margin-left:130px;margin-top: 50px;width: 600px;'><table style='width:100%'>";
        $html[]="<tr>";
        $html[]="<td style='width:125px;vertical-align: top'><i class='fa-duotone fa-compact-disc fa-8x' style='color:rgb(26, 179, 148)'></i></td>";
        $html[]="<td style='vertical-align: top'><H1>{enable_ufdbguardd}</H1>";
        $html[]="<p>{enable_ufdbguardd_text}</p>";
        $html[]="<div style='margin-top:20px;text-align:right;border-top:1px solid #CCCCCC;padding-top:10px'>$button</div>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</table></div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        
    }



    $SquidUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency"));
    $SquidUFDBUrgency=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency"));
    if($SquidUrgency==1) {
        echo $tpl->FATAL_ERROR_SHOW_128("{proxy_in_emergency_mode}","Loadjs('fw.proxy.emergency.remove.php');");
    }

    if($SquidUFDBUrgency==1) {
        echo $tpl->FATAL_ERROR_SHOW_128("{proxy_in_webfiltering_emergency_mode}","Loadjs('fw.ufdb.emergency.remove.php');");
    }

    $groups=$tpl->_ENGINE_parse_body("{sources}");
    $blacklists=$tpl->_ENGINE_parse_body("{blacklists}");
    $whitelists=$tpl->_ENGINE_parse_body("{whitelists}");
    $delete=$tpl->_ENGINE_parse_body("{delete}");
    $order=$tpl->javascript_parse_text("{order}");
    $TRCLASS=null;
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

    $jsrestart=$tpl->framework_buildjs("/ufdb/compile",
        "dansguardian2.mainrules.progress",
        "dansguardian2.mainrules.progress.log",
        "progress-ufdbrules-restart",
        "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');"
    );

    $jsUpdate=$tpl->framework_buildjs("/category/ufdb/update",
        "artica-webfilterdb.progress","artica-webfilterdb.log","progress-ufdbrules-restart",
        "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');",
        "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');");


    $add="Loadjs('fw.ufdb.rules.edit.php?ID=-1')";

    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array($add, ico_plus, "{new_rule}");
    }

    $topbuttons[] = array("$jsrestart", ico_save, "{build_web_filtering_rules}");
    $topbuttons[] = array("Loadjs('fw.ufdb.categories.groups.php')", "fas fa-folder-tree", "{categories_groups}");


    $topbuttons[] = array("Loadjs('fw.ufdb.verify.rules.php')", "fas fa-check", "{verify_rules}");

    $topbuttons[] = array("Loadjs('fw.ufdb.used.databases.php')", "fas fas fa-database", "{used_databases}");
    $topbuttons[] = array($jsUpdate, ico_download, "{update_databases}");



    $html[]="<div id='missing-databases-ufdb-alert'></div>";
    $html[]="<table id='table-filtragewebrules-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >$order</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$rule_text</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$groups</th>";



    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>". $tpl->_ENGINE_parse_body($blacklists)."</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>". $tpl->_ENGINE_parse_body($whitelists)."</th>";
    $html[]="<th data-sortable=true class='text-capitalize' style='width:1%' nowrap>". $tpl->_ENGINE_parse_body("{duplicate}")."</th>";
    $html[]="<th data-sortable=true class='text-capitalize' style='width:1%' nowrap>". $tpl->_ENGINE_parse_body("{move}")."</th>";
    $html[]="<th data-sortable=false style='width:1%'>$delete</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $ligne=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule"));
    if(!isset( $ligne["defaultPosition"])){
        $ligne["defaultPosition"]=0;
    }
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
    $webfilter=new webfilter_rules();
    $t=time();
    $tpl->CLUSTER_CLI=true;
    $PRODUCTION_RULES=array();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/rules"));
    if(!property_exists($data,"Rules")){
        echo $tpl->div_error("Rest API rules does not exist");

    }else{
        foreach ($data->Rules as $rule) {
            $PRODUCTION_RULES[intval($rule)]=true;
        };
    }

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=intval($ligne["ID"]);
        $ligne["groupname"]=$tpl->utf8_encode($ligne["groupname"]);
        $endofrule=$ligne["endofrule"];
        if($endofrule==null){$endofrule="any";}
        $md=md5(serialize($ligne));
        $text_class=null;
        $status="<span class='label label-default'>{inactive2}</span>";
        if(isset($PRODUCTION_RULES[$ID])){
            $status="<span class='label label-primary'>{active2}</span>";
        }

        $MAIN_EXPLAIN_TR=array();

        if($GLOBALS["VERBOSE"]){echo "<HR id='$index'>webfilter->rule_time_list_from_ruleid($ID)<HR><br>\n";}

        $CountDeBlack=intval($webfilter->COUNTDEGBLKS($ligne["ID"]));
        $CountDewhite=intval($webfilter->COUNTDEGBWLS($ligne["ID"]));

        $CountDeAll=$CountDeBlack+$CountDewhite;
        if($CountDeAll==0){

            $MAIN_EXPLAIN_TR[]="<i class='text-danger'>$no_category_has_been_added</i>";
        }

        if($ligne["groupmode"]==0){
            $MAIN_EXPLAIN_TR[]="<i class='text-danger'>{all_websites_are_banned}</span>";
        }
        if($ligne["groupmode"]==2){
            $MAIN_EXPLAIN_TR[]="<i class='text-info'>{everything_is_allowed}</span>";
        }
        $iconw="";
        $icon="";
        $TimeSpace=$webfilter->rule_time_list_explain($ligne["TimeSpace"],$ligne["ID"],$t);
        $TimeSpace=str_replace('\n\n', "<br>", $TimeSpace);

        $CountDeGroups=$webfilter->COUNTDEGROUPES($ligne["ID"]);
        $row_sources=$tpl->td_href("$CountDeGroups {sources}",null,"Loadjs('fw.ufdb.rules.sources.php?ID={$ligne['ID']}')");

        if($ligne["AllSystems"]==1){$row_sources="<i class=\"fas fa-asterisk\"></i>";}

        if($ligne["AllSystems"]==0){
            if($CountDeGroups==0){
                $text_class="text-danger";
                $MAIN_EXPLAIN_TR[]="<span class='text-danger'>{no_source_defined}</span>";
            }
        }

        $MAIN_EXPLAIN_TR[]=$endofrule_TEXTS[$endofrule];
        if($TimeSpace<>null){$MAIN_EXPLAIN_TR[]=$TimeSpace;}


        $MAIN_EXPLAIN_TEXT=$tpl->_ENGINE_parse_body("<br>".@implode("<br>", $MAIN_EXPLAIN_TR));
        if($ligne["enabled"]==0){$MAIN_EXPLAIN_TEXT=null;}
        if(trim($ligne["groupname"])==null){$ligne["groupname"]="noname";}

        if($CountDewhite==0){$iconw="fal fa-layer-group";}
        if($CountDeBlack==0){$icon="fal fa-layer-group";}

        if($CountDewhite>0){$iconw="fas fa-layer-group";}
        if($CountDeBlack>0){$icon="fas fa-layer-group";}

        if($CountDewhite<2){$CountDewhite="$CountDewhite {category}";}else{$CountDewhite="$CountDewhite {categories}";}
        if($CountDeBlack<2){$CountDeBlack="$CountDeBlack {category}";}else{$CountDeBlack="$CountDeBlack {categories}";}
        $groupnameenc=urlencode($ligne["groupname"]);

        $jsCatBlack="Loadjs('fw.ufdb.rules.categories.php?js-ID={$ligne['ID']}&modeblk=0');";
        $jsCatWhite="Loadjs('fw.ufdb.rules.categories.php?js-ID={$ligne['ID']}&modeblk=1');";

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap class='center'>{$ligne["zOrder"]}</td>";
        $html[]="<td style='width:1%' nowrap class='center'>$status</td>";
        $html[]="<td class='$text_class'>". $tpl->td_href($ligne["groupname"],null,"Loadjs('fw.ufdb.rules.edit.php?ID={$ligne['ID']}')")."&nbsp;&nbsp;$MAIN_EXPLAIN_TEXT</td>";
        $html[]="<td style='width:1%' nowrap class='$text_class center'>$row_sources</td>";

        $blackRow="<strong><i class='$icon'></i>&nbsp;".$tpl->td_href($CountDeBlack,null,$jsCatBlack)."</strong>";

        if($endofrule=="none"){
            $blackRow="&nbsp;";

        }
        $html[]="<td style='width:1%' nowrap class='$text_class center'>$blackRow</td>";
        $html[]="<td style='width:1%' nowrap class='$text_class center'><strong><i class='$iconw'></i>&nbsp;".$tpl->td_href($CountDewhite,null,$jsCatWhite)."</strong></td>";


        $html[]="<td style='width:1%' nowrap class='$text_class center'>". $tpl->icon_copy("Loadjs('fw.webfiltering.rule.duplicate.php?from={$ligne['ID']}&t=$t&page=$page')")."</td>";
        $html[]="<td style='width:1%' nowrap class='$text_class center'>". $tpl->icon_up("RuleGroupUpDown$t({$ligne['ID']},1);").$tpl->icon_down("RuleGroupUpDown$t({$ligne['ID']},0);")."</td>";
        $html[]="<td style='width:1%' nowrap class='$text_class center'>". $tpl->icon_delete("Loadjs('$page?delete-js={$ligne['ID']}&rule=$groupnameenc&md=$md')","AsDansGuardianAdministrator")."</td>";
        $html[]="</tr>";


    }

    if($DefaultPosition==1){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]=DefaultRule($TRCLASS);
    }

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{WEB_FILTERING}: {rules}";
    $TINY_ARRAY["ICO"]="fa fa-align-justify";
    $TINY_ARRAY["EXPL"]="{ufdbgdb_rules_explain}";
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='9'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."$headsjs;
$(document).ready(function() { $('#table-filtragewebrules-rules').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });";


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
    $html[]="LoadAjaxSilent('missing-databases-ufdb-alert','$page?missing-databases-ufdb-alert=yes');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function save(){
    $sock=new sockets();
    foreach ($_POST as $key=>$value){
        $sock->SET_INFO($key, $value);
    }
}

function DefaultRule($TRCLASS){
    $t=time();
    if(isset($_GET["t"])) {
        $t = $_GET["t"];
    }
    $webfilter=new webfilter_rules();
    $tpl=new template_admin();
    $EnableGoogleSafeSearch_text=null;

    $ligne=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule"));

    if(!isset($ligne["groupmode"])){$ligne["groupmode"]=1;}

    $EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
    if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}
    if(!is_numeric($ligne["groupmode"])){$ligne["groupmode"]=1;}

    $endofrule_TEXTS["any"]="<i class='text-info'>".$tpl->_ENGINE_parse_body("{ufdb_explain_any}")."</i>";
    $endofrule_TEXTS["none"]="<i class='text-danger'>".$tpl->_ENGINE_parse_body("{ufdb_explain_none}")."</i>";

    if(!isset($ligne["endofrule"])){$ligne["endofrule"]="any";}

    if($ligne["endofrule"]==null){$ligne["endofrule"]="any";}

    $CountDeBlack=intval($webfilter->COUNTDEGBLKS(0));
    $CountDewhite=intval($webfilter->COUNTDEGBWLS(0));
    $CountDeAll=$CountDeBlack+$CountDewhite;

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
    if(!isset($ligne["endofrule"])){
        $ligne["endofrule"]="any";
    }

    $TimeSpace="";
    $ligne=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule"));
    if(isset($ligne["TimeSpace"])) {
        $TimeSpace = $webfilter->rule_time_list_explain($ligne["TimeSpace"], 0, $t);
        $TimeSpace = str_replace('\n\n', "<br>", $TimeSpace);
    }
    $endofrule=$ligne["endofrule"];
    if(!is_null($endofrule)) {
        $MAINTR[] = $endofrule_TEXTS[$endofrule];
    }
    if($EnableGoogleSafeSearch_text<>null){$MAINTR[]=$EnableGoogleSafeSearch_text;}
    if($TimeSpace<>null){$MAINTR[]=$TimeSpace;}

    $MAINTRTEXT=$tpl->_ENGINE_parse_body(@implode("<br>", $MAINTR));

    if($CountDewhite==0){$iconw="fal fa-layer-group";}
    if($CountDeBlack==0){$icon="fal fa-layer-group";}

    if($CountDewhite>0){$iconw="fas fa-layer-group";}
    if($CountDeBlack>0){$icon="fas fa-layer-group";}

    if($CountDewhite<2){
        $CountDewhite="$CountDewhite {category}";
    }else{
        $CountDewhite="$CountDewhite {categories}";
    }
    if($CountDeBlack<2){
        $CountDeBlack="$CountDeBlack {category}";
    }else{
        $CountDeBlack="$CountDeBlack {categories}";
    }
    $MAINTRTEXT=str_replace("<br>\n<br>", "<br>", $MAINTRTEXT);
    $MAINTRTEXT=str_replace("<br><br>", "<br>", $MAINTRTEXT);
    $status="<span class='label label-primary'>{active2}</span>";
    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td class='center'>". $tpl->icon_nothing()."</td>";
    $html[]="<td class='center'>$status</td>";
    $html[]="<td>". $tpl->td_href("{default_webrule}",null,"Loadjs('fw.ufdb.rules.edit.php?ID=0')")."&nbsp;&nbsp;$MAINTRTEXT</td>";
    $html[]="<td class='center'><i class=\"fas fa-asterisk\"></i></td>";

    $jsCatBlack="Loadjs('fw.ufdb.rules.categories.php?js-ID=0&modeblk=0');";
    $jsCatWhite="Loadjs('fw.ufdb.rules.categories.php?js-ID=0&modeblk=1');";

    $blackRow="<strong><i class='$icon'></i>&nbsp;".$tpl->td_href($CountDeBlack,null,$jsCatBlack)."</strong>";

    if($endofrule=="none"){
        $blackRow="&nbsp;";

    }

    $html[]="<td class='center'>$blackRow</td>";
    $html[]="<td class='center'><strong><i class='$iconw'></i>&nbsp;".$tpl->td_href($CountDewhite,null,$jsCatWhite)."</strong></td>";
    $html[]="<td class='center'>". $tpl->icon_copy("Loadjs('fw.webfiltering.rule.duplicate.php?default-rule=yes&t=$t')")."</td>";
    $html[]="<td class='center'>". $tpl->icon_nothing()."</td>";
    $html[]="<td class='center'>". $tpl->icon_nothing()."</td>";
    $html[]="</tr>";

    return @implode("\n", $html);

}
function move_rule(){

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
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

function missing_databases_alert():bool{
    $catz=new mysql_catz();
    $f=array();
    $UfdbUsedDatabases=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUsedDatabases"));
    if(!$UfdbUsedDatabases){
        $UfdbUsedDatabases["MISSING"]=array();
    }
    $CATMEM=FILL_CATEGORIES_MEM();

    if(!isset($CATMEM["categories_descriptions"])){
        $CATMEM["categories_descriptions"]["SKIPPED"]=0;
    }

    if(!isset($CATMEM["categories_descriptions"]["SKIPPED"])){
        $CATMEM["categories_descriptions"]["SKIPPED"]=0;
    }
    $SKIPPED=$CATMEM["categories_descriptions"]["SKIPPED"];
    if(!isset($UfdbUsedDatabases["MISSING"])){
        $UfdbUsedDatabases["MISSING"]=array();
    }


    $CountDeMissing=count($UfdbUsedDatabases["MISSING"]);
    if($CountDeMissing>0){
        foreach ($UfdbUsedDatabases["MISSING"] as $category_id=>$none){
            if(isset($SKIPPED[$category_id])){
                continue;
            }

            $category_name=$catz->CategoryIntToStr($category_id);
            $f[]=$category_name;
        }
    }

    if(count($f)==0){return true;}
    $CountDeMissing=count($f) ." (" .@implode(", ",$f).")";
    $tpl=new template_admin();
    $missing_webf_database_explain=$tpl->_ENGINE_parse_body("{missing_webf_database_explain}");
    $missing_webf_database_explain=str_replace("%s",$CountDeMissing,$missing_webf_database_explain);
    $updateurl="document.location.href='/webfiltering-update-databases'";
    $updatelink=$tpl->td_href("{update_parameters}","",$updateurl);
    $link="<div style='margin-top:10px;text-align:right'>$updatelink</div>";


    echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>$missing_webf_database_explain$link</div>");
    return true;
}