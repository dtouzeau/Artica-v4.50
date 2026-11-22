<?php
	include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["BandwithCalculationSchedule"])){save();exit;}
	if(isset($_GET["latest-picture"])){latest_picture();exit;}
	if(isset($_GET["metrics"])){metrics_json();exit;}
	if(isset($_GET["events"])){events_js();exit;}
	if(isset($_GET["events-search"])){events_search();exit;}
	if(isset($_GET["events-table"])){events_table();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{bandwidth}", "$page?popup=yes",1024);
}



function latest_picture(){
    $sock="/usr/share/artica-postfix/bin/run/articarest.sock";
    $ch=curl_init();
    curl_setopt($ch,CURLOPT_UNIX_SOCKET_PATH,$sock);
    curl_setopt($ch,CURLOPT_URL,"http://localhost/speedtests/latest/picture");
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $DATA=curl_exec($ch);
    curl_close($ch);
    if($DATA===false){$DATA="";}
    header("Content-type: image/png");
    header("Content-Length: ".strlen($DATA));
    echo $DATA;
    return;
}

function popup_install():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $jsrestart=$tpl->framework_buildjs(
        "/speedtests/install",
        "speedtests.progress","speedtests.log",
        "bandwidth-progress",
        "LoadAjax('monit-params','fw.system.monit.php?settings2=yes');Loadjs('$page');");

    $btn=$tpl->button_autnonome("{install}",$jsrestart,
        ico_cd,"AsSquidAdministrator",350,"btn-primary",80);

    $html= "<p style='margin-top:10px;text-align: right;margin-right:20px'><div id='bandwidth-progress'></div>$btn</p>";
    $div=$tpl->div_explain("{tests_speed}||{tests_speed_explain}$html");
    echo $tpl->_ENGINE_parse_body($div);
    return true;
}

function popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $EnableBandwithCalculation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBandwithCalculation"));
    if($EnableBandwithCalculation==0){
        return popup_install();
    }

    $BandwithCalculationSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BandwithCalculationSchedule"));
    $BandwithCalculationInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BandwithCalculationInterface"));
    $BandwithCalculationLocalProxy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BandwithCalculationLocalProxy"));
    $BandwithCalculationServerID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BandwithCalculationServerID"));
    $DisableBandwithCalculationSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableBandwithCalculationSchedule"));
    if($BandwithCalculationSchedule==0){$BandwithCalculationSchedule=1800;}
    $ServerList=file_get_contents("/usr/share/artica-postfix/ressources/logs/speed/speedtest-servers.json");
    $showServerList=false;

    if (strlen($ServerList)>10 ){
        $showServerList=true;
        $servers=json_decode($ServerList,TRUE);
        foreach($servers["servers"] as $key => $value){
            $SLIST[$value["id"]]=mb_strimwidth($value["name"],0
                ,30,"...")." / ".$value["location"]." / ".$value["country"];
        }

    }

    $jsrestart=$tpl->framework_buildjs(
        "/speedtests/install",
        "speedtests.progress","speedtests.log",
        "bandwidth-progress",
        "LoadAjax('monit-params','fw.system.monit.php?settings2=yes');");

    $jsresUninstall=$tpl->framework_buildjs(
        "/speedtests/uninstall",
        "speedtests.progress","speedtests.log",
        "bandwidth-progress",
        "LoadAjax('monit-params','fw.system.monit.php?settings2=yes');dialogInstance1.close();");


    // BandwithCalculationSchedule is stored in MINUTES (default 1800 = 30h).
    $Array=array();
    if($DisableBandwithCalculationSchedule==1){
        $BandwithCalculationLocalProxy=99999;
    }

    $Array[99999]="{only_by_schedule}";
    $Array[5]="5 {minutes}";
    $Array[10]="10 {minutes}";
    $Array[15]="15 {minutes}";
    $Array[30]="30 {minutes}";
    for($i=1;$i<=30;$i++){
        $mins=$i*60;
        $Array[$mins]=($i<2)?"$i {hour}":"$i {hours}";
    }
    $form[]=$tpl->field_checkbox("BandwithCalculationLocalProxy","{use_local_proxy}",$BandwithCalculationLocalProxy);
    $form[]=$tpl->field_array_hash($Array,"BandwithCalculationSchedule","nonull:{every}",$BandwithCalculationSchedule);
    $form[]=$tpl->field_interfaces("BandwithCalculationInterface","{outgoing_interface}",$BandwithCalculationInterface);
    if($showServerList){
        $form[]=$tpl->field_array_hash($SLIST,"BandwithCalculationServerID","{server} id",$BandwithCalculationServerID);
    }
    $form[]=$tpl->form_add_button("{uninstall}",$jsresUninstall);
    $forms=$tpl->form_outside("",$form,null,"{apply}",$jsrestart,"AsSystemAdministrator",true);


    $btn=$tpl->button_medium("{execute_now}","btn-primary","Loadjs('fw.system.status.bandwidth.progress.php')");
    $btnEvents=$tpl->button_medium("{events}","btn-default","Loadjs('$page?events=yes')");


    $latest=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/speedtests/latest"),true);
    $ligne=isset($latest["data"]) && is_array($latest["data"]) ? $latest["data"] : array("ztime"=>0);
    $hasPicture = isset($ligne["has_picture"]) ? $ligne["has_picture"] : false;
	$html[]="<H2>{latest_scan} (".$tpl->time_to_date($ligne["ztime"],true).")&nbsp;&nbsp;&nbsp;<span>$btn&nbsp;$btnEvents</span></H2>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:350px;vertical-align:top'>";
    if($hasPicture){
        $html[] = "<img style='width:350px' src='$page?latest-picture=yes'>";
    }



    $html[]="</td>";
    $html[]="<td style='padding-left: 15px;vertical-align:top'>";
    $html[]="<div id='bandwidth-progress'></div>";
    $html[]=$forms;
    $html[]="</td>";
    $html[]="</tr>";

    $wsel[]="<div style='margin:8px 0'>";
    foreach(array("hour"=>"{hour}","day"=>"{today}","week"=>"{week}","month"=>"{month}") as $w=>$lbl){
        $wsel[]="<button class='btn btn-default btn-sm' onclick=\"speedtestRenderCharts('$w');\">".$tpl->_ENGINE_parse_body($lbl)."</button> ";
    }
    $wsel[]="</div>";
    $html[]="<tr><td colspan=2>".implode("",$wsel)."</td></tr>";
    $html[]="<tr><td colspan=2><canvas id='bandwidth-canvas' height='90'></canvas></td></tr>";
    $html[]="<tr><td colspan=2><canvas id='upload-canvas' height='90'></canvas></td></tr>";
    $html[]="</table>";

    // Chart.min.4.5.1.js MUST load in the same response as chart creation (repo gotcha).
    $html[]="<script src='angular/js/plugins/chartJs/Chart.min.4.5.1.js'></script>";
    $html[]="<script>";
    $html[]="var bwCharts={};";
    $html[]="function bwDraw(id,label,labels,data,color){var c=document.getElementById(id);if(!c){return;}if(bwCharts[id]){bwCharts[id].destroy();}bwCharts[id]=new Chart(c,{type:'line',data:{labels:labels,datasets:[{label:label,data:data,borderColor:color,backgroundColor:color,tension:0.3,fill:false}]},options:{responsive:true,plugins:{legend:{display:true}},scales:{x:{type:'category'},y:{beginAtZero:true,title:{display:true,text:'Mbps'}}}}});}";
    $html[]="function speedtestRenderCharts(win){fetch('$page?metrics=yes&window='+win).then(function(r){return r.json();}).then(function(j){var labels=[],dl=[],ul=[];(j.points||[]).forEach(function(p){labels.push(p.label);dl.push(p.download_avg);ul.push(p.upload_avg);});bwDraw('bandwidth-canvas','Download (Mbps)',labels,dl,'#1f77b4');bwDraw('upload-canvas','Upload (Mbps)',labels,ul,'#2ca02c');});}";
    $html[]="speedtestRenderCharts('day');";
    $html[]="</script>";

	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function metrics_json(){
    $win=isset($_GET["window"])?preg_replace("/[^a-z]/","",$_GET["window"]):"day";
    header("Content-Type: application/json;charset=UTF-8");
    echo $GLOBALS["CLASS_SOCKETS"]->REST_API("/speedtests/metrics?window=$win");
}

function events_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{bandwidth}: {events}","$page?events-search=yes",900);
}

function events_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='speedtest-events-wrap' style='margin-top:10px'>";
    // search_block self-fires its initial (empty) search after 800ms
    // (Start$t()->ss$t()), which loads the events table into its results div —
    // no manual auto-click needed (a manual trigger would double-fetch).
    $html[]=$tpl->search_block($page,"",null,null,"&events-table=yes");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function events_table(){
    $tpl=new template_admin();
    $search=isset($_GET["search"])?trim($_GET["search"]):"";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/speedtests/logs/search?limit=500&q=".urlencode($search)),true);
    $events=(is_array($json) && isset($json["events"]) && is_array($json["events"]))?$json["events"]:array();

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{date}</th>";
    $html[]="<th class='center'>{result}</th>";
    $html[]="<th class='center'>{download}</th>";
    $html[]="<th class='center'>{upload}</th>";
    $html[]="<th>{server}</th>";
    $html[]="<th>{message}</th>";
    $html[]="</tr></thead><tbody id='spd-rows'>";

    $n=0;
    foreach($events as $e){
        if(!is_array($e)){continue;}
        $n++;
        $html[]=speedtest_event_row($e);
    }
    if($n==0){
        $html[]="<tr><td colspan='6' class='awc-empty'>{no_data}</td></tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n</span>"
           ."<span class='awc-pg' id='spd-pager'></span></div>";
    $html[]="</div></div>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();</script>"
        .awc_pager_js(50,"spd-rows","spd-pager");
}

/**
 * Un evenement de test de debit.
 * L'horodatage etait passe a date() sans garde: une entree sans "ts" produit
 * une erreur fatale en PHP 8.
 */
function speedtest_event_row(array $e):string{
    $ts=intval($e["ts"] ?? 0);
    $date=($ts>0)?date("Y-m-d H:i:s",$ts):"-";
    $level=(string)($e["level"] ?? "info");
    if($level=="success"){$badge="<span class='awc-badge awc-badge-on'>{success}</span>";}
    elseif($level=="failed"){$badge="<span class='awc-badge' style='background:#ba1a1a'>{failed}</span>";}
    else{$badge="<span class='awc-badge awc-badge-off'>".htmlspecialchars($level)."</span>";}

    $dl=(isset($e["download"]) && $e["download"]>0)?round($e["download"])." Mbps":"-";
    $ul=(isset($e["upload"])   && $e["upload"]>0)  ?round($e["upload"])." Mbps":"-";

    $h ="<tr>";
    $h.="<td class='awc-nowrap awc-mono'>$date</td>";
    $h.="<td class='center awc-nowrap'>$badge</td>";
    $h.="<td class='center awc-nowrap'>$dl</td>";
    $h.="<td class='center awc-nowrap'>$ul</td>";
    $h.="<td>".htmlspecialchars((string)($e["server"] ?? "-"))."</td>";
    $h.="<td>".htmlspecialchars((string)($e["message"] ?? ""))."</td>";
    $h.="</tr>";
    return $h;
}


function save(){

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableBandwithCalculationSchedule",0);

    if(intval($_POST["BandwithCalculationSchedule"]==99999)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableBandwithCalculationSchedule",1);
    }

    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}
