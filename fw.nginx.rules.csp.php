<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");


if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-buttons"])){popup_buttons();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}
if(isset($_POST["CSPTestContent"])){ContentSecurityPolicyReportOnly_save();exit;}

if(isset($_GET["rule-export-js"])){rule_export_js();exit;}
if(isset($_GET["rule-export-popup"])){rule_export_popup();exit;}
if(isset($_POST["importid"])){rule_export_save();exit;}
if(isset($_GET["enable-rule-js"])){enable_feature();}
if(isset($_GET["enable-test-js"])){enable_tests();}
if(isset($_GET["Content-Security-Policy-Report-Only-js"])){ContentSecurityPolicyReportOnly_js();exit;}
if(isset($_GET["Content-Security-Policy-Report-Only-popup"])){ContentSecurityPolicyReportOnly_popup();exit;}

function ContentSecurityPolicyReportOnly_js():bool{
    $serviceid  = intval($_GET["Content-Security-Policy-Report-Only-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    return $tpl->js_dialog6("Content Security Policy Report Only","$page?Content-Security-Policy-Report-Only-popup=$serviceid",650);
}
function ContentSecurityPolicyReportOnly_popup():bool{
    $serviceid  = intval($_GET["Content-Security-Policy-Report-Only-popup"]);
    $sock       = new socksngix($serviceid);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $CSPTestContent=$sock->GET_INFO("CSPTestContent");
    if(strlen($CSPTestContent)<5){
        $CSPTestContent="default-src 'self'; script-src 'self' https://trusted.cdn.com; report-uri /csp-violation-report-endpoint";
    }

    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_textareaP("CSPTestContent","",$CSPTestContent);
    $html[]=$tpl->form_outside("",$form,"{Content-Security-Policy-Report-Only}","{apply}","LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')","AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ContentSecurityPolicyReportOnly_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("CSPTestContent",$_POST["CSPTestContent"]);
    $servername=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks_post("Save Content-Security-Policy-Report-Only for $servername");
}
function service_js(){
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tpl->js_dialog4("Content Security Policy (CSP)","$page?popup-main=$serviceid");
}
function rule_js(){
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();

    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}

    $tpl->js_dialog5("Content Security Policy (CSP): $title","$page?popup-rule=$rule&serviceid=$serviceid");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $ruleid=intval($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("csp_rules")));
    unset($data[$ruleid]);
    $encoded=serialize($data);
    $sock->SET_INFO("csp_rules",base64_encode($encoded));
    echo "$('#$ruleid').remove();\n";
    echo "LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";
    echo refresh_global_no_close($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;
}
function refresh_global_no_close($serviceid):string{
    $f[]="LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid')";
    return @implode(";",$f);

}

function rule_enable():bool{
    $ruleid=intval($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("csp_rules")));
    if(intval($data[$ruleid]["enable"])==1){
        $data[$ruleid]["enable"]=0;
    }else{
        $data[$ruleid]["enable"]=1;
    }
    $encoded=serialize($data);
    $sock->SET_INFO("csp_rules",base64_encode($encoded));
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;
}



function rule_popup(){
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("csp_rules")));
    $ligne["enable"] = 1;
    $bt="{add}";
    if($ruleid>0){ $ligne=$data[$ruleid];$bt="{apply}"; }

    $jsrestart="dialogInstance5.close();LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');". refresh_global_no_close($serviceid);

    $types["default-src"]="default-src";
    $types["script-src"]="script-src";
    $types["style-src"]="style-src";
    $types["object-src"]="object-src";
    $types["img-src"]="img-src";
    $types["media-src"]="media-src";
    $types["frame-src"]="frame-src";
    $types["font-src"]="font-src";
    $types["connect-src"]="connect-src";
    $types["form-action"]="form-action";
    $types["worker-src"]="worker-src";
    $types["report-to"]="report-to";
    $types["report-uri"]="report-uri";
    $types["base-uri"]="base-uri";
    $types["upgrade-insecure-requests"]="upgrade-insecure-requests";
    $types["navigate-to"]="navigate-to";
    $types["require-trusted-types-for"]="require-trusted-types-for";

    if(!isset($ligne["type"])){
        $ligne["type"]="default-src";
    }

    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_checkbox("enable","{enable}",$ligne["enable"]);
    $form[]=$tpl->field_array_hash($types,"ztype","{type}",$ligne["type"]);
    $form[]=$tpl->field_text("value","{value}",$ligne["value"]);
    $html[]=$tpl->form_outside("{rule} $ruleid",$form,null,$bt,$jsrestart,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);

}

function rule_save(){
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $ruleid     = intval($_POST["ruleid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("csp_rules")));
    if($ruleid==0){
        $ruleid=time()+rand(0,5);
    }

    $_POST["type"]=$_POST["ztype"];
    $_POST["value"]=str_replace("'","",$_POST["value"]);

    $data[$ruleid]=$_POST;
    $encoded=serialize($data);
    $sock->SET_INFO("csp_rules",base64_encode($encoded));
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    $servername=get_servicename($serviceid);
    return admin_tracks_post("Save CSP rule #$ruleid for $servername");
}

function popup_main(){
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')</script>";
}

function rule_export_js(){
    $serviceid=intval($_GET["rule-export-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tpl->js_dialog6("Content Security Policy (CSP): {export}/{import}","$page?rule-export-popup=$serviceid",950);
}

function rule_export_popup(){
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $serviceid=intval($_GET["rule-export-popup"]);
    $sock       = new socksngix($serviceid);
    $tpl->field_hidden("importid","$serviceid");
    $jsrestart="dialogInstance6.close();LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";

    $form[]=$tpl->field_textarea("export", "{rules}", $sock->GET_INFO("csp_rules"),"664px");
    echo $tpl->form_outside("{export}/{import}", @implode("\n", $form),null,"{apply}",$jsrestart);
}
function rule_export_save(){
    $serviceid=intval($_POST["importid"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("csp_rules",$_POST["export"]);
}
function enable_feature():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["serviceid"]);
    $enable=intval($_GET["enable-rule-js"]);
    $sockngix=new socksngix(($serviceid));
    $sockngix->SET_INFO("EnableCSP",$enable);
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjaxSilent('progress-buttons-$serviceid','$page?popup-buttons=$serviceid')";

    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Turn feature to $enable for Content Security Policies headers on  $get_servicename reverse-proxy site");


}

function enable_tests():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["serviceid"]);
    $enable=intval($_GET["enable-test-js"]);
    $sockngix=new socksngix(($serviceid));
    $sockngix->SET_INFO("EnableCSPTests",$enable);
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')";
    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Turn feature TEST CSP $enable for Content Security Policies headers on  $get_servicename reverse-proxy site");

}

function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}

function popup_buttons():bool{
    $serviceid  = intval($_GET["popup-buttons"]);
    $sock       = new socksngix($serviceid);
    $EnableCSP=intval($sock->GET_INFO("EnableCSP"));
    $EnableCSPTests=intval($sock->GET_INFO("EnableCSPTests"));


    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=currentPageName();

    if($EnableCSP==1){
        $topbuttons[] = array("Loadjs('$page?enable-rule-js=0&serviceid=$serviceid');", ico_run, "{disable_feature}");

    }else{
        $topbuttons[] = array("Loadjs('$page?enable-rule-js=1&serviceid=$serviceid');", ico_disabled, "{enable_feature}","OFF");
   }

     if($EnableCSPTests==1){
         $topbuttons[] = array("Loadjs('$page?enable-test-js=0&serviceid=$serviceid');", ico_run, "{tests_mode} ON");
     }else{
         $topbuttons[] = array("Loadjs('$page?enable-test-js=1&serviceid=$serviceid');", ico_run, "{tests_mode} OFF");
     }

    $topbuttons[] = array("Loadjs('$page?rule-js=0&serviceid=$serviceid');", ico_plus, "{new_rule}");

    $topbuttons[] = array("Loadjs('$page?rule-export-js=$serviceid');", ico_import, "{export}/{import}");

    echo $tpl->th_buttons($topbuttons);
    return true;

}

function popup_table():bool{
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $tableid    = time();



    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="<div id='progress-buttons-$serviceid'></div>";

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{type}</th>
        	<th nowrap>{value}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $data=unserialize(base64_decode($sock->GET_INFO("csp_rules")));

    if(!is_array($data)){
        $data=array();
    }
    if(count($data)==0){

        $csp["default-src"][]="self";
        $csp["default-src"][]="unsafe-inline";
        $csp["frame-ancestors"][]="self";
        $csp["form-action"][]="self";
        $csp["font-src"][]="self";
        $csp["font-src"][]="fonts.gstatic.com";
        $csp["style-src"][]="self";
        $csp["style-src"][]="fonts.googleapis.com";
        $csp["script-src"][]="self";
        $csp["script-src"][]="https://www.googletagmanager.com";
        $csp["img-src"][]="self";
        $csp["img-src"][]="www.googletagmanager.com";
        foreach ($csp as $type=>$array){
            foreach ($array as $index=>$line){
                $ruleid=time()+rand(0,5)+$index;
                $data[$ruleid]["type"]=$type;
                $data[$ruleid]["value"]=$line;
                $data[$ruleid]["enable"]=1;
            }

        }

        $sock->SET_INFO("csp_rules",base64_encode(serialize($data)));
    }

    $EnableCSPTests=intval($sock->GET_INFO("EnableCSPTests"));
    if($EnableCSPTests==1){
        $CSPTestContent=$sock->GET_INFO("CSPTestContent");
        if(strlen($CSPTestContent)<5){
            $CSPTestContent="default-src 'self'; script-src 'self' https://trusted.cdn.com; report-uri /csp-violation-report-endpoint";
        }


        $url=$tpl->td_href("Content-Security-Policy-Report-Only","","Loadjs('$page?Content-Security-Policy-Report-Only-js=$serviceid')");

        $html[]="<tr id='NONE'>
				<td style='width:50%'>$url</td>
				<td style='width:50%' >$CSPTestContent</td>
				<td style='width:1%'  nowrap >&nbsp;</td>
				<td style='width:1%'  nowrap >&nbsp;</td>
				</tr>";
    }


    foreach ($data as $num=>$ligne){
        $enable=intval($ligne["enable"]);
        $value=trim($ligne["value"]);
        $type=$ligne["type"];

        if(strlen($value)>128){$value=substr($value,0,125)."...";}
        $value=htmlentities($value);

        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$num&serviceid=$serviceid')","","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$num&serviceid=$serviceid')","AsWebMaster");
        $value=$tpl->td_href($value,"","Loadjs('$page?rule-js=$num&serviceid=$serviceid');");

    $html[]="<tr id='$num'>
				<td style='width:50%'>$type</td>
				<td style='width:50%' >$value</td>
				<td style='width:1%'  nowrap >$enable</td>
				<td style='width:1%'  nowrap >$delete</td>
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
    $html[]="LoadAjaxSilent('progress-buttons-$serviceid','$page?popup-buttons=$serviceid');";
        
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        }