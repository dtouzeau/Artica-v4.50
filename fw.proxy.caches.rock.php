<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["form-js"])){form_js();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_POST["EnableRockCache"])){Save();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{central_cache} &middot; {rock_store}",
        ico_rocket,"{rock_header_tagline}","$page?table=yes","rock-cache","progress-rock-restart",false,
        "table-loader-cache-rock");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{central_cache} &middot; {rock_store}","$page?form-popup=yes");
    return true;
}

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"))==1){
        $tpl->FATAL_ERROR_SHOW_128("{DisableAnyCache_enabled_warning}");
        return false;
    }

    $swaptimeout=array(0=>"{disabled}",300=>"300 ms",500=>"500 ms",1000=>"1000 ms");
    $max_swap_rate=array(0=>"{default}",100=>"{lower_disk_speed}",200=>"{normal_disk_speed}",300=>"{high_disk_speed}");

    $shm_kb=rock_devshm_kb()*0.7;
    $max_rock_kb=$shm_kb*32;
    $sizes=rock_sizes(round($max_rock_kb/1024/1024));

    $SquidRockPath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockPath"));
    $EnableRockCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRockCache"));
    $SquidRockSwapTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockSwapTimeOut"));
    $SquidRockMaxSwap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockMaxSwap"));
    $SquidRockSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockSize"));
    $SquidRockMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockMaxSize"));
    if($SquidRockMaxSize==0){$SquidRockMaxSize=128;}
    $workers=rock_workers();

    // Live figures: Squid's cache manager (articarest /proxy/metrics/info, 10 s
    // snapshot) and the store-dir report (squid_get_cache_infos.db).
    $mgr=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/metrics/info"),true);
    $live=is_array($mgr) && !empty($mgr["status"]);
    $stores=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("squid_get_cache_infos.db"));
    if(!is_array($stores)){$stores=array();}
    $rockLive=$stores["$SquidRockPath/rock"]??null;

    $configure="Loadjs('$page?form-js=yes')";
    $h=array();

    // ---- KPI row ----
    $h[]="<section class='rck-kpis'>";
    if($live){
        $hit60=floatval($mgr["cache_hits"]["hits_percent_all_requests"]["60min"]??0);
        $hit5=floatval($mgr["cache_hits"]["hits_percent_all_requests"]["5min"]??0);
        $byte60=floatval($mgr["cache_hits"]["hits_percent_bytes_sent"]["60min"]??0);
        $byte5=floatval($mgr["cache_hits"]["hits_percent_bytes_sent"]["5min"]??0);
        $tHit=floatval($mgr["median_service_times"]["cache_hits_60min"]??0)*1000;
        $tMiss=floatval($mgr["median_service_times"]["cache_misses_60min"]??0)*1000;
        $h[]=rock_kpi("{rock_kpi_hits}","fas fa-bullseye",round($hit60,1)."%","",
            "{rock_kpi_hits_sub}","{last_5_minutes}",round($hit5,1)."%","violet");
        $h[]=rock_kpi("{rock_kpi_bytes}","fas fa-cloud-arrow-down",round($byte60,1)."%","",
            "{rock_kpi_bytes_sub}","{requests}",number_format(intval($mgr["http_requests_received"]??0),0,"."," "),"green");
        if($tHit>0){
            $badge=($tMiss>$tHit)?"<span class='ak-pill ak-info'>".round($tMiss/$tHit,1)."&times; {faster}</span>":"";
            $h[]=rock_kpi("{rock_kpi_latency}","fas fa-gauge-high",rock_ms($tHit),$badge,
                "{rock_kpi_latency_sub}","{rock_kpi_without_cache}",$tMiss>0?rock_ms($tMiss):"-","blue");
        }else{
            $h[]=rock_kpi("{rock_kpi_latency}","fas fa-gauge-high","-","",
                "{rock_kpi_no_hit_yet}","{rock_kpi_without_cache}",$tMiss>0?rock_ms($tMiss):"-","blue");
        }
    }else{
        $h[]="<div class='ak-card rck-kpi rck-kpi-wide'><div class='rck-kpi-head'><span>{rock_kpi_hits}</span></div>"
            ."<div class='rck-kpi-val rck-muted'>-</div><p>{rock_kpi_proxy_silent}</p></div>";
    }
    if($EnableRockCache==1 && is_array($rockLive) && intval($rockLive["MAX"]??0)>0){
        $cur=intval($rockLive["CURRENT"]??0);$max=intval($rockLive["MAX"]);
        $pct=round(min(100,$cur*100/$max),1);
        $h[]=rock_kpi("{rock_kpi_fill}","fas fa-layer-group","$pct%","",
            FormatBytes($cur)." / ".FormatBytes($max),"{workers}",(string)$workers,"amber");
    }else{
        $h[]=rock_kpi("{rock_kpi_fill}","fas fa-layer-group","-","",
            $EnableRockCache==1?"{cache_not_reported}":"{rock_kpi_disabled_sub}","{workers}",(string)$workers,"amber");
    }
    $h[]="</section>";

    // ---- Spotlight ----
    $on=$EnableRockCache==1;
    $h[]="<section class='ak-card ak-card-pad rck-spot'>";
    $h[]="<div class='rck-spot-top'>";
    $h[]="<div class='rck-spot-id'><div class='rck-logo'><i class='".ico_rocket."'></i></div><div class='rck-spot-txt'>";
    $h[]="<div class='rck-title-row'><h2>{central_cache} <span>&middot; {rock_store}</span></h2>";
    $h[]="<span class='ak-pill ak-violet rck-up'>{rock_multi_cpu}</span>";
    $h[]=$on?"<span class='ak-pill ak-ok'><span class='ak-dot'></span>{active2}</span>"
            :"<span class='ak-pill ak-off'><span class='ak-dot'></span>{inactive2}</span>";
    $h[]="</div><p>{rock_marketing_explain}</p>";
    if($SquidRockPath!=""){$h[]="<div class='rck-path'><i class='far fa-folder'></i> ".htmlspecialchars($SquidRockPath)."</div>";}
    $h[]="</div></div>";
    $h[]="<a class='rck-switch-box' href=\"javascript:blur();\" OnClick=\"$configure\">"
        ."<span class='rck-switch-txt'><strong>{rock_engine}</strong><small>".($on?"{enabled}":"{disabled}")."</small></span>"
        ."<span class='ak-sw".($on?" on":"")."' aria-hidden='true'></span></a>";
    $h[]="</div>";

    $sizeKB=$SquidRockSize*1024;
    $sizeRaw=$max_rock_kb>0?$sizeKB*100/$max_rock_kb:0;
    $sizePct=round(min(100,$sizeRaw));
    $h[]="<div class='rck-params'>";
    $h[]=rock_param("{available_memory}",FormatBytes($shm_kb),"","<div class='rck-bar'><div class='rck-fill rck-fill-violet' style='width:70%'></div></div>","{rock_shm_note}");
    $h[]=rock_param("{cache_size}",$sizes[$SquidRockSize]??($SquidRockSize>0?$SquidRockSize."M":"-"),
        $sizeRaw>100?"<span class='ak-pill ak-warn'>{rock_over_limit}</span>":"",
        "<div class='rck-bar'><div class='rck-fill".($sizePct>90?" rck-fill-amber":"")."' style='width:$sizePct%'></div></div>",
        "{max_allowed_size_of_rock_store}: ".FormatBytes($max_rock_kb));
    $h[]=rock_param("{maximum_object_size}","{$SquidRockMaxSize} KB","","","{rock_objects_note}");
    $h[]=rock_param("{swap_timeout}",$swaptimeout[$SquidRockSwapTimeOut]??"{disabled}","","","{max_swap_rate}: ".($max_swap_rate[$SquidRockMaxSwap]??"{default}"));
    $h[]="</div>";

    $h[]="<div class='rck-pills'><div class='rck-pill-list'>";
    $h[]="<span class='rck-pill'><i class='fas fa-microchip'></i> {rock_pill_workers}: <strong>$workers</strong></span>";
    $h[]="<span class='rck-pill'><i class='fas fa-clone'></i> {rock_pill_single_copy}</span>";
    $h[]="<span class='rck-pill'><i class='fas fa-feather'></i> {rock_pill_objects} <strong>{$SquidRockMaxSize} KB</strong></span>";
    $h[]="</div><a class='rck-link' href=\"javascript:blur();\" OnClick=\"$configure\">{configure_rock_store} <i class='fas fa-arrow-right'></i></a></div>";
    $h[]="</section>";

    $topbuttons=array();
    $topbuttons[]=array($configure,ico_params,"{configure_rock_store}");
    $topbuttons[]=array("LoadAjax('table-loader-cache-rock','$page?table=yes');",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{central_cache} &middot; {rock_store}";
    $TINY["ICO"]=ico_rocket;
    $TINY["EXPL"]="{rock_header_tagline}";
    $TINY["URL"]="rock-cache";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $h[]="<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";

    echo rock_style().$tpl->_ENGINE_parse_body(@implode("",$h));
    return true;
}

// rock_devshm_kb: size of the shared-memory tmpfs, in KB (read here instead of
// the former framework round-trip squid.php?devshmsize).
function rock_devshm_kb():float{
    foreach (array("/run/shm","/dev/shm") as $d){
        if(!is_dir($d)){continue;}
        $t=@disk_total_space($d);
        if($t!==false && $t>0){return $t/1024;}
    }
    return 0;
}

function rock_sizes(int $maxGB):array{
    $a=array(320=>"320M",512=>"512M",640=>"640M",2000=>"2GB",3200=>"3.2GB",5120=>"5.1GB",
        6400=>"6.4GB",32000=>"32GB",51200=>"51GB",64000=>"64GB",128000=>"128GB");
    $a[$maxGB]="{$maxGB}GB";
    ksort($a);
    return $a;
}

// rock_workers: Squid SMP workers (SquidSMPConfig, one entry per CPU worker).
function rock_workers():int{
    $cfg=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    if(!is_array($cfg)){return 1;}
    $n=0;
    foreach ($cfg as $v){if(intval($v)>0){$n++;}}
    return max(1,$n);
}

function rock_ms(float $ms):string{
    if($ms>=1000){return round($ms/1000,2)." s";}
    if($ms<10){return round($ms,1)." ms";}
    return round($ms)." ms";
}

function rock_kpi(string $title,string $icon,string $value,string $badge,string $sub,string $footLabel,string $footValue,string $tone):string{
    return "<div class='ak-card rck-kpi'>"
        ."<div class='rck-kpi-head'><span>$title</span><span class='rck-kpi-ico rck-tone-$tone'><i class='$icon'></i></span></div>"
        ."<div class='rck-kpi-val'>$value $badge</div>"
        ."<p>$sub</p>"
        ."<div class='rck-kpi-foot'><span>$footLabel</span><strong>".htmlspecialchars($footValue,ENT_QUOTES)."</strong></div>"
        ."</div>";
}

function rock_param(string $label,string $value,string $badge,string $bar,string $note):string{
    return "<div class='rck-param'><span class='rck-param-lbl'>$label</span>"
        ."<div class='rck-param-val'><span>$value</span>$badge</div>$bar"
        .($note!=""?"<p>$note</p>":"")."</div>";
}

// rock_style: design stitch_proxy_cache_rock. Accents follow the brand palette;
// class.brand.inc may be missing on an older appliance.
function rock_style():string{
    if(isset($GLOBALS["RCK_STYLE_DONE"])){return "";}
    $GLOBALS["RCK_STYLE_DONE"]=true;
    $accent="#10b981";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$dark=$a["accent_dark"];
    }
    return "<style>
.rck-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px;margin-top:10px}
.rck-kpi{padding:18px;display:flex;flex-direction:column}
.rck-kpi-wide{grid-column:span 3}
.rck-kpi-head{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#475569}
.rck-kpi-ico{width:30px;height:30px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;font-size:14px;flex:0 0 auto}
.rck-tone-violet{background:#e6eeed;color:#005447}
.rck-tone-green{background:#ecfdf5;color:#059669}
.rck-tone-blue{background:#eff6ff;color:#2563eb}
.rck-tone-amber{background:#fffbeb;color:#d97706}
.rck-kpi-val{font-size:28px;font-weight:800;color:#0f172a;letter-spacing:-.02em;margin-top:8px;font-variant-numeric:tabular-nums;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.rck-kpi p{font-size:12px;color:#64748b;margin:4px 0 0 0;flex:1}
.rck-kpi-foot{display:flex;justify-content:space-between;gap:8px;border-top:1px solid #f1f5f9;margin-top:12px;padding-top:10px;font-size:12px;color:#64748b}
.rck-kpi-foot strong{color:#0f172a;font-variant-numeric:tabular-nums}
.rck-muted{color:#94a3b8}
.rck-up{text-transform:uppercase}
.rck-spot{margin-top:20px}
.rck-spot-top{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap;padding-bottom:20px;border-bottom:1px solid #f1f5f9}
.rck-spot-id{display:flex;gap:16px;align-items:flex-start;flex:1 1 520px;min-width:0}
.rck-logo{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#005447,#005447);color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;flex:0 0 auto;box-shadow:0 6px 16px rgba(0,84,71,.35)}
.rck-title-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.rck-title-row h2{font-size:20px;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.01em}
.rck-title-row h2 span{font-weight:600;color:#475569}
.rck-spot-txt p{font-size:13px;line-height:1.6;color:#475569;margin:8px 0 0 0;max-width:820px}
.rck-path{font-size:12px;color:#64748b;margin-top:8px;display:flex;align-items:center;gap:6px}
.rck-switch-box{display:flex;align-items:center;gap:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:12px 16px;text-decoration:none!important;cursor:pointer}
.rck-switch-box:hover{border-color:#cbd5e1}
.rck-switch-txt{display:flex;flex-direction:column;text-align:right}
.rck-switch-txt strong{font-size:13px;color:#0f172a}
.rck-switch-txt small{font-size:11px;color:#64748b}
.rck-params{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-top:20px}
.rck-param{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;min-width:0}
.rck-param-lbl{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b}
.rck-param-val{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:6px;font-size:20px;font-weight:700;color:#0f172a;font-variant-numeric:tabular-nums}
.rck-param p{font-size:11px;color:#64748b;margin:6px 0 0 0}
.rck-bar{width:100%;height:6px;border-radius:999px;background:#e2e8f0;margin-top:10px;overflow:hidden}
.rck-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,$accent,$dark)}
.rck-fill-violet{background:linear-gradient(90deg,#005447,#005447)}
.rck-fill-amber{background:linear-gradient(90deg,#f59e0b,#d97706)}
.rck-pills{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px;padding-top:16px;border-top:1px solid #f1f5f9}
.rck-pill-list{display:flex;gap:18px;flex-wrap:wrap}
.rck-pill{display:inline-flex;align-items:center;gap:7px;font-size:12px;color:#475569}
.rck-pill svg{color:#005447}
.rck-pill strong{color:#0f172a}
.rck-link{font-size:13px;font-weight:700;color:#005447!important;text-decoration:none!important;display:inline-flex;align-items:center;gap:6px;cursor:pointer}
.rck-link:hover{color:#005447!important}
@media(max-width:820px){.rck-kpi-wide{grid-column:auto}.rck-switch-txt{text-align:left}}
</style>";
}

function form_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$DisableAnyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));
	
	if($DisableAnyCache==1){
		$tpl->FATAL_ERROR_SHOW_128("{DisableAnyCache_enabled_warning}");
		return false;
	}
	
	
	
	
	$swaptimeout[0]="{disabled}";
	$swaptimeout[300]=300;
	$swaptimeout[500]=500;
	$swaptimeout[1000]=1000;
	
	$max_swap_rate[0]="{default}";
	$max_swap_rate[100]="{lower_disk_speed}";
	$max_swap_rate[200]="{normal_disk_speed}";
	$max_swap_rate[300]="{high_disk_speed}";
	
	$DEV_SHM=rock_devshm_kb()*0.7;
	$max_rock_size_kb=$DEV_SHM*32;
	$max_rock_size=($DEV_SHM*32);
	$max_rock_size_MB=round($max_rock_size/1024);
	$max_rock_size_GB=round($max_rock_size_MB/1024);
	$DEV_SHM=FormatBytes($DEV_SHM);
	$max_rock_size=FormatBytes($max_rock_size);
	
	
	$array[320]="320M";
	$array[512]="512M";
	$array[640]="640M";
	$array[2000]="2GB";
	$array[3200]="3.2GB";
	$array[5120]="5.1GB";
	$array[6400]="6.4GB";
	$array[32000]="32GB";
	$array[51200]="51GB";
	$array[64000]="64GB";
	$array[128000]="128GB";
	$array[$max_rock_size_GB]="{$max_rock_size_GB}GB";

	ksort($array);
	
	//cache_dir rock /hdd1 40000 min-size=0 max-size=65536 swap-timeout=300 max-swap-rate=200/sec slot-size=32000
	
	$SquidRockPath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockPath"));
	$EnableRockCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRockCache"));
	$SquidRockSwapTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockSwapTimeOut"));
	$SquidRockMaxSwap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockMaxSwap"));
	$SquidRockSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockSize"));
	$SquidRockMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockMaxSize"));
	
	if($SquidRockMaxSize==0){$SquidRockMaxSize=128;}
	

	

	$form[]=$tpl->field_checkbox("EnableRockCache","{enable_feature}",$EnableRockCache,true);
	$form[]=$tpl->field_browse_directory("SquidRockPath", "{directory}", $SquidRockPath,null,true);
	$form[]=$tpl->field_array_hash($array, "SquidRockSize", "{cache_size}", $SquidRockSize,false,null);
	$form[]=$tpl->field_numeric("SquidRockMaxSize","{maximum_object_size} (KB)",$SquidRockMaxSize,"{cache_dir_max_size_text}");
	$form[]=$tpl->field_array_hash($swaptimeout, "SquidRockSwapTimeOut", "{swap_timeout}", $SquidRockSwapTimeOut,false,"{swap_timeout_explain}");
	$form[]=$tpl->field_array_hash($max_swap_rate, "SquidRockMaxSwap", "{max_swap_rate}", $SquidRockMaxSwap,false,"{max_swap_rate_explain}");
	

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.rock.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.rock.progress.txt";
	$ARRAY["CMD"]="/proxy/caches/rock/apply"; // articarest squid/proxy_caches_rock.go
	$ARRAY["TITLE"]="{APP_SQUID}::{rock_store}";

	$prgress=base64_encode(serialize($ARRAY));
	$jsafter="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-rock-restart')";
	
	echo $tpl->form_outside("{squid_rock}", @implode("\n", $form),null,"{apply}",
        "LoadAjax('table-loader-cache-rock','$page?table=yes');dialogInstance1.close();$jsafter","AsSquidAdministrator");

    return true;
}
function Save(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl->CLEAN_POST();
	foreach ($_POST as $key=>$val){
		$sock->SET_INFO($key, $val);
	}
	
	
	
	
}
