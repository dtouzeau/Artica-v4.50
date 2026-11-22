<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.meminfo.inc");

if(isset($_GET["zram-js"])){zram_js();exit;}
if(isset($_GET["zram-popup"])){zram_popup();exit;}
if(isset($_POST["EnableZram"])){zram_save();exit;}
if(isset($_POST["overcommit_memory"])){table_save();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_GET["form-js"])){form_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["history"])){mem_history();exit;}
if(isset($_GET["split"])){mem_split();exit;}
if(isset($_GET["levers"])){mem_levers();exit;}
if(isset($_GET["drop-now"])){mem_drop_now();exit;}
if(isset($_GET["MemoryCacheCleaning-js"])){MemoryCacheCleaning_js();exit;}
if(isset($_GET["MemoryCacheCleaning-popup"])){MemoryCacheCleaning_popup();exit;}
if(isset($_POST["MemoryCacheCleaning"])){MemoryCacheCleaning_save();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header(
        "{memory_info}","fad fa-memory",
        "{memory_info_text}","$page?tabs=yes","system-memory","progress-system-memory",false,"table-system-memory");
    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);
}

/**
 * Les enregistrements modifient le noyau (sysctl, zRam, purge) : le bouton
 * masque ne suffit pas, chaque gestionnaire verifie le privilege. Ils
 * repondaient a toute session ouverte.
 */
function mem_denied():bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");
    return true;
}

function zram_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->js_dialog1("{zram_compressed_swap}","$page?zram-popup=yes",650);
}

/** Nombre de processus, pour le compteur de l'onglet. */
function mem_process_count():int{
    $n=0;
    $d=@scandir("/proc");
    if(!is_array($d)){return 0;}
    foreach($d as $e){ if(ctype_digit($e)){$n++;} }
    return $n;
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $n=mem_process_count();
    $cnt=$n>0?"<span class='ak-count memx-cnt'>$n</span>":"";
    $array["{status}"]="$page?table=yes";
    $array["<span class='memx-cap'>{processes}</span>$cnt"]="fw.system.memory.processes.php";
    echo "<style>.memx-cap{display:inline-block}.memx-cap::first-letter{text-transform:uppercase}.memx-cnt{margin-left:6px;font-weight:700}</style>";
    echo $tpl->tabs_default($array);
    return true;
}
function form_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->js_dialog1("{parameters}","$page?form-popup=yes",650);
}
function MemoryCacheCleaning_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->js_dialog1("{clean_cache}","$page?MemoryCacheCleaning-popup=yes",650);
}

/**
 * Seuil de purge automatique. Le daemon (VMDirtyRatio.Start) n'arme la
 * surveillance qu'entre 20 et 95 % : la liste proposait 5 a 94 %, un seuil
 * sous 20 % s'affichait comme actif sans jamais rien declencher.
 */
function MemoryCacheCleaning_popup():bool{
    $tpl=new template_admin();
    $MemoryCacheCleaning=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MemoryCacheCleaning"));
    if($MemoryCacheCleaning<20 OR $MemoryCacheCleaning>95){
        $MemoryCacheCleaning=0;
    }
    $f[0]="{disabled}";
    for ($i=20;$i<=95;$i++){
        $f[$i]="$i%";
    }
    $form[]=$tpl->field_array_hash($f,"MemoryCacheCleaning","{max_value}",$MemoryCacheCleaning);
    $js[]="dialogInstance1.close();";
    $js[]="if(typeof MemxLevers==='function'){MemxLevers();}";
    echo $tpl->form_outside("",$form,"{sys_drop_caches}","{apply}",implode(";",$js),"AsSystemAdministrator");
    return true;
}
function MemoryCacheCleaning_save():bool{
    if(mem_denied()){return false;}
    $v=intval($_POST["MemoryCacheCleaning"]);
    if($v<20 || $v>95){$v=0;}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MemoryCacheCleaning",$v);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/memory/dirtyratio");
    return admin_tracks("Save system memory cache cleaning after $v%");
}

function form_popup():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/sysctl/json"));
    $tpl=new template_admin();
    if(!is_object($json) || empty($json->Status)){
        echo $tpl->div_error(is_object($json)&&isset($json->Error)?$json->Error:"sysctl API error");
        return false;
    }
    $Overcommiting_Memory[0]="{Overcommiting_Memory_0}";
    $Overcommiting_Memory[1]="{Overcommiting_Memory_1}";
    if($json->kernel->isSwapByTable) {
        $Overcommiting_Memory[2] = "{Overcommiting_Memory_2}";
    }
    for($i=50;$i<101;$i++){
        $overcommit_ratioH[$i]="$i%";
    }
    $pageSize=intval($json->kernel->page_size ?? 0);
    if($pageSize<=0){$pageSize=4096;}
    $form[]=$tpl->field_section("{Overcommiting_Memory_behavior}","{vm.overcommit_memory}");
    $form[]=$tpl->field_array_hash($Overcommiting_Memory,"overcommit_memory","{Overcommiting_Memory_behavior}",$json->kernel->overcommit_memory);

    $form[]=$tpl->field_section("{ratio}","{vm.overcommit_ratio}");
    $form[]=$tpl->field_array_hash($overcommit_ratioH,"overcommit_ratio","{ratio}",$json->kernel->overcommit_ratio);

    $form[]=$tpl->field_section("{kernel_shmmax}","{kernel_shmmax_explain}");
    $form[]=$tpl->field_numeric("kernel_shmmax","{kernel_shmmax} (bytes)",$json->kernel->shmmax);
    // kernel.shmall compte des PAGES : le libelle « (bytes) » faisait saisir
    // des octets, ecrits tels quels par le daemon comme un nombre de pages.
    $form[]=$tpl->field_section("{kernel_shmall}","{kernel_shmall_explain}");
    $form[]=$tpl->field_numeric("kernel_shmall","{kernel_shmall} ({memst_pages} &times; ".intval($pageSize/1024)." KB)",$json->kernel->shmall);

    $js[]="dialogInstance1.close();";
    $js[]="if(typeof MemxLevers==='function'){MemxLevers();}";
    echo $tpl->form_outside("",$form,"","{apply}",implode(";",$js),"AsSystemAdministrator");
    return true;
}
function table_save():bool{
    if(mem_denied()){return false;}
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    return admin_tracks_post("Saving system parameters");
}

function zram_save(){
    if(mem_denied()){return false;}
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/zmem/reconfigure");
    return admin_tracks_post("Saving zRam settings");
}
function zram_popup():bool{
    $tpl=new template_admin();
    $EnableZram=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZram"));
    $ZramSizePercent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZramSizePercent"));
    if($ZramSizePercent==0){$ZramSizePercent=50;}
    $percOptions=array();
    for($i=10;$i<=80;$i+=5){
        $percOptions[$i]="$i%";
    }
    $form[]=$tpl->field_checkbox("EnableZram","{enable_feature}",$EnableZram);
    $form[]=$tpl->field_array_hash($percOptions,"ZramSizePercent","{zram_size_percent}",$ZramSizePercent);
    $js_after[]="dialogInstance1.close();";
    $js_after[]="if(typeof MemxLevers==='function'){MemxLevers();}";
    $js_after[]="if(typeof MemxSplit==='function'){MemxSplit();}";
    $html[]=$tpl->form_outside("",$form,"{zram_explain}","{apply}",implode(";",$js_after),"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

/** Purge immediate du cache (articarest /system/memory/drop-caches), reponse JS. */
function mem_drop_now():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){return $tpl->js_no_privileges();}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/memory/drop-caches"),true);
    if(!is_array($json) || empty($json["Status"])){
        $err=is_array($json)&&isset($json["Error"])?(string)$json["Error"]:"drop-caches API error";
        return $tpl->js_error($err);
    }
    admin_tracks("Dropped the memory caches (".intval($json["FreedMB"])." MB freed)");
    $msg=$tpl->javascript_parse_text("{memst_purge_done}");
    $msg=str_replace("%s",intval($json["FreedMB"])." MB",$msg);
    echo "if(typeof MemxLevers==='function'){MemxLevers();}\n";
    echo "if(typeof MemxSplit==='function'){MemxSplit();}\n";
    echo "if(typeof MemxHistory==='function'){MemxHistory();}\n";
    echo "swal({title:".json_encode($msg).",type:'success',timer:2500});\n";
    return true;
}

// ---------------------------------------------------------------- donnees

function mem_sysctl():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/sysctl/json"),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["kernel"]) || !is_array($json["kernel"])){return array();}
    return $json["kernel"];
}
function mem_zram():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/zmem/status"),true);
    if(!is_array($json) || empty($json["success"]) || !isset($json["data"]) || !is_array($json["data"])){return array();}
    return $json["data"];
}
/** Pression memoire (articarest /system/memory/pressure) ; vide sur un ancien daemon. */
function mem_pressure():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/memory/pressure"),true);
    if(!is_array($json) || empty($json["Status"])){return array();}
    return $json;
}
/** kB -> "3.37 GB" / "758 MB". */
function mem_fmt_kb($kb):string{
    $kb=floatval($kb);
    if($kb>=1048576){return sprintf("%.2f GB",$kb/1048576);}
    if($kb>=1024){return sprintf("%.0f MB",$kb/1024);}
    return sprintf("%.0f KB",$kb);
}

// ---------------------------------------------------------------- style

function mem_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

function mem_css():string{
    $ac=mem_accents();
    $A=$ac["accent"]; $D=$ac["accent_dark"]; $S=$ac["accent_soft"]; $L=$ac["accent_line"];
    // Seules les regles propres a la page : cartes, pastilles, boutons, bandeaux, jauges viennent du kit ak-.
    $f[]="<style>";
    $f[]=".memx{padding:6px 2px}";
    $f[]=".memx-top{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(0,1fr);gap:20px;margin-bottom:28px}";
    $f[]=".memx-card,.memx-lv{padding:20px 22px}";
    $f[]=".memx-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding-bottom:14px;border-bottom:1px solid var(--ak-border2);margin-bottom:14px}";
    $f[]=".memx-t{display:flex;align-items:center;gap:10px;font-size:16px;font-weight:700}";
    $f[]=".memx-live{display:inline-block;width:10px;height:10px;border-radius:50%;background:$A;box-shadow:0 0 0 4px $S}";
    $f[]=".memx-now{font-size:14px;color:var(--ak-text2)}.memx-now strong{color:var(--ak-text)}";
    $f[]=".memx-canvas{position:relative;height:220px}";
    $f[]=".memx-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:16px;padding-top:16px;border-top:1px solid var(--ak-border2)}";
    $f[]=".memx-stat{border:1px solid var(--ak-border);border-radius:12px;padding:10px 12px;text-align:center;background:var(--ak-surface)}";
    $f[]=".memx-stat.memx-good{border-color:$L;background:$S}";
    $f[]=".memx-stat.memx-good .ak-fig-l,.memx-stat.memx-good .memx-stat-v{color:$D}";
    $f[]=".memx-stat-v{font-size:18px;font-weight:800;margin-top:2px}";
    // Tons propres a la page (pastilles et icones) : vert émeraude (#005447) du zRam, sarcelle de l'overcommit.
    $f[]=".memx-violet{--ak-tfg:#005447;--ak-tbg:#f2f6f6}.memx-teal{--ak-tfg:#0d9488;--ak-tbg:#f0fdfa}";
    $f[]=".memx-gauges{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}";
    $f[]=".memx-gauge{border:1px solid var(--ak-border2);border-radius:14px;padding:14px;text-align:center;background:var(--ak-surface)}";
    $f[]=".memx-gauge-t{font-size:14px;font-weight:600;color:var(--ak-text2);margin-bottom:8px}";
    $f[]=".memx-ring{position:relative;width:150px;height:150px;margin:0 auto}";
    $f[]=".memx-ring svg{display:block}";
    $f[]=".memx-ring-c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}";
    $f[]=".memx-ring-v{font-size:24px;font-weight:800}.memx-ring-s{font-size:12px;color:var(--ak-muted)}";
    $f[]=".memx-leg{margin-top:12px;text-align:left;font-size:13px;color:var(--ak-label)}";
    $f[]=".memx-leg div{display:flex;justify-content:space-between;gap:8px;padding:2px 0}";
    $f[]=".memx-leg i{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:7px;vertical-align:middle}";
    $f[]=".memx-leg strong{color:var(--ak-text);white-space:nowrap}";
    $f[]=".memx-alert{margin-top:14px;font-size:13px}.memx-alert .ak-mono{font-weight:700}";
    $f[]=".memx-sec{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}";
    $f[]=".memx-sec-t{font-size:19px;font-weight:700}.memx-sec-e{font-size:13px;color:var(--ak-muted);margin-top:2px}";
    $f[]=".memx-sec-n{font-size:13px;color:var(--ak-muted);font-weight:600}";
    $f[]=".memx-lv{display:flex;flex-direction:column;justify-content:space-between;gap:14px}";
    $f[]=".memx-lv-top{display:flex;gap:14px;align-items:flex-start}";
    $f[]=".memx-lv-h{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap}";
    $f[]=".memx-lv-t{font-size:16px;font-weight:700}";
    $f[]=".memx-lv-sub{margin-top:4px}";
    $f[]=".memx-lv-e{font-size:13px;color:var(--ak-muted);line-height:1.5;margin-top:8px}";
    $f[]=".memx-lv-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding-top:12px;border-top:1px solid var(--ak-border2);font-size:13px;color:var(--ak-label)}";
    $f[]=".memx-lv-foot strong{color:var(--ak-text)}";
    $f[]=".memx .ak-bar{margin-top:6px}";
    $f[]=".memx-row{display:flex;justify-content:space-between;gap:10px;font-size:12px;color:var(--ak-muted)}";
    $f[]=".memx-empty{text-align:center;color:#94a3b8;padding:40px 0}";
    $f[]="@media (max-width:1250px){.memx-top{grid-template-columns:1fr}}";
    $f[]="@media (max-width:900px){.memx-stats{grid-template-columns:1fr}}";
    $f[]="@media (max-width:560px){.memx-gauges{grid-template-columns:1fr}}";
    $f[]="</style>";
    return @implode("\n",$f);
}

// ---------------------------------------------------------------- vue Statut

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $TINY_ARRAY["TITLE"]="{memory_info}";
    $TINY_ARRAY["ICO"]="fad fa-memory";
    $TINY_ARRAY["EXPL"]="{memory_info_text}";
    $TINY_ARRAY["URL"]="system-memory";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html=array();
    $html[]="<div class='memx ak'>";
    $html[]="<div class='memx-top'><div id='memx-history'></div><div id='memx-split'></div></div>";
    $html[]="<div id='memx-levers'></div>";
    $html[]="</div>";

    $js=array();
    $js[]="<script>";
    $js[]="NoSpinner();";
    $js[]="function MemxHistory(){ LoadAjaxSilent('memx-history','$page?history=yes'); }";
    $js[]="function MemxSplit(){ LoadAjaxSilent('memx-split','$page?split=yes'); }";
    $js[]="function MemxLevers(){ LoadAjaxSilent('memx-levers','$page?levers=yes'); }";
    $js[]="MemxHistory();MemxSplit();MemxLevers();";
    // Rafraichissement : s'arrete seul quand on quitte la page, saute un tour si une fenetre est ouverte.
    $js[]="if(window.MemxTimer){clearInterval(window.MemxTimer);}";
    $js[]="window.MemxTick=0;";
    $js[]="window.MemxTimer=setInterval(function(){";
    $js[]="  if(!document.getElementById('memx-split')){clearInterval(window.MemxTimer);window.MemxTimer=null;return;}";
    $js[]="  if(document.hidden || document.querySelector('.modal.in')){return;}";
    $js[]="  window.MemxTick++; MemxSplit(); if(window.MemxTick%2==0){MemxHistory();}";
    $js[]="},30000);";
    $js[]=$jstiny;
    $js[]="</script>";
    echo mem_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).@implode("\n",$js);
}

/** Carte 1 : utilisation de la RAM sur la derniere heure. */
function mem_history(){
    $tpl=new template_admin();
    $ac=mem_accents();
    $mem=api_meminfo();
    $totalKB=intval($mem["MAIN"]["memtotal"]);
    $availKB=intval($mem["MAIN"]["memavailable"]);
    $usedKB=max(0,$totalKB-$availKB);
    $curPct=$totalKB>0?round($usedKB*100/$totalKB,1):0;

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/sysmonitor/chart?hours=1"),true);
    $labels=array(); $usedMB=array(); $pct=array();
    if(is_array($json) && !empty($json["success"]) && isset($json["data"]) && is_array($json["data"])){
        $d=$json["data"];
        $labels=(isset($d["labels"]) && is_array($d["labels"]))?$d["labels"]:array();
        $usedMB=(isset($d["mem_used_mb"]) && is_array($d["mem_used_mb"]))?$d["mem_used_mb"]:array();
        $pct=(isset($d["mem"]) && is_array($d["mem"]))?$d["mem"]:array();
    }
    $vals=array();
    foreach($pct as $v){ if($v!==null){$vals[]=floatval($v);} }
    $avg=count($vals)>0?round(array_sum($vals)/count($vals),1):$curPct;
    $peak=count($vals)>0?round(max($vals),1):$curPct;
    $freePct=$totalKB>0?round($availKB*100/$totalKB,1):0;

    $h=array();
    $h[]="<div class='ak-card memx-card'>";
    $h[]="<div class='memx-head'>";
    $h[]="<span class='memx-t'><span class='memx-live'></span>{memst_ram_usage}</span>";
    $h[]="<span class='ak-chip'>{memst_interval}: 1 h</span>";
    $h[]="<span class='memx-now'>{current}: <strong>$curPct% (".mem_fmt_kb($usedKB).")</strong></span>";
    $h[]="<span class='ak-help'>{memst_ceiling}: ".mem_fmt_kb($totalKB)."</span>";
    $h[]="</div>";
    if(count($labels)==0){
        $h[]="<div class='memx-empty'><i class='fa-solid fa-chart-line'></i><div>{no_history_data}</div></div>";
    }else{
        $tt=time();
        $h[]="<div class='memx-canvas'><canvas id='memx-hist-$tt'></canvas></div>";
    }
    $h[]="<div class='memx-stats'>";
    $h[]="<div class='memx-stat'><div class='ak-fig-l'>{memst_avg_1h}</div><div class='memx-stat-v'>$avg%</div></div>";
    $h[]="<div class='memx-stat'><div class='ak-fig-l'>{memst_peak}</div><div class='memx-stat-v'>$peak%</div></div>";
    $h[]="<div class='memx-stat memx-good'><div class='ak-fig-l'>{memst_free_reserve}</div><div class='memx-stat-v'>".mem_fmt_kb($availKB)." ($freePct%)</div></div>";
    $h[]="</div></div>";

    $script="";
    if(count($labels)>0){
        $gb=array();
        foreach($usedMB as $v){ $gb[]=($v===null)?"null":sprintf('%.2f',floatval($v)/1024); }
        $lab=array();
        foreach($labels as $x){ $lab[]=json_encode((string)$x); }
        $ceil=sprintf('%.2f',$totalKB/1048576);
        $usedLbl=json_encode($tpl->javascript_parse_text("{used}"));
        $A=$ac["accent"]; $D=$ac["accent_dark"];
        $script="<script>(function(){var el=document.getElementById('memx-hist-$tt');if(!el||typeof Chart==='undefined'){return;}"
            ."window.memxCharts=window.memxCharts||{};if(window.memxCharts.h){try{window.memxCharts.h.destroy();}catch(e){}}"
            ."var ctx=el.getContext('2d');var g=ctx.createLinearGradient(0,0,0,220);g.addColorStop(0,'".str_replace("'","",$ac["accent_line"])."');g.addColorStop(1,'rgba(255,255,255,0)');"
            ."window.memxCharts.h=new Chart(ctx,{type:'line',data:{labels:[".implode(",",$lab)."],datasets:[{label:$usedLbl,data:[".implode(",",$gb)."],borderColor:'$D',backgroundColor:g,borderWidth:2.5,fill:true,tension:0.4,pointRadius:0,spanGaps:true}]},"
            ."options:{responsive:true,maintainAspectRatio:false,interaction:{intersect:false,mode:'index'},plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return c.parsed.y==null?'-':c.parsed.y.toFixed(2)+' GB';}}}},"
            ."scales:{x:{grid:{display:false},border:{display:false},ticks:{color:'#94a3b8',maxRotation:0,autoSkip:true,maxTicksLimit:7,font:{family:'monospace'}}},"
            ."y:{min:0,suggestedMax:$ceil,grid:{color:'#f1f5f9'},border:{display:false},ticks:{color:'#94a3b8',font:{family:'monospace'},callback:function(v){return v+' GB';}}}}}});})();</script>";
    }
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h)).$script;
}

/** Anneau SVG : pourcentage, couleur, texte central. */
function mem_ring($pct,$color,$big,$small):string{
    $pct=max(0,min(100,floatval($pct)));
    $r=62; $c=2*M_PI*$r;
    $dash=sprintf("%.1f %.1f",$c*$pct/100,$c);
    $h ="<div class='memx-ring'><svg width='150' height='150' viewBox='0 0 150 150'>";
    $h.="<circle cx='75' cy='75' r='$r' fill='none' stroke='#e2e8f0' stroke-width='14'/>";
    $h.="<circle cx='75' cy='75' r='$r' fill='none' stroke='$color' stroke-width='14' stroke-linecap='round' stroke-dasharray='$dash' transform='rotate(-90 75 75)'/>";
    $h.="</svg><div class='memx-ring-c'><span class='memx-ring-v'>$big</span><span class='memx-ring-s'>$small</span></div></div>";
    return $h;
}

/** Bandeau de pression memoire (PSI + trafic swap), verdict du daemon. */
function mem_pressure_banner($p):string{
    if(count($p)==0){return "";}
    $v=(string)($p["verdict"] ?? "ok");
    $psi=$p["psi"] ?? array();
    $full=sprintf("%.2f",floatval($psi["full_avg60"] ?? 0));
    $some=sprintf("%.2f",floatval($psi["some_avg60"] ?? 0));
    $swin=floatval($p["swap"]["in_per_sec"] ?? 0);
    $right=!empty($psi["available"])?"<span class='ak-mono ak-nowrap'>PSI full $full%</span>":"";
    if($v=="thrashing"){
        return "<div class='ak-banner ak-err memx-alert'><i class='fas fa-triangle-exclamation'></i><span class='ak-banner-m'>{memst_thrashing}</span>$right</div>";
    }
    if($v=="pressure"){
        $txt=!empty($psi["available"])?"PSI some $some%":sprintf("%.0f {memst_pages}/s",$swin);
        return "<div class='ak-banner ak-warn memx-alert'><i class='fas fa-circle-exclamation'></i><span class='ak-banner-m'>{memst_pressure}</span><span class='ak-mono ak-nowrap'>$txt</span></div>";
    }
    return "<div class='ak-banner ak-ok memx-alert'><i class='fas fa-circle-check'></i><span class='ak-banner-m'>{memst_no_stall}</span>$right</div>";
}

/** Carte 2 : RAM et swap/zRam en anneaux, plus le bandeau de pression. */
function mem_split(){
    $tpl=new template_admin();
    $ac=mem_accents();
    $mem=api_meminfo();
    if(!$mem["ok"]){
        echo $tpl->_ENGINE_parse_body("<div class='ak-card memx-card'>".$tpl->div_error(htmlspecialchars((string)$mem["error"]))."</div>");
        return;
    }
    $M=$mem["MAIN"];
    $total=intval($M["memtotal"]); $avail=intval($M["memavailable"]);
    $used=max(0,$total-$avail);
    $ramPct=$total>0?round($used*100/$total,1):0;
    $swTotal=intval($M["swaptotal"]); $swFree=intval($M["swapfree"]);
    $swUsed=max(0,$swTotal-$swFree);
    $swPct=$swTotal>0?round($swUsed*100/$swTotal,1):0;
    $z=mem_zram();
    $p=mem_pressure();

    if($p && ($p["verdict"] ?? "")=="thrashing"){
        $badge="<span class='ak-pill ak-err'>{memst_state_thrashing}</span>";
    }elseif($p && ($p["verdict"] ?? "")=="pressure"){
        $badge="<span class='ak-pill ak-warn'>{memst_state_pressure}</span>";
    }elseif($ramPct>=90){
        $badge="<span class='ak-pill ak-warn'>{memst_state_high}</span>";
    }else{
        $badge="<span class='ak-pill ak-ok'>{memst_state_ok}</span>";
    }

    $h=array();
    $h[]="<div class='ak-card memx-card'>";
    $h[]="<div class='memx-head'><span class='memx-t'>{memst_split_title}</span>$badge</div>";
    $h[]="<div class='memx-gauges'>";
    $h[]="<div class='memx-gauge'><div class='memx-gauge-t'>{memst_ram}</div>";
    $h[]=mem_ring($ramPct,$ac["accent_dark"],"$ramPct%",mem_fmt_kb($used));
    $h[]="<div class='memx-leg'><div><span><i style='background:{$ac["accent_dark"]}'></i>{memst_used}</span><strong>".mem_fmt_kb($used)."</strong></div>";
    $h[]="<div><span><i style='background:#cbd5e1'></i>{memst_available}</span><strong>".mem_fmt_kb($avail)."</strong></div></div></div>";

    $zOn=!empty($z["enabled"]);
    $h[]="<div class='memx-gauge'><div class='memx-gauge-t'>".($zOn?"{memst_swap_zram}":"{memst_swap}")."</div>";
    if($swTotal<=0){
        $h[]=mem_ring(0,"#005447","&mdash;","{memst_no_swap}");
        $h[]="<div class='memx-leg'><div><span><i style='background:#cbd5e1'></i>{memst_swap}</span><strong>0</strong></div></div></div>";
    }else{
        $sub=$zOn?"{ratio} ".sprintf("%.1f",floatval($z["compression_ratio"] ?? 0)).":1":mem_fmt_kb($swTotal);
        $h[]=mem_ring($swPct,"#005447","$swPct%",$sub);
        $h[]="<div class='memx-leg'><div><span><i style='background:#005447'></i>{memst_swap_used}</span><strong>".mem_fmt_kb($swUsed)."</strong></div>";
        if($zOn){
            $gain=max(0,floatval($z["orig_data_mb"] ?? 0)-floatval($z["compr_data_mb"] ?? 0));
            $h[]="<div><span><i style='background:{$ac["accent"]}'></i>{memst_zram_gain}</span><strong>+".mem_fmt_kb($gain*1024)."</strong></div>";
        }else{
            $h[]="<div><span><i style='background:#cbd5e1'></i>{memst_swap_total}</span><strong>".mem_fmt_kb($swTotal)."</strong></div>";
        }
        $h[]="</div></div>";
    }
    $h[]="</div>";
    $h[]=mem_pressure_banner($p);
    $h[]="</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h));
}

// ---------------------------------------------------------------- leviers

/** Coquille commune des cartes de reglage. */
function mem_lever($ico,$tone,$title,$sub,$badge,$explain,$extra,$footLeft,$footRight):string{
    $tones=array("green"=>"ak-accent","blue"=>"ak-info","violet"=>"memx-violet","teal"=>"memx-teal");
    $h ="<div class='ak-card memx-lv'><div><div class='memx-lv-top'>";
    $h.="<span class='ak-ico ak-ico-sq ".($tones[$tone] ?? "ak-accent")."'><i class='$ico'></i></span>";
    $h.="<div style='flex:1;min-width:0'><div class='memx-lv-h'><span class='memx-lv-t'>$title</span>$badge</div>";
    if($sub<>""){$h.="<div class='memx-lv-sub'>$sub</div>";}
    $h.="</div></div><div class='memx-lv-e'>$explain</div>$extra</div>";
    $h.="<div class='memx-lv-foot'><span>$footLeft</span>$footRight</div></div>";
    return $h;
}

function mem_is_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}

/** Levier 1 : purge du cache (manuelle et automatique). */
function mem_lever_cache($M,$p,$page):string{
    $reclaimKB=max(0,intval($M["cached"] ?? 0)+intval($M["buffers"] ?? 0)+intval($M["kreclaimable"] ?? 0)-intval($M["shmem"] ?? 0));
    $thr=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MemoryCacheCleaning"));
    if($thr>=20 && $thr<=95){
        $sub="<span class='ak-pill ak-ok'>{memst_auto_above} $thr%</span>";
    }else{
        $sub="<span class='ak-pill ak-off'>{memst_auto_off}</span>";
    }
    $badge="<span class='ak-pill ak-off'>".mem_fmt_kb($reclaimKB)." {memst_reclaimable}</span>";
    $last=(isset($p["last_drop"]) && is_array($p["last_drop"]))?$p["last_drop"]:array();
    if(count($last)>0 && intval($last["at"] ?? 0)>0){
        $src=($last["source"] ?? "")=="auto"?"{memst_source_auto}":"{memst_source_manual}";
        $foot="<i class='fas fa-clock-rotate-left'></i> {memst_last_purge}: <strong>".date("d/m/Y H:i",intval($last["at"]))."</strong> &middot; ".intval($last["freed_mb"])." MB &middot; $src";
    }elseif(count($p)>0){
        $foot="<i class='fas fa-clock-rotate-left'></i> {memst_last_purge}: <strong>{memst_never}</strong>";
    }else{
        $foot="";
    }
    $btn="";
    if(mem_is_admin()){
        $btn="<span style='display:flex;gap:8px'><button type='button' class='ak-btn' OnClick=\"Loadjs('$page?MemoryCacheCleaning-js=yes');\"><i class='fas fa-gear'></i> {memst_automatic}</button>"
            ."<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?drop-now=yes');\"><i class='fas fa-bolt'></i> {memst_purge_now}</button></span>";
    }
    return mem_lever("fas fa-broom","green","{clean_cache}",$sub,$badge,"{memst_cache_explain}","",$foot,$btn);
}

/** Levier 2 : comportement de l'overcommit (protection OOM). */
function mem_lever_overcommit($k,$M,$page):string{
    $oc=intval($k["overcommit_memory"] ?? 0);
    $labels=array(0=>"{Overcommiting_Memory_0}",1=>"{Overcommiting_Memory_1}",2=>"{Overcommiting_Memory_2}");
    $tone=array(0=>"ak-ok",1=>"ak-warn",2=>"ak-info");
    $badge="<span class='ak-pill ".($tone[$oc] ?? "ak-off")."'>".($labels[$oc] ?? $oc)."</span>";
    $limit=intval($M["commitlimit"] ?? 0); $comm=intval($M["committed_as"] ?? 0);
    $extra="";
    // CommitLimit n'est applique qu'en mode 2 : en mode 0 ou 1, un engagement
    // au-dela de la limite est normal et un pourcentage > 100 inquietait a tort.
    if($limit>0 && $oc!=2){
        $extra="<div style='margin-top:12px' class='memx-row'><span>{memst_committed}: <strong style='color:#0f172a'>".mem_fmt_kb($comm)."</strong></span><span>{memst_not_limited}</span></div>";
    }elseif($limit>0){
        $pc=round($comm*100/$limit,1);
        $col=$oc==2&&$pc>=90?"#e11d48":"#2563eb";
        $extra="<div style='margin-top:12px'><div class='memx-row'><span>{memst_committed}: <strong style='color:#0f172a'>".mem_fmt_kb($comm)."</strong> / ".mem_fmt_kb($limit)."</span><span>$pc%</span></div>"
            ."<div class='ak-bar'><span style='width:".min(100,$pc)."%;background:$col'></span></div></div>";
    }
    $foot="{memst_mode}: <strong>vm.overcommit_memory = $oc</strong>";
    if($oc==2){$foot.=" &middot; <strong>{ratio} ".intval($k["overcommit_ratio"] ?? 0)."%</strong>";}
    $btn=mem_is_admin()?"<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?form-js=yes');\"><i class='fas fa-gear'></i> {memst_configure}</button>":"";
    return mem_lever("fas fa-shield-halved","teal","{Overcommiting_Memory_behavior}","",$badge,"{memst_overcommit_explain}",$extra,$foot,$btn);
}

/** Levier 3 : memoire partagee (shmmax, shmall en pages). */
function mem_lever_shm($k,$M,$page):string{
    $pageSize=intval($k["page_size"] ?? 0);
    if($pageSize<=0){$pageSize=4096;}
    $shmallKB=floatval($k["shmall"] ?? 0)*$pageSize/1024;
    $shmmaxKB=floatval($k["shmmax"] ?? 0)/1024;
    $shmemKB=intval($M["shmem"] ?? 0);
    $badge="<span class='ak-pill ak-info'>{memst_ceiling}: ".mem_fmt_kb($shmallKB)."</span>";
    $pc=$shmallKB>0?round($shmemKB*100/$shmallKB,1):0;
    $extra="<div style='margin-top:12px'><div class='memx-row'><span>{memst_in_use}: <strong style='color:#0f172a'>".mem_fmt_kb($shmemKB)."</strong> / ".mem_fmt_kb($shmallKB)."</span><span>$pc%</span></div>"
        ."<div class='ak-bar'><span style='width:".min(100,$pc)."%;background:#2563eb'></span></div></div>";
    $foot="{kernel_shmmax}: <strong>".mem_fmt_kb($shmmaxKB)."</strong> &middot; kernel.shmall: <strong>".number_format(floatval($k["shmall"] ?? 0),0,"."," ")." {memst_pages}</strong>";
    $btn=mem_is_admin()?"<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?form-js=yes');\"><i class='fas fa-gear'></i> {memst_configure}</button>":"";
    return mem_lever("fas fa-sitemap","blue","{memst_shm_title}","",$badge,"{memst_shm_explain}",$extra,$foot,$btn);
}

/** Levier 4 : swap compresse en RAM (zRam). */
function mem_lever_zram($z,$page):string{
    $on=!empty($z["enabled"]);
    $pct=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZramSizePercent"));
    if($pct==0){$pct=50;}
    if($on){
        $gain=max(0,floatval($z["orig_data_mb"] ?? 0)-floatval($z["compr_data_mb"] ?? 0));
        $badge="<span class='ak-pill memx-violet'>{memst_net_gain}: +".mem_fmt_kb($gain*1024)."</span>";
        $sub="<span class='ak-pill ak-ok'>{active2} &middot; $pct% RAM</span>";
        $extra="<div style='margin-top:12px' class='memx-row'><span>".htmlspecialchars(strtoupper((string)($z["comp_algorithm"] ?? "")))." &middot; {ratio} ".sprintf("%.2f",floatval($z["compression_ratio"] ?? 0)).":1</span>"
            ."<span>".sprintf("%.1f",floatval($z["swap_used_mb"] ?? 0))." / ".sprintf("%.0f",floatval($z["swap_total_mb"] ?? 0))." MB</span></div>";
        $foot="{memst_device}: <strong>".intval($z["disksize_mb"] ?? 0)." MB</strong>";
    }else{
        $badge="<span class='ak-pill ak-off'>{memst_inactive}</span>";
        $sub="";
        $extra="";
        $foot="{memst_zram_off}";
    }
    $btn=mem_is_admin()?"<button type='button' class='ak-btn".($on?"":" ak-btn-primary")."' OnClick=\"Loadjs('$page?zram-js=yes');\"><i class='fas fa-gear'></i> ".($on?"{memst_configure}":"{memst_enable}")."</button>":"";
    return mem_lever("fas fa-compress","violet","{zram_compressed_swap}",$sub,$badge,"{memst_zram_explain}",$extra,$foot,$btn);
}

function mem_levers(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $mem=api_meminfo();
    $M=$mem["MAIN"];
    $k=mem_sysctl();
    $z=mem_zram();
    $p=mem_pressure();
    $active=0;
    $thr=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MemoryCacheCleaning"));
    if($thr>=20 && $thr<=95){$active++;}
    if(!empty($z["enabled"])){$active++;}

    $h=array();
    $h[]="<div class='memx-sec'><div><div class='memx-sec-t'>{memst_levers_title}</div><div class='memx-sec-e'>{memst_levers_explain}</div></div>";
    $h[]="<span class='memx-sec-n'>$active / 2 {memst_auto_enabled}</span></div>";
    $h[]="<div class='ak-grid-2'>";
    $h[]=mem_lever_cache($M,$p,$page);
    if(count($k)>0){
        $h[]=mem_lever_overcommit($k,$M,$page);
        $h[]=mem_lever_shm($k,$M,$page);
    }else{
        $h[]="<div class='ak-card memx-lv'>".$tpl->div_error("sysctl API error")."</div>";
    }
    $h[]=mem_lever_zram($z,$page);
    $h[]="</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h))."<script>NoSpinner();</script>";
}
