<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["explainthis"])){explainthis();exit;}
if(isset($_POST["SquidCacheLevel"])){Save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{cache_level}</h1>
	<p>{cache_level_explain}</p>
	</div>

	</div>



	<div class='row' style='min-height:1200px'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-cache-level'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-cache-level','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}


function table(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$squid=new squidbee();
	$sock=new sockets();
	$need_to_reload_proxy=$tpl->javascript_parse_text("{need_to_reload_proxy}");
	$SquidReloadIntoIMS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReloadIntoIMS"));
	$SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));

	
	if(!is_numeric($SquidReloadIntoIMS)){$SquidReloadIntoIMS=1;}
	$refresh_pattern_def_min=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_min"));
	$refresh_pattern_def_max=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_max"));
	$refresh_pattern_def_perc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_perc"));
	$refresh_pattern_def_opts=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("refresh_pattern_def_opts")));
	$store_dir_select_algorithm=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("store_dir_select_algorithm"));
	$SquidCacheSwapHigh=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheSwapHigh"));
	$SquidCacheSwapLow=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheSwapLow"));
	if($SquidCacheSwapLow==0){$SquidCacheSwapLow=95;}
	if($SquidCacheSwapHigh==0){$SquidCacheSwapHigh=97;}
	
	if($store_dir_select_algorithm==null){$store_dir_select_algorithm="least-load";}
	$CacheReplacementPolicy=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheReplacementPolicy");
	
	if($CacheReplacementPolicy==null){$CacheReplacementPolicy="heap_LFUDA";}
	
	$store_dir_select_algorithm_array["least-load"]="least-load";
	$store_dir_select_algorithm_array["round-robin"]="round-robin";
	
	
	if($refresh_pattern_def_min==0){$refresh_pattern_def_min=1;}
	if($refresh_pattern_def_max==0){$refresh_pattern_def_max=259200;}
	if($refresh_pattern_def_perc==0){$refresh_pattern_def_perc=100;}
	
	$SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
	$SquidCpuNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCpuNumber"));
	$squid_cache_mem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("squid_cache_mem"));
	$SquidMemoryCacheMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryCacheMode"));
	$SquidMemoryReplacementPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryReplacementPolicy"));
	$maximum_object_size_in_memory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("maximum_object_size_in_memory"));
	$SquidReadAheadGap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReadAheadGap"));
	$shared_memory_locking_disable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("shared_memory_locking_disable"));
	
	
	
	if($SquidMemoryReplacementPolicy==null){$SquidMemoryReplacementPolicy="lru";}
	if($maximum_object_size_in_memory==0){$maximum_object_size_in_memory=512;}
	if($SquidReadAheadGap==0){$SquidReadAheadGap=1024;}
	if($squid_cache_mem==0){$squid_cache_mem=256;}
	
	
	$precents[0]="{default}";
	for($i=1;$i<101;$i++){
		$precents[$i]="{$i}%";
	}
	
	$meminfo=unserialize(base64_decode($sock->getFrameWork("system.php?meminfo=yes")));
	$MEMTOTAL=$meminfo["MEMTOTAL"];
	if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>MEM TOTAL: $MEMTOTAL</span><br>\n";}
	$MEMTOTAL=$MEMTOTAL/1024;
	$MEMTOTAL=round($MEMTOTAL/1024);
	$MEMTOTAL=$MEMTOTAL-1500;
	$MEMTOTAL=$MEMTOTAL/3;

	$Count=$MEMTOTAL/128;
	if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>MEM TOTAL: $MEMTOTAL MB /128 - $Count] </span><br>\n";}
	$MEMZ["min"]=128;
	$zValues[]=256;
	for($i=2;$i<$Count;$i++){
		
		$CurMem=$i*128;
		$prc=$CurMem/$MEMTOTAL;
		$prc=round($prc*100);
		if($GLOBALS["VERBOSE"]){echo "<span style='color:red'>MEM TOTAL($prc): $CurMem MB [". $CurMem/$MEMTOTAL."] $CurMem/$MEMTOTAL*100</span><br>\n";}
		
		$MEMZ["{$prc}%"]=$CurMem;
		$zValues[]=$CurMem;
		
	}
	$MEMZ["max"]=$Count*128;
	$zValues[]=$Count*128;
	
	if($SquidReloadIntoIMS==0){unset($refresh_pattern_def_opts["reload-into-ims"]);}
	
	$CacheReplacementPolicyArray["lru"]="{cache_lru}";
	$CacheReplacementPolicyArray["heap_GDSF"]="{heap_GDSF}";
	$CacheReplacementPolicyArray["heap_LFUDA"]="{heap_LFUDA}";
	$CacheReplacementPolicyArray["heap_LRU"]="{heap_LRU}";
	
	
	
	$memory_cache_mode["always"]="always";
	$memory_cache_mode["disk"]="disk";
	$memory_cache_mode["network"]="disk";
	

	

	$SquidMaximumObjectSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMaximumObjectSize"));
	$SquidMiniMumObjectSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMiniMumObjectSize"));
	if($SquidMaximumObjectSize==0){$SquidMaximumObjectSize=32768;}
	if($SquidMiniMumObjectSize==0){$SquidMiniMumObjectSize=1;}
	
	
	$SquidMemoryCacheMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryCacheMode"));
	$SquidMemoryReplacementPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryReplacementPolicy"));
	$SquidReadAheadGap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReadAheadGap"));
	if($SquidReadAheadGap==0){$SquidReadAheadGap=1024;}
	
	if($SquidMemoryCacheMode==null){$SquidMemoryCacheMode=$squid->global_conf_array["memory_cache_mode"];}

	if($SquidMemoryReplacementPolicy==null){$SquidMemoryReplacementPolicy="heap_LFUDA";}
	

	if(preg_match("#([0-9]+)\s+#", $SquidReadAheadGap,$re)){$SquidReadAheadGap=$re[1];}
	if(preg_match("#([0-9]+)\s+#", $SquidMaximumObjectSize,$re)){$SquidMaximumObjectSize=$re[1];}
	$range_offset_limit_microsoft=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("range_offset_limit_microsoft"));

	$cache_level[0]="{no_cache_level}";
	$cache_level[1]="{smooth_cache_level}";
	$cache_level[2]="{medium_cache_level}";
	$cache_level[3]="{strong_cache_level}";
	$cache_level[4]="{booster_cache_level}";
	
	$memory_cache_mode["always"]="always";
	$memory_cache_mode["disk"]="disk";
	$memory_cache_mode["network"]="disk";
	$memory_replacement_policy["lru"]="{cache_lru}";
	$memory_replacement_policy["heap_GDSF"]="{heap_GDSF}";
	$memory_replacement_policy["heap_LFUDA"]="{heap_LFUDA}";
	$memory_replacement_policy["heap_LRU"]="{heap_LRU}";
	
	$explain="{SquidCacheLevel{$SquidCacheLevel}}";
	
	
	$form[]=$tpl->field_section("{memory_caching}","{squid_cache_memory_explain}");
	$form[]=$tpl->field_numeric("squid_cache_mem","{central_memory} (MB)",$squid_cache_mem,"{cache_mem_text}");
	$form[]=$tpl->field_numeric("SquidReadAheadGap","{read_ahead_gap} (KB)",$SquidReadAheadGap,"{read_ahead_gap_text}");
	$form[]=$tpl->field_array_hash($memory_cache_mode, "SquidMemoryCacheMode", "{memory_cache_mode}", $SquidMemoryCacheMode,true,"{memory_cache_mode_text}");
	$form[]=$tpl->field_array_hash($memory_replacement_policy, "SquidMemoryReplacementPolicy", "{cache_replacement_policy}", $SquidMemoryReplacementPolicy,true,"{cache_replacement_policy_explain}");
	$form[]=$tpl->field_numeric("maximum_object_size_in_memory","{maximum_object_size_in_memory} (KB)",$maximum_object_size_in_memory,"{maximum_object_size_in_memory_text}");
	$form[]=$tpl->field_checkbox("shared_memory_locking_disable","{shared_memory_locking_disable}",$shared_memory_locking_disable,false,"{shared_memory_locking_disable_explain}");
	
	
	
	$form[]=$tpl->field_section("{disk_caching}","$explain");
	$form[]=$tpl->field_array_hash($cache_level, "SquidCacheLevel", "{cache_level}", $SquidCacheLevel,false,null);

	
	
	$form[]=$tpl->field_numeric("SquidMiniMumObjectSize","{minimum_object_size} (bytes)",$SquidMiniMumObjectSize,"{minimum_object_size_text}");
	$form[]=$tpl->field_numeric("SquidMaximumObjectSize","{maximum_object_size} (MB)",$SquidMaximumObjectSize,"{maximum_object_size_text}");
	
	$form[]=$tpl->field_checkbox("SquidReloadIntoIMS","{reload_into_ims}",$SquidReloadIntoIMS,false,"{reload_into_ims_explain}");
	
	$form[]=$tpl->field_array_hash($q->CACHE_AGES, "refresh_pattern_def_min", "{minimal_time}", $refresh_pattern_def_min,false,"{caches_rules_min}");
	$form[]=$tpl->field_array_hash($q->CACHE_AGES, "refresh_pattern_def_max", "{max_time}", $refresh_pattern_def_max,false,"{caches_rules_max}");
	
	
	
		$form[]=$tpl->field_array_hash($CacheReplacementPolicyArray, "CacheReplacementPolicy", "{cache_replacement_policy}", $CacheReplacementPolicy,false,"{cache_replacement_policy_explain}");
		$form[]=$tpl->field_array_hash($precents, "refresh_pattern_def_perc", "{refresh_percent}", $refresh_pattern_def_perc,false,"{caches_rules_percent}");
		$form[]=$tpl->field_array_hash($store_dir_select_algorithm_array, "store_dir_select_algorithm", "{store_dir_select_algorithm}", $store_dir_select_algorithm,false,"{store_dir_select_algorithm_explain}");
		$form[]=$tpl->field_array_hash($precents, "SquidCacheSwapLow", "{cache_swap_low}", $SquidCacheSwapLow,false,"{cache_swap_low_text}");
		$form[]=$tpl->field_array_hash($precents, "SquidCacheSwapHigh", "{cache_swap_low}", $SquidCacheSwapHigh,false,"{cache_swap_high_text}");
	
		
		$f["override-expire"]=true;
		$f["override-lastmod"]=true;
		$f["reload-into-ims"]=true;
		$f["ignore-reload"]=true;
		$f["ignore-no-store"]=true;
		$f["ignore-private"]=true;
		$f["ignore-auth"]=true;
		$f["refresh-ims"]=true;
		$f["store-stale"]=true;

        foreach ($f  as $key=>$val){
			$valueX=0;
			if(isset($refresh_pattern_def_opts[$key])){$valueX=1;}
			$form[]=$tpl->field_checkbox($key,$key,$valueX,false,"{{$key}}");
		}
		
		
	
	
	

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.caches.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.caches.progress.log";
	$ARRAY["CMD"]="squid.php?verify-caches-progress-reload=yes";
	$ARRAY["TITLE"]="{verify_caches}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsCompile="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";
	$tpl->form_add_button("{apply_configuration}", $jsCompile);
	$html[]=$tpl->form_outside("{cache_level}", @implode("\n", $form),null,"{save}","LoadAjax('table-loader-cache-level','$page?table=yes');","AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function Save(){
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$f["override-expire"]=true;
	$f["override-lastmod"]=true;
	$f["reload-into-ims"]=true;
	$f["ignore-reload"]=true;
	$f["ignore-no-store"]=true;
	$f["ignore-private"]=true;
	$f["ignore-auth"]=true;
	$f["refresh-ims"]=true;
	$f["store-stale"]=true;
	$SquidCacheLevel=intval($_POST["SquidCacheLevel"]);
	
	reset($_POST);
	
	foreach ($_POST as $key=>$val){
		if(isset($f[$key])){
			$refresh_pattern_def_opts[$key]=true;
			continue;
		}
		
		$sock->SET_INFO($key, $val);
	}
	
	$refresh_pattern_data=base64_encode(serialize($refresh_pattern_def_opts));
	$sock->SaveConfigFile($refresh_pattern_data, "refresh_pattern_def_opts");
	
}

function explainthis(){
	$level=intval($_GET["explainthis"]);
	$UseSimplifiedCachePattern=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseSimplifiedCachePattern"));
	$exp[]="<div class='alert alert-info' style='min-height:455px'>";
	$exp[]="{SquidCacheLevel{$level}}";
	
	if($UseSimplifiedCachePattern==1){
		$exp[]="<ul>";
		if($level==4){
			$f["override-expire"]=true;
			$f["override-lastmod"]=true;
			$f["reload-into-ims"]=true;
			$f["ignore-reload"]=true;
			$f["ignore-no-store"]=true;
			$f["refresh-ims"]=true;
			$f["store-stale"]=true;
		}
		if($level==3){
			$f["override-expire"]=true;
			$f["override-lastmod"]=true;
			$f["reload-into-ims"]=true;
			$f["ignore-reload"]=true;

			$f["refresh-ims"]=true;
		}
		if($level==2){
			$f["override-expire"]=true;
			$f["override-lastmod"]=true;
		}


        foreach ($f as $key=>$val){
			$exp[]="<li>{{$key}}</li>";
			
		}
		
		$exp[]="</uL>";
	}
	
	$exp[]="</div>";
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body(@implode("\n", $exp));
	
}
