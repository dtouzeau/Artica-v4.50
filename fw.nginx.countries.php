<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.rtmm.tools.inc");

if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-table2"])){popup_table2();exit;}
if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["Country"])){rule_save();exit;}
if(isset($_GET["enable-rule-js"])){enable_feature();}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}


function enable_feature():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["serviceid"]);
    $enable=intval($_GET["enable-rule-js"]);
    $sockngix=new socksngix($serviceid);
    $sockngix->SET_INFO("FilterCountries",$enable);
    $get_servicename=get_servicename($serviceid);
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjaxSilent('countries-nginx-$serviceid','$page?top-buttons=$serviceid');";

    header("content-type: application/x-javascript");
    echo @implode(";",$f);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Turn feature to $enable for deny Countries on  $get_servicename reverse-proxy site");

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

function service_js():bool{
    $serviceid  = intval($_GET["service-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    return $tpl->js_dialog4("{countries}","$page?popup-main=$serviceid");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    return $tpl->js_dialog5("{countries}: $title","$page?popup-rule=$rule&serviceid=$serviceid");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $md=$_GET["md"];
    $Country=$_GET["pattern-remove"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FCountries")));
    unset($data[$Country]);
    $encoded=serialize($data);
    $sock->SET_INFO("FCountries",base64_encode($encoded));
    echo "$('#$md').remove();\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    echo refresh_global_no_close($serviceid);
    return true;

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
function rule_enable():bool{
    $Country=$_GET["pattern-enable"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FCountries")));
    if(intval($data[$Country])==1){
        $data[$Country]=0;
    }else{
        $data[$Country]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FCountries",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    return admin_tracks("Enable={$data[$Country]} For reverse-proxy $get_servicename User-Agent rule $Country");
}

function rule_popup():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $bt="{add}";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("Country","{http_user_agent}","",true);
    $html[]=$tpl->form_outside(null,$form,null,$bt,refresh_global($serviceid),"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function refresh_global_no_close($serviceid):string{
    $f[]="LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');";
    return @implode(";",$f)."\n";

}

function refresh_global($serviceid):string{
    $page=CurrentPageName();
    $f[]=refresh_global_no_close($serviceid);
    $f[]="LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');";
    $f[]="dialogInstance5.close();";
    return @implode(";",$f);
}

function rule_export_js(){
    $serviceid=intval($_GET["rule-export-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog6("{http_user_agent}: {export}/{import}","$page?rule-export-popup=$serviceid",950);
}

function rule_export_popup(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $serviceid=intval($_GET["rule-export-popup"]);
    $sock       = new socksngix($serviceid);
    $tpl->field_hidden("importid","$serviceid");
    $jsrestart="dialogInstance6.close();LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid');LoadAjax('nginx-options-$serviceid','fw.nginx.reverse-options.php?main=yes&service=$serviceid');";

    $form[]=$tpl->field_textarea("export", "{rules}", $sock->GET_INFO("FCountries"),"664px");
    echo $tpl->form_outside("{export}/{import}", @implode("\n", $form),null,"{apply}",$jsrestart,null);
}
function rule_export_save(){
    $serviceid=intval($_POST["importid"]);
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $sock       = new socksngix($serviceid);
    $sock->SET_INFO("FCountries",$_POST["export"]);
}

function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $serviceid  = intval($_POST["serviceid"]);
    $sock       = new socksngix($serviceid);
    $data       = unserialize(base64_decode($sock->GET_INFO("FCountries")));

    if(trim($_POST["Country"])==null){return false;}
    $data[$_POST["Country"]]=1;
    $encoded=serialize($data);
    $sock->SET_INFO("FCountries",base64_encode($encoded));
    $get_servicename=get_servicename($serviceid);
    return admin_tracks("Add a new User-Agent {$_POST["Country"]} to deny for reverse-proxy $get_servicename");

}

function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid')</script>";
    return true;
}



function popup_table():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $tpl        = new template_admin();
    echo "<div id='countries-nginx-$serviceid' style='margin-bottom:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&popup-table2=$serviceid");
    return true;
}

function top_buttons():bool{
    $serviceid  = intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $FilterCountries=intval($sock->GET_INFO("FilterCountries"));
    if($FilterCountries==1){
        $topbuttons[] = array("Loadjs('$page?enable-rule-js=0&serviceid=$serviceid')", ico_check, "{active2}");
    }else{
        $topbuttons[] = array("Loadjs('$page?enable-rule-js=1&serviceid=$serviceid')", ico_disabled, "{disabled}");

    }


    $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&serviceid=$serviceid&function=$function')", ico_filter, "{OnlyActive}");


    $topbuttons[] = array("Loadjs('$page?disableall=yes&serviceid=$serviceid&function=$function')", ico_disabled, "{disable_all}");
    $topbuttons[] = array("Loadjs('$page?enableall=yes&serviceid=$serviceid&function=$function')", ico_check, "{enable_all}");




    if(!isHarmpID()) {
        $compile_js_progress=compile_js_progress($serviceid);
        $topbuttons[] = array($compile_js_progress, ico_save, "{apply}");
    }
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function rule_disable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $GeoIPCountriesList=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPCountriesList"));
    foreach ($GeoIPCountriesList as $IsoCodes => $Names) {
        $data[$IsoCodes]=0;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FCountries",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Disable all user-Agent Deny For reverse-proxy $get_servicename");
}
function rule_enable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);

    $GeoIPCountriesList=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPCountriesList"));
    foreach ($GeoIPCountriesList as $IsoCodes => $Names) {
        $data[$IsoCodes]=1;
    }
    $encoded=serialize($data);
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("FCountries",base64_encode($encoded));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return admin_tracks("Enable all Countries For reverse-proxy $get_servicename");
}
function popup_table2():bool{
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $sock       = new socksngix($serviceid);
    $function =$_GET["function"];
    $tableid    = time();
    $html[]="<div id='progress-compile-replace-$serviceid'></div>";
    $html[]="</div>";

    $search=$_GET["search"];

    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    if($EnableGeoipUpdate==0){
        $html[]=$tpl->div_warning("{warning}||{GeoIPUpdate_not_installed}");
    }


    $html[]="
<table class=\"table table-hover\" id='$tableid'>
	<thead>
    	<tr>
        	<th nowrap colspan='2'>{countries}</th>
        	<th nowrap>{enable}</small></th>
        </tr>
  	</thead>
	<tbody>
";

    $OnlyActive=false;
    $KeyActive=basename(__FILE__)."OnlyActive";
    if(isset($_SESSION[$KeyActive])){
        $OnlyActive=true;
    }
    $data=unserialize(base64_decode($sock->GET_INFO("FCountries")));
    $GeoIPCountriesList=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPCountriesList"));

    if(!$GeoIPCountriesList){
        $GeoIPCountriesList=array();
    }

    if(count($GeoIPCountriesList)==0){
        $html[]=$tpl->div_warning("{warning}||{geoipdb_not_updated}");

    }

    foreach ($GeoIPCountriesList as $IsoCodes => $Names) {
        $flag=GetFlags($Names);
        if($search<>null){
            $search=str_replace(".","\.",$search);
            $search=str_replace("*",".*?",$search);
            if(!preg_match("#$search#i","$IsoCodes $Names")){
                continue;
            }
        }


        if(strlen($Names)>128){$Names=substr($Names,0,125)."...";}
        $Names=htmlentities($Names);
        $md=md5($Names);

        if(!isset($data[$IsoCodes])){
            $data[$IsoCodes]=0;
        }
        $enable= $data[$IsoCodes];

        if($OnlyActive){
            if($enable==0){
                continue;
            }
        }

        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$IsoCodes&serviceid=$serviceid')","","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$IsoCodes&serviceid=$serviceid&md=$md')","AsWebMaster");


    $html[]="<tr id='$md'>
                <td width=1%  nowrap ><img src='img/$flag'></td>
				<td width=100%>$Names</td>
				<td width=1%  nowrap >$enable</td>
				</tr>";

    }

        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="LoadAjax('countries-nginx-$serviceid','$page?top-buttons=$serviceid&function=$function');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        }