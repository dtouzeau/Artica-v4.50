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

class WebService(object):
    
    def __init__(self):
        mkdir("/var/log/wsus-http",0755)
        mkdir("/var/run/wsus-http",0755)
        port=GET_INFO_INT("WindowsUpdateHTTPPort")
        if port==0:
            port=18816
        
        cherrypy.config.update({'server.socket_host': '127.0.0.1', })
        cherrypy.config.update({'server.socket_port': port, })
        cherrypy.config.update({'log.access_file': "/var/log/wsus-http/http-server.log", })
        cherrypy.config.update({'log.error_file': "/var/log/wsus-http/http-server.err", })
        PIDFile(cherrypy.engine, "/var/run/wsus-http/http-server.pid").subscribe()
        self.rootdir = GET_INFO_STR("WindowsUpdateCachingDir")
    
        if len(self.rootdir) == 0:
            self.rootdir ="/home/squid/WindowsUpdate"
            
        cherrypy.log("WSUS rootdir: "+self.rootdir)
        
    @cherrypy.expose
    
    def root(self, domain, md5, sourceip,uid,mac):
        query=parse_query_string(cherrypy.request.query_string)
        cherrypy.log('OK root():'+domain+","+md5)
        print query
    root.exposed=True  
        
    @cherrypy.expose    
    def index(self, domain, md5, sourceip,uid,mac):
        query=parse_query_string(cherrypy.request.query_string)
        cherrypy.log('OK index():'+md5+","+domain)
        PathHeader = self.rootdir + '/'+domain+'/'+md5+'.head'
        FileDataPath=self.rootdir  + '/'+ domain+'/'+md5+'.data'
        cherrypy.log('WSUS Request header:'+PathHeader)
        
        if not os.path.exists(PathHeader):
            raise cherrypy.HTTPError(404)
        
        heads=file_get_contents(PathHeader)
        array=heads.split(";")
        content_type=array[0]
        content_size=array[1]
        filename=array[2]
        
        cherrypy.log('WSUS Request data:'+FileDataPath)
        if not os.path.exists(FileDataPath):
            raise cherrypy.HTTPError(404)
        return serve_file(FileDataPath, content_type, "attachment",filename)
    
    index.exposed=True    
        
    @cherrypy.expose    
    def default(self, domain, md5, sourceip,uid,mac):
        cherrypy.log('['+sourceip+'/'+uid+'/'+mac+']: '+domain+'/'+md5)

    default.exposed=True
    
     
            
d = Daemonizer(cherrypy.engine)
d.subscribe()        
cherrypy.quickstart(WebService(), '/')
