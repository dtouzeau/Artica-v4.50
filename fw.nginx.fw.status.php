<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
$users=new usersMenus();if(!$users->AsWebStatisticsAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["ModSecurityAction"])){Save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["charts"])){render_charts();exit;}
if(isset($_GET["modsec-web-status"])){modsec_status();exit;}
page();

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $MODSECURITY_VERSION="";
    $MODSECURITY_VER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODSECURITY_VER"));
    if($MODSECURITY_VER<>null){
        $MODSECURITY_VERSION=" v{$MODSECURITY_VER}";
    }
	$html=$tpl->page_header("{WAF_LONG}$MODSECURITY_VERSION","fa-solid fa-block-brick-fire",
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

function modsec_status():bool{
    $EnableModSecurityIngix         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    $MODSECURITY_INSTALLED      =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODSECURITY_INSTALLED"));
    $disable_icon                   = "far fa-times-circle";
    $page                           = CurrentPageName();
    $tpl                            = new template_admin();
    $refreshAll="Loadjs('fw.progress.php?refresh-menus=yes');";
    if($MODSECURITY_INSTALLED==0){
       echo $tpl->_ENGINE_parse_body($tpl->widget_h("gray",$disable_icon,"{not_installed}","{WAF_LONG}"));
        return true;
    }

    if($EnableModSecurityIngix==0) {

        $install_modsecurity = $tpl->framework_buildjs("nginx:/modsecurity/enable",
            "modsecurity.progress",
            "modsecurity.progress.log",
            "progress-web-firewall", "LoadAjaxSilent('webf-section','$page?tabs=yes');$refreshAll");

        $button["name"] = "{install}";
        $button["js"] = $install_modsecurity;
        $button["ico"] = "fa-solid fa-compact-disc";

        echo $tpl->_ENGINE_parse_body($tpl->widget_h("gray", ico_shield_disabled, "{disabled}", "{WAF_LONG}", $button));
        return true;
    }

    $install_modsecurity=$tpl->framework_buildjs("nginx:/modsecurity/disable",
        "modsecurity.progress",
        "modsecurity.progress.log",
        "progress-web-firewall","LoadAjaxSilent('webf-section','$page?tabs=yes');$refreshAll");

    $button["name"] = "{uninstall}";
    $button["js"] = $install_modsecurity;
    $button["ico"]=ico_trash;

    echo $tpl->_ENGINE_parse_body($tpl->widget_h("green",ico_shield,"{active2}","{WAF_LONG}",$button));
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



    // EnableNginxFW dit seulement que la fonctionnalité est installée. Un pare-feu
    // arrêté (NGINX_FW_STOPPED=1) s'affichait pourtant « Activé » en vert, alors
    // que la tuile de fw.nginx.status.php et le widget d'état juste en dessous
    // le donnaient inactif (arpx4, 2026-11-19). Même règle que la tuile.
    $NGINX_FW_STOPPED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_FW_STOPPED"));
    if($EnableNginxFW==1 && $NGINX_FW_STOPPED==1){
        $FIREWALL_GLOBAL=$tpl->widget_h("gray",$disable_icon,"{inactive2}","{firewall_for_web}");
    }elseif($EnableNginxFW==1){
        $FIREWALL_GLOBAL = $tpl->widget_h("green", "fa fa-thumbs-up", "{enabled}", "{firewall_for_web}");
    }else{
        $FIREWALL_GLOBAL=$tpl->widget_h("gray",$disable_icon,"{disabled}","{firewall_for_web}");
    }

    $ProtectedSites=count_protected_sites();

    $jsSchedule=$tpl->RefreshInterval_js("firewall-web-status","fw.nginx.fw.php","fw-status=yes");

	$js[]=$jsSchedule;
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:412px;padding-top:10px;vertical-align: top'>";
    $html[]="<div id='modsec-web-status'></div>";
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
            $ids_signatures = $tpl->widget_h("green", ico_database,
                $ModSecurityPatternCount."&nbsp;<small style='font-size:11px;color:white'>v$ModSecurityPatternVersion</small>",
                "{signatures} ($xtime)");
        } else {
            $ids_signatures = $tpl->widget_h("red", ico_database, $ModSecurityPatternCount, "{signatures}");
        }

        if ($ModSecurityDisableOWASP == 1) {
            $ids_signatures = $tpl->widget_h("grey", ico_database, "{disabled}", "{signatures}");
        }

        if ($ProtectedSites == 0) {
            $protected_sites = $tpl->widget_h("red", "fas fa-shield-check", 0, "{protected_sites}");
        } else {
            $protected_sites = $tpl->widget_h("green", "fas fa-shield-check", $ProtectedSites, "{protected_sites}");
        }

        $html[] = "<table style='width:100%'>";

        $html[] = "<tr>";
        $html[] = "<td style='width:50%;vertical-align: top;'>$ids_signatures</td>";
        $html[] = "<td style='width:50%;vertical-align: top;padding-left: 10px;'>$protected_sites</td>";
        $html[] = "</tr>";

        $html[] = "<tr><td colspan=2><div id='modsec-charts-zone'></div></td></tr>";

        $js[]="LoadAjax('modsec-charts-zone','$page?charts=yes');";

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
    $TINY_ARRAY["ICO"]="fa-solid fa-block-brick-fire";
    $TINY_ARRAY["EXPL"]="{APP_NGINX_FW_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    $jRefersh=$tpl->RefreshInterval_js("modsec-web-status",$page,"modsec-web-status=yes");

    $html[]="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');\n";
    $html[]=$jRefersh;
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}

// The charts come from the reverse-proxy daemon (GET /waf/stats/overview, module
// WafOverview): the page no longer queries PostgreSQL, so it no longer breaks
// when it is newer than the database schema.
function waf_status_js($v):string{
    // labels come from the traffic: never let one close the <script>
    return json_encode($v,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
}

function waf_status_points($o,$sec):array{
    if(!isset($o[$sec]) || !is_array($o[$sec])){return array();}
    return $o[$sec];
}

// A section the daemon could not read is a warning, never "no data".
function waf_status_empty($tpl,$errors,$sec):string{
    if(isset($errors[$sec])){
        return $tpl->div_warning("{error}||".htmlspecialchars(strval($errors[$sec]),ENT_QUOTES));
    }
    return $tpl->div_info("{no_data}");
}

function waf_status_box($title,$icon,$body):string{
    return "<div class='ibox'><div class='ibox-title'><h5><i class='$icon'></i>&nbsp; $title</h5></div><div class='ibox-content'>$body</div></div>";
}

function waf_status_chart($id,$height,$type,$labels,$datasets,$options):string{
    return "<div style='position:relative;height:{$height}px'><canvas id='$id'></canvas></div>".
        "<script>new Chart(document.getElementById('$id'),{type:'$type',data:{labels:".waf_status_js($labels).
        ",datasets:$datasets},options:$options});</script>";
}

function render_charts():bool{
    $tpl=new template_admin();
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/waf/stats/overview");
    $json=json_decode($resp,true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["overview"]) || !is_array($json["overview"])){
        $err=is_array($json) && isset($json["Error"]) ? strval($json["Error"]) : "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($err,ENT_QUOTES))."<script>NoSpinner();</script>");
        return false;
    }
    $o=$json["overview"];
    $errors=isset($o["errors"]) && is_array($o["errors"]) ? $o["errors"] : array();
    $threatsT=waf_status_js($tpl->javascript_parse_text("{threats}"));
    $noLegend="plugins:{legend:{display:false}}";
    $h=array();

    $line=function($sec,$id,$height,$title,$icon,$color,$fill,$extra) use ($tpl,$o,$errors,$threatsT,$noLegend){
        $pts=waf_status_points($o,$sec);
        if(count($pts)==0){return waf_status_box($title,$icon,waf_status_empty($tpl,$errors,$sec));}
        $labels=array();$data=array();
        foreach($pts as $p){$labels[]=strval($p["label"]);$data[]=intval($p["count"]);}
        $ds="[{label:$threatsT,data:".json_encode($data).",borderColor:'$color',backgroundColor:'$fill',fill:true,$extra}]";
        return waf_status_box($title,$icon,waf_status_chart($id,$height,"line",$labels,$ds,
            "{responsive:true,maintainAspectRatio:false,$noLegend,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}"));
    };
    $hbar=function($sec,$id,$title,$icon,$color,$rowpx,$prefix) use ($tpl,$o,$errors,$threatsT,$noLegend){
        $pts=waf_status_points($o,$sec);
        if(count($pts)==0){return waf_status_box($title,$icon,waf_status_empty($tpl,$errors,$sec));}
        $labels=array();$data=array();
        foreach($pts as $p){
            $l=$prefix.strval($p["label"]);
            if(strlen($l)>45){$l=substr($l,0,42)."...";}
            $labels[]=$l;$data[]=intval($p["count"]);
        }
        $ds="[{label:$threatsT,data:".json_encode($data).",backgroundColor:'$color'}]";
        return waf_status_box($title,$icon,waf_status_chart($id,max(280,count($data)*$rowpx),"bar",$labels,$ds,
            "{indexAxis:'y',responsive:true,maintainAspectRatio:false,$noLegend,scales:{x:{beginAtZero:true,ticks:{precision:0}},y:{ticks:{font:{size:11}}}}}"));
    };
    $pie=function($sec,$id,$type,$title,$icon,$colors,$legendPos) use ($tpl,$o,$errors){
        $pts=waf_status_points($o,$sec);
        if(count($pts)==0){return waf_status_box($title,$icon,waf_status_empty($tpl,$errors,$sec));}
        $labels=array();$data=array();$bg=array();$i=0;
        foreach($pts as $p){
            $labels[]=strval($p["label"]);$data[]=intval($p["count"]);
            $bg[]=is_callable($colors) ? $colors($p) : $colors[$i % count($colors)];
            $i++;
        }
        $ds="[{data:".json_encode($data).",backgroundColor:".json_encode($bg).",borderWidth:2}]";
        return waf_status_box($title,$icon,waf_status_chart($id,300,$type,$labels,$ds,
            "{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'$legendPos',labels:{padding:6,boxWidth:12,font:{size:11}}}}}"));
    };

    // Row 1: threats by hour today, row 2: 10-minute counters of the last 24 h, row 3: by day
    $h[]=$line("today_by_hour","chart-today",260,$tpl->javascript_parse_text("{threats} {by_hour} {today}"),
        "fas fa-clock","#1ab394","rgba(26,179,148,0.15)","tension:0.3,pointRadius:3,pointBackgroundColor:'#1ab394'");
    $h[]=$line("last_24h","chart-10min",220,$tpl->javascript_parse_text("{threats} {last} 24h (10min)"),
        "fas fa-wave-square","#1c84c6","rgba(28,132,198,0.10)","tension:0.2,pointRadius:0,borderWidth:1.5");

    $titleDays=$tpl->javascript_parse_text("{threats} {by_day}");
    $days=waf_status_points($o,"by_day");
    if(count($days)==0){
        $h[]=waf_status_box($titleDays,"fas fa-chart-bar",waf_status_empty($tpl,$errors,"by_day"));
    }else{
        $labels=array();$data=array();
        foreach($days as $p){$labels[]=strval($p["label"]);$data[]=intval($p["count"]);}
        $ds="[{label:$threatsT,data:".json_encode($data).",backgroundColor:'rgba(26,179,148,0.7)',borderColor:'#1ab394',borderWidth:1}]";
        $h[]=waf_status_box($titleDays,"fas fa-chart-bar",waf_status_chart("chart-days",260,"bar",$labels,$ds,
            "{responsive:true,maintainAspectRatio:false,$noLegend,scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{ticks:{font:{size:11}}}}}"));
    }

    // Row 4: severity doughnut + top attack types
    $SEV_COLORS=array(0=>"#97c782",1=>"#1ab394",2=>"#f8ac59",3=>"#ed5565",4=>"#c23616",5=>"#470000");
    $sevColor=function($p) use ($SEV_COLORS){$k=intval($p["key"]);return isset($SEV_COLORS[$k]) ? $SEV_COLORS[$k] : "#676a6c";};
    $h[]="<table style='width:100%'><tr><td style='width:40%;vertical-align:top'>";
    $h[]=$pie("severity","chart-severity","doughnut",$tpl->javascript_parse_text("{severity}"),"fas fa-exclamation-triangle",$sevColor,"bottom");
    $h[]="</td><td style='width:60%;vertical-align:top;padding-left:10px'>";
    $h[]=$hbar("top_attacks","chart-attacks",$tpl->javascript_parse_text("{top} {threats}"),"fas fa-bug","#ed5565",32,"");
    $h[]="</td></tr></table>";

    // Row 5: top exposed websites + top source addresses
    $PIE_COLORS=array("#005447","#006455","#1ab394","#3fb88f","#5fbd8b","#7cc287","#97c782","#b0cc5d","#d4e297","#F6F9D1",
        "#1c84c6","#23c6c8","#f8ac59","#ed5565","#676a6c","#ab7df6","#e74c3c","#3498db","#2ecc71","#9b59b6");
    $h[]="<table style='width:100%'><tr><td style='width:50%;vertical-align:top'>";
    $h[]=$pie("top_sites","chart-sites","pie",$tpl->javascript_parse_text("{top_exposed_websites}"),"fas fa-globe",$PIE_COLORS,"right");
    $h[]="</td><td style='width:50%;vertical-align:top;padding-left:10px'>";
    $h[]=$pie("top_ips","chart-ips","pie",$tpl->javascript_parse_text("{top} {src}"),"fas fa-network-wired",array_merge(array_slice($PIE_COLORS,5),array_slice($PIE_COLORS,0,5)),"right");
    $h[]="</td></tr></table>";

    // Row 6: top rule ids
    $h[]=$hbar("top_rules","chart-rules",$tpl->javascript_parse_text("{top} {rules}"),"fas fa-list-ol","#1c84c6",28,"Rule ");

    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
    return true;
}

function count_protected_sites():int{
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/service_parameters/zkey/EnableModSecurity");
    $data=json_decode($resp,true);
    if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return 0;}
    $n=0;
    foreach($data["rows"] as $r){
        if(isset($r["zvalue"]) && trim(strval($r["zvalue"]))==="1"){$n++;}
    }
    return $n;
}
