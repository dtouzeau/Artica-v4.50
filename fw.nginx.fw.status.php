<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

if(isset($_POST["ModSecurityAction"])){Save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters-start"])){parameters_start();exit;}
if(isset($_GET["modsec-graph-line"])){modsec_graph_line();exit;}
if(isset($_GET["modsec-graph-today"])){modsec_graph_today();exit;}
if(isset($_GET["modsec-graph-pie"])){modsec_graph_pie();exit;}
if(isset($_GET["modsec-graph-ips"])){modsec_graph_ipaddr();exit;}


page();

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $MODSECURITY_VERSION="";
    $MODSECURITY_VER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODSECURITY_VER"));
    if($MODSECURITY_VER<>null){
        $MODSECURITY_VERSION=" v{$MODSECURITY_VER}";
    }
	$html=$tpl->page_header("{WAF_LONG}$MODSECURITY_VERSION","fab fa-free-code-camp",
        "{APP_NGINX_FW_EXPLAIN}","$page?tabs=yes","web-firewall","progress-web-firewall",false,"webf-section");


	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: Web Application firewall {status}",$html);
		echo $tpl->build_firewall();
		return true;
	}


	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $MODSECURITY_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODSECURITY_INSTALLED"));
	$array["{status}"]="$page?status=yes";
    if($MODSECURITY_INSTALLED==1) {
        $array["{parameters}"]="fw.modsecurity.php?parameters-start=yes";
        $array["{default_rules}"]="fw.modsecurity.defrules.php";
        $array["{service_events}"] = "fw.modsecurity.events.php";
        $array["{update_events}"] = "fw.modsecurity.update.php";
    }
	echo $tpl->tabs_default($array);
    return true;
}

function status():bool{
	$page                           = CurrentPageName();
	$tpl                            = new template_admin();
    $refreshAll="Loadjs('fw.progress.php?refresh-menus=yes');";
    $EnableModSecurityIngix         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    $disable_icon                   = "far fa-times-circle";
    $MODSECURITY_INSTALLED      =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODSECURITY_INSTALLED"));
    $EnableNginxFW              = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"));
    $ModSecurityPatternCount    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityPatternCount"));
    $ModSecurityDisableOWASP    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDisableOWASP"));
    if($MODSECURITY_INSTALLED==0){$EnableModSecurityIngix=0;}

    if($EnableModSecurityIngix==1){
        $uninstall_modsecurity=$tpl->framework_buildjs("nginx.php?modsecurity-uninstall=yes",
            "modsecurity.progress",
            "modsecurity.progress.log",
            "progress-web-firewall","LoadAjaxSilent('webf-section','$page?tabs=yes');$refreshAll");

        $button["name"] = "{uninstall}";
        $button["js"] = $uninstall_modsecurity;
        $button["ico"]="fa fa-trash";

        $MODSECURITY_GLOBAL = $tpl->widget_h("green", "fa fa-thumbs-up", "{enabled}", "{WAF_LONG}",$button);
    }else{

        $install_modsecurity=$tpl->framework_buildjs("nginx.php?modsecurity-install=yes",
            "modsecurity.progress",
            "modsecurity.progress.log",
            "progress-web-firewall","LoadAjaxSilent('webf-section','$page?tabs=yes');$refreshAll");

        $button["name"] = "{install}";
        $button["js"] = $install_modsecurity;
        $button["ico"]="fa-solid fa-compact-disc";

        //$btn["help"]="https://wiki.articatech.com/maintenance/geolocation";
        $MODSECURITY_GLOBAL=$tpl->widget_h("gray",$disable_icon,"{disabled}","{WAF_LONG}",$button);
    }
    if($MODSECURITY_INSTALLED==0){
        $MODSECURITY_GLOBAL=$tpl->widget_h("gray",$disable_icon,"{not_installed}","{WAF_LONG}");
    }

    if($EnableNginxFW==1){
        $FIREWALL_GLOBAL = $tpl->widget_h("green", "fa fa-thumbs-up", "{enabled}", "{firewall_for_web}");
    }else{
        $FIREWALL_GLOBAL=$tpl->widget_h("gray",$disable_icon,"{disabled}","{firewall_for_web}");
    }



    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT count(ID) as tcount from service_parameters WHERE zkey='EnableModSecurity' AND zvalue='1'");
    if(!$q->ok){echo $q->mysql_error;}

    $ProtectedSites=intval($ligne["tcount"]);
    $qp=new postgres_sql();
    $modsecurity_events=$qp->COUNT_ROWS("modsecurity_events");

    $jsSchedule=$tpl->RefreshInterval_js("firewall-web-status","fw.nginx.fw.php","fw-status=yes");

	$js[]=$jsSchedule;
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:412px;padding-top:10px;vertical-align: top'>";
	$html[]= $MODSECURITY_GLOBAL;
	$html[]= $FIREWALL_GLOBAL;
    $html[]="<div id='firewall-web-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:75%;vertical-align:top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='padding-left:10px;padding-top:10px'>";
    if($EnableModSecurityIngix==1) {
        if ($ModSecurityPatternCount > 100) {
            $ModSecurityPatternCount = $tpl->FormatNumber($ModSecurityPatternCount);
            $oldIniArry = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityPattern"));
            $ModSecurityPatternVersion=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityPatternVersion"));
            $ModSecurityPatternVersion=str_replace("OWASP_CRS/","",$ModSecurityPatternVersion);
            $xtime = $tpl->time_to_date($oldIniArry["zDate"]);
            $ids_signatures = $tpl->widget_h("green", "fas fa-database",
                $ModSecurityPatternCount."&nbsp;<small style='font-size:11px;color:white'>v$ModSecurityPatternVersion</small>",
                "{signatures} ($xtime)");
        } else {
            $ids_signatures = $tpl->widget_h("red", "fas fa-database", $ModSecurityPatternCount, "{signatures}");
        }




        if ($ModSecurityDisableOWASP == 1) {
            $ids_signatures = $tpl->widget_h("grey", "fas fa-database", "{disabled}", "{signatures}");
        }

        if ($ProtectedSites == 0) {
            $protected_sites = $tpl->widget_h("red", "fas fa-shield-check", 0, "{protected_sites}");

        } else {
            $protected_sites = $tpl->widget_h("green", "fas fa-shield-check", $ProtectedSites, "{protected_sites}");
        }



        if ($modsecurity_events == 0) {
            $modsecurity_evs = $tpl->widget_h("grey", "fas fa-user-secret", 0, "{total}: {EmergingThreats}");

        } else {
            $modsecurity_events = $tpl->FormatNumber($modsecurity_events);
            $modsecurity_evs = $tpl->widget_h("yellow", "fas fa-user-secret", $modsecurity_events, "{total}: {EmergingThreats}");
        }
        $html[] = "<table style='width:100%'>";

        $html[] = "<tr>";
        $html[] = "<td style='width:50%;vertical-align: top;'>$ids_signatures</td>";
        $html[] = "<td style='width:50%;vertical-align: top;padding-left: 10px;'>$protected_sites</td>";
        $html[] = "</tr>";

        $html[] = "<tr>";
        $html[] = "<td colspan=2><div id='modsec-graph-today'></div></td>";
        $html[]="</tr>";
        $html[] = "<tr>";
        $html[] = "<td colspan=2><div id='modsec-graph-line'></div></td>";
        $html[]="</tr>";
        $html[] = "<tr>";
        $html[] = "<td style='width:50%;vertical-align: top;'><div id='modsec-graph-pie'></div></td>";
        $html[] = "<td style='width:50%;vertical-align: top;'><div id='modsec-graph-ips'></div></td>";

        $js[]="Loadjs('$page?modsec-graph-line=yes&id=modsec-graph-line');";
        $js[]="Loadjs('$page?modsec-graph-today=yes&id=modsec-graph-today');";
        $js[]="Loadjs('$page?modsec-graph-pie=yes&id=modsec-graph-pie');";
        $js[]="Loadjs('$page?modsec-graph-ips=yes&id=modsec-graph-ips');";

        $html[]="</table>";
    }
    if($MODSECURITY_INSTALLED==0){
        $js=array();
        $html[]=$tpl->div_warning("{WAF_LONG}: {feature_not_installed}||{extension_missing_upgrade}||wiki:https://wiki.articatech.com/reverse-proxy/update-reverse-proxy");
    }

	$html[]="</div>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>";
    $html[]=@implode("\n",$js);

    $MODSECURITY_VERSION="";
    $MODSECURITY_VER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODSECURITY_VER"));
    if($MODSECURITY_VER<>null){$MODSECURITY_VERSION=" v{$MODSECURITY_VER}"; }


    $jsrestart=$tpl->framework_buildjs("nginx:/reverse-proxy/waf/reconfigure",
        "nginx.waf.reconfigure",
        "nginx.waf.reconfigure.log","progress-web-firewall"
    );

    $topbuttons[] = array($jsrestart, ico_retweet, "{reconfigure}");

    $TINY_ARRAY["TITLE"]="{WAF_LONG}$MODSECURITY_VERSION";
    $TINY_ARRAY["ICO"]="fab fa-free-code-camp";
    $TINY_ARRAY["EXPL"]="{APP_NGINX_FW_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $html[]="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');\n";

    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}
function modsec_graph_today():bool{
    $q=new postgres_sql();
    $currentdaye=date("Y-m-d");
    $sql="SELECT to_char(date_trunc('hour', created), 'MM-DD-YYYY HH24') AS hour_formatted,
    COUNT(*) AS event_count FROM modsecurity_audit WHERE created>'$currentdaye 00:00:00' GROUP BY 1 ORDER BY 1;";
    $ydata=array();
    $xdata=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $hour_formatted=strtotime($ligne["hour_formatted"].":00:00");
        $final=date("H",$hour_formatted)."h";
        VERBOSE("{$ligne["hour_formatted"]}:00:00 -> $hour_formatted -> $final",__LINE__);
        $xdata[]=$final;
        $ydata[]=$ligne["event_count"];
    }
    if(count($xdata)==0){
        header("content-type: application/x-javascript");
        return true;
    }

    $title="{threats} {by_hour} {today}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{threats}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{hour}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{threats}"=>$ydata);
    echo $highcharts->BuildChart();
    return true;
}
function modsec_graph_line(){

    $q=new postgres_sql();
    $sql="SELECT to_char(date_trunc('hour', created), 'MM-DD') AS hour_formatted,
    COUNT(*) AS event_count FROM modsecurity_audit GROUP BY 1 ORDER BY 1;";
    $ydata=array();
    $xdata=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $xdata[]=$ligne["hour_formatted"];
        $ydata[]=$ligne["event_count"];
    }


    $title="{threats} {by_day} {this_year}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="{threats}";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="{days}";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{threats}"=>$ydata);
    echo $highcharts->BuildChart();
}
function modsec_graph_ipaddr():bool{

    $q=new postgres_sql();
    $sql="SELECT ipaddr,
    COUNT(*) AS event_count FROM modsecurity_audit GROUP BY ipaddr ORDER BY event_count DESC LIMIT 20;";
    $MODSECURITY_PIE_DAY=array();
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        VERBOSE($q->mysql_error,__LINE__);
    }
    while($ligne=@pg_fetch_assoc($results)){
        $MODSECURITY_PIE_DAY[$ligne["ipaddr"]]=$ligne["event_count"];
    }
    if(count($MODSECURITY_PIE_DAY)==0){
        return false;
    }

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$MODSECURITY_PIE_DAY;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top} {src}";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();
    return true;
}
function modsec_graph_pie():bool{

    $q=new postgres_sql();
    $sql="SELECT serviceid,
    COUNT(*) AS event_count FROM modsecurity_audit GROUP BY serviceid ORDER BY event_count DESC;";
    $MODSECURITY_PIE_DAY=array();
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        VERBOSE($q->mysql_error,__LINE__);
    }
    while($ligne=@pg_fetch_assoc($results)){
        $servicename=get_servicename($ligne["serviceid"]);
        VERBOSE($ligne["serviceid"]." $servicename === ".$ligne["event_count"],__LINE__);

        $MODSECURITY_PIE_DAY[$servicename]=$ligne["event_count"];
    }
    if(count($MODSECURITY_PIE_DAY)==0){
        return false;
    }

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$MODSECURITY_PIE_DAY;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_exposed_websites}";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();
    return true;
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

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