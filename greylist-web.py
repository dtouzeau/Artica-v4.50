#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import cherrypy
import logging
from cherrypy.lib.httputil import parse_query_string
from cherrypy.process.plugins import Daemonizer
from cherrypy.lib.static import serve_file
from cherrypy.process.plugins import PIDFile
from netaddr import IPNetwork, IPAddress
from postgressql import *
import tldextract
import os
import re
from unix import *

class RootServer:
    
    def __init__(self):
        logging.basicConfig(format='%(asctime)s [%(levelname)s] %(message)s',filename='/var/log/greylist-web.log',  filemode='a',level=logging.INFO)
        logging.raiseExceptions = False
        self.q=Postgres()
        self.q.log=logging
        self.logging=logging
        self.MimeDefangAutoWhiteList=GET_INFO_INT("MimeDefangAutoWhiteList")
        self.EnableMilterGreylistExternalDB=GET_INFO_INT("EnableMilterGreylistExternalDB")
        PIDFile(cherrypy.engine, "/var/run/greylist-web.pid").subscribe()
        pass
    
    def check_autowhite(self,domain_sender,mailfrom,domain_recipient,recipient):
        sql="SELECT zmd5 FROM autowhite WHERE mailfrom='"+mailfrom+"' AND mailto='"+recipient+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        sql="SELECT zmd5 FROM autowhite WHERE mailfrom='"+domain_sender+"' AND mailto='*'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        
        sql="SELECT zmd5 FROM autowhite WHERE mailfrom='"+domain_sender+"' AND mailto='"+domain_recipient+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        sql="SELECT zmd5 FROM autowhite WHERE mailfrom='*' AND mailto='"+domain_recipient+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        sql="SELECT zmd5 FROM autowhite WHERE mailfrom='*' AND mailto='"+recipient+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True          
        
        pass
    
    def check_ipaddr(self,method,ipaddr):
        #method = blacklist or Whitelist
        sql="SELECT ID,description FROM miltergreylist_acls WHERE method='"+method+"' AND type='addr' AND pattern='"+ipaddr+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        exploded=ipaddr.split(".")
        mask=exploded[0]+"."+exploded[1]+"."+exploded[2]+".0/24"
        sql="SELECT ID,description FROM miltergreylist_acls WHERE method='"+method+"' AND type='addr' AND pattern='"+mask+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        
        if self.EnableMilterGreylistExternalDB==0:
            return False
        
        sql="SELECT ID,description FROM miltergreylist_artica WHERE method='"+method+"' AND type='addr' AND pattern='"+ipaddr+"' AND enabled=1";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
       
        sql="SELECT ID,description FROM miltergreylist_artica WHERE method='"+method+"' AND type='addr' AND pattern='"+mask+"' AND enabled=1";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True        
        
        
        
        pass
    
    def inMyNet(self,ipaddr):
        networkfs=file_get_contents("/etc/artica-postfix/mynetworks")
        array=networkfs.split("\n")
        
        for cdir in array:
            cdir=cdir.strip()
            if len(cdir)==0:
                continue
            if IPAddress(ipaddr) in IPNetwork(cdir):
                return True
        pass
    
        
        
        
    
    def check_hostname(self,method,hostname):
        #method = blacklist or Whitelist
        sql="SELECT ID,description FROM miltergreylist_acls WHERE method='"+method+"' AND type='domain' AND pattern='"+hostname+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        if self.EnableMilterGreylistExternalDB==1:
            sql="SELECT ID,description FROM miltergreylist_artica WHERE method='"+method+"' AND type='domain' AND pattern='"+hostname+"' AND enabled=1";
            self.logging.debug(sql)
            zrows=self.q.QUERY_SQL(sql)
            if len(zrows)>0:
                return True
                
        if is_valid_ip(hostname):
            return False
        
        ext = tldextract.extract(hostname)
        familysite=ext.domain+'.'+ext.suffix
            
        sql="SELECT ID,description FROM miltergreylist_acls WHERE method='"+method+"' AND type='domain' AND pattern='"+familysite+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
            
        sql="SELECT ID,description FROM miltergreylist_acls WHERE method='"+method+"' AND type='domain' AND pattern='."+familysite+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        if self.EnableMilterGreylistExternalDB==0:
            return False
        
        sql="SELECT ID,description FROM miltergreylist_artica WHERE method='"+method+"' AND type='domain' AND pattern='"+familysite+"'  AND enabled=1";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
            
        sql="SELECT ID,description FROM miltergreylist_artica WHERE method='"+method+"' AND type='domain' AND pattern='."+familysite+"'  AND enabled=1";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True        
            
        
        pass
    
    def check_from(self,method,sender,senderdomain):
        #method = blacklist or Whitelist
        sql="SELECT ID,description FROM miltergreylist_acls WHERE method='"+method+"' AND type='from' AND pattern='"+sender+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        sql="SELECT ID,description FROM miltergreylist_acls WHERE method='"+method+"' AND type='from' AND pattern='"+senderdomain+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        
        sql="SELECT ID,description FROM miltergreylist_acls WHERE method='"+method+"' AND type='from' AND pattern='*@"+senderdomain+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        sql="SELECT ID,description FROM miltergreylist_acls WHERE method='"+method+"' AND type='from' AND pattern='@"+senderdomain+"'";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True          

        if self.EnableMilterGreylistExternalDB==0:
            return False
        
        sql="SELECT ID,description FROM miltergreylist_artica WHERE method='"+method+"' AND type='from' AND pattern='"+sender+"' AND enabled=1";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        sql="SELECT ID,description FROM miltergreylist_artica WHERE method='"+method+"' AND type='from' AND pattern='"+senderdomain+"' AND enabled=1";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        
        sql="SELECT ID,description FROM miltergreylist_artica WHERE method='"+method+"' AND type='from' AND pattern='*@"+senderdomain+"' AND enabled=1";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True
        
        sql="SELECT ID,description FROM miltergreylist_artica WHERE method='"+method+"' AND type='from' AND pattern='@"+senderdomain+"' AND enabled=1";
        self.logging.debug(sql)
        zrows=self.q.QUERY_SQL(sql)
        if len(zrows)>0:
            return True        
        
        
        pass        
            
        
    @cherrypy.expose
    def index(self, **keywords):
        print keywords
        return "it works! index "

    @cherrypy.expose
    def blacklist(self, **keywords):
        rcpt=keywords["rcpt"]
        mailfrom=keywords["from"]
        ipaddr=keywords["ipaddr"]
        hostname=keywords["hostname"]
        helo=keywords["helo"]
        domain_recipient=keywords["domainr"]
        domain_sender=keywords["domainf"]
        hostname=hostname.replace('[',"")
        hostname=hostname.replace(']',"")
        
        prefix="TAILTHIS|"+ipaddr+"|"+hostname+"|"+mailfrom+"|"+helo+"|"+mailfrom+"|"+rcpt
        
        self.logging.debug("Blacklist: Checking "+ipaddr)
        if self.check_ipaddr("blacklist",ipaddr):
            self.logging.info(prefix+"|BLACK|IPADDR")
            self.logging.debug("Blacklist: blacklisted "+ipaddr)
            return "milterGreylistStatus: Ok\nmilterGreylistMsg: Your IP/Network is blacklisted\n"
        
        self.logging.debug("Blacklist: Checking "+domain_sender+" "+mailfrom)
        if self.check_from("blacklist",domain_sender,mailfrom):
            self.logging.info(prefix+"|BLACK|FROM")
            self.logging.debug("Blacklist: blacklisted "+domain_sender+"/"+mailfrom)
            return "milterGreylistStatus: Ok\nmilterGreylistMsg: Your mail/domain is blacklisted\n"        
    
        self.logging.debug("Blacklist: Checking "+hostname)
        if self.check_hostname("blacklist",hostname):
            self.logging.info(prefix+"|BLACK|HOST")
            self.logging.info("Blacklist: blacklisted "+hostname)
            self.logging.debug("Blacklist: blacklisted "+hostname)
            return "milterGreylistStatus: Ok\nmilterGreylistMsg: Your server hostname is blacklisted\n"
        
        return "\n"
        
    @cherrypy.expose    
    def whitelist(self, **keywords):
        rcpt=keywords["rcpt"]
        mailfrom=keywords["from"]
        ipaddr=keywords["ipaddr"]
        hostname=keywords["hostname"]
        helo=keywords["helo"]
        domain_recipient=keywords["domainr"]
        domain_sender=keywords["domainf"]
        hostname=hostname.replace('[',"")
        hostname=hostname.replace(']',"")
        
        prefix="TAILTHIS|"+ipaddr+"|"+hostname+"|"+mailfrom+"|"+helo+"|"+mailfrom+"|"+rcpt
        
        self.logging.debug("Whitelist: Checking "+ipaddr+" in my networks")
        if self.inMyNet(ipaddr):
            self.logging.info(prefix+"|WHITE|MYNET")
            self.logging.debug("Whitelist: Ok: "+ipaddr+" is in my networks")          
            return "milterGreylistReport: In My Net\nmilterGreylistStatus: Ok\n"
            
        
        self.logging.debug("Whitelist: Checking "+ipaddr)
        if self.check_ipaddr("whitelist",ipaddr):
            self.logging.info(prefix+"|WHITE|IPADDR")
            return "milterGreylistStatus: Ok\n"
        
        self.logging.debug("Whitelist: Checking "+domain_sender+" "+mailfrom)
        if self.check_from("whitelist",domain_sender,mailfrom):
            self.logging.info(prefix+"|WHITE|FROM")
            self.logging.debug("Whitelist: Whitelisted "+domain_sender+"/"+mailfrom)
            return "milterGreylistStatus: Ok\n"        
    
        self.logging.debug("Whitelist: Checking "+hostname)
        if self.check_hostname("whitelist",hostname):
            self.logging.info(prefix+"|WHITE|HOST")
            self.logging.debug("Whitelist: Whitelisted "+hostname)
            return "milterGreylistStatus: Ok\n"
        
        
        self.logging.debug("Whitelist: Checking  "+domain_sender+" "+mailfrom+" against "+ domain_recipient+"/"+rcpt)
        if self.check_autowhite(domain_sender,mailfrom,domain_recipient,rcpt):
            self.logging.info(prefix+"|WHITE|AUTOWHITE")
            return "milterGreylistReport: Auto-whitelist\nmilterGreylistStatus: Ok\n"
        
        self.logging.info(prefix+"|GREY|UNKNWON")
        return "\n"


if __name__ == '__main__':
    

    http_port=0

    
    if http_port == 0:
        http_port=16777
        
    cherrypy.tree.mount(RootServer())
    cherrypy.server.unsubscribe()

    server2 = cherrypy._cpserver.Server()
    server2.socket_port=http_port
    server2._socket_host="127.0.0.1"
    server2.thread_pool=100
    server2.socket_queue_size=50
    server2.subscribe()

    cherrypy.engine.start()
    cherrypy.engine.block()
    
    d = Daemonizer(cherrypy.engine)
    d.subscribe()  
    
    