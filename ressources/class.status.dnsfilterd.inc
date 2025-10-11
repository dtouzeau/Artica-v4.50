<?php

function dnsfilterd_status():string{
	// Abondonné, remplacé par le DNS Firewall.
	$l[]="[APP_DNSFILTERD]";
	$l[]="service_name=APP_DNSFILTERD";
	$l[]="service_cmd=/etc/init.d/dnsfilterd";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";
	//$EnableDNSFilterd=0;
	if(is_file("/etc/init.d/dnsfilterd")){
            $unix=new unix();
            $unix->framework_exec("exec.dnsfilterd.php --uninstall");
			squid_admin_mysql(0, "DNS Filter is installed but is unsupported after v4.40x, remove it", null,__FILE__,__LINE__);

			
		}
		$l[]="service_disabled=0";
		$l[]="running=0";
		return @implode("\n", $l);
		


}




