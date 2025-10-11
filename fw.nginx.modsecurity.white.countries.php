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

service_js();
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
    $serviceid  = intval($_GET["serviceid"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    if($EnableGeoipUpdate==0){
        return $tpl->js_error("{GeoIPUpdate_not_installed}");
    }
    return $tpl->js_dialog7("{countries}","$page?popup-main=$serviceid");
}
function rule_js():bool{
    $serviceid  = intval($_GET["serviceid"]);
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    return $tpl->js_dialog7("{countries}: $title","$page?popup-rule=$rule&serviceid=$serviceid");
}
function compile_js_progress($ID,$final=null):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

}

function rule_remove():bool{
    $md=$_GET["md"];
    $Country=$_GET["pattern-remove"];
    $serviceid=intval($_GET["serviceid"]);
    $sock       = new socksngix($serviceid);

    $dataSource=trim($sock->GET_INFO("ModSecurityExcludeCountries"));
    $data=array();
    if(strpos("   $dataSource",",")>0) {
        $tb = explode(",", $dataSource);
        foreach ($tb as $IsoCode) {
            $data[$IsoCode] = true;
        }
    }else{
        $data[$dataSource]=true;
    }

    unset($data[$Country]);
    $f=array();
    foreach ($data as $code=>$value) {
        $f[]=$code;
    }
    $sock->SET_INFO("ModSecurityExcludeCountries",@implode(",",$f));
    echo "$('#$md').remove();\n";
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

    $dataSource=trim($sock->GET_INFO("ModSecurityExcludeCountries"));
    $data=array();
    if(strpos("   $dataSource",",")>0) {
        $tb = explode(",", $dataSource);
        foreach ($tb as $IsoCode) {
            $data[$IsoCode] = true;
        }
    }else{
        $data[$dataSource]=true;
    }
    $data[$Country]=true;

    $f=array();
    foreach ($data as $IsoCode=>$Value) {
        $f[]=$IsoCode;
    }
    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("ModSecurityExcludeCountries",@implode(",",$f));
    echo refresh_global_no_close($serviceid);
    return admin_tracks("Whitelist country $Country from reverse-proxy $get_servicename WAF engine");
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
    $f[]="LoadAjax('modsecurity-parameters-$serviceid','fw.nginx.sites.modsecurity.php?www-parameters2=$serviceid');";
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

    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("ModSecurityExcludeCountries","");
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all whitelisted country from WAF on $get_servicename");
}
function rule_enable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    $sock       = new socksngix($serviceid);
    $GeoIPCountriesList=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPCountriesList"));
    $f=array();
    foreach ($GeoIPCountriesList as $IsoCodes => $Names) {
       $f[]=$IsoCodes;
    }

    $get_servicename=get_servicename($serviceid);
    $sock->SET_INFO("ModSecurityExcludeCountries",@implode(",",$f));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Whitelist all Countries for reverse-proxy WAF on $get_servicename");
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

    $dataSource=trim($sock->GET_INFO("ModSecurityExcludeCountries"));
    $data=array();
    if(strpos("   $dataSource",",")>0) {
        $tb = explode(",", $dataSource);
        foreach ($tb as $IsoCode) {
            $data[$IsoCode] = true;
        }
    }else{
        $data[$dataSource]=true;
    }

    $GeoIPCountriesList=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPCountriesList"));
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

        $enable=$tpl->icon_add("Loadjs('$page?pattern-enable=$IsoCodes&serviceid=$serviceid')","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$IsoCodes&serviceid=$serviceid&md=$md')","AsWebMaster");

        if(!isset($data[$IsoCodes])){
            $delete="";
            $data[$IsoCodes]=0;
        }else{
            $enable="<div class='center'><i class='".ico_check."'></i></div>";
        }


        if($OnlyActive){
            if(!isset($data[$IsoCodes])){
                continue;
            }
        }

        $html[]="<tr id='$md'>
                    <td style='vertical-align:middle;width:1%' nowrap ><img src='img/$flag'></td>
                    <td style='width:100%'>$Names</td>
                    <td style='vertical-align:middle;width:1%' nowrap >$enable</td>
                    <td style='vertical-align:middle;width:1%' nowrap >$delete</td>
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