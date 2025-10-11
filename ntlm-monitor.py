#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')

import os
import logging
import re
from daemon import runner
from unix import *
import datetime
import time
import socket
import psutil
import pycron
import dns.resolver
from squidclient import *
from phpserialize import serialize, unserialize
import traceback as tb


# --------------------------------------------------------------------------------------------------------
class App():

    def __init__(self):
        self.stdin_path = '/dev/null'
        self.stdout_path = '/dev/null'
        self.stderr_path = '/dev/null'
        self.pidfile_path = '/var/run/artica-ntlm.pid'
        
        self.hostname=GET_INFO_STR("myhostname")
        self.logger=object
        self.pidfile_timeout = 5
        self.ad_ip_addr=""
        self.UsersHash={}
        self.zHeads={}
        self.CountTotal=0
        self.Counter=0
        self.TempMD5=''
        self.debug=False
        self.EnableArticaHotSpot=GET_INFO_INT("EnableArticaHotSpot")
        self.KerbMonitorIntervall=GET_INFO_INT("KerbMonitorIntervall")
        self.KerbMaxAttempts=GET_INFO_INT("KerbMaxAttempts")
        self.KerbMonitorAction=GET_INFO_STR("KerbMonitorAction")
        self.WindowsActiveDirectoryKerberos=0
        self.PingCount=0
        self.SMBPing=0
        self.wbinfoCount=0
        self.testjoinCount=0
        
        self.af_map = {
            socket.AF_INET: 'ADDR',
            socket.AF_INET6: 'ADDR6',
            psutil.AF_LINK: 'MAC',
            }
        
        if len(self.KerbMonitorAction)==0: self.KerbMonitorAction="disable_ad"
        if self.KerbMonitorIntervall==0: self.KerbMonitorIntervall=5
        if self.KerbMaxAttempts==0: self.KerbMaxAttempts=3
        tmp=file_get_contents("/etc/ntlm-watchdog/watchdog.conf")
        expl=tmp.split("\n")
        self.ad_ip_addr=expl[0]
        self.ad_host=expl[1]
        self.ad_user=expl[2]
        self.ad_pass=expl[3]
        if len(self.ad_ip_addr)==0: self.ad_ip_addr=self.ad_host

        
        pass
    
# --------------------------------------------------------------------------------------------------------
    def run(self):
        self.WindowsActiveDirectoryKerberos=GET_INFO_INT("WindowsActiveDirectoryKerberos")
        if self.EnableArticaHotSpot==1: return
        if self.WindowsActiveDirectoryKerberos==1: return
        
        self.logger.info("run(): Starting service for "+str(self.KerbMonitorIntervall)+"mn....")
        while True:
            
            if pycron.is_now('*/'+str(self.KerbMonitorIntervall)+' * * * *'):
                ActiveDirectoryEmergency=GET_INFO_INT("ActiveDirectoryEmergency")
                self.logger.info("run(): ActiveDirectoryEmergency: "+str(ActiveDirectoryEmergency)+" pointer....")
                if ActiveDirectoryEmergency==1:
                    logging.info("Currently in Active Directory Emergency, stopping tests")
                    time.sleep(30)
                    next
                    
#---------------------------------------------------------------------------------------------------------------
                logging.info("run(): ntlmprocesses...")
                try:
                    self.ntlmprocesses()
                except:
                    logging.info(tb.format_exc())
#---------------------------------------------------------------------------------------------------------------
                logging.info("run(): smbcontrol...")
                try:
                    if self.smbcontrol():
                        logging.info("run(): smbcontrol test failed -> Perform Action...")
                        self.PerformAction("SMBCONTROL")
                        time.sleep(30)
                        next
                except:
                    logging.info(tb.format_exc())                    
#---------------------------------------------------------------------------------------------------------------
                logging.info("run(): wbinfo...")
                try:
                    if self.wbinfo():
                        logging.info("run(): wbinfo test failed -> Perform Action...")
                        self.PerformAction("WBINFO")
                        time.sleep(30)
                        next
                except:
                    logging.info(tb.format_exc())                    
#---------------------------------------------------------------------------------------------------------------
                logging.info("run(): testjoin...")
                try:
                    if self.testjoin():
                        logging.info("run(): testjoin test failed -> Perform Action...")
                        self.PerformAction()
                        time.sleep(30)
                        next
                except:
                    logging.info(tb.format_exc())                    
#---------------------------------------------------------------------------------------------------------------  
            logging.info("run(): Sleeping 40s")
            time.sleep(40)
            
            
        
        pass
    
# --------------------------------------------------------------------------------------------------------
    def ntlmprocesses(self):
        squid=SQUID_CLIENT()
        squid.log=logging
        data= squid.MakeQuery("ntlmauthenticator")
        MAIN={}
        MAX={}
        
        CPU=0
        MAIN[CPU]=0
        MAX[CPU]=0
        array=data.split("\n")
        c=0
        for line in array:
            matches=re.match("by kid([0-9]+)",line)
            if matches: CPU=matches.group(1)
            matches=re.match("number active: ([0-9]+) of ([0-9]+)",line)
            if matches: MAX[CPU]=matches.group(2)
            matches=re.match("^([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(B|C|R|S|P|\s)\s+([0-9\.]+)\s+([0-9\.]+)\s+(.*)",line)
            if matches:
                ID=matches.group(1)
                FD=matches.group(2)
                PID=matches.group(3)
                Requests=matches.group(4)
                Replies=matches.group(5)
                Flags=matches.group(6)
                Flags=Flags.strip()
                Time=matches.group(7)
                Offset=matches.group(8)
                Request_text=matches.group(9)
                c=c+1
                if len(Flags)>0: MAIN[CPU]=MAIN[CPU]+1
            
        
        for cpu in MAIN:
            zcount=MAIN[cpu]
            if zcount == 0:
                self.logger.info("ntlmprocesses(): CPU "+str(cpu)+" = 0% used for "+str(MAX[cpu])+" processes current:"+str(c)+" running")
                continue
                
            percent=MAX[cpu]/zcount
            self.logger.info("ntlmprocesses(): CPU "+str(cpu)+" = "+str(zcount)+"/"+str(MAX[cpu])+" "+str(percent)+"% used current:"+str(c)+" running")
            
            if percent >98:
                squid_admin_mysql(0, "NTLM Monitor: Alert! " + str(percent)+"% NTLM Daemons used [action=reload]",data,"ntlmprocesses","ntlm-monitor","147")
                Executed=execute("/usr/sbin/artica-phpfpm-service -reload-proxy")
                return False
            
                
        pass
# --------------------------------------------------------------------------------------------------------    
    def smbcontrol(self):
        self.logger.info("smbcontrol(): 'STARTING'")
        try:
            os.chmod("/var/lib/samba/winbindd_privileged", 0755)
        except:
            self.logger.info("smbcontrol(): Alert! can't chmod /var/lib/samba/winbindd_privileged")
        
        results=execute("/usr/bin/smbcontrol winbindd ping")
        for line in results:
            self.logger.info("smbcontrol(): '"+line+"'")
            matches=re.search("No replies received",line)
            if matches:
                self.SMBPing=self.SMBPing+1
                self.logger.info("smbcontrol(): Alert! cannot ping winbindd service")
                squid_admin_mysql(1, "NTLM Monitor: Alert! cannot ping winbindd service [action=restart-winbind]","\n".join(results),"smbcontrol","ntlm-monitor","147")
                Executed=execute("/etc/init.d/winbind restart --force >/dev/null")
                if self.SMBPing == self.KerbMaxAttempts: return True 
                if self.SMBPing > self.KerbMaxAttempts: return True
                return False
        
        self.SMBPing=0
        self.logger.info("smbcontrol(): winbindd ping OK")
        return False
        pass
# --------------------------------------------------------------------------------------------------------     
    def wbinfo(self):
        results=execute("/usr/bin/wbinfo -t")
        for line in results:
            self.logger.info("wbinfo(): '"+line+"'")
            matches=re.search("WBC_ERR_WINBIND_NOT_AVAILABLE",line)
            if matches:
                self.wbinfoCount=self.wbinfoCount+1
                squid_admin_mysql(1, "NTLM Monitor: Alert! winbindd service WBC_ERR_WINBIND_NOT_AVAILABLE [action=start-winbind]","\n".join(results),"wbinfo","ntlm-monitor","147")
                Executed=execute("/etc/init.d/winbind start --force >/dev/null")                
                if self.wbinfoCount == self.KerbMaxAttempts: return True 
                if self.wbinfoCount > self.KerbMaxAttempts: return True
                
            matches=re.search("failed",line)
            if matches:
                self.wbinfoCount=self.wbinfoCount+1
                squid_admin_mysql(1, "NTLM Monitor: Alert! winbindd service failed [action=start-winbind]","\n".join(results),"wbinfo","ntlm-monitor","147")
                Executed=execute("/etc/init.d/winbind start --force >/dev/null")                
                if self.wbinfoCount == self.KerbMaxAttempts: return True 
                if self.wbinfoCount > self.KerbMaxAttempts: return True                 
            
            
            matches=re.search("succeeded",line)
            if matches:
                self.logger.info("wbinfo(): OK")
                self.wbinfoCount=0
                return False
        
        
        pass
# --------------------------------------------------------------------------------------------------------
    def testjoin(self):
        log=""
        results=execute("/usr/bin/net ads testjoin")
        for line in results:
            self.logger.info("testjoin(): '"+line+"'")
            matches=re.search("is OK",line)
            if matches:
                self.logger.info("testjoin(): '"+line+"' -> MATCH")
                self.testjoinCount=0
                return False
            
            self.logger.info("testjoin(): '"+line+"' -> NO MATCH")
            log=log+" Line "+line+" no matches OK\n"
        
        self.testjoinCount=self.testjoinCount+1
        squid_admin_mysql(1, "NTLM Monitor: Alert! testing join with AD failed ("+str(self.testjoinCount)+"/"+str(self.KerbMaxAttempts)+") [action=notify]",log+"\n"+"\n".join(results),"testjoin","ntlm-monitor","147")
        if self.testjoinCount == self.KerbMaxAttempts: return True 
        if self.testjoinCount > self.KerbMaxAttempts: return True
        pass
# --------------------------------------------------------------------------------------------------------    
    def PerformAction(self,why):
        self.PingCount=0
        self.SMBPing=0
        self.wbinfoCount=0
        self.testjoinCount=0
        
        if self.KerbMonitorAction=="none":
            squid_admin_mysql(0, "NTLM Monitor:["+why+"] Fatal! All tests with AD failed [action=notify]","\n","PerformAction","ntlm-monitor","147")
            return False
        
        if self.KerbMonitorAction=="restart":
            results=execute("/usr/bin/php /usr/share/artica-postfix/exec.kerbauth.php --build --force")
            squid_admin_mysql(0, "NTLM Monitor:["+why+"] Fatal! All tests with AD failed [action=re-connect]","\n".join(results),"PerformAction","ntlm-monitor","147")
            return False
        
        if self.KerbMonitorAction=="failover":
            squid_admin_mysql(0, "NTLM Monitor:["+why+"] Fatal! All tests with AD failed [action=fail-over]","\n","PerformAction","ntlm-monitor","147")
            execute("/etc/init.d/artica-failover stop")
            return False
        
        
        if self.KerbMonitorAction=="disable_ad":
            results=execute("/usr/bin/php /usr/share/artica-postfix/exec.kerbauth.watchdog.php --enable")
            squid_admin_mysql(0, "NTLM Monitor:["+why+"] Fatal! All tests with AD failed [action=Emergency]","\n".join(results),"PerformAction","ntlm-monitor","147")
            return False
        
        if self.KerbMonitorAction=="failover":
            squid_admin_mysql(0, "NTLM Monitor:["+why+"] Fatal! All tests with AD failed [action=reboot]","\n","PerformAction","ntlm-monitor","147")
            execute("/sbin/shutdown -rf")
            return False
        pass
# --------------------------------------------------------------------------------------------------------    
        
    

app = App()
app.debug=False

logger = logging.getLogger("artica-monitor")
logger.setLevel(logging.INFO)
formatter = logging.Formatter("[%(asctime)s]: %(message)s")
handler = logging.FileHandler("/var/log/ntlm-monitor.log")
handler.setFormatter(formatter)
logger.addHandler(handler)
app.logger=logger

#app.run()

daemon_runner = runner.DaemonRunner(app)
#This ensures that the logger file handle does not get closed during daemonization
daemon_runner.daemon_context.files_preserve=[handler.stream]
daemon_runner.do_action()

# --------------------------------------------------------------------------------------------------------