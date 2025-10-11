<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");



if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}

js();

function js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog4("{propagate_routes}","$page?popup-main=yes");
}
function rule_js(){
    $ID       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{area}: $ID";
    if($ID==0){$title="{area} {new_rule}";}
    $tpl->js_dialog5("$title","$page?popup-rule=$ID&");
}
function compile_js_progress():string{
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/ospfd.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/ospfd.log";
    $ARRAY["CMD"]="ospfd.php?restart=yes";
    $ARRAY["TITLE"]="{restarting}";
    $ARRAY["AFTER"]="LoadAjax('ospfd-section-status','fw.network.ospf.php?status2=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=ospfd-section-progress')";
    return $jsafter;
}

function rule_remove():bool{
    $ruleid     = intval($_GET["pattern-remove"]);
    $id         = $_GET["ID"];
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM ospf_networks WHERE ID=$ruleid");

    $network    = $ligne["network"];
    $q->QUERY_SQL("DELETE FROM ospf_networks WHERE ID=$ruleid");
    if (!$q->ok) {echo "jserror:" . $tpl->javascript_parse_text($q->mysql_error);return false;}
    admin_tracks("OSPF, remove area $network");

    echo "$('#$id').remove();\n";
    echo "LoadAjax('ospfd-section-status','fw.network.ospf.php?status2=yes');";
    return true;

}

function rule_enable():bool{
    $ruleid=intval($_GET["pattern-enable"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $tpl        = new template_admin();
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM ospf_networks WHERE ID=$ruleid");
    if(intval($ligne["enabled"])==1) {
        $q->QUERY_SQL("UPDATE ospf_networks SET enabled='0' WHERE ID=$ruleid");
        if (!$q->ok) {
            echo "jserror:" . $tpl->javascript_parse_text($q->mysql_error);
            return false;
        }
        admin_tracks("OSPF, turn area {$ligne["network"]} to disabled");
        return true;
    }
    $q->QUERY_SQL("UPDATE ospf_networks SET enabled='1' WHERE ID=$ruleid");
    if (!$q->ok) {echo "jserror:" . $tpl->javascript_parse_text($q->mysql_error);return false;}
    admin_tracks("OSPF, turn area {$ligne["network"]} to enabled");
    return true;
}

function rule_popup(){
    $ID         = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $q          = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM ospf_networks WHERE ID=$ID");
    $bt         = "{apply}";
    if($ID==0){
        $ligne["enabled"] = 1;
        $ligne["realm"] = "0.0.0.0";
        $ligne["network"] = "192.168.0.0/16";
        $bt="{add}";
    }

    $jsrestart="dialogInstance5.close();LoadAjax('main-popup-areas','$page?popup-table=yes');LoadAjax('ospfd-section-status','fw.network.ospf.php?status2=yes');";
    $form[]=$tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enable}",$ligne["enabled"]);
    $form[]=$tpl->field_text("network","{network}",$ligne["network"]);
    $form[]=$tpl->field_text("realm","{area}",$ligne["realm"]);
    $html[]=$tpl->form_outside("{area} $ID",$form,"{ospf_network_explain}",$bt,$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}

function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $ID     = intval($_POST["ruleid"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $enable     = $_POST["enabled"];
    $network    = $_POST["network"];
    $realm      = $_POST["realm"];

    if($ID==0){
        $q->QUERY_SQL("INSERT INTO ospf_networks
        (network, realm, enabled) VALUES ( '$network','$realm','$enable')");
        if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
        admin_tracks("Created a new OSPF area $network/$realm");
        return true;
    }

    $q->QUERY_SQL("UPDATE ospf_networks 
        SET enabled='$enable',realm='$realm',network='$network' WHERE ID=$ID");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
    admin_tracks("Modified OSPF area $network/$realm");
    return true;
}

function popup_main(){
    $page       = CurrentPageName();
    echo "<div id='main-popup-areas'></div>
    <script>LoadAjax('main-popup-areas','$page?popup-table=yes')</script>";
}

function popup_table(){

    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $q          = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $tableid    = time();
    $compile_js_progress=compile_js_progress();

    $html[]="<div id='progress-compile-replace-areas'></div>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=0');\">
	<i class='fa fa-plus'></i> {new_rule} </label>";
    $html[]="<label class=\"btn btn btn-warning\" OnClick=\"$compile_js_progress\">
	<i class='fa fa-save'></i> {apply_parameters_to_the_system} </label>";
    $html[]="</div>";

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{network}</th>
        	<th nowrap>{areas}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $results=$q->QUERY_SQL("SELECT * FROM ospf_networks ORDER BY network");

    foreach ($results as $num=>$ligne){
        $ID=$ligne["ID"];
        $id=md5(serialize($ligne));
        $enable=intval($ligne["enabled"]);
        $network=trim($ligne["network"]);
        $realm=$ligne["network"];
        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$ID')","","AsSystemAdministrator");
    $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$ID&id=$id')","AsSystemAdministrator");
        $network=$tpl->td_href($network,"","Loadjs('$page?rule-js=$ID');");

    $html[]="<tr id='$id'>
				<td width=50%>$network</td>
				<td width=50% >$realm</td>
				<td width=1%  nowrap >$enable</td>
				<td width=1%  nowrap >$delete</td>
				</tr>";

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
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="$(document).ready(function() { $('#$tableid').footable( { \"filtering\": { \"enabled\": true";
        $html[]="},\"sorting\": { \"enabled\": true },";
        $html[]="\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        }