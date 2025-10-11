#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import os
import logging
import tailer
import hashlib
import re
from daemon import runner
import tldextract
import urllib
from categories import *
from unix import *
import datetime
import time
global DEBUG



class App():

    def __init__(self):
        self.stdin_path = '/dev/null'
        self.stdout_path = '/dev/null'
        self.stderr_path = '/dev/null'
        self.pidfile_path = '/var/run/squid-size-tail.pid'
        self.pidfile_timeout = 5
        self.RequestNumber=0
        self.UsersHash={}
        self.CountTotal=0
        self.debug=False
        self.Counters={}
        self.logger=object
        

    def run(self):
        logger.info('Following /home/squid/tail/access.log')
        catz=Categories()
        catz.Debug=False
        catz.log=logger
        self.RotateTime=int(time.time())
        file_put_contents("/etc/artica-postfix/settings/Daemons/SquidTailGsize",str( os.path.getsize('/home/squid/tail/access.log')))

        for line in tailer.follow(open('/home/squid/tail/access.log')):
            try:
                self.Explode(line,catz)
            except Exception as error:
                logger.info('Crash!!! while parsing line')
                logger.exception(error)
        pass
    
    def MinutesToTen(self,min):
        if min<=10: return 0
        if min<=20: return 10
        if min<=30: return 20
        if min<=40: return 30
        if min<=50: return 40
        if min<=60: return 50
        pass
 # ---------------------------------------------------------------------------------------------------   
    def GET_INT(self,fullpath):
        if not os.path.exists(fullpath): return 0
        data=file_get_contents(fullpath)
        return strtoint(data)
# ---------------------------------------------------------------------------------------------------
    
    def Explode(self,line,catz):
        
        array=line.split(":::")
        if len(array)<8:
            self.logger.info('Error: index out of range:"'+line+'"')
            return
        if self.debug: logger.info(line)
        mac=array[0].strip()
        ipaddr=array[1].strip()
        uid=array[2].strip()
        sitename=array[3].strip()
        sni=array[4].strip()
        REMOTE_IP=array[5].strip()
        size=int(array[6].strip())
        code_error=array[7].strip()
        size=int(size)
        
        if( size == 0): return
        if sitename =='127.0.0.1': return
        if int(code_error) == 302: return
        if int(code_error) == 301: return
        if sni =="-": sni=''
        if mac =='-': mac=''
        if uid =='-': uid=''
        
        if len(sitename) <2:
            if len(sni)>2: sitename=sni
        
        if len(sitename) <2:
            if len(REMOTE_IP)>2: sitename=REMOTE_IP
            
        if len(sitename) <2: return
        
        if sitename.find(":") > 0:
            zsitename=sitename.split(":")
            sitename=zsitename[0]
        
        MinutesDiff=difference_minutes(self.RotateTime)
        mac=mac.lower()
        ipaddr=ipaddr.lower()
        uid=uid.lower()
        mac=mac.replace('-',':')
        if mac == '00:00:00:00:00:00': mac=''

            
            
        self.RequestNumber=self.RequestNumber+1
        
        if len(uid)>0:
            uid=urllib.unquote(uid)
            uid=uid.replace('$','')
            
            
        familysite=sitename   
        xtime=datetime.datetime.now()
        SUFFIX_DATE=datetime.datetime.now().strftime("%Y%m%d%H")
        logzdate=datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        zyear=int(datetime.datetime.now().strftime("%Y"))
        zday=int(datetime.datetime.now().strftime("%d"))
        zmonth=int(datetime.datetime.now().strftime("%m"))
        zhour=int(datetime.datetime.now().strftime("%H"))
        zMin=self.MinutesToTen(int(datetime.datetime.now().strftime("%M")))
        MinDir="/home/squid/tail/"+str(zyear)+"/"+str(zmonth)+"/" +str(zday)+"/"+ str(zhour)+"/"+str(zMin)
        HourDir="/home/squid/tail/"+str(zyear)+"/"+str(zmonth)+"/" +str(zday)+"/"+ str(zhour)
        DayDir="/home/squid/tail/"+str(zyear)+"/"+str(zmonth)+"/" +str(zday)
        MonthDir="/home/squid/tail/"+str(zyear)+"/"+str(zmonth)
        
        KEY_USER='' 
        

            
        if not is_valid_ip(sitename):
            ext = tldextract.extract(sitename)
            familysite=ext.domain+'.'+ext.suffix
            
            
        if familysite=='msftncsi.com"': return
            
        category=catz.GET_CATEGORY(sitename)
        if len(category)==0: category='cat_unknown'
        logger.debug('mac........: "'+mac+'"')
        logger.debug('uid........: "'+uid+'"')
        logger.debug('ipaddr.....: "'+ipaddr+'"')
        logger.debug('sitename...: "'+sitename+'/'+familysite+'"')
        logger.debug('size.......: "'+str(size)+' bytes"')
        logger.debug('Minutes....: "'+str(zMin)+'"')
        logger.debug('Category...: "'+category+'"')
        logger.debug('Schedule...: "'+str(MinutesDiff)+'mn"')
        
      
        if len(uid)==0: uid="unknown"
        if len(mac)==0: mac="unknown_mac"
        self.InCrementSize(MinDir,ipaddr,uid,mac,familysite,category,size)
        self.InCrementSize(HourDir,ipaddr,uid,mac,familysite,category,size)
        self.InCrementSize(DayDir,ipaddr,uid,mac,familysite,category,size)
        self.InCrementSize(MonthDir,ipaddr,uid,mac,familysite,category,size)
        self.CountTotal=self.CountTotal+size
        if MinutesDiff>3:
            logger.info(str(self.CountTotal)+" bytes analyzed, "+str(self.RequestNumber)+" requests")
            self.RotateTime=int(time.time())
            
            
            

        pass
    
    
    def InCrementSize(self,path,ipaddr,mac,uid,domain,category,size):
        IpaddrPath=path+"/"+ipaddr
        UserPath=path+"/"+uid
        DomPath=path+"/"+domain
        CatzPath=path+"/"+category
        MacPath=path+"/"+mac
        
        
        
        mkdir(IpaddrPath,0755)
        mkdir(UserPath,0755)
        mkdir(DomPath,0755)
        mkdir(CatzPath,0755)
        mkdir(MacPath,0755)

        
        category=category.replace("/","_")
        
        DomUsrSizeFile=UserPath+"/"+domain
        logger.debug(DomUsrSizeFile)
        
        DomIPSize=self.GET_INT(IpaddrPath+"/"+domain)+size
        DomUsrSize=self.GET_INT(DomUsrSizeFile)+size
        DomMacSize=self.GET_INT(MacPath+"/"+domain)+size
        
        CatzIPsize=self.GET_INT(IpaddrPath+"/"+category)+size
        CatzUsrSize=self.GET_INT(UserPath+"/"+category)+size
        CatzMacSize=self.GET_INT(MacPath+"/"+category)+size
        
        file_put_contents(IpaddrPath+"/"+domain,str(DomIPSize))
        file_put_contents(IpaddrPath+"/"+category,str(CatzIPsize))
        
        file_put_contents(DomUsrSizeFile,str(DomUsrSize))
        file_put_contents(UserPath+"/"+category,str(CatzUsrSize))
        
        file_put_contents(MacPath+"/"+domain,str(DomMacSize))
        file_put_contents(MacPath+"/"+category,str(CatzMacSize))
        
        
        
        IpAddrSize=self.GET_INT(IpaddrPath+"/TOT")+size
        UserSize=self.GET_INT(UserPath+"/TOT")+size
        DomainSize=self.GET_INT(DomPath+"/TOT")+size
        CatzSize=self.GET_INT(CatzPath+"/TOT")+size
        MaczSize=self.GET_INT(MacPath+"/TOT")+size
        
        
        
        
        file_put_contents(IpaddrPath+"/TOT",str(IpAddrSize))
        file_put_contents(UserPath+"/TOT",str(UserSize))
        file_put_contents(DomPath+"/TOT",str(DomainSize))
        file_put_contents(CatzPath+"/TOT",str(CatzSize))
        file_put_contents(MacPath+"/TOT",str(MaczSize))
        
        pass
    

            

LOG_LEVEL=logging.INFO
SquidQuotaSizeDebug=GET_INFO_INT("SquidQuotaSizeDebug")
if SquidQuotaSizeDebug == 1: LOG_LEVEL=logging.DEBUG    
app = App()
app.debug=False
logger = logging.getLogger("squidtail")
logger.setLevel(LOG_LEVEL)
formatter = logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s")
handler = logging.FileHandler("/home/squid/tail/access.debug")
handler.setFormatter(formatter)
logger.addHandler(handler)
daemon_runner = runner.DaemonRunner(app)
#This ensures that the logger file handle does not get closed during daemonization
daemon_runner.daemon_context.files_preserve=[handler.stream]
daemon_runner.do_action()