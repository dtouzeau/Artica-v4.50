#!/usr/bin/env python
import os
import sys
sys.path.append('/usr/share/artica-postfix/ressources')
import time
import syslog
import traceback as tb
from unix import *

syslog.openlog("ksrn", syslog.LOG_PID)
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
syslog.syslog(syslog.LOG_INFO,"[PROXY]: FEATURE MODE = %s" % results_all)

SPEEDMODE=False

if results_all==0:
    syslog.syslog(syslog.LOG_INFO,"[PROXY]: No feature as been enabled, use the max speed mode")
    SPEEDMODE=True


try:
    from theshieldsclient import *
    ShieldsClient = TheShieldsClient()
    moduleloaded = True
except:
    syslog.syslog(syslog.LOG_INFO, "[PROXY]: Starting Client Error Loading TheShields Client %s !!!" % tb.format_exc())

if SPEEDMODE:
    try:
        from categorizeclass import *
        catz = categorize()
        moduleloaded = True
    except:
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: Starting Client Error Loading Categorize Client %s !!!" % tb.format_exc())


syslog.syslog(syslog.LOG_INFO, "[PROXY]: Starting Client Daemon SpeedMode:%s DEBUG=%s PID %s v2.5" % (SPEEDMODE,ExternalAclFirstDebug,os.getpid()))


while True:
    channel=0
    line = sys.stdin.readline()
    line = line.strip()
    size = len(line)
    LenOfline = len(line)

    if LenOfline == 0:
        if ExternalAclFirstDebug == 1: syslog.syslog(syslog.LOG_INFO, '[PROXY]: Stopping loop... No data input')
        break

    try:
        if line[-1] == '\n': line = line[:-1]
    except IndexError:
        syslog.syslog(syslog.LOG_INFO, "[PROXY]: ERROR IndexError: string [%s] index out of range" % line)

    options = line.split()

    try:
        if options[0].isdigit(): channel = options.pop(0)
        if not moduleloaded:
            syslog.syslog(syslog.LOG_INFO, "[PROXY]: ERROR NO MODULE LOADED!" % line)
            sys.stdout.write("%s OK first=ERROR\n" % channel)
            sys.stdout.flush()
            continue
    except IndexError:
        syslog.syslog(syslog.LOG_INFO, '[PROXY]: IndexError on %s' % line)
        sys.stdout.write("0 OK first=ERROR\n")
        sys.stdout.flush()
        continue

    if line.find("/squid-internal-dynamic") > 5:
        if ExternalAclFirstDebug == 1: syslog.syslog(syslog.LOG_INFO, "[PROXY]: squid-internal-dynamic in [%s]" % line)
        sys.stdout.write("%s OK\n" % channel)
        sys.stdout.flush()


    if SPEEDMODE:
        try:
            if ExternalAclFirstDebug == 1: syslog.syslog(syslog.LOG_INFO, "[PROXY]: SPEEDMODE Receive [%s]" % line)
            TOKENS=[]
            sitename=""
            ipaddr=""
            URL=""
            mac=""
            WHITE=False
            parsed_array=ShieldsClient.ParseLine(line)
            concurrency = parsed_array["concurrency"]
            if "URL" in parsed_array:       URL = parsed_array["URL"]
            if "domain" in parsed_array:    sitename = parsed_array["domain"]
            if "ipaddr" in parsed_array:    ipaddr = parsed_array["ipaddr"]
            if "mac" in parsed_array:       mac = parsed_array["mac"]
            if len(sitename) == 0:
                if len(URL) > 0: sitename = URL
            sitename        = sitename.lower()
            sitename        = sitename.strip()
            CATEGORY        = catz.get_category(sitename)
            CATEGORY_NAME   = catz.category_int_to_string(CATEGORY)

            if not WHITE: WHITE= catz.admin_whitelist(sitename)
            if not WHITE:
                if len(mac) > 3:
                    WHITE=catz.admin_whitelist_mac(mac)
            if not WHITE:
                if len(ipaddr) > 3:
                    WHITE = catz.admin_whitelist_src(ipaddr)

            if WHITE: TOKENS.append("srn=WHITE rblpass=yes")
            TOKENS.append("category=%s category-name=%s clog=cinfo:%s-%s; " % (CATEGORY, CATEGORY_NAME, CATEGORY, CATEGORY_NAME))
            LineToSend="%s OK %s\n" % (concurrency," ".join(TOKENS))
            if ExternalAclFirstDebug == 1: syslog.syslog(syslog.LOG_INFO, "[PROXY]: SPEEDMODE Send [%s]" % LineToSend)
            sys.stdout.write(LineToSend)
            sys.stdout.flush()
            continue
        except:
            syslog.syslog(syslog.LOG_INFO, '[PROXY]: SPEEDMODE %s' % tb.format_exc())
            sys.stdout.write("%s OK first=ERROR\n" % channel)
            sys.stdout.flush()
            continue

    if ExternalAclFirstDebug == 1: syslog.syslog(syslog.LOG_INFO, "[PROXY]: Receive [%s]" % line)
    try:
        sresults = ShieldsClient.Process(line)
    except:
        syslog.syslog(syslog.LOG_INFO, '[PROXY]: %s' % tb.format_exc())
        sys.stdout.write("%s OK first=ERROR\n" % channel)
        sys.stdout.flush()
        continue

    try:
        if ExternalAclFirstDebug == 1: syslog.syslog(syslog.LOG_INFO, '[PROXY]: Sends [%s]' % sresults)
        sys.stdout.write(sresults)
        sys.stdout.flush()
        continue

    except IOError as e:
        if exc.errno == 32:
            syslog.syslog(syslog.LOG_INFO, "[PROXY]: Error Broken PIPE!")
        else:
            syslog.syslog(syslog.LOG_INFO, "[PROXY]: IOError %s" % tb.format_exc())
    except:
        syslog.syslog(syslog.LOG_INFO, '[PROXY]: %s' % tb.format_exc())
        sys.stdout.write(sresults)
        sys.stdout.flush()
        continue
syslog.syslog(syslog.LOG_INFO, "[PROXY]: Stopping PID (%s)" % os.getpid())
