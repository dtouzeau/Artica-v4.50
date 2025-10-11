#!/usr/bin/env python
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import cherrypy
from cherrypy.lib.httputil import parse_query_string
from cherrypy.process.plugins import Daemonizer
from cherrypy.lib.static import serve_file
from cherrypy.process.plugins import PIDFile
import os
import re
from unix import *
from proypacclass import *
import traceback as tb

class WebService(object):
    
    def __init__(self):
        mkdir("/var/log/proxy-pac",755)
        mkdir("/var/run/proxy-pac",755)
        mkdir("/home/squid/proxy_pac_rules",755)
        port=9505
        ListenAddr="127.0.0.1"
        cherrypy.config.update({'server.socket_host': ListenAddr, })
        cherrypy.config.update({'server.socket_port': port, })
        cherrypy.config.update({'log.access_file': "/var/log/proxy-pac/access.log", })
        cherrypy.config.update({'log.error_file': "/var/log/proxy-pac/server.log", })
        cherrypy.config.update({'server.thread_pool': 100, })
        cherrypy.config.update({'server.socket_queue_size': 50, })
        cherrypy.config.update({'server.protocol_version':  "HTTP/1.1", })
        cherrypy.config.update({'tools.caching.on': False, })

        cherrypy.log("[127.0.0.1][INFO]: Proxy Pac builder v2.2 Listen: "+ListenAddr+":"+str(port),'ENGINE')
        
        cherrypy.server.protocol_version = "HTTP/1.1"
        cherrypy.server.thread_pool=20
        cherrypy.server.socket_queue_size=50
        
        PIDFile(cherrypy.engine, "/var/run/proxy-pac/http-server.pid").subscribe()
        self.rootdir ="/home/squid/proxy_pac_rules"
        self.UserAgent=""
        self.HTTP_X_REAL_IP=""
        self.HTTP_X_FORWARDED_FOR=""
        self.remote_ip=""
        self.PacClass=PROXYPAC()
        self.PacClass.cherrypy=cherrypy
        self.ProxyPacDebug=GET_INFO_INT("ProxyPacDebug")

        if self.ProxyPacDebug==1:
            cherrypy.log("[127.0.0.1][DEBUG]: PAC Service in DEBUG MODE", 'ENGINE')


        cherrypy.log("[127.0.0.1][INFO]: PAC rootdir: "+self.rootdir,'ENGINE')
        
    @cherrypy.expose
    
    def root(self):
        raise cherrypy.HTTPError(404)
        pass
    
    root.exposed=True
    
    @cherrypy.expose    
    def wpad_dat(self):
        self.GetInfos()
        content=self.PacClass.PackThis(self.UserAgent,self.remote_ip)
        self.AddHeaders()
        return content
        pass
        
    @cherrypy.expose    
    def index(self):
        self.GetInfos()
        if (self.UserAgent.find('Monit/5') != -1):
            self.Add200()
            return ""
        content=self.PacClass.PackThis(self.UserAgent,self.remote_ip)
        self.AddHeaders()
        return content
        pass

    @cherrypy.expose    
    def proxy_pac(self):
        self.GetInfos()
        if (self.UserAgent.find('Monit/5') != -1):
            self.Add200()
            return ""
        content=self.PacClass.PackThis(self.UserAgent,self.remote_ip)
        self.AddHeaders()
        return content
        pass

    @cherrypy.expose
    def proxy_pa(self):
        self.GetInfos()
        if (self.UserAgent.find('Monit/5') != -1):
            self.Add200()
            return ""
        content=self.PacClass.PackThis(self.UserAgent,self.remote_ip)
        self.AddHeaders()
        return content
        pass
    
    @cherrypy.expose 
    def wspad_dat(self):
        self.GetInfos()
        if (self.UserAgent.find('Monit/5') != -1):
            self.Add200()
            return ""
        content=self.PacClass.PackThis(self.UserAgent,self.remote_ip)
        self.AddHeaders()
        return content
        pass

    @cherrypy.expose
    def wpad_dat(self):
        self.GetInfos()
        if (self.UserAgent.find('Monit/5') != -1):
            self.Add200()
            return ""
        content=self.PacClass.PackThis(self.UserAgent,self.remote_ip)
        self.AddHeaders()
        return content
        pass

    @cherrypy.expose
    def wpad_da(self):
        self.GetInfos()
        if (self.UserAgent.find('Monit/5') != -1):
            self.Add200()
            return ""
        content=self.PacClass.PackThis(self.UserAgent,self.remote_ip)
        self.AddHeaders()
        return content
        pass
    
    def AddHeaders(self):
        cherrypy.response.headers['Content-Type']= 'application/x-ns-proxy-autoconfig'
        cherrypy.response.headers['Content-Transfer-Encoding']='binary'
        cherrypy.response.headers['Content-Disposition']='attachment; filename="proxy.pac"'
        pass

    def Add200(self):
        cherrypy.response.headers['Content-Type']= 'text/html'
        pass
    
    def GetInfos(self):
        self.UserAgent = ""
        self.HTTP_X_REAL_IP = ""
        self.HTTP_X_FORWARDED_FOR = ""
        self.remote_ip = ""
        self.PacClass = PROXYPAC()
        self.PacClass.cherrypy = cherrypy

        if self.ProxyPacDebug==1:
            cherrypy.log("[127.0.0.1][DEBUG]: Retrieve infos", 'ENGINE')

        if self.ProxyPacDebug == 1:
            for key in cherrypy.request.headers:
                cherrypy.log("[127.0.0.1][DEBUG]: Head '"+key+"'" + cherrypy.request.headers[key],"ENGINE")


        try:
            self.remote_ip = cherrypy.request.headers["Remote-Addr"]
        except:
            cherrypy.log("[127.0.0.1][WARNING]: cherrypy.request.remote_addr Failed!")
            cherrypy.log(tb.format_exc())
        
        try:
            if self.ProxyPacDebug == 1: cherrypy.log("[127.0.0.1][DEBUG]: Header User-Agent = '"+cherrypy.request.headers["User-Agent"]+"'","ENGINE")
            self.UserAgent = cherrypy.request.headers["User-Agent"]
        except:
            cherrypy.log("[127.0.0.1][WARNING]: Unable to find the UserAgent in headers...","PAC",logging.DEBUG)
            
        try:
            self.HTTP_X_FORWARDED_FOR = cherrypy.request.headers["HTTP_X_FORWARDED_FOR"]
        except:
            cherrypy.log("[127.0.0.1][WARNING]: Unable to find the HTTP_X_FORWARDED_FOR in headers...","PAC",logging.DEBUG)
            
        try:
            self.HTTP_X_REAL_IP = cherrypy.request.headers["HTTP_X_REAL_IP"]
        except:
            cherrypy.log("[127.0.0.1][WARNING]: Unable to find the HTTP_X_REAL_IP in headers...","PAC",logging.DEBUG)

        if 'X-Real-Ip' in cherrypy.request.headers:
            self.HTTP_X_REAL_IP = cherrypy.request.headers['X-Real-Ip']

        if 'X-Forwarded-For' in cherrypy.request.headers:
            self.HTTP_X_FORWARDED_FOR = cherrypy.request.headers["X-Forwarded-For"]


        # cherrypy.log("HTTP_X_FORWARDED_FOR: " + self.HTTP_X_FORWARDED_FOR + "[" + self.UserAgent + "]")
        # cherrypy.log("HTTP_X_REAL_IP: " + self.HTTP_X_REAL_IP + "[" + self.UserAgent + "]")
        if(len(self.HTTP_X_FORWARDED_FOR)>3): self.remote_ip=self.HTTP_X_FORWARDED_FOR
        if(len(self.HTTP_X_REAL_IP)>3): self.remote_ip=self.HTTP_X_REAL_IP

        if (self.UserAgent.find('Monit/5') != -1):
            if self.ProxyPacDebug == 1: cherrypy.log(
                "[127.0.0.1][DEBUG]: Will not serve proxy.pac for local monitor, Aborting'", "ENGINE")
            return True

        cherrypy.log("["+self.remote_ip+"][INFO]: Connexion with UserAgent:[" + self.UserAgent + "]", 'CONNEX')
        
        
    
    
    index.exposed=True    
        
    @cherrypy.expose    
    def default(self):
        self.GetInfos()
        pass


    default.exposed=True
    
     
          
d = Daemonizer(cherrypy.engine)
d.subscribe()        
cherrypy.quickstart(WebService(), '/')
