<?php
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.watchdog.inc");


CHECK_MEMORY_USE();


function CHECK_MEMORY_USE(){


	$unix=new unix();
	$TimeFile="/etc/artica-postfix/pids/CHECK_MEMORY_USE.time";
	$xtime=$unix->file_time_min($TimeFile);
	if($xtime<5){exit();}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	
	
	$w=new squid_watchdog();
	$MonitConfig=$w->MonitConfig;
	if($MonitConfig["MEMORY_TEST"]==0){return;}


	$sys=new os_system();
	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	$swap_perc=$unix->SYSTEM_GET_SWAP_PERC();

	$MAX_MEM_ALERT_TTIME="/etc/artica-postfix/pids/MAX_MEM_ALERT_TTIME";
	$MAX_MEM_PRC_TTIME="/etc/artica-postfix/pids/MAX_MEM_PRC_TTIME";

	$MAX_MEM_MNS=$MonitConfig["MAX_MEM_MNS"];
	$MAX_MEM_ALERT=$MonitConfig["MAX_MEM_ALERT"];
	$MAX_MEM_PRC=$MonitConfig["MAX_MEM_PRC"];

	if($pourc>$MAX_MEM_PRC){
		$CheckTime=$unix->file_time_min($MAX_MEM_ALERT_TTIME);
		if($CheckTime<$MAX_MEM_MNS){
			CHECK_MEMORY_USE_ACTION();
			return;
		}
	}


	CHECK_MEMORY_USE_WARN();
}

function CHECK_MEMORY_USE_WARN(){
	$w=new squid_watchdog();
	$unix=new unix();
	$MonitConfig=$w->MonitConfig;
	if($MonitConfig["MEMORY_TEST"]==0){return;}

	$sys=new os_system();
	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	$MAX_MEM_ALERT_TTIME="/etc/artica-postfix/pids/MAX_MEM_ALERT_TTIME";
	$MAX_MEM_PRC_TTIME="/etc/artica-postfix/pids/MAX_MEM_PRC_TTIME";
	$MAX_MEM_ALERT=$MonitConfig["MAX_MEM_ALERT"];

	if($pourc<$MAX_MEM_ALERT){return;}
	if($unix->file_time_min($MAX_MEM_ALERT_TTIME)<10){return;}
	$report=$unix->ps_mem_report();
	$text_ram=xFormatBytes($ram_used,true)."/".xFormatBytes($ram_total,true);
	squid_admin_mysql(0, "Memory exceed {$MAX_MEM_ALERT}% - Current {$pourc}%($text_ram)", $report,__FILE__,__LINE__);
	@unlink($MAX_MEM_ALERT_TTIME);
	@file_put_contents($MAX_MEM_ALERT_TTIME, time());


}
function CHECK_MEMORY_USE_ACTION(){
	$sys=new os_system();
	$unix=new unix();
	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	$MAX_MEM_ALERT_TTIME="/etc/artica-postfix/pids/MAX_MEM_ALERT_TTIME";
	$MAX_MEM_PRC_TTIME="/etc/artica-postfix/pids/MAX_MEM_PRC_TTIME";

	$ram_log[]="Before Action = {$pourc}% $ram_used/$ram_total";
	$w=new squid_watchdog();
	$MonitConfig=$w->MonitConfig;
	$MAX_MEM_RST_MYSQL=$MonitConfig["MAX_MEM_RST_MYSQL"];
	$MAX_MEM_RST_UFDB=$MonitConfig["MAX_MEM_RST_UFDB"];
	$MAX_MEM_RST_APACHE=$MonitConfig["MAX_MEM_RST_APACHE"];
	$MAX_MEM_RST_SQUID=$MonitConfig["MAX_MEM_RST_SQUID"];
	$MAX_MEM_PRC=$MonitConfig["MAX_MEM_PRC"];
	$text_ram=xFormatBytes($ram_used,true)."/".xFormatBytes($ram_total,true);
	//Events("#1 Memory {$pourc}% Used:$ram_used Total: $ram_total");


	squid_admin_mysql(0, "Memory exceed {$MAX_MEM_PRC}% ($text_ram) [action=stop-services]", null,__FILE__,__LINE__);

	if($MAX_MEM_RST_MYSQL==1){
		squid_admin_mysql(0, "Memory exceed {$MAX_MEM_PRC}% ($text_ram) {restarting} {APP_MYSQL}", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/mysql restart --force --framework=". basename(__FILE__));
		shell_exec("/etc/init.d/ufdbcat restart --force");

		$mem=$sys->realMemory();
		$pourc=$mem["ram"]["percent"];
		$ram_used=$mem["ram"]["used"];
		$ram_total=$mem["ram"]["total"];
		$text_ram=xFormatBytes($ram_used,true)."/".xFormatBytes($ram_total,true);
		$ram_log[]="After restarting MySQL services {$pourc}% $text_ram";
	}


	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	//Events("#2 Memory {$pourc}% Used:$ram_used Total: $ram_total");


	if($pourc<$MAX_MEM_PRC){
		$report=$unix->ps_mem_report();
		squid_admin_mysql(1, "Memory OK {$pourc}% [action=report]", @implode("\n", $ram_log)."\n$report",__FILE__,__LINE__);
		@unlink($MAX_MEM_ALERT_TTIME);
		@unlink($MAX_MEM_PRC_TTIME);
		return;
	}


	if($MAX_MEM_RST_UFDB==1){
		squid_admin_mysql(1, "Memory exceed {$pourc}% - Restarting Webfiltering service", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/ufdb restart --force --framework=". basename(__FILE__));

		$mem=$sys->realMemory();
		$pourc=$mem["ram"]["percent"];
		$ram_used=$mem["ram"]["used"];
		$ram_total=$mem["ram"]["total"];
		$text_ram=xFormatBytes($ram_used,true)."/".xFormatBytes($ram_total,true);
		$ram_log[]="After restarting Webfiltering service {$pourc}% $text_ram";
	}


	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	//Events("#3 Memory {$pourc}% Used:$ram_used Total: $ram_total");


	if($pourc<$MAX_MEM_PRC){
		$report=$unix->ps_mem_report();
		squid_admin_mysql(1, "Memory OK {$pourc}% [action=report]", @implode("\n", $ram_log)."\n$report",__FILE__,__LINE__);
		@unlink($MAX_MEM_ALERT_TTIME);
		@unlink($MAX_MEM_PRC_TTIME);
		return;

	}


	if($MAX_MEM_RST_APACHE==1){
		squid_admin_mysql(1, "Memory exceed {$pourc}% - Restarting Web Servers services", null,__FILE__,__LINE__);
		if(is_file("/etc/init.d/apache2")){
			shell_exec("/etc/init.d/apache2 restart --force --framework=". basename(__FILE__));
		}
		shell_exec("/etc/init.d/artica-webconsole restart --force --framework=". basename(__FILE__));

		shell_exec("/etc/init.d/artica-memcache restart --force --framework=". basename(__FILE__));
		shell_exec("/etc/init.d/ntopng restart  --force --framework=". basename(__FILE__));
		$mem=$sys->realMemory();
		$pourc=$mem["ram"]["percent"];
		$ram_used=$mem["ram"]["used"];
		$ram_total=$mem["ram"]["total"];
		$ram_log[]="After restarting web servers services {$pourc}% $ram_used/$ram_total";
	}


	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	//Events("#4 Memory {$pourc}% Used:$ram_used Total: $ram_total");

	if($pourc<$MAX_MEM_PRC){
		$report=$unix->ps_mem_report();
		squid_admin_mysql(1, "Memory OK {$pourc}% [action=report]", @implode("\n", $ram_log)."\n$report",__FILE__,__LINE__);
		@unlink($MAX_MEM_ALERT_TTIME);
		@unlink($MAX_MEM_PRC_TTIME);
		return;

	}

	if($MAX_MEM_RST_SQUID==1){
		squid_admin_mysql(1, "Memory exceed {$pourc}% - {restarting_proxy_service}", null,__FILE__,__LINE__);
		system("/etc/init.d/squid restart --force");
		$mem=$sys->realMemory();
		$pourc=$mem["ram"]["percent"];
		$ram_used=$mem["ram"]["used"];
		$ram_total=$mem["ram"]["total"];
		$text_ram=xFormatBytes($ram_used,true)."/".xFormatBytes($ram_total,true);
		$ram_log[]="After {restarting_proxy_service} {$pourc}% $text_ram";
	}

	$mem=$sys->realMemory();
	$pourc=$mem["ram"]["percent"];
	$ram_used=$mem["ram"]["used"];
	$ram_total=$mem["ram"]["total"];
	//Events("#5 Memory {$pourc}% Used:$ram_used Total: $ram_total");
	$report=$unix->ps_mem_report();

	if($pourc<$MAX_MEM_PRC){

		squid_admin_mysql(1, "Memory OK {$pourc}% [action=report]", @implode("\n", $ram_log)."\n$report",__FILE__,__LINE__);
		@unlink($MAX_MEM_ALERT_TTIME);
		@unlink($MAX_MEM_PRC_TTIME);
		return;

	}

	squid_admin_mysql(0, "Clean memory failed after restarting all services!!! [action=report]", @implode("\n", $ram_log)."\n$report",__FILE__,__LINE__);


}
function xFormatBytes($kbytes){

	$spacer="";

	if($kbytes>1048576){
		$value=round($kbytes/1048576, 2);
		if($value>1000){
			$value=round($value/1000, 2);
			return "$value{$spacer}TB";
		}
		return "$value{$spacer}GB";
	}
	elseif ($kbytes>=1024){
		$value=round($kbytes/1024, 2);
		return "$value{$spacer}MB";
	}
	else{
		$value=round($kbytes, 2);
		return "$value{$spacer}KB";
	}
}