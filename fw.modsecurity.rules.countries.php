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
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["Country"])){rule_save();exit;}
if(isset($_GET["enable-rule-js"])){enable_feature();}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["disableall"])){rule_disable_all();exit;}
if(isset($_GET["enableall"])){rule_enable_all();exit;}
if(isset($_GET["OnlyActive"])){OnlyActive();exit;}

service_js();


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
    $serviceid  = intval($_GET["id"]);
    $tpl        = new template_admin();
    $suffixForm=$_GET["suffix-form"];
    $page       = CurrentPageName();
    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    if($EnableGeoipUpdate==0){
        return $tpl->js_error("{GeoIPUpdate_not_installed}");
    }
    return $tpl->js_dialog7("{countries}","$page?popup-main=$serviceid&suffix-form=$suffixForm");
}
function rule_remove():bool{
    $md=$_GET["md"];
    $Country=$_GET["pattern-remove"];
    $serviceid=intval($_GET["serviceid"]);


    $dataSource=GetDataSource($serviceid);
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

    echo "$('#$md').remove();\n";
    SetDataSource($serviceid,@implode("\n",$f));
    echo refresh_global_no_close($serviceid);
    return true;

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
    $dataSource=GetDataSource($serviceid);
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

    SetDataSource($serviceid,@implode(",",$f));
    echo refresh_global_no_close($serviceid);
    return admin_tracks("Add country $Country from reverse-proxy WAF rule #$serviceid");
}
function refresh_global_no_close($serviceid):string{
    $suffixForm=$_GET["suffix-form"];
    $Data=GetDataSource($serviceid);
    $fieldid=md5("description$suffixForm");
    $f[]="document.getElementById('$fieldid').value='$Data';\n";
    return @implode(";",$f)."\n";

}
function popup_main():bool{
    $suffixForm=$_GET["suffix-form"];
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-$serviceid'></div>
    <script>LoadAjax('main-popup-$serviceid','$page?popup-table=$serviceid&suffix-form=$suffixForm')</script>";
    return true;
}
function popup_table():bool{
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["popup-table"]);
    $suffixForm=$_GET["suffix-form"];
    $tpl        = new template_admin();
    echo "<div id='countries-nginx-$serviceid' style='margin-bottom:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&popup-table2=$serviceid&suffix-form=$suffixForm");
    return true;
}

function top_buttons():bool{
    $suffixForm=$_GET["suffix-form"];
    $serviceid  = intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();

    $topbuttons[] = array("Loadjs('$page?OnlyActive=yes&serviceid=$serviceid&function=$function&suffix-form=$suffixForm')", ico_filter, "{OnlyActive}");
    $topbuttons[] = array("Loadjs('$page?disableall=yes&serviceid=$serviceid&function=$function&suffix-form=$suffixForm')", ico_disabled, "{disable_all}");
    $topbuttons[] = array("Loadjs('$page?enableall=yes&serviceid=$serviceid&function=$function&suffix-form=$suffixForm')", ico_check, "{enable_all}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

function rule_disable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];
    SetDataSource($serviceid,"");
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Disable all whitelisted country from WAF rule #$serviceid");
}
function rule_enable_all():bool{
    $function=$_GET["function"];
    $serviceid=$_GET["serviceid"];

    $GeoIPCountriesList=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPCountriesList"));
    $f=array();
    foreach ($GeoIPCountriesList as $IsoCodes => $Names) {
       $f[]=$IsoCodes;
    }

    SetDataSource($serviceid,@implode(",",$f));
    echo refresh_global_no_close($serviceid);
    echo "$function();";
    return admin_tracks("Whitelist all Countries for reverse-proxy WAF #$serviceid");
}
function popup_table2():bool{
    $suffixForm=$_GET["suffix-form"];
    $serviceid  = intval($_GET["popup-table2"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
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

    $dataSource=GetDataSource($serviceid);
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

        $enable=$tpl->icon_add("Loadjs('$page?pattern-enable=$IsoCodes&serviceid=$serviceid&suffix-form=$suffixForm')","AsWebMaster");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$IsoCodes&serviceid=$serviceid&md=$md&suffix-form=$suffixForm')","AsWebMaster");

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
        $html[]="LoadAjax('countries-nginx-$serviceid','$page?top-buttons=$serviceid&function=$function&suffix-form=$suffixForm');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
        }
function GetDataSource($id):string{
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT description FROM mod_security_patterns WHERE ID='$id'");
    return strval($ligne["description"]);
}
function SetDataSource($id,$value){
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $q->QUERY_SQL("UPDATE mod_security_patterns SET operator='Countries',fields='',description='$value' WHERE ID='$id'");
}