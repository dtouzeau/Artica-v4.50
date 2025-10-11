<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");

if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}
if(isset($_GET["enable-rule-default"])){enable_default();}

if(isset($_GET["rule-export-js"])){rule_export_js();exit;}
if(isset($_GET["rule-export-popup"])){rule_export_popup();exit;}
if(isset($_POST["importid"])){rule_export_save();exit;}

function enable_default():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["serviceid"]);
    $enable=intval($_GET["enable-rule-default"]);
    $sockngix=new socksngix(($serviceid));
    $function=$_GET["function"];
    $sockngix->SET_INFO("DisableHideHeadersDefault",$enable);
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global_no_close($serviceid);
    $f[]="$function();";

    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Turn feature to $enable for Hide default rules on $get_servicename reverse-proxy site");
}
function refresh_global_no_close($serviceid):string{
    $f[]="LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');";
    return @implode(";",$f)."\n";

}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function service_js(){
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tpl->js_dialog4("{header_remove_rules}","$page?popup-main=$serviceid");
}
function rule_js(){
    $serviceid  = intval($_GET["serviceid"]);

    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $function = $_GET["function"];
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}

    $tpl->js_dialog5("{header_remove_rules}: $title","$page?popup-rule=$rule&serviceid=$serviceid&function=$function");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $ruleid=intval($_GET["pattern-remove"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserializeb64($sock->GET_INFO("proxy_hide_headers"));
    unset($data[$ruleid]);
    $encoded=serialize($data);
    $sock->SET_INFO("proxy_hide_headers",base64_encode($encoded));
    echo "$('#$ruleid').remove();\n";
    echo "LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;

}

function rule_enable():bool{
    $ruleid=intval($_GET["pattern-enable"]);
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserializeb64($sock->GET_INFO("proxy_hide_headers"));
    if(intval($data[$ruleid]["enable"])==1){
        $data[$ruleid]["enable"]=0;
    }else{
        $data[$ruleid]["enable"]=1;
    }
    $encoded=serialize($data);
    $sock->SET_INFO("proxy_hide_headers",base64_encode($encoded));
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;
}



function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $function = $_GET["function"];
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $sock       = new socksngix($serviceid);
    $data       = unserializeb64($sock->GET_INFO("proxy_hide_headers"));
    $ligne["enable"] = 1;
    $bt="{add}";
    if($ruleid>0){ $ligne=$data[$ruleid];$bt="{apply}"; }
    $jsrestart="dialogInstance5.close();$function();".refresh_global_no_close($serviceid);
    $form[]=$tpl->field_hidden("ruleid",$ruleid);

    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_hidden("header_value","false");
    $form[]=$tpl->field_checkbox("enable","{enable}",$ligne["enable"]);
    $form[]=$tpl->field_text("header","{remove_http_header}",$ligne["header"]);
    $form[]=$tpl->field_checkbox("bonly","{backends_only}",$ligne["bonly"]);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    $html[]=$tpl->form_outside("{rule} $ruleid",$form,null,$bt,$jsrestart,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function rule_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $ruleid     = intval($_POST["ruleid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserializeb64($sock->GET_INFO("proxy_hide_headers"));
    if($ruleid==0){
        $ruleid=time()+rand(0,5);
    }
    $data[$ruleid]=$_POST;
    $encoded=serialize($data);
    $sock->SET_INFO("proxy_hide_headers",base64_encode($encoded));
    $get_servicename=get_servicename($serviceid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks_post("Add Hide header for the reverse-proxy $get_servicename");

}


function popup_main():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-main"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    echo "<div id='remove-headers-nginx-$serviceid' style='margin-bottom:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&popup-table=$serviceid");
    return true;
}

function rule_export_js(){
    $serviceid=intval($_GET["rule-export-js"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $tpl->js_dialog6("{header_remove_rules}: {export}/{import}","$page?rule-export-popup=$serviceid",950);
}

function rule_export_popup(){
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $serviceid=intval($_GET["rule-export-popup"]);
    $sock       = new socksngix($serviceid);
    $tpl->field_hidden("importid","$serviceid");
    $jsrestart="dialogInstance6.close();LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";

    $form[]=$tpl->field_textarea("export", "{rules}", $sock->GET_INFO("proxy_hide_headers"),"664px");
    echo $tpl->form_outside("{export}/{import}", @implode("\n", $form),null,"{apply}",$jsrestart,null);
}
function rule_export_save(){
    $serviceid=intval($_POST["importid"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("proxy_hide_headers",$_POST["export"]);
}
function top_buttons():bool{
    $serviceid  = intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sockngix       = new socksngix($serviceid);
    $DisableHideHeadersDefault=intval($sockngix->GET_INFO("DisableHideHeadersDefault"));
    if($DisableHideHeadersDefault==0){
            $topbuttons[] = array("Loadjs('$page?enable-rule-default=1&serviceid=$serviceid&function=$function')", ico_check, "{default} {rules} {active2}");
        }else{
            $topbuttons[] = array("Loadjs('$page?enable-rule-default=0&serviceid=$serviceid&function=$function')", ico_disabled, "{default} {rules} {disabled}");

    }


    $topbuttons[] = array("Loadjs('$page?rule-js=0&serviceid=$serviceid&function=$function')", ico_plus, "{new_rule}");
  //  $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&serviceid=$serviceid&function=$function')", ico_filter, "{OnlyActive}");

    if(!isHarmpID()) {
        if($serviceid>0) {
            $compile_js_progress = compile_js_progress($serviceid);
            $topbuttons[] = array($compile_js_progress, ico_save, "{apply}");
        }
    }
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function HarmpID():int{
    if(!isset($_SESSION["HARMPID"])){return 0;}
    if(intval($_SESSION["HARMPID"])==0){
        return 0;
    }
    return intval($_SESSION["HARMPID"]);
}

function isHarmpID():bool{
    $HarmpID=HarmpID();
    if($HarmpID==0){return false;}

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}

function popup_table():bool{
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $tableid    = time();
    $function=$_GET["function"];


    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap>{http_headers}</th>
        	<th nowrap>{enable}</small></th>
        	<th nowrap>{delete}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $data=unserializeb64($sock->GET_INFO("proxy_hide_headers"));

    $DisableHideHeadersDefault=intval($sock->GET_INFO("DisableHideHeadersDefault"));
    if($DisableHideHeadersDefault==0){
        $DefaultXHeaders["X-Host"]=true;
        $DefaultXHeaders["X-Config-id"]=true;
        $DefaultXHeaders["X-Cache-status"]=true;
        $DefaultXHeaders["X-Powered-By"]=true;
        $DefaultXHeaders["X-AspNet-Version"]=true;
        $DefaultXHeaders["X-AspNetMvc-Version"]=true;
        $DefaultXHeaders["X-Generator"]=true;
        $DefaultXHeaders["Via"]=true;
        $DefaultXHeaders["X-Runtime"]=true;
        $DefaultXHeaders["X-Version"]=true;


        foreach ($DefaultXHeaders as $HeaderName=>$none){
            $num=md5($HeaderName);
            $html[]="<tr id='$num'>
				<td style='width:50%'>{remove2} $HeaderName</td>
				<td style='width:1%'  nowrap >&nbsp;</td>
				<td style='width:1%'  nowrap >&nbsp;</td>
				</tr>";

        }

    }



    foreach ($data as $num=>$ligne){
        $enable=intval($ligne["enable"]);
        $bonly=intval($ligne["bonly"]);
        $description=trim($ligne["description"]);
        $pattern=$ligne["header"];
        if(strlen($pattern)>128){$pattern=substr($pattern,0,125)."...";}
        $pattern=htmlentities($pattern);
        if($description<>null){
            $description="<br><small>$description</small>";
        }
        $backendopts="";
        if($bonly==1){
            $backendopts=" <i>({backends_only})</i>&nbsp;";
        }

    $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$num&serviceid=$serviceid')","","AsWebMaster");
    $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$num&serviceid=$serviceid')","AsWebMaster");
    $pattern=$tpl->td_href($pattern,"","Loadjs('$page?rule-js=$num&serviceid=$serviceid&function=$function');");

    $html[]="<tr id='$num'>
				<td style='width:50%'>{remove2} $pattern$backendopts{$description}</td>
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
        $html[]="LoadAjax('remove-headers-nginx-$serviceid','$page?top-buttons=$serviceid&function=$function');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }