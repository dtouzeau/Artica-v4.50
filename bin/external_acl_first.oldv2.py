#!/usr/bin/env python
import os
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import time
import signal
import locale
import syslog
import traceback
import threading
import select
import traceback as tb
from theshieldsclient import *
from categorizeclass import *
from unix import *
from classhttp import *



ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")
syslog.openlog("ksrn", syslog.LOG_PID)
syslog.syslog(syslog.LOG_INFO, "[PROXY]: Starting Client Daemon DEBUG=%s v1.0" % ExternalAclFirstDebug)
ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")
KSRNOnlyCategorization = GET_INFO_INT("KSRNOnlyCategorization")
KSRNEnable = GET_INFO_INT("KSRNEnable")
KSRNRemote = GET_INFO_INT("KSRNRemote")
EnableITChart  = GET_INFO_INT("EnableITChart")
EnableUfdbGuard        = GET_INFO_INT("EnableUfdbGuard")
EnableSquidMicroHotSpot=GET_INFO_INT("EnableSquidMicroHotSpot")
results_all=KSRNEnable+KSRNRemote+EnableITChart+EnableUfdbGuard+EnableSquidMicroHotSpot


MAIN_DELAY = 0.5
JOIN_TIMEOUT = 1.0
DEDUP_TIMEOUT = 0.5

class ClienThread():

    def __init__(self,SPEEDMODE,ShieldsClient,catz,external_uri,KSRNClientTimeOut):
        self._exiting = False
        self._cache = {}
        self.ShieldsClient=ShieldsClient
        self.catz = catz
        self.SPEEDMODE=SPEEDMODE
        self.external_uri=external_uri
        self.KSRNClientTimeOut=KSRNClientTimeOut
        self.stime=0
        self.ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")

    def exit(self):
        self._exiting = True

    def xsyslog(self,text):
        syslog.openlog("ksrn", syslog.LOG_PID)
        syslog.syslog(syslog.LOG_INFO,"[PROXY]: %s" % text)

    def TimeExec(self):
        sockststop = time.time()
        socksdifference = sockststop - self.stime
        return socksdifference



    def stdout(self, lineToSend):
        diff = self.TimeExec()
        xtime= "ptime=%s" % diff
        lineToSend=lineToSend.replace("ptime=",xtime)
        if self.ExternalAclFirstDebug == 1: self.xsyslog("STDOUT_PROXY [%s] <%s>" % (xtime,lineToSend))
        try:
            sys.stdout.write(lineToSend)
            sys.stdout.flush()

        except IOError as e:
            try:
                if e.errno==32:
                    self.xsyslog("ERROR Broken PIPE!")
                else:
                    self.xsyslog("IOError %s" % tb.format_exc())
            except:
                self.xsyslog("Stdout <%s>" % tb.format_exc())
        except:
            self.xsyslog("Stdout <%s>" % tb.format_exc())


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
        TOKENS.append(
            "category=%s category-name=%s clog=cinfo:%s-%s; " % (CATEGORY, CATEGORY_NAME, CATEGORY, CATEGORY_NAME))
        LineToSend = "%s OK %s\n" % (concurrency, " ".join(TOKENS))
        if ExternalAclFirstDebug == 1: self.xsyslog("DEBUG: SPEEDMODE Send [%s]" % LineToSend)
        return LineToSend

    def SendRemote(self,channel,query):
        if  self.ExternalAclFirstDebug ==1 :self.xsyslog("[DEBUG] <%s> to server <%s>" % (query,self.external_uri))
        xbase=base64_encode(query)
        uri_to_test = "%s/proxy/%s" % (self.external_uri,xbase)
        curl=ccurl(uri_to_test)
        try:
            sresults = curl.get()

            if sresults is None:
                self.xsyslog("FATAL!!! HTTP ERROR <%s> <%s> L.133" % (uri_to_test,curl.error))
                self.stdout("%s OK first=ERROR ptime=\n" % (channel))
                return False

            if not curl.ok:
                diff = self.TimeExec()
                self.xsyslog("FATAL!!! HTTP ERROR <%s> <%s> L.138" % (uri_to_test,curl.error))
                self.stdout("%s OK first=ERROR ptime=\n" % (channel))
                return False

        except:
            diff = self.TimeExec()
            self.xsyslog("DEBUG: %s" % tb.format_exc())
            self.stdout("%s OK first=ERROR ptime=\n" % (channel))
            return False


        if self.ExternalAclFirstDebug == 1: self.xsyslog("[DEBUG] RESPONSE L.144 <%s>" % sresults)
        self.stdout("%s\n" % sresults)
        return True



    def force_exit(self,line):
        if line.find('/squid-internal-dynamic/')>10: return True
        if line.find('/squid-internal-static/') > 10: return True
        if line.find('/squid-internal-mgr/')>10: return True
        if line.find('cache_object:/')>0: return True
        return False

    def run(self):
        if  self.ExternalAclFirstDebug ==1 : self.xsyslog("DEBUG: Running loop")
        len_of_uri=len(self.external_uri)
        while not self._exiting:
            if sys.stdin in select.select([sys.stdin], [], [], DEDUP_TIMEOUT)[0]:
                self.stime = time.time()
                line = sys.stdin.readline()
                LenOfline=len(line)

                if LenOfline==0:
                    if  self.ExternalAclFirstDebug ==1 : self.xsyslog("DEBUG: Stopping loop... No data input")
                    self._exiting=True
                    break

                if line[-1] == '\n':line = line[:-1]
                if  self.ExternalAclFirstDebug ==1 : self.xsyslog("DEBUG: Remote Uri:%s Receive len(%s) <%s>" % (len_of_uri,LenOfline,line))
                channel = None
                options = line.split()

                try:
                    if options[0].isdigit(): channel = options.pop(0)
                except IndexError:
                    self.xsyslog("DEBUG: IndexError on %s" % line)
                    self.stdout("0 OK first=ERROR ptime=\n")
                    continue


                if self.force_exit(line):
                    self.stdout("%s OK srn=WHITE category=82 category-name=internal clog=cinfo:82-internal; webfilter=pass ptime=%s\n" % (channel))
                    continue


                if len(self.external_uri)>3:
                    if self.ExternalAclFirstDebug == 1: self.xsyslog("DEBUG: * * * Send to Remote * * *")
                    self.SendRemote(channel,line)
                    continue




                try:
                    if self.SPEEDMODE:
                        sresults=self.speedmode(line)
                        self.stdout(sresults)
                        continue

                    sitename=self.ShieldsClient.sitename
                    sresults=self.ShieldsClient.Process(line)
                    sock_time = "{} seconds".format(diff)
                    if  self.ExternalAclFirstDebug ==1 : self.xsyslog("%s: DEBUG: Return %s time:%s" % (sitename,sresults,sock_time))
                    if int(diff)>2:self.xsyslog("%s: [ALERT] processing took more than 2s (%s)" % (sitename,sock_time))
                    self.stdout(sresults)
                except:
                    self.xsyslog("DEBUG: %s" % tb.format_exc())
                    self.stdout("%s OK first=ERROR ptime=%s\n" % (channel))


        if  self.ExternalAclFirstDebug ==1 : self.xsyslog("DEBUG: run::finished")

class Main(object):
    def __init__(self):
        self._threads = []
        self._exiting = False
        self._reload = False
        self._config = ""
        self.remote_uri=""
        self.ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")
        ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")
        KSRNOnlyCategorization = GET_INFO_INT("KSRNOnlyCategorization")
        KSRNEnable = GET_INFO_INT("KSRNEnable")
        self.KSRNRemote = GET_INFO_INT("KSRNRemote")
        EnableITChart = GET_INFO_INT("EnableITChart")
        EnableUfdbGuard = GET_INFO_INT("EnableUfdbGuard")
        EnableSquidMicroHotSpot = GET_INFO_INT("EnableSquidMicroHotSpot")
        self.KSRNClientTimeOut = GET_INFO_INT("KSRNClientTimeOut")

        if self.KSRNRemote==1:
            KSRNRemoteAddr=GET_INFO_STR("KSRNRemoteAddr")
            KSRNRemotePort = GET_INFO_INT("KSRNRemotePort")
            if KSRNRemotePort == 0: KSRNRemotePort=2004
            if self.KSRNClientTimeOut == 0: self.KSRNClientTimeOut=5
            self.remote_uri="http://%s:%s" % (KSRNRemoteAddr,KSRNRemotePort)


        self.xsyslog("The Shields Enabled = %s" % KSRNEnable)
        self.xsyslog("Use Remote Service = %s" % self.KSRNRemote)
        self.xsyslog("IT Charter enabled = %s" % EnableITChart)
        self.xsyslog("Web Filtering Enabled = %s" % EnableUfdbGuard)
        self.xsyslog("HotSpot Enabled = %s" % EnableUfdbGuard)
        results_all = KSRNEnable + self.KSRNRemote + EnableITChart + EnableUfdbGuard + EnableSquidMicroHotSpot
        self.SPEEDMODE = False
        self.catz = categorize()
        self.ShieldsClient=TheShieldsClient()

        if results_all == 0:
            self.xsyslog("No feature as been enabled, use the max speed mode")
            self.SPEEDMODE = True

        for sig, action in (
            (signal.SIGINT, self.shutdown),
            (signal.SIGQUIT, self.shutdown),
            (signal.SIGTERM, self.shutdown),
            (signal.SIGHUP, lambda s, f: setattr(self, '_reload', True)),
            (signal.SIGPIPE, signal.SIG_IGN),
        ):
            try:
                signal.signal(sig, action)
            except AttributeError:
                pass

    def xsyslog(self,text):
        syslog.openlog("ksrn", syslog.LOG_PID)
        syslog.syslog(syslog.LOG_INFO,"[PROXY]: %s" % text)

    def shutdown(self, sig = None, frame = None):
        self.xsyslog("Shutdown(%s, sig: %s)" % (os.getpid(), sig))
        self._exiting = True
        self.stop_threads()

    def start_threads(self):
        if  self.ExternalAclFirstDebug ==1 : self.xsyslog("DEBUG: start_threads")
        dedup = ClienThread(self.SPEEDMODE,self.ShieldsClient,self.catz,self.remote_uri,self.KSRNClientTimeOut)
        t = threading.Thread(target = dedup.run)
        t.start()
        self._threads.append((dedup, t))



    def stop_threads(self):
        if  self.ExternalAclFirstDebug ==1 : self.xsyslog("DEBUG stop_threads")
        for p, t in self._threads:
            p.exit()
        for p, t in self._threads:
            t.join(timeout = JOIN_TIMEOUT)
        self._threads = []

    def run(self):
        """ main loop """
        ret = 0
        if self.ExternalAclFirstDebug == 1: self.xsyslog("DEBUG: Run/start_threads - Starting PID (%s)" % os.getpid())
        self.start_threads()
        if self.ExternalAclFirstDebug == 1: self.xsyslog("DEBUG: Run/start_threads - Finished")
        return ret


if __name__ == '__main__':
    # set C locale
    locale.setlocale(locale.LC_ALL, 'C')
    os.environ['LANG'] = 'C'
    ret = 0
    try:
        main = Main()
        ret = main.run()
    except SystemExit:
        pass
    except KeyboardInterrupt:
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: terminated by ^C")
        ret = 4
    except:
        exc_type, exc_value, tb = sys.exc_info()
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: Internal error: %s" %
            ''.join(traceback.format_exception(exc_type, exc_value, tb)))
        ret = 8
    sys.exit(ret)