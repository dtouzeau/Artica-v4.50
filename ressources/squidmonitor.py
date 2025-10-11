#!/usr/bin/env python
import socket
from unix import *
import re
import os.path

class SQUIDMONITOR():
    
    def __init__(self,logger):
        self.logger=logger
        self.InfluxUseRemote=GET_INFO_INT("InfluxUseRemote")
        self.InfluxSyslogRemote=GET_INFO_INT("InfluxSyslogRemote")
        pass
    
    
    def Execute(self):
        self.squid_tail()
        self.IsKerconnected()
        

        pass
    def squid_tail(self):
        if self.InfluxUseRemote==1:
            if self.InfluxSyslogRemote==1:
                return
            
        if not os.path.exists("/var/log/squid/squidtail.log"):
            self.logger.info("/var/log/squid/squidtail.log: No such file, aborting")
            return True
        
        
        SquidTailfsize=GET_INFO_INT("SquidTailfsize")
        CurrentSize=os.path.getsize('/var/log/squid/squidtail.log')
        
        if SquidTailfsize>CurrentSize:
            self.logger.info("/var/log/squid/squidtail.log: size is newer than the first execution, restart service")
            os.popen("/etc/init.d/squid-tail restart")
            return True
        
        xtime=file_time_min("/etc/artica-postfix/settings/Daemons/SquidTailPing")
        if xtime>1:
            self.logger.info("SquidTailPing: "+str(xtime)+"mn")
            self.logger.info("/var/log/squid/squidtail.log: SquidTailPing > 1 , restart service")
            os.popen("/etc/init.d/squid-tail restart")
            return True
        

        pass
    
    def IsKerconnected(self):
        filename="/etc/squid3/authenticate.conf"
        if not os.path.exists(filename):filename="/etc/squid3/squid.conf"
        if not os.path.exists(filename): return True
        with open(filename,"r") as f:
            for txt in f :
                txt=txt.rstrip('\n')
                if len(txt)<5: next
                matches=re.search('^auth_param.*?(ntlm_auth|negotiate_wrapper|negotiate_kerberos)',txt)
                if matches:
                    self.logger.info("IsKerconnected: Yes")
                    file_put_contents("/etc/artica-postfix/settings/Daemons/IsKerconnected","1")
                    return True
        self.logger.info("IsKerconnected: No")
        file_put_contents("/etc/artica-postfix/settings/Daemons/IsKerconnected","0")
        pass

        