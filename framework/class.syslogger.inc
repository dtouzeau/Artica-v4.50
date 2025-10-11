<?php

class syslogger{
	
	function __construct(){
		
	}
	
	function MailDustbin($buffer){
		if(strpos($buffer,"/trivial-rewrite[")>0){return true;}
		if(strpos($buffer,"/error[")>0){return true;}
		if(strpos($buffer,"/bounce[")>0){return true;}
		if(strpos($buffer,"]: success resolving '")>0){return true;}
		if(strpos($buffer,"/qmgr[")>0){return true;}
		if(strpos($buffer,"/cleanup[")>0){return true;}
		if(strpos($buffer,"/pipe[")>0){return true;}
		if(strpos($buffer,"/smtp[")>0){return true;}		
		if(strpos($buffer,"run_av (ClamAV-clamd)")>0){return true;}
		if(strpos($buffer,"smtp cmd> MAIL FROM:")>0){return true;}
		if(strpos($buffer,"milter-greylist: ")>0){return true;}
		if(strpos($buffer,"smtp cmd> RCPT TO:")>0){return true;}
		if(strpos($buffer,"smtp session reuse,")>0){return true;}
		if(strpos($buffer,"smtp session most likely")>0){return true;}
		if(strpos($buffer,"] run_as_subprocess: child done")>0){return true;}
		if(strpos($buffer,") do_notify_and_quar:")>0){return true;}
		if(strpos($buffer,"_WARN: rules: failed to run")>0){return true;}
		if(strpos($buffer,"amavisd-new[")>0){return true;}
		if(strpos($buffer,"amavis[")>0){return true;}
		if(strpos($buffer,"process_request:")>0){return true;} 
		if(strpos($buffer,") loaded policy bank")>0){return true;}
		if(strpos($buffer,") collect banned table[")>0){return true;} 
		if(strpos($buffer,") p.path")>0){return true;}  
		if(strpos($buffer,") ClamAV-clamd:")>0){return true;}  
		if(strpos($buffer,") smtp connection cache,")>0){return true;} 
		if(strpos($buffer,") smtp resp to MAIL (")>0){return true;} 
		if(strpos($buffer,") AUTH not needed,")>0){return true;} 
		if(strpos($buffer,") smtp resp to RCPT (")>0){return true;} 
		if(strpos($buffer,") ESMTP::")>0){return true;} 
		if(strpos($buffer,") body hash:")>0){return true;} 
		if(strpos($buffer,") Checking: ")>0){return true;} 
		if(strpos($buffer,") p001 1")>0){return true;}
		if(strpos($buffer,") p002 1")>0){return true;}  
		if(strpos($buffer,") p003 1")>0){return true;} 
		if(strpos($buffer,") p004 1")>0){return true;} 
		if(strpos($buffer,") inspect_dsn:")>0){return true;} 
		if(strpos($buffer,") Checking for banned types")>0){return true;} 
		if(strpos($buffer,") TIMING [total")>0){return true;} 
		if(strpos($buffer,") sending SMTP response:")>0){return true;} 
		if(strpos($buffer,") TIMING-SA total")>0){return true;} 
		if(strpos($buffer,") smtp resp to DATA:")>0){return true;} 
		if(strpos($buffer,") Passed CLEAN,")>0){return true;} 
		if(strpos($buffer,") DSN: sender is")>0){return true;} 
		if(strpos($buffer,") smtp cmd> DATA")>0){return true;} 
		if(strpos($buffer,") spam_scan: score=")>0){return true;} 
		if(strpos($buffer,")  artica-plugin: client")>0){return true;} 
		if(strpos($buffer,".From: <")>0){return true;} 
		if(strpos($buffer,") ask_av Using (")>0){return true;} 
		if(strpos($buffer,") smtp resp to data-dot (")>0){return true;} 
		if(strpos($buffer,") Amavis::")>0){return true;} 
		if(strpos($buffer,") FWD via SMTP:")>0){return true;} 
		if(strpos($buffer,") _WARN: razor2")>0){return true;} 
		if(strpos($buffer,") smtp cmd>")>0){return true;} 
		if(strpos($buffer,") smtp resp to ")>0){return true;}  
		if(strpos($buffer,") smtp cmd> QUIT")>0){return true;}  
		if(strpos($buffer,") smtp session: setting up")>0){return true;}  
		if(strpos($buffer,"IO::Socket::INET")>0){return true;}
		if(strpos($buffer,", rundown:")>0){return true;}
		if(strpos($buffer,") bounce rescued by:")>0){return true;}
		if(strpos($buffer,") load: ")>0){return true;}
		if(strpos($buffer,") ESMTP: NOTICE:")>0){return true;}
		if(strpos($buffer,") SPAM-TAG,")>0){return true;}
		if(strpos($buffer,") smtp session rundown")>0){return true;}
		if(strpos($buffer,") bounce rescued by originating")>0){return true;}
		if(strpos($buffer,"]: SA dbg:")>0){return true;} 
		if(strpos($buffer,") SA dbg:")>0){return true;} 	
		if(strpos($buffer,"cyrus PLAIN User logged in")>0){return true;}
		if(strpos($buffer,"cyrus/ctl_cyrusdb")>0){return true;}
		if(strpos($buffer,"exited, status 0")>0){return true;}
		if(strpos($buffer,"fetching user_deny")>0){return true;}
		if(strpos($buffer,"seen_db: user")>0){return true;}
		if(strpos($buffer,"mystore: starting txn")>0){return true;}
		if(strpos($buffer,"mystore: committing")>0){return true;}
		if(strpos($buffer,"duplicate_mark:")>0){return true;}
		if(strpos($buffer,"root-servers.net:")>0){return true;}
		if(strpos($buffer,"KASINFO")>0){return true;}	
		if(strpos($buffer,"] run_as_subprocess: child done")>0){return true;}
		if(strpos($buffer,") Requesting process rundown after")>0){return true;}
		if(strpos($buffer,") TempDir removal: empty tempdir")>0){return true;}
		if(strpos($buffer,"artica-plugin: client")>0){return true;}	
		return false;		
	}
	
	
	
}