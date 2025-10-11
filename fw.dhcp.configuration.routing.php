<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["route-move-js"])){route_move_js();exit;}
if(isset($_GET["route-delete-js"])){route_delete_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_POST["ID"])){rule_save();exit;}

js();


function js(){
    $interface=$_GET["interface"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{routing_rules}: $interface","$page?popup=$interface");
}
function rule_js(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{new_rule}");
    $ID=intval($_GET["rule-js"]);
    $ruleid=trim($_GET["ruleid"]);;
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $RouteName="{new_route}";


    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT `network` FROM dhcpd_routes WHERE ID='$ID'");
        $title=$ligne["pattern"];
        $RouteName="{route}";
        if(!$q->ok){echo $q->mysql_error_html();}

    }


    $tpl->js_dialog3("$RouteName: $title", "$page?rule-popup=$ID&ruleid=$ruleid");

}
function rule_popup(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $tpl=new template_admin();
    $btname="{add}";
    $ID=intval($_GET["rule-popup"]);
    $ruleid=$_GET["ruleid"];
    $title="{new_route}";

    if($ID>0){
        $btname="{apply}";
        $ligne=$q->mysqli_fetch_array("SELECT * FROM dhcpd_routes WHERE ID='$ID'");
        if(!$q->ok){echo $q->mysql_error_html();}
        $title=$ligne["pattern"];
    }


    if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=0;}
    if(!is_numeric($ligne["metric"])){$ligne["metric"]=0;}

    $form[]=$tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_hidden("ruleid", "{$_GET["ruleid"]}");
    $form[]=$tpl->field_numeric("zOrder","{order}",$ligne["zOrder"]);
    $form[]=$tpl->field_text("network", "{item} ({address}/{network2})", $ligne["network"]);
    $form[]=$tpl->field_text("gateway", "{gateway}", $ligne["gateway"]);

    $js[]="dialogInstance3.close();";
    $js[]="LoadAjax('routing-table-dhcp-{$_GET["ruleid"]}','$page?table={$_GET["ruleid"]}');";


    $html=$tpl->form_outside("$ruleid: $title {$ligne["network"]}", @implode("\n", $form),null,$btname,@implode("", $js),"ASDCHPAdmin");
    echo $html;

}

function popup(){
    $interface=$_GET["popup"];
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<div id='routing-table-dhcp-$interface'></div>";
    $html[]="<script>LoadAjax('routing-table-dhcp-$interface','$page?table=$interface');</script>";
    echo $tpl->_ENGINE_parse_body($html);


}
function route_delete_js(){
    header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $tpl=new template_admin();
    $q->mysqli_fetch_array("DELETE FROM dhcpd_routes WHERE ID='{$_GET["route-delete-js"]}'");
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    echo "$('#{$_GET["md"]}').remove();\n";
}
function route_move_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $ID=$_GET["route-move-js"];
    $dir=$_GET["dir"];
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM dhcpd_routes WHERE ID='$ID'");

    $zOrder=$ligne["zOrder"];
    if($dir=="up"){
        $NewzOrder=$zOrder-1;
    }else{
        $NewzOrder=$zOrder+1;
    }

    if($NewzOrder<0){$NewzOrder=0;}


    $q->QUERY_SQL("UPDATE dhcpd_routes SET zOrder='$zOrder' WHERE zOrder='$NewzOrder' AND ID<>'$ID'");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return null;}
    $q->QUERY_SQL("UPDATE dhcpd_routes SET zOrder='$NewzOrder' WHERE ID='$ID'");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return null;}

    $results=$q->QUERY_SQL("SELECT * FROM dhcpd_routes WHERE interface='{$_GET["ruleid"]}' ORDER BY zOrder");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return null;}
    $c=0;
    foreach ($results as $index=>$ligne){
        $c++;
        $q->QUERY_SQL("UPDATE dhcpd_routes SET zOrder='$c' WHERE ID='{$ligne["ID"]}'");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return null;}
    }
return null;


}
function rule_save(){

    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);

    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");

    if(!$q->FIELD_EXISTS("dhcpd_routes","zOrder")){
        $q->QUERY_SQL("ALTER TABLE dhcpd_routes ADD zOrder INTEGER NOT NULL DEFAULT 0");
    }

    if($ID==0){
        $sql="INSERT INTO dhcpd_routes (`gateway`,`network`,`interface`,`zOrder`)
		VALUES('{$_POST["gateway"]}','{$_POST["network"]}','{$_POST["ruleid"]}','{$_POST["zOrder"]}');";
    }else{
        $sql="UPDATE dhcpd_routes SET 
		`zOrder`='{$_POST["zOrder"]}', 
		`network`='{$_POST["network"]}',
		`gateway`='{$_POST["gateway"]}' WHERE `ID`='$ID'";

    }


    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}

}


function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");

    $MAIN_ID=trim($_GET["table"]);
    $gateway=$tpl->_ENGINE_parse_body("{next_hope}");
    $items=$tpl->_ENGINE_parse_body("{items}");
    $order=$tpl->javascript_parse_text("{order}");
    $new_rule=$tpl->_ENGINE_parse_body("{new_rule}");



    $page=CurrentPageName();

    $jsafter=$tpl->framework_buildjs(
        "/kea/dhcp/reload","kea.service.progress",
        "kea.service.log","progress-for-static-routes-$MAIN_ID","LoadAjax('dhcp-service','fw.dhcp.configuration.php?status=yes');","","","ASDCHPAdmin");


    $html[]=$tpl->_ENGINE_parse_body("
<div id='progress-for-static-routes-$MAIN_ID'></div>
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0&ruleid=$MAIN_ID');\"><i class='fa fa-plus'></i> $new_rule </label>
			
			<label class=\"btn btn btn-info\" OnClick=\"LoadAjax('routing-table-dhcp-$MAIN_ID','$page?table=$MAIN_ID');\"><i class='fas fa-redo'></i> {reload} </label>
			
			<label class=\"btn btn btn-warning\" OnClick=\"$jsafter\"><i class='fas fa-file-check'></i> {apply_configuration} </label>
			</div>");



    $html[]="<table id='table-destsrc-{$MAIN_ID}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$order</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$items</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>$gateway</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>mv</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $sql="SELECT *  FROM `dhcpd_routes` WHERE interface='{$MAIN_ID}' ORDER BY zOrder";
    $results = $q->QUERY_SQL($sql);

    $TRCLASS=null;$ligne=null;

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $md=md5(serialize($ligne));
        $network=$tpl->td_href($ligne["network"],null,"Loadjs('$page?rule-js={$ligne["ID"]}&ruleid=$MAIN_ID');");

        $down=$tpl->icon_down("Loadjs('$page?route-move-js={$ligne["ID"]}&ruleid={$MAIN_ID}&dir=down');","ASDCHPAdmin");
        $up=$tpl->icon_up("Loadjs('$page?route-move-js={$ligne["ID"]}&ruleid={$MAIN_ID}&dir=up');","ASDCHPAdmin");
        $delete=$tpl->icon_delete("Loadjs('$page?route-delete-js={$ligne["ID"]}&ruleid={$MAIN_ID}&md=$md');","ASDCHPAdmin");

        if($ligne["gateway"]==null){$ligne["gateway"]=$tpl->icon_nothing();}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' class='center' nowrap>{$ligne["zOrder"]}</td>";
        $html[]="<td><strong>$network</strong></td>";
        $html[]="<td style='width:1%' nowrap><strong>{$ligne["gateway"]}</strong></td>";
        $html[]="<td style='width:1%' nowrap>$up&nbsp;$down</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";


    }


    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-destsrc-{$MAIN_ID}').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}