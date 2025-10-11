<?php

class rdpproxy_monitor{
	
	
	function __construct(){
		
	}
	
	
	public function parse($buffer){
		if(strpos($buffer, "Resizing to")>0){return;}
		if(strpos($buffer, "Reading font file")>0){return;}
		if(strpos($buffer, "GCC::UserData")>0){return;}
		if(strpos($buffer, "Front::incoming")>0){return;}
		if(strpos($buffer, "ACL SERIALIZER")>0){return;}
		if(strpos($buffer, "Ask next")>0){return;}
		if(strpos($buffer, "receiving 'width'=")>0){return;}
		if(strpos($buffer, "receiving 'file_encryption'")>0){return;}
		if(strpos($buffer, "ACL receive")>0){return;}
		if(strpos($buffer, "receiving 'ip_target'=")>0){return;}
		if(strpos($buffer, "receiving 'auth_channel_result'=")>0){return;}
		if(strpos($buffer, "receiving 'display_message'=")>0){return;}
		if(strpos($buffer, "receiving 'height'=")>0){return;}
		if(strpos($buffer, "receiving 'target_login'=")>0){return;}
		if(strpos($buffer, "receiving 'keepalive'=")>0){return;}
		if(strpos($buffer, "receiving 'accept_message'=")>0){return;}
		if(strpos($buffer, "receiving 'trans_ok'=")>0){return;}
		if(strpos($buffer, "receiving 'rec_path'=")>0){return;}
		if(strpos($buffer, "receiving 'width'=")>0){return;}
		if(strpos($buffer, "receiving 'clipboard'=")>0){return;}
		if(strpos($buffer, "receiving 'selector_current_page'=")>0){return;}
		if(strpos($buffer, "receiving 'trans_cancel'=")>0){return;}
		if(strpos($buffer, "receiving 'proxy_type'=")>0){return;}
		if(strpos($buffer, "receiving 'selector_group_filter'=")>0){return;}
		if(strpos($buffer, "receiving 'ip_client'=")>0){return;}
		if(strpos($buffer, "receiving 'file_encryption'=")>0){return;}
		if(strpos($buffer, "receiving 'target_device'=")>0){return;}
		if(strpos($buffer, "receiving 'selector_device_filter'=")>0){return;}
		if(strpos($buffer, "receiving 'auth_channel_target'=")>0){return;}
		if(strpos($buffer, "receiving 'real_target_device'=")>0){return;}
		if(strpos($buffer, "receiving 'authenticated'=")>0){return;}
		if(strpos($buffer, "receiving 'password'=")>0){return;}
		if(strpos($buffer, "receiving 'selector_proto_filter'=")>0){return;}
		if(strpos($buffer, "receiving 'bpp'=")>0){return;}		
		if(strpos($buffer, "receiving 'selector_lines_per_page'")>0){return;}		
		if(strpos($buffer, "receiving 'target_password'")>0){return;}		
		if(strpos($buffer, "receiving 'proto_dest'")>0){return;}		
		if(strpos($buffer, "receiving 'is_rec'=")>0){return;}		
		if(strpos($buffer, "SESSION_ID =")>0){return;}	
		if(strpos($buffer, "-- CR Recv:")>0){return;}	
		if(strpos($buffer, "MODULE_FROM_PROTOCOL")>0){return;}	
		if(strpos($buffer, "Begin Sending data to ACL")>0){return;}	
		if(strpos($buffer, "process save session info")>0){return;}	
		if(strpos($buffer, "Deactivate All PDU")>0){return;}	
		
		
	
		
		
		if(preg_match("#reporting=(.*?):(.*?):Code=([0-9]+)#",$buffer,$re)){
			rdpproxy_admin_mysql(2, "[{$re[1]}] to {$re[2]} code:{$re[3]}", $buffer,__FILE__,__LINE__);
			return;
		}
		if(preg_match("#reporting'='(.*?):(.*?):(.*?)'#",$buffer,$re)){
			rdpproxy_admin_mysql(2, "[{$re[1]}] to {$re[2]} code:{$re[3]}", $buffer,__FILE__,__LINE__);
			return;
		}
		
		if(preg_match("#Connection to\s+(.*?)\s+failed with errno = ([0-9]+) (Connection refused)#i", $buffer,$re)){
			$file="/etc/artica-postfix/croned.1/rdpproxy_monitor_{$re[1]}_failed_{$re[2]}";
			if(IfFileTime($file,1)){
				rdpproxy_admin_mysql(0, "Connection to {$re[1]} failed Error: {$re[2]}", $buffer,__FILE__,__LINE__);
			}
			return;
		}
		
		if(preg_match("#connection to (.*?) succeeded : socket#i", $buffer,$re)){
			$file="/etc/artica-postfix/croned.1/rdpproxy_monitor_{$re[1]}_success";
			if(IfFileTime($file,1)){
				rdpproxy_admin_mysql(2, "Connection to {$re[1]} success", $buffer,__FILE__,__LINE__);
			}
			return;
		}
		
		if(preg_match("#connecting to (.*)#i", $buffer,$re)){
			rdpproxy_admin_mysql(2, "Connection to {$re[1]}...", $buffer,__FILE__,__LINE__);
			return;
		}
		
		if(preg_match("#\s+Remote RDP Server login:(.*?)\s+host:(.*)#", $buffer,$re)){
			$file="/etc/artica-postfix/croned.1/rdpproxy_monitor_{$re[1]}_{$re[2]}_success";
			if(IfFileTime($file,1)){
				rdpproxy_admin_mysql(2, "Client {$re[2]} access to RDP with login {$re[1]}", $buffer,__FILE__,__LINE__);
			}
			return;
		}
		
		if(preg_match("#New session on [0-9]+.*?from (.*?)\s+to\s+(.*?)#", $buffer,$re)){
			rdpproxy_admin_mysql(2, "New session from {$re[1]}", $buffer,__FILE__,__LINE__);
			return;
		}
		
		if(preg_match("#'timeclose'='([0-9]+)'#", $buffer,$re)){
			$file="/etc/artica-postfix/croned.1/rdpproxy_monitor_timeclose_{$re[1]}";
			if(IfFileTime($file,1)){
				$dd=date("Y {l} {F} d H:i",$re[1]);
				rdpproxy_admin_mysql(2, "Will closing at $dd", $buffer,__FILE__,__LINE__);
			}
			return;
		}
		
		
		events_not_filtered(__CLASS__."/".__FUNCTION__." Not filtered \"$buffer\"");
		
		
	}
	
	
}
?>