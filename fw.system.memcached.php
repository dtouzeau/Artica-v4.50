<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

if(isset($_GET["memcached-status"])){service_status();exit;}
if(isset($_GET["memcached-top-status"])){top_status();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["stats-memory"])){stats_memory();exit;}
if(isset($_GET["stats-traffic"])){stats_traffic();exit;}
if(isset($_GET["stats-items"])){stats_items();exit;}
if(isset($_GET["params"])){params();exit;}
if(isset($_POST["MemCachedThreads"])){save();exit;}

page();

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$MEMCACHED_VERSION=$sock->GET_INFO("APP_MEMCACHED_VERSION");
	$title=$tpl->_ENGINE_parse_body("{APP_MEMCACHED} &raquo;&raquo; {service_status}");
	$js="$page?table=yes";
	$MUNIN_CLIENT_INSTALLED=intval($sock->GET_INFO("MUNIN_CLIENT_INSTALLED"));
	$EnableMunin=intval($sock->GET_INFO("EnableMunin"));
	if($MUNIN_CLIENT_INSTALLED==1){
		if($EnableMunin==1){
			$js="$page?tabs=yes";
		}
	}
	$APP_MEMCACHED_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MEMCACHED_VERSION");

    $html=$tpl->page_header("$title v.$APP_MEMCACHED_VERSION",
        ico_memory,"{APP_MEMCACHED_TEXT}",$js,
        "memcached",
        "progress-memcached-restart",false,"table-memcached");

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_MEMCACHED} v$MEMCACHED_VERSION",$html);
		echo $tpl->build_firewall();
		return true;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function params():bool{
	$tpl=new template_admin();
	$sock=new sockets();
	
	
	$MemCachedThreads=intval($sock->GET_INFO("MemCachedThreads"));
	$MemCachedMem=intval($sock->GET_INFO("MemCachedMem"));
	$MemCachedConnections=intval($sock->GET_INFO("MemCachedConnections"));
	$MaxItemSize=intval($sock->GET_INFO("MaxItemSize"));
	if($MemCachedThreads==0){$MemCachedThreads=4;}
	if($MemCachedMem==0){$MemCachedMem=196;}
	if($MaxItemSize==0){$MaxItemSize=2;}
	if($MaxItemSize>128){$MaxItemSize=128;}
	if($MemCachedConnections==0){$MemCachedConnections=2048;}
	if($MaxItemSize>$MemCachedMem){$MaxItemSize=$MemCachedMem;}
	
	$form[]=$tpl->field_numeric("MemCachedThreads","{threads}",$MemCachedThreads);
	$form[]=$tpl->field_numeric("MemCachedMem","{cache_memory} (MB)",$MemCachedMem);
	$form[]=$tpl->field_numeric("MemCachedConnections","{max_connexions}",$MemCachedConnections);
	$form[]=$tpl->field_numeric("MaxItemSize","{max_size_per_itemc} (MB)",$MaxItemSize);
	
	
	$service_restart=restart_javascript();
	echo $tpl->form_outside("{APP_MEMCACHED}", $form,null,"{apply}",$service_restart,"AsSystemAdministrator");
    return true;
}
function save():bool{
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
    return true;
}

function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
	$array["{parameters}"]="$page?params=yes";
	$array["{statistics} {traffic}"]="$page?stats-traffic=yes";
	$array["{statistics} {memory}"]="$page?stats-memory=yes";
	$array["{statistics} {items}"]="$page?stats-items=yes";
	echo $tpl->tabs_default($array);
    return true;
}

function restart_javascript():string{
	$page=CurrentPageName();
	$sock=new sockets();
    $tpl=new template_admin();
    $js="";
	$MUNIN_CLIENT_INSTALLED=$sock->GET_INFO("MUNIN_CLIENT_INSTALLED");
	$EnableMunin=intval($sock->GET_INFO("EnableMunin"));
	if($MUNIN_CLIENT_INSTALLED==1){
		if($EnableMunin==1){$js="LoadAjax('table-memcached','$page?tabs=yes');";}
	}
    return $tpl->framework_buildjs("/memcached/restart",
    "memcached.progress","memcached.progress.log",
    "progress-memcached-restart",$js);


	
}
function service_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/memcached/status"));


    if(!$json->Status) {
        echo $tpl->widget_rouge($json->Error, "{error}");
        echo "<script>LoadAjaxSilent('memcached-top-status','$page?memcached-top-status=yes');</script>";

        return false;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $service_restart=restart_javascript();
    echo $tpl->SERVICE_STATUS($ini, "APP_MEMCACHED",$service_restart);
    echo "<script>LoadAjaxSilent('memcached-top-status','$page?memcached-top-status=yes');</script>";
    return true;
}
function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();



    $js=$tpl->RefreshInterval_js('memcached-status',$page,"memcached-status=yes");

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px;vertical-align:top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td >
	<div class=\"ibox\" style='border-top:0;'>
    	<div class=\"ibox-content\" style='border-top:0;margin-top:13px;' id='memcached-status'></div>
    </div></td></tr>";

	$html[]="</table></td>";
    $html[]="<td style='width:99%;vertical-align:top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='padding-left:10px;padding-top:20px'>";
    $html[]="<div id='memcached-top-status'></div>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="</td>";
	$html[]="</tr>";

	$html[]="</table>";
    $html[]="<script>$js</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function top_status():bool{

    $mem=new lib_memcached();
    $tpl=new template_admin();
    $limit_maxbytes_prc=0;
    $MAIN=array();
    $MAINz=$mem->Statistics();



    if(!$mem->ok){
        echo $tpl->div_error($mem->mysql_error);
        return false;
    }
    if(isset($MAINz["/var/run/memcached.sock:11211"])) {
        $MAIN = $MAINz["/var/run/memcached.sock:11211"];
    }

    if(isset($MAINz["/run/memcached.sock:11211"])) {
        $MAIN = $MAINz["/run/memcached.sock:11211"];
    }
    $keys="total_connections,bytes,get_hits,get_misses,curr_items,listen_disabled_num,limit_maxbytes";
    $zkeys=explode(",",$keys);
    foreach ($zkeys as $zkey) {
        if(!isset($MAIN[$zkey])){
            $MAIN[$zkey]=0;
        }
    }
    $timeTemp=time();
    $key="MEMCACHED_TEST";
    $mem->SetSetting($key,$timeTemp);
    $timeTemp2=$mem->GetSetting($key);

    $html[]="<div class=\"col-lg-8\">";

    if($timeTemp2<>$timeTemp){
        $html[]="
		<div class=\"widget style1 red-bg\">
		    <div class=\"row\">
		        <div class=\"col-xs-4\"><i class=\"fad fa-memory fa-5x\"></i></div>
		        <div class=\"col-xs-8 text-right\">
		            <span> {error} ($timeTemp2)</span>
		            <h2 class=\"font-bold\">Save Cache!</h2>
		        </div>
		    </div>
		</div>";

    }




    $total_connections=$MAIN["total_connections"];
    $bytes=intval($MAIN["bytes"]);
    $memused=FormatBytes($bytes/1024);
    $get_hits=intval($MAIN["get_hits"]);
    $get_misses=intval($MAIN["get_misses"]);
    $curr_items=intval($MAIN["curr_items"]);

    $curr_items=FormatNumber($curr_items);
    $total_connections=FormatNumber($total_connections);
    $listen_disabled_num=intval($MAIN["listen_disabled_num"]);
    $limit_maxbytes=intval($MAIN["limit_maxbytes"]);
    if($limit_maxbytes>0) {
        $limit_maxbytes_prc = round(($bytes / $limit_maxbytes) * 100, 2);
    }
    if(!isset($MAIN["threads"])){
        $MAIN["threads"]=0;
    }

    $threads=intval($MAIN["threads"]);
    $xrate=$get_misses+$get_hits;
    $rate=0;
    if($xrate>0){
        $rate = round(($get_hits / $xrate) * 100, 2);
    }



    if($listen_disabled_num>0){
        $listen_disabled_num=FormatNumber($listen_disabled_num);
        $html[]="
		<div class=\"widget style1 red-bg\">
		<div class=\"row\">
		<div class=\"col-xs-4\">
		<i class=\"fad fa-memory fa-5x\"></i>
		</div>
		<div class=\"col-xs-8 text-right\">
		<span> {errors}</span>
		<h2 class=\"font-bold\">$listen_disabled_num</h2>
		</div>
		</div>
		</div>";

    }

    $html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fad fa-memory fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {memory_used} (cache)</span>
	<h2 class=\"font-bold\">$memused ($limit_maxbytes_prc%)</h2>
	</div>
	</div>
	</div>";


    $html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 navy-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fas fa-list-ul fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {items}</span>
	<h2 class=\"font-bold\">$curr_items</h2>
	</div>
	</div>
	</div>";


    $html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fas fa-percent fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {cache_rate}</span>
	<h2 class=\"font-bold\">{$rate}%</h2>
	</div>
	</div>
	</div>";


    $html[]="<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 navy-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fas fa-microchip fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {connections} ({$threads} threads)</span>
	<h2 class=\"font-bold\">$total_connections</h2>
	</div>
	</div>
	</div>";

    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function stats_memory():bool{
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";

	$f[]="memcached_multi_memory-day.png";
	$f[]="memcached_multi_memory-month.png";
	$f[]="memcached_multi_memory-week.png";
	$f[]="memcached_multi_memory-year.png";

	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<div class='center' style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t' alt=''></div>";
		}
    }

	if(!$OUTPUT){
		$tpl=new template_admin();
		echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
    return true;
}

function stats_items(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	
	$f[]="memcached_multi_items-day.png";
	$f[]="memcached_multi_items-month.png";
	$f[]="memcached_multi_items-week.png";
	$f[]="memcached_multi_items-year.png";
	
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<div class='center' style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t' alt=''></div>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}

function stats_traffic(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	
	$f[]="memcached_multi_bytes-day.png"; 
	$f[]="memcached_multi_bytes-month.png";	
	$f[]="memcached_multi_bytes-week.png";
	$f[]="memcached_multi_bytes-year.png";

	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<div class='center' style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t' alt=''></div>";
		}


	}

	if(!$OUTPUT){
		$tpl=new template_admin();
		echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'):string{$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}