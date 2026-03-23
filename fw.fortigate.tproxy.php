<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["fortigate-params-popup"])){fortigate_params_popup();exit;}
if(isset($_GET["fortigate-params-js"])){fortigate_params_js();exit;}
if(isset($_POST["fortigate-uninstall"])){fortigate_uninstall_confirm();exit;}
if(isset($_GET["fortigate-uninstall-js"])){fortigate_uninstall_js();exit;}
if(isset($_GET["fortigate-rules-popup"])){fortigate_rules_popup();exit;}
if(isset($_GET["fortigate-rules-js"])){fortigate_rules_js();exit;}
if(isset($_GET["tooltip-js"])){tooltip_js();exit;}
if(isset($_GET["tooltip-start"])){tooltip_start();exit;}
if(isset($_GET["tooltip-step1"])){tooltip_step1();exit;}
if(isset($_GET["tooltip-step2"])){tooltip_step2();exit;}
if(isset($_GET["tooltip-step3"])){tooltip_step3();exit;}
if(isset($_GET["tooltip-step4"])){tooltip_step4();exit;}
if(isset($_POST["FortinetTransparentFromIface"])){tooltip_save();exit;}
if(isset($_POST["FortinetTransparentToIface"])){tooltip_save();exit;}
if(isset($_GET["fortigate-stats"])){fortigate_stats();exit;}
if(isset($_GET["tproxy-chart"])){tproxy_chart_render();exit;}
if(isset($_GET["start"])){start();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $title="Fortigate &raquo; {APP_SQUID}";
    $addon="";
    if(isset($_GET["fortiget"])){
        $addon="&fortiget=yes";
    }
    $html=$tpl->page_header($title,ico_dashboard,"{fortigate_use_tproxy}","$page?start=yes$addon",
        "fortiports",
        "progress-fortiports-restart",false,
        "table-fortiports"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Fortigate > {APP_SQUID}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function fortigate_params_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog7("{parameters}","$page?fortigate-params-popup=yes",650);
}
function fortigate_params_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FortinetTransparentFromIface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortinetTransparentFromIface");
    $FortinetTransparentToIface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortinetTransparentToIface");
    $f[]=$tpl->field_hidden("InstallIT","yes");
    $f[]=$tpl->field_interfaces("FortinetTransparentFromIface","{listen_interface}",$FortinetTransparentFromIface);
    $f[]=$tpl->field_interfaces("FortinetTransparentToIface","{outgoing_interface}",$FortinetTransparentToIface);
    echo $tpl->form_outside("",$f,"","{apply}","dialogInstance7.close();");
    return true;
}
function fortigate_rules_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog7("{fortigate_rules}","$page?fortigate-rules-popup=yes",650);
}
function fortigate_uninstall_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_confirm_delete("{uninstall}","fortigate-uninstall","yes","document.location.href='/proxy-status'");
}
function fortigate_uninstall_confirm():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/uninstall");
    return admin_tracks("Uninstall Fortigate integration...");
}
function start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td><div id='fortigate-stats'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";


    $topbuttons[] = array("Loadjs('$page?fortigate-rules-js')", ico_support, "{fortigate_rules}");
    $topbuttons[] = array("Loadjs('$page?fortigate-params-js')", ico_params, "{parameters}");
    $topbuttons[] = array("Loadjs('$page?fortigate-uninstall-js')", ico_trash, "{uninstall}");

    $title="Fortigate &raquo; {APP_SQUID}";
    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=ico_firewall;
    $TINY_ARRAY["EXPL"]="{fortigate_use_tproxy}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]=$jstiny;
    if(isset($_GET["fortiget"])){
    $html[]="Loadjs('$page?fortigate-rules-js');";
    }
    $html[]="LoadAjaxSilent('fortigate-stats','$page?fortigate-stats=yes');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tooltip_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog7("Fortigate > {APP_SQUID}","$page?tooltip-start=yes",650);
}
function tooltip_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $f[]="<div id='fortidiv'></div>";
    $f[]="<script>LoadAjax('fortidiv','$page?tooltip-step1=yes')</script>";
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}
function tooltip_step1():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $nextButton=$tpl->button_autnonome("{next}","LoadAjax('fortidiv','$page?tooltip-step2=yes')",ico_wizard,
        "AsSquidAdministrator",400,"btn-primary");

    $f[]="<table style='width:100%;margin-top:-35px'>";
    $f[]="<tr>";
    $f[]="<td style='width:64px' nowrap><img src='img/Fortinet-logomark-rgb-red.png' style='margin:5px;width:100%'></td>";
    $f[]="<td style='width:70%;text-align:left'>";
    $f[]="<p class='font-size:18px'>{fortigate_use_tproxy2}</p>";
    $f[]="</td>";
    $f[]="</tr>";
    $f[]="</table>";

    $f[]="<table style='width:100%;margin-top:10px'>";
    $f[]="<tr>";
    $f[]="<td style='width:50%'></td>";
    $f[]="<td style='width:50%;text-align:right'>$nextButton</td>";
    $f[]="</tr>";
    $f[]="</table>";
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}

function tooltip_step2():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FortinetTransparentFromIface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortinetTransparentFromIface");
    $f[]=$tpl->field_interfaces("FortinetTransparentFromIface","{listen_interface}",$FortinetTransparentFromIface);
    $f[]=$tpl->form_add_button("{previous}","LoadAjax('fortidiv','$page?tooltip-step2=yes')");
    $html= $tpl->form_outside("",$f,"{fortigate_use_tproxy3}","{next}","LoadAjax('fortidiv','$page?tooltip-step3=yes')");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tooltip_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $InstallIT=false;
    if(isset($_POST["InstallIT"])){
        $InstallIT=true;
        unset($_POST["InstallIT"]);
    }
    $tpl->SAVE_POSTs();
    if($InstallIT){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/install");
        return admin_tracks_post("Fortigate params...");
    }
    return admin_tracks_post("Fortigate wizard...");
}
function tooltip_step3():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FortinetTransparentToIface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortinetTransparentToIface");
    $f[]=$tpl->field_interfaces("FortinetTransparentToIface","{outgoing_interface}",$FortinetTransparentToIface);
    $f[]=$tpl->form_add_button("{previous}","LoadAjax('fortidiv','$page?tooltip-step3=yes')");
    $html= $tpl->form_outside("",$f,"{fortigate_use_tproxy4}","{next}","LoadAjax('fortidiv','$page?tooltip-step4=yes')");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tooltip_step4():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/install");
    $f[]="<script>";
    $f[]="document.location.href='/fortiports'";
    $f[]="</script>";
    echo implode("\n",$f);
    return true;
}

function fortigate_rules_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FortinetTransparentFromIface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortinetTransparentFromIface");
    $ipclass=new system_nic($FortinetTransparentFromIface);
    $Ipaddr=$ipclass->IPADDR;
    $html[]="<div class='chatCode'>";
    $html[]="<div class='chatCode-header'>";
    $html[]="<span class='chatCode-lang'>fortigate console</span>";
    $html[]="<button class='chatCode-copy' onclick='...'>&nbsp;</button>";
    $html[]="</div>";
    $html[]="<pre># {fortigate_subtitle0}</pre>";
    $html[]="<pre>config system settings</pre>";
    $html[]="<pre>    set asymroute enable</pre>";
    $html[]="<pre>end</pre>";
    $html[]="<pre>&nbsp;</pre>";
    $html[]="<pre># {fortigate_subtitle1}</pre>";
    $html[]="<pre>config firewall address</pre>";
    $html[]="<pre>\tedit \"client1\"</pre>";
    $html[]="<pre>\t\tset subnet 192.168.1.190 255.255.255.255</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>\tedit \"client2\"</pre>";
    $html[]="<pre>\t\tset subnet 192.168.1.191 255.255.255.255</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>\tedit \"ArticaProxy\"</pre>";
    $html[]="<pre>\t\tset subnet $Ipaddr 255.255.255.255</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>end</pre>";
    $html[]="<pre>&nbsp;</pre>";
    $html[]="<pre># {fortigate_subtitle2}</pre>";
    $html[]="<pre>config firewall addrgrp</pre>";
    $html[]="<pre>\tedit \"proxy-clients\"</pre>";
    $html[]="<pre>\t\tset member \"client1\" \"client2\"</pre>";
    $html[]="<pre>\tnext</pre>";
    $html[]="<pre>end</pre>";
    $html[]="<pre>&nbsp;</pre>";
    $html[]="<pre># {fortigate_subtitle3}</pre>";
    $html[]="<pre>config router policy</pre>";
    $html[]="<pre># {fortigate_subtitle5}</pre>";
    $html[]="<pre>    edit 1</pre>";
    $html[]="<pre>        set input-device \"internal\"</pre>";
    $html[]="<pre>        set srcaddr \"ArticaProxy\"</pre>";
    $html[]="<pre>        set gateway 10.10.1.254</pre>";
    $html[]="<pre>        set output-device \"wan1\"</pre>";
    $html[]="<pre>    next</pre>";
    $html[]="<pre>    edit 2</pre>";
    $html[]="<pre>        set input-device internal</pre>";
    $html[]="<pre>        set srcaddr \"proxy-clients\"</pre>";
    $html[]="<pre>        set protocol 6</pre>";
    $html[]="<pre>        set start-port 80</pre>";
    $html[]="<pre>        set end-port 80</pre>";
    $html[]="<pre>        set gateway $Ipaddr</pre>";
    $html[]="<pre>        set output-device internal</pre>";
    $html[]="<pre>    next</pre>";
    $html[]="<pre>    edit 3</pre>";
    $html[]="<pre>        set input-device internal</pre>";
    $html[]="<pre>        set srcaddr \"proxy-clients\"</pre>";
    $html[]="<pre>        set protocol 6</pre>";
    $html[]="<pre>        set start-port 443</pre>";
    $html[]="<pre>        set end-port 443</pre>";
    $html[]="<pre>        set gateway $Ipaddr</pre>";
    $html[]="<pre>        set output-device internal</pre>";
    $html[]="<pre>    next</pre>";
    $html[]="<pre>end</pre>";
    $html[]="<pre>&nbsp;</pre>";
    $html[]="<pre># {fortigate_subtitle4}</pre>";
    $html[]="<pre>config firewall policy</pre>";
    $html[]="<pre>    edit 15</pre>";
    $html[]="<pre>        set name \"Transparent_proxy_hairpin\"</pre>";
    $html[]="<pre>        set srcintf internal</pre>";
    $html[]="<pre>        set dstintf internal</pre>";
    $html[]="<pre>        set srcaddr \"proxy-clients\"</pre>";
    $html[]="<pre>        set dstaddr all</pre>";
    $html[]="<pre>        set action accept</pre>";
    $html[]="<pre>        set schedule always</pre>";
    $html[]="<pre>        set service HTTP HTTPS</pre>";
    $html[]="<pre>        set comments \"Transparent-proxy-hairpin\"</pre>";
    $html[]="<pre>    next</pre>";
    $html[]="<pre>    move 15 before 1";
    $html[]="<pre>end</pre>";
    $html[]="<pre>&nbsp;</pre>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function _tproxy_date_fmt($period){
    $map=array('current'=>'H:i','hourly'=>'M d H:i','daily'=>'M d','weekly'=>'M d');
    return isset($map[$period]) ? $map[$period] : 'H:i';
}

function fortigate_stats(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    // Status
    $statusJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/status"));
    $enabled=false; $iface='—'; $srcIp='—'; $modsOk=false; $routeOk=false; $rulesOk=false; $collectorOk=false;
    if(is_object($statusJson) && !empty($statusJson->success) && is_object($statusJson->data)){
        $d=$statusJson->data;
        $enabled=!empty($d->enabled);
        $iface=htmlspecialchars($d->interface ?? '—');
        $srcIp=htmlspecialchars($d->source_ip ?? '—');
        $modsOk=!empty($d->modules_loaded);
        $routeOk=!empty($d->routing_ok);
        $rulesOk=!empty($d->rules_ok);
        $collectorOk=!empty($d->collector_running);
    }

    $h=array();

    // Summary widgets
    $enabledBg=$enabled?'#1ab394':'#ed5565';
    $enabledTxt=$enabled?'{enabled}':'{disabled}';
    $h[]="<div class='row' style='margin:10px 0 15px'>";
    $h[]="<div class='col-md-2'><div style='background:$enabledBg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:18px;font-weight:700'>$enabledTxt</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-power-off'></i> TPROXY</div></div></div>";
    $h[]="<div class='col-md-2'><div style='background:#1c84c6;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:16px;font-weight:700'>$iface</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-ethernet'></i> {interface}</div></div></div>";
    $h[]="<div class='col-md-3'><div style='background:#34495e;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:14px;font-weight:700;font-family:monospace'>$srcIp</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-network-wired'></i> {source}</div></div></div>";

    // Health badges
    $badges=array();
    $badges[]=$modsOk ? "<span class='badge' style='background:#1ab394;color:#fff'>Modules</span>" : "<span class='badge' style='background:#ed5565;color:#fff'>Modules</span>";
    $badges[]=$routeOk ? "<span class='badge' style='background:#1ab394;color:#fff'>Routing</span>" : "<span class='badge' style='background:#ed5565;color:#fff'>Routing</span>";
    $badges[]=$rulesOk ? "<span class='badge' style='background:#1ab394;color:#fff'>Rules</span>" : "<span class='badge' style='background:#ed5565;color:#fff'>Rules</span>";
    $badges[]=$collectorOk ? "<span class='badge' style='background:#1ab394;color:#fff'>Collector</span>" : "<span class='badge' style='background:#ed5565;color:#fff'>Collector</span>";
    $h[]="<div class='col-md-5' style='padding-top:10px'>".implode(" ",$badges)."</div>";
    $h[]="</div>";

    // Period buttons
    $periods=array('current'=>'{this_hour}','hourly'=>'{today}','daily'=>'{this_week}','weekly'=>'{this_month}');
    $h[]="<div style='margin-bottom:15px'>";
    $h[]="  <div class='btn-group' id='tp-range-btns'>";
    foreach($periods as $key=>$label){
        $active=($key==='current')?' active':'';
        $h[]="    <button class='btn btn-sm btn-default$active' onclick=\"TpRange('$key',this)\">$label</button>";
    }
    $h[]="  </div>";
    $h[]="  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"TpRefresh();\">";
    $h[]="    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[]="</div>";

    $h[]="<div id='tp-chart-area'></div>";

    $h[]="<script>";
    $h[]="var _tpPeriod='current';";
    $h[]="function TpRange(p,btn){";
    $h[]="  _tpPeriod=p;";
    $h[]="  \$('#tp-range-btns .btn').removeClass('active');";
    $h[]="  \$(btn).addClass('active');";
    $h[]="  TpRefresh();";
    $h[]="};";
    $h[]="function TpRefresh(){";
    $h[]="  LoadAjax('tp-chart-area','$page?tproxy-chart=yes&period='+_tpPeriod);";
    $h[]="};";
    $h[]="TpRefresh();";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function tproxy_chart_render(){
    $tpl=new template_admin();
    $period=trim($_GET["period"] ?? 'hourly');
    $allowed=array('current','hourly','daily','weekly');
    if(!in_array($period,$allowed)) $period='hourly';
    $fmt=_tproxy_date_fmt($period);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/stats?period=$period"));
    if(!is_object($json) || empty($json->success) || !is_object($json->data) || !is_array($json->data->samples) || empty($json->data->samples)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $samples=$json->data->samples;

    // Determine best byte unit
    $maxBytes=0;
    foreach($samples as $s){
        $v=max(intval($s->http_bytes ?? 0),intval($s->https_bytes ?? 0));
        if($v>$maxBytes) $maxBytes=$v;
    }
    if($maxBytes>1073741824){ $unit='GB'; $divisor=1073741824; }
    elseif($maxBytes>1048576){ $unit='MB'; $divisor=1048576; }
    else{ $unit='KB'; $divisor=1024; }

    $labels=array();
    $httpBytes=array(); $httpsBytes=array();
    $httpPkts=array(); $httpsPkts=array();

    foreach($samples as $s){
        $labels[]=date($fmt,$s->timestamp);
        $httpBytes[]=sprintf('%.2f',intval($s->http_bytes ?? 0)/$divisor);
        $httpsBytes[]=sprintf('%.2f',intval($s->https_bytes ?? 0)/$divisor);
        $httpPkts[]=intval($s->http_pkts ?? 0);
        $httpsPkts[]=intval($s->https_pkts ?? 0);
    }

    $labelsJS="['".implode("','",$labels)."']";
    $httpBJS='['.implode(',',$httpBytes).']';
    $httpsBJS='['.implode(',',$httpsBytes).']';
    $httpPJS='['.implode(',',$httpPkts).']';
    $httpsPJS='['.implode(',',$httpsPkts).']';
    $pr=($period==='current')?'1':'2';

    $h=array();
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // ── Bandwidth chart (line) ──
    $uid1='tp-bw-'.uniqid();
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-tachometer-alt'></i>&nbsp; TPROXY {bandwidth}</h5>";
    $h[]="<div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$period</span></div></div>";
    $h[]="<div class='ibox-content'><div style='position:relative;height:300px'><canvas id='$uid1'></canvas></div></div></div>";
    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid1'),{type:'line',";
    $h[]="data:{labels:$labelsJS,datasets:[";
    $h[]="{label:'HTTP ($unit)',data:$httpBJS,borderColor:'rgb(54,162,235)',backgroundColor:'rgba(54,162,235,0.08)',pointRadius:$pr,tension:0.3,fill:true,borderWidth:2},";
    $h[]="{label:'HTTPS ($unit)',data:$httpsBJS,borderColor:'rgb(255,99,132)',backgroundColor:'rgba(255,99,132,0.08)',pointRadius:$pr,tension:0.3,fill:true,borderWidth:2}";
    $h[]="]},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}},";
    $h[]="tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toFixed(2)+' $unit';}}}},";
    $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:20}},";
    $h[]="y:{beginAtZero:true,title:{display:true,text:'$unit'}}}}});";
    $h[]="</script>";

    // ── Packets chart (bar) ──
    $uid2='tp-pkt-'.uniqid();
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-box'></i>&nbsp; TPROXY Packets</h5></div>";
    $h[]="<div class='ibox-content'><div style='position:relative;height:260px'><canvas id='$uid2'></canvas></div></div></div>";
    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid2'),{type:'bar',";
    $h[]="data:{labels:$labelsJS,datasets:[";
    $h[]="{label:'HTTP',data:$httpPJS,backgroundColor:'rgba(54,162,235,0.7)',borderRadius:3},";
    $h[]="{label:'HTTPS',data:$httpsPJS,backgroundColor:'rgba(255,99,132,0.7)',borderRadius:3}";
    $h[]="]},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}},";
    $h[]="tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toLocaleString()+' pkts';}}}},";
    $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:20}},";
    $h[]="y:{beginAtZero:true,title:{display:true,text:'Packets'}}}}});";
    $h[]="</script>";

    // ── HTTP vs HTTPS ratio (doughnut) ──
    $totalHttp=0; $totalHttps=0;
    foreach($samples as $s){
        $totalHttp+=intval($s->http_bytes ?? 0);
        $totalHttps+=intval($s->https_bytes ?? 0);
    }
    if($totalHttp>0 || $totalHttps>0){
        $uid3='tp-ratio-'.uniqid();
        $httpPct=($totalHttp+$totalHttps)>0 ? sprintf('%.1f',$totalHttp/($totalHttp+$totalHttps)*100) : '0';
        $httpsPct=($totalHttp+$totalHttps)>0 ? sprintf('%.1f',$totalHttps/($totalHttp+$totalHttps)*100) : '0';

        $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-chart-pie'></i>&nbsp; HTTP / HTTPS {ratio}</h5></div>";
        $h[]="<div class='ibox-content' style='text-align:center'><div style='position:relative;height:250px;max-width:350px;margin:0 auto'><canvas id='$uid3'></canvas></div></div></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('$uid3'),{type:'doughnut',";
        $h[]="data:{labels:['HTTP ($httpPct%)','HTTPS ($httpsPct%)'],datasets:[{";
        $h[]="data:[$totalHttp,$totalHttps],";
        $h[]="backgroundColor:['rgba(54,162,235,0.8)','rgba(255,99,132,0.8)'],";
        $h[]="borderWidth:0,hoverOffset:8}]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,";
        $h[]="plugins:{legend:{position:'bottom',labels:{padding:15}}}}});";
        $h[]="</script>";
    }

    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}
