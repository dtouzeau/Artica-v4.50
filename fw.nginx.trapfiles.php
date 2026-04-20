<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");

if(isset($_GET["enable-feature"])){popup_enable();exit;}
if(isset($_GET["disable-feature"])){popup_disable();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-search"])){popup_search();exit;}

if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}


function service_js(){
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    return $tpl->js_dialog6("{trap_files}","$page?popup-main=$serviceid",650);
}
function popup_enable():bool{
    $function=$_GET["function"];
    $serviceid  = intval($_GET["enable-feature"]);
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("EnableTrapFiles",1);

    $data       = json_decode($sock->GET_INFO("trap_files"),true);
    if(!is_array($data)){$data=array();}

    if(count($data)<5) {
        $data = rule_defaults();
        $sock->SET_INFO("trap_files", json_encode($data));
    }
    $servicename=get_servicename($serviceid);
    header("content-type: application/x-javascript");
    echo "$function();\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Enable to Trap files feature for $servicename");
}
function popup_disable():bool{
    $function=$_GET["function"];
    $serviceid  = intval($_GET["disable-feature"]);
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("EnableTrapFiles",0);
    $servicename=get_servicename($serviceid);
    header("content-type: application/x-javascript");
    echo "$function();\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Disable to Trap files feature for $servicename");
}
function rule_js(){
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = urlencode($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $function   = $_GET["function"];

    $title      = "";
    if($rule==""){$title="{new_file}";}

    $tpl->js_dialog7("{file}: $title","$page?popup-rule=$rule&serviceid=$serviceid&function=$function");
}
function compile_js_progress($ID):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
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
function rule_remove():bool{
    $md=$_GET["num"];
    $ruleid=base64_decode($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = json_decode($sock->GET_INFO("trap_files"),true);
    unset($data[$ruleid]);
    $encoded=json_encode($data);
    $sock->SET_INFO("trap_files",$encoded);
    echo "$('#$md').remove();\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    echo "LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";
    return true;

}

function rule_enable():bool{
    $ruleid=base64_decode($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $dataTxt=$sock->GET_INFO("trap_files");
    VERBOSE($dataTxt,__LINE__);
    $data       = json_decode($dataTxt,true);
    if(!isset($data[$ruleid])){
        $data[$ruleid]=1;
    }

    if(intval($data[$ruleid])==1){
        $data[$ruleid]=0;
    }else{
        $data[$ruleid]=1;
    }
    $sock->SET_INFO("trap_files",json_encode($data));
    $servicename=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Set rule $ruleid to enable=$data[$ruleid] for Trap files feature on $servicename");
}

function rule_popup(){
    $serviceid  = intval($_GET["serviceid"]);
    $RuleInd=$_GET["popup-rule"];
    $ruleSrc     = base64_decode($RuleInd);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $function   = $_GET["function"];



    
    $ligne["enable"] = 1;
    $bt="{add}";
    if(strlen($ruleSrc)>3){ $bt="{apply}"; }
    $jsrestart="dialogInstance7.close();LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');$function();";
    $form[]=$tpl->field_hidden("ruleid",$RuleInd);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_checkbox("enable","{enable}",$ligne["enable"]);
    $form[]=$tpl->field_textarea_normal("trapfile","{file}",$ruleSrc,"470px","50px");
    $html[]=$tpl->form_outside("",$form,null,$bt,$jsrestart,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);

}

function rule_export_js(){
    $serviceid=intval($_GET["rule-export-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tpl->js_dialog6("{header_checks}: {export}/{import}","$page?rule-export-popup=$serviceid",950);
}

function rule_defaults():array{
    $f[]="/admin.php";
    $f[]="regex:/(\.env|info|phpinfo|test)";
    $f[]="/backup.zip";
    $f[]="regex:/(sito|cms|media|wp2|site|[0-9]+|news|wp|wordpress|blog)/wp-includes/wlwmanifest\.xml";
    $f[]="/login/";
    $f[]="/app/";
    $f[]="/js../.git/config";
    $f[]="/.github/workflows/ci.yml";
    $f[]="/.github/workflows/main.yml";
    $f[]="/.git/config";
    $f[]="regex:/(assets|static|js|css)\.\./.git/config";
    $f[]="/admin/.git/config";
    $f[]="/api/.git/config";
    $f[]="/js/lkk_ch.js";
    $f[]="/js/twint_ch.js";
    $f[]="regex:/(i|php|check|test|php_info|info|phpversion|php_version|phptest|diagnostic|debug|server|serverinfo)\.php";
    $f[]="regex:/(info1|info2|pi)\.php";
    $f[]="regex:/(test|php_info|info|\.env)\.php\.(tmp|swp|backup|orig|bak|save|sample|example|test|development|staging|prod|local|new)";
    $f[]="regex:/\.env\.(tmp|swp|backup|orig|bak|save|sample|example|test|development|staging|dev|production|prod|local|new)";
    $f[]="regex/(backend|kyc|config|node_modules|main|xampp|site|public|nginx|mailer|mail|laravel|js|docker|cron|conf|awstats|aws-secret|new\.vscode)/.env";
    $f[]="regex:/(app|api|admin|storage|backend|vendor|themes|plugins|modules|core|system|application|lib|includes|assets|public|src|config|new)/\.env";
    $f[]="regex:/(app|api|storage|admin|backend|vendor|themes|plugins|modules|core|system|application|lib|includes|assets|public|src|config|new)/\.env\.(old|bak|save)";
    $f[]="regex:/(site|www|web|cgi-bin|scripts|includes|tmp|dev|test)/(info\.php\.save|phpinfo\.php\.save|phpinfo\.php)";
    $f[]="/site/phpinfo.php.save";
    $f[]="/site/phpinfo.php";

    foreach ($f as $index=>$item) {
        $array[$item]=1;
    }
    return $array;
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
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $ruleSrc     = base64_decode($_POST["ruleid"]);
    $newcontent =$_POST["trapfile"];
    $enable=intval($_POST["enable"]);
    $sock       = new socksngix($serviceid);
    $data       = json_decode($sock->GET_INFO("trap_files"),true);
    if(!is_array($data)){$data=rule_defaults();}

    if(count($data)==0){
        $data=rule_defaults();
    }
    unset($data[$ruleSrc]);
    $data[$newcontent]=$enable;
    $sock->SET_INFO("trap_files",json_encode($data));
    $servicename=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Edit rule $newcontent to Trap files feature for $servicename");

}

function popup_main(){
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')</script>";
}

function popup_table():bool{
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    echo $tpl->search_block($page,null,null,null,"&popup-search=$serviceid");
    return true;
}

function popup_search():bool{
    $serviceid  = intval($_GET["popup-search"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $function   = $_GET["function"];
    $tableid    = time();
    $EnableTrapFiles=intval($sock->GET_INFO("EnableTrapFiles"));

    if($EnableTrapFiles==0){
        $bton=$tpl->button_autnonome("{enable_feature}","Loadjs('$page?enable-feature=$serviceid&function=$function')",ico_cd,
            "AsWebMaster",335,"btn-default");
        echo $tpl->_ENGINE_parse_body($tpl->div_explain("{trap_files}||{nginx_trap_files_explain}<div style='margin:30px;text-align:right'>$bton</div>"));
        return true;
    }


    $compile_js_progress=compile_js_progress($serviceid);
    $Disabled_feature="Loadjs('$page?disable-feature=$serviceid&function=$function')";
    $Disabled_ico=ico_trash;

    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?rule-js=&serviceid=$serviceid');\">
	<i class='fa fa-plus'></i> {new_rule} </label>";
    $html[]="<label class=\"btn btn btn-danger\" OnClick=\"$Disabled_feature\">
	<i class='$Disabled_ico'></i> {disable_feature} </label>";
    $html[]="</div>";

    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{trap_files}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $data       = json_decode($sock->GET_INFO("trap_files"),true);
    if(!is_array($data)){$data=rule_defaults();}

    if(count($data)==0){
        $data=rule_defaults();
        $sock->SET_INFO("trap_files",json_encode($data));
    }

    foreach ($data as $pattern=>$enable){
        $patternDisplay=$pattern;
        if(strlen($patternDisplay)>60){$patternDisplay=substr($pattern,0,57)."...";}
        $patternencoded=urlencode(base64_encode($pattern));
        $patternDisplay=htmlentities($patternDisplay);
        $num=md5($pattern);
        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$patternencoded&serviceid=$serviceid')","","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$patternencoded&serviceid=$serviceid&num=$num')","AsWebMaster");
        $patternDisplay=$tpl->td_href($patternDisplay,"","Loadjs('$page?rule-js=$patternencoded&serviceid=$serviceid&function=$function');");

    $html[]="<tr id='$num'>
				<td style='width:100%'>$patternDisplay</td>
				<td style='width:1%' nowrap>$enable</td>
				<td style='width:1%' nowrap>$delete</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="<tfoot>";

        $html[]="<tr>";
        $html[]="<td colspan='3'>";
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
        return true;
        }