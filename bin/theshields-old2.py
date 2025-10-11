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
import traceback as tb
import syslog
from theshieldsservice import *
from categorizeclass import *
from unix import *


class WebService(object):

    def __init__(self):

        HOST = "127.0.0.1"
        PORT = 2004
        self.CategoriesClass=categorize()
        TheShieldsIP = GET_INFO_STR("TheShieldsIP")
        TheShieldsPORT = GET_INFO_INT("TheShieldsPORT")
        try:
            if len(TheShieldsIP) > 3: HOST = TheShieldsIP
            if TheShieldsPORT > 0: PORT = TheShieldsPORT
        except:
            print(tb.format_exc())
            sys.exit(0)

        self.TheShieldDebug = GET_INFO_INT("TheShieldDebug")
        if self.TheShieldDebug == 1: self.debug = True
        TheShieldsThreads=GET_INFO_INT("TheShieldsThreads")
        TheShieldsBackLog=GET_INFO_INT("TheShieldsThreads")
        if TheShieldsThreads<200: TheShieldsThreads=200
        if TheShieldsBackLog < 20: TheShieldsBackLog = 20
        thread_pool=int(round(TheShieldsThreads/2))

        cherrypy.config.update({'server.socket_host': HOST, })
        cherrypy.config.update({'server.socket_port': PORT, })
        cherrypy.config.update({'log.access_file': "", })
        cherrypy.config.update({'log.error_file': "/var/log/theshields-daemon.log", })

        cherrypy.config.update({'server.thread_pool_max': TheShieldsThreads, })
        cherrypy.config.update({'server.thread_pool': thread_pool, })
        cherrypy.config.update({'server.socket_queue_size': TheShieldsBackLog, })
        cherrypy.config.update({'server.protocol_version': "HTTP/1.1", })
        cherrypy.config.update({'tools.caching.on': False, })
        PIDFile(cherrypy.engine, "/var/run/theshields.pid").subscribe()


    @cherrypy.expose
    def root(self,opt):
        cherrypy.log("[/]: %s" % opt, "ENGINE")
        raise cherrypy.HTTPError(404)
        pass

    root.exposed = True
    @cherrypy.expose
    def query(self,ReceivedData=None):
        TheShieldsClass = TheShieldsService(self.CategoriesClass)
        if self.TheShieldDebug == 1: cherrypy.log("[query]: %s" % ReceivedData, "ENGINE")
        try:
            ReceivedData=ReceivedData.decode("utf-8")
            results =  TheShieldsClass.response(ReceivedData)
        except:
            cherrypy.log("[query]: %s ERROR [%s]" % (ReceivedData,tb.format_exc()), "ERROR")
            return tb.format_exc()

        if self.TheShieldDebug == 1: cherrypy.log("[query]: return [%s]" % results, "ENGINE")
        return " %s" % results
        pass



d = Daemonizer(cherrypy.engine)
d.subscribe()
cherrypy.quickstart(WebService(), '/')
