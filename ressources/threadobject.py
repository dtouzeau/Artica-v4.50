#!/usr/bin/env python

import sys
import syslog
sys.path.append('/usr/share/artica-postfix/ressources')

global emergency
global class_http_loaded
global results_all
global isSpeedMode
global categorizeclass_loaded
global theshieldsclient_loaded
global CategorizeClassLoaded
import resource
isSpeedMode=False
class_http_loaded=False
categorizeclass_loaded=False
theshieldsclient_loaded=False
emergency=False
from unix import *
KSRNEnable = GET_INFO_INT("KSRNEnable")
KSRNRemote = GET_INFO_INT("KSRNRemote")
EnableITChart = GET_INFO_INT("EnableITChart")
EnableUfdbGuard = GET_INFO_INT("EnableUfdbGuard")
EnableSquidMicroHotSpot = GET_INFO_INT("EnableSquidMicroHotSpot")
results_all = KSRNEnable + KSRNRemote + EnableITChart + EnableUfdbGuard + EnableSquidMicroHotSpot
if results_all==0: isSpeedMode=True
syslog.openlog("ksrn", syslog.LOG_PID)
Memsize = resource.getrusage(resource.RUSAGE_SELF).ru_maxrss
Memsize = round(Memsize/1024)

try:
    from categorizeclass import *
    CategorizeClassLoaded=categorize()
    categorizeclass_loaded = True
except:
    pass

if KSRNRemote==0:
    try:
        from theshieldsclient import *
        theshieldsclient_loaded=True
    except:
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: [MODULE] theshieldsclient FAILED <%s>" % tb.format_exc())
        emergency=True

if KSRNRemote==1:
    try:
        from classhttp import *
        class_http_loaded=True
    except:
        pass

import syslog
import traceback as tb
import time

class ThreadSrnObject:
    def __init__(self):
        self.SPEEDMODE=False
        global emergency
        global results_all
        global isSpeedMode
        global categorizeclass_loaded
        global theshieldsclient_loaded
        self.catz=None
        self.ShieldsClient=None
        self.emergency=emergency
        self.ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")
        self.KSRNOnlyCategorization = GET_INFO_INT("KSRNOnlyCategorization")
        self.KSRNEnable = GET_INFO_INT("KSRNEnable")
        self.KSRNRemote = GET_INFO_INT("KSRNRemote")
        self.EnableITChart = GET_INFO_INT("EnableITChart")
        self.EnableUfdbGuard = GET_INFO_INT("EnableUfdbGuard")
        self.EnableSquidMicroHotSpot = GET_INFO_INT("EnableSquidMicroHotSpot")
        self.KSRNOnlyCategorization = GET_INFO_INT("KSRNOnlyCategorization")
        self.KSRNClientTimeOut = GET_INFO_INT("KSRNClientTimeOut")

        self.external_uri=""
        if KSRNRemote == 0:
            if not self.emergency: self.catz = categorize()

        self.SPEEDMODE = isSpeedMode
        if categorizeclass_loaded:
            global CategorizeClassLoaded
            self.catz=CategorizeClassLoaded


        if theshieldsclient_loaded:
            self.ShieldsClient = TheShieldsClient(self.catz)
        else:
            self.xsyslog('ERROR: TheShieldsClient() not loaded')

        if self.KSRNRemote==1:
            KSRNRemoteAddr=GET_INFO_STR("KSRNRemoteAddr")
            KSRNRemotePort = GET_INFO_INT("KSRNRemotePort")
            if KSRNRemotePort == 0: KSRNRemotePort=2004
            if self.KSRNClientTimeOut == 0: self.KSRNClientTimeOut=5
            self.external_uri="http://%s:%s" % (KSRNRemoteAddr,KSRNRemotePort)


        self.len_of_uri=len(self.external_uri)
        self.xsyslog('Modules Loaded memory ressource %sMB' % self.MemUsage())

    def xsyslog(self, text):
        syslog.openlog("ksrn", syslog.LOG_PID)
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: %s" % text)

    def MemUsage(self):
        Memsize = resource.getrusage(resource.RUSAGE_SELF).ru_maxrss
        Memsize = round(Memsize / 1024)
        return Memsize

    def force_exit(self,line):
        if line.find('/squid-internal-dynamic/')>10: return True
        if line.find('/squid-internal-static/') > 10: return True
        if line.find('/squid-internal-mgr/')>10: return True
        if line.find('cache_object:/')>0: return True
        return False


    def input_proxy(self,line):
        global theshieldsclient_loaded
        self.stime = time.time()
        LenOfline = len(line)

        if LenOfline == 0:
            if self.ExternalAclFirstDebug == 1: self.xsyslog("ERROR: No data input L.54")
            return self.stdout("0 OK first=ERROR ptime=\n")

        if line[-1] == '\n': line = line[:-1]
        if self.ExternalAclFirstDebug == 1: self.xsyslog("DEBUG: Remote Uri:%s Receive len(%s) <%s>" % (self.len_of_uri, LenOfline, line))
        channel = None
        options = line.split()

        try:
            if options[0].isdigit(): channel = options.pop(0)
        except IndexError:
            self.xsyslog("DEBUG: IndexError on %s" % line)
            return self.stdout("0 OK first=ERROR ptime=\n")

        if self.force_exit(line):
            return self.stdout("%s OK srn=WHITE category=82 category-name=internal clog=cinfo:82-internal; webfilter=pass ptime=\n" % channel)


        if self.len_of_uri > 3:
            if self.ExternalAclFirstDebug == 1: self.xsyslog("DEBUG: * * * Send to Remote * * *")
            return self.SendRemote(channel, line)

        if self.SPEEDMODE:
            sresults = self.speedmode(line)
            return self.stdout(sresults)

        if theshieldsclient_loaded:
            try:
                sitename = self.ShieldsClient.sitename
                sresults = self.ShieldsClient.Process(line)
                return self.stdout(sresults)
            except:
                self.xsyslog("DEBUG: %s" % tb.format_exc())
                return self.stdout("%s OK first=ERROR ptime=\n" % channel)
        self.xsyslog("DEBUG: ShieldsClient Not Loaded!")
        return self.stdout("%s OK first=ERROR ShieldsClient=notloaded ptime=\n" % channel)


    def SendRemote(self,channel,query):
        global class_http_loaded
        if not class_http_loaded:
            if self.ExternalAclFirstDebug == 1: self.xsyslog("[DEBUG] ALERT! class_http_loaded False")
            return self.stdout("%s OK first=ERROR ptime=\n" % channel)


        if  self.ExternalAclFirstDebug ==1 :self.xsyslog("[DEBUG] <%s> to server <%s>" % (query,self.external_uri))
        xbase=base64_encode(query)
        uri_to_test = "%s/proxy/%s" % (self.external_uri,xbase)
        curl=ccurl(uri_to_test)
        try:
            sresults = curl.get()

            if sresults is None:
                self.xsyslog("FATAL!!! HTTP ERROR <%s> <%s> L.133" % (uri_to_test,curl.error))
                return self.stdout("%s OK first=ERROR ptime=\n" % channel)

            if not curl.ok:
                diff = self.TimeExec()
                self.xsyslog("FATAL!!! HTTP ERROR <%s> <%s> L.138" % (uri_to_test,curl.error))
                return self.stdout("%s OK first=ERROR ptime=\n" % (channel))


        except:
            diff = self.TimeExec()
            self.xsyslog("DEBUG: %s" % tb.format_exc())
            return self.stdout("%s OK first=ERROR ptime=\n" % (channel))



        if self.ExternalAclFirstDebug == 1: self.xsyslog("[DEBUG] RESPONSE L.113 <%s>" % sresults)
        return self.stdout("%s\n" % sresults)

    def stdout(self, lineToSend):
        diff = self.TimeExec()
        xtime= "ptime=%s" % diff
        lineToSend=lineToSend.replace("ptime=",xtime)
        if self.ExternalAclFirstDebug == 1:

            self.xsyslog("STDOUT_PROXY [%s] <%s> Memory usage: %s" % (xtime,lineToSend,self.MemUsage()))
        if lineToSend.find("\n")<2: lineToSend="%s\n" % lineToSend
        return lineToSend


    def speedmode(self,line):
        if self.ExternalAclFirstDebug == 1: self.xsyslog("DEBUG: SPEEDMODE Receive [%s]" % line)
        TOKENS = []
        sitename = ""
        ipaddr = ""
        URL = ""
        mac = ""
        WHITE = False

        parsed_array = self.ShieldsClient.ParseLine(line)
        concurrency = parsed_array["concurrency"]

        if "URL" in parsed_array:       URL = parsed_array["URL"]
        if "domain" in parsed_array:    sitename = parsed_array["domain"]
        if "ipaddr" in parsed_array:    ipaddr = parsed_array["ipaddr"]
        if "mac" in parsed_array:       mac = parsed_array["mac"]
        if len(sitename) == 0:
            if len(URL) > 0: sitename = URL
        sitename = sitename.lower()
        sitename = sitename.strip()
        CATEGORY = self.catz.get_category(sitename)
        CATEGORY_NAME = self.catz.category_int_to_string(CATEGORY)

        if not WHITE: WHITE = self.catz.admin_whitelist(sitename)
        if not WHITE:
            if len(mac) > 3:
                WHITE = self.catz.admin_whitelist_mac(mac)
        if not WHITE:
            if len(ipaddr) > 3:
                WHITE = self.catz.admin_whitelist_src(ipaddr)

        if WHITE: TOKENS.append("srn=WHITE rblpass=yes")
        TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s; " % (CATEGORY, CATEGORY_NAME, CATEGORY, CATEGORY_NAME))
        LineToSend = "%s OK %s\n" % (concurrency, " ".join(TOKENS))
        if self.ExternalAclFirstDebug == 1: self.xsyslog("DEBUG: SPEEDMODE Send [%s]" % LineToSend)
        return LineToSend

    def xsyslog(self,text):
        syslog.openlog("ksrn", syslog.LOG_PID)
        syslog.syslog(syslog.LOG_INFO,"[PROXY]: %s" % text)

    def TimeExec(self):
        sockststop = time.time()
        socksdifference = sockststop - self.stime
        return socksdifference
    


