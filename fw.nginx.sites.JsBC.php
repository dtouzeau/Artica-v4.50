<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.tpl.inc");
include_once(dirname(__FILE__)."/ressources/class.modsecurity.tools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();


if(isset($_GET["JsBC-js"])){www_parameters_JsBC_js();exit;}
if(isset($_GET["popup"])){www_parameters_JsBC_popup();exit;}
if(isset($_GET["tabs"])){www_parameters_JsBC_tabs();exit;}
if(isset($_POST["JsBC"])){www_parameters_JsBC_save();exit;}




if(isset($_GET["whitelist-start"])){whitelist_start();exit;}
if(isset($_GET["whitelist-table"])){whitelist_table();exit;}
if(isset($_GET["top-buttons"])){whitelist_top_buttons();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["web-js"])){web_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_GET["popup-web"])){web_popup();exit;}


if(isset($_GET["rule-enable"])){rule_enable();exit;}
if(isset($_GET["rule-remove"])){rule_remove();exit;}
if(isset($_POST["item"])){rule_save();exit;}
www_parameters_JsBC_js();

function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = trim($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $function   = $_GET["function"];
    $title      = "{rule}: $rule";
    $ruleEnc=urlencode($rule);
    if($rule==0){$title="{new_rule}";}
    return $tpl->js_dialog5("{exclude}: $title","$page?popup-rule=$ruleEnc&serviceid=$serviceid&function=$function");
}
function web_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = trim($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $function   = $_GET["function"];
    $title      = "{path}: $rule";
    $ruleEnc=urlencode($rule);
    if($rule==0){$title="{new_path}";}
    return $tpl->js_dialog5("{exclude}: $title","$page?popup-web=$ruleEnc&serviceid=$serviceid&function=$function");
}


function www_parameters_JsBC_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["serviceid"]);
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    return $tpl->js_dialog2("#$ID - $servicename", "$page?tabs=$ID");
}
function www_parameters_JsBC_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["tabs"]);
    $array["{parameters}"]="$page?popup=$ID";
    $array["{whitelist}"]="$page?whitelist-start=$ID";
    echo $tpl->tabs_default($array);
    return true;
}
function www_parameters_JsBC_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->CLUSTER_CLI = true;
    $ID                         = intval($_GET["popup"]);
    $sockngix                   = new socksngix($ID);
    $JsBC=intval($sockngix->GET_INFO("JsBC"));
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->BigCircleCheckbox("JsBC|ID:$ID","{signed_js_browser_challenge}",
        "{signed_js_browser_challenge_explain}",$JsBC,www_parameters_reload($ID),"AsSystemWebMaster");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function www_parameters_reload($serviceid):string{
    $js[]="LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid')";
    $js[]="Loadjs('fw.nginx.sites.php?td-row=$serviceid');";
    return @implode(";",$js);
}
function whitelist_start():bool{
    $serviceid  = intval($_GET["whitelist-start"]);
    $tpl = new template_admin();
    $page       = CurrentPageName();
    echo "<div id='top-buttons-$serviceid' style='margin-top:5px;margin-bottom:5px'></div>";
    echo $tpl->search_block($page,"","","","&whitelist-table=$serviceid");
    return true;
}
function whitelist_top_buttons():bool{
    $serviceid  = intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $topbuttons[] = array("Loadjs('$page?rule-js=&serviceid=$serviceid&function=$function')", ico_plus, "{new_address}");
    $topbuttons[] = array("Loadjs('$page?web-js=&serviceid=$serviceid&function=$function')", ico_plus, "{new_path}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function whitelist_table():bool{
    $serviceid  = intval($_GET["whitelist-table"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $function = $_GET["function"];
    $tableid    = time();

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap colspan='2'>{network}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $sock=new socksngix($serviceid);
    $Conf=$sock->GetCache();
    $ico=ico_server;
    foreach ($Conf["JSBCWhite"] as $num=>$ligne){
        $enable=intval($ligne["enabled"]);
        $description=trim($ligne["description"]);
        $pattern=trim($ligne["item"]);
        $patternenc=urlencode($pattern);
        $md=md5(serialize($ligne));
        if(strlen($description)>1){
            $description="<br><small>$description</small>";
        }

//
        $enable=$tpl->icon_check($enable,"Loadjs('$page?rule-enable=$patternenc&serviceid=$serviceid')","","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?rule-remove=$patternenc&serviceid=$serviceid&md=$md')","AsWebMaster");
        $pattern=$tpl->td_href($pattern,"","Loadjs('$page?rule-js=$patternenc&serviceid=$serviceid');");

        $html[]="<tr id='$md'>
				<td style='width:1%' nowrap><i class='$ico'></i></td>
				<td style='width:99%'>$pattern$description</td>
				<td style='width:1%' nowrap>$enable</td>
				<td style='width:1%' nowrap>$delete</td>
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
    $html[]="LoadAjaxSilent('top-buttons-$serviceid','$page?top-buttons=$serviceid&function=$function');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_enable():bool{
    $item=urlencode($_GET["rule-enable"]);
    $serviceid  = intval($_GET["serviceid"]);
    $sock=new socksngix($serviceid);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $servicename=$sock->GetServiceName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/jsbc/white/switch/$serviceid/$item"),true);

    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");

    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    return admin_tracks("Switch enable/disable Signed JavaScript Browser Challenge rule {$_GET["rule-enable"]} from $servicename");
}

function rule_remove():bool{
    $item=urlencode($_GET["rule-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock=new socksngix($serviceid);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $servicename=$sock->GetServiceName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/jsbc/white/del/$serviceid/$item"),true);

    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");

    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    return admin_tracks("Delete Signed JavaScript Browser Challenge rule {$_GET["rule-remove"]} from $servicename");
}
function www_parameters_JsBC_save():bool{
    $ID=$_POST["ID"];
    $JsBC=$_POST["JsBC"];
    $sockngix                   = new socksngix($ID);
    $sockngix->SET_INFO("JsBC",$JsBC);
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return admin_tracks("Set Signed JavaScript Browser Challenge feature to $JsBC for $servicename");
}
function web_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $pattern     = $_GET["popup-rule"];
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $function =$_GET["function"];


    $sock       = new socksngix($serviceid);
    $Conf       = $sock->GetCache();
    $ligne =array();
    foreach ($Conf["JSBCWhite"] as $num=>$items){
        if($items["item"]==$pattern){
            $ligne=$items;
            break;
        }
    }
    $bt="{apply}";
    if(!isset($ligne["enable"] )) {
        $ligne["enable"] = 1;
    }
    if(!isset($ligne["item"])) {
        $bt = "{add}";
        $ligne["item"]="";
    }

    $jsrestarts[]="dialogInstance5.close();";
    if(strlen($function)>2){
        $jsrestarts[]="$function();";
    }
    $jsApply=@implode("", $jsrestarts);
    $form[]=$tpl->field_hidden("service_id",$serviceid);
    $form[]=$tpl->field_text("item","{path}",$ligne["item"],true);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    $form[]=$tpl->field_checkbox("enabled","{enable}",$ligne["enabled"]);
    $html[]=$tpl->form_outside("",$form,"{excludepaths_explain}",$bt,$jsApply,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $pattern     = $_GET["popup-rule"];
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $function =$_GET["function"];


    $sock       = new socksngix($serviceid);
    $Conf       = $sock->GetCache();
    $ligne =array();
    foreach ($Conf["JSBCWhite"] as $num=>$items){
        if($items["item"]==$pattern){
            $ligne=$items;
            break;
        }
    }
    $bt="{apply}";
    if(!isset($ligne["enable"] )) {
        $ligne["enable"] = 1;
    }
    if(!isset($ligne["item"])) {
        $bt = "{add}";
        $ligne["item"]="";
    }

    $jsrestarts[]="dialogInstance5.close();";
    if(strlen($function)>2){
        $jsrestarts[]="$function();";
    }
    $jsApply=@implode("", $jsrestarts);
    $form[]=$tpl->field_hidden("service_id",$serviceid);
    $form[]=$tpl->field_text("item","{address}",$ligne["item"],true);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    $form[]=$tpl->field_checkbox("enabled","{enable}",$ligne["enabled"]);
    $html[]=$tpl->form_outside("",$form,"{excludetransparentin_explain}",$bt,$jsApply,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();
    $_POST["service_id"]=intval($_POST["service_id"]);
    $json=json_decode($sock->REST_API_NGINX_POST_JSON("/jsbc/white/add",$_POST),true);
    if(!isset($json["Status"])){
        echo "Protocol Error";
        return false;
    }
    if(!$json["Status"]){
        echo $json["Error"];
        return false;
    }
    return admin_tracks_post("Save JavaScript Browser Challenge whitelist item");

}