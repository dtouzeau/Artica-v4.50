#!/usr/bin/env python
from unix import *
import os.path
import datetime
import urllib
from mysqlclass import *
from phpserialize import serialize, unserialize
from netaddr import IPNetwork, IPAddress


class MICROHOTSPOT:
    
    def __init__(self,logging):
        self.SquidGuardIPWeb=''
        self.SquidGuardIPWebSSL=''
        self.memoryRules={}
        self.MemoryTimeOuts={}
        self.MemoryMaxperiod={}
        self.KeyUserMemory={}
        self.logging=logging
        self.MemoryTimeFiletime={}
        self.EnableSquidMicroHotSpotSSL=0
        self.output=''
        self.CachePath='/home/artica/microhotspot/Caches'
        if os.path.exists("/etc/artica-postfix/microhotspot/rules/networks.array"):
            networks=file_get_contents("/etc/artica-postfix/microhotspot/rules/networks.array")
            self.logging.debug("[HOTSPOT]: Rules.........: "+str(len(networks))+" Bytes ")
            if len(networks)>10:
                self.memoryRules=unserialize(networks)
            
        


        
        pass
    
    def HotSpotThis(self,sys,Channel,url,ipaddr,uid,mac,PROXY_PROTO):
        field=''
        field_value=''
        MyRuleID=0
        KeyUser=''
        LineChannel=''
        self.logging.debug("[HOTSPOT]: Channel.......: "+str(Channel))
        self.logging.debug("[HOTSPOT]: Receive ipaddr: "+ipaddr+" uid:"+uid+" mac:"+mac)
        self.logging.debug("[HOTSPOT]: Memory Rules..: "+str( len( self.memoryRules) ) )
        self.logging.debug("[HOTSPOT]: Requested URL.: "+PROXY_PROTO+"'"+str(url)+"'" )
        
        if len( self.memoryRules)==0:
            self.logging.debug("[HOTSPOT]: 0 rules... -> Aborting" )
            return False
        if mac =='00:00:00:00:00:00':
            mac=''
        if ipaddr=="127.0.0.1":
            return False
        
        if len(mac)>5:
            KeyUser=mac
            
        if len(KeyUser)==0:
            KeyUser=ipaddr
        
        StampFile=self.CachePath+"/"+KeyUser
        StampTime=self.CachePath+"/"+KeyUser+".time"
        matches=re.search('(\.googleapis\.com|\.gstatic\.com|\plus\.google\.com)',url)
        if matches:
             self.logging.debug("[HOTSPOT]: googleapis.com in URL --> PASS")
             return False
            
        matches=re.search('(.*?)\/microhotspot-disconnect',url)
        if matches:
            self.logging.debug("[HOTSPOT]: Disconnecting....")
            query=matches.group(1)
            self.output=LineChannel+'OK status=302 rewrite-url="'+query+'"'
            self.logging.debug("[HOTSPOT]: "+self.output)
            RemoveFile(StampFile)
            RemoveFile(StampTime)
            return True
            
        matches=re.search('\/microhotspot\?(.+)',url)
        if matches:
            self.logging.debug("[HOTSPOT]: Found Authentication in url")
            query=matches.group(1)
            self.output=LineChannel+'OK status=302 rewrite-url="http://127.0.0.1:16180/auth?'+query+'"'
            self.logging.debug("[HOTSPOT]: "+self.output)
            return True
        
        
        
        if self.CheckFilePointer(StampFile,StampTime,KeyUser):
            return False
        
  
        for cdir in self.memoryRules:
            ruleid=self.memoryRules[cdir]
            self.logging.debug("[HOTSPOT]: Checking Network: '"+str(cdir)+"' Rule N."+str(ruleid))
            if IPAddress(ipaddr) in IPNetwork(cdir):
                self.logging.debug("[HOTSPOT]: "+ipaddr+" Matches '"+str(cdir)+"' Rule N."+str(ruleid))
                MyRuleID=ruleid
                break
        
        if MyRuleID == 0:
            self.logging.debug("[HOTSPOT]: "+ipaddr+" No rule match")
            return False
            
        self.logging.debug("[HOTSPOT]: "+ipaddr+" rule N."+str(ruleid)+" match")
        if Channel>0:
            LineChannel=str(Channel)+" "
            
        uriencoded=urllib.quote_plus(url)
        if PROXY_PROTO=='CONNECT':
            self.logging.debug("[HOTSPOT]: CONNECT ( enabled == "+str(self.EnableSquidMicroHotSpotSSL)+" )")
            if self.EnableSquidMicroHotSpotSSL==1:
                 self.output=LineChannel+'OK status=302 rewrite-url="127.0.0.1:16143/login?ruleid='+str(MyRuleID)+'&KeyUser='+KeyUser+'&ipaddr='+ipaddr+'&uid='+uid+'&mac='+mac+'&url='+uriencoded+'"'
                 self.logging.debug("[HOTSPOT]: "+self.output)
                 return True 
                
        
        self.logging.info("[HOTSPOT]: "+ipaddr+" Rule N."+str(MyRuleID)+" Redirecting to 127.0.0.1:16180")
        self.output=LineChannel+'OK status=302 rewrite-url="http://127.0.0.1:16180/login?ruleid='+str(MyRuleID)+'&KeyUser='+KeyUser+'&ipaddr='+ipaddr+'&uid='+uid+'&mac='+mac+'&url='+uriencoded+'"'
        self.logging.debug("[HOTSPOT]: "+self.output)
        return True 
        
        return False
        pass
    
    def CheckFilePointer(self,StampFile,StampTime,KeyUser):
        if not os.path.exists(StampFile):
            return False
        self.logging.debug("[HOTSPOT]: "+StampFile +" exists")
        
        if not self.KeyUserMemory.has_key(KeyUser):
            Conf=file_get_contents(StampFile)
            array=Conf.split("|")
            ArraySize=len(array)
            self.logging.debug("[HOTSPOT]: Conf ->"+str(ArraySize)+" items")
            if ArraySize<2:
                self.logging.debug("[HOTSPOT]: Conf -> FALSE remove"+str(StampFile)+" conf")
                RemoveFile(StampFile)
                RemoveFile(StampTime)
                return False
        
            xTime=array[0]
            ruleid=array[1]
            userid=array[2]
            array.append(str(current_time_stamp()))
            TimeCheck=array[3]
            self.KeyUserMemory[KeyUser]="|".join(array)
        else:
            Conf=self.KeyUserMemory[KeyUser]
            array=Conf.split("|")
            xTime=array[0]
            ruleid=array[1]
            userid=array[2]
            TimeCheck=int(array[3])
            
        
            
        if not self.MemoryTimeOuts.has_key(ruleid):
            self.MemoryTimeOuts[ruleid]=strtoint(file_get_contents("/etc/artica-postfix/microhotspot/rules/"+ruleid+"/incativityperiod"))
        if not self.MemoryMaxperiod.has_key(ruleid):
            self.MemoryMaxperiod[ruleid]=strtoint(file_get_contents("/etc/artica-postfix/microhotspot/rules/"+ruleid+"/maxperiod"))
        if not self.MemoryTimeFiletime.has_key(StampTime):
            self.MemoryTimeFiletime[StampTime]=os.path.getmtime(StampTime)
            
            
        self.logging.debug("[HOTSPOT]: "+KeyUser+": TimeCheck = "+str(TimeCheck)+"s")    
        diffMem=difference_minutes(TimeCheck)
        self.logging.debug("[HOTSPOT]: "+KeyUser+":Diff memory = "+str(diffMem)+"Mn")
        if diffMem>1:
            self.logging.debug("[HOTSPOT]: "+KeyUser+":Saving "+StampFile)
            array[0]=str(datetime.now())
            array[3]=str(current_time_stamp())
            file_put_contents(StampFile,"|".join(array))
            
        
        if self.MemoryTimeOuts[ruleid]<2:
            self.logging.debug("[HOTSPOT]: "+KeyUser+":  [INACTIVITY] Memory Time out == 0, did not check timeouts")
            return True
        
        zTime=strtotime(xTime,"%Y-%m-%d %H:%M:%S.%f")
        diff=difference_minutes(zTime)
        self.logging.debug("[HOTSPOT]: "+KeyUser+": [INACTIVITY] Time out == "+str(self.MemoryTimeOuts[ruleid])+"mn Current, "+str(diff)+"mn")

        if diff>self.MemoryTimeOuts[ruleid]:
            self.logging.debug("[HOTSPOT]:  "+KeyUser+": [INACTIVITY] TIMED OUT...!")
            RemoveFile(StampFile)
            RemoveFile(StampTime)
            return False
        
        if(int(self.MemoryMaxperiod[ruleid])>0):
            self.logging.debug("[HOTSPOT]: "+KeyUser+":[SESSION]: Get Time difference from Stampfile: "+str(self.MemoryTimeFiletime[StampTime]))
            diffStamp=difference_minutes(self.MemoryTimeFiletime[StampTime])
            self.logging.debug("[HOTSPOT]: "+KeyUser+":[SESSION]: Get Time difference:"+str(diffStamp)+"mn and closing after "+str(self.MemoryMaxperiod[ruleid])+"mn")
            if diffStamp>int(self.MemoryMaxperiod[ruleid]):
                self.logging.debug("[HOTSPOT]: "+KeyUser+": [SESSION] TIMED OUT...!")
                RemoveFile(StampFile)
                RemoveFile(StampTime)
                return False
                
        else:
            self.logging.debug("[HOTSPOT]: "+KeyUser+": Session will be never closed ( 0 ) set")
            
        
            

        array[0]=str(datetime.now())
        self.KeyUserMemory[KeyUser]="|".join(array)
        self.logging.debug("[HOTSPOT]: "+KeyUser+":"+userid +" on rule number "+str(ruleid)+" Time:"+str(zTime)+' <> diif:'+str(diff)+" mn")
        return True
            
                
        
    
