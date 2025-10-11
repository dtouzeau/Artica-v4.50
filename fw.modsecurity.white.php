<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.modsectools.inc');
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}

if(isset($_GET["reconfigure"])){reconfigure_waf();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
if(isset($_GET["zoom-download"])){zoom_download();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["active-tabs"])){active_tabs();exit;}
if(isset($_GET["active-list"])){active_list();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}
if(isset($_GET["active-remove"])){active_remove();exit;}
if(isset($_POST["active-remove"])){active_remove_perform();exit;}
if(isset($_GET["delete-by-rule"])){delete_by_rule_js();exit;}
if(isset($_POST["delbyrule"])){delete_by_rule_perform();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["ruleid-popup"])){rule_popup();exit;}
if(isset($_GET["active-enable"])){active_enable();exit;}
page();

function rule_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=intval($_GET["ruleid-js"]);
    $title="{new_rule}";
    $etet=null;$rid=null;
    if($ruleid>0){
        $title="{rule} #$ruleid";
    }else{
        if(isset($_GET["domain"])) {
            $domain = urlencode($_GET["domain"]);
            $query = urlencode($_GET["query"]);
            $etet = "&domain=$domain&query=$query";
        }
        if(isset($_GET["rid"])){
            $rid="&rid={$_GET["rid"]}";
        }
    }



    $tpl->js_dialog1($title,"$page?ruleid-popup=$ruleid$etet$rid");
}

function active_enable():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["active-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne = $q->mysqli_fetch_array("SELECT * FROM modsecurity_whitelist WHERE ID='$ID'");
    $wfrule=$ligne["wfrule"];
    $serviceid=$ligne["serviceid"];
    $enabled=$ligne["enabled"];
    if($enabled==0){
        $q->QUERY_SQL("UPDATE modsecurity_whitelist SET enabled=1 WHERE ID=$ID");
        admin_tracks("Enable white rule $ID $wfrule/$serviceid");
    }else{
        $q->QUERY_SQL("UPDATE modsecurity_whitelist SET enabled=0 WHERE ID=$ID");
        admin_tracks("Disable white rule $ID $wfrule/$serviceid");
    }
    echo "Loadjs('$page?reconfigure=yes&silent=yes');\n";
    return true;
}

function rule_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ruleid-popup"]);
    $bt="{apply}";
     $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne["enabled"]=1;
    $error=null;
    if($ID>0) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM modsecurity_whitelist WHERE ID='$ID'");
        $title="{rule}: $ID";
    }
    if($ID==0) {
        $title = "{new_rule} {whitelist}";
        $bt="{create_rule}";
        $ligne["wfrule"]=0;
        $ligne["serviceid"]=0;
        if(isset($_GET["domain"])) {
            $domain = $_GET["domain"];
            $query = $_GET["query"];
            $title=$title."&nbsp;&raquo;&nbsp;$domain";
            $ligne["serviceid"]=$tpl->nginxHostToInt($domain);
            if($ligne["serviceid"]==0){
                $title=$title."<strong> ({host_not_found})</strong>";
            }
            $full_uri="http://www.domain.com$query";
            $scom=parse_url($full_uri);
            if(isset($scom["path"])){
                $ligne["spath"]=$scom["path"];
            }else{
                $ligne["spath"]=$query;
            }
            if(isset($_GET['rid'])){
                $ligne["wfrule"]=intval($_GET["rid"]);
            }

            $zligne = $q->mysqli_fetch_array("SELECT ID FROM modsecurity_whitelist WHERE wfrule={$ligne["wfrule"]} AND serviceid={$ligne["serviceid"]} AND spath LIKE '{$ligne["spath"]}%'");
            if($zligne["ID"]>0){
                $ID=$zligne["ID"];
                $title="{rule}: $ID";
                $bt="{apply}";
            }
        }


    }
    $serviceid=intval($ligne["serviceid"]);
    if($ligne["serviceid"]==0) {
        $js[] = "dialogInstance1.close();";
        $js[]="Loadjs('$page?reconfigure=yes&silent=yes');";
    }else{
        $js[]="Loadjs('$page?reconfigure=yes&silent=yes');";
    }


    $tpl->field_hidden("ruleid",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    if($ligne["wfrule"]==1){$ligne["wfrule"]=0;}
    if($ID>0) {
        $form[] = $tpl->field_choose_websites("serviceid", "{website}", $ligne["serviceid"]);
    }else{
        if($ligne["serviceid"]>0){
            $tpl->field_hidden("serviceid",$ligne["serviceid"]);
        }else{
            $form[] = $tpl->field_choose_websites("serviceid", "{website}",0);
        }
    }

    if($ligne["serviceid"]>0){
        $title=$title." {website} #{$ligne["serviceid"]}";
    }

    $form[]=$tpl->field_numeric("wfrule","Web Firewall: {rule_number}",intval($ligne["wfrule"]));
    $form[]=$tpl->field_text("spath","{url}",$ligne["spath"]);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);


    $html[]="<div id='modsecurity-compile-{$ligne["serviceid"]}'></div>";
    $html[]=$error;
    $html[]=$tpl->form_outside($title,$form,null,$bt,"LoadAjax('waf-white-table','$page?table=yes');".@implode(";",$js),"AsWebSecurity");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ruleid"]);
    unset($_POST["ruleid"]);
    if($_POST["serviceid"]==null){$_POST["serviceid"]=0;}
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $edit=array();
    foreach ($_POST as $key=>$val){
        $val=$q->sqlite_escape_string2($val);
        $edit[]="`$key`='$val'";
        $keys[]="`$key`";
        $vals[]="'$val'";
        $zkeys[]="$key=$val";
    }

    // $sql="CREATE TABLE IF NOT EXISTS `modsecurity_whitelist`
    //        ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT, wfrule INTEGER,`serviceid` INTEGER, spath TEXT)";

    if($ID>0){
        $sql="UPDATE modsecurity_whitelist SET ".@implode(",",$edit)." WHERE ID=$ID";
        $admin_track="Update Web Application whitelist rule ".@implode(",",$zkeys);
    }else{
        $sql="INSERT INTO modsecurity_whitelist (".@implode(",",$keys).") VALUES (".@implode(",",$vals).")";
        $admin_track="Create a new Web Application whitelist rule ".@implode(",",$zkeys);
    }
    writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        $tpl->post_error($q->mysql_error);
        return false;
    }
    admin_tracks($admin_track);
    return true;
}

function delete_by_rule_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=intval($_GET["delete-by-rule"]);
    $function=$_GET["function"];
    $tpl->js_confirm_delete("{all_records_from_this_rule} #$ruleid","delbyrule",$ruleid,"dialogInstance1.close();$function()");

}

function table_start(){
    $page=CurrentPageName();
    echo "<div id='waf-white-table'></div><script>LoadAjax('waf-white-table','$page?table=yes');</script>";
}

function delete_by_rule_perform():bool{
    $ruleid=intval($_POST["delbyrule"]);
    $q=new postgres_sql();


    $sql="SELECT modsecurity_events.unique_id,modsecurity_events.ruleid 
    FROM modsecurity_events WHERE modsecurity_events.ruleid=$ruleid ORDER BY modsecurity_events.unique_id DESC";
    $UNIQS=array();
    $q=new postgres_sql();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)) {
        $unique_id = $ligne["unique_id"];
        $UNIQS[] = $unique_id;
    }

    $q->QUERY_SQL("DELETE FROM modsecurity_events WHERE ruleid=$ruleid");
    foreach ($UNIQS as $uniq_id){
        $q->QUERY_SQL("DELETE FROM modsecurity_reports WHERE unique_id=$uniq_id");
    }
    admin_tracks(count($UNIQS)." events removed from Web application firewall threats list");
    return true;



}





function get_disabled_ruleid($ruleid,$serviceid,$path){
    $ss=array();

    if($serviceid>0) {
        $ss[] = "AND serviceid=$serviceid";
    }
    if($path<>null) {
        $ss[] = "AND spath='$path'";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $sql="SELECT ID FROM `modsecurity_whitelist` WHERE wfrule=$ruleid ". @implode(" ",$ss);
    $ligne=$q->mysqli_fetch_array($sql);
    return intval($ligne["ID"]);

}


function reconfigure_js($serviceid=0):string{
    $page=CurrentPageName();
    return "Loadjs('$page?reconfigure=yes');";
}
function reconfigure_waf():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $content=$sock->REST_API_NGINX("/reverse-proxy/wafnohup");
    $json=json_decode($content);
    if (json_last_error()> JSON_ERROR_NONE) {
        $tpl->js_error("Decoding: ".strlen($content)." bytes<hr>".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }
    if(!isset($_GET["silent"])) {
        $tpl->js_ok();
    }
    return true;
}



function active_remove():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["active-remove"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT wfrule,serviceid FROM modsecurity_whitelist WHERE ID=$ID");

    $serviceid=$ligne["serviceid"];
    $reconfigure_js=reconfigure_js($serviceid);
    $md=$_GET["md"];
    $rulename=rulename($ligne["wfrule"]);
    echo $tpl->js_confirm_delete("{whitelist}<br>$rulename<br>{$ligne["wfrule"]}","active-remove",$ID,"$('#$md').remove();$reconfigure_js");
    return true;
}

function active_remove_perform():bool{
    $tpl=new template_admin();
    $ID=intval($_POST["active-remove"]);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT wfrule FROM modsecurity_whitelist WHERE ID=$ID");
    $rulename=rulename($ligne["wfrule"]);
    $q->QUERY_SQL("DELETE FROM modsecurity_whitelist WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_error($q->mysql_error);return false;}
    admin_tracks("DELETE WAF white-list Number $ID for rule $rulename");
    return true;
}

function table() {
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $TRCLASS=null;
    $ruleid=intval($_GET["active-list"]);
    $t=time();
    $function=$_GET["function"];




    $results=$q->QUERY_SQL("SELECT * FROM modsecurity_whitelist");
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" style='margin-top:10px' data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{rule}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{rulename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{web_service}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{path}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{enabled}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $serviceid=$ligne["serviceid"];
        $compile="Loadjs('$page?reconfigure=yes');";
        $path=$ligne["spath"];
        $md=md5(serialize($ligne));
        $www="{all_websites}";
        $ruleid=$ligne["wfrule"];
        $compile_tb=null;
        if($path==null){$path="{all_directories}";}
        $ID=$ligne["ID"];
        $uri_rule=null;
        $rulename_text=null;
        $rulename=rulename($ruleid);
        if($rulename<>null) {
            $uri_rule = "Loadjs('fw.modsecurity.defrules.php?ruleid-js=$ruleid')";
            $rulename_text="$rulename";
        }
        $compile_tb=$tpl->icon_run($compile);
        if($serviceid>0){
            $sql = "SELECT servicename FROM nginx_services WHERE ID=$serviceid";
            $WebService=$q->mysqli_fetch_array($sql);
            $www=$WebService["servicename"];
        }


        $ruleid_column=$ruleid;

        if($uri_rule<>null) {
            $ruleid_column = $tpl->td_href($ruleid, null, $uri_rule);
        }
        if($ruleid==0){
            $ruleid="*";$rulename_text="{all}";
            $ruleid_column=$ruleid;
        }


        $delete_ico=$tpl->icon_delete("Loadjs('$page?active-remove=$ID&md=$md')","AsWebSecurity");
        $www=$tpl->td_href($www,null,"Loadjs('$page?ruleid-js=$ID')");
        $enabled=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?active-enable=$ID&md=$md')");
        $description=$ligne["description"];
        if(strlen($description)>2) {
            $description="<br><i>$description</i>";
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%' nowrap>$ruleid_column</td>";
        $html[]="<td width='1%' nowrap>$rulename_text</td>";
        $html[]="<td width='5%' nowrap>$www</td>";
        $html[]="<td width='50%' nowrap>$path$description</td>";
        $html[]="<td width='1%' nowrap>$compile_tb</td>";
        $html[]="<td width='1%' nowrap>$enabled</td>";
        $html[]="<td width='1%' nowrap>$delete_ico</td>";
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
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": false },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);


}

function rulename($ruleid){
    if($ruleid=="200002"){return "Failed to parse request body.";}
    $q=new lib_sqlite("/home/artica/SQLITE/modsecurity_rules.db");
    $ligne=$q->mysqli_fetch_array("SELECT rulename FROM rules WHERE ID=$ruleid");
    return trim($ligne["rulename"]);
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $reconfigure_js=reconfigure_js();
    //
    $add="Loadjs('$page?ruleid-js=0');";
    $refresh="LoadAjax('waf-white-table','$page?table=yes');";
    $bt[] = "<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $bt[] = "<label class=\"btn btn btn-warning\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
    $bt[] = "<label class=\"btn btn btn-primary\" OnClick=\"$refresh\"><i class='fas fa-sync'></i> {refresh} </label>";
    $bt[] = "<label class=\"btn btn btn-blue\" OnClick=\"$reconfigure_js\"><i class='fa fa-save'></i> {apply_firewall_rules} </label>";


    $bt[] = "</div>";

    $html=$tpl->page_header("{WAF_LONG}:{whitelist}","fas fa-thumbs-up",@implode("",$bt),"$page?table-start=yes","waf-white","waf-white-progress",false,"waf-white-section");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Web Application firewall {DETECTED_THREATS}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}



