<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.rtmm.tools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["json-data"])){json_data_js();exit;}
if(isset($_GET["json-data-popup"])){json_data_popup();exit;}
if(isset($_GET["status"])){status_js();exit;}
if(isset($_GET["activate"])){activate();exit;}
if(isset($_GET["popup-search"])){search_header();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["popup-js"])){popup_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["status-popup"])){status_popup();exit;}
if(isset($_GET["activate-popup"])){activate_popup();exit;}
if(isset($_POST["fingerprint"])){status_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["ArticaWebFingerPrint"])){activate_save();exit;}
if(isset($_GET["zoom-ips"])){zoom_ips_js();exit;}
if(isset($_GET["zoom-ips-popup"])){zoom_ips_popup();exit;}

page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html=$tpl->page_header("{fingerprinting}","fas fa-fingerprint","{fingerprinting_explain}",
        "$page?tabs=yes","fingerprinting","progress-fingerprinting-restart",false,"table-fingerprinting");

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{fingerprints}"]="$page?popup-search=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function search_header(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&real-search=yes");
    echo "</div>";
}


function status_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fingerprint=$_GET["status"];
    $function=$_GET["function"];
    $tpl->js_dialog1("{fingerprint} $fingerprint","$page?status-popup=$fingerprint&function=$function");
}
function status_save():bool{
    $tpl=new template_admin();
    $fingerprint=$_POST["fingerprint"];
    $ztype=intval($_POST["ztype"]);
    $q=new postgres_sql();

    if($ztype==5){
        $q->QUERY_SQL("DELETE FROM fingerprints WHERE fingerprint='$fingerprint'");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
        $q->QUERY_SQL("DELETE FROM fingerprints_events WHERE fingerprint='$fingerprint'");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
        return admin_tracks("Remove fingerprint Web console access $fingerprint");

    }


    $q->QUERY_SQL("UPDATE fingerprints SET status=$ztype WHERE fingerprint='$fingerprint'");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks("Save fingerprint Web console access $fingerprint status to $ztype");
}
function status_popup(){
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $fingerprint=$_GET["status-popup"];
    $ligne=$q->mysqli_fetch_array("SELECT * FROM fingerprints WHERE fingerprint='$fingerprint'");
    $Types[0] = "{artica_captcha_greyzone}";
    $Types[1] = "{artica_captcha_allow}";
    $Types[2] = "{artica_captcha_deny}";
    $Types[5] = "{artica_captcha_delete}";
    $form[]=$tpl->field_hidden("fingerprint",$fingerprint);
    $form[]=$tpl->field_array_checkboxes2Columns($Types, "ztype", $ligne["status"]);
    echo $tpl->form_outside("",$form,"","{apply}","$function();dialogInstance1.close();");

}

function popup_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fingerprint=$_GET["popup-js"];
    return $tpl->js_dialog1("{fingerprint} $fingerprint","$page?popup=$fingerprint");
}

function zoom_ips_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fingerprint=$_GET["zoom-ips"];
    return $tpl->js_dialog2("{fingerprint} > $fingerprint > {ipaddresses}","$page?zoom-ips-popup=$fingerprint");
}
function json_data_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fingerprint=$_GET["json-data"];
    return $tpl->js_dialog2("{fingerprint} $fingerprint","$page?json-data-popup=$fingerprint");
}
function json_data_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $fingerprint=$_GET["json-data-popup"];
    $ligne=$q->mysqli_fetch_array("SELECT jsondata FROM fingerprints WHERE fingerprint='$fingerprint'");
    $jsondata=base64_decode($ligne["jsondata"]);
    echo "<textarea style='width:100%;min-height:450px'>$jsondata</textarea>";
    return true;

}
function GetPlateForm($jsondata){
    $jsondata=json_decode($jsondata);
    if(!property_exists($jsondata,"components")){
        return "NO_COMP";
    }
    $components=$jsondata->components;
    if(!property_exists($components,"platform")){
        return "NO_PLAT";

    }
    return $components->platform->value;
}

function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $fingerprint=$_GET["popup"];
    $ligne=$q->mysqli_fetch_array("SELECT * FROM fingerprints WHERE fingerprint='$fingerprint'");
    $UserAgent=$ligne["useragent"];
    $jsondata=json_decode(base64_decode($ligne["jsondata"]));
    //var_dump($jsondata);
    $FontArray=array();

    if(strlen($UserAgent)<3){
        if(property_exists($jsondata,"userAgent")) {
            $UserAgent = $jsondata->userAgent;
        }
    }

    $LanguageArray=array();
    $screenResolution=array();
    $zips=array();
    $plugins=array();
    $timezone="";$platform="";$vendor="";$videoCard="";
    if(property_exists($jsondata,"components")){
        $components=$jsondata->components;
        if(property_exists($components,"fonts")){
            $fonts=$components->fonts->value;
            foreach ($fonts as $font){
                $FontArray[]=$font;
            }
        }
        if(property_exists($components,"languages")){
            $languages=$components->languages->value;

            foreach ($languages as $lang){
                foreach ($lang as $lang2) {
                    $LanguageArray[] = $lang2;
                }
            }
        }
        if(property_exists($components,"screenResolution")){
            $screen=$components->screenResolution->value;
            foreach ($screen as $src){
                $screenResolution[]=$src;
            }
        }
        if(property_exists($components,"plugins")){
            $plugs=$components->plugins->value;
            foreach ($plugs as $src){
                $plugins[]=$src->name;
            }
        }


        if(property_exists($components,"videoCard")){
            $videoCard=$components->videoCard->value->vendor;
            if(property_exists($components->videoCard->value,"renderer")){
                $videoCard=$components->videoCard->value->renderer;
            }
        }

        if(property_exists($components,"vendor")){
            $vendor=$components->vendor->value;
        }
        if(property_exists($components,"vendorFlavors")) {
            foreach ($components->vendorFlavors->value as $val){
                $vendor = $vendor . " " . $val;
            }
        }


        if(property_exists($components,"timezone")){
            $timezone=$components->timezone->value;
        }
        if(property_exists($components,"platform")){
            $platform=$components->platform->value;
        }
    }

    $results=$q->QUERY_SQL("SELECT ipaddr FROM fingerprints_events WHERE fingerprint='$fingerprint'");
    $zipZoom=false;
    $zips21=array();
    if($q->ok){
        while($zipligne=@pg_fetch_array($results)){
            $zips21[$zipligne["ipaddr"]]=true;

        }
        if(count($zips21)>5){
            $zipZoom=true;
        }
        $c=0;
        foreach ($zips21 as $ip=>$none){
            $zips[]=$ip;
            if($zipZoom){
                if(count($zips)>3){
                    break;
                }
            }
        }
    }

    if(count($zips)>0){
        if(!$zipZoom) {
            $tpl->table_form_field_text("{ipaddresses}", "" . implode(", ", $zips) . "", ico_nic);
        }else{
            $tpl->table_form_field_js("Loadjs('$page?zoom-ips=$fingerprint')");
            $tpl->table_form_field_text("{ipaddresses}", "<small>" . implode(", ", $zips) . " <strong class='text-danger'>(".(count($zips21)-3)." {ipaddresses})</small>", ico_nic);
            $tpl->table_form_field_js("");
        }
    }

    if(strlen($vendor)>0){
           $tpl->table_form_field_text("{browser}",$vendor,"fad fa-browser");
    }
    if(strlen($UserAgent)>0){
        $tpl->table_form_field_text("{useragent}","<small>$UserAgent</small>","fad fa-browser");
    }


    if(strlen($timezone)>0){
        $tpl->table_form_field_text("{timezone}",$timezone,ico_clock_desk);
    }
    if(strlen($platform)>0){
        $tpl->table_form_field_text("{OS}",$platform,ico_params);
    }
    if(strlen($videoCard)>0){
        $tpl->table_form_field_text("{video}","<small>$videoCard</small>","fad fa-camcorder");
    }


    if(count($plugins)>0){
        $tpl->table_form_field_text("{plugins}","<small>".@implode(", ",$plugins)."</small>",ico_plug);
    }
    if(count($FontArray)>0){
        $tpl->table_form_field_text("{font_face}","<small>".@implode(", ",$FontArray)."</small>",ico_fonts);
    }
    if(count($LanguageArray)>0){
        $tpl->table_form_field_text("{language}","<small>".@implode(", ",$LanguageArray)."</small>",ico_language);
    }
    if(count($screenResolution)>0){
        $tpl->table_form_field_text("{screen}","<small>".@implode("x",$screenResolution)."</small>",ico_computer);
    }

    $tpl->table_form_button("{data}","Loadjs('$page?json-data=$fingerprint');",null,"fas fa-fingerprint");

    echo $tpl->table_form_compile();
}
function hashServices():array{
    $zids=array();
    $q=new lib_sqlite(NginxGetDB());
    $sql="SELECT ID,servicename FROM nginx_services WHERE enabled=1 ORDER BY servicename";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $servicename=$ligne["servicename"];
        $ID=$ligne["ID"];
        $zids[$ID]=$servicename;


    }
    return $zids;
}
function table(){
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $t=time();
    $TRCLASS=null;
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{fingerprint}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{OS}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{website}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";




    $search=$_GET["search"];
    $sql="SELECT * FROM fingerprints_events ORDER BY unixtime DESC LIMIT 250";

    if(strlen($search)>0){
        $search="*$search*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*","%",$search);
        $sql="SELECT * FROM fingerprints_events WHERE (
            ipaddr::TEXT LIKE '$search' OR webtype LIKE '$search')  ORDER BY unixtime DESC LIMIT 250";
    }
    VERBOSE($sql,__LINE__);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($q->mysql_error);
        return false;
    }

    $clock="<i class='".ico_clock."'></i>&nbsp;";

    $IconOS["Linux x86_64"]="<i class='fab fa-linux'></i>";
    $IconOS["MacIntel"]="<i class='fas fa-apple-alt'></i>";
    $IconOS["Win32"]="<i class='fab fa-windows'></i>";
    $IconOS["Linux armv81"]="<i class='fab fa-linux'></i>";
    $IconOS["iPad"]="<i class='fas fa-apple-alt'></i>";
    $IconOS["iPhone"]="<i class='fas fa-apple-alt'></i>";
    $hashServices=hashServices();
    while($ligne=@pg_fetch_array($results)){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $fingerprint=$ligne["fingerprint"];
        $ipaddr=$ligne["ipaddr"];
        $webtype=$ligne["webtype"];
        $unixtime=$tpl->time_to_date($ligne["unixtime"],true);
        $serviceid=intval($ligne["siteid"]);
        $sitename=$tpl->icon_nothing();
        if($webtype<>"WEBCONSOLE") {
            if(isset($hashServices[$serviceid])) {
                $sitename = $hashServices[$serviceid];
            }
        }

        $ligne2=$q->mysqli_fetch_array("SELECT jsondata,useragent,status FROM fingerprints WHERE fingerprint='$fingerprint'");
        $fingerprint=$tpl->td_href($fingerprint,"","Loadjs('$page?popup-js=$fingerprint&function=$function')");
        $country="";
        $flag=GetFlags($ligne["country"]);
        if(strlen($ligne["country"])>0){
            $country="&nbsp;(".$ligne["country"].")";
        }
        $OS=GetPlateForm($jsondata);
        $system="<i class='fas fa-question-circle'></i>&nbsp;($OS)";
        $jsondata=base64_decode($ligne2["jsondata"]);

        if(isset($IconOS[$OS])){
            $system=$IconOS[$OS]."&nbsp;($OS)";
        }

        $ipaddr=$tpl->td_href($ipaddr,null,"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$ipaddr')");

        $ico="<i class='".UserAgentToIcon($ligne2["useragent"])."'></i>&nbsp;";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$clock$unixtime</td>";
        $html[]="<td style='width:99%' nowrap>$ico$fingerprint</td>";
        $html[]="<td style='width:99%' nowrap>$system</td>";
        $html[]="<td style='width:1%' nowrap>$sitename</td>";
        $html[]="<td style='width:1%' nowrap><img src='img/$flag'>&nbsp;$ipaddr$country</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$webtype</td>";
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


    $ArticaWebFingerPrint=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrint"));

    if($ArticaWebFingerPrint==0) {
        $topbuttons[] = array("Loadjs('$page?activate=yes&function=$function')", ico_plus, "{activate_fingerprint_verification}");
    }else{
        $topbuttons[] = array("Loadjs('$page?activate=yes&function=$function')", ico_trash, "{disable_fingerprint_verification}");
    }


    $TINY_ARRAY["TITLE"]="{fingerprinting}";
    $TINY_ARRAY["ICO"]="fas fa-fingerprint";
    $TINY_ARRAY["EXPL"]="{fingerprinting_explain}";
    //$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
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
function HarmpID():int{
    if(!isset($_SESSION["HARMPID"])){return 0;}
    if(intval($_SESSION["HARMPID"])==0){
        return 0;
    }
    return intval($_SESSION["HARMPID"]);
}
function zoom_ips_popup():bool{
    $fingerprint=$_GET["zoom-ips-popup"];
    $t=time();
    $q=new postgres_sql();
    $hashServices=hashServices();
    $tpl=new template_admin();
    $results=$q->QUERY_SQL("SELECT count(*) as tcount,ipaddr,siteid,country FROM fingerprints_events WHERE siteid > 0 AND fingerprint='$fingerprint' GROUP BY ipaddr,siteid,country ORDER BY tcount DESC");

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $TRCLASS=null;
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{website}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{requests}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    while($ligne=@pg_fetch_array($results)) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = md5(serialize($ligne));

        $ipaddr = $ligne["ipaddr"];
        $country="";
        $flag=GetFlags($ligne["country"]);
        if(strlen($ligne["country"])>0){
            $country="&nbsp;(".$ligne["country"].")";
        }


        $serviceid = intval($ligne["siteid"]);
        $sitename = $tpl->icon_nothing();
        $requests=$ligne["tcount"];

            if (isset($hashServices[$serviceid])) {
               $sitename = $hashServices[$serviceid];
            }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap><img src='img/$flag'>&nbsp;$ipaddr$country</td>";
        $html[]="<td style='width:1%' nowrap>$sitename</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$requests</td>";
        $html[]="</tr>";


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

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);


return true;
}