<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-table2"])){popup_table2();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ipaddr"])){rule_save();exit;}
if(isset($_GET["enable-rule-js"])){enable_feature();}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}



function enable_feature():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["serviceid"]);
    $enable=intval($_GET["enable-rule-js"]);
    $sockngix=new socksngix(($serviceid));
    $sockngix->SET_INFO("FilterUserAgents",$enable);
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global();
    $f[]="LoadAjaxSilent('masquerade-sources-$serviceid','$page?top-buttons=$serviceid');";
    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    return admin_tracks("Turn feature to $enable for deny User-Agents on  $get_servicename reverse-proxy site");

}

function service_js():bool{
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{source_network}","$page?popup-main=$serviceid");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $function2=$_GET["function2"];
    $function=$_GET["function"];
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    return $tpl->js_dialog5("{source_network}: $title","$page?popup-rule=$rule&serviceid=$serviceid&function=$function&function2=$function2",550);
}


function rule_remove():bool{
    $md=$_GET["md"];
    $ID=intval($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT networks FROM pnic_bridges_src WHERE ID='$ID'");
    $networks=$ligne['networks'];
    $q->QUERY_SQL("DELETE FROM pnic_bridges_src WHERE ID='$ID'");
    $RuleName=get_servicename($serviceid);
    echo "$('#$md').remove();\n";
    echo refresh_global();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/routers/build");
    return admin_tracks("Remove $networks for masquerading rule $RuleName");


}
function get_servicename($ID):string{
    $ID=intval($ID);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM pnic_bridges WHERE ID='$ID'");
    return $ligne['rulename'];

}


function rule_enable():bool{
    $ID=intval($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT networks,enabled FROM pnic_bridges_src WHERE ID='$ID'");
    $sValue=intval($ligne['enabled']);
    $network=$ligne["networks"];
    if($sValue==1){
        $q->QUERY_SQL("UPDATE pnic_bridges_src SET enabled=0 WHERE ID='$ID'");
        $text="disable";
    }else{
        $q->QUERY_SQL("UPDATE pnic_bridges_src SET enabled=1 WHERE ID='$ID'");
        $text="enable";
    }
    $RuleName=get_servicename($serviceid);
    echo refresh_global();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/routers/build");
    return admin_tracks("$text $network for masquerading rule $RuleName");
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $bt="{add}";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("ipaddr","{source_network}","",true);
    $html[]=$tpl->form_outside(null,$form,null,$bt,refresh_global().";dialogInstance5.close();","AsFirewallManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function refresh_global():string{

    $function2=$_GET["function2"];
    $function=$_GET["function"];
    $f=array();
    if(strlen($function)>2) {
        $f[] = "$function();";
    }
    if(strlen($function2)>2) {
        $f[] = "$function2();";
    }

    return @implode(";",$f);
}




function rule_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ipaddr=$_POST["ipaddr"];

    if(trim($ipaddr)==null){return false;}
    $q->QUERY_SQL("INSERT INTO pnic_bridges_src (networks,pnicid) VALUES('$ipaddr','$serviceid')");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $get_servicename=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/routers/build");
    return admin_tracks("Add a new masquerade source for rule $get_servicename");

}

function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $function=$_GET["function"];
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid&function2=$function')</script>";
    return true;
}



function popup_table():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $function2=$_GET["function2"];
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    echo "<div id='masquerade-sources-$serviceid' style='margin-bottom:10px;margin-top:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&popup-table2=$serviceid&function2=$function2");

    return true;
}
function OnlyActive():bool{
    $function=$_GET["function"];
    $Key=basename(__FILE__)."OnlyActive";
    if(!isset($_SESSION[$Key])){
        $_SESSION[$Key]=true;
    }else{
        unset($_SESSION[$Key]);
    }
    header("content-type: application/x-javascript");
    echo "$function();";
    return true;
}

function top_buttons():bool{
    $serviceid  = intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    if($serviceid==0){
        echo $tpl->div_error("ServiceID === 0");
        return true;
    }
    $topbuttons[] = array("Loadjs('$page?rule-js=0&serviceid=$serviceid&function=$function&function2=$function2')", ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&serviceid=$serviceid&function=$function&function2=$function2')", ico_filter, "{OnlyActive}");
    $topbuttons[] = array("Loadjs('$page?disableall=yes&serviceid=$serviceid&function=$function&function2=$function2')", ico_disabled, "{disable_all}");
    $topbuttons[] = array("Loadjs('$page?enableall=yes&serviceid=$serviceid&function=$function&function2=$function2')", ico_check, "{enable_all}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function rule_disable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

    $q->QUERY_SQL("UPDATE pnic_bridges_src SET enabled=0 WHERE pnicid=$serviceid");
    $get_servicename=get_servicename($serviceid);
    echo refresh_global();
    echo "$function();";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/routers/build");
    return admin_tracks("Disable all network sources from masqerading rule $get_servicename");
}
function rule_enable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $get_servicename=get_servicename($serviceid);
    $q->QUERY_SQL("UPDATE pnic_bridges_src SET enabled=1 WHERE pnicid=$serviceid");
    echo refresh_global();
    echo "$function();";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/routers/build");
    return admin_tracks("Enable all network sources from masqerading rule $get_servicename");
}
function popup_table2():bool{
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $function =$_GET["function"];
    $function2=$_GET["function2"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $tableid    = time();
    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="</div>";

    $search=$_GET["search"];

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{ipsrc}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $OnlyActive=false;
    $KeyActive=basename(__FILE__)."OnlyActive";
    if(isset($_SESSION[$KeyActive])){
        $OnlyActive=true;
    }
    $QS="";
    if(strlen($search)>0){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $QS=" AND (networks LIKE '%$search%')";
    }

    $results=$q->QUERY_SQL("SELECT * FROM pnic_bridges_src WHERE pnicid=$serviceid$QS");
    $c=0;
    foreach ($results as $index=>$ligne){
        $enabled=$ligne["enabled"];

        if($OnlyActive){
            if($enabled==0){continue;}
        }
        $pattern=$ligne["networks"];
        $ID=$ligne["ID"];
        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}
        $pattern=htmlentities($pattern);
        $md=md5($pattern);

        $enable=$tpl->icon_check($enabled,"Loadjs('$page?pattern-enable=$ID&function2=$function2')","","AsFirewallManager");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$ID&md=$md&serviceid=$serviceid&function2=$function2')","AsFirewallManager");

        $c++;

        if($c>250){break;}

    $html[]="<tr id='$md'>
				<td style='width:100%'>$pattern</td>
				<td style='width:1%'  nowrap >$enable</td>
				<td style='width:1%' nowrap >$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('masquerade-sources-$serviceid','$page?top-buttons=$serviceid&function=$function&function2=$function2');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        }