<?php


function parse_opendkim($buffer){
	

	
	if(preg_match("#warning: connect to Milter service unix:.*?opendkim.sock: No such file or directory#i", $buffer,$re)){
		$file="/etc/artica-postfix/pids/OpenDKIM.restart.time";
		$timefile=file_time_min($file);
		if($timefile>3){
			shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/opendkim restart >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());
		}
		return true;
	}
	
	
	// ---------------------------------------------------------------------------------------------------------------
	if(preg_match("#OpenDKIM Filter: Unable to bind to port.*?opendkim\.sock: Address already in use#i", $buffer,$re)){
		$file="/etc/artica-postfix/pids/OpenDKIM.restart.time";
		$timefile=file_time_min($file);
		if($timefile>3){
			shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/opendkim restart >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());
		}
		return true;
	}
	// ---------------------------------------------------------------------------------------------------------------
	if(preg_match("#opendkim.*?socket cleanup failed: Permission denied#i", $buffer,$re)){
		$file="/etc/artica-postfix/pids/OpenDKIM.restart.time";
		$timefile=file_time_min($file);
		if($timefile>3){
			shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/opendkim restart >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());
		}
		return true;
	}
	// ---------------------------------------------------------------------------------------------------------------
	if(preg_match("#OpenDKIM Filter: Unable to create listening socket on conn.*?\/opendkim\.sock#i", $buffer,$re)){
		$file="/etc/artica-postfix/pids/OpenDKIM.restart.time";
		$timefile=file_time_min($file);
		if($timefile>3){
			shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/opendkim restart >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time());
		}
		return true;
	}	
	
	// ---------------------------------------------------------------------------------------------------------------
	if(preg_match("#opendkim\[.*?can't load key from\s+(.+?):\s+Permission denied#", $buffer,$re)){
		$dir=dirname($re[1]);
		shell_exec("{$GLOBALS["CHOWN"]} -R postfix:postfix $dir >/dev/null 2>&1");
		shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.opendkim.php --perms >/dev/null 2>&1 &");
		return true;
	}

	if(preg_match("#opendkim\[([0-9]+)\]:\s+OpenDKIM\s+Filter\s+v(.+?)\s+starting#",$buffer,$re)){
		events("opendkim start");
		email_events("Postfix: Plugin OpenDKIM version {$re[2]} successfuly started","OpenDKIM inform\n$buffer\n","postfix");
		return true;
	}

	if(preg_match("#postfix-(.+?)\/smtpd\[.+?warning: connect to Milter service unix:.+?opendkim\.sock: No such file or directory#",$buffer,$re)){
		events("{$re[1]}::OpenDKIM Failed");
		$file="/etc/artica-postfix/croned.1/postfix.{$re[1]}.opendkim.error";
		if(file_time_min($file)>10){
			email_events("Postfix:{$re[1]}: OpenDKIM socket failed","Postfix claim\n$buffer\nArtica try to restart OpenDKIM and reconfigure {$re[1]} instance.","postfix");
			shell_exec_maillog("/etc/init.d/artica-postfix restart dkfilter &");

			@unlink($file);
			file_put_contents($file,"#");
		}
		return true;
	}

	if(strpos($buffer,"warning: connect to Milter service unix:/var/run/opendkim/opendkim.sock: No such file or directory")>0){
		events("OpenDKIM Failed");
		$file="/etc/artica-postfix/croned.1/postfix.opendkim.error";
		if(file_time_min($file)>10){
			email_events("Postfix: OpenDKIM socket failed","Postfix claim\n$buffer\nArtica try to restart OpenDKIM.","postfix");
			shell_exec_maillog("/etc/init.d/artica-postfix restart dkfilter &");
			@unlink($file);
			file_put_contents($file,"#");
		}
		return;
	}	
	
	
}