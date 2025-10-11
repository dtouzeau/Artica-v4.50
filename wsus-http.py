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
        cherrypy.config.update({'server.thread_pool': 100, })
        cherrypy.config.update({'server.socket_queue_size': 50, })
        cherrypy.config.update({'server.protocol_version':  "HTTP/1.1", })
        
        cherrypy.server.protocol_version = "HTTP/1.1"
        cherrypy.server.thread_pool=100
        cherrypy.server.socket_queue_size=50
        
        PIDFile(cherrypy.engine, "/var/run/wsus-http/http-server.pid").subscribe()
        self.rootdir = GET_INFO_STR("WindowsUpdateCachingDir")
    
        if len(self.rootdir) == 0:
            self.rootdir ="/home/squid/WindowsUpdate"
            
        cherrypy.log("WSUS rootdir: "+self.rootdir)
        
    @cherrypy.expose
    
    def root(self, domain, md5, sourceip,uid,mac):
        query=parse_query_string(cherrypy.request.query_string)
        raise cherrypy.HTTPError(404)
    root.exposed=True  
        
    @cherrypy.expose    
    def index(self, domain, md5, sourceip,uid,mac):
        query=parse_query_string(cherrypy.request.query_string)
        PathHeader = self.rootdir + '/'+domain+'/'+md5+'.head'
        FileDataPath=self.rootdir  + '/'+ domain+'/'+md5+'.data'
        
        
        if not os.path.exists(PathHeader):
            PathHeaderName=os.path.basename(PathHeader)
            cherrypy.log('['+domain+']WSUS from '+sourceip+'/'+mac+'/'+uid +' Header '+PathHeaderName+' no such file')
            raise cherrypy.HTTPError(404)
        
        heads=file_get_contents(PathHeader)
        
        array=heads.split(";")
        content_type=array[0]
        content_size=array[1]
       
        filename=array[2]
        Unit="Ko"
        SizeKo=int(content_size)/1024
        if SizeKo>1024:
            Unit="Mo"
            SizeKo=int(SizeKo)/1024

        if SizeKo>1024:
            Unit="Go"
            SizeKo=int(SizeKo)/1024
            
        
        
        SizeKo=round(SizeKo,2)
        if not os.path.exists(FileDataPath):
            cherrypy.log('['+domain+']WSUS from '+sourceip+'/'+mac+'/'+uid +' FileData '+filename+' no such file')
            raise cherrypy.HTTPError(404)
        
        RangText=cherrypy.request.headers.get('Range')
        if RangText is not None:
            matches=re.search("bytes=([0-9]+)-([0-9]+)",RangText)
            if matches:
                Number1=int(matches.group(1))
                Number2=int(matches.group(2))
                Reste=Number2-Number1
                UnitNumber1="bytes"
                if Reste>1024:
                    Reste=Reste/1024
                    UnitNumber1="Ko"
                    
                
                if Reste>1024:
                    Reste=Reste/1024
                    UnitNumber1="Mo"
                          
                Reste=round(Reste,2)
                RangText=str(Reste)+""+ UnitNumber1
         
        if RangText==None: RangText= str(SizeKo)+Unit  
        cherrypy.log('WSUS from '+sourceip+'['+mac+']'+filename+' ('+content_type+"/"+str(RangText)+"/"+str(SizeKo)+Unit+')')
        return serve_file(FileDataPath, content_type, None,filename)
    
    index.exposed=True    
        
    @cherrypy.expose    
    def default(self, domain, md5, sourceip,uid,mac):
        cherrypy.log('['+sourceip+'/'+uid+'/'+mac+']: '+domain+'/'+md5)

    default.exposed=True
    
     
            
d = Daemonizer(cherrypy.engine)
d.subscribe()        
cherrypy.quickstart(WebService(), '/')
