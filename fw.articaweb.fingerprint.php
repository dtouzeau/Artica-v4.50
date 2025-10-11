<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["status"])){status_js();exit;}
if(isset($_GET["activate"])){activate();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["popup-js"])){popup_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["status-popup"])){status_popup();exit;}
if(isset($_GET["activate-popup"])){activate_popup();exit;}
if(isset($_POST["fingerprint"])){status_save();exit;}
if(isset($_POST["ArticaWebFingerPrint"])){activate_save();exit;}

search();

function activate():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM fingerprints WHERE status=1");
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        return false;
    }
    $count=intval($ligne["tcount"]);
    if($count==0){
        $tpl->js_error("{error_no_item_auth}");
        return false;
    }
    $function=$_GET["function"];
    return $tpl->js_dialog1("{fingerprint} {parameters}","$page?activate-popup=yes&function=$function",550);
}
function activate_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ArticaWebFingerPrint=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrint"));
    $ArticaWebFingerPrintCaptcha=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrintCaptcha"));
    $function=$_GET["function"];

    $form[]=$tpl->field_checkbox("ArticaWebFingerPrint","{enable_feature}",$ArticaWebFingerPrint);
    $form[]=$tpl->field_checkbox("ArticaWebFingerPrintCaptcha","{use_captcha}",$ArticaWebFingerPrintCaptcha);
    echo $tpl->form_outside("",$form,"","{apply}","$function();dialogInstance1.close();");
    return true;
}
function activate_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/reconfigure");
    return true;
}
function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page);
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

function popup_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fingerprint=$_GET["popup-js"];
    $tpl->js_dialog1("{fingerprint} $fingerprint","$page?popup=$fingerprint");
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
    if($q->ok){
        $zips21=array();
        while($zipligne=@pg_fetch_array($results)){
            $zips21[$zipligne["ipaddr"]]=true;
        }
        foreach ($zips21 as $ip=>$none){
            $zips[]=$ip;
        }
    }

    if(count($zips)>0){
        $tpl->table_form_field_text("{ipaddresses}","<small>".implode(", ",$zips)."</small>",ico_nic);
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

    echo $tpl->table_form_compile();
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
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{fingerprint}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $search=$_GET["search"];
    $sql="SELECT * FROM fingerprints_events ORDER BY unixtime DESC LIMIT 250";

    if(strlen($search)>0){
        $search="*$search*";
        $search=str_replace($search,"**","*");
        $search=str_replace($search,"**","*");
        $sql="SELECT * FROM fingerprints_events WHERE (
            ipaddr::TEXT LIKE '$search' OR webtype LIKE '$search')  ORDER BY unixtime DESC LIMIT 250";
    }

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($q->mysql_error);
        return false;
    }

    $clock="<i class='".ico_clock."'></i>&nbsp;";



    while($ligne=@pg_fetch_array($results)){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $fingerprint=$ligne["fingerprint"];
        $ipaddr=$ligne["ipaddr"];
        $webtype=$ligne["webtype"];
        $unixtime=$tpl->time_to_date($ligne["unixtime"],true);

        $Status[0]=$tpl->td_href("<span class='label label-default'>{none}</span>","","Loadjs('$page?status=$fingerprint&function=$function')");
        $Status[1]=$tpl->td_href("<span class='label label-primary'>{allow}</span>","","Loadjs('$page?status=$fingerprint&function=$function')");
        $Status[2]=$tpl->td_href("<span class='label label-danger'>{deny}</span>","","Loadjs('$page?status=$fingerprint&function=$function')");

        $ligne2=$q->mysqli_fetch_array("SELECT useragent,status FROM fingerprints WHERE fingerprint='$fingerprint'");
        if(!is_array($ligne2)){$ligne2["status"]=0;}
        $statusValue=intval($ligne2["status"]);
        $IconStatus=$Status[$statusValue];
        $fingerprint=$tpl->td_href($fingerprint,"","Loadjs('$page?popup-js=$fingerprint&function=$function')");
        $ico="<i class='".UserAgentToIcon($ligne2["useragent"])."'></i>&nbsp;";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$clock$unixtime</td>";
        $html[]="<td style='width:1%' nowrap>$IconStatus</td>";
        $html[]="<td style='width:99%' nowrap>$ico$fingerprint</td>";
        $html[]="<td style='width:1%' nowrap>$ipaddr</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$webtype</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
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

    $TINY_ARRAY["TITLE"]="{fingerprints_webaccess}";
    $TINY_ARRAY["ICO"]="far fa-browser";
    $TINY_ARRAY["EXPL"]="{fingerprints_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
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