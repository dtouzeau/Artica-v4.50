<?php
class squid_common{

	function __construct(){
		
	}
	public function build(){
        system("/usr/sbin/artica-phpfpm-service -proxy-commonconf");
	}
}