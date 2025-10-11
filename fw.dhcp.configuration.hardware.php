<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["route-delete-js"])){route_delete_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["rule-delete"])){rule_delete();exit;}
if(isset($_GET["rule-enable"])){rule_enable();exit;}
if(isset($_POST["rule-delete"])){rule_delete_perform();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["hardware-start"])){hardware_start();exit;}
if(isset($_GET["harware-list"])){harware_list();exit;}
if(isset($_GET["hardware-unlink"])){hardware_unlink();exit;}
if(isset($_GET["items-js"])){items_js();exit;}
if(isset($_GET["items-browse"])){items_browse();exit;}
if(isset($_GET["items-add"])){items_add();exit;}
if(isset($_GET["search"])){items_search();exit;}
js();


function js(){
    $interface      = $_GET["interface"];
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");

    if(!$q->FIELD_EXISTS("dhcpd_hardware","enabled")){
        $q->QUERY_SQL("ALTER TABLE dhcpd_hardware ADD `enabled` INTEGER NOT NULL DEFAULT 1");
        if(!$q->ok){
            $tpl->js_mysql_alert($q->mysql_error);
            return;
        }
    }




    $tpl->js_dialog2("{hardware_attribution}: $interface","$page?popup=$interface",650);
}
function items_js(){
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $hardware_id    = intval($_GET["items-js"]);
    $ruleid         = trim($_GET["ruleid"]);

    $tpl->js_dialog4("{browse_hardwares}", "$page?items-browse=$hardware_id&ruleid=$ruleid",555);

}

function items_browse(){
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $hardware_id    = intval($_GET["items-browse"]);
    $ruleid         = trim($_GET["ruleid"]);

    echo $tpl->search_block($page,"sqlite:/home/artica/SQLITE/dhcpd.db","dhcpd_MacsList",null,"&hardware-id=$hardware_id&ruleid=$ruleid");

}

function items_search(){
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $hardware_id    = intval($_GET["hardware-id"]);
    $ruleid         = trim($_GET["ruleid"]);
    $search         = $_GET["search"];
    $querys         = $tpl->query_pattern("$search");
    $MAX            = $querys["MAX"];
    if($MAX>100){$MAX=100;}
    $t              = time();
    $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");



    if($q->COUNT_ROWS("dhcpd_MacsList")==0){
        echo "<div class='alert alert-danger'>No items in table</div>";
    }

    if($querys["Q"]==null){
        $querys["Q"]=" WHERE description LIKE '%{$querys["S"]}%'";
    }
    $sql="SELECT * FROM dhcpd_MacsList {$querys["Q"]} ORDER BY description LIMIT $MAX";
    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error</div>";
    }

    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"20\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
    $html[]="<th data-sortable=false>{description}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    $td1="style='width:1%' nowrap";
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $bt_color       = "btn-primary";
        $idrow          = md5(serialize($ligne));
        $ID             = $ligne["ID"];
        $MacID          = $ligne["MacID"];
        $description    = $ligne["description"];

        $button_select=$tpl->button_autnonome("&nbsp;{select}",
            "Loadjs('$page?items-add=$ID&hardware-id=$hardware_id&ruleid=$ruleid&md=$idrow')",
            "fas fa-hand-pointer","",0,$bt_color,"small");

        $html[]="<tr class='$TRCLASS' id='$idrow'>";
        $html[]="<td $td1><i class=\"fas fa-ethernet\"></i></td>";
        $html[]="<td $td1>$MacID</td>";
        $html[]="<td>$description</td>";
        $html[]="<td $td1>$button_select</td>";
    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";

    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);


}

function items_add(){
    $ID             = intval($_GET["items-add"]);
    $ruleid         = trim($_GET["ruleid"]);
    $hardware_id    = intval($_GET["hardware-id"]);
    $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $tpl            = new template_admin();
    $md             = $_GET["md"];
    $page           = CurrentPageName();

    $sql = "CREATE TABLE IF NOT EXISTS `dhcpd_hardware_list` (
            `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
            `dhcpd_hardware_id` INTEGER NOT NULL,
            `dhcpd_item_id` INTEGER NOT NULL
            )";

    $q->QUERY_SQL($sql);

    if($hardware_id==0){
        $tpl->js_mysql_alert("Hardware ID == 0 !");
        return;
    }

    $q->QUERY_SQL("INSERT OR IGNORE INTO dhcpd_hardware_list (dhcpd_hardware_id,dhcpd_item_id) VALUES ('$hardware_id','$ID')");
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return;
    }

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "LoadAjax('hardware-table-items-$hardware_id','$page?harware-list=$hardware_id&ruleid=$ruleid');\n";

}
function rule_enable():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["rule-enable"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $ligne      = $q->mysqli_fetch_array("SELECT `enabled` FROM dhcpd_hardware WHERE ID='$ID'");

    if(intval($ligne["enabled"])==1){
        $q->QUERY_SQL("UPDATE dhcpd_hardware SET enabled=0 WHERE ID=$ID");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
        return false;
    }
    $q->QUERY_SQL("UPDATE dhcpd_hardware SET enabled=1 WHERE ID=$ID");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    return true;
}

function rule_delete(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["rule-delete"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $ligne      = $q->mysqli_fetch_array("SELECT `PooName` FROM dhcpd_hardware WHERE ID='$ID'");
    $PoolName   = $ligne["PooName"];
    $md         = $_GET["md"];
    $pool_name  = $tpl->_ENGINE_parse_body("{pool_name}");
    $tpl->js_confirm_delete("$pool_name $PoolName","rule-delete",$ID,"$('#$md').remove();");

}

function rule_delete_perform(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_POST["rule-delete"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $sql        = "DELETE FROM dhcpd_hardware_list WHERE dhcpd_hardware_id=$ID";
    $sql2       = "DELETE FROM dhcpd_hardware WHERE ID=$ID";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return;}

    $q->QUERY_SQL($sql2);
    if(!$q->ok){echo $q->mysql_error;return;}
}

function rule_js():bool{
    header("content-type: application/x-javascript");
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $title      = $tpl->javascript_parse_text("{new_pool}");
    $ID         = intval($_GET["rule-js"]);
    $ruleid     = trim($_GET["ruleid"]);;
    $q          = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $RouteName  = "{new_pool}";


    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT `PooName` FROM dhcpd_hardware WHERE ID='$ID'");
        $title=$ligne["PooName"];
        $RouteName="{pool}";
        if(!$q->ok){echo $q->mysql_error_html();}
        return $tpl->js_dialog3("$RouteName: $title", "$page?rule-tabs=$ID&ruleid=$ruleid");
    }
    return $tpl->js_dialog3("$RouteName: $title", "$page?rule-popup=$ID&ruleid=$ruleid",550);

}

function rule_tabs():bool{
    $ruleid     = trim($_GET["ruleid"]);;
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["rule-tabs"]);

    $array["{hardwares}"]="$page?hardware-start=$ID&ruleid=$ruleid";
    $array["{pool}"]="$page?rule-popup=$ID&ruleid=$ruleid";
    echo $tpl->tabs_default($array);
    return true;
}

function hardware_start():bool{
    $hardware_id     = intval($_GET["hardware-start"]);
    $ruleid          = $_GET["ruleid"];
    $page            = CurrentPageName();
    $tpl             = new template_admin();

    $html[]="<div id='hardware-table-items-$hardware_id'></div>";
    $html[]="<script>LoadAjax('hardware-table-items-$hardware_id','$page?harware-list=$hardware_id&ruleid=$ruleid');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function hardware_unlink():bool{
    $tpl            = new template_admin();
    $ID             = intval($_GET["hardware-unlink"]);
    $hardware_id    = intval($_GET["hardware-id"]);
    $ruleid         = $_GET["ruleid"];
    $md             = $_GET["md"];
    $page           = CurrentPageName();
    $sql            = "DELETE FROM dhcpd_hardware_list WHERE ID=$ID";
    $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return false;
    }

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    return true;

}

function harware_list(){
    $tpl            = new template_admin();
    $q              = new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $hardware_id    = intval($_GET["harware-list"]);
    $page           = CurrentPageName();
    $ruleid         = $_GET["ruleid"];



    $topbuttons[] = array("Loadjs('$page?items-js=$hardware_id&ruleid=$ruleid');", ico_plus, "{link_hardware}");

    $topbuttons[] = array("LoadAjax('hardware-table-items-$hardware_id','$page?harware-list=$hardware_id&ruleid=$ruleid');", ico_refresh, "{reload}");

    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="</div>";

    $html[]="<table id='table-hardwarelinked-{$hardware_id}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{hardwares}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{description}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $sql="SELECT *  FROM `dhcpd_hardware_list` WHERE dhcpd_hardware_id='{$hardware_id}'";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $TRCLASS=null;$ligne=null;
    $td1="style='width:1%' nowrap";
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID                 = $ligne["ID"];
        $dhcpd_item_id      = $ligne["dhcpd_item_id"];
        $md                 = md5(serialize($ligne));

        $ligne2=$q->mysqli_fetch_array("SELECT * FROM dhcpd_MacsList WHERE ID=$dhcpd_item_id");

        $dhcpd_item_name    = $ligne2["description"];
        $dhcpd_class        = $ligne2["MacID"];
        $delete             = $tpl->icon_unlink("Loadjs('$page?hardware-unlink={$ID}&hardware-id=$hardware_id&ruleid={$ruleid}&md=$md');","AsSystemAdministrator");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $td1><i class=\"fas fa-ethernet\"></i></td>";
        $html[]="<td $td1>$dhcpd_class</td>";
        $html[]="<td>$dhcpd_item_name</td>";
        $html[]="<td $td1>$delete</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-hardwarelinked-{$hardware_id}').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


}


function rule_popup():bool{
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $tpl=new template_admin();
    $btname="{add}";
    $ID=intval($_GET["rule-popup"]);
    $ruleid=$_GET["ruleid"];
    $title="{new_pool}";

    if($ID>0){
        $btname="{apply}";
        $ligne=$q->mysqli_fetch_array("SELECT * FROM dhcpd_hardware WHERE ID='$ID'");
        if(!$q->ok){echo $q->mysql_error_html();}
        $title=$ligne["PooName"];
    }

    $dhcp       = new dhcpd(0, 1, $ruleid);
    preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#",$dhcp->range1,$re);
    preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#",$dhcp->range2,$ri);

    if($ligne["range_from"]==null){$ligne["range_from"]="{$re[1]}.{$re[2]}.{$re[3]}.5";}
    if($ligne["range_to"]==null){$ligne["range_to"]="{$ri[1]}.{$ri[2]}.{$ri[3]}.90";}

    if(intval($ligne["max-lease-time"])<5){
        $ligne["max-lease-time"]=7200;
    }
    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("ruleid", "{$_GET["ruleid"]}");
    $form[]=$tpl->field_text("PooName", "{pool_name}", $ligne["PooName"]);
    $form[]=$tpl->field_text("range_from", "{ipfrom}", $ligne["range_from"]);
    $form[]=$tpl->field_text("range_to", "{ipto}", $ligne["range_to"]);
    $form[]=$tpl->field_numeric("max-lease-time","{max_lease_time} ({seconds})",$ligne["max-lease-time"],"{max_lease_time_text}");
    $form[]=$tpl->field_ipaddr("DNS1", "{DNS_1}", $ligne["DNS1"]);
    $form[]=$tpl->field_ipaddr("DNS2", "{DNS_2}", $ligne["DNS2"]);
    $form[]=$tpl->field_ipaddr("ntp_server", "{ntp_server} ({optional})", $ligne["ntp_server"]);



    $js[]="dialogInstance3.close();";
    $js[]="LoadAjax('hardware-table-dhcp-{$_GET["ruleid"]}','$page?table={$_GET["ruleid"]}');";


    $html=$tpl->form_outside("$ruleid: $title", @implode("\n", $form),null,$btname,@implode("", $js),"AsSystemAdministrator");
    echo $html;
    return true;
}

function popup(){
    $interface=$_GET["popup"];
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<div id='hardware-table-dhcp-$interface'></div>";
    $html[]="<script>LoadAjax('hardware-table-dhcp-$interface','$page?table=$interface');</script>";
    echo $tpl->_ENGINE_parse_body($html);


}


function rule_save(){

    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $Interface=$_POST["ruleid"];

    unset($_POST["ruleid"]);
    unset($_POST["ID"]);
    $_POST["interface"]=$Interface;


    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");

    foreach ($_POST as $key=>$value){
        $upd[]="`$key`='$value'";
        $Fields[]="`$key`";
        $Datas[]="'$value'";

    }


    if($ID==0){
        $Fields[]="`enabled`";
        $Datas[]="'1'";

        $sql="INSERT INTO dhcpd_hardware (".@implode(",",$Fields).") VALUES (".@implode(",",$Datas).")";

    }else{
        $sql="UPDATE dhcpd_hardware SET ".@implode(",",$upd). " WHERE ID=$ID";

    }


    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}

}


function table(){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $MAIN_ID=trim($_GET["table"]);
    $RefreshAfter="LoadAjax('dhcp-service','fw.dhcp.configuration.php?status=yes');";
    $page=CurrentPageName();

    $jsafter=$tpl->framework_buildjs("/dhcpd/service/restart",
        "dhcpd.progress","dhcpd.progress.txt","progress-for-static-routes-$MAIN_ID");

    if($EnableKEA==1){
        $jsafter=$tpl->framework_buildjs(
            "/kea/dhcp/reload","kea.service.progress",
            "kea.service.log","progress-for-static-routes-$MAIN_ID",
            $RefreshAfter,$RefreshAfter);
    }


    $DHCP4_PARSER_FAIL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCP4_PARSER_FAIL");
    if(strlen($DHCP4_PARSER_FAIL)>5){
        if(preg_match("#DHCP4_PARSER_FAIL\s+(.+?)\(#",$DHCP4_PARSER_FAIL ,$re)){$DHCP4_PARSER_FAIL =$re[1];}
        $DHCP4_PARSER_FAIL=str_replace("failed to create or run parser for configuration element","",$DHCP4_PARSER_FAIL);
        $DHCP4_PARSER_FAIL=$tpl->div_error($DHCP4_PARSER_FAIL);
    }
    $html[]="<div id='progress-for-static-routes-$MAIN_ID'>$DHCP4_PARSER_FAIL</div>";

    $topbuttons[] = array("Loadjs('$page?rule-js=0&ruleid=$MAIN_ID');", ico_plus, "{new_pool}");

    $topbuttons[] = array("LoadAjax('hardware-table-dhcp-$MAIN_ID','$page?table=$MAIN_ID');", ico_refresh, "{reload}");

    $topbuttons[] = array("$jsafter", ico_save, "{apply_configuration}");

    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="</div>";

    $html[]="<table id='table-destsrc-{$MAIN_ID}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{pool_name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap style='text-align: right'>{ipfrom}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap style='text-align: right'>{ipto}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{items}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{enabled}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $sql="SELECT *  FROM `dhcpd_hardware` WHERE interface='{$MAIN_ID}' ORDER BY PooName";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $PooName        = $ligne["PooName"];
        $range_from     = $ligne["range_from"];
        $range_to       = $ligne["range_to"];
        $ID             = $ligne["ID"];
        $md             = md5(serialize($ligne));
        $PooName        = $tpl->td_href($PooName,$PooName,"Loadjs('$page?rule-js={$ligne["ID"]}&ruleid=$MAIN_ID');");
        $delete         = $tpl->icon_delete("Loadjs('$page?rule-delete={$ligne["ID"]}&ruleid=$MAIN_ID&md=$md');","AsSystemAdministrator");
        $enabled        = $tpl->icon_check($ligne["enabled"],"Loadjs('$page?rule-enable={$ligne["ID"]}&ruleid=$MAIN_ID&md=$md');","AsSystemAdministrator");

        $ligne2=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM dhcpd_hardware_list WHERE dhcpd_hardware_id=$ID");
        $Count=intval($ligne2["tcount"]);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><strong>$PooName</strong></td>";
        $html[]="<td style='width:1%;text-align: right' id='$index' nowrap>$range_from</td>";
        $html[]="<td style='width:1%;text-align: right' nowrap>$range_to</td>";
        $html[]="<td style='width:1%' nowrap class='center'>$Count</td>";
        $html[]="<td style='width:1%' nowrap>$enabled</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
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
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-destsrc-{$MAIN_ID}').footable( { 	\"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}