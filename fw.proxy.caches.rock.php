<?php
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
    $html=$tpl->page_header("{central_cache} ({squid_rock})",
        ico_rocket,"{cache_rock_explain}","$page?table=yes","rock-cache","progress-rock-restart",false,
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
    $tpl->js_dialog1("{central_cache} ({squid_rock})","$page?form-popup=yes");
    return true;
}

function table():bool{
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

    $DEV_SHM=intval($sock->getFrameWork("squid.php?devshmsize=yes"))*0.7;
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
    $rock_explain_form="<span style='font-size: 18px'>{info}: <strong>{available_memory}: $DEV_SHM, {max_allowed_size_of_rock_store}: $max_rock_size</strong></span>";

    $tpl->table_form_field_js("Loadjs('$page?form-js=yes')");
    $tpl->table_form_section($rock_explain_form);
    $tpl->table_form_field_bool("{enable_feature}",$EnableRockCache,ico_rocket);
    $tpl->table_form_field_text("{directory}",$SquidRockPath,ico_directory);
    $tpl->table_form_field_text("{cache_size}",$array[$SquidRockSize],ico_weight);
    $tpl->table_form_field_text("{max_size}","{$SquidRockMaxSize}KB",ico_weight);
    $tpl->table_form_field_text("{timeout}","{swap_timeout} $swaptimeout[$SquidRockSwapTimeOut]ms {max_swap_rate} $max_swap_rate[$SquidRockMaxSwap]",ico_timeout);
    echo $tpl->table_form_compile();

    return true;

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
	
	$DEV_SHM=intval($sock->getFrameWork("squid.php?devshmsize=yes"))*0.7;
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
	$form[]=$tpl->field_numeric("SquidRockMaxSize","{max_size} (KB)",$SquidRockMaxSize,"{cache_dir_max_size_text}");
	$form[]=$tpl->field_array_hash($swaptimeout, "SquidRockSwapTimeOut", "{swap_timeout}", $SquidRockSwapTimeOut,false,"{swap_timeout_explain}");
	$form[]=$tpl->field_array_hash($max_swap_rate, "SquidRockMaxSwap", "{max_swap_rate}", $SquidRockMaxSwap,false,"{max_swap_rate_explain}");
	

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.rock.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.rock.progress.txt";
	$ARRAY["CMD"]="squid.php?rockstore-progress=yes";
	$ARRAY["TITLE"]="{APP_SQUID}::{rock_store}";

	$prgress=base64_encode(serialize($ARRAY));
	$jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=progress-rock-restart')";
	
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
