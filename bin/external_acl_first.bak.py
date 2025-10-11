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

syslog.syslog(syslog.LOG_INFO,"[PROXY]: The Shields Enabled = %s" % KSRNEnable)
syslog.syslog(syslog.LOG_INFO,"[PROXY]: Use Remote Service = %s" % KSRNRemote)
syslog.syslog(syslog.LOG_INFO,"[PROXY]: IT Charter enabled = %s" % EnableITChart)
syslog.syslog(syslog.LOG_INFO,"[PROXY]: Web Filtering Enabled = %s" % EnableUfdbGuard)
syslog.syslog(syslog.LOG_INFO,"[PROXY]: HotSpot Enabled = %s" % EnableUfdbGuard)
results_all=KSRNEnable+KSRNRemote+EnableITChart+EnableUfdbGuard+EnableSquidMicroHotSpot


MAIN_DELAY = 0.5
JOIN_TIMEOUT = 1.0
DEDUP_TIMEOUT = 0.5

class ClienThread():

    def __init__(self,ClientClass):
        self._exiting = False
        self._cache = {}
        self.ShieldsClient=ClientClass
        self.ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")

    def exit(self):
        self._exiting = True

    def stdout(self, lineToSend):
        try:
            sys.stdout.write(lineToSend)
            sys.stdout.flush()

        except IOError as e:
            if exc.errno==32:
                syslog.syslog(syslog.LOG_INFO, "[PROXY]: Error Broken PIPE!")
            else:
                syslog.syslog(syslog.LOG_INFO, "[PROXY]: IOError %s" % tb.format_exc())
        except:
            syslog.syslog(syslog.LOG_INFO, "[PROXY]: stdout %s" % tb.format_exc())


    def run(self):
        if  self.ExternalAclFirstDebug ==1 : syslog.syslog(syslog.LOG_INFO, '[PROXY]: Running loop')
        while not self._exiting:
            if sys.stdin in select.select([sys.stdin], [], [], DEDUP_TIMEOUT)[0]:
                line = sys.stdin.readline()
                LenOfline=len(line)

                if LenOfline==0:
                    if  self.ExternalAclFirstDebug ==1 : syslog.syslog(syslog.LOG_INFO, '[PROXY]: Stopping loop... No data input')
                    self._exiting=True
                    break

                if line[-1] == '\n':line = line[:-1]
                if  self.ExternalAclFirstDebug ==1 : syslog.syslog(syslog.LOG_INFO, '[PROXY]: Receive len(%s) <%s>' % (LenOfline,line))
                channel = None
                options = line.split()

                try:
                    if options[0].isdigit(): channel = options.pop(0)
                except IndexError:
                    syslog.syslog(syslog.LOG_INFO, '[PROXY]: IndexError on %s' % line)
                    self.stdout("0 OK first=ERROR\n")
                    continue

                try:
                    if self.ExternalAclFirstDebug == 1: syslog.syslog(syslog.LOG_INFO, "[PROXY]: Sends [%s]" % line)
                    sresults=self.ShieldsClient.Process(line)
                    if  self.ExternalAclFirstDebug ==1 : syslog.syslog(syslog.LOG_INFO, "[PROXY]: Return %s" % sresults)
                    self.stdout(sresults)
                except:
                    syslog.syslog(syslog.LOG_INFO, '[PROXY]: %s' % tb.format_exc())
                    self.stdout("%s OK first=ERROR\n" % channel)


        if  self.ExternalAclFirstDebug ==1 : syslog.syslog(syslog.LOG_INFO, '[PROXY]: run::finished')

class Main(object):
    def __init__(self):
        self._threads = []
        self._exiting = False
        self._reload = False
        self._config = ""
        self.ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")
        ExternalAclFirstDebug = GET_INFO_INT("ExternalAclFirstDebug")
        KSRNOnlyCategorization = GET_INFO_INT("KSRNOnlyCategorization")
        KSRNEnable = GET_INFO_INT("KSRNEnable")
        KSRNRemote = GET_INFO_INT("KSRNRemote")
        EnableITChart = GET_INFO_INT("EnableITChart")
        EnableUfdbGuard = GET_INFO_INT("EnableUfdbGuard")
        EnableSquidMicroHotSpot = GET_INFO_INT("EnableSquidMicroHotSpot")

        syslog.syslog(syslog.LOG_INFO, "[PROXY]: The Shields Enabled = %s" % KSRNEnable)
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: Use Remote Service = %s" % KSRNRemote)
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: IT Charter enabled = %s" % EnableITChart)
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: Web Filtering Enabled = %s" % EnableUfdbGuard)
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: HotSpot Enabled = %s" % EnableUfdbGuard)
        results_all = KSRNEnable + KSRNRemote + EnableITChart + EnableUfdbGuard + EnableSquidMicroHotSpot
        self.SPEEDMODE = False

        if results_all == 0:
            syslog.syslog(syslog.LOG_INFO, "[PROXY]: No feature as been enabled, use the max speed mode")
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



    def shutdown(self, sig = None, frame = None):
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: Shutdown(%s, sig: %s)" % (os.getpid(), sig))
        self._exiting = True
        self.stop_threads()

    def start_threads(self):
        if  self.ExternalAclFirstDebug ==1 : syslog.syslog(syslog.LOG_INFO, "[PROXY]: start_threads")
        # dedup thread
        ShieldsClient=TheShieldsClient()
        dedup = ClienThread(ShieldsClient)
        t = threading.Thread(target = dedup.run)
        t.start()
        self._threads.append((dedup, t))



    def stop_threads(self):
        if  self.ExternalAclFirstDebug ==1 : syslog.syslog(syslog.LOG_INFO, "[PROXY]: stop_threads")
        for p, t in self._threads:
            p.exit()
        for p, t in self._threads:
            t.join(timeout = JOIN_TIMEOUT)
        self._threads = []

    def run(self):
        """ main loop """
        ret = 0
        if self.ExternalAclFirstDebug == 1: syslog.syslog(syslog.LOG_INFO, "[PROXY]: Running (%s)" % os.getpid())
        self.start_threads()
        if self.ExternalAclFirstDebug == 1: syslog.syslog(syslog.LOG_INFO, "[PROXY]: Run:: finished")
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