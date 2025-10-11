#!/usr/bin/env python
import sys
import syslog
sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/lib/python2.7/dist-packages')
import os
import re
import traceback as tb
import syslog
global error
global CategoriesClass
global ShieldsClient
error=""
try:
    from theshieldsclient import *
    from theshieldsservice import *
    from categorizeclass import *
    from unix import *
    from classgeoip2free import *
    from phpserialize import serialize, unserialize
except:
    error=tb.format_exc()
    pass

version="44.0"
CategoriesClass=categorize()
ShieldsClient=TheShieldsClient(CategoriesClass)

def application(env, start_response):
    global error
    global CategoriesClass
    global ShieldsClient
    TheShieldDebug=0
    Debug=False;
    if len(error)>0: xsyslog(error)

    try:
        TheShieldDebug = GET_INFO_INT("TheShieldDebug")
    except:
        xsyslog(tb.format_exc())
        start_response('500 Error', [('Content-Type', 'text/html')])
        return [tb.format_exc()]

    QUERY_STRING=env["QUERY_STRING"]
    REQUEST_URI=env["REQUEST_URI"]
    REMOTE_ADDR=env["REMOTE_ADDR"]
    if TheShieldDebug == 1: Debug=True

    if len(REQUEST_URI) <3:
        start_response('200 OK', [('Content-Type', 'text/html')])
        return [b'Hey if your monit, i`m OK!']

    if REQUEST_URI.startswith('/geo/'):
        matches = re.search("^\/geo\/(.+?)\/(.+)", REQUEST_URI)
        src_ip=matches.group(1)
        geolist=matches.group(2)
        geoipC = geoip2free(Debug)
        if not geoipC.operate(self.src_ip):
            start_response('500 Error', [('Content-Type', 'text/txt')])
            return [geoipC.error]

        CountryCode = geoipC.iso_code
        CountryCode = CountryCode.upper()
        tb = geolist.split("-")
        for index in tb:
            if len(index)<2: continue
            index=index.upper()
            if index == CountryCode: return [b'TRUE']

        return [b'FALSE']

    if REQUEST_URI.startswith('/filtering/'):
        matches = re.search("^\/filtering\/(.+?)\/(.+)", REQUEST_URI)
        if not matches:
            start_response('500 Error', [('Content-Type', 'text/txt')])
            return [b'Wrong URL %s L.53' % REQUEST_URI]

        domain = matches.group(1)
        if domain.endswith('.'):
            matches2 = re.search('^(.+?)\.$', domain)
            domain = matches2.group(1)


        CLIENT_IP = matches.group(2)
        if TheShieldDebug == 1: xsyslog("Webfiltering: %s --> %s L.58" % (CLIENT_IP,domain))

        PROXY_URL="http://%s" % domain
        CLIENT_HOSTNAME=CLIENT_IP
        CLIENT_MAC = "00:00:00:00:00:00"
        PROXY_IP = "127.0.0.1"
        PROXY_PORT = 3128
        try:
            if TheShieldDebug == 1: ShieldsClient.debug=True
            results=ShieldsClient.ufdbguard_client(0,PROXY_URL,PROXY_URL,domain,CLIENT_IP,CLIENT_HOSTNAME,CLIENT_MAC,PROXY_IP,PROXY_PORT)
            if TheShieldDebug == 1: xsyslog("Webfiltering: %s --> %s [%s] L.66" % (CLIENT_IP, domain,results))
        except:
            start_response('500 Error', [('Content-Type', 'text/txt')])
            return [ tb.format_exc()]

        start_response('200 OK', [('Content-Type', 'text/html')])
        if len(results)>5:
            if TheShieldDebug == 1: xsyslog("result: %s return webfiltering=<%s> L.58" % (domain, results))
            return [b'TRUE']

        if TheShieldDebug == 1: xsyslog("result: %s return webfiltering=<%s> L.58" % (domain, results))
        return [b'FALSE']


    if REQUEST_URI.startswith('/theshields/'):
        matches = re.search("^\/theshields\/(.+)", REQUEST_URI)
        if not matches:
            start_response('500 Error', [('Content-Type', 'text/txt')])
            return [b'Wrong URL']

        domain=matches.group(1)

        domain = matches.group(1)
        if domain.endswith('.'):
            matches2 = re.search('^(.+?)\.$', domain)
            domain = matches2.group(1)

        try:
            ShieldsClient.sitename=domain
            results=ShieldsClient.Process_the_shields()
        except:
            start_response('500 Error', [('Content-Type', 'text/txt')])
            xsyslog("THESHIELDS: %s Unable to cast <%s> L.105" % (domain))
            return [b'FALSE']


        if TheShieldDebug == 1: xsyslog("result: %s return <%s> L.108" % (domain,results))

        if len(results)< 5:
            start_response('500 Error', [('Content-Type', 'text/txt')])
            if self.debug: xsyslog("%s ERROR LEN ARRAY" % log_prefix)
            return [b'FALSE']

        try:
            result = unserialize(results)
        except:
            start_response('500 Error', [('Content-Type', 'text/txt')])
            xsyslog("%s: [ERROR]: Unserialize error L.509 " % sitename)
            xsyslog("%s: [ERROR]: L.435 Data was [%s]" % (sitename,results))
            return [b'FALSE']

        if len(ACTION) == 0:
            start_response('500 Error', [('Content-Type', 'text/txt')])
            return [b'FALSE']

        start_response('200 OK', [('Content-Type', 'text/html')])
        ACTION = result["ACTION"]
        if ACTION == "WHITELIST": return [b'FALSE']
        if ACTION == "PASS": return [b'FALSE']
        if ACTION == "WHITE": return [b'FALSE']
        return [b'TRUE']

    if REQUEST_URI.startswith('/category/'):
        matches = re.search("^\/category\/(.+?)\/(.+)", REQUEST_URI)
        if not matches:
            start_response('500 Error', [('Content-Type', 'text/txt')])
            return [b'Wrong URL']

        domain=matches.group(1)

        domain = matches.group(1)
        if domain.endswith('.'):
            matches2 = re.search('^(.+?)\.$', domain)
            domain = matches2.group(1)


        start_response('200 OK', [('Content-Type', 'text/html')])
        try:
            category=int(CategoriesClass.get_category(domain))
        except:
            xsyslog("CATEGORY_QUERY: %s Unable to cast <%s> L.61" % (domain, category))
            return [b'FALSE']

        if TheShieldDebug == 1: xsyslog("result: %s return category=<%s> L.58" % (domain,category))

        if category==0:
            if TheShieldDebug == 1: xsyslog("result: %s return result=<%s> L.58" % (domain, "FALSE"))
            return [b'FALSE']

        Checks=matches.group(2)

        tb=Checks.split("-")
        for index in tb:
            try:
                category_int=int(index)
            except:
                if TheShieldDebug == 1: xsyslog("result: %s int(%s) -> ERROR L.70" % (domain, index))
                continue

            if category_int == category:
                return [b'TRUE']

        return [b'FALSE']

    if REQUEST_URI.startswith('/encoded/'):
        if TheShieldDebug == 1: xsyslog("OK <%s> L.60" % "ENCODED PART")
        matches=re.search("^\/encoded\/(.+)",REQUEST_URI)
        if matches:
            unencoded=base64_decode(matches.group(1))
            if TheShieldDebug==1: xsyslog("OK <%s> L.60" % unencoded)
            try:
               TheShieldsClass = TheShieldsService(CategoriesClass)
               results=TheShieldsClass.response(unencoded)
               if TheShieldDebug==1: xsyslog("result: %s L.56" % results)
               start_response('200 OK', [('Content-Type','text/html')])
               return [results]
            except:
                xsyslog("ERROR <%s> L.60" % tb.format_exc())
                start_response('500 Error', [('Content-Type', 'text/txt')])
                return [tb.format_exc()]

    if REQUEST_URI.startswith('/query/'):
        matches=re.search("^\/query\/(.+)",REQUEST_URI)
        if matches:
            unencoded=matches.group(1)
            try:
               TheShieldsClass = TheShieldsService(CategoriesClass)
               results=TheShieldsClass.response(unencoded)
               if TheShieldDebug==1: xsyslog("result: %s L.56" % results)
               start_response('200 OK', [('Content-Type','text/html')])
               return [results]
            except:
                xsyslog("ERROR <%s> L.60" % tb.format_exc())
                start_response('500 Error', [('Content-Type', 'text/txt')])
                return [tb.format_exc()]

    if REQUEST_URI.startswith('/proxy/'):
        try:
            matches = re.search("^\/proxy\/(.+)", REQUEST_URI)
            unencoded = base64_decode(matches.group(1))
            if TheShieldDebug == 1: xsyslog("Send Query: <%s> L.70" % unencoded)
            TheShieldsClass = TheShieldsService(CategoriesClass)
            results = ShieldsClient.Process(unencoded)
            if len(results)<3: xsyslog("ERROR !!!: <%s> L.68 query=</proxy/%>" % (results,matches.group(1)))
            if TheShieldDebug == 1: xsyslog("result: %s L.70" % results)
            start_response('200 OK', [('Content-Type', 'text/html')])

            return [results]
        except:
            xsyslog("ERROR <%s> L.74" % tb.format_exc())
            start_response('500 Error', [('Content-Type', 'text/txt')])
            return [tb.format_exc()]





    start_response('407 Permission Denied', [('Content-Type','text/html')])
    return [b"Bad request"]


def xsyslog(text):
    sysDaemon=syslog
    sysDaemon.openlog("ksrn", syslog.LOG_PID)
    sysDaemon.syslog(syslog.LOG_INFO, "[SERVER]: %s" % text)



