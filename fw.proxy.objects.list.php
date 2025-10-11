<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete"])){rule_delete_perform();exit;}
if(isset($_GET["countItems"])){countItems();exit;}
if(isset($_GET["remove-rules-js"])){remove_rules_js();exit;}
if(isset($_POST["remove-rules-perform"])){remove_rules_perform();exit;}
page();


function countItems(){
    $tpl                =new template_admin();
    $gpid=$_GET["countItems"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne2=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$gpid'");
    $items=intval($ligne2["tcount"]);
    echo $tpl->FormatNumber($items);
}
function remove_rules_perform():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tables[]="webfilters_sqitems";
    $tables[]="webfilters_sqgroups";
    $tables[]="sslrules_sqacllinks";
    $tables[]="sslproxy_cert_error_sqacllinks";
    $tables[]="ssl_rules";
    $tables[]="tcp_outgoing_mark";
    $tables[]="tcp_outgoing_mark_links";
    $tables[]="squid_http_headers_acls";
    $tables[]="http_reply_access_links";
    $tables[]="quid_http_headers_acls";
    $tables[]="http_reply_access";
    $tables[]="http_reply_access_links";
    $tables[]="quid_http_bandwidth_acls";
    $tables[]="quid_http_bandwidth_link";
    $tables[]="quid_auth_schemes_acls";
    $tables[]="quid_auth_schemes_link";
    $tables[]="quid_icap_acls";
    $tables[]="quid_icap_acls_link";
    $tables[]="logs_sqacllinks";
    $tables[]="http_upgrade_sqacllinks";
    $tables[]="http_upgrade_sqacls";
    $tables[]="quid_parents_acls";
    $tables[]="webfilters_sqaclaccess";
    $tables[]="acls_whitelist";
    $tables[]="squid_logs_acls";
    $tables[]="squid_outgoingaddr_acls";
    $tables[]="deny_cache_domains";
    $tables[]="squid_url_rewrite_acls";
    $tables[]="ext_time_quota_acl";
    $tables[]="squid_url_rewrite_link";
    $tables[]="ext_time_quota_acl_link";
    $tables[]="sslrules_sqacllinks";
    $tables[]="sslproxy_cert_error_sqacllinks";
    $tables[]="global_whitelist";
    $tables[]="http_reply_access";
    $tables[]="webfilters_blkwhlts";
    $tables[]="webfilters_gpslink";
    $tables[]="limit_bdwww";
    $tables[]="webfilters_sqaclaccess";
    $tables[]="webfilters_sqacls";
    $tables[]="webfilters_sqaclsports";
    $tables[]="parents_sqacllinks";
    $tables[]="webfilters_sqitems";
    foreach ($tables as $table) {
        $q->QUERY_SQL("DELETE FROM $table");
    }
  return admin_tracks("As deleted all acls!");
}
function remove_rules_js():bool{
    $function=$_GET["function"];
    $tpl =new template_admin();
    return $tpl->js_confirm_delete("{acl_warn_remove_all}","remove-rules-perform","yes","$function()");
}
function start(){
    $tpl                =new template_admin();
    $page               = CurrentPageName();
    $title              = $_GET["title"];
    $explain            = $_GET["explain"];
    $links              = base64_encode($_GET["links"]);
    $titleenc              = base64_encode($title);
    $Explainenc         = base64_encode($explain);
    $firewall_query     = intval($_GET["firewall_query"]);
    $ProxyPac        = intval($_GET["ProxyPac"]);
    $addons  = array();
    $addons[]="title=$titleenc";
    $addons[]="explain=$Explainenc";
    $addons[]="links=$links";
    $addons[]="ProxyPac=$ProxyPac";


    if($firewall_query==1){
        $addons[]="firewall=yes";
    }

    $saddon=@implode("&",$addons);
    $html[]=$tpl->search_block($page,null,null,null,"&$saddon",null);
    echo $tpl->_ENGINE_parse_body($html);
}

function page(){
    $tpl                =new template_admin();
    $page               = CurrentPageName();
    $links              = "proxy-objects";
    $title              = "{proxy_objects}";
    $Explain            = "{proxy_objects_explain}";
    $firewall_query     = 0;
    $addons=null;

    if(isset($_GET["firewall"])){
        $title="{firewall_objects}";
        $Explain="{firewall_objects_explain}";
        $links="firewall-objects";
        $addons="&firewall=yes";
        $firewall_query=1;
    }
    $titleenc=urlencode($title);
    $Explainenc=urlencode($Explain);
    $error=null;


    $html=$tpl->page_header($title,"fas fa-cubes",$Explain,"$page?start=yes&title=$titleenc&explain=$Explainenc&links=$links&firewall_query=$firewall_query",$links,"progress-pobjects-restart",false,"div-acls_objects-list");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{proxy_objects}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function rule_delete_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-rule-js"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$ID");
    $tpl->js_confirm_delete(utf8_encode($ligne["GroupName"]), "delete", $ID,"$('#$md').remove()");
}
function rule_delete_perform(){
    $ID=$_POST["delete"];
    $acls=new squid_acls();
    $acls->delete_group($ID);

}

function enable_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["enable-js"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM webfilters_sqgroups WHERE ID=$ID");
    if(intval($ligne["enabled"])==1){
        $q->QUERY_SQL("UPDATE webfilters_sqgroups SET enabled=0 WHERE ID=$ID");
    }else{
        $q->QUERY_SQL("UPDATE webfilters_sqgroups SET enabled=1 WHERE ID=$ID");
    }
}



function IntelligentSearch($search):array{
    $q              = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $search=trim($search);
    if(preg_match("#^group([0-9]+)$#i",$search,$re)){
        $search=$re[1];
    }


    if(is_numeric($search)){
        $sql="SELECT * FROM webfilters_sqgroups WHERE ID=$search";
        return $q->QUERY_SQL($sql);
    }
    if(trim($_GET["search"])==null){return array();}
    $search="*".trim($_GET["search"])."*";
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);

    $sql = "SELECT webfilters_sqgroups.ID,webfilters_sqgroups.GroupName,
         webfilters_sqgroups.GroupType,webfilters_sqgroups.enabled
         FROM webfilters_sqgroups,webfilters_sqitems WHERE 
         webfilters_sqgroups.ID=webfilters_sqitems.gpid AND webfilters_sqitems.pattern LIKE '$search' OR webfilters_sqgroups.ID=webfilters_sqitems.gpid AND webfilters_sqitems.description LIKE '$search' ORDER BY pattern";

    return $q->QUERY_SQL($sql);



}

function table(){
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $q              = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $qProxy         = new mysql_squid_builder();
    $acls           = new squid_acls();
    $function       = $_GET["function"];
    $jsafter        = base64_encode("$function()");
    $ProxyPac       = intval($_GET["ProxyPac"]);
    $t=time();



    $acls->LoadGroupsInMemory(true);
    $ppac=WHICH_RULE_OBJECT_WPAD();

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>ID</th>";
    $html[]="<th data-sortable=true data-type='text'>{object_name}</th>";
    $html[]="<th data-sortable=true data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true data-type='text'>{rules}</th>";
    $html[]="<th data-sortable=true data-type='text'>{items}</th>";
    $html[]="<th data-sortable=false>{enabled}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $_GET["search"]=$tpl->CLEAN_BAD_XSS($_GET["search"]);
    $search="*".trim($_GET["search"])."*";
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);
//
//GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`
    $TRCLASS=null;
    $sql="SELECT * FROM webfilters_sqgroups WHERE GroupName LIKE '$search' ORDER BY GroupName";
    $results=$q->QUERY_SQL($sql);

    if(strlen($search)>0) {
        $results2 = IntelligentSearch($_GET["search"]);
        if (count($results2) > 0) {
            $results = array_merge($results, $results2);
        }
    }
    $ALREADY=array();
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        if(isset($ALREADY[$ID])){continue;}

        $md=md5(serialize($ligne));
        $ALREADY[$ID]=true;
        $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
        $SrcGroupType=$ligne["GroupType"];
        $addGroup=null;
        $qProxy->acl_GroupType["geoip"] = "{geoip_location}";
        $GroupType=$qProxy->acl_GroupType[$ligne["GroupType"]];
        $gpsSlavles=array();

        $ico=$qProxy->acl_GroupTypeIcon[$ligne["GroupType"]];
        if($GroupType==null){
            if($SrcGroupType=="AclsGroup") {
                $GroupType = "{group_of_objects}";
            }

        }
        if(isset($qProxy->acl_ARRAY_NO_ITEM[$ligne["GroupType"]])){$items="-";}else{
            $ligne2=$q->mysqli_fetch_array("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='$ID'");
            $items=$tpl->FormatNumber(intval($ligne2["tcount"]));
        }

        $js="Loadjs('fw.rules.items.php?groupid=$ID&js-after=&TableLink=&RefreshTable=$jsafter&ProxyPac=$ProxyPac&firewall=0&RefreshFunction=$jsafter')";

        if($SrcGroupType=="AclsGroup") {
            $items=0;
            $results_gplist=$q->QUERY_SQL("SELECT gpid FROM webfilters_gpslink WHERE groupid=$ID");
            foreach ($results_gplist as $index=>$gplist_ligne){
                $webfilters_sqgroups_GroupName=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID={$gplist_ligne["gpid"]}");
                $items++;

                $jsSlaves="Loadjs('fw.rules.items.php?groupid={$gplist_ligne["gpid"]}&js-after=&TableLink=&RefreshTable=$jsafter&ProxyPac=$ProxyPac&firewall=0&RefreshFunction=$jsafter')";
                $gpsSlavles[]=$tpl->td_href($webfilters_sqgroups_GroupName["GroupName"],null,$jsSlaves);
            }
            $addGroup="&nbsp;&nbsp;<small>".@implode("&nbsp;|&nbsp;",$gpsSlavles)."</small>";
        }
        
        
        $rules_text=WHICH_RULE_OBJECT($ID);



        $delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js=$ID&md=$md')","AsDansGuardianAdministrator");
        $enable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')",null,"AsDansGuardianAdministrator");

        $GroupName=$tpl->td_href($GroupName,null,$js);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><strong>Group$ID</strong></td>";
        $html[]="<td><strong><i class='fa-duotone fa-layer-group'></i>&nbsp;$GroupName</strong>$addGroup</td>";
        $html[]="<td  style='width:1%' nowrap><strong><i class='$ico'></i>&nbsp;$GroupType</strong></td>";
        $html[]="<td>$rules_text</td>";
        $html[]="<td  style='width:1%' nowrap><span id='explain-this-rule-$md' data='$page?countItems=$ID'>$items</span></td>";
        $html[]="<td style='width:1%;' nowrap class='center'>$enable</td>";
        $html[]="<td style='width:1%;' nowrap class='center'>$delete</td>";
        $html[]="</tr>";


    }
    $firewall_query=0;
    $title=base64_decode($_GET["title"]);
    $explain=base64_decode($_GET["explain"]);
    $function=base64_encode($_GET["function"]."();");
    $links=base64_decode($_GET["links"]);
    if(isset($_GET["firewall_query"])) {
        $firewall_query = intval($_GET["firewall_query"]);
    }
    $new_object="Loadjs('fw.proxy.acls.objects.php?new-object-js=yes&ID=0&direction=0&TableLink=&RefreshTable=$function&ProxyPac=0&firewall=$firewall_query')";
    $import3x="Loadjs('fw.proxy.acls.objects.import3x.php?func=$function')";
    $users=new usersMenus();
    $topbuttons=array();
    if($users->AsDansGuardianAdministrator) {
        $topbuttons[] = array($new_object, ico_plus, "{new_object}");
    }
    if($users->AsSquidAdministrator) {
        $topbuttons[] = array($import3x, ico_download, "{import} 3.x");
    }
    $topbuttons[] = array("Loadjs('$page?remove-rules-js=yes&function=$function')", ico_trash, "{delete_all}");



    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]="fas fa-cubes";
    $TINY_ARRAY["EXPL"]=$explain;
    $TINY_ARRAY["URL"]=$links;
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='7'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<div><small>$sql</small></div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	
	$jstiny
</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function WHICH_RULE_OBJECT($ID):string{
    $tpl=new template_admin();
    if(!isset($GLOBALS["WHICH_RULE_OBJECT_WPAD"])){$GLOBALS["WHICH_RULE_OBJECT_WPAD"]=WHICH_RULE_OBJECT_WPAD();}
    $RULES=array();
    $i=0;
    if(isset($GLOBALS["LoadRulesInMemory"][$ID])) {

        foreach ($GLOBALS["LoadRulesInMemory"][$ID] as $array) {

            $i++;
            if($i>9){$i=1;}

            $RuleID=$array[1];
            $RuleTable=$array[2];
            $Type=null;
            $rule_unknown=null;
            $js=null;
            $Link=$tpl->td_href($array[0],$RuleTable);
            if($RuleTable=="webfilters_sqacls"){
                $Type="{access_rules}";
                $js="Loadjs('fw.proxy.acls.php?rule-id-js=$RuleID')";
                $Link=$tpl->td_href($array[0],$Type,$js);
            }

            if($RuleTable=="squid_url_rewrite_acls"){
                $Type=$tpl->_ENGINE_parse_body("{web_filter_policies}");
                $js="Loadjs('fw.proxy.urlrewrite.php?rule-id-js=$RuleID')";
                $Link=$tpl->td_href($array[0],$Type,$js);
            }

            if($js==null){
                $rule_unknown="($RuleTable)";
            }

            $class="<i class='fa-solid fa-circle-$i'></i>&nbsp;";
            $RULES[] = "$class$Link&nbsp;$rule_unknown";

        }
    }

    if(!isset($GLOBALS["WHICH_RULE_OBJECT_WPAD"][$ID])){
        VERBOSE("$ID !isset WHICH_RULE_OBJECT_WPAD",__LINE__);
    }

    if(isset($GLOBALS["WHICH_RULE_OBJECT_WPAD"][$ID])){
        foreach ($GLOBALS["WHICH_RULE_OBJECT_WPAD"][$ID] as $ruletext){
            $i++;
            if($i>9){$i=1;}
            $class="<i class='fa-solid fa-circle-$i'></i>&nbsp;";
            $RULES[]="$class$ruletext";

        }
    }

    if(count($RULES)==0){
        VERBOSE("$ID ! WHICH_RULE_OBJECT = NO RULE",__LINE__);
        return "";
    }

    VERBOSE("$ID ! ".count($RULES)." rules",__LINE__);

    return @implode("<br>",$RULES);
}
function WHICH_RULE_OBJECT_WPAD():array{
    $array=array();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results = $q->QUERY_SQL("SELECT 
                wpad_rules.rulename,
				wpad_rules.enabled,
				wpad_rules.ID as aclid,
				wpad_sources_link.gpid 
				FROM wpad_sources_link,wpad_rules 
				WHERE wpad_sources_link.aclid=wpad_rules.ID");
    
    if(!$q->ok){
        VERBOSE($q->mysql_error,__LINE__);
    }

    foreach ($results as $ligne){
        $gpid=$ligne["gpid"];
        $aclid=$ligne["aclid"];
        $js="Loadjs('fw.proxypac.rules.php?ruleid-js=$aclid&function=$function')";
        $array[$gpid][$aclid]=$tpl->td_href("Proxy.PAC: {$ligne["rulename"]}",null,$js);
    }

    $results = $q->QUERY_SQL("SELECT 
                wpad_rules.rulename,
				wpad_rules.enabled,
				wpad_rules.ID as aclid,
				wpad_white_link.gpid 
				FROM wpad_sources_link,wpad_rules 
				WHERE wpad_white_link.aclid=wpad_rules.ID");

    if(!$q->ok){
        VERBOSE($q->mysql_error,__LINE__);
    }
    foreach ($results as $ligne){
        $gpid=$ligne["gpid"];
        $aclid=$ligne["aclid"];
        $js="Loadjs('fw.proxypac.rules.php?ruleid-js=$aclid&function=$function')";
        $array[$gpid][$aclid]=$tpl->td_href("Proxy.PAC: {$ligne["rulename"]}",null,$js);
    }

    return $array;
}

