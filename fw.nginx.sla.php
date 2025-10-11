<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.sla.inc.php");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["frontend-search"])){frontend_center_search();exit;}
if(isset($_GET["table"])){search_header();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["drop-table"])){drop_table();exit;}
if(isset($_POST["drop-table"])){drop_table_perform();exit;}
if(isset($_GET["view-errors"])){view_error_js();exit;}
if(isset($_GET["view-success"])){view_success_js();exit;}
if(isset($_GET["view-latency"])){view_latency_js();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["backend"])){search_header();exit;}
if(isset($_GET["frontend-top-status"])){frontend_top_status();exit;}
if(isset($_GET["frontend-center-status"])){frontend_center_status();exit;}

if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
page();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?status=yes";
   // $array["{frontend}"]="$page?frontend=yes";
    $array["{backend}"]="$page?backend=yes";
    echo $tpl->tabs_default($array);

}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();

  //  $json=json_decode(@file_get_contents(slapath));


    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:100%;vertical-align: top' colspan='2'><div id='top-status'></div></td>";
    $html[]="</tr>";
    $html[]="<td style='vertical-align: top;width:240px'><div id='left-status'></div></td>";
    $html[]="<td style='vertical-align: top;width:99%;padding-left:10px'><div id='center-status'></div></td>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('top-status','$page?frontend-top-status=yes');";
    $html[]="LoadAjax('center-status','$page?frontend-center-status=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function widget_frontend_time():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode(@file_get_contents(slapath));
    if(is_null($json)){
        return $tpl->widget_h("grey",ico_timeout,"- - -","{report_time}");
    }

    if(!property_exists($json,"time")){
        return $tpl->widget_h("grey",ico_timeout,"- - -","{report_time}");

    }
    return $tpl->widget_h("green",ico_timeout,$tpl->time_to_date($json->time,true),"{report_time}");

}

function widget_frontend_domains():array{
    $tpl=new template_admin();
    $json=json_decode(@file_get_contents(slapath));
    if(!property_exists($json,"services")){
        return array(
            $tpl->widget_h("grey",ico_earth,0,"{tested_domains}"),
            $tpl->widget_h("grey",ico_earth,0,"{domain_errors}"));


    }
    $Errors=0;
    $Good=0;
    foreach($json->services as $service){
        if(property_exists($service,"domains")){
            foreach($service->domains as $domain){
                if(!$domain->Status){
                    $Errors++;
                    continue;
                }
                $Good++;
            }
        }
    }
    $SitesNum=$Errors+$Good;
    if($SitesNum==0){
        return array(
            $tpl->widget_h("grey",ico_earth,0,"{tested_domains}"),
            $tpl->widget_h("grey",ico_bug,0,"{domain_errors}"));
    }

    $a=$tpl->widget_h("green",ico_earth,$SitesNum,"{tested_domains}");
    $b=$tpl->widget_h("green",ico_bug,0,"{errors}");
    if($Errors>0){
        $b=$tpl->widget_h("yellow",ico_bug,$Errors,"{domain_errors}");
    }


    return array($a,$b);
}
function report_js():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $domain=urlencode($_GET["report-js"]);
    $serviceid=intval($_GET["serviceid"]);
    return $tpl->js_dialog4($domain,"$page?report-popup=$domain&serviceid=$serviceid",2048);
}
function report_popup():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $domain=$_GET["report-popup"];
    $serviceid=intval($_GET["serviceid"]);
    $report="/usr/share/artica-postfix/ressources/logs/reverse-proxy/$serviceid/$domain/report.report.html";
    if(!is_file($report)){
        echo "reverse-proxy/$serviceid/$domain no such file or directory";
        return false;
    }
    $t=time();
    $html[]="<div id='id-$t' style='width:100%;border:0;padding: 5px;margin: -5px;overflow: hidden;'><iframe id=`'contentFrame' src='ressources/logs/reverse-proxy/$serviceid/$domain/report.report.html' style='width:100%;min-height:1400px;overflow: hidden;margin:0;border:0;'></iframe></div>";

    echo @implode("\n",$html);
    return true;
}

function frontend_top_status():string{
    $frontend_time=widget_frontend_time();
    list($widget_frontend_domains,$widget_frontend_errors)=widget_frontend_domains();
    $html[]="<table style='width:100%;margin-top:0'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%;vertical-align: top'>$frontend_time</td>";
    $html[]="<td style='width:33%;vertical-align:top;padding-left:5px'>$widget_frontend_domains</td>";
    $html[]="<td style='width:33%;vertical-align:top;padding-left:5px'>$widget_frontend_errors</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function frontend_center_status():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    echo $tpl->search_block($page,null,null,null,"&frontend-search=yes");
    return true;
}



function textError($domainError):string{
    $domainError=str_replace("Runtime error encountered:","",$domainError);
    $domainError=str_replace("The URL you have provided does not have a valid security certificate","{no_valid_certificate2}<br>",$domainError);
    $domainError=str_replace("Chrome prevented page load with an interstitial. Make sure you are testing the correct URL and that the server is properly responding to all requests","{chrome_error1}",$domainError);
    $domainError=str_replace("Lighthouse was unable to reliably load the page you requested. Make sure you are testing the correct URL and that the server is properly responding to all requests.","{chrome_error2}",$domainError);
    if(strlen($domainError)==0){
        $domainError="Possible execution error..";
    }
    return $domainError;
}

function frontend_center_search():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $html[]="<table id='table-latency-glob' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{service}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domain}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{audit}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{report}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{vitrification}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS="";
    $sla=new NginxSla();
    $sla->Parse();

    if(count($sla->MainArray)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{nginx_sla_frontend_no_report}"));
        return true;
    }
    $search=$_GET["search"];
    $search=str_replace("*",".*?",$search);

    $ico_earth=ico_earth;
    foreach($sla->MainArray as $domain=>$ligne){
        $Latency="";
        $text_err="";
        $icoLatency="<span class='label label-default'>{inactive2}</span>";
        $icoVitrification="<span class='label label-default'>{inactive2}</span>";
        $icoReport=$tpl->icon_nothing();
        $md=md5($domain.serialize($ligne));
        $serviceid=$ligne["serviceid"];
        $serviceName=get_servicename($serviceid);
        if(strlen($search)>1){
            if(!preg_match("/$search/i","$serviceName $domain")){
                continue;
            }
        }

        $js="Loadjs('fw.nginx.sites.php?www-js=$serviceid&domain=$domain');";
        if(isset($ligne["latency"]) && isset($ligne["Status"])){
            $icoLatency="<span class='label label-primary'>{active2}</span>";
            if(!$ligne["Status"]){
                $icoLatency="<span class='label label-danger'>{error}</span>";
                if(isset($ligne["error"])){
                    $text_err="<br><span class='text-danger'>".textError($ligne["error"])."</span>";
                }
            }
            if($ligne["latency"]["BackendMs"]>0) {
                $Latency = msToSeconds($ligne["latency"]["BackendMs"]);
            }
        }
        if(isset($ligne["vitrification"])){
            $icoVitrification="<span class='label label-primary'>{active2}</span>";
            if(!$ligne["vitrification"]["aivalable"]){
                $icoVitrification="<span class='label label-default'>{unavailable}</span>";
            }
        }
        $report="/usr/share/artica-postfix/ressources/logs/reverse-proxy/$serviceid/$domain/report.report.html";
        if(is_file($report)) {
            $domainEn = urlencode($domain);
            $JsReport="Loadjs('$page?report-js=$domainEn&serviceid=$serviceid');";
            $icoReport=$tpl->icon_stats($JsReport);
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap><i class='$ico_earth'></i>&nbsp;". $tpl->td_href($serviceName,"{click_to_edit}",$js)."</td>";
        $html[]="<td>$domain $Latency $text_err</td>";
        $html[]="<td style='width:1%' nowrap>$icoLatency</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$icoReport</td>";
        $html[]="<td style='width:1%' nowrap>$icoVitrification</td>";
        $html[]="</tr>";


    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-latency-glob').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";


    if(count($Errors)>0){
        $tpl->table_form_section("{domain_errors}");
        foreach($Errors as $dom=>$domain){
            $domainError=$domain->error;
            $domainError=str_replace("Runtime error encountered:","",$domainError);
            $domainError=str_replace("The URL you have provided does not have a valid security certificate","{no_valid_certificate2}<br>",$domainError);
            $domainError=str_replace("Chrome prevented page load with an interstitial. Make sure you are testing the correct URL and that the server is properly responding to all requests","{chrome_error1}",$domainError);
            $domainError=str_replace("Lighthouse was unable to reliably load the page you requested. Make sure you are testing the correct URL and that the server is properly responding to all requests.","{chrome_error2}",$domainError);
            if(strlen($domainError)==0){
                $domainError="Possible execution error..";
            }
            $tpl->table_form_field_text("<span style='text-transform:none'>$dom</span>","<span style='text-transform:none'>$domainError</span>",ico_bug,true);
        }

    }
    if(count($Good)>0){
        $tpl->table_form_section("{tested_domains}");
        foreach($Good as $dom=>$domain){
            $tpl->table_form_field_js("");
            if(property_exists($domain,"serviceid")){
                $report="/usr/share/artica-postfix/ressources/logs/reverse-proxy/$domain->serviceid/$dom/report.report.html";
                if(is_file($report)){
                    $domainEn=urlencode($dom);
                    $tpl->table_form_field_js("Loadjs('$page?report-js=$domainEn&serviceid=$domain->serviceid');");
                }
            }else{
                VERBOSE("$dom \$domain->serviceid Not Found",__LINE__);
            }
            $resolved=$domain->resolved;
            $Latency=msToSeconds($domain->latency->BackendMs);
            $tpl->table_form_field_text("<span style='text-transform:none'>$dom</span>","$resolved $Latency",ico_earth);
        }

    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function msToSeconds($milliseconds) {
    if(intval($milliseconds)==0) {
        return "($milliseconds ms)";
    }
    if (!is_numeric($milliseconds) || $milliseconds < 0) {
        return null;
    }

    // Convert milliseconds to seconds (1 second = 1000 milliseconds)
    $seconds=$milliseconds / 1000;
    if ($seconds < 1) {
        $milliseconds=round($milliseconds,2);
        return "($milliseconds ms)";
    }
    if ($seconds > 1) {
        $seconds=round($seconds,2);
        return "<strong class='text-danger'>($seconds {seconds})</strong>";
    }
    $seconds=round($seconds,2);
    return "<span class='text-warning'>($seconds {second})</span>";
}
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $title="{APP_NGINX} SLA";

    $html=$tpl->page_header($title,ico_performance,"{nginx_sla_explain}","$page?tabs=yes",
        "reverse-sla",
        "progress-nginx-slar",false,
        "table-nginx-sla"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: $title",$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function search_header():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);
    return true;
}
function drop_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_confirm_delete("{empty_database_explain}","drop-table","yes","$function()");
}
function drop_table_perform(){
    $q=new postgres_sql();
    $q->QUERY_SQL("TRUNCATE TABLE nginx_sla");
    return admin_tracks("Remove reverse-proxy SLA database");
}
function view_error_js(){
    $function=$_GET["function"];
    $_SESSION["SLAERRORS"]=1;
    $_SESSION["SLASUCCESS"]=0;
    echo "$function()";
}
function view_success_js(){
    $function=$_GET["function"];
    $_SESSION["SLAERRORS"]=0;
    $_SESSION["SLASUCCESS"]=1;
    echo "$function()";
}
function view_latency_js(){
    $function=$_GET["function"];
    $_SESSION["SLALATENCY"]=intval($_GET["view-latency"]);
    echo "$function()";
}
function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $view_errors="Loadjs('$page?view-errors=yes&function=$function')";
    $view_success="Loadjs('$page?view-success=yes&function=$function')";
    $view_latency_off="Loadjs('$page?view-latency=0&function=$function')";
    $view_latency_on="Loadjs('$page?view-latency=1&function=$function')";

    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{detection_time}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{servicename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $q=new postgres_sql();
    $AND=array();
    if(!isset($_SESSION["SLALATENCY"])){$_SESSION["SLALATENCY"]=0;}
    if(!isset($_SESSION["SLAERRORS"])){$_SESSION["SLAERRORS"]=0;}
    if(!isset($_SESSION["SLASUCCESS"])){$_SESSION["SLASUCCESS"]=0;}
    if(isset($_GET["search"])){$_GET["search"]="";}

    if($_SESSION["SLALATENCY"]==1){
        $AND[]="testype=5";
        $topbuttons[] = array($view_latency_off,ico_performance,"{latency} ON");

    }else{
        $topbuttons[] = array($view_latency_on,ico_performance,"{latency} OFF");
    }

    if($_SESSION["SLAERRORS"]==1){
        $AND[]="result=1";
        $topbuttons[] = array($view_success,ico_loupe,"{success}");
    }else{
        $topbuttons[] = array($view_errors,ico_loupe,"{failed}");
    }
    if( $_SESSION["SLASUCCESS"]==1){
        $AND[]="result=2";
    }
    $search=$_GET["search"];
    $ids=get_all_services($search);
    if(count($ids)>0){
        $ff=array();
        foreach ($ids as $index=>$ID){
            $ff[]="serviceid=$ID";
        }
        $AND[]="(".@implode("OR ",$ff).")";
    }

    $WHERE="";
    if(count($AND)>0){
        $WHERE="WHERE ".@implode(" AND ",$AND);
    }

    $sql="SELECT * FROM nginx_sla $WHERE ORDER BY zdate DESC LIMIT 250";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return;
    }
    $arrayType[1] = "Frontend Checking";
	$arrayType[2] = "Backend Checking";
	$arrayType[3] = "Backend Latency";
    $arrayType[4] = "Public DNS Checks";
    $arrayType[5] = "Backend Latency";

    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }

       // zdate	testype	serviceid	content

        $zdate = strtotime($ligne["zdate"]);
        $finalDate=$tpl->time_to_date($zdate,true);
        $testype = $arrayType[$ligne["testype"]];
        if(strlen($testype)==0){
            $testype="TEST {$ligne["testype"]}";
        }
        $result=intval($ligne["result"]);
        $serviceid = intval($ligne["serviceid"]);
        $content=$ligne["content"];
        $servicename=get_servicename($serviceid);
        $result_text="";

        $tr1=$tpl->table_td1prcLeft();
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td $tr1><strong>$finalDate</strong></td>";

        if($result==1){
            $result_text="<span class='label label-danger'>$testype</span>";
        }
        if($result==2){
            $result_text="<span class='label label-primary'>$testype</span>";
        }
        $html[] = "<td $tr1><strong>$result_text</strong></td>";
        $html[] = "<td nowrap>$servicename</td>";
        $html[] = "<td>$content</td>";
        $html[] = "</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'>$sql</ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";


    $checkbackend=$tpl->framework_buildjs(
        "nginx:/reverse-proxy/sla/backends",
        "nginx.slabackends.progress",
        "nginx.slabackends.log",
        "progress-nginx-slar",
        "$function()"
    );

    $DropTable="Loadjs('$page?drop-table=yes&function=$function')";


    $topbuttons[] = array($checkbackend,ico_refresh,"{checkbackends}");
    $topbuttons[] = array($DropTable,ico_trash,"{empty_database}");

    $title="{APP_NGINX} SLA";
    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=ico_performance;
    $TINY_ARRAY["EXPL"]="{nginx_sla_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function get_all_services($search):array{
    $f=array();
    if(strlen($search)<3){
        return $f;
    }
    $search="*$search*";
    $search=str_replace("**","*",$search);
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);
    $q                          = new lib_sqlite(NginxGetDB());
    $results=$q->QUERY_SQL("SELECT ID FROM nginx_services WHERE servicename LIKE '$search'");
    foreach ($results as $index=>$ligne){
        $f[]=$ligne["ID"];
    }
    return $f;
}
function get_servicename($ID):string{
    if(isset($GLOBALS["SERVICES"][$ID])){return $GLOBALS["SERVICES"][$ID];}
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    $GLOBALS["SERVICES"][$ID]=trim($ligne["servicename"]);
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