#!/usr/bin/env python
import sys
import io
import logging
sys.path.append('/usr/share/artica-postfix/ressources')
import cherrypy
from cherrypy.lib.httputil import parse_query_string
from cherrypy.process.plugins import Daemonizer
from cherrypy.lib.static import serve_file
from cherrypy.process.plugins import PIDFile
from datetime import datetime
from netaddr import IPNetwork, IPAddress
import time
import hashlib
import pwd
import grp
import os
import re
from unix import *
from mysqlclass import *
from ldapclass import *
from activedirectoryclass import *


def http_error_404_hander(status, message, traceback, version):
    SquidMicroHotSpotSSLLanding=GET_INFO_STR("SquidMicroHotSpotSSLLanding")
    if len(SquidMicroHotSpotSSLLanding)==0:
        SquidMicroHotSpotSSLLanding="http://www.bing.com"
        
    page="<head><meta http-equiv='refresh' content='0; url="+SquidMicroHotSpotSSLLanding+"'></head><body></body></html>";
    return page

class RootServer:
    
    def __init__(self):
        logging.basicConfig(format='%(asctime)s [%(levelname)s] %(message)s',filename='/var/log/microhotspot.debug',  filemode='a',level=logging.DEBUG)
        logging.raiseExceptions = False
        self.logging=logging
        self.SquidMicroHotSpotSSLLanding=GET_INFO_STR("SquidMicroHotSpotSSLLanding")
        self.CachePath='/home/artica/microhotspot/Caches'
        self.mac=None
        self.ruleid=0
        self.debug=True
        self.StampFile=""
        mkdir(self.CachePath,0755)
        mkchown(self.CachePath,'squid','squid')
        pass
         
    @cherrypy.expose
    def index(self, **keywords):
        if not "ruleid" in keywords:
            SquidMicroHotSpotSSLLanding=GET_INFO_STR("SquidMicroHotSpotSSLLanding")
            if len(SquidMicroHotSpotSSLLanding)==0: SquidMicroHotSpotSSLLanding="http://www.bing.com"
            page="<head><meta http-equiv='refresh' content='0; url="+SquidMicroHotSpotSSLLanding+"'></head><body></body></html>";
            return page
            
        self.ruleid=keywords["ruleid"]
        content=file_get_contents("/etc/artica-postfix/microhotspot/rules/"+str(self.ruleid)+"/redirect.html")
        content=content.replace("%URL%",self.SquidMicroHotSpotSSLLanding)
        return content
    
    @cherrypy.expose
    def login(self, **keywords):
        CurrentLIC=GET_INFO_INT("CurrentLIC")
        if CurrentLIC==0:
            content="<html><head><body><H1>License ERROR</H1><H2>This feature cannot be used without a valid corporate license.</h2></body></html>"
            return content
            
        filename="/etc/artica-postfix/settings/Daemons/SquidMicroHotSpotNetworks"
        if not os.path.exists(filename):
            content="<html><head><body><H1>Please apply your rules in MicroHotSpot settings</H1><H2>SquidMicroHotSpotNetworks configuration missing</h2></body></html>"
            return content
            
        self.url=keywords["url"]
        self.ipaddr=keywords["ipaddr"]
        self.mac=None
        self.ruleid=0
        self.KeyUser=None
        self.username=""
        self.uid=""
        if "mac" in keywords: self.mac=keywords["mac"]
        if "uid" in keywords:self.uid=keywords["uid"]
        if "ruleid" in keywords:self.ruleid=keywords["ruleid"]
        if "KeyUser" in keywords: self.KeyUser=keywords["KeyUser"]
        if self.mac==None: self.mac=IpToMac(self.ipaddr)
        if self.ruleid==0: self.GetRuleID()
        if self.KeyUser==None: self.KeyUser=self.GetKeyUser()
        if self.debug: cherrypy.log("RuleID: " + str(self.ruleid)+" KeyUser:"+str(self.KeyUser)+" mac:"+str(self.mac),"LOGIN")
        return self.FormFailed("")
    
    def FormFailed(self,Texterror):
        content=self.read_file("index.html")
        if len(content)==0: content="<html><head><body><H1>Please apply your rules in MicroHotSpot settings</H1><H2>Rule ID: "+str(self.ruleid)+" have no content</h2></body></html>"
        content=content.replace("%HIDDENFIELDS%",self.hidden_fields())
        if len(Texterror)>3: Texterror="<div style='color:#A60000;font-size:80%;font-weight:bold;margin-top:10px'>&laquo;"+Texterror+"&raquo;</div>"
        content=content.replace("%ERROR%",Texterror)
        content=content.replace("%username%",self.username)
        if self.debug: cherrypy.log("Output index.html ("+str(len(content))+" bytes)","FORM")
        return content
    
    
    def GetRuleID(self):
        filename="/etc/artica-postfix/settings/Daemons/SquidMicroHotSpotNetworks"
        if not os.path.exists(filename):
            if self.debug: cherrypy.log("GetRuleID(): "+filename+" no such file!","GetRuleID")
            self.ruleid=1
            return 1
        
        with open(filename,"r") as f:
            for txt in f :
                txt=txt.rstrip('\n')
                if len(txt)<5: next
                matches=re.search('(.+):([0-9]+)',txt)
                if not matches: next
                Network=matches.group(1)
                RuleID=int(matches.group(2))
                
                if IPAddress(self.ipaddr) in IPNetwork(Network):
                    if self.debug: cherrypy.log("GetRuleID(): "+self.ipaddr+" matches "+str(self.ruleid),"GetRuleID")
                    self.ruleid=RuleID
                    return RuleID
        
        self.ruleid=1
        return 1
        pass
# ---------------------------------------------------------------------------------------------------------------------------------------------    
    def GetKeyUser(self):
        try:
            if len(self.mac)>5:
                if self.mac=="00:00:00:00:00:00": self.mac=""
        except:
            return self.ipaddr
            
        if len(self.mac)>5: return self.mac
        return self.ipaddr
        pass
# ---------------------------------------------------------------------------------------------------------------------------------------------   
    def read_file(self,filename):
        filepath="/etc/artica-postfix/microhotspot/rules/"+str(self.ruleid)+"/"+filename
        if self.debug: cherrypy.log("open: " + filepath,"I/O")
        if not os.path.exists(filepath): return ""
        with io.open(filepath, "r", encoding="ISO-8859-1") as my_file: return my_file.read()
        pass
    
# ---------------------------------------------------------------------------------------------------------------------------------------------
    def read_int(self,keyfile):
        filepath="/etc/artica-postfix/microhotspot/rules/"+str(self.ruleid)+"/"+keyfile
        if self.debug: cherrypy.log("open: " + filepath,"I/O")
        if not os.path.exists(filepath): return 0
        data=file_get_contents(filepath)
        return strtoint(data)
        pass     
# ---------------------------------------------------------------------------------------------------------------------------------------------
    @cherrypy.expose
    def auth(self, **keywords):
        if "ipaddr" in keywords: self.ipaddr=keywords["ipaddr"]
        if "mac" in keywords: self.mac=keywords["mac"]
        if "username" in keywords: self.uid=keywords["username"]
        if "url" in keywords:
            if is_array(keywords["url"]):
                self.url=keywords["url"][0]
            else:
                self.url=keywords["url"]
             
        if "ruleid" in keywords: self.ruleid=int(keywords["ruleid"])
        if "password" in keywords: self.password=keywords["password"]
        if "KeyUser" in keywords: self.KeyUser=keywords["KeyUser"]
        
        if len(self.password)==0: return self.FormFailed("Authentication failed")
        if len(self.username)==0: return self.FormFailed("Authentication failed")
        if len(self.KeyUser)==0: return self.FormFailed("KeyUser failed")
        if self.ruleid==0: return self.FormFailed("Rule ID failed")    
        
       
        self.StampFile=self.CachePath+"/"+self.KeyUser
        RemoveFile(self.StampFile)
   
        
        authtype=self.read_int("authtype")
        self.incativityperiod=self.read_int("incativityperiod")
        self.maxperiod=self.read_int("maxperiod")
        
        
        if self.debug: cherrypy.log("Rule: " + str(self.ruleid)+" KeyUser = '"+str(self.KeyUser)+"'","AUTH")
        if self.debug: cherrypy.log("Rule: " + str(self.ruleid)+" AUTH TYPE = '"+str(authtype)+"'","AUTH")
        
        content=self.read_file("redirect.html")
        content=content.replace("%URL%",self.url)
        
        if authtype==2:
            if not os.path.exists("/etc/artica-postfix/microhotspot/rules/CurrentAD"):
                content="<html><head><body><H1>Please apply your rules in MicroHotSpot settings</H1><H2>Current Active Directory configuration missing</h2></body></html>"
                return content
            CurrentAD=file_get_contents("/etc/artica-postfix/microhotspot/rules/CurrentAD")
            if self.debug: cherrypy.log("Rule: " + str(self.ruleid)+" AUTH TYPE = 'Current Active Directory ["+str(CurrentAD)+"]'","AUTH")
            ADClass=ActiveDirectory(self.logging)
            ADClass.NoSQL=True
            ADClass.ldap_server=CurrentAD
            ADClass.username=self.username
            ADClass.password=self.password
            if not ADClass.TestBind():
                if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): BIND:"+ self.username+" Error:"+ADClass.ldap_error+" [BAD]","ACTIVEDIRECTORY")
                return self.FormFailed(ADClass.ldap_error)
            self.WriteStampFile()
            return content
        
        if authtype==3:
            CurrentAD=self.read_file("ad_addr")
            if len(CurrentAD)==0:
                content="<html><head><body><H1>Please apply your rules in MicroHotSpot settings</H1><H2>Current Active Directory configuration missing</h2></body></html>"
                return content
            if self.debug: cherrypy.log("Rule: " + str(self.ruleid)+" AUTH TYPE = 'Active Directory ["+str(CurrentAD)+"]'","AUTH")
            ADClass=ActiveDirectory(self.logging)
            ADClass.NoSQL=True
            ADClass.ldap_server=CurrentAD
            ADClass.username=self.username
            ADClass.password=self.password
            if not ADClass.TestBind():
                if self.debug: cherrypy.log("Rule("+str(self.ruleid)+"): BIND:"+ self.username+" Error:"+ADClass.ldap_error+" [BAD]","ACTIVEDIRECTORY")
                return self.FormFailed(ADClass.ldap_error)
            self.WriteStampFile()
            return content            
                
                
        
        
        if authtype==1:
            ldap=CLLDAP(self.logging)
            ldap_password=ldap.GetUserPassword(self.username)
            if ldap_password == self.password:
                 self.WriteStampFile()
                 return content
            
        return self.FormFailed("No Auth method")
        pass
           
# ---------------------------------------------------------------------------------------------------------------------------------------------    
    @cherrypy.expose
    def css(self, **keywords):
        content= file_get_contents("/usr/share/hotspot/"+str(keywords["ruleid"])+"/index.css")
        content=content.replace('img/','images?picture=')
        return content
        pass
# ---------------------------------------------------------------------------------------------------------------------------------------------    
    @cherrypy.expose
    def images(self, **keywords):
        filename=keywords["picture"]
        return serve_file("/usr/share/artica-postfix/img/"+filename, '', "attachment",filename)
        pass
# ---------------------------------------------------------------------------------------------------------------------------------------------
    @cherrypy.expose
    def microhotspot(self, **keywords):
        self.url=keywords["url"]
        self.ipaddr=keywords["ipaddr"]
        self.mac=keywords["mac"]
        self.uid=keywords["username"]
        if is_array(keywords["url"]):
            self.url=keywords["url"][0]
        else:
             self.url=keywords["url"]
             
        self.ruleid=keywords["ruleid"]
        self.username=keywords["username"]
        self.password=keywords["password"]
        self.KeyUser=keywords["KeyUser"]
        return self.auth();
# ---------------------------------------------------------------------------------------------------------------------------------------------
    
    def hidden_fields(self):
        form=[]
        form.append("<input type='hidden' name='url' value='"+self.url+"'>")
        form.append("<input type='hidden' name='ruleid' value='"+str(self.ruleid)+"'>")
        form.append("<input type='hidden' name='ipaddr' value='"+self.ipaddr+"'>")
        form.append("<input type='hidden' name='mac' value='"+str(self.mac)+"'>")
        form.append("<input type='hidden' name='uid' value='"+str(self.uid)+"'>")
        form.append("<input type='hidden' name='KeyUser' value='"+str(self.KeyUser)+"'>")
        return "\n".join(form)
        pass

# ---------------------------------------------------------------------------------------------------------------------------------------------
    def WriteStampFile(self):
        Curtime=datetime.datetime.now()    
        file_put_contents(self.StampFile,str(Curtime)+"|"+str(Curtime)+"|"+str(self.incativityperiod)+"|"+str(self.maxperiod)+"|"+str(self.username) )
        os.chown(self.StampFile,pwd.getpwnam("squid").pw_uid,grp.getgrnam("squid").gr_gid)
        os.chmod(self.StampFile,0755)
# ---------------------------------------------------------------------------------------------------------------------------------------------

    
    def authenticate(self):
        USE_MYSQL=self.GET_INT("USE_MYSQL")
        self.logging.debug("Rule("+str(self.ruleid)+"): USE_MYSQL: "+str(USE_MYSQL))
        md5Password=hashlib.md5(self.password).hexdigest()
        
        
        if USE_MYSQL==1:
            sql="SELECT uid,password,creationtime,ttl,enabled FROM hotspot_members WHERE uid='"+self.username+"' LIMIT 0,1"
            rows=self.q.QUERY_SQL(sql)
            self.logging.debug("Rule("+str(self.ruleid)+"): SQL QUERY: "+str(sql))
            self.logging.debug("Rule("+str(self.ruleid)+"): SQL ROWS: "+str(len(rows)))
            if len(rows)==0:
                return False
            print(rows)
            uid=rows[0][0]
            password=rows[0][1]
            creationtime=rows[0][2]
            ttl=rows[0][3]
            enabled=rows[0][4]
            self.logging.debug("Rule("+str(self.ruleid)+"): SQL Results: "+str(uid)+":"+str(password)+"/"+md5Password)
        
        pass
    
    
    def GET_INT(self,key):
        filename="/usr/share/hotspot/"+str(self.ruleid)+"/"+key
        if not os.path.exists(filename):
            file_put_contents(filename,0)
            return 0
        data=file_get_contents(filename)
        testdata=unicode(data,'utf-8')
        if data == '':
            return 0
        
        if testdata.isnumeric():
            return int(data)
        return 0
        pass
    
    

    def parse_accept_header(self,accept_header):
        accepts = []
        for line in accept_header.replace(' ','').split(','):
            values = line.split(';q=')
            if len(values) > 1:
                try:
                    prio = float(values[1])
                except (ValueError, TypeError):
                    prio = 0.0
            else:
                prio = 1.0
            accepts.append((values[0], prio))
        accepts.sort(key=lambda (l, s): s, reverse=True)
        accept_headers = [l[0] for l in accepts]
        return accept_headers

if __name__ == '__main__':
    

    http_port=GET_INFO_INT("MicroHotSpotPort")
    ssl_port=GET_INFO_INT("MicroHotSpotPortSSL")
    EnableSquidMicroHotSpotSSL=GET_INFO_INT("EnableSquidMicroHotSpotSSL")
    SquidMicroHotSpotSSLPath=GET_INFO_STR("SquidMicroHotSpotSSLPath")
    SquidMicroHotSpotAddr=GET_INFO_STR("SquidMicroHotSpotAddr")
    MicroHotSpotThreads=GET_INFO_INT("MicroHotSpotThreads")
    certificate_path=SquidMicroHotSpotSSLPath+'/server.crt'
    private_key_path=SquidMicroHotSpotSSLPath+'/server.key'
    
    if not os.path.exists(certificate_path):
        print "*********** WARNING "+certificate_path+" doesn't exists ***********"
        EnableSquidMicroHotSpotSSL=0
    
    if not os.path.exists(private_key_path):
        print "*********** WARNING "+private_key_path+" doesn't exists ***********"
        EnableSquidMicroHotSpotSSL=0    
    
    if http_port == 0: http_port=16180
    if ssl_port == 0: ssl_port=16143
    if MicroHotSpotThreads==0: MicroHotSpotThreads=50

    cherrypy.tree.mount(RootServer())
    cherrypy.server.unsubscribe()
    PIDFile(cherrypy.engine, "/var/run/microhotspot.pid").subscribe()
   # cherrypy.config.update({'error_page.404': error_page_404, })

    if EnableSquidMicroHotSpotSSL==1:
        print "SSL is enabled "+certificate_path+" - "+private_key_path
        server1 = cherrypy._cpserver.Server()
        server1.socket_port=ssl_port
        server1._socket_host=SquidMicroHotSpotAddr
        server1.thread_pool=MicroHotSpotThreads
        server1.ssl_module = 'pyopenssl'
        server1.ssl_certificate = certificate_path
        server1.ssl_private_key = private_key_path
        #server1.ssl_certificate_chain = '/home/ubuntu/gd_bundle.crt'
        server1.subscribe()

    print "MicroHotspot: Listen addr: "+SquidMicroHotSpotAddr+":"+str(http_port)
    server2 = cherrypy._cpserver.Server()
    server2.socket_port=http_port
    server2._socket_host=SquidMicroHotSpotAddr
    server2.thread_pool=MicroHotSpotThreads
    server2.subscribe()
    cherrypy.config.update({'error_page.404': http_error_404_hander,})
    cherrypy.engine.start()
    cherrypy.engine.block()
