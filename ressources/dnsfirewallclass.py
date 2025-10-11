#!/usr/bin/env python
import sys

sys.path.append('/usr/share/artica-postfix/ressources')
sys.path.append('/usr/share/artica-postfix/bin')
import re
import random
import os
import traceback
import base64

from datetime import datetime
from datetime import timedelta
from time import mktime
from time import gmtime
from time import time

from goldlic import *
from unix import *
from adclass import *
from classgeoip2free import *
from threadobject import *

import sqlite3
import hashlib
import syslog
import socket
from classartmem import *
from inspect import currentframe
from pprint import pprint
from phpserialize import serialize, unserialize
from netaddr import IPNetwork, IPAddress
import traceback as tb
import dns.resolver


class dnsfw:
    def __init__(self, DEBUG, OUT=False):
        self.ThreadP = ThreadSrnObject()
        self.version="4.8"
        self.acls_objects = None
        self.DEBUG = DEBUG
        self.OUT = OUT
        self.unbound_log= ""
        self.iso_code_src=""
        self.EnableGeoipUpdate = GET_INFO_INT("EnableGeoipUpdate")

        self.LICENSE = False
        self.SRN_ACTION=""
        self.SHIELDS_ERROR=False
        self.isArticaLicense = 0
        self.src_ip = "0.0.0.0"
        self.returned_ip = "0.0.0.0"
        self.qtype = 0
        self.rtype=""
        self.intercept_address = ""
        self.intercept_host = ""
        self.domainname = ""
        self.DebugTool=False
        self.logdomain = ""
        self.rulename = ""
        self.action = 0
        self.redirector = ""
        self.reply = ""
        self.ttl = 0
        self.DIR = "IN"
        self.DNSMessage = None
        self.dns_query_results = None
        self.invalidateQueryInCache = None
        self.storeQueryInCache = None
        self.RR_CLASS_IN = None
        self.PKT_QR = None
        self.PKT_RA = None
        self.RR_TYPE_A = None
        self.blockv6 = False
        self.log_info = None
        self.qstate = None
        self.category_id = 0
        self.category_name=""
        self.virtual_user=""
        self.array_logs={}
        self.zRules={}
        self.INTERNAL_LOGS = []
        self.nameservers = []
        self.EnableipV6 = GET_INFO_INT("EnableipV6")
        self.logdir="/home/artica/temp-dnsfirewall"
        self.DNSFireWallVerbose = GET_INFO_INT("DNSFireWallVerbose")
        self.KSRNClientCacheTime= GET_INFO_INT("KSRNClientCacheTime")
        self.DisableDNSFWLogRules = GET_INFO_INT("DisableDNSFWLogRules")

        self.DNSFireWallDefaultReply = GET_INFO_STR("DNSFireWallDefaultReply")
        self.DNSFireWallDefaultTTL = GET_INFO_INT("DNSFireWallDefaultTTL")
        if len(self.DNSFireWallDefaultReply)<3: self.DNSFireWallDefaultReply="0.0.0.0"
        if self.DNSFireWallDefaultTTL == 0: self.DNSFireWallDefaultTTL=1800

        self.MAIN_ACTION = ""
        if self.EnableipV6 == 1: self.blockv6 = True
        if self.KSRNClientCacheTime==0: self.KSRNClientCacheTime=300
        self.memdb = art_memcache()
        liclass = isGoldkey()
        if liclass.is_corp_license():
            self.isArticaLicense = 1
            self.LICENSE = False
        SET_INFO("DNSFW_VERSION",self.version)
        self.read_resolv_conf()

        self.expose_extensions = False
        if self.DNSFireWallVerbose==1:
            self.DEBUG=True
            self.xyslog("DNS Firewall class loaded...")
    pass

    def get_linenumber(self):
        cf = currentframe()
        return cf.f_back.f_lineno

    def load_rule(self, ruleid):
        if int(ruleid)==9999999:
            if self.DNSFireWallDefaultReply=="0.0.0.0":
                self.rulename = "Default"
                self.action = 0
                self.ttl = self.DNSFireWallDefaultTTL
                return True


            self.rulename = "Default"
            self.action = 4
            self.reply = self.DNSFireWallDefaultReply
            self.ttl = self.DNSFireWallDefaultTTL
            return True


        results = self.get_cached_rules()
        self.rulename = results[ruleid]["rulename"]
        self.action = results[ruleid]["action"]
        self.reply = results[ruleid]["reply"]
        self.ttl = results[ruleid]["ttl"]

    def xyslog(self,text):
        sysDaemon = syslog
        sysDaemon.openlog("ksrn", syslog.LOG_PID)
        sysDaemon.syslog(syslog.LOG_INFO, "[DNS_CLIENT]: %s" % text)

    def StrToMD5(self,value):
        value=str(value)
        value=value.encode('utf8')
        return str(hashlib.md5(value).hexdigest())

    def operate(self, SourceIPAddr, qtype, domainname, DIR, decoded_aswer=None):
        ACTIONSPASS = ["WHITELIST",  "WHITE", "ERROR"]
        NOSRNACTION = ["PASS", "pass", "ERROR", "error"]
        self.SRN_ACTION=""
        func="operate"
        self.category_id = 0
        self.array_logs = {}
        self.INTERNAL_LOGS = []
        self.src_ip = SourceIPAddr
        self.returned_ip = decoded_aswer
        self.qtype = qtype
        self.domainname = domainname.lower()

        if self.domainname.find('root-servers.net') > 0: return 0
        if self.domainname.find('filter.artica.center') > 0: return 0
        if self.domainname.find('cguardprotect.net') > 0: return 0

        self.logdomain = "[%s] %s:%s" % (self.src_ip, qtype, self.domainname)
        self.DIR = DIR
        logdir="/home/artica/temp-dnsfirewall"

        self.array_logs["TIME"]=self.CurrentTime()
        self.array_logs["DOMAIN"]=self.domainname
        self.array_logs["SRC"]=self.src_ip
        self.array_logs["QTYPE"] = qtype


        self.ilog(self.get_linenumber(), func, "%s - - - - - - - - - - - - - - - - - - - - - -  - - - " % self.logdomain)
        sGLOBALKEY="%s%s" % (domainname, SourceIPAddr)
        sGLOBALKEY_md5 = self.StrToMD5(sGLOBALKEY)
        sGLOBALLKEY = "DNSFWGBCACHE:%s" % sGLOBALKEY_md5
        results = self.memdb.ksrncache_get(sGLOBALLKEY)

        if results is None:
            self.QueryShield()
            if not self.SHIELDS_ERROR:
                self.ilog(self.get_linenumber(), func,"%s MISS <%s> Cache of %s seconds" % (self.logdomain,sGLOBALLKEY,self.KSRNClientCacheTime))
                data="%s||%s||%s||%s" % (self.category_id,self.category_name,self.SRN_ACTION,self.virtual_user)
                self.memdb.ksrncache_set(sGLOBALLKEY,data,self.KSRNClientCacheTime)
        else:
            tb=results.split("||")
            self.category_id=tb[0]
            self.category_name=tb[1]
            self.SRN_ACTION=tb[2]
            self.virtual_user=tb[3]

        self.array_logs["CATEGORY"] = self.category_id
        self.array_logs["ACTION"] = self.SRN_ACTION
        self.array_logs["RULE"] = 0

        if self.SRN_ACTION in ACTIONSPASS:
            self.ilog(self.get_linenumber(), func, "%s WHITELISTED %s"  % (self.logdomain,self.SRN_ACTION))
            self.array_logs["RULE"] = 0
            zserialize = serialize(self.array_logs)
            self.statslogs(zserialize)
            return 0

        if self.SRN_ACTION in NOSRNACTION: self.SRN_ACTION=""


        if self.SRN_ACTION=="BLACK":
            ruleid = 9999999
            self.array_logs["CACHE"] = 1
            self.array_logs["RULE"] = ruleid
            zserialize = serialize(self.array_logs)
            self.statslogs(zserialize)
            return int(ruleid)


        SKEY = "%s%s%s%s" % (domainname, SourceIPAddr, qtype, DIR)
        md5 = self.StrToMD5(SKEY)
        LKEY = "DNSFWCACHE:%s" % md5
        ruleid = self.memdb.ksrncache_get(LKEY)

        if self.DEBUG:
            ruleid=None
            self.ilog(self.get_linenumber(),func,"%s MISS ( Cached rule ) - in debug mode no cache as been made)" % self.logdomain)


        if ruleid is not None:
            self.array_logs["CACHE"] = 1
            self.array_logs["RULE"] = ruleid
            self.array_logs["ACTION"]="FW"
            zserialize = serialize(self.array_logs)
            self.statslogs(zserialize)
            return int(ruleid)




        self.ilog(self.get_linenumber(), func, "%s checks Query type:%s"  % (self.logdomain, qtype))
        ruleid = self.check_rules()


        if ruleid == 0:
            if len(self.SRN_ACTION)>2:
                if not self.SRN_ACTION in NOSRNACTION:
                    self.ilog(self.get_linenumber(), func, "%s checks Query type:%s affect rule 9999999 FOR [%s]" % (self.logdomain, qtype,self.SRN_ACTION))
                    ruleid = 9999999

        self.array_logs["CACHE"] = 0
        self.array_logs["RULE"] = ruleid
        if ruleid > 0:
            if ruleid < 9999999:
                if not self.DEBUG: self.memdb.ksrncache_set(LKEY, ruleid, int(self.ttl))

        zserialize = serialize(self.array_logs)
        self.statslogs(zserialize)
        return ruleid

    def get_cached_rules(self):
        func="get_cached_rules"

        if len(self.zRules) >0:
            stemp=serialize(self.zRules)
            return unserialize(stemp)

        results = self.memdb.memcache_get("DNSFWOBJS")

        if self.DEBUG:
            self.ilog(self.get_linenumber(),func,"In debug mode, caching rules is disabled")
            results=None

        if results is not None:
            self.ilog(self.get_linenumber(),func,"HIT")
            return unserialize(results)

        start_time = time.time()
        self.ilog(self.get_linenumber(), func, "LOADING RULES...")
        results = self.load_objects()
        end_time = self.time_elaps(start_time)
        self.ilog(self.get_linenumber(), func, "MISS %s" % end_time)


        if not self.memdb.memcache_set("DNSFWOBJS", results, 3600):
            self.ilog(self.get_linenumber(), func, "STORE FAILED")

        return unserialize(results)

    def statslogs(self,content):
        if self.DisableDNSFWLogRules == 1: return False
        func="statslogs"
        logilename=self.log_filename()
        path="%s/%s.log" % (self.logdir, logilename)
        try:
            file_object = open(path, 'a')
            file_object.write('%s\n' % content)
            file_object.close()
        except:
            self.ilog(self.get_linenumber(), func, "STORE LOG %s FAILED" % path)

    def load_objects(self):
        func="load_objects"
        start_time = time.time()
        self.ilog(self.get_linenumber(), func, "load_objects start")
        rules = self.QUERY_SQL("SELECT ID,rulename,action,redirector,reply,ttl FROM dnsfw_acls WHERE enabled=1 ORDER BY zorder")
        self.zRules = {}

        for cols in rules:
            ruleid = cols[0]
            rulename = cols[1]
            action = cols[2]
            redirector = cols[3]
            reply = cols[4]
            ttl = cols[5]
            if not self.LICENSE:
                if len(self.zRules) > 10:
                    self.ilog(self.get_linenumber(), func, "<strong style='color:red'>ERROR</strong> Unable to load more than 10 rules ( License Error )")
                    self.zsyslog("[ERROR]: Unable to load more than 10 rules ( License Error )")
                    break

            self.zRules[ruleid] = {}
            self.zRules[ruleid]["rulename"] = rulename
            self.zRules[ruleid]["action"] = action
            self.zRules[ruleid]["redirector"] = redirector
            self.zRules[ruleid]["reply"] = reply
            self.zRules[ruleid]["ttl"] = ttl
            self.zRules[ruleid]["DIR"] = "IN"
            self.zRules[ruleid]["acls_objects"] = {}
            self.ilog(self.get_linenumber(), func, "Load rule %s ID:%s" % (ruleid,rulename))

            groups_results = self.QUERY_SQL("SELECT gpid,zmd5,negation FROM dnsfw_acls_link WHERE aclid=%s ORDER BY zOrder" % ruleid)

            for a in groups_results:
                gpid = a[0]
                negation=a[2]
                self.zRules[ruleid]["acls_objects"][gpid] = {}
                ligne = self.QUERY_SQL("SELECT GroupName,GroupType,enabled,params FROM webfilters_sqgroups WHERE ID=%s" % gpid,True)
                GroupName = ligne[0]
                GroupType = ligne[1]
                enabled = ligne[2]
                params  = ligne[3]
                if enabled == 0: continue

                self.zRules[ruleid]["acls_objects"][gpid]["GroupName"] = GroupName
                self.zRules[ruleid]["acls_objects"][gpid]["GroupType"] = GroupType
                self.zRules[ruleid]["acls_objects"][gpid]["enabled"] = enabled
                self.zRules[ruleid]["acls_objects"][gpid]["negation"] = negation
                self.zRules[ruleid]["acls_objects"][gpid]["params"] = params
                self.zRules[ruleid]["acls_objects"][gpid]["gpitems"] = []

                if GroupType == 'categories':
                    self.expose_extensions=True


                if GroupType == 'the_shields':
                    self.expose_extensions = True

                if GroupType == "geoipsrc":
                    self.expose_extensions = True

                if GroupType == "dst": self.zRules[ruleid]["DIR"] = "OUT"

                if GroupType == "geoipdest":
                    self.zRules[ruleid]["DIR"] = "OUT"
                    self.expose_extensions = True


                zitems = self.QUERY_SQL("SELECT pattern FROM webfilters_sqitems where gpid=%s" % gpid)
                for item in zitems:
                    src_item = item[0]
                    src_item = src_item.strip()
                    self.zRules[ruleid]["acls_objects"][gpid]["gpitems"].append(src_item)

        if self.DEBUG:
            end_time = self.time_elaps(start_time)
            self.INTERNAL_LOGS.append(str("FIREWALL_MODULE: %s - %s seconds" % (self.logdomain, end_time)))

        stemp=serialize(self.zRules)
        if not self.memdb.memcache_set("DNSFWOBJS", stemp, 3600):
            self.ilog(self.get_linenumber(), func, "STORE FAILED")
        return stemp

    def decode_data(self, rawdata, start):
        text = ''
        remain = ord(rawdata[2])
        for c in rawdata[3 + start:]:
            if remain == 0:
                text += '.'
                remain = ord(c)
                continue
            remain -= 1
            text += c
        return text.strip('.').lower()

    def decode_aswer(self, answer, type):
        if type == 'A':
            name = "%d.%d.%d.%d" % (ord(answer[2]), ord(answer[3]), ord(answer[4]), ord(answer[5]))
            return name

        if type == 'AAAA':
            name = "%02x%02x:%02x%02x:%02x%02x:%02x%02x:%02x%02x:%02x%02x:%02x%02x:%02x%02x" % (
            ord(answer[2]), ord(answer[3]), ord(answer[4]), ord(answer[5]), ord(answer[6]), ord(answer[7]),
            ord(answer[8]), ord(answer[9]), ord(answer[10]), ord(answer[11]), ord(answer[12]), ord(answer[13]),
            ord(answer[14]), ord(answer[15]), ord(answer[16]), ord(answer[17]))
            return name

        if type in ('CNAME', 'NS'):
            name = self.decode_data(answer, 0)
            return name
        if type == 'MX':
            name = self.decode_data(answer, 1)
            return name
        if type == 'PTR':
            name = self.decode_data(answer, 0)
            return name

        if type == 'SOA':
            name = self.decode_data(answer, 0).split(' ')[0][0].strip('.')
            return name

        if type == 'SRV':
            name = self.decode_data(answer, 5)
            return name

        return None

    def time_elaps(self,start_time):
        elapsed_time_secs = time.time() - start_time
        text="took: %s secs" % timedelta(seconds=round(elapsed_time_secs))
        return text


    def check_rules(self):
        func="check_rules"
        results = self.get_cached_rules()
        ld = self.logdomain
        if results is None:
            self.ilog(self.get_linenumber(), func, "get_cached_rules = NONE, [ABORT]")
            return False

        if self.DEBUG: self.ilog(self.get_linenumber(), func, "--> start %s rule(s)" % len(results))
        self.rulename = ""
        self.action = 0
        self.redirector = ""
        self.reply = ""
        self.ttl = 0
        CountOfRules = 0
        start_time = time.time()
        self.category_id = 0
        self.category_name=""


        for ruleid in results:
            self.rulename = results[ruleid]["rulename"]
            self.action = results[ruleid]["action"]
            DIR = results[ruleid]["DIR"]
            if self.OUT: print("rulename:", rulename, " action:", action, " ttl:", ttl)
            if self.DEBUG: self.ilog(self.get_linenumber(), func, "%s %s ID [%s] action %s" % (ld, ruleid, self.rulename, self.action))
            if DIR != self.DIR:
                if self.DEBUG: self.ilog(self.get_linenumber(), func, "%s != %s [SKIP]" % ( DIR, self.DIR))
                continue

            if self.DEBUG: self.ilog(self.get_linenumber(), func, "%s %s ID [%s] action %s" % (ld, ruleid, self.rulename, self.action))
            try:
                CountOfRules = CountOfRules + 1
                start_time_objs = time.time()
                if not self.objects_matches(ruleid):
                    secondselaps = self.time_elaps(start_time_objs)
                    if self.DEBUG: self.ilog(self.get_linenumber(), func, "%s %s" % (self.rulename, secondselaps))
                    continue
            except:
                slogs = tb.format_exc()
                if self.DEBUG: self.ilog(self.get_linenumber(), func, "v.%s [%s]" % ( self.version,slogs))
                continue

            if self.DEBUG: self.ilog(self.get_linenumber(), func, "%s matches !" % ld)

            self.redirector = results[ruleid]["redirector"]
            self.reply = results[ruleid]["reply"]
            self.ttl = results[ruleid]["ttl"]
            if self.DEBUG:
                secondselaps = self.time_elaps(start_time)
                self.ilog(self.get_linenumber(), func, "[SUCCESS] rule:%s (%s) MATCHES after %s scanned rule(s) for action %s (%s)" % (ruleid, self.rulename,CountOfRules,self.action,secondselaps))

            return ruleid

        if self.DEBUG:
            secondselaps = self.time_elaps(start_time)
            self.ilog(self.get_linenumber(), func, "[PASS] no rule matches criterias after parsing %s rule(s) %s"  % (secondselaps, CountOfRules))


        return 0

    def read_resolv_conf(self):
        try:
            f = open("/etc/resolv.conf", 'r')
        except IOError:
            self.nameservers.append('8.8.8.8')
            return

        try:
            for l in f:
                l = l.strip()
                if len(l) == 0 or l[0] == '#' or l[0] == ';': continue
                tokens = l.split()
                if len(tokens) < 2: continue
                if tokens[0] == 'nameserver':
                    if tokens[1] == "127.0.0.1": continue
                    self.nameservers.append(tokens[1])
        finally:
            f.close()

        if len(self.nameservers) == 0: self.nameservers.append('8.8.8.8')

    def objects_matches(self, ruleid):
        func="objects_matches"
        rules=self.get_cached_rules()
        if not ruleid in rules:
            self.INTERNAL_LOGS.append(str("--------------: dnsfw:objects_matches:%s [ERROR] rules=array as no rule id " % (self.get_linenumber(),ruleid)))

        try:
            objects=rules[ruleid]["acls_objects"]
        except:
            self.INTERNAL_LOGS.append(str("--------------: dnsfw:objects_matches:%s [ERROR] %s " %  (self.get_linenumber(),tb.format_exc())))
            return False

        main_time = time.time()
        if len(objects)==0:
            self.INTERNAL_LOGS.append(str("--------------: dnsfw:objects_matches:%s [ERROR] objects as no Len" % self.get_linenumber()))
            return False

        for gpid in objects:
            start_time = time.time()
            params = None
            negation = 0
            Tnegation="must match"
            if not "GroupName" in objects[gpid]:
                if self.DEBUG: self.INTERNAL_LOGS.append(str("--------------: dnsfw:objects_matches:%s [ERROR] Key Error GroupName in gpid [%s] rule %s" % (self.get_linenumber(),gpid,self.rulename)))
                continue

            if not "GroupType" in objects[gpid]:
                if self.DEBUG: self.INTERNAL_LOGS.append(str("--------------: dnsfw:objects_matches:%s  [ERROR] Key Error GroupType in gpid [%s] rule %s" % (self.get_linenumber(),gpid,self.rulename)))
                continue

            GroupName = objects[gpid]["GroupName"]
            negation  = objects[gpid]["negation"]
            GroupType = objects[gpid]["GroupType"]
            enabled = objects[gpid]["enabled"]
            gpitems = objects[gpid]["gpitems"]
            if "params" in objects[gpid]: params  = objects[gpid]["params"]
            if "negation" in objects[gpid]: negation = objects[gpid]["negation"]
            if negation == 1: Tnegation="must NOT match"

            if enabled == 0:
                self.ilog(self.get_linenumber(), func, "[%s] Type:[%s] [disabled] --> Continue" % (GroupName, GroupType))
                continue

            if self.DNSFireWallVerbose == 1: self.ilog(self.get_linenumber(), func, "[%s] Type:[%s] - - - %s - - -" % ( GroupName, GroupType,Tnegation))

            if GroupType == 'categories':
                start_time = time.time()
                if self.DNSFireWallVerbose == 1: self.ilog(self.get_linenumber(), func, "[%s]: Checking categories against %s item(s)...." % (self.domainname,len(gpitems)))
                if not self.object_categories(gpitems):
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [OK] %s" % (GroupName, secondselaps))
                        continue
                    self.ilog(self.get_linenumber(), func, "Group [%s] [FAILED] [BAD] %s" % (GroupName, secondselaps))
                    return False
                else:
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [BAD] %s" % (GroupName, secondselaps))
                        return False
                    self.ilog(self.get_linenumber(), func, "Group [%s] [MATCH] [OK] %s" % (GroupName, secondselaps))
                    continue

            if GroupType == 'src':
                start_time = time.time()
                if not self.object_srcip(gpitems):
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [OK] %s" % (GroupName, secondselaps))
                        continue
                    self.ilog(self.get_linenumber(), func, "Group [%s] [FAILED] [BAD] %s" % (GroupName, secondselaps))
                    return False
                else:
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [BAD] %s" % (GroupName, secondselaps))
                        return False
                    self.ilog(self.get_linenumber(), func, "Group [%s] [MATCH] [OK] %s" % (GroupName, secondselaps))
                    continue

            if GroupType == 'weekrange':
                start_time = time.time()
                if not self.object_weekrange(params):
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [OK] %s" % (GroupName, secondselaps))
                        continue

                    self.ilog(self.get_linenumber(), func, "Group [%s] [FAILED] [BAD] %s" % (GroupName, secondselaps))
                    return False
                else:
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [BAD] %s" % (GroupName, secondselaps))
                        return False
                    self.ilog(self.get_linenumber(), func, "Group [%s] [MATCH] [OK] %s" % (GroupName, secondselaps))
                    continue


            if GroupType == 'dstdomain':
                start_time = time.time()
                if not self.object_dstdomain(gpitems):
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [OK] %s" % (GroupName, secondselaps))
                        continue
                    self.ilog(self.get_linenumber(), func, "Group [%s] [FAILED] [BAD] %s" % (GroupName, secondselaps))
                    return False
                else:
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [BAD] %s" % (GroupName, secondselaps))
                        return False
                    self.ilog(self.get_linenumber(), func, "Group [%s] [MATCH] [OK] %s" % (GroupName, secondselaps))
                    continue

            if GroupType == 'dstdom_regex':
                start_time = time.time()
                if not self.object_dstdom_regex(gpitems):
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [OK] %s" % (GroupName, secondselaps))
                        continue
                    self.ilog(self.get_linenumber(), func, "Group [%s] [FAILED] [BAD] %s" % (GroupName, secondselaps))
                    return False
                else:
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [BAD] %s" % (GroupName, secondselaps))
                        return False
                    self.ilog(self.get_linenumber(), func, "Group [%s] [MATCH] [OK] %s" % (GroupName, secondselaps))
                    continue


            if GroupType == 'dnsquerytype':
                start_time = time.time()
                if not self.object_dnsquerytype(gpitems):
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [OK] %s" % (GroupName, secondselaps))
                        continue
                    self.ilog(self.get_linenumber(), func, "Group [%s] [FAILED] [BAD] %s" % (GroupName, secondselaps))
                    return False
                else:
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [BAD] %s" % (GroupName, secondselaps))
                        return False
                    self.ilog(self.get_linenumber(), func, "Group [%s] [MATCH] [OK] %s" % (GroupName, secondselaps))
                    continue

            if GroupType == 'geoipdest':
                start_time = time.time()
                if not self.object_geoipdest(gpitems):
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [OK] %s" % (GroupName, secondselaps))
                        continue
                    self.ilog(self.get_linenumber(), func, "Group [%s] [FAILED] [BAD] %s" % (GroupName, secondselaps))
                    return False
                else:
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [BAD] %s" % (GroupName, secondselaps))
                        return False
                    self.ilog(self.get_linenumber(), func, "Group [%s] [MATCH] [OK] %s" % (GroupName, secondselaps))
                    continue

            if GroupType == 'geoipsrc':
                start_time = time.time()
                if not self.object_geoipsrc(gpitems):
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [OK] %s" % (GroupName, secondselaps))
                        continue
                    self.ilog(self.get_linenumber(), func, "Group [%s] [FAILED] [BAD] %s" % (GroupName, secondselaps))
                    return False
                else:
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [BAD] %s" % (GroupName, secondselaps))
                        return False
                    self.ilog(self.get_linenumber(), func, "Group [%s] [MATCH] [OK] %s" % (GroupName, secondselaps))
                    continue

            if GroupType == 'dst':
                start_time = time.time()
                if not self.object_dst(gpitems):
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [OK] %s" % (GroupName, secondselaps))
                        continue
                    self.ilog(self.get_linenumber(), func, "Group [%s] [FAILED] [BAD] %s" % (GroupName, secondselaps))
                    return False
                else:
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [BAD] %s" % (GroupName, secondselaps))
                        return False
                    self.ilog(self.get_linenumber(), func, "Group [%s] [MATCH] [OK] %s" % (GroupName, secondselaps))
                    continue

            if GroupType == 'the_shields':
                start_time = time.time()
                if not self.object_theshields():
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [OK] %s" % (GroupName, secondselaps))
                        continue
                    self.ilog(self.get_linenumber(), func, "Group [%s] [FAILED] [BAD] %s" % (GroupName, secondselaps))
                    return False
                else:
                    secondselaps = self.time_elaps(start_time)
                    if negation==1:
                        self.ilog(self.get_linenumber(), func,"Group [%s] is NOT [MATCH] [BAD] %s" % (GroupName, secondselaps))
                        return False
                    self.ilog(self.get_linenumber(), func, "Group [%s] [MATCH] [OK] %s" % (GroupName, secondselaps))
                    continue


            if GroupType == 'all': continue

        secondselaps = self.time_elaps(main_time)
        self.ilog(self.get_linenumber(), func, "Checking %s All objects return true, [OK] MATCHES!" % (secondselaps))

        return True

    def GetGoIPFromaddr(self,ipaddr):
        if ipaddr is None: return "-;-;-"
        if len(ipaddr)==0: return "-;-;-"
        if self.EnableGeoipUpdate == 0:return "-;-;-"
        matches=re.search('^[0-9\.]+$',ipaddr)
        if not matches: return "-;-;-"

        md5 = ipaddr
        LKEY = "DNSFWCACHE:%s" % md5
        value=self.memdb.memcache_get(LKEY)
        if value is not None: return value
        Geo = geoip2free(self.DEBUG)
        if not Geo.operate(ipaddr):
            syslog.openlog("dns-firewall", syslog.LOG_PID)
            self.zsyslog("[ERROR] GeoIP: %s" % Geo.error)
            return "-;-;-"
        isocode=Geo.iso_code

        AS=Geo.autonomous_system_number
        ISP=Geo.autonomous_system_organization
        final="%s;%s;%s" % (isocode,AS,ISP)
        self.memdb.memcache_set(LKEY,final,28800)
        return final

    def final_syslog(self,action,ruleid):
        dstipaddr = self.returned_ip
        srcipaddr = self.src_ip
        if dstipaddr is None: dstipaddr="-"
        Geoloc = self.GetGoIPFromaddr(dstipaddr)
        domainname = self.domainname
        cattex=""
        rtype = self.rtype
        text="[%s:%s] %s %s[%s]:%s %s" % (action,ruleid,srcipaddr,domainname,dstipaddr,rtype,Geoloc)
        sOUT="IN"
        category_id=self.category_id
        if category_id>0: cattex=self.category_name
        if self.OUT: sOUT="OUT"
        self.unbound_log=str("%s %s %s %s %s [%s:%s]:%s %s" % (srcipaddr,rtype,domainname,dstipaddr,Geoloc,action,ruleid,sOUT,cattex))



    def generate_response(self, qstate, rname, rtype, rrtype, ruleid):
        self.INTERNAL_LOGS = []
        func    = "generate_response"
        self.load_rule(ruleid)
        action = self.action
        redirector = self.redirector
        newttl = self.ttl
        reply = self.reply
        rmsg=""
        intercept_address = ""
        self.rtype=rtype
        intercept_host = "localhost.localdomain"
        fname = ""
        KeyINcr = "DNSFWCACHE:%s" % ruleid
        matches = re.search('^([0-9\.]+)$', reply)
        if matches:
            intercept_address = matches.group(1)
            intercept_host = "localhost.localdomain"

        matches = re.search('^([0-9\.]+)\s+(.+)', reply)
        if matches:
            intercept_address = matches.group(1)
            intercept_host = matches.group(2)

        self.ilog(self.get_linenumber(), func, "Correspond to ruleid:%s action:[%s] timeout:[%s]" % (ruleid,action, newttl))
        self.ilog(self.get_linenumber(), func, "Correspond to ruleid:%s rname:[%s] rrtype:[%s]" % (ruleid,rname, rrtype))

        if self.blockv6 and ((rtype == 'AAAA') or rname.endswith('.ip6.arpa')):
            self.final_syslog(self.MAIN_ACTION,-1)
            self.ilog(self.get_linenumber(), func, "RESPONSE: HIT on IPv6 for \"%s\" (RR:%s)" % (rname, rtype))
            return False

        if action == 0:
            self.MAIN_ACTION = "REFUSED"
            self.final_syslog(self.MAIN_ACTION,ruleid)
            self.ilog(self.get_linenumber(), func, "ruleid:%s ACTION = NXDOMAIN" % ruleid)
            if not self.memdb.memcache_incr(KeyINcr): self.ilog(self.get_linenumber(), func,"%s increment failed" % KeyINcr)
            return True

        if action == 1:
            self.MAIN_ACTION = "NXDOMAIN"
            self.final_syslog(self.MAIN_ACTION,ruleid)
            self.ilog(self.get_linenumber(), func, "ruleid:%s ACTION = NXDOMAIN" % ruleid)
            if not self.memdb.memcache_incr(KeyINcr): self.ilog(self.get_linenumber(), func,"%s increment failed" % KeyINcr)
            return True


        if action == 6:
            self.MAIN_ACTION = "PASS"
            self.final_syslog(self.MAIN_ACTION,ruleid)
            self.ilog(self.get_linenumber(), func, "ruleid:%s ACTION = NXDOMAIN" % ruleid)
            if not self.memdb.memcache_incr(KeyINcr): self.ilog(self.get_linenumber(), func,"%s increment failed" % KeyINcr)
            return True

        if action == 3:
            self.MAIN_ACTION = "SERVFAIL"
            self.final_syslog(self.MAIN_ACTION,ruleid)
            self.ilog(self.get_linenumber(), func, "ruleid:%s ACTION = SERVFAIL" % ruleid)
            if not self.memdb.memcache_incr(KeyINcr): self.ilog(self.get_linenumber(), func,"%s increment failed" % KeyINcr)
            return True

        if action == 5:
            self.MAIN_ACTION = "ANSWER"
            self.final_syslog(self.MAIN_ACTION,ruleid)
            self.ilog(self.get_linenumber(),func,"ACTION = QUERY %s with %s ttl:%s" % (rtype, redirector, newttl))
            if not self.dns_query(redirector, self.domainname, rtype, ruleid, newttl):
                self.ilog(self.get_linenumber(),func,"%s [FAILED]" % redirector)
                self.MAIN_ACTION = "SERVFAIL"
                return False

            rmsg = self.DNSMessage(rname, rrtype, self.RR_CLASS_IN, self.PKT_QR | self.PKT_RA)
            for fname in self.dns_query_results:
                rmsg.answer.append('%s %s IN %s %s' % (rname, newttl, rtype, fname))
            if not self.memdb.memcache_incr(KeyINcr): self.ilog( self.get_linenumber(),func,"%s increment failed" % KeyINcr)
            return self.dns_end(rmsg, qstate)

        if action == 4:
            self.MAIN_ACTION = "ANSWER"
            self.ilog( self.get_linenumber(),func,"ACTION = ANSWER %s with %s ttl:%s" % (rtype, reply, newttl))
            if not rtype in ('A', 'CNAME', 'MX', 'NS', 'PTR', 'SOA', 'SRV', 'TXT', 'ANY'):
                self.ilog( self.get_linenumber(),func,"%s is not supported return default = SERVFAIL" % rtype)
                self.MAIN_ACTION = "SERVFAIL"
                self.final_syslog(self.MAIN_ACTION, ruleid)
                if not self.memdb.memcache_incr(KeyINcr): self.ilog( self.get_linenumber(),func,"%s increment failed" % KeyINcr)
                return True

            self.final_syslog(self.MAIN_ACTION,ruleid)
            DONE = False

            if rtype in ('CNAME', 'MX', 'NS', 'PTR', 'SOA', 'SRV'):
                DONE = True
                serial = datetime.datetime.now().strftime('%Y%m%d%H')
                fname = intercept_host
                if rtype == 'MX':  fname = '0 ' + intercept_host
                if rtype == 'SOA': fname ="%s hostmaster.%s %s 86400 7200 3600000 %s" % (intercept_host,intercept_host,serial,newttl)
                if rtype == 'SRV': fname = '0 0 80 ' + intercept_host
                rmsg = self.DNSMessage(rname, rrtype, self.RR_CLASS_IN, self.PKT_QR | self.PKT_RA)
                rmsg.answer.append('%s %s IN %s %s' % (rname, newttl, rtype, fname))
                rmsg.answer.append('%s %s IN A %s' % (intercept_host, newttl, intercept_address))

            if rtype == 'TXT':
                DONE = True
                rmsg = self.DNSMessage(rname, rrtype, self.RR_CLASS_IN, self.PKT_QR | self.PKT_RA)
                redirect = '\"%s\"' % intercept_host
                rmsg.answer.append('%s %s IN %s %s' % (rname, newttl, rtype, redirect))

            if not DONE:
                qname = rname + '.'
                Append = '%s %s IN A %s' % (qname, newttl, intercept_address)
                self.ilog(self.get_linenumber(),func, Append)
                try:
                    rmsg = self.DNSMessage(rname, self.RR_TYPE_A, self.RR_CLASS_IN, self.PKT_QR | self.PKT_RA)
                    rmsg.answer.append(Append)
                except:
                    self.isyserror(tb.format_exc())

            if self.DebugTool: return Append

            return self.dns_end(rmsg, qstate)

        return False

    def ilog_populate(self,the_dic):
        for line in  the_dic:
            self.INTERNAL_LOGS.append(str(line))

    def ilog(self,line,func,text):
        if self.DNSFireWallVerbose==1:
            sysDaemon = syslog
            sysDaemon.openlog("ksrn", syslog.LOG_PID)
            sysDaemon.syslog(syslog.LOG_INFO, "[DNS_CLIENT]: %s:[%s][DEBUG]: %s" % (line,func,text))

        text="--------------: dnsfw:%s %s %s" % (line,func,text)
        self.INTERNAL_LOGS.append(str(text))



    def dns_end(self, rmsg, qstate):
        try:
            rmsg.set_return_msg(qstate)
        except:
            return False

        if not rmsg.set_return_msg(qstate):
            self.ilog(self.get_linenumber(),func, "GENERATE-RESPONSE ERROR: %s" % str(rmsg.answer))
            return False

        if qstate.return_msg.qinfo:
            self.invalidateQueryInCache(qstate, qstate.return_msg.qinfo)

        qstate.no_cache_store = 0
        self.storeQueryInCache(qstate, qstate.return_msg.qinfo, qstate.return_msg.rep, 0)
        qstate.return_msg.rep.security = 2
        self.qstate = qstate
        return True

    def object_geoipdest(self,items):
        func = "object_geoipdest"
        ipaddr = self.returned_ip
        Geo     = geoip2free(self.DEBUG)
        if len(items) == 0: return False
        for index in items:
            Country = str(items[index])
            if not Geo.operate(ipaddr):
                self.ilog(self.get_linenumber(), func, "ERROR [%s] in [%s] -- %s --" % (Country, ipaddr,Geo.error))
                continue

            self.iso_code = Geo.iso_code
            self.ilog(self.get_linenumber(), func, "Check %s in %s = %s" % (Country, ipaddr,Geo.iso_code))
            if Geo.iso_code == Country: return True


        return False

    def object_geoipsrc(self,items):
        func = "object_geoipsrc"
        ipaddr = self.src_ip
        Geo     = geoip2free(self.DEBUG)
        if len(items) == 0: return False
        for index in items:
            Country = str(items[index])
            try:
                if not Geo.operate(ipaddr):
                    self.ilog(self.get_linenumber(), func, "ERROR [%s] in [%s] -- %s --" % (Country, ipaddr,Geo.error))
                    continue
            except:
                self.ilog(self.get_linenumber(), func, "ERROR [%s] in [%s] -- %s --" % (Country, ipaddr, tb.format_exc()))
                continue

            try:
                self.iso_code_src = Geo.iso_code
            except:
                continue

            self.ilog(self.get_linenumber(), func, "Check %s in %s = %s" % (Country, ipaddr,Geo.iso_code))
            if Geo.iso_code == Country: return True

    def QueryShield(self):
        self.SHIELDS_ERROR=False
        func="QueryShield"
        MYQTYPE=['A','AAAA','a','aaaa','MX','mx']
        result = {}

        if not self.qtype in MYQTYPE:
            result["ACTION"] = "PASS"
            result["categoy_id"] = 0
            result["categoy_name"] ="ipaddr"
            self.category_id=0
            self.category_name="ipaddr"
            self.SRN_ACTION ="PASS"
            return result


        if self.domainname.find('.in-addr.arpa')>0:
            result["ACTION"] = "PASS"
            result["categoy_id"] = 0
            result["categoy_name"] ="ipaddr"
            self.category_id=0
            self.category_name="ipaddr"
            self.SRN_ACTION ="PASS"
            return result

        strline="1000 MacToUid_dns - %s 00:00:00:00:00:00 - %s - - - - - CONNECT" % ( self.src_ip,self.domainname)
        results = self.ThreadP.input_proxy(strline)
        if  self.DNSFireWallVerbose==1: self.ilog(self.get_linenumber(), func, "The Shields --> receive <%s>" % results)

        if len(results)< 5:
            if self.DNSFireWallVerbose == 1: self.ilog(self.get_linenumber(), func, "BAD ANSWER [%s] from The Shields" % results)
            self.isyserror("BAD ANSWER [%s] from The Shields" % results)
            return None



        if results.find('first=ERROR')>0:
            self.isyserror("[%s] Error reported from The Shields" % self.domainname)
            self.SHIELDS_ERROR=True

        if results.find('shieldsblock=yes')>0:
            if self.DNSFireWallVerbose == 1: self.ilog(self.get_linenumber(), func,"%s Blocked by The Shields" % (self.domainname))



        matches=re.search('srn=(.+?)\s+',results)
        if matches:
            result["ACTION"]=matches.group(1)
            self.SRN_ACTION=matches.group(1)

        matches=re.search('clog=cinfo:([0-9]+)-(.+?);',results)
        if not matches:
            if self.DNSFireWallVerbose == 1: self.ilog(self.get_linenumber(), func, "%s no matches <%s>" % (self.domainname,results))

        if matches:
            if self.DNSFireWallVerbose == 1: self.ilog(self.get_linenumber(), func,"%s The Shields --> Category=%s (%s)" % (self.domainname,matches.group(1),matches.group(2)))
            result["categoy_id"]=matches.group(1)
            result["categoy_name"]=matches.group(2)
            self.category_id=int(matches.group(1))
            self.category_name=matches.group(2)

        matches = re.search('user=(.+?)\s+', results)
        if matches:
            result["VIRTUAL_USER"]=matches.group(1)
            self.virtual_user=matches.group(1)
        return result



    def object_theshields(self):
        func="object_theshields"
        if self.category_id>0: return self.category_id
        error=''
        ACTION=''
        VIRTUAL_USER=''
        CountryCode=''


        try:
            result = self.QueryShield()
        except:
            if self.DNSFireWallVerbose == 1: self.ilog(self.get_linenumber(), func,"ERROR %s" % results)
            self.isyserror("ERROR %s" % tb.format_exc())
            return False

        if result is None:
            self.isyserror("BAD ANSWER The Shields Return None !")
            return False


        if 'error' in result : error = result["error"]
        if 'categoy_id' in result: self.category_id = int(result["categoy_id"])
        if 'categoy_name' in result: self.category_name = result["categoy_name"]
        if 'ACTION' in result: ACTION = result["ACTION"]
        if 'VIRTUAL_USER' in result: VIRTUAL_USER = result["VIRTUAL_USER"]
        if 'COUNTRY_CODE' in result: CountryCode = result["COUNTRY_CODE"]

        if ACTION == "PASS": return False
        if ACTION == "WHITELIST": return False
        if ACTION == "WHITE": return False
        if len(ACTION) == 0: return False

        return True

    def isyslog(self,text):
        self.xyslog("[INFO] %s %s" % (self.src_ip, text))
        syslog.openlog("dns-firewall", syslog.LOG_PID)
        self.zsyslog("[00000:0] info: %s %s" % (self.src_ip, text))

    def isyserror(self,text):
        self.xyslog("ERROR %s %s" % (self.src_ip,text))
        syslog.openlog("dns-firewall", syslog.LOG_PID)
        self.zsyslog("[00000:0] error: %s %s" % (self.src_ip,text))


    def object_dnsquerytype(self, items):
        func = "object_dnsquerytype"
        x=self.qtype
        x = x.strip()
        x = x.lower()
        if len(items) == 0: return False
        for index in items:
            Query=str(items[index])
            Query=Query.strip()
            Query=Query.lower()
            self.ilog(self.get_linenumber(), func, "Checking <%s> against <%s>" % (Query,x))
            if Query == x: return True

    def object_dst(self, items):
        func="object_dst"
        if len(items) == 0: return False
        matches = re.search('^([0-9\.]+)', self.returned_ip)
        ipaddr = self.returned_ip
        if not matches: return False
        for index in items:
            ipchecks = str(items[index])
            if ipaddr == ipchecks: return True
            if ipchecks.find('/') > 0:
                try:
                    if IPAddress(ipaddr) in IPNetwork(ipchecks): return True
                except:
                    self.ilog(self.get_linenumber(), func, "[ERROR]: object_dst %s :=: %s %s " % (ipaddr, ipchecks, tb.format_exc()))
                    return False

        return False

    def object_srcip(self, items):
        func = "object_srcip"
        matches=re.search('^([0-9\.]+)$',self.src_ip)
        if not matches:
            self.ilog(self.get_linenumber(),func, "[ERROR]: object_srcip [%s] not seems an IP address..." % self.src_ip)
            return False

        ipaddr = self.src_ip

        if len(items) == 0: return False
        for index in items:
            ipchecks = str(items[index])
            if ipaddr == ipchecks: return True
            if ipchecks.find('/') > 0:
                try:
                    if IPAddress(ipaddr) in IPNetwork(ipchecks): return True
                except:
                    self.ilog(self.get_linenumber(),func, "[ERROR]: object_srcip %s :=: %s %s " % (ipaddr, ipchecks, tb.format_exc()))
                    return False

        return False

    def object_weekrange(self,params):
        if params is None:
            error = "[ERROR]: object_weekrange params is a None data assume FALSE" % params
            syslog.syslog(syslog.LOG_INFO, error)
            self.ilog(self.get_linenumber(), func, error)
            return False

        tDays=['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday','Saturday']
        Cur=datetime.today().weekday()
        CurDayStr=tDays[Cur]
        func="object_weekrange"
        if len(params)==0:
            error = "[ERROR]: object_weekrange params as an empty data assume FALSE" % params
            syslog.syslog(syslog.LOG_INFO, error)
            self.ilog(self.get_linenumber(), func, error)
            return False

        try:
            base64decoded = base64.b64decode(params)
        except:
            error="[ERROR]: object_weekrange cannot decode %s assume FALSE" % params
            syslog.syslog(syslog.LOG_INFO,error )
            self.ilog(self.get_linenumber(), func,error)
            return False

        try:
            main=unserialize(base64decoded)
        except:
            error = "[ERROR]: object_weekrange cannot unserialize %s assume FALSE" % base64decoded
            syslog.syslog(syslog.LOG_INFO, error)
            self.ilog(self.get_linenumber(), func, error)
            return False

        if not "TIME" in main:
            error = "[ERROR]: object_weekrange array did not store MAIN Key [%s] assume FALSE" % base64decoded
            syslog.syslog(syslog.LOG_INFO, error)
            self.ilog(self.get_linenumber(), func, error)
            return False


        TIME=main["TIME"]
        if len(TIME)==0:
            error = "[ERROR]: object_weekrange TIME object did not store any period [%s] assume FALSE" % base64decoded
            syslog.syslog(syslog.LOG_INFO, error)
            self.ilog(self.get_linenumber(), func, error)
            return False



        for period in TIME:
            matches=re.search('^([0-9]+)_(.+)',period)
            if not matches: continue
            sDay = int(matches.group(1))
            sHour =  matches.group(2)
            if sDay!=Cur:continue
            tCur=self.CurrentTime()
            tCurText = self.TimeToStr(tCur)
            try:
                gHourA=self.object_weekrange_hourA(sHour)
                gHourB=self.object_weekrange_hourB(sHour)
                tHourA=self.TimeToStr(gHourA)
                tHourB=self.TimeToStr(gHourB)
            except:
                error = "[ERROR]: object_weekrange cannot convert hour1 %s (%s)" % (sHour,tb.format_exc())
                syslog.syslog(syslog.LOG_INFO, error)
                self.ilog(self.get_linenumber(), func, error)
                continue

            if gHourA>tCur: continue
            if tCur > gHourB: continue
            self.ilog(self.get_linenumber(), func, "Checking Time=%s %s %s matches between %s and %s " % (tCur,CurDayStr,tCurText,tHourA,tHourB))
            return True
        return False


    def TimeToStr(self,timestamp):
        utc_time = datetime.fromtimestamp(timestamp)
        return utc_time.strftime("%Y-%m-%d %H:%M:%S")

    def object_weekrange_hourA(self,sHour):
        sHour = sHour.replace("-", ".")
        zHour = float(sHour)
        fHour = zHour * 3600
        utc_time = datetime.fromtimestamp(fHour)
        zString = utc_time.strftime("%H:%M")
        return self.strHour2TimeStamp(zString)

    def object_weekrange_hourB(self,sHour):
        sHour = sHour.replace("-", ".")
        zHour = float(sHour)
        zHour = zHour + 0.5
        fHour = zHour * 3600
        utc_time = datetime.fromtimestamp(fHour)
        zString = utc_time.strftime("%H:%M")
        if zString =="00:00": zString="23:59"
        return self.strHour2TimeStamp(zString)

    def strHour2TimeStamp(self,strtime):
        thisXMas = datetime.now()
        TimePrefix = datetime.strftime(thisXMas, "%Y-%m-%d")
        newtime="%s %s:00" % (TimePrefix,strtime)
        tt = datetime.strptime(newtime, "%Y-%m-%d %H:%M:%S").timetuple()
        return int(mktime(tt))

    def CurrentTime(self):
        thisXMas = datetime.now()
        strtime = datetime.strftime(thisXMas, "%Y-%m-%d %H:%M:%S")
        return self.strtime2TimeStamp(strtime)

    def strtime2TimeStamp(self,strtime):
        tt = datetime.strptime(strtime, "%Y-%m-%d %H:%M:%S").timetuple()
        return int(mktime(tt))

    def object_dstdom_regex(self,items):
        if len(items) == 0: return False
        for index in items:
            domain = str(items[index])
            try:
                matches = re.search('%s' % domain, self.domainname)
            except:
                continue
            if matches: return True


    def object_dstdomain(self, items):
        if len(items) == 0: return False
        for index in items:
            domain = str(items[index])
            if len(domain) < 3: continue
            domain = domain.replace('..', '.')
            domain = domain.replace(';', '.')
            domain = domain.replace('.', '\.')
            try:
                matches = re.search('(\.|^)%s$' % domain, self.domainname)
            except:
                continue
            if matches: return True

    def object_categories(self, items):
        self.category_id=0
        func = "object_categories"
        if len(items) == 0:
            self.ilog(self.get_linenumber(),func,"no item")
            return False

        try:
            results = self.QueryShield()
        except:
            if self.DNSFireWallVerbose == 1: self.ilog(self.get_linenumber(), func,
                                                       "[%s]: FATAL <%s>" % (self.domainname,tb.format_exc()))
            self.isyserror("object_categories %s" % tb.format_exc())
            return False

        if results is None:
            if self.DNSFireWallVerbose == 1: self.ilog(self.get_linenumber(), func,"[%s]: BAD ANSWER from The Shields" % (self.domainname))
            self.isyserror("object_categories BAD ANSWER from The Shields")
            return False


        if 'error' in results: error = results["error"]
        if 'categoy_id' in results: self.category_id = int(results["categoy_id"])
        if 'categoy_name' in results: self.category_name = results["categoy_name"]
        if 'ACTION' in results: ACTION = results["ACTION"]
        if 'VIRTUAL_USER' in results: VIRTUAL_USER = results["VIRTUAL_USER"]
        if 'COUNTRY_CODE' in results: CountryCode = results["COUNTRY_CODE"]

        if self.DNSFireWallVerbose==1:
            self.ilog(self.get_linenumber(), func,"Found category ID <%s> (%s)" % (self.category_id,self.domainname))

        for index in items:
            categoryid = int(items[index])
            self.ilog(self.get_linenumber(),func,"checking catz:%s" % categoryid)
            if self.category_id == categoryid:
                self.ilog(self.get_linenumber(), func, "checking catz:%s == %s [OK] (%s)" % (categoryid, self.category_id,self.category_name))
                return True

        return False

    def dns_query(self, redirector, domainname, rrtype, ruleid, newttl):
        SKEY = "%s%s%s%s" % (domainname, rrtype, ruleid, redirector)
        LKEY=""
        func="dns_query"
        aa = []
        if newttl > 0:
            md5 = self.StrToMD5(SKEY)
            LKEY = "DNSFWCACHE:%s" % md5
            results_cache = self.memdb.memcache_get(LKEY)
            if results_cache is not None:
                self.ilog(self.get_linenumber(),func,"%s [HIT] ( Cached DNS )" % domainname)
                cached = unserialize(results_cache)
                for line in cached:
                    self.ilog(self.get_linenumber(), func, "%s [HIT]" % cached[line])
                    aa.append(cached[line])
                return True

        resolver = dns.resolver.Resolver()
        if redirector.find(",") > 0:
            nameservers = redirector.split(",")
            resolver.nameservers = nameservers
        else:
            resolver.nameservers = [redirector]



        resolver.timeout = 4
        resolver.lifetime = 4

        try:
            result = resolver.query(domainname, rrtype)

        except (dns.resolver.NXDOMAIN):
            self.ilog(self.get_linenumber(), func,"ERROR  %s:%s rule:%s DNS Servers [%s] error None of DNS query names exist" % (domainname, rrtype, ruleid, redirector))
            return False

        except (dns.resolver.NoAnswer):
            self.ilog(self.get_linenumber(), func,"ERROR  %s:%s rule:%s DNS Servers [%s] error NoAnswer" % (domainname, rrtype, ruleid, redirector))
            return False

        except (dns.resolver.Timeout):
            self.ilog(self.get_linenumber(), func,"ERROR  %s:%s rule:%s DNS Servers [%s] error Timed-Out" % (domainname, rrtype, ruleid, redirector))
            return False

        except Exception as e:
            self.ilog(self.get_linenumber(),func,"ERROR  %s:%s rule:%s DNS Servers [%s] error %s" % (domainname, rrtype, ruleid,redirector ,e))
            return False

        for item in result.rrset.items:
            aa.append(item.to_text())

        if len(aa) == 0:
            self.ilog(self.get_linenumber(), func, "%s:%s rule:%s no parsed data from targeted servers" % (domainname, rrtype, ruleid))
            return False

        self.dns_query_results = aa
        if newttl > 0:
            self.ilog(self.get_linenumber(), func, "%s [MISS] ( Cached DNS )" % domainname)
            if len(LKEY)>0: self.memdb.memcache_set(LKEY, serialize(aa), newttl)

        return True

    def QUERY_SQL(self, sql, fetchone=False):
        rows = None
        func = "QUERY_SQL"
        try:
            conn = sqlite3.connect('/home/artica/SQLITE/acls.db')
            conn.text_factory = lambda b: b.decode(errors='ignore')
        except Error as e:
            self.zsyslog("[ERROR]: SQL %s" % e)
            return None

        cur = conn.cursor()
        try:
            cur.execute(sql)
            if fetchone: rows = cur.fetchone()
            if not fetchone: rows = cur.fetchall()

        except:
            self.ilog(self.get_linenumber(), func, "[ERROR]: SQL %s" % tb.format_exc())
            conn.close()
            return None

        conn.close()
        if self.OUT: print(rows)
        return rows

    def zsyslog(self,text):
        sfw=syslog
        sfw.openlog("dns-firewall", syslog.LOG_PID)
        sfw.syslog(syslog.LOG_INFO, text)

    def log_filename(self):
        return self.log_filename_round(datetime.now(), timedelta(minutes=10)).strftime('%Y%m%d%H%M')

    def log_filename_round(self, dt=None, dateDelta=timedelta(minutes=1)):
        roundTo = dateDelta.total_seconds()
        if dt is None: dt = datetime.now()
        seconds = (dt - dt.min).seconds
        rounding = (seconds + roundTo / 2) // roundTo * roundTo
        return dt + timedelta(0, rounding - seconds, -dt.microsecond)


