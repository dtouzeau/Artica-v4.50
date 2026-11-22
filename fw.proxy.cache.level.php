<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.meminfo.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Cache level page (Your proxy > Caching > Cache level): flat cards, one dialog per group, one save per
// dialog on a closed list of settings. Consumed by articarest: squid/Caches.conf.go (cacheMem,
// cacheTWeaks), squid/SquidRefreshPattern (refresh_pattern_domains.conf), squid/CentralConf.go
// (store_dir_select_algorithm). Applied by the full reconfiguration (/proxy/nohup/reconfigure).

if(isset($_GET["flat"])){flat();exit;}
if(isset($_GET["level-js"])){level_js();exit;}
if(isset($_GET["level-popup"])){level_popup();exit;}
if(isset($_GET["refresh-js"])){refresh_js();exit;}
if(isset($_GET["refresh-popup"])){refresh_popup();exit;}
if(isset($_GET["memory-js"])){memory_js();exit;}
if(isset($_GET["memory-popup"])){memory_popup();exit;}
if(isset($_GET["disk-js"])){disk_js();exit;}
if(isset($_GET["disk-popup"])){disk_popup();exit;}
if(isset($_POST["cachelevel-section"])){section_save();exit;}

page();

// ---------------------------------------------------------------------------------------------------
// Settings, their defaults (the ones articarest applies when a token is empty) and their bounds.

function cl_int(string $key,int $default):int{
    $v=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
    return $v==0?$default:$v;
}
function cl_str(string $key,string $default):string{
    $v=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
    return $v==""?$default:$v;
}

function cl_levels():array{
    return array(0=>"{no_cache_level}",1=>"{smooth_cache_level}",2=>"{medium_cache_level}",
        3=>"{strong_cache_level}",4=>"{booster_cache_level}");
}
function cl_policies():array{
    return array("lru"=>"{cache_lru}","heap_GDSF"=>"{heap_GDSF}","heap_LFUDA"=>"{heap_LFUDA}","heap_LRU"=>"{heap_LRU}");
}
function cl_memory_modes():array{
    // "" = no memory_cache_mode directive: Squid's default (always).
    return array(""=>"{default} (always)","always"=>"always","disk"=>"disk","network"=>"network");
}
function cl_algorithms():array{
    return array("least-load"=>"least-load","round-robin"=>"round-robin");
}
function cl_ages():array{
    // Minutes, as refresh_pattern expects them.
    return array(0=>"{default}",30=>"30 {minutes}",60=>"1 {hour}",120=>"2 {hours}",360=>"6 {hours}",
        720=>"12 {hours}",1440=>"1 {day}",2880=>"2 {days}",4320=>"3 {days}",10080=>"1 {week}",
        20160=>"2 {weeks}",43200=>"1 {month}",129600=>"3 {months}",259200=>"6 {months}",525600=>"1 {year}");
}

// MIRROR of articarest squid/SquidRefreshPattern/options.go: the options Squid 6 accepts and what each
// cache level adds to the catch-all refresh_pattern. Change both together.
function cl_refresh_options():array{
    return array("override-expire","override-lastmod","reload-into-ims","ignore-reload","ignore-no-store",
        "ignore-private","refresh-ims","store-stale");
}
function cl_level_preset(int $level):array{
    $p=array(2=>array("override-expire","override-lastmod"),
        3=>array("override-expire","override-lastmod","ignore-reload","refresh-ims","reload-into-ims"),
        4=>array("override-expire","override-lastmod","ignore-reload","refresh-ims","reload-into-ims","ignore-no-store","store-stale"));
    return $p[$level]??array();
}
// cl_user_options: the administrator's explicit options (refresh_pattern_user_opts, comma-separated).
function cl_user_options():array{
    $out=array();
    foreach(explode(",",(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_user_opts")) as $o){
        $o=trim($o);
        if(in_array($o,cl_refresh_options(),true)){$out[$o]=true;}
    }
    return array_keys($out);
}
function cl_effective_options(int $level,bool $ims):array{
    $set=array();
    foreach(array_merge(cl_level_preset($level),cl_user_options()) as $o){$set[$o]=true;}
    if(!$ims){unset($set["reload-into-ims"]);}
    $out=array_keys($set);
    sort($out);
    return $out;
}

function cl_settings():array{
    $s=array();
    $s["SquidCacheLevel"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
    $s["squid_cache_mem"]=cl_int("squid_cache_mem",256);
    $s["maximum_object_size_in_memory"]=cl_int("maximum_object_size_in_memory",512);
    $s["SquidReadAheadGap"]=cl_int("SquidReadAheadGap",1024);
    $s["SquidMemoryReplacementPolicy"]=cl_str("SquidMemoryReplacementPolicy","heap_LFUDA");
    $s["SquidMemoryCacheMode"]="";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableMemoryCacheMode"))==0){
        $mode=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryCacheMode"));
        if(isset(cl_memory_modes()[$mode])){$s["SquidMemoryCacheMode"]=$mode;}
    }
    $s["SquidMiniMumObjectSize"]=cl_int("SquidMiniMumObjectSize",1);
    $s["SquidMaximumObjectSize"]=cl_int("SquidMaximumObjectSize",32768);
    $s["CacheReplacementPolicy"]=cl_str("CacheReplacementPolicy","heap_LFUDA");
    $s["store_dir_select_algorithm"]=cl_str("store_dir_select_algorithm","least-load");
    $s["SquidCacheSwapLow"]=cl_int("SquidCacheSwapLow",95);
    $s["SquidCacheSwapHigh"]=cl_int("SquidCacheSwapHigh",97);
    $s["refresh_pattern_def_min"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_min"));
    $s["refresh_pattern_def_max"]=cl_int("refresh_pattern_def_max",259200);
    $s["refresh_pattern_def_perc"]=cl_int("refresh_pattern_def_perc",100);
    // articarest writes reload_into_ims on when the token was never set.
    $ims=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReloadIntoIMS"));
    $s["SquidReloadIntoIMS"]=$ims==""?1:intval($ims);
    return $s;
}

// cl_ram_mb: physical memory in MB (REST GET /system/memory/meminfo, kernel kB), 0 when unknown.
function cl_ram_mb():int{
    $mem=api_meminfo();
    return intval(intval($mem["MAIN"]["memtotal"]??0)/1024);
}

function cl_age_text(int $minutes,string $default):string{
    $ages=cl_ages();
    if($minutes==0){return $default;}
    if(isset($ages[$minutes])){return $ages[$minutes];}
    return "$minutes {minutes}";
}

// ---------------------------------------------------------------------------------------------------
// Page and flat cards

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{cache_level}</h1>
	<p>{cache_level_explain}</p>
	</div>
	</div>
	<div class='row'><div id='progress-cache-level-restart'></div>
	<div class='ibox-content'>
	<div id='cache-level-flat'></div>
	</div>
	</div>
	<script>LoadAjax('cache-level-flat','$page?flat=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function flat(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $s=cl_settings();
    $levels=cl_levels();
    $policies=cl_policies();
    $modes=cl_memory_modes();
    $level=$s["SquidCacheLevel"];
    $opts=cl_effective_options($level,$s["SquidReloadIntoIMS"]==1);

    $tpl->table_form_section("{cache_level}","{SquidCacheLevel$level}");
    $tpl->table_form_field_js("Loadjs('$page?level-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{cache_level}",$levels[$level]??"{cache_level} $level",ico_speed);
    $tpl->table_form_field_js("Loadjs('$page?refresh-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{minimal_time} / {max_time}",
        cl_age_text($s["refresh_pattern_def_min"],"1 {minutes}")." / ".cl_age_text($s["refresh_pattern_def_max"],"6 {months}")
        .", {refresh_percent} {$s["refresh_pattern_def_perc"]}%",ico_clock);
    $tpl->table_form_field_bool("{reload_into_ims}",$s["SquidReloadIntoIMS"],ico_refresh);
    $tpl->table_form_field_text("{options}",count($opts)>0?implode(", ",$opts):"{none}",ico_params);

    $ram=cl_ram_mb();
    $tpl->table_form_section("{memory_caching}");
    $tpl->table_form_field_js("Loadjs('$page?memory-js=yes')","AsSquidAdministrator");
    $mem="{$s["squid_cache_mem"]} MB";
    if($ram>0){$mem.=" ({physical_memory}: ".FormatBytes($ram*1024).")";}
    $tpl->table_form_field_text("{central_memory}",$mem,ico_memory);
    $tpl->table_form_field_text("{maximum_object_size_in_memory}","{$s["maximum_object_size_in_memory"]} KB",ico_weight);
    $tpl->table_form_field_text("{memory_cache_mode}",$modes[$s["SquidMemoryCacheMode"]],ico_params);
    $tpl->table_form_field_text("{cache_replacement_policy}",$policies[$s["SquidMemoryReplacementPolicy"]]??$s["SquidMemoryReplacementPolicy"],ico_objects_group);
    $tpl->table_form_field_text("{read_ahead_gap}","{$s["SquidReadAheadGap"]} KB",ico_speed);

    $tpl->table_form_section("{disk_caching}");
    $tpl->table_form_field_js("Loadjs('$page?disk-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{minimum_object_size} / {maximum_object_size}",
        "{$s["SquidMiniMumObjectSize"]} bytes / ".FormatBytes($s["SquidMaximumObjectSize"]*1024),ico_weight);
    $tpl->table_form_field_text("{cache_replacement_policy}",$policies[$s["CacheReplacementPolicy"]]??$s["CacheReplacementPolicy"],ico_objects_group);
    $tpl->table_form_field_text("{store_dir_select_algorithm}",$s["store_dir_select_algorithm"],ico_hd);
    $tpl->table_form_field_text("{cache_swap_low} / {cache_swap_high}","{$s["SquidCacheSwapLow"]}% / {$s["SquidCacheSwapHigh"]}%",ico_database);

    $jsApply=$tpl->framework_buildjs("/proxy/nohup/reconfigure","squid.articarest.nohup","squid.articarest.log",
        "progress-cache-level-restart","LoadAjaxSilent('cache-level-flat','$page?flat=yes');");
    $tpl->table_form_button("{apply_configuration}",$jsApply,"AsSquidAdministrator",ico_refresh);
    echo $tpl->table_form_compile(2);
}

// ---------------------------------------------------------------------------------------------------
// Dialogs

function cl_jsafter():string{
    $page=CurrentPageName();
    return "dialogInstance1.close();LoadAjaxSilent('cache-level-flat','$page?flat=yes');";
}

function level_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{cache_level}","$page?level-popup=yes");
}
function level_popup():bool{
    $tpl=new template_admin();
    $s=cl_settings();
    $html[]=$tpl->div_explain("{cache_level}||{cache_level_explain}");
    $form[]=$tpl->field_hidden("cachelevel-section","level");
    $form[]=$tpl->field_array_hash(cl_levels(),"SquidCacheLevel","{cache_level}",$s["SquidCacheLevel"],true);
    $html[]=$tpl->form_outside("",$form,"","{apply}",cl_jsafter(),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function refresh_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{cache_level}: {options}","$page?refresh-popup=yes");
}
function refresh_popup():bool{
    $tpl=new template_admin();
    $s=cl_settings();
    $level=$s["SquidCacheLevel"];
    $preset=cl_level_preset($level);
    $user=cl_user_options();
    $levels=cl_levels();
    $percents=array(0=>"{default}");
    for($i=1;$i<101;$i++){$percents[$i]="$i%";}

    $html[]=$tpl->div_explain("{options}||{caches_rules_min}");
    $form[]=$tpl->field_hidden("cachelevel-section","refresh");
    $form[]=$tpl->field_array_hash(cl_ages(),"refresh_pattern_def_min","{minimal_time}",$s["refresh_pattern_def_min"],false,"{caches_rules_min}");
    $form[]=$tpl->field_array_hash(cl_ages(),"refresh_pattern_def_max","{max_time}",$s["refresh_pattern_def_max"],false,"{caches_rules_max}");
    $form[]=$tpl->field_array_hash($percents,"refresh_pattern_def_perc","{refresh_percent}",$s["refresh_pattern_def_perc"],false,"{caches_rules_percent}");
    $form[]=$tpl->field_checkbox("SquidReloadIntoIMS","{reload_into_ims}",$s["SquidReloadIntoIMS"],false,"{reload_into_ims_explain}");
    $form[]=$tpl->field_section("{options}");
    foreach(cl_refresh_options() as $o){
        if(in_array($o,$preset,true)){
            // Given by the level: shown, not editable here.
            $form[]=$tpl->field_info("opt-$o",$o,"{cache_level}: ".($levels[$level]??$level));
            continue;
        }
        $form[]=$tpl->field_checkbox("opt-$o",$o,in_array($o,$user,true)?1:0,false,"{{$o}}");
    }
    $html[]=$tpl->form_outside("",$form,"","{apply}",cl_jsafter(),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function memory_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{memory_caching}","$page?memory-popup=yes");
}
function memory_popup():bool{
    $tpl=new template_admin();
    $s=cl_settings();
    $ram=cl_ram_mb();
    $explain="{squid_cache_memory_explain}";
    if($ram>0){$explain.="<br>{physical_memory}: ".FormatBytes($ram*1024).", {recommended}: &le; ".cl_memory_recommended($ram)." MB";}
    $html[]=$tpl->div_explain("{memory_caching}||$explain");
    $form[]=$tpl->field_hidden("cachelevel-section","memory");
    $form[]=$tpl->field_numeric("squid_cache_mem","{central_memory} (MB)",$s["squid_cache_mem"],"{cache_mem_text}");
    $form[]=$tpl->field_numeric("maximum_object_size_in_memory","{maximum_object_size_in_memory} (KB)",$s["maximum_object_size_in_memory"],"{maximum_object_size_in_memory_text}");
    $form[]=$tpl->field_array_hash(cl_memory_modes(),"SquidMemoryCacheMode","{memory_cache_mode}",$s["SquidMemoryCacheMode"],false,"{memory_cache_mode_text}");
    $form[]=$tpl->field_array_hash(cl_policies(),"SquidMemoryReplacementPolicy","{cache_replacement_policy}",$s["SquidMemoryReplacementPolicy"],true,"{cache_replacement_policy_explain}");
    $form[]=$tpl->field_numeric("SquidReadAheadGap","{read_ahead_gap} (KB)",$s["SquidReadAheadGap"],"{read_ahead_gap_text}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",cl_jsafter(),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}
// cl_memory_recommended: a third of the memory left after 1.5 GB for the system (the former page's rule).
function cl_memory_recommended(int $ramMB):int{
    return max(128,intval(($ramMB-1500)/3));
}

function disk_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{disk_caching}","$page?disk-popup=yes");
}
function disk_popup():bool{
    $tpl=new template_admin();
    $s=cl_settings();
    $percents=array();
    for($i=1;$i<101;$i++){$percents[$i]="$i%";}
    $html[]=$tpl->div_explain("{disk_caching}||{SquidCacheLevel{$s["SquidCacheLevel"]}}");
    $form[]=$tpl->field_hidden("cachelevel-section","disk");
    $form[]=$tpl->field_numeric("SquidMiniMumObjectSize","{minimum_object_size} (bytes)",$s["SquidMiniMumObjectSize"],"{minimum_object_size_text}");
    $form[]=$tpl->field_numeric("SquidMaximumObjectSize","{maximum_object_size} (MB)",$s["SquidMaximumObjectSize"],"{maximum_object_size_text}");
    $form[]=$tpl->field_array_hash(cl_policies(),"CacheReplacementPolicy","{cache_replacement_policy}",$s["CacheReplacementPolicy"],true,"{cache_replacement_policy_explain}");
    $form[]=$tpl->field_array_hash(cl_algorithms(),"store_dir_select_algorithm","{store_dir_select_algorithm}",$s["store_dir_select_algorithm"],true,"{store_dir_select_algorithm_explain}");
    $form[]=$tpl->field_array_hash($percents,"SquidCacheSwapLow","{cache_swap_low}",$s["SquidCacheSwapLow"],true,"{cache_swap_low_text}");
    $form[]=$tpl->field_array_hash($percents,"SquidCacheSwapHigh","{cache_swap_high}",$s["SquidCacheSwapHigh"],true,"{cache_swap_high_text}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",cl_jsafter(),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// ---------------------------------------------------------------------------------------------------
// Save: one closed list per dialog, every value checked before anything is written.

function cl_post_int(string $key,int $min,int $max,string &$err):int{
    $v=trim((string)($_POST[$key]??""));
    if(!preg_match('/^[0-9]+$/',$v) || intval($v)<$min || intval($v)>$max){
        $err="$key: $min - $max";
        return 0;
    }
    return intval($v);
}
function cl_post_choice(string $key,array $choices,string &$err):string{
    $v=(string)($_POST[$key]??"");
    if(!array_key_exists($v,$choices)){$err="$key: {invalid}";return "";}
    return $v;
}

function section_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $tpl->CLEAN_POST();
    $err="";
    $set=array();
    switch((string)$_POST["cachelevel-section"]){
        case "level":
            $set["SquidCacheLevel"]=intval(cl_post_choice("SquidCacheLevel",cl_levels(),$err));
            break;
        case "refresh":
            $min=intval(cl_post_choice("refresh_pattern_def_min",cl_ages(),$err));
            $max=intval(cl_post_choice("refresh_pattern_def_max",cl_ages(),$err));
            $perc=cl_post_int("refresh_pattern_def_perc",0,100,$err);
            if($err==""){
                $effMin=$min==0?1:$min;
                $effMax=$max==0?259200:$max;
                if($effMin>$effMax){$err="{minimal_time} &gt; {max_time}";}
            }
            $set["refresh_pattern_def_min"]=$min;
            $set["refresh_pattern_def_max"]=$max;
            $set["refresh_pattern_def_perc"]=$perc;
            $set["SquidReloadIntoIMS"]=intval($_POST["SquidReloadIntoIMS"]??0)==1?1:0;
            // Explicit options only: the ones given by the level are not stored.
            $preset=cl_level_preset(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel")));
            $opts=array();
            foreach(cl_refresh_options() as $o){
                if(in_array($o,$preset,true)){continue;}
                if(intval($_POST["opt-$o"]??0)==1){$opts[]=$o;}
            }
            $set["refresh_pattern_user_opts"]=implode(",",$opts);
            break;
        case "memory":
            $set["squid_cache_mem"]=cl_post_int("squid_cache_mem",16,1048576,$err);
            $set["maximum_object_size_in_memory"]=cl_post_int("maximum_object_size_in_memory",1,1048576,$err);
            $mode=cl_post_choice("SquidMemoryCacheMode",cl_memory_modes(),$err);
            $set["SquidMemoryReplacementPolicy"]=cl_post_choice("SquidMemoryReplacementPolicy",cl_policies(),$err);
            $set["SquidReadAheadGap"]=cl_post_int("SquidReadAheadGap",1,1048576,$err);
            $ram=cl_ram_mb();
            if($err=="" && $ram>0 && $set["squid_cache_mem"]>$ram){$err="{central_memory} &gt; {physical_memory} ($ram MB)";}
            // articarest writes memory_cache_mode only when SquidDisableMemoryCacheMode is 0.
            $set["SquidMemoryCacheMode"]=$mode;
            $set["SquidDisableMemoryCacheMode"]=$mode==""?1:0;
            break;
        case "disk":
            $set["SquidMiniMumObjectSize"]=cl_post_int("SquidMiniMumObjectSize",0,1073741824,$err);
            $set["SquidMaximumObjectSize"]=cl_post_int("SquidMaximumObjectSize",1,10485760,$err);
            $set["CacheReplacementPolicy"]=cl_post_choice("CacheReplacementPolicy",cl_policies(),$err);
            $set["store_dir_select_algorithm"]=cl_post_choice("store_dir_select_algorithm",cl_algorithms(),$err);
            $set["SquidCacheSwapLow"]=cl_post_int("SquidCacheSwapLow",1,100,$err);
            $set["SquidCacheSwapHigh"]=cl_post_int("SquidCacheSwapHigh",1,100,$err);
            if($err=="" && $set["SquidCacheSwapLow"]>=$set["SquidCacheSwapHigh"]){$err="{cache_swap_low} &ge; {cache_swap_high}";}
            if($err=="" && $set["SquidMiniMumObjectSize"]>$set["SquidMaximumObjectSize"]*1048576){$err="{minimum_object_size} &gt; {maximum_object_size}";}
            break;
        default:
            $err="{invalid}";
    }
    if($err<>""){echo $tpl->post_error($err);return;}
    $log=array();
    foreach($set as $key=>$value){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key,$value);
        $log[]="$key=$value";
    }
    admin_tracks("Proxy cache level settings saved: ".implode(", ",$log));
}
