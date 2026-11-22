<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["build-page"])){build_page();exit;}
if(isset($_GET["build-tabs"])){build_tabs();exit;}

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["center-status"])){center_status();exit;}
page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("Artica SIEM {status}",ico_speed,"{APP_ARTICA_SIEM_EXPLAIN}",
        "$page?build-tabs=yes","siem-status",
        "progress-siem",false);
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica SIEM {status}",$html);
        echo $tpl->build_firewall();return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// {capacity_calculator}
function build_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();

    $array["<i class='fas fa-tachometer-fastest'></i>&nbsp;{status}"]="$page?build-page=yes";
    $array["<i class='fas fa-compact-disc'></i>&nbsp;{installation}"]="fw.statistics.install.php?build-page=yes";
    $array["<i class='fas fa-sliders'></i>&nbsp;{siem_parameters}"]="fw.statistics.parameters.php?build-page=yes";
    $array["<i class='fas fa-calculator'></i>&nbsp;{capacity_calculator}"]="fw.statistics.capacity.php?build-page=yes";
    echo $tpl->tabs_default($array);
}

// siem_bytes() is NOT declared here: it comes from class.statistics_appliance.inc
// (included above), which every SIEM page shares. PHP hoists a file's top-level
// function declarations at compile time, BEFORE its own include_once runs — so a
// local copy is declared first and the shared include then fatals on redeclare,
// taking the whole page down. Reuse the shared helper, never re-declare it.

function center_status(){
    $tpl=new template_admin();
    $stats=new statistics_appliance();
    $o=$stats->get("/api/v1/admin/overview");
    if(!$stats->ok()){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{api_unavailable}")); return; }
    $total=isset($o["total_events"])?intval($o["total_events"]):0;
    $db=isset($o["db_bytes"])?floatval($o["db_bytes"]):0;
    $eps=isset($o["events_per_sec"])?floatval($o["events_per_sec"]):0;
    $series=isset($o["series"])?$o["series"]:array();

    $html=array();
    // Row of three KPI widgets.
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>";
    $html[]=$tpl->widget_h("minwidth:210:green","fas fa-database",number_format($total),"{total_events}");
    $html[]="</td>";
    $html[]="<td style='width:33%;padding-left:5px'>";
    $html[]=$tpl->widget_h("minwidth:210:lazur","fas fa-hard-drive",siem_bytes($db),"{database_size}");
    $html[]="</td>";
    $html[]="<td style='width:33%;padding-left:5px'>";
    $html[]=$tpl->widget_h("minwidth:210:blue","fas fa-gauge-high",number_format($eps,2)." /s","{siem_events_per_sec}");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    // Line chart: stored events per 10-minute bucket.
    $labels=array();$data=array();
    if(is_array($series)){
        foreach($series as $p){
            $labels[]=json_encode(substr(isset($p["bucket"])?$p["bucket"]:"",11)); // HH:MM
            $data[]=intval(isset($p["events"])?$p["events"]:0);
        }
    }
    $lbl=implode(",",$labels);$dv=implode(",",$data);
    $html[]="<div style='background:#fff;border:1px solid #e2e8f0;border-radius:5px;padding:16px 20px;margin-top:14px'>";
    $html[]="<div style='font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#191c1e;margin-bottom:10px'>{siem_events_10m}</div>";
    $html[]="<div style='position:relative;height:260px'><canvas id='siem-evts'></canvas></div></div>";

    $js="function siemEnsureChart(cb){if(typeof Chart!=='undefined'){cb();return;}".
        "var s=document.createElement('script');s.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';".
        "s.onload=function(){cb();};document.head.appendChild(s);}";
    $js.="siemEnsureChart(function(){var c=document.getElementById('siem-evts');if(!c)return;".
         "if(c._ch){c._ch.destroy();}".
         "c._ch=new Chart(c,{type:'line',data:{labels:[$lbl],datasets:[".
         "{label:'{events}',data:[$dv],borderColor:'#10b981',backgroundColor:'rgba(16,185,129,0.12)',fill:true,tension:0.35,pointRadius:0,borderWidth:2}]},".
         "options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},".
         "scales:{x:{grid:{display:false},ticks:{maxTicksLimit:10,color:'#64748b'}},".
         "y:{beginAtZero:true,grid:{color:'#eceef0'},ticks:{color:'#64748b'}}}}});});";
    $html[]="<script>$js</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}
function build_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:250px;vertical-align:top'><div id='siem-status'></div></td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'><div id='center-status'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";

    // TINY header buttons (refresh).
    $topbuttons=array();
    $jsrestart=$tpl->framework_buildjs(
        "/articasiem/restart","articasiem.progress","articasiem.progress.logs",
        "progress-siem");


    $topbuttons[]=array($jsrestart,ico_refresh,"{restart_service}");
    $APP_ARTICA_SIEM_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ARTICA_SIEM_VERSION");
    if(strlen($APP_ARTICA_SIEM_VERSION)>3){
        $APP_ARTICA_SIEM_VERSION=" v$APP_ARTICA_SIEM_VERSION";
    }
    $TINY=array();
    $TINY["TITLE"]="Artica SIEM$APP_ARTICA_SIEM_VERSION {status}";
    $TINY["ICO"]=ico_speed;
    $TINY["EXPL"]="{APP_ARTICA_SIEM_EXPLAIN}";
    $TINY["URL"]="siem-status";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    $refresh=$tpl->RefreshInterval_js("siem-status","$page","status=yes");
    $refresh2=$tpl->RefreshInterval_js("center-status","$page","center-status=yes");

    $html[]="<script>";
    $html[]="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
    $html[]=$refresh;
    $html[]=$refresh2;
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}
function status():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articasiem/status"),true);

    if(!isset($json["Status"])){
        echo $tpl->widget_rouge("{status}","{error} {protocol}");
        return true;
    }
    if(!$json["Status"]){
        echo $tpl->widget_rouge("{status}",$json["Error"]);
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json["Info"]);
    echo $tpl->SERVICE_STATUS($ini,"APP_ARTICA_SIEM",$jsrestart);
    echo $tpl->SERVICE_STATUS($ini,"APP_CLICKHOUSE",null);

    return true;
}


