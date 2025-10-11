#!/usr/bin/env python
import socket
from unix import *
import os.path
import datetime
import urllib
import hashlib
import traceback as tb
import re
import memcache
from urlparse import urlparse
from netaddr import IPNetwork, IPAddress
import sqlite3
import base64

class DNSFILTER:
    
    def __init__(self,debug):
        self.UfdbgclientSockTimeOut=self.GET_INFO_INT('UfdbgclientSockTimeOut')
        self.remote_port=0
        self.CLIENT_MAC=""
        self.PROXY_PROTO='GET'
        self.debug=self.GET_INFO_INT("DebugFilter")
        self.NoOutput=False
        self.LineToSend=""
        self.RedirectURI=""
        self.redirect_ip=""
        self.remote_ip= ""
        self.redirect_ttl=3600
        self.SquidGuardClientEnableMemory=0
        self.SquidGuardClientMaxMemorySeconds=0
        self.DebugFilter=self.GET_INFO_INT("DebugFilter");
        self.CheckLocalConfig()
        self.blacklist_src = set()
        self.blacklist_dst = set()
        
        self.SquidGuardClientEnableMemory=self.GET_INFO_INT("SquidGuardClientEnableMemory");
        self.SquidGuardClientMaxMemorySeconds=self.GET_INFO_INT("SquidGuardClientMaxMemorySeconds");
        if self.UfdbgclientSockTimeOut==0: self.UfdbgclientSockTimeOut=2
        if self.remote_ip=="all": self.remote_ip="127.0.0.1"
        if self.SquidGuardClientMaxMemorySeconds==0: self.SquidGuardClientMaxMemorySeconds=300
        
        self.read_list("/etc/unbound/blacklist_src.db", self.blacklist_src)
        self.read_list("/etc/unbound/blacklist_dst.db", self.blacklist_dst) 

        self.Unbound_event("Debug mode on and connection to "+self.remote_ip+":"+str(self.remote_port))
        self.Unbound_event("MemCache Support:"+str(self.SquidGuardClientEnableMemory)+" For "+str(self.SquidGuardClientMaxMemorySeconds)+" seconds")
        
        pass
    
    
    def GET_INFO(self,key):
        con = sqlite3.connect('/home/artica/SQLITE/dns.db')
        cur = con.cursor()
        try:
            cur.execute("SELECT ID,zvalue FROM DNSFilterSettings WHERE `zkey`='"+key+"'")
            data = cur.fetchone()
            if not data:
                con.close()
                return ""
            value=data[1]
            if len(value)==0:return ""
            return base64.b64decode(data[1])
        except sqlite3.Error as e:
            self.Unbound_event("GET_INFO(): Database error: %s" % e)
        except Exception as e:
            self.Unbound_event("GET_INFO(): Exception in _query: %s" % e)
           
            
    
    def GET_INFO_INT(self,key):
        con = sqlite3.connect('/home/artica/SQLITE/dns.db')
        cur = con.cursor()
        try:
            cur.execute("SELECT ID,zvalue FROM DNSFilterSettings WHERE `zkey`='"+key+"'")
            data = cur.fetchone()
            if not data:
                con.close()
                return 0
            value=data[1]
            if len(value)==0:return 0
            value=base64.b64decode(data[1])
            return strtoint(value)
        except sqlite3.Error as e:
            self.ufdb_event("GET_INFO(): Database error: %s" % e)
        except Exception as e:
            self.ufdb_event("GET_INFO(): Exception in _query: %s" % e)
            
            
    def read_list(self,name, xlist):
        try:
            with open(name, "r") as f:
                for line in f:
                    xlist.add(line.rstrip())
        except IOError:
            return False
        
        
    def check_name(self,name, xlist):
        while True:
            if (name in xlist):
                return True
            elif (name.find('.') == -1):
                return False;
            else:
                name = name[name.find('.')+1:]
                
    def CheckSources(self,ipaddr):
        if len(self.blacklist_src)==0: return False
        for line in self.blacklist_src:
            if ipaddr==line: return True
            if line.find('/')>0:
                if IPAddress(ipaddr) in IPNetwork(line): return True
        return False
    
 
    
    def Unbound_event(self,text):
        if self.DebugFilter==0: return None
        try:
            zfile  = open("/var/log/unbound-filter.log", "a")
            zfile.write(str(datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")) +" "+text+"\n")
            zfile.close()
        except:
            return True
            
    def ufdb_event(self,text):
        try:
            zfile  = open("/var/log/dnsfilterd/ufdbguardd.log", "a")
            zfile.write(str(datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")) +" [0000] "+text+"\n")
            zfile.close()
        except:
            return True        
       
    
    
    def CheckLocalConfig(self):
        if not os.path.exists("/etc/dnsfilterd/dnsfilterd.conf"): return
        
        for line in open('/etc/dnsfilterd/dnsfilterd.conf','r').readlines():
            matches=re.search("^interface\s+(.+)",line)
            if matches:
                self.remote_ip=str(matches.group(1))
                if self.remote_ip=="all": self.remote_ip="127.0.0.1"
            
            matches=re.search("^port\s+([0-9]+)",line)
            if matches:
                self.remote_port=int(matches.group(1))
                
            matches=re.search("^ufdb-debug-filter\s+on",line)
            if matches:
                self.DebugFilter=1
                
        pass
    
    
        
        

    def SendSocket(self,query):
        self.InactiveService=False
        if self.remote_port==0:
            self.ufdb_event('[FILTER]: Configuration Error, no port set... Aborting!')
            return ''
        
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(self.UfdbgclientSockTimeOut)
        
        try:
            sock.connect((self.remote_ip,self.remote_port))
        except socket.error as msg:
            self.ufdb_event('[FILTER]: Connection Error: Unable to connect to '+str(self.remote_ip)+':'+str(self.remote_port)+' ! - '+ str(msg[0]))
            return ''
        
        try:
            sock.send(query)
        except socket.error as msg:
            sock.close()
            self.ufdb_event('[FILTER]: Connection Error:  Unable to send data to '+str(self.remote_ip)+':'+str(self.remote_port)+' ! - '+ str(msg[0]))
            return ''
        
        try:
            response = sock.recv(1024)
        except socket.error as msg:
            sock.close()
            self.ufdb_event('[FILTER]: Connection Error:  Unable to receive data from '+str(self.remote_ip)+':'+str(self.remote_port)+' ! - '+ str(msg[0]))       
            return ''
            
        response=response.strip()
        sock.close()
    
        self.Unbound_event('[FILTER]: RESPONSE: "'+response+'"')
        if response.find("?loading-database=yes")>0: return ''
        if response.find("?fatalerror=yes")>0: return ''
        return response

    def SendToUfdb(self,clientip,hostname):
        CONNECT=False
        redirection=""
        KEY='url'
        CategoryFound=""
        RULE_ID=0
        md5=""
        matches = re.search('127\.0\.0\.0', hostname)
        if matches: return False

        EnableMemory=self.SquidGuardClientEnableMemory
        if len(hostname)==0: EnableMemory=0
        mc = memcache.Client(['unix:/var/run/memcached.sock'], debug=0)


        query="http://"+hostname+ " "+clientip+"/"+clientip+" - "+" GET myip=127.0.0.1 myport=3128\n"

# --------------------------------------------------- MEM BLOCK -----------------------------------------------        
        if EnableMemory==1:
            stemp=hostname + clientip + 'DNSFILTER'
            stemp=stemp.encode('utf8')
            md5 = str(hashlib.md5(stemp).hexdigest())
            #strtime=str(datetime.datetime.now().strftime("%Y-%m-%d %H"))
            self.Unbound_event("[FILTER]: ("+hostname+clientip+") ---> UFDB:"+md5+" -->MemCached")            
            
            LineToSend=mc.get("DNSFILTERD:"+md5)
            if LineToSend is not None:
                self.Unbound_event("[FILTER]: HIT ("+LineToSend+")")
                DNSFILTER_HITS=mc.get("DNSFILTER_HITS")
                if DNSFILTER_HITS is None: DNSFILTER_HITS=0
                DNSFILTER_HITS=int(DNSFILTER_HITS)+1
                mc.set("DNSFILTER_HITS",DNSFILTER_HITS)
                if LineToSend=="0": return False
                matches=re.search('http:\/\/([0-9\.]+)\/([0-9]+)/(.+?)\/(.+)',LineToSend)
                if matches:
                    self.redirect_ip=matches.group(1)
                    self.redirect_ttl=matches.group(2)
                    mc.set("DNSFILTER:"+clientip+":"+hostname,matches.group(3)+":"+matches.group(4),300)
                    rule=matches.group(3)
                    category=matches.group(4)
                    self.ufdb_event("BLOCK -\t\t"+clientip+"\t"+rule+"\t"+category+"\thttp://"+hostname+" GET")
                    return True
                
                
# -------------------------------------------------------------------------------------------------------------------------------------------------                 
        if EnableMemory==1: self.Unbound_event("[FILTER]: MISS ("+md5+")")
        
        if len(self.blacklist_dst)>0:
            if (self.check_name(hostname, self.blacklist_dst)):
                self.Unbound_event('[FILTER][240]: response: ['+hostname+'] BLACKLISTED')
                self.redirect_ip=self.GET_INFO("DefaultIpRedirection")
                if len(self.redirect_ip)==0: self.redirect_ip="127.0.0.1"
                self.redirect_ttl=self.GET_INFO_INT("dns_neg_ttl")
                if self.redirect_ttl==0: self.redirect_ttl=3600
                self.ufdb_event("BLOCK -\t\t"+clientip+"\tblacklist\tP0\thttp://"+hostname+" GET")
                redirection_source="http://"+self.redirect_ip+"/"+str(self.redirect_ttl)+"/blacklist/P0"
                if EnableMemory==1: mc.set("DNSFILTERD:"+md5, redirection_source,self.SquidGuardClientMaxMemorySeconds)
                return True
            
        if len(self.blacklist_src)>0:
            if self.CheckSources(clientip):
                self.Unbound_event('[FILTER][252]: response: ['+clientip+'] BLACKLISTED')
                self.redirect_ip=self.GET_INFO("DefaultIpRedirection")
                if len(self.redirect_ip)==0: self.redirect_ip="127.0.0.1"
                self.redirect_ttl=self.GET_INFO_INT("dns_neg_ttl")
                if self.redirect_ttl==0: self.redirect_ttl=3600
                self.ufdb_event("BLOCK -\t\t"+clientip+"\tblacklist\tP0\thttp://"+hostname+" GET")
                redirection_source="http://"+self.redirect_ip+"/"+str(self.redirect_ttl)+"/blacklist/P0"
                if EnableMemory==1: mc.set("DNSFILTERD:"+md5, redirection_source,self.SquidGuardClientMaxMemorySeconds)
                return True
                
        
                  
        self.Unbound_event('[FILTER][222]: Query: ['+query.strip()+']')
        
        UFDB_CONNECTS=mc.get("UFDB_CONNECTS")
        if UFDB_CONNECTS is None: UFDB_CONNECTS=0
        UFDB_CONNECTS=int(UFDB_CONNECTS)+1
        mc.set("UFDB_CONNECTS",UFDB_CONNECTS)
        
        self.Unbound_event('[FILTER]:237: UFDB_CONNECTS='+str(UFDB_CONNECTS))
        response=self.SendSocket(query)
        
        self.Unbound_event('[FILTER][226]: response: ['+response+']')
        
        if response =="OK":
            self.Unbound_event('[FILTER]: OK: "PASS"')
            if EnableMemory==1:
                if not self.InactiveService: mc.set("DNSFILTERD:"+md5, "0",self.SquidGuardClientMaxMemorySeconds)
            return False
        
        if len(response)==0:
            self.Unbound_event('[FILTER][238]: UNKNOWN: "PASS"')
            if EnableMemory==1: mc.set("DNSFILTERD:"+md5, "0",self.SquidGuardClientMaxMemorySeconds)
            return False
    
        
        matches=re.search('url="(.*?)"',response)
           
        if matches: redirection=matches.group(1)
        if len(redirection)==0: redirection=response
        self.Unbound_event('[FILTER][251]: redirection = "'+redirection+'" (247)')
        redirection_source=redirection
        
        matches=re.search('http:\/\/([0-9\.]+)\/([0-9]+)/(.+?)\/(.+)',redirection_source)
        if matches:
            if EnableMemory==1: mc.set("DNSFILTERD:"+md5, redirection_source,self.SquidGuardClientMaxMemorySeconds)
            self.Unbound_event('[FILTER]: group(1)=='+matches.group(1)+"'")
            self.redirect_ip=matches.group(1)
            self.redirect_ttl=matches.group(2)
            rule=matches.group(3)
            category=matches.group(4)
            mc.set("DNSFILTERD:"+clientip+":"+hostname,rule+":"+category,300)
                
        
        self.Unbound_event('[FILTER]: redirect_ip = "'+self.redirect_ip+'" redirect_ttl = "'+str(self.redirect_ttl)+'"')        
        return True
        pass
        