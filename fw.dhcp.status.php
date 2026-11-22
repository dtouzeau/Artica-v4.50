<?php
// stamp 01/11/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["range1"])){dhcp_save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$DHCPDVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");

    $html=$tpl->page_header("{APP_DHCP} v$DHCPDVersion",ico_computer_down,"{EnableDHCPServer_text}","$page?table=yes","dhcp-status",
        "progress-dhcp-restart",false,"table-loader-dhcp-service");
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_DHCP} v$DHCPDVersion",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

// The DHCP status dashboard (?table=yes): the service tile on the left, and
// on the right three KPI tiles, the last-hour/yesterday request curves, the
// most active device / most assigned address, the occupancy of each pool
// (bars + history) and the latest leases. Design:
// /home/claude/images/stitch_dhcp_status_dashboard_redesign.zip.
function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.dhcpmetrics.inc");

    $defined=null; $used=null; $free=null;
    $DHCPD_POOLS_JSON=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_POOLS_JSON");
    if(strlen($DHCPD_POOLS_JSON)>10){
        $json=json_decode($DHCPD_POOLS_JSON);
        if(isset($json->summary)){
            $defined=intval($json->summary->defined);
            $used=intval($json->summary->used);
            $free=intval($json->summary->free);
        }
    }
    if($defined===null){
        $q=new postgres_sql();
        $used=intval($q->COUNT_ROWS_LOW("dhcpd_leases"));
        $defined=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_COUNT_OF_QUERIES"));
    }

    $h=array();
    $h[]="<table style='width:100%;table-layout:fixed'><tr>";
    $h[]="<td style='width:350px;vertical-align:top'><div id='dhcp-service'></div></td>";
    $h[]="<td style='vertical-align:top'><div class='dhst'>";

    // ---- KPI tiles
    $pctUsed=$defined>0?sprintf("%.1f%%",$used*100/$defined):"";
    $pctFree=($defined>0 && $free!==null)?sprintf("%.1f%%",$free*100/$defined):"";
    $h[]="<div class='dhst-kpis'>";
    $h[]=dhst_kpi("dhst-k1","fa-solid fa-desktop","{computers_number}",$defined,"");
    $h[]=dhst_kpi("dhst-k2","fa-solid fa-laptop","{leases}",$used,$pctUsed==""?"":"$pctUsed {dhst_active_occupancy}");
    if($free!==null){
        $h[]=dhst_kpi("dhst-k3","fa-solid fa-pause","{dhst_free_ip}",$free,$pctFree==""?"":"$pctFree {dhst_free_reserve}");
    }
    $h[]="</div>";

    // ---- request curves
    $href="LoadAjax('MainContent','fw.dhcp.metrics.php')";
    $lh=dhcpm_api("/dhcpd/metrics/timeline",array("preset"=>"lasthour","interval"=>"5min"));
    $yd=dhcpm_api("/dhcpd/metrics/timeline",array("preset"=>"yesterday","interval"=>"hour"));
    $js=array();
    if($lh && $lh->success){
        $h[]="<div class='dhst-grid2'>";
        list($card,$script)=dhst_curve("dhstlh",$lh,"{dhcp_requests} &mdash; {last_hour}","{dhst_lasthour_explain}","peak","#059669","rgba(16,185,129,0.25)",$href);
        $h[]=$card; $js[]=$script;
        if($yd && $yd->success){
            list($card,$script)=dhst_curve("dhstyd",$yd,"{dhcp_requests} &mdash; {yesterday}","{dhst_yesterday_explain}","total","#0891b2","rgba(6,182,212,0.22)",$href);
            $h[]=$card; $js[]=$script;
        }
        $h[]="</div>";
    }

    // ---- most active device / most assigned address (last 24 h)
    $sum=dhcpm_api("/dhcpd/metrics/summary",array("range"=>"24h"));
    if($sum!==null && $sum->success){
        $d=$sum->data;
        $h[]="<div class='dhst-grid2'>";
        $h[]=dhst_callout("{most_active_mac}",$d->top_mac??null,"dhst-green");
        $h[]=dhst_callout("{most_assigned_ip}",$d->top_ip??null,"dhst-cyan");
        $h[]="</div>";
    }

    // ---- pools
    list($card,$script)=dhst_pools();
    $h[]=$card; $js[]=$script;

    // ---- latest leases
    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log","progress-dhcp-restart",
            "LoadAjax('dhcp-status','$page?table=yes')"
        );
        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";
        $h[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
    }else{
        $h[]=dhst_leases();
    }

    $h[]="</div></td></tr></table>";
    $h[]="<script>LoadAjax('dhcp-service','$page?status=yes');</script>";

    // CSS and chart scripts go around the parse, never through it: the
    // engine would read every CSS/JS {...} block as a {token}.
    echo dhst_style().$tpl->_ENGINE_parse_body(implode("\n",$h))
        ."<script>".dhcpm_charts_runtime_js()."\nwindow.dhcpmEnsureChart(function(){\n".implode("\n",$js)."\n});</script>";
    return true;
}

function dhst_kpi(string $cls,string $ico,string $label,$value,string $sub): string {
    $v=$value===null?"-":FormatNumber(intval($value));
    $s=$sub==""?"":"<div class='dhst-kpi-sub'>".$sub."</div>";
    return "<div class='dhst-kpi $cls'><div class='dhst-kpi-ico'><i class='$ico'></i></div>"
        ."<div class='dhst-kpi-txt'><div class='dhst-kpi-lbl'>$label</div><div class='dhst-kpi-val'>$v</div>$s</div></div>";
}

// One request-curve card. $badge "peak" = highest 5-minute count,
// "total" = sum of the series. Returns [card html, chart js].
function dhst_curve(string $id,$json,string $title,string $explain,string $badge,string $line,string $fill,string $href): array {
    $labels=array(); $vals=array();
    $fmt=(isset($json->data->interval)&&$json->data->interval=="day")?"m-d":"H:i";
    foreach($json->data->series as $pt){ $labels[]=date($fmt,intval($pt->timestamp)); $vals[]=intval($pt->count); }
    if($badge=="peak"){
        $b="{peak}: ".(count($vals)?max($vals):0)." {dhst_req_per_5min}";
        $bcls="dhst-badge-green";
    }else{
        $b="{total}: ".FormatNumber(array_sum($vals))." {dhst_req}";
        $bcls="dhst-badge-grey";
    }
    $card="<div class='dhst-card dhst-click' OnClick=\"$href\">"
        ."<div class='dhst-card-head'><div><div class='dhst-card-title'>$title</div><div class='dhst-card-sub'>$explain</div></div>"
        ."<span class='dhst-badge $bcls'>$b</span></div>"
        ."<div style='position:relative;height:110px'><canvas id='$id'></canvas></div></div>";
    $js="window.dhcpmDraw('$id',{type:'line',data:{labels:".json_encode($labels).",datasets:[{data:".json_encode($vals).",fill:true,tension:0.4,borderWidth:2.5,pointRadius:0,borderColor:'$line',backgroundColor:'$fill'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{maxTicksLimit:4,maxRotation:0,font:{size:11},color:'#64748b'}},y:{display:false,beginAtZero:true}}}});";
    return array($card,$js);
}

function dhst_callout(string $label,$top,string $cls): string {
    $v="-";
    if($top!==null && isset($top->key)){
        $v=dhcpm_h($top->key)." <span class='$cls'>(".FormatNumber(intval($top->count))." {dhst_req})</span>";
    }
    return "<div class='dhst-callout'><span class='dhst-callout-lbl'>$label</span><span class='dhst-callout-val'>$v</span></div>";
}

// Pool occupancy: one bar per range (red at or above the saturation alert
// threshold) and percent_max over the last 24 h per range.
function dhst_pools(): array {
    $colors=array("#0284c7","#f43f5e","#d97706","#005447","#059669","#475569");
    $json=dhcpm_api("/dhcpd/metrics/pools",array("range"=>"24h"));
    $st=dhcpm_api("/dhcpd/metrics/alerts/status");
    $limit=90.0;
    if($st!==null && $st->success && isset($st->data->thresholds->pool_percent)){$limit=floatval($st->data->thresholds->pool_percent);}
    $samples=($json!==null && $json->success && isset($json->data->current->samples) && is_array($json->data->current->samples))?$json->data->current->samples:array();

    $c=array("<div class='dhst-card dhst-section'>");
    $legend=array();
    foreach($samples as $i=>$s){
        $legend[]="<span class='dhst-leg'><span class='dhst-leg-sq' style='background:".$colors[$i%count($colors)]."'></span>".dhcpm_h($s->range)."</span>";
    }
    $c[]="<div class='dhst-section-head'><div><div class='dhst-section-title'><i class='fa-solid fa-bars-progress'></i> {dhcp_pool_occupancy}</div>"
        ."<div class='dhst-card-sub'>{dhst_pool_explain}</div></div><div class='dhst-legend'>".implode("",$legend)."</div></div>";
    if(count($samples)==0){
        $c[]="<div class='dhst-empty'>{dhcp_pool_occupancy_none}</div></div>";
        return array(implode("",$c),"");
    }
    foreach($samples as $i=>$s){
        $pct=floatval($s->percent);
        $color=$pct>=$limit?"#ba1a1a":$colors[$i%count($colors)];
        $w=max(0,min(100,$pct));
        $c[]="<div class='dhst-pool'><div class='dhst-pool-line'><span class='dhst-pool-range'>".dhcpm_h($s->range)."</span>"
            ."<span class='dhst-pool-stat'><strong style='color:$color'>".intval($s->used)."</strong> / ".intval($s->defined)." {dhst_leases_assigned}"
            ." <span class='dhst-dot'>&bull;</span> <strong>".sprintf("%.1f%%",$pct)."</strong> {dhst_occupied}"
            ." <span class='dhst-muted'>(".intval($s->free)." {dhst_available})</span></span></div>"
            ."<div class='dhst-bar'><div style='width:".sprintf("%.1f",$w)."%;background:$color'></div></div></div>";
    }

    // History: labels = sorted union of every series' timestamps, each
    // dataset aligned on it (null = no sample at that time).
    $series=($json!==null && isset($json->data->series) && is_array($json->data->series))?$json->data->series:array();
    $js="";
    if(count($series)>0){
        $ts=array(); $byRange=array();
        foreach($series as $sr){
            $m=array();
            foreach($sr->points as $pt){ $t=intval($pt->timestamp); $ts[$t]=true; $m[$t]=round(floatval($pt->percent_max),1); }
            $byRange[(string)$sr->range]=$m;
        }
        $keys=array_keys($ts); sort($keys,SORT_NUMERIC);
        $labels=array(); foreach($keys as $t){$labels[]=date("H:i",$t);}
        $datasets=array(); $i=0;
        foreach($byRange as $range=>$m){
            $data=array(); foreach($keys as $t){$data[]=array_key_exists($t,$m)?$m[$t]:null;}
            $col=$colors[$i%count($colors)]; $i++;
            $datasets[]=array("label"=>$range,"data"=>$data,"fill"=>false,"tension"=>0.3,"borderWidth"=>2.5,"borderColor"=>$col,"backgroundColor"=>$col,"pointRadius"=>2,"spanGaps"=>true);
        }
        $c[]="<div class='dhst-timeline'><div class='dhst-timeline-head'><span>{dhst_pool_timeline}</span><span class='dhst-muted'>{dhst_last_24h}</span></div>"
            ."<div style='position:relative;height:190px'><canvas id='dhstpools'></canvas></div></div>";
        $js="window.dhcpmDraw('dhstpools',{type:'line',data:{labels:".json_encode($labels).",datasets:".json_encode($datasets)."},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{maxTicksLimit:8,maxRotation:0,color:'#64748b'}},y:{beginAtZero:true,max:100,ticks:{color:'#94a3b8'},grid:{color:'#f1f5f9'}}}}});";
    }
    $c[]="</div>";
    return array(implode("",$c),$js);
}

// The five most recent leases (PostgreSQL dhcpd_leases, the table behind
// fw.dhcp.leases.php) with their state.
function dhst_leases(): string {
    $q=new postgres_sql();
    $total=intval($q->COUNT_ROWS_LOW("dhcpd_leases"));
    $results=$q->QUERY_SQL("SELECT ipaddr,mac,hostname,starts,ends FROM dhcpd_leases ORDER BY starts DESC LIMIT 5");
    $rows=array(); $now=time();
    if($q->ok && $results){
        while($l=pg_fetch_assoc($results)){
            // dhcpd writes lease times in UTC and dhcpd_leases stores them as
            // "timestamp without time zone": read them as UTC, display local.
            $ends=dhst_utc((string)$l["ends"]);
            if($ends>0 && $ends<$now){$st="<span class='dhst-pill dhst-pill-grey'>{expired}</span>";$dot="#94a3b8";}
            // "Expires soon" = in the last eighth of the lease (at least 5 min):
            // a fixed hour would flag every lease on a 1-hour-lease network.
            elseif($ends>0 && $ends-$now<max(300,intval(($ends-dhst_utc((string)$l["starts"]))/8))){$st="<span class='dhst-pill dhst-pill-amber'>{dhst_expires_soon}</span>";$dot="#d97706";}
            else{$st="<span class='dhst-pill dhst-pill-green'>{dhst_active}</span>";$dot="#10b981";}
            $start=dhst_utc((string)$l["starts"]);
            $rows[]="<tr><td class='dhst-strong'><span class='dhst-rowdot' style='background:$dot'></span>".dhcpm_h($l["ipaddr"])."</td>"
                ."<td class='dhst-muted'>".dhcpm_h($l["mac"])."</td>"
                ."<td>".dhcpm_h(trim((string)$l["hostname"])==""?"-":$l["hostname"])."</td>"
                ."<td class='dhst-muted' nowrap>".($start>0?dhcpm_h(date("Y-m-d H:i",$start)):"-")."</td>"
                ."<td class='dhst-muted' nowrap>".($ends>0?dhcpm_h(date("Y-m-d H:i",$ends)):"-")."</td>"
                ."<td style='text-align:right'>$st</td></tr>";
        }
    }
    if(count($rows)==0){$rows[]="<tr><td colspan='6' class='dhst-empty'>{no_data}</td></tr>";}
    $n=count($rows);
    return "<div class='dhst-card dhst-leases'><div class='dhst-leases-head'><div><span class='dhst-section-title'>{dhst_latest_leases}</span>"
        ." <span class='dhst-pill dhst-pill-grey'>".min(5,$total)." / ".FormatNumber($total)."</span></div>"
        ."<a href=\"javascript:blur();\" OnClick=\"LoadAjax('MainContent','fw.dhcp.leases.php')\" class='dhst-link'>{dhst_view_all_leases} &rarr;</a></div>"
        ."<div style='overflow-x:auto'><table class='dhst-tbl'><thead><tr><th>{ip_address}</th><th>{mac_address}</th><th>{hostname}</th>"
        ."<th>{dhst_lease_start}</th><th>{dhst_expiration}</th><th style='text-align:right'>{status}</th></tr></thead><tbody>"
        .implode("",$rows)."</tbody></table></div></div>";
}

function dhst_utc(string $v): int {
    $v=trim($v);
    if($v==""){return 0;}
    $t=strtotime($v." UTC");
    return $t===false?0:$t;
}

function dhst_style(): string {
    return "<style>
.dhst{display:flex;flex-direction:column;gap:18px;padding-left:10px}
.dhst *{box-sizing:border-box}
.dhst-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.dhst-kpi{border-radius:16px;padding:18px 20px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:110px}
.dhst-k1{background:linear-gradient(135deg,#0891b2,#0369a1)}
.dhst-k2{background:linear-gradient(135deg,#0d9488,#047857)}
.dhst-k3{background:linear-gradient(135deg,#059669,#115e59)}
.dhst-kpi-ico{width:56px;height:56px;border-radius:12px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:24px;flex:0 0 auto}
.dhst-kpi-txt{text-align:right;min-width:0}
.dhst-kpi-lbl{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;opacity:.9}
.dhst-kpi-val{font-size:32px;font-weight:800;line-height:1.15;margin-top:2px}
.dhst-kpi-sub{font-size:11px;opacity:.85;margin-top:2px}
.dhst-grid2{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px}
.dhst-kpis>*,.dhst-grid2>*{min-width:0}
.dhst-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:16px}
.dhst-click{cursor:pointer}
.dhst-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:8px}
.dhst-card-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#1e293b}
.dhst-card-sub{font-size:11px;color:#64748b;margin-top:2px}
.dhst-badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:600;white-space:nowrap}
.dhst-badge::first-letter{text-transform:uppercase}
.dhst-badge-green{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
.dhst-badge-grey{background:#f1f5f9;color:#475569}
.dhst-callout{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:12px}
.dhst-callout-lbl{color:#64748b;font-weight:500}
.dhst-callout-val{background:#f1f5f9;border-radius:6px;padding:2px 8px;font-weight:600;color:#1e293b}
.dhst-green{color:#059669;font-weight:700}
.dhst-cyan{color:#0891b2;font-weight:700}
.dhst-section{padding:20px}
.dhst-section-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;padding-bottom:12px;margin-bottom:16px;border-bottom:1px solid #f1f5f9}
.dhst-section-title{font-size:14px;font-weight:700;color:#0f172a}
.dhst-section-title i,.dhst-section-title svg{color:#059669;margin-right:6px}
.dhst-legend{display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:#475569}
.dhst-leg{display:inline-flex;align-items:center;gap:6px}
.dhst-leg-sq{width:12px;height:12px;border-radius:3px;display:inline-block}
.dhst-pool{margin-bottom:18px}
.dhst-pool-line{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;font-size:12px;margin-bottom:6px}
.dhst-pool-range{font-size:14px;font-weight:600;color:#1e293b}
.dhst-pool-stat{color:#334155}
.dhst-dot{color:#94a3b8;margin:0 4px}
.dhst-muted{color:#64748b}
.dhst-bar{width:100%;height:14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:2px;overflow:hidden}
.dhst-bar div{height:100%;border-radius:999px;min-width:0}
.dhst-timeline{margin-top:22px;padding-top:18px;border-top:1px solid #f1f5f9}
.dhst-timeline-head{display:flex;justify-content:space-between;font-size:12px;font-weight:600;color:#334155;margin-bottom:6px}
.dhst-empty{padding:20px;text-align:center;color:#64748b}
.dhst-leases{padding:0;overflow:hidden}
.dhst-leases-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid #f1f5f9}
.dhst-link{font-size:12px;font-weight:600;color:#059669!important}
.dhst-tbl{width:100%;border-collapse:collapse;font-size:12px}
.dhst-tbl thead th{background:#f8fafc;color:#64748b;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;padding:10px 16px;border-bottom:1px solid #f1f5f9;white-space:nowrap}
.dhst-tbl tbody td{padding:9px 16px;border-top:1px solid #f1f5f9;color:#334155}
.dhst-tbl tbody tr:hover{background:#f8fafc}
.dhst-strong{font-weight:600;color:#0f172a!important;white-space:nowrap}
.dhst-rowdot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px}
.dhst-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:600}
.dhst-pill-green{background:#d1fae5;color:#065f46}
.dhst-pill-amber{background:#fef3c7;color:#92400e}
.dhst-pill-grey{background:#f1f5f9;color:#475569}
@media(max-width:1100px){.dhst-kpis{grid-template-columns:1fr}.dhst-grid2{grid-template-columns:1fr}}
</style>";
}

function dhcp_save():bool{
	$sock=new sockets();
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return false;}
	$IPClass=new IP();
	
	$dhcp=new dhcpd(0,1);
	$sock=new sockets();
	
	$sock->SET_INFO('EnableDHCPUseHostnameOnFixed',$_POST["EnableDHCPUseHostnameOnFixed"]);
	$sock->SET_INFO("IncludeDHCPLdapDatabase", $_POST["IncludeDHCPLdapDatabase"]);

	$dhcp->listen_nic=$_POST["listen_nic"];
	$dhcp->deny_unkown_clients=$_POST["deny_unkown_clients"];
	$dhcp->ddns_domainname=$_POST["ddns_domainname"];
	$dhcp->max_lease_time=$_POST["max_lease_time"];
	$dhcp->get_lease_hostnames=$_POST["get_lease_hostnames"];
	$dhcp->netmask=$_POST["netmask"];
	$dhcp->range1=$_POST["range1"];
	$dhcp->range2=$_POST["range2"];
	$dhcp->subnet=$_POST["subnet"];
	$dhcp->broadcast=$_POST["broadcast"];
	$dhcp->WINS=$_POST["WINS"];
	$dhcp->ping_check=$_POST["DHCPPing_check"];
	$dhcp->authoritative=$_POST["DHCPauthoritative"];
	$dhcp->local_pac_server=$_POST["local-pac-server"];
	$dhcp->browser_portal_page=$_POST["browser-portal-page"];
	$dhcp->gateway=$_POST["gateway"];
	$dhcp->DNS_1=$_POST["DNS_1"];
	$dhcp->DNS_2=$_POST["DNS_2"];
	$dhcp->ntp_server=$_POST["ntp_server"];
	
	$dhcp->EnableArticaAsDNSFirst=$_POST["EnableArticaAsDNSFirst"];
	$dhcp->do_no_verify_range=$_POST["do_no_verify_range"];
	
	$MASK=$IPClass->maskTocdir($dhcp->subnet, $dhcp->netmask);
	
	if(!$IPClass->isInRange($dhcp->range1, "$MASK")){
		echo "$dhcp->subnet/$dhcp->netmask = $MASK\n$dhcp->range1 invalid for $MASK\n";
		return false;
	}
	if(!$IPClass->isInRange($dhcp->range2, "$MASK")){
	echo "$dhcp->subnet/$dhcp->netmask = $MASK\n$dhcp->range1 invalid for $MASK\n";
	return false;
	}
	if($dhcp->ddns_domainname==null){$dhcp->ddns_domainname="home.lan";}
	$dhcp->Save();
    return true;
}


function status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new template_admin();

    $data = $sock->REST_API("/dhcpd/service/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);



    $jsrestart=$tpl->framework_buildjs("/dhcpd/service/restart",
        "dhcpd.progress","dhcpd.progress.txt","progress-dhcp-restart");


	$btn_config=$tpl->button_autnonome("{config_file}", "Loadjs('$page?config-file-js=yes')", "fas fa-file-code","AsSystemAdministrator",335);
	echo $tpl->SERVICE_STATUS($ini, "APP_DHCP",$jsrestart).$tpl->_ENGINE_parse_body($btn_config);
	
	
}

function config_file_popup(){
	$tpl=new template_admin();
	// Read-only: the file is generated by articarest from the DHCP settings, secrets masked.
	$json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/dhcpd/config/file"));
	if(!is_object($json) || empty($json->Status)){
		echo $tpl->div_error("{error}: articarest /dhcpd/config/file");
		return;
	}
	if(!empty($json->Error)){
		// Pentest 2026-11-04 : div_error encode deja son texte (double encodage retire).
		echo $tpl->div_error((string)$json->Error);
		return;
	}
	// The content never goes through _ENGINE_parse_body(): dhcpd.conf blocks are "{ ... }".
	// Pentest 2026-11-04 : contenu dhcpd.conf (noms d'hotes) : encodage octet-sur (UTF-8 invalide rendait "").
	echo "<div class='awc-wrap'><div class='awc-card'><pre style='max-height:640px;overflow:auto;white-space:pre;font-family:monospace;font-size:12px;margin:0;padding:10px'>".req_html((string)$json->Content)."</pre></div></div>";
}
function config_file_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return;}
	$tpl->js_dialog6("{APP_DHCP} >> {config_file}", "$page?config-file-popup=yes",1000);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}