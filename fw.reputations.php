<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.modsectools.inc");
if(isset($_GET["check-rule-perform"])){checkrule_perform();exit;}
if(isset($_GET["check-rule-popup"])){checkrule_popup();exit;}
if(isset($_GET["check-rule"])){checkrule_js();exit;}
if(isset($_POST["reset"])){reset_perform();exit;}
if(isset($_GET["reset-js"])){reset_js();exit;}
if(isset($_GET["group-move-js"])){group_move_js();exit;}
if(isset($_POST["delete-rule"])){delete_rule_perform();exit;}
if(isset($_GET["delete-rule"])){delete_rule();exit;}
if(isset($_GET["enable-rule"])){enable_rule();exit;}
if(isset($_POST["delete-group"])){delete_group_perform();exit;}
if(isset($_GET["delete-group"])){delete_group();exit;}
if(isset($_GET["link-group"])){group_link();exit;}
if(isset($_GET["group-link-popup"])){group_link_popup();exit;}
if(isset($_GET["group-link"])){group_link_js();exit;}
if(isset($_POST["unlink-group"])){unlink_group_perform();exit;}
if(isset($_GET["unlink-group"])){unlink_group();exit;}
if(isset($_GET["enable-group"])){enable_group();exit;}
if(isset($_GET["enable-service"])){enable_service();exit;}
if(isset($_GET["delete-service"])){delete_service();exit;}
if(isset($_POST["delete-service"])){delete_service_perform();exit;}
if(isset($_POST["serviceid"])){service_save();exit;}
if(isset($_GET["service-popup"])){service_popup();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["services-search"])){services_list();exit;}
if(isset($_GET["popup-search"])){popup_search();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-tab"])){rule_tab();exit;}
if(isset($_GET["status-build"])){status_build();exit;}
if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_POST["groupid"])){group_save();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}
if(isset($_GET["groups-popup"])){groups_popup();exit;}
if(isset($_GET["groups-list"])){groups_list();exit;}
if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["group-popup"])){group_popup();exit;}
if(isset($_GET["group-tab"])){group_tab();exit;}
if(isset($_GET["services-popup"])){services_popup();exit;}
if(isset($_GET["services-button"])){services_button();exit;}

page();


function reset_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $tpl->js_confirm_execute("{reset}","reset","yes","$function()");
}
function reset_perform(){
    $sock=new sockets();
    $sock->REST_API("/reputations/reset");
}
function checkrule_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["check-rule"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID='$ID'");
    $title=$ligne["rulename"];
    $tpl->js_dialog2($title, "$page?check-rule-popup=$ID&");
    return true;
}
function checkrule_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["check-rule-popup"]);
    echo $tpl->search_block($page,"","","","&check-rule-perform=$ID");
}
function checkrule_perform():bool{
    $tpl=new template_admin();
    $ipaddr=trim($_GET["search"]);
    if(strlen($ipaddr)<8){
        return false;
    }

    $modtools=new modesctools();
    $modtools->hostinfo($ipaddr,true);
    $flag="";
    if(strlen($modtools->flag)>3){
        $flag="&nbsp;<img src='img/".$modtools->flag."'>&nbsp;&nbsp;";
    }
    $tpl->table_form_field_text("{hostname}",$flag.$modtools->hostname,ico_computer);
    if(strlen($modtools->city)>1) {
        $tpl->table_form_field_text("{city}", $modtools->city, ico_city);
    }
    $tpl->table_form_field_text("{country}",$modtools->country_name,ico_location);
    $tpl->table_form_field_text("{continent}",$modtools->continent,ico_earth);
    $tpl->table_form_field_text("{isp}",$modtools->isp." AS:$modtools->asn_number",ico_networks);
    $infos=$tpl->_ENGINE_parse_body($tpl->table_form_compile());

    $ID=intval($_GET["check-rule-perform"]);
    $sock=new sockets();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID='$ID'");
    $title=$ligne["rulename"];
    $json=json_decode($sock->REST_API("/reputations/check/$ID/$ipaddr"));
    if($json->Found){
        echo $tpl->div_error("{iplisted_in_reputation}||<H1>$title</h1><H3>{found} $ipaddr</H3>")."<hr>$infos";
        return true;
    }else{
        echo $tpl->_ENGINE_parse_body($tpl->div_explain("notitle:<H1>$title</H1><h3>$ipaddr {host_not_found}</H1>"))."<hr>$infos";
    }
    echo "";
    return false;
}
function rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=intval($_GET["rule-js"]);
    $title="{new_item}";

    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID='$ID'");
        $title=$ligne["rulename"];
        $tpl->js_dialog2($title, "$page?rule-tab=$ID&function=$function");
        return true;
    }
    return $tpl->js_dialog2($title, "$page?rule-popup=$ID&function=$function");
}
function delete_group_perform():bool{

    $ID=intval($_POST["delete-group"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM rbl_reputations_group WHERE ID='$ID'");
    $GroupName=$ligne["GroupName"];
    $q->QUERY_SQL("DELETE FROM rbl_reputations_data WHERE groupid=$ID");
    $q->QUERY_SQL("DELETE FROM rbl_reputations_link WHERE groupid=$ID");
    $q->QUERY_SQL("DELETE FROM rbl_reputations_group WHERE ID=$ID");
    return admin_tracks("Removing reputation group $GroupName");

}
function unlink_group_perform():bool{
    $ID=intval($_POST["unlink-group"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupid,ruleid FROM rbl_reputations_link WHERE ID='$ID'");
    $groupid=$ligne["groupid"];
    $ruleid=$ligne["ruleid"];
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID='$ruleid'");
    $rulename=$ligne["rulename"];
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM rbl_reputations_group WHERE ID='$groupid'");
    $GroupName=$ligne["GroupName"];

    $q->QUERY_SQL("DELETE FROM rbl_reputations_link WHERE ID='$ID'");
    return admin_tracks("Unlink reputation group $GroupName from rule $rulename");

}
function delete_rule():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["delete-rule"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID='$ID'");
    $rulename=$ligne["rulename"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete("$rulename","delete-rule",$ID,"$('#$md').remove();");
}
function delete_rule_perform():bool{
    $ID=intval($_POST["delete-rule"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID='$ID'");
    $rulename=$ligne["rulename"];
    $q->QUERY_SQL("DELETE FROM rbl_reputations_link WHERE ruleid=$ID");
    $q->QUERY_SQL("DELETE FROM rbl_reputations WHERE ID=$ID");
    return admin_tracks("Remove reputation rule $rulename");
}


function delete_group():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["delete-group"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM rbl_reputations_group WHERE ID='$ID'");
    $GroupName=$ligne["GroupName"];
    $md=$_GET["md"];
    $function=$_GET["function"];
    return $tpl->js_confirm_delete("$GroupName","delete-group",$ID,"$('#$md').remove();$function();");
}
function unlink_group():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["unlink-group"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupid,ruleid FROM rbl_reputations_link WHERE ID='$ID'");
    $groupid=$ligne["groupid"];
    $ruleid=$ligne["ruleid"];
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID='$ruleid'");
    $rulename=$ligne["rulename"];
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM rbl_reputations_group WHERE ID='$groupid'");
    $GroupName=$ligne["GroupName"];

    $md=$_GET["md"];
    $function=$_GET["function"];


    $js="LoadAjax('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function');";
    return $tpl->js_confirm_execute("{unlink} $GroupName {from} $rulename","unlink-group",$ID,"$('#$md').remove();$function();$js");
}
function delete_service():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["delete-service"]);
    $md=$_GET["md"];
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT uri FROM rbl_reputations_data WHERE ID='$ID'");
    $uri=$ligne["uri"];
    return $tpl->js_confirm_delete($uri,"delete-service",$ID,"$('#$md').remove();$function()");
}
function enable_group():bool{
    $ID=intval($_GET["enable-group"]);
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled,GroupName FROM rbl_reputations_group WHERE ID='$ID'");
    $enabled=$ligne["enabled"];
    $GroupName=$ligne["GroupName"];
    if($enabled==1){
        $q->QUERY_SQL("UPDATE rbl_reputations_group SET enabled=0 WHERE ID='$ID'");
        echo "$function();";
        return admin_tracks("Disable reputation group $GroupName");


    }
    $q->QUERY_SQL("UPDATE rbl_reputations_group SET enabled=1 WHERE ID='$ID'");
    echo "$function();";
    return admin_tracks("Enable reputation group $GroupName");
}

function enable_rule():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["enable-rule"]);
    if($ID==0){
        echo $tpl->js_error("ID is zero!");
        return false;

    }
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename,enabled FROM rbl_reputations WHERE ID='$ID'");
    $enabled=$ligne["enabled"];
    $rulename=$ligne["rulename"];
    if($enabled==1){
        $q->QUERY_SQL("UPDATE rbl_reputations SET enabled=0 WHERE ID='$ID'");
        return admin_tracks("Disable reputation rule $rulename");

    }
    $q->QUERY_SQL("UPDATE rbl_reputations SET enabled=1 WHERE ID='$ID'");
    return admin_tracks("Enable reputation rule $rulename");
}
function enable_service():bool{
    $ID=intval($_GET["enable-service"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled,uri FROM rbl_reputations_data WHERE ID='$ID'");
    $enabled=$ligne["enabled"];
    $uri=$ligne["uri"];
    if($enabled==1){
        $q->QUERY_SQL("UPDATE rbl_reputations_data SET enabled=0 WHERE ID='$ID'");
        return admin_tracks("Disable reputation service $uri");

    }
    $q->QUERY_SQL("UPDATE rbl_reputations_data SET enabled=1 WHERE ID='$ID'");
    return admin_tracks("Enable reputation service $uri");
}
function delete_service_perform():bool{
    $ID=intval($_POST["delete-service"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT uri FROM rbl_reputations_data WHERE ID='$ID'");
    $uri=$ligne["uri"];
    $q->QUERY_SQL("DELETE FROM rbl_reputations_data WHERE ID='$ID'");
    return admin_tracks("Deleted reputation service $uri");
}
function service_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $function1=$_GET["function1"];
    $ID=intval($_GET["service-js"]);
    $ruleid=intval($_GET["ruleid"]);
    $groupid=intval($_GET["groupid"]);
    //&groupid=$groupid&ruleid=$ruleid&function=$function&function1=$function1

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM rbl_reputations_group WHERE ID='$groupid'");
    $GroupName=$ligne["GroupName"];
    if($ID==0){
        $title="$GroupName: {new_service}";
    }else{
    $title="$GroupName: {service} #$ID";
}
    return $tpl->js_dialog4($title, "$page?service-popup=$ID&groupid=$groupid&function=$function&ruleid=$ruleid&function1=$function1");

}
function group_link_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=intval($_GET["group-link"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID='$ruleid'");
    $rulename=$ligne["rulename"];
    return $tpl->js_dialog3("$rulename: {link_group}", "$page?group-link-popup=$ruleid&function=$function");
}
function group_link():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $groupid=intval($_GET["link-group"]);
    $ruleid=intval($_GET["ruleid"]);
    if($ruleid==0){
        echo $tpl->js_error("Rule ID is zero!");
        return false;
    }
    if($groupid==0){
        echo $tpl->js_error("Group ID is zero!");
        return false;
    }
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID='$ruleid'");
    $rulename=$ligne["rulename"];
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM rbl_reputations_group WHERE ID='$groupid'");
    $GroupName=$ligne["GroupName"];

    $zmd5=md5($ruleid.$groupid);
    $q->QUERY_SQL("INSERT INTO rbl_reputations_link (groupid,ruleid,zmd5) 
            VALUES ('$groupid','$ruleid','$zmd5')");

    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "$function();\n";
    echo "LoadAjax('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function')";

    return admin_tracks("Link reputation group $GroupName to the $rulename rule");

}
function group_link_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=intval($_GET["group-link-popup"]);
    $t=time();
    $sql="SELECT ID,GroupName,description FROM rbl_reputations_group ORDER BY GroupName";
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");


    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{group name}</th>";
    $html[]="<th data-sortable=false>{select}</th>";
    $html[]="<th data-sortable=false>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $TRCLASS=null;
    $TD1="style='width:1%' nowrap";

    $ico=ico_folder;
    foreach($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd ") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd ";
        }
        $md=md5(serialize($ligne));
        $GroupName=$ligne["GroupName"];
        $description=$ligne["description"];
        $ID=intval($ligne["ID"]);

        $jsselect="Loadjs('$page?link-group=$ID&ruleid=$ruleid&function=$function&md=$md')";
        $choose="<button class='btn btn-primary btn-xs' type='button' OnClick=\"$jsselect\">{select}</button>";

        $delete= $tpl->icon_delete("Loadjs('$page?delete-group=$ID&function=$function&md=$md')");

        $html[]="<tr style='vertical-align:middle' class='$TRCLASS' id='$md'>";
        $html[]="<td $TD1><i class='$ico'></i></td>";
        $html[]="<td>$GroupName<br><i>$description</i></span></td>";
        $html[]="<td $TD1>$choose</td>";
        $html[]="<td $TD1>$delete</td>";
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

    $html[]="<script>";
    $html[]="NoSpinner()";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function group_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $title="{new_group}";
    $groupid=$_GET["group-js"];
    $ruleid=$_GET["ruleid"];
    if($groupid>0){
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM rbl_reputations_group WHERE ID='$groupid'");
        $title=$ligne["GroupName"];
        return $tpl->js_dialog3($title, "$page?group-tab=$groupid&function=$function&ruleid=$ruleid");
    }
    return $tpl->js_dialog3($title, "$page?group-popup=$groupid&function=$function&ruleid=$ruleid");
}
function services_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ruleid=$_GET["ruleid"];
    $groupid=$_GET["services-popup"];
    echo "<div style='margin-top:10px;margin-bottom:5px' id='ButtonServicesFor$groupid'></div>";
    echo "<div style='margin-bottom:10px;'>";
    echo $tpl->search_block($page,"","","","&services-search=$groupid&ruleid=$ruleid&function1=$function","");
    echo "</div>";
    return true;
}
function service_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $function1=$_GET["function1"];
    $ID=intval($_GET["service-popup"]);
    $ruleid=$_GET["ruleid"];
    $groupid=intval($_GET["groupid"]);
    $BootstrapDialog="dialogInstance4.close();";
    $ligne=array();
    $button="{add}";

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    if($ID>0) {
        VERBOSE("SELECT * FROM rbl_reputations_data WHERE ID='$ID'" ,__LINE__);
        $ligne = $q->mysqli_fetch_array("SELECT * FROM rbl_reputations_data WHERE ID='$ID'");
        if(!$q->ok){
            echo $tpl->div_error($q->mysql_error);
        }

        $button="{apply}";
    }


    if(!isset($ligne["uri"])) {
        $ligne["uri"]="rbl://zen.spamhaus.org";
    }
    if(!isset($ligne["public"])) {
        $ligne["public"] = 1;
    }
    if(!isset($ligne["timeout"])) {
        $ligne["timeout"] = 3;
    }
    if(!isset($ligne["noresponse"])){
        $ligne["noresponse"]=0;
    }
    if(!isset($ligne["responsecode"])){
        $ligne["responsecode"]="127.0.0.2";
    }
    if(!isset($ligne["forcedns"])){
        $ligne["forcedns"]="0.0.0.0";
    }

    $form[]=$tpl->field_hidden("serviceid",$ID);
    $form[]=$tpl->field_hidden("groupid",$groupid);
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_text("uri", "{service}", $ligne["uri"]);
    $form[]=$tpl->field_ipv4("responsecode", "{answer}", $ligne["responsecode"]);
    $form[]=$tpl->field_ipv4("forcedns", "{DNSServer}", $ligne["forcedns"]);

    $form[]=$tpl->field_checkbox("noresponse", "{noresponse}", $ligne["noresponse"]);
    $form[]=$tpl->field_numeric("timeout", "{timeout} ({seconds})", $ligne["timeout"]);
    $html=$tpl->form_outside("",$form,"",$button,"$function();$function1();$BootstrapDialog;LoadAjax('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function')","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function service_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["serviceid"];
    $groupid=intval($_POST["groupid"]);
    $url=trim($_POST["uri"]);
    $noresponse=intval($_POST["noresponse"]);
    $responsecode=$_POST["responsecode"];
    $forcedns=$_POST["forcedns"];
    $timeout=intval($_POST["timeout"]);
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

    if($ID==0){
        $q->QUERY_SQL("INSERT INTO rbl_reputations_data (uri,groupid,noresponse,forcedns,timeout,responsecode) VALUES ('$url','$groupid','$noresponse','$forcedns','$timeout','$responsecode')");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return true;
        }
        return admin_tracks("Create new reputation element $url");
    }

    $q->QUERY_SQL("UPDATE rbl_reputations_data SET 
        uri='$url',
        noresponse='$noresponse',
        responsecode='$responsecode',
        forcedns='$forcedns',
        timeout='$timeout' WHERE ID='$ID'");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return true;
    }
    return admin_tracks("Update reputation rule $url");

}

function group_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=$_GET["group-popup"];
    $ruleid=$_GET["ruleid"];
    $BootstrapDialog="dialogInstance3.close();";
    $button="{add}";
    if($ID>0){
        $button="{apply}";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,description FROM rbl_reputations_group WHERE ID='$ID'");
    $form[]=$tpl->field_hidden("groupid",$ID);
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_text("GroupName", "{group name}", $ligne["GroupName"]);
    $form[]=$tpl->field_text("description", "{description}", $ligne["description"]);
    $html=$tpl->form_outside("",$form,"",$button,"$function();$BootstrapDialog;LoadAjax('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function')","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=$_GET["rule-popup"];
    $BootstrapDialog="dialogInstance2.close();";
    $button="{add}";
    if($ID>0){
        $button="{apply}";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM rbl_reputations WHERE ID='$ID'");
    if(intval($ligne["maxtime"])<5){
        $ligne["maxtime"]=14400;
    }

    $form[]=$tpl->field_hidden("ruleid",$ID);
    $form[]=$tpl->field_text("rulename", "{rulename}", $ligne["rulename"]);
    $form[]=$tpl->field_checkbox("firewall", "{firewall_protection}", $ligne["firewall"],"portslist,maxtime");
    $form[]=$tpl->field_text("portslist", "{ports}", $ligne["ports"]);
    $form[]=$tpl->field_numeric("maxtime","{Fail2bantime}",$ligne["maxtime"]);


    $html=$tpl->form_outside("",$form,"",$button,"$function();$BootstrapDialog","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function group_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=intval($_POST["ruleid"]);
    $groupid=intval($_POST["groupid"]);
    $GroupName=$_POST["GroupName"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $GroupName=$q->sqlite_escape_string2($GroupName);
    $description=$q->sqlite_escape_string2($_POST["description"]);
    if($groupid==0){
        if($ruleid==0){
            echo $tpl->post_error("ruleid = 0");
            return false;
        }
        $q->QUERY_SQL("INSERT INTO rbl_reputations_group (GroupName,description) VALUES ('$GroupName','$description')");
        $groupid=$q->last_id;
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return true;
        }

        $zmd5=md5($ruleid.$groupid);
        $q->QUERY_SQL("INSERT INTO rbl_reputations_link (groupid,ruleid,zmd5) 
            VALUES ('$groupid','$ruleid','$zmd5')");

        return admin_tracks("Create new reputation Group $GroupName");
    }

    $q->QUERY_SQL("UPDATE rbl_reputations_group SET GroupName='$GroupName',description='$description' 
                             WHERE ID='$groupid'");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return true;
    }
    return admin_tracks("Update reputation Group $GroupName");
}
function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["ruleid"];
    $rulename=$_POST["rulename"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");

    if($ID==0){
        $q->QUERY_SQL("INSERT INTO rbl_reputations (rulename) VALUES ('$rulename')");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return true;
        }
        return admin_tracks("Create new reputation rule $rulename");
    }

    $q->QUERY_SQL("UPDATE rbl_reputations SET rulename='$rulename' WHERE ID='$ID'");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return true;
    }
    return admin_tracks("Update reputation rule $rulename");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{reputation}",ico_clouds,"{APP_REPPUTATION_EXPLAIN}",
        "$page?tabs=yes","reputation",
        "progress-rblclients-restart");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{reputation}",$html);
        echo $tpl->build_firewall();return true;}
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_tab():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["rule-tab"];
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rbl_reputations WHERE ID='$ID'");
    $array[$ligne["rulename"]] = "$page?rule-popup=$ID&function=$function";
    $array["{reputation_services}"] = "$page?groups-popup=$ID&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}
function group_tab():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $ID=$_GET["group-tab"];
    $ruleid=$_GET["ruleid"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM rbl_reputations_group WHERE ID='$ID'");
    $array[$ligne["GroupName"]] = "$page?group-popup=$ID&ruleid=$ruleid&function=$function";
    $array["{remote_services}"]="$page?services-popup=$ID&ruleid=$ruleid&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{rules}"] = "$page?popup-search=yes";
    $array["{events}"] = "fw.reputations.requests.php";
    echo $tpl->tabs_default($array);
    return true;
}
function popup_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}
function groups_popup():bool{
    $page=CurrentPageName();
    $ruleid=$_GET["groups-popup"];
    $function=$_GET["function"];
    echo "<div id='groupsOf$ruleid' style='margin-top:10px'></div>";
    echo "<script type='text/javascript'>LoadAjax('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function')";
    echo "</script>";
    return true;
}
function services_button():bool{
    $page=CurrentPageName();
    $ruleid=$_GET["ruleid"];
    $function1=$_GET["function1"];
    $groupid=intval($_GET["services-button"]);
    $function=$_GET["function"];
    $tpl=new template_admin();
    $topbuttons[] = array("Loadjs('$page?service-js=0&groupid=$groupid&ruleid=$ruleid&function=$function&function1=$function1')", ico_plus, "{new_service}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function services_list():bool{
    $ruleid=$_GET["groups-list"];
    $function1=$_GET["function1"];
    $groupid=intval($_GET["services-search"]);
    $function=$_GET["function"];
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $style="style='margin-top:10px'";
    $t=time();
    $sock=new sockets();
    $sql="SELECT * FROM rbl_reputations_data WHERE groupid=$groupid";



    $html[]="<table id='table-$t' class=\"table table-stripped\" $style data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{services}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{DNS_SERVER}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{response}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>{enabled}</th>";
    $html[]="<th data-sortable=false>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $TRCLASS=null;
    $TD1="style='width:1%' nowrap";
    $page=CurrentPageName();
    $ico=ico_database;
    foreach($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd ") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd ";
        }
        $md=md5(serialize($ligne));
        $enabled = intval($ligne["enabled"]);
        $ID = $ligne["ID"];


        $uri = $tpl->utf8_encode($ligne["uri"]);
        $responsecode=  $ligne["responsecode"];
        $forcedns=$ligne["forcedns"];
        if($forcedns==null){
            $forcedns="{default}";
        }
        $noresponse=$ligne["noresponse"];
        if($noresponse==1){
            $responsecode="{no_answer}";
        }
        $class = "text-primary";
        $Error="";
        if(preg_match("#sdns:#",$uri)){
            $dnstamp=base64_encode($uri);
            $json=json_decode($sock->REST_API("/dnstamp/decode/$dnstamp"));
            if(!$json->Status){
                $uri="???&nbsp;";
                $Error="<spn class='label label-danger'>DNS Stamp Error</span><br><span class='text-danger'>$json->Error</span>";
            }else{
                $uri="<strong>DOH</strong> https://{$json->Info->ProviderName}{$json->Info->Path}";
                $forcedns="{$json->Info->ServerAddrStr}";
            }
        }

        $check=$tpl->icon_check($enabled,"Loadjs('$page?enable-service=$ID')");
        $delete=$tpl->icon_delete("Loadjs('$page?delete-service=$ID&function=$function&function1=$function1&md=$md')");
        $uri=$tpl->td_href($uri,"","Loadjs('$page?service-js=$ID&groupid=$groupid&function=$function&function1=$function1')");



        $html[]="<tr style='vertical-align:middle' class='$TRCLASS' id='$md'>";
        $html[]="<td $TD1><i class='$ico'></i></td>";
        $html[]="<td>$uri&nbsp;$Error</span></td>";
        $html[]="<td $TD1 >$forcedns</td>";
        $html[]="<td $TD1 >$responsecode</td>";
        $html[]="<td $TD1>$check</td>";
        $html[]="<td $TD1>$delete</td>";
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

    $html[]="<script>";
    $html[]="LoadAjaxSilent('ButtonServicesFor$groupid','$page?services-button=$groupid&function=$function&function1=$function1&ruleid=$ruleid')";
    $html[]="NoSpinner()";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function group_move_js():bool{
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $ID=$_GET["route-move-js"];
    $dir=$_GET["dir"];
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT zorder FROM rbl_reputations_link WHERE ID='$ID'");

    $zOrder=$ligne["zorder"];
    if($dir=="up"){
        $NewzOrder=$zOrder-1;
    }else{
        $NewzOrder=$zOrder+1;
    }

    if($NewzOrder<0){$NewzOrder=0;}

    $q->QUERY_SQL("UPDATE rbl_reputations_link SET zorder='$zOrder' WHERE zorder='$NewzOrder' AND ID<>'$ID'");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return true;}
    $q->QUERY_SQL("UPDATE rbl_reputations_link SET zorder='$NewzOrder' WHERE ID='$ID'");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return true;}

    $results=$q->QUERY_SQL("SELECT * FROM rbl_reputations_link WHERE ruleid='{$_GET["ruleid"]}' ORDER BY zorder");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return true;}
    $c=0;
    foreach ($results as $index=>$ligne){
        $c++;
        $q->QUERY_SQL("UPDATE rbl_reputations_link SET zorder='$c' WHERE ID='{$ligne["ID"]}'");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return true;}
    }
    return true;


}
function groups_list():bool{
    $page=CurrentPageName();
    $ruleid=$_GET["groups-list"];
    $function=$_GET["function"];
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $style="style='margin-top:10px'";
    $t=time();
    $topbuttons[] = array("Loadjs('$page?group-js=0&ruleid=$ruleid&function=$function')", ico_plus, "{new_group}");
    $topbuttons[] = array("Loadjs('$page?group-link=$ruleid&function=$function')", ico_link, "{link_group}");


    $sql="SELECT rbl_reputations_group.*,rbl_reputations_link.ID as linkid FROM rbl_reputations_group,rbl_reputations_link 
         WHERE rbl_reputations_link.ruleid=$ruleid
         AND rbl_reputations_link.groupid=rbl_reputations_group.ID 
         ORDER BY rbl_reputations_link.zorder";


    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="<table id='table-$t' class=\"table table-stripped\" $style data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{groupname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>{enabled}</th>";
    $html[]="<th data-sortable=false>{unlink}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $TRCLASS=null;
    $TD1="style='width:1%' nowrap";

    $ico=ico_directory;
    foreach($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd ") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd ";
        }
        $md=md5(serialize($ligne));
        $enabled = intval($ligne["enabled"]);
        $ID = $ligne["ID"];
        $GroupName = $ligne["GroupName"];
        $description= $ligne["description"];
        $linkid=$ligne["linkid"];
        $class = "text-primary";
        $check=$tpl->icon_check($enabled,"Loadjs('$page?enable-group=$ID&function=$function')");
        $delete=$tpl->icon_delete("Loadjs('$page?unlink-group=$linkid&md=$md&function=$function&ruleid=$ruleid')");
        $GroupName=$tpl->td_href($GroupName,"","Loadjs('$page?group-js=$ID&ruleid=$ruleid&function=$function')");

        $down=$tpl->icon_down("Loadjs('$page?group-move-js=$linkid&ruleid=$ID&dir=down');","AsSystemAdministrator");
        $up=$tpl->icon_up("Loadjs('$page?group-move-js=$linkid&ruleid=$ID&dir=up');","AsSystemAdministrator");

        $html[]="<tr style='vertical-align:middle' class='$TRCLASS' id='$md'>";
        $html[]="<td $TD1><i class='$ico'></i></td>";
        $html[]="<td $TD1>$GroupName</span></td>";
        $html[]="<td>$description</span></td>";
        $html[]="<td $TD1>$down&nbsp;$up</span></td>";
        $html[]="<td $TD1>$check</td>";
        $html[]="<td $TD1>$delete</td>";
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

    $html[]="<script>";
    $html[]="NoSpinner()";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $t=time();

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rulename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groups}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>{check}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>{enabled}</th>";
    $html[]="<th data-sortable=false>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    $TD1="style='width:1%' nowrap";

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results=$q->QUERY_SQL("SELECT * FROM rbl_reputations");

    foreach($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd ") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd ";
        }
        $md=md5(serialize($ligne));
        $ID = $ligne["ID"];
        $EXPLAIN = explain_thisrule($ID);
        $enabled = intval($ligne["enabled"]);
        $rulename = $ligne["rulename"];
        $class = "";
        $check=$tpl->icon_check($enabled,"Loadjs('$page?enable-rule=$ID')");
        $delete=$tpl->icon_delete("Loadjs('$page?delete-rule=$ID&md=$md&function=$function')");
        if($enabled==0){
            $class="text-muted";
        }
        $test=$tpl->icon_loupe(true,"Loadjs('$page?check-rule=$ID')");
        $rulename=$tpl->td_href($rulename,"","Loadjs('$page?rule-js=$ID&function=$function')");
        $ico=ico_script;
        $html[]="<tr style='vertical-align:middle' class='$TRCLASS' id='$md'>";
        $html[]="<td $TD1><i class='$class $ico'></i></td>";
        $html[]="<td $TD1><span class='$class'>$rulename</span></td>";
        $html[]="<td><span class='$class'>$EXPLAIN</span></td>";
        $html[]="<td $TD1>$test</td>";
        $html[]="<td $TD1>$check</td>";
        $html[]="<td $TD1>$delete</td>";
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


    $topbuttons[] = array("Loadjs('$page?rule-js=0&function=$function');", ico_plus, "{new_rule}");
    $topbuttons[] = array("Loadjs('$page?reset-js=yes&function=$function');", ico_trash, "{reset}");

    $TINY_ARRAY["TITLE"]="{reputation}&nbsp;&raquo;&nbsp;{rules}";
    $TINY_ARRAY["ICO"]=ico_clouds;
    $TINY_ARRAY["EXPL"]="{APP_REPPUTATION_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="NoSpinner()";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function explain_thisrule($ID){
    $f=array();
    $function=$_GET["function"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $sql="SELECT rbl_reputations_group.*,rbl_reputations_link.ID as linkid FROM rbl_reputations_group,rbl_reputations_link 
         WHERE rbl_reputations_link.ruleid=$ID
         AND rbl_reputations_link.groupid=rbl_reputations_group.ID";

    $results=$q->QUERY_SQL($sql);
    foreach($results as $index=>$ligne) {
        $groupid=$ligne["ID"];
        $GroupName=$ligne["GroupName"];
        $GroupName=$tpl->td_href($GroupName,"","Loadjs('$page?group-js=$groupid&ruleid=$ID&function=$function')");
        $f[]=$GroupName;
    }
    if(count($f)==0){
        return $tpl->_ENGINE_parse_body("<span class=text-danger>{no_group}</span>");
    }

    return @implode(", ",$f);
}