#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')

import os
import logging
import re
from daemon import runner
from postgressql import *
from unix import *
from squidmonitor import *
import datetime
import time
import socket
import psutil
import pycron
import dns.resolver
import traceback as tb
from phpserialize import serialize, unserialize

# --------------------------------------------------------------------------------------------------------
class App():

    def __init__(self):
        self.stdin_path = '/dev/null'
        self.stdout_path = '/dev/null'
        self.stderr_path = '/dev/null'
        self.pidfile_path = '/var/run/artica-monitor.pid'
        self.hostname=GET_INFO_STR("myhostname")
        self.InfluxUseRemote=GET_INFO_INT("InfluxUseRemote")
        self.InfluxSyslogRemote=GET_INFO_INT("InfluxSyslogRemote")
        self.WatchdogInterfacesCount=0
        self.logger=object
        self.pidfile_timeout = 5
        self.RequestNumber=0
        self.UsersHash={}
        self.zHeads={}
        self.CountTotal=0
        self.Counter=0
        self.TempMD5=''
        self.debug=False
        self.MyInterfaces={}
        self.MyUpInterfaces={}
        self.UsePortGreSQL=True
        self.xPathFunctional='L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw=='
        self.xPathFunctionalKey='L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL0FydGljYUNvcnBMaWNlbnNl='
        
        self.af_map = {
            socket.AF_INET: 'ADDR',
            socket.AF_INET6: 'ADDR6',
            psutil.AF_LINK: 'MAC',
            }
        
        if self.InfluxUseRemote==1:
            if self.InfluxSyslogRemote==1:
                self.UsePortGreSQL=False

        
        pass
    
# --------------------------------------------------------------------------------------------------------
    def run(self):
        self.POSTGRES=Postgres()
        self.POSTGRES.log=self.logger
        self.Squid=SQUIDMONITOR(self.logger)
        self.UpdateKey()
        
        
        while True:
           
            if pycron.is_now('* * * * *'):
                try:
                    self.monitor_system()
                except:
                    logger.info(tb.format_exc())
            
            if pycron.is_now('*/2 * * * *'):
                logger.info("Running settings.inc health check")
                self.test_include_settings()
                
            if pycron.is_now('*/5 * * * *'):
                logger.info("Running DNS settings health check")
                try:
                    self.CheckDNSServers()
                except:
                    logger.info("Running DNS settings health check - FATAL Exception -")
                
            try:
                self.Squid.Execute()
            except:
                logger.info("Running Squid.Execute() - FATAL Exception -")
                
            logger.info("Sleeping 30s...")
            time.sleep(30)
            self.UpdateKey()
            
        
        pass
    
# --------------------------------------------------------------------------------------------------------
    def UpdateKey(self):
        data=""
        if not os.path.exists(self.xPathFunctional.decode('base64')):
            file_put_contents(self.xPathFunctionalKey.decode('base64'),0)
            return
        data=file_get_contents(self.xPathFunctional.decode('base64'))
        data=data.strip()
        data=data.upper()
        if data == 'TRUE':
            file_put_contents(self.xPathFunctionalKey.decode('base64'),1)
            return
        file_put_contents(self.xPathFunctionalKey.decode('base64'),0)
# --------------------------------------------------------------------------------------------------------        
        

    def monitor_system(self):

        if os.path.exists("/etc/artica-postfix/force-status"):
            os.unlink("/etc/artica-postfix/force-status")
            os.popen("/usr/bin/php /usr/share/artica-postfix/exec.status.php --all --nowachdog >/usr/share/artica-postfix/ressources/logs/global.status.ini 2>&1")
            
        
        mem=psutil.virtual_memory()
        swap=psutil.swap_memory()
        memory_available=mem.available/1024
        memory_total=mem.total/1024
        memory_buffers=mem.buffers/1024
        percent=mem.percent
        swap_percent=swap.percent
        ram_used=round(mem.used/1024)
        
        swap_total=swap.total/1024
        swap_used=swap.used/1024
        swap_free=swap.free/1024
        memory_cached=mem.cached/1024
       
        HASH={}
        HASH["memory_available"]=memory_available
        HASH["memory_total"]=memory_total
        HASH["memory_buffers"]=memory_buffers
        HASH["memory_cached"]=memory_cached
        HASH["percent"]=percent
        HASH["ram_used"]=ram_used
        HASH["swap_percent"]=swap_percent
        HASH["swap_total"]=swap_total
        HASH["swap_used"]=swap_used
        HASH["swap_free"]=swap_free
        
        cpuUsage=psutil.cpu_percent(interval=None)
        internal_load, av2, av3 = os.getloadavg()
        if self.UsePortGreSQL:
            prefix="INSERT INTO system (zdate,proxyname,load_avg,mem_stats,cpu_stats)";
            values="VALUES(now(),'"+self.hostname+"','"+str(internal_load)+"','"+str(int(ram_used))+"','"+str(cpuUsage)+"')"
            self.POSTGRES.QUERY_SQL(prefix+" "+values)
            if not self.POSTGRES.ok:
                logger.info("PostgreSQL Error:"+self.POSTGRES.sql_error)
                # Unable to connect
                matches=re.match("Unable to connect.*?host=.*?([0-9\.]+)",self.POSTGRES.sql_error)
                if matches:
                    logger.info("PostgreSQL ping server "+matches.group(1))
                    self.ChockPostGreSQL(matches.group(1))
           
            
            
        try:
            CpuCount=psutil.cpu_count()
        except:
            logger.info(tb.format_exc())
        
        nics={}
        MACS={}
        stats = psutil.net_if_stats()
        for nic, addrs in psutil.net_if_addrs().items():
            if nic in stats:
                self.MyUpInterfaces[nic]=stats[nic].isup
                for addr in addrs:
                    if self.af_map.get(addr.family) =='MAC':
                        MACS[nic]=addr.address
                        continue
                    if self.af_map.get(addr.family) =='ADDR':
                        logger.info("Interface: "+nic+" = '"+addr.address+"/"+addr.netmask+"'")
                        self.MyInterfaces[nic]=addr.address
                        nics[nic]=addr.address+"/"+addr.netmask
                        continue  
        
       
        
        file_put_contents("/etc/artica-postfix/settings/Daemons/CPU_NUMBER",str(CpuCount))
        file_put_contents("/etc/artica-postfix/settings/Daemons/CPU_USAGE",str(cpuUsage))
        file_put_contents("/etc/artica-postfix/settings/Daemons/NET_IPADDRS",serialize(nics))
        file_put_contents("/etc/artica-postfix/settings/Daemons/NET_MACS",serialize(MACS))
        file_put_contents("/etc/artica-postfix/settings/Daemons/MEM_USAGE",serialize(HASH))
        logger.info("Testing interfaces")
        self.WatchdogInterfaces()
        
        pass
# --------------------------------------------------------------------------------------------------------

    def test_include_settings(self):
        
        if not os.path.exists("/usr/share/artica-postfix/ressources/settings.inc"):
            logger.info("settings.inc -> No such fule ( running process1) ")
            RemoveFile("/usr/share/artica-postfix/ressources/settings.new.inc")
            RemoveFile("/usr/share/artica-postfix/ressources/settings.inc")
            RemoveFile("/etc/artica-postfix/pids/exec.tests-settings.php.time")
            os.popen("/usr/bin/nohup /usr/share/artica-postfix/bin/process1 --force --verbose >/dev/null 2>&1 &")
            return
        
            
        Minutes=file_time_min("/etc/artica-postfix/pids/exec.tests-settings.php.time")
        logger.info("settings.inc -> "+str(Minutes)+"mn")
        if Minutes< 2:
            return
        
        executed=os.popen("/usr/bin/php /usr/share/artica-postfix/exec.tests-settings.php")
        for element in executed:
            matches=re.search('OK_INCLUDE',element.strip());
            if matches:
                return True
        
        
        logger.info("settings.inc -> Failed ( running process1) ")
        RemoveFile("/usr/share/artica-postfix/ressources/settings.new.inc")
        RemoveFile("/usr/share/artica-postfix/ressources/settings.inc")
        os.popen("/usr/bin/nohup /usr/share/artica-postfix/bin/process1 --force --verbose >/dev/null 2>&1 &")
        pass
# --------------------------------------------------------------------------------------------------------

    def resolve_server(self,dnsserver):
        logger.info("Resolving DNS with "+dnsserver+" DNS server")
        resolver = dns.resolver.Resolver()
        resolver.timeout = 1
        resolver.lifetime = 1
        resolver.nameservers = [dnsserver]
        try:
            answers_IPv4 = resolver.query('www.google.com', 'A')
            return True
        except:
            return False
# --------------------------------------------------------------------------------------------------------        

    def CheckDNSServers(self):
        servers={}
        with open("/etc/resolv.conf",'r') as reader :
            for line in reader :
                matches=re.search('nameserver\s+([0-9\.]+)',line.strip())
                if not matches:
                    continue
            servers[matches.group(1)]=True
            
        if os.path.exists("/etc/squid3/squid.conf"):
            with open("/etc/squid3/squid.conf",'r') as reader :
                for line in reader :
                    matches=re.search('dns_nameservers\s+(.*)',line.strip())
                    if not matches:
                        continue
                    DnsServers=matches.group(1)
                    DnsServersArray=DnsServers.split(" ")
                    for xline in DnsServersArray :
                        xline=xline.strip()
                        if xline == '':
                            continue
                        servers[xline]=True
        
        for servername in servers :
            logger.info("DNS -> "+str(len(servers))+" DNS servers")
            try:
                for nameserver in servers :
                    if not self.resolve_server(nameserver):
                        logger.info("[CheckDNSServers]: Resolving DNS failed with "+nameserver+" DNS server")
                        try:
                            squid_admin_mysql(1, "Resolving DNS failed with "+nameserver+" DNS server", "PLease modify your configuration","CheckDNSServers()","artica-monitor",__LINE__())
                        except:
                            logger.info("[CheckDNSServers]: Fatal while sending notification via unix.py")
                            
            except:
                logger.info("[CheckDNSServers]: DNS -> "+str(len(servers))+" DNS servers - Fatal in loop - ")
# --------------------------------------------------------------------------------------------------------
    def WatchdogInterfaces(self):
        self.WatchdogInterfacesCount=GET_INFO_INT("WatchdogInterfacesCount")
        if self.WatchdogInterfacesCount >4:
            logger.info("[WatchdogInterfaces]: Too many retries, aborting")
            return
            
        FileName="/etc/artica-postfix/nics-watchdog/Interfaces.array"
        if not os.path.exists(FileName): return False

        self.ArticaIfUPExecuted=0
        with open(FileName,"r") as f:
            for txt in f :
                txt=txt.rstrip('\n')
                if len(txt)<3:
                    logger.debug("[WatchdogInterfaces]: Wrong data: "+txt)
                    next
                Interface=txt
                logger.debug("[WatchdogInterfaces]: Checking Interface "+Interface)
                self.WatchdogSingleInterface(Interface)
                
        if self.ArticaIfUPExecuted==0:
            file_put_contents("/etc/artica-postfix/settings/Daemons/WatchdogInterfacesCount", "0")
            return
                
        if self.ArticaIfUPExecuted>0:
            self.WatchdogInterfacesCount=self.WatchdogInterfacesCount+1
            file_put_contents("/etc/artica-postfix/settings/Daemons/WatchdogInterfacesCount", str(self.WatchdogInterfacesCount))
            
        
        pass
# --------------------------------------------------------------------------------------------------------        
    def WatchdogSingleInterface(self,Interface):
        logger.info("[WatchdogSingleInterface]: Verify Interface: "+Interface)
        if not self.MyInterfaces.has_key(Interface):
            squid_admin_mysql(0,"Interface "+Interface+" is missing!","see next notification","WatchdogSingleInterface","artica-monitor",255)
            self.IfUPText=self.ArticaIfUP()
            if not self.MyInterfaces.has_key(Interface): return self.WatchdogActionInterface(Interface)
        
        if not self.MyUpInterfaces[Interface]:
            squid_admin_mysql(0,"Interface "+Interface+" is down!","see next notification","WatchdogSingleInterface","artica-monitor",255)
            self.IfUPText=self.ArticaIfUP()
            if not self.MyUpInterfaces[Interface]: return self.WatchdogActionInterface(Interface)
            
            
        CurrentIP=self.MyInterfaces[Interface]
        FileName="/etc/artica-postfix/nics-watchdog/"+Interface+"/IPADDR"
        ConfiguredIP=file_get_contents(FileName)
        logger.debug("[WatchdogSingleInterface]: Checking Interface "+Interface+"["+CurrentIP+"] Must be:"+ConfiguredIP)
        if CurrentIP == ConfiguredIP:
            logger.info("[WatchdogSingleInterface]: Interface "+Interface+"["+CurrentIP+"] OK")
            return True
        
        squid_admin_mysql(0,"Interface "+Interface+" is not correctly set!","see next notification","WatchdogSingleInterface","artica-monitor",255)
        self.IfUPText=self.ArticaIfUP()
        CurrentIP=self.MyInterfaces[Interface]
        if CurrentIP == ConfiguredIP: return True
        return self.WatchdogActionInterface(Interface)
       
        pass
# --------------------------------------------------------------------------------------------------------
    def ArticaIfUP(self):
        self.ArticaIfUPExecuted=self.ArticaIfUPExecuted+1
        cmdline="/etc/init.d/artica-ifup start"
        logger.debug("[ArticaIfUP]: Execute "+cmdline)
        results=execute(cmdline)
        results_text="\n".join(results)
        self.ResetNetz()
        return results_text
        pass
# --------------------------------------------------------------------------------------------------------    
        
    
    def WatchdogActionInterface(self,Interface):
        CurrentIP=self.MyInterfaces[Interface]
        FileName="/etc/artica-postfix/nics-watchdog/"+Interface+"/IPADDR"
        ConfiguredIP=file_get_contents(FileName)
        cmdline="/sbin/ifconfig "+Interface+" "+ConfiguredIP+" netmask "+NETMASK+" up"
        logger.debug("[WatchdogSingleInterface]: execute ["+cmdline+"]")
        results=execute(cmdline)
        results_text="\n".join(results)
        textinfo="The Interface "+Interface +"("+ConfiguredIP+") as been changed by "+CurrentIP+"\nThe watchdog will reconfigure to "+ ConfiguredIP+"\nFirst Execution\n"+self.IfUPText+"\nNext execution\n"+cmdline+":\n"+results_text
        squid_admin_mysql(0,"Corrupted Interface! "+Interface,textinfo,"WatchdogActionInterface","artica-monitor",255)
        pass
# --------------------------------------------------------------------------------------------------------      
    def ResetNetz(self):
        self.MyUpInterfaces={}
        self.MyInterfaces={}
        stats = psutil.net_if_stats()
        for nic, addrs in psutil.net_if_addrs().items():
            if nic in stats:
                self.MyUpInterfaces[nic]=stats[nic].isup
                for addr in addrs:
                    if self.af_map.get(addr.family) =='MAC':
                        MACS[nic]=addr.address
                        continue
                    if self.af_map.get(addr.family) =='ADDR':
                        self.MyInterfaces[nic]=addr.address
                        continue
        pass
# --------------------------------------------------------------------------------------------------------
    def ChockPostGreSQL(self,ipaddr):
        if ipaddr=="127.0.0.1":
            logger.info("[ChockPostGreSQL]: loopback ["+ipaddr+"]")
            squid_admin_mysql(0,"Local PostGreSQL issue [action=restart]","artica-monitor",255)
            os.system("/etc/init.d/artica-postgres restart")
            return
        if self.InfluxUseRemote==0:
            logger.info("[ChockPostGreSQL]: local service ["+ipaddr+"]")
            squid_admin_mysql(0,"Local PostGreSQL issue [action=restart]","artica-monitor",255)
            os.system("/etc/init.d/artica-postgres restart")
            return
        os.system("/usr/bin/php /usr/share/artica-postfix/exec.chock-stats-appliance.php &")
        pass
# --------------------------------------------------------------------------------------------------------    
            
        
            
        
        

app = App()
app.debug=False
RemoveFile("/var/log/artica-monitor.log")
logger = logging.getLogger("artica-monitor")
#logger.setLevel(logging.DEBUG)
logger.setLevel(logging.INFO)
formatter = logging.Formatter("[%(asctime)s]: %(message)s")
handler = logging.FileHandler("/var/log/artica-monitor.log")
handler.setFormatter(formatter)
logger.addHandler(handler)
app.logger=logger

#app.run()

daemon_runner = runner.DaemonRunner(app)
#This ensures that the logger file handle does not get closed during daemonization
daemon_runner.daemon_context.files_preserve=[handler.stream]
daemon_runner.do_action()

# --------------------------------------------------------------------------------------------------------