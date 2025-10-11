<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");

if(isset($_GET["popup-tabs"])){popup_tabs();exit;}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}


function service_js(){
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    return $tpl->js_dialog6("{gzip_rules}","$page?popup-main=$serviceid",650);
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $function=$_GET["function"];

    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}

   return $tpl->js_dialog7("{dynamic_rate_limiting}: $title","$page?popup-tabs=$rule&serviceid=$serviceid&function=$function");
}
function popup_tabs():bool{
    $page=CurrentPageName();
    $ruleid=intval($_GET["rule-id"]);
    $serviceid=intval($_GET["service-id"]);
    $function=$_GET["function"];
    $tpl=new template_admin();
    $array["{rule}"]="$page?popup-rule=$ruleid&serviceid=$serviceid&function=$function";
    if($ruleid>0){

    }
    echo $tpl->tabs_default($array);
    return true;
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";
}

function rule_remove():bool{
    $ruleid=intval($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("gzip_types")));
    unset($data[$ruleid]);
    $encoded=serialize($data);
    $sock->SET_INFO("gzip_types",base64_encode($encoded));
    echo "$('#$ruleid').remove();\n";
    echo "LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";
    return true;

}

function rule_enable(){
    $ruleid=intval($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("gzip_types")));
    if(intval($data[$ruleid]["enable"])==1){
        $data[$ruleid]["enable"]=0;
    }else{
        $data[$ruleid]["enable"]=1;
    }
    $encoded=serialize($data);
    $sock->SET_INFO("gzip_types",base64_encode($encoded));
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $function   = $_GET["function"];

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM nginx_drl_sqacls WHERE ID=$ruleid");

    if(!isset($ligne["enable"])){
        $ligne["enabled"] = 1;
    }
    $bt="{add}";
    if($ruleid>0){ $bt="{apply}"; }
    $jsrestart="dialogInstance7.close();$function();LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_checkbox("enabled","{enable}",$ligne["enabled"]);
    $ligne["aclname"]=$tpl->utf8_encode($ligne["aclname"]);
    $form[]=$tpl->field_text("aclname", "{rule_name}", $ligne["aclname"],true);
    $form[]=$tpl->field_numeric("rate", "{limit_rate}", $ligne["rate"],true);
    $html[]=$tpl->form_outside("",$form,null,$bt,$jsrestart,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function rule_export_js(){
    $serviceid=intval($_GET["rule-export-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tpl->js_dialog6("{header_checks}: {export}/{import}","$page?rule-export-popup=$serviceid",950);
}

function rule_export_popup(){
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $serviceid=intval($_GET["rule-export-popup"]);
    $sock       = new socksngix($serviceid);
    $tpl->field_hidden("importid","$serviceid");
    $jsrestart="dialogInstance6.close();LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";

    $form[]=$tpl->field_textarea("export", "{rules}", $sock->GET_INFO("gzip_types"),"664px");
    echo $tpl->form_outside("{export}/{import}", @implode("\n", $form),null,"{apply}",$jsrestart,null);
}
function rule_export_save(){
    $serviceid=intval($_POST["importid"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("gzip_types",$_POST["export"]);
}

function rule_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $ruleid     = intval($_POST["ruleid"]);
    $rate       = intval($_POST["rate"]);
    $aclname    = $q->sqlite_escape_string2($_POST["aclname"]);
    $enabled    = intval($_POST["enabled"]);
    if($ruleid==0) {

        $xORDER=1;
        $PortDirection=0;
        $sql = "INSERT INTO nginx_drl_sqacls (aclname,enabled,acltpl,xORDER,aclport,aclgroup,aclgpid,PortDirection,serviceid,rate) VALUES ('$aclname',$enabled,'','$xORDER','0','0','0','$PortDirection',$serviceid,$rate)";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
        return admin_tracks_post("New Reverse-proxy Dynamic Rate rule");
    }

    $sql="UPDATE nginx_drl_sqacls SET enabled=$enabled,
                        aclname='$aclname',
                        rate=$rate,
                        WHERE ID=$ruleid";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks_post("Edit Reverse-proxy Dynamic Rate rule #$ruleid");
}

function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='top-buttons-$serviceid' style='margin-bottom: 5px'></div>".$tpl->search_block($page,"","","","&popup-table=$serviceid");
    return true;
}
function top_buttons():bool{
    $serviceid  = intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();


    $topbuttons[] = array("Loadjs('$page?rule-js=0&serviceid=$serviceid&function=$function')", ico_plus, "{new_rule}");

    $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&serviceid=$serviceid&function=$function')", ico_filter, "{OnlyActive}");
    $topbuttons[] = array("Loadjs('$page?disableall=yes&serviceid=$serviceid&function=$function')", ico_disabled, "{disable_all}");
    $topbuttons[] = array("Loadjs('$page?enableall=yes&serviceid=$serviceid&function=$function')", ico_check, "{enable_all}");



    $compile_js_progress=compile_js_progress($serviceid);
    $topbuttons[] = array($compile_js_progress, ico_save, "{apply}");

    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function popup_table(){
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $tableid    = time();
    $function   = $_GET["function"];

    $compile_js_progress=compile_js_progress($serviceid);
    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{dynamic_rate_limiting}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";
    $TRCLASS="";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT * FROM nginx_drl_sqacls WHERE serviceid=$serviceid ORDER BY xORDER");

    foreach($results as $index=>$ligne) {
        $MUTED=null;
        $ID=$ligne["ID"];
        if(isset($already_isset[$ID])){continue;}
        $already_isset[$ID]=true;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $explain = EXPLAIN_THIS_RULE($ligne['ID'], $ligne["enabled"], $ligne["aclgroup"]);
        $delete=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$ID')");
        $js="Loadjs('$page?rule-id-js=$ID')";
        if($ligne["enabled"]==0){$MUTED=" text-muted";}
        $up=$tpl->icon_up("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=1');");
        $down=$tpl->icon_down("Loadjs('$page?acl-rule-move=$ID&acl-rule-dir=0');");
        $rule_status="<span class='label label-primary'>{active2}</span>";
        if($ligne["enabled"]==0){
            $rule_status="<span class='label label-default'>{inactive}</span>";
        }


        $aclname = $ligne["aclname"];
        if(strlen($aclname)>50){
            $aclname=substr($aclname,0,47)."...";
        }
        $row_order=$tpl->td_href("<span class=\"label label-default\" id='acl-order-$ID'>{$ligne["xORDER"]}</span>",
            null,"Loadjs('$page?change-order=$ID');");



        $html[]="<tr class='$TRCLASS{$MUTED}' id='acl-$ID'>";
        $html[]="<td class=\"center\" style='width:1%' nowrap >$row_order</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap>$rule_status</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  nowrap>". $tpl->td_href($aclname,"{click_to_edit}",$js)."</td>";
        $html[]="<td style='vertical-align:middle'>$explain</td>";
        $html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_refresh("LoadAjaxTiny
        ('explain-this-rule-$ID','$page?explain-this-rule=$ID&enabled={$ligne["enabled"]}&aclgroup={$ligne["aclgroup"]}')")."</td>";
        $html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_copy("Loadjs('$page?duplicate-js=$ID')","AsDansGuardianAdministrator")."</td>";
        $html[]="<td class='center' style='width:1%' nowrap>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-js=$ID')",null,"AsDansGuardianAdministrator")."</td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$up&nbsp;&nbsp;$down</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%'  class='center' nowrap>$delete</center></td>";
        $html[]="</tr>";

    }

        $html[]="</tbody>";
        $html[]="<tfoot>";

        $html[]="<tr>";
        $html[]="<td colspan='9'>";
        $html[]="<ul class='pagination pull-right'></ul>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</tfoot>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="$(document).ready(function() { $('#$tableid').footable( { \"filtering\": { \"enabled\": false";
        $html[]="},\"sorting\": { \"enabled\": true },";
        $html[]="\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";
        $html[]="LoadAjaxSilent('top-buttons-$serviceid','$page?top-buttons=$serviceid&function=$function');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        }


function EXPLAIN_THIS_RULE($ID){
    $acls=new squid_acls_groups();
    $tpl=new templates();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT rate FROM nginx_drl_sqacls WHERE ID=$ID");
    $objects=$acls->getobjectsNameFromAclrule($ID,"black","nginx_drl_sqacls");
    if(count($objects)>0){$method[]="{for_objects} ". @implode(", {and} ", $objects);}
    $method[]="{then} {limit_rate} &laquo;{$ligne["rate"]}/s&raquo;";
    $page=CurrentPageName();

    if(isset($_GET["explain-this-rule"])){
        return $tpl->_ENGINE_parse_body(@implode(" ", $method));
    }

    return  $tpl->_ENGINE_parse_body("<span id='explain-this-rule-$ID' data='$page?explain-this-rule=$ID'>".@implode(" ", $method)."</span>");
}